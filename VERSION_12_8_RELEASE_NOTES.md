# Version 12.8 Release Notes

## Summary

Fixed critical highlighting issues in listening tests and simplified the admin UI by removing redundant dropdown menu. All tests now use a single unified template controlled by a simple checkbox.

## Issues Fixed

### 1. ✅ Yellow highlighting no longer shows automatically on submission
**Problem:** In listening tests, yellow highlighting appeared on all answers immediately after submission, making it too easy to locate answers.

**Solution:** Removed automatic highlighting. Now, highlighting only appears when the user clicks the "Show me" button, consistent with reading tests.

**Files Changed:**
- `assets/css/frontend.css` - Removed `.quiz-submitted[data-test-type="listening"] .reading-answer-marker` auto-highlight rule

### 2. ✅ Highlighting now targets only marked sections, not entire paragraphs
**Problem:** When clicking "Show in transcript" button, the entire paragraph was highlighted instead of just the marked answer section.

**Solution:** Updated JavaScript to highlight only `.reading-answer-marker` elements within the paragraph, not the entire `<p>` tag.

**Files Changed:**
- `assets/js/frontend.js` - Updated `.show-in-transcript-link` click handler

### 3. ✅ Unified button text: "Show me"
**Problem:** Button text varied between "Show in transcript" and "Show me the section of the reading passage", causing confusion.

**Solution:** Changed all feedback buttons to simply say "Show me" for both listening and reading tests.

**Files Changed:**
- `includes/class-quiz-handler.php` - Updated all 6 instances of feedback button text

### 4. ✅ Simplified admin UI - removed redundant dropdown
**Problem:** Layout dropdown only had one option since the unified template was implemented, but was still visible.

**Solution:** Removed dropdown, replaced with hidden input field. Only the checkbox "This is for a listening exercise" controls the test type.

**Files Changed:**
- `includes/admin/class-admin.php` - Removed dropdown select, added hidden field

## Admin UI Reference

### Admin UI - Checkbox UNCHECKED (Reading Test)

```
┌─────────────────────────────────────────────────────────┐
│ Exercise Settings                                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Starting Question Number: [1]                           │
│ Set the first question number for this exercise...      │
│                                                         │
│ ☐ This is for a listening exercise                      │
│ Check this box to enable audio player and transcripts.  │
│ Leave unchecked for reading tests with passages.        │
│                                                         │
│ ☑ Open exercise in popup window                         │
│ When enabled, exercises open in a centered popup...     │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ READING TEXTS SECTION (Visible)                        │
├─────────────────────────────────────────────────────────┤
│ Reading Text 1                                          │
│ [Title input field]                                     │
│ [Content editor with markers: [Q1], [Q2], etc.]        │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ LISTENING AUDIO & TRANSCRIPTS (Hidden)                 │
├─────────────────────────────────────────────────────────┤
└─────────────────────────────────────────────────────────┘
```

### Admin UI - Checkbox CHECKED (Listening Test)

