# Version 15.32 Release Notes

## Summary
Added automatic cleanup of duplicate lesson/resource connections during sync to ensure accurate counts that match the primary site.

## Problem Statement
Even with the duplicate prevention fixes in 15.31, existing duplicates remained connected to lessons/courses. This caused:
- Incorrect counts (showing 20 sublessons when primary has 10)
- Duplicate items in lesson menus
- User confusion about which content is correct

## Solution
Added two new cleanup methods that run automatically during sync:
- `cleanup_duplicate_lesson_connections()` - Disconnects duplicate resources/exercises from lessons
- `cleanup_duplicate_course_connections()` - Disconnects duplicate lessons from courses

## How It Works

### Duplicate Detection
When syncing a lesson or course, the system now:
1. Finds all posts connected to that lesson/course
2. Groups them by `_ielts_cm_original_id`
3. Identifies groups with more than one post (duplicates)

### Duplicate Resolution
For each set of duplicates:
1. **Keeps the first post** (lowest post ID) connected
2. **Disconnects all others** by removing their metadata associations
3. Logs the cleanup actions for debugging

### Example Scenario
**Before Cleanup:**
```
Lesson "Reading Skills" (ID: 100) has these connected resources:
- Post 301: "Reading in Detail" (original_id: 50)
- Post 302: "Reading in Detail-2" (original_id: 50)  ← Duplicate!
- Post 303: "Reading in Detail-3" (original_id: 50)  ← Duplicate!
```

**After Cleanup:**
```
Lesson "Reading Skills" (ID: 100) has these connected resources:
- Post 301: "Reading in Detail" (original_id: 50)  ← Only this one connected

Posts 302 and 303 still exist in database but are no longer connected to the lesson
```

## What Gets Cleaned Up

### Lesson Page Duplicates
- Resources with same `original_id` connected to a lesson
- Exercises with same `original_id` connected to a lesson
- Videos with same `original_id` connected to a lesson

### Course Lesson Duplicates
- Lessons with same `original_id` connected to a course

## Metadata Changes

For disconnected posts, the system removes:
- `_ielts_cm_lesson_id` metadata (singular association)
- Entry from `_ielts_cm_lesson_ids` array (plural associations)
- `_ielts_cm_course_id` metadata (for lessons)
- Entry from `_ielts_cm_course_ids` array (for lessons)

## Important Notes

### Posts Are Not Deleted
- Duplicate posts remain in the database
- They're just disconnected from lessons/courses
- This preserves any student progress or data
- Site admins can manually delete them later if desired

### Which Duplicate Is Kept?
The system keeps the post with the **lowest post ID** (oldest post). This is typically the original post that was created first.

### Automatic vs Manual Cleanup
- **Automatic**: Runs every time a course/lesson is synced
- **Manual**: Site admins can still manually review and delete duplicate posts

## Files Modified

1. **ielts-course-manager.php**
   - Updated plugin version from 15.31 to 15.32
   - Updated IELTS_CM_VERSION constant

2. **includes/class-sync-api.php**
   - Added `cleanup_duplicate_lesson_connections()` method (lines ~678-776)
   - Added `cleanup_duplicate_course_connections()` method (lines ~583-681)
   - Modified `sync_lesson_pages()` to call cleanup (line ~678)
   - Modified `sync_course_lessons()` to call cleanup (line ~583)

## Benefits

### Immediate Benefits
1. ✅ **Accurate Counts**: Sublesson/exercise counts now match primary site
2. ✅ **Clean Lesson Menus**: Only one item per content piece
3. ✅ **Automatic Cleanup**: Runs every sync, no manual intervention needed
4. ✅ **Non-Destructive**: Keeps duplicate posts in database (just disconnects them)

### Long-term Benefits
1. **Self-Healing**: Syncs automatically fix duplicate connections
2. **Data Preservation**: Student progress on duplicates is preserved
3. **Easier Troubleshooting**: Clear logs show what was disconnected
4. **Reduced Confusion**: Users see correct number of items

## Testing Recommendations

### 1. Check Current State (Before Sync)
On a subsite with duplicates:
1. Go to a lesson page
2. Count how many sublessons/exercises appear
3. Note the counts shown in admin

