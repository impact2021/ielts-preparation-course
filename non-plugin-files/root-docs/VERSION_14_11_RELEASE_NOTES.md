# Version 14.11 Release Notes

## Summary
This release fixes critical issues with membership enrollment and expiry email notifications that were preventing users from upgrading from free trials and not being notified when their memberships expired.

## Fixed Issues

### 1. Dropdown/Payment Issue: Users Cannot Enroll in Full Academic Module After Free Trial
**Problem**: When a user's Academic Free Trial expired, navigating to the "Become a member" page would show:
- Full Academic Module with NO price in the dropdown
- Full General Training with price in brackets (correct)
- Selecting Academic Module did nothing (no payment section appeared)
- Only selecting General Training would show the Stripe payment field

**Root Cause**: Paid memberships (academic_full, general_full) could be configured with price = $0 in the database, causing the dropdown to not display pricing and the JavaScript to not trigger the payment section.

**Fixes**:
1. **Payment Settings Validation** (`includes/class-membership.php`, lines 685-706)
   - Added validation to prevent paid (non-trial) memberships from being set to $0
   - If admin tries to set price to $0, it's automatically set to $1.00 with a warning message
   - Warning clearly explains that paid memberships must have a price > $0

2. **Dropdown Display** (`includes/class-shortcodes.php`, lines 1809-1824)
   - Updated to always show price information for paid memberships
   - If price is > $0: Shows "Membership Name ($X.XX)"
   - If price is 0: Shows "Membership Name (Price Not Set - Contact Admin)"

3. **JavaScript Error Handling** (`assets/js/registration-payment.js`, lines 26-44)
   - Enhanced to detect paid memberships with no price
   - Shows clear error message: "This membership option is not properly configured. Please contact the site administrator or choose a different option."
   - Prevents confusion for users trying to upgrade

**Impact**: Users can now successfully upgrade from Academic Free Trial to Full Academic Module membership.

---

### 2. Automatic Expiry Email Not Sending
**Problem**: The automatic daily expiry check (via WordPress cron) wasn't sending "end of trial" emails consistently, though the manual "Check for Expired Memberships Now" button worked perfectly.

**Root Cause**: WordPress cron only runs on page loads, making it unreliable on low-traffic sites or sites with caching. Additionally, there was no fallback mechanism to catch memberships that expired between cron runs.

**Fixes**:
1. **Fallback Expiry Check** (`includes/class-membership.php`, lines 1357-1421)
   - Added new method `check_expired_on_access()` that runs on WordPress 'init' hook (priority 20)
   - Checks the current logged-in user's membership expiry on every page load
   - Sends expiry emails and updates status just like the main cron job
   - **Rate Limited**: Only checks once per hour per user to prevent performance issues
   - Uses user meta `_ielts_cm_last_expiry_check` to track last check time

2. **Early Hook Registration** (`includes/class-membership.php`, lines 37-44)
   - Ensured cron action hook is registered in constructor (already was)
   - Added fallback check registration in constructor as well

**Performance Considerations**:
- Rate limiting ensures minimal database queries (max once per hour per user)
- Only checks currently logged-in user, not all users
- Early return if user has no membership or is already expired
- No impact on non-logged-in users
- Negligible performance impact even on high-traffic sites

**Impact**: Users will now receive expiry emails reliably, either from the daily cron or when they next access the site.

---

## Version Update
- Plugin version updated from 14.10 to 14.11
- IELTS_CM_VERSION constant updated to 14.11

## Files Modified
1. `ielts-course-manager.php` - Version numbers
2. `includes/class-membership.php` - Payment validation, fallback expiry check
3. `includes/class-shortcodes.php` - Dropdown display improvement
4. `assets/js/registration-payment.js` - Error handling for misconfigured prices

## Testing Recommendations
1. **Dropdown/Payment Testing**:
   - Create a test user with expired academic_trial membership
   - Navigate to registration/upgrade page
   - Verify both Academic and General Training modules show prices
   - Verify selecting a paid membership shows Stripe payment form
   - Test completing a payment

2. **Expiry Email Testing**:
   - Create test user with membership expiring soon
   - Wait for expiry time to pass
   - Either wait for daily cron OR have user log in
   - Verify expiry email is sent within 1 hour of user accessing site
   - Check error logs for confirmation messages

3. **Admin Settings Testing**:
   - Go to Payment Settings page
   - Try to set a paid membership price to $0
   - Verify warning message appears
   - Verify price is set to $1.00 minimum
   - Update to correct price and save

## Upgrade Notes
- No database migrations required
- No breaking changes
- Existing pricing configurations will continue to work
- If any paid memberships have price = $0, admin should update them to correct price

## Known Considerations
- Minimum price of $1.00 is hardcoded as safety measure (can be made configurable if needed)
- Error logging uses WordPress error_log() function (consider proper logging system for production)
- JavaScript trial detection uses '_trial' suffix convention (consistent with PHP is_trial_membership method)

## Security
- All input is sanitized and validated
- Nonce verification on payment settings save
- No new security vulnerabilities introduced
- Rate limiting prevents potential abuse of expiry check
