# Automatic Transcript Annotation Feature

## Overview

The IELTS Course Manager now automatically annotates listening test transcripts with answer locations when XML files are imported. This feature helps instructors and students easily identify where each answer can be found in the transcript.

## How It Works

When you upload an XML file for a listening exercise that contains both:
1. A transcript (in the `_ielts_cm_transcript` field)
2. Questions with answers (in the `_ielts_cm_questions` field)

The system will automatically scan the transcript and mark where each answer appears using the format:

```html
<strong>[Q#: answer]</strong>
```

Where `#` is the question number and `answer` is the actual text from the transcript.

## Example

### Before Import
Original transcript text:
```html
<td>We'd like to stay for 4 nights please.</td>
```

### After Import
Annotated transcript:
```html
<td>We'd like to stay for <strong>[Q1: 4]</strong> nights please.</td>
```

## Supported Question Types

The annotation feature supports:

1. **Summary Completion Questions** - Form filling, sentence completion, etc.
   - Each field within a summary_fields array is treated as a separate question
   - Multiple acceptable answers (separated by `|`) are handled automatically

2. **Other Question Types** - Multiple choice, True/False, etc.
   - Any question with a `correct_answer` field

## Features

- **Multiple Answer Variants**: Automatically handles multiple acceptable answers (e.g., "4|four")
- **Case-Insensitive Matching**: Finds answers regardless of case differences
- **First Occurrence Only**: Marks only the first occurrence of each answer to avoid confusion
- **Prevents Duplicates**: Won't re-annotate already marked answers
- **Respects Question Numbers**: Uses the `_ielts_cm_starting_question_number` if specified

## Import Modes

The annotation feature works in both import modes:

1. **Replace Mode** (default): Replaces all exercise content and annotates the new transcript
2. **Append Mode**: Adds new questions to existing ones and annotates the transcript

## Technical Details

### Function
`annotate_transcript_with_answers($transcript, $questions, $starting_question_number = 1)`

### Location
`includes/admin/class-admin.php` - Part of the `IELTS_CM_Admin` class

### Processing Flow
1. XML file is uploaded via the import interface
2. XML is parsed to extract questions and transcript
3. **New**: Transcript is automatically annotated with answer locations
4. Annotated transcript and questions are saved to the database

## Example Output

For Test 2 Section 1, the transcript will show:

```html
<tr>
<td>Visitor</td>
<td>We'd like to stay for <strong>[Q1: 4]</strong> nights please. 
We'll be arriving on August the <strong>[Q2: 5th]</strong>...</td>
</tr>
<tr>
<td>Visitor</td>
<td>No – there will be <strong>[Q3: 8]</strong> adults and 3 children.</td>
</tr>
<tr>
<td>Visitor</td>
<td>My name is Eric <strong>[Q4: Huse]</strong>...</td>
</tr>
```

And so on for all 10 questions.

## Benefits

1. **For Instructors**: Quickly verify that questions align with transcript content
2. **For Students**: Easily locate answers when reviewing their performance
3. **For Content Creators**: Automatic quality check during content creation
4. **Time Saving**: No manual annotation needed

## Backward Compatibility

- This feature is automatically applied during import
- Existing exercises are not affected unless re-imported
- The feature can be disabled by modifying the code if needed (though not recommended)

## Known Limitations

1. The annotation looks for exact text matches, so slight variations in wording may not be detected
2. Only the first occurrence of an answer is marked
3. Very short answers (1-2 characters) may match unintended words if not careful with the answer format

## Future Enhancements

Potential improvements for future versions:
- Option to disable/enable annotation during import
- Support for highlighting answer ranges (e.g., multi-word phrases)
- Visual styling options for different question types
- Re-annotation of existing exercises via bulk action
