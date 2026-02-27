# Version 16.5 Release Notes

**Release Date**: February 2026  
**Type**: Bug Fix / Enhancement Release

## Overview
This release restores the Course Group selector on the "Create User Manually" form, while keeping access codes course-agnostic so students can choose their module at registration time.

## Changes

### 1. Course Group Field Restored for Manual User Creation
**Issue**: A previous change removed the Course Group dropdown from the "Create User Manually" form, causing all manually-created users to be assigned `course_group = 'any'` regardless of the intended module.

**Fix**:
- Re-added the Course Group (`<select name="course_group">`) dropdown to the "Create User Manually" form in the partner dashboard
- Server-side handler (`ajax_create_user_manually`) now reads `course_group` from the POST data and applies it when setting the user's membership and enrolling them in courses
- Added server-side validation of the submitted course group before processing, to fail fast on invalid input

**Impact**: Admins and partner managers creating user accounts directly can now assign the correct course module (Academic, General Training, General English, Entry Test) at creation time.

---

### 2. Access Codes Remain Course-Agnostic
Access codes (invite codes and purchased codes) continue to be issued without a fixed course group (`course_group = 'any'`). When a student redeems an `'any'` code via the registration shortcode they are presented with a course selector and their chosen module is applied at that point.

---

## Technical Changes

### Files Modified
1. **includes/class-access-codes.php**
   - Added Course Group `<select>` field to the "Create User Manually" HTML form (between the Access Days row and the Send Copy checkbox row)
   - Changed `$course_group` assignment in `ajax_create_user_manually()` from hardcoded `'any'` to `sanitize_text_field($_POST['course_group'])`
   - Added early validation: `array_key_exists($course_group, $this->course_groups)` check before email validation to fail fast on invalid input

2. **ielts-course-manager.php**
   - Updated plugin header version from `16.4` to `16.5`
   - Updated `IELTS_CM_VERSION` constant from `16.4` to `16.5`

### Security Considerations
- The submitted `course_group` value is sanitized with `sanitize_text_field()` and validated against the known `$this->course_groups` array — unknown values are rejected with an error response before any database writes occur
- No new security vulnerabilities introduced

## Upgrade Notes
- No database changes required
- No configuration changes needed
- Fully backward compatible with existing installations

## Testing Recommendations
When testing this release, verify:

1. **Manual User Creation — Course Group**
   - Log in as a partner admin or site admin
   - Open the partner dashboard and expand "Create User Manually"
   - Confirm the "Course Group" dropdown is visible and lists all enabled modules
   - Create a user, selecting a specific module (e.g. Academic Module)
   - Verify the newly created user has the correct `ielts_course_group` user meta set to the chosen value
   - Verify the user is enrolled in the courses for that module

2. **Access Codes Remain Universal**
   - Create a new invite code and confirm no course group field is shown
   - Redeem the code via the `[ielts_access_code_registration]` shortcode
   - Confirm the course selector appears during registration
   - Complete registration and verify the user is enrolled in the chosen module

3. **Invalid Course Group Rejected**
   - Submit the "Create User Manually" form with a tampered `course_group` value not in the allowed list
   - Verify the server returns an error and no user is created

## Known Issues
None at this time.

## Credits
- Issue reported by: impact2021
- Fixed by: GitHub Copilot Agent
- Tested by: (Pending)
