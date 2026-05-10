# Version 13.5 Release Notes

## Critical Bug Fix: "Show me" Buttons Not Appearing

**Date:** 2026-01-23

### Problem Statement
A major issue was discovered where the "Show me" buttons were not appearing for correct or incorrect answers in multiple choice questions - they only showed when no answer was given. This severely impacted the learning experience as students couldn't see where answers were located in reading passages or listening transcripts after answering questions.

### Root Cause Analysis

The issue was in the JavaScript file `assets/js/frontend.js`. For `multi_select` and `closed_question` question types:

1. **Option-level feedback display:** When users answer correctly or incorrectly, feedback is displayed under each selected option (inside the option label), not at the question level
2. **No question-level container:** The code did NOT create a `.question-feedback-message` div at the question level for these cases
3. **"Show me" button needs container:** The "Show me" button code (lines 1099-1147) tries to append to `.question-feedback-message`:
   ```javascript
   var feedbackDiv = questionElement.find('.question-feedback-message');
   if (feedbackDiv.length) {
       feedbackDiv.append(buttonContainer);
   }
   ```
4. **Button lost:** Since there's no `.question-feedback-message` div, `feedbackDiv.length` is 0, and the button is never appended!

**Why It Only Worked for "No Answer":**
- When no answer is given, the code creates a question-level `.question-feedback-message` div to show the no-answer feedback
- This div exists, so the "Show me" button can be appended to it
- For correct/incorrect answers with per-option feedback, this div was never created

### The Fix

Modified the JavaScript to create an empty `.question-feedback-message` div for multi_select and closed_question types when:
- User has answered the question (correct or incorrect)
- Question has `audio_section_id` or `reading_text_id` (indicating a "Show me" button is needed)
- This div serves purely as a container for the "Show me" button

**Code changes in assets/js/frontend.js:**

For `multi_select` questions (after line 857):
```javascript
} else if ((questionResult.audio_section_id !== null && questionResult.audio_section_id !== undefined) ||
           (questionResult.reading_text_id !== null && questionResult.reading_text_id !== undefined)) {
    // Create an empty question-level feedback div for "Show me" button
    // even when feedback is shown per-option (for correct/incorrect answers)
    var feedbackDiv = $('<div>')
        .addClass('question-feedback-message')
        .addClass(questionResult.correct ? 'feedback-correct' : 'feedback-incorrect');
    questionElement.append(feedbackDiv);
}
```

For `closed_question` questions (after line 913):
```javascript
} else if ((questionResult.audio_section_id !== null && questionResult.audio_section_id !== undefined) ||
           (questionResult.reading_text_id !== null && questionResult.reading_text_id !== undefined)) {
    // Create an empty question-level feedback div for "Show me" button
    // even when feedback is shown per-option (for correct/incorrect answers)
    var feedbackDiv = $('<div>')
        .addClass('question-feedback-message')
        .addClass(questionResult.correct ? 'feedback-correct' : 'feedback-incorrect');
    questionElement.append(feedbackDiv);
}
```

### Files Modified

1. **assets/js/frontend.js**
   - Added else-if clause at line 858-866 for multi_select questions
   - Added else-if clause at line 914-922 for closed_question questions

2. **ielts-course-manager.php**
   - Updated version from 13.4 to 13.5

### Example Flow (After Fix)

1. Student answers a multiple choice question correctly ✅
2. Option feedback appears under the selected option ✅
3. Empty question-level `.question-feedback-message` div is created (as container) ✅
4. "Show me" button is appended to this div ✅
5. Student can now click "Show me" to see where the answer is in the passage! ✅

### Impact

**Before Fix:**
- ❌ "Show me" buttons missing for correct answers (with option feedback)
- ❌ "Show me" buttons missing for incorrect answers (with option feedback)
- ✅ "Show me" buttons working for unanswered questions

**After Fix:**
- ✅ "Show me" buttons appear for ALL answer states (correct/incorrect/no answer)
- ✅ Option-level feedback still displays correctly under selected options
- ✅ Buttons display consistently for both reading and listening tests

### Testing Recommendations

To verify the fix works correctly:

1. **Multiple choice question with correct answer:**
   - Select the correct option and submit
   - Verify option feedback appears under the option
   - Verify "Show me" button appears at question level
   - Click it and verify highlighting works

2. **Multiple choice question with incorrect answer:**
   - Select an incorrect option and submit
   - Verify option feedback appears under the option
   - Verify "Show me" button appears at question level
   - Click it and verify highlighting works

3. **No answer given:**
   - Leave question unanswered and submit
   - Verify "Show me" button still appears (regression test)

### Security

CodeQL analysis confirmed no security vulnerabilities were introduced by these changes.

---

**Version:** 13.5  
**Previous Version:** 13.4  
**Type:** Critical Bug Fix  
**Breaking Changes:** None
