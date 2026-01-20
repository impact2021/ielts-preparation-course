# Version 11.18 Visual Summary

## Manual Reading Passage Markers

This update enables full manual control over reading passage highlighting, with clear separation from listening transcripts.

---

## Visual Comparison

### Before Clicking "Show in the reading passage"
```
Regular reading text without any highlighting.
```

### After Clicking "Show in the reading passage"
```
Regular text [HIGHLIGHTED TEXT IN YELLOW] more regular text.
```

---

## Marker Format Comparison

### 📻 LISTENING TRANSCRIPTS

**Format:**
```html
<span id="transcript-q1" data-question="1">
    <span class="question-marker-badge">Q1</span>
</span>
<span class="transcript-answer-marker">It's Anne Hawberry.</span>
```

**Visual Result:**
```
[Q1] It's Anne Hawberry.
 ↑        ↑
Badge  Yellow highlight
```

**Key Features:**
- ID: `transcript-q#`
- Class: `transcript-answer-marker`
- Shows visible Q1 badge
- Yellow highlight on answer

---

### 📖 READING PASSAGES

**Format:**
```html
<span id="passage-q1" data-question="1"></span>
<span class="reading-answer-marker">statistics show profit margins</span>
```

**Visual Result:**
```
statistics show profit margins
        ↑
Yellow highlight (no badge)
```

**Key Features:**
- ID: `passage-q#`
- Class: `reading-answer-marker`
- NO visible badge
- Yellow highlight on answer

---

## Two Methods for Reading Passages

### Method 1: Automatic Markers (Quick)

**Input:**
```
Statistics show that [Q1]profit margins averaged 1.3 per cent.
```

**Generates:**
```html
<span id="passage-q1" data-question="1"></span>
<span class="reading-answer-marker reading-answer-highlight">profit margins averaged 1.3 per cent</span>
```

**Use when:** Answer is a short phrase with natural boundaries (comma, period)

---

### Method 2: Manual Markers (Precise Control)

**Input:**
```html
Statistics show that <span id="passage-q1" data-question="1"></span>
<span class="reading-answer-marker">profit margins averaged 1.3 per cent</span>.
```

**Use when:** You need exact control over what text is highlighted

---

## Side-by-Side Comparison

| Feature | Listening | Reading |
|---------|-----------|---------|
| **ID Format** | `transcript-q1` | `passage-q1` |
| **Answer Class** | `transcript-answer-marker` | `reading-answer-marker` |
| **Question Badge** | ✅ Visible (Q1) | ❌ Hidden |
| **Highlight Color** | 🟡 Yellow | 🟡 Yellow |
| **Manual Control** | ✅ Yes | ✅ Yes (NEW) |
| **Auto Markers** | ✅ [Q#] | ✅ [Q#] |

---

## Example: Full Reading Passage

```html
<p>A recent tax study revealed that 
<span id="passage-q7" data-question="7"></span>
<span class="reading-answer-marker">nearly a third of private companies 
pay no tax on profits</span> to the government.</p>

<p>Similarly, 
<span id="passage-q1" data-question="1"></span>
<span class="reading-answer-marker">statistics in New Zealand show that 
multi-national earners reported an average profit of just 1.3 per cent
</span> yet their parent companies reported profit margins of over 20 per cent.</p>

<p><span id="passage-q2" data-question="2"></span>
<span class="reading-answer-marker">Worldwide, lost income from unpaid taxes 
is estimated at 10 per cent of global corporate income.</span></p>
```

---

## Student Experience

### Step 1: Answer Question
Student sees question: "What percentage of global corporate income goes untaxed?"

### Step 2: Click "Show in the reading passage"
Button appears after quiz submission

### Step 3: Passage Highlights
- Reading column scrolls to Question 2
- The text "Worldwide, lost income from unpaid taxes is estimated at 10 per cent of global corporate income." appears with yellow background
- Student can clearly see where the answer is located

---

## Benefits

✅ **Precision:** Manually control exactly what text is highlighted  
✅ **Clarity:** Distinct naming (`passage-` vs `transcript-`)  
✅ **Flexibility:** Choose automatic or manual markers  
✅ **Consistency:** Same approach as listening transcripts  
✅ **Backward Compatible:** Old formats still work  

---

## Migration Guide

### Old Format (Still Works)
```html
<span id="transcript-q1"></span>
<span class="transcript-answer-marker">text</span>
```

### New Format (Recommended)
```html
<span id="passage-q1"></span>
<span class="reading-answer-marker">text</span>
```

**Why change?** Better clarity - immediately know if you're working with listening or reading content.

---

## Quick Reference

### For Content Creators:

**Want quick setup?**
→ Use `[Q1]` markers in your reading text

**Need precise control?**
→ Use `<span id="passage-q#"></span><span class="reading-answer-marker">exact text</span>`

**Working on listening?**
→ Use `<span id="transcript-q#"><span class="question-marker-badge">Q#</span></span><span class="transcript-answer-marker">text</span>`

---

## Version 11.18 Summary

🎉 **Now Live:** Manual reading passage markers with full control over highlighting  
📍 **Distinct IDs:** `passage-q#` for reading, `transcript-q#` for listening  
🎨 **Distinct Classes:** `reading-answer-marker` vs `transcript-answer-marker`  
🔄 **Backward Compatible:** Old formats continue to work  
📚 **Documented:** Complete guide in READING_PASSAGE_MARKER_GUIDE.md  
