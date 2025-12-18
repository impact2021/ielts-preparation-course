# Exercise Feedback System Guide

## Overview

The IELTS Course Manager now includes a comprehensive feedback system for quiz exercises. This allows you to provide specific feedback to students based on their answers, helping them learn from their mistakes and understand why answers are correct or incorrect.

## Features

### 1. HTML Support in Questions and Feedback
- Questions now support full HTML formatting including images, bold text, colors, and links
- Feedback messages also support HTML for rich formatting
- All HTML is safely sanitized to prevent security issues

### 2. Feedback Types

#### For All Question Types:
- **Correct Answer Feedback**: Shown when the student answers correctly
- **Incorrect Answer Feedback**: Shown when the student answers incorrectly

#### Additional for Multiple Choice:
- **Per-Option Feedback**: Specific feedback for each wrong answer option
- If no specific option feedback is provided, the general incorrect feedback is used

## How to Use

### Adding Feedback to Questions

When editing a quiz in the WordPress admin:

1. **Edit or Create a Quiz** (IELTS Courses > Quizzes)
2. **Add or edit a question**
3. **Scroll to the "Feedback Messages" section** at the bottom of each question
4. **Fill in the feedback fields:**
   - **Correct Answer Feedback**: Enter text to show when correct (HTML supported)
   - **Incorrect Answer Feedback**: Enter general feedback for wrong answers (HTML supported)
   - **Per-Option Feedback** (Multiple Choice only): Enter feedback for each option, one per line

### Example: True/False Question

**Question:** "There are 40 questions in the reading test."

**Options:**
- True
- False

**Correct Answer:** true

**Correct Answer Feedback:**
```
Correct answer
```

**Incorrect Answer Feedback:**
```
There are 40 questions in the reading test.
```

### Example: Multiple Choice with Per-Option Feedback

**Question:** "In the General Training module, all three sections are long, formal texts."

**Options:**
- True
- False

**Correct Answer:** 1 (False)

**Correct Answer Feedback:**
```
Incorrect
```

**Per-Option Feedback** (one per line):
```
In Sections 1 and 2, the texts can be quite short – sometimes just a timetable or short advert.

```

(Note: The first line is feedback for option 0 (True), second line for option 1 (False). Since option 1 is correct, leave its feedback blank or the correct feedback will be shown instead.)

### Example: HTML Formatting in Questions

**Question Text:**
```html
<strong><span style="color: #3366ff">Are the ideas below suitable and accurate to include in paragraph 2?</span></strong>

<img src="https://example.com/chart.png" alt="Chart" width="500" />

<strong>Europe and Australasia equal in 46-60 group</strong>
```

This will now render properly with:
- Bold blue text
- The image displayed
- Bold text for the statement

### Example: Complex Feedback with HTML

**Correct Answer Feedback:**
```html
<strong style="color: green;">Correct!</strong>
<p>It's FALSE because although there are commonly 5 parts (2 parts to Section 1, 2 parts in Section 2 and 1 part in Section 3), this is not ALWAYS the case – it is possible to have 6 different sections, with 3 sections in Section 1.</p>
```

**Incorrect Answer Feedback:**
```html
<strong style="color: red;">Incorrect</strong>
<p>Remember that while there are <em>commonly</em> 5 parts, this is not <strong>always</strong> the case.</p>
```

## Technical Details

### Data Structure

Questions are stored with the following structure:

```php
array(
    'type' => 'multiple_choice', // or 'true_false', 'fill_blank', 'essay'
    'question' => 'Question text with <strong>HTML</strong>',
    'options' => "Option 1\nOption 2\nOption 3", // for multiple choice
    'correct_answer' => '0', // option index for MC, 'true'/'false'/'not_given' for T/F
    'points' => 1,
    'correct_feedback' => 'Feedback for correct answer',
    'incorrect_feedback' => 'Feedback for incorrect answer',
    'option_feedback' => array( // only for multiple choice
        'Feedback for option 0',
        'Feedback for option 1',
        'Feedback for option 2'
    )
)
```

### Frontend Display

After a student submits a quiz, feedback is displayed:
- ✓ **Question X: Correct** - with correct feedback (if provided)
- ✗ **Question X: Incorrect** - with specific or general incorrect feedback

### Security

- All HTML in questions and feedback is sanitized using `wp_kses_post()`
- This allows safe HTML tags (p, strong, em, img, a, etc.) while blocking dangerous scripts
- Question text and feedback are stored with HTML intact
- Display uses safe rendering functions

## Migrating Existing Quizzes

Existing quizzes without feedback will continue to work normally:
- Questions display as before
- Feedback fields are optional
- If no feedback is provided, only "Correct" or "Incorrect" status is shown
- You can edit existing quizzes and add feedback at any time

## XML Exercise Import

When creating exercises from XML:
- Feedback fields are initialized as empty strings
- You can edit each exercise after creation to add appropriate feedback
- Questions and content preserve HTML formatting from the original source

## Best Practices

1. **Keep feedback concise but helpful** - Students should understand why they got it right or wrong
2. **Use HTML formatting sparingly** - Bold key terms, but avoid excessive styling
3. **Provide specific feedback for common mistakes** - Use per-option feedback for multiple choice
4. **Test your feedback** - Submit test answers to see how feedback appears to students
5. **Be encouraging** - Even incorrect feedback should help students learn

## Troubleshooting

### HTML code showing instead of rendering
✅ **Fixed in v1.16** - Questions and feedback now properly render HTML

### Feedback not showing
- Check that you've filled in the feedback fields when editing the question
- Verify the quiz has been saved after adding feedback
- Clear browser cache if needed

### Images not displaying
- Verify the image URL is correct and accessible
- Check image URL uses `https://` for security
- Ensure images are uploaded to your WordPress media library or hosted externally

## Future Enhancements

Potential improvements for future versions:
- Visual editor for feedback fields
- Feedback templates library
- Bulk feedback import/export
- Analytics on which feedback is most viewed
