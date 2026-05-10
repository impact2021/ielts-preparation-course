# Next Unit Button Debugger - Visual Guide

## What the Debugger Looks Like

When you add `?debug_nav=1` to the URL, a large yellow debugging panel appears on the page showing comprehensive information about why the "Move to next unit" button is or isn't displayed.

## Visual Layout

```
┌─────────────────────────────────────────────────────────────┐
│  🔍 Next Unit Button Debugger                               │
│  This panel explains why the "Move to next unit" button    │
│  is or isn't showing                                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─ Current State ────────────────────────────────────┐    │
│  │  Quiz ID:           12345                          │    │
│  │  Course ID:         67890                          │    │
│  │  Lesson ID:         11111                          │    │
│  │  Has Next Item:     ✗ NO                           │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  ┌─ Button Logic Check ───────────────────────────────┐    │
│  │  Is Last Lesson:    ✓ TRUE                         │    │
│  │  Has Next Unit:     ✓ YES (ID: 67891 - Unit 2)    │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  ┌─ Decision Tree ────────────────────────────────────┐    │
│  │  ✓ No next item in lesson (last resource/quiz)    │    │
│  │  ✓ This is the last lesson in the unit            │    │
│  │  ✓ Next unit found → BUTTON SHOULD BE VISIBLE     │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  ┌─ Expected Result ──────────────────────────────────┐    │
│  │  ✓ BUTTON SHOULD BE VISIBLE                        │    │
│  │                                                     │    │
│  │  The "Move to Unit 2" button should appear because:│    │
│  │  • This is the last resource/quiz in the lesson    │    │
│  │  • This is the last lesson in the unit             │    │
│  │  • A next unit exists (Academic Unit 2)            │    │
│  │                                                     │    │
│  │  If you don't see the button, check:               │    │
│  │  • CSS is loaded (check browser dev tools)         │    │
│  │  • No custom CSS hiding the button                 │    │
│  │  • The .button and .button-primary classes styled  │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  ┌─ All Lessons in Course (in order) ────────────────┐    │
│  │  1. Lesson 1: Introduction (ID: 10001)            │    │
│  │  2. Lesson 2: Practice (ID: 10002)                │    │
│  │  3. Lesson 3: Advanced Topics (ID: 11111)         │    │
│  │     ← YOU ARE HERE [LAST LESSON]                  │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  ┌─ All Units (in order) ─────────────────────────────┐    │
│  │  1. Academic Unit 1 (ID: 67890) ← CURRENT UNIT     │    │
│  │  2. Academic Unit 2 (ID: 67891) ← NEXT UNIT        │    │
│  │  3. Academic Unit 3 (ID: 67892)                    │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  ┌─ How to use this debugger: ───────────────────────┐    │
│  │  • Add ?debug_nav=1 to URL to enable              │    │
│  │  • Or define IELTS_CM_DEBUG_NAV as true           │    │
│  │  • This shows all logic determining button        │    │
│  │  • Use to report exactly why button isn't showing │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Color Coding

### Header
- **Yellow background** (#fff3cd) with orange border (#ffc107)
- Makes the debugger highly visible on the page

### Success Indicators
- **Green text** (✓) for conditions that are TRUE
- **Green background** for "BUTTON SHOULD BE VISIBLE" results
- Green badges for current/next unit markers

### Error/Warning Indicators
- **Red text** (✗) for conditions that are FALSE
- **Red background** for decision steps that fail
- Red badge for "LAST LESSON" marker

### Information Sections
- **White background** for each section
- Gray borders for separation
- Light gray backgrounds for lesson/unit lists

## Example Scenarios

### Scenario 1: Button Working (All Conditions Met)

```
Decision Tree:
┌─────────────────────────────────────────────┐
│ ✓ No next item in lesson                   │ GREEN
├─────────────────────────────────────────────┤
│ ✓ This is the last lesson in the unit      │ GREEN
├─────────────────────────────────────────────┤
│ ✓ Next unit found → BUTTON SHOULD BE VISIBLE│ GREEN
└─────────────────────────────────────────────┘

Expected Result:
┌────────────────────────────────────────────┐
│ ✓ BUTTON SHOULD BE VISIBLE                 │ GREEN BOX
│                                            │
│ Button appears with correct unit number    │
└────────────────────────────────────────────┘
```

### Scenario 2: Not Last Lesson (Condition Failed)

```
Decision Tree:
┌─────────────────────────────────────────────┐
│ ✓ No next item in lesson                   │ GREEN
├─────────────────────────────────────────────┤
│ ✗ NOT the last lesson in unit              │ RED
│   → Shows "You have finished this lesson"  │
└─────────────────────────────────────────────┘

Expected Result:
┌────────────────────────────────────────────┐
│ ⚠ BUTTON NOT SHOWN (Not Last Lesson)       │ YELLOW BOX
│                                            │
│ Completion message only, no button         │
└────────────────────────────────────────────┘
```

### Scenario 3: Last Unit (No Next Unit)

```
Decision Tree:
┌─────────────────────────────────────────────┐
│ ✓ No next item in lesson                   │ GREEN
├─────────────────────────────────────────────┤
│ ✓ This is the last lesson in the unit      │ GREEN
├─────────────────────────────────────────────┤
│ ✗ No next unit found                       │ RED
│   → Only shows completion message          │
└─────────────────────────────────────────────┘

Expected Result:
┌────────────────────────────────────────────┐
│ ⚠ BUTTON NOT SHOWN (No Next Unit)          │ YELLOW BOX
│                                            │
│ This is the last unit in the course        │
└────────────────────────────────────────────┘
```

## Key Features

### 1. Immediate Visibility
- Large yellow panel stands out on the page
- Can't be missed when debugging

### 2. Complete Information
- Shows all variables involved
- Lists all relevant data (lessons, units)
- No need to check database or code

### 3. Step-by-Step Logic
- Decision tree shows exact flow
- Easy to see which condition failed
- Clear explanation of why

### 4. Actionable Guidance
- Tells you what should happen
- Provides troubleshooting steps
- Helps formulate support requests

### 5. Easy to Enable/Disable
- URL parameter for quick testing
- No code changes needed
- Simple to share with support

## When to Use

Use the debugger when:
- ✅ Button isn't appearing when expected
- ✅ Button appears but links to wrong unit
- ✅ Unsure if lesson/unit configuration is correct
- ✅ Need to report issue to support
- ✅ Testing after making changes to lessons/units

Don't need the debugger when:
- ❌ Button is working correctly
- ❌ Issue is with styling (use browser dev tools)
- ❌ Issue is with permissions (different problem)

## Screenshots

When reporting issues, capture:
1. The entire debugger panel (scroll if needed)
2. The actual navigation area (to show if button is missing)
3. The browser URL (showing the page and ?debug_nav=1)

This gives support everything needed to diagnose the issue.

## Mobile View

On mobile devices (screens < 768px):
- Panel width adjusts to screen
- Font sizes slightly smaller
- All information still visible
- May require scrolling within debugger

## Browser Compatibility

Works in:
- ✅ Chrome/Edge
- ✅ Firefox
- ✅ Safari
- ✅ Mobile browsers

Uses standard CSS, no special browser features required.
