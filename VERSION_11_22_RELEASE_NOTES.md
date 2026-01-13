# Version 11.22 Release Notes

## Bug Fix: Question Navigation Scrolling in Reading Tests

### Issue Description
Question navigation buttons in Academic Reading Tests (and other CBT quizzes) had incorrect scrolling behavior that was state-dependent:

**Symptoms:**
1. Click Q1 → centered correctly ✓
2. Click Q2 → scrolls down and centered correctly ✓
3. Click Q3 → scrolls too far down, user must scroll back up to see it ✗
4. Click Q1 again → scrolls DOWN instead of UP ✗
5. **Critical observation:** If Q3 is clicked FIRST (without clicking Q1/Q2 first), it aligns perfectly

### Root Cause
The scrolling calculation in `assets/js/frontend.js` (lines 1154-1159) used `jQuery.position().top` which returns the element's position relative to its offset parent's **current scroll state**. This caused cumulative errors:

**Old Code:**
```javascript
var questionOffset = questionElement.position().top; // ← Position relative to CURRENT scroll state
var columnScrollTop = questionsColumn.scrollTop();
questionsColumn.animate({
    scrollTop: columnScrollTop + questionOffset - 50
}, 300);
```

The problem: `position().top` changes based on the current scroll position, so:
- After scrolling to Q2, `position().top` for Q3 is calculated from Q2's scroll position
- This creates cumulative positioning errors
- Clicking Q1 after Q3 adds to the already-incorrect scroll position, moving DOWN instead of UP

### Solution
Changed the scroll calculation to use absolute positioning within the container:

**New Code:**
```javascript
// Get absolute position within the scrollable container
var questionAbsoluteTop = questionElement.offset().top;        // Absolute from document top
var columnAbsoluteTop = questionsColumn.offset().top;          // Column's absolute position
var columnScrollTop = questionsColumn.scrollTop();             // Current scroll position
// Calculate the question's TRUE position within scrollable content
var questionPositionInContainer = questionAbsoluteTop - columnAbsoluteTop + columnScrollTop;

// Calculate target scroll position to center the question
var columnHeight = questionsColumn.height();
var questionHeight = questionElement.outerHeight();
var targetScrollTop = questionPositionInContainer - (columnHeight / 2) + (questionHeight / 2);

questionsColumn.animate({
    scrollTop: targetScrollTop
}, 300);
```

**Key improvements:**
1. Uses `offset().top` for absolute positioning (not affected by scroll state)
2. Calculates true position within scrollable container
3. Centers the question in the viewport (better UX than the old `-50` offset)
4. Consistent behavior regardless of previous scroll operations

### Technical Details

**Why `position().top` failed:**
- `position().top` returns position relative to the offset parent
- When the parent has already been scrolled, this value doesn't represent the true position in the scrollable content
- Each scroll operation changed the reference point for the next calculation

**Why `offset().top` works:**
- `offset().top` returns position relative to the document, which is fixed
- By subtracting the column's offset and adding current scroll, we get the true position within the content
- This value is consistent regardless of how many times we've scrolled

### Testing Recommendations
Test the following sequence to verify the fix:
1. Click Q1 → should center in viewport
2. Click Q2 → should center in viewport
3. Click Q3 → should center in viewport (not scroll too far)
4. Click Q1 → should scroll UP and center in viewport
5. Click Q3 → should scroll DOWN and center in viewport
6. Click Q2 → should center in viewport (between Q1 and Q3)

Also test:
- Click Q3 FIRST → should center perfectly (confirms fix doesn't break clean-state navigation)
- Random navigation pattern (Q2, Q1, Q3, Q2, Q1) → all should center correctly

### Files Changed
- `assets/js/frontend.js` - Fixed scroll calculation in question navigation handler
- `ielts-course-manager.php` - Updated version to 11.22

### Impact
- ✅ Fixes scrolling for all CBT quiz types (reading tests, listening tests, etc.)
- ✅ Improves UX by centering questions instead of using arbitrary offset
- ✅ State-independent - works the same whether it's the first click or the hundredth
- ✅ No breaking changes - only affects scroll behavior
