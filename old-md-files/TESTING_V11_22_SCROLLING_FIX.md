# Manual Test Plan: Question Navigation Scrolling Fix (v11.22)

## Overview
This test plan verifies the fix for question navigation scrolling issues in CBT quizzes (reading tests, listening tests, etc.).

## Test Environment Setup
1. Install WordPress with IELTS Course Manager plugin v11.22
2. Create or access an Academic Reading Test (CBT layout) with at least 3 questions
3. Ensure the test uses the computer-based layout with question navigation buttons
4. Open the test in a modern browser (Chrome, Firefox, Safari, or Edge)

## Pre-Test Verification
- [ ] Verify plugin version is 11.22
- [ ] Verify the test has question navigation buttons (Q1, Q2, Q3, etc.)
- [ ] Verify the layout is CBT (split screen with reading passage and questions)
- [ ] Verify you can scroll the questions column independently

## Test Cases

### Test Case 1: Sequential Forward Navigation
**Purpose:** Verify questions center correctly when navigating forward

**Steps:**
1. Load the reading test
2. Click Q1 button
3. Observe scroll position
4. Click Q2 button
5. Observe scroll position
6. Click Q3 button
7. Observe scroll position

**Expected Results:**
- Q1 should be centered in the viewport
- Q2 should be centered in the viewport (scrolls down from Q1)
- Q3 should be centered in the viewport (scrolls down from Q2, NOT too far)

**Pass/Fail:** ___________

**Notes:** ________________________________

---

### Test Case 2: Backward Navigation
**Purpose:** Verify questions center correctly when navigating backward

**Steps:**
1. Continue from Test Case 1 (currently at Q3)
2. Click Q2 button
3. Observe scroll position
4. Click Q1 button
5. Observe scroll position

**Expected Results:**
- Q2 should be centered in the viewport (scrolls UP from Q3)
- Q1 should be centered in the viewport (scrolls UP from Q2)

**Pass/Fail:** ___________

**Notes:** ________________________________

---

### Test Case 3: Clean State Navigation (Q3 First)
**Purpose:** Verify Q3 centers correctly when clicked FIRST (without prior navigation)

**Steps:**
1. Reload the page to reset scroll state
2. Click Q3 button directly (skip Q1 and Q2)
3. Observe scroll position

**Expected Results:**
- Q3 should be centered in the viewport
- Position should be identical whether Q3 is clicked first or after Q1→Q2

**Pass/Fail:** ___________

**Notes:** ________________________________

---

### Test Case 4: Random Navigation Pattern
**Purpose:** Verify scrolling works correctly regardless of navigation history

**Steps:**
1. Reload the page to reset scroll state
2. Click buttons in this order: Q2 → Q3 → Q1 → Q3 → Q2 → Q1
3. Observe each scroll position

**Expected Results:**
- Each question should center correctly in the viewport
- No cumulative errors should occur
- Navigation should be smooth and predictable

**Pass/Fail:** ___________

**Notes:** ________________________________

---

### Test Case 5: Repeated Navigation
**Purpose:** Verify repeated clicks on the same question don't cause issues

**Steps:**
1. Click Q1 button
2. Click Q1 button again
3. Click Q2 button
4. Click Q2 button again
5. Click Q1 button

**Expected Results:**
- Repeated clicks on the same question should not cause movement (already centered)
- Switching between questions should still work correctly

**Pass/Fail:** ___________

**Notes:** ________________________________

---

### Test Case 6: Edge Cases
**Purpose:** Test edge cases with first and last questions

**Steps:**
1. Load test with multiple questions (ideally 10+)
2. Click Q1 (first question)
3. Click last question (e.g., Q10, Q15)
4. Click Q1 again
5. Navigate through several questions randomly

**Expected Results:**
- First question centers correctly
- Last question centers correctly even with long scroll distance
- Return to Q1 works correctly (scrolls to top)
- All intermediate navigations work correctly

**Pass/Fail:** ___________

**Notes:** ________________________________

---

### Test Case 7: Different Screen Sizes
**Purpose:** Verify scrolling works on different viewport sizes

**Steps:**
1. Test on desktop (1920x1080)
2. Test on laptop (1366x768)
3. Test on tablet simulation (768x1024)
4. For each size, perform Test Case 1 (Q1 → Q2 → Q3)

**Expected Results:**
- Questions center correctly on all screen sizes
- Centering adjusts appropriately for viewport height

**Pass/Fail (Desktop):** ___________
**Pass/Fail (Laptop):** ___________
**Pass/Fail (Tablet):** ___________

**Notes:** ________________________________

---

### Test Case 8: Multiple Test Types
**Purpose:** Verify fix works for all CBT quiz types

**Test Types to Verify:**
- [ ] Academic Reading Test
- [ ] Listening Practice Quiz
- [ ] Listening Exercise Quiz

**Steps (for each type):**
1. Load the quiz/test
2. Perform Test Case 1 (Q1 → Q2 → Q3)
3. Perform Test Case 2 (Q3 → Q1 backward navigation)

**Expected Results:**
- Fix works consistently across all CBT quiz types
- Scrolling behavior is identical

**Pass/Fail:** ___________

**Notes:** ________________________________

---

## Regression Testing

### Verify Existing Functionality Still Works
- [ ] Reading passage switching still works when clicking questions
- [ ] Audio section switching still works (for listening tests)
- [ ] Question highlighting still works
- [ ] Answer input still functions correctly
- [ ] Quiz submission works
- [ ] Results display works
- [ ] No JavaScript console errors

---

## Browser Compatibility

Test in multiple browsers:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

**Expected:** Consistent behavior across all browsers

---

## Performance Check
- [ ] Scrolling animations are smooth (300ms duration)
- [ ] No lag or jank during navigation
- [ ] No memory leaks after extensive navigation (50+ clicks)

---

## Known Issues from Previous Version

Issues that should be FIXED in v11.22:
- ✗ Q3 scrolling too far down (FIXED - now centers correctly)
- ✗ Q1 scrolling down instead of up on second click (FIXED - now uses absolute positioning)
- ✗ State-dependent behavior (FIXED - calculation is now state-independent)

---

## Test Results Summary

| Test Case | Status | Notes |
|-----------|--------|-------|
| TC1: Sequential Forward | | |
| TC2: Backward Navigation | | |
| TC3: Clean State (Q3 First) | | |
| TC4: Random Pattern | | |
| TC5: Repeated Navigation | | |
| TC6: Edge Cases | | |
| TC7: Screen Sizes | | |
| TC8: Multiple Types | | |
| Regression Tests | | |
| Browser Compatibility | | |
| Performance | | |

---

## Sign-off

**Tester Name:** ___________________________

**Date:** ___________________________

**Version Tested:** 11.22

**Overall Result:** PASS / FAIL / PARTIAL

**Comments:** 
_____________________________________________
_____________________________________________
_____________________________________________
