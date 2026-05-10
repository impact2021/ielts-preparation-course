# Version 15.9 Release Notes

## Critical Bug Fix: Access Code Enrollment

### Problem
When editing a user in the WordPress admin backend, the access code enrollment system would **NOT** enroll users in courses. This resulted in:
- "Enroll Now" button still showing even after admin set access code membership
- Users unable to access courses they should have access to
- The issue persisted even when changing between different course groups

### Root Causes (Two Bugs Fixed)

#### Bug #1: Enrollment Only Ran When Course Group Changed
In `includes/class-membership.php` at line 355, the enrollment logic was wrapped in this condition:

```php
if ($course_group !== $old_course_group) {
    // Only ran when course group CHANGED
    update_user_meta($user_id, 'iw_course_group', $course_group);
    // ... role assignment and enrollment code
}
```

This meant:
1. **Re-saving the same course group**: No enrollment happened
2. **Changing only the expiry date**: No enrollment happened  
3. **First-time setup worked**: But any subsequent save failed

#### Bug #2: Access Code Roles Weren't Being Created
The access code membership roles (`access_academic_module`, `access_general_module`, `access_general_english`) were only created if the global access code system was enabled (`ielts_cm_access_code_enabled` option).

**When the access code system was disabled:**
1. Roles didn't exist in WordPress
2. `$user->add_role($membership_type)` failed silently
3. User got enrolled in database BUT had no role
4. `is_enrolled()` check failed because user lacked the required role
5. User saw "Enroll Now" button instead of "Continue Course"

**This explains why changing course groups didn't work either** - the roles simply didn't exist to be assigned!

### Why This Was Missed Multiple Times

The bugs were subtle because:

1. **Database looked correct** - The `iw_course_group` meta was saved, enrollment records existed
2. **No error messages** - WordPress `add_role()` fails silently if role doesn't exist
3. **Roles looked like they existed** - If access code system was ever enabled, roles were created, but if disabled they were removed
4. **First-time worked (sometimes)** - When changing from one group to another AND access code system was enabled
5. **Performance optimization** - The original "only on change" logic made sense but backfired

### The Fixes

**Fix #1: Always Enroll When Course Group Is Set**

Changed Lines 354-360 in `includes/class-membership.php`:

```php
// BEFORE (broken)
if ($course_group !== $old_course_group) {
    $course_group_changed = true;
    update_user_meta($user_id, 'iw_course_group', $course_group);
    
    if (!empty($course_group) && class_exists('IELTS_CM_Access_Codes')) {
        // enrollment code
    }
}

// AFTER (fixed)
if ($course_group !== $old_course_group) {
    $course_group_changed = true;
}

update_user_meta($user_id, 'iw_course_group', $course_group);

// Process ALWAYS when course_group is set (not just when changed)
if (!empty($course_group) && class_exists('IELTS_CM_Access_Codes')) {
    // enrollment code - NOW RUNS EVERY TIME
}
```

**Fix #2: Always Create Required Access Code Roles**

Added role creation logic (lines 408-418):

```php
// Ensure the access code role exists before trying to assign it
if (!get_role($membership_type)) {
    $subscriber = get_role('subscriber');
    if ($subscriber) {
        // Only create if this is an access code role (safety check)
        if (isset(IELTS_CM_Access_Codes::ACCESS_CODE_MEMBERSHIP_TYPES[$membership_type])) {
            add_role($membership_type, 
                    IELTS_CM_Access_Codes::ACCESS_CODE_MEMBERSHIP_TYPES[$membership_type], 
                    $subscriber->capabilities);
        }
    }
}
```

**Key improvements:**
1. ✅ Roles are created on-demand when needed (independent of global setting)
2. ✅ Safety check ensures only access code roles are created (not paid membership roles)
3. ✅ Works even if access code system is globally disabled
4. ✅ Admin can always manually assign access code memberships via user profile

