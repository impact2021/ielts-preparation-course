# Version 15.23 Release Notes

**Release Date:** February 8, 2026

## Summary
This release fixes three critical issues in the partner dashboard for hybrid sites, including email delivery, UI improvements, and full PayPal payment integration.

---

## 🐛 Bug Fixes

### 1. Partner Dashboard Label Consistency (Issue #1)
**Problem:** The "Access Days:" label was showing in the Purchase Access Codes form on hybrid sites, which was inconsistent with the design.

**Solution:** 
- Changed label from "Access Days:" to "Access Duration (Days):" for better clarity
- Added aria-label for improved accessibility for screen reader users
- Maintains consistency across the hybrid site interface

**Impact:** Partners will now see a clearer, more accessible form when purchasing codes.

---

### 2. Email Not Sending After Code Purchase (Issue #2)
**Problem:** After purchasing access codes via Stripe, partners were not receiving confirmation emails with their codes, and codes were not being properly tracked.

**Solution:**
- Fixed critical bug in Stripe webhook handler (`handle_code_purchase_payment()`)
- Changed email function call to pass `$user_id` instead of `$partner_org_id`
- Ensured proper user data retrieval for email sending

**Impact:** Partners now receive confirmation emails immediately after successful Stripe payments with all their purchased access codes listed.

**Technical Details:**
- **File:** `includes/class-stripe-payment.php`
- **Line:** 1151
- **Change:** `send_purchase_confirmation_email($user_id, ...)` instead of `send_purchase_confirmation_email($partner_org_id, ...)`

---

### 3. PayPal Integration Missing (Issue #3)
**Problem:** When PayPal was selected as the payment method, nothing appeared - the PayPal button container was empty.

**Solution:** Implemented complete PayPal integration for access code purchasing:

#### PayPal SDK Loading
- Added PayPal SDK script loading in `enqueue_frontend_scripts()`
- SDK only loads when hybrid mode is enabled and PayPal credentials are configured
- Uses PayPal client ID from hybrid settings

#### PayPal Button Initialization
- Added PayPal Buttons initialization in JavaScript
- Integrated with existing payment method toggle UI
- Provides smooth user experience with proper error handling

#### Backend API Handlers
1. **Order Creation** (`ajax_create_paypal_code_order()`)
   - Validates user permissions and input data
   - Verifies pricing against server-side settings
   - Creates PayPal order via REST API
   - Stores pending purchase data securely

2. **Order Capture** (`ajax_capture_paypal_code_order()`)
   - Validates and captures PayPal payment
   - Generates access codes in database
   - Sends confirmation email to partner
   - Comprehensive error handling and logging

**Impact:** Partners can now use PayPal as an alternative payment method for purchasing access codes.

---

## 🔒 Security Improvements

### PayPal Integration Security
1. **Stale Order Cleanup**
   - Pending orders expire after 1 hour
   - Prevents order ID conflicts and data accumulation
   - Uses constant `PAYPAL_ORDER_EXPIRATION` for maintainability

2. **Replay Attack Prevention**
   - Verifies order status with PayPal before capture
   - Ensures order is in "APPROVED" state before processing
   - Prevents double-charging and fraudulent captures

3. **Access Token Validation**
   - Added explicit validation for PayPal access tokens
   - Prevents undefined index errors
   - Provides clear error messages on authentication failure

4. **Database Insert Error Handling**
   - Checks each code insert operation for errors
   - Logs critical failures for admin review
   - Rejects transaction if >50% of codes fail to generate
   - Uses constant `CODE_GENERATION_FAILURE_THRESHOLD` for configuration

5. **Email Failure Logging**
   - Logs email sending failures to error log
   - Includes order ID and user information for debugging
   - Doesn't fail the transaction if codes were generated successfully

---

## ♿ Accessibility Improvements

1. **Better Form Labels**
   - Changed "Days:" to "Access Duration (Days):" for better context
   - Added aria-label attribute for screen readers
   - Improves understanding for assistive technology users

2. **Consistent Error Messaging**
   - Replaced `alert()` calls with inline error messages
   - Uses existing error message system for consistency
   - Better integration with page layout and user workflow

---

## 🔧 Code Quality Improvements

### Named Constants
Added class constants for better maintainability:
- `PAYPAL_ORDER_EXPIRATION = 3600` - PayPal order expiration (1 hour)
- `CODE_GENERATION_FAILURE_THRESHOLD = 0.5` - Maximum acceptable failure rate (50%)

### Error Handling
- Comprehensive logging throughout PayPal payment flow
- Critical errors logged with order IDs for support
- Non-blocking errors for email failures

---

## 📋 Files Changed

1. **includes/class-access-codes.php**
   - Added PayPal SDK loading
   - Added PayPal button initialization
   - Added `ajax_create_paypal_code_order()` method
   - Added `ajax_capture_paypal_code_order()` method
   - Updated form labels
   - Added security constants

2. **includes/class-stripe-payment.php**
   - Fixed email sending bug in webhook handler

3. **ielts-course-manager.php**
   - Updated version to 15.23

---

## 🧪 Testing Recommendations

### For Partners
1. **Stripe Payment:**
   - Purchase access codes using Stripe
   - Verify confirmation email is received
   - Check codes appear in "Your Codes" section

2. **PayPal Payment:**
   - Select PayPal payment method
   - Verify PayPal button appears
   - Complete PayPal payment
   - Verify confirmation email is received
   - Check codes appear in dashboard

### For Administrators
1. Verify PayPal credentials are configured in Hybrid Settings
2. Test with both Stripe and PayPal enabled
3. Check error logs for any issues
4. Verify email delivery is working

---

## 🔄 Upgrade Notes

- No database schema changes
- No special upgrade steps required
- Backward compatible with existing installations
- PayPal settings are optional (Stripe continues to work independently)

---

## 📝 Configuration

### PayPal Setup
To enable PayPal for code purchases:

1. Go to **Hybrid site settings** in WordPress admin
2. Enable "Enable PayPal"
3. Enter PayPal REST API Client ID
4. Enter PayPal REST API Secret
5. Enter PayPal Business Email Address (optional)
6. Save settings

PayPal SDK will automatically load on partner dashboard when enabled.

---

## 🐛 Known Issues

None at this time.

---

## 👥 Credits

- Development: GitHub Copilot
- Testing: impact2021
- Code Review: Automated via GitHub Actions

---

## 📞 Support

For issues or questions:
- GitHub Issues: https://github.com/impact2021/ielts-preparation-course/issues
- Error logs: Check WordPress debug.log for detailed error information
