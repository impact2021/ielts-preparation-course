# Sync Status Admin Page - Documentation

## Overview

A new admin page has been added to the IELTS Course Manager plugin that allows administrators to view the synchronization status of all course content across connected subsites.

## Location

The new page can be found in the WordPress admin under:
**IELTS Courses → Sync Status**

## Features

### 1. Comprehensive Content Hierarchy Display

The page displays all courses and their complete hierarchy:
- **Courses** (top level)
  - **Lessons** (indented once)
    - **Sub-lessons/Resources** (indented twice)
    - **Exercises/Quizzes** (indented twice)

### 2. Per-Subsite Sync Status

For each content item, the page shows sync status across all connected subsites with color-coded badges:

- **Green (Synced)**: Content is up-to-date on the subsite
  - Shows a checkmark icon
  - Displays time since last sync
  
- **Yellow (Out of Sync)**: Content exists but is outdated
  - Shows an update icon
  - Displays time since last sync
  - Indicates content needs to be re-synced
  
- **Red (Never Synced)**: Content has never been pushed to this subsite
  - Shows a warning icon
  - No sync timestamp

### 3. Check Sync Status Button

A prominent button at the top of the page allows administrators to:
- Trigger a comprehensive sync status check
- View summary statistics (total items, synced, out of sync, never synced)
- See updated status for all content items

The button uses AJAX to update the status without reloading the page, with a loading animation while checking.

### 4. Summary Dashboard

After clicking "Check Sync Status", a summary dashboard appears showing:
- **Synced Items**: Count of content items fully synced across all subsites
- **Out of Sync**: Count of items that need updating
- **Never Synced**: Count of items that have never been pushed
- **Total Items**: Total count of all content items

## Technical Implementation

### New PHP Classes and Methods

#### `IELTS_CM_Sync_Status_Page` class
Located in `includes/admin/class-sync-status-page.php`

**Methods:**
- `add_menu_page()`: Registers the admin menu page
- `render_page()`: Renders the sync status UI
- `handle_ajax_check_sync()`: Handles AJAX requests for status checks
- `render_content_row()`: Renders individual content rows with status

#### New Methods in `IELTS_CM_Multi_Site_Sync` class

**`get_all_courses_with_hierarchy()`**
- Returns complete course structure with lessons, resources, and exercises
- Builds hierarchical data structure for display

**`get_content_sync_status($content_id, $content_type)`**
- Gets sync status for a specific content item across all subsites
- Compares current content hash with last synced hash
- Returns detailed status including sync date and status

**`get_all_content_sync_status()`**
- Gets comprehensive sync status for all content
- Returns summary statistics
- Iterates through entire content hierarchy

**`update_status_for_content($content, &$status_summary)`** (private helper)
- Updates status summary for a single content item
- Tracks counts for synced, out-of-sync, and never-synced items

### Database Tables Used

The feature uses the existing `wp_ielts_cm_content_sync` table:
- `content_id`: ID of the content item
- `content_type`: Type (course, lesson, resource, quiz)
- `content_hash`: SHA-256 hash of content for change detection
- `site_id`: ID of the subsite
- `sync_date`: Timestamp of last sync
- `sync_status`: Status (success/failed)

## Prerequisites

To use this feature:

1. **Site must be configured as Primary**
   - Go to **IELTS Courses → Multi-Site Sync**
   - Set site role to "Primary Site"

2. **Subsites must be connected**
   - Add at least one subsite connection
   - Verify connection is active and working

If these prerequisites aren't met, the page displays appropriate warning messages.

## User Workflow

1. Navigate to **IELTS Courses → Sync Status**
2. View the current sync status of all content
3. Click **"Check Sync Status"** button to refresh
4. Review the summary dashboard
5. Identify which content items need syncing
6. Use the Multi-Site Sync page or individual content editors to push updates

## UI Elements

### Color Coding
- **Green badges**: Everything is in sync ✓
- **Yellow badges**: Content needs updating ⟳
- **Red badges**: Content missing on subsite ⚠

### Table Layout
- **Content Item column**: Shows hierarchical structure with indentation
- **Type column**: Identifies content type (Course, Lesson, Sub-lesson, Exercise)
- **Subsite columns**: One column per connected subsite showing status

### Interactive Elements
- **Check Sync Status button**: Large, primary button with rotating icon during check
- **Status badges**: Clickable for future enhancements
- **Timestamp display**: Shows relative time (e.g., "2 hours ago")

## Security

- Page requires `manage_options` capability (administrator access)
- AJAX requests use WordPress nonces for CSRF protection
- All data is sanitized and escaped for display

## Future Enhancements

Potential improvements could include:
- Bulk sync actions (sync all out-of-sync items)
- Individual item sync buttons
- Filtering by sync status
- Export sync report
- Email notifications for sync issues
- Scheduled automatic sync checks

## Version Information

- Added in: Version 15.4 (unreleased)
- Requires: WordPress 5.8+
- Requires PHP: 7.2+
