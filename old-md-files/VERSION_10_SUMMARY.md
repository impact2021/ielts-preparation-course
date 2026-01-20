# IELTS Course Manager - Version 10.1 Summary

## Version 10.1 Updates

Version 10.1 is a patch release that fixes critical issues with feedback coloring and question rendering across different layouts.

### Key Fixes in Version 10.1

#### 1. Fixed Feedback Coloring Across All Layouts
**Problem:** Feedback colors were not displaying correctly. Instead of showing green for correct answers and red for incorrect answers, the feedback appeared with plain/inherited colors, making it difficult for students to see which answers were right or wrong.

**Root Cause:** CSS rules were using `background: inherit !important` and `border-color: inherit !important`, which prevented the proper feedback colors from being applied.

**Solution:** Updated CSS rules in `assets/css/frontend.css` to explicitly set proper colors for all quiz layouts:
- ✅ **Correct answers:** Green background (#4caf50) with white text
- ✗ **Incorrect answers:** Red background (#f44336) with white text  
- **Correct answers (not selected):** Transparent background with green border

**Files Changed:**
- `assets/css/frontend.css` (lines 436-456 for standard quiz, lines 2199-2225 for listening quizzes)

**Affected Layouts:**
- 1 Column Exercise (`.ielts-single-quiz`)
- Listening Practice Quiz (`.ielts-listening-practice-quiz`)
- Listening Exercise Quiz (`.ielts-listening-exercise-quiz`)

#### 2. Fixed Missing Question Types in 2-Column Layouts
**Problem:** Questions using `closed_question` and `open_question` types were not rendering in 2-column layouts (2 Column Reading, 2 Column Listening, 2 Column Exercise). Users would only see "Question 1" but no actual question content or input fields.

**Root Cause:** The `single-quiz-computer-based.php` template was missing the case handlers for `closed_question` and `open_question` types, while `single-quiz.php` had them.

**Solution:** Added the missing question type handlers to `single-quiz-computer-based.php`:
- **closed_question:** Supports both single-select (radio buttons) and multi-select (checkboxes) based on `correct_answer_count`
- **open_question:** Supports inline blanks (`[blank]` or `[field N]`) and separate input fields

**Files Changed:**
- `templates/single-quiz-computer-based.php` (added lines 840-949)

**Benefits:**
- Questions now render consistently across ALL layout types
- No need for different question formats depending on layout choice
- Single source of truth for question rendering logic

### Version Update
- **Plugin version:** Updated from 10.0 to 10.1
- **Version constant:** `IELTS_CM_VERSION` updated in `ielts-course-manager.php`

---

## Version 10.0 Summary

## Overview
Version 10.0 is a major update that simplifies the layout type system and fixes critical feedback display issues.

## Key Changes

### 1. Fixed Feedback Coloring Issue
**Problem:** In computer-based layouts, correct and incorrect answer feedback was showing with white/blank colors instead of green and red.

**Solution:** Removed conflicting CSS rule that was forcing feedback colors to inherit from parent elements. The feedback now properly displays:
- ✅ Green background (#4caf50) for correct answers
- ✗ Red background (#f44336) for incorrect answers
- Green border for correct answers that weren't selected

**File Changed:** `assets/css/frontend.css` (lines 1015-1024)

### 2. Simplified Layout Type System
**Previous System:**
- Layout Type dropdown with 2 options: "Standard Layout" and "Computer-Based IELTS Layout"
- For computer-based, a separate "Test Type" radio button (Reading or Listening)
- Complex conditional logic to show/hide sections

**New System:**
- Single Layout Type dropdown with 4 clear options:
  1. **2 Column Reading Test** - Two-column layout with reading texts on left
  2. **2 Column Listening Test** - Two-column layout with audio player on left
  3. **2 Column Exercise** - Two-column layout with no specific content type
  4. **1 Column Exercise** - Traditional single-column layout (questions only)

**Benefits:**
- Clearer interface - no nested options
- Easier to understand for content creators
- More flexible for different exercise types

### 3. Updated Default Settings
- **Default Layout:** Changed from "Standard Layout" to "2 Column Exercise" (two-column is now the standard)
- **Default Popup Setting:** "Open as Popup/Fullscreen Modal" is now checked by default for two-column layouts

### 4. Layout Type Mapping

#### Internal Values:
- `one_column_exercise` - Single column layout
- `two_column_exercise` - Two columns without specific content
- `two_column_reading` - Two columns with reading texts
- `two_column_listening` - Two columns with audio player

#### Backward Compatibility:
Old layout types are automatically converted when exercises are loaded or edited:
- `standard` → `one_column_exercise`
- `computer_based` + `reading` test type → `two_column_reading`
- `computer_based` + `listening` test type → `two_column_listening`
- `computer_based` + no test type → `two_column_exercise`
- `listening_practice` → `two_column_listening`
- `listening_exercise` → `two_column_listening`

## Files Modified

### Core Plugin Files:
1. **ielts-course-manager.php**
   - Updated version number from 9.2 to 10.0
   - Updated version constant

2. **includes/admin/class-admin.php**
   - Removed "Test Type" radio buttons
   - Updated Layout Type dropdown options
   - Updated default layout type
   - Changed popup default to checked
   - Updated JavaScript for section visibility
   - Removed cbt_test_type save logic
   - Added backward compatibility mapping

3. **assets/css/frontend.css**
   - Removed problematic CSS rule preventing feedback colors

### Template Files:
4. **templates/single-quiz-page.php**
   - Updated template loading logic
   - Added backward compatibility mapping
   - Simplified template selection

5. **templates/single-quiz-computer-based.php**
   - Changed from using `cbt_test_type` to `layout_type`
   - Simplified test type determination

6. **templates/single-lesson.php**
   - Updated fullscreen detection logic
   - Changed from `is_cbt` to `is_two_column`

7. **templates/single-resource-page.php**
   - Updated navigation URL generation
   - Changed from `is_cbt` to `is_two_column`

## Database Changes

### Meta Fields:
- `_ielts_cm_layout_type`: Now stores new layout type values (old values still supported)
- `_ielts_cm_cbt_test_type`: No longer used for new exercises (kept for backward compatibility)
- `_ielts_cm_open_as_popup`: Still used, now defaults to '1' for new exercises

### No Migration Required:
Existing exercises will continue to work without any database migration. The new code automatically maps old layout types to new ones at runtime.

## Testing Checklist

- [ ] Create new exercise with each layout type
- [ ] Verify feedback colors display correctly (green for correct, red for incorrect)
- [ ] Test popup/fullscreen modal functionality
- [ ] Open existing exercises to verify backward compatibility
- [ ] Test audio playback in listening exercises
- [ ] Test reading text display in reading exercises
- [ ] Verify navigation between exercises works correctly
- [ ] Check that XML export/import maintains compatibility

## Upgrade Notes for Site Administrators

1. **Automatic Upgrade:** No manual intervention required. Existing exercises will continue to function.

2. **Gradual Migration:** As you edit existing exercises, they will be automatically updated to use the new layout type values when saved.

3. **No Data Loss:** All existing content, questions, reading texts, and audio files are preserved.

4. **Template Compatibility:** Old template files (`single-quiz-listening-practice.php`, `single-quiz-listening-exercise.php`) are kept but no longer used. They can be safely removed in a future version.

## Breaking Changes

**None.** This update is fully backward compatible with existing exercises.

## Future Enhancements

Potential improvements for future versions:
- Migration script to automatically update all exercises to new layout types
- Remove deprecated template files
- Remove `_ielts_cm_cbt_test_type` meta field cleanup
- Enhanced two-column layout options (customizable column widths, etc.)

## Support

For questions or issues related to Version 10.0, please refer to:
- Plugin documentation in WordPress admin
- GitHub repository issues
- Support forum

---
**Version:** 10.1  
**Release Date:** December 2024  
**Compatibility:** WordPress 5.8+, PHP 7.2+
