# Version 15.38 Release Notes

## Release Date
February 10, 2026

## Summary
This release focuses on UI/UX improvements to navigation, progress indicators, and completion tracking accuracy. All changes ensure a more consistent and accurate learning experience for students.

## Changes

### 1. Navigation Standardization
**Issue:** Inconsistent font styling in "Back to" navigation links across templates.

**Changes:**
- Standardized all "Back to" navigation links to use a single, consistent format
- Removed mixed font styles (`<small>` and `<strong>` tags used together)
- Simplified text: "Back to the Unit" and "Back to the Lesson" (removed "menu" suffix)

**Files Modified:**
- `templates/single-lesson.php`
- `templates/single-quiz.php`
- `templates/single-quiz-computer-based.php`
- `templates/single-resource-page.php`

**Before:**
```html
<small>Back to</small>
<strong>the lesson menu</strong>
```

**After:**
```html
<small>Back to the Lesson</small>
```

---

### 2. Circular Progress Indicators for Lesson Content Items
**Issue:** Lesson content items (sublessons, exercises) only showed basic checkmark icons without visual progress feedback.

**Changes:**
- Added circular progress indicators with checkmarks to lesson content tables
- Matched the design of course-level lesson progress circles
- Provides consistent visual feedback across all hierarchy levels

**Files Modified:**
- `templates/single-lesson.php` (HTML and CSS)

**Visual:**
- Completed items: Green circle with ✓ checkmark
- Incomplete items: Gray circle with ○ marker

---

### 3. Fixed Premature Completion Badge
**Issue:** The "Completed" badge was showing immediately when students opened a sublesson page for the first time, which was misleading.

**Changes:**
- Modified completion tracking logic to only mark resources as completed on SECOND visit
- First visit now tracks page access without setting the completed flag
- Ensures students only see "Completed" badge when they return to a previously viewed page

**Files Modified:**
- `templates/single-resource-page.php`
- `includes/class-progress-tracker.php` (added `get_progress_table()` helper method)

**Logic:**
- **First view:** Track access, don't mark as completed
- **Return visit:** Mark as completed and show badge

---

### 4. Fixed Course Completion Percentage Discrepancy
**Issue:** A course showing 100% completion on the course page showed only 93.8% on the courses list page.

**Root Cause:** 
Different formatting functions were being used:
- Course page: `number_format($completion, 1)` 
- Courses list: `round($completion, 1)`

These functions can produce different results due to rounding behavior.

**Changes:**
- Standardized all completion percentage displays to use `number_format($completion, 1)`
- Ensures consistent rounding across all templates

**Files Modified:**
- `templates/courses-list.php`
- `templates/progress-page.php`

**Impact:**
All completion percentages now display identically across:
- Individual course pages
- Courses list
- Progress tracking pages

---

## Technical Details

### Database Changes
None - all changes are display and logic-only.

### API Changes
- Added public method: `IELTS_CM_Progress_Tracker::get_progress_table()`

### Backward Compatibility
✅ Fully backward compatible - no breaking changes.

### Testing Recommendations
1. Test navigation links across all template types
2. Verify circular progress indicators display correctly for completed/incomplete items
3. Test sublesson completion badge behavior:
   - First visit: No badge
   - Second visit: Badge appears
4. Compare completion percentages across all display locations

---

## Upgrade Instructions
1. Upload the updated plugin files
2. No database migrations required
3. Clear any page/object caches if using caching plugins

---

## Files Changed
```
ielts-course-manager.php
includes/class-progress-tracker.php
templates/single-lesson.php
templates/single-quiz.php
templates/single-quiz-computer-based.php
templates/single-resource-page.php
templates/courses-list.php
templates/progress-page.php
```

## Contributors
- Development: GitHub Copilot Agent
- Issue Reporting: impact2021

---

## Known Issues
None identified in this release.

## Next Steps
Continue monitoring user feedback on the new completion tracking behavior.
