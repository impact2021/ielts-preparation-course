# Example: Fixing Transcript Marker Placement

This document shows a real example of fixing marker placement in an IELTS Listening Test transcript based on the image provided in the issue.

## Original Problem (From Screenshot)

The screenshot showed a listening test where:
- Question 1 asks for the applicant's name
- The answer is "Anne Hawberry"
- But the Q1 marker was at the beginning of Anne's response

## The Transcript (Before Fix)

```html
<tr>
    <td><strong>Admissions officer:</strong></td>
    <td>That's great. Okay so first of all, can you tell me your name please?</td>
</tr>
<tr>
    <td><strong>Anne:</strong></td>
    <td>[Q1]Yes of course. It's Anne Hawberry.</td>
</tr>
```

### What Happened (Before)
1. Q1 badge appeared at the very start: **Q1** Yes of course. It's Anne Hawberry.
2. The highlighting wrapped: **Yes of course.** (first sentence)
3. The actual answer "Anne Hawberry" had no highlighting
4. Students were confused about which part was the answer

## The Transcript (After Fix)

```html
<tr>
    <td><strong>Admissions officer:</strong></td>
    <td>That's great. Okay so first of all, can you tell me your name please?</td>
</tr>
<tr>
    <td><strong>Anne:</strong></td>
    <td>Yes of course. It's [Q1]Anne Hawberry.</td>
</tr>
```

### What Happens (After)
1. Q1 badge appears before the answer: Yes of course. It's **Q1** Anne Hawberry.
2. The highlighting wraps: **Anne Hawberry.** (first sentence after marker)
3. The actual answer gets the yellow background
4. Students can clearly see where the answer is

## More Examples from Typical Transcripts

### Example 2: Date Question

**BEFORE (Wrong):**
```html
<td>[Q2]Anne: I'm 24. My date of birth is 22 May 1981.</td>
```
Result: Highlights "Anne: I'm 24." instead of the date

**AFTER (Correct):**
```html
<td>Anne: I'm 24. My date of birth is [Q2]22 May 1981.</td>
```
Result: Highlights "22 May 1981." (the actual answer)

### Example 3: Duration Question

**BEFORE (Wrong):**
```html
<td>[Q3]The Settlement Support Programme lasts for three weeks.</td>
```
Result: Highlights "The Settlement Support Programme lasts for three weeks." (entire sentence, but answer is just "three weeks")

**AFTER (Correct):**
```html
<td>The Settlement Support Programme lasts for [Q3]three weeks.</td>
```
Result: Highlights "three weeks." (just the answer)

### Example 4: Cost Question

**BEFORE (Wrong):**
```html
<td>[Q4]Students pay £7.95 if they order early enough to qualify for the discount.</td>
```
Result: Highlights entire sentence starting with "Students pay..."

**AFTER (Correct):**
```html
<td>Students pay [Q4]£7.95 if they order early enough to qualify for the discount.</td>
```
Result: Highlights "£7.95 if they order early enough to qualify for the discount." (includes the answer)

Note: Could be even more precise:
```html
<td>Students only pay [Q4]£7.95 if they order early enough to qualify for the discount.</td>
```
Result: Highlights "£7.95 if they order early enough..." (starts with the answer)

### Example 5: Multi-Sentence Paragraph

**BEFORE (Wrong):**
```html
<p>[Q6]Telephone operator: Well these are one to one sessions instead of a group class. They run for two weeks with two one hour sessions each week. We currently have 15 mentors on the team.</p>
```
Result: Highlights "Telephone operator: Well these are one to one sessions instead of a group class." (wrong)

**AFTER (Correct - if answer is "15"):**
```html
<p>Telephone operator: Well these are one to one sessions instead of a group class. They run for two weeks with two one hour sessions each week. We currently have [Q6]15 mentors on the team.</p>
```
Result: Highlights "15 mentors on the team." (contains the answer)

## Table-Based Transcripts

Most IELTS listening transcripts use table format. Here's a complete example:

### Complete Section (Before)

