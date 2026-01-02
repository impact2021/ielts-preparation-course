# ⚠️ CRITICAL FEEDBACK RULES - READ FIRST ⚠️

## ⛔ RULE #1: NEVER REINSTATE THE GENERIC FEEDBACK TABLE

**The generic "Answer Feedback" table (`.general-feedback-field`) is PERMANENTLY DELETED.**

This table contained:
- Correct Answer Feedback
- Incorrect Answer Feedback  
- No Answer Feedback

**DO NOT** ever add this back to the admin interface under any circumstances.

If you are asked to "add feedback", "fix feedback", or work on any feedback-related feature:
1. **STOP**
2. **READ THIS FILE**
3. **READ** `TEMPLATES/JSON-FORMAT-README.md` sections on feedback
4. **READ** `ANSWER-FEEDBACK-GUIDELINES.md`
5. **THEN** proceed with changes

---

## How Feedback Actually Works

### Open Questions (Text Input)

**Structure:**
- Has `field_count` number of input fields
- Each field has its own individual feedback

**Feedback Per Field:**
- `correct_feedback` - Shown when this specific field is correct
- `incorrect_feedback` - Shown when this specific field is wrong
- `no_answer_feedback` - Shown when this specific field is left empty

**Admin Interface:**
- Open questions show fields (field_labels, field_answers)
- Each field can have its own feedback (managed in field-specific UI)
- NO generic feedback table

### Closed Questions (Multiple Choice)

**Structure:**
- Has `mc_options` array with multiple answer choices
- Each option has its own individual feedback

**Feedback Per Option:**
- Each `mc_option` has a `feedback` field
- This feedback is shown when the student selects that option
- Correct options show positive feedback
- Incorrect options show corrective feedback

**NO ANSWER Feedback:**
- Single field `no_answer_feedback` at question level
- Shown when student submits without selecting ANY option
- This is the ONLY question-level feedback for closed questions

**Admin Interface:**
- Closed questions show mc_options with feedback per option
- One single NO ANSWER feedback field for the entire question
- NO generic feedback table

---

## The Critical Difference

| Feature | Open Question | Closed Question |
|---------|--------------|-----------------|
| **Correct Feedback** | Per field | Per option (in mc_options) |
| **Incorrect Feedback** | Per field | Per option (in mc_options) |
| **NO ANSWER Feedback** | Per field | Single field for entire question |
| **Generic Feedback Table** | ❌ NEVER | ❌ NEVER |

---

## JSON Import Format

### Open Question
```json
{
  "type": "open_question",
  "field_count": 3,
  "field_labels": ["1. Label", "2. Label", "3. Label"],
  "field_answers": ["answer1", "answer2", "answer3"],
  "correct_feedback": "Applied to each field",
  "incorrect_feedback": "Applied to each field",
  "no_answer_feedback": "Applied to each field"
}
```

### Closed Question
```json
{
  "type": "closed_question",
  "correct_answer_count": 1,
  "mc_options": [
    {
      "text": "A. Option",
      "is_correct": false,
      "feedback": "Feedback for THIS option only"
    },
    {
      "text": "B. Option",
      "is_correct": true,
      "feedback": "Feedback for THIS option only"
    }
  ],
  "no_answer_feedback": "Shown when nothing is selected"
}
```

**Note:** Closed questions do NOT have question-level `correct_feedback` or `incorrect_feedback`.

---

## Memory Storage

**STORE THIS IN MEMORY:**

1. ❌ **NEVER** add back the generic "Answer Feedback" table (`.general-feedback-field`)
2. ✅ **ALWAYS** use per-option feedback for closed questions
3. ✅ **ALWAYS** use per-field feedback for open questions
4. ✅ Closed questions have ONE `no_answer_feedback` field (not per-option)
5. ✅ Open questions have `no_answer_feedback` per field
6. ✅ **ALWAYS** check this file + JSON-FORMAT-README.md + ANSWER-FEEDBACK-GUIDELINES.md before any feedback changes

---

## Version History

- **2026-01-02**: Created this critical rules file to prevent future mistakes
- Permanent rule: Generic feedback table is DELETED and must NEVER be reinstated

---

**IF YOU SEE THE GENERIC FEEDBACK TABLE IN THE CODE, DELETE IT IMMEDIATELY.**
