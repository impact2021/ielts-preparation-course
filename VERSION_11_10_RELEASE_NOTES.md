# Version 11.10 Release Notes

**Release Date:** January 11, 2026  
**Focus:** Q1 Answer Feedback Visual Highlighting & Transcript Answer Highlighting Fixes

## Overview

This release fixes two visual issues:
1. **Question feedback messages** - Entire feedback blocks now have colored backgrounds, not just the question number
2. **Transcript answer highlighting** - Yellow highlights now appear on actual answers, not entire sentences

## What Changed

### Issue 1: Question Feedback Visual Highlighting

**Before (Version 11.9)**
- Only the checkmark (✓) or cross (✗) icon was colored
- Only the number portion of "Question 1:" had color
- The feedback message text appeared without any background
- Feedback was difficult to distinguish visually

**After (Version 11.10)**  
- **Correct answers:** Entire feedback block has light green background (#4caf50 with 10% opacity)
- **Incorrect answers:** Entire feedback block has light red background (#f44336 with 10% opacity)
- **Left border:** 4px colored border matching the feedback type (green for correct, red for incorrect)
- **Padding:** 12px padding around feedback text for better readability
- **Question number:** "Question X:" text is colored (darker green #2e7d32 for correct, darker red #c62828 for incorrect)
- **Icons:** Checkmark (✓) and cross (✗) icons maintain their vibrant colors

### Issue 2: Transcript Answer Highlighting

**Before (Version 11.9)**
- Yellow highlighting appeared on entire sentences (up to 100 characters)
- Highlighted text often included irrelevant context before/after the answer
- Example: `[Q1]Yes of course. It's Anne Hawberry.` would highlight "Yes of course. It's Anne Hawberry." instead of just "Anne Hawberry"

**After (Version 11.10)**
- **Smart boundary detection** - Stops highlighting at natural answer boundaries:
  - Commas (`,`)
  - Semicolons (`;`)
  - Period followed by capital letter (`. Next`)
  - Newlines
  - 50 character limit (reduced from 100)
- **Word trimming** - If over 50 characters, trims to last complete word
- **Example:** `It's [Q1]Anne Hawberry, and I live in London.` now highlights only "Anne Hawberry"

## Technical Details

### Files Modified

#### 1. `assets/css/frontend.css`
Added comprehensive styling for field feedback messages:

```css
/* Correct answer feedback - green theme */
.question-feedback-message.open-question-feedback .field-feedback-correct {
    background: rgba(76, 175, 80, 0.1);        /* Light green background */
    border-left-color: #4caf50;                 /* Green left border */
    padding: 12px 15px;                         /* Comfortable padding */
    border-radius: 5px;                         /* Rounded corners */
}

.question-feedback-message.open-question-feedback .field-feedback-correct strong {
    color: #2e7d32;                             /* Dark green text for "Question X:" */
}

/* Incorrect answer feedback - red theme */
.question-feedback-message.open-question-feedback .field-feedback-incorrect {
    background: rgba(244, 67, 54, 0.1);         /* Light red background */
    border-left-color: #f44336;                 /* Red left border */
    padding: 12px 15px;                         /* Comfortable padding */
    border-radius: 5px;                         /* Rounded corners */
}

.question-feedback-message.open-question-feedback .field-feedback-incorrect strong {
    color: #c62828;                             /* Dark red text for "Question X:" */
}
```

#### 2. `templates/single-quiz-computer-based.php`
Updated `process_transcript_markers_cbt()` function with smart boundary detection:

```php
// Smart answer extraction: highlight only the answer portion, not entire sentences
if (preg_match('/^([^,;]+?)(?:[,;]|\.\s+[A-Z]|\n|$)/s', $answer_text, $boundary_match)) {
    // Found a natural boundary - use text up to that point
    $highlighted_text = $boundary_match[1];
} else {
    // No natural boundary found - take first 50 characters
    $highlighted_text = mb_substr($answer_text, 0, 50);
}
```

#### 3. `templates/single-quiz-listening-practice.php`
Updated `process_transcript_markers_practice()` with same smart detection logic

#### 4. `templates/single-quiz-listening-exercise.php`
Updated `process_transcript_markers()` with same smart detection logic

#### 5. `ielts-course-manager.php`
- Updated version from 11.9 to 11.10
- Updated IELTS_CM_VERSION constant to 11.10

#### 6. `TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md`
- Updated to version 11.10
- Documented new smart boundary detection algorithm
- Updated technical implementation section

## Visual Examples

### 1. Question Feedback (Correct Answer)
```
┌─────────────────────────────────────────────────────┐
│ ✓ Question 1: Correct!                             │ ← Light green background
│   Well done! This is the right answer.             │   Dark green "Question 1:"
│   [Show in transcript]                             │   Green checkmark icon
└─────────────────────────────────────────────────────┘
  ↑ Green left border (4px)
```

### 2. Question Feedback (Incorrect Answer)
```
┌─────────────────────────────────────────────────────┐
│ ✗ Question 1: The correct answer is: LONDON       │ ← Light red background
│   Review the transcript to find where this is      │   Dark red "Question 1:"
│   mentioned. [Show in transcript]                  │   Red cross icon
└─────────────────────────────────────────────────────┘
  ↑ Red left border (4px)
```

### 3. Transcript Answer Highlighting (Before vs After)

**Before (Version 11.9):**
```
Woman: [Q1] ← Yellow Q badge
[Yes of course. It's Anne Hawberry. I've been living]
└─ Yellow highlight (entire sentence up to 100 chars)
```
Problem: Highlights too much text, including "Yes of course" which isn't the answer

**After (Version 11.10):**
```
Woman: It's [Q1] ← Yellow Q badge
              [Anne Hawberry] ← Yellow highlight (stops at comma)
              , and I've been living...
```
Solution: Only highlights the actual answer "Anne Hawberry"

## Affected Question Types and Features

### Question Feedback Styling
- **Open questions** with multiple fields
- Questions that display per-field feedback (e.g., "Question 1:", "Question 2:", etc.)
- Primarily affects listening and reading tests with multi-field answers

### Transcript Highlighting
- **All listening tests** with `[Q#]` markers in transcripts
- **Reading passages** with `[Q#]` markers (computer-based tests)
- Affects both practice tests and exercises
- Works in both computer-based and standard quiz layouts

## Benefits

1. **Better Visual Hierarchy:** Feedback messages are now clearly distinguished from surrounding content
2. **Improved Readability:** Background colors and padding make feedback easier to read
3. **Clearer Status Indication:** Color-coded backgrounds immediately communicate correct vs. incorrect
4. **Consistent Design:** Matches the styling of other feedback elements in the quiz interface
5. **Professional Appearance:** Polished look that enhances the learning experience
6. **Accurate Answer Highlighting:** Students can now immediately see the exact answer in transcripts
7. **Reduced Confusion:** No more highlighting of irrelevant text around answers
8. **Better Learning Experience:** Students can quickly locate and verify answers in transcripts

## Browser Compatibility

- All modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile browsers (iOS Safari, Chrome Mobile)
- Responsive design maintained

## Upgrade Instructions

1. Update plugin files via WordPress admin or FTP
2. Clear browser cache to see new styles
3. Clear any caching plugins (e.g., WP Super Cache, W3 Total Cache)
4. Test with a quiz that has open questions with multiple fields

## Testing Recommendations

Test the following scenarios:

### Question Feedback
1. Submit quiz with all correct answers → Check green backgrounds appear
2. Submit quiz with some incorrect answers → Check red backgrounds appear
3. Submit quiz with no answers → Check red backgrounds with "correct answer is" messages
4. View on mobile devices → Check feedback remains readable
5. View in different browsers → Check styling consistency

### Transcript Highlighting
1. View transcript with `[Q1]` marker before short answer → Check only answer is highlighted
2. View transcript with `[Q1]` marker before long sentence → Check highlighting stops at comma/semicolon
3. Click "Show in transcript" link → Check highlighting appears on correct text
4. Test with answers of varying lengths (1 word, 2-3 words, 5+ words)
5. Verify yellow Q badge appears next to highlighted answer

## Known Issues

None

## Future Enhancements

Potential improvements for future versions:
- Add animation when feedback appears
- Allow customization of feedback colors via admin panel
- Add option to show/hide icons
- Support for RTL languages

## Support

If you encounter any issues with the new feedback styling:
1. Clear all caches (browser and server-side)
2. Check browser console for JavaScript errors
3. Verify CSS file loaded correctly (check Network tab in DevTools)
4. Contact support with screenshots showing the issue

---

**Version:** 11.10  
**Previous Version:** 11.9  
**Changelog Category:** Visual/UI Enhancement  
**Priority:** Medium  
**Backward Compatible:** Yes
