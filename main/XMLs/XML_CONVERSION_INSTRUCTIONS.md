# Listening Test 3 XML Conversion Instructions

## Overview
Formatted text files have been created for all 4 sections of Listening Test 3. These files are ready to be converted to XML using the WordPress plugin's built-in text parser.

## Files Created
1. `Listening Test 3 Section 1 - formatted.txt` (Questions 1-10)
2. `Listening Test 3 Section 2 - formatted.txt` (Questions 11-20)
3. `Listening Test 3 Section 3 - formatted.txt` (Questions 21-30)
4. `Listening Test 3 Section 4 - formatted.txt` (Questions 31-40)

## Conversion Steps

### Step 1: Access the Text Import Tool
1. Log into WordPress admin
2. Navigate to: Quizzes > Create Exercises from Text
3. This tool will parse the formatted text and create proper exercises

### Step 2: Convert Each Section
For each of the 4 formatted text files:

1. Open the formatted text file
2. Copy the entire contents
3. Paste into the "Exercise Text" field in WordPress
4. Select "Draft" as Post Status (recommended for review)
5. Click "Create Exercise"
6. Review the created exercise to ensure all questions are present
7. Check that feedback is properly assigned

### Step 3: Add Audio and Transcript
For each created exercise:

1. Edit the quiz post
2. Scroll to "Listening Audio & Transcripts" section
3. Add the audio URL from the original text file:
   - Section 1: `https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0049-1.mp3`
   - Section 2: `https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0049-2.mp3`
   - Section 3: `https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0049-3.mp3`
   - Section 4: `https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0049-4.mp3`

4. Click "Add a Transcript" to add transcript text
   - Extract transcript from the original .txt files (the table content at the bottom)
   - For sections with multiple parts, you can add multiple transcript sections
   - Set section number appropriately (1-4)

5. Verify settings are correct:
   - Layout Type: Listening Practice Test (No Audio Controls)
   - Display Label: Practice test
   - Scoring Type: IELTS Listening (Band Score)
   - Starting Question Number: (1, 11, 21, or 31 depending on section)

### Step 4: Export to XML
For each completed exercise:

1. While editing the quiz, find the "Convert to XML" option
   - Or use the row actions on the quiz list page
2. Click "Export to XML"
3. Save the XML file with the name:
   - `Listening Test 3 Section 1.xml`
   - `Listening Test 3 Section 2.xml`
   - `Listening Test 3 Section 3.xml`
   - `Listening Test 3 Section 4.xml`

### Step 5: Validate XML
For each XML file:

1. Open in a text editor
2. Verify the structure includes:
   - `<wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>`
   - `<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>`
   - Questions array with all expected questions
3. Check that serialization is valid (byte counts match string lengths)
4. Verify no "No questions found" error will occur

### Step 6: Final Placement
1. Save the validated XML files to: `/main/XMLs/` directory
2. Name them:
   - `Listening Test 3 Section 1.xml`
   - `Listening Test 3 Section 2.xml`
   - `Listening Test 3 Section 3.xml`
   - `Listening Test 3 Section 4.xml`
3. The formatted .txt files can remain or be moved to a `/main/XMLs/formatted/` subdirectory
4. Delete the draft quiz posts from WordPress (they were temporary for XML generation)

## Quality Checklist

✅ All 4 sections have formatted text files
✅ Each file includes comprehensive feedback:
   - [CORRECT] feedback
   - [INCORRECT] feedback
   - [NO ANSWER] feedback
✅ Exercise settings are properly formatted
✅ Starting question numbers are correct (1, 11, 21, 31)
✅ Layout type set to listening_practice
✅ Scoring type set to ielts_listening
✅ All alternative answers included where applicable

## Question Breakdown

### Section 1 (Questions 1-10)
- Type: Short answer
- Format: Fill in the blank with {ANSWER}
- All 10 questions have 3 feedback types

### Section 2 (Questions 11-20)
- Questions 11-15: Table completion
- Questions 16-20: Summary completion
- All 10 questions have 3 feedback types per field

### Section 3 (Questions 21-30)
- Questions 21-26: Matching/Classifying (A, B, C options)
- Questions 27-30: Short answer
- All 10 questions have comprehensive feedback

### Section 4 (Questions 31-40)
- Questions 31-35: Short answer
- Questions 36-40: Table completion
- All 10 questions have 3 feedback types per field

## Notes
- The formatted text files follow the plugin's parsing rules exactly
- All feedback is contextual and references specific parts of the listening audio
- No placeholder feedback like "Please select an answer" - all feedback is meaningful
- Alternative spellings and formats are included where appropriate
- The parser will handle serialization automatically when using the built-in tools

## Troubleshooting
If you encounter "No questions found" error:
1. Check that question numbers are present (e.g., "1.", "11.")
2. Verify {ANSWER} placeholders or [ANSWER N] format is used
3. Ensure === markers are on their own lines
4. Check for any special characters that might break parsing

If feedback is missing:
1. Ensure [CORRECT], [INCORRECT], and [NO ANSWER] markers are present
2. Check that feedback text follows the markers on the same line or next lines
3. Verify no extra blank lines break the feedback association
