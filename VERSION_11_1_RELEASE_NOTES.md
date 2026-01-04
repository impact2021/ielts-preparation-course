# Version 11.1 Release Notes

## Overview

Version 11.1 is a minor update that fixes a layout issue on exercise pages where the WordPress admin bar was still taking up space even though it was hidden.

## Date

January 4, 2026

## Changes

### Bug Fixes

#### Admin Bar Spacing Removal

**Problem:**
The WordPress admin bar, although hidden with `display: none` in focus mode, still caused unwanted spacing at the top of exercise pages. This occurred because WordPress automatically adds a `margin-top` to the `html` element when the admin bar is present (typically 32px on desktop and 46px on mobile). Even when the admin bar was hidden, this margin remained, creating empty space at the top of the page.

**Solution:**
1. Added CSS rule to explicitly remove the margin-top from the `html` element when in focus mode
2. Updated JavaScript to apply the `ielts-quiz-focus-mode` class to both the `body` and `html` elements

**Files Changed:**
- `assets/css/frontend.css` - Added CSS rule for `html.ielts-quiz-focus-mode`
- `assets/js/frontend.js` - Added `$('html').addClass('ielts-quiz-focus-mode')`
- `ielts-course-manager.php` - Version bump to 11.1

### Version Update

- **Plugin Version:** Updated from 11.0 to 11.1
- **Version Constant:** `IELTS_CM_VERSION` updated to '11.1'

## Technical Details

### CSS Changes

```css
/* Remove WordPress admin bar margin from html element in focus mode */
html.ielts-quiz-focus-mode {
    margin-top: 0 !important;
}
```

This rule ensures that the WordPress admin bar's automatic margin-top is removed when exercises are in focus mode, providing a true full-screen experience.

### JavaScript Changes

```javascript
// Auto-enable focus mode for all CBT quizzes
$('body').addClass('ielts-quiz-focus-mode');
$('html').addClass('ielts-quiz-focus-mode');
```

The focus mode class is now applied to both the `body` and `html` elements, allowing CSS rules to target both elements as needed.

## Testing

- ✅ Code review completed with no issues
- ✅ Security scan (CodeQL) completed with no vulnerabilities
- ✅ All changes are minimal and targeted

## Impact

This fix ensures that exercise pages (computer-based tests, listening exercises, etc.) now use the full viewport height without any unwanted spacing at the top, providing a better user experience.

## Files Modified

1. `assets/css/frontend.css` - Added margin-top removal for html element
2. `assets/js/frontend.js` - Added focus mode class to html element
3. `ielts-course-manager.php` - Version update to 11.1

## Upgrade Path

**From Version 11.0:**
- No manual steps required
- Simple plugin update
- No database changes
- No content migration needed

## Notes

This is a minor bug fix release that addresses a specific layout issue. All existing functionality remains unchanged.

---

**Previous Version:** 11.0  
**Current Version:** 11.1  
**Release Date:** January 4, 2026