```
┌─────────────────────────────────────────────────────────┐
│ Exercise Settings                                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Starting Question Number: [1]                           │
│ Set the first question number for this exercise...      │
│                                                         │
│ ☑ This is for a listening exercise                      │
│ Check this box to enable audio player and transcripts.  │
│ Leave unchecked for reading tests with passages.        │
│                                                         │
│ ☑ Open exercise in popup window                         │
│ When enabled, exercises open in a centered popup...     │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ READING TEXTS SECTION (Hidden)                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
├─────────────────────────────────────────────────────────┤
│ LISTENING AUDIO & TRANSCRIPTS (Visible)                │
├─────────────────────────────────────────────────────────┤
│ Audio URL: [https://example.com/audio.mp3]             │
│                                                         │
│ Transcript Section 1                                    │
│ [Title input field]                                     │
│ [Content editor with markers: [Q1], [Q2], etc.]        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

## Frontend Behavior Reference

### Reading Test (Checkbox UNCHECKED)

#### Before Submission:
```
┌──────────────────────────┬──────────────────────────────┐
│ READING PASSAGE          │ QUESTIONS                    │
├──────────────────────────┼──────────────────────────────┤
│                          │                              │
│ The industrial revolution│ Question 1:                  │
│ began in Britain. [Q1]   │ When did the revolution      │
│ It started in the late   │ start?                       │
│ 18th century and spread  │ [_______________]            │
│ across Europe.           │                              │
│                          │ Question 2:                  │
│ The main innovation was  │ What was the key innovation? │
│ [Q2] the steam engine,   │ [_______________]            │
│ invented by James Watt.  │                              │
│                          │                              │
│ Answer markers:          │ [Submit Test]                │
│ • Transparent (invisible)│                              │
│                          │                              │
└──────────────────────────┴──────────────────────────────┘
```

#### After Submission (Before Clicking "Show me"):
```
┌──────────────────────────┬──────────────────────────────┐
│ READING PASSAGE          │ FEEDBACK                     │
├──────────────────────────┼──────────────────────────────┤
│                          │                              │
│ The industrial revolution│ ✓ Question 1: Correct!       │
│ began in Britain. [Q1]   │   [Show me]                  │
│ It started in the late   │                              │
│ 18th century and spread  │ ✗ Question 2: Incorrect      │
│ across Europe.           │   The answer is steam engine │
│                          │   [Show me]                  │
│ The main innovation was  │                              │
│ [Q2] the steam engine,   │                              │
│ invented by James Watt.  │                              │
│                          │                              │
│ Answer markers:          │                              │
│ • Still transparent      │                              │
│                          │                              │
└──────────────────────────┴──────────────────────────────┘
```

#### After Clicking "Show me":
```
┌──────────────────────────┬──────────────────────────────┐
│ READING PASSAGE          │ FEEDBACK                     │
├──────────────────────────┼──────────────────────────────┤
│                          │                              │
│ The industrial revolution│ ✓ Question 1: Correct!       │
│ began in Britain. [Q1]   │   [Show me] ← clicked        │
│ ▼ Scrolled here          │                              │
│ It started in the late   │ ✗ Question 2: Incorrect      │
│ 🟨 18th century 🟨 and   │   The answer is steam engine │
│ spread across Europe.    │   [Show me]                  │
│                          │                              │
│ The main innovation was  │                              │
│ [Q2] the steam engine,   │                              │
│ invented by James Watt.  │                              │
│                          │                              │
│ Only the marked text     │                              │
│ "18th century" is        │                              │
│ highlighted in yellow    │                              │
│                          │                              │
└──────────────────────────┴──────────────────────────────┘
```

### Listening Test (Checkbox CHECKED)

#### Before Submission:
```
┌──────────────────────────┬──────────────────────────────┐
│ AUDIO PLAYER             │ QUESTIONS                    │
├──────────────────────────┼──────────────────────────────┤
│                          │                              │
│ 🔊 [▶ Play] [━━━━━] 2:45 │ Question 1:                  │
│                          │ When did the revolution      │
│ Section 1                │ start?                       │
│                          │ [_______________]            │
│ TRANSCRIPTS HIDDEN       │                              │
│ (Not visible until       │ Question 2:                  │
│  after submission)       │ What was the key innovation? │
│                          │ [_______________]            │
│                          │                              │
│                          │ [Submit Test]                │
│                          │                              │
└──────────────────────────┴──────────────────────────────┘
```

#### After Submission (Before Clicking "Show me"):
```
┌──────────────────────────┬──────────────────────────────┐
│ TRANSCRIPT               │ FEEDBACK                     │
├──────────────────────────┼──────────────────────────────┤
│ 🔊 [▶ Play] [━━━━━] 2:45 │                              │
│                          │ ✓ Question 1: Correct!       │
│ ┌─ Section 1 ──┐─────┐   │   [Show me] [Listen]         │
│                          │                              │
│ The industrial revolution│ ✗ Question 2: Incorrect      │
│ began in Britain. [Q1]   │   The answer is steam engine │
│ It started in the late   │   [Show me] [Listen]         │
│ 18th century and spread  │                              │
│ across Europe.           │                              │
│                          │                              │
│ The main innovation was  │                              │
│ [Q2] the steam engine,   │                              │
│ invented by James Watt.  │                              │
│                          │                              │
│ Answer markers:          │                              │
│ • Transparent (invisible)│                              │
│ • NOT auto-highlighted   │                              │
│                          │                              │
└──────────────────────────┴──────────────────────────────┘
```

#### After Clicking "Show me":
```
┌──────────────────────────┬──────────────────────────────┐
│ TRANSCRIPT               │ FEEDBACK                     │
├──────────────────────────┼──────────────────────────────┤
│ 🔊 [▶ Play] [━━━━━] 2:45 │                              │
│                          │ ✓ Question 1: Correct!       │
│ ┌─ Section 1 ──┐─────┐   │   [Show me] [Listen]         │
│                          │   ↑ clicked                  │
│ The industrial revolution│                              │
│ began in Britain. Q1     │ ✗ Question 2: Incorrect      │
│ ▼ Scrolled here          │   The answer is steam engine │
│ It started in the late   │   [Show me] [Listen]         │
│ 🟨 18th century 🟨 and   │                              │
│ spread across Europe.    │                              │
│                          │                              │
│ The main innovation was  │                              │
│ [Q2] the steam engine,   │                              │
│ invented by James Watt.  │                              │
│                          │                              │
│ Only the marked text     │                              │
│ "18th century" is        │                              │
│ highlighted in yellow    │                              │
│                          │                              │
└──────────────────────────┴──────────────────────────────┘
```

## Key Behavior Changes

### Before Version 12.8 (OLD - BROKEN):

**Listening Tests:**
- ❌ Yellow highlighting appeared automatically on ALL answers after submission
- ❌ Clicking "Show in transcript" highlighted the ENTIRE paragraph
- ❌ Button text was "Show in transcript" (inconsistent)

**Admin UI:**
- ❌ Dropdown with only one option (redundant)
- ❌ Confusing interface with duplicate controls

### After Version 12.8 (NEW - FIXED):

**Listening Tests:**
- ✅ No automatic highlighting - markers stay transparent until button is clicked
- ✅ Clicking "Show me" highlights ONLY the marked text (e.g., "18th century")
- ✅ Button text is "Show me" (consistent with reading tests)

**Reading Tests:**
- ✅ Same behavior as before (no breaking changes)
- ✅ "Show me" button text (simplified from "Show me the section of the reading passage")

**Admin UI:**
- ✅ No dropdown - just a simple checkbox
- ✅ Clear, intuitive interface
- ✅ Same template for both test types

## Technical Details

### CSS Changes (assets/css/frontend.css)

**Removed automatic highlighting:**
```css
/* OLD - REMOVED */
.quiz-submitted[data-test-type="listening"] .reading-answer-marker {
    background-color: #fff9c4; /* Auto-highlight - BAD! */
}

