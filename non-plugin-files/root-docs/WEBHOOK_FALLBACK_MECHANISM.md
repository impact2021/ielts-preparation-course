# Webhook Fallback Mechanism - February 2026

## Problem
Hybrid site partners were unable to purchase access codes because webhook signature verification was failing. Even though payments were successfully processed by Stripe, codes were never created because the webhook handler couldn't verify the signature.

The error shown in debug information:
```
verification_failed | N/A | Amount: $0 | 2026-02-18 09:39:56
Error: Signature verification failed: No signatures found matching the expected signature for payload
```

## Why This Happens
Webhook signature verification can fail for various reasons:
1. **Webhook not configured** - Webhook endpoint not set up in Stripe Dashboard
2. **Wrong webhook secret** - Incorrect signing secret in WordPress settings
3. **Server configuration** - Headers being stripped by firewall, proxy, CDN, or web server
4. **SSL/TLS issues** - Certificate problems preventing Stripe from connecting
5. **Endpoint blocking** - Security plugins or server rules blocking webhook endpoint

## Solution: Automatic Fallback Mechanism

Instead of relying solely on webhooks, the system now uses a **hybrid approach**:

### Primary Method: Webhooks (Preferred)
- Fast and efficient
- Real-time processing
- Low server load

### Fallback Method: Direct Status Polling (New)
- Activates automatically when payment succeeds
- Polls Stripe API directly to check payment status
- Creates codes/applies extensions even if webhooks fail
- No configuration needed - works automatically

## How It Works

### Step-by-Step Flow

1. **User initiates purchase**
   - Clicks "Complete Payment & Purchase Codes"
   - Payment intent created on server

2. **Stripe processes payment**
   - User enters card details
   - Stripe confirms payment
   - Returns payment result to client

3. **Webhook processing (Primary)**
   - Stripe sends webhook to server
   - If signature verifies → codes created immediately
   - If verification fails → webhook logs error

4. **Fallback polling (Automatic)**
   - Client waits 3 seconds (gives webhook time to process)
   - Then polls payment status every 2 seconds
   - Makes up to 10 attempts (20 seconds total)
   - Checks payment status directly with Stripe API

