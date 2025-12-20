# Testing Guide for Independent Question Types

## Overview
This guide provides comprehensive testing steps to verify that all question types are now 100% independent and working correctly, especially the headings question type that was previously broken.

## Changes Made

### 1. Template Separation
Each question type now has its own dedicated case in both templates:
- **single-quiz.php**: Standard quiz layout
- **single-quiz-computer-based.php**: Computer-based test layout

Question types separated:
- `headings` - Independent implementation with unique CSS classes
- `matching_classifying` - Independent implementation with unique CSS classes
- `matching` - Independent implementation with unique CSS classes
- `multiple_choice` - Already independent
- `true_false` - Already independent
- Other types remain unchanged

### 2. Quiz Handler Separation
- Each question type now has its own case in `check_answer()` method
- Each type has independent handling in `submit_quiz()` method
- Eliminated shared code paths that caused interference

### 3. Admin JavaScript Separation
- Each question type has its own handler in the question type change event
- Eliminated grouped handling that caused UI conflicts

## Testing Steps

### Prerequisites
1. Have WordPress installed with IELTS Course Manager plugin activated
2. Be logged in as an administrator
3. Have access to the test file: `ielts-reading-test-complete.txt`

### Test 1: Import Complete Reading Test
**Purpose**: Verify all question types parse correctly

1. Go to **WordPress Admin** → **IELTS Courses** → **Create Exercises from Text**
2. Copy the entire contents of `ielts-reading-test-complete.txt`
3. Paste into the text area
4. Select **Draft** as Post Status
5. Click **Create Exercise**

**Expected Result**:
- Success message appears
- 40 questions imported successfully
- 3 reading passages attached
- Question types correctly assigned:
  - Questions 1-5: `headings` type
  - Questions 6-9: `true_false` type
  - Questions 10-13: `matching` type
  - Questions 14-17: `true_false` type
  - Questions 18-24: `matching_classifying` type
  - Questions 25-32: `short_answer` type
  - Questions 33-37: `true_false` type
  - Questions 38-40: `matching` type

### Test 2: Verify Headings Questions Display
**Purpose**: Verify headings questions render correctly with all options

1. Navigate to the imported quiz on the frontend
2. Scroll to Questions 1-5 (Headings section)

**Expected Result**:
- Each question (1-5) displays:
  - Question number and text (e.g., "1. Paragraph B")
  - All 9 heading options (I through IX)
  - Each option has a radio button
  - Radio buttons are grouped per question (can only select one per question)
  - All radio buttons are visible and clickable
  - CSS class `headings-options` on container
  - CSS class `headings-radio` on radio inputs

**What to Check**:
- ✅ All 9 options visible for each question
- ✅ Radio buttons work (can select an option)
- ✅ Only one option can be selected per question
- ✅ No "single radio button" issue
- ✅ Options are properly formatted and readable

### Test 3: Test Headings Answer Saving
**Purpose**: Verify headings answers are saved correctly

1. Select an answer for Question 1 (e.g., select option E)
2. Select answers for Questions 2-5
3. Submit the quiz

**Expected Result**:
- Answers are submitted successfully
- Results page shows selected answers
- Correct answers are highlighted
- Score is calculated correctly
- No JavaScript errors in console

**What to Check**:
- ✅ Selected answers are submitted
- ✅ Answers persist after submission
- ✅ Correct/incorrect marking works
- ✅ No console errors

### Test 4: Verify Matching Questions Display
**Purpose**: Verify matching questions work independently

1. Scroll to Questions 10-13 (Matching section)

**Expected Result**:
- Each question displays with all matching options
- Radio buttons work correctly
- CSS class `matching-options` on container
- CSS class `matching-radio` on radio inputs

### Test 5: Verify Matching Classifying Questions Display
**Purpose**: Verify matching_classifying questions work independently

1. Scroll to Questions 18-24 (Matching/Classifying section)

**Expected Result**:
- Each question displays with all classification options
- Radio buttons work correctly
- CSS class `matching-classifying-options` on container
- CSS class `matching-classifying-radio` on radio inputs

### Test 6: Computer-Based Layout Test
**Purpose**: Verify all question types work in CBT layout

1. Edit the quiz settings
2. Change **Layout Type** to "Computer-Based Test"
3. Enable **Open as Popup/Fullscreen**
4. Save changes
5. View the quiz on the frontend
6. Click to open in fullscreen/popup

