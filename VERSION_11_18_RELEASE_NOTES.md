# Version 11.18 Release Notes

## Manual Reading Passage Markers

Version 11.18 introduces support for manual reading passage markers, giving content creators full control over what text gets highlighted when students click "Show in the reading passage."

## What's New

### Distinct Marker Formats for Listening vs Reading

**Listening Transcripts:**
```html
<span id="transcript-q1" data-question="1"><span class="question-marker-badge">Q1</span></span><span class="transcript-answer-marker">It's Anne Hawberry.</span>
```
- Uses `transcript-q#` ID prefix
- Shows visible question badge (Q1, Q2, etc.)
- Uses `transcript-answer-marker` class

**Reading Passages:**
```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">exact text to highlight</span>
```
- Uses `passage-q#` ID prefix (NEW)
- No visible badge
- Uses `reading-answer-marker` class (NEW)

### Key Features

1. **Manual Control**: Content creators can now manually specify exactly what text should be highlighted in reading passages, just like they can in listening transcripts.

2. **Clear Separation**: Different ID prefixes and classes for listening vs reading provide ongoing clarity:
   - Listening: `transcript-q#` + `transcript-answer-marker`
   - Reading: `passage-q#` + `reading-answer-marker`

3. **Backward Compatibility**: The system still supports:
   - Automatic `[Q#]` markers (converted to the new format)
   - Old `transcript-q#` IDs in reading passages (for legacy content)

4. **Consistent Styling**: Both `transcript-answer-marker` and `reading-answer-marker` use the same yellow highlight styling, but remain separate classes for clarity.

## Changes Made

### PHP (`templates/single-quiz-computer-based.php`)
- Updated `process_transcript_markers_cbt()` function to generate:
  - `passage-q#` IDs for reading passages
  - `reading-answer-marker` class for reading passages
  - Maintains `transcript-q#` and `transcript-answer-marker` for listening

### JavaScript (`assets/js/frontend.js`)
- Updated "Show in the reading passage" click handler to:
  - Look for `#passage-q#` IDs first (new format)
  - Fall back to `#transcript-q#` IDs (backward compatibility)
  - Recognize both `.reading-answer-marker` and `.reading-answer-highlight` classes

### CSS (`assets/css/frontend.css`)
- Added `.reading-answer-marker` styles (same as `.transcript-answer-marker`)
- Updated rules to hide/show both marker types before/after quiz submission

### Documentation
- Created comprehensive `READING_PASSAGE_MARKER_GUIDE.md`
- Includes examples, best practices, and migration guide
- Explains when to use automatic vs manual markers

## How to Use

### Automatic Markers (Quick Setup)
```
Statistics show that [Q1]manual markers work perfectly in reading passages.
```

### Manual Markers (Full Control)
```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">exact text to highlight</span>
```

## Benefits

1. **Precision**: Manually control exactly what text is highlighted
2. **Clarity**: Distinct naming makes it clear whether you're working with listening or reading content
3. **Flexibility**: Choose between automatic markers for speed or manual markers for precision
4. **Consistency**: Same format approach as listening transcripts

## Migration Notes

If you have existing reading passages with manual markers using the old format:
- `id="transcript-q#"` → Change to `id="passage-q#"` (recommended)
- `class="transcript-answer-marker"` → Change to `class="reading-answer-marker"` (recommended)

The old format will continue to work for backward compatibility, but the new format is recommended for clarity.

## Version History

- **Version 11.18** - Added manual reading passage markers with distinct `passage-q#` IDs and `reading-answer-marker` class
- **Version 11.17** - Previous version
