# XML Exercises Creator Guide

## Overview

The XML Exercises Creator is a tool that automatically creates exercise pages from the converted LearnDash XML file. This tool is designed to speed up the process of importing exercises that were previously stored in LearnDash.

## What It Does

The XML Exercises Creator:

1. **Reads the converted XML file** - Processes the `ieltstestonline.WordPress.2025-12-17.xml` file
2. **Groups related questions** - Automatically groups questions that belong to the same quiz into multi-question exercises
3. **Extracts exercise data** - Retrieves question content, metadata, and settings from each `ielts_quiz` item
4. **Creates exercise posts** - Generates WordPress posts of type `ielts_quiz` with the extracted data
5. **Preserves HTML content** - Maintains images, formatting, and other HTML elements in question text
6. **Adds placeholder values** - Pre-fills options and correct answers with helpful examples
7. **Maps question types** - Automatically converts LearnDash question types to IELTS Course Manager types
8. **Detects True/False questions** - Identifies True/False questions based on title and content patterns
9. **Preserves metadata** - Maintains point values and quiz associations from the original data

## Features

### Automatic Question Type Detection

The tool maps LearnDash question types to IELTS Course Manager types:

| LearnDash Type | IELTS CM Type |
|----------------|---------------|
| single | multiple_choice |
| multiple | multiple_choice |
| free_answer | fill_blank |
| essay | essay |
| cloze_answer | fill_blank |
| assessment_answer | essay |
| matrix_sort_answer | multiple_choice |
| sort_answer | multiple_choice |

Additionally, the tool automatically detects True/False questions by looking for indicators like:
- "true or false"
- "true/false"
- "t or f"
- "t/f/ng"
- "true false not given"
- "true, false, or not given"

### Batch Processing

- Process all 4,547 exercises at once, or
- Set a limit to process a specific number for testing
- Skip existing exercises to avoid duplicates
- Choose between Draft or Published status for created exercises

### Error Handling

- Comprehensive error logging
- Tracks created, skipped, and failed exercises
- Displays detailed results after processing

## How to Use

### Step 1: Access the Tool

1. Log into WordPress admin panel
2. Navigate to **IELTS Courses > Create Exercises from XML**

### Step 2: Configure Options

Before creating exercises, configure these options:

- **Skip Existing**: Enable to avoid creating duplicate exercises (matched by title)
- **Post Status**: 
  - **Draft (recommended)**: Creates exercises as drafts so you can review and add answers before publishing
  - **Published**: Creates exercises as published posts immediately
- **Limit**: Optionally set a maximum number of exercises to process (useful for testing)

### Step 3: Create Exercises

1. Click **"Create Exercises from XML"**
2. Wait for the process to complete (this may take several minutes for large files)
3. Review the results summary showing:
   - Number of exercises created
   - Number of exercises skipped
   - Any errors encountered

### Step 4: Edit Exercises

After creation, each exercise will have **placeholder values** that you need to review and update:

#### For Multiple Choice Questions:
1. **Options**: Placeholder options are pre-filled (Option A, Option B, etc.). Replace these with your actual answer choices:
   ```
   Your first option
   Your second option
   Your third option
   Your fourth option
   ```
2. **Correct Answer**: Pre-filled with `0` (first option). Update to the correct option number:
   - `0` for first option
   - `1` for second option
   - `2` for third option, etc.

#### For True/False Questions:
1. **Correct Answer**: Pre-filled with `true`. Update to the correct answer:
   - `true`
   - `false`
   - `not_given`

#### For Fill in the Blank Questions:
1. **Correct Answer**: Pre-filled with a placeholder. Replace with the expected answer (case-insensitive matching)

#### For Essay Questions:
- No correct answer needed (requires manual grading)

#### Question Text Editor:
- Questions are displayed in a **WYSIWYG editor** that preserves HTML formatting
- Images and other HTML content from the XML are automatically included
- You can edit formatting, add or remove images, and adjust styling as needed

### Step 5: Assign and Publish

1. **Assign to Courses/Lessons**: In the exercise settings, select which courses and/or lessons should include this exercise
2. **Review the Question**: Ensure the question text is clear and formatted correctly
3. **Publish**: Change the status from Draft to Published when ready

## About Feedback

The IELTS Course Manager automatically provides feedback to students based on their answers:

### Automatic Feedback System

- **Correct Answers**: Shows a success message and awards points
- **Incorrect Answers**: Displays what the correct answer should have been
- **Score Display**: Shows total score and percentage at quiz completion
- **Answer Review**: Students can review their answers after submission

### Customizing Feedback (Future Enhancement)

While the current system provides standard feedback, the IELTS Course Manager quiz handler can be extended to support custom feedback messages for individual questions. This would allow you to provide:

- Detailed explanations for correct answers
- Hints and tips for incorrect answers
- Additional learning resources

## Important Notes

### What's Included in the XML

The XML export includes:
- Question titles
- Question content/text
- Question types
- Point values
- Quiz associations (via `ld_quiz_*` metadata)
- Creation dates and modification dates

### What's NOT Included in the XML

The XML export does NOT include:
- Answer options (must be added manually)
- Correct answers (must be specified manually)
- Custom feedback messages (use default feedback system)
- Detailed quiz settings (these are stored in separate database tables)

This is a limitation of the WordPress XML export format and the LearnDash data structure.

## Troubleshooting

### "XML File Not Found" Error

**Problem**: The tool cannot find the XML file.

**Solution**: 
1. Ensure the file `ieltstestonline.WordPress.2025-12-17.xml` exists in the plugin root directory
2. Check file permissions (file should be readable by WordPress)
3. Verify the file name matches exactly (case-sensitive)

### Large File Processing Times

**Problem**: Processing takes a very long time or times out.

**Solutions**:
1. Increase PHP execution time limit in `php.ini` or `.htaccess`:
   ```
   max_execution_time = 600
   ```
2. Increase PHP memory limit:
   ```
   memory_limit = 512M
   ```
3. Process exercises in batches using the "Limit" option:
   - First batch: Process 1000 exercises
   - Second batch: Enable "Skip Existing" and process remaining exercises

### Duplicate Exercises

**Problem**: Multiple exercises with the same name.

**Solution**: 
1. Enable "Skip Existing" option
2. Re-run the tool to skip duplicates
3. Manually delete duplicate exercises if needed

### Incorrect Question Types

**Problem**: Questions are assigned the wrong type.

**Solution**: 
1. Edit the exercise post
2. Change the question type in the Questions section
3. Update the answer format accordingly

## Best Practices

### 1. Test First
- Use the "Limit" option to create 10-20 exercises first
- Review these exercises to ensure quality
- Adjust your workflow as needed
- Then process all exercises

### 2. Use Draft Status
- Create exercises as drafts initially
- Review and add answers in batches
- Publish exercises only when complete and tested

### 3. Organize as You Go
- Assign exercises to courses/lessons during editing
- Use consistent naming conventions
- Add tags or categories if needed

### 4. Bulk Editing
- Use WordPress bulk edit features to assign multiple exercises to the same course
- Consider creating a spreadsheet to track which exercises need which answers
- Work through exercises systematically

### 5. Quality Control
- Spot-check created exercises regularly
- Test exercises from student perspective
- Ensure images and formatting display correctly

## Support

For issues or questions:
1. Check this guide first
2. Review the IELTS Course Manager documentation
3. Post issues on GitHub: https://github.com/impact2021/ielts-preparation-course/issues

Include in your issue:
- Steps to reproduce the problem
- Error messages or screenshots
- Number of exercises being processed
- PHP version and memory limits
