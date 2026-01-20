# Version 11.3 Release Notes

## Overview

Version 11.3 fixes a critical layout issue where practice tests and exercises did not properly adapt to extended monitor heights. This update ensures that content panels (reading text and questions) properly expand to utilize the full available screen space regardless of whether the page is displayed on a laptop screen or an extended/external monitor.

## Date

January 4, 2026

## Changes

### Bug Fixes

#### Extended Monitor Height Adaptation

**Problem:**
When users opened practice tests on an extended or external monitor, the reading column and questions column retained the maximum height of the smaller laptop screen instead of expanding to use the full height of the extended monitor. This was caused by using `vh` (viewport height) units in CSS, which are fixed based on the viewport at page load and don't adapt when content is moved to a different screen.

The issue affected:
- Computer-based reading tests (CBT)
- Listening practice tests
- Listening exercises
- All quiz layouts with split-panel designs

**Solution:**
1. Updated the base reading and questions column CSS to use `max-height: 100% !important` instead of `calc(100vh - 300px)`, allowing them to expand naturally within their container
2. Updated responsive breakpoints to use `100%` instead of viewport-based calculations
3. Updated focus mode styles to use `dvh` (dynamic viewport height) instead of `vh` for better adaptation to screen changes
4. The `dvh` unit dynamically adjusts when the viewport size changes, including when moving windows between monitors

**Technical Details:**
- `vh` units are static and locked to the initial viewport height
- `dvh` units (dynamic viewport height) adapt to viewport changes
- Using `100%` for non-focus mode allows natural expansion within flex containers
- Focus mode still uses `dvh` with offsets to account for the timer bar and navigation

**Files Changed:**
- `assets/css/frontend.css` - Updated max-height properties for reading/questions columns
- `ielts-course-manager.php` - Version bump to 11.3

### Version Update

- **Plugin Version:** Updated from 11.2 to 11.3
- **Version Constant:** `IELTS_CM_VERSION` updated to '11.3'

## Technical Details

### CSS Changes

#### Base Layout (Lines 893-946)
```css
/* Reading column (left) */
.reading-column {
    flex: 0 0 48%;
    border-right: 2px solid #e0e0e0;
    background: #f9f9f9;
    overflow-y: auto;
    max-height: 100% !important;  /* Changed from calc(100vh - 300px) */
    position: relative;
}

/* Questions column (right) */
.questions-column {
    flex: 0 0 52%;
    background: #fff;
    overflow-y: auto;
    max-height: 100% !important;  /* Changed from calc(100vh - 300px) */
}
```

#### Responsive Breakpoints (Lines 1214-1235)
```css
@media (max-width: 1024px) {
    .reading-column,
    .questions-column {
        flex: 1 1 100%;
        max-height: 100% !important;  /* Changed from calc(100vh - 400px) */
        border-right: none;
    }
}

@media (max-width: 768px) {
    .reading-column,
    .questions-column {
        max-height: 100% !important;  /* Changed from calc(100vh - 450px) */
    }
}
```

#### Focus Mode (Lines 2956-2975)
```css
body.ielts-quiz-focus-mode .reading-column,
body.ielts-quiz-focus-mode .questions-column,
body.ielts-quiz-focus-mode .listening-audio-column {
    max-height: calc(100dvh - 180px) !important;  /* Changed from 100vh to 100dvh */
}

@media (max-width: 1024px) {
    body.ielts-quiz-focus-mode .reading-column,
    body.ielts-quiz-focus-mode .questions-column,
    body.ielts-quiz-focus-mode .listening-audio-column {
        max-height: calc(100dvh - 200px) !important;  /* Changed from 100vh to 100dvh */
    }
}

@media (max-width: 768px) {
    body.ielts-quiz-focus-mode .reading-column,
    body.ielts-quiz-focus-mode .questions-column,
    body.ielts-quiz-focus-mode .listening-audio-column {
        max-height: calc(100dvh - 220px) !important;  /* Changed from 100vh to 100dvh */
    }
}
```

## Browser Compatibility

The `dvh` unit is supported in:
- Chrome 108+
- Firefox 101+
- Safari 15.4+
- Edge 108+

For older browsers, the layout will gracefully fall back to using the container's natural height.

## Testing

To verify this fix:
1. Open a practice test on a laptop screen
2. Move the browser window to an extended monitor with a larger resolution
3. The reading and questions panels should expand to use the full available height of the extended monitor
4. Test in both normal and focus modes
5. Test at different responsive breakpoints

## Impact

This fix ensures that:
- Practice tests and exercises properly utilize the full screen height on extended monitors
- Users get a better reading experience with more content visible at once
- The layout adapts dynamically when moving windows between monitors
- Focus mode continues to work correctly while accounting for fixed UI elements

## Files Modified

1. `assets/css/frontend.css` - Updated max-height properties throughout
2. `ielts-course-manager.php` - Version update to 11.3

## Upgrade Path

**From Version 11.2:**
- No manual steps required
- Simple plugin update
- No database changes
- No content migration needed
- CSS changes will take effect immediately upon update

## Notes

This is a minor bug fix release that addresses a specific layout issue with extended monitors. All existing functionality remains unchanged. The fix uses modern CSS units (`dvh`) that provide better adaptability to viewport changes while maintaining backward compatibility.

---

**Previous Version:** 11.2  
**Current Version:** 11.3  
**Release Date:** January 4, 2026
