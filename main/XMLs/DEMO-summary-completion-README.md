# DEMO Summary Completion XML - Buying a Puppy (Listening)

## Overview
This demo XML file demonstrates the correct format for summary completion questions with field-based answers for a listening exercise.

## File Location
`main/XMLs/DEMO-summary-completion-microchipping.xml`

## Contents

### Question Format
- **Type**: Summary Completion
- **Questions**: 1-10 (10 questions)
- **Instruction**: Answer the following questions USING NO MORE THAN THREE WORDS

### Sample Questions
1. Who is the customer buying the puppy for?
2. When will the puppies be available?
3. When is the breeder not available to meet?
4. What is outside the kennels?
5. What shop is on the left side of Barnett Drive?
... (and 5 more)

### Fields and Answers
Each field includes:
- **Answer** (correct response with multiple acceptable variations)
- **Correct feedback** (shown when answer is correct)
- **Incorrect feedback** (shown when answer is wrong)
- **No-answer feedback** (shown when field is left blank)

Sample answers:
- Q1: "his daughter" (or "daughter", "HIS DAUGHTER", "DAUGHTER")
- Q2: "in one week" (or "in 1 week", "one week", "1 week", "a week", "in a week")
- Q3: "on friday afternoon" (or "friday afternoon", uppercase variants)

### Audio Content
This is a listening exercise. The XML does NOT include reading texts (`_ielts_cm_reading_texts` is empty).

## Validation Status (Fixed December 30, 2025)
✅ **FIXED**: PHP serialization lengths corrected using `fix-serialization-lengths.py`
✅ Passes all XML validation checks
✅ PHP serialization is correct
✅ All required WordPress meta fields present
✅ No CDATA spacing issues
✅ Ready for import

### Previous Issue
The original XML file had corrupted PHP serialization string lengths where declared lengths didn't match actual string byte counts (e.g., `s:92:` declared but 149 bytes actual). This caused `unserialize()` to fail, returning `false`, which triggered the "No questions found in the XML file" error.

### Fix Applied
Used `python3 TEMPLATES/fix-serialization-lengths.py` to recalculate all string lengths in the serialized data. The file now imports successfully without errors.

## Import Instructions
1. Log into WordPress admin
2. Go to IELTS Quiz import page
3. Select this XML file
4. Import
5. The quiz should import without "No questions" error

## Features Demonstrated
- Proper WordPress XML export format
- Summary completion question type for listening exercises
- Multiple answer fields with individual feedback per question
- Proper PHP serialization format (after fix)
- Multiple acceptable answer variations using pipe separator
- Listening exercise layout (`listening_practice`)
- Timer functionality (5 minutes)
- Percentage-based scoring

## Key Points
- This is a **listening exercise** (not reading), so no reading texts are included
- Each question uses the `summary_completion` type with a single field
- Answers can have multiple acceptable variations separated by `|` (e.g., `in one week|in 1 week|one week|1 week|a week|in a week`)
- All feedback follows the ANSWER-FEEDBACK-GUIDELINES.md format
- Exercise uses a 5-minute timer
- Layout type is `listening_practice`
- Starting question number is 1 (default)
