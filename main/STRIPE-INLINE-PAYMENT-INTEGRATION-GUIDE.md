# Stripe Inline Payment Integration Guide

## Overview

This guide explains how to implement **inline Stripe payment** on the registration page, allowing users to create an account AND pay for their membership in a single, seamless flow.

## Current vs. Desired Flow

### Current Flow (Two Steps)
1. User registers with email/password → Gets free trial
2. User must separately upgrade to paid membership

### Desired Flow (One Step) ✓
1. User fills registration form
2. User selects membership level (free trial OR paid)
3. If paid selected → Stripe Payment Element appears inline
4. User enters payment details
5. On successful payment → Account created + Paid membership assigned
6. On failed payment → Account NOT created, user can retry

## Architecture

### Components Needed

1. **Frontend (JavaScript)**
   - Stripe.js library
   - Payment Element UI
   - Form state management
   - Client-side validation

2. **Backend (PHP)**
   - Stripe PHP SDK
   - Payment Intent creation endpoint
   - Webhook handler for payment confirmation
   - User creation after payment success

3. **Security**
   - Nonce verification
   - Server-side price validation
   - Webhook signature verification
   - Idempotency keys

## Implementation Steps

### Step 1: Install Stripe PHP SDK

Add Stripe PHP library to your plugin. You can either:

**Option A: Use Composer** (Recommended)
```bash
cd /path/to/plugin
composer require stripe/stripe-php
```

**Option B: Manual installation**
Download from: https://github.com/stripe/stripe-php/releases
Place in: `includes/vendor/stripe-php/`

### Step 2: Create Payment Intent Endpoint

Create a new file: `includes/class-stripe-payment.php`

```php
<?php
/**
 * Stripe Payment Processing
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once IELTS_CM_PLUGIN_DIR . 'vendor/autoload.php';

class IELTS_CM_Stripe_Payment {
    
    public function init() {
        // AJAX endpoint for creating payment intent
        add_action('wp_ajax_nopriv_ielts_create_payment_intent', array($this, 'create_payment_intent'));
        add_action('wp_ajax_ielts_create_payment_intent', array($this, 'create_payment_intent'));
        
        // Webhook handler for payment confirmation
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }
    
    /**
     * Create Payment Intent for registration
     */
    public function create_payment_intent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_payment_intent')) {
            wp_send_json_error('Security check failed', 403);
        }
        
        $membership_type = sanitize_text_field($_POST['membership_type']);
        
        // SECURITY: Validate membership type exists and get server-side price
        $pricing = get_option('ielts_cm_membership_pricing', array());
        if (!isset($pricing[$membership_type])) {
            wp_send_json_error('Invalid membership type', 400);
        }
        
        $price = floatval($pricing[$membership_type]);
        
        // Don't create payment intent for free memberships
        if ($price <= 0) {
            wp_send_json_error('This membership is free', 400);
        }
        
        // Get Stripe secret key
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        if (empty($stripe_secret)) {
            wp_send_json_error('Payment system not configured', 500);
        }
        
        \Stripe\Stripe::setApiKey($stripe_secret);
        
        try {
            // Create Payment Intent
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => intval($price * 100), // Convert to cents
                'currency' => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'membership_type' => $membership_type,
                    'email' => sanitize_email($_POST['email']),
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                ],
            ]);
            
            wp_send_json_success([
                'clientSecret' => $payment_intent->client_secret,
            ]);
            
        } catch (\Exception $e) {
            error_log('Stripe Payment Intent Error: ' . $e->getMessage());
            wp_send_json_error('Payment system error', 500);
        }
    }
    
    /**
     * Register Stripe webhook endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('ielts-cm/v1', '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Webhook has signature verification
        ));
    }
    
    /**
     * Handle Stripe webhook events
     */
    public function handle_webhook($request) {
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        \Stripe\Stripe::setApiKey($stripe_secret);
        
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe_signature');
        
        // Get webhook signing secret from settings
        $webhook_secret = get_option('ielts_cm_stripe_webhook_secret', '');
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhook_secret
            );
        } catch (\Exception $e) {
            error_log('Webhook signature verification failed: ' . $e->getMessage());
            return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 400));
        }
        
        // Handle the event
        if ($event->type === 'payment_intent.succeeded') {
            $payment_intent = $event->data->object;
            $this->handle_successful_payment($payment_intent);
        }
        
        return new WP_REST_Response(['status' => 'success'], 200);
    }
    
    /**
     * Create user and assign membership after successful payment
     */
    private function handle_successful_payment($payment_intent) {
        $metadata = $payment_intent->metadata;
        
        // Extract user data from metadata
        $email = $metadata->email;
        $first_name = $metadata->first_name;
        $last_name = $metadata->last_name;
        $membership_type = $metadata->membership_type;
        
        // Check if user already exists (idempotency)
        if (email_exists($email)) {
            error_log("User already exists for email: $email");
            return;
        }
        
        // Generate username from email
        $email_parts = explode('@', $email);
        $base_username = sanitize_user($email_parts[0], true);
        $username = $base_username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Create user account
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        
        if (is_wp_error($user_id)) {
            error_log('Failed to create user: ' . $user_id->get_error_message());
            return;
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        ));
        
        // Assign paid membership
        update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
        
        // Set expiry date
        $expiry_date = IELTS_CM_Membership::calculate_expiry_date($membership_type);
        update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
        
        // Store payment info
        update_user_meta($user_id, '_ielts_cm_payment_intent_id', $payment_intent->id);
        update_user_meta($user_id, '_ielts_cm_payment_amount', $payment_intent->amount / 100);
        update_user_meta($user_id, '_ielts_cm_payment_date', current_time('mysql'));
        
        // Send welcome email
        wp_new_user_notification($user_id, null, 'user');
        
        error_log("Successfully created user $user_id with membership $membership_type after payment");
    }
}
```

