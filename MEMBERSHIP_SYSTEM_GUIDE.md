# Membership System Guide (v1.7)

This guide explains how to use the new membership management features added in version 1.7.

## Table of Contents
1. [Admin: Managing User Enrollments](#admin-managing-user-enrollments)
2. [Admin: Modifying Course Access](#admin-modifying-course-access)
3. [User: My Account Page](#user-my-account-page)
4. [Understanding Lesson Completion](#understanding-lesson-completion)

---

## Admin: Managing User Enrollments

### Accessing the Enrollment Manager

1. Log in to WordPress admin
2. Navigate to **IELTS Courses > Manage Enrollments**

### Creating a New User and Enrolling Them

Perfect for manually adding students to your courses:

1. In the **"Create New User and Enroll"** section:
   - **Username**: Enter a unique username (required)
   - **Email**: Enter the user's email address (required)
   - **Password**: Set a password for the user (required)
   - **First Name**: Optional
   - **Last Name**: Optional
   - **Enroll in Courses**: Select one or more courses (Hold Ctrl/Cmd for multiple)

2. Click **"Create User and Enroll"**

3. The user will be:
   - Created in WordPress
   - Automatically enrolled in all selected courses
   - Given 1 year of access (365 days from enrollment date)

### Enrolling an Existing User

To add courses to users who already have WordPress accounts:

1. In the **"Enroll Existing User"** section:
   - **Select User**: Choose from dropdown of all WordPress users
   - **Enroll in Courses**: Select one or more courses

2. Click **"Enroll User"**

3. The user will be enrolled with 1 year access to selected courses

---

## Admin: Modifying Course Access

### Viewing All Enrollments

The **"Current Enrollments"** table shows:
- User name
- Course name
- Enrolled date
- Current end date (course access expiration)
- Status (Active/Inactive)

### Updating Course End Dates

To extend or modify a user's access:

1. Find the enrollment in the **"Current Enrollments"** table
2. In the **End Date** column, change the date using the date picker
3. Click the **"Update"** button for that row

**Note**: The end date determines when the user loses access to the course. Set it to a future date to extend access.

### Deleting an Enrollment

To remove a user's access to a course:

1. Find the enrollment in the table
2. Click the **"Delete"** button
3. Confirm the deletion

**Warning**: This will mark the enrollment as inactive and remove the user's access.

---

## User: My Account Page

### Adding the Account Page to Your Site

Create a new WordPress page and add the shortcode:

```
[ielts_my_account]
```

This displays a complete account dashboard for logged-in users.

### What Users See

The My Account page shows:

#### User Information Section
- Username
- Email address
- Full name

#### Course Enrollments Section
For each enrolled course:
- **Course Title** (clickable link)
- **Enrolled Date**: When they were enrolled
- **Access Until**: Course access expiration date
- **Progress**: Completion percentage with visual progress bar
- **Status**: Active or Expired indicator
- **Action Button**: 
  - "Continue Learning" for active courses
  - Expiration notice for expired courses

### Expired Course Handling

When a course access expires:
- The course appears grayed out
- A red "Expired" badge is shown
- Progress is still visible (historical data)
- User sees a message to contact support for renewal
- "Continue Learning" button is replaced with expiration notice

---

## Understanding Lesson Completion

### How Lesson Completion Works (NEW in v1.7)

A lesson is marked as **"Completed"** only when BOTH conditions are met:

1. **All Sublesson Pages Viewed**: Every sublesson (resource) in the lesson must be viewed
2. **All Exercises Attempted**: Every quiz/exercise in the lesson must be attempted at least once

### Why This Matters

- **Accurate Progress Tracking**: Prevents lessons from showing as complete prematurely
- **Course Completion**: Course completion percentage only counts truly finished lessons
- **Student Motivation**: Clear indication of what still needs to be done

### Checking Progress

**For Admins:**
- Go to **IELTS Courses > Progress Reports**
- View detailed progress for all users

**For Students:**
- Use the `[ielts_my_progress]` shortcode or `[ielts_my_account]` shortcode
- See completion percentage for each course
- Track which lessons are completed

---

## Best Practices

### For Course Access Duration

1. **Standard Duration**: Default 1-year access is suitable for most courses
2. **Short Courses**: For intensive short courses, consider 3-6 months
3. **Ongoing Support**: For courses with lifetime updates, set far-future dates (e.g., 10 years)
4. **Renewals**: When renewing, set new end date from current date, not old end date

### For User Management

1. **Bulk Enrollment**: When enrolling many users, create them first, then use the existing user enrollment feature
2. **Documentation**: Keep records of who was enrolled when and for how long
3. **Communication**: Notify users of their access dates and how to access their account page
4. **Expiration Reminders**: Set up external reminders 30 days before access expires

### For Content Structure

1. **Clear Sublesson Organization**: Ensure sublessons (resources) are properly assigned to lessons
2. **Quiz Placement**: Assign quizzes to specific lessons, not just courses
3. **Progress Monitoring**: Regularly check progress reports to see where students get stuck

---

## Troubleshooting

### "User already exists" Error
- The username or email is already in use
- Try a different username or enroll the existing user instead

### Course Not Showing in Enrollment List
- Ensure the course is published (not draft)
- Refresh the page

### User Can't See Enrolled Courses
- Verify they're logged in
- Check the enrollment table shows their enrollment as "active"
- Verify the course_end_date hasn't passed

### Lesson Shows as Incomplete Despite Viewing All Content
- Check that ALL sublesson pages have been viewed
- Ensure ALL exercises/quizzes have been attempted (not just passed)
- Progress tracking requires page visits and quiz submissions

---

## Technical Notes

### Database Changes in v1.7

The enrollment table now includes:
- `course_end_date` (datetime): When course access expires
- Existing enrollments are updated automatically on plugin update

### Shortcode Reference

```
[ielts_my_account]        - User account dashboard
[ielts_my_progress]       - User's progress across all courses
[ielts_courses]           - List all courses
[ielts_course id="123"]   - Single course display
```

### Access Control

- Users automatically lose access when `course_end_date` passes
- Expired courses still appear in user account but with limitations
- Progress data is preserved even after expiration

---

## Support

For issues or questions:
- Check the [CHANGELOG.md](CHANGELOG.md) for recent updates
- Review [PLUGIN_README.md](PLUGIN_README.md) for detailed documentation
- Visit the GitHub repository for the latest information
