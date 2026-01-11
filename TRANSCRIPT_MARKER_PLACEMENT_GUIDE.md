# Transcript Marker Placement Guide - Version 11.10

## Overview
This guide explains how to properly place `[Q#]` markers in listening test transcripts to ensure the question badges and answer highlighting appear in the correct locations.

## The Problem (Fixed in Version 11.9)

Previously, Q markers were often placed at the beginning of sentences, causing:
1. The yellow Q badge to appear far from the actual answer
2. Irrelevant text to be highlighted instead of the answer itself
3. Confusion for students trying to identify where the answer is located

## The Solution

**Place `[Q#]` markers immediately before the actual answer text, not at the beginning of sentences.**

## Placement Rules

### Rule 1: Place Marker Immediately Before Answer
The `[Q#]` marker should be positioned as close as possible to the actual answer text.

**❌ WRONG:**
```
Anne: [Q1]Yes of course. It's Anne Hawberry.
```
This highlights "Yes of course." instead of "Anne Hawberry"

**✅ CORRECT:**
```
Anne: Yes of course. It's [Q1]Anne Hawberry.
```
This correctly highlights "Anne Hawberry."

### Rule 2: For Short Answers (Single Words/Numbers)
Place the marker directly before the answer word or number.

**✅ Examples:**
```
The program runs for [Q2]three weeks.
Each session lasts [Q5]one hour.
The cost is [Q4]£7.95.
```

### Rule 3: For Multi-Word Answers
Place the marker at the start of the answer phrase.

**✅ Examples:**
```
I arrived [Q1]two months ago.
My name is [Q1]Anne Hawberry.
We're located on the [Q9]2nd floor.
```

### Rule 4: For Longer Answers or Sentences
Place the marker at the beginning of the sentence that contains the answer.

**✅ Example:**
```
Woman: I'm interested in the settlement support programme. [Q1]I arrived in the country two months ago.
```

### Rule 5: Within Table Cells
For table-formatted transcripts, place the marker within the appropriate cell where the answer is spoken.

**✅ Example:**
```html
<tr>
    <td><strong>Anne:</strong></td>
    <td>Yes of course. It's [Q1]Anne Hawberry.</td>
</tr>
```

## How the Highlighting Works (Updated in Version 11.10)

Once you place the marker correctly, the system automatically:

1. **Displays the Q badge** with yellow background (#ffc107)
2. **Wraps answer text** in light yellow background (#fff9c4)
3. **Smart answer detection** - Stops highlighting at:
   - First comma (`,`) - for embedded answers like "It's Anne Hawberry, and..."
   - First semicolon (`;`)
   - First period followed by space and capital letter (`. Next sentence`)
   - First newline character
   - First 50 characters (safety limit, reduced from 100 for better accuracy)
4. **Word boundary trimming** - If over 50 characters, trims to last complete word

This ensures that only the answer itself gets highlighted, not entire sentences or paragraphs.

## Technical Implementation (Updated in Version 11.10)

The pattern used is: `/\[Q(\d+)\]([^\[]*?)(?=\[Q|$)/is`

This regex:
- Matches `[Q1]`, `[Q2]`, etc.
- Captures text after the marker
- Stops at the next Q marker or end of string
- Works across multiple lines

The highlighting logic then applies smart boundary detection:
```php
// Stop at comma, semicolon, sentence boundary, or newline
if (preg_match('/^([^,;]+?)(?:[,;]|\.\s+[A-Z]|\n|$)/s', $answer_text, $boundary_match)) {
    $highlighted_text = $boundary_match[1];
} else {
    $highlighted_text = mb_substr($answer_text, 0, 50);
}
```

This ensures precise answer highlighting without capturing extra context.

## Visual Result

When placed correctly, students will see:

- **Q1** ← Yellow badge with question number
- **Anne Hawberry.** ← Light yellow background on answer text

Both elements appear together, making it crystal clear where the answer is located in the transcript.

## Common Mistakes to Avoid

### ❌ Mistake 1: Marker at Sentence Start
```
[Q1]Anne: Yes of course. It's Anne Hawberry.
```
Problem: Badge appears before speaker name

### ❌ Mistake 2: Marker Too Early
```
Anne: [Q1]Yes of course. It's Anne Hawberry.
```
Problem: Highlights wrong text

### ❌ Mistake 3: Marker After Answer
```
Anne: Yes of course. It's Anne Hawberry[Q1].
```
Problem: No text after marker to highlight

### ❌ Mistake 4: No Space Before Answer
```
Anne: Yes of course. It's[Q1]Anne Hawberry.
```
Problem: Badge touches preceding text (though it will still work)

## Best Practices

1. **Review the question** to understand what the answer is
2. **Find the exact answer text** in the transcript
3. **Place the marker** immediately before that text
4. **Add a space** before the marker for readability
5. **Test the result** by viewing the rendered transcript

## Examples by Question Type

### Name/Identification Questions
```
Question: What is the applicant's name?
Placement: It's [Q1]Anne Hawberry.
```

### Numeric Answers
```
Question: How long is the program?
Placement: The program runs for [Q2]three weeks.
```

### Date Answers
```
Question: When was she born?
Placement: My date of birth is [Q2]22 May 1981.
```

### Location Answers
```
Question: Which floor is the office on?
Placement: We're on the [Q9]2nd floor.
```

### Yes/No/True/False
```
Question: Is she a permanent resident?
Placement: Woman: [Q1]Yes, I am.
```

## Checking Your Work

After placing markers, ask yourself:

1. ✓ Is the Q badge near the answer?
2. ✓ Will the highlighted text contain the answer?
3. ✓ Is the answer clearly visible to students?
4. ✓ Would a student immediately understand which text answers the question?

If you answer "no" to any of these, reposition the marker closer to the actual answer.

## Version History

- **Version 11.10** - Improved smart answer boundary detection (stops at commas, semicolons, 50-char limit)
- **Version 11.9** - Implemented automatic answer text highlighting with yellow background
- **Version 11.8** - Documented intended behavior (not fully implemented)
- **Version 11.6** - Changed Q badge color from blue to yellow

## Summary

**Golden Rule:** Place `[Q#]` markers immediately before the actual answer text, not at the beginning of sentences or speaker labels.

When in doubt, ask: "If I highlight the text right after this marker, will students see the answer?" If yes, the placement is correct.
