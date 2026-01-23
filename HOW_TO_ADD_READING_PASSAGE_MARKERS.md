# How to Add Reading Passage Markers to Enable "Show Me" Buttons

## Problem
When you import a JSON file with reading passages and questions, the "Show me" buttons may appear but don't highlight anything in the reading passages. This happens when your reading passage content is missing the required markers.

## Solution
Add question markers to your reading passage content to link questions to specific text sections.

## Two Methods to Add Markers

### Method 1: Automatic Markers (Recommended - Easy)

Add `[Q#]` before the text that contains the answer. The system will automatically create the marker and highlight the relevant text.

**Example:**

```json
{
  "reading_texts": [
    {
      "title": "Question 1",
      "content": "The majority of gap year programmes involve working in regions requiring external assistance with their development; Melissa Hedges, Director of GYOME says work placements may involve teaching English to local children. Though [Q1]Scott Bradley warns that some students from some particular study disciplines may find that their own industry is less receptive to the advantages of a gap year than others."
    }
  ]
}
```

**What happens:**
- `[Q1]` is placed right before "Scott Bradley warns..."
- When students click "Show me" for Question 1, the text from "Scott Bradley warns..." up to the next comma, period, or 50 characters will be highlighted
- The marker itself is invisible to students

### Method 2: Manual HTML Markers (Full Control)

Use HTML `<span>` tags to precisely control what text gets highlighted.

**Format:**
```html
<span id="q#" data-question="#"></span><span class="reading-answer-marker">exact text to highlight</span>
```

**Example:**

```json
{
  "reading_texts": [
    {
      "title": "Question 1",
      "content": "<p>The majority of gap year programmes involve working in regions requiring external assistance with their development; Melissa Hedges, Director of GYOME says work placements may involve teaching English to local children. Though <span id=\"q1\" data-question=\"1\"></span><span class=\"reading-answer-marker\">Scott Bradley warns that some students from some particular study disciplines may find that their own industry is less receptive to the advantages of a gap year than others</span>.</p>"
    }
  ]
}
```

**Benefits:**
- Exact control over highlighted text
- Can span multiple sentences
- Can have multiple markers for the same question number (to highlight different sections)

## Complete Working Example

Here's the JSON from the problem statement with markers added:

```json
{
    "title": "Matching and classifying example",
    "content": "Read each text carefully and select the correct answer based on the information given.",
    "questions": [
        {
            "type": "closed_question",
            "question": "According to the text, who believes that a gap year may not necessarily enhance employment opportunities?",
            "points": 1,
            "reading_text_id": 0,
            "mc_options": [
                {
                    "text": "Melissa Hedges",
                    "is_correct": false
                },
                {
                    "text": "Lucy Clarke",
                    "is_correct": false
                },
                {
                    "text": "Scott Bradley",
                    "is_correct": true
                }
            ],
            "correct_answer_count": 1,
            "show_option_letters": true
        },
        {
            "type": "closed_question",
            "question": "When did the construction of Hadrian's Wall take place, according to the passage?",
            "points": 1,
            "reading_text_id": 1,
            "mc_options": [
                {
                    "text": "before 43 A.D.",
                    "is_correct": false
                },
                {
                    "text": "between 43 A.D. and 343 A.D.",
                    "is_correct": true
                },
                {
                    "text": "after 343 A.D.",
                    "is_correct": false
                }
            ],
            "correct_answer_count": 1,
            "show_option_letters": true
        }
    ],
    "reading_texts": [
        {
            "title": "Question 1",
            "content": "The majority of gap year programmes involve working in regions requiring external assistance with their development; Melissa Hedges, Director of GYOME (Gap Year Organisation Made Easy) says work placements may involve teaching English to local children, farm work or infrastructure development projects. Since participants in the programmes are helping to bring genuine benefit to impoverished areas, such an experience can be not only personally rewarding, but helpful with future employment searches, employers often holding the view that travel and in particular voluntary work overseas help to develop maturity, independence and team-building skills in potential graduate employees. Employment Agency Consultant Lucy Clarke says such experience can add tremendous value to applicants' resumes and positively impact on their success at reaching interview stage. She adds that it is helpful for students to plan a gap year placement which will involve functions and responsibilities somehow related to their chosen future career if at all possible, though [Q1]Scott Bradley warns that some students from some particular study disciplines may find that their own industry is less receptive to the advantages of a gap year than others. For example, graduates of a technology-related degree could find themselves at a disadvantage on their return as knowledge and applications within the industry are so dynamic."
        },
        {
            "title": "Question 2",
            "content": "To compress thousands of years of history in a few paragraphs is a difficult task, and as with almost any historical point a millennia old, even 'facts' are disputable. What is generally accepted is that the first notable new arrivals to Britain came over 2000 years ago in the form of an army of Romans. Conflicts ensued with the tribal populations of Britain, but the organised martial might of the Romans proved superior, and for the following century, Roman influence spread throughout what is now known as England. Yet this was not a true invasion, as it was not until nearly 100 years later that Rome decided it wanted Britain to be part of the Roman Empire. Consequently, [Q2]in 43 A.D., the first full scale invasion took place in the South East of England. Some thirty years later, Roman control had spread throughout England and Wales, although Scotland had remained defiant. Frequent incursions into England from tribes in Scotland led to the creation of one of Britain's most impressive constructions – Hadrian's Wall."
        }
    ],
    "settings": {
        "pass_percentage": "70",
        "layout_type": "two_column_reading",
        "cbt_test_type": "",
        "exercise_label": "exercise",
        "scoring_type": "percentage",
        "timer_minutes": "",
        "starting_question_number": "1"
    }
}
```

## Key Points

1. **Each question needs a marker** - Questions without markers won't have working "Show me" buttons
2. **Use the question number** - Marker `[Q1]` corresponds to question 1, `[Q2]` to question 2, etc.
3. **Place marker right before the answer** - Put `[Q#]` immediately before the text containing the answer
4. **Markers are automatic** - The system automatically converts `[Q#]` to proper HTML spans
5. **For precise control** - Use manual HTML markers with `<span id="q#" data-question="#"></span><span class="reading-answer-marker">text</span>`

## How to Test

1. Import your JSON file into WordPress
2. Take the test and answer questions
3. After submitting, click the "Show me" button next to any question's feedback
4. The reading passage should scroll to and highlight the relevant text

## Need More Help?

- See `READING_PASSAGE_MARKER_GUIDE.md` for complete marker documentation
- Check `main/Academic Read Test JSONs/Test-Manual-Markers.json` for a working example
- Check `main/Academic Read Test JSONs/Academic-IELTS-Reading-Test-07.json` for automatic markers example

## Common Issues

**Problem**: "Show me" button doesn't highlight anything
- **Cause**: Missing markers in reading_texts content
- **Solution**: Add `[Q#]` markers before answer text

**Problem**: Wrong text is highlighted
- **Cause**: Marker placed in wrong location
- **Solution**: Move `[Q#]` to be right before the correct answer text

**Problem**: Want to highlight multiple sections for one question
- **Solution**: Use manual markers with the same question number multiple times:
  ```html
  <span id="q1" data-question="1"></span><span class="reading-answer-marker">first section</span>
  ...
  <span id="q1" data-question="1"></span><span class="reading-answer-marker">second section</span>
  ```
