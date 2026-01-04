# Fix for Exercise Navigation Height on Extended Monitors

## Issue Description
When an IELTS exercise is opened on a laptop and then the window is moved to an extended monitor with a different screen size, the question navigation doesn't resize properly. The reading and question columns retain the height from the original laptop display and don't adjust to the larger monitor's viewport.

## Root Cause
The CSS `vh` (viewport height) unit is calculated once when the page loads based on the initial viewport size. When the browser window is moved to a different monitor with a different resolution, the `vh` value doesn't automatically recalculate, causing the fixed `max-height` values to remain at the original size.

Affected CSS in `frontend.css`:
```css
.reading-column, .questions-column {
    max-height: calc(100vh - 300px);
}
```

## Solution
Added JavaScript resize event handler in `frontend.js` that:

1. **Dynamically calculates heights** using `window.innerHeight` instead of CSS `vh` units
2. **Applies responsive offsets** based on screen width and focus mode state
3. **Automatically updates** when window is resized or moved to different monitors
4. **Debounces resize events** to avoid excessive recalculations (100ms delay)

### Implementation Details

The fix adds a `updateDynamicHeights()` function that:

```javascript
function updateDynamicHeights() {
    var vh = window.innerHeight;
    var isFocusMode = $('body').hasClass('ielts-quiz-focus-mode');
    
    // Calculate appropriate offset based on focus mode and screen size
    var offset;
    if (isFocusMode) {
        if (window.innerWidth <= 768) {
            offset = 220; // Mobile focus mode
        } else if (window.innerWidth <= 1024) {
            offset = 200; // Tablet focus mode
        } else {
            offset = 180; // Desktop focus mode
        }
    } else {
        if (window.innerWidth <= 768) {
            offset = 450; // Mobile normal mode
        } else if (window.innerWidth <= 1024) {
            offset = 400; // Tablet normal mode
        } else {
            offset = 300; // Desktop normal mode
        }
    }
    
    var maxHeight = vh - offset;
    
    // Apply the calculated height to the columns
    $('.reading-column, .questions-column, .listening-audio-column').css('max-height', maxHeight + 'px');
}
```

This function is:
- Called once on page load
- Called on every window resize event (debounced)
- Automatically detects monitor changes via the resize event

### Affected Layouts
This fix applies to:
- Computer-based reading exercises (`.ielts-computer-based-quiz`)
- Listening practice exercises (`.ielts-listening-practice-quiz`)
- Listening exercise quizzes (`.ielts-listening-exercise-quiz`)

## Testing
To test this fix:

1. Open an IELTS exercise on your laptop
2. Note the height of the reading/question columns
3. Move the browser window to an external monitor with different resolution
4. The columns should automatically resize to fit the new viewport
5. Try resizing the browser window - columns should adjust in real-time

### Test File
A standalone test file has been created at `/tmp/test-resize.html` that demonstrates the fix without requiring WordPress. Open this file in a browser and move it between monitors to see the dynamic resizing in action.

## Browser Compatibility
This solution works in all modern browsers that support:
- `window.innerHeight` (all modern browsers)
- `window.addEventListener('resize')` (all modern browsers)
- jQuery (already included in the plugin)

## Performance Considerations
- Resize events are debounced with a 100ms delay to prevent excessive recalculations
- Height calculation is a simple arithmetic operation with minimal performance impact
- No DOM manipulation occurs except setting the `max-height` CSS property

## Files Modified
- `/assets/js/frontend.js` - Added dynamic height recalculation logic

## Backward Compatibility
This fix is fully backward compatible:
- Original CSS values remain as fallback for browsers without JavaScript
- No changes to HTML structure or CSS classes
- Works alongside existing responsive breakpoints

## Future Improvements
Consider using CSS custom properties (CSS variables) for even better performance:
```css
.reading-column, .questions-column {
    max-height: var(--column-max-height, calc(100vh - 300px));
}
```
Then update the CSS variable via JavaScript instead of inline styles.
