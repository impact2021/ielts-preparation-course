# Version 15.45 & 15.46 Release Notes

## Overview
This release addresses two key user experience improvements:
1. **Navigation Enhancement (v15.45)**: Fixed end-of-unit navigation to include next unit information in the link text
2. **Video Speed Controls (v15.46)**: Added YouTube-style playback speed controls for course videos

---

## Version 15.45 - Navigation Fix

### Issue
When completing the last lesson of a unit, users saw "That is the end of this unit" message but had to look for a separate button to navigate to the next unit. The message itself was not actionable.

### Solution
Combined the completion message with the navigation link to create a single, clear call-to-action:
- **Old behavior**: "That is the end of this unit" (plain text) + separate "Move to Unit X" button
- **New behavior**: "That is the end of this unit. Move on to Unit X" (single hyperlink)

### Files Modified
1. `templates/single-quiz.php`
2. `templates/single-quiz-computer-based.php`
3. `templates/single-resource-page.php`
4. `ielts-course-manager.php` (version updated to 15.45)

### Technical Details
- Unit number is extracted from the next unit's title using regex pattern `/Unit\s+(\d+)/i`
- Falls back to "Move on to next unit" if unit number cannot be determined
- Shows plain text "That is the end of this unit" if no next unit exists
- All unit numbers are properly escaped with `esc_html()` to prevent XSS vulnerabilities
- Text is internationalized using WordPress translation functions

### User Impact
✅ Clearer navigation path between units
✅ Reduced confusion about how to proceed
✅ More intuitive user experience

---

## Version 15.46 - Video Speed Controls

### New Feature
Added professional-grade playback speed controls to all HTML5 video players in the course, similar to YouTube's speed control feature.

### Features
- **Speed Options**: 0.5x, 0.75x, 1x (Normal), 1.25x, 1.5x, 2x
- **Modern UI**: Glassmorphism design with smooth animations
- **Responsive**: Adapts to mobile and desktop screens
- **Smart Detection**: Only enhances HTML5 videos; leaves YouTube/Vimeo embeds unchanged (they already have speed controls)

### Files Modified
1. `assets/js/frontend.js` - Added video speed control functionality
2. `assets/css/frontend.css` - Added modern styling for controls
3. `ielts-course-manager.php` (version updated to 15.46)

### Technical Implementation

#### JavaScript (`frontend.js`)
```javascript
// Key features:
- Detects native HTML5 <video> elements
- Adds custom speed control UI
- Uses event delegation for performance
- Debounced MutationObserver for dynamic content
- Prevents duplicate initialization
- Respects existing video controls attribute
```

#### CSS (`frontend.css`)
```css
// Modern design features:
- Glassmorphism effect with backdrop-filter
- Smooth transitions and animations
- Active state with visual checkmark
- Hover effects for better UX
- Mobile-responsive sizing
```

### User Interface

**Speed Control Button**
- Located in bottom-right corner of video
- Shows current playback speed (e.g., "1x", "1.5x")
- Clock icon for easy recognition
- Semi-transparent dark background with blur effect

**Speed Menu**
- Appears above the button when clicked
- Lists all available speed options
- Active speed highlighted with blue background and checkmark
- Closes when clicking outside or selecting a speed

### Performance Optimizations
1. **Event Delegation**: Single document-level click handler instead of multiple per-video handlers
2. **Debouncing**: MutationObserver waits 100ms before re-scanning for videos
3. **Smart Detection**: Only re-initializes if actual video elements are added to DOM
4. **Initialization Check**: Videos marked with `data-speed-controls-initialized` to prevent duplicates

### Browser Compatibility
- ✅ Modern browsers (Chrome, Firefox, Safari, Edge)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)
- ✅ Supports both modern CSS (backdrop-filter) and fallbacks

### Accessibility
- ARIA-compliant button with title attribute
- Keyboard-friendly (can be activated with Enter/Space)
- Clear visual feedback for all states
- High contrast text for readability

### Security
- ✅ Passed CodeQL security scan (0 alerts)
- No XSS vulnerabilities
- No injection risks
- Safe DOM manipulation

---

## Testing Guide

### Testing Navigation Fix (v15.45)

1. **Test successful navigation:**
   - Log in as a student
   - Navigate to any unit with multiple lessons
   - Complete all lessons in that unit
   - On the last lesson, verify you see: "That is the end of this unit. Move on to Unit X"
   - Click the link to verify it navigates to the next unit

2. **Test edge cases:**
   - Test on the last unit of a course (should show plain text without link)
   - Test with units that have non-standard naming (should fall back gracefully)
   - Test on all three template types (quiz, computer-based quiz, resource page)

### Testing Video Speed Controls (v15.46)

1. **Test HTML5 videos:**
   - Navigate to a resource page with a direct MP4 video
   - Verify the speed control button appears in bottom-right corner
   - Click the button to open the speed menu
   - Select different speeds and verify playback changes
   - Verify current speed is highlighted with checkmark
   - Verify button label updates to show current speed

2. **Test YouTube/Vimeo embeds:**
   - Navigate to a resource with a YouTube video
   - Verify the embedded player appears normally
   - Verify YouTube's native speed controls work
   - Verify no duplicate speed controls are added

3. **Test responsiveness:**
   - Test on desktop (should show full-size controls)
   - Test on mobile (should show smaller controls)
   - Verify controls don't interfere with video playback

4. **Test interactions:**
   - Click outside the menu to verify it closes
   - Play video and change speed to verify it persists
   - Pause and resume to verify speed is maintained

---

## Version History
- **15.44** → **15.45**: Navigation enhancement
- **15.45** → **15.46**: Video speed controls

## Security Summary

### Navigation Fix (v15.45)
✅ **No vulnerabilities introduced**
- Unit numbers properly escaped with `esc_html()`
- URLs escaped with `esc_url()`
- Translation functions used for all text
- CodeQL scan: PASSED

### Video Speed Controls (v15.46)
✅ **No vulnerabilities introduced**
- No user input handling
- Safe DOM manipulation
- No external dependencies
- Event delegation prevents memory leaks
- CodeQL scan: PASSED (0 alerts)

---

## Migration Notes
No database migrations required. Changes are purely frontend enhancements.

## Rollback
To rollback, revert to version 15.44 and clear browser caches.

## Support
For issues or questions, please contact the development team.
