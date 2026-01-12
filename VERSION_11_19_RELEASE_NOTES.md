# Version 11.19 Release Notes - Reading Test Feedback Fix

**Release Date:** January 12, 2026  
**Version:** 11.19  
**Previous Version:** 11.18

## Overview

This release fixes the reading test feedback behavior to only show yellow highlighting on ONE section at a time, and ONLY when the user clicks the "Show me the section of the reading passage" button. Previously, all answer sections were showing yellow highlighting after quiz submission.

## Issue Summary

**Problem:** In reading tests, ALL answer sections were displaying yellow highlighting all the time after quiz submission, making it difficult for students to identify which specific section related to their selected answer.

**Solution:** Modified the CSS to keep reading answer markers transparent even after submission, with highlighting only appearing when the user explicitly clicks the "Show me the section" button for a specific question.

## Changes Made

### CSS Changes (`assets/css/frontend.css`)

#### 1. Updated `.reading-answer-marker` Default Style
**Before:**
```css
.reading-answer-marker {
    background-color: #fff9c4; /* Yellow highlight background */
}
```

**After:**
```css
.reading-answer-marker {
    background-color: transparent; /* Transparent by default */
    transition: background-color 0.3s ease; /* Smooth transition */
}
```

#### 2. Updated Post-Submission Behavior
**Before:**
```css
/* Show answer marker highlighting after quiz submission (both listening and reading) */
.quiz-submitted .transcript-answer-marker,
.quiz-submitted .reading-answer-marker {
    background-color: #fff9c4; /* Yellow highlight after submission */
}
```

**After:**
```css
/* Show answer marker highlighting after quiz submission (ONLY for listening tests) */
.quiz-submitted .transcript-answer-marker {
    background-color: #fff9c4; /* Yellow highlight for listening tests */
}

/* For reading tests, keep markers transparent even after submission */
.quiz-submitted .reading-answer-marker {
    background-color: transparent; /* Only highlighted on button click */
}
```

### Version Update (`ielts-course-manager.php`)

Updated plugin version from 11.18 to 11.19.

## How It Works Now

### Reading Test Feedback Flow

1. **Before Quiz Submission:**
   - All reading answer markers are transparent
   - No visual highlighting appears in the reading passage

2. **After Quiz Submission:**
   - Reading answer markers REMAIN transparent
   - Feedback appears in the questions section
   - "Show me the section of the reading passage" buttons are available

3. **When Clicking "Show me the section" Button:**
   - JavaScript removes ALL previous highlights: `$('.reading-text .reading-passage-highlight').removeClass('reading-passage-highlight')`
   - JavaScript adds `.reading-passage-highlight` class to the SPECIFIC section for that question
   - The section displays with yellow background (`#fff9c4`) and subtle shadow
   - Page scrolls to the highlighted section

4. **When Clicking Another Button:**
   - Previous highlight is removed
   - New section is highlighted
   - Page scrolls to the new section
   - **Result:** Only ONE section is highlighted at any time

### Listening Test Feedback (Unaffected)

The listening test feedback behavior remains UNCHANGED because:

1. **Different CSS Classes:**
   - Listening tests use `.transcript-answer-marker` (still shows yellow after submission)
   - Reading tests use `.reading-answer-marker` (transparent after submission)

2. **Different Highlight Classes:**
   - Listening tests use `.transcript-highlight` for button clicks
   - Reading tests use `.reading-passage-highlight` for button clicks

3. **Independent Implementation:**
   - Listening test CSS rules are separate from reading test rules
   - JavaScript handlers are separate functions
   - No shared state between the two systems

## Visual Comparison

### Before Fix
- **Issue:** All reading answer sections showed yellow highlighting after submission
- **Result:** Students couldn't identify which section related to each question
- **User Experience:** Confusing and cluttered

### After Fix
- **Behavior:** No highlighting appears initially after submission
- **On Button Click:** Only ONE section highlights in yellow
- **On Another Click:** Previous highlight removed, new section highlighted
- **User Experience:** Clear and focused feedback

## Technical Implementation

### CSS Specificity
The fix uses CSS specificity to differentiate between reading and listening tests:

```css
/* Base rule - both transparent */
.transcript-answer-marker,
.reading-answer-marker {
    background-color: transparent;
}

/* After submission - different behavior */
.quiz-submitted .transcript-answer-marker {
    background-color: #fff9c4; /* Listening: shown */
}

.quiz-submitted .reading-answer-marker {
    background-color: transparent; /* Reading: hidden */
}

/* On button click - explicit highlight */
.reading-passage-highlight {
    background-color: #fff9c4 !important; /* Reading: shown on click */
    animation: highlightFadeIn 0.5s ease-in-out;
}
```

### JavaScript Implementation (Already Correct)
The existing JavaScript in `frontend.js` already handled highlighting correctly:

```javascript
// Remove all previous highlights
$('.reading-text .reading-passage-highlight').removeClass('reading-passage-highlight');

// Add highlight to specific section
$answerHighlight.addClass('reading-passage-highlight');
```

The issue was purely in the CSS - the JavaScript was working as intended.

## Testing Recommendations

### Reading Test Verification
1. Complete a reading test exercise
2. Submit the quiz
3. **Verify:** No yellow highlighting appears in the reading passage
4. Click "Show me the section of the reading passage" for any question
5. **Verify:** Only that ONE section shows yellow highlighting
6. Click the button for a different question
7. **Verify:** Previous highlight disappears, new section highlights
8. **Verify:** Page scrolls to the highlighted section

### Listening Test Verification
1. Complete a listening test exercise
2. Submit the quiz
3. **Verify:** Yellow highlighting DOES appear in the transcript (unchanged behavior)
4. Click "Show in transcript" for any question
5. **Verify:** Specific transcript section highlights (unchanged behavior)
6. **Verify:** Listening test feedback works exactly as before

## Browser Compatibility

The fix uses standard CSS properties that are supported by all modern browsers:
- `background-color` (universal support)
- `transition` (IE10+, all modern browsers)
- `!important` (universal support)

## Notes for Content Creators

This change is **completely transparent** to content creators:
- No changes needed to existing reading tests
- No changes needed to existing listening tests
- All existing marker formats continue to work
- The fix is purely in the presentation layer (CSS)

## Summary

**What Changed:**
- Reading test answer markers are now transparent by default and after submission
- Yellow highlighting only appears when clicking the "Show me the section" button
- Only ONE section is highlighted at a time

**What Stayed the Same:**
- Listening test behavior (unchanged)
- JavaScript highlighting logic (unchanged)
- All existing tests and content (unchanged)
- "Show me the section" button functionality (unchanged)

**Impact:**
- ✅ Better user experience for reading tests
- ✅ Clearer feedback for students
- ✅ No breaking changes
- ✅ No impact on listening tests
- ✅ No content updates required