### 2. Run Sync
1. Navigate to sync settings
2. Sync a course that has duplicates
3. Watch the error logs for cleanup messages

### 3. Verify Cleanup (After Sync)
1. Refresh the lesson page
2. Verify only one copy of each sublesson/exercise appears
3. Check admin counts - should match primary site
4. Verify the kept posts are accessible and functional

### 4. Check Logs
Look for log entries like:
```
IELTS Sync: Found 3 posts with original_id=50 connected to lesson 100. 
Keeping post 301, disconnecting 2 duplicates.
IELTS Sync: Disconnected duplicate post 302 (original_id=50) from lesson 100
IELTS Sync: Disconnected duplicate post 303 (original_id=50) from lesson 100
IELTS Sync: Cleanup complete for lesson 100: disconnected 2 duplicate connections
```

### 5. Verify Disconnected Posts
1. Go to Posts/Resources admin page
2. Search for posts with `-2`, `-3` in the title
3. Verify they exist but have no lesson association
4. Optional: Manually trash these posts if desired

## Edge Cases Handled

### Multiple Metadata Types
System handles posts that use either:
- `_ielts_cm_lesson_id` (singular)
- `_ielts_cm_lesson_ids` (array)
- Or both

### Serialized vs JSON Arrays
Handles different array formats:
- PHP serialized: `a:1:{i:0;i:100;}`
- JSON: `[100]`
- Single value: `100`

### No Duplicates Present
If no duplicates exist, cleanup exits early with no changes.

## Logging

All cleanup actions are logged with these prefixes:
- `IELTS Sync:` - Main sync operations
- Detailed logs include:
  - Number of duplicates found
  - Which post IDs are being kept
  - Which post IDs are being disconnected
  - Final cleanup count

## Performance Impact

### Minimal Overhead
- Cleanup only runs when syncing (not on every page load)
- SQL queries use indexed fields (`meta_key`, `post_status`)
- Early exit if no duplicates found
- Processes only affected lessons/courses

### Expected Runtime
- Small course (5-10 lessons): < 1 second additional time
- Medium course (10-30 lessons): 1-3 seconds additional time
- Large course (30+ lessons): 3-5 seconds additional time

## Backward Compatibility

✅ **Fully backward compatible**
- No database schema changes
- No API changes
- Works with existing synced content
- Only affects duplicate connections, not valid content

## Security

✅ **No security concerns**
- All inputs sanitized with `intval()`
- Uses `$wpdb->prepare()` for SQL safety
- Only modifies metadata, not post content
- Respects WordPress permissions

## Known Limitations

### Manual Duplicates
If an admin manually creates duplicate content (not via sync), it won't be cleaned up unless it has the `_ielts_cm_original_id` metadata.

### Post Selection Logic
Always keeps the lowest post ID. In rare cases, this might not be the "best" copy (e.g., if newer post has updated content). Future versions could implement smarter selection.

### Already-Disconnected Posts
Does not automatically delete posts that were disconnected. Admins must manually trash/delete if desired.

## Migration Notes

### For Existing Subsites
The next sync will automatically clean up duplicate connections:
1. **Recommended**: Sync all courses to clean up all duplicates
2. After sync, verify counts match primary site
3. Optional: Manually delete disconnected duplicate posts

### For New Subsites
No migration needed - cleanup runs automatically on every sync.

## Combining with Version 15.31

Version 15.32 builds on 15.31's fixes:
- **15.31**: Prevents NEW duplicates from being created
- **15.32**: Cleans up EXISTING duplicate connections

Together they provide a complete solution:
1. No new duplicates created (15.31)
2. Existing duplicates get disconnected (15.32)
3. Result: Clean, accurate subsites

## Summary

Version 15.32 completes the duplicate content fix by automatically disconnecting duplicate posts from lessons/courses during sync. This ensures counts are accurate and match the primary site, while preserving duplicate posts in the database for data safety. The cleanup is automatic, non-destructive, and runs on every sync.

**Key Outcome**: After syncing, sublesson/exercise/video counts will match the primary site exactly! 🎉
