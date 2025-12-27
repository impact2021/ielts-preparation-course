# IELTS Membership System - Quick Reference

## 📍 Location
```
/wp-content/plugins/ielts-membership-system/
```

## 🎯 Quick Start (3 Steps)

1. **Upload & Activate**
   - Upload folder to `/wp-content/plugins/`
   - Activate in WordPress Admin → Plugins

2. **Configure Payments**
   - Go to Membership → Settings
   - Add PayPal email OR Stripe keys
   - Save

3. **Test**
   - Visit `/membership-login/`
   - Create account
   - Test payment (use sandbox/test mode)

## 💰 Pricing

| Plan | Price | Duration |
|------|-------|----------|
| New Membership | $24.95 | 90 days |
| 1 Week Extension | $5.00 | 7 days |
| 1 Month Extension | $10.00 | 30 days |
| 3 Months Extension | $20.00 | 90 days |

## 📄 Pages Created

- `/membership-login/` - Login, forgot password, reset
- `/membership-register/` - User registration
- `/my-account/` - Account dashboard & payments

## 🔑 Payment Gateway Setup

### PayPal (2 minutes)
1. Settings → Enable PayPal
2. Enter business email
3. Check sandbox for testing

### Stripe (5 minutes)
1. Get keys from stripe.com/dashboard
2. Settings → Enable Stripe
3. Paste Publishable & Secret keys

## 🧪 Test Mode

**PayPal Sandbox:**
- Enable in settings
- Use test account from developer.paypal.com

**Stripe Test:**
- Use test keys (start with `pk_test_` and `sk_test_`)
- Test card: `4242 4242 4242 4242`
- Any future date + any CVC

## 🔗 Key URLs

**Frontend:**
- Login: `yoursite.com/membership-login/`
- Register: `yoursite.com/membership-register/`
- Account: `yoursite.com/my-account/`

**Admin:**
- Settings: `Admin → Membership → Settings`
- Members: `Admin → Membership → Members`
- Payments: `Admin → Membership → Payments`

**Webhooks:**
- Stripe: `yoursite.com/wp-admin/admin-ajax.php?action=ielts_ms_stripe_webhook`
- PayPal IPN: `yoursite.com/wp-admin/admin-ajax.php?action=ielts_ms_paypal_ipn`

## 🛠️ Integration

**With IELTS Course Manager:**
- ✅ Automatic integration (no config needed)
- Active members get `subscriber` role
- Subscribers have access to all courses
- Uses WordPress filter hooks

## 📊 Database Tables

- `wp_ielts_ms_memberships` - User memberships
- `wp_ielts_ms_payments` - Payment transactions

## 🎨 Customization

**CSS Classes:**
- `.ielts-ms-login-wrapper`
- `.ielts-ms-account-wrapper`
- `.ielts-ms-pricing-grid`
- `.pricing-card`

**Shortcodes:**
- `[ielts_membership_login]`
- `[ielts_membership_register]`
- `[ielts_membership_account]`

## 🔧 Troubleshooting

| Issue | Solution |
|-------|----------|
| Login not redirecting | Enable "Custom Login" in Settings |
| Payment not processing | Check API keys, verify gateway enabled |
| 404 on pages | Settings → Permalinks → Save |
| Membership not activating | Check webhook/IPN configured |

## 📚 Documentation Files

- `README.md` - Overview & features
- `INSTALLATION.md` - Step-by-step setup
- `TECHNICAL-SUMMARY.md` - Architecture details
- `MEMBERSHIP-SYSTEM-SUMMARY.md` - Complete implementation guide

## 🔐 Security

✅ CSRF protection (nonces)  
✅ SQL injection prevention (prepared statements)  
✅ XSS prevention (sanitization)  
✅ CodeQL scan passed  

## 🚨 Important Notes

- **HTTPS Required** for live payments
- **Test First** with sandbox/test mode
- **Backup Database** before activation
- **Monitor Payments** page regularly
- **Keep WordPress Updated**

## 🎓 User Flow

1. User registers → Account created
2. User purchases → Payment processed
3. Membership activated → Subscriber role
4. User accesses courses → Full access
5. Membership expires → Access removed
6. User extends → Access restored

## 🎁 Features Included

✅ PayPal & Stripe integration  
✅ Custom login/registration  
✅ Account management  
✅ Membership tracking  
✅ Payment history  
✅ Extension options  
✅ Legacy user link  
✅ Admin dashboard  
✅ Security best practices  
✅ Full documentation  

## 📞 Need Help?

1. Check documentation in plugin folder
2. Review error logs
3. Test in sandbox/test mode first
4. Contact IELTStestONLINE support

---

**Version**: 1.0.0  
**Status**: Production Ready ✅  
**Last Updated**: December 2024
