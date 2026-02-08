# Hybrid Mode Code Purchase Fix - Version 15.28

## Problem Statement

In HYBRID SITE mode, codes were not being properly tracked when partners purchased them. The debug panel showed:
- Total Codes in Database: 0
- Codes Created by Your Org: 0
- Last Payment: None found

This indicated a critical issue with either code creation or payment tracking.

## Root Cause Analysis

After thorough investigation of the codebase, two bugs were identified:

### Bug #1: Incorrect Database Column Name in Debug Query
**Location**: `includes/class-access-codes.php` line 1793

**Issue**: The debug panel query was using a non-existent column name:
```php
// WRONG - payment_date doesn't exist
"SELECT * FROM $payment_table WHERE user_id = %d ORDER BY payment_date DESC LIMIT 1"

// Table actually has created_at column (defined in class-stripe-payment.php line 67)
```

**Impact**: Even when payments existed in the database, the debug panel would show "Last Payment: None found" because the query would fail silently.

### Bug #2: Missing Payment Logging in PayPal Flow
**Location**: `includes/class-access-codes.php` in `ajax_capture_paypal_code_order()`

**Issue**: The PayPal code purchase flow generated codes successfully but never logged the payment to the database.

**Comparison**:
- Stripe webhook (`class-stripe-payment.php` lines 1223-1233): ✅ Logs payment
- PayPal capture (`class-access-codes.php`): ❌ Does NOT log payment

**Impact**: PayPal code purchases would create codes but leave no payment record in the database.

## Solutions Implemented

### Fix #1: Corrected Database Column References
**File**: `includes/class-access-codes.php`

Changed query from:
```php
ORDER BY payment_date DESC
```

To:
```php
ORDER BY created_at DESC
```

Also updated the display to use `$recent_payment->created_at` instead of `$recent_payment->payment_date`.

### Fix #2: Added Payment Logging to PayPal Flow
**File**: `includes/class-access-codes.php` after line 3128

Added comprehensive payment logging matching the Stripe webhook pattern:
```php
// Log the payment to database
$payment_table = $wpdb->prefix . 'ielts_cm_payments';
$insert_result = $wpdb->insert(
    $payment_table,
    array(
        'user_id' => $user_id,
        'membership_type' => 'access_codes_' . $quantity,
        'amount' => $amount,
        'transaction_id' => $order_id,
        'payment_status' => 'completed'
    ),
    array('%d', '%s', '%f', '%s', '%s')
);
```

### Enhancement: Comprehensive Debug Logging

Added detailed logging throughout the payment flows to make future debugging easier:

**Stripe Flow** (`class-stripe-payment.php`):
- Logs when `create_code_purchase_payment_intent()` is called
- Tracks permission checks and validation failures
- Logs user ID and organization ID

**PayPal Flow** (`class-access-codes.php`):
- Logs when `ajax_create_paypal_code_order()` is called
- Logs when `ajax_capture_paypal_code_order()` is called
- Tracks order capture and code generation
- Logs organization ID resolution

## Code Flow Analysis

### Stripe Payment Flow
1. User clicks "Purchase Codes" with Stripe payment
2. `create_code_purchase_payment_intent()` creates payment intent with metadata
3. Stripe processes payment and sends webhook to `/wp-json/ielts-cm/v1/stripe-webhook`
4. Webhook handler calls `handle_code_purchase_payment()`
5. Codes are created with `created_by = partner_org_id`
6. Payment is logged to database ✅
7. Confirmation email sent

### PayPal Payment Flow
1. User clicks "Purchase Codes" with PayPal payment
2. `ajax_create_paypal_code_order()` creates PayPal order
3. User approves payment on PayPal
4. `ajax_capture_paypal_code_order()` captures the payment
5. Codes are created with `created_by = partner_org_id`
6. **Payment is NOW logged to database** ✅ (FIXED)
7. Confirmation email sent

## Testing Recommendations

### Manual Testing
1. **Stripe Code Purchase**:
   - Purchase codes as a partner user with org ID 2
   - Verify codes appear in dashboard
   - Check debug panel shows "Last Payment: [details]"
   - Check database: `SELECT * FROM wp_ielts_cm_payments ORDER BY created_at DESC LIMIT 5`

2. **PayPal Code Purchase**:
   - Purchase codes as a partner user with org ID 2
   - Verify codes appear in dashboard
   - Check debug panel shows "Last Payment: [details]"
   - Check database for payment record

3. **Organization Isolation**:
   - Create two partner users with different org IDs (e.g., 1 and 2)
   - Each purchases codes
   - Verify each user only sees their own organization's codes

### Database Verification
```sql
-- Check payment table structure
DESCRIBE wp_ielts_cm_payments;
-- Should show: id, user_id, membership_type, amount, transaction_id, payment_status, created_at, updated_at

-- Check recent payments
SELECT * FROM wp_ielts_cm_payments ORDER BY created_at DESC LIMIT 10;

-- Check access codes
SELECT code, course_group, created_by, status, created_date 
FROM wp_ielts_cm_access_codes 
ORDER BY created_date DESC LIMIT 10;
```

### Log Verification
Check WordPress error logs for entries like:
```
IELTS Stripe: create_code_purchase_payment_intent CALLED
IELTS Stripe: Creating payment intent for user [ID]
IELTS Webhook: handle_code_purchase_payment START - Payment Intent ID: [ID]
IELTS Webhook: Partner Org ID: [org_id]
IELTS Webhook: Code created successfully: [CODE]
SUCCESS: Payment logged to database for user [ID]

IELTS PayPal: ajax_create_paypal_code_order CALLED
IELTS PayPal: Creating PayPal order for user [ID]
IELTS PayPal: ajax_capture_paypal_code_order CALLED
IELTS PayPal: Payment captured successfully - Generating [N] codes
SUCCESS: PayPal payment logged to database for user [ID]
```

## Impact on Other Site Types

### Non-Hybrid Mode Sites
**No Impact** ✅

The changes only affect:
1. Debug panel display (shows correct payment data regardless of mode)
2. PayPal payment logging (now works in all modes)
3. Debug logging (helpful in all modes)

All changes are backward compatible and don't alter core code generation logic.

### Hybrid Mode Sites
**Positive Impact** ✅

- Payment tracking now works correctly for both Stripe and PayPal
- Debug panel accurately reflects payment status
- Enhanced logging makes troubleshooting easier
- No changes to organization isolation logic

## Files Modified

1. `ielts-course-manager.php`
   - Version updated from 15.27 to 15.28

2. `includes/class-access-codes.php`
   - Fixed payment query column name (line ~1793)
   - Added payment logging to PayPal capture (after line ~3128)
   - Added debug logging to PayPal functions

3. `includes/class-stripe-payment.php`
   - Added debug logging to code purchase payment intent creation

4. `README.md`
   - Added changelog section
   - Updated version number

## Version Information

- **Previous Version**: 15.27
- **New Version**: 15.28
- **Release Date**: 2026-02-08
- **Compatibility**: WordPress 5.8+, PHP 7.2+

## Summary

This release fixes critical bugs in the hybrid mode code purchase system that prevented proper payment tracking. The fixes ensure that both Stripe and PayPal code purchases are correctly logged to the database, making the debug panel accurate and enabling proper audit trails. Enhanced logging throughout the payment flows will make future troubleshooting significantly easier.

**Key Benefits**:
- ✅ Debug panel now shows accurate payment information
- ✅ PayPal payments properly tracked in database
- ✅ Comprehensive logging for easier troubleshooting
- ✅ No negative impact on existing functionality
- ✅ Backward compatible with all site types
