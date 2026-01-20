# Reading Test 03 - Complete Question Type Listing

## Summary
- **Total Question Objects in JSON**: 38
- **Total Question Points/Numbers**: 39 (one multi-field question counts as 2)
- **Reading Passages**: 3

---

## Detailed Question Breakdown

### Reading Passage 1: Driverless Cars
**Questions 1-13**

| Q# | Question Type | Description |
|----|---------------|-------------|
| 1 | Matching Headings | Paragraph B - Choose heading from list |
| 2 | Matching Headings | Paragraph C - Choose heading from list |
| 3 | Matching Headings | Paragraph D - Choose heading from list |
| 4 | Matching Headings | Paragraph E - Choose heading from list |
| 5 | Matching Headings | Paragraph F - Choose heading from list |
| 6 | True/False/Not Given | "Before the driverless car can navigate, a human driver must first drive the route." |
| 7 | True/False/Not Given | "Cars driven by people cause nearly one million deaths per year." |
| 8 | True/False/Not Given | "Driverless cars travel at a slower speed than manned cars." |
| 9 | True/False/Not Given | "Driverless cars are expected to become mainstream in the next ten years." |
| 10 | Matching Information | Match person: "Was responsible for encouraging the original inspiration for the idea of driverless cars" |
| 11 | Matching Information | Match person: "Claimed that this would reduce the interest in driving" |
| 12 | Matching Information | Match person: "Felt that the driverless car was more observant than a human driver" |
| 13 | Matching Information | Match person: "Believes that current driving conditions should not have come about in the first place" |

**Passage 1 Total: 13 questions**

---

### Reading Passage 2: Scandinavian Design
**Questions 14-26**

| Q# | Question Type | Description |
|----|---------------|-------------|
| 14 | True/False/Not Given | "The first exhibition of Scandinavian design in Canada and the United States was from 1954 to 1957." |
| 15 | True/False/Not Given | "The first ideas of a 'Scandinavian design' came about at the same time as new emerging movements in Art, such as Bauhaus and Dadaism." |
| 16 | True/False/Not Given | "The term 'industrial arts' was coined by Scandinavian designers and artists in the Art Nouveau movement together." |
| 17 | True/False/Not Given | "As well as furniture and accessories, Scandinavian design philosophies now also include environmentalism." |
| 18 | Matching Information | Match statement: "has been praised by art critics for the artistic achievements as well as being functional." |
| 19 | Matching Information | Match statement: "is now another name for 'Scandinavian design'." |
| 20 | Matching Information | Match statement: "came up with new ways to shape and use a traditional construction material." |
| 21 | Matching Information | Match statement: "emerged in the 1920s and 1930s, along with other European movements." |
| 22 | Matching Information | Match statement: "was originally created to maintain standards of perfection." |
| 23 | Matching Information | Match statement: "moved from designing only furniture to accessories." |
| 24 | Matching Information | Match statement: "designed a chair based on a new movement in Art." |
| 25-26 | Summary Completion | **Multi-field question (2 points)**: Complete sentence with NO MORE THAN TWO WORDS - [field 1] concerns related to where raw materials are sourced from; IKEA uses wood from [field 2] |

**Passage 2 Total: 13 questions (12 question objects, 1 multi-field worth 2 points)**

---

### Reading Passage 3: The Nobel Prize
**Questions 27-39**

| Q# | Question Type | Description |
|----|---------------|-------------|
| 27 | Sentence Completion | "The Nobel prizes are awarded for [field 1] in a number of fields." |
| 28 | Sentence Completion | "Nobel was inspired to establish the Prizes following an obituary issued in [field 1]." |
| 29 | Sentence Completion | "Family arguments were part of the reason why there was a delay of [field 1] before the first prizes." |
| 30 | Sentence Completion | "In the middle of the last century, a Russian nominee was compelled into [field 1] the award." |
| 31 | Sentence Completion | "[field 1] has earned a Prize on a number of occasions." |
| 32 | Sentence Completion | "In the early 1940s, there was [field 1] on two occasions." |
| 33 | True/False/Not Given | "Marie Curie is the only female to have been awarded a Prize." |
| 34 | True/False/Not Given | "There are currently 6 categories for which Prizes are awarded." |
| 35 | True/False/Not Given | "Prizes cannot be awarded to people who have died." |
| 36 | True/False/Not Given | "Some members of the judging panel have resigned because they disagreed with how the winner was chosen." |
| 37 | Matching Information | Match person: "Has been accused of not being sympathetic to refugees." (Cordell Hull) |
| 38 | Matching Information | Match person: "Had a medical condition that could have influenced his decision making." (John Forbes Nash) |
| 39 | Matching Information | Match person: "May have been made a recipient of the award for ulterior motives." (Barack Obama) |

**Passage 3 Total: 13 questions**

---

## Question Type Summary

| Question Type | Count |
|---------------|-------|
| Matching Headings | 5 |
| True/False/Not Given | 12 |
| Matching Information | 14 |
| Sentence Completion | 6 |
| Summary Completion | 2 (multi-field, counted as 1 object) |
| **TOTAL** | **39** |

---

## Technical Details

### Question Objects vs Question Numbers
- The JSON contains **38 question objects**
- However, question 25-26 is stored as a **single multi-field open_question** with `"points": 2` and `"field_count": 2`
- This creates **39 total question numbers** (Q1-Q39)

### Question Numbering
- Questions are numbered sequentially from 1 to 39
- The multi-field question (25-26) occupies two question numbers but is one JSON object
- The `starting_question_number` in settings is set to "1"

---

## Analysis - ISSUE FOUND!

The discrepancy mentioned in the issue states:
- **System says**: 39 questions
- **User sees**: 40 questions

**ROOT CAUSE IDENTIFIED**: The JSON file contains 39 questions, but the instructions for Question 27 (first question of Passage 3) incorrectly state:

> "You should spend about 20 minutes on Questions 27 – **40** which are based on Reading Passage 3."

This is **INCORRECT**. The actual question range for Passage 3 is:
- **Questions 27-39** (13 questions)

The question numbering is correct as follows:
- Passage 1: Q1-13 (13 questions)
- Passage 2: Q14-26 (13 questions, where Q25-26 is one multi-field object)
- Passage 3: Q27-39 (13 questions)
- **Total: 39 questions**

**THE FIX**: Change the instructions text from "Questions 27 – 40" to "Questions 27 – 39"
