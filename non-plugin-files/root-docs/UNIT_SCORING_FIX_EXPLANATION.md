# Unit Scoring Fix - Detailed Explanation

## Executive Summary

**Fixed:** Unit/course completion showing less than 100% even when all lessons show 100% completion.

**Root Cause:** Course completion was counting ALL quizzes associated with the course (including orphaned content), while lesson completion only counted quizzes assigned to specific lessons.

**Solution:** Changed course completion to only count quizzes that are actually part of lessons, ensuring consistency between lesson and course completion calculations.

## The Problem in Detail

### What Users Were Seeing

- **Lesson View:** Each lesson showing 100% completion ✓
- **Course/Unit View:** Course showing <100% completion (e.g., 85%) ✗
- **Expected:** If all lessons are 100%, the course should be 100%

### Why This Happened

The plugin has three types of content:
1. **Resources** (sub-lessons/videos) - Always linked to specific lessons via `_ielts_cm_lesson_id`
2. **Quizzes/Exercises** - Can be linked to:
   - Specific lessons via `_ielts_cm_lesson_id` 
   - Entire course via `_ielts_cm_course_id`
   - Multiple lessons via `_ielts_cm_lesson_ids` (serialized array)

### The Discrepancy

**Lesson Completion Calculation (CORRECT):**
```
Total items = Resources with lesson_id + Quizzes with lesson_id
```

**Course Completion Calculation (INCORRECT - BEFORE FIX):**
```
Total items = Resources from all lessons + Quizzes with course_id
```

The problem: `Quizzes with course_id` could include quizzes NOT assigned to any lesson!

### When This Manifested

This issue appeared when:
1. Content was removed/unpublished from lessons but still had course association
2. Quizzes were associated with course but not with any specific lesson
3. Lessons were deleted but quizzes remained with course_id

