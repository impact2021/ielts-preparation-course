# Sync Order Fix - Progress Preservation

## Problem

When pushing a course with existing lessons to subsites, ALL student progress records were being corrupted and lost.

### Root Cause

The sync process was pushing content in the wrong order:

1. **Course** was synced first
2. **Lessons** were synced immediately after
3. **Resources and Quizzes** (lesson children) were synced last

When a lesson was synced, the `receive_content()` method in `class-sync-api.php` would call `sync_lesson_pages()` to clean up orphaned content. This method would:
- Check which pages (resources/quizzes) exist on the primary site
- Trash any pages on the subsite that aren't in the current list
- Since the lesson's children hadn't been synced yet, they would all be trashed
- When the children were synced afterwards, they would be recreated with **new post IDs**

This caused progress loss because:
- User progress is stored with references to specific post IDs
- When content is trashed and recreated, the post IDs change
- All existing progress records become orphaned (pointing to trashed posts)

## Solution

Changed the sync order across all sync paths to ensure children are synced **BEFORE** their parent:

### Correct Sync Order

1. **Courses** (so lessons can reference them)
2. **Resources and Quizzes** (so they exist before lesson sync)
3. **Lessons** (after their children are already synced)

This prevents `sync_lesson_pages()` from trashing existing content because all the lesson's children are already up-to-date when the lesson is synced.

## Files Modified

### 1. `includes/class-multi-site-sync.php`

**Method: `push_content_with_children()`**

- For **courses**: Now pushes each lesson's children BEFORE pushing the lesson
- For **lessons**: Now pushes children BEFORE pushing the lesson itself
- Courses are still pushed first (lessons need them to exist)

**Before:**
```php
foreach ($lessons as $lesson) {
    $lesson_results = $this->push_content_to_subsites($lesson->ID, 'lesson');
    $lesson_children = $this->push_lesson_children($lesson->ID);
}
```

**After:**
```php
foreach ($lessons as $lesson) {
    $lesson_children = $this->push_lesson_children($lesson->ID);
    $lesson_results = $this->push_content_to_subsites($lesson->ID, 'lesson');
}
```

### 2. `includes/class-auto-sync-manager.php`

**Method: `get_changed_content()`**

- Reordered to return items in correct dependency order
- Collects all courses first, then all lesson children, then all lessons
- Optimized to use array append instead of array_merge in loops

**Sync Order:**
1. All courses
2. All resources and quizzes for all lessons
3. All lessons

### 3. `includes/admin/class-sync-status-page.php`

**Method: `handle_ajax_bulk_sync()`**

- Added automatic sorting by content type priority
- Ensures bulk syncs happen in the correct order even if user selects items randomly

**Type Priority:**
1. Courses (priority 1)
2. Resources and Quizzes (priority 2)
3. Lessons (priority 3)

## Impact

- **Student Progress Preserved**: Progress records are no longer lost when courses are re-synced
- **Data Integrity**: Post IDs remain stable across syncs
- **Performance**: Optimized array operations for better performance
- **Consistency**: All sync paths (manual, auto, bulk) now use correct order

## Testing Recommendations

To verify the fix:

1. **Setup:**
   - Create a test course on primary site with multiple lessons
   - Add resources and quizzes to each lesson
   - Push course to subsite

2. **Add Progress:**
   - On subsite, log in as a test student
   - Complete several resources and quizzes
   - Verify progress is recorded

3. **Modify and Re-sync:**
   - On primary site, modify course content (change lesson titles, add/remove resources)
   - Push course to subsite again

4. **Verify:**
   - Check that completed resources still show as completed
   - Verify quiz results are preserved
   - Confirm no orphaned progress records

## Technical Details

### Progress Storage

Progress is stored in `wp_ielts_cm_progress` table with direct post ID references:
- `course_id` → Points to course post ID
- `lesson_id` → Points to lesson post ID
- `resource_id` → Points to resource post ID

When posts are trashed and recreated, these references break.

### Content Sync Flow

**Syncing a Lesson:**
1. Primary site calls `push_content_to_subsites($lesson_id, 'lesson')`
2. `serialize_content()` includes `current_page_ids` (all resource/quiz IDs)
3. Subsite receives content via REST API
4. `process_incoming_content()` updates or creates the lesson
5. `sync_lesson_pages()` is called with `current_page_ids`
6. Any pages not in `current_page_ids` are trashed

**The Fix:**
By syncing children before the lesson, `current_page_ids` contains the IDs of freshly-synced content, so nothing gets trashed unnecessarily.

## Version

Fixed in version: 15.41+
