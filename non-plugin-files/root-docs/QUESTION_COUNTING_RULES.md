# CRITICAL: How to Count Questions Correctly in IELTS Tests

## ⚠️ Common Mistake - READ THIS FIRST

**DO NOT count JSON objects as questions!**

The number of JSON objects in the `questions` array **DOES NOT EQUAL** the number of student-facing questions.

---

## Correct Counting Rules

### Rule 1: Summary Completion Questions
**Each `[field N]` placeholder is ONE question**

```json
{
  "type": "summary_completion",
  "question": "Fill in the blanks...",
  "summary_fields": {
    "1": {"answer": "answer1"},
    "2": {"answer": "answer2"},
    "3": {"answer": "answer3"}
  }
}
```
- JSON objects: **1**
- Student questions: **3** (one for each field)

### Rule 2: Open Questions with Multiple Fields
**Each `[field N]` placeholder is ONE question**

```json
{
  "type": "open_question",
  "question": "The arm contains [field 1]... powered by [field 5]",
  "field_count": 5,
  "field_answers": {
    "1": "sensors",
    "2": "microprocessor",
    "3": "motors",
    "4": "battery",
    "5": "skin"
  }
}
```
- JSON objects: **1**
- Student questions: **5** (field_count value)

### Rule 3: Closed Questions with Multiple Correct Answers
**Each required correct answer is ONE question**

```json
{
  "type": "matching_classifying",
  "question": "Which TWO are correct?",
  "correct_answer_count": 2,
  "mc_options": [...]
}
```
- JSON objects: **1**
- Student questions: **2** (correct_answer_count value)

### Rule 4: All Other Questions
**Standard questions count as ONE**

```json
{
  "type": "headings",
  "question": "Choose the heading for paragraph B",
  "mc_options": [...]
}
```
- JSON objects: **1**
- Student questions: **1**

---

## Example: Test 16 Breakdown

### JSON Objects vs Student Questions

| JSON Index | Type | Field Count / Correct Count | Student Questions |
|:----------:|:----:|:---------------------------:|:-----------------:|
| 1-6 | headings | 1 each | 6 |
| 7-13 | true_false | 1 each | 7 |
| 14-18 | short_answer | 1 each | 5 |
| 19-22 | matching | 1 each | 4 |
| 23-26 | multiple_choice | 1 each | 4 |
| **27** | **summary_completion** | **6 fields** | **6** |
| 28-32 | matching | 1 each | 5 |
| 33-34 | multiple_choice | 1 each | 2 |
| 35 | short_answer | 1 each | 1 |

**Total:**
- JSON objects: 35
- Student questions: **40** ✓

---

## Calculation Formula

```python
def count_student_questions(question_object):
    q_type = question_object.get('type')
    
    if q_type == 'summary_completion':
        return len(question_object.get('summary_fields', {}))
    
    elif q_type == 'open_question':
        return question_object.get('field_count', 1)
    
    else:  # closed questions
        return question_object.get('correct_answer_count', 1)
```

---

## Why This Matters

### Incorrect Counting Leads To:
- ❌ Wrong test totals (e.g., showing 32 instead of 40 questions)
- ❌ Incorrect scoring calculations
- ❌ Broken test imports
- ❌ Student confusion
- ❌ **Wasted time fixing non-existent problems**

### Correct Counting Ensures:
- ✅ Accurate test totals (always 40 for IELTS Reading)
- ✅ Proper scoring
- ✅ Successful imports
- ✅ Clear student experience

---

## Quality Review Table Implications

When generating quality review tables, you MUST:

1. Count using the formula above, not `len(questions)`
2. Verify each test has exactly 40 student-facing questions
3. Account for multi-field questions properly
4. Check correct_answer_count for closed questions

---

## Test 16 Original Issue

**User reported:** "I see 45 questions when I upload"

**Investigation:**
- JSON objects: 40
- Student questions: **45** ❌ (5 too many)

**Root Cause:**
- 5 short answer questions (Q14-18) had `reading_text_id: null` (not assigned to any passage)
- These should have been part of Passage 2
- Passage 3 had 19 questions instead of ~14

**Fix Applied:**
1. Assigned Q14-18 to Passage 2 (now has 13 questions)
2. Removed 5 excess questions from Passage 3 (now has 14 questions)
3. **Result: 40 questions total** ✓

---

## Memory Storage

This information MUST be stored in memory and referenced before:
- Counting questions in any test
- Generating quality review tables
- Validating test imports
- Analyzing test structure

**Key Takeaway:** Always count `field_count` and `correct_answer_count`, never just count JSON objects!

---

## Related Documentation

- `EXERCISE_JSON_STANDARDS.md` - Multi-field question standards
- `MULTI_FIELD_OPEN_QUESTION_FIX.md` - Historical context on field counting
- `CRITICAL-FEEDBACK-RULES.md` - Feedback structure (related to fields)

---

**Version:** 1.0  
**Last Updated:** 2026-01-17  
**Status:** CRITICAL REFERENCE - Must be reviewed before any question counting