### Step 3: Modify Registration Form

Update `includes/class-shortcodes.php` in the `display_registration()` function:

**Add to the form HTML** (after membership selection dropdown):

```php
<!-- Payment Section (Hidden by default) -->
<div id="ielts-payment-section" style="display: none;">
    <p>
        <label><?php _e('Payment Information', 'ielts-course-manager'); ?></label>
        <div id="payment-element">
            <!-- Stripe Payment Element will be inserted here -->
        </div>
        <div id="payment-message" class="error"></div>
    </p>
</div>
```

**Enqueue Stripe.js and custom script:**

```php
// In the display_registration() function, add:
$stripe_publishable = get_option('ielts_cm_stripe_publishable_key', '');
$pricing = get_option('ielts_cm_membership_pricing', array());

wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
wp_enqueue_script('ielts-registration-payment', IELTS_CM_PLUGIN_URL . 'assets/js/registration-payment.js', array('jquery', 'stripe-js'), IELTS_CM_VERSION, true);

wp_localize_script('ielts-registration-payment', 'ieltsPayment', array(
    'publishableKey' => $stripe_publishable,
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('ielts_payment_intent'),
    'pricing' => $pricing,
));
```

### Step 4: Create Frontend JavaScript

Create file: `assets/js/registration-payment.js`

```javascript
(function($) {
    'use strict';
    
    let stripe;
    let elements;
    let paymentElement;
    
    // Initialize Stripe
    if (typeof Stripe !== 'undefined' && ieltsPayment.publishableKey) {
        stripe = Stripe(ieltsPayment.publishableKey);
    }
    
    // Listen for membership type selection
    $('#ielts_membership_type').on('change', function() {
        const membershipType = $(this).val();
        const price = ieltsPayment.pricing[membershipType] || 0;
        
        // Show/hide payment section based on price
        if (price > 0) {
            showPaymentSection(membershipType, price);
        } else {
            hidePaymentSection();
        }
    });
    
    function showPaymentSection(membershipType, price) {
        const $paymentSection = $('#ielts-payment-section');
        
        // Show the section
        $paymentSection.slideDown();
        
        // Create Payment Intent
        $.ajax({
            url: ieltsPayment.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ielts_create_payment_intent',
                nonce: ieltsPayment.nonce,
                membership_type: membershipType,
                email: $('#ielts_email').val(),
                first_name: $('#ielts_first_name').val(),
                last_name: $('#ielts_last_name').val(),
            },
            success: function(response) {
                if (response.success) {
                    initializePaymentElement(response.data.clientSecret);
                } else {
                    showError(response.data || 'Failed to initialize payment');
                }
            },
            error: function() {
                showError('Network error. Please try again.');
            }
        });
    }
    
    function hidePaymentSection() {
        $('#ielts-payment-section').slideUp();
        if (elements) {
            elements = null;
            paymentElement = null;
        }
    }
    
    function initializePaymentElement(clientSecret) {
        // Create Elements instance
        elements = stripe.elements({ clientSecret });
        
        // Create and mount Payment Element
        paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
    }
    
    // Intercept form submission
    $('form[name="ielts_registration_form"]').on('submit', function(e) {
        const membershipType = $('#ielts_membership_type').val();
        const price = ieltsPayment.pricing[membershipType] || 0;
        
        // If it's a paid membership, handle payment first
        if (price > 0 && stripe && elements) {
            e.preventDefault();
            handlePaymentSubmission();
        }
        // Otherwise, allow normal form submission for free registrations
    });
    
    async function handlePaymentSubmission() {
        setLoading(true);
        
        // Confirm payment with Stripe
        const {error} = await stripe.confirmPayment({
            elements,
            confirmParams: {
                // Return URL after successful payment
                return_url: window.location.href + '?payment=success',
            },
            redirect: 'if_required',
        });
        
        if (error) {
            // Payment failed
            showError(error.message);
            setLoading(false);
        } else {
            // Payment succeeded
            // The webhook will create the user account
            showSuccess('Payment successful! Your account is being created...');
            
            // Redirect to success page
            setTimeout(function() {
                window.location.href = window.location.href + '?registration=success';
            }, 2000);
        }
    }
    
    function showError(message) {
        $('#payment-message').text(message).show();
    }
    
    function showSuccess(message) {
        $('#payment-message').removeClass('error').addClass('success').text(message).show();
    }
    
    function setLoading(isLoading) {
        if (isLoading) {
            $('#ielts_register_submit').prop('disabled', true).val('Processing...');
        } else {
            $('#ielts_register_submit').prop('disabled', false).val('Register');
        }
    }
    
})(jQuery);
```

