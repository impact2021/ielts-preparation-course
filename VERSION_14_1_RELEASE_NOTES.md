# Version 14.1 Release Notes

## Bug Fix: Membership Admin Menu Visibility

**Date:** 2026-01-25

### Problem Statement
The membership admin menu was only visible when the membership system was enabled, creating a catch-22 situation where administrators couldn't access the settings page to enable the system in the first place. Users reported: "There's nowhere I can see to toggle the membership on or off?"

### Root Cause Analysis

In `includes/class-membership.php`, the `init()` method had the following logic:

```php
public function init() {
    // Only initialize if membership system is enabled
    if (!$this->is_enabled()) {
        return;
    }
    
    // ... other initialization code
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
}
```

**The Problem:**
1. When membership was disabled (`ielts_cm_membership_enabled` option is false)
2. The `init()` method would return early
3. The admin menu hooks were never registered
4. Users couldn't see the "Memberships" menu in WordPress admin
5. Without the menu, they couldn't access the Settings page to enable it

This created an impossible situation for new installations or when the membership system was intentionally disabled.

### The Fix

Reordered the initialization logic to always register the admin menu and settings, regardless of whether the membership system is enabled:

```php
public function init() {
    // Always add admin menu and register settings so users can enable/disable the system
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    
    // Only initialize other features if membership system is enabled
    if (!$this->is_enabled()) {
        return;
    }
    
    // Add user columns
    add_filter('manage_users_columns', array($this, 'add_user_columns'));
    add_filter('manage_users_custom_column', array($this, 'user_column_content'), 10, 3);
    
    // Add user edit fields
    add_action('show_user_profile', array($this, 'user_membership_fields'));
    add_action('edit_user_profile', array($this, 'user_membership_fields'));
    add_action('personal_options_update', array($this, 'save_user_membership_fields'));
    add_action('edit_user_profile_update', array($this, 'save_user_membership_fields'));
}
```

**What Changed:**
- Admin menu and settings registration moved BEFORE the `is_enabled()` check
- User-facing features (columns, profile fields) remain conditional
- Menu is now always visible, allowing access to the Settings page
- Other membership features only activate when system is enabled

### Files Modified

1. **includes/class-membership.php**
   - Reordered initialization in `init()` method (lines 30-49)
   - Admin menu hooks now registered unconditionally
   - Other features remain conditional based on enable/disable setting

2. **ielts-course-manager.php**
   - Updated version from 14.0 to 14.1

### Impact

**Before Fix:**
- ❌ Admin menu hidden when membership disabled
- ❌ No way to access Settings page to enable the system
- ❌ Catch-22: Can't enable without menu, can't see menu when disabled
- ✅ Membership features properly disabled when setting is off

**After Fix:**
- ✅ Admin menu always visible in WordPress admin
- ✅ Can access Settings page to toggle membership on/off
- ✅ Can access documentation and other admin pages
- ✅ Membership features still properly disabled when setting is off
- ✅ User columns and profile fields only show when system is enabled

### User Experience Flow (After Fix)

1. Fresh installation or membership disabled:
   - Navigate to WordPress admin
   - See "Memberships" menu in sidebar ✅
   - Click "Memberships → Settings"
   - Check "Enable the membership system"
   - Click "Save Changes"
   - Membership features now active!

2. Disabling membership:
   - Navigate to "Memberships → Settings"
   - Uncheck "Enable the membership system"
   - Click "Save Changes"
   - Menu remains visible (can re-enable anytime)
   - Membership features hidden from users
   - No user columns or profile fields

### Testing Recommendations

To verify the fix works correctly:

1. **Fresh Installation Test:**
   - Install plugin for first time (membership disabled by default)
   - Check WordPress admin sidebar
   - Verify "Memberships" menu is visible
   - Click "Memberships → Settings"
   - Verify setting page loads correctly
   - Enable membership system
   - Verify features activate

2. **Disable/Enable Toggle Test:**
   - Go to "Memberships → Settings"
   - Disable the system
   - Verify menu remains visible
   - Verify user columns disappear from Users list
   - Re-enable the system
   - Verify features reactivate
   - Verify user columns reappear

3. **Menu Access Test:**
   - With membership disabled, verify all menu items accessible:
     - Memberships (list page)
     - Docs (documentation page)
     - Settings (settings page)
     - Courses (mapping page)
     - Payment Settings (payment config page)

### Documentation Updates

Updated `MEMBERSHIP_QUICK_START.md` to reflect that the admin menu is now always visible, making Step 1 more straightforward.

### Security

CodeQL analysis confirmed no security vulnerabilities were introduced by these changes.

---

**Version:** 14.1  
**Previous Version:** 14.0  
**Type:** Bug Fix  
**Breaking Changes:** None
