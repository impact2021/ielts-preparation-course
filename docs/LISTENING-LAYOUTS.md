# Listening Test Layouts - Implementation Guide

## Overview
This document describes the two new listening test layouts added to the IELTS Course Manager plugin:

1. **Listening Practice Test** - Simulates official IELTS test conditions (no audio controls)
2. **Listening Exercise** - Practice mode with audio controls available

## Features

### Listening Practice Test
- **Enable Audio button** to start playback (no countdown)
- **No audio controls** during the test (simulates real IELTS conditions)
- **Progress bar visualization** shows audio playback progress and time remaining
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
  - This single audio file will be used for the entire test
  
- **Audio Transcript**: Enter the full transcript of the audio
  - This will be shown to students after they submit their answers
  - Supports rich text formatting

**Multiple Transcript Sections (Optional)**
For tests with multiple sections (like IELTS Listening which has 4 sections), you can add multiple transcript sections:
- Click "Add Audio Section" to create additional transcript tabs
- Each section has:
  - **Section Number**: The section number (1-4)
  - **Transcript**: The transcript text for that section
- The same audio file plays for all sections
- Transcripts will be displayed in tabs after submission
- Students can switch between section transcripts using the tabs

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
- JavaScript timer counts down before autoplay (Exercise mode only)
- Practice mode uses an "Enable Audio" button instead
- Visual countdown number with pulse animation (Exercise mode)
- Smooth fade transition to audio player

### Audio Progress Display (Practice Test Only)
- Progress bar shows audio playback position
- Current time and total duration displayed
- Visual feedback replaces graphic equalizer
- Indicates audio playback status

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
- `.listening-countdown` - Countdown container (Exercise mode only)
- `.countdown-text` - "Audio will start in:" text
- `.countdown-number` - Large countdown number

### Enable Audio Button
- `.listening-enable-audio` - Enable audio button container (Practice mode only)
- `.enable-audio-btn` - Enable audio button

### Audio Player
- `.listening-audio-player` - Audio player container
- `.listening-practice-player` - Practice mode specific player
- `.audio-progress-container` - Progress bar container (Practice mode)
- `.audio-progress-bar` - Progress bar background
- `.audio-progress-fill` - Progress bar fill
- `.audio-time-display` - Time display container
- `.audio-current-time` - Current playback time
- `.audio-duration` - Total audio duration
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

// Practice mode: Enable Audio button
$('#enable-audio-btn').on('click', function() { ... }

// Exercise mode: Countdown timer (1s)
var countdownSeconds = 1;

// Update progress bar as audio plays (Practice mode)
function updateAudioProgress() { ... }

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
- Requires user interaction (Enable Audio button for Practice mode)
- Exercise mode attempts autoplay after countdown
- Students manually start audio via button in Practice mode

## Troubleshooting

### Audio Doesn't Play
1. Check audio URL is correct and accessible
2. Verify audio file format (MP3 recommended)
3. Check browser console for errors
4. Practice mode requires clicking "Enable Audio" button
5. Exercise mode should autoplay after countdown

### Progress Bar Not Showing
1. Ensure you're using Listening Practice (no controls) layout
2. Click "Enable Audio" to start playback
3. Check JavaScript console for errors
4. Progress bar only shows in Practice mode, not Exercise mode

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
- Visual waveform display
- Time-synced transcript highlighting
- Download transcript option
- Audio progress indicator

## Starting Question Number Feature

### Overview
All exercise layouts now support setting a custom starting question number. This is useful for exercises that are part of a larger test where questions don't start at 1.

### Use Cases
- **Full IELTS Reading Test**: Section 1 (Q1-13), Section 2 (Q14-26), Section 3 (Q27-40)
- **Continuation Exercises**: When splitting a long test into multiple exercises
- **Practice Tests**: Simulating specific sections of official tests

### Configuration

#### In Admin Interface
1. Edit any quiz/exercise
2. Find the "Starting Question Number" field (below Pass Percentage)
3. Enter the desired starting number (default: 1, min: 1, max: 100)
4. Example: Enter "21" to start at Question 21

#### In Text Format
Add to the EXERCISE SETTINGS block:
```
=== EXERCISE SETTINGS ===
Starting Question Number: 21
=== END EXERCISE SETTINGS ===
```

#### In XML Export/Import
The field is automatically included in XML exports:
```xml
<wp:meta_key><![CDATA[_ielts_cm_starting_question_number]]></wp:meta_key>
<wp:meta_value><![CDATA[21]]></wp:meta_value>
```

### How It Works

The starting question number affects:
1. **Question Headers**: "Question 21" instead of "Question 1"
2. **Multi-Question Items**: "Questions 21-23" for multi-select
3. **Navigation Buttons**: Bottom buttons show correct numbers
4. **Question Ranges**: Automatically calculated based on question types

### Example

If you set Starting Question Number to 27:
- First question displays as "Question 27"
- A 3-option multi-select would be "Questions 27-29"
- Next single question would be "Question 30"
- Navigation buttons show: [27] [28] [29] [30] ...

### Technical Details

- **Database Field**: `_ielts_cm_starting_question_number`
- **Default Value**: 1 (when not set)
- **Validation**: Integer, minimum 1, maximum 100
- **Templates Updated**: All 4 quiz templates (standard, CBT, listening practice, listening exercise)
- **Backward Compatible**: Existing exercises without this field default to 1

### Considerations

- Question numbers are display-only (for student interface)
- Backend question indices remain 0-based
- Scoring and grading are unaffected
- Question count calculations work the same way
