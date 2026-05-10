# Webhook Troubleshooting Guide - Access Code Purchases Not Working

## Problem
After purchasing access codes through Stripe, no codes are created and no payment is logged in the database.

## Root Cause
The Stripe webhook is either not configured, misconfigured, or failing to process events.

## Solution Steps

### 1. Check Webhook Configuration in WordPress

1. Go to **Hybrid Site Settings** in WordPress admin
2. Scroll to **Stripe Settings**
3. Check for configuration warnings:
   - ⚠️ **"Webhook Secret is not configured"** - This means the webhook won't work!
   - ✅ **"Stripe appears to be configured correctly"** - Configuration looks good

### 2. Configure Webhook in Stripe Dashboard

If webhook secret is not configured:

1. Log in to [Stripe Dashboard](https://dashboard.stripe.com/)
2. Go to **Developers** → **Webhooks**
3. Click **Add endpoint**
4. Enter the Webhook URL shown in your WordPress settings page:
   - Format: `https://your-site.com/wp-json/ielts-cm/v1/stripe-webhook`
   - Example: `https://ieltspreparation.com/wp-json/ielts-cm/v1/stripe-webhook`
5. Under **Events to send**, select:
   - **payment_intent.succeeded** ← This is critical!
6. Click **Add endpoint**
7. Copy the **Signing secret** (starts with `whsec_`)
8. Paste it into the **Webhook Secret** field in WordPress Hybrid Site Settings
9. Click **Save Changes**

### 3. Check Error Logs

After configuring the webhook, test a purchase and check the WordPress error logs for:

```
IELTS Stripe Webhook: Received webhook request
IELTS Stripe Webhook: Successfully verified signature for event type: payment_intent.succeeded
IELTS Stripe Webhook: Processing payment_intent.succeeded event
IELTS Stripe Webhook: Delegating to handle_code_purchase_payment
IELTS Webhook: handle_code_purchase_payment START
```

If you see these logs, the webhook is working!

### 4. Common Issues

#### Issue: "Webhook signature verification failed"
**Solution**: The webhook secret in WordPress doesn't match the secret in Stripe. Copy the correct secret from Stripe and update WordPress settings.

#### Issue: "Webhook secret not configured"
**Solution**: Follow step 2 above to configure the webhook in Stripe and add the secret to WordPress.

#### Issue: "Received unhandled event type: checkout.session.completed"
**Solution**: This is normal. The code uses PaymentIntent API, not Checkout Sessions. Make sure your webhook is listening for `payment_intent.succeeded`.

#### Issue: "Access codes table does not exist"
**Solution**: Deactivate and reactivate the IELTS Course Manager plugin to create database tables.

#### Issue: "IELTS_CM_Access_Codes class not found"
**Solution**: The Access Codes feature may not be loaded. Check that the file `includes/class-access-codes.php` exists and is being included in the main plugin file.

### 5. Testing

To test if webhook is working without making a real purchase:

1. In Stripe Dashboard, go to **Developers** → **Webhooks**
2. Click on your webhook endpoint
3. Click **Send test webhook**
4. Select event type: **payment_intent.succeeded**
5. Click **Send test webhook**
6. Check WordPress error logs for webhook processing logs

### 6. Debug Information

The Access Codes page shows debug information:
- **Total Codes in Database**: Should show codes if any were created
- **Codes Created by Your Org**: Should show your codes
- **Last Payment**: Should show payment after successful webhook processing

If "Last Payment: None found" appears after a successful Stripe charge, the webhook is not processing correctly.

## Contact Support

If you've followed all steps and codes still aren't being created:

1. Check WordPress error logs for detailed error messages
2. Verify Stripe is in live mode (or test mode if testing)
3. Ensure the webhook endpoint URL is accessible (not blocked by firewall/security plugins)
4. Try disabling security plugins temporarily to see if they're blocking webhooks

## Quick Checklist

- [ ] Webhook endpoint created in Stripe Dashboard
- [ ] Webhook listening for `payment_intent.succeeded` event
- [ ] Webhook secret copied to WordPress settings
- [ ] Stripe API keys (publishable and secret) configured in WordPress
- [ ] WordPress error logs show webhook is being called
- [ ] Access codes table exists in database
- [ ] Plugin is activated and all files are loaded
