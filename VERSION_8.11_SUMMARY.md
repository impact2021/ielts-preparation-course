# Version 8.11 Summary

## Release Date
December 28, 2024

## Overview
Version 8.11 is a bug fix release that addresses feedback highlighting issues in the Computer-Based Test (CBT) layout.

## Issue Fixed
**Feedback Highlighting Not Visible in CBT Layout**

Previously, when users submitted answers in the CBT layout (particularly with listening exercises), there was no clear color-based visual feedback to indicate which answers were correct or incorrect. Only text feedback was shown, making it difficult for users to quickly identify the correct answers.

### Problem Details
- The CSS classes for answer feedback (`.answer-correct`, `.answer-incorrect`, `.answer-correct-highlight`) were defined but not being applied properly in CBT layouts
- The base `.ielts-computer-based-quiz .option-label` styles were overriding the feedback color styles due to CSS specificity issues
- Hover states could potentially interfere with feedback colors after submission

## Changes Made

### 1. CSS Enhancements (frontend.css)

#### Added CBT-specific feedback styles
```css
/* Ensure correct answer highlighting works in CBT layout */
.ielts-computer-based-quiz .question-options .option-label.answer-correct {
    background: #4caf50 !important;
    border: 3px solid #4caf50 !important;
    color: #fff !important;
}

/* Ensure incorrect answer highlighting works in CBT layout */
.ielts-computer-based-quiz .question-options .option-label.answer-incorrect {
    background: #f44336 !important;
    border: 3px solid #f44336 !important;
    color: #fff !important;
}
```

#### Prevented hover state interference
```css
/* Prevent hover from overriding feedback colors */
.ielts-computer-based-quiz .option-label.answer-correct:hover,
.ielts-computer-based-quiz .option-label.answer-incorrect:hover,
.ielts-computer-based-quiz .option-label.answer-correct-highlight:hover {
    background: inherit !important;
    border-color: inherit !important;
}
```

### 2. Version Updates
- Updated plugin version from 8.10 to 8.11 in plugin header
- Updated `IELTS_CM_VERSION` constant from 8.10 to 8.11

## Impact

### User Experience Improvements
- **Clear Visual Feedback**: Users can now immediately see which answers are correct (green) and incorrect (red) after submission
- **Improved Learning**: Color-coded feedback makes it easier for users to review their mistakes and understand the correct answers
- **Consistent Experience**: Feedback highlighting now works consistently across all quiz layouts (standard, CBT, listening practice, listening exercise)

### Technical Improvements
- **CSS Specificity**: Proper use of `!important` ensures feedback colors always override base styles
- **Maintainability**: Clear comments explain the purpose of each CSS rule
- **Robustness**: Hover state handling prevents accidental color changes during review

## Affected Features
- Computer-Based Test (CBT) layout for reading tests
- Computer-Based Test (CBT) layout for listening tests
- All question types within CBT layout:
  - Multiple choice
  - True/False/Not Given
  - Multi-select
  - Matching questions
  - Headings questions
  - Locating information questions

## Testing Recommendations
1. Test CBT reading exercises with multiple choice questions
2. Test CBT listening exercises with radio button questions
3. Verify green highlighting for correct answers
4. Verify red highlighting for incorrect answers
5. Verify green highlighting for correct answer hints (when user answer is wrong)
6. Confirm hover states don't interfere with feedback colors
7. Test across different browsers (Chrome, Firefox, Safari, Edge)
8. Test on mobile devices

## Backward Compatibility
This is a pure CSS fix with no PHP or JavaScript changes. All existing functionality remains intact:
- No database changes
- No API changes
- No breaking changes
- Fully backward compatible with WordPress 5.8+
- Fully compatible with PHP 7.2+

## Files Modified
1. `assets/css/frontend.css` - Added CBT-specific feedback highlighting styles
2. `ielts-course-manager.php` - Updated version numbers

## Related Issues
- Issue: "Unclear answer.png in the /main/ folder - apart from the text feedback, there's no quick colour based way to see what the correct answer is"
- Affected all CBT layouts with listening radio buttons and other question types

## Future Considerations
None - this is a complete fix for the reported issue.
