# Testing Checklist - CBT Fullscreen Fix

## Prerequisites
1. Have at least one CBT exercise with `_ielts_cm_open_as_popup` enabled
2. Be logged in as a user with access to courses
3. Have a course with lessons containing CBT exercises

## Test Case 1: Text Link Opens Fullscreen (PRIMARY FIX)
**Location:** Lesson page, content items table  
**Goal:** Verify text link opens fullscreen directly

### Steps:
1. Navigate to a lesson page that contains a CBT exercise with popup enabled
2. Locate the exercise title in the "Lesson Content" table
3. Click on the exercise title (text link)

### Expected Results:
- [ ] Page opens in fullscreen mode (no theme header/footer)
- [ ] URL contains `?fullscreen=1` parameter
- [ ] Quiz form displays immediately (no "Open in Fullscreen" button shown)
- [ ] Timer starts counting down (if configured)
- [ ] Reading text is visible in left column
- [ ] Questions are visible in right column
- [ ] Navigation buttons are visible at bottom

### Actual Results:
```
[Document your results here]
```

---

## Test Case 2: "Start CBT Exercise" Button Opens Fullscreen (PRIMARY FIX)
**Location:** Lesson page, content items table, Action column  
**Goal:** Verify button opens fullscreen directly, same as text link

### Steps:
1. Navigate to the same lesson page
2. Locate the "Start CBT Exercise" button in the Action column
3. Click the button

### Expected Results:
- [ ] Page opens in fullscreen mode (no theme header/footer)
- [ ] URL contains `?fullscreen=1` parameter
- [ ] Quiz form displays immediately (no "Open in Fullscreen" button shown)
- [ ] Timer starts counting down (if configured)
- [ ] Reading text is visible in left column
- [ ] Questions are visible in right column
- [ ] Navigation buttons are visible at bottom
- [ ] **Behavior is IDENTICAL to text link in Test Case 1**

### Actual Results:
```
[Document your results here]
```

---

## Test Case 3: Timer Functionality in Fullscreen
**Location:** Fullscreen quiz page  
**Goal:** Verify timer works correctly

### Steps:
1. Open a CBT exercise in fullscreen mode (using either link or button)
2. Observe the timer at the top of the quiz form
3. Wait and watch timer count down

### Expected Results:
- [ ] Timer displays correct initial time (matches quiz settings)
- [ ] Timer counts down every second
- [ ] Timer format is MM:SS (e.g., "40:00" for 40 minutes)
- [ ] Timer color changes to orange at 5 minutes remaining
- [ ] Timer color changes to red at 1 minute remaining
- [ ] When timer reaches 0:00, an alert appears
- [ ] Quiz auto-submits when timer expires

### Actual Results:
```
[Document your results here]
```

---

## Test Case 4: Reading Text Visibility and Navigation
**Location:** Fullscreen quiz page  
**Goal:** Verify reading text doesn't disappear

### Steps:
1. Open a CBT exercise with multiple reading texts
2. Scroll through questions in right column
3. Click navigation buttons at bottom

### Expected Results:
- [ ] Reading text is visible in left column throughout quiz
- [ ] Scrolling questions doesn't affect reading text visibility
- [ ] Clicking navigation buttons switches reading text if linked to question
- [ ] Reading text doesn't disappear when submitting quiz
- [ ] Left column (reading) scrolls independently from right column (questions)

### Actual Results:
```
[Document your results here]
```

---

## Test Case 5: Navigation Buttons Functionality
**Location:** Fullscreen quiz page, bottom navigation  
**Goal:** Verify navigation buttons work correctly

### Steps:
1. Open a CBT exercise in fullscreen mode
2. Answer a few questions
3. Click various navigation buttons (question numbers)
4. Observe the behavior

### Expected Results:
- [ ] Navigation buttons display question numbers (1, 2, 3, etc.)
- [ ] Clicking a button scrolls to that question in right column
- [ ] Answered questions show highlighted navigation buttons
- [ ] Scroll position updates correctly when clicking buttons
- [ ] Navigation remains sticky at bottom during scroll

### Actual Results:
```
[Document your results here]
```

---

## Test Case 6: Answer Highlighting After Submission
**Location:** Fullscreen quiz page after submission  
**Goal:** Verify answers are highlighted correctly

