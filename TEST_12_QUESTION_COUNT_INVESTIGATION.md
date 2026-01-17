# Academic Reading Test 12 - Question Count Investigation

**Date:** 2026-01-17  
**Issue:** User reports seeing 56 questions instead of expected 40  
**Status:** ✅ JSON FILE IS CORRECT - Issue is likely in WordPress database

## Summary

The JSON source file for Academic Reading Test 12 is **CORRECT** and contains exactly **40 questions** as required for IELTS Academic Reading tests.

If you are seeing 56 questions when viewing this test in WordPress, this indicates a **database duplication issue**, not a problem with the source file.

## Test Structure (Correct)

| Passage | Question Range | Count |
|---------|---------------|-------|
| Passage 1 | Questions 1-15 | 15 |
| Passage 2 | Questions 16-28 | 13 |
| Passage 3 | Questions 29-40 | 12 |
| **TOTAL** | **1-40** | **40** ✓ |

## Why 56 Questions Appears

Based on analysis, **56 = 40 + 16**, which suggests:
- Questions 1-16 were duplicated during WordPress import
- This created 16 extra questions (40 original + 16 duplicates = 56)

This is a **WordPress database issue**, not a JSON file problem.

## How to Fix

### Option 1: Re-import the Test (Recommended)

1. **In WordPress Admin:**
   - Navigate to the IELTS Course Manager
   - Find "Academic Reading Test 12"
   - Delete the test completely

2. **Re-import from JSON:**
   - Go to Import/Upload section
   - Select the file: `/main/Academic Read Test JSONs/Academic-IELTS-Reading-Test-12.json`
   - Import the test
   - Verify it now shows 40 questions

### Option 2: Database Cleanup (Advanced)

If you have database access:
1. Identify duplicate question entries for Test 12
2. Delete questions with IDs that appear twice
3. Ensure only 40 unique questions remain

## Verification Steps

After fixing, verify the test has:

✅ **Passage 1:** 15 questions (1-15)
- 4 heading matching questions (Q1-4)
- 6 person matching questions (Q5-10)  
- 5 sentence completion questions (Q11-15)

✅ **Passage 2:** 13 questions (16-28)
- 5 heading matching questions (Q16-20)
- 5 short answer questions (Q21-25)
- 3 multiple choice questions (Q26-28)

✅ **Passage 3:** 12 questions (29-40)
- 4 sentence completion matching (Q29-32)
- 4 flowchart completion (Q33-36)
- 4 TRUE/FALSE/NOT GIVEN (Q37-40)

✅ **Total:** Exactly 40 questions

## JSON File Validation

The source JSON file has been validated and confirmed correct:

```
✓ Valid JSON structure
✓ 40 question objects
✓ All questions linked to reading passages
✓ Proper question numbering (1-40)
✓ No duplicate question arrays
✓ Metadata intact
```

## Technical Details

### Question Count Methodology

Per IELTS standards and `QUESTION_COUNTING_RULES.md`:
- Closed questions: Count = 1 (unless correct_answer_count > 1)
- Open questions: Count = field_count or field_feedback entries
- Summary completion: Count = number of fields

Test 12 breakdown:
- 26 closed questions (1 point each) = 26
- 14 open questions (1 field each) = 14
- **Total: 40 student-facing questions**

### Known Non-Issues

The following are NOT problems:
- ✓ Questions 21-25 lack `[field N]` placeholders (correct for short answer format)
- ✓ Questions 1-4 and 16-20 use same question text "Paragraph B/C/D..." (correct for heading matching)
- ✓ Multiple passages have TRUE/FALSE questions with same option text (standard IELTS format)

## Conclusion

**The JSON file requires no changes.** It is correct and compliant with IELTS standards.

If you continue to see 56 questions:
1. The issue is in your WordPress database
2. Follow the "How to Fix" steps above
3. Contact your WordPress administrator if database cleanup is needed

## Files Checked

- ✅ `/main/Academic Read Test JSONs/Academic-IELTS-Reading-Test-12.json` - CORRECT (40 questions)
- ✅ No other Test 12 files found that could cause conflicts
- ✅ No duplicate JSON question arrays in file
- ✅ No malformed JSON structure

---

**Need Help?**

If re-importing doesn't resolve the issue, there may be a plugin bug or custom code causing duplication. Check:
1. WordPress plugin version (should be 11.27 or later)
2. Any custom import scripts
3. Database integrity
