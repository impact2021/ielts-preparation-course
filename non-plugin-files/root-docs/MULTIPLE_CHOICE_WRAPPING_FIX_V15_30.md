# Multiple Choice Text Wrapping Fix - Version 15.30

## Issue
Multiple choice question options with long text were not wrapping properly. Instead of text wrapping neatly next to the radio button, the radio button would sit on one line and the text would drop below it.

**Before Fix:**
```
○
This is a very long multiple choice option text that extends beyond the container width
```

**After Fix:**
```
○ This is a very long multiple choice option text that extends
  beyond the container width
```

## Root Cause

The CSS for option text spans used `flex-shrink: 1` instead of `flex: 1`. This prevented the text from properly growing to fill available space in the flexbox container, causing improper wrapping behavior.

## Solution

Changed the flex property from `flex-shrink: 1` to `flex: 1` for all quiz types:

### Files Modified
- `assets/css/frontend.css`

### CSS Changes

**Standard Quiz:**
```css
.ielts-single-quiz .option-label > span {
    flex: 1;                /* Changed from flex-shrink: 1 */
    min-width: 0;          /* Allows text to wrap in flexbox */
    word-wrap: break-word; /* Breaks long words if needed */
}
```

**Computer-Based Quiz:**
```css
.ielts-computer-based-quiz .option-label > span {
    flex: 1;                /* Changed from flex-shrink: 1 */
    min-width: 0;
    word-wrap: break-word;
}
```

**Listening Practice & Exercise Quizzes:**
```css
.ielts-listening-practice-quiz .option-label > span,
.ielts-listening-exercise-quiz .option-label > span {
    flex: 1;                /* Changed from flex-shrink: 1 */
    min-width: 0;
    word-wrap: break-word;
}
```

## How It Works

### Flexbox Layout
The option label uses flexbox with these key properties:
- **Container:** `display: flex` with `gap: 10px`
- **Radio button:** `flex-shrink: 0` (keeps fixed size)
- **Text span:** `flex: 1` (grows to fill remaining space)

### The `flex: 1` Property
- `flex: 1` is shorthand for `flex: 1 1 0%`
  - `flex-grow: 1` - Element will grow to fill available space
  - `flex-shrink: 1` - Element can shrink if needed
  - `flex-basis: 0%` - Start from 0 width, then grow

This ensures the text takes up all remaining space after the radio button, allowing proper wrapping.

### Why `min-width: 0` is Critical
In flexbox, items have an implicit minimum width based on content. Without `min-width: 0`, the flex item won't shrink below its content width, preventing text from wrapping. Setting `min-width: 0` overrides this and allows proper text wrapping.

## Quiz Types Fixed

All quiz types now have consistent text wrapping:
- ✅ Standard Quiz (`.ielts-single-quiz`)
- ✅ Computer-Based Quiz (`.ielts-computer-based-quiz`)
- ✅ Listening Practice Quiz (`.ielts-listening-practice-quiz`)
- ✅ Listening Exercise Quiz (`.ielts-listening-exercise-quiz`)

## Testing

To verify the fix:

1. **Short Options:** Radio button appears on left, text on same line
2. **Long Single-Line Options:** Text wraps to multiple lines, radio stays top-left
3. **Very Long Words:** Words break if necessary to fit container
4. **Multiple Options:** All options wrap consistently
5. **Responsive:** Works at all screen widths

## Browser Compatibility

All CSS properties used are widely supported:
- `flex: 1` - All modern browsers, IE 10+
- `min-width: 0` - All modern browsers, IE 10+
- `word-wrap: break-word` - All browsers, IE 5.5+

## Impact

- **Visual Improvement:** Clean, professional appearance
- **Readability:** Text flows naturally next to radio buttons
- **Consistency:** All quiz types behave the same way
- **Accessibility:** Better for screen readers (text stays with input)
- **No Breaking Changes:** Purely visual CSS fix

## Version Information

- **Version:** 15.30
- **Previous Version:** 15.29
- **File Changed:** assets/css/frontend.css (3 rule changes)
- **Lines Changed:** 3 (one per quiz type)

## Related Documentation

This fix complements the previous radio button alignment fix documented in `old-md-files/RADIO_BUTTON_ALIGNMENT_FIX.md`, which addressed the vertical alignment of radio buttons. This fix ensures the horizontal layout and text wrapping work correctly.
