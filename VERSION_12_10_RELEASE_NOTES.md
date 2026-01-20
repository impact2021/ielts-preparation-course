# Version 12.10 Release Notes

## Summary

Fixed critical issues with "Show me" button functionality: corrected anchor scrolling behavior and fixed button placement to appear inline with feedback text instead of on a separate line.

## Issues Fixed

### 1. ✅ Show me button anchor scrolling now works correctly
**Problem:** When clicking "Show me" buttons, the URL hash was not updated and the reading passage did not scroll to bring the highlighted section to the top of the reading pane. The yellow highlighting appeared but the section remained wherever it was in the viewport.

**Solution:** 
1. Added URL hash update using `window.history.replaceState()` to update the browser address bar with the anchor (e.g., `#q23`)
2. Fixed scroll container detection to check for `.reading-column` first (for CBT layouts), then fallback to other containers
3. Used the same scrolling logic as the transcript handler, which was working correctly

**Files Changed:**
- `assets/js/frontend.js` - Updated `.show-in-reading-passage-link` click handler to:
  - Update URL hash on click
  - Check for `.reading-column` first for CBT layouts
  - Use proper scroll calculation with `position().top` for column-based scrolling

### 2. ✅ Show me buttons now appear inline with feedback text
**Problem:** The "Show me" buttons were appearing on a new line below the feedback text, creating awkward spacing and poor visual hierarchy.

**Solution:** 
1. Removed `<br>` tag before button insertion
2. Added `margin-left: 10px` CSS to create inline spacing
3. Removed `margin-top: 8px` from CSS rules for all feedback links
4. Applied fix to both listening ("Show me" for transcripts) and reading ("Show me" for passages) buttons

**Files Changed:**
- `assets/js/frontend.js` - Removed `<br>` tags and added inline `margin-left` styling
  - Updated listening transcript link insertion (line ~1049)
  - Updated reading passage link insertion (line ~1107)
  - Removed `<br>` cleanup code since it's no longer needed
- `assets/css/frontend.css` - Removed `margin-top: 8px` from:
  - `.show-in-transcript-link`
  - `.show-in-reading-passage-link`
  - `.listen-to-answer-link`

### 3. ✅ Unified "Show me" button text for listening tests
**Problem:** Listening test buttons still said "Show in transcript" instead of the unified "Show me" text.

**Solution:** Changed button text from "Show in transcript" to "Show me" to match reading tests.

**Files Changed:**
- `assets/js/frontend.js` - Updated text for listening transcript links

## Visual Changes

### Before (Version 12.9)
```
Feedback text about the answer
[Show me button on new line]
```

### After (Version 12.10)
```
Feedback text about the answer [Show me] [Listen to this answer]
```

All buttons now appear inline with consistent spacing using `margin-left: 10px`.

## Technical Details

### Scroll Container Priority
The updated scrolling logic now checks containers in this order:
1. `.reading-column` (CBT layout - scrollable column)
2. `.reading-text-section` or `.cbt-passage-panel` (alternative containers)
3. `html, body` (fallback for standard layouts)

### URL Hash Update
Uses `window.history.replaceState()` when available (modern browsers) to avoid adding browser history entries, with fallback to `window.location.hash` for older browsers.

## Version Information
- **Previous Version:** 12.9
- **Current Version:** 12.10
- **Release Date:** 2026-01-20
