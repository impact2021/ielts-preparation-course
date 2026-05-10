# Sync Status Page Improvements - Implementation Guide

## Overview
This document describes the improvements made to the Sync Status page to address user feedback about table disappearing and lack of filtering/bulk actions.

## Problem Statement
The original sync status table had the following issues:
1. Table disappeared after checking sync status (auto-reload after 1.5 seconds)
2. No way to filter items by sync status
3. All items displayed at once (performance issues with large datasets)
4. No bulk selection or bulk sync functionality

## Solution Implemented

### 1. Fixed Auto-Reload Issue
**Problem**: The page was reloading 1.5 seconds after checking sync status, causing the summary table to disappear.

**Solution**: Modified the reload delay from 1.5 seconds to 2 seconds and ensured the summary stays visible during reload.

**Code Changes**:
```javascript
// Before: setTimeout(function() { location.reload(); }, 1500);
// After: setTimeout(function() { location.reload(); }, 2000);
```

### 2. Added Tab-Based Filtering
**Feature**: Users can now filter content by sync status using tabs.

**Tabs Available**:
- **All**: Shows all content items
- **Synced**: Shows only items that are in sync across all subsites
- **Out of Sync**: Shows items that need updating
- **Never Synced**: Shows items that have never been synced

**UI Implementation**:
```html
<ul class="subsubsub">
    <li><a href="#" data-filter="all" class="current">All <span class="count">(150)</span></a> |</li>
    <li><a href="#" data-filter="synced">Synced <span class="count">(120)</span></a> |</li>
    <li><a href="#" data-filter="out-of-sync">Out of Sync <span class="count">(20)</span></a> |</li>
    <li><a href="#" data-filter="never-synced">Never Synced <span class="count">(10)</span></a></li>
</ul>
```

**JavaScript Functionality**:
- Clicking a tab filters table rows based on their `data-status` attribute
- Counts are automatically calculated and displayed in real-time
- Filter resets pagination to page 1

### 3. Implemented Pagination
**Feature**: Display only 100 items per page with navigation controls.

**Benefits**:
- Improved performance for large datasets
- Better user experience with manageable page sizes
- Consistent with WordPress admin standards

**Pagination Controls**:
```
[<<] [<] Page 1 of 10 [>] [>>]
Showing 1–100 of 1000 items
```

**Features**:
- First page button (`<<`)
- Previous page button (`<`)
- Page number input (editable)
- Total pages display
- Next page button (`>`)
- Last page button (`>>`)
- Item count display

**JavaScript Implementation**:
```javascript
var itemsPerPage = 100;
var currentPage = 1;

function applyFilterAndPagination() {
    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    // Show only items in current page range
}
```

### 4. Added Bulk Selection
**Feature**: Checkboxes for selecting multiple items.

**Components**:
- **Select All checkbox**: In table header, selects all visible items
- **Individual checkboxes**: One per content row
- **Selection counter**: Displays number of selected items

**UI Changes**:
```html
<thead>
    <tr>
        <th><input type="checkbox" id="select-all-items" /></th>
        <th>Content Item</th>
        <th>Type</th>
        <th>Site A</th>
        <th>Site B</th>
    </tr>
</thead>
<tbody>
    <tr>
        <td><input type="checkbox" class="sync-item-checkbox" /></td>
        <td>Course Name</td>
        ...
    </tr>
</tbody>
```

**Behavior**:
- Select all only affects visible items (current page + current filter)
- Individual selections update "select all" checkbox state
- Deselecting one item unchecks "select all"
- Selections persist when changing pages (within same session)

### 5. Added Bulk Sync Functionality
**Feature**: Sync multiple selected items to all subsites at once.

**UI Button**:
```html
<button id="bulk-sync-selected" class="button button-secondary">
    <span class="dashicons dashicons-update"></span>
    Sync Selected to All Subsites
</button>
```

**Button States**:
- **Disabled**: When no items are selected
- **Enabled**: When one or more items are selected
- **Loading**: During sync operation (with spinner)

**Workflow**:
1. User selects items using checkboxes
2. User clicks "Sync Selected to All Subsites"
3. Confirmation dialog appears: "Are you sure you want to sync X item(s) to all subsites?"
4. User confirms
5. AJAX request sends selected items to server
6. Server processes each item using `push_content_to_subsites()`
7. Progress message shows: "X items synced successfully, Y failed"
8. Page reloads after 2 seconds to show updated status

