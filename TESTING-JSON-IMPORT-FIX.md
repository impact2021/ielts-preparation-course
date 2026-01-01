# Testing Guide: JSON Import Fixes

## Overview
This document describes how to test the JSON import fixes for open_question and closed_question types.

## Issues Fixed
1. **Open Question (Issue 1)**: Field labels were not being incorporated into question text, and feedback was not being created for each field
2. **Closed Question (Issue 2)**: Feedback fields were not visible in the admin backend

## Test Setup

### Prerequisites
- WordPress with IELTS Course Manager plugin installed
- Admin access to create/edit exercises
- The example JSON file: `TEMPLATES/example-exercise.json`

## Test Cases

### Test Case 1: Import Example JSON (Replace Mode)

**Steps:**
1. Log in to WordPress admin
2. Navigate to "Quizzes" → "Add New" or edit an existing exercise
3. Save the exercise first (if new)
4. Scroll to the "Import/Export" section in the sidebar
5. In the "Import from JSON" section:
   - Select "Replace all content" mode
   - Click "Choose File" and select `TEMPLATES/example-exercise.json`
   - Click "Upload & Import JSON"
6. Wait for success message
7. Page will reload automatically

**Expected Results:**
- Success message: "Exercise content replaced successfully!"
- Page reloads showing the imported content

### Test Case 2: Verify Open Question (Question 1)

**Steps:**
1. After import, scroll to the Questions section
2. Find Question 1 (type: "Open Question")
3. Examine the "Question Text" field
4. Expand the "Open Question Settings" section
5. Check the "Field Answers and Feedback" section

**Expected Results:**

✅ **Question Text should contain:**
```
Complete the following:

1. The owner wants to rent the house by ________
2. The woman will come to look at the house this ________
3. The woman will need to have her own ________
4. There are two ________
5. The garden is slightly longer than ________
```

✅ **Field Count:** 5

✅ **Field 1:**
- Correct Answer: `Friday`
- Correct Answer Feedback: `Excellent! You got it right.`
- Incorrect Answer Feedback: `Not quite. Listen to the audio again and check the transcript.`
- No Answer Feedback: `The correct answer is shown above. Listen carefully and take notes while listening.`

✅ **Fields 2-5:** Should have the same feedback as Field 1, with different correct answers:
- Field 2: `afternoon`
- Field 3: `bed`
- Field 4: `bathrooms`
- Field 5: `4 metres|four metres|4 m|4m|4 meters|four meters`

**Common Issues (These should NOT happen):**
❌ Question text only says "Complete the following:" without the field labels
❌ Feedback fields are empty
❌ "No answer given" is the only feedback shown

### Test Case 3: Verify Open Question (Question 2)

**Steps:**
1. Scroll to Question 2 (also type: "Open Question")
2. Check the question text and field settings

**Expected Results:**

✅ **Question Text should contain:**
```
Label the map locations (Questions 6-10):

6. ________
7. ________
8. ________
9. ________
10. ________
```

✅ **Field Count:** 5

✅ **Field Answers:**
- Field 1: `post office`
- Field 2: `Hill Park`
- Field 3: `Wood Lane`
- Field 4: `Petrol station|gas station`
- Field 5: `Bus stop`

✅ **Each field has:**
- Correct Answer Feedback: `Correct! Well done.`
- Incorrect Answer Feedback: `Not quite. Review the map and listen again.`
- No Answer Feedback: `The correct answer is shown above.`

### Test Case 4: Verify Closed Question (Question 3)

**Steps:**
1. Scroll to Question 3 (type: "Closed Question")
2. Check that feedback fields are visible (this is the main fix for closed questions)
3. Examine the feedback content

**Expected Results:**

✅ **Question Text:**
```
Select the TWO correct answers:
```

✅ **Instructions:**
```
Which TWO of the following does the chef say are true of the herb oregano? Choose TWO letters A-F.
```

✅ **Options (6 total):**
- A. It's easy to sprinkle on food ❌
- B. It's tastier when fresh ❌
- C. It's used in the majority of Italian dishes ✅
- D. It has a lemony flavour ❌
- E. It gives food a rounded flavour ❌
- F. It's a good accompaniment to many meat dishes ✅

✅ **Feedback Fields (MUST BE VISIBLE - this was the bug):**
- **Correct Answer Feedback:** `Correct! The answers are C and F.`
- **Incorrect Answer Feedback:** `Not quite. The correct answers are C and F. Listen again for what the chef says about oregano.`
- **No Answer Selected Feedback:** `The correct answers are C and F.`

**Common Issues (These should NOT happen):**
❌ Feedback fields are hidden or show "display:none"
❌ Only "No answer given" message appears
❌ Feedback fields are empty

### Test Case 5: Import in Append Mode

**Steps:**
1. Create a new exercise or use an existing one with some questions
2. Note the current number of questions
3. Go to "Import from JSON" section
4. Select "Add to existing content" mode
5. Upload `TEMPLATES/example-exercise.json`
6. Click "Upload & Import JSON"

**Expected Results:**
- Success message: "Exercise content added successfully!"
- The 3 new questions are added AFTER existing questions
- All imported questions have proper formatting (field labels, feedback, etc.)
- Existing questions remain unchanged

### Test Case 6: Verify No Duplicate Transformation

**Technical Test (for developers):**

This test verifies that the transformation only happens once (not twice as it did before the fix).

**Steps:**
1. Enable WordPress debug mode (WP_DEBUG = true in wp-config.php)
2. Import the JSON in append mode
3. Check debug.log for any errors or warnings

**Expected Results:**
- No errors or warnings in debug.log
- Import completes successfully
- Questions are properly formatted

## Validation Checklist

After completing all test cases, verify:

- [ ] Question 1 has field labels in question text (not just "Complete the following:")
- [ ] Question 1 has feedback for all 5 fields
- [ ] Question 2 has field labels in question text  
- [ ] Question 2 has feedback for all 5 fields
- [ ] Question 3 shows feedback fields in admin (not hidden)
- [ ] Question 3 has all three types of feedback populated
- [ ] Append mode works correctly without duplicating transformations
- [ ] No PHP errors or warnings in debug.log
- [ ] Exercise can be saved successfully after import
- [ ] Exercise displays correctly on the frontend (bonus test)

## Troubleshooting

### Issue: Field labels not showing in question text
**Cause:** Old version of plugin or import didn't transform questions
**Solution:** Ensure you have the latest version with the transformation function

### Issue: Feedback fields empty for open questions
**Cause:** Transformation didn't create per-field feedback
**Solution:** Check that transform_json_questions_to_admin_format() is being called

### Issue: Feedback fields hidden for closed questions
**Cause:** Old CSS hiding the fields
**Solution:** Ensure 'closed_question' is added to the list on line 3091

### Issue: Double transformation error
**Cause:** Transformation called twice in append mode
**Solution:** Verify the append function doesn't call transformation again

## Success Criteria

The JSON import is working correctly when:

1. ✅ All field labels appear in the question text for open questions
2. ✅ All fields have proper feedback (not just "No answer given")
3. ✅ Closed questions show feedback fields in admin
4. ✅ All feedback from JSON is transferred to admin interface
5. ✅ No PHP errors or warnings occur during import
6. ✅ Both replace and append modes work correctly

## Next Steps

If all tests pass:
- [ ] Mark the issue as resolved
- [ ] Update version number
- [ ] Deploy to production

If any tests fail:
- [ ] Document the failure
- [ ] Check the transformation function
- [ ] Verify the admin display CSS
- [ ] Review the append mode logic
