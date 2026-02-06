# Bulk Enrollment Feature - Testing Guide

## Overview
This feature adds a bulk action to the WordPress Users page (`/wp-admin/users.php`) that allows administrators to enroll multiple users at once into an IELTS course with a 30-day expiry date.

## How to Test

### 1. Access the Users Page
- Log in to WordPress admin as an administrator
- Navigate to **Users** → **All Users** (`/wp-admin/users.php`)

### 2. Select Users to Enroll
- Check the boxes next to the users you want to enroll
- Or use the checkbox at the top to select all users on the current page

### 3. Use the Bulk Action
- In the "Bulk Actions" dropdown at the top of the users list
- Select **"Enroll in IELTS Course (30 days)"**
- Click the **Apply** button

### 4. Verify Enrollment
- You should see a success message at the top of the page showing:
  - Number of users enrolled
  - Course name they were enrolled in
  - Expiry date (30 days from today)

## Expected Behavior

### Success Case
When users are successfully enrolled, you will see a green success notice:
```
[X users enrolled in [Course Name] with expiry date: [Date 30 days from now]]
```

### Error Case
If no IELTS courses exist in the system, you will see a red error notice:
```
No IELTS courses found. Please create a course first.
```

## Technical Details

### What Happens When You Enroll Users
1. The system finds the first published IELTS course
2. The system determines the course group (academic_module, general_module, or general_english) based on the course's categories
3. Each selected user is enrolled in that course
4. User metadata is set for partner dashboard visibility:
   - `iw_course_group` - Course group type
   - `iw_membership_expiry` - 30 days from now
   - `iw_membership_status` - 'active'
   - `_ielts_cm_membership_type` - Access role type
   - `_ielts_cm_membership_status` - 'active'
   - `_ielts_cm_membership_expiry` - 30 days from now
5. WordPress role is assigned based on course group:
   - Academic courses → `access_academic_module` role
   - General courses → `access_general_module` role
   - English-only courses → `access_general_english` role
6. Enrollment status is set to 'active'
7. Expiry date is set to exactly 30 days from the current date/time (respecting WordPress timezone)
8. If a user is already enrolled, their enrollment is updated

### Partner Dashboard Visibility
After enrollment, users will:
- Appear in the partner dashboard (Users > Partner Dashboard)
- Have access to courses based on their assigned course group
- Show the correct expiry date (30 days from enrollment)
- Have the appropriate WordPress role for course access

### Database Changes
- Records are created/updated in the `wp_ielts_cm_enrollment` table:
  - `user_id`: The WordPress user ID
  - `course_id`: The IELTS course ID
  - `status`: 'active'
  - `enrolled_date`: Current timestamp
  - `course_end_date`: 30 days from now
- User metadata is set (8 meta fields total)
- WordPress user role is assigned

## Notes for Legacy User Migration
- This is a one-time feature designed for migrating legacy users
- All users get the same 30-day trial period
- After all legacy users are enrolled, this feature can be left in place or removed
- The feature only appears in the WordPress admin area

## Verification Steps
After bulk enrollment, verify the fix is working by:

1. **Check Partner Dashboard**
   - Navigate to the Partner Dashboard
   - Verify enrolled users now appear in the student list
   - Verify expiry dates are shown correctly (30 days from enrollment)

2. **Check User Meta**
   - Go to Users → All Users
   - Edit one of the enrolled users
   - In the Custom Fields section (or use a plugin like "User Meta Manager"), verify these meta fields exist:
     - `iw_course_group` (should be academic_module, general_module, or general_english)
     - `iw_membership_expiry` (should be 30 days from now)
     - `iw_membership_status` (should be 'active')
     - `_ielts_cm_membership_type` (should be access_academic_module, access_general_module, or access_general_english)
     - `_ielts_cm_membership_status` (should be 'active')
     - `_ielts_cm_membership_expiry` (should be 30 days from now)

3. **Check User Role**
   - In the user edit screen, verify the user has one of these roles:
     - `access_academic_module` (for academic courses)
     - `access_general_module` (for general courses)
     - `access_general_english` (for english-only courses)

4. **Check Course Access**
   - Log in as one of the enrolled users
   - Verify they can access the course they were enrolled in
   - Verify they can view course content

## Troubleshooting
If users don't appear in the partner dashboard:
- Check the error log for messages starting with "IELTS Bulk Enrollment:"
- Verify the course has proper categories assigned (academic, general, or english)
- Verify the user meta fields were set correctly
