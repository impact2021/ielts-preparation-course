# Version 11.16 Release Notes - Reading Test Highlighting Fixes

## Overview
This release fixes critical issues with the reading test highlighting and answer feedback system that was preventing proper display and interaction with reading test questions.

## Issues Fixed

### Issue 1: Question Markers Showing Before Submission
**Problem:** Question marker badges (Q#) were displaying immediately when starting a test, revealing answer locations before students attempted the questions.

**Root Cause:** The CSS class `.question-marker-badge` was set to `display: inline-block` without any conditional hiding mechanism.

**Solution:**
- Updated `assets/css/frontend.css` to hide `.question-marker-badge` by default (`display: none`)
- Added `.quiz-submitted` class to show markers only after submission
- Modified `assets/js/frontend.js` to add `quiz-submitted` class to quiz container on successful submission
- This ensures markers are hidden during the test and only revealed during review

### Issue 2: Missing "Show in Reading Passage" Button
**Problem:** After submitting a quiz, the feedback for each question did not include a "Show in reading passage" button to help students locate the answer in the text.

**Root Cause:** The `reading_text_id` field was not being included in the `question_results` array returned by the backend, so the frontend couldn't create the button even though the code existed.

**Solution:**
- Modified `includes/class-quiz-handler.php` to include `reading_text_id` in the question results array (line 369)
- Frontend JavaScript (already existing) now properly receives this data and creates the "Show in reading passage" link
- Button appears in the feedback section and scrolls to the relevant reading text when clicked

### Issue 3: No Highlighting in Reading Passage
**Problem:** Reading passages had no system for highlighting where answers are found, unlike listening tests which have this feature.

**Root Cause:** The infrastructure existed (the `process_transcript_markers_cbt()` function) but was not being applied to reading texts - only to listening transcripts.

**Solution:**
- Modified `templates/single-quiz-computer-based.php` to process reading text content through `process_transcript_markers_cbt()`
- This enables [Q#] markers in reading texts to be processed into highlighted answer sections
- Combined with Issue 1 fix, markers are hidden until after submission
- Reading texts can now include markers like `[Q15]answer text here` which will:
  - Be invisible during the test
  - Show a Q15 badge after submission
  - Highlight the answer text in yellow

### Issue 4: Q33-37 Have No Answer Input Fields
**Problem:** Questions 33-37 in Academic Reading Test 01 (and similar questions in other tests) had no radio buttons or input fields for students to select answers.

**Root Cause:** These questions were typed as `closed_question` but had empty `mc_options` arrays. The template code required options to render input fields, so questions without options showed nothing. These were actually TRUE/FALSE/NOT GIVEN questions that should have been typed as `true_false` or should have had the appropriate options.

**Solution:**
- Enhanced `templates/single-quiz-computer-based.php` `closed_question` case handler
- Added intelligent detection: if `mc_options` is empty AND instructions contain "TRUE"/"FALSE"/"NOT GIVEN" or "YES"/"NO", render as a true/false question
- Automatically detects whether to use TRUE/FALSE or YES/NO based on instruction text
- Provides backward compatibility for legacy/incorrectly-typed questions
- Allows questions to be fixed in the data later without breaking existing tests

## Technical Changes Summary

### Files Modified:
1. `includes/class-quiz-handler.php` - Added reading_text_id to question results
2. `assets/css/frontend.css` - Hide markers until submission
3. `assets/js/frontend.js` - Add quiz-submitted class on submission
4. `templates/single-quiz-computer-based.php` - Process reading markers, handle closed_question without options
5. `ielts-course-manager.php` - Version bump to 11.16

### Why This Happened (Post-Mortem)

The problems occurred because:

1. **Incomplete Feature Implementation**: Someone added the highlighting infrastructure (CSS classes, marker processing function) for reading tests but didn't:
   - Hide the markers initially
   - Connect the backend data flow (reading_text_id)
   - Apply the processing to reading texts
   - Test with actual reading test data

2. **Data Quality Issues**: The JSON test files had questions with incorrect types or missing data:
   - `closed_question` type used for TRUE/FALSE questions
   - Empty `mc_options` arrays when options should have been populated
   - This suggests tests were created/imported without proper validation

3. **Lack of Testing**: The issues would have been immediately apparent if someone had:
   - Loaded a reading test
   - Attempted to answer questions
   - Submitted and reviewed results

### Prevention for Next Time

To avoid similar issues in the future:

1. **Test Before Committing**: Always load the actual quiz interface and test the complete user flow
2. **Data Validation**: Add validation to prevent questions with missing required fields (e.g., closed_question must have options)
3. **Feature Completion Checklist**: When adding features like highlighting:
   - ☑ Backend returns necessary data
   - ☑ Frontend processes the data
   - ☑ CSS handles all states (before/after submission)
   - ☑ Tested with actual quiz data
   - ☑ Works for all question types
4. **Type Safety**: Consider adding question type validation in the import/save process to catch data errors early

## Upgrade Notes

### For Site Administrators:
- No database changes required
- Clear browser cache after upgrading to ensure new CSS is loaded
- Existing quizzes will work immediately with no data migration needed

### For Content Creators:
- Reading texts can now include `[Q#]answer text` markers to highlight answer locations
- Markers will be automatically hidden during tests and shown during review
- Questions typed as `closed_question` will automatically render as TRUE/FALSE if they have no options but appropriate instructions

### For Developers:
- The `process_transcript_markers_cbt()` function now handles both transcripts and reading texts
- Question results now include `reading_text_id` for frontend use
- Template handles fallback rendering for malformed question data

## Testing Recommendations

After deploying this version:
1. Test a reading quiz from start to finish
2. Verify Q markers are hidden during test
3. Submit quiz and verify:
   - Markers appear in reading text
   - "Show in reading passage" buttons work
   - Questions 33-37 (and similar) have answer options
4. Test the highlighting by clicking "Show in reading passage"
5. Verify answer areas are highlighted in yellow

## Version
11.16 (Released: 2026-01-12)
