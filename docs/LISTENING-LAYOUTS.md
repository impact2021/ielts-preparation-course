# Listening Test Layouts - Implementation Guide

## Overview
This document describes the two new listening test layouts added to the IELTS Course Manager plugin:

1. **Listening Practice Test** - Simulates official IELTS test conditions (no audio controls)
2. **Listening Exercise** - Practice mode with audio controls available

## Features

### Listening Practice Test
- **3-second countdown** before audio autoplay
- **No audio controls** during the test (simulates real IELTS conditions)
- **Graphic equalizer visualization** shows audio is playing
- **Transcript revealed** after submission with audio controls

### Listening Exercise  
- **1-second countdown** before audio autoplay
- **Audio controls visible** (play, pause, seek)
- **Warning message** reminds students that official IELTS doesn't allow audio control
- **Transcript revealed** after submission

## Setup Instructions

### 1. Create or Edit a Quiz
1. Go to WordPress Admin → Quizzes → Add New (or edit existing)
2. Set the **Layout Type** to either:
   - "Listening Practice Test (No Audio Controls)"
   - "Listening Exercise (With Audio Controls)"

### 2. Configure Audio Settings
When you select a listening layout, a new section appears:

**Listening Audio**
- **Audio URL**: Enter the direct URL to your MP3 audio file
  - Example: `https://example.com/audio/listening-test-1.mp3`
  - Recommended format: MP3
  
- **Audio Transcript**: Enter the full transcript of the audio
  - This will be shown to students after they submit their answers
  - Supports rich text formatting

### 3. Add Questions
Add your listening comprehension questions as normal:
- Multiple choice
- True/False/Not Given
- Short answer
- Summary completion
- Table completion
- Dropdown paragraph
- Multi-select
- And more...

### 4. Configure Other Settings
- **Timer**: Set time limit if needed
- **Pass Percentage**: Set minimum score to pass
- **Scoring Type**: Choose "IELTS Listening (Band Score)" for authentic scoring

## Template Files

### Backend (Admin)
- `includes/admin/class-admin.php` - Admin interface with audio/transcript fields

### Frontend (Student View)
- `templates/single-quiz-listening-practice.php` - Practice test template
- `templates/single-quiz-listening-exercise.php` - Exercise template
- `templates/single-quiz-page.php` - Template router

### Assets
- `assets/css/frontend.css` - Styling for countdown, audio player, visualizer
- `assets/js/frontend.js` - JavaScript for countdown and audio control

## Technical Details

### Countdown Implementation
- JavaScript timer counts down before autoplay
- Visual countdown number with pulse animation
- Smooth fade transition to audio player

### Audio Visualizer (Practice Test Only)
- 8 animated bars that pulse with audio
- CSS animations create wave effect
- Indicates audio is actively playing

### Transcript Display
- Hidden during test
- Revealed after form submission
- Includes audio player with full controls for review

### Responsive Design
- Mobile-friendly layout
- Adjusts visualizer size on smaller screens
- Touch-friendly controls

## CSS Classes Reference

### Containers
- `.ielts-listening-practice-quiz` - Main container for practice tests
- `.ielts-listening-exercise-quiz` - Main container for exercises
- `.listening-audio-column` - Left panel for audio player
- `.listening-audio-content` - Content wrapper for audio area

### Countdown
- `.listening-countdown` - Countdown container
- `.countdown-text` - "Audio will start in:" text
- `.countdown-number` - Large countdown number

### Audio Player
- `.listening-audio-player` - Audio player container
- `.audio-visualizer` - Visualizer container (practice only)
- `.visualizer-bar` - Individual visualizer bars
- `.audio-status` - Playing status indicator

### Transcript
- `.listening-transcript` - Transcript container
- `.transcript-content` - Transcript text area
- `.transcript-audio-controls` - Audio controls after submission

### Warning (Exercise Only)
- `.ielts-warning-notice` - Warning message container

## JavaScript Functions

### Main Listening Logic
Located in `assets/js/frontend.js`:

```javascript
// Detects listening quiz type
var listeningPracticeQuiz = $('.ielts-listening-practice-quiz');
var listeningExerciseQuiz = $('.ielts-listening-exercise-quiz');

// Countdown timer (3s for practice, 1s for exercise)
var countdownSeconds = isListeningPractice ? 3 : 1;

// Autoplay audio when countdown completes
function startAudioPlayback() { ... }

// Show transcript after submission
$('#ielts-quiz-form').on('submit', function(e) { ... }
```

## Database Fields

New meta fields for quizzes:
- `_ielts_cm_audio_url` - URL to audio file
- `_ielts_cm_transcript` - Audio transcript text
- `_ielts_cm_layout_type` - Now includes `listening_practice` and `listening_exercise`

## Import/Export Support

### XML Export
The new fields are included in XML exports:
- Audio URL
- Transcript
- Layout type (including new listening types)

### Text Format
Layout types in text format:
```
=== EXERCISE SETTINGS ===
Layout Type: Listening Practice Test (No Audio Controls)
=== END EXERCISE SETTINGS ===
```

Or:
```
=== EXERCISE SETTINGS ===
Layout Type: Listening Exercise (With Audio Controls)
=== END EXERCISE SETTINGS ===
```

## Browser Compatibility

### Audio Support
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires HTML5 audio support
- MP3 format recommended for widest compatibility

### Autoplay
- May require user interaction in some browsers
- Fallback message shown if autoplay fails
- Students can manually start audio if needed

## Troubleshooting

### Audio Doesn't Play
1. Check audio URL is correct and accessible
2. Verify audio file format (MP3 recommended)
3. Check browser console for errors
4. Some browsers block autoplay - students may need to click play

### Transcript Not Showing
1. Ensure transcript is entered in admin
2. Check JavaScript console for errors
3. Verify quiz form submission is working

### Visualizer Not Animating
1. CSS animations may be disabled in browser
2. Check for CSS conflicts with theme
3. Inspect element to verify CSS classes are applied

## Best Practices

### Audio Files
- Use high-quality MP3 files (128kbps minimum)
- Host on reliable CDN or server
- Keep file sizes reasonable (<10MB)
- Test audio playback before publishing

### Transcripts
- Include speaker labels if multiple speakers
- Use proper punctuation and formatting
- Include timestamps if helpful
- Proofread carefully

### Questions
- Align question difficulty with audio content
- Use appropriate question types for listening
- Test the entire exercise before publishing
- Consider question order and grouping

### Timing
- Set reasonable timer based on audio length
- Add extra time for reading questions
- Test with real students if possible

## Future Enhancements

Potential improvements for future versions:
- Playback speed control (for exercises)
- Section-based audio (multiple audio files)
- Visual waveform display
- Time-synced transcript highlighting
- Download transcript option
- Audio progress indicator
