# Academic IELTS Reading Test 14 - Conversion Guide

## Overview
This document provides a complete guide for converting `Academic-IELTS-Reading-Test-14.txt` to the proper JSON format.

## Current Status

### ✅ Completed
- JSON structure created and validated
- Questions 1-10 (Reading Passage 1) fully formatted with complete feedback
- Template files created showing correct pattern
- Understanding of marker placement requirements
- Reference to guidelines (READING_PASSAGE_MARKER_GUIDE.md, ANSWER-FEEDBACK-GUIDELINES.md)

### ⏳ Remaining Work
- Questions 11-22 (Reading Passage 2 - Magazine development)
- Questions 23-40 (Reading Passage 3 - Dawn of culture)
- All 3 reading passages with answer markers
- Final validation and testing

## File Structure

```
Academic-IELTS-Reading-Test-14.txt     - Source file (322 lines)
Academic-IELTS-Reading-Test-14.json    - Target file (to be ~150KB)
Academic-IELTS-Reading-Test-14-template.json  - Shows pattern for Q1-10
```

## Question Breakdown

### Reading Passage 1: "A running controversy" (Drug use in sport)
- **Questions 1-4**: Diagram label completion ✅ COMPLETE
- **Questions 5-8**: Short answer ✅ COMPLETE
- **Questions 9-10**: Summary completion ✅ COMPLETE

### Reading Passage 2: "The development of the magazine"
- **Questions 11-14**: Multiple choice (A-D) ⏳ TODO
- **Questions 15-18**: TRUE/FALSE/NOT GIVEN ⏳ TODO
- **Questions 19-22**: Short answer (NO MORE THAN TWO WORDS) ⏳ TODO

### Reading Passage 3: "The dawn of culture"
- **Questions 23-29**: YES/NO/NOT GIVEN ⏳ TODO
- **Questions 30-34**: Summary completion with word bank ⏳ TODO
- **Questions 35-40**: Short answer (NO MORE THAN THREE WORDS) ⏳ TODO

## Template Pattern

Each question object needs:

```json
{
    "type": "open_question" or "closed_question",
    "ielts_question_type": "Type from txt file",
    "instructions": "Full instructions from txt file",
    "question": "Question text",
    "points": 1 or more,
    "correct_feedback": "Positive message + explanation",
    "incorrect_feedback": "Gentle correction + where to look",
    "no_answer_feedback": "The correct answer is X. Located in paragraph Y.",
    "reading_text_id": 0, 1, or 2,
    "audio_section_id": null,
    "audio_start_time": null,
    "audio_end_time": null,
    ...additional fields based on question type
}
```

### For Open Questions (fill-in-the-blank):
```json
"field_count": 4,
"field_labels": ["1. Label...", "2. Label..."],
"field_answers": ["answer1|variant1", "answer2|variant2"]
```

### For Closed Questions (multiple choice):
```json
"ielts_question_category": "multiple_choice_r",
"mc_options": [
    {
        "text": "Option A text",
        "is_correct": true/false,
        "feedback": "Explanation"
    }
],
"options": "Option A\nOption B\nOption C\nOption D",
"correct_answer_count": 1,
"show_option_letters": false
```

## Reading Passage Markers

Each passage needs answer markers like:

```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">exact text containing the answer</span>
```

**Important:**
- Use `passage-q#` IDs (NOT `reading-text-q#` or `transcript-q#`)
- Use `reading-answer-marker` class
- Marker should surround the exact text that answers the question
- Place marker at the location in the passage where the answer appears

## Step-by-Step Completion Process

### 1. Add Questions 11-22 (Reading Passage 2)

From txt file lines 71-113, extract:

**Q11-14**: Multiple choice
- Format: closed_question with mc_options
- 4 options each (A-D)
- Feedback explaining why each is correct/incorrect

**Q15-18**: TRUE/FALSE/NOT GIVEN
- Format: closed_question
- 3 options (TRUE, FALSE, NOT GIVEN)
- Feedback explaining the passage reference

**Q19-22**: Short answer
- Format: open_question
- field_count: 4
- Maximum TWO WORDS
- Answers from txt file

### 2. Add Questions 23-40 (Reading Passage 3)

From txt file lines 218-244, extract:

**Q23-29**: YES/NO/NOT GIVEN (7 questions)
- Format: closed_question
- 3 options
- Author's opinion focus

**Q30-34**: Summary completion with word bank (5 questions)
- Format: closed_question
- Options from provided word bank
- Select from A-I

**Q35-40**: Short answer (6 questions)
- Format: open_question
- field_count: 6
- Maximum THREE WORDS

### 3. Add Reading Passages

**Passage 1**: Lines 5-23 from txt file
- Title: "A running controversy"
- Add markers for Q1-Q10 at appropriate locations
- Paragraph 3: Q1-Q4 (drug types)
- Paragraph 2: Q5 (short careers)
- Paragraph 5: Q6 (drug testing), Q8 (evade detection)
- Paragraph 6: Q7 (natural advantages), Q9 (inaccuracy)
- Paragraph 7: Q10 (allegations)

**Passage 2**: Lines 68-133 from txt file
- Title: "The development of the magazine"
- Add markers for Q11-Q22
- Organize by paragraphs discussing magazine history

**Passage 3**: Lines 218-261 from txt file
- Title: "The dawn of culture"
- Add markers for Q23-Q40
- Comprehensive passage about ideology and culture

## Quality Checklist

Before finalizing:
- [ ] All 40 questions have complete feedback (3 types each)
- [ ] All answers match txt file exactly (including variants)
- [ ] All reading passages have correct markers
- [ ] Markers link to correct question numbers
- [ ] JSON is valid (test with JSON validator)
- [ ] File size is approximately 150KB
- [ ] No spelling errors in feedback
- [ ] All IDs use `passage-q#` format (not old formats)

## Common Pitfalls to Avoid

1. ❌ Using `reading-text-q#` instead of `passage-q#`
2. ❌ Forgetting to include answer variants (e.g., "short career|short careers")
3. ❌ Generic feedback without specific answers
4. ❌ Missing the `no_answer_feedback` field
5. ❌ Incorrect `reading_text_id` (should be 0, 1, or 2)
6. ❌ Not escaping special characters in JSON strings
7. ❌ Markers placed in wrong location in passage

## Testing

After completion:
1. Validate JSON syntax
2. Load in the IELTS system
3. Test each question type
4. Click "Show in the reading passage" for each question
5. Verify markers highlight correctly
6. Check feedback displays properly

## Estimated Time

- Questions 11-22: ~2 hours
- Questions 23-40: ~3 hours
- Reading passages with markers: ~2 hours
- Testing and refinement: ~1 hour
- **Total: ~8 hours** (for careful, quality work)

## Reference Files

- `Academic-IELTS-Reading-Test-01.json` - Complete example
- `Academic-IELTS-Reading-Test-14-template.json` - Pattern for this test
- `READING_PASSAGE_MARKER_GUIDE.md` - Marker guidelines
- `ANSWER-FEEDBACK-GUIDELINES.md` - Feedback requirements

## Contact

For questions about this conversion, refer to the template files and guidelines listed above.
