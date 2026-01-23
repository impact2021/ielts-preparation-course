# Show Me Buttons Fix Documentation

## Issue Summary
"Show me" buttons were not appearing for the "Matching & classifying practice" exercise.

## Root Cause
The exercise JSON had all questions with:
- `"audio_section_id": null`
- `"reading_text_id": null`

According to the code in `includes/class-quiz-handler.php` (lines 230-234, 285-289, 356-367, 380-391, 405-416), "show me" buttons only appear when:
1. For listening exercises: `audio_section_id !== null`
2. For reading exercises: `reading_text_id !== null`

## The Fix
Updated all 7 questions in the exercise to set `"audio_section_id": 2` to match the section number in the audio array.

### Before:
```json
{
    "question": "11. Over 30% of the population was under 15.",
    "audio_section_id": null,
    "reading_text_id": null,
    ...
}
```

### After:
```json
{
    "question": "11. Over 30% of the population was under 15.",
    "audio_section_id": 2,
    "reading_text_id": null,
    ...
}
```

## How "Show Me" Buttons Work

### For Listening Exercises:
1. Each question must have `audio_section_id` set to a valid section number
2. The audio object must have a corresponding section with that `section_number`
3. The transcript in that section must contain question markers like `<span id="q11" data-question="11"></span>`
4. When a student clicks "Show me", the frontend scrolls to the question marker and highlights the answer text

### For Reading Exercises:
1. Each question must have `reading_text_id` set to a valid reading text ID
2. The reading_texts array must contain a text with that ID
3. The reading text must contain question markers like `<span id="q11" data-question="11"></span>`
4. When a student clicks "Show me", the frontend scrolls to the question marker and highlights the answer text

### Code Reference (class-quiz-handler.php):
```php
// Add "Show me" link for both listening and reading tests
if (isset($question['audio_section_id']) && $question['audio_section_id'] !== null) {
    $feedback .= '<br><a href="#q' . esc_attr($question_num) . '" class="show-in-transcript-link" data-section="' . esc_attr($question['audio_section_id']) . '" data-question="' . esc_attr($question_num) . '">' . __('Show me', 'ielts-course-manager') . '</a>';
} elseif (isset($question['reading_text_id']) && $question['reading_text_id'] !== null) {
    $feedback .= '<br><a href="#q' . esc_attr($question_num) . '" class="show-in-reading-passage-link" data-reading-text="' . esc_attr($question['reading_text_id']) . '" data-question="' . esc_attr($question_num) . '">' . __('Show me', 'ielts-course-manager') . '</a>';
}
```

## Prevention Guidelines

When creating new exercises, ensure:

### For Listening Exercises:
1. ✅ Set `audio_section_id` to the appropriate section number (not null)
2. ✅ Create audio sections with matching `section_number` values
3. ✅ Include question markers in the transcript: `<span id="qN" data-question="N"></span>`
4. ✅ Place answer text inside `<span class="reading-answer-marker">answer text</span>` immediately after the question marker

### For Reading Exercises:
1. ✅ Set `reading_text_id` to the appropriate reading text ID (not null)
2. ✅ Create reading texts with matching IDs
3. ✅ Include question markers in the reading text: `<span id="qN" data-question="N"></span>`
4. ✅ Place answer text inside `<span class="reading-answer-marker">answer text</span>` immediately after the question marker

### For Other Exercise Types (Writing, Speaking):
- ❌ Leave both `audio_section_id` and `reading_text_id` as `null`
- "Show me" buttons should not appear for these exercise types

## Validation Checklist

Before deploying a new exercise, verify:

- [ ] All listening questions have `audio_section_id` set to a valid section number
- [ ] All reading questions have `reading_text_id` set to a valid text ID
- [ ] Audio sections exist with matching `section_number` values
- [ ] Reading texts exist with matching IDs
- [ ] Transcripts/reading texts contain all required question markers
- [ ] Question markers match the question numbers in the feedback
- [ ] Answer text is wrapped in `reading-answer-marker` spans

## Testing

To test if "show me" buttons appear:
1. Load the exercise in a browser
2. Complete and submit the exercise
3. Check the feedback for each question
4. Verify "Show me" links appear (if applicable)
5. Click each "Show me" link to ensure it scrolls to the correct location
6. Verify the answer text is highlighted with a yellow background

## Related Files
- `includes/class-quiz-handler.php` - Backend logic for generating feedback and "show me" buttons
- `assets/js/frontend.js` - Frontend logic for handling "show me" button clicks
- `templates/single-quiz-computer-based.php` - Template for rendering exercises
- `main/Exercises/matching-and-classifying-practice.json` - Fixed exercise file
