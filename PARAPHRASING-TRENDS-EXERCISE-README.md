# Paraphrasing Trends Exercise - README

## Overview

This exercise teaches IELTS Writing Task 1 students how to paraphrase trend descriptions using three different sentence structures, directly addressing the pedagogical challenge of turning the example into an interactive learning experience.

## File Location

`main/Exercises/Paraphrasing-trends-in-different-ways.json`

## Exercise Details

- **Title**: Paraphrasing trends in different ways
- **Total Questions**: 11
- **Time Limit**: 15 minutes
- **Pass Percentage**: 70%
- **Layout**: Two-column exercise

## Learning Objectives

Students will learn to express the same trend information using three grammatical structures:

1. **Noun phrase as subject**: "There was a dramatic increase in sales from January to March."
2. **Data as subject**: "Sales increased dramatically from January to March."
3. **Time phrase first**: "From January to March, sales increased dramatically."

## Key Grammar Points Covered

### Adjectives vs. Adverbs
- When using a **noun** (increase, decrease, rise, fall) → use an **adjective** (dramatic, significant, slight)
- When using a **verb** (increased, decreased, rose, fell) → use an **adverb** (dramatically, significantly, slightly)

### Examples from the Exercise
- "There was a **sharp** rise" (adjective + noun)
- "Profits rose **sharply**" (verb + adverb)
- "A **steady** increase" (adjective + noun)
- "Increased **steadily**" (verb + adverb)

## Question Types Used

### Closed Questions (6 questions)
Multiple-choice questions that help students:
- Recognize equivalent sentence structures
- Identify parts of speech (adjective vs. adverb)
- Choose grammatically correct options
- Understand sentence structure variations

### Open Questions (5 questions)
Fill-in-the-blank questions that require students to:
- Transform adjectives to adverbs and vice versa
- Rewrite sentences with different structures
- Apply grammar rules in practice
- Complete sentences with correct word forms

## Question Breakdown

1. **Q1**: Recognition - Are all three sentence structures correct?
2. **Q2**: Grammar - Identifying adjectives in noun-based structures
3. **Q3**: Grammar - Identifying adverbs in verb-based structures
4. **Q4**: Structure - Recognizing time-phrase-first structure
5. **Q5**: Practice - Converting sharp → sharply (verb + adverb)
6. **Q6**: Practice - Converting sharp → sharp (noun + adjective)
7. **Q7**: Practice - Rewriting with time-phrase-first structure
8. **Q8**: Practice - Converting noun form to verb form
9. **Q9**: Practice - Converting verb form to noun form
10. **Q10**: Application - Selecting the correct verb form
11. **Q11**: Application - Identifying equivalent sentence structures

## Pedagogical Approach

The exercise uses a **gradual progression** approach:

1. **Recognition** (Q1-4): Students first recognize that multiple structures express the same meaning
2. **Understanding** (Q2-3): Students identify the grammatical elements in each structure
3. **Practice** (Q5-9): Students apply the rules by transforming sentences and words
4. **Application** (Q10-11): Students demonstrate mastery by creating and identifying correct structures

## Feedback Design

All questions include three types of feedback:
- **Correct feedback**: Reinforces the correct answer and explains why it's right
- **Incorrect feedback**: Explains what's wrong and guides students to the correct answer
- **No answer feedback**: Shows the correct answer with a brief explanation

## How This Addresses the Original Problem

The original problem statement asked: *"Any good ideas on how - with the question types we have - this can be turned into an exercise?"*

The example showed three ways to describe the same trend:
1. "There was a dramatic increase in sales from January to March."
2. "Sales increased dramatically from January to March."
3. "From January to March, sales increased dramatically."

**This exercise solves the problem by:**
- Using **closed questions** to help students recognize and understand the three structures
- Using **open questions** to provide practice transforming between the structures
- Teaching the underlying grammar rules (adjectives vs. adverbs)
- Providing interactive practice with immediate feedback
- Creating a complete learning experience from recognition to application

## Usage in IELTS Preparation Course

This exercise is ideal for:
- **Writing Task 1 preparation modules**
- **Grammar and paraphrasing skills development**
- **Practice before writing full Task 1 responses**
- **Review exercises after learning about trend vocabulary**
- **Standalone grammar practice**

## Technical Details

### JSON Structure
- Follows `EXERCISE_JSON_STANDARDS.md`
- Uses standard `closed_question` and `open_question` types
- All open questions have proper `field_count` and `field_answers` matching
- All questions include complete feedback (correct, incorrect, no_answer)
- Compatible with IELTS Course Manager WordPress plugin v12.2+

### Settings
```json
{
  "pass_percentage": "70",
  "layout_type": "two_column_reading",
  "scoring_type": "percentage",
  "timer_minutes": "15",
  "starting_question_number": "1"
}
```
**Note:** As of v12.6, use `two_column_reading` or `two_column_listening`. The old `two_column_exercise` template has been deprecated.

## Future Enhancements

Potential additions could include:
- More examples with different trends (decrease, fall, drop, fluctuate)
- Questions about more complex sentence structures
- Practice with quantifying changes (doubled, tripled, halved)
- Integration with actual graph/chart data
- Multi-sentence paraphrasing practice

## Related Documentation

- `EXERCISE_JSON_STANDARDS.md` - Standards for creating exercise JSON files
- `DEVELOPMENT-GUIDELINES.md` - General development guidelines
- `ANSWER-FEEDBACK-GUIDELINES.md` - Guidelines for writing effective feedback

## Credits

- **Created**: 2026-01-19
- **Format Version**: 1.0
- **Plugin Version**: 12.2
- **Question Types**: closed_question, open_question
