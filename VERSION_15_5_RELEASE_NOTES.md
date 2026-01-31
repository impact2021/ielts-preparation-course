# Version 15.5 Release Notes

## Release Date
January 31, 2026

## Summary
This release fixes critical issues with the Access Code Membership system and ensures complete separation between Paid Membership and Access Code Membership systems.

## Major Fixes

### 🔧 Access Code Membership System Overhaul

#### **Issue:** Users created via Partner Dashboard had no course access
**Root Cause:** The access code system was setting meta fields but not creating actual course enrollments or assigning proper WordPress roles.

**Fixed:**
1. ✅ Access code users now get proper WordPress membership roles assigned:
   - `access_academic_module` for Academic Module
   - `access_general_module` for General Training Module
   - `access_general_english` for General English

2. ✅ Course enrollment logic completely rewritten:
   - Now queries actual courses from database by category slug
   - Creates proper enrollment table records with course IDs
   - Sets expiry dates on all enrollments
   - Backward compatible with legacy meta fields

3. ✅ Access checking now recognizes both paid and access code users:
   - `is_enrolled()` validates both role types
   - `user_has_course_access()` properly handles access code users
   - Enrollment table shared between both systems

### 🔒 System Separation & Role Management

#### **Issue:** Paid membership roles created even when system was disabled
**Fixed:**
- ✅ `create_membership_roles()` now only runs when Paid Membership is enabled
- ✅ Access code roles only created when Access Code system is enabled
- ✅ No role conflicts between the two systems

#### **Membership Type Mapping:**

**Access Code Memberships:**
| Membership Type | WordPress Role | Courses Included |
|----------------|----------------|------------------|
| Academic Module | `access_academic_module` | Categories: academic, english, academic-practice-tests |
| General Training Module | `access_general_module` | Categories: general, english, general-practice-tests |
| General English | `access_general_english` | Category: english only |

**Paid Memberships (unchanged):**
| Membership Type | WordPress Role |
|----------------|----------------|
| Academic Trial | `academic_trial` |
| General Trial | `general_trial` |
| Academic Full | `academic_full` |
| General Full | `general_full` |
| Academic Plus | `academic_plus` |
| General Plus | `general_plus` |
| English Trial | `english_trial` |
| English Full | `english_full` |

### 📚 New Documentation

Added comprehensive documentation page under **Partner Dashboard → How It Works** explaining:
- System comparison between Paid and Access Code memberships
- How membership types work
- Course access logic flow
- User management procedures
- Troubleshooting guide

## Technical Changes

### Files Modified:

1. **includes/class-access-codes.php**
   - Added `ACCESS_CODE_MEMBERSHIP_TYPES` constant
   - Added `create_access_code_membership_roles()` method
   - Rewrote `enroll_user_in_courses()` to query courses by category
   - Rewrote `set_ielts_membership()` to assign WordPress roles
   - Added `get_courses_by_category_slugs()` helper method
   - Updated `remove_user_enrollments()` to work with enrollment table
   - Added `documentation_page()` method

2. **includes/class-membership.php**
   - Updated `create_membership_roles()` to check if system is enabled
   - Updated `user_has_course_access()` to handle access code users
   - Added separation logic for the two systems

3. **includes/class-enrollment.php**
   - Updated `is_enrolled()` to recognize access code membership roles
   - Added role array merging for both system types

4. **ielts-course-manager.php**
   - Updated version to 15.5

### Database Changes:
No schema changes. Uses existing tables:
- `wp_ielts_cm_enrollments` - Shared by both systems
- `wp_ielts_cm_access_codes` - Access code tracking
- WordPress user meta - Separate fields for each system

## Compatibility

### ✅ Backward Compatibility
- Legacy meta fields (`enrolled_ielts_academic`, etc.) still set for compatibility
- Existing users not affected
- Course category system unchanged

### ✅ System Independence
- Paid Membership system unaffected by changes
- Access Code system can be enabled/disabled independently
- No conflicts between the two systems
- See `ACCESS_CODE_PAID_MEMBERSHIP_COMPATIBILITY.md` for detailed analysis

## Upgrade Instructions

### For Sites Using Access Code Membership:

1. **Before Upgrading:**
   - Note all current access code users
   - Document their membership types

2. **After Upgrading:**
   - Go to **IELTS Courses → Settings**
   - Ensure **Access Code Membership** is enabled
   - Verify roles were created: Users → (check role dropdown)

3. **Re-save Existing Users (IMPORTANT):**
   - For each access code user:
     - Go to Users → Edit User
     - Scroll to "Access Code Enrollment"
     - Change Course Group to something else
     - Save
     - Change Course Group back to correct value
     - Save again
   - This will trigger the new enrollment logic and assign proper roles

### For Sites Using Only Paid Membership:
No action required. System will work as before.

## Known Limitations

1. **Course Categories Required:** Courses must have proper category slugs assigned:
   - `academic`, `general`, `english`, `academic-practice-tests`, `general-practice-tests`
   - Users won't be enrolled in courses without proper categories

2. **One Membership Type Per User:** A user can have either a Paid membership OR an Access Code membership, not both simultaneously (WordPress role limitation).

3. **Manual User Migration:** Existing access code users need to be re-saved to get the new enrollment logic applied.

## Testing Performed

- ✅ Verified paid membership user access still works
- ✅ Verified access code user creation creates enrollments
- ✅ Verified role assignment for both systems
- ✅ Verified enrollment table shared correctly
- ✅ Verified system toggle behavior
- ✅ Verified no role conflicts
- ✅ Verified meta field separation

## Support

For issues or questions:
- Review documentation: Partner Dashboard → How It Works
- Check compatibility analysis: `ACCESS_CODE_PAID_MEMBERSHIP_COMPATIBILITY.md`
- Review troubleshooting section in documentation page

## Contributors

- System Architecture & Implementation
- Compatibility Verification
- Documentation

---

**Version 15.5** - A complete overhaul of the Access Code Membership system ensuring proper course access and system separation.
