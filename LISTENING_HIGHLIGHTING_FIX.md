# Listening Test Answer Highlighting Fix

## Issue
User reported: "Why does this keep disappearing?" and "The highlighting should be exactly the same as the reading?"

## Problem Description

In IELTS Listening Test 01, the answer highlighting was inconsistent across different sections:

- **Section 1** used: `<span class="listening-answer-marker">the answer section is here</span>`
- **Sections 2-4** used: `<strong style="background-color: yellow">[Q#: answer]</strong>`

The `.listening-answer-marker` class did not exist in the CSS file, causing the highlighted text in Section 1 to appear transparent/invisible (effectively "disappearing"), while sections 2-4 displayed yellow highlighting via inline styles.

## Root Cause

The JSON data for Listening Test 01 Section 1 referenced a CSS class (`.listening-answer-marker`) that was never defined in the stylesheet (`assets/css/frontend.css`).

## Solution

Added CSS rules for the `.listening-answer-marker` class to ensure consistent highlighting behavior:

```css
/* Hide answer marker highlighting initially (both listening and reading) */
.transcript-answer-marker,
.reading-answer-marker,
.listening-answer-marker {
    background-color: transparent; /* No highlighting before submission */
}

/* Show answer marker highlighting after quiz submission (ONLY for listening tests - transcripts) */
.quiz-submitted[data-test-type="listening"] .reading-answer-marker,
.quiz-submitted[data-test-type="listening"] .listening-answer-marker {
    background-color: #fff9c4; /* Yellow highlight after submission for listening tests */
}
```

## Expected Behavior

After this fix:

1. **Before Quiz Submission**: All answer markers (`.listening-answer-marker`, `.reading-answer-marker`, `.transcript-answer-marker`) are transparent
2. **After Quiz Submission (Listening Tests)**: All answer markers are highlighted with yellow background (#fff9c4)
3. **Consistency**: Section 1 now matches the highlighting behavior of Sections 2-4

## Files Modified

- `assets/css/frontend.css`: Added `.listening-answer-marker` class styling

## Testing Recommendations

1. Load IELTS Listening Test 01
2. Start the test and submit answers
3. After submission, verify that:
   - Section 1 transcript shows yellow highlighting on answer markers
   - All sections (1-4) have consistent yellow highlighting
   - The highlighting matches the inline `style="background-color: yellow"` behavior in other sections

## Impact

- Only affects IELTS Listening Test 01 (only test using `.listening-answer-marker` class)
- No breaking changes to other tests
- Maintains backward compatibility with existing inline styles and `.transcript-answer-marker` class
