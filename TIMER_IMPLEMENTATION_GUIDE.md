# Timer Implementation Guide

## Overview

This guide documents the timer auto-submit and fullscreen modal features added to IELTS exercises.

## Features Implemented

### 1. Timer Field for Exercises

**Location**: Admin > IELTS Courses > Exercises > Edit Exercise

A new "Timer (Minutes)" field has been added to the Quiz Settings meta box.

**Configuration**:
- Enter the number of minutes for the timer (integer value)
- Leave empty for no timer
- Accepts values >= 0

**Behavior**:
- Timer starts when the exercise page loads
- Displays remaining time in MM:SS format
- Automatically submits the exercise when time expires
- **Submits regardless of completion status** (as per requirement)

### 2. Timer Display

**Standard Quiz Layout**:
- Timer appears below the quiz info section
- Shows "Time Remaining: MM:SS"
- Color changes based on remaining time:
  - Blue: Normal (> 5 minutes)
  - Orange: Warning (≤ 5 minutes)
  - Red: Critical (≤ 1 minute)

**Computer-Based Layout (Fullscreen)**:
- Timer appears at the top of the modal as a sticky header
- Same color-coding as standard layout
- Remains visible while scrolling

### 3. Fullscreen Modal

**Location**: Computer-based exercises show a button "Open in Fullscreen"

**New Behavior**:
- Opens as a modal overlay (not a new window)
- Removes browser address bar, tabs, and chrome
- Removes breadcrumbs and page title
- Maximizes space for test content
- Exit button with confirmation prompt

**Benefits**:
- Maximum screen real estate for reading passages and questions
- Simulates actual IELTS computer test environment
- No distractions from browser UI

## Technical Implementation

### Files Modified

1. **includes/admin/class-admin.php**
   - Added timer field to quiz meta box (line ~653)
   - Added timer save logic (line ~1447)

2. **templates/single-quiz.php**
   - Added timer metadata retrieval (line ~19)
   - Added timer display in quiz info (line ~63)
   - Added timer div for countdown (line ~72)

3. **templates/single-quiz-computer-based.php**
   - Added timer metadata retrieval (line ~25)
   - Modified fullscreen button to open modal instead of new window
   - Added modal structure and styles
   - Added fullscreen timer display
   - Removed breadcrumbs/title in fullscreen mode

4. **assets/js/frontend.js**
   - Added timer countdown logic (line ~11)
   - Auto-submit functionality
   - Memory leak prevention with cleanup on page unload

5. **assets/css/frontend.css**
   - Timer styling for standard layout
   - Timer styling for fullscreen modal

### Database Schema

**Meta Key**: `_ielts_cm_timer_minutes`
- **Type**: Integer
- **Storage**: Post meta for ielts_quiz post type
- **Default**: Empty (no timer)

## Usage Instructions

### For Administrators

1. **Create/Edit an Exercise**
   - Go to IELTS Courses > Exercises
   - Create new or edit existing exercise

2. **Set Timer**
   - Scroll to "Quiz Settings" meta box
   - Find "Timer (Minutes)" field
   - Enter desired duration (e.g., 60 for 60 minutes)
   - Leave empty for no timer

3. **Save Exercise**
   - Publish or update the exercise

### For Students

1. **Standard Layout Exercises**
   - Navigate to the exercise
   - Timer appears below quiz information
   - Start answering questions
   - Timer counts down automatically
   - Alert shows when time expires
   - Form submits automatically

2. **Computer-Based Layout Exercises**
   - Navigate to the exercise
   - Click "Open in Fullscreen" button
   - Modal opens with exercise content
   - Timer appears at top (if configured)
   - Answer questions in fullscreen mode
   - Click "Exit Fullscreen" to close (with confirmation)
   - Timer auto-submits when time expires

## Testing Checklist

### Timer Functionality
- [ ] Admin field saves and loads correctly
- [ ] Timer displays on quiz page when set
- [ ] Timer counts down correctly (verify MM:SS format)
- [ ] Color changes at 5 minutes (blue → orange)
- [ ] Color changes at 1 minute (orange → red)
- [ ] Alert appears when timer expires
- [ ] Form auto-submits when timer reaches zero
- [ ] No timer appears when field is empty

### Fullscreen Modal (Computer-Based Layout)
- [ ] "Open in Fullscreen" button appears
- [ ] Modal opens on button click
- [ ] No address bar visible in modal
- [ ] No breadcrumbs or title in modal
- [ ] Reading passages display correctly
- [ ] Questions display correctly
- [ ] Timer appears at top of modal (if configured)
- [ ] "Exit Fullscreen" button works
- [ ] Confirmation prompt shows on exit
- [ ] Content is maximized (no wasted space)

### Memory Management
- [ ] No console errors
- [ ] Timer stops when modal closes
- [ ] Timer cleans up on page navigation
- [ ] No multiple timers running simultaneously

## Known Limitations

1. **Client-Side Only**: Timer runs in browser JavaScript, so it can be manipulated by tech-savvy users. For high-stakes testing, consider server-side validation.

2. **No Pause/Resume**: Timer cannot be paused once started. Refreshing the page resets the timer.

3. **Browser Compatibility**: Modal fullscreen tested on modern browsers (Chrome, Firefox, Safari, Edge). Older browsers may show standard fullscreen behavior.

4. **Mobile Experience**: Modal works on mobile devices but screen size may be limiting for reading exercises.

## Troubleshooting

### Timer Not Appearing
- Check that timer field is set in admin
- Verify user is logged in
- Check that questions exist for the exercise
- Clear browser cache

### Timer Not Auto-Submitting
- Check browser console for JavaScript errors
- Verify jQuery is loaded
- Ensure no other plugins conflict with form submission

### Modal Not Opening
- Check browser console for JavaScript errors
- Verify jQuery is loaded
- Test in different browser

### Timer Continues After Modal Close
- This has been fixed in the implementation
- Clear browser cache and test again
- Report if issue persists

## Support

For issues or questions:
1. Check this guide first
2. Review browser console for errors
3. Test in incognito/private mode
4. Contact plugin support with specific details

---

**Version**: 2.8
**Last Updated**: 2025-12-18
**Feature**: Timer Auto-Submit and Fullscreen Modal
