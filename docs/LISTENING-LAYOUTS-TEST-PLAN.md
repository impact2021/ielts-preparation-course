# Listening Test Layouts - Test Plan

## Requirements Verification

### Requirement 1: Listening Test Practice Tests (CBT Layout)
**Status**: ✅ Implemented

Requirements:
- [x] Left panel has area for audio file link (no text)
- [x] Student cannot control audio during test (controls hidden)
- [x] Audio autoplays after 3-second countdown
- [x] Countdown shows in left panel
- [x] Graphic equalizer/visualization shows audio is playing
- [x] On submission, left panel reveals transcript
- [x] On submission, student can see and play audio with controls

Implementation:
- Template: `templates/single-quiz-listening-practice.php`
- Layout type: `listening_practice`
- Audio controls: Hidden during test via CSS
- Countdown: 3 seconds with animated number
- Visualizer: 8-bar animated graphic equalizer
- Transcript: Revealed on form submission with audio controls

### Requirement 2: Listening Test Exercises
**Status**: ✅ Implemented

Requirements:
- [x] Audio autoplays after 1-second countdown
- [x] Left pane shows audio player with controls (student can stop)
- [x] Warning note: "Remember in the official IELTS test you are NOT able to stop the audio recordings"
- [x] On submission, user sees transcript

Implementation:
- Template: `templates/single-quiz-listening-exercise.php`
- Layout type: `listening_exercise`
- Audio controls: Visible via HTML5 `<audio controls>`
- Countdown: 1 second
- Warning: Displayed above audio player with icon
- Transcript: Revealed on form submission with audio controls

---

## Manual Test Checklist

### Setup Tests

#### Test 1: Admin Interface
- [ ] Navigate to Quizzes → Add New
- [ ] Verify "Layout Type" dropdown includes:
  - [ ] "Listening Practice Test (No Audio Controls)"
  - [ ] "Listening Exercise (With Audio Controls)"
- [ ] Select "Listening Practice Test"
- [ ] Verify "Listening Audio" section appears
- [ ] Verify "Audio URL" field is present
- [ ] Verify "Audio Transcript" field is present
- [ ] Enter sample audio URL
- [ ] Enter sample transcript
- [ ] Save quiz
- [ ] Reload page and verify fields are saved

#### Test 2: Layout Switching
- [ ] Edit quiz from Test 1
- [ ] Change layout to "Standard Layout"
- [ ] Verify "Listening Audio" section hides
- [ ] Change layout to "Listening Exercise"
- [ ] Verify "Listening Audio" section shows
- [ ] Change layout to "Computer-Based IELTS Layout"
- [ ] Verify "Listening Audio" section hides
- [ ] Verify "Reading Texts" section shows

### Listening Practice Test

#### Test 3: Basic Functionality
- [ ] Create quiz with "Listening Practice Test" layout
- [ ] Add audio URL (use test MP3 file)
- [ ] Add transcript text
- [ ] Add 3-5 questions
- [ ] Publish quiz
- [ ] View quiz on frontend
- [ ] Verify countdown appears showing "3"
- [ ] Verify countdown counts down: 3, 2, 1
- [ ] Verify audio starts playing automatically
- [ ] Verify graphic equalizer appears and animates
- [ ] Verify audio controls are NOT visible
- [ ] Verify "Audio Playing" status shows
- [ ] Complete quiz and submit
- [ ] Verify transcript appears after submission
- [ ] Verify audio controls appear in transcript section
- [ ] Verify audio can be played again

#### Test 4: Audio Handling
- [ ] Test with working audio URL
- [ ] Verify audio plays successfully
- [ ] Test with non-existent URL
- [ ] Verify appropriate error handling
- [ ] Test with different audio formats (MP3, OGG, WAV)
- [ ] Verify browser compatibility

#### Test 5: Visual Elements
- [ ] Verify countdown is centered and large
- [ ] Verify countdown has pulse animation
- [ ] Verify graphic equalizer has 8 bars
- [ ] Verify bars animate in wave pattern
- [ ] Verify smooth transition from countdown to player
- [ ] Verify responsive design on mobile

### Listening Exercise

#### Test 6: Basic Functionality  
- [ ] Create quiz with "Listening Exercise" layout
- [ ] Add audio URL
- [ ] Add transcript
- [ ] Add questions
- [ ] Publish quiz
- [ ] View quiz on frontend
- [ ] Verify countdown appears showing "1"
- [ ] Verify countdown counts down quickly
- [ ] Verify audio starts playing automatically
- [ ] Verify audio controls ARE visible
- [ ] Verify warning message appears
- [ ] Verify warning says "Remember in the official IELTS test you are NOT able to stop the audio recordings"
- [ ] Test pause button - verify it works
- [ ] Test play button - verify it works
- [ ] Test seek/scrub - verify it works
- [ ] Complete quiz and submit
- [ ] Verify transcript appears
- [ ] Verify audio controls appear

