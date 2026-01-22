# Transcript Marker Placement Guide - Version 12.9

## Overview
This guide explains how to properly place markers in listening test transcripts using the unified HTML span format.

## Important: Unified Format (v12.9+)

**All exercise types (reading and listening) now use the SAME simplified format:**

```html
<span id="q1" data-question="1"></span><span class="reading-answer-marker">answer text here</span>
```

**Key changes in v12.9:**
- ✅ Use `id="q#"` for ALL exercise types (no more `passage-q#` or `q#`)
- ✅ Use `class="reading-answer-marker"` for ALL exercise types
- ✅ NO badge span needed - clean and simple format
- This provides consistent styling and functionality across all exercise types

**Do NOT use the `[Q#]` format in listening transcripts.** The `[Q#]` format is only for automatic conversion in reading passages.

## The Problem (Fixed in Version 11.9)

Previously, Q markers were often placed at the beginning of sentences, causing:
1. Irrelevant text to be highlighted instead of the answer itself
2. Confusion for students trying to identify where the answer is located

## The Solution

**Place markers immediately before the actual answer text using the unified format.**

### Unified Format for All Exercise Types:
```html
<span id="q1" data-question="1"></span><span class="reading-answer-marker">answer text</span>
```

### Format Components:
1. **ID span**: `<span id="q1" data-question="1"></span>` - Anchor with unique ID and question number
2. **Answer marker span**: `<span class="reading-answer-marker">answer text</span>` - Highlighted answer text

## Placement Rules

### Rule 1: Place Marker Immediately Before Answer
The transcript marker should be positioned as close as possible to the actual answer text.

**❌ WRONG:**
```html
Anne: <span id="q1" data-question="1"></span><span class="reading-answer-marker">Yes of course. It's Anne Hawberry.</span>
```
This highlights "Yes of course. It's Anne Hawberry." instead of just "Anne Hawberry"

**✅ CORRECT:**
```html
Anne: Yes of course. It's <span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry</span>.
```
This correctly highlights only "Anne Hawberry."

### Rule 2: For Short Answers (Single Words/Numbers)
Place the marker directly before the answer word or number.

**✅ Examples:**
```html
The program runs for <span id="q2" data-question="2"></span><span class="reading-answer-marker">three weeks</span>.

Each session lasts <span id="q5" data-question="5"></span><span class="reading-answer-marker">one hour</span>.

The cost is <span id="q4" data-question="4"></span><span class="reading-answer-marker">£7.95</span>.
```

### Rule 3: For Multi-Word Answers
Place the marker at the start of the answer phrase.

**✅ Examples:**
```html
I arrived <span id="q1" data-question="1"></span><span class="reading-answer-marker">two months ago</span>.

My name is <span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry</span>.

We're located on the <span id="q9" data-question="9"></span><span class="reading-answer-marker">2nd floor</span>.
```

### Rule 4: For Longer Answers or Sentences
Place the marker at the beginning of the sentence that contains the answer.

**✅ Example:**
```html
Woman: I'm interested in the settlement support programme. <span id="q1" data-question="1"></span><span class="reading-answer-marker">I arrived in the country two months ago</span>.
```

### Rule 5: Within Table Cells
For table-formatted transcripts, place the marker within the appropriate cell where the answer is spoken.

**✅ Example:**
```html
<tr>
    <td><strong>Anne:</strong></td>
    <td>Yes of course. It's <span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry</span>.</td>
</tr>
```

## How the Highlighting Works

The HTML span format ensures:

