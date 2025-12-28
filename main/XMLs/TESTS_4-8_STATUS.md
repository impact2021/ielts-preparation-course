# Listening Tests 4-8 - Status and Instructions

## Current Status

Listening Tests 4-8 currently exist only as text files (.txt), not as XML exports. These text files contain:
- Question content with HTML formatting
- Answer keys in the Answers section
- Audio transcripts with question markers (Q1), (Q2), etc.

## Extracted Transcripts

Annotated transcript files have been created for all sections of Tests 4-8:
- `Listening Test 4 Section 1-transcript.txt` through `Listening Test 4 Section 4-transcript.txt`
- `Listening Test 5 Section 1-transcript.txt` through `Listening Test 5 Section 4-transcript.txt`
- `Listening Test 6 Section 1-transcript.txt` through `Listening Test 6 Section 4-transcript.txt`
- `Listening Test 7 Section 1-transcript.txt` through `Listening Test 7 Section 4-transcript.txt`
- `Listening Test 8 Section 1-transcript.txt` through `Listening Test 8 Section 4-transcript.txt`

These transcript files include yellow-highlighted answer markers where detected.

## How to Create XMLs for Tests 4-8

To create proper XML exports for these tests, they need to be imported into WordPress first:

### Method 1: Using WordPress Text Import Tool

1. Log into WordPress admin
2. Navigate to: Quizzes > Create Exercises from Text
3. For each section:
   - Open the corresponding .txt file (e.g., `Listening Test 4 Section 1.txt`)
   - Copy the entire content
   - Paste into the "Exercise Text" field
   - Select "Draft" as Post Status
   - Click "Create Exercise"
   - Review and publish

4. Once created in WordPress:
   - Edit the quiz post
   - Add the audio URL (found in the .txt file in `[audio mp3="..."]` tag)
   - Add the transcript from the corresponding `-transcript.txt` file
   - Ensure proper feedback is set for all questions
   - Export to XML using the "Export to XML" option

### Method 2: Manual Creation

Alternatively, you can create each quiz manually in WordPress:
1. Create a new IELTS Quiz post
2. Add questions one by one using the question editor
3. Set correct answers and feedback
4. Add the audio URL
5. Add the transcript
6. Export to XML

## Completed Work (Tests 1-3)

The following XML files have been fully updated:
- Listening Test 1 Sections 1-4: ✓ Fixed feedback, ✓ Annotated transcripts
- Listening Test 2 Sections 1-4: ✓ Fixed feedback, ✓ Annotated transcripts  
- Listening Test 3 Sections 2-4: ✓ Fixed feedback, ✓ Annotated transcripts

All no_answer_feedback fields now provide the correct answer with a learning tip, instead of just hints.

## Version Update

Plugin version updated to 8.10 in `ielts-course-manager.php`.
