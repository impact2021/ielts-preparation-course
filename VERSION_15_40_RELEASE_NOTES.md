# Version 15.40 Release Notes

## Overview
This release addresses three critical requirements:
1. Terminology standardization across the plugin
2. Fix for Continue button showing on first page load (4th request)
3. Version number updates

## Changes

### 1. Terminology Updates

The term "Course" was being used inconsistently throughout the plugin, causing confusion. The following standardization has been applied:

#### Course → Unit
User-facing labels now consistently use "Unit" to refer to what was previously called "Course":
- "Start Course" → "Start Unit"
- "Continue Course" → "Continue Unit"
- "Course Progress" → "Unit Progress"
- "Return to course" → "Return to unit"
- "View Course" → "View Unit"
- "Course Lessons" → "Unit Lessons"
- Enrollment messages: "enrolled in this course" → "enrolled in this unit"

**Note**: The term "Course" is still used internally in variable names and database fields to avoid breaking changes. Only user-visible text has been updated.

#### Sublesson → Learning Resource
- "sublesson" → "learning resource" in content type labels
- Updated in both singular and plural forms
- Applied to content count displays and type badges

**Files Updated:**
- `templates/courses-list.php`
- `templates/single-course.php`
- `templates/single-lesson.php`
- `templates/single-lesson-page.php`
- `templates/single-quiz-page.php`
- `templates/single-quiz.php`
- `templates/single-resource-page.php`
- `templates/single-quiz-computer-based.php`
- `templates/single-quiz-listening-exercise.php`
- `templates/single-quiz-listening-practice.php`
- `templates/progress-page.php`

### 2. Continue Button Fix (Critical)

**Issue**: The "Next" navigation button was appearing on quiz pages immediately upon first load, making users think they had already viewed/completed the content. This is the 4th time this issue has been raised.

**Root Cause**: The `$next_url` variable was being populated on page load based solely on whether there was a next item in the lesson sequence, without checking if the user had actually completed the current quiz.

**Solution**: Added database check to verify quiz completion before showing the Continue/Next button:

```php
// Check if user has already completed this quiz
$user_has_completed_quiz = false;
if ($user_id) {
    global $wpdb;
    $quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
    $user_has_completed_quiz = (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM $quiz_results_table WHERE user_id = %d AND quiz_id = %d LIMIT 1",
        $user_id,
        $quiz->ID
    ));
}

// Only set next_url if user has completed the quiz
if ($user_has_completed_quiz && $current_index >= 0 && $current_index < count($all_items) - 1) {
    // ... set $next_url
}
```

**Behavior**:
- **First visit**: No Continue/Next button is shown
- **After quiz submission**: Continue/Next button appears (when quiz result is saved to database)
- **Subsequent visits**: Button remains visible since user has completed the quiz

**Query Optimization**: Used `SELECT 1` instead of `SELECT id` for better performance since we only need to check existence.

**Files Updated:**
- `templates/single-quiz-computer-based.php`
- `templates/single-quiz-listening-exercise.php`
- `templates/single-quiz-listening-practice.php`

### 3. Version Updates

- Plugin version: `15.39` → `15.40`
- `IELTS_CM_VERSION` constant: `15.39` → `15.40`

**File Updated:**
- `ielts-course-manager.php`

## Database Impact

**No database schema changes** were made in this release. All changes are to user-visible labels and button display logic only.

The Continue button fix uses the existing `ielts_cm_quiz_results` table to check for quiz completion, ensuring data integrity.

## Testing Recommendations

### 1. Terminology Verification
- [ ] Navigate to the units/courses list page
- [ ] Verify buttons show "Start Unit" or "Continue Unit" (not "Start Course" or "Continue Course")
- [ ] Open a unit and verify "Unit Progress" and "Unit Lessons" labels
- [ ] Check lesson content counts show "learning resource" instead of "sublesson"
- [ ] Verify enrollment error messages use "unit" instead of "course"

### 2. Continue Button Verification
- [ ] As a logged-in user, navigate to a quiz you have NOT completed
- [ ] Verify NO "Next" button appears in the top navigation
- [ ] Complete the quiz (submit answers)
- [ ] Verify "Next" button NOW appears after submission
- [ ] Reload the page
- [ ] Verify "Next" button is still visible (because quiz is completed)
- [ ] Test with all three quiz types:
  - Computer-based reading test
  - Listening exercise
  - Listening practice

### 3. Backwards Compatibility
- [ ] Verify all existing quiz results still display correctly
- [ ] Verify course/unit enrollment still works
- [ ] Verify progress tracking is unaffected

## Security Review

✅ No security vulnerabilities introduced
- All database queries use `$wpdb->prepare()` for SQL injection prevention
- Only user-visible text changes, no new attack surfaces
- Existing access control and enrollment checks remain unchanged

## Migration Notes

No migration needed. This is a drop-in update with no database changes.

## Known Limitations

- Internal code (variable names, function names, database fields) still uses "course" terminology to maintain backwards compatibility
- Only user-visible text has been updated to use "unit" and "learning resource"

## Summary

This release provides a cleaner, more consistent user experience with proper terminology and fixes a persistent issue with the Continue button appearing prematurely. The changes are surgical and focused, minimizing risk while delivering high user impact.