1. **Displays the Q badge** - The `question-marker-badge` span shows a yellow badge with the question number (#ffc107)
2. **Wraps answer text** - The `reading-answer-marker` span highlights the answer with a light yellow background (#fff9c4)
3. **Precise highlighting** - The answer text is explicitly enclosed within the `reading-answer-marker` span, ensuring only the answer is highlighted

## Visual Result

When placed correctly, students will see:

- **Q1** ← Yellow badge with question number
- **Anne Hawberry** ← Light yellow background on answer text

Both elements appear together, making it crystal clear where the answer is located in the transcript.

## Common Mistakes to Avoid

### ❌ Mistake 1: Using [Q#] Format Instead of HTML
```
[Q1]Anne: Yes of course. It's Anne Hawberry.
```
Problem: Wrong format for listening transcripts. Use HTML spans instead.

**✅ CORRECT:**
```html
<span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry</span>
```

### ❌ Mistake 2: Marker Too Early in Sentence
```html
Anne: <span id="q1" data-question="1"></span><span class="reading-answer-marker">Yes of course. It's Anne Hawberry</span>.
```
Problem: Highlights wrong text - includes "Yes of course"

**✅ CORRECT:**
```html
Anne: Yes of course. It's <span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry</span>.
```

### ❌ Mistake 3: Missing Closing Span
```html
Anne: It's <span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry.
```
Problem: Unclosed span tag will break rendering

**✅ CORRECT:**
```html
Anne: It's <span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry</span>.
```

### ❌ Mistake 4: Incorrect ID Format
```html
<span id="question-1"></span><span class="reading-answer-marker">Anne Hawberry</span>
```
Problem: ID must be `q1` not `question-1`

**✅ CORRECT:**
```html
<span id="q1" data-question="1"></span><span class="reading-answer-marker">Anne Hawberry</span>
```

## Best Practices

1. **Review the question** to understand what the answer is
2. **Find the exact answer text** in the transcript
3. **Place the marker** immediately before that text using the HTML span format
4. **Verify the closing span** is present after the answer text

## Examples from Real Tests

### Example 1: Single Word Answer
```html
<p><strong>Barry:</strong> Yes, I've been working in corporate hospitality at a local hotel for the last <span id="q21" data-question="21"></span><span class="reading-answer-marker">three years</span>; we arrange functions and conferences for business clients.</p>
```
**Placement:** Marker before "three years" (the answer)

### Example 2: Short Phrase Answer
```html
<p><strong>Barry:</strong> My employer will pay for the <span id="q22" data-question="22"></span><span class="reading-answer-marker">course fees and a proportion of my living costs</span>, but of course only if I work for them full-time as well.</p>
```
**Placement:** Marker before the complete answer phrase

### Example 3: Number Answer
```html
<p><strong>Kathryn:</strong> There are <span id="q27" data-question="27"></span><span class="reading-answer-marker">24 modules</span> in total; whichever way you study you have to complete all of them.</p>
```
**Placement:** Marker before the number and its unit

### Example 4: Table Format
```html
<tr>
    <td valign="top">Professor Ripley</td>
    <td valign="top">Well, it is very difficult to measure it accurately. Figures range from 100 000, to as few as 30 000, but it is generally estimated that there are <span id="q25" data-question="25"></span><span class="reading-answer-marker">50 000</span>. In order to maintain the population and protect the species from poachers, many are moved to protected areas.</td>
</tr>
```
**Placement:** Marker within the table cell before the answer

## Important Note: Reading vs Listening Formats

- **Listening transcripts**: Use `<span id="q1">` format (as shown in this guide)
- **Reading passages**: Use `[Q1]` format which is automatically converted to `<span id="passage-q1">` by the system

Do not mix these formats. Always use the HTML span format for listening transcripts.

## Version History

- **Version 12.5** - Updated documentation to reflect correct HTML span format for listening transcripts
- **Version 11.10** - Improved smart answer boundary detection (stops at commas, semicolons, 50-char limit)
- **Version 11.9** - Implemented automatic answer text highlighting with yellow background
- **Version 11.8** - Documented intended behavior (not fully implemented)
- **Version 11.6** - Changed Q badge color from blue to yellow

## Summary

**Golden Rule:** For listening transcripts, always use the HTML span format with `<span id="q#">` immediately before the actual answer text.

When creating listening transcripts, place the marker so that the `reading-answer-marker` span contains only the answer text that students need to identify.

## Common Issues and Troubleshooting

### Issue: Transcript Not Showing in Admin UI

**Symptom:** After importing a JSON file, the transcript doesn't appear in the WordPress admin interface or on the front-end.

**Common Causes:**

1. **Missing Answer Markers** (Most Common)
   - The transcript text is incomplete or has answer text removed
   - Look for incomplete sentences like "which involves ." or "need to pay for ,"
   - **Solution:** Ensure transcript has complete sentences with proper answer markers

2. **Wrong Layout Type**
   - `layout_type` is set to `two_column_reading` instead of `two_column_listening`
   - **Solution:** Set `"layout_type": "two_column_listening"` in settings

3. **Empty Transcript Field**
   - The `audio.transcript` field is empty or contains only whitespace
   - **Solution:** Ensure transcript contains the full text with markers

**How to Fix:**

```json
{
  "settings": {
    "layout_type": "two_column_listening",  // NOT two_column_reading
    "scoring_type": "ielts_listening_band"  // For IELTS listening tests
  },
  "audio": {
    "url": "https://example.com/audio.mp3",
    "transcript": "<p>Full text here <span id=\"q1\" data-question=\"1\"></span><span class=\"reading-answer-marker\">answer text</span> continuing...</p>"
  }
}
```

**Validation Checklist:**
- ✅ Transcript field is not empty
- ✅ Transcript contains complete sentences (no "which involves ." patterns)
- ✅ All answer markers are present (count should match number of questions)
- ✅ Each marker uses format: `<span id="q#" data-question="#"></span><span class="reading-answer-marker">ANSWER</span>`
- ✅ `layout_type` is `two_column_listening` (not `two_column_reading`)
- ✅ `scoring_type` is `ielts_listening_band` (for listening tests)

### Issue: Answer Markers Stripped Out After Export

**Symptom:** When you export a quiz from WordPress and re-import it, the answer markers are missing.

**Cause:** WordPress may process the transcript and remove the markers during export/import cycle.

**Solution:** Always keep a clean copy of your JSON file with markers before importing. After exporting from WordPress, the transcript may need markers re-added manually or from your backup.

**Prevention:**
1. Keep original JSON files with markers in version control
2. Don't rely on WordPress export as your source of truth
3. Use the original JSON files as templates for new exercises