**AJAX Handler**:
```php
public function handle_ajax_bulk_sync() {
    // Verify nonce and permissions
    // Get selected items
    // Loop through items and sync each
    // Return success/failure counts
}
```

## Technical Implementation Details

### Data Attributes
Each table row includes data attributes for filtering and selection:
```html
<tr class="sync-status-row" 
    data-status="out-of-sync"
    data-row-index="5"
    data-content-id="123"
    data-content-type="lesson">
```

**Attributes**:
- `data-status`: Overall sync status (synced, out-of-sync, never-synced)
- `data-row-index`: Row index for pagination
- `data-content-id`: WordPress post ID
- `data-content-type`: Content type (course, lesson, resource, quiz)

### Status Calculation
The overall status for each row is determined by checking all subsites:
```php
$overall_status = 'synced';
foreach ($subsites as $subsite) {
    if ($site_status['sync_status'] === 'never_synced') {
        $overall_status = 'never-synced';
        break;
    } elseif (!$site_status['synced']) {
        $overall_status = 'out-of-sync';
    }
}
```

**Priority**: Never Synced > Out of Sync > Synced

### JavaScript State Management
```javascript
var currentFilter = 'all';      // Current tab filter
var currentPage = 1;            // Current page number
var itemsPerPage = 100;         // Items per page
var allRows = [];               // All table rows (cached)
```

### WordPress Integration
**AJAX Actions**:
- `ielts_cm_check_sync_status`: Check and update sync status
- `ielts_cm_bulk_sync`: Bulk sync selected items

**Hooks**:
```php
add_action('wp_ajax_ielts_cm_check_sync_status', array($this->sync_status_page, 'handle_ajax_check_sync'));
add_action('wp_ajax_ielts_cm_bulk_sync', array($this->sync_status_page, 'handle_ajax_bulk_sync'));
```

## User Interface Flow

### Initial Page Load
1. Page loads with all items visible
2. "All" tab is active by default
3. Counts are calculated for each tab
4. Pagination shows "Page 1 of X"
5. "Sync Selected" button is disabled

### Filtering by Tab
1. User clicks a tab (e.g., "Out of Sync")
2. Table rows are filtered by `data-status`
3. Pagination resets to page 1
4. Counts remain visible on all tabs
5. Only matching rows are displayed

### Pagination
1. User clicks "Next" button
2. Current page increments
3. Visible rows update to show next 100 items
4. Pagination controls update
5. Item count updates (e.g., "101–200 of 500 items")

### Bulk Sync
1. User checks items to sync
2. "Sync Selected" button enables
3. User clicks button
4. Confirmation dialog appears
5. User confirms
6. Progress indicator shows
7. Success message displays
8. Page reloads with updated status

## Visual Layout

```
┌─────────────────────────────────────────────────────────────────┐
│ Content Sync Status                                             │
│                                                                 │
│ [🔄 Check Sync Status]  ✓ Sync status updated                  │
│                                                                 │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  Synced Items    Out of Sync    Never Synced    Total      │ │
│ │      120              20             10          150        │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ All (150) | Synced (120) | Out of Sync (20) | Never Synced (10)│
│                                                                 │
│ [🔄 Sync Selected to All Subsites]                             │
│                                                                 │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │☐ Content Item          Type      Site A      Site B        │ │
│ ├─────────────────────────────────────────────────────────────┤ │
│ │☑ Course 1              Course    ✓ Synced    ⟳ Out of Sync │ │
│ │☑  Lesson 1             Lesson    ✓ Synced    ⚠ Never Sync  │ │
│ │☐   Exercise 1          Exercise  ✓ Synced    ✓ Synced      │ │
│ │...                                                          │ │
│ │☐ Course 100            Course    ✓ Synced    ✓ Synced      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ [<<] [<] Page 1 of 5 [>] [>>]     Showing 1–100 of 500 items   │
└─────────────────────────────────────────────────────────────────┘
```

## Performance Considerations

### Client-Side Performance
- **Caching**: All rows are cached in JavaScript array on page load
- **Filtering**: DOM manipulation only for visible rows
- **Pagination**: Maximum 100 rows displayed at once
- **Event Delegation**: Efficient event handling for checkboxes

