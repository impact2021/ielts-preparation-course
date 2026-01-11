# Version 11.11 Release Notes

**Release Date:** January 2026  
**Previous Version:** 11.10  
**Current Version:** 11.11

## Overview

This release focuses on improving the user experience for the audio feedback buttons in listening tests. The changes make the "Listen to this answer" button more consistent with other UI elements and provide visual feedback during audio loading.

## Changes

### 1. Button Styling Consistency

**Issue:** The "Listen to this answer" button appeared as a plain link, inconsistent with the "Show in transcript" button.

**Solution:** 
- Added button-style formatting to `.listen-to-answer-link` to match `.show-in-transcript-link`
- Both buttons now display as blue buttons with:
  - Consistent padding (5px 12px)
  - Blue background (#0073aa)
  - White text
  - Border radius (3px)
  - Hover effect (darker blue #005a87)
  - Focus outline for accessibility

**Files Modified:**
- `assets/css/frontend.css` (lines 1839-1861)

### 2. Loading Indicator

**Issue:** When clicking "Listen to this answer", there was a delay while the audio seeked to the correct timestamp, with no visual feedback to the user.

**Solution:**
- Added a loading state with animated spinner
- Shows spinner while audio is seeking to the start time
- Automatically removed when audio is ready to play
- Prevents multiple clicks during loading
- Graceful error handling

**Implementation Details:**
- CSS: Added `.listen-to-answer-link.loading` class with:
  - Animated spinning circle (CSS keyframe animation)
  - Increased padding-right to accommodate spinner
  - Reduced opacity (0.8) to indicate disabled state
  - Pointer-events: none to prevent clicks during loading
  
- JavaScript: Enhanced click handler to:
  - Add loading class immediately on click
  - Listen for 'seeked' event to remove loading state
  - Handle errors gracefully
  - Clean up event listeners properly

**Files Modified:**
- `assets/css/frontend.css` (lines 1863-1887)
- `assets/js/frontend.js` (lines 1376-1432)

### 3. Improved Spacing

**Issue:** The feedback buttons were too close to the next question, causing potential confusion.

**Solution:**
- Increased bottom margin on `.field-feedback` from 10px to 20px
- Added `margin-bottom: 20px` to `.question-feedback-message`
- Creates clear visual separation between questions

**Files Modified:**
- `assets/css/frontend.css` (lines 1693-1700, 1745-1751)

### 4. Version Update

**Files Modified:**
- `ielts-course-manager.php` (lines 6, 23)
  - Updated version from 11.10 to 11.11

## Technical Details

### CSS Changes

```css
/* Listen to this answer button */
.listen-to-answer-link {
    display: inline-block;
    margin-top: 8px;
    padding: 5px 12px;
    background: #0073aa;
    color: #fff !important;
    text-decoration: none;
    border-radius: 3px;
    font-size: 0.9em;
    transition: background 0.2s ease;
    position: relative;
}

/* Loading state with spinner */
.listen-to-answer-link.loading {
    padding-right: 35px;
    pointer-events: none;
    opacity: 0.8;
}

.listen-to-answer-link.loading::after {
    content: '';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin-loading 0.6s linear infinite;
}

@keyframes spin-loading {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
}
```

### JavaScript Changes

```javascript
$(document).on('click', '.listen-to-answer-link', function(e) {
    e.preventDefault();
    var $button = $(this);
    var startTime = parseFloat($button.data('start-time'));
    var endTime = parseFloat($button.data('end-time'));
    
    var $audioPlayer = $('#listening-audio-player audio').first();
    
    if ($audioPlayer.length && !isNaN(startTime) && !isNaN(endTime)) {
        var audioElement = $audioPlayer[0];
        
        // Show loading state
        $button.addClass('loading');
        
        // Set the current time to start time
        audioElement.currentTime = startTime;
        
        // Remove loading state when seeking is complete
        var seekedHandler = function() {
            $button.removeClass('loading');
            audioElement.removeEventListener('seeked', seekedHandler);
        };
        audioElement.addEventListener('seeked', seekedHandler);
        
        // Play the audio with error handling
        var playPromise = audioElement.play();
        if (playPromise !== undefined) {
            playPromise.catch(function(error) {
                $button.removeClass('loading');
                audioElement.removeEventListener('seeked', seekedHandler);
                console.log('Audio playback failed:', error);
            });
        }
        
        // ... rest of the handler
    }
});
```

## User Impact

### Before
- "Listen to this answer" appeared as a plain link
- No feedback when clicking the button
- Users unsure if click registered
- Buttons too close to next question

### After
- "Listen to this answer" is a styled blue button matching "Show in transcript"
- Spinning loading indicator appears immediately on click
- Clear visual feedback that action is in progress
- Better spacing between questions improves readability

## Browser Compatibility

- CSS animations supported in all modern browsers
- Fallback: Without CSS animation support, loading state still shows (reduced opacity, disabled clicks)
- HTML5 audio 'seeked' event widely supported

## Testing Recommendations

1. Test on a listening quiz with audio feedback
2. Click "Listen to this answer" button
3. Verify:
   - Button appears as blue styled button
   - Spinner appears immediately on click
   - Spinner disappears when audio starts playing
   - Button cannot be clicked again while loading
   - Audio plays from correct timestamp
   - Adequate spacing below buttons

## Files Changed

1. `ielts-course-manager.php` - Version update
2. `assets/css/frontend.css` - Button styling, loading indicator, spacing
3. `assets/js/frontend.js` - Loading state management

## Backward Compatibility

✅ Fully backward compatible
- No database changes
- No breaking changes to existing functionality
- Pure CSS and JavaScript enhancements
- Existing HTML structure unchanged
