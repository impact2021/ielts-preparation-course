# JSON Import Fix for Single-Field Open Questions

## Problem Statement
When importing Academic Reading Test 5 JSON, question 21 (and other single-field open questions) had feedback in the JSON file, but after upload:
- Answer fields were empty
- Feedback fields were empty
- `[field n]` markers were not showing in the question

## Root Cause Analysis

### The Issue
The `transform_json_questions_to_admin_format()` function in `includes/admin/class-admin.php` had logic to handle two types of questions:

1. **Multi-field open questions** with `field_labels` array
2. **Closed questions** with `mc_options`

However, it did **not** handle single-field open questions that use:
- `correct_answer` (string) instead of `field_answers` (array)
- Question-level feedback fields instead of `field_feedback` array

### JSON Format vs Admin Format

**JSON Format (single-field open question):**
```json
{
  "type": "open_question",
  "question": "Railroad companies were looking for a system to track their ________.",
  "correct_answer": "railway cars",
  "correct_feedback": "Correct! Well done.",
  "incorrect_feedback": "Incorrect. The correct answer is: RAILWAY CARS.",
  "no_answer_feedback": "The correct answer is: RAILWAY CARS. Always try to answer.",
  "field_labels": null,
  "field_count": null
}
```

**Expected Admin Format:**
```php
array(
  'type' => 'open_question',
  'question' => 'Railroad companies were looking for a system to track their [field 1].',
  'field_count' => 1,
  'field_answers' => array(
    1 => 'railway cars'
  ),
  'field_feedback' => array(
    1 => array(
      'correct' => 'Correct! Well done.',
      'incorrect' => 'Incorrect. The correct answer is: RAILWAY CARS.',
      'no_answer' => 'The correct answer is: RAILWAY CARS. Always try to answer.'
    )
  )
)
```

## Solution

### Code Changes
Added a new conditional branch in `transform_json_questions_to_admin_format()` to handle single-field open questions:

```php
elseif ($type === 'open_question' && !isset($question['field_labels'])) {
    // Handle single-field open questions without field_labels
    // These questions use correct_answer instead of field_answers
    
    // Set field_count to 1
    $question['field_count'] = 1;
    
    // Convert correct_answer to field_answers[1]
    if (isset($question['correct_answer'])) {
        $question['field_answers'] = array(
            1 => $question['correct_answer']
        );
        unset($question['correct_answer']);
    }
    
    // Create field_feedback[1] from question-level feedback
    $question['field_feedback'] = array(
        1 => array(
            'correct' => isset($question['correct_feedback']) ? $question['correct_feedback'] : '',
            'incorrect' => isset($question['incorrect_feedback']) ? $question['incorrect_feedback'] : '',
            'no_answer' => isset($question['no_answer_feedback']) ? $question['no_answer_feedback'] : ''
        )
    );
    
    // Replace ________ (8 or more underscores) with [field 1] in question text
    if (isset($question['question'])) {
        $question['question'] = preg_replace('/_{8,}/', '[field 1]', $question['question']);
    }
}
```

### Location
File: `includes/admin/class-admin.php`
Function: `transform_json_questions_to_admin_format()`
Lines: 7083-7110

## Testing

### Test Methodology
Created standalone PHP test scripts to verify the transformation logic:

1. **Unit test** - Tested transformation of a single question
2. **Integration test** - Tested transformation of all questions in Academic Reading Test 5

### Test Results

**Questions Affected in Academic Reading Test 5:**
- Question 18 (index 17): "punch cards"
- Question 19 (index 18): "product information"
- Question 20 (index 19): "bulls-eye|bull's-eye"
- Question 21 (index 20): "railway cars" ← **Original reported issue**
- Question 22 (index 21): "laser|laser light"
- Question 23 (index 22): "1974"
- Plus 6 more questions

**Total:** 12 single-field open questions properly transformed

### Verification Checklist
All tests passed:
- ✅ `field_count` set to 1
- ✅ `field_answers[1]` contains the correct answer
- ✅ `field_feedback[1]['correct']` contains correct feedback
- ✅ `field_feedback[1]['incorrect']` contains incorrect feedback
- ✅ `field_feedback[1]['no_answer']` contains no-answer feedback
- ✅ `correct_answer` field removed from output
- ✅ Underscores replaced with `[field 1]` marker

## Impact

### Files Modified
- `includes/admin/class-admin.php` - Added 28 lines to handle single-field open questions

### Backward Compatibility
✅ **No breaking changes**
- Existing multi-field questions with `field_labels` continue to work
- Existing closed questions continue to work
- Only adds support for previously unsupported question format

### Questions Fixed
This fix resolves the issue for any JSON import containing single-field `open_question` types without `field_labels`, which includes:
- Academic Reading Test 5 (12 questions)
- Any other reading tests using similar format

## Code Review Notes

### Minor Suggestion from Review
The code review suggested using a constant for the magic number `8` in the regex pattern `/_{8,}/`. However, this pattern is already used twice in the same function (including the existing multi-field logic at line 7043), so keeping it consistent with the existing code.

## Security Summary

### Vulnerability Analysis
✅ **No security issues introduced**

- Uses existing sanitization patterns
- No SQL injection risks (uses WordPress metadata functions)
- No XSS risks (data is sanitized when saved and displayed)
- No file system access vulnerabilities
- No authentication bypass

### CodeQL Results
CodeQL checker did not detect any vulnerabilities in the changes.

## User Impact

### Before Fix
When importing Academic Reading Test 5 JSON:
1. Questions 18-23 (and others) appeared in the admin interface
2. BUT answer fields were empty
3. AND feedback fields were empty
4. Users had to manually re-enter all answers and feedback

### After Fix
When importing Academic Reading Test 5 JSON:
1. Questions 18-23 (and others) appear in the admin interface
2. Answer fields are **populated** with correct answers
3. Feedback fields are **populated** with appropriate feedback
4. `[field 1]` markers show where input boxes appear (if underscores present)
5. No manual data entry needed

## Deployment

### Installation
Simply merge this PR and update the plugin. No database migrations or additional configuration needed.

### Testing Recommendations
After deployment:
1. Create a new exercise in WordPress admin
2. Import `Academic-IELTS-Reading-Test-05.json`
3. Verify question 21 shows:
   - Answer: "railway cars"
   - Correct feedback: "Correct! Well done."
   - Incorrect feedback: "Incorrect. The correct answer is: RAILWAY CARS..."
   - No answer feedback: "The correct answer is: RAILWAY CARS..."
4. Verify all fields are populated and editable

## Version History
- **2026-01-14**: Initial fix implemented and tested
- Function: `transform_json_questions_to_admin_format()`
- Affected questions: Single-field `open_question` types without `field_labels`

## Related Documentation
- `ANSWER-FEEDBACK-GUIDELINES.md` - Guidelines for answer feedback
- `CRITICAL-FEEDBACK-RULES.md` - Feedback rules for closed questions
- `JSON-IMPORT-FIX-SUMMARY.md` - Previous JSON import fixes
