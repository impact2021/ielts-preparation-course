# Reading Passage Marker Guide

## Overview
This guide explains how to add question markers to reading passages so that the "Show in the reading passage" button can highlight the correct text for students.

## Two Methods for Adding Markers

You can use either automatic markers or manual markers, depending on your needs.

### Method 1: Automatic Markers (Recommended for Simple Cases)

Use `[Q#]` markers in your reading passage content. The system will automatically:
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

### Method 2: Manual Markers (Full Control)

For precise control over what text gets highlighted, use manual HTML markers just like in listening transcripts.

**Format:**
```html
<span id="transcript-q1" data-question="1"></span><span class="transcript-answer-marker">exact text to highlight</span>
```

**Key differences from listening:**
- **No question badge** - Reading passages don't show the "Q1" badge
- **Same ID format** - Use `transcript-q#` (not `reading-text-q#`)
- **Same class** - Use `transcript-answer-marker`

**Example:**
```html
Statistics in New Zealand show that <span id="transcript-q1" data-question="1"></span><span class="transcript-answer-marker">a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span>.
```

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
<p>A recent tax study was completed in Australia and close reading of the assembled data revealed that <span id="transcript-q7" data-question="7"></span><span class="transcript-answer-marker">nearly a third of private companies with annual incomes of AUD200 million or more pay no tax on profits</span> to the Australian government.</p>

<p>Similarly, <span id="transcript-q1" data-question="1"></span><span class="transcript-answer-marker">statistics in New Zealand show that a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span> yet their parent companies reported, on average, profit margins of over 20 per cent for their global operations.</p>
```

## Best Practices

1. **Be precise** - Highlight only the text that answers the question
2. **Avoid excess** - Don't highlight unnecessary context
3. **Test it** - Click the "Show in the reading passage" button to verify
4. **Consistency** - Use the same method throughout a passage

## How It Works

When a student clicks "Show in the reading passage":

1. The system finds the marker: `#transcript-q{number}`
2. It locates the answer text: `.transcript-answer-marker[data-question="{number}"]` or the next `.transcript-answer-marker`
3. It adds the `.reading-passage-highlight` class (yellow background)
4. It scrolls to that location in the passage

## Migration from Old Format

If you have existing reading passages using:
- `id="reading-text-q#"` → Change to `id="transcript-q#"`
- `class="reading-text-answer-marker"` → Change to `class="transcript-answer-marker"`

The new format matches the listening transcript format for consistency.

## Visual Comparison

### Listening Transcript (shows badge):
**Q1** It's Anne Hawberry. ← Yellow badge + highlighted text

### Reading Passage (no badge):
It's Anne Hawberry. ← Only highlighted text (no badge)

## Technical Notes

- Reading passages use the same marker system as listening transcripts
- The difference is controlled by the `$is_reading` parameter in `process_transcript_markers_cbt()`
- Both methods generate the same output format internally
- The `data-question` attribute is optional but recommended for better targeting

## Summary

**For Reading Passages:**
- Use `[Q#]` for automatic highlighting (quick setup)
- Use `<span id="transcript-q#"></span><span class="transcript-answer-marker">text</span>` for manual control
- Both methods link to the "Show in the reading passage" button
- No question badges are shown (unlike listening)
- Use the same format as listening transcripts for consistency
