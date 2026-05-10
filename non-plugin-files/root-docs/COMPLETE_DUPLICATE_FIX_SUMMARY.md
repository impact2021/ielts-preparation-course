# Subsite Duplicate Content Fix - Complete Solution

## Problem Summary

Users reported that subsites had duplicate sublessons and exercises appearing in lesson menus, with incorrect counts. For example:
- Primary site: `/ielts-lesson-page/reading-in-detail/`
- Subsite had: `/reading-in-detail/`, `/reading-in-detail-2/`, `/reading-in-detail-3/`
- Counts showed 20 items when primary had 10

## User Requirement
> "I just want them removed from the lesson menu - don't give a shit if they're still on the subsite so long as they're not connected and the sublesson, exercise and video counts should match the primary."

## Two-Part Solution

### Part 1: Version 15.31 - Prevent New Duplicates

**Root Cause**: `find_existing_content()` failed to find existing posts due to WordPress `get_posts()` limitations with `post_status => 'any'`. Every sync created NEW posts instead of updating existing ones.

**Fixes**:
1. Rewrote `find_existing_content()` using direct SQL query
2. Added DISTINCT to `sync_course_lessons()` query
3. Added DISTINCT to `sync_lesson_pages()` query
4. Added type casting for consistent meta_value comparison

**Result**: Syncing now UPDATES existing posts instead of creating duplicates with auto-incremented slugs.

### Part 2: Version 15.32 - Clean Up Existing Duplicates

**Problem**: Even after 15.31, existing duplicate posts were still connected to lessons/courses.

**Solution**: Added automatic cleanup during sync:
1. `cleanup_duplicate_lesson_connections()` - Finds and disconnects duplicate resources/exercises from lessons
2. `cleanup_duplicate_course_connections()` - Finds and disconnects duplicate lessons from courses

**How It Works**:
```
1. Sync runs normally (updates content)
2. Cleanup method executes:
   - Finds all posts connected to this lesson/course
   - Groups by original_id
   - Identifies duplicates (multiple posts with same original_id)
   - Keeps first post (lowest ID)
   - Disconnects all others by removing metadata
3. Counts now match primary site
```

**Result**: Duplicate posts remain in database but are disconnected from lessons. Only one item per original_id appears in menus.

## What Happens to Duplicates?

### They Are Disconnected, Not Deleted
- Posts like `reading-in-detail-2` still exist in WordPress
- But their `_ielts_cm_lesson_id` metadata is removed
- They no longer appear in lesson menus
- They don't count in lesson statistics
- Site admins can manually delete them later if desired

### Why Not Delete Them?
- **Safety**: Preserves any student progress data
- **Flexibility**: Admins can review before permanent deletion
- **Reversibility**: Can reconnect if needed
- **Non-destructive**: Safer approach

## Complete Fix Workflow

### Before Any Fixes (Broken State)
```
Sync Course → Creates duplicate posts every time
lesson-page, lesson-page-2, lesson-page-3...
Count: 10 → 20 → 30 → 40 (grows infinitely)
```

### After Version 15.31 (Prevention)
```
Sync Course → Updates existing posts (no new duplicates)
But: lesson-page-2, lesson-page-3 still connected
Count: Still incorrect (shows 30 when should be 10)
```

### After Version 15.32 (Cleanup)
```
Sync Course → Updates existing posts + disconnects duplicates
lesson-page connected to lesson
lesson-page-2, lesson-page-3 disconnected from lesson
Count: Correct! (shows 10, matches primary)
```

## Technical Details

### Metadata Removed During Disconnect
For each duplicate post, the cleanup removes:
- `_ielts_cm_lesson_id` - Singular lesson association
- Entry from `_ielts_cm_lesson_ids` array - Plural lesson associations
- `_ielts_cm_course_id` - Singular course association (for lessons)
- Entry from `_ielts_cm_course_ids` array - Plural course associations (for lessons)

### Which Duplicate Is Kept?
The post with the **lowest post ID** is kept. This is typically:
- The first post created (original)
- The oldest post
- Often the one with the "clean" URL (no `-2` suffix)

