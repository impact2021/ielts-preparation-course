# Stripe Inline Payment Integration Guide

## Overview

This document explains how we successfully integrated Stripe's inline payment form on the same page as the registration form, allowing users to complete account creation and payment in a single, seamless flow without being redirected to an external checkout page.

## The Challenge

The traditional approach to payment processing involves creating a user account first, then redirecting them to a separate payment page (either Stripe Checkout or PayPal). This creates friction in the user experience and can lead to abandoned registrations. Users expect a modern, streamlined experience where they can enter their information and payment details all at once.

## The Solution: Stripe Payment Elements with Payment Intents

We implemented Stripe's Payment Elements API in combination with Payment Intents to create an inline payment experience. Here's how it works:

### Key Components

**1. Frontend Payment Element Initialization (JavaScript)**

The critical breakthrough was initializing Stripe Elements immediately when the page loads, not after form submission. We created a dedicated function `initializeRegistrationStripeElements()` that:

- Validates that Stripe.js is loaded and a valid publishable key is available
- Retrieves the membership amount from the form
- Creates a Stripe Elements instance in "payment" mode with the amount preset
- Mounts the Payment Element to a div on the registration form
- Uses automatic payment methods to support cards, digital wallets, and regional payment options

```javascript
function initializeRegistrationStripeElements() {
    // Get the membership amount from the hidden form field
    const membershipAmount = $('input[name="membership_amount"]').val();
    
    if (!membershipAmount || parseFloat(membershipAmount) <= 0) {
        // Show error if amount is invalid
        return;
    }
    
    // Create Stripe Elements with payment mode
    elements = stripe.elements({
        mode: 'payment',
        amount: Math.round(parseFloat(membershipAmount) * 100), // Amount in cents
        currency: 'usd',
        appearance: {
            theme: 'stripe',
            variables: { colorPrimary: '#0073aa' }
        }
    });
    
    // Create and mount the payment element
    paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');
}
```

This function is called immediately on page load if Stripe is selected, making the card input fields visible right away. Users can see and interact with all form fields (account info + payment info) simultaneously.

**2. Server-Side Payment Intent Creation (PHP)**

When the user submits the registration form, we use AJAX to create a Payment Intent on the server before processing the payment. The Payment Intent is Stripe's way of tracking a payment from creation through completion. Our implementation:

- Creates the user account first (so we have a user_id to associate with the payment)
- Sends an AJAX request to `create_payment_intent` action
- The server creates a Payment Intent with Stripe's API, passing the amount, currency, and metadata
- Returns a `clientSecret` to the frontend
- Uses `automatic_payment_methods` instead of explicit payment method types to support all configured payment options

```php
// In class IELTS_MS_Stripe_Gateway (extends IELTS_MS_Payment_Gateway)
public function create_payment_intent() {
    // Verify AJAX nonce for security
    check_ajax_referer('ielts_ms_nonce', 'nonce');
    
    // Get and validate input parameters from POST
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $amount = floatval($_POST['amount']);
    $duration_days = intval($_POST['duration_days']);
    $payment_type = sanitize_text_field($_POST['payment_type']);
    $plan_key = sanitize_text_field($_POST['plan_key']);
    
    // Get Stripe secret key from WordPress options
    $secret_key = get_option('ielts_ms_stripe_secret_key', '');
    
    // Create pending payment record in database
    $payment_id = $this->record_payment($user_id, $amount, $duration_days, null, 'pending', $payment_type);
    
    // Create Payment Intent with Stripe API
    $stripe_data = array(
        'amount' => intval($amount * 100), // Convert to cents
        'currency' => 'usd',
        'automatic_payment_methods' => array('enabled' => true),
        'metadata' => array(
            'user_id' => $user_id,
            'duration_days' => $duration_days,
            'payment_id' => $payment_id,
            'is_registration' => 'true'
        )
    );
    
    // Make API request to Stripe
    $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $secret_key,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
        'body' => $this->build_stripe_query($stripe_data)
    ));
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    // API call returns clientSecret
    wp_send_json_success(array(
        'clientSecret' => $body['client_secret'],
        'payment_id' => $payment_id
    ));
}
```

**3. Payment Confirmation Flow**

Once we have the Payment Intent client secret, we immediately confirm the payment using the card details already entered by the user. This is the magic that makes it "inline" – there's no redirect, no second page, just a single form submission:

```javascript
// Confirm the payment using already-entered card details
stripe.confirmPayment({
    elements: elements,
    clientSecret: response.data.clientSecret,
    confirmParams: {
        return_url: window.location.origin + window.location.pathname
    },
    redirect: 'if_required'
}).then(function(result) {
    if (result.error) {
        // Handle error
    } else if (result.paymentIntent.status === 'succeeded') {
        // Payment successful - confirm on server
        confirmPaymentOnServer(result.paymentIntent.id, paymentId);
    }
});
```

The `redirect: 'if_required'` parameter is crucial – it only redirects if required for 3D Secure authentication, otherwise the payment completes inline.

**4. Final Confirmation and Membership Activation**

