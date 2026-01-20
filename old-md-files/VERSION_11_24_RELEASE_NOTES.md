# Version 11.24 Release Notes

## Bug Fix: Question Navigation Scrolling Requires Double-Click

### Issue Description
Question navigation buttons in CBT quizzes (reading tests, listening tests, etc.) required a double-click to scroll properly when navigating back up to previous questions:

**Symptoms:**
1. Click Q3 → scrolls down correctly ✓
2. Click Q1 (going back up) → doesn't scroll or scrolls incorrectly ✗
3. Click Q1 again (double-click) → now it scrolls correctly ✓
4. Single click with delay, then another click also works (but not first click)

This issue specifically affected upward navigation (going from a lower question to a higher question).

### Root Cause
The `scrollToQuestion()` function in `assets/js/frontend.js` calculated element positions immediately when the button was clicked. However, when the browser was still processing layout updates from previous scrolling operations, these position calculations would be based on stale layout information.

**The Problem:**
```javascript
function scrollToQuestion(questionElement) {
    var questionsColumn = $('.questions-column');
    
    if (questionsColumn.length && questionElement.length) {
        // Position calculations happen IMMEDIATELY
        var questionAbsoluteTop = questionElement.offset().top;  // ← May use stale layout!
        var columnAbsoluteTop = questionsColumn.offset().top;
        var columnScrollTop = questionsColumn.scrollTop();
        // ... rest of calculation
    }
}
```

When clicking rapidly or when the browser hasn't finished rendering:
- First click: Uses stale positions → incorrect scroll calculation
- Second click: Browser has finished layout updates → correct positions → works!

This explains why double-clicking worked but single clicks didn't.

### Solution
Defer position calculations to the next animation frame using `requestAnimationFrame()`. This ensures the browser completes any pending layout updates before we calculate scroll positions.

**The Fix:**
```javascript
function scrollToQuestion(questionElement) {
    var questionsColumn = $('.questions-column');
    
    // Defer position calculations to next animation frame to ensure layout is stable
    // This fixes the issue where scrolling back up requires a double-click
    // The browser needs to finish any pending layout updates before positions are accurate
    requestAnimationFrame(function() {
        if (questionsColumn.length && questionElement.length) {
            // Position calculations now happen AFTER browser updates layout
            var questionAbsoluteTop = questionElement.offset().top;  // ← Now uses fresh layout!
            var columnAbsoluteTop = questionsColumn.offset().top;
            var columnScrollTop = questionsColumn.scrollTop();
            // ... rest of calculation
        }
    });
}
```

**Why `requestAnimationFrame()` works:**
1. `requestAnimationFrame()` schedules the callback to run before the next browser repaint
2. This gives the browser time to finish any pending layout recalculations
3. By the time our position calculations run, the DOM layout is stable and current
4. This ensures accurate positions on the first click, eliminating the need for double-clicking

### Technical Details

**What is `requestAnimationFrame()`?**
- A browser API that schedules a function to run before the next repaint
- Typically runs at 60fps (every ~16ms)
- Ensures the browser has completed layout updates before the callback executes
- More reliable than `setTimeout(0)` for layout-dependent code

