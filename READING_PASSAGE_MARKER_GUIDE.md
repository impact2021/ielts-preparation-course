# Reading Passage Marker Guide

## Overview
This guide explains how to add question markers to reading passages and listening transcripts so that the "Show me" button can highlight the correct text for students.

**⚠️ CRITICAL: Both reading passages and listening transcripts now use the EXACT SAME simplified format! (Version 12.9+)**

## Unified Format (Version 12.9+)

Both reading passages and listening transcripts use **ONE simple format**:

```html
<span id="q1" data-question="1"></span><span class="reading-answer-marker">this is the highlighted answer area</span>
```

**Key points:**
- **Same format for everything:** Use `id="q#"` for all exercise types (reading and listening)
- **No badge span:** The format is clean and simple
- **Same class:** Both use `class="reading-answer-marker"` for consistency
- **Easy to remember:** Just `q1`, `q2`, `q3`, etc.

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
- Generates: `<span id="q1">...</span><span class="reading-answer-marker">text</span>`

### Method 2: Manual Markers (Full Control)

For precise control over what text gets highlighted, use manual HTML markers.

**Unified Format (for both Reading and Listening):**
```html
<span id="q1" data-question="1"></span><span class="reading-answer-marker">exact text to highlight</span>
```

**Key points:**
- **ID format:** Use `q#` for all exercise types (q1, q2, q3, etc.)
- **Class:** Use `reading-answer-marker` for all exercise types
- **No question badge** - Clean and simple format
- **Multiple markers:** You can use the same `id="q#"` multiple times to highlight different sections for one question
  - *Note: While HTML standards recommend unique IDs, the system is designed to handle multiple elements with the same `q#` ID for this specific use case*

**Example:**
```html
<p>Statistics in New Zealand show that <span id="q1" data-question="1"></span><span class="reading-answer-marker">a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span>.</p>
```

**Multiple Sections for One Question:**
```html
<p>Something something something <span id="q1" data-question="1"></span><span class="reading-answer-marker">proof of the answer</span>. More text, more text, more text etc.</p>

<p>Additional paragraph. <span id="q1" data-question="1"></span><span class="reading-answer-marker">and this is the second part that proves Q1</span>.</p>
```
When the student clicks "Show me" for Q1, **both** highlighted sections will be shown simultaneously.

## Unified Format (Version 12.9+)

Both reading and listening use the **exact same format**:

```html
<span id="q1" data-question="1"></span><span class="reading-answer-marker">It's Anne Hawberry.</span>
```

No difference between reading and listening - **same format for everything**.

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

## Complete Example: Manual Markers

```html
<p>A recent tax study was completed in Australia and close reading of the assembled data revealed that <span id="q7" data-question="7"></span><span class="reading-answer-marker">nearly a third of private companies with annual incomes of AUD200 million or more pay no tax on profits</span> to the Australian government.</p>

<p>Similarly, <span id="q1" data-question="1"></span><span class="reading-answer-marker">statistics in New Zealand show that a list of 20 of the top multi-national earners in New Zealand reported an average profit of just 1.3 per cent for New Zealand-generated revenue</span> yet their parent companies reported, on average, profit margins of over 20 per cent for their global operations.</p>

<p><span id="q2" data-question="2"></span><span class="reading-answer-marker">Worldwide, lost income from unpaid taxes is estimated to come in at 10 per cent of global corporate income.</span></p>
```

## Example: Multiple Markers for One Question

Sometimes evidence for a single answer appears in multiple locations. You can use the same question number multiple times:

```html
<p>The first piece of evidence shows that <span id="passage-q3" data-question="3"></span><span class="reading-answer-marker">companies use various tax avoidance strategies</span> to minimize their obligations.</p>

<p>Some intervening text that is not relevant to the answer...</p>

<p>Later in the passage, we see additional proof: <span id="passage-q3" data-question="3"></span><span class="reading-answer-marker">these strategies include transfer pricing and profit shifting</span> which reduce taxable income.</p>
```

When students click "Show me" for Q3, **both highlighted sections** will appear simultaneously, allowing them to see all relevant evidence at once.

## Best Practices

1. **Be precise** - Highlight only the text that answers the question
2. **Avoid excess** - Don't highlight unnecessary context
3. **Test it** - Click the "Show me" button to verify
4. **Consistency** - Use the same method throughout a passage
5. **Use correct IDs and classes** - Always use `q#` and `reading-answer-marker`

## How It Works

When a student clicks "Show me":

1. The system finds the marker: `#q{number}`
2. It locates the answer text: `.reading-answer-marker[data-question="{number}"]` or the next `.reading-answer-marker`
3. It adds the `.reading-passage-highlight` class (yellow background)
4. It scrolls to that location in the passage

## Migration from Old Formats ⚠️ IMPORTANT

**OLD FORMATS - DO NOT USE:**
- ❌ `id="passage-q#"` - DEPRECATED (use `q#`)
- ❌ `id="transcript-q#"` - DEPRECATED (use `q#`)
- ❌ `id="reading-text-q#" data-question="#"><span class="question-marker-badge">Q#</span></span>` - DEPRECATED
- ❌ `class="reading-text-answer-marker"` - DEPRECATED
- ❌ `class="transcript-answer-marker"` - DEPRECATED (use reading-answer-marker)
- ❌ `<span class="question-marker-badge">Q#</span>` - DEPRECATED (no badge span needed)

**NEW UNIFIED FORMAT (v12.9+):**
- ✅ `id="q#" data-question="#"></span>` - CORRECT for ALL exercise types
- ✅ `class="reading-answer-marker"` - CORRECT for BOTH reading and listening

**Migration Steps:**
If you have existing content using old formats, update them:
1. Replace `<span id="passage-q#" ...` or `<span id="transcript-q#" ...` with `<span id="q#" ...`
2. Remove any `<span class="question-marker-badge">Q#</span>` elements
3. Replace all `class="reading-text-answer-marker"` with `class="reading-answer-marker"`
4. Replace all `class="transcript-answer-marker"` with `class="reading-answer-marker"`

## Visual Display

Both reading and listening show the same thing: highlighted text (no badge).

Example: It's Anne Hawberry. ← Only highlighted text (yellow background)

## Technical Notes

- All exercise types now use unified `q#` IDs (simplified in v12.9+)
- **All exercise types use `reading-answer-marker` class**
- Both share the same CSS styling (yellow highlight)
- Both methods generate the same output format internally
- The `data-question` attribute is optional but recommended for better targeting

## Summary

**ONE UNIFIED FORMAT FOR EVERYTHING (v12.9+):**
- Use `[Q#]` for automatic highlighting (quick setup) → generates `q#` ID
- Use `<span id="q#"></span><span class="reading-answer-marker">text</span>` for manual control
- Both methods link to the "Show me" button
- Same format for reading passages, listening transcripts, and all other exercise types
- No badges, no complexity - just one simple pattern to remember
