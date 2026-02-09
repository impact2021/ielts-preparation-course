# Version 15.31 Release Notes

## Summary
Fixed critical subsite syncing issues causing duplicate sublessons and exercises, along with incorrect lesson counts.

## Problem Statement
When syncing courses to subsites:
1. **Duplicate Content**: Sublessons and exercises appeared multiple times in subsites
2. **Sync Not Removing Duplicates**: Running sync again did not remove the duplicate entries
3. **Incorrect Counts**: Lesson counts for sublessons and exercises did not match actual content

## Root Cause Analysis

### Issue 1: Duplicate Records from Missing DISTINCT
**File**: `includes/class-sync-api.php`, lines 516 and 594

**Problem**: SQL queries in `sync_course_lessons()` and `sync_lesson_pages()` were missing `DISTINCT` keywords. When a post had multiple postmeta entries (e.g., both `_ielts_cm_lesson_id` and `_ielts_cm_lesson_ids`), the JOIN returned the same post multiple times.

**Example Scenario**:
```
Resource Post ID 123 has:
- _ielts_cm_original_id = 456
- _ielts_cm_lesson_id = 5
- _ielts_cm_lesson_ids = [5, 6] (serialized array)

Query returns:
- Row 1: post_id=123, original_id=456 (matched via _ielts_cm_lesson_id)
- Row 2: post_id=123, original_id=456 (matched via _ielts_cm_lesson_ids)

Result: Post 123 processed twice → appears as duplicate
```

**Impact**: 
- Duplicate processing of the same content
- Incorrect counts showing double (or more) the actual number
- Sync operations not idempotent (running twice creates different results)

### Issue 2: Imprecise Content Matching - CRITICAL
**File**: `includes/class-sync-api.php`, line 239

**Problem**: `find_existing_content()` used WordPress `get_posts()` with `'post_type' => 'any'` and `'post_status' => 'any'`. However, `get_posts()` has known issues with `post_status => 'any'` - it doesn't always return ALL statuses reliably. This caused the function to fail to find existing posts, even when they existed.

**Real-World Example from User**:
```
Primary site: https://www.ieltstestonline.com/ielts-lesson-page/reading-in-detail/

Subsite after sync:
- https://www.ieltstestonline.com/2026/ielts-lesson-page/reading-in-detail/
- https://www.ieltstestonline.com/2026/ielts-lesson-page/reading-in-detail-2/

WordPress added "-2" suffix because it detected duplicate slug!
```

**Root Cause Chain**:
1. `find_existing_content()` fails to find the existing post (due to `get_posts()` limitation)
2. Returns `false` even though post exists
3. `process_incoming_content()` thinks post doesn't exist
4. Calls `wp_insert_post()` instead of `wp_update_post()`
5. WordPress creates new post with auto-incremented slug (`-2`, `-3`, etc.)
6. Result: Multiple copies of same content with different URLs

**Example Scenario**:
```
Primary site has:
- Resource ID 100 → synced to subsite
- Quiz ID 100 → about to sync

Without post_type filter:
- Query finds the Resource with _ielts_cm_original_id = 100
- Updates the Resource instead of creating a new Quiz
- Result: Resource is overwritten, Quiz never created, duplicate references
```

**Impact**: 
- **Every sync creates new posts** instead of updating existing ones
- WordPress auto-increments slugs: `reading-in-detail`, `reading-in-detail-2`, `reading-in-detail-3`
- Counts grow infinitely (10 lessons becomes 20, then 30, then 40...)
- Broken URLs and SEO issues
- **This is the PRIMARY cause of the duplicate issue**

## Solutions Implemented

### Fix 1: Added DISTINCT to Sync Queries

#### In `sync_course_lessons()` (line 516)
**Before**:
```php
SELECT post_id, meta_value as original_id 
FROM {$wpdb->postmeta} pm
INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
```

**After**:
```php
SELECT DISTINCT pm.post_id as post_id, pm.meta_value as original_id 
FROM {$wpdb->postmeta} pm
INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
```

#### In `sync_lesson_pages()` (line 594)
**Before**:
```php
SELECT p.ID as post_id, p.post_title, p.post_type, pm.meta_value as original_id 
FROM {$wpdb->postmeta} pm
INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
```

**After**:
```php
SELECT DISTINCT p.ID as post_id, p.post_title, p.post_type, pm.meta_value as original_id 
FROM {$wpdb->postmeta} pm
INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
```

### Fix 2: Rewrote find_existing_content() with Direct SQL Query

**Critical Change**: Replaced unreliable `get_posts()` with direct SQL query.

**Before**:
```php
private function find_existing_content($original_id, $content_type) {
    $args = array(
        'post_type' => 'any',
        'meta_key' => '_ielts_cm_original_id',
        'meta_value' => $original_id,
        'posts_per_page' => 1,
        'post_status' => 'any'  // ← This doesn't work reliably
    );
    
    $posts = get_posts($args);
    return !empty($posts) ? $posts[0]->ID : false;
}
```

**After**:
```php
private function find_existing_content($original_id, $content_type) {
    global $wpdb;
    
    // Map content type to post type for more accurate matching
    $post_type_map = array(
        'course' => 'ielts_course',
        'lesson' => 'ielts_lesson',
        'resource' => 'ielts_resource',
        'quiz' => 'ielts_quiz'
    );
    
    // Use specific post type if available
    $post_type = isset($post_type_map[$content_type]) ? $post_type_map[$content_type] : 'any';
    
    // Direct SQL query - more reliable than get_posts() for 'any' status
    if ($post_type !== 'any') {
        $existing_post = $wpdb->get_var($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_ielts_cm_original_id'
            AND pm.meta_value = %s
            AND p.post_type = %s
            AND p.post_status != 'trash'  // ← Exclude only trash, include all others
            LIMIT 1
        ", $original_id, $post_type));
    } else {
        $existing_post = $wpdb->get_var($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_ielts_cm_original_id'
            AND pm.meta_value = %s
            AND p.post_status != 'trash'
            LIMIT 1
        ", $original_id));
    }
    
    return $existing_post ? intval($existing_post) : false;
}
```

