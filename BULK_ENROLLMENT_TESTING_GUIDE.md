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
2. Each selected user is enrolled in that course
3. Enrollment status is set to 'active'
4. Expiry date is set to exactly 30 days from the current date/time (respecting WordPress timezone)
5. If a user is already enrolled, their enrollment is updated

### Database Changes
- Records are created/updated in the `wp_ielts_cm_enrollment` table
- Each record includes:
  - `user_id`: The WordPress user ID
  - `course_id`: The IELTS course ID
  - `status`: 'active'
  - `enrolled_date`: Current timestamp
  - `course_end_date`: 30 days from now

## Notes for Legacy User Migration
- This is a one-time feature designed for migrating legacy users
- All users get the same 30-day trial period
- After all legacy users are enrolled, this feature can be left in place or removed
- The feature only appears in the WordPress admin area
