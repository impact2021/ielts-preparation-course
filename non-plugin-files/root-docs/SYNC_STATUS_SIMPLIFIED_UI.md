# Simplified Sync Status Page - UI Documentation

## Overview
The sync status page has been simplified to show only course-level synchronization status without hierarchical expansion. This makes it easier to quickly see which courses are fully synchronized across all subsites.

## Page Layout

```
╔═══════════════════════════════════════════════════════════════════════════╗
║ WordPress Admin Header                                                    ║
╠═══════════════════════════════════════════════════════════════════════════╣
║                                                                            ║
║  Content Sync Status                                                      ║
║                                                                            ║
║  ┌─────────────────────────────────────────────────────────────────────┐  ║
║  │ [🔄 Check Sync Status]  ✓ Sync status updated                       │  ║
║  │ Last checked: 5 minutes ago                                         │  ║
║  └─────────────────────────────────────────────────────────────────────┘  ║
║                                                                            ║
║  ┌─────────────────────────────────────────────────────────────────────┐  ║
║  │ SIMPLE TABLE                                                        │  ║
║  │ ┌────────────────────────────┬──────────────┬──────────────┐        │  ║
║  │ │ Course (Unit) Name         │ Site A       │ Site B       │        │  ║
║  │ ├────────────────────────────┼──────────────┼──────────────┤        │  ║
║  │ │ IELTS Course 1             │      ✓       │      ✓       │        │  ║
║  │ │ IELTS Course 2             │      ✓       │      ✗       │        │  ║
║  │ │ IELTS Course 3             │      ✗       │      ✗       │        │  ║
║  │ │ Academic Writing Module    │      ✓       │      ✓       │        │  ║
║  │ │ Speaking Practice Course   │      ✗       │      ✓       │        │  ║
║  │ └────────────────────────────┴──────────────┴──────────────┘        │  ║
║  └─────────────────────────────────────────────────────────────────────┘  ║
║                                                                            ║
╚═══════════════════════════════════════════════════════════════════════════╝
```

## Key Features

### 1. Last Check Time
- Displayed below the "Check Sync Status" button
- Shows human-readable time difference (e.g., "5 minutes ago", "2 hours ago")
- Only appears if a check has been performed at least once
- Updates automatically when "Check Sync Status" is clicked

### 2. Check Sync Status Button
- Primary action button at the top of the page
- When clicked:
  1. Shows "Checking sync status..." message
  2. Button disabled during check
  3. Spinning icon animation during check
  4. Updates last check time
  5. Refreshes page to show updated status
  6. Shows success message "✓ Sync status updated"

### 3. Simplified Table
- **Column 1**: Course (Unit) Name
  - Shows the full name of each course
  - Bold text for emphasis
- **Remaining Columns**: One column per connected subsite
  - Column header shows the subsite name
  - Each cell contains either:
    - ✓ (green checkmark) = Course is fully synced
    - ✗ (red cross) = Course is NOT fully synced

### 4. Sync Status Logic
A course is considered "fully synced" (✓) only when ALL of the following are synced:
- The course itself
- All lessons in the course
- All resources in each lesson
- All exercises (quizzes) in each lesson

If ANY component is missing or out of sync, the course shows ✗.

## Comparison: Before vs After

### Before (Old Complex Version)
```
❌ Hierarchical display with expand/collapse
❌ Shows individual lessons, resources, and exercises
❌ Checkboxes for bulk selection
❌ Filter tabs (All, Synced, Out of Sync, Never Synced)
❌ Pagination controls
❌ Bulk sync actions
❌ Individual status badges (Synced, Out of Sync, Never Synced)
❌ 778 lines of code
```

### After (New Simplified Version)
```
✅ Flat list of courses only
✅ Simple ✓/✗ indicators
✅ Last check time display
✅ Single "Check Sync Status" button
✅ No checkboxes or bulk actions
✅ No filters or pagination
✅ Cleaner, more focused interface
✅ 373 lines of code (52% reduction)
```

## User Workflow

### Checking Sync Status
1. User navigates to **IELTS Courses → Sync Status**
2. Page loads showing current sync status
3. User sees "Last checked: X time ago" (if available)
4. User clicks **"Check Sync Status"** button
5. Button shows spinning icon, page shows "Checking sync status..."
6. AJAX request fetches current sync status
7. Page reloads with updated status
8. "Last checked" time updates to "a few seconds ago"

### Interpreting Results
- **Green ✓**: Course and all its content are fully synchronized
  - No action needed
  - Everything is up to date on this subsite
  
- **Red ✗**: Course or some of its content is not synchronized
  - Action required
  - Use Multi-Site Sync page to push updates
  - Or edit individual course to sync

## Visual Elements

### Icons
- **✓ (dashicons-yes)**: Green checkmark
  - Font size: 24px
  - Color: #155724 (dark green)
  - Tooltip: "Course is fully synced"

- **✗ (dashicons-no)**: Red cross
  - Font size: 24px
  - Color: #721c24 (dark red)
  - Tooltip: "Course is not fully synced"

- **🔄 (dashicons-update)**: Spinning refresh icon
  - Appears on button
  - Rotates 360° continuously when checking

### Button States
- **Normal**: Blue background (#0073aa)
- **Hover**: Darker blue (#005a87)
- **Disabled**: Gray (#ddd)
- **Checking**: Spinning icon, disabled state

## Technical Details

### AJAX Action
- **Action Name**: `ielts_cm_check_sync_status`
- **Nonce**: `ielts_cm_sync_status`
- **Response**: Success message with updated status
- **Side Effect**: Updates `ielts_cm_sync_last_check_time` option

### Key Methods
1. `render_page()`: Main page rendering
2. `render_course_row()`: Renders a single course row
3. `check_course_complete_sync()`: Checks if entire course is synced
4. `handle_ajax_check_sync()`: Handles AJAX sync check request

### Data Flow
```
User clicks button
    ↓
AJAX request to handle_ajax_check_sync()
    ↓
Update last_check_time option
    ↓
Get sync status from sync_manager
    ↓
Return success response
    ↓
JavaScript reloads page
    ↓
render_page() displays updated status
    ↓
For each course: check_course_complete_sync()
    ↓
Display ✓ or ✗ based on results
```

## Benefits of Simplification

1. **Faster Loading**: No complex hierarchy to build
2. **Easier to Scan**: Quick visual overview of all courses
3. **Less Cognitive Load**: Simple ✓/✗ vs. complex status badges
4. **Mobile Friendly**: Simpler table layout works better on small screens
5. **Better Performance**: 52% less code to execute
6. **Clearer Purpose**: Focus on course-level sync status only

## Notes

- This page is read-only (checking status only)
- To actually sync content, users must use the Multi-Site Sync page
- The check does NOT perform synchronization, only status verification
- All courses are displayed (no filtering by sync status)
- Courses are ordered alphabetically by title

---

**Last Updated**: 2026-02-14
**Version**: Simplified UI v1.0