5. **Code creation via fallback**
   - If payment succeeded → processes immediately
   - Creates codes or applies extension
   - Includes idempotency check (won't duplicate if webhook also succeeded)

6. **User feedback**
   - Shows "Processing..." while checking
   - Shows success when complete
   - Refreshes page to display new codes

## Technical Details

### New AJAX Endpoint
```php
// File: includes/class-stripe-payment.php
public function check_payment_status()
```

**What it does:**
1. Verifies user is logged in
2. Validates nonce for security
3. Retrieves payment intent from Stripe API
4. Verifies payment belongs to current user
5. Checks if payment was already processed (idempotency)
6. Processes payment if successful:
   - For code purchases → calls `handle_code_purchase_payment()`
   - For extensions → calls `handle_extension_payment()`

### Client-Side Polling

**Code Purchase (class-access-codes.php)**
```javascript
function checkPaymentStatus() {
    checkAttempts++;
    
    $.ajax({
        url: admin_url,
        type: 'POST',
        data: {
            action: 'ielts_cm_check_payment_status',
            payment_intent_id: paymentIntentId,
            nonce: nonce
        },
        success: function(response) {
            if (response.data.status === 'completed') {
                // Success! Reload page
            } else if (checkAttempts < maxAttempts) {
                // Try again in 2 seconds
            }
        }
    });
}
```

**Course Extension (class-shortcodes.php)**
- Same polling logic
- Adapted for extension context

## Security

### Protections in Place
1. **Nonce verification** - Prevents CSRF attacks
2. **User authentication** - Must be logged in
3. **Payment ownership** - Verifies payment belongs to user
4. **Idempotency** - Prevents duplicate processing
5. **Stripe API verification** - Confirms payment status with Stripe

### What Cannot Be Exploited
- Cannot check other users' payments (ownership check)
- Cannot process fake payments (Stripe API validates)
- Cannot bypass payment (must pay through Stripe first)
- Cannot trigger double processing (idempotency check)

## Benefits

### For Users
✅ **Purchases complete successfully** even with webhook issues
✅ **No manual intervention** required
✅ **Clear feedback** about payment status
✅ **Automatic retry** if temporary issues occur

### For Admins
✅ **No configuration changes** needed
✅ **Works immediately** on all sites
✅ **Backward compatible** with existing webhook setup
✅ **Reduces support requests** for failed purchases

### Technical
✅ **Idempotent** - Safe to process multiple times
✅ **Resilient** - Works despite webhook failures
✅ **Fast** - 2-second polling interval
✅ **Bounded** - Max 10 attempts, then gives clear message

## User Experience

### Success Case (Webhook Works)
```
Payment successful! Processing your order...
[3 seconds delay]
✓ Payment successful! Your access codes have been created. Refreshing...
[Page reloads with new codes visible]
```

### Success Case (Webhook Fails, Fallback Works)
```
Payment successful! Processing your order...
[Polling starts]
[2-4 seconds delay]
✓ Payment successful! Your access codes have been created. Refreshing...
[Page reloads with new codes visible]
```

### Edge Case (Webhook and Fallback Both Slow)
```
Payment successful! Processing your order...
[Polling continues]
[After 20 seconds]
⚠ Payment successful but processing is taking longer than expected.
   Please refresh the page in a moment to see your codes.
```

## Testing

### How to Test

**Scenario 1: Working Webhooks**
1. Configure webhook properly in Stripe
2. Make a test purchase
3. Observe webhook processes first (fast)
4. Fallback doesn't need to run

**Scenario 2: Broken Webhooks**
1. Don't configure webhook OR use wrong secret
2. Make a test purchase
3. Payment succeeds in Stripe
4. Fallback kicks in after 3 seconds
5. Codes created successfully via polling

**Scenario 3: Slow Webhooks**
1. Configure webhook with delays
2. Make a test purchase
3. Fallback starts polling
4. Either webhook or fallback completes first
5. Idempotency prevents double processing

### Expected Logs

**Webhook Success (Primary)**
```
IELTS Stripe Webhook: Received webhook request
IELTS Stripe Webhook: Retrieved signature using method: $_SERVER
IELTS Stripe Webhook: Successfully verified signature
IELTS Webhook: handle_code_purchase_payment START
IELTS Webhook: Successfully created 5/5 access codes
```

**Fallback Success (Webhook Failed)**
```
IELTS Stripe: check_payment_status CALLED
IELTS Stripe: Checking payment status for intent pi_xxxxx
IELTS Stripe: Payment intent status: succeeded
IELTS Stripe: Processing code purchase via fallback mechanism
IELTS Webhook: handle_code_purchase_payment START
IELTS Webhook: Successfully created 5/5 access codes
```

**Idempotency (Both Attempted)**
```
IELTS Stripe: check_payment_status CALLED
IELTS Stripe: Payment already processed (idempotency check)
```

## Troubleshooting

### Codes Still Not Created

**Check 1: Was payment successful in Stripe?**
- Log into Stripe Dashboard
- Check Payments tab
- Verify payment shows as "Succeeded"

**Check 2: Check WordPress error logs**
```bash
tail -f /path/to/wp-content/debug.log | grep "IELTS"
```

**Check 3: Check browser console**
- Open Developer Tools (F12)
- Look for AJAX errors
- Check network tab for failed requests

**Check 4: Verify Stripe API key**
- Settings → Hybrid Site Settings
- Ensure Secret Key is set
- Ensure it matches Stripe dashboard

### Common Issues

**Issue: "Payment system not configured"**
- Solution: Add Stripe Secret Key in settings

**Issue: "Security check failed"**
- Solution: Refresh page and try again (nonce expired)

**Issue: "Payment verification failed"**
- Solution: Payment belongs to different user

**Issue: Polling times out**
- Solution: Check server errors, Stripe might be down

## Migration from Old System

### What Changed
- **Before**: Only webhooks could create codes
- **After**: Webhooks + fallback polling

### Backward Compatibility
✅ Existing webhook setup still works
✅ No breaking changes to API
✅ No configuration migration needed
✅ Old code purchases unaffected

### Recommended Actions
1. ✅ Update plugin to latest version
2. ✅ Test a purchase
3. ⚠️ Still recommended to configure webhooks properly (for best performance)
4. ℹ️ Monitor logs to see which method is being used

## Future Enhancements

Potential improvements for future versions:

1. **Admin notification** - Alert when fallback is frequently used
2. **Health check** - Endpoint to test webhook configuration
3. **Retry webhook** - Button to manually retry failed webhooks
4. **Extended polling** - Configurable timeout for slow servers
5. **Webhook testing** - Built-in tool to send test webhooks

## Related Documentation

- `WEBHOOK_SIGNATURE_VERIFICATION_FIX.md` - Details on signature retrieval methods
- `WEBHOOK_TROUBLESHOOTING.md` - General webhook troubleshooting
- `HYBRID_SITE_ACCESS_CODE_FIX_SUMMARY.md` - Previous webhook fixes

## Version Information

- **Implemented in**: Version 15.52 (February 2026)
- **Fixes issue**: Codes not created when webhooks fail
- **Affects**: Hybrid sites with code purchasing enabled
- **Severity**: Critical (blocks all purchases when webhooks fail)
- **Impact**: High (enables purchases to complete despite webhook issues)

---

**Note**: While this fallback ensures purchases complete successfully, properly configuring webhooks is still recommended for optimal performance. The fallback adds a 3-20 second delay compared to instant webhook processing.