/* NEW - CORRECT */
.quiz-submitted[data-test-type="listening"] .reading-answer-marker,
.quiz-submitted[data-test-type="reading"] .reading-answer-marker {
    background-color: transparent; /* Only highlight on button click */
}
```

### JavaScript Changes (assets/js/frontend.js)

**Improved highlighting logic:**
```javascript
// OLD - Highlighted entire paragraph
var $paragraph = $questionMarker.closest('p');
if ($paragraph.length) {
    $paragraph.addClass('transcript-highlight'); // Too broad!
}

// NEW - Highlights only the marked section
var $answerMarker = $questionMarker.nextAll('.reading-answer-marker').first();
if ($answerMarker.length) {
    $answerMarker.addClass('transcript-highlight'); // Precise!
}
```

### PHP Changes (includes/class-quiz-handler.php)

**Unified button text:**
```php
// OLD
__('Show in transcript', 'ielts-course-manager')
__('Show me the section of the reading passage', 'ielts-course-manager')

// NEW
__('Show me', 'ielts-course-manager') // For both test types
```

### Admin Changes (includes/admin/class-admin.php)

**Simplified UI:**
```php
// OLD - Dropdown with one option (redundant)
<select id="ielts_cm_layout_type" name="ielts_cm_layout_type">
    <option value="two_column_reading">2 Column Test (Reading or Listening)</option>
</select>

// NEW - Hidden field + checkbox only
<input type="hidden" name="ielts_cm_layout_type" value="two_column_reading">

<input type="checkbox" name="ielts_cm_is_listening_exercise" value="1">
This is for a listening exercise
```

## Files Modified

1. `ielts-course-manager.php` - Version bumped to 12.8
2. `assets/css/frontend.css` - Removed automatic highlighting
3. `assets/js/frontend.js` - Fixed paragraph highlighting issue
4. `includes/class-quiz-handler.php` - Unified button text to "Show me"
5. `includes/admin/class-admin.php` - Removed dropdown, simplified UI

## Backward Compatibility

✅ All existing tests continue to work
✅ Old `two_column_listening` layout type automatically converts to checkbox
✅ Old `two_column_reading` layout type works as before
✅ No database migrations required
✅ No content changes required

## Testing Recommendations

1. Create a new reading test (checkbox unchecked)
   - Verify passages show, transcripts hidden
   - Verify "Show me" button works and highlights only marked text
   
2. Create a new listening test (checkbox checked)
   - Verify audio player shows, passages hidden
   - Verify transcripts hidden until submission
   - Verify NO automatic highlighting after submission
   - Verify "Show me" button highlights only marked text, not entire paragraph
   
3. Test existing reading tests
   - Verify no breaking changes
   
4. Test existing listening tests
   - Verify highlighting now requires button click
   - Verify precise highlighting (not entire paragraphs)

## Version History

- **12.8** - Fixed highlighting issues, simplified admin UI, unified button text
- **12.7** - Unified template implementation with checkbox control
- **12.6** - Previous version before highlighting fixes