**Why the double-click worked:**
- First click: Triggers scroll with stale positions (doesn't work correctly)
- Browser updates layout (~16ms later)
- Second click: Now uses fresh positions (works correctly!)
- The delay between clicks gave the browser time to update layout

**Why single click with delay worked:**
- First click: Triggers scroll with stale positions
- User waits (browser updates layout during wait)
- Second click: Uses fresh positions (works!)

**Why our fix works on first click:**
- Click happens
- `requestAnimationFrame()` schedules position calculation
- Browser updates layout (~0-16ms)
- Position calculation runs with fresh layout
- Scroll animation uses correct positions ✓

### Testing Recommendations

**Test Case 1: Upward Navigation (Primary Issue)**
1. Load a reading test with 3+ questions
2. Click Q3 (scroll down)
3. Click Q1 (scroll up) - should work on FIRST click
4. Click Q3 (scroll down) - should work on first click
5. Click Q2 (scroll up) - should work on first click

**Expected:** All scrolling works correctly on first click, no double-clicking needed.

**Test Case 2: Rapid Navigation**
1. Click Q1, Q2, Q3, Q1, Q3, Q2 rapidly (< 1 second between clicks)
2. **Expected:** All scrolling works correctly despite rapid clicking

**Test Case 3: Random Navigation Pattern**
1. Click buttons in random order: Q2 → Q1 → Q3 → Q1 → Q2
2. **Expected:** Every click scrolls correctly on first attempt

**Test Case 4: Edge Cases**
1. Test with first question (Q1)
2. Test with last question (Q10+)
3. Test with middle questions
4. **Expected:** All positions calculated correctly

### Regression Testing
Verify existing functionality still works:
- [ ] Question navigation buttons work on first click
- [ ] Double-clicking doesn't cause issues (should just scroll to same position)
- [ ] Reading passage switching still works
- [ ] Audio section switching still works (for listening tests)
- [ ] Question highlighting works
- [ ] "Review my answers" button still scrolls correctly
- [ ] No JavaScript console errors

### Performance Impact
- **Minimal performance impact**: `requestAnimationFrame()` adds < 16ms delay (one frame)
- **User-imperceptible**: The delay is far less than human reaction time (~200ms)
- **Better UX**: Eliminates need for double-clicking, making the interface more responsive
- **No memory impact**: No additional memory usage or event listeners

### Files Changed
- `assets/js/frontend.js` (+4 lines of comments, wrapped existing code in `requestAnimationFrame()`)
- `ielts-course-manager.php` (+2 lines, -2 lines: version update from 11.23 to 11.24)
- `VERSION_11_24_RELEASE_NOTES.md` (new file)

### Browser Compatibility
`requestAnimationFrame()` is supported by all modern browsers:
- ✅ Chrome (all versions used in past 10 years)
- ✅ Firefox (all versions used in past 10 years)
- ✅ Safari (all versions used in past 10 years)
- ✅ Edge (all versions)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile, Firefox Mobile)

No fallback needed - this is a standard web API available since 2011.

### Impact
- ✅ Fixes scrolling on first click (eliminates double-click requirement)
- ✅ Works for both upward and downward navigation
- ✅ Improves UX by making navigation feel more responsive
- ✅ No breaking changes - only enhances existing functionality
- ✅ Minimal code change (surgical fix)
- ✅ No new dependencies or libraries required

### Related Fixes
This fix builds on previous scrolling improvements:
- **v11.22**: Fixed cumulative scroll errors using absolute positioning (replaced `position().top` with `offset().top`)
- **v11.23**: Added automatic scrolling when "Review my answers" is clicked
- **v11.24**: Fixed double-click requirement by deferring position calculations

Together, these fixes ensure **all scrolling in CBT quizzes is reliable, predictable, and works on first click**.

---

## Explanation of the Fix

The key insight is that browser layout updates are asynchronous. When you scroll or change the DOM, the browser doesn't immediately update element positions. It schedules a layout update for the next repaint.

**Before the fix:**
```
User clicks Q1 → Calculate positions → Scroll → (browser updates layout later)
                   ↑
                   Uses stale positions from previous scroll!
```

**After the fix:**
```
User clicks Q1 → Schedule calculation → Browser updates layout → Calculate positions → Scroll
                                       ↑
                                       Fresh positions! ✓
```

By using `requestAnimationFrame()`, we ensure calculations happen **after** the browser finishes layout updates, giving us accurate positions on the first try.

---

## Version History Context

- **v11.22**: Fixed question navigation using absolute positioning
- **v11.23**: Fixed feedback review scrolling  
- **v11.24**: Fixed double-click requirement for upward navigation

These versions together provide a complete, robust scrolling solution for CBT quizzes.
