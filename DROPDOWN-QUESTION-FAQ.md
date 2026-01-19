# Dropdown Question Type - FAQ

## ⚠️ IMPORTANT: JSON Import Only

**The `closed_question_dropdown` type is ONLY available through JSON import.** It is NOT available in the WordPress admin interface when creating exercises manually.

### How to Create Dropdown Questions

You have two options:

1. **JSON Import** (Recommended for dropdown questions) - Use the `closed_question_dropdown` type as documented below
2. **WordPress Admin UI** - Only supports standard `closed_question` (traditional multiple choice) and `open_question` (text input) types

---

## Question: Where do I put the dropdown options?

### Quick Answer (For JSON Import)

For the `[dropdown]` question type (technically called `closed_question_dropdown`), you put the dropdown options in the **`mc_options`** array, just like a regular multiple choice question. The `[dropdown]` placeholder in your question text is where the dropdown menu will appear.

**Note:** This question type must be created via JSON import - it cannot be created directly in the WordPress admin interface.

---

## Complete Example: Single Dropdown

```json
{
  "type": "closed_question_dropdown",
  "ielts_question_type": "Multiple Choice (Single Answer)",
  "instructions": "Complete the sentence using the dropdown menu.",
  "question": "I know that [dropdown] this might be difficult.",
  "correct_answer_count": 1,
  "mc_options": [
    {
      "text": "completing",
      "is_correct": true,
      "feedback": "Correct! 'Completing' is the right verb form here."
    },
    {
      "text": "complete",
      "is_correct": false,
      "feedback": "Incorrect. The sentence requires a gerund (verb+ing form)."
    },
    {
      "text": "completed",
      "is_correct": false,
      "feedback": "Incorrect. The past tense doesn't fit the context."
    }
  ],
  "correct_answer": "field_1:0",
  "no_answer_feedback": "You did not select an answer. The correct answer is 'completing'.",
  "points": 1
}
```

### How it Works:
1. **`question`**: Use `[dropdown]` as a placeholder where the dropdown should appear
2. **`mc_options`**: List all the options that will appear in the dropdown menu
3. **`correct_answer`**: Format is `"field_1:X"` where X is the index (0-based) of the correct option in `mc_options`

---

## Complete Example: Multiple Dropdowns

```json
{
  "type": "closed_question_dropdown",
  "ielts_question_type": "Multiple Choice (Multiple Answers)",
  "instructions": "Complete the paragraph using the dropdown menus.",
  "question": "The student [dropdown] to the library yesterday and [dropdown] three books.",
  "correct_answer_count": 2,
  "mc_options": [
    {
      "text": "go",
      "is_correct": false,
      "feedback": "Incorrect. This is present tense, but 'yesterday' indicates past tense."
    },
    {
      "text": "went",
      "is_correct": true,
      "feedback": "Correct! 'Went' is the past tense of 'go'."
    },
    {
      "text": "going",
      "is_correct": false,
      "feedback": "Incorrect. The gerund form doesn't fit this sentence."
    },
    {
      "text": "borrow",
      "is_correct": false,
      "feedback": "Incorrect. This is present tense, but the sentence needs past tense."
    },
    {
      "text": "borrowed",
      "is_correct": true,
      "feedback": "Correct! 'Borrowed' is the past tense."
    }
  ],
  "correct_answer": "field_1:1|field_2:4",
  "no_answer_feedback": "You did not complete all answers. The correct answers are 'went' and 'borrowed'.",
  "points": 2
}
```

### How it Works:
1. **`question`**: Use multiple `[dropdown]` placeholders where dropdowns should appear
2. **`mc_options`**: All dropdowns will show the **same** set of options
3. **`correct_answer`**: Format is `"field_1:X|field_2:Y"` where X and Y are the indices of correct options
   - `field_1:1` means the first dropdown's correct answer is at index 1 (which is "went")
   - `field_2:4` means the second dropdown's correct answer is at index 4 (which is "borrowed")

---

## Key Points to Remember

### ✅ DO:
- Use `"type": "closed_question_dropdown"` (not just "dropdown")
- Put all your dropdown options in the `mc_options` array
- Use `[dropdown]` placeholders in your question text
- Mark which options are correct with `"is_correct": true`
- Include feedback for each option
- Use 0-based indexing for `correct_answer` (first option is index 0)

### ❌ DON'T:
- Don't use a separate field for dropdown options - they go in `mc_options`
- Don't forget the `[dropdown]` placeholder in your question text
- Don't use 1-based indexing (first option is 0, not 1)
- Don't forget to set `correct_answer_count` to match the number of dropdowns

---

## Structure Summary

```
closed_question_dropdown
├── type: "closed_question_dropdown"
├── question: "Your text with [dropdown] placeholder(s)"
├── correct_answer_count: (number of dropdowns)
├── mc_options: [
│   ├── { text: "option1", is_correct: true/false, feedback: "..." }
│   ├── { text: "option2", is_correct: true/false, feedback: "..." }
│   └── ...
│   ]
├── correct_answer: "field_1:X|field_2:Y|..." (0-based indices)
└── no_answer_feedback: "..."
```

---

## More Examples

See the complete working example file:
- **`TEMPLATES/example-dropdown-closed-question.json`** - Full exercise with 3 dropdown questions

See the full documentation:
- **`TEMPLATES/JSON-FORMAT-README.md`** - Complete JSON format reference (lines 210-299)

---

## Common Mistakes

### ❌ Wrong: Trying to create in WordPress Admin UI
The `closed_question_dropdown` type is **not available** in the WordPress admin interface. You will only see:
- **Closed Question** (traditional multiple choice with radio buttons/checkboxes)
- **Open Question** (text input fields)

To use inline dropdown questions, you **must use JSON import**.

### ❌ Wrong: Creating a separate dropdown_options field
```json
{
  "type": "closed_question_dropdown",
  "question": "I [dropdown] to the store.",
  "dropdown_options": ["go", "went", "going"]  // ❌ This won't work!
}
```

### ✅ Correct: Use mc_options array (via JSON import)
```json
{
  "type": "closed_question_dropdown",
  "question": "I [dropdown] to the store.",
  "mc_options": [
    {"text": "go", "is_correct": false, "feedback": "..."},
    {"text": "went", "is_correct": true, "feedback": "..."},
    {"text": "going", "is_correct": false, "feedback": "..."}
  ],
  "correct_answer": "field_1:1"
}
```

---

## If You're Using the WordPress Admin Interface...

If you're seeing the WordPress admin interface (like in the screenshot with many dropdown option fields), you're using the standard **Closed Question** type, not `closed_question_dropdown`.

**What you're seeing is the UI for traditional multiple choice questions** with radio buttons or checkboxes - not inline dropdown questions.

### To create inline dropdown questions:
1. Create a JSON file with your exercise (see examples above)
2. Go to WordPress Admin → Quizzes → Edit Quiz
3. Find the "Import from JSON" section
4. Upload your JSON file
5. The `closed_question_dropdown` type will be imported and work correctly

---

## Still Have Questions?

- Check `TEMPLATES/JSON-FORMAT-README.md` for complete documentation
- Open `TEMPLATES/example-dropdown-closed-question.json` for a working example
- Look at `main/Exercises/describing-people.json` for real-world usage