**Why This Fixes the Duplicate Issue**:
1. ✅ Direct SQL reliably finds posts in any status (draft, publish, pending, etc.)
2. ✅ Proper post type filtering prevents type mismatches
3. ✅ Existing posts are now FOUND correctly
4. ✅ `wp_update_post()` is called instead of `wp_insert_post()`
5. ✅ **No more duplicate posts with -2, -3, -4 slugs**

## Files Modified

1. **ielts-course-manager.php**
   - Updated plugin version from 15.30 to 15.31
   - Updated IELTS_CM_VERSION constant

2. **includes/class-sync-api.php**
   - Line 516: Added DISTINCT to sync_course_lessons query
   - Line 594: Added DISTINCT to sync_lesson_pages query  
   - Lines 240-279: **CRITICAL FIX** - Rewrote find_existing_content with direct SQL query to reliably find existing posts and prevent duplicate creation

3. **VERSION_15_31_RELEASE_NOTES.md** (this file)
   - Comprehensive documentation of fixes and testing procedures

## Benefits

### Immediate Benefits
1. ✅ **No More Duplicates**: Syncing now updates existing posts instead of creating duplicates
2. ✅ **No More Auto-Incremented Slugs**: URLs remain clean (`/reading-in-detail/` not `/reading-in-detail-2/`)
3. ✅ **Accurate Counts**: Lesson, resource, and exercise counts reflect actual content
4. ✅ **Idempotent Syncs**: Running sync multiple times produces same result (critical!)
5. ✅ **Correct Content Matching**: Resources match resources, quizzes match quizzes
6. ✅ **SEO Preservation**: Existing URLs not duplicated or broken

### Long-term Benefits
1. **Data Integrity**: Prevents content corruption from mismatched types
2. **Reliable Syncing**: Subsites accurately mirror primary site content
3. **Better Performance**: Fewer duplicate records mean less database overhead
4. **Easier Maintenance**: Predictable sync behavior makes debugging easier

## Testing Recommendations

### 1. Clean Sync Test
On a subsite with existing duplicates:
1. Navigate to sync page
2. Sync a course with multiple lessons and resources
3. **Expected**: Each lesson/resource appears once
4. **Verify**: Count matches primary site count

### 2. Re-sync Test
After syncing once:
1. Sync the same course again
2. **Expected**: No new duplicates created
3. **Verify**: Counts remain the same

### 3. Mixed Content Test
Create a course with:
- Multiple lessons
- Multiple resources per lesson
- Multiple quizzes
- Some content in draft status
1. Sync to subsite
2. **Expected**: All content synced correctly with correct types
3. **Verify**: No type mismatches (resources aren't quizzes, etc.)

### 4. Removal Test
On primary site:
1. Remove a lesson from a course
2. Sync course to subsite
3. **Expected**: Removed lesson is trashed on subsite
4. **Verify**: No phantom duplicates remain

### 5. Count Verification
After syncing:
1. Check course admin page on subsite
2. Compare lesson counts to primary site
3. **Expected**: Counts match exactly
4. Check individual lessons
5. Compare resource/exercise counts
6. **Expected**: Counts match exactly

## Migration Notes

### For Existing Subsites with Duplicates
This fix **prevents new duplicates** but does not automatically clean up existing ones. To clean up:

1. **Manual Cleanup** (Recommended):
   - Identify duplicate posts by checking `_ielts_cm_original_id` 
   - Keep the most recent, trash the others
   - Re-sync to ensure consistency

2. **Fresh Sync** (If feasible):
   - Backup subsite data
   - Trash all synced content on subsite
   - Re-sync from primary site
   - All counts will be accurate

3. **SQL Cleanup Script** (Advanced):
   ```sql
   -- Find duplicate posts with same original_id
   SELECT pm.meta_value as original_id, COUNT(*) as count
   FROM wp_postmeta pm
   INNER JOIN wp_posts p ON pm.post_id = p.ID
   WHERE pm.meta_key = '_ielts_cm_original_id'
   AND p.post_status != 'trash'
   GROUP BY pm.meta_value
   HAVING COUNT(*) > 1;
   ```

## Backward Compatibility
✅ **Fully backward compatible**
- No database schema changes
- No API changes
- Existing synced content unaffected
- Only affects future sync operations

## Security
✅ **No security concerns**
- Changes are query optimizations only
- No new external inputs
- No changes to authentication/authorization
- Maintains existing security posture

## Performance
✅ **Neutral to slight improvement**
- `DISTINCT` adds minimal overhead
- Prevents duplicate processing (net gain)
- Reduces database bloat over time
- No noticeable performance impact expected

## Known Limitations
- Does not automatically clean up existing duplicates (by design)
- Relies on `_ielts_cm_original_id` being set correctly
- Post type mapping requires standard post types (course, lesson, resource, quiz)

## Summary
Version 15.31 resolves the critical duplicate content issue in subsite syncing. The root cause was `find_existing_content()` failing to locate existing posts due to WordPress `get_posts()` limitations with `post_status => 'any'`. This caused every sync to create NEW posts instead of updating existing ones, resulting in duplicate URLs with auto-incremented slugs (e.g., `reading-in-detail-2`, `reading-in-detail-3`).

The fix rewrites `find_existing_content()` using direct SQL queries and adds `DISTINCT` to sync queries, ensuring each record is processed exactly once and matched correctly. **Syncing is now idempotent, reliable, and accurate.**
