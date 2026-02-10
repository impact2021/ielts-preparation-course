# Completed Badge Fix - Version 15.40

## Issue
The green "Completed" badge/text was appearing on resource pages on the **second visit**, even though the user had not actually completed the content. This was confusing because it made users think they had already finished content when they had only viewed it once before.

## Root Cause
In `templates/single-resource-page.php`, there was logic that automatically marked resources as completed on the second visit:

```php
if ($existing) {
    // Resource has been accessed before - mark as completed if not already
    if (!$is_completed) {
        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, true);
        $is_completed = true;  // ❌ Wrong! Auto-completing on 2nd visit
    }
}
```

This was originally implemented to prevent showing "Completed" on the first visit, but it went too far by auto-completing on the second visit.

## Solution
Resources are now tracked for access (updating the `last_accessed` timestamp) but are **NOT** automatically marked as completed. The "Completed" badge only appears when a resource is explicitly marked as complete through user action.

### New Behavior
```php
if ($existing) {
    // Resource has been accessed before - update last_accessed but keep completed status as-is
    $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, $existing->completed);
} else {
    // First time viewing - track access without marking as completed
    $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, false);
}
```

### What Changed
1. **First visit**: Tracks access with `completed = false` ✅
2. **Second+ visits**: Updates `last_accessed` timestamp, preserves existing `completed` status (stays false until explicitly marked) ✅
3. **Badge display**: Only shows when `is_completed = true` (from database, not auto-set) ✅

## Files Modified
- `templates/single-resource-page.php` - Fixed auto-completion logic (lines 63-82)
- `includes/frontend/class-frontend.php` - Updated documentation comment to reflect correct behavior

## Testing Checklist
- [ ] Navigate to a resource page for the first time as logged-in user
- [ ] Verify NO "Completed" badge appears
- [ ] Leave the page and return to the same resource (second visit)
- [ ] Verify NO "Completed" badge appears on second visit
- [ ] Verify badge ONLY appears when resource is actually marked as complete

## Impact
- **Before**: Confusing - "Completed" badge appeared on second visit
- **After**: Clear - Badge only appears when actually completed
- **Database**: No schema changes, uses existing `completed` field correctly
- **Backwards Compatible**: Existing completion records are preserved

## Technical Details
The `record_progress()` function updates the progress table:
- If record exists: Updates `last_accessed` timestamp and `completed` status
- The key change: We now pass `$existing->completed` instead of hardcoded `true`
- This preserves whatever completion status already exists in the database
- Resources only get marked as complete through explicit user action (e.g., a "Mark as Complete" button if implemented)

## Related Changes
This is part of version 15.40 which also includes:
1. Terminology updates (Course→Unit, sublesson→learning resource)
2. Continue/Next button fix (only show after quiz completion)
3. This Completed badge fix