**Expected Result**:
- Headings questions (1-5) display correctly
- Matching questions (10-13) display correctly
- Matching/Classifying questions (18-24) display correctly
- All radio buttons work
- Navigation panel shows all questions
- Answer tracking works (answered questions highlighted)

### Test 7: Answer Submission in CBT Layout
**Purpose**: Verify answers save correctly in CBT layout

1. In CBT mode, answer all 40 questions
2. Submit the quiz

**Expected Result**:
- All answers submit successfully
- Results modal displays
- Score calculated correctly
- No errors in console

### Test 8: Multiple Choice Independence
**Purpose**: Verify multiple choice questions still work correctly

1. Create a new quiz manually in WordPress admin
2. Add a question with type "Multiple Choice"
3. Add 4 options, mark one as correct
4. Save the quiz
5. View on frontend

**Expected Result**:
- Question displays correctly
- All 4 options visible
- Radio buttons work
- Can select and submit answer
- Scoring works correctly

### Test 9: Admin Panel Test
**Purpose**: Verify admin UI works correctly for all types

1. Go to **IELTS Courses** → **Add New Quiz**
2. Add a question
3. Change question type to "Headings"

**Expected Result**:
- MC Options field shows
- Multi-select settings hide
- General feedback field hides
- Correct answer field hides

4. Change type to "Matching"

**Expected Result**:
- Same behavior as Headings
- Fields show/hide correctly

5. Change type to "Matching/Classifying"

**Expected Result**:
- Same behavior as Headings and Matching
- Fields show/hide correctly

### Test 10: Cross-Question Type Test
**Purpose**: Verify no interference between question types

1. Create a quiz with mixed question types:
   - Question 1: Multiple Choice
   - Question 2: Headings
   - Question 3: Matching
   - Question 4: Matching/Classifying
   - Question 5: True/False
   - Question 6: Short Answer

2. Add options/answers for each
3. Save and view on frontend
4. Answer all questions
5. Submit

**Expected Result**:
- All questions render correctly
- All answers save correctly
- No interference between types
- Correct scoring for all types

## Regression Testing

### Other Question Types to Verify
Test these to ensure no regressions:

1. **True/False Questions**
   - Display all 3 options (True, False, Not Given)
   - Answer submission works
   - Scoring correct

2. **Multi-Select Questions**
   - Checkboxes display correctly
   - Max selection limit enforced
   - Partial credit scoring works

3. **Short Answer Questions**
   - Text input displays
   - Answer acceptance works
   - Multiple acceptable answers work (pipe-separated)

4. **Summary Completion**
   - Inline inputs display correctly
   - Multiple blanks work
   - Answers save correctly

5. **Dropdown Paragraph**
   - Dropdowns render inline
   - Options display correctly
   - Answers save and validate

## Success Criteria

All tests should pass with the following outcomes:

✅ **Headings Questions**
- All 9 options display for each question
- Radio buttons work independently
- Answers save and submit correctly
- No "single radio button" issue
- Scoring works correctly

✅ **Matching Questions**
- Display correctly with all options
- Independent from other types
- Answer saving works

✅ **Matching/Classifying Questions**
- Display correctly with all options
- Independent from other types
- Answer saving works

✅ **No Regressions**
- All other question types still work
- No JavaScript errors
- No PHP errors

## Troubleshooting

### Issue: Only one radio button shows
**Cause**: Question data missing `mc_options` array
**Fix**: Re-import the test file or manually add options in admin

### Issue: Answers not saving
**Cause**: JavaScript error or form submission issue
**Check**: Browser console for errors

### Issue: Wrong question type assigned
**Cause**: Parser not detecting marker correctly
**Fix**: Ensure [HEADINGS], [MATCHING] markers are present in import text

## Browser Testing

Test in these browsers to ensure cross-browser compatibility:
- ✅ Chrome/Chromium
- ✅ Firefox
- ✅ Safari
- ✅ Edge
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Notes

With 40 questions on one page, monitor:
- Page load time
- Answer submission speed
- Memory usage in browser
- No lag when selecting radio buttons

## Conclusion

If all tests pass, the implementation successfully makes all question types 100% independent, fixing the headings question issue and preventing future interference between question types.
