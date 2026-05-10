# Webhook Signature Verification Fix - Testing Guide

## Overview
This guide helps test the enhanced webhook signature verification fix for the hybrid site. The fix implements a comprehensive fallback chain to retrieve the Stripe-Signature header across all server configurations.

## Prerequisites
- Access to WordPress admin panel
- Access to Stripe Dashboard
- Access to server error logs
- Test Stripe account or ability to use live mode carefully

## Testing Steps

### 1. Verify Webhook Configuration

**In WordPress Admin:**
1. Navigate to **Hybrid Site Settings**
2. Scroll to **Stripe Settings** section
3. Verify the following are configured:
   - ✅ Stripe Publishable Key
   - ✅ Stripe Secret Key
   - ✅ Stripe Webhook Secret
4. Note the webhook URL displayed (e.g., `https://yoursite.com/wp-json/ielts-cm/v1/stripe-webhook`)

**In Stripe Dashboard:**
1. Log in to [Stripe Dashboard](https://dashboard.stripe.com/)
2. Go to **Developers** → **Webhooks**
3. Verify your webhook endpoint exists with:
   - Correct URL matching WordPress settings
   - Event: `payment_intent.succeeded` enabled
   - Status: Active

### 2. Test Webhook with Stripe Test Event

**Send Test Webhook:**
1. In Stripe Dashboard → **Developers** → **Webhooks**
2. Click on your webhook endpoint
3. Click **Send test webhook**
4. Select event type: `payment_intent.succeeded`
5. Click **Send test webhook**

**Check WordPress Error Logs:**

Look for one of these success patterns:

```
✅ SUCCESS - Primary method works:
IELTS Stripe Webhook: Received webhook request
IELTS Stripe Webhook: Retrieved signature using method: get_header
IELTS Stripe Webhook: Successfully verified signature for event type: payment_intent.succeeded
```

```
✅ SUCCESS - Fallback method works ($_SERVER):
IELTS Stripe Webhook: Received webhook request
IELTS Stripe Webhook: Retrieved signature using method: $_SERVER
IELTS Stripe Webhook: Successfully verified signature for event type: payment_intent.succeeded
```

```
✅ SUCCESS - Alternative fallback (getallheaders):
IELTS Stripe Webhook: Received webhook request
IELTS Stripe Webhook: Retrieved signature using method: getallheaders
IELTS Stripe Webhook: Successfully verified signature for event type: payment_intent.succeeded
```

**If Still Failing:**

If you see this, it indicates a deeper configuration issue:
```
❌ FAILURE:
IELTS Stripe Webhook: Received webhook request
IELTS Stripe Webhook: ERROR - Signature header NOT FOUND with any method
IELTS Stripe Webhook: Available headers: Host, Content-Type, Content-Length, User-Agent
```

**Action Required:** The available headers list will help diagnose:
- If "Stripe-Signature" is in the list → Case sensitivity issue (should be fixed by code)
- If list is very short → Server/proxy is stripping headers
- If no list appears → Server configuration prevents header access entirely

### 3. Test Real Access Code Purchase

**Make Test Purchase:**
1. Log in to the hybrid site as a partner admin user
2. Navigate to **Access Codes** page
3. Click **Purchase Codes** button
4. Fill in purchase form:
   - Quantity: 1 (minimum for testing)
   - Payment details: Use Stripe test card `4242 4242 4242 4242`
5. Complete purchase

**Verify Success:**

**Immediate Checks:**
- ✅ Payment confirmation shown
- ✅ Browser doesn't show error

**In Access Codes Page:**
- ✅ New code(s) appear in the table
- ✅ "Codes Created by Your Org" count increased

**In Debug Information:**
- ✅ "Last Payment" shows the payment you just made
- ✅ "Recent Webhook Events" shows successful event with green checkmark (✓)

**In Error Logs:**
```
IELTS Stripe Webhook: Received webhook request
IELTS Stripe Webhook: Retrieved signature using method: [method_name]
IELTS Stripe Webhook: Successfully verified signature for event type: payment_intent.succeeded
IELTS Stripe Webhook: Processing payment_intent.succeeded event
IELTS Webhook: handle_code_purchase_payment START
IELTS Webhook: Creating access codes for org_id: [your_org_id], quantity: 1
```

### 4. Monitor Webhook Delivery in Stripe

**In Stripe Dashboard:**
1. Go to **Developers** → **Webhooks**
2. Click on your webhook endpoint
3. Check recent deliveries:
   - ✅ Status: `succeeded` (green)
   - ✅ Response code: `200`
   - ⏱️ Response time: < 2 seconds typically

**If Status Shows Failed:**
- Click on the failed event
- Check "Response" tab for error details
- Match error with WordPress logs to diagnose

### 5. Test Different Server Scenarios (Advanced)

If you have access to different server configurations, test:

| Server Type | Expected Method | How to Test |
|------------|-----------------|-------------|
| **Apache + mod_php** | get_header | Default - should work immediately |
| **Nginx + PHP-FPM** | $_SERVER or getallheaders | Check logs to see which succeeds |
| **Apache + FastCGI** | $_SERVER | Check logs for confirmation |
| **LiteSpeed** | get_header | Should use primary method |
| **Behind Cloudflare** | Varies | May need additional Cloudflare config |

### 6. Troubleshooting Common Issues

#### Issue: "Webhook secret not configured"
**Solution:** 
1. Go to Stripe Dashboard → Developers → Webhooks
2. Click on your webhook → Signing secret (starts with `whsec_`)
3. Copy the secret
4. Paste into WordPress Hybrid Site Settings → Webhook Secret
5. Save changes

#### Issue: "No webhook events found"
**Solution:**
1. Verify webhook URL is correct in Stripe
2. Check firewall/security plugins aren't blocking `/wp-json/` endpoints
3. Temporarily disable security plugins to test
4. Check .htaccess isn't blocking REST API requests

#### Issue: All methods fail to find signature
**Solutions to try:**
1. Check if site is behind proxy/CDN that strips headers
2. Add to wp-config.php: `define('WP_DEBUG', true);` to see more logs
3. Contact hosting support - some hosts block custom headers
4. Check for mod_security rules blocking Stripe headers

## Expected Results Summary

✅ **Success Indicators:**
- Webhook test in Stripe returns 200 OK
- Error logs show "Retrieved signature using method: [method]"
- Access codes created after purchase
- Payment logged in database
- Debug info shows green checkmark for webhook events

❌ **Failure Indicators:**
- Webhook test returns 400 or 500 error
- Error logs show "Signature header NOT FOUND"
- No codes created after successful payment
- Red X (✗) in Recent Webhook Events

## Post-Testing Actions

1. **If Successful:**
   - Note which method worked in your environment (from logs)
   - Keep monitoring for a few days to ensure stability
   - Consider documenting your server configuration for future reference

2. **If Failed:**
   - Share error logs with support team
   - Include list of "Available headers" from error log
   - Provide server type (Apache/Nginx/etc.) and PHP version
   - Check if other WordPress REST API endpoints work

## Performance Impact

✅ **Expected Impact: Minimal**
- Only adds 3-4 conditional checks per webhook
- Header caching prevents redundant function calls
- No database queries added
- Response time increase: < 1ms

## Security Verification

✅ **All values are sanitized:**
- `sanitize_text_field()` applied to all header values
- Stripe SDK validates signature cryptographically
- Only header keys (not values) logged for debugging
- No sensitive data exposed in logs

## Support Contacts

If issues persist after testing:
1. Check all steps in WEBHOOK_TROUBLESHOOTING.md
2. Review WEBHOOK_SIGNATURE_VERIFICATION_FIX.md for technical details
3. Collect error logs from testing
4. Contact support with server configuration details

---

**Last Updated:** February 2026
**Version:** 15.32
**Related Docs:** 
- WEBHOOK_SIGNATURE_VERIFICATION_FIX.md
- WEBHOOK_TROUBLESHOOTING.md