```html
<table>
<tr>
    <td width="20%"><strong>Admissions officer:</strong></td>
    <td width="80%">That's great. Okay so first of all, can you tell me your name please?</td>
</tr>
<tr>
    <td width="20%"><strong>Anne:</strong></td>
    <td width="80%">[Q1]Yes of course. It's Anne Hawberry.</td>
</tr>
<tr>
    <td width="20%"><strong>Admissions officer:</strong></td>
    <td width="80%">Hawberry. Is that H-A-W-B-E-R-R-Y?</td>
</tr>
<tr>
    <td width="20%"><strong>Anne:</strong></td>
    <td width="80%">Yes that's right.</td>
</tr>
<tr>
    <td width="20%"><strong>Admissions officer:</strong></td>
    <td width="80%">Okay and how old are you Ms Hawberry?</td>
</tr>
<tr>
    <td width="20%"><strong>Anne:</strong></td>
    <td width="80%">[Q2]I'm 24. My date of birth is 22 May 1981.</td>
</tr>
</table>
```

### Complete Section (After)

```html
<table>
<tr>
    <td width="20%"><strong>Admissions officer:</strong></td>
    <td width="80%">That's great. Okay so first of all, can you tell me your name please?</td>
</tr>
<tr>
    <td width="20%"><strong>Anne:</strong></td>
    <td width="80%">Yes of course. It's [Q1]Anne Hawberry.</td>
</tr>
<tr>
    <td width="20%"><strong>Admissions officer:</strong></td>
    <td width="80%">Hawberry. Is that H-A-W-B-E-R-R-Y?</td>
</tr>
<tr>
    <td width="20%"><strong>Anne:</strong></td>
    <td width="80%">Yes that's right.</td>
</tr>
<tr>
    <td width="20%"><strong>Admissions officer:</strong></td>
    <td width="80%">Okay and how old are you Ms Hawberry?</td>
</tr>
<tr>
    <td width="20%"><strong>Anne:</strong></td>
    <td width="80%">I'm 24. My date of birth is [Q2]22 May 1981.</td>
</tr>
</table>
```

## Step-by-Step Guide to Fix Your Transcripts

### Step 1: Identify the Question
Look at the question to understand what answer is expected.

Example: "What is the applicant's name?"

### Step 2: Find the Answer in Transcript
Locate where that answer is spoken in the transcript.

Example: "It's Anne Hawberry."

### Step 3: Locate Current Marker
Find where the `[Q#]` marker currently is.

Example: `[Q1]Yes of course. It's Anne Hawberry.`

### Step 4: Move Marker to Answer
Reposition the marker immediately before the actual answer.

Example: `Yes of course. It's [Q1]Anne Hawberry.`

### Step 5: Test the Result
If possible, view the rendered transcript to verify:
- Q badge appears before the answer ✓
- Answer text gets yellow highlighting ✓
- The highlighted text is the actual answer ✓

## Quick Reference: Common Patterns

### Name/Person
```
OLD: [Q#]My name is John Smith.
NEW: My name is [Q#]John Smith.
```

### Number/Quantity
```
OLD: [Q#]There are 15 mentors on the team.
NEW: There are [Q#]15 mentors on the team.
```

### Date
```
OLD: [Q#]The course starts on 3rd October.
NEW: The course starts on [Q#]3rd October.
```

### Price/Cost
```
OLD: [Q#]The handbook costs £7.95.
NEW: The handbook costs [Q#]£7.95.
```

### Duration/Time
```
OLD: [Q#]Each session lasts one hour.
NEW: Each session lasts [Q#]one hour.
```

### Location/Place
```
OLD: [Q#]We're on the 2nd floor of the building.
NEW: We're on the [Q#]2nd floor of the building.
```

## Automated Find and Replace?

While it would be nice to automatically fix all markers, it's not safe to do programmatically because:
1. Each question has a different answer
2. The answer can appear anywhere in the sentence
3. Context matters (same words might appear multiple times)
4. Manual review ensures accuracy

**Recommendation:** Fix transcripts one by one, testing as you go.

## Summary

**Key Principle:** Place `[Q#]` immediately before the actual answer text.

**Before:** `[Q1]Yes of course. It's Anne Hawberry.` ❌  
**After:** `Yes of course. It's [Q1]Anne Hawberry.` ✅

This ensures the yellow highlighting appears on the correct text, making it clear to students where the answer is located.
