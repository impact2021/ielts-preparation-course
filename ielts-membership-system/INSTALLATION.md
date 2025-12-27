# IELTS Membership System - Installation Guide

## Quick Start

1. **Upload Plugin**
   - Upload the entire `ielts-membership-system` folder to `/wp-content/plugins/`
   - OR zip the folder and upload via WordPress Admin > Plugins > Add New > Upload

2. **Activate**
   - Go to WordPress Admin > Plugins
   - Find "IELTS Membership System"
   - Click "Activate"

3. **Initial Setup**
   - Upon activation, the plugin will automatically create three pages:
     - `/membership-login/` - Login page
     - `/membership-register/` - Registration page
     - `/my-account/` - Account management page

4. **Configure Payment Gateways**
   - Go to **Membership > Settings** in WordPress admin
   - Configure PayPal and/or Stripe settings (see below)

## Payment Gateway Configuration

### PayPal Setup (5 minutes)

1. Log in to your PayPal business account at https://www.paypal.com
2. Note your PayPal business email address
3. In WordPress:
   - Go to **Membership > Settings**
   - Check "Enable PayPal"
   - Enter your PayPal business email
   - For testing: Check "Enable PayPal sandbox"
   - Click "Save Changes"

**That's it!** PayPal is now ready to accept payments.

### Stripe Setup (10 minutes)

1. Create a Stripe account at https://stripe.com (if you don't have one)
2. Get your API keys:
   - Log in to Stripe Dashboard
   - Go to Developers > API Keys
   - Copy your "Publishable key" and "Secret key"
3. In WordPress:
   - Go to **Membership > Settings**
   - Check "Enable Stripe"
   - Paste your Publishable Key
   - Paste your Secret Key
   - Click "Save Changes"

**Optional Webhook Setup (for advanced features):**
1. In Stripe Dashboard, go to Developers > Webhooks
2. Click "Add endpoint"
3. Enter URL: `https://yoursite.com/wp-admin/admin-ajax.php?action=ielts_ms_stripe_webhook`
4. Select events: `checkout.session.completed`
5. Copy the webhook signing secret
6. In WordPress settings, paste it in "Webhook Secret" field

## Integration with IELTS Course Manager

If you have the IELTS Course Manager plugin installed:

1. The membership system will automatically integrate
2. Active members get "subscriber" role
3. Subscribers automatically have access to all courses
4. No additional configuration needed!

## Testing Your Setup

### Test Registration & Login

1. Open an incognito/private browser window
2. Go to `/membership-login/`
3. Click "Register"
4. Create a test account
5. Verify you can log in

### Test Payment (Sandbox Mode)

**PayPal Sandbox:**
1. Enable sandbox mode in settings
2. Create a test buyer account at https://developer.paypal.com/developer/accounts/
3. Make a test purchase using test credentials
4. Verify membership is activated

**Stripe Test Mode:**
1. Use Stripe test API keys (they start with `pk_test_` and `sk_test_`)
2. Use test card: `4242 4242 4242 4242`
3. Use any future expiry date and any CVC
4. Make a test purchase
5. Verify membership is activated

## Customization

### Custom Login Redirect

By default, successful login redirects to the account page. To change:
- Edit `/includes/class-login-manager.php`
- Modify the redirect URL in the `handle_login()` method

### Email Templates

Password reset emails use WordPress defaults. To customize:
- Use a plugin like "WP Mail SMTP" for branded emails
- Or hook into `retrieve_password_message` filter

### Styling

Custom CSS can be added in:
1. Your theme's `style.css`
2. WordPress Customizer > Additional CSS
3. Or edit `/assets/css/style.css` directly

Target classes:
- `.ielts-ms-login-wrapper` - Login page container
- `.ielts-ms-account-wrapper` - Account page container
- `.ielts-ms-pricing-grid` - Pricing options
- `.pricing-card` - Individual pricing cards

## Troubleshooting

### Login page not redirecting
- Check "Custom Login" is enabled in Settings
- Clear browser cache
- Check permalinks (Settings > Permalinks > Save)

### Payments not processing
- Verify API keys are correct (no extra spaces)
- Check payment gateway is enabled
- Review transaction logs in Membership > Payments

### Membership not activating
- Check PayPal IPN settings (Auto Return: On)
- Verify webhook is configured for Stripe
- Check PHP error logs

### Pages show 404 error
- Go to Settings > Permalinks
- Click "Save Changes" (flushes rewrite rules)

## Getting Help

- Check the main README.md for feature documentation
- Review code comments in plugin files
- Contact plugin maintainer

## Next Steps

1. Test the complete user journey (register → purchase → access courses)
2. Set up regular backups of your database
3. Monitor the Payments page for transactions
4. Review Members page to track active memberships
5. Consider setting up automated expiry notifications (future feature)

## Security Best Practices

- Use HTTPS (SSL certificate) for your site
- Keep WordPress and plugins updated
- Use strong API keys/passwords
- Enable two-factor authentication on payment accounts
- Regular database backups
- Monitor for suspicious transactions

---

**Questions?** Refer to the main README.md or contact support.
