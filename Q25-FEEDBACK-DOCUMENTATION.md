# Q25 Feedback Documentation - Reading Test 8

## Question

**Q25** in Reading Test 8 is a heading matching question:
- **Type**: `closed_question` (multiple choice)
- **Question Text**: "Paragraph B"
- **Instructions**: "Choose the most suitable headings for paragraphs B-G from the list below. Use each heading once only."
- **Correct Answer**: Option 6 (index 6 in the array)
- **Correct Answer Text**: "vii. Temporary gain outweighing long term concerns"

## Feedback When User Enters No Answer

When a user submits Q25 without selecting any answer, the system displays the following feedback:

```
You did not select an answer. The correct answer is vii. Paragraph B discusses how immediate financial benefits from timber are prioritized over long-term environmental sustainability.
```

### Feedback Structure

This feedback follows the IELTS Course Manager guidelines by:

1. **Acknowledging the user action**: "You did not select an answer"
2. **Showing the correct answer clearly**: "The correct answer is vii"
3. **Explaining WHY it's correct**: Provides context about Paragraph B discussing "immediate financial benefits from timber" being prioritized over "long-term environmental sustainability"

## Implementation Details

### JSON Structure

```json
{
  "type": "closed_question",
  "question": "Paragraph B",
  "correct_answer": "6",
  "no_answer_feedback": "You did not select an answer. The correct answer is vii. Paragraph B discusses how immediate financial benefits from timber are prioritized over long-term environmental sustainability.",
  "mc_options": [
    // ... options 0-5 ...
    {
      "text": "vii. Temporary gain outweighing long term concerns",
      "is_correct": true,
      "feedback": "Correct! Paragraph B discusses how immediate financial benefits from timber are prioritized over long-term environmental sustainability."
    },
    // ... options 7-8 ...
  ]
}
```

### Code Processing

The quiz handler (`includes/class-quiz-handler.php`) processes this as follows:

```php
} elseif ($user_answer === null || $user_answer === '') {
    // No answer provided - use no_answer_feedback if available
    if (isset($question['no_answer_feedback']) && !empty($question['no_answer_feedback'])) {
        $feedback = wp_kses_post($question['no_answer_feedback']);
    } else {
        $feedback = '';
    }
}
```

When the user's answer is null or empty string, the system uses the `no_answer_feedback` field from the question data.

## Compliance with Guidelines

This feedback implementation follows:

1. **ANSWER-FEEDBACK-GUIDELINES.md**: 
   - ✅ Students MUST always see the correct answer
   - ✅ Never shows generic messages without the actual answer
   - ✅ Includes "The correct answer is: [ANSWER]"
   - ✅ Helps students learn by showing what the right answer was

2. **CRITICAL-FEEDBACK-RULES.md**:
   - ✅ Uses question-level `no_answer_feedback` for closed questions
   - ✅ Does not use the deprecated generic feedback table
   - ✅ Each mc_option has its own feedback for when selected

## Verification

All 35 questions in Reading Test 8 have been verified to have proper `no_answer_feedback` fields that:
- Clearly state the correct answer
- Provide explanatory context
- Follow the established guidelines

## Summary

**Answer to the question**: "What feedback should be given if the user enters no answer for Q25?"

The feedback shown is:
> You did not select an answer. The correct answer is vii. Paragraph B discusses how immediate financial benefits from timber are prioritized over long-term environmental sustainability.

This feedback is already properly configured in the JSON file and will be displayed correctly by the system when a user submits Q25 without selecting any answer.
