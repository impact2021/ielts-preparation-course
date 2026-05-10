# Stripe Payment Network Error - Fix Documentation

## Problem

Users encountered a "Network error. Please try again." message when selecting a paid course membership instead of seeing the Stripe payment area.

## Root Causes

### 1. Missing Stripe PHP Library (FIXED)
- The `vendor/` directory with the Stripe PHP library was not committed to the repository
- When the server-side code tried to load Stripe via `require_once IELTS_CM_PLUGIN_DIR . 'vendor/autoload.php'`, it failed
- This caused AJAX requests to return 500 errors, triggering the "Network error" message

### 2. Incorrect Stripe Integration Approach (FIXED)
- The original implementation created Payment Intents too early (on membership selection)
- It used `clientSecret` mode instead of `payment` mode with preset amount
- It didn't follow the documented working approach for inline payments

## Solutions Implemented

### Solution 1: Install Stripe PHP Library
- Ran `composer install` to install stripe/stripe-php v19.2.0
- Updated `.gitignore` to allow `vendor/` directory to be committed
- Removed Stripe README containing example test key (security scanning issue)
- Committed vendor directory so plugin works out-of-the-box

### Solution 2: Implement Correct Stripe Payment Flow

#### JavaScript Changes (`assets/js/registration-payment.js`)

**Before (WRONG):**
```javascript
// Created Payment Intent when membership selected
function showPaymentSection(membershipType, price) {
    $.ajax({
        action: 'ielts_create_payment_intent',
        // Created intent without user_id
    });
}

// Used clientSecret mode
elements = stripe.elements({ clientSecret });
```

**After (CORRECT):**
```javascript
// Initialize Elements in payment mode with preset amount
function initializePaymentElement(price) {
    elements = stripe.elements({
        mode: 'payment',  // KEY CHANGE
        amount: Math.round(parseFloat(price) * 100),
        currency: 'usd'
    });
}

// On form submit:
// 1. Validate card with elements.submit()
// 2. Create user account first
// 3. Create Payment Intent with user_id
// 4. Confirm payment inline
// 5. Confirm on server and activate membership
```

#### PHP Changes (`includes/class-stripe-payment.php`)

Added three AJAX endpoints:

1. **`ielts_register_user`** - Creates user account FIRST
   - Validates all fields
   - Creates WordPress user
   - Stores pending membership type
   - Returns user_id

2. **`ielts_create_payment_intent`** - Creates Payment Intent with user_id
   - Accepts user_id from previous step
   - Validates amount against server-side pricing
   - Creates payment record in database
   - Creates Stripe Payment Intent with automatic_payment_methods
   - Returns clientSecret and payment_id

3. **`ielts_confirm_payment`** - Finalizes membership
   - Updates payment record with transaction_id
   - Assigns membership to user
   - Sets expiry date
   - Sends welcome email
   - Returns redirect URL

#### Database Changes (`includes/class-database.php`)

Added `ielts_cm_payments` table:
```sql
CREATE TABLE ielts_cm_payments (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    membership_type varchar(50) NOT NULL,
    amount decimal(10,2) NOT NULL,
    transaction_id varchar(255) DEFAULT NULL,
    payment_status varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## New Payment Flow

1. **User selects paid membership**
   - Payment Element appears immediately with payment form
   - Uses `mode: 'payment'` with preset amount

2. **User fills registration form**
   - Account info (name, email, password)
   - Payment info (card details) - both visible at same time

3. **User submits form**
   - JavaScript validates card details with `elements.submit()`
   - AJAX creates user account → Returns user_id
   - AJAX creates Payment Intent → Returns clientSecret
   - Stripe confirms payment inline → Returns payment_intent_id if successful
   - AJAX confirms payment on server → Activates membership

4. **Success**
   - User redirected to login page
   - Welcome email sent
   - Membership activated

## Key Technical Details

### Why `mode: 'payment'` instead of `clientSecret`?

The Stripe Payment Elements API supports two initialization modes:

1. **Payment Mode** (what we use now):
   ```javascript
   elements = stripe.elements({
       mode: 'payment',
       amount: 4999,  // Amount in cents
       currency: 'usd'
   });
   ```
   - Amount is preset when Elements initializes
   - Payment Intent created later, when user submits
   - Better for inline payments where you want to show card form immediately

2. **ClientSecret Mode** (old approach):
   ```javascript
   elements = stripe.elements({
       clientSecret: 'pi_xxx_secret_yyy'
   });
   ```
   - Requires Payment Intent to exist before showing card form
   - Creates intent too early (before user finishes form)
   - Not ideal for registration flows

### Why `automatic_payment_methods`?

```php
'automatic_payment_methods' => ['enabled' => true]
```

Instead of:
```php
'payment_method_types' => ['card']
```

Benefits:
- Matches how Payment Elements works in payment mode
- Automatically supports all payment methods enabled in Stripe account
- Includes Stripe Link, Apple Pay, Google Pay, and regional methods
- Prevents "cannot be confirmed through the API" errors

### Why Create User First?

Creating the user account before the Payment Intent ensures:
1. We have a user_id to track the payment
2. If payment fails, we can still contact the user
3. User doesn't have to re-enter information
4. Better audit trail for failed payments

### Why Three AJAX Calls?

While it seems like more requests, this approach:
1. Validates each step before proceeding
2. Provides better error messages
3. Allows recovery from failures
4. Follows security best practices (don't trust client-side amounts)

## Testing

To test the fix:

1. **Configure Stripe** (see MEMBERSHIP_QUICK_START.md):
   - Add Stripe test keys in Memberships → Payment Settings
   - Set prices for membership types

2. **Test Payment Flow**:
   - Go to registration page with `[ielts_registration]` shortcode
   - Select a paid membership type
   - Verify payment form appears immediately (no "Network error")
   - Fill in account details
   - Use test card: 4242 4242 4242 4242, any future date, any CVC
   - Submit form
   - Verify successful payment and redirect to login

3. **Check Results**:
   - User created in WordPress
   - Membership assigned
   - Payment recorded in database
   - Welcome email sent

## Files Changed

1. `.gitignore` - Removed `vendor/` exclusion
2. `vendor/` - Added Stripe PHP library v19.2.0 (464 files)
3. `assets/js/registration-payment.js` - Rewrote to use payment mode
4. `includes/class-stripe-payment.php` - Added 3 AJAX endpoints
5. `includes/class-database.php` - Added payments table
6. `MEMBERSHIP_QUICK_START.md` - Added Stripe configuration guide

## Security

- ✅ No secrets committed to repository
- ✅ All API keys configured through WordPress admin
- ✅ Server-side price validation (client can't manipulate amounts)
- ✅ Nonce verification on all AJAX endpoints
- ✅ PCI compliant (Stripe handles all card data)
- ✅ Stripe webhook signature verification

## Compatibility

- PHP 7.4+
- WordPress 5.0+
- Stripe API 2025-12-15.clover
- stripe/stripe-php v19.2.0

## Future Enhancements

Potential improvements:
1. Add subscription support for recurring payments
2. Support for more payment gateways (PayPal completed separately)
3. Payment retry mechanism for failed payments
4. Admin dashboard for payment analytics
5. Refund handling

---

**Version:** 14.2+  
**Fix Date:** January 2026  
**Author:** GitHub Copilot
