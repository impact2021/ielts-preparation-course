# Reading Passage Marker Guide

## Overview
This guide explains how to add question markers to reading passages and listening transcripts so that the "Show in passage" button can highlight the correct text for students.

**⚠️ CRITICAL: Both reading passages and listening transcripts now use the same standardized format!**

## Standardized Format (Version 12.6+)

Both reading passages and listening transcripts now use the **same format** for answer highlighting:

```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">this is the highlighted answer area</span>
```

For listening transcripts, use `transcript-q#` for the ID but the same `reading-answer-marker` class:

```html
<span id="transcript-q1" data-question="1"><span class="question-marker-badge">Q1</span></span><span class="reading-answer-marker">this is the highlighted answer area</span>
```

**Key points:**
- **Reading passages:** Use `id="passage-q#"` with `class="reading-answer-marker"`
- **Listening transcripts:** Use `id="transcript-q#"` with `class="reading-answer-marker"` (NOT transcript-answer-marker)
- **Both use the same class:** `reading-answer-marker` for consistency
- **Question badge:** Listening shows visible Q# badge, reading does not

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
- **Multiple markers:** You can use the same `id="passage-q#"` multiple times to highlight different sections for one question
  - *Note: While HTML standards recommend unique IDs, the system is designed to handle multiple elements with the same `passage-q#` ID for this specific use case*

**Example:**
```html
<p>Statistics in New Zealand show that <span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span>.</p>
```

**Multiple Sections for One Question:**
```html
<p>Something something something <span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">proof of the answer</span>. More text, more text, more text etc.</p>

<p>Additional paragraph. <span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">and this is the second part that proves Q1</span>.</p>
```
When the student clicks "Show me the section of the reading passage" for Q1, **both** highlighted sections will be shown simultaneously.

## Comparison: Listening vs Reading Markers (UPDATED v12.6)

Both now use the same `reading-answer-marker` class for consistency.

### Listening Transcript Format:
```html
<span id="transcript-q1" data-question="1"><span class="question-marker-badge">Q1</span></span><span class="reading-answer-marker">It's Anne Hawberry.</span>
```
- Uses `transcript-q#` ID
- Shows visible Q1 badge
- Uses `reading-answer-marker` class (standardized in v12.6)

### Reading Passage Format:
```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">exact text to highlight</span>
```
- Uses `passage-q#` ID
- No visible badge
- Uses `reading-answer-marker` class

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
- **You need to highlight multiple separate sections for one question**

## Complete Example: Manual Markers in Reading Passage

```html
<p>A recent tax study was completed in Australia and close reading of the assembled data revealed that <span id="passage-q7" data-question="7"></span><span class="reading-answer-marker">nearly a third of private companies with annual incomes of AUD200 million or more pay no tax on profits</span> to the Australian government.</p>

<p>Similarly, <span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">statistics in New Zealand show that a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span> yet their parent companies reported, on average, profit margins of over 20 per cent for their global operations.</p>

<p><span id="passage-q2" data-question="2"></span><span class="reading-answer-marker">Worldwide, lost income from unpaid taxes is estimated to come in at 10 per cent of global corporate income.</span></p>
```

## Example: Multiple Markers for One Question

Sometimes evidence for a single answer appears in multiple locations. You can use the same question number multiple times:

```html
<p>The first piece of evidence shows that <span id="passage-q3" data-question="3"></span><span class="reading-answer-marker">companies use various tax avoidance strategies</span> to minimize their obligations.</p>

<p>Some intervening text that is not relevant to the answer...</p>

<p>Later in the passage, we see additional proof: <span id="passage-q3" data-question="3"></span><span class="reading-answer-marker">these strategies include transfer pricing and profit shifting</span> which reduce taxable income.</p>
```

When students click "Show me the section of the reading passage" for Q3, **both highlighted sections** will appear simultaneously, allowing them to see all relevant evidence at once.

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
- ❌ `class="transcript-answer-marker"` - DEPRECATED (use reading-answer-marker)
- ❌ `class="question-marker-badge"` in reading passages - DEPRECATED

**NEW FORMAT - ALWAYS USE THIS (v12.6+):**
- ✅ `id="passage-q#" data-question="#"></span>` - CORRECT for reading
- ✅ `id="transcript-q#" data-question="#"></span>` - CORRECT for listening
- ✅ `class="reading-answer-marker"` - CORRECT for BOTH reading and listening

**Migration Steps:**
If you have existing content using old formats, update them:
1. Replace `<span id="reading-text-q#" data-question="#"><span class="question-marker-badge">Q#</span></span>` 
   with `<span id="passage-q#" data-question="#"></span>`
2. Replace all `class="reading-text-answer-marker"` with `class="reading-answer-marker"`
3. Replace all `class="transcript-answer-marker"` with `class="reading-answer-marker"`
4. Remove any `<span class="question-marker-badge">` elements from reading passages (keep for listening)

## Visual Comparison

### Listening Transcript (shows badge):
**Q1** It's Anne Hawberry. ← Yellow badge + highlighted text

### Reading Passage (no badge):
It's Anne Hawberry. ← Only highlighted text (no badge)

## Technical Notes

- Reading passages use `passage-q#` IDs to distinguish from listening's `transcript-q#`
- **Both reading and listening now use `reading-answer-marker` class (v12.6+)**
- Both share the same CSS styling (yellow highlight)
- The difference is controlled by the `$is_reading` parameter in `process_transcript_markers_cbt()`
- Both methods generate the same output format internally
- The `data-question` attribute is optional but recommended for better targeting
- Backward compatibility maintained for old `transcript-answer-marker` format

## Summary

**For Reading Passages:**
- Use `[Q#]` for automatic highlighting (quick setup) → generates `passage-q#` ID
- Use `<span id="passage-q#"></span><span class="reading-answer-marker">text</span>` for manual control
- Both methods link to the "Show in the reading passage" button
- No question badges are shown (unlike listening)

**For Listening Transcripts (v12.6+):**
- Use `<span id="transcript-q#"><span class="question-marker-badge">Q#</span></span><span class="reading-answer-marker">text</span>`
- Shows visible Q# badge
- Uses the same `reading-answer-marker` class as reading passages for consistency