After Stripe confirms the payment succeeded, we send one final AJAX request to our server to:

- Update the payment record in the database
- Create/activate the membership
- Send welcome email
- Redirect user to login or account page

```php
// In class IELTS_MS_Stripe_Gateway (extends IELTS_MS_Payment_Gateway)
public function confirm_payment() {
    // Verify AJAX nonce for security
    check_ajax_referer('ielts_ms_nonce', 'nonce');
    
    // Get parameters from AJAX request
    $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);
    $payment_id = intval($_POST['payment_id']);
    
    // Get payment details from database
    global $wpdb;
    $table = IELTS_MS_Database::get_payments_table();
    $payment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $payment_id
    ));
    
    // Update payment status in database
    $wpdb->update($table, array(
        'payment_status' => 'completed',
        'transaction_id' => $payment_intent_id
    ), array('id' => $payment_id));
    
    // Get user's enrollment type preference
    $enrollment_type = get_user_meta($payment->user_id, 'ielts_ms_enrollment_type', true);
    
    // Create membership for the user
    $membership = new IELTS_MS_Membership();
    $membership->create_membership($payment->user_id, $payment->duration_days, $payment_id, $enrollment_type, false);
    
    // Clean up registration pending flags
    delete_user_meta($payment->user_id, 'ielts_ms_registration_pending');
    delete_user_meta($payment->user_id, 'ielts_ms_registration_timestamp');
    
    // Send welcome email to new user
    wp_new_user_notification($payment->user_id, null, 'user');
    
    // Return success with redirect URL
    wp_send_json_success(array(
        'redirect' => get_permalink(get_option('ielts_ms_login_page_id'))
    ));
}
```

## Critical Implementation Details

### 1. Using Payment Mode vs Setup Mode

We use `mode: 'payment'` when initializing Elements because we're collecting a one-time payment, not setting up a subscription. This tells Stripe to configure the Payment Element for immediate charge.

### 2. Automatic Payment Methods

Instead of explicitly defining payment methods with `payment_method_types: ['card']`, we use `automatic_payment_methods: array('enabled' => true)`. This is essential because:

- It matches how Payment Elements works in payment mode
- It automatically supports all payment methods enabled in your Stripe account
- It includes Stripe Link, Apple Pay, Google Pay, and regional payment methods
- It prevents the "cannot be confirmed through the API" error

### 3. Registration Flow Order

The order of operations is crucial for inline payment:

1. User fills out registration form (account info + payment info both visible)
2. JavaScript validates card details using `elements.submit()`
3. AJAX creates user account on server
4. AJAX creates Payment Intent with user's ID
5. JavaScript immediately confirms payment with Stripe
6. AJAX confirms payment on server and activates membership
7. User redirected to success page

This ensures we have a user_id before creating the Payment Intent (important for tracking) while still maintaining the inline experience.

### 4. Variable Separation

To avoid conflicts, we maintain separate Stripe Elements instances for different pages:

- Registration page: `elements`, `paymentElement`
- Account page: `accountElements`, `accountPaymentElement`

Each instance is only initialized when needed and never mixed.

### 5. Error Handling

We display user-friendly error messages at multiple points:

- If Stripe.js fails to load
- If Elements fails to initialize
- If card validation fails
- If payment confirmation fails

Error messages are shown directly in the UI, not just in the console.

## Template Structure

The registration form template (`templates/register-form.php`) includes:

```php
<!-- Stripe Payment Element Container (for inline payment) -->
<?php if (get_option('ielts_ms_stripe_enabled', true)): ?>
<div id="stripe-payment-section" class="stripe-payment-section">
    <div class="ielts-ms-form-group">
        <label><?php _e('Card Details', 'ielts-membership-system'); ?></label>
        <div id="payment-element" class="stripe-payment-element">
            <!-- Stripe Elements will be inserted here -->
        </div>
        <div id="payment-errors" class="ielts-ms-message" style="display: none;"></div>
    </div>
</div>
<?php endif; ?>
```

The `#payment-element` div is where Stripe injects the secure payment form. This div is part of the registration form HTML, making it truly "inline" with the account creation fields.

## Benefits of This Approach

1. **Single-Page Experience**: Users complete registration and payment in one flow without redirects
2. **Better Conversion**: Fewer steps mean less abandonment
3. **PCI Compliance**: Stripe handles all sensitive card data; it never touches our server
4. **Modern UX**: Supports digital wallets, autofill, and Stripe Link
5. **Flexibility**: Easy to toggle between inline and redirect payment modes
6. **Security**: 3D Secure authentication automatically triggered when needed

## What Makes This Work

The key insight is that Stripe's Payment Elements API is designed for exactly this use case. By using:

- **Payment Elements** (not legacy Elements or Checkout)
- **Payment Intents** (not Charges API)
- **Payment mode** (not setup mode)
- **Automatic payment methods** (not explicit payment method types)

We get a modern, secure, inline payment experience that integrates seamlessly with our registration form.

The payment fields appear directly on our page, users enter their card details alongside their account information, and everything is submitted together without leaving the page (except for optional 3D Secure authentication if required by the card).