### Steps:
1. Open a CBT exercise in fullscreen mode
2. Answer all questions (mix of correct and incorrect answers)
3. Click "Submit Quiz" button
4. Review the highlighted answers

### Expected Results:
- [ ] Correct answers have green highlight/border
- [ ] Incorrect answers have red highlight/border
- [ ] Navigation buttons update to show green (correct) or red (incorrect)
- [ ] User's answers are clearly marked
- [ ] For incorrect answers, correct answer is shown (if applicable)
- [ ] Results summary displays at top or in modal

### Actual Results:
```
[Document your results here]
```

---

## Test Case 7: CBT Exercise WITHOUT Popup Enabled
**Location:** Lesson page with standard CBT exercise  
**Goal:** Verify normal behavior still works

### Steps:
1. Navigate to a lesson with a CBT exercise where `_ielts_cm_open_as_popup` is NOT enabled
2. Click text link or button

### Expected Results:
- [ ] Page opens in NORMAL mode (with theme header/footer)
- [ ] "Open in Fullscreen" link/button is shown on the page
- [ ] Quiz form is hidden initially
- [ ] Clicking "Open in Fullscreen" opens fullscreen mode
- [ ] All fullscreen features work after clicking the link

### Actual Results:
```
[Document your results here]
```

---

## Test Case 8: Standard (Non-CBT) Quiz
**Location:** Lesson page with standard quiz  
**Goal:** Verify standard quizzes still work normally

### Steps:
1. Navigate to a lesson with a standard (non-CBT) quiz
2. Click link or button to open quiz

### Expected Results:
- [ ] Quiz opens in normal page format (with theme header/footer)
- [ ] Questions display in vertical list (not two-column layout)
- [ ] Timer works if enabled
- [ ] Submit and results work correctly
- [ ] No fullscreen mode offered

### Actual Results:
```
[Document your results here]
```

---

## Test Case 9: Security Testing
**Goal:** Verify no XSS or injection vulnerabilities

### Steps:
1. Try URL manipulation: `?fullscreen=<script>alert('xss')</script>`
2. Try URL manipulation: `?fullscreen=1'; DROP TABLE wp_posts; --`
3. Try URL manipulation: `?fullscreen=true`
4. Try URL manipulation: `?fullscreen=0`
5. Inspect page source for unescaped output

### Expected Results:
- [ ] Malicious scripts are NOT executed
- [ ] SQL injection attempts have no effect
- [ ] Only `?fullscreen=1` (exact value) triggers fullscreen mode
- [ ] All other values show normal page with "Open in Fullscreen" button
- [ ] All output in HTML is properly escaped
- [ ] No JavaScript errors in console

### Actual Results:
```
[Document your results here]
```

---

## Test Case 10: Browser Compatibility
**Goal:** Verify functionality across different browsers

### Browsers to Test:
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari (if available)
- [ ] Edge
- [ ] Mobile browsers (iOS Safari, Chrome Android)

### Expected Results:
- [ ] Fullscreen mode works in all browsers
- [ ] Timer functions correctly in all browsers
- [ ] Layout displays properly (two-column) in all browsers
- [ ] Navigation works smoothly in all browsers
- [ ] No console errors in any browser

### Actual Results:
```
Browser: [Name]
Status: [Pass/Fail]
Issues: [List any issues]

Browser: [Name]
Status: [Pass/Fail]
Issues: [List any issues]
```

---

## Summary

### Critical Issues Found:
```
[List any critical issues that prevent functionality]
```

### Minor Issues Found:
```
[List any minor issues or improvements needed]
```

### Overall Test Result:
- [ ] PASS - All critical functionality working
- [ ] PASS WITH MINOR ISSUES - Works but needs small fixes
- [ ] FAIL - Critical issues prevent deployment

### Tester Information:
- **Name:** _______________
- **Date:** _______________
- **Environment:** _______________ (Dev/Staging/Production)
- **WordPress Version:** _______________
- **Plugin Version:** _______________

### Approval:
- [ ] Approved for deployment
- [ ] Needs fixes before deployment
- [ ] Rejected

**Signature:** _______________
**Date:** _______________
