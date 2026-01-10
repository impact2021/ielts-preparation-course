# Version 11.8 - Release Notes

## Overview
Version 11.8 addresses several layout and user experience issues identified in the listening test interface, particularly around question navigation spacing, transcript highlighting, and multi-field question handling.

## Issues Fixed

### 1. Bottom Navigation Bar Spacing on Extended Monitors
**Problem:** The question navigation bar at the bottom of the screen had approximately 40px of space below it on extended monitors, causing the navigation to not sit flush at the bottom.

**Solution:** 
- Removed `border-radius` from `.question-navigation` CSS class (changed from 5px to 0)
- Added `!important` flag to `margin-bottom: 0` to ensure no bottom margin
- Set `padding-bottom: 0` on `.questions-content` to allow nav bar to sit flush

**Files Changed:**
- `assets/css/frontend.css`

### 2. Restore Yellow Background Highlighting for Transcript Answers
**Problem:** The yellow background highlighting that wrapped around correct answer text in the transcript was removed. Only the Q number badge was showing, making it harder to identify answer locations.

**Solution:**
- Modified `process_transcript_markers_practice()`, `process_transcript_markers()`, and `process_transcript_markers_cbt()` functions
- Updated regex pattern from `/\[Q(\d+)\]/i` to `/\[Q(\d+)\]([^\[]*?)(?=\[Q|$)/is` to capture text after the marker
- The function now wraps the first sentence or ~100 characters of answer text in a yellow-highlighted span
- Updated `.transcript-answer-marker` CSS class to include yellow background color (#fff9c4), padding, and border-radius

**Files Changed:**
- `templates/single-quiz-listening-practice.php`
- `templates/single-quiz-listening-exercise.php`
- `templates/single-quiz-computer-based.php`
- `assets/css/frontend.css`

### 3. Individual "Show in Transcript" Links for Multi-Field Questions
**Problem:** Questions like Q1-5 in Listening Test 15 that use the `open_question` type with multiple fields (`field_count: 5`) were being treated as a single question. This resulted in:
- Only one "Show in transcript" button underneath all 5 questions
- Incorrect hyperlinking where Q6 button would show Q2's answer in the transcript

**Solution:**
- Modified `includes/class-quiz-handler.php` to add individual "Show in transcript" links in the feedback HTML for each field
- Each field now gets its own:
  - Question number (`$field_question_num`)
  - Feedback text with field-specific feedback
  - "Show in transcript" link with correct `data-question` attribute
- Updated `field_results` array to include `question_number` and `audio_section_id` for each field
- Added delegated event handler in JavaScript for `.show-in-transcript-link` to handle dynamically generated links
- Modified JavaScript to skip adding question-level "Show in transcript" link for `open_question` types (since they're now added per-field)

**Files Changed:**
- `includes/class-quiz-handler.php`
- `assets/js/frontend.js`

### 4. Version Number Updates
**Files Changed:**
- `ielts-course-manager.php` - Updated plugin header and IELTS_CM_VERSION constant to '11.8'

## Technical Details

### Transcript Marker Processing
The new regex pattern `/\[Q(\d+)\]([^\[]*?)(?=\[Q|$)/is` works as follows:
- `\[Q(\d+)\]` - Matches the Q marker (e.g., [Q1])
- `([^\[]*?)` - Captures any text after the marker until the next Q marker or end
- `(?=\[Q|$)` - Lookahead to stop at the next Q marker or end of string
- `i` - Case insensitive
- `s` - Dot matches newlines

The captured text is then trimmed to:
- First sentence (ending with `.`, `!`, `?`, or line break), or
- First 100 characters if no sentence ending is found

### Multi-Field Question Handling
For `open_question` type with multiple fields:
- Each field is now treated as an individual question for display and feedback purposes
- Field numbers map to question numbers: `field 1` → `Q1`, `field 2` → `Q2`, etc.
- This is calculated as: `$field_question_num = $display_start + $field_num - 1`
- Each field gets its own "Show in transcript" link with the correct question number

### Event Delegation
A new delegated event handler was added to handle all `.show-in-transcript-link` clicks:
```javascript
$(document).on('click', '.show-in-transcript-link', function(e) {
    // Handles both JavaScript-generated and PHP-generated links
});
```

This ensures that links added dynamically via PHP (in the feedback HTML) will work correctly.

## Testing Recommendations

### 1. Extended Monitor Testing
- Load a listening test on an extended/external monitor
- Verify the question navigation bar sits flush at the bottom with no gap
- Test on different monitor sizes and resolutions

### 2. Transcript Highlighting
- Complete a listening test and submit answers
- Click on question feedback to view transcript
- Verify that:
  - Q markers show with yellow badge
  - Answer text following Q markers has yellow background
  - Highlighting wraps the first sentence or ~100 characters

### 3. Multi-Field Questions
- Load Listening Test 15 (which has Q1-5 as a single open_question)
- Answer and submit the test
- Verify that:
  - Each of Q1, Q2, Q3, Q4, Q5 has its own "Show in transcript" link in the feedback
  - Clicking each link shows the correct corresponding Q marker in the transcript
  - Q6 link shows Q6 (not Q2)
  - Navigation buttons correctly map to their question numbers

### 4. Layout Integrity
- Verify no DIV structure issues
- Check that two-column layout works correctly
- Test responsive behavior on mobile/tablet

## Files Modified Summary

1. **assets/css/frontend.css**
   - Updated `.question-navigation` styles
   - Updated `.questions-content` styles  
   - Updated `.transcript-answer-marker` styles

2. **assets/js/frontend.js**
   - Added delegated event handler for `.show-in-transcript-link`
   - Modified "Show in transcript" link creation to skip `open_question` types

3. **includes/class-quiz-handler.php**
   - Modified `open_question` handling to add per-field "Show in transcript" links
   - Updated `field_results` array structure

4. **templates/single-quiz-listening-practice.php**
   - Modified `process_transcript_markers_practice()` function

5. **templates/single-quiz-listening-exercise.php**
   - Modified `process_transcript_markers()` function

6. **templates/single-quiz-computer-based.php**
   - Modified `process_transcript_markers_cbt()` function

7. **ielts-course-manager.php**
   - Updated version to 11.8

## Backward Compatibility
All changes are backward compatible. Existing tests and questions will continue to work as before. The improvements enhance the user experience without breaking existing functionality.
