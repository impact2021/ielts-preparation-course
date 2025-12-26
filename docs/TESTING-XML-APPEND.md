# Manual Testing Plan for XML Import Append Mode

## Test Setup

### Prerequisites
- WordPress installation with IELTS Course Manager plugin active
- Access to admin panel
- Two XML template files from TEMPLATES directory

### Test Environment
- Browser: Any modern browser
- User: Admin user with edit_posts capability

## Test Cases

### Test Case 1: Basic Append Functionality

**Objective:** Verify that questions are appended to existing exercise

**Steps:**
1. Create a new exercise (ielts_quiz)
2. Add 2-3 questions manually using the quiz editor
3. Save the exercise
4. Note the number of questions (should be 2-3)
5. Go to "Import/Export XML" meta box
6. Select "Add to existing content" radio button
7. Upload `TEMPLATE-short-answer.xml` (has 2 questions)
8. Click "Upload & Import XML"
9. Wait for success message
10. Reload page

**Expected Result:**
- Success message: "Exercise content added successfully!"
- Total questions should be 4-5 (original 2-3 + 2 from XML)
- Original questions should remain unchanged
- New questions should appear after original questions
- Exercise title should remain unchanged
- Exercise settings should remain unchanged

---

### Test Case 2: Replace Mode (Original Behavior)

**Objective:** Verify replace mode still works correctly

**Steps:**
1. Create or edit an existing exercise with some questions
2. Note the exercise title and number of questions
3. Go to "Import/Export XML" meta box
4. Select "Replace all content" radio button
5. Upload `TEMPLATE-multiple-choice.xml`
6. Confirm the warning dialog
7. Click "Upload & Import XML"
8. Wait for success message
9. Reload page

**Expected Result:**
- Success message: "Exercise content replaced successfully!"
- Exercise title should change to match XML
- All questions should be replaced with questions from XML
- Exercise settings should match XML settings
- Previous questions should no longer exist

---

### Test Case 3: Reading Text ID Remapping

**Objective:** Verify reading texts are appended with correct ID remapping

**Steps:**
1. Create a new exercise
2. Add a reading text manually (should get ID 0)
3. Add 2 questions that reference reading text ID 0
4. Save the exercise
5. Export the exercise to verify structure (optional)
6. Upload XML with reading texts using "Add to existing content" mode
   - Use `TEMPLATE-short-answer.xml` (has Demo Reading Passage with ID 0)
7. Wait for success message
8. Reload page
9. Export the exercise again to check the structure

**Expected Result:**
- Original reading text should still have ID 0
- New reading text from XML should have ID 1 (remapped from 0)
- Original questions should still reference reading text ID 0
- New questions from XML should reference reading text ID 1 (remapped)
- Both reading texts should be visible and functional

---

### Test Case 4: UI Behavior

**Objective:** Verify UI changes work correctly

**Steps:**
1. Edit any exercise
2. Go to "Import/Export XML" meta box
3. Observe the radio buttons and messages

**Expected Result:**
- Two radio buttons are visible:
  - "Replace all content (overwrites everything)" 
  - "Add to existing content (keeps current questions and adds new ones)"
- "Replace all content" is selected by default
- Warning message is shown: "Replace mode will overwrite all current content. Export a backup first!"
- When switching to "Add to existing content":
  - Warning message should hide
  - Info message should show: "Questions and reading texts from the XML will be added after your current content."
- When switching back to "Replace all content":
  - Info message should hide
  - Warning message should show again

---

### Test Case 5: Confirmation Dialogs

**Objective:** Verify confirmation messages are appropriate for each mode

**Steps:**
1. Edit any exercise
2. Go to "Import/Export XML" meta box
3. Select a valid XML file

**For Replace Mode:**
4. Select "Replace all content" radio button
5. Click "Upload & Import XML"
6. Check the confirmation dialog text

**Expected:** "This will replace all current exercise content. Are you sure you want to continue?"

**For Append Mode:**
7. Cancel the dialog
8. Select "Add to existing content" radio button
9. Click "Upload & Import XML"
10. Check the confirmation dialog text

**Expected:** "This will add the XML content to your current exercise. Are you sure you want to continue?"

---

### Test Case 6: Empty Exercise Append

**Objective:** Verify append mode works on empty exercise

**Steps:**
1. Create a new exercise with no questions
2. Save it
3. Use "Add to existing content" mode to import XML
4. Upload `TEMPLATE-true-false.xml`
5. Reload page

**Expected Result:**
- Questions from XML should be added
- Should behave same as replace mode but preserve title/settings
- No errors should occur

---

### Test Case 7: Multiple Appends

**Objective:** Verify multiple successive appends work correctly

**Steps:**
1. Create a new exercise with 1 question
2. Save it
3. Append `TEMPLATE-short-answer.xml` (2 questions)
   - Total should be 3 questions
4. Reload and verify
5. Append `TEMPLATE-true-false.xml` (2 questions)
   - Total should be 5 questions
6. Reload and verify
7. Export the exercise to check structure

**Expected Result:**
- Each append should add questions after existing ones
- Reading text IDs should be remapped correctly each time
- No duplicate reading texts
- All questions should reference correct reading texts
- Question order should be: original, short-answer, true-false

---

## Edge Cases to Test

### Edge Case 1: XML with No Questions
- Import XML file with no questions in append mode
- Should show error or skip gracefully

### Edge Case 2: XML with No Reading Texts
- Import XML with questions but no reading texts
- Questions should be appended
- No reading text errors should occur

### Edge Case 3: Large XML File
- Import XML with 40+ questions (use TEMPLATE-FULL-TEST.xml)
- In append mode with existing questions
- Should complete successfully without timeout

### Edge Case 4: Invalid XML
- Try to import invalid/corrupted XML
- Should show error message
- Existing content should remain unchanged

---

## Validation Checklist

After each test:
- [ ] No PHP errors in debug.log
- [ ] No JavaScript console errors
- [ ] Page loads correctly after import
- [ ] Questions display correctly on frontend
- [ ] Can submit and grade the exercise successfully
- [ ] Export XML shows correct structure

---

## Regression Testing

Verify that existing functionality still works:
- [ ] Manual question creation/editing
- [ ] Exercise settings (layout, timer, scoring)
- [ ] Export XML (both modes should export same structure)
- [ ] Delete questions
- [ ] Reorder questions
- [ ] Add/edit reading texts manually

---

## Performance Testing

For large exercises:
- [ ] Import 40 questions in append mode - should complete in < 5 seconds
- [ ] Exercise with 80+ questions still loads in admin
- [ ] Export of large exercise completes successfully

---

## Browser Testing

Test on multiple browsers:
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari (Mac)

---

## Notes for Tester

**Common Issues to Watch For:**
1. Reading text ID conflicts causing wrong text to display for questions
2. Questions being duplicated instead of appended
3. Settings being changed when they shouldn't be (in append mode)
4. Success messages not appearing or incorrect
5. Page not reloading after import

**Tips:**
- Always export before testing replace mode
- Check the exported XML structure to verify IDs are correct
- Use browser dev tools to check for JavaScript errors
- Enable WordPress debug mode to see PHP errors
