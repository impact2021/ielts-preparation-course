# Version 15.44 Release Notes

## Date: February 10, 2026

## Summary
This release fixes the navigation link display issue where the "Move to next unit" button was not visible at the end of units.

## Bug Fixes

### Next Unit Link Not Displaying
**Issue:** When users completed the last lesson of a unit, they were seeing the message "That is the end of this unit" but the clickable link to move to the next unit was not visible, even though it existed in the HTML code.

**Root Cause:** The CSS for `.nav-completion-message` was using `display: flex` without `flex-direction: column`, which prevented the button from displaying properly below the completion message text.

**Fix:** Updated the CSS styling for `.nav-completion-message` to:
- Added `flex-direction: column` to stack elements vertically
- Added `gap: 10px` for proper spacing between message and button
- Added specific styling for `.nav-completion-message a.button` to override inline margins

**Files Changed:**
- `assets/css/frontend.css` - Updated `.nav-completion-message` styling

**Impact:** Users can now properly see and click the link to move to the next unit after completing all lessons in a unit.

## Technical Details

### CSS Changes
```css
.nav-completion-message {
    display: flex;
    flex-direction: column;  /* NEW: Stack vertically */
    align-items: center;
    justify-content: center;
    gap: 10px;              /* NEW: Add spacing */
    padding: 8px 16px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    color: #155724;
    font-weight: 600;
    font-size: 13px;
    text-align: center;
}

.nav-completion-message a.button {
    margin-top: 0;          /* NEW: Override inline margins */
}
```

## User Experience Improvements
- Users completing a unit can now immediately navigate to the next unit
- Clear visual separation between completion message and navigation button
- Consistent spacing and alignment

## Testing Recommendations
1. Complete the last lesson of any unit
2. Verify the "That is the end of this unit" message appears
3. Verify the "Move to Unit X" button is visible and clickable
4. Click the button and verify navigation to the next unit works
5. Test on both desktop and mobile devices

## Version History
- Previous: 15.43
- Current: 15.44