### Scope: Access Code Membership Only

**This fix ONLY affects access code enrollment** (lines 347+ in class-membership.php):
- ✅ Access code roles: `access_academic_module`, `access_general_module`, `access_general_english`
- ✅ Access code POST fields: `iw_course_group`, `iw_membership_expiry`

**This fix does NOT affect paid membership** (lines 318-345):
- ✅ Paid membership roles: `academic_trial`, `general_trial`, `academic_full`, `general_full`, `academic_plus`, `general_plus`, `english_trial`, `english_full`
- ✅ Paid membership POST fields: `ielts_cm_membership_type`, `ielts_cm_membership_expiry`

The two systems are completely isolated with no role overlap.

### Testing Scenarios

To verify this fix works, test these scenarios:

**Scenario 1: New User**
1. Create new user (or use existing user with no course group)
2. Edit user in admin → Access Code Enrollment → Select "Academic Module" → Save
3. ✓ User should now see "Continue Course" on academic courses (not "Enroll Now")
4. ✓ Check user roles - should have `access_academic_module` role
5. ✓ Check enrollment table - user should be enrolled in academic courses

**Scenario 2: Re-saving Same Course Group (THE BUG FIX)**
1. Edit user who already has "Academic Module" set
2. Don't change anything, just click Save
3. ✓ User should STILL have access (enrollment refreshed)
4. ✓ If they previously had no access, they should now have access

**Scenario 3: Changing Expiry Date Only**
1. Edit user with course group set
2. Change only the "Access Code Expiry" date → Save
3. ✓ New expiry should be reflected in enrollment table
4. ✓ User access should continue to work
5. ✓ Check enrollment records - `course_end_date` should match new expiry

**Scenario 4: Changing Course Group**
1. Edit user with "Academic Module" set
2. Change to "General Training Module" → Save
3. ✓ Old role removed, new role added
4. ✓ Enrolled in general courses instead of academic
5. ✓ Access to correct courses reflected in UI

**Scenario 5: Clearing Course Group**
1. Edit user with course group set
2. Change to "None" → Save
3. ✓ Access code role removed
4. ✓ All access code meta fields cleared
5. ✓ User loses access to courses (sees "Enroll Now" again)

### Files Changed

- `ielts-course-manager.php` - Version bumped to 15.9
- `includes/class-membership.php` - Fixed enrollment logic (lines 347-465)

### Migration Notes

**Existing users with broken enrollments:**

If you have users who were affected by this bug (course_group set but no enrollments), they will be **automatically fixed** the next time you edit and save their user profile in the admin.

Alternatively, you can write a one-time script:

```php
// Get all users with access code membership
$users = get_users(array(
    'meta_key' => 'iw_course_group',
    'meta_compare' => 'EXISTS'
));

foreach ($users as $user) {
    $course_group = get_user_meta($user->ID, 'iw_course_group', true);
    if (!empty($course_group) && class_exists('IELTS_CM_Access_Codes')) {
        $access_codes = new IELTS_CM_Access_Codes();
        $access_codes->enroll_user_in_courses($user->ID, $course_group);
        error_log("Fixed enrollment for user {$user->ID}");
    }
}
```

### Security Impact

**None** - This is purely a bug fix that makes the system work as intended. No new security vulnerabilities introduced.

### Performance Impact

**Minimal** - The enrollment code now runs on every user profile save where access code enrollment is set. However:
- This only happens during admin user editing (infrequent operation)
- The enrollment function is already optimized with proper database queries
- The benefit of reliability far outweighs the minimal performance cost

### Backwards Compatibility

**100% compatible** - The fix maintains all existing behavior and only ensures enrollment happens reliably.

---

## Summary

**Version 15.9 fixes the critical bug where admin-edited access code memberships would not enroll users in courses.** Users can now be reliably enrolled by editing their profile in the WordPress admin, regardless of whether the course group was previously set or changed.