#### Test 7: Warning Message
- [ ] Verify warning has warning icon
- [ ] Verify warning has yellow/amber background
- [ ] Verify warning text is clear and visible
- [ ] Verify warning appears above audio player

### Cross-Layout Tests

#### Test 8: Question Types
Test both layouts with various question types:
- [ ] Multiple choice
- [ ] True/False/Not Given
- [ ] Short answer
- [ ] Summary completion
- [ ] Table completion
- [ ] Dropdown paragraph
- [ ] Multi-select

#### Test 9: Navigation
- [ ] Test with timer enabled
- [ ] Verify timer works with listening layouts
- [ ] Test with course/lesson navigation
- [ ] Verify "Previous page" button works
- [ ] Verify "Next page" button works
- [ ] Verify "Return to course" button works

#### Test 10: Results Display
- [ ] Submit quiz with correct answers
- [ ] Verify results show correctly
- [ ] Test with percentage scoring
- [ ] Test with IELTS Listening band score
- [ ] Verify score calculation is accurate

### Data Persistence Tests

#### Test 11: Save and Load
- [ ] Create listening practice quiz
- [ ] Save as draft
- [ ] Reload page
- [ ] Verify all fields maintained
- [ ] Publish quiz
- [ ] Edit quiz
- [ ] Verify fields still correct

#### Test 12: Import/Export
- [ ] Create listening practice quiz
- [ ] Export to XML
- [ ] Verify audio_url in XML
- [ ] Verify transcript in XML
- [ ] Verify layout_type in XML
- [ ] Delete quiz
- [ ] Import XML
- [ ] Verify quiz recreated correctly
- [ ] Verify all fields present

#### Test 13: Text Format
- [ ] Export quiz to text format
- [ ] Verify layout type shows correctly
- [ ] Import from text format
- [ ] Verify import successful

### Browser Compatibility

#### Test 14: Desktop Browsers
Test on:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

Verify for each:
- [ ] Countdown displays correctly
- [ ] Audio autoplays (or shows fallback)
- [ ] Controls work as expected
- [ ] Transcript displays correctly
- [ ] Animations work smoothly

#### Test 15: Mobile Browsers
Test on:
- [ ] Mobile Chrome (iOS)
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

Verify for each:
- [ ] Responsive layout works
- [ ] Touch controls work
- [ ] Audio plays (may require user interaction)
- [ ] Countdown visible
- [ ] Transcript readable

### Edge Cases

#### Test 16: Missing Data
- [ ] Create listening quiz without audio URL
- [ ] Verify graceful handling
- [ ] Create listening quiz without transcript
- [ ] Verify transcript section handles empty content
- [ ] Create listening quiz without questions
- [ ] Verify appropriate message

#### Test 17: Special Characters
- [ ] Enter transcript with special characters (quotes, apostrophes)
- [ ] Enter transcript with HTML entities
- [ ] Enter transcript with line breaks
- [ ] Verify all display correctly

#### Test 18: Long Content
- [ ] Enter very long transcript (1000+ words)
- [ ] Verify scrolling works
- [ ] Enter audio URL with query parameters
- [ ] Verify URL handled correctly

### Performance Tests

#### Test 19: Load Time
- [ ] Create quiz with large audio file (10MB+)
- [ ] Measure page load time
- [ ] Verify countdown doesn't delay
- [ ] Verify audio preloads properly

#### Test 20: Multiple Instances
- [ ] Create course with multiple listening quizzes
- [ ] Navigate between them
- [ ] Verify no JavaScript conflicts
- [ ] Verify audio stops when leaving page

---

## Automated Test Coverage

### PHP Syntax
- [x] `templates/single-quiz-listening-practice.php` - No syntax errors
- [x] `templates/single-quiz-listening-exercise.php` - No syntax errors
- [x] `includes/admin/class-admin.php` - No syntax errors
- [x] `includes/admin/class-text-exercises-creator.php` - No syntax errors

### JavaScript Syntax
- [x] `assets/js/frontend.js` - No syntax errors

---

## Known Limitations

1. **Autoplay Restrictions**: Some browsers block autoplay. Fallback message shown if autoplay fails.
2. **Audio Formats**: MP3 recommended for best compatibility. Other formats may not work in all browsers.
3. **Mobile Autoplay**: May require user interaction on mobile devices.

---

## Test Results Summary

**Total Tests**: 20 test suites
**Status**: Ready for manual testing
**Blocking Issues**: None identified
**Non-Blocking Issues**: None identified

---

## Sign-Off

**Developer**: ✅ Implementation complete
**Code Review**: ⏳ Pending
**QA Testing**: ⏳ Pending
**Production Ready**: ⏳ Pending user testing
