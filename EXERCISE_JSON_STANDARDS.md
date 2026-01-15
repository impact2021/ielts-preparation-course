# Exercise JSON Standards - CRITICAL REFERENCE

**⚠️ CRITICAL: This document MUST be reviewed before making ANY changes to exercise JSON files.**

## Version Control
- **Document Version:** 1.0
- **Last Updated:** 2026-01-15

---

## Heading Questions

### Numbering Format
- Headings in heading questions **MUST** use **UPPERCASE ROMAN NUMERALS**
- Format: `I.`, `II.`, `III.`, `IV.`, `V.`, `VI.`, `VII.`, `VIII.`, `IX.`, `X.`, etc.
- Example: "I. The leading authority", "II. Financial concerns"

### Highlighting Behavior
- The highlight span **MUST** start at the beginning of the paragraph
- The highlight span **MUST** close at the end of the paragraph that the question relates to
- This ensures the entire paragraph is highlighted when "Show me the section" is clicked

---

## Open Questions (Fill-in-the-Blank)

### Placeholder Format
- Open questions **MUST** have a `[field n]` placeholder in the question text
- Example: "The bionic arm contains [field 1] that detect electrical signals..."

### Multi-Field Questions
- Some open questions have multiple placeholders (e.g., Q11-15 in a single question, Q30-34)
- Each field should be numbered sequentially: `[field 1]`, `[field 2]`, `[field 3]`, etc.
- The question number should reflect the range (e.g., "Q11" for a 5-field question that spans Q11-15, or "Q30" for Q30-34)

### Validation Rule - CRITICAL
**The number of entries in `field_answers` MUST equal `field_count`**
- If `field_count: 5`, then `field_answers` must have keys "1", "2", "3", "4", "5"
- Each field must also have corresponding entries in `field_feedback` with "correct", "incorrect", and "no_answer" messages
- **This is a common error - always verify after adding/updating multi-field questions**


---

## Answer Highlighting - Reading vs Listening

**CRITICAL: Only TWO types of answer highlighting are used:**

### Reading Tests
**Format:** `<span id="passage-q#" data-question="#"></span><span class="reading-answer-marker">text to highlight</span>`

**Highlighting Rules:**
1. **For heading questions:** 
   - Place `<span id="passage-q#" data-question="#">` at the start of the paragraph
   - Do NOT use `<span class="reading-answer-marker">` wrapper
   - The entire paragraph will be highlighted
   
2. **For all other questions (specific answers):**
   - Place `<span id="passage-q#" data-question="#"></span>` before the answer
   - Wrap the answer text in `<span class="reading-answer-marker">text</span>`
   - Include surrounding context if it helps students understand why that's the answer
   - The highlighted text should support student learning

### Listening Tests
**Format:** `<span id="transcript-q#" data-question="#"><span class="question-marker-badge">Q#</span></span><span class="transcript-answer-marker">answer text</span>`

**Key Difference:** Listening tests show the visible question number badge (Q1, Q2, etc.)

---

## General Rules

### ID Format
- Reading: `passage-q#` (e.g., `passage-q1`, `passage-q12`)
- Listening: `transcript-q#` (e.g., `transcript-q1`, `transcript-q12`)

### Class Names
- Reading answer highlighting: `reading-answer-marker`
- Listening answer highlighting: `transcript-answer-marker`
- Question badge (listening only): `question-marker-badge`

### No Classes on ID Spans
- The ID span itself should NOT have any classes
- Classes are only used on the answer marker spans

---

## Prohibited Formats

❌ **DO NOT USE:**
- Automatic `[Q#]` markers (legacy format, not for new/updated exercises)
- `class="reading-passage-marker"` on ID spans
- `class="reading-text-answer-marker"` (deprecated)
- Different formats for different question types within the same test type

✅ **DO USE:**
- Manual HTML spans with explicit IDs
- Consistent formatting across all questions in a test
- Only the two approved formats (Reading vs Listening)

---

## Checklist Before Updating Exercise JSONs

- [ ] Have I reviewed this EXERCISE_JSON_STANDARDS.md document?
- [ ] Are heading questions using UPPERCASE ROMAN NUMERALS (I., II., III., etc.)?
- [ ] Do open questions have proper `[field n]` placeholders?
- [ ] **For multi-field questions: Does `field_answers` have all fields matching `field_count`?**
- [ ] **For multi-field questions: Does `field_feedback` have all fields with correct/incorrect/no_answer messages?**
- [ ] Am I using the correct format for Reading vs Listening?
- [ ] For heading questions, does the highlight span cover the entire paragraph?
- [ ] For specific answer questions, does the highlighted text include helpful context?
- [ ] Have I avoided using prohibited legacy formats?
- [ ] Have I updated the version number in the JSON if content changed?

---

## Examples

### Heading Question (Reading)
```html
<p><strong>A</strong><span id="passage-q1" data-question="1"></span> The history of prosthetics dates back thousands of years. Archaeological evidence suggests...</p>
```
Question options: "I. History of prosthetics", "II. Modern advances", etc.

### Specific Answer Question (Reading)
```html
<p>For individuals like James Baird, who lost his leg below the knee <span id="passage-q7" data-question="7"></span><span class="reading-answer-marker">due to complications from diabetes</span>, modern prosthetics offer...</p>
```

### Open Question with Multiple Fields
```json
{
  "question": "Technology has allowed us to (30) [field 1] at home instead of the office. For the company, this means (31) [field 2] can be reduced...",
  "field_count": 5,
  "field_answers": {
    "1": "conduct business",
    "2": "fixed costs",
    "3": "flexibility",
    "4": "benefits",
    "5": "psychological"
  }
}
```

---

**Remember: This document is CRITICAL. Always check it before making changes to exercise JSONs.**
