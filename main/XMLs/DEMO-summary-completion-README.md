# DEMO Summary Completion XML - Microchipping Pets

## Overview
This demo XML file demonstrates the correct format for summary completion questions with field-based answers.

## File Location
`main/XMLs/DEMO-summary-completion-microchipping.xml`

## Contents

### Question Format
- **Type**: Summary Completion
- **Questions**: 7-10 (4 fields)
- **Instruction**: Complete the summary below using ONE WORD ONLY

### Question Text
```
Micro-chipping pets is a secure way of keeping them safe. Collars and tags are 
convenient but can be removed or 7. [field 1]. Micro chips should be checked 
occasionally to make sure they are still working properly. The chips are about 
the same size as a grain of 8. [field 2] and dogs should feel discomfort for no 
more than a few 9. [field 3]. Infection risk can be minimised by owners making 
sure the area does not become 10. [field 4]
```

### Fields and Answers
Each field includes:
- **Answer** (correct response)
- **Correct feedback** (shown when answer is correct)
- **Incorrect feedback** (shown when answer is wrong)
- **No-answer feedback** (shown when field is left blank)

| Field | Answer | Alternative |
|-------|--------|-------------|
| 1 | LOST | - |
| 2 | RICE | - |
| 3 | SECONDS | SECOND |
| 4 | INFECTED | INFLAMED |

### Reading Text
The XML includes a complete reading passage about pet microchipping that contains all the information needed to answer the questions.

## Validation Status
✅ Passes all XML validation checks
✅ PHP serialization is correct
✅ All required WordPress meta fields present
✅ No CDATA spacing issues
✅ Ready for import

## Import Instructions
1. Log into WordPress admin
2. Go to IELTS Quiz import page
3. Select this XML file
4. Import
5. The quiz should import without "No questions" error

## Features Demonstrated
- Proper WordPress XML export format
- Summary completion question type
- Multiple answer fields with individual feedback
- Proper PHP serialization format
- Reading text integration
- Question numbering starting at 7 (as requested)

## Key Points
- Each field (`[field 1]`, `[field 2]`, etc.) becomes an input box in the quiz
- The numbers in the question text (7, 8, 9, 10) are for display only
- Field numbers in the data structure start at 1, not 7
- Answers can have multiple acceptable variations separated by `|` (e.g., `SECONDS|SECOND`)
- All feedback follows the ANSWER-FEEDBACK-GUIDELINES.md format
