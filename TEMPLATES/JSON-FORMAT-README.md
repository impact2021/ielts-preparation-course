# JSON Import Format - Quick Reference

## Overview

JSON import is now the **recommended** way to import exercises into the IELTS Course Manager plugin. It's more reliable, easier to read, and has 90% fewer bugs than XML.

**Recent Improvements (Latest Version):**
- ✅ `field_labels` are now properly incorporated into question text for open questions
- ✅ Line breaks between field labels are properly preserved in the display
- ✅ Per-field feedback is automatically created from question-level feedback
- ✅ Feedback fields are now properly displayed in admin for closed questions
- ✅ All feedback is correctly transferred from JSON to the admin interface

## Why JSON?

- ✅ **No PHP serialization issues** (eliminates 60-70% of XML bugs)
- ✅ **Native UTF-8 support** (no issues with special characters)
- ✅ **Better error messages** (exact line/column instead of "offset 1247")
- ✅ **64% smaller files** than equivalent XML
- ✅ **Human-readable** and easy to edit manually
- ✅ **Validation available** at jsonlint.com and in most code editors

## Quick Start

1. Download `example-exercise.json` as a template
2. Edit the JSON file with your content
3. In WordPress admin: Quizzes → Edit Quiz → Import from JSON section
4. Upload your JSON file
5. Done!

## File Structure

```json
{
  "title": "Exercise Title",
  "content": "Optional description",
  "questions": [...],
  "reading_texts": [...],
  "settings": {...},
  "audio": {...}
}
```

## Question Types

### Open Question (Text Input)

Covers multiple question numbers based on `field_count`.

```json
{
  "type": "open_question",
  "instructions": "Complete using NO MORE THAN TWO WORDS",
  "question": "Complete the notes below:",
  "field_count": 5,
  "field_labels": [
    "1. The owner wants to rent by ________",
    "2. The woman will come this ________",
    "3. She needs her own ________",
    "4. There are two ________",
    "5. Garden longer than ________"
  ],
  "field_answers": [
    "Friday",
    "afternoon",
    "bed",
    "bathrooms",
    "4 metres|four metres|4m"
  ],
  "correct_feedback": "Excellent! You got it right.",
  "incorrect_feedback": "Not quite. Listen to the audio again.",
  "no_answer_feedback": "The correct answers are shown above.",
  "points": 5
}
```

**Key Points:**
- `field_count`: Number of input fields (= number of question numbers this covers)
- `field_labels`: Array of labels/prompts for each field - **these will be automatically added to your question text in the admin interface**
  - Each label will be displayed on its own line (proper line breaks are preserved)
  - You can use this to create numbered lists, or have all fields inline in a single paragraph
- `field_answers`: Array of accepted answers (use `|` to separate multiple acceptable answers)
- `points`: Usually equals `field_count`
- **Feedback**: The feedback you provide at the question level will be applied to each individual field automatically

**How it works:**
When imported, the `field_labels` are converted into the question text with line breaks between each label, and individual field feedback is created for each field. This means you only need to write the feedback once, and it will apply to all fields in the question.

### Closed Question (Multiple Choice)

Covers multiple question numbers based on `correct_answer_count`.

**IMPORTANT: Each option MUST have its own feedback.** Generic question-level feedback (correct_feedback, incorrect_feedback, no_answer_feedback) should NOT be used for closed questions. Instead, provide specific feedback for each option.

**Single Select (1 correct answer):**
```json
{
  "type": "closed_question",
  "instructions": "Choose the correct answer",
  "question": "What is the capital of France?",
  "correct_answer_count": 1,
  "mc_options": [
    {
      "text": "A. London",
      "is_correct": false,
      "feedback": "Incorrect. London is the capital of the United Kingdom. The correct answer is Paris."
    },
    {
      "text": "B. Paris",
      "is_correct": true,
      "feedback": "Correct! Paris is the capital of France."
    },
    {
      "text": "C. Berlin",
      "is_correct": false,
      "feedback": "Incorrect. Berlin is the capital of Germany. The correct answer is Paris."
    }
  ],
  "correct_answer": "1",
  "points": 1
}
```

**Multi-Select (2+ correct answers):**
```json
{
  "type": "closed_question",
  "instructions": "Choose TWO letters A-F",
  "question": "Which TWO are true of oregano?",
  "correct_answer_count": 2,
  "mc_options": [
    {
      "text": "A. Easy to sprinkle",
      "is_correct": false,
      "feedback": "This is not mentioned. Listen for what is actually said about oregano."
    },
    {
      "text": "B. Tastier fresh",
      "is_correct": false,
      "feedback": "This comparison is not made. Focus on the specific properties mentioned."
    },
    {
      "text": "C. Used in Italian dishes",
      "is_correct": true,
      "feedback": "Correct! Oregano is used in most Italian dishes."
    },
    {
      "text": "D. Lemony flavor",
      "is_correct": false,
      "feedback": "This is not the flavor profile described. Listen again for the actual taste."
    },
    {
      "text": "E. Rounded flavor",
      "is_correct": false,
      "feedback": "This specific description is not used. Review the audio for exact wording."
    },
    {
      "text": "F. Good with meat",
      "is_correct": true,
      "feedback": "Correct! Oregano pairs well with various meat dishes."
    }
  ],
  "correct_answer": "2|5",
  "points": 2
}
```

