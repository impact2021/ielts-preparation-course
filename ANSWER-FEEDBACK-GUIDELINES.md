# Answer Feedback Guidelines for IELTS Course Manager

## Purpose
This document explains how answer feedback works in the IELTS Course Manager plugin and provides clear guidelines to prevent issues where students don't see the correct answers.

## Critical Rule: Students MUST Always See the Correct Answer

**When a student doesn't answer a question or gets it wrong, they MUST be able to see what the correct answer is.**

This is essential for learning and review. Never show generic messages like "You should take a guess" without also showing the actual answer.

## How Answer Feedback Works

### Question Structure in XML

Short answer questions (like those in Listening tests) have two levels of feedback:

1. **Top-level feedback fields**:
   - `correct_feedback`: Shown when answer is correct
   - `incorrect_feedback`: Shown when answer is wrong
   - `no_answer_feedback`: Shown when no answer is provided

2. **Summary fields feedback** (for questions with summary_fields):
   - `summary_fields[N]['correct_feedback']`: Shown when field N is correct
   - `summary_fields[N]['incorrect_feedback']`: Shown when field N is wrong
   - `summary_fields[N]['no_answer_feedback']`: Shown when field N is not answered

### Processing Logic in class-quiz-handler.php

For **short_answer** questions:
- If the question has `summary_fields`, the system uses feedback from there
- The top-level feedback is only used as a fallback
- **Important**: The summary_fields feedback MUST include the correct answer

## Correct Feedback Examples

### ✅ CORRECT - Clear Answer Display
```
No Answer Feedback: "The correct answer is: HIS DAUGHTER. Make sure to listen carefully for key information and take notes while listening."

Incorrect Feedback: "Incorrect. The correct answer is: HIS DAUGHTER. Review the transcript to find where this is mentioned."
```

### ❌ WRONG - Generic Message Only
```
No Answer Feedback: "In the IELTS test, you should always take a guess. You don't lose points for a wrong answer."
```

This doesn't help students learn because they never see what the right answer was!

## XML Structure Requirements

### For Short Answer Questions

```xml
<question>
  <type>short_answer</type>
  <question>Who is the customer buying the puppy for?</question>
  <correct_answer>his daughter|daughter</correct_answer>
  
  <!-- Top-level feedback (used as fallback) -->
  <correct_feedback>Correct!</correct_feedback>
  <incorrect_feedback>Incorrect.</incorrect_feedback>
  <no_answer_feedback>Please provide an answer.</no_answer_feedback>
  
  <!-- Summary fields (PREFERRED - contains actual answers) -->
  <summary_fields>
    <field number="1">
      <answer>his daughter|daughter</answer>
      <correct_feedback>Correct!</correct_feedback>
      <incorrect_feedback>Incorrect.</incorrect_feedback>
      <no_answer_feedback>The correct answer is: HIS DAUGHTER. Make sure to listen carefully for key information and take notes while listening.</no_answer_feedback>
    </field>
  </summary_fields>
</question>
```

## Fixing Issues

### Problem: Students See Generic Message Instead of Answer

**Symptoms:**
- Students submit with no answers and see "In the IELTS test, you should always take a guess..."
- Students can't learn because they never see the correct answer

**Solution:**
1. Check the XML file for the question
2. Look at the `summary_fields[N]['no_answer_feedback']` field
3. Ensure it includes "The correct answer is: [ANSWER]"
4. If not, update it to show the correct answer clearly

### Problem: Wrong Feedback Showing

**Symptoms:**
- Correct answer shows wrong feedback
- Incorrect answer shows no feedback

**Solution:**
1. Verify all three feedback fields are filled in `summary_fields`
2. Ensure `correct_feedback` and `incorrect_feedback` are set
3. Test the question by submitting correct, incorrect, and no answer

## Code Implementation

### In class-quiz-handler.php

For short_answer questions with summary_fields:
```php
if (isset($question['summary_fields']) && is_array($question['summary_fields'])) {
    // Use summary_fields feedback (which includes correct answers)
    foreach ($question['summary_fields'] as $field_num => $field_data) {
        if ($user_answered_correctly) {
            $feedback = $field_data['correct_feedback'];
        } elseif ($user_answer_empty) {
            $feedback = $field_data['no_answer_feedback']; // Shows "The correct answer is: X"
        } else {
            $feedback = $field_data['incorrect_feedback'];
        }
    }
}
```

## Admin Interface

### Default Messages to Avoid

**Don't use as the only feedback:**
- "In the IELTS test, you should always take a guess. You don't lose points for a wrong answer."
- "You need to answer this question."
- "No answer provided."

**Instead use:**
- "The correct answer is: [ANSWER]. Make sure to listen carefully for key information and take notes while listening."
- "The correct answer is: [ANSWER]. Review the passage to find where this information is located."

## Testing Checklist

When creating or modifying quiz questions:

- [ ] All questions have correct_feedback filled
- [ ] All questions have incorrect_feedback filled
- [ ] All questions have no_answer_feedback filled
- [ ] no_answer_feedback clearly shows the correct answer
- [ ] Test by submitting with no answers - correct answer is visible
- [ ] Test by submitting with wrong answer - correct answer is visible
- [ ] Test by submitting with correct answer - congratulations shown

## Version History

- **Version 8.12**: Fixed issue where summary_fields feedback wasn't being used for short_answer questions, causing generic messages to show instead of actual answers
- **Version 8.11**: CSS fixes for feedback highlighting
- **Version 8.10**: Initial feedback improvements

## Summary

**The Golden Rule**: Every question's feedback must make it possible for a student to learn what the correct answer was. Generic encouragement is nice, but seeing the actual answer is essential for learning.

When in doubt, always include "The correct answer is: [ANSWER]" in your no_answer_feedback and incorrect_feedback fields.
