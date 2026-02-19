# Show Me Button Fix - Version 16.2

## Issue Description

Users reported that the "Show me" button was not appearing for questions, even when audio was configured. The issue occurred when:
- Questions had empty `correct_feedback` and `incorrect_feedback` fields (empty strings)
- Questions had `audio_section_id: null` even though audio was available
- Users expected the button to appear automatically

## Root Causes

### 1. No Feedback Container Created
When a question had empty feedback text AND no `audio_section_id` or `reading_text_id` set, the feedback container div was not created. This prevented:
- Visual feedback (correct/incorrect indicator via CSS classes)
- A place to display "Show me" buttons if audio/reading IDs were added later

The condition in `frontend.js` was:
```javascript
} else if (questionResult.feedback || needsShowMeContainer(questionResult)) {
```

This evaluated to false when:
- `feedback` was an empty string (falsy)
- `needsShowMeContainer` returned false (both audio_section_id and reading_text_id were null)

### 2. No Auto-Defaulting of audio_section_id
Users had to manually set `audio_section_id: 0` even when:
- The quiz had audio defined
- There was only one audio section
- The intent was clearly to link questions to that section

This created a poor user experience and confusion about why the "Show me" button wasn't appearing.

## Solutions Implemented

### 1. Frontend Changes (assets/js/frontend.js)

#### Added Helper Function
```javascript
function isQuestionAnswered(questionResult) {
    var userAnswer = questionResult.user_answer;
    if (userAnswer == null) {
        return false;
    }
    // Handle arrays (multi-select, etc.)
    if (Array.isArray(userAnswer)) {
        return userAnswer.length > 0;
    }
    // Handle objects (dropdown_paragraph, etc.)
    if (typeof userAnswer === 'object') {
        return Object.keys(userAnswer).length > 0;
    }
    // Handle strings and numbers
    if (typeof userAnswer === 'string') {
        return userAnswer.trim() !== '';
    }
    return true;
}
```

#### Updated Feedback Container Logic
Changed conditions from:
```javascript
} else if (questionResult.feedback || needsShowMeContainer(questionResult)) {
```

To:
```javascript
} else if (questionResult.feedback || needsShowMeContainer(questionResult) || isQuestionAnswered(questionResult)) {
```

Applied to:
- `multi_select` questions (line 958)
- `closed_question` with option_feedback (line 1010)
- `closed_question` fallback (line 1024)
- Other question types (line 1138)

**Result**: Feedback container is now created for ALL answered questions, ensuring visual feedback is always shown.

### 2. Backend Changes (includes/class-quiz-handler.php)

Added auto-defaulting logic in the `submit_quiz()` function:

```php
// Auto-default audio_section_id to 0 if not set and there's exactly one audio section
// This improves usability when users forget to set audio_section_id
// Note: audio_section_id uses 0-based indexing, so 0 refers to the first section
$audio_sections = get_post_meta($quiz_id, '_ielts_cm_audio_sections', true);
if (is_array($audio_sections) && count($audio_sections) === 1) {
    foreach ($questions as $idx => $question) {
        if (!isset($question['audio_section_id']) && !isset($question['reading_text_id'])) {
            // Only default if no reading_text_id either (avoid ambiguity)
            $questions[$idx]['audio_section_id'] = 0;
        }
    }
}
```

**Result**: Questions automatically get linked to audio when there's only one section, without manual configuration.

## Benefits

### For Users
- No longer need to manually set `audio_section_id` when there's only one audio section
- Visual feedback (correct/incorrect indicators) always appears for answered questions
- "Show me" button appears automatically when appropriate

### For Developers
- More intuitive behavior reduces support questions
- Feedback container is always created, making the system more predictable
- Code is more defensive with proper null/edge case handling

## Technical Details

### Audio Section Indexing
- Audio sections use **0-based indexing**
- `section_number: 1` in the JSON maps to `audio_section_id: 0`
- This is important for users to understand when manually setting IDs

### Edge Cases Handled
1. **Multiple audio sections**: Only defaults when there's exactly ONE section (avoids ambiguity)
2. **Reading vs Audio**: Only defaults audio_section_id when reading_text_id is also not set
3. **Null vs undefined**: Uses `== null` pattern in JavaScript to catch both
4. **Empty arrays/objects**: Helper function properly checks for empty collections
5. **Various question types**: Logic applied consistently across all question types

## Testing

### Validation Performed
- ✅ PHP syntax validation passed
- ✅ JavaScript syntax validation passed
- ✅ Code review completed and feedback addressed
- ✅ CodeQL security scan - no issues found

### Manual Testing Recommended
Test with JSON like:
```json
{
    "questions": [
        {
            "type": "closed_question",
            "audio_section_id": null,
            "correct_feedback": "",
            "incorrect_feedback": "",
            "no_answer_feedback": "Some feedback",
            "mc_options": [...]
        }
    ],
    "audio": {
        "url": "https://example.com/audio.mp3",
        "sections": [
            {
                "section_number": 1,
                "transcript": "..."
            }
        ]
    }
}
```

**Expected behavior:**
1. When user answers the question, feedback container appears
2. If correct: green border-left indicator
3. If incorrect: visual indicator (no red border in current design)
4. "Show me" button appears automatically (since audio_section_id gets defaulted to 0)

## Files Modified

1. **assets/js/frontend.js**
   - Added `isQuestionAnswered()` helper function
   - Updated feedback container creation logic (4 locations)
   - Simplified null checks

2. **includes/class-quiz-handler.php**
   - Added auto-defaulting logic for audio_section_id
   - Added clarifying comments

## Version Information

- **Plugin Version**: 16.2
- **Date**: 2026-02-19
- **Related Issue**: "wHY IS THIS not showing me the 'Show me' button?"

## Migration Notes

### For Existing Installations
- No database migrations required
- No changes to JSON format requirements
- Backward compatible - existing quizzes work as before
- New behavior only applies to quizzes with single audio section and null audio_section_id

### For Users
- Existing JSONs with explicitly set audio_section_id values are unchanged
- JSONs with null audio_section_id will benefit from auto-defaulting
- Feedback containers will now appear for all answered questions (improvement)

## Security Summary

- ✅ No new security vulnerabilities introduced
- ✅ CodeQL scan completed with 0 alerts
- ✅ Input validation unchanged (still server-side sanitized)
- ✅ No new user input vectors added
- ✅ Defensive coding practices maintained

## Future Considerations

While not implemented in this minimal fix, future improvements could include:

1. **Helper Function for Repeated Logic**: Extract `needsShowMeContainer() || isQuestionAnswered()` into a single helper
2. **Performance Optimization**: Reorder condition checks for faster evaluation
3. **Multi-Section Auto-Linking**: Consider UI for helping users link questions to specific sections
4. **Clear Error Messages**: When audio_section_id is ambiguous, show helpful message

However, these are optimization opportunities rather than critical fixes.