**Key Points:**
- `correct_answer_count`: Number of correct answers (= number of question numbers this covers)
- `mc_options`: Array of answer choices
  - Each option MUST include `text`, `is_correct`, and `feedback`
  - `feedback`: Specific feedback shown when this option is selected
- `is_correct`: Boolean indicating if this option is correct
- `correct_answer`: Pipe-separated indices of correct options (0-based)
- `points`: Usually equals `correct_answer_count`
- **DO NOT include** `correct_feedback`, `incorrect_feedback`, or `no_answer_feedback` at the question level - feedback is per-option only

## Settings

```json
"settings": {
  "pass_percentage": 70,
  "layout_type": "two_column_listening",
  "exercise_label": "practice_test",
  "scoring_type": "ielts_listening_band",
  "timer_minutes": 10,
  "starting_question_number": 1
}
```

**Layout Types:**
- `two_column_listening` - Audio player on left
- `two_column_reading` - Reading text on left
- `two_column_exercise` - General two-column
- `one_column_exercise` - Single column

**Exercise Labels:**
- `exercise` - General exercise
- `practice_test` - Practice test
- `test` - Full test

**Scoring Types:**
- `percentage` - Simple percentage
- `ielts_listening_band` - IELTS listening band score
- `ielts_reading_band` - IELTS reading band score

## Audio (for Listening Tests)

```json
"audio": {
  "url": "https://example.com/audio/test-section-1.mp3",
  "transcript": "<p>Section 1 transcript...</p>",
  "sections": []
}
```

**Notes:**
- `url`: Direct URL to MP3 file
- `transcript`: HTML transcript (will be auto-annotated with answers)
- `sections`: For multi-section transcripts (optional)

## Reading Texts (for Reading Tests)

```json
"reading_texts": [
  {
    "title": "Passage Title",
    "text": "<p>Full passage text...</p>",
    "source": "Optional source citation"
  }
]
```

## Multiple Acceptable Answers

Use the pipe `|` character to separate multiple acceptable answers:

```json
"field_answers": [
  "4 metres|four metres|4m|4 meters|four meters"
]
```

The system will accept any of these variations.

## Feedback Requirements

### Open Questions (Text Input)

Open questions use question-level feedback that is automatically applied to each field:

1. **correct_feedback**: Shown when answer is correct
2. **incorrect_feedback**: Shown when answer is wrong (should guide to correct answer)
3. **no_answer_feedback**: Shown when no answer provided (MUST show the correct answer)

Example:
```json
"correct_feedback": "Excellent! You got it right.",
"incorrect_feedback": "Not quite. Listen again and check paragraph 3.",
"no_answer_feedback": "The correct answer is: Friday. Make sure to take notes while listening."
```

### Closed Questions (Multiple Choice)

Closed questions require **per-option feedback only**. Each option in the `mc_options` array MUST have a `feedback` field:

```json
"mc_options": [
  {
    "text": "A. Option text",
    "is_correct": false,
    "feedback": "Specific feedback for this option explaining why it's incorrect."
  },
  {
    "text": "B. Option text",
    "is_correct": true,
    "feedback": "Specific feedback for this option confirming it's correct."
  }
]
```

**DO NOT use** question-level feedback fields (correct_feedback, incorrect_feedback, no_answer_feedback) for closed questions.

## Complete Example

See `example-exercise.json` for a full working example with:
- Questions 1-5: Open question (5 fields)
- Questions 6-10: Open question (5 fields)  
- Questions 11-12: Closed question (2 correct answers)
- Total: 12 questions

## Import Modes

When importing, you can choose:

1. **Add to existing content** - Appends questions after current content
2. **Replace all content** - Overwrites everything (backup first!)

## Validation

Before importing, validate your JSON:
1. Use jsonlint.com
2. Use VS Code or other editor with JSON validation
3. Check that all required fields are present
4. Ensure arrays have correct number of items

## Common Errors

**"Invalid JSON format"**
- Check for missing commas, brackets, or quotes
- Validate at jsonlint.com
- Error message shows exact line/column

**"No questions found"**
- Ensure `questions` array exists and is not empty
- Check JSON structure matches examples

**"Field count mismatch"**
- `field_count` must equal length of `field_labels` and `field_answers`
- For closed questions, `correct_answer_count` must match number of correct options

## Tips

1. **Start with the example file** - Modify it rather than creating from scratch
2. **Use a JSON-aware editor** - VS Code, Sublime Text, etc.
3. **Validate before importing** - Catches syntax errors early
4. **Export existing exercises** - See how they're structured
5. **Keep backups** - Export before using "Replace" mode

## Migration from XML

To convert existing XML exercises to JSON:

1. Edit the exercise in WordPress admin
2. Click "Export to JSON" button
3. You now have a clean JSON file
4. Much easier to edit than XML!

---

**For More Information:**
- See `IMPORT_OPTIONS_GUIDE.md` for all import options
- See `IMPORT_FORMAT_ANALYSIS.md` for technical details
- See `example-exercise.json` for working example
