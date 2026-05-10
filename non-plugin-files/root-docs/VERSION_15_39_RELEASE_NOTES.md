# Version 15.39 Release Notes

## Unit Scoring Fix - Dynamic Calculation from Actual Lesson Content

### Issue Fixed
Fixed critical issue where unit/course completion percentage was not showing 100% even when all lessons within the unit showed 100% completion.

### Root Cause
The course completion calculation was counting ALL quizzes associated with the course (using `_ielts_cm_course_id` meta key), including quizzes that were not assigned to any lesson. This meant:

- **Lesson completion**: Counted only resources and quizzes with `_ielts_cm_lesson_id` 
- **Course completion**: Counted all resources from lessons + ALL course quizzes (including orphaned ones)

When content was removed from lessons or unpublished but still had the course association, it would:
- Decrease the total count for lessons (showing 100% completion for each lesson)
- Keep the higher total count for the course (showing less than 100% for the course)

### Solution
Modified `get_course_completion_percentage()` in `/includes/class-progress-tracker.php` to:

1. **Only count quizzes that belong to actual lessons** in the course
2. Use the same logic as lesson completion (checking `_ielts_cm_lesson_id` and `_ielts_cm_lesson_ids` meta keys)
3. Ensure course completion reflects only what users actually see in lessons

### Technical Changes

**File**: `includes/class-progress-tracker.php`

**Changed**: Lines 186-206 - Quiz counting logic for course completion

**Before**: 
- Counted quizzes with `_ielts_cm_course_id` OR `_ielts_cm_course_ids` matching the course
- Could include quizzes not assigned to any lesson

**After**:
- Counts only quizzes with `_ielts_cm_lesson_id` OR `_ielts_cm_lesson_ids` matching lessons in the course
- Ensures course completion matches the sum of lesson content

### Expected Behavior After Fix

✅ If all lessons in a unit show 100% completion → Unit shows 100% completion
✅ Course completion is calculated dynamically from ACTUAL existing lesson content
✅ Orphaned or removed quiz content no longer affects course completion percentage
✅ Course and lesson completion percentages are now consistent

### Version Updates
- Plugin version: `15.38` → `15.39`
- Constant: `IELTS_CM_VERSION` updated to `15.39`

### Files Modified
1. `includes/class-progress-tracker.php` - Fixed course completion calculation
2. `ielts-course-manager.php` - Updated version numbers
3. `VERSION_15_39_RELEASE_NOTES.md` - This file

### Testing Recommendations
1. Check courses where all lessons show 100% completion
2. Verify course completion also shows 100%
3. Test with courses that have orphaned quiz content (quizzes with course_id but no lesson_id)
4. Verify progress tracking still works correctly for partial completion

### Backward Compatibility
✅ This change maintains backward compatibility
✅ No database migrations required
✅ Existing progress data remains valid
✅ Only the calculation logic changed, not data storage
