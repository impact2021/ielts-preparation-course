# Dropdown Question Type - FAQ

## ✅ NOW Available in WordPress Admin UI!

**The `closed_question_dropdown` type is now available in the WordPress admin interface!**

### How to Create Dropdown Questions

You have two options:

1. **WordPress Admin UI** (New!) - Select "Closed Question Dropdown (Inline dropdown menus)" from the Question Type dropdown
   - Use `[dropdown]` placeholders in your question text
   - Add your options in the same options interface as regular multiple choice
   - All dropdowns will show the same set of options
   
2. **JSON Import** - Use the `closed_question_dropdown` type as documented below
   - Useful for bulk import or complex exercises
   - Same functionality as the admin UI

---

## Question: Where do I put the dropdown options?

### Quick Answer

For the `[dropdown]` question type (technically called `closed_question_dropdown`), you put the dropdown options in the **options list** (in admin UI) or **`mc_options`** array (in JSON), just like a regular multiple choice question. The `[dropdown]` placeholder in your question text is where the dropdown menu will appear.

---

## Using Dropdown Questions in WordPress Admin UI

### Step-by-Step Instructions:

1. **Create or Edit a Quiz** in WordPress Admin
2. **Add a Question** or edit an existing one
3. **Select Question Type**: Choose "Closed Question Dropdown (Inline dropdown menus)"
4. **Write Your Question**: Use `[dropdown]` placeholders where you want dropdown menus
   - Example: "The student [dropdown] to the library yesterday."
   - For multiple dropdowns: "She [dropdown] working [dropdown] this company [dropdown] five years."
5. **Set Number of Dropdowns**: Enter how many `[dropdown]` placeholders you used
6. **Add Options**: Add your dropdown options in the options list (same as multiple choice)
   - Each option needs text, feedback, and correct/incorrect marking
   - All dropdowns will show the same options
7. **Save** the quiz

### What You'll See:
- When students view the quiz, they'll see dropdown menus inline in the question text
- Each dropdown shows all the options you defined
- Students select the correct option from each dropdown

---

## Complete Example: Single Dropdown (JSON Format)

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

## Complete Example: Numbered Dropdowns (NEW!)

You can now include question numbers inline with dropdowns by using numbered placeholders like `1.[dropdown]`, `2.[dropdown]`, etc.

```json
{
  "type": "closed_question_dropdown",
  "ielts_question_type": "Multiple Choice (Multiple Answers)",
  "instructions": "Complete the dialogue by selecting the correct options from the dropdown menus.",
  "question": "<strong>Examiner:</strong> Do you think parents should allow children to watch television?<br><strong>Candidate:</strong> Well, that's an interesting question. There are clearly some 1.[dropdown] where watching television does not negatively affect children. Some educational programmes, for example, can help 2.[dropdown].",
  "correct_answer_count": 2,
  "mc_options": [
    {
      "text": "situations",
      "is_correct": true,
      "feedback": "Correct! 'Situations' fits well in this context."
    },
    {
      "text": "times",
      "is_correct": false,
      "feedback": "Not quite. 'Times' doesn't work as well in this context."
    },
    {
      "text": "children learn",
      "is_correct": false,
      "feedback": "Close, but not the best answer."
    },
    {
      "text": "children to learn",
      "is_correct": true,
      "feedback": "Correct! 'Help children to learn' is the right construction."
    }
  ],
  "correct_answer": "field_1:0|field_2:3",
  "no_answer_feedback": "You did not complete all the answers.",
  "points": 2
}
```

### How it Works:
1. **Numbered Syntax**: Use `1.[dropdown]`, `2.[dropdown]`, etc. instead of just `[dropdown]`
2. **Display**: The numbers (1., 2., etc.) will appear inline before each dropdown
3. **Same Options**: All dropdowns still use the same `mc_options` list
4. **Backward Compatible**: You can still use `[dropdown]` without numbers if you prefer

### When to Use Numbered Dropdowns:
- ✅ Use when you want explicit question numbers visible inline with dropdowns
- ✅ Useful for IELTS-style questions where each blank needs a number
- ✅ Better for dialogues or passages with multiple blanks
- ❌ Not needed if you prefer the cleaner look without numbers

---

## Key Points to Remember

### ✅ DO:
- Use `"type": "closed_question_dropdown"` (not just "dropdown")
- Put all your dropdown options in the `mc_options` array
- Use `[dropdown]` placeholders in your question text (or `1.[dropdown]`, `2.[dropdown]` for numbered dropdowns)
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
├── question: "Your text with [dropdown] or 1.[dropdown], 2.[dropdown] placeholder(s)"
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

See the complete working example files:
- **`TEMPLATES/example-dropdown-closed-question.json`** - Full exercise with 3 dropdown questions
- **`TEST-DROPDOWN-NUMBERED.json`** - Example with numbered dropdowns (NEW!)

See the full documentation:
- **`TEMPLATES/JSON-FORMAT-README.md`** - Complete JSON format reference (lines 210-299)

---

## Common Mistakes

### ❌ Wrong: Forgetting the [dropdown] placeholder
If you select "Closed Question Dropdown" but don't use `[dropdown]` in your question text, the dropdowns won't render properly. Make sure to include the placeholder!

### ❌ Wrong: Creating a separate dropdown_options field (JSON only)
```json
{
  "type": "closed_question_dropdown",
  "question": "I [dropdown] to the store.",
  "dropdown_options": ["go", "went", "going"]  // ❌ This won't work!
}
```

### ✅ Correct: Use mc_options array (JSON) or Options list (Admin UI)

**In WordPress Admin UI:**
1. Select "Closed Question Dropdown" as question type
2. Write question with `[dropdown]` placeholders
3. Add options using the options interface

**In JSON:**
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

## Still Have Questions?

- Check `TEMPLATES/JSON-FORMAT-README.md` for complete documentation
- Open `TEMPLATES/example-dropdown-closed-question.json` for a working example
- Look at `main/Exercises/describing-people.json` for real-world usage
