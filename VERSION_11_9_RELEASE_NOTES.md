# Version 11.9 - Release Notes

**Release Date:** January 11, 2026

## Overview

Version 11.9 implements the transcript answer highlighting feature that was documented in Version 11.8 but never actually coded. This critical fix ensures that question markers (Q1, Q2, etc.) appear with yellow background highlighting on the actual answer text in listening test transcripts.

## Problem Statement

Users reported that:
1. Yellow Q badges appeared at the beginning of sentences rather than near the actual answers
2. The answer text itself had no yellow background highlighting
3. This made it difficult to identify which part of the transcript contained the answer

See original issue: https://github.com/impact2021/ielts-preparation-course/blob/main/main/Fucking-Useless.png

## What Was Fixed

### Issue 1: No Answer Text Highlighting
**Problem:** Version 11.8 release notes documented a feature to wrap answer text in yellow highlighting, but the code was never actually implemented. The PHP functions still used the old pattern `/\[Q(\d+)\]/i` which only created the Q badge without highlighting the answer text.

**Solution:** Implemented the documented feature by:
- Updated regex pattern to `/\[Q(\d+)\]([^\[]*?)(?=\[Q|$)/is` to capture text after Q markers
- Added logic to wrap first sentence or ~100 characters in `<span class="transcript-answer-marker">` 
- Answer text now displays with light yellow background (#fff9c4)

### Issue 2: Q Marker Placement Guidance
**Problem:** Transcripts had `[Q#]` markers placed at the beginning of sentences rather than immediately before the actual answer text.

**Solution:** Created comprehensive placement guide (`TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md`) with:
- Clear rules for where to place markers
- Before/after examples showing correct vs incorrect placement
- Best practices for different question types
- Visual demonstrations

## Files Changed

### Core Template Files (Answer Highlighting Implementation)
1. **templates/single-quiz-listening-exercise.php**
   - Updated `process_transcript_markers()` function
   - Changed pattern from `/\[Q(\d+)\]/i` to `/\[Q(\d+)\]([^\[]*?)(?=\[Q|$)/is`
   - Added answer text wrapping with `.transcript-answer-marker` span

2. **templates/single-quiz-listening-practice.php**
   - Updated `process_transcript_markers_practice()` function
   - Same pattern and highlighting changes as above

3. **templates/single-quiz-computer-based.php**
   - Updated `process_transcript_markers_cbt()` function
   - Same pattern and highlighting changes as above

### Version Update
4. **ielts-course-manager.php**
   - Updated plugin version from 11.6 to 11.9
   - Updated IELTS_CM_VERSION constant to '11.9'

### Documentation
5. **TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md** (NEW)
   - Comprehensive guide for transcript authors
   - Rules and examples for proper `[Q#]` marker placement
   - Before/after comparisons
   - Best practices by question type

## Technical Details

### Regex Pattern Explanation

**New Pattern:** `/\[Q(\d+)\]([^\[]*?)(?=\[Q|$)/is`

Components:
- `\[Q(\d+)\]` - Matches the Q marker (e.g., `[Q1]`) and captures the question number
- `([^\[]*?)` - Captures any text after the marker until the next `[` character (non-greedy)
- `(?=\[Q|$)` - Lookahead: stops at next Q marker or end of string
- `i` - Case insensitive matching
- `s` - Dot matches newlines (for multi-line transcripts)

### Highlighting Logic

The captured answer text is processed to:
1. Extract the first complete sentence (ending with `.`, `!`, `?`, or `\n`)
2. OR take the first ~100 characters if no sentence ending found
3. Wrap in `<span class="transcript-answer-marker">` for yellow highlighting
4. Append any remaining text without highlighting

### Generated HTML Structure

**Before (Version 11.6):**
```html
<span id="transcript-q1" data-question="1">
    <span class="question-marker-badge">Q1</span>
</span>Yes of course. It's Anne Hawberry.
```

**After (Version 11.9):**
```html
<span id="transcript-q1" data-question="1">
    <span class="question-marker-badge">Q1</span>
</span>
<span class="transcript-answer-marker">Anne Hawberry.</span>
```

### CSS Classes Used

- `.question-marker-badge` - Yellow badge (#ffc107) for Q number
- `.transcript-answer-marker` - Light yellow background (#fff9c4) for answer text

Both CSS classes were already present from Version 11.6/11.8; Version 11.9 actually uses them as intended.

## Visual Demonstration

![Version 11.9 Demo](https://github.com/user-attachments/assets/f6259a64-bf0f-4e9f-bbb2-8ab7c5119e69)

The demonstration shows:
- ❌ **Before:** Q1 badge at sentence start, wrong text highlighted
- ✅ **After:** Q1 badge immediately before answer, correct text highlighted

## Important Notes for Content Authors

### Marker Placement is Critical

For this feature to work correctly, `[Q#]` markers MUST be placed immediately before the actual answer text in the transcript.

**Wrong Placement:**
```
Anne: [Q1]Yes of course. It's Anne Hawberry.
```
Result: Highlights "Yes of course." (wrong)

**Correct Placement:**
```
Anne: Yes of course. It's [Q1]Anne Hawberry.
```
Result: Highlights "Anne Hawberry." (correct)

### Updating Existing Transcripts

Existing transcripts with poorly-placed markers will now show highlighting, but it may highlight the wrong text. To fix:

1. Review each transcript
2. Find where each `[Q#]` marker is placed
3. Move it to immediately before the actual answer
4. Test by viewing the rendered transcript

See `TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md` for detailed instructions.

## Testing Recommendations

### 1. Visual Test
- Load a listening test with transcript
- Submit answers (correct or incorrect)
- Click "Show in transcript" link in feedback
- Verify:
  - ✓ Q badge appears in yellow (#ffc107)
  - ✓ Answer text has light yellow background (#fff9c4)
  - ✓ Highlighting wraps appropriate text (sentence or ~100 chars)
  - ✓ Highlighted text actually contains the answer

### 2. Marker Placement Test
- Check that `[Q#]` markers are immediately before answers
- Not at beginning of sentences
- Not at speaker labels
- Right next to the answer text

### 3. Multi-Question Test
- Test with questions that have multiple fields (Q1-5 in single question)
- Verify each Q marker highlights its corresponding answer
- Check that Q6 doesn't show Q2's answer (bug from earlier versions)

### 4. Layout Integrity
- Verify two-column layout still works
- Check mobile responsive behavior
- Ensure no DIV structure issues
- Test on different screen sizes

## Backward Compatibility

All changes are backward compatible. Existing tests will continue to work, though transcripts with poorly-placed `[Q#]` markers will now show highlighting that may not be ideal. 

To get optimal results:
- Update marker placement in existing transcripts following the new guide
- New transcripts should follow placement rules from the start

## Security Considerations

### HTML in Transcripts

Transcript content is stored with HTML formatting (e.g., `<strong>`, `<p>` tags) to preserve structure and emphasis. The highlighting code preserves this HTML by design.

**Important Security Notes:**

1. **Transcript content is trusted**: Transcripts are created and managed by site administrators through the WordPress admin interface, not by end users.

2. **HTML preservation required**: The code does not escape HTML in transcripts because:
   - Transcripts contain intentional HTML formatting (speaker names in `<strong>` tags, etc.)
   - This HTML must be preserved for correct display
   - Escaping would break the transcript layout

3. **Access control**: Transcript creation and editing should only be accessible to trusted administrators. The WordPress admin interface provides this security layer.

4. **Input validation**: If adding transcript editing features in the future, implement:
   - Strict capability checks (e.g., `manage_options` or custom capability)
   - Server-side validation of allowed HTML tags
   - Sanitization using `wp_kses()` with allowed tags whitelist

**Current Implementation:**
```php
// HTML tags in transcripts are preserved intentionally
$output .= '<span class="transcript-answer-marker">' . $trimmed_text . '</span>';
$output .= $remaining_text;
```

This is safe because:
- Transcripts come from database (admin-controlled content)
- No user-submitted content is processed
- Consistent with how transcripts are displayed elsewhere in the codebase


- Update marker placement in existing transcripts following the new guide
- New transcripts should follow placement rules from the start

## Why Version 11.9 Instead of 11.7?

Version 11.8 was documented in release notes but the code was never fully implemented. Since some parts of 11.8 may have been partially implemented elsewhere, we're using 11.9 to clearly indicate this is a new release that completes the 11.8 work.

## Related Documentation

- `TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md` - How to place Q markers correctly
- `VERSION_11_8_RELEASE_NOTES.md` - Original documentation (not fully implemented)
- `VERSION_11_6_FIX_SUMMARY.md` - When Q badge color changed to yellow

## Migration Path

### For Developers
No code changes needed - just update to version 11.9.

### For Content Authors
1. Read `TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md`
2. Review existing transcripts
3. Reposition `[Q#]` markers as needed
4. Test each transcript after updates
5. Follow placement rules for all new transcripts

## Future Improvements

Potential enhancements for future versions:
- Automated tool to suggest optimal marker placement
- Visual editor for placing markers in transcripts
- Validation to warn about markers not near expected answers
- Bulk update tool for fixing marker placement in existing tests

## Summary

Version 11.9 finally implements the answer text highlighting feature that was intended since Version 11.8. The combination of:
- Proper code implementation (capturing and highlighting answer text)
- Clear placement guidelines (where to put Q markers)
- Visual demonstrations (before/after examples)

...ensures that students can easily identify where answers are located in listening test transcripts.

**Key Takeaway:** Place `[Q#]` markers immediately before the actual answer text, and the system will automatically add yellow highlighting to make answers clearly visible.