### SQL Strategy
```sql
-- Find duplicates grouped by original_id
SELECT pm.meta_value as original_id, 
       GROUP_CONCAT(p.ID ORDER BY p.ID ASC) as post_ids
FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
WHERE pm.meta_key = '_ielts_cm_original_id'
  AND [lesson/course connection conditions]
GROUP BY pm.meta_value
HAVING COUNT(*) > 1  -- Only duplicates

-- Result: List of original_ids with multiple posts
-- Action: Keep first ID, disconnect rest
```

## Files Modified

### Version 15.31
- `ielts-course-manager.php` - Version 15.30 → 15.31
- `includes/class-sync-api.php` - Rewrote find_existing_content(), added DISTINCT
- `VERSION_15_31_RELEASE_NOTES.md` - Documentation

### Version 15.32
- `ielts-course-manager.php` - Version 15.31 → 15.32
- `includes/class-sync-api.php` - Added cleanup methods
- `VERSION_15_32_RELEASE_NOTES.md` - Documentation

## Testing Checklist

### Before Deploying
- [x] PHP syntax validation (no errors)
- [x] Code review completed
- [x] Security check (CodeQL - no issues)
- [x] Documentation created

### After Deploying (Recommended)
1. **Check current counts** on subsite (before sync)
2. **Run sync** on a course with known duplicates
3. **Check logs** for cleanup messages
4. **Verify counts** now match primary site
5. **Test lesson pages** - only one copy of each item appears
6. **Optional**: Search for `-2`, `-3` posts and manually delete

### Expected Log Output
```
IELTS Sync: Found 3 posts with original_id=50 connected to lesson 100. 
Keeping post 301, disconnecting 2 duplicates.
IELTS Sync: Disconnected duplicate post 302 (original_id=50) from lesson 100
IELTS Sync: Disconnected duplicate post 303 (original_id=50) from lesson 100
IELTS Sync: Cleanup complete for lesson 100: disconnected 2 duplicate connections
```

## Benefits

### For Users
✅ Correct counts that match primary site
✅ Clean lesson menus (one item per content)
✅ No confusion about which content is "real"
✅ Better learning experience

### For Admins
✅ Automatic cleanup (no manual intervention)
✅ Self-healing on every sync
✅ Clear logs for debugging
✅ Safe, non-destructive approach

### For System
✅ Prevents new duplicates (15.31)
✅ Cleans up existing duplicates (15.32)
✅ Idempotent operations
✅ Database integrity maintained

## Known Limitations

### GROUP_CONCAT Limit
MySQL's default GROUP_CONCAT limit is 1024 characters. If a single lesson has hundreds of duplicate posts, the query might truncate. This is extremely unlikely in practice (would need 100+ duplicates of the same content).

### LIKE Patterns for Arrays
The code uses LIKE patterns to search serialized arrays. While this works for standard PHP serialization, it could miss edge cases with custom serialization. This matches existing patterns in the codebase for consistency.

### Manual Duplicates
If an admin manually creates duplicate content (not via sync) without the `_ielts_cm_original_id` metadata, it won't be detected or cleaned up.

## Future Improvements (Optional)

### Could Add:
1. Admin UI to preview duplicates before cleanup
2. Option to permanently delete disconnected posts
3. Bulk cleanup tool for all lessons at once
4. Statistics dashboard showing cleanup results

### Not Needed Now:
These improvements are optional nice-to-haves. The current solution fully addresses the user's requirement.

## Summary

The complete fix (versions 15.31 + 15.32) solves the duplicate content problem:
1. **Prevention** (15.31): No new duplicates created
2. **Cleanup** (15.32): Existing duplicates disconnected
3. **Result**: Counts match primary, lesson menus clean

**User requirement met**: Duplicates removed from lesson menus, counts accurate, posts preserved in database. ✅

## Deployment

Simply deploy version 15.32 to subsites. The next sync will:
1. Update content normally (15.31 fixes)
2. Automatically disconnect duplicates (15.32 cleanup)
3. Result: Accurate counts immediately!

No manual cleanup needed - it's all automatic! 🎉