### Step 5: Update Registration Form Processing

Modify `includes/class-shortcodes.php` registration processing to handle two paths:

```php
// In display_registration() function, modify the form processing:

if (isset($_POST['ielts_register_submit'])) {
    // ... existing nonce and validation ...
    
    $membership_type = isset($_POST['ielts_membership_type']) ? sanitize_text_field($_POST['ielts_membership_type']) : '';
    
    // Get pricing
    $pricing = get_option('ielts_cm_membership_pricing', array());
    $price = isset($pricing[$membership_type]) ? floatval($pricing[$membership_type]) : 0;
    
    // PATH 1: Free registration (trials and free memberships)
    if ($price <= 0) {
        // Existing registration logic
        // ... create user, assign membership, etc.
    }
    // PATH 2: Paid registration
    else {
        // For paid memberships, registration is handled by webhook after payment
        // This form submission shouldn't happen because JavaScript handles it
        // But add this as a fallback
        $errors[] = __('Please complete payment to create your account.', 'ielts-course-manager');
    }
}
```

### Step 6: Configure Stripe Webhook

1. In your Stripe Dashboard:
   - Go to Developers → Webhooks
   - Click "Add endpoint"
   - URL: `https://yoursite.com/wp-json/ielts-cm/v1/stripe-webhook`
   - Events to send: `payment_intent.succeeded`
   - Copy the "Signing secret"

2. In WordPress Admin:
   - Go to Memberships → Payment Settings
   - Add new field for "Stripe Webhook Secret"
   - Paste the signing secret from Stripe

### Step 7: Update Payment Settings Page

Add webhook secret field to `includes/class-membership.php`:

```php
// In display_payment_settings() function, add:

register_setting('ielts_membership_payment', 'ielts_cm_stripe_webhook_secret');

// Add this field in the HTML:
<tr>
    <th scope="row"><?php _e('Stripe Webhook Secret', 'ielts-course-manager'); ?></th>
    <td>
        <input type="password" name="ielts_cm_stripe_webhook_secret" 
               value="<?php echo esc_attr(get_option('ielts_cm_stripe_webhook_secret', '')); ?>" 
               class="regular-text">
        <p class="description">
            <?php _e('Get this from Stripe Dashboard → Developers → Webhooks. Required for payment verification.', 'ielts-course-manager'); ?>
        </p>
    </td>
</tr>
```

## Testing

### Test Mode

1. Use Stripe test keys (starts with `pk_test_` and `sk_test_`)
2. Test card numbers:
   - Success: `4242 4242 4242 4242`
   - Requires authentication: `4000 0027 6000 3184`
   - Declined: `4000 0000 0000 0002`
3. Use any future expiry date
4. Use any 3-digit CVC
5. Use any 5-digit ZIP code

### Test Flow

1. Go to registration page
2. Fill in name and email
3. Select a PAID membership type
4. Payment section should appear
5. Enter test card `4242 4242 4242 4242`
6. Submit form
7. Check webhook was received in Stripe Dashboard
8. Verify user account was created
9. Verify membership was assigned
10. Verify payment recorded in user meta

## Security Considerations

✅ **Implemented:**
- Nonce verification for AJAX requests
- Server-side price validation (never trust client)
- Webhook signature verification
- Sanitization of all inputs
- Idempotency (check if user exists before creating)

⚠️ **Additional Recommendations:**
- Use HTTPS only
- Implement rate limiting on payment endpoints
- Log all payment attempts for audit
- Add CAPTCHA to prevent bot abuse
- Implement email verification before membership activation

## Error Handling

1. **Payment fails**: Show error, allow retry
2. **Webhook fails**: Payment succeeded but account not created
   - Add admin page to manually process "orphaned" payments
   - Check Stripe for successful payments without matching users
3. **Network issues**: Show user-friendly message, save form data
4. **Duplicate email**: Check before creating payment intent

## Future Enhancements

1. **Support for other currencies**
2. **Proration for upgrades**
3. **Subscription model** (recurring payments)
4. **Coupons and discounts**
5. **Split payment** (pay in installments)
6. **Refund handling**
7. **Admin dashboard** for payment history

## Files Modified/Created

### New Files:
- `includes/class-stripe-payment.php` - Payment processing class
- `assets/js/registration-payment.js` - Frontend payment handling
- `main/STRIPE-INLINE-PAYMENT-INTEGRATION-GUIDE.md` - This guide

### Modified Files:
- `includes/class-shortcodes.php` - Updated registration form
- `includes/class-membership.php` - Added webhook secret setting
- `ielts-course-manager.php` - Include new payment class

## Support

For issues with this integration:
1. Check Stripe logs in Dashboard → Developers → Logs
2. Check WordPress error logs
3. Test with Stripe test mode first
4. Verify webhook endpoint is accessible (not blocked by firewall)

---

**Version:** 1.0  
**Last Updated:** January 2026  
**Author:** IELTS Course Manager Development Team
