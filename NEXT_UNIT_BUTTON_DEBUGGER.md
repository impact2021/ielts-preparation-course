# Next Unit Button Visual Debugger

## Overview

The Next Unit Button Visual Debugger is an on-page debugging tool that helps diagnose why the "Move to next unit" button may not be appearing at the end of a unit. This debugger provides a comprehensive, visual explanation of the logic that determines button visibility.

## Problem It Solves

When users complete the last resource or quiz in the last lesson of a unit, they should see a button to move to the next unit. However, if this button doesn't appear, it can be difficult to understand why. This debugger shows **exactly** why the button is or isn't showing by displaying:

- All the variables and conditions involved
- A step-by-step decision tree
- Lists of all lessons and units
- Clear explanations of what's expected

## How to Enable the Debugger

There are two ways to enable the visual debugger:

### Method 1: URL Parameter (Recommended for Testing)

Add `?debug_nav=1` to the URL of any quiz or resource page:

```
https://yoursite.com/quiz-or-resource-page/?debug_nav=1
```

This method is perfect for:
- Quick testing
- Sharing debug information with support
- One-time debugging without code changes

### Method 2: WordPress Configuration (For Persistent Debugging)

Add this line to your `wp-config.php` file:

```php
define('IELTS_CM_DEBUG_NAV', true);
```

This method is useful for:
- Development environments
- Ongoing debugging
- Systematic testing across multiple pages

**Remember to remove or set to `false` when done debugging!**

## What the Debugger Shows

The debugger displays several sections:

### 1. Current State
Shows the basic information about the current page:
- Quiz/Resource ID
- Course ID
- Lesson ID
- Whether there's a next item in the lesson

### 2. Button Logic Check
Shows the two critical variables that determine button visibility:
- **Is Last Lesson**: Whether this is the last lesson in the unit
- **Has Next Unit**: Whether a next unit exists

### 3. Decision Tree
A visual flow showing the logic:
- ✓ Green boxes = Conditions that are true
- ✗ Red boxes = Conditions that are false

This makes it easy to see exactly which condition is preventing the button from showing.

### 4. Expected Result
A clear explanation box that tells you:
- **✓ BUTTON SHOULD BE VISIBLE** - Shows when the button should appear and why
- **⚠ BUTTON NOT SHOWN** - Explains why the button isn't showing
- **ℹ REGULAR NAVIGATION** - Indicates when regular "Next" navigation applies

If the button should be visible but isn't, this section provides troubleshooting steps.

### 5. All Lessons in Course
Shows all lessons in the current unit/course:
- Lessons are listed in order
- Current lesson is highlighted
- Last lesson is marked with a "LAST LESSON" badge

### 6. All Units
Shows all units in the system:
- Units are listed in order
- Current unit is highlighted
- Next unit (if found) is marked

## Understanding the Logic

The "Move to next unit" button appears when ALL of these conditions are true:

1. **No next item in lesson** - This is the last resource/quiz in the lesson
2. **Is last lesson** - This is the last lesson in the unit
3. **Has next unit** - A next unit exists in the course

If any condition is false, the button won't appear:

- If there's a next item → Shows regular "Next" button
- If not last lesson → Shows "You have finished this lesson"
- If no next unit → Shows "That is the end of this unit" (no button)

## Common Issues and Solutions

### Issue: Button should show but doesn't

**Debugger shows**: ✓ BUTTON SHOULD BE VISIBLE

**Possible causes**:
1. CSS file not loaded properly
2. Custom theme CSS hiding the button
3. JavaScript error preventing rendering
4. Caching issue

**Solutions**:
1. Check browser developer tools Console for errors
2. Check Network tab to ensure `frontend.css` is loaded
3. Temporarily disable custom theme CSS
4. Clear browser and WordPress cache
5. Check that `.button` and `.button-primary` classes are styled

### Issue: Shows "not last lesson" but should be

**Debugger shows**: ✗ NOT the last lesson in unit

**Check**:
1. Look at "All Lessons in Course" section
2. Verify which lesson is marked "LAST LESSON"
3. Ensure lessons are ordered correctly (check menu_order in database)
4. Verify the lesson belongs to the correct course

### Issue: No next unit found

**Debugger shows**: ✗ No next unit found

**Check**:
1. Look at "All Units" section
2. Verify there is a unit after the current one
3. Ensure units are published (not draft)
4. Check units are ordered correctly (check menu_order)

## Example Scenarios

### Scenario 1: Button Working Correctly

```
Decision Tree:
✓ No next item in lesson (last resource/quiz in lesson)
✓ This is the last lesson in the unit
✓ Next unit found → BUTTON SHOULD BE VISIBLE

Expected Result:
✓ BUTTON SHOULD BE VISIBLE
The "Move to Unit 2" button should appear because:
- This is the last resource/quiz in the lesson
- This is the last lesson in the unit
- A next unit exists (Academic Unit 2)
```

### Scenario 2: Not Last Lesson

```
Decision Tree:
✓ No next item in lesson (last resource/quiz in lesson)
✗ NOT the last lesson in unit → Shows "You have finished this lesson"

Expected Result:
⚠ BUTTON NOT SHOWN (Not Last Lesson)
Shows "You have finished this lesson" because:
- This is the last resource/quiz in the lesson
- But this is NOT the last lesson in the unit
```

### Scenario 3: Last Unit in Course

```
Decision Tree:
✓ No next item in lesson (last resource/quiz in lesson)
✓ This is the last lesson in the unit
✗ No next unit found → Only shows completion message

Expected Result:
⚠ BUTTON NOT SHOWN (No Next Unit)
Only completion message shows because:
- This is the last resource/quiz in the lesson
- This is the last lesson in the unit
- But there is no next unit (this is the last unit in the course)
```

## Reporting Issues

When reporting issues about the button not appearing, please:

1. Enable the debugger with `?debug_nav=1`
2. Take a screenshot of the entire debugger panel
3. Include the URL of the page
4. Note what you expected to see vs. what you actually see

The debugger provides all the information needed to diagnose the issue quickly.

## Technical Details

### Templates Modified

The debugger is integrated into three templates:
1. `templates/single-quiz.php` - Regular quizzes
2. `templates/single-quiz-computer-based.php` - Computer-based quizzes
3. `templates/single-resource-page.php` - Resource pages

### CSS Classes

The debugger uses these CSS classes (defined in `assets/css/frontend.css`):
- `.ielts-nav-debugger` - Main container
- `.debugger-section` - Each section
- `.debugger-table` - Data tables
- `.decision-step` - Decision tree items
- `.result-box` - Expected result boxes

### Security

The debugger:
- Only shows when explicitly enabled
- Uses WordPress escaping functions for all output
- Uses prepared statements for database queries
- Does not expose sensitive user data
- Does not allow modification of data

## Disabling the Debugger

### For URL Parameter Method
Simply remove `?debug_nav=1` from the URL or visit the page without the parameter.

### For Configuration Method
1. Open `wp-config.php`
2. Remove the line: `define('IELTS_CM_DEBUG_NAV', true);`
3. Or change to: `define('IELTS_CM_DEBUG_NAV', false);`

## Version Information

- **Added in**: Version TBD
- **Related to**: Next Unit Button visibility issues
- **Previous fixes**: v15.44 (CSS), v15.47 (HTML button classes)

## Support

If the debugger doesn't help you identify the issue, please contact support with:
- Screenshot of the debugger output
- URL of the problematic page
- Description of expected vs actual behavior
- Browser and WordPress version

The debugger output will help support quickly identify and resolve the issue.
