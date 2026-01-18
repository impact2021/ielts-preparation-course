# Radio Button Alignment Fix

## Problem
When quiz options with multi-line text wrapped to a second line, the radio button could appear on its own line ABOVE the option text instead of being aligned to the left of the first line.

### Before Fix
```
○
This is a very long option that wraps to
multiple lines
```

### After Fix
```
○ This is a very long option that wraps to
  multiple lines
```

## Solution
The fix involved adding specific CSS properties to ensure proper flexbox behavior:

### Changes Made

1. **Prevent radio button from shrinking**: `flex-shrink: 0`
   - Ensures the radio button maintains its size and doesn't get compressed

2. **Align radio button to top**: `align-self: flex-start`
   - Keeps the radio button aligned at the top of the first line

3. **Allow text to wrap properly**: 
   - `flex: 1` - Text takes up remaining space
   - `min-width: 0` - Critical for allowing text wrapping in flexbox
   - `word-wrap: break-word` - Handles very long words

### Files Modified
- `assets/css/frontend.css`

### Quiz Types Fixed
- Standard Quiz (`.ielts-single-quiz`)
- Computer-Based Quiz (`.ielts-computer-based-quiz`)
- Listening Practice Quiz (`.ielts-listening-practice-quiz`)
- Listening Exercise Quiz (`.ielts-listening-exercise-quiz`)

## Technical Details

### CSS Properties Added

```css
/* Radio button/checkbox styling */
input[type="radio"], 
input[type="checkbox"] {
    flex-shrink: 0;        /* Don't shrink the input */
    align-self: flex-start; /* Align to top of container */
}

/* Text span styling */
.option-label > span {
    flex: 1;               /* Take up remaining space */
    min-width: 0;          /* Allow wrapping in flexbox */
    word-wrap: break-word; /* Break long words */
}
```

### Why `min-width: 0` is Important
In flexbox, items have an implicit minimum size based on their content. Without `min-width: 0`, long text won't wrap properly because the flex item thinks it needs to be wide enough to fit the content. Setting `min-width: 0` overrides this behavior and allows the text to wrap as expected.

### Preserving Feedback Messages
The fix maintains the existing `flex-wrap: wrap` behavior to ensure that feedback messages (`.option-feedback-message`) continue to appear on their own line below the option text when displayed.

## Testing Recommendations

To verify the fix works correctly:

1. **Test with short options**: Radio button should appear on the left
2. **Test with long single-line options**: Text should wrap, radio button stays on first line left
3. **Test with very long words**: Words should break if needed
4. **Test with feedback messages**: Feedback should appear below on its own line
5. **Test on different screen sizes**: Wrapping should work at all viewport widths

## Browser Compatibility
The CSS properties used are widely supported:
- `flex-shrink` - All modern browsers
- `align-self` - All modern browsers  
- `min-width` - All modern browsers
- `word-wrap: break-word` - All modern browsers (IE 5.5+, with fallback)
