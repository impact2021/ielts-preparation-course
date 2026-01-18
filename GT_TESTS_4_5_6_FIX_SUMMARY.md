# General Training Reading Tests 4, 5, 6 - Fix Summary

## Task
Fix GT Reading Tests 4, 5, and 6 to each have exactly 40 questions with proper Academic passage questions.

## Initial Status

### Test 4 ✓
- **Status**: Already correct
- **Structure**: 5 reading texts, 39 question objects = 40 questions
- **Distribution**: 7 + 7 + 6 + 6 + 14 = 40 questions
- **Action**: No changes needed

### Test 5 ⚠
- **Status**: Missing 13 questions and 1 reading passage
- **Original**: 4 reading texts, 27 question objects = 27 questions
- **Issue**: Missing "Dressed for Work" passage and Academic Test 05 Passage 3 questions
- **Distribution**: 7 + 3 + 4 + 13 (misplaced) = 27 questions

### Test 6 ⚠
- **Status**: Missing 13 questions
- **Original**: 4 reading texts, 27 question objects = 27 questions  
- **Issue**: Had wrong questions for Academic passage, needed Academic Test 06 Passage 3 questions
- **Distribution**: 7 + 7 + 6 + 7 = 27 questions

## Changes Made

### Test 5 - Added Missing Content
1. **Inserted** "Dressed for Work" passage as Reading Text 3
   - 4 questions (Q11-14) already existed, just needed the passage content
2. **Renumbered** existing texts:
   - Text 3 (Salary Negotiation) → Text 4
   - Text 4 (Pluto passage) → Text 5
3. **Added** 13 Pluto questions (Q28-40) from Academic Test 05 Passage 3
   - Only first 13 of 14 questions added (Q28-40 = 13 questions)
   - Questions assigned to text_id=4 (Pluto passage)

### Test 6 - Added Academic Questions
1. **Added** 13 Ford questions from Academic Test 06 Passage 3
   - 11 question objects with 13 total points
   - Questions assigned to text_id=3 (Ford passage)
   - Academic passage was already in place at Reading Text 5

## Final Results

### Test 4 ✓
- **Texts**: 5
- **Questions**: 39 objects = 40 questions
- **Distribution**: Text 0: 7, Text 1: 7, Text 2: 6, Text 3: 6, Text 4: 14

### Test 5 ✓
- **Texts**: 5 (added "Dressed for Work")
- **Questions**: 40 objects = 40 questions
- **Distribution**: Text 0: 7, Text 1: 3, Text 2: 4, Text 3: 13, Text 4: 13

### Test 6 ✓
- **Texts**: 4
- **Questions**: 38 objects = 40 questions
- **Distribution**: Text 0: 7, Text 1: 7, Text 2: 6, Text 3: 20

## Verification

All three tests now have exactly 40 questions as required for IELTS General Training Reading tests.

```
GT Test 4: 40 questions ✓
GT Test 5: 40 questions ✓
GT Test 6: 40 questions ✓
```

## Files Modified
- `main/General Training Reading Test JSONs/General Training Reading Test 5.json`
- `main/General Training Reading Test JSONs/General Training Reading Test 6.json`
