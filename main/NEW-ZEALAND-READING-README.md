# The First Inhabitants of New Zealand - Reading Test

## Overview
This JSON file contains an IELTS Academic Reading test with **open questions** (short answer questions) that require students to answer using NO MORE THAN THREE WORDS AND/OR A NUMBER from the passage.

## File Location
`/main/New-Zealand-First-Inhabitants-Reading.json`

## Key Features

### 1. Open Questions with Field-Level Feedback
Each question includes:
- **Multiple acceptable answers** separated by pipe (`|`) character
- **Field-level feedback** for each answer field with three types:
  - `correct`: Shown when the student answers correctly
  - `incorrect`: Shown when the student provides a wrong answer
  - `no_answer`: Shown when the student leaves the field blank

### 2. Answer Variants
Each question accepts multiple valid answer formats:

**Question 1**: Wing span
- Acceptable: `3 metres`, `three metres`, `3 meters`, `three meters`

**Question 2**: How prey died
- Acceptable: `blood loss`, `extensive blood loss`, `loss of blood`

**Question 3**: Spirit contact
- Acceptable: `spirits`, `spirit world`, `the spirit world`

**Question 4**: Human problem from extinction
- Acceptable: `extreme famine`, `famine`, `cases of famine`

**Question 5**: Natural phenomenon
- Acceptable: `volcanic eruption`, `a volcanic eruption`, `eruption`

### 3. Reading Text with Answer Markers
The reading passage includes:
- **Paragraph labels** (A, B, C, D) in `<strong>` tags
- **Answer location markers** using `<span id="q#" data-question="#"></span>`
- **Answer highlighting** using `<span class="reading-answer-marker">text</span>`

This follows the repository's Exercise JSON Standards (v12.6+) for answer highlighting.

### 4. Comprehensive Feedback
Each feedback message includes:
- The correct answer(s)
- Reference to the specific paragraph where the answer is found
- Direct quote from the passage
- Additional context (e.g., word limit notes for Question 4)

## JSON Structure

```json
{
  "title": "Academic IELTS Reading Test - The First Inhabitants of New Zealand",
  "questions": [
    {
      "type": "open_question",
      "ielts_question_type": "Short Answer Questions",
      "field_count": 1,
      "field_answers": {
        "1": "answer1|answer2|answer3"
      },
      "field_feedback": {
        "1": {
          "correct": "Positive feedback with paragraph reference",
          "incorrect": "Shows correct answer with explanation",
          "no_answer": "Shows correct answer with encouragement"
        }
      }
    }
  ],
  "reading_texts": [
    {
      "title": "The First Inhabitants of New Zealand",
      "text": "HTML formatted text with answer markers"
    }
  ],
  "settings": {
    "layout_type": "one_column_reading",
    "scoring_type": "ielts_reading_band",
    "timer_minutes": 20
  }
}
```

## Testing Requirements

The JSON file adheres to:
1. **ANSWER-FEEDBACK-GUIDELINES.md** - All questions show correct answers in feedback
2. **EXERCISE_JSON_STANDARDS.md** - Uses proper answer markers and field structure
3. **CRITICAL-FEEDBACK-RULES.md** - Never shows generic messages without the actual answer

## Validation

The JSON file has been validated for:
- ✅ Valid JSON syntax
- ✅ All questions have complete field feedback (correct, incorrect, no_answer)
- ✅ All questions have multiple acceptable answer variants
- ✅ Reading text has answer markers for all questions (q1-q5)
- ✅ Reading text uses `reading-answer-marker` class
- ✅ Field count matches field_answers and field_feedback counts

## Usage Notes

1. **Word Limit**: The instructions specify "NO MORE THAN THREE WORDS AND/OR A NUMBER"
   - Question 4 notes that "extreme cases of famine" exceeds this limit
   - Acceptable answers include shorter variants like "famine" or "extreme famine"

2. **Case Sensitivity**: Answers are typically case-insensitive in IELTS scoring

3. **Spelling Variants**: Both British ("metres") and American ("meters") spellings are accepted

## Content Attribution

This exercise is based on an IELTS-style reading passage about:
- The geological history of New Zealand (Aotearoa)
- The Giant Moa and Haast's Eagle
- Maori cultural practices with kites
- Extinction of native bird species
- Natural phenomena affecting bird populations

The reading passage spans approximately 400 words across four paragraphs (A-D).
