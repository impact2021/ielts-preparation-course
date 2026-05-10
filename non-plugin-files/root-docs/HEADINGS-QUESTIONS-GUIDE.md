# Heading-Style Questions Guide for IELTS Course Manager

## Overview
This guide explains the correct format for heading-style questions in IELTS Academic Reading tests, specifically for "Choose the correct heading" questions.

## Important Rules

### ✅ CORRECT FORMAT

For heading-style questions, the `mc_options[].text` field **MUST** include both the numeral/letter AND the full heading text, matching exactly the format used in the `options` field.

**Example 1: Roman Numerals (uppercase)**
```json
{
  "type": "closed_question",
  "instructions": "Choose the correct heading for paragraphs B-D and F from the list of headings below.\n\nType the correct number I-VIII in boxes 1 – 4.",
  "question": "Paragraph B",
  "mc_options": [
    {
      "text": "I. Customer inequality",
      "is_correct": false,
      "feedback": "Incorrect. The correct answer is VIII..."
    },
    {
      "text": "II. Monopoly control",
      "is_correct": false,
      "feedback": "Incorrect. The correct answer is VIII..."
    },
    {
      "text": "VIII. Already expensive, yet always on the increase",
      "is_correct": true,
      "feedback": "Correct! Paragraph B discusses..."
    }
  ],
  "options": "I. Customer inequality\nII. Monopoly control\nIII. Historic changes in dependence\nIV. State statistics\nV. Shift in responsibilities increases vulnerability\nVI. Preying on the unemployed\nVII. Identifying worst-hit victims\nVIII. Already expensive, yet always on the increase"
}
```

**Example 2: Roman Numerals (lowercase)**
```json
{
  "type": "closed_question",
  "instructions": "Choose the correct heading for paragraphs B-D, F and H from the list of headings below.\n\nType the correct number i-viii in boxes 16 – 20",
  "question": "Paragraph B",
  "mc_options": [
    {
      "text": "i. Aesthetic design",
      "is_correct": false,
      "feedback": "Incorrect..."
    },
    {
      "text": "v. Changing uses",
      "is_correct": true,
      "feedback": "Correct! Paragraph B describes..."
    },
    {
      "text": "viii. Alternative methods of transportation",
      "is_correct": false,
      "feedback": "Incorrect..."
    }
  ],
  "options": "i. Aesthetic design\nii. Current significance\niii. The man behind the achievement\niv. Penitentiary statistics\nv. Changing uses\nvi. Defending the city\nvii. Realising a dream\nviii. Alternative methods of transportation"
}
```

**Example 3: Letters**
```json
{
  "type": "closed_question",
  "instructions": "Complete each sentence with the correct ending A-H below",
  "question": "The meaning of 'Luau' traditionally referred to…",
  "mc_options": [
    {
      "text": "A. should be divided amongst the community",
      "is_correct": false,
      "feedback": "Incorrect..."
    },
    {
      "text": "F. a vegetable used in Hawai'ian cuisine.",
      "is_correct": true,
      "feedback": "Correct! The passage states..."
    }
  ],
  "options": "A. should be divided amongst the community\nB. has changed over time\nC. a celebration of the success of a warrior.\nD. as a sign of warmth and kindness\nE. only to women\nF. a vegetable used in Hawai'ian cuisine.\nG. to indicate marital status\nH. must be shared with the gods"
}
```

### ❌ INCORRECT FORMAT

**DO NOT** use only the numeral/letter without the heading text:

```json
// ❌ WRONG - Only shows Roman numeral
{
  "text": "I",
  "is_correct": false
}

// ❌ WRONG - Only shows lowercase Roman numeral
{
  "text": "v",
  "is_correct": true
}

// ❌ WRONG - Only shows letter
{
  "text": "A",
  "is_correct": false
}
```

## Why This Matters

1. **User Experience**: Students need to see the full heading text to make an informed choice, not just a numeral/letter
2. **Accessibility**: Screen readers and assistive technologies need the full text
3. **Consistency**: All IELTS question types should provide complete information in the answer options
4. **Real IELTS Format**: In actual IELTS tests, students see the full list of headings, not just numerals

## Pattern to Match

The `mc_options[].text` field should **EXACTLY** match the corresponding line in the `options` field:

```json
"mc_options": [
  {"text": "I. Customer inequality", ...},      // Matches first line of options
  {"text": "II. Monopoly control", ...}     // Matches second line of options
],
"options": "I. Customer inequality\nII. Monopoly control\n..."
```

## Format Variations

Different tests may use different separators:

- **Period-space**: `"I. Customer inequality"` (most common in reading tests)
- **Space-dash-space**: `"i - Researchers conclude"` (used in some tests)
- **Letter-period**: `"A. should be divided"` (used for matching sentence endings)

Always check the `options` field to determine which format to use, and ensure `mc_options[].text` matches it exactly.

## Validation Checklist

When creating or reviewing heading-style questions:

- [ ] Check that `mc_options[].text` includes both numeral/letter AND full heading text
- [ ] Verify format matches the `options` field exactly (same separator)
- [ ] Ensure all options in `mc_options` array have full text, not just numerals
- [ ] Confirm that the format is consistent across all options in the same question

## Examples in Repository

**Correct implementations:**
- `Academic-IELTS-Reading-Test-11.json` - Uses `"i - Heading text"` format
- `Academic-IELTS-Reading-Test-12.json` - Uses `"I. Heading text"` and `"i. Heading text"` formats

## Common Mistakes to Avoid

1. **Copying only the numeral**: Don't just use `"I"`, `"II"`, etc.
2. **Mixing formats**: Don't use `"I - Heading"` when options use `"I. Heading"`
3. **Forgetting spacing**: `"I.Customer"` is wrong, should be `"I. Customer"`
4. **Inconsistent case**: If options use lowercase `"i. heading"`, don't use uppercase `"I. heading"`

## Testing Your Changes

After creating heading-style questions:

1. Import the JSON into WordPress
2. View the question in the student interface
3. Verify that students see the full heading text, not just numerals
4. Check that the format is clear and readable

## Related Documentation

- `IMPORT_OPTIONS_GUIDE.md` - General JSON import guidelines
- `TEMPLATES/example-exercise.json` - Example JSON structure
- `DEVELOPMENT-GUIDELINES.md` - Overall development guidelines

---

**Last Updated:** January 14, 2026  
**Issue Reference:** GitHub issue about heading-style closed questions in Academic-IELTS-Reading-Test-12.json
