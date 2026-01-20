# Listening Test Answer Highlighting Fix

## Issue
User reported: "Why does this keep disappearing?" and requested using only the single standardized highlighting format.

## Problem Description

In IELTS Listening Test 01, Section 1 was using the wrong CSS class:

- **Section 1** incorrectly used: `<span class="listening-answer-marker">the answer section is here</span>`
- This class is NOT the standard format and had no CSS support

## Root Cause

The JSON data for Listening Test 01 Section 1 used a non-standard CSS class (`.listening-answer-marker`) instead of the documented standard format.

## Standard Format (v12.6+)

Per **TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md**, the ONLY accepted format is:

```html
<span id="transcript-q1" data-question="1"><span class="question-marker-badge">Q1</span></span><span class="reading-answer-marker">answer text here</span>
```

**Key requirement:** Use `class="reading-answer-marker"` for BOTH reading AND listening tests.

## Solution

1. **Reverted CSS changes** - Removed `.listening-answer-marker` support (it should never have been added)
2. **Fixed JSON** - Changed `class="listening-answer-marker"` to `class="reading-answer-marker"` in Listening Test 01 Section 1

## Expected Behavior

After this fix:

1. **Before Quiz Submission**: All answer markers are transparent
2. **After Quiz Submission (Listening Tests)**: Answer markers with `class="reading-answer-marker"` are highlighted with yellow background (#fff9c4)
3. **After Quiz Submission (Reading Tests)**: Answer markers remain transparent until "Show me the section" button is clicked
4. **Single Format**: All tests now use `class="reading-answer-marker"` exclusively

## Files Modified

- `assets/css/frontend.css`: Reverted to original state (no `.listening-answer-marker` support)
- `main/Listening Test JSONs/IELTS-Listening-Test-01.json`: Changed to use standard `class="reading-answer-marker"`

## Testing Recommendations

1. Load IELTS Listening Test 01
2. Start the test and submit answers
3. After submission, verify that:
   - Section 1 transcript shows yellow highlighting on answer markers
   - The highlighting works consistently with other sections
   - Uses the standard `reading-answer-marker` class format

## Impact

- Only affects IELTS Listening Test 01 (only test that was using non-standard `.listening-answer-marker` class)
- No breaking changes to other tests
- Now fully compliant with documented standards in TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md
- Single standardized format for all highlighting across the entire system