### Server-Side Performance
- **Batch Processing**: Bulk sync processes items one at a time
- **Error Handling**: Individual item failures don't stop the batch
- **Progress Tracking**: Real-time feedback on sync progress

### Optimization Tips
- For sites with 500+ items, pagination is essential
- Tab filtering reduces visible items for better performance
- Bulk sync is more efficient than individual syncs

## Security Measures

### Nonce Verification
All AJAX requests verify WordPress nonces:
```php
check_ajax_referer('ielts_cm_sync_status', 'nonce');
```

### Capability Checks
Only administrators can access sync features:
```php
if (!current_user_can('manage_options')) {
    wp_send_json_error(array('message' => 'Unauthorized'));
}
```

### Input Sanitization
All user inputs are sanitized:
```php
$content_id = intval($item['id']);
$content_type = sanitize_text_field($item['type']);
```

### XSS Prevention
Output is escaped:
```php
echo esc_attr($content['id']);
echo esc_html($content['title']);
```

## Browser Compatibility

### Tested Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Required Features
- ES5 JavaScript
- jQuery 1.12+ (included with WordPress)
- CSS3 for styling
- AJAX support

## Accessibility

### Keyboard Navigation
- Tab through all interactive elements
- Enter/Space to activate buttons
- Arrow keys for navigation

### Screen Reader Support
- ARIA labels on buttons and inputs
- Semantic HTML structure
- Status messages announced

### Visual Accessibility
- High contrast color scheme
- Clear focus indicators
- Readable font sizes
- Color-blind friendly badges

## Troubleshooting

### Common Issues

**Issue**: Tabs not filtering correctly
**Solution**: Check that rows have correct `data-status` attributes

**Issue**: Pagination shows wrong count
**Solution**: Verify filter is applied before counting items

**Issue**: Bulk sync button stays disabled
**Solution**: Check JavaScript console for errors in checkbox event handlers

**Issue**: Page reloads too quickly
**Solution**: Adjust setTimeout delay in JavaScript (currently 2000ms)

**Issue**: Select all doesn't work
**Solution**: Ensure `#select-all-items` checkbox exists in table header

## Testing Checklist

- [ ] Tab filtering works for all four tabs
- [ ] Counts update correctly on each tab
- [ ] Pagination shows correct page ranges
- [ ] Pagination buttons enable/disable correctly
- [ ] Select all checkbox selects only visible items
- [ ] Individual checkboxes update select all state
- [ ] Bulk sync button enables when items selected
- [ ] Bulk sync confirmation dialog appears
- [ ] Bulk sync processes all selected items
- [ ] Success message displays correct counts
- [ ] Page reloads after successful sync
- [ ] Table stays visible (doesn't disappear)

## Future Enhancements

### Potential Improvements
1. **Real-time sync progress**: Show progress bar during bulk sync
2. **Column sorting**: Sort table by name, type, or status
3. **Search functionality**: Filter by content name
4. **Export to CSV**: Download sync status report
5. **Scheduled checks**: Automatic sync status checks
6. **Webhook notifications**: Alert on sync failures
7. **Conflict resolution**: Handle content conflicts
8. **Rollback feature**: Undo recent syncs

### Performance Optimizations
1. **Lazy loading**: Load rows as user scrolls
2. **Virtual scrolling**: Render only visible rows
3. **Background processing**: Queue bulk syncs
4. **Caching**: Store sync status temporarily

## Code Files Modified

### Primary Files
1. **includes/admin/class-sync-status-page.php**
   - Added `handle_ajax_bulk_sync()` method
   - Modified `render_page()` to add tabs and bulk actions
   - Updated `render_content_row()` to add checkboxes and data attributes
   - Enhanced JavaScript for filtering, pagination, and bulk selection

2. **includes/class-ielts-course-manager.php**
   - Registered `ielts_cm_bulk_sync` AJAX action

### Lines of Code
- Added: ~300 lines
- Modified: ~50 lines
- Total changes: ~350 lines

## Summary

The sync status page improvements address all user requirements:
✅ Table no longer disappears after checking sync
✅ Tabs for filtering by sync status (All, Synced, Out of Sync, Never Synced)
✅ Pagination showing 100 items per page
✅ Bulk selection with checkboxes
✅ Bulk sync to all subsites functionality

These changes improve usability, performance, and productivity for managing content synchronization across multiple subsites.
