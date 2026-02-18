# Version 16.2 Release Notes

**Release Date**: February 2026  
**Type**: Bug Fix Release

## Overview
This release fixes critical issues with hybrid site functionality, specifically around course extension payments and partner permissions.

## Issues Fixed

### 1. Payment Options Not Appearing for Course Extensions
**Issue**: When students on hybrid sites attempted to extend their course, they could see the extension duration dropdown but no payment options would appear after selecting a duration.

**Root Cause**: The payment section JavaScript and HTML were only loaded when the standard membership system was enabled (`ielts_cm_membership_enabled`). However, hybrid sites using only access code memberships (without paid memberships enabled) would have this option disabled, causing the entire extension payment system to fail silently.

**Fix**: 
- Removed the unnecessary `ielts_cm_membership_enabled` requirement from extension payment logic
- Payment section now always renders on hybrid sites regardless of membership system status
- JavaScript and pricing data are now correctly enqueued for all hybrid site extension scenarios
- Updated the extension debugger to reflect that membership system is not required

**Impact**: Students on hybrid sites can now successfully purchase course extensions using the inline payment form.

---

### 2. Partners Changing Course Expiry Dates on Hybrid Sites
**Issue**: Partner admins on hybrid sites could edit and change course expiry dates for their users, which violated the intended restriction that only site administrators should be able to modify expiry dates on hybrid sites.

**Root Cause**: The user profile edit screen did not check whether the current user was a partner admin before allowing expiry date modifications.

**Fix**:
- Added permission check to identify partner admins (users with `manage_partner_invites` capability but not `manage_options`)
- Expiry date field is now readonly for partner admins on hybrid sites with visual styling
- Server-side validation prevents partner admins from changing expiry dates even if they bypass the readonly field
- Updated field description to clearly indicate the restriction

**Impact**: Partner admins on hybrid sites can no longer change course expiry dates. Only site administrators retain this ability.

---

## Technical Changes

### Files Modified
1. **includes/class-shortcodes.php**
   - Line 2772: Removed `get_option('ielts_cm_membership_enabled')` check from extension payment JavaScript enqueue condition
   - Line 3356: Removed conditional wrapper around payment section div
   - Line 3179: Updated debugger logic for extension payment requirements
   - Line 3206: Updated debugger display to show membership system as "not required"
   - Line 3248: Removed membership system from list of potential issues

2. **includes/class-membership.php**
   - Line 267: Added `$is_partner_admin` check to identify partner admins
   - Line 268: Added `$expiry_readonly` flag for partner admins on hybrid sites
   - Line 294: Made expiry date field readonly with visual styling for partner admins
   - Line 336: Added hybrid site restriction logic to prevent expiry date changes by partner admins
   - Line 344-357: Server-side validation to restore original expiry date if partner admin attempts to change it

3. **ielts-course-manager.php**
   - Line 6: Updated plugin version from 16.1 to 16.2
   - Line 23: Updated version constant to 16.2

### Security Considerations
- Server-side validation ensures partner admins cannot bypass the readonly restriction
- Uses WordPress capability system (`manage_partner_invites`, `manage_options`) for permission checks
- No new security vulnerabilities introduced

## Upgrade Notes
- No database changes required
- No configuration changes needed
- Fully backward compatible with existing installations
- Partners will immediately see the readonly expiry date field on next page load

## Testing Recommendations
When testing this release, verify:

1. **Extension Payment Flow**
   - Log in as a student with an access code membership on a hybrid site
   - Navigate to the account page and select the "Course Extension" tab
   - Select an extension duration (1 Week, 1 Month, or 3 Months)
   - Verify payment section appears with card input fields
   - Complete a test payment to ensure the full flow works

2. **Partner Permissions**
   - Log in as a partner admin on a hybrid site
   - Navigate to Users → Edit User
   - Verify the "Expiry Date" field is readonly (gray background, disabled cursor)
   - Verify the field description explains the restriction
   - Attempt to change the expiry date and verify it doesn't save

3. **Site Admin Access**
   - Log in as a site administrator
   - Navigate to Users → Edit User
   - Verify the "Expiry Date" field is NOT readonly
   - Change the expiry date and verify it saves successfully

4. **Non-Hybrid Sites**
   - Test on a non-hybrid site to ensure existing functionality is unchanged
   - Verify both regular membership and access code functionality still works

## Debugger Information
For administrators debugging extension payment issues on hybrid sites:

1. Enable hybrid site mode in IELTS Course settings
2. Navigate to your account page while logged in as an admin
3. The Extension Payment Debugger will appear below the extension dropdown
4. Check the diagnostic details to ensure:
   - Hybrid Mode Enabled: ✓ Yes
   - Stripe Key Configured: ✓ Yes
   - Is Access Code Membership: ✓ Yes
   - Is Trial Membership: ✓ No (or user is not on trial)
   - Membership System Enabled: Shows "No (not required for extensions)" - this is OK!

## Known Issues
None at this time.

## Credits
- Issue reported by: impact2021
- Fixed by: GitHub Copilot Agent
- Tested by: (Pending)
