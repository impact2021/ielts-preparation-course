# Bulk Enrollment Debugger - Complete Guide

## Overview

A visible debugger panel has been added to the `/wp-admin/users.php` page to help diagnose and fix bulk enrollment issues. The debugger provides real-time information about:

- Course availability
- Category configuration
- Recent enrollment activity
- Error messages and warnings
- System status

## What Was Added

### 1. Visible Debug Panel

A floating panel appears on the bottom-right of the `/wp-admin/users.php` page showing:

- **System Status**: Whether the system is operational or has issues
- **Course Statistics**: Total courses, published courses, and academic courses
- **Available Categories**: All course categories in the system
- **Published Courses**: Detailed list with IDs, titles, and categories
- **Recent Activity Log**: Timestamped logs of enrollment attempts
- **Current Action Status**: Success or error messages from bulk enrollment
- **Troubleshooting Tips**: Quick reference guide

### 2. Enhanced Logging

All bulk enrollment operations are now logged with:
- Timestamps for each operation
- Course query results
- User enrollment success/failure
- Error messages and warnings

Logs are:
- Stored in WordPress transients for 30 minutes
- Visible in the debug panel
- Also written to WordPress error log
- Limited to last 50 entries

### 3. Interactive Features

The debug panel includes:
- **Draggable**: Click and drag the header to reposition
- **Collapsible**: Click the minus button to minimize
- **Clear Log**: Button to reset the activity log
- **Color Coding**: Red for errors, yellow for warnings, green for success

## How to Use the Debugger

### Step 1: Access the Debug Panel

1. Log into WordPress admin
2. Navigate to **Users → All Users** (`/wp-admin/users.php`)
3. The debug panel appears automatically in the bottom-right corner

### Step 2: Check System Status

Look at the top section of the debug panel:
- **Green "✓ System operational"** = Everything is configured correctly
- **Red "⚠️ No published courses found!"** = Critical issue, no courses exist

### Step 3: Review Course Information

Check the statistics:
```
📚 Course Statistics:
• Total courses (all statuses): X
• Published courses: X
• Academic module courses: X
```

**What to look for:**
- If "Published courses" is 0 → You need to create and publish courses
- If "Academic module courses" is 0 → You need to add academic categories to courses

### Step 4: Verify Categories

Check the "Available Categories" section:
```
🏷️ Available Categories:
• Academic (academic)
• General Training (general)
• etc.
```

**Required categories for bulk enrollment:**
- `academic` or `academic-practice-tests`

### Step 5: Try Bulk Enrollment

1. Select one or more users from the user list
2. Choose **"Enroll in Academic Module (Access Code) - 30 days"** from bulk actions
3. Click **Apply**
4. Watch the debug panel's "Recent Activity Log" section

### Step 6: Review Activity Log

The log shows detailed information:
```
📝 Recent Activity Log:
[2026-02-07 10:30:15] Bulk enrollment started for 3 user(s)
[2026-02-07 10:30:15] Academic courses found: 5
[2026-02-07 10:30:15] Selected course ID: 123 (Academic IELTS Reading)
[2026-02-07 10:30:15] User ID 45 enrolled successfully
[2026-02-07 10:30:15] User ID 46 enrolled successfully
[2026-02-07 10:30:15] User ID 47 enrolled successfully
[2026-02-07 10:30:16] Bulk enrollment completed. Total enrolled: 3
```

## Common Issues and Solutions

### Issue 1: "No published courses found!"

**Symptoms:**
- Red warning at top of debug panel
- URL shows `ielts_bulk_enroll=no_courses_at_all`
- No users are enrolled

**Solution:**
1. Go to **IELTS Courses → Add New**
2. Create a course with any title
3. Click **Publish**
4. Go back to Users page and try again

### Issue 2: "No Academic courses found"

**Symptoms:**
- Yellow warning in activity log
- Users are enrolled but in wrong course
- Shows "Fallback: Total courses found: X"

**Solution:**
1. Go to **IELTS Courses → All Courses**
2. Edit your course
3. On the right side, find **Course Categories**
4. Check "Academic" or create it if it doesn't exist
5. Click **Update**
6. Try bulk enrollment again

### Issue 3: No categories available

**Symptoms:**
- "Available Categories" section shows "No categories found"

