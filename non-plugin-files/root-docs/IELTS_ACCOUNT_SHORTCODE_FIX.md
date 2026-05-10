# [ielts_account] Shortcode Fix - Access Code Membership Support

## Issue
The `[ielts_account]` shortcode was not displaying the membership tab with expiry date and other information for users with access code memberships.

## Root Cause
1. Access code membership types (e.g., `access_academic_module`) were not included in the membership levels array
2. Tab visibility was only based on the `ielts_cm_membership_enabled` option, which may not be set for access code users

## Solution
Made minimal changes to `includes/class-shortcodes.php` in the `display_account()` method:

### 1. Merge Membership Type Arrays
```php
// Get membership levels from both regular and access code memberships
$membership_levels = IELTS_CM_Membership::MEMBERSHIP_LEVELS;
if (class_exists('IELTS_CM_Access_Codes')) {
    $membership_levels = array_merge($membership_levels, IELTS_CM_Access_Codes::ACCESS_CODE_MEMBERSHIP_TYPES);
}
```

### 2. Update Tab Visibility Logic
```php
// Determine if we should show membership tab
// Show if membership system is enabled OR user has a membership (including access codes)
$show_membership_tab = get_option('ielts_cm_membership_enabled') || !empty($membership_type);
```

### 3. Use New Variable Throughout
Replaced 7 instances of `get_option('ielts_cm_membership_enabled')` with `$show_membership_tab` for consistency.

## Impact
- Users with access code memberships now see their membership information
- Membership tab displays: type, expiry date, and status
- Backward compatible - existing functionality unchanged
- No security vulnerabilities introduced

## Testing
✅ All logic tests passed  
✅ Edge cases handled  
✅ Security review clean  
✅ PHP syntax valid  

## Files Changed
- `includes/class-shortcodes.php` (14 insertions, 7 deletions)

## Date
February 7, 2026
