# Quick Fix: Hybrid Site Code Purchase Issues

## Problem
Access codes not created after purchase even though payment succeeded in Stripe.

## Symptoms
- ✗ Webhook verification failures in debug info
- ✗ Codes not appearing after purchase
- ✗ Payment successful in Stripe but nothing happens
- ✗ Error: "No signatures found matching the expected signature"

## Solution (Automatic)

**Good news!** This issue is now fixed automatically with the new fallback mechanism.

### What Happens Now
1. You make a purchase → Payment succeeds
2. System tries webhook (fast, preferred method)
3. **If webhook fails** → System uses fallback (checks Stripe directly)
4. Codes are created either way!

### No Action Required
- ✅ Update to latest plugin version
- ✅ Make a test purchase
- ✅ Codes will be created (even with broken webhooks)

## If Issues Persist

### Step 1: Update Plugin
Make sure you have the latest version with the fallback mechanism:
```
Version 15.52 or higher (February 2026)
```

### Step 2: Test Purchase
1. Navigate to your purchase page
2. Select quantity and course group
3. Complete payment with test card
4. Wait up to 20 seconds
5. Codes should appear

### Step 3: Check Logs
If codes still don't appear, check error logs:

**In WordPress:**
```
WP Debug: ON
Check: wp-content/debug.log
Search for: "IELTS Stripe"
```

**Expected logs (success via fallback):**
```
IELTS Stripe: check_payment_status CALLED
IELTS Stripe: Payment intent status: succeeded
IELTS Stripe: Processing code purchase via fallback mechanism
IELTS Webhook: Successfully created 5/5 access codes
```

### Step 4: Verify Payment
Check if payment reached Stripe:
1. Log into [Stripe Dashboard](https://dashboard.stripe.com/)
2. Go to Payments
3. Find your test payment
4. Should show: "Succeeded"

## Still Recommended: Configure Webhooks

While the fallback works, webhooks are still faster and more efficient.

### Quick Webhook Setup
1. **Stripe Dashboard** → Developers → Webhooks
2. **Add endpoint**: `https://your-site.com/wp-json/ielts-cm/v1/stripe-webhook`
3. **Select event**: `payment_intent.succeeded`
4. **Copy signing secret** (starts with `whsec_`)
5. **WordPress Admin** → Hybrid Site Settings
6. **Paste secret** in "Webhook Secret" field
7. **Save Changes**

### Test Webhook
In Stripe Dashboard:
1. Go to your webhook endpoint
2. Click "Send test webhook"
3. Select: `payment_intent.succeeded`
4. Check WordPress logs for success message

## Comparison: Webhook vs Fallback

| Method | Speed | Requires Config | Works When |
|--------|-------|-----------------|------------|
| **Webhook** | Instant | Yes | Configured properly |
| **Fallback** | 3-20 sec | No | Always |

## Support

If codes still don't appear after 30 seconds:

**Provide this info:**
1. WordPress error logs (search "IELTS")
2. Browser console errors (F12 → Console)
3. Stripe payment ID (from Stripe Dashboard)
4. User ID making purchase

**Contact:** See main plugin documentation

## Technical Details

For developers who want to understand how this works:

### New Files
- `WEBHOOK_FALLBACK_MECHANISM.md` - Full technical documentation

### Key Changes
- `class-stripe-payment.php` - New `check_payment_status()` method
- `class-access-codes.php` - Client polling logic
- `class-shortcodes.php` - Extension payment polling

### How Fallback Works
```
Payment Success → Wait 3s → Poll Status → Check Stripe API → 
Process Payment → Create Codes → Show Success
```

### Security
- ✅ Nonce verification
- ✅ User authentication
- ✅ Payment ownership check
- ✅ Idempotency (no duplicates)
- ✅ Stripe API verification

---

**Version:** 15.52+ (February 2026)  
**Status:** Production ready  
**Impact:** Fixes all webhook-related purchase failures
