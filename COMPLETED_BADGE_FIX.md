# Completed Badge Fix - Version 15.40

## Issue
The green "Completed" badge was appearing on resource pages **on the very first page load**, even though users hadn't read anything yet. This was confusing and nonsensical - a student opens a resource for the first time and immediately sees "Completed" at the top.

## Root Cause Evolution

### First Iteration (Original Bug)
The code was automatically marking resources as completed on the **second visit**:
```php
if ($existing) {
    // Wrong! Auto-completing on 2nd visit
    $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, true);
    $is_completed = true;
}
```

### Second Iteration (Still Buggy)
After fixing the auto-completion, the badge was still showing on **first visit** because it was checking completion status without verifying if the user had visited before:
```php
// Check completion
$is_completed = $progress_tracker->is_resource_completed($user_id, $lesson_id, $resource_id);

// Show badge if completed
if ($user_id && $lesson_id && $is_completed) {
    // Shows badge even on first visit if somehow marked complete
}
```

### Final Fix (Correct)
The badge now requires TWO conditions:
1. A progress record must exist (user has visited before)
2. The resource must be marked as completed

```php
// Check if visited before
if ($existing) {
    $has_visited_before = true;
    $is_completed = (bool) $existing->completed;
} else {
    $has_visited_before = false;  // First visit!
    $is_completed = false;
}

// Show badge ONLY if visited before AND completed
if ($user_id && $lesson_id && $has_visited_before && $is_completed) {
    // Show badge
}
```

## Solution
Added a `$has_visited_before` flag that tracks whether a progress record exists in the database for this user+resource combination.

### First Visit Behavior
When a user opens a resource for the first time:
1. Query database for existing progress record → returns NULL
2. Set `$has_visited_before = false`
3. Set `$is_completed = false`
4. Create new progress record with `completed = false`
5. Badge display check: `$has_visited_before && $is_completed` → `false && false` → **NO BADGE** ✅

### Subsequent Visit Behavior
When a user returns to a resource they've seen before:
1. Query database for existing progress record → returns record
2. Set `$has_visited_before = true`
3. Set `$is_completed = (bool) $existing->completed` from database
4. Update progress record's `last_accessed` timestamp
5. Badge display check: `$has_visited_before && $is_completed` → `true && [db_value]` → **SHOWS IF COMPLETED** ✅

## What Changed
1. **Moved database query** to check for existing record BEFORE checking completion status
2. **Added `$has_visited_before` flag** based on whether progress record exists
3. **Updated badge condition** to require BOTH `$has_visited_before` AND `$is_completed`

## Files Modified
- `templates/single-resource-page.php` - Added first-visit tracking and updated badge display logic

## Impact
**Before Fix:**
- 1st visit: Badge could show if resource was marked complete by admin/bulk operation ❌
- User confusion: "Why does it say completed? I just opened it!"

**After Fix:**
- 1st visit: Badge NEVER shows, regardless of database state ✅
- 2nd+ visits: Badge shows only if actually marked as completed ✅
- Clear user experience: "Completed" only appears after I've been here before

## Technical Details

### The Key Insight
The issue wasn't just about auto-completion on visit N. The real problem was showing ANY completion status on the very first page load. Even if a resource was legitimately marked as complete (by admin, bulk import, etc.), showing "Completed" on first visit is confusing to the user.

The solution: Track whether the user has accessed this resource page before. Only show completion status if they've been here previously.

### Why This Works
- Progress records are created on first visit
- On subsequent visits, the record exists, so we know the user has been here before
- This gives a clean UX: first visit shows clean page, subsequent visits show status

## Testing Checklist
- [ ] Navigate to a resource you've NEVER visited before as logged-in user
- [ ] Verify NO "Completed" badge appears at the top
- [ ] Reload the page (second visit)
- [ ] Verify badge still doesn't appear (resource not completed)
- [ ] If resource gets marked as complete somehow
- [ ] Reload page again
- [ ] Verify badge NOW appears (visited before + completed = show badge)

## Related Changes
This is part of version 15.40 which also includes:
1. Terminology updates (Course→Unit, sublesson→learning resource)
2. Continue/Next button fix (only show after quiz completion)
3. This Completed badge fix (multiple iterations to get it right)

