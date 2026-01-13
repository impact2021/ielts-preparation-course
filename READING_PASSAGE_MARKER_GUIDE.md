# Reading Passage Marker Guide

## Overview
This guide explains how to add question markers to reading passages so that the "Show in the reading passage" button can highlight the correct text for students.

**⚠️ CRITICAL: Always use the NEW format with `passage-q#` IDs, NOT the old `reading-text-q#` format!**

## Two Methods for Adding Markers

You can use either automatic markers or manual markers, depending on your needs.

### Method 1: Automatic Markers (Recommended for Simple Cases)

Use `[Q#]` markers in your reading passage content. The system will automatically:
- Create invisible anchors with ID `passage-q#`
- Hide the question number badge (unlike listening transcripts)
- Highlight the answer text using smart boundary detection
- Create clickable navigation from the "Show in the reading passage" button

**Example:**
```
Statistics in New Zealand show that [Q1]a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue.
```

**How it works:**
- The `[Q1]` marker is invisible to students
- The text after it (up to the next comma, period, or 50 characters) is highlighted when clicked
- Smart boundary detection stops at natural breakpoints
- Generates: `<span id="passage-q1">...</span><span class="reading-answer-marker">text</span>`

### Method 2: Manual Markers (Full Control)

For precise control over what text gets highlighted, use manual HTML markers.

**Format for Reading Passages:**
```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">exact text to highlight</span>
```

**Key points:**
- **ID format:** Use `passage-q#` for reading passages
- **Class:** Use `reading-answer-marker` for reading passages
- **No question badge** - Reading passages don't show the "Q1" badge

**Example:**
```html
<p>Statistics in New Zealand show that <span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span>.</p>
```

## Comparison: Listening vs Reading Markers

### Listening Transcript Format:
```html
<span id="transcript-q1" data-question="1"><span class="question-marker-badge">Q1</span></span><span class="transcript-answer-marker">It's Anne Hawberry.</span>
```
- Uses `transcript-q#` ID
- Shows visible Q1 badge
- Uses `transcript-answer-marker` class

### Reading Passage Format:
```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">exact text to highlight</span>
```
- Uses `passage-q#` ID
- No visible badge
- Uses `reading-answer-marker` class (different from listening for clarity)

## When to Use Each Method

### Use Automatic Markers ([Q#]) when:
- The answer is a short phrase or sentence
- Smart boundary detection works well (stops at comma, period, etc.)
- You want quick setup

### Use Manual Markers when:
- You need exact control over highlighted text
- The answer spans multiple sentences
- Smart boundary detection doesn't work well
- You want to highlight specific portions

## Complete Example: Manual Markers in Reading Passage

```html
<p>A recent tax study was completed in Australia and close reading of the assembled data revealed that <span id="passage-q7" data-question="7"></span><span class="reading-answer-marker">nearly a third of private companies with annual incomes of AUD200 million or more pay no tax on profits</span> to the Australian government.</p>

<p>Similarly, <span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">statistics in New Zealand show that a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span> yet their parent companies reported, on average, profit margins of over 20 per cent for their global operations.</p>

<p><span id="passage-q2" data-question="2"></span><span class="reading-answer-marker">Worldwide, lost income from unpaid taxes is estimated to come in at 10 per cent of global corporate income.</span></p>
```

## Best Practices

1. **Be precise** - Highlight only the text that answers the question
2. **Avoid excess** - Don't highlight unnecessary context
3. **Test it** - Click the "Show in the reading passage" button to verify
4. **Consistency** - Use the same method throughout a passage
5. **Use correct IDs and classes** - Always use `passage-q#` and `reading-answer-marker` for reading

## How It Works

When a student clicks "Show in the reading passage":

1. The system finds the marker: `#passage-q{number}`
2. It locates the answer text: `.reading-answer-marker[data-question="{number}"]` or the next `.reading-answer-marker`
3. It adds the `.reading-passage-highlight` class (yellow background)
4. It scrolls to that location in the passage

## Migration from Old Formats ⚠️ IMPORTANT

**OLD FORMATS - DO NOT USE:**
- ❌ `id="reading-text-q#" data-question="#"><span class="question-marker-badge">Q#</span></span>` - DEPRECATED
- ❌ `class="reading-text-answer-marker"` - DEPRECATED
- ❌ `class="question-marker-badge"` in reading passages - DEPRECATED

**NEW FORMAT - ALWAYS USE THIS:**
- ✅ `id="passage-q#" data-question="#"></span>` - CORRECT
- ✅ `class="reading-answer-marker"` - CORRECT

**Migration Steps:**
If you have existing reading passages using old formats, update them:
1. Replace `<span id="reading-text-q#" data-question="#"><span class="question-marker-badge">Q#</span></span>` 
   with `<span id="passage-q#" data-question="#"></span>`
2. Replace all `class="reading-text-answer-marker"` with `class="reading-answer-marker"`
3. Remove any `<span class="question-marker-badge">` elements from reading passages

**For Reference Only** (backward compatibility exists but should not be relied upon):
- `id="transcript-q#"` - Old format from listening tests, works but use `passage-q#` for reading
- `class="transcript-answer-marker"` - Old format from listening tests

## Visual Comparison

### Listening Transcript (shows badge):
**Q1** It's Anne Hawberry. ← Yellow badge + highlighted text

### Reading Passage (no badge):
It's Anne Hawberry. ← Only highlighted text (no badge)

## Technical Notes

- Reading passages use `passage-q#` IDs to distinguish from listening's `transcript-q#`
- Reading passages use `reading-answer-marker` class (listening uses `transcript-answer-marker`)
- Both classes share the same CSS styling (yellow highlight)
- The difference is controlled by the `$is_reading` parameter in `process_transcript_markers_cbt()`
- Both methods generate the same output format internally
- The `data-question` attribute is optional but recommended for better targeting
- Backward compatibility maintained for old `transcript-q#` format

## Summary

**For Reading Passages:**
- Use `[Q#]` for automatic highlighting (quick setup) → generates `passage-q#` ID
- Use `<span id="passage-q#"></span><span class="reading-answer-marker">text</span>` for manual control
- Both methods link to the "Show in the reading passage" button
- No question badges are shown (unlike listening)
- Use distinct `passage-q#` IDs and `reading-answer-marker` class (separate from listening)
