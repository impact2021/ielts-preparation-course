# Version 11.17 Release Notes - Reading Test Button Fix

## Overview
This release fixes the missing "Show me the section of the reading passage" button that should appear after submitting reading tests. The button was implemented in version 11.16 but only worked for certain question types.

## Issue Fixed

### Missing "Show in Reading Passage" Button for Most Question Types
**Problem:** After submitting a reading test, the "Show me the section of the reading passage" button was not appearing for most questions, even though the backend was correctly returning the `reading_text_id` data.

**Root Cause:** 
- The frontend JavaScript had the button creation logic only in the generic "else if" handler (line 966+) that processes "other question types"
- Reading tests primarily use specific question types like:
  - `closed_question` (TRUE/FALSE/NOT GIVEN questions)
  - `multiple_choice`
  - `matching`
  - `headings`
  - `locating_information`
  - `multi_select`
- Each of these question types has its own dedicated feedback handler
- None of these dedicated handlers included the logic to add the "Show in reading passage" button
- Therefore, the button only appeared for question types that didn't have dedicated handlers (rare in reading tests)

**Solution:**
- Added a unified button addition loop that runs after all question-specific feedback processing
- This loop checks each question for the presence of `reading_text_id`
- If present (and the question type is not `open_question`), it adds the button to the question element
- This approach:
  - Ensures the button appears for ALL question types used in reading tests
  - Avoids code duplication across multiple question type handlers
  - Maintains backward compatibility with existing question types
  - Only affects reading tests (button only added when `reading_text_id` is present)
  - Does not interfere with listening tests (they use `audio_section_id` instead)

## Technical Changes

### Files Modified:
1. `assets/js/frontend.js` (lines 1031-1058)
   - Added unified button creation loop after question processing
   - Checks for duplicate buttons to avoid adding multiple times
   - Only processes questions with `reading_text_id` present
   - Skips `open_question` type (those buttons are added per-field in PHP)

2. `ielts-course-manager.php`
   - Version bump from 11.16 to 11.17
   - Updated in both plugin header and constant definition

### Design Confirmation

The following design decisions from version 11.16 remain unchanged and correct:

1. **Question Markers in Reading Passages**: Still hidden via CSS (`.reading-text .question-marker-badge { display: none !important; }`)
   - This is intentional and correct for reading tests
   - Showing answer locations would defeat the purpose of the test
   - Different from listening tests where markers are shown after submission to aid learning

2. **Separation of Reading and Listening Tests**:
   - Reading tests use `.reading-text` class for passages
   - Listening tests use `.transcript-section-content` and `.transcript-content` classes
   - CSS and JavaScript correctly target only the appropriate test type
   - No cross-contamination between test types

3. **Question Numbers in Question Panel**: Still shown (`.question-number` class)
   - These are the "Question 1", "Question 2" headings in the questions column
   - Different from the passage markers
   - Should remain visible as they're part of the question interface

## Testing Performed

- ✅ Verified CSS only targets `.reading-text` elements
- ✅ Verified listening templates don't use `.reading-text` class
- ✅ Confirmed button logic only triggers when `reading_text_id` is present
- ✅ Ensured no duplicate buttons are created

## Upgrade Notes

### For Site Administrators:
- No database changes required
- Clear browser cache after upgrading to ensure new JavaScript is loaded
- Test reading quizzes to verify button appears after submission

### For Students:
- After submitting a reading test, you will now see a "Show me the section of the reading passage" button
- Clicking this button will scroll to and highlight the relevant section in the passage
- This feature helps you understand where the answer was located in the text

### For Developers:
- The button creation logic is now centralized in one location (after line 1030 in frontend.js)
- Adding new question types to reading tests will automatically get the button feature
- The logic is defensive (checks for duplicates, validates data presence)

## Version
11.17 (Released: 2026-01-12)

## Related Versions
- Version 11.16: Initial implementation of reading passage highlighting and buttons
- This version fixes the button visibility issue discovered after 11.16 deployment
