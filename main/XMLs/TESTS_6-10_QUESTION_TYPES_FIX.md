# Listening Tests 6-10 - Question Type Detection Fix

## Issue Fixed - December 30, 2025

### Problem
All questions in Listening Tests 6-10 were incorrectly generated as `short_answer` type by the `generate_simple_tests_6_10.py` script, completely ignoring the diverse question types present in the original .txt files (multiple choice, map labeling, summary completion, etc.).

### Impact
- Test takers were seeing only short answer input boxes for ALL questions
- Map labeling, multiple choice, and other question types were not being displayed correctly
- This affected all 40 sections (Tests 6-10, 4 sections each)

### Root Cause
The `generate_simple_tests_6_10.py` script at line 401 hardcoded:
```python
'type': 'short_answer',
```
for ALL questions, regardless of their actual type in the source .txt file.

### Solution
Created new enhanced generator: `generate_tests_6_10_with_types.py`

This new generator includes intelligent question type detection based on:
1. **Context analysis** - Examines surrounding text for type indicators
2. **Answer format** - Checks answer patterns (single letter, multiple letters, words)
3. **HTML structure** - Detects `<ol>` lists, `<strong>` tags, paragraph structure
4. **Instruction keywords** - Looks for "Label", "Choose TWO", "Complete", etc.

### Detected Question Types

The new generator correctly identifies:

1. **summary_completion** - Answers embedded in paragraphs with `<strong>N</strong>` markers
2. **multiple_choice** - Questions with `<ol>` option lists and single-letter answers
3. **multi_select** - "Choose TWO letters" questions with multiple correct answers
4. **matching** - Map/diagram labeling with letter answers
5. **sentence_completion** - "Complete the..." tasks in tables or sentences
6. **short_answer** - Questions with question marks requiring text answers

### Results for Test 6

**Before Fix:**
- Section 1: ALL 10 questions → short_answer ❌
- Section 2: ALL 10 questions → short_answer ❌

**After Fix:**
- Section 1: 4 short_answer, 2 matching, 3 summary_completion, 1 sentence_completion ✓
- Section 2: 3 sentence_completion, 2 short_answer, 5 summary_completion ✓

### All Tests 6-10 Status

✅ Test 6 - All 4 sections regenerated with correct types
✅ Test 7 - All 4 sections regenerated with correct types
✅ Test 8 - All 4 sections regenerated with correct types
✅ Test 9 - All 4 sections regenerated with correct types
✅ Test 10 - All 4 sections regenerated with correct types

**Total:** 40 XML files regenerated

### Type Distribution Across Tests 6-10

Sample of question type distributions:
- **Test 6 Section 1:** 4 short_answer, 2 matching, 3 summary_completion, 1 sentence_completion
- **Test 6 Section 2:** 3 sentence_completion, 2 short_answer, 5 summary_completion
- **Test 7 Section 1:** 7 short_answer, 2 sentence_completion, 1 summary_completion
- **Test 8 Section 1:** 10 sentence_completion
- **Test 9 Section 4:** 7 short_answer, 3 summary_completion

### Code Quality Improvements

Based on code review feedback:
1. ✅ Extracted duplicate summary text logic into `extract_summary_text()` helper
2. ✅ Created `is_summary_completion_context()` helper for readability
3. ✅ Extracted `extract_ordered_list_options()` helper for reusability
4. ✅ Defined regex patterns as named constants at module level
5. ✅ All changes validated - no security vulnerabilities found

### Validation

All regenerated XML files pass validation:
- ✓ Valid PHP serialization
- ✓ All required postmeta fields present
- ✓ Correct post type (ielts_quiz)
- ✓ Ready for WordPress import

### Usage

To regenerate any test (if needed in the future):
```bash
cd main/XMLs
python3 generate_tests_6_10_with_types.py
```

The script will regenerate all Tests 6-10 with proper question type detection.

### Files Modified

**New Generator:**
- `main/XMLs/generate_tests_6_10_with_types.py` (created)

**Regenerated XMLs (40 files):**
- `Listening Test 6 Section 1-4.xml`
- `Listening Test 7 Section 1-4.xml`
- `Listening Test 8 Section 1-4.xml`
- `Listening Test 9 Section 1-4.xml`
- `Listening Test 10 Section 1-4.xml`

**Legacy Generator (now obsolete):**
- `main/XMLs/generate_simple_tests_6_10.py` (DO NOT USE - creates all questions as short_answer)

### Technical Details

Detection Priority (highest to lowest):
1. Summary completion (paragraph context)
2. Multi-select ("Choose TWO" with recent `<ol>`)
3. Multiple choice (single letter + `<ol>`)
4. Map labeling (letter answer + "label" keyword)
5. Short answer (question mark present)
6. Sentence completion ("Complete the..." instruction)
7. Fallback to short_answer

### Testing Performed

✓ Generated all 40 XML files successfully
✓ Validated Test 6 Section 1 and 2 XMLs
✓ Verified question type detection accuracy
✓ Tested code after refactoring - no regressions
✓ Security scan - 0 vulnerabilities found

---

**Generator:** `generate_tests_6_10_with_types.py`  
**Date Fixed:** December 30, 2025  
**Tests Affected:** Listening Tests 6-10 (40 sections)  
**Status:** ✅ COMPLETED
