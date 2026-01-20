# Quality Dashboard Fix - Question Counting Correction

**Date:** 2026-01-17  
**Issue:** Quality dashboard incorrectly counted JSON objects instead of actual student-facing questions

---

## The Problem

The quality dashboard was showing **incorrect question counts** for many reading tests because it was counting the number of JSON objects in the `questions` array, rather than the actual number of student-facing questions.

### Example: Test 01

**Old Dashboard:**
- Showed: **39 questions** ❌
- Status: 🔴 INCOMPLETE

**Actual Count:**
- JSON objects: 39
- **Student questions: 40** ✓
- Status: ✓ COMPLETE

**Why the difference?**
Questions 39-40 is a single JSON object with `"correct_answer_count": 2`, meaning it asks students to choose **TWO answers**, counting as **2 questions**, not 1.

```json
{
  "type": "closed_question",
  "instructions": "Questions 39-40\r\n\r\nChoose TWO letters, A-E.",
  "question": "Which TWO of the following are mentioned...",
  "correct_answer_count": 2,
  ...
}
```

---

## The Solution

Created `generate_quality_dashboard.py` script that **correctly counts** student-facing questions using the rules from `QUESTION_COUNTING_RULES.md`:

### Counting Rules Applied

1. **Summary Completion Questions**
   - Count: `len(summary_fields)`
   - Each `[field N]` placeholder = 1 question

2. **Open Questions with Multiple Fields**
   - Count: `field_count` value
   - Each `[field N]` placeholder = 1 question

3. **Closed Questions with Multiple Correct Answers**
   - Count: `correct_answer_count` value
   - Each required answer = 1 question

4. **All Other Questions**
   - Count: 1

### Implementation

```python
def count_student_questions(question):
    """Count actual student-facing questions according to IELTS standards"""
    q_type = question.get('type', '')
    
    if q_type == 'summary_completion':
        return len(question.get('summary_fields', {}))
    elif q_type == 'open_question':
        return question.get('field_count', 1)
    else:
        return question.get('correct_answer_count', 1)
```

---

## Results

### Before Fix
- **Total Questions:** 773 (incorrect)
- **Complete Tests (40 Qs):** 9/21
- **Incomplete Tests:** 12/21

### After Fix
- **Total Questions:** 839 (correct)
- **Complete Tests (40 Qs):** 20/21 ✓
- **Incomplete Tests:** 1/21 (only Test 03 with 39 questions)

### Tests Fixed

| Test | Old Count | New Count | Status Change |
|:----:|:---------:|:---------:|:-------------:|
| 01   | 39        | **40**    | 🔴 → ✓ COMPLETE |
| 04   | 39        | **40**    | 🔴 → ✓ COMPLETE |
| 05   | 38        | **40**    | 🔴 → ✓ COMPLETE |
| 06   | 35        | **40**    | 🔴 → ✓ COMPLETE |
| 08   | 35        | **40**    | 🔴 → ✓ COMPLETE |
| 09   | 32        | **40**    | 🔴 → ✓ COMPLETE |
| 10   | 32        | **40**    | 🔴 → ✓ COMPLETE |
| 11   | 33        | **40**    | 🔴 → ✓ COMPLETE |
| 13   | 33        | **40**    | 🔴 → ✓ COMPLETE |
| 14   | 25        | **40**    | 🔴 → ✓ COMPLETE |
| 16   | 35        | **40**    | Fixed previously |
| 20   | 34        | **40**    | 🔴 → ✓ COMPLETE |
| 21   | 34        | **40**    | 🔴 → ✓ COMPLETE |

### Only Remaining Issue

**Test 03:** Actually has **39 questions** (needs 1 more question added)
- 38 JSON objects
- 1 open_question with field_count: 2
- Total: 39 student-facing questions

---

## Why This Kept Happening

### Root Causes

1. **Incorrect Mental Model**
   - Assumed: 1 JSON object = 1 question
   - Reality: 1 JSON object can represent multiple questions

2. **Missing the Critical Detail**
   - `correct_answer_count` field was overlooked
   - `field_count` in open questions was ignored
   - `summary_fields` object size wasn't counted

3. **No Automated Validation**
   - Quality dashboard was manually maintained HTML
   - No script to enforce correct counting logic
   - Easy to make counting mistakes

4. **Insufficient Documentation Review**
   - `QUESTION_COUNTING_RULES.md` exists with clear rules
   - Was not consistently referenced before counting
   - Rules were not applied to dashboard generation

---

## Prevention Measures

### 1. Automated Generation
- ✅ Created `generate_quality_dashboard.py` script
- ✅ Script implements correct counting logic
- ✅ Can be re-run anytime to update dashboard

### 2. Clear Documentation
- ✅ `QUESTION_COUNTING_RULES.md` provides detailed rules
- ✅ This document explains the fix and prevention

### 3. Validation Process
- Always use the Python script to count questions
- Never manually count JSON objects
- Review `QUESTION_COUNTING_RULES.md` before any quality analysis

### 4. How to Update Dashboard

```bash
# Run the script to regenerate the dashboard
python3 generate_quality_dashboard.py

# Output will be written to:
# main/Practice-Tests/quality-dashboard.html
```

---

## Tests with Wrong Question Count

After applying the correct counting logic, **only 1 test** out of 21 has the wrong number of questions:

**Test 03:** 39 questions (needs 1 more question)

All other 20 tests have exactly 40 questions as required for IELTS Reading tests.

---

## Key Takeaways

1. **Never count JSON objects** - Count student-facing questions
2. **Check `correct_answer_count`** - Questions requiring multiple answers count as multiple questions
3. **Check `field_count`** - Multi-field open questions count as multiple questions
4. **Count `summary_fields`** - Each field in summary completion is a separate question
5. **Use the script** - Always regenerate dashboard with `generate_quality_dashboard.py`

---

## Related Documentation

- `QUESTION_COUNTING_RULES.md` - Detailed rules and examples
- `EXERCISE_JSON_STANDARDS.md` - JSON structure standards
- `generate_quality_dashboard.py` - Dashboard generation script

---

**Version:** 1.0  
**Last Updated:** 2026-01-17  
**Status:** ISSUE RESOLVED
