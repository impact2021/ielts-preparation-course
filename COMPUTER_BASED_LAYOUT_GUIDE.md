# Computer-Based IELTS Layout Guide

## Overview

Version 2.1 introduces a new computer-based IELTS test layout that mimics the actual IELTS computer-delivered test interface. This layout features a two-column design with reading passages on the left and questions on the right, providing students with a realistic exam experience.

## Features

### Two-Column Layout
- **Left Column**: Displays reading passages with scrollable content
- **Right Column**: Shows questions with answer inputs
- **Full Width**: Utilizes the entire screen width for better readability
- **Independent Scrolling**: Each column scrolls independently

### Reading Passages
- Support for multiple reading texts per exercise
- Optional titles for each passage (e.g., "Passage 1", "Passage 2")
- Rich text formatting preserved
- Proper paragraph spacing and formatting

### Question Navigation
- Bottom navigation bar with numbered buttons
- Click any button to jump directly to that question
- Visual feedback when questions are answered (green highlight)
- Smooth scrolling animation
- Question highlight effect on navigation

### Responsive Design
- **Desktop (>1024px)**: Side-by-side columns with 700px max height
- **Tablet (768-1024px)**: Vertical stacking with 400px column height
- **Mobile (<768px)**: Optimized layout with 300px column height

## How to Use

### Creating a Computer-Based Exercise

1. **Create or Edit an Exercise**
   - Navigate to "IELTS Courses > Exercises"
   - Create a new exercise or edit an existing one

2. **Select Layout Type**
   - In the "Quiz Settings" meta box
   - Find the "Layout Type" dropdown
   - Select "Computer-Based IELTS Layout (Two Columns)"

3. **Add Reading Texts**
   - Once computer-based layout is selected, the "Reading Texts" section appears
   - Click "Add Reading Text" to add a passage
   - Enter an optional title (e.g., "Passage 1")
   - Paste or type the reading passage content
   - Add multiple reading texts if needed

4. **Add Questions**
   - Add questions as usual in the "Questions" section
   - Questions will appear in the right column
   - All question types are supported:
     - Multiple Choice
     - True/False/Not Given
     - Fill in the Blank
     - Essay

5. **Publish**
   - Save or publish the exercise
   - The computer-based layout will be used automatically

### Student Experience

When students view a computer-based exercise:

1. **Reading the Passage**
   - Reading text appears in the left column
   - Students can scroll through the passage independently
   - Multiple passages are stacked vertically

2. **Answering Questions**
   - Questions appear in the right column
   - Students scroll through questions independently
   - All standard input types are available

3. **Navigation**
   - Bottom navigation shows all question numbers
   - Click any number to jump to that question
   - Answered questions show with a green highlight
   - Current question is highlighted briefly when navigated to

4. **Submitting**
   - Submit button appears at the bottom of the questions column
   - Results display after submission as usual

## Best Practices

### Reading Text Guidelines

1. **Text Length**: Keep passages to a reasonable length (500-1000 words per passage)
2. **Formatting**: Use proper paragraphs for better readability
3. **Titles**: Use descriptive titles like "Passage 1: Climate Change" or "Text A"
4. **Multiple Passages**: Group related questions by adding multiple reading texts

### Question Design

1. **Question Order**: Order questions logically, typically matching the passage order
2. **Question Grouping**: If you have multiple passages, group questions by passage
3. **Clear Instructions**: Include clear instructions in the exercise description
4. **Question Numbers**: Navigation automatically numbers questions for easy reference

### Layout Selection

**Use Computer-Based Layout When:**
- Exercise includes reading comprehension passages
- Mimicking actual IELTS computer test is important
- Students need to reference long texts while answering

**Use Standard Layout When:**
- Exercise is primarily practice questions without long passages
- Questions are independent and don't require text reference
- Simpler layout is preferred

## Technical Details

### New Meta Fields

- `_ielts_cm_layout_type`: Stores the layout type (standard/computer_based)
- `_ielts_cm_reading_texts`: Array of reading text objects with title and content

### Templates

- Standard layout: `templates/single-quiz.php`
- Computer-based layout: `templates/single-quiz-computer-based.php`
- Template selection: `templates/single-quiz-page.php`

### CSS Classes

Main container: `.ielts-computer-based-quiz`
- `.computer-based-container`: Two-column wrapper
- `.reading-column`: Left column for passages
- `.questions-column`: Right column for questions
- `.question-navigation`: Bottom navigation bar

### JavaScript Features

- Question navigation with smooth scrolling
- Answer tracking for visual feedback
- Highlight animation on question navigation

## Troubleshooting

### Layout Not Showing

**Problem**: Exercise still shows standard layout  
**Solution**: 
1. Edit the exercise
2. Ensure "Layout Type" is set to "Computer-Based IELTS Layout"
3. Save the exercise
4. Clear browser cache and reload

### Scrolling Issues

**Problem**: Columns not scrolling properly  
**Solution**: 
1. Check browser compatibility (modern browsers required)
2. Clear browser cache
3. Disable conflicting theme CSS if necessary

### Navigation Not Working

**Problem**: Navigation buttons don't scroll to questions  
**Solution**: 
1. Ensure JavaScript is enabled
2. Check browser console for errors
3. Clear browser cache and reload

### Mobile Display Issues

**Problem**: Layout not responsive on mobile  
**Solution**: 
1. The layout automatically adapts to mobile screens
2. Columns stack vertically on smaller screens
3. Test with different mobile devices/browsers

## Examples

### Example 1: Single Passage Exercise

```
Layout Type: Computer-Based IELTS Layout
Reading Text 1:
  Title: Passage 1
  Content: [Your reading passage about climate change]

Questions:
  1. Multiple Choice: What is the main idea?
  2. True/False/Not Given: The article mentions...
  3. Fill in the Blank: The greenhouse effect is caused by...
```

### Example 2: Multiple Passage Exercise

```
Layout Type: Computer-Based IELTS Layout
Reading Text 1:
  Title: Passage 1: Ancient Civilizations
  Content: [Historical text]

Reading Text 2:
  Title: Passage 2: Modern Society
  Content: [Contemporary comparison text]

Questions:
  1-5: Questions about Passage 1
  6-10: Questions about Passage 2
  11-12: Comparison questions
```

## Backward Compatibility

All existing exercises continue to work with the standard layout. The computer-based layout is opt-in:
- Existing exercises: Automatically use standard layout
- New exercises: Choose between standard and computer-based
- No migration required

## Future Enhancements

Potential improvements for future versions:
- Question-to-passage linking (specify which questions go with which passages)
- Highlighting text in reading passages
- Note-taking functionality
- Timer display
- Section-based navigation (groups of questions)

## Support

For questions or issues with the computer-based layout:
1. Check this guide first
2. Review the CHANGELOG.md for recent updates
3. Test with different browsers
4. Contact plugin support with specific details

---

**Version**: 2.1  
**Last Updated**: 2025-12-18