**Example Scenario:**
- Course originally had 5 lessons with 20 quizzes total
- 2 lessons were removed, taking 8 quizzes with them
- But those 8 quizzes still had `course_id` set
- Result:
  - Lessons counted 12 quizzes (correct, only what's visible)
  - Course counted 20 quizzes (incorrect, including orphaned ones)
  - User completes all 12 visible quizzes → 12/12 = 100% per lesson
  - But course shows 12/20 = 60% (incorrect!)

## The Fix

### Code Changes

**File:** `includes/class-progress-tracker.php`

**Function:** `get_course_completion_percentage()`

**Before (lines 186-196):**
```php
// Get all quizzes in the course (check both old and new meta keys)
$quiz_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT pm.post_id 
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.post_type = 'ielts_quiz'
      AND p.post_status = 'publish'
      AND ((pm.meta_key = '_ielts_cm_course_id' AND pm.meta_value = %d)
        OR (pm.meta_key = '_ielts_cm_course_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
", $course_id, $int_pattern, $str_pattern));
```

**After (lines 186-228):**
```php
// Get all quizzes that belong to lessons in this course
// (not course-level quizzes that aren't in lessons)
$quiz_ids = array();
if (!empty($lesson_ids)) {
    $lesson_count = count($lesson_ids);
    if ($lesson_count <= self::MAX_QUERY_ITEMS) {
        $lesson_ids = array_map('intval', $lesson_ids);
        
        // Build safe OR conditions for each lesson ID
        $quiz_conditions = array();
        foreach ($lesson_ids as $lid) {
            // Check _ielts_cm_lesson_id
            $quiz_conditions[] = $wpdb->prepare(
                "(pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)",
                $lid
            );
            // Check _ielts_cm_lesson_ids (serialized array)
            $int_pattern_lesson = '%' . $wpdb->esc_like('i:' . $lid . ';') . '%';
            $str_pattern_lesson = '%' . $wpdb->esc_like(serialize(strval($lid))) . '%';
            $quiz_conditions[] = $wpdb->prepare(
                "(pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s))",
                $int_pattern_lesson,
                $str_pattern_lesson
            );
        }
        $quiz_where_clause = implode(' OR ', $quiz_conditions);
        
        $quiz_ids = $wpdb->get_col("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ($quiz_where_clause)
        ");
    }
}
```

### What Changed

1. **Source of Truth:** Instead of using `course_id`, now uses `lesson_id` from all lessons in the course
2. **Consistency:** Matches the same logic used by `get_lesson_completion_percentage()`
3. **Accuracy:** Only counts quizzes that are actually visible in lessons

### Security Considerations

The new code maintains security by:
- Using `intval()` to sanitize all lesson IDs
- Using `wpdb->prepare()` for each individual condition with proper type specifiers (%d for integers, %s for strings)
- Using `wpdb->esc_like()` for LIKE pattern escaping
- Each condition is individually escaped before concatenation

The concatenation pattern is necessary because WordPress's `wpdb->prepare()` doesn't support dynamic OR clause construction with variable numbers of conditions.

## Version Update

- **Previous:** 15.38
- **New:** 15.39

Updated in:
- `ielts-course-manager.php` (Plugin header)
- `ielts-course-manager.php` (IELTS_CM_VERSION constant)

## Expected Behavior After Fix

✅ **Scenario 1: Complete Course**
- All 5 lessons show 100%
- Course shows 100%

✅ **Scenario 2: Partial Completion**
- 3 out of 5 lessons at 100%
- 2 out of 5 lessons at 50%
- Course shows aggregate completion based on actual lesson content

✅ **Scenario 3: Orphaned Content**
- Course has quizzes with course_id but no lesson_id
- These quizzes are NOT counted in course completion
- Course completion only reflects lesson content

✅ **Scenario 4: Content Removed**
- Lessons are unpublished/deleted
- Course completion recalculates based on remaining lessons
- No "ghost" content affects the percentage

## Testing Recommendations

### Manual Testing

1. **Test Case 1: Complete All Lessons**
   - Enroll in a course
   - Complete all resources and quizzes in all lessons
   - Verify each lesson shows 100%
   - **Expected:** Course also shows 100%

2. **Test Case 2: Partial Completion**
   - Complete 50% of content in each lesson
   - Verify lessons show ~50%
   - **Expected:** Course shows ~50%

3. **Test Case 3: Orphaned Quizzes**
   - Create a quiz with course_id but no lesson_id
   - Complete all lesson content
   - **Expected:** Course shows 100% (orphaned quiz not counted)

### Database Queries for Verification

```sql
-- Find orphaned quizzes (have course_id but no lesson_id)
SELECT p.ID, p.post_title
FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'ielts_quiz'
  AND p.post_status = 'publish'
  AND pm.meta_key = '_ielts_cm_course_id'
  AND p.ID NOT IN (
    SELECT post_id FROM wp_postmeta 
    WHERE meta_key = '_ielts_cm_lesson_id'
  );
```

## Backward Compatibility

✅ **Fully Backward Compatible**
- No database schema changes
- No data migrations required
- Existing progress data remains valid
- Only calculation logic changed
- No API changes

## Edge Cases Handled

1. **MAX_QUERY_ITEMS Limit:** Courses with more than 1000 lessons will skip quiz counting (edge case limitation documented in code)
2. **Serialized Arrays:** Handles both `_ielts_cm_lesson_ids` (plural, serialized) and `_ielts_cm_lesson_id` (singular)
3. **Integer vs String IDs:** Handles both integer serialization (`i:123;`) and string serialization (`s:3:"123";`)
4. **Empty Courses:** Returns 0% for courses with no lessons
5. **Unpublished Content:** Only counts published resources and quizzes

## Impact Assessment

### Positive Impacts
- ✅ Accurate course completion percentages
- ✅ Consistency between lesson and course calculations
- ✅ Better user experience (no confusing discrepancies)
- ✅ Dynamic calculation from actual content

### Potential Issues (None Expected)
- ⚠️ Courses relying on orphaned quiz counting may show higher completion percentages (but this is actually correct behavior)

## Related Documentation

- Main release notes: `VERSION_15_39_RELEASE_NOTES.md`
- Code changes: See git diff for commit `Fix unit scoring to calculate from actual lesson content`