**Solution:**
1. Go to **IELTS Courses → Course Categories**
2. Create these categories (use exact slugs):
   - Name: **Academic**, Slug: **academic**
   - Name: **Academic Practice Tests**, Slug: **academic-practice-tests**
3. Assign categories to your courses
4. Try bulk enrollment again

### Issue 4: Users enrolled but not showing in partner dashboard

**Symptoms:**
- Enrollment succeeds (green message)
- Activity log shows successful enrollments
- Users don't appear in partner dashboard

**Check:**
1. Verify user meta fields were set (use a database tool):
   - `iw_course_group` = 'academic_module'
   - `iw_membership_expiry` = future date
   - `iw_membership_status` = 'active'
2. Check activity log for any error messages about user meta

## Testing Your Setup

### Quick Test Script

A test script is available at `/test-bulk-enrollment.php`. Run it via WP-CLI:

```bash
wp eval-file test-bulk-enrollment.php
```

This will:
- Check if post types and taxonomies are registered
- Count courses by status
- List all categories
- Show detailed course information
- Provide recommendations

### Manual Testing Checklist

- [ ] Debug panel appears on `/wp-admin/users.php`
- [ ] System status shows green or red correctly
- [ ] Course statistics match expected values
- [ ] Categories are listed
- [ ] Published courses are shown with details
- [ ] Bulk enrollment logs appear in activity log
- [ ] Clear log button works
- [ ] Panel can be dragged
- [ ] Panel can be collapsed/expanded

## Understanding the Debug Panel

### System Status Colors

- 🟢 **Green (d1ecf1 background)**: System operational, courses available
- 🔴 **Red (f8d7da background)**: Critical error, no courses

### Activity Log Colors

- ⚫ **Black**: Informational messages
- 🟡 **Yellow**: Warnings (non-critical issues)
- 🔴 **Red**: Errors (enrollment failures)

### Troubleshooting Section

Always visible at the bottom with quick tips:
```
💡 Troubleshooting:
• If no courses are found, create an IELTS course first
• Ensure courses are published (not draft)
• Add "academic" or "academic-practice-tests" category
• Check WordPress error logs for details
```

## Technical Details

### Files Modified

- `includes/admin/class-bulk-enrollment.php`
  - Added `$debug_log` property
  - Added `log_debug()` method
  - Added `render_debug_panel()` method
  - Added `clear_debug_log_ajax()` AJAX handler
  - Enhanced `handle_bulk_action()` with logging

### WordPress Hooks Used

- `admin_footer-users.php`: Renders the debug panel
- `wp_ajax_clear_bulk_enrollment_debug_log`: Handles log clearing

### Data Storage

- Logs stored in WordPress transient: `ielts_bulk_enrollment_debug_log`
- Expiration: 30 minutes
- Maximum entries: 50 (automatically trimmed)

### JavaScript Features

- jQuery for DOM manipulation
- Vanilla JS for drag functionality
- AJAX for log clearing
- Event handlers for collapse/expand

## Maintenance

### Clearing Old Logs

Logs automatically expire after 30 minutes. To manually clear:
1. Click "Clear Log" button in debug panel, OR
2. Delete transient in database: `DELETE FROM wp_options WHERE option_name = '_transient_ielts_bulk_enrollment_debug_log'`

### Disabling the Debugger

To temporarily disable the debug panel, comment out this line in `class-bulk-enrollment.php`:

```php
// add_action('admin_footer-users.php', array($this, 'render_debug_panel'));
```

### Production Considerations

The debugger is designed to be safe in production:
- Only visible to WordPress admins
- Only shows on users.php page
- Does not impact performance
- Logs expire automatically
- No sensitive data exposed

## Next Steps

1. **Test the debugger** by navigating to `/wp-admin/users.php`
2. **Create test courses** if none exist
3. **Try bulk enrollment** on test users
4. **Review activity logs** to identify any issues
5. **Report findings** based on what the debugger shows

## Support

If you encounter issues with the debugger itself:
1. Check WordPress error log for PHP errors
2. Check browser console for JavaScript errors
3. Verify jQuery is loaded on the users.php page
4. Ensure the plugin is activated

The debugger will help identify the root cause of the enrollment issue by providing real-time visibility into what's happening during the bulk enrollment process.
