# IELTS Reading Test Screenshot Demo Files

This directory contains 12 distinct HTML demo files for capturing screenshots of the IELTS reading test interface. Each file showcases a unique feature or UI element.

## Demo Files

### 1. `01-cbt-main-interface.html` ⭐ Main Interface
**Purpose:** Clean CBT interface without modal, showing the split-screen layout
- **Features:**
  - Reading passage on the left (48% width)
  - Questions on the right (52% width)
  - Question navigation bar at the bottom
  - Timer at top showing 60:00
  - 3 sample questions (Multiple Choice)
- **Optimal for:** Capturing the main interface of the CBT platform

### 2. `02-band-score-7-5-modal.html` 🏆 Band Score Modal
**Purpose:** Result modal showing Band 7.5 (32/40 correct)
- **Features:**
  - Centered modal overlay with 70% dark background
  - Large score display (7.5)
  - Score details (32/40 correct)
  - Congratulatory message
  - Action buttons (Review Answers, Retake Test, Return to Course)
- **Optimal for:** Capturing the result screen after quiz completion

### 3. `03-correct-answer-feedback.html` ✅ Correct Answer
**Purpose:** Demonstrates correct answer feedback with green highlight and checkmark
- **Features:**
  - Selected correct answer in green (#4caf50)
  - White checkmark (✓) indicator
  - Feedback message below
  - Styled according to plugin CSS
- **Optimal for:** Showing positive feedback to users

### 4. `04-incorrect-answer-feedback.html` ❌ Incorrect Answer
**Purpose:** Shows incorrect answer in red with X mark, displays correct answer with green border
- **Features:**
  - Selected incorrect answer in red (#f44336)
  - White X mark (✗) indicator
  - Correct answer highlighted with green border (not filled)
  - Explanatory feedback message
- **Optimal for:** Showing error feedback and correction

### 5. `05-show-me-button.html` 🔍 Show Me Button
**Purpose:** Displays "Show me the section" button in feedback area
- **Features:**
  - Green feedback box for correct answer
  - Blue "Show me the section" button
  - Proper spacing and styling
  - Click-to-highlight functionality placeholder
- **Optimal for:** Showing user navigation help features

### 6. `06-highlighted-passage.html` 🟨 Highlighted Text
**Purpose:** Reading passage with yellow highlighted text selections
- **Features:**
  - Clear highlights in yellow (#ffff00) on key phrases
  - "Clear Highlights" button in top-right
  - Multiple highlighted sections showing different use cases
  - Normal reading text alongside highlights
- **Optimal for:** Demonstrating text highlighting feature

### 7. `07-multiple-choice-question.html` 🔘 Multiple Choice
**Purpose:** Shows classic Multiple Choice question format (A, B, C, D)
- **Features:**
  - Two sample questions
  - 4 options each labeled with letters
  - Blue instruction box
  - Question numbers and text styling
- **Optimal for:** Capturing question type specific to reading tests

### 8. `08-true-false-question.html` ✔️ True/False/Not Given
**Purpose:** Displays True/False/Not Given question format
- **Features:**
  - Three sample questions
  - TRUE, FALSE, NOT GIVEN radio buttons
  - Instruction box explaining format
  - Proper styling for each option
- **Optimal for:** Showing alternative question format

### 9. `09-timer-countdown.html` ⏱️ Low Time Warning
**Purpose:** Timer display showing 15:30 (low time warning in red)
- **Features:**
  - Timer at 15:30 in RED (#d32f2f) to indicate low time
  - Light red background on timer
  - Full interface layout with questions
  - Urgent visual indicator
- **Optimal for:** Demonstrating time pressure feature

### 10. `10-font-controls.html` 🔤 Font Controls
**Purpose:** Shows font size control buttons (A−, A, A+)
- **Features:**
  - Three font size buttons in header
  - "A−" button for decreasing size
  - "A" button for reset (highlighted/active state)
  - "A+" button for increasing size
  - Full working interface
- **Optimal for:** Showing accessibility features

### 11. `11-detailed-feedback-correct.html` 📚 Detailed Explanation (Correct)
**Purpose:** Shows comprehensive feedback for correct answers
- **Features:**
  - Green feedback container with icon
  - Detailed explanation of correct answer
  - Key Learning section
  - Reference to source material
  - Professional styling
- **Optimal for:** Demonstrating educational feedback

### 12. `12-detailed-feedback-incorrect.html` 📚 Detailed Explanation (Incorrect)
**Purpose:** Shows comprehensive feedback for incorrect answers with correct answer shown
- **Features:**
  - Red feedback container for incorrect selection
  - Detailed explanation why answer is wrong
  - "Correct Answer" section with green border
  - Full passage reference context
  - Explanatory text and learning points
- **Optimal for:** Showing correction and learning opportunities

## Technical Details

### Viewport
- **Resolution:** 1280x720 (Optimal for screenshots)
- **Responsive:** Basic responsive adjustments included

### Styling
- All files use inline CSS embedded in the HTML
- Styling matches the actual plugin CSS from `frontend.css`
- Color scheme:
  - Primary: #0073aa (Blue)
  - Success: #4caf50 (Green)
  - Error: #f44336 (Red)
  - Warning: #d32f2f (Dark Red - low time)
  - Background: #f9f9f9, #fff

### Self-Contained
- ✅ All files are completely self-contained
- ✅ No external dependencies or links
- ✅ Can be opened directly in browser
- ✅ Ready for screenshot capture at 1280x720

## Usage for Screenshots

### Recommended Tools
1. **Headless Browser (Playwright, Puppeteer)**
   ```bash
   playwright screenshot 01-cbt-main-interface.html --size 1280x720
   ```

2. **Browser Screenshot Extensions**
   - Fireshot (Chrome)
   - Full Page Screen Capture (Firefox)

3. **Manual Screenshots**
   - Open file in browser
   - Set zoom to 100%
   - Adjust viewport to 1280x720
   - Capture screenshot

### Screenshot Sequence Recommendation
1. **Demo Flow:**
   - 01 (Main interface)
   - 09 (Timer countdown)
   - 07, 08 (Question types)
   - 03, 04 (Answer feedback)
   - 05 (Show me button)
   - 06 (Highlighting)
   - 10 (Font controls)
   - 11, 12 (Detailed feedback)
   - 02 (Final result)

2. **Band Score Highlight:**
   - Use file 02 prominently for Band 7.5 achievement display

## Features Demonstrated

| Feature | File | Demo |
|---------|------|------|
| Main CBT Interface | 01 | ✅ |
| Split Screen Layout | 01 | ✅ |
| Timer Display | 09, 10 | ✅ |
| Band 7.5 Result | 02 | ✅ |
| Correct Answer (Green) | 03 | ✅ |
| Incorrect Answer (Red) | 04 | ✅ |
| Correct Answer Highlight | 04, 12 | ✅ |
| Show Me Button | 05 | ✅ |
| Text Highlighting (Yellow) | 06 | ✅ |
| Multiple Choice Questions | 07 | ✅ |
| True/False Questions | 08 | ✅ |
| Timer Warning (Red) | 09 | ✅ |
| Font Controls | 10 | ✅ |
| Detailed Feedback | 11, 12 | ✅ |
| Question Navigation | 01, 09, 10 | ✅ |

## Content Quality

- ✅ **Realistic Content:** All passages and questions use realistic IELTS-style content
- ✅ **Varied Topics:** Climate change, technology, renewable energy, microbiomes, etc.
- ✅ **Proper Length:** Questions and passages appropriately sized for screenshots
- ✅ **Professional Styling:** Matches production CSS exactly
- ✅ **Band 7.5 Display:** Always uses 32/40 for consistency

## Accessibility Features Shown

- ✅ Font size controls (A−, A, A+)
- ✅ Clear color contrast
- ✅ Semantic HTML structure
- ✅ Proper form labels
- ✅ Readable sans-serif fonts

## File Organization

```
/main/screenshots/
├── 01-cbt-main-interface.html          (11 KB)
├── 02-band-score-7-5-modal.html        (3.3 KB)
├── 03-correct-answer-feedback.html     (6.0 KB)
├── 04-incorrect-answer-feedback.html   (6.3 KB)
├── 05-show-me-button.html              (6.4 KB)
├── 06-highlighted-passage.html         (7.5 KB)
├── 07-multiple-choice-question.html    (7.1 KB)
├── 08-true-false-question.html         (8.2 KB)
├── 09-timer-countdown.html             (9.1 KB)
├── 10-font-controls.html               (11 KB)
├── 11-detailed-feedback-correct.html   (7.5 KB)
├── 12-detailed-feedback-incorrect.html (8.9 KB)
└── README.md                           (this file)
```

**Total Size:** ~106 KB (all files)

## Notes for Screenshot Capture

1. **Viewport:** Always use 1280x720 for consistency
2. **Zoom:** Set to 100% (no zoom)
3. **Scrolling:** Some files show scrollable content - capture both viewport and full page
4. **Modals:** File 02 shows modal centered - may need full page screenshot
5. **Colors:** Verify color accuracy (especially green #4caf50, red #f44336)
6. **Text:** Ensure all text is readable and not cut off
7. **Responsive:** On smaller screens, layout switches to single column - adjust as needed

## Recent Updates

- ✅ Created all 12 demo files with unique features
- ✅ Used Band 7.5 (32/40) throughout for consistency
- ✅ Implemented proper CSS styling from frontend.css
- ✅ Added realistic IELTS content
- ✅ Ensured self-contained HTML files
- ✅ Optimized for 1280x720 screenshots

---

**Created:** January 16, 2025
**Total Files:** 12 distinct demo HTML files
**Ready for:** Screenshot capture and documentation
