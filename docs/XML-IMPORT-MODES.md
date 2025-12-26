# XML Import Modes

## Overview

The XML import feature now supports two modes:
1. **Replace all content** - Overwrites all existing exercise content
2. **Add to existing content** - Appends questions and reading texts to the current exercise

## Usage

When editing an exercise (ielts_quiz post type), navigate to the "Import/Export XML" meta box in the sidebar.

### Import Mode Options

#### Replace All Content
- **Description:** Overwrites all current exercise content
- **Use when:** You want to completely replace an exercise with new content
- **What it updates:**
  - Exercise title
  - Exercise content/description
  - All questions (replaces existing)
  - All reading texts (replaces existing)
  - All settings (layout type, timer, scoring, etc.)
- **Warning:** This cannot be undone. Export a backup first!

#### Add to Existing Content
- **Description:** Adds questions and reading texts from XML to the current exercise
- **Use when:** You want to add more questions to an existing exercise
- **What it updates:**
  - Questions (appends new questions after existing ones)
  - Reading texts (appends new reading texts with remapped IDs)
- **What it preserves:**
  - Exercise title
  - Exercise content/description
  - All settings (layout type, timer, scoring, etc.)
  - Existing questions and reading texts

## How It Works

### Replace Mode
1. Parses the XML file
2. Overwrites all exercise data with data from XML
3. Updates all metadata fields

### Append Mode
1. Parses the XML file
2. Gets existing questions from the exercise
3. Gets new questions from the XML
4. Merges questions: `existing + new`
5. Gets existing reading texts
6. Gets new reading texts from XML
7. Remaps reading text IDs to avoid conflicts
8. Updates question references to use remapped IDs
9. Saves merged questions and reading texts

### Reading Text ID Remapping

When appending content, reading text IDs from the XML file are automatically remapped to avoid conflicts:

**Example:**
- Existing exercise has reading texts with IDs: 0, 1, 2
- XML file has reading texts with IDs: 0, 1
- After import, XML reading texts will have IDs: 3, 4
- Questions from XML that reference reading text 0 will be updated to reference 3
- Questions from XML that reference reading text 1 will be updated to reference 4

This ensures that:
1. No ID conflicts occur
2. Questions correctly reference their intended reading texts
3. Existing content remains unchanged

## Best Practices

### For Replace Mode
1. Always export a backup before replacing
2. Use when creating a new exercise or completely updating an old one
3. Verify the XML file is correct and complete

### For Append Mode
1. Use when adding questions to an existing exercise
2. Ensure XML contains only the questions you want to add
3. Reading texts in the XML should be complete (not assume existing texts)
4. Question numbering will continue from the last existing question

## Limitations

### Append Mode Does Not Update
- Exercise title
- Exercise description/content
- Exercise settings (layout type, timer, scoring, etc.)
- Existing questions or reading texts

If you need to update these, use Replace mode or edit them manually.

## Example Use Cases

### Use Case 1: Adding More Questions to Practice Test
You have a practice test with 20 questions and want to add 20 more.

1. Create XML file with only the 20 new questions
2. Select "Add to existing content" mode
3. Import the XML
4. Result: Exercise now has 40 questions (20 original + 20 new)

### Use Case 2: Replacing Entire Exercise
You want to completely update an exercise with new content.

1. Export current exercise as backup
2. Select "Replace all content" mode
3. Import the new XML file
4. Result: Exercise is completely replaced with new content

### Use Case 3: Building Exercise Incrementally
You're creating a large exercise in parts.

1. Create base exercise with first set of questions
2. Create XML for next set of questions
3. Select "Add to existing content" and import
4. Repeat for additional question sets
5. Result: Large exercise built from multiple imports

## Troubleshooting

### Questions not appearing after append
- Check that XML file has valid questions data
- Verify questions array is properly formatted
- Ensure XML uses correct CDATA formatting (no spaces)

### Reading text references incorrect after append
- This should not happen due to ID remapping
- If it does, export the exercise to check the reading_text_id values
- May indicate a bug in the remapping logic

### Settings changed unexpectedly
- Only Replace mode changes settings
- Append mode should never change settings
- If settings changed, you may have used Replace mode by mistake
