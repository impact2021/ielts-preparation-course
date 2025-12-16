# LearnDash Structure Rebuild Guide

## Overview

This tool solves a critical problem when migrating from LearnDash: **preserving course structure when XML exports don't maintain relationships**.

When you export content from LearnDash using WordPress's native export tool, the relationships between courses, lessons, and topics are often lost. This new feature allows you to rebuild the proper hierarchy by providing:

1. **The course name**
2. **LearnDash HTML showing the course structure** OR **a structured text list**

## When to Use This Tool

Use this tool when:
- XML exports from LearnDash don't preserve course/lesson/topic relationships
- You have the course structure visible on a LearnDash page but can't export it properly
- You want to manually create a course structure based on a document or screenshot
- You need to recreate a course hierarchy without access to the original WordPress database

## How It Works

The tool has two input modes:

### Mode 1: HTML from LearnDash (Recommended)

This mode parses the HTML structure directly from a LearnDash course page.

**Steps:**
1. Open a course page in your LearnDash site
2. Right-click on the course curriculum/outline section
3. Select "Inspect" or "Inspect Element" (opens browser developer tools)
4. In the developer tools panel, locate the curriculum container element (usually has classes like `ld-item-list` or `ld-table-list`)
5. Right-click on that element in the developer tools
6. Select "Copy" → "Copy element" or "Copy outer HTML"
7. Paste the HTML into the Structure Rebuild tool

**What the parser looks for:**
- LearnDash lesson elements: `.ld-lesson-item`, `.ld-lesson`, etc.
- LearnDash topic elements: `.ld-topic-item`, `.ld-topic`, etc.
- Hierarchical structure (topics nested under lessons)
- Element text content for names

### Mode 2: Plain Text Structure

This mode parses a simple text outline.

**Format:**
```
Lesson 1 Name
  - Topic 1 Name
  - Topic 2 Name
Lesson 2 Name
  - Topic 3 Name
  - Topic 4 Name
Lesson 3 Name
```

**Rules:**
- Lessons start at the beginning of a line (no indentation)
- Topics are indented with spaces or start with a dash
- Empty lines are ignored
- Leading dashes are optional but help readability

**Example:**
```
Introduction to IELTS
  - What is IELTS?
  - Test Format Overview
  - Scoring System
Reading Skills
  - Skimming and Scanning
  - Understanding Main Ideas
  - Detail Questions
  - Inference Questions
Writing Task 1
  - Describing Graphs
  - Comparing Data
  - Task Achievement
Writing Task 2
  - Essay Structure
  - Argument Development
  - Coherence and Cohesion
```

## Step-by-Step Usage

### Step 1: Access the Tool

1. Log in to WordPress admin
2. Go to **IELTS Courses** → **Rebuild from LearnDash**

### Step 2: Input Course Information

1. **Course Name**: Enter the name of the course you want to create
2. **Structure Input**: 
   - Paste LearnDash HTML (from developer tools)
   - OR enter a plain text outline
3. **Input Type**: Select whether you're providing HTML or plain text
4. Click **"Parse Structure"**

### Step 3: Review and Edit Structure

After parsing, you'll see:
- A visual tree of the course structure
- Lessons with their nested topics
- Drag-and-drop handles for reordering

**Actions you can take:**
- Drag lessons to reorder them
- Drag topics to reorder them within a lesson
- Verify all items are named correctly
- Click "Back to Input" if you need to start over

### Step 4: Create the Course

1. Review the structure one final time
2. Click **"Create Course Structure"**
3. The tool will create:
   - 1 Course post
   - Multiple Lesson posts (linked to the course)
   - Multiple Lesson Page posts (linked to their respective lessons)
4. You'll see a success message with:
   - Course ID and name
   - Number of lessons created
   - Number of lesson pages created
   - A link to edit the course

### Step 5: Add Content

After creating the structure:
1. Click the "Edit Course" button (or navigate to the course)
2. Add course description and details
3. Edit each lesson to add lesson content
4. Edit each lesson page to add page content
5. Create and assign quizzes as needed

## Examples

### Example 1: Simple Course from Text

**Input:**
```
Course Name: IELTS Writing Basics
Structure:
Introduction
  - Overview of Writing Test
  - Scoring Criteria
Task 1 Preparation
  - Understanding Questions
  - Practice Exercises
Task 2 Preparation
  - Essay Structure
  - Common Topics
```

**Result:**
- Course: "IELTS Writing Basics"
- Lesson: "Introduction"
  - Lesson Page: "Overview of Writing Test"
  - Lesson Page: "Scoring Criteria"
- Lesson: "Task 1 Preparation"
  - Lesson Page: "Understanding Questions"
  - Lesson Page: "Practice Exercises"
- Lesson: "Task 2 Preparation"
  - Lesson Page: "Essay Structure"
  - Lesson Page: "Common Topics"

### Example 2: Complex Course from LearnDash HTML

When you copy HTML from LearnDash, you might get something like:

```html
<div class="ld-item-list">
  <div class="ld-item-list-item ld-lesson-item">
    <span class="ld-item-title">Reading Skills</span>
    <div class="ld-lesson-topic-list">
      <div class="ld-topic-item">
        <span class="ld-topic-title">Skimming Techniques</span>
      </div>
      <div class="ld-topic-item">
        <span class="ld-topic-title">Scanning Techniques</span>
      </div>
    </div>
  </div>
  <div class="ld-item-list-item ld-lesson-item">
    <span class="ld-item-title">Listening Practice</span>
  </div>
</div>
```

The parser will extract:
- Lesson: "Reading Skills"
  - Lesson Page: "Skimming Techniques"
  - Lesson Page: "Scanning Techniques"
- Lesson: "Listening Practice"

## Tips and Best Practices

### For HTML Input

1. **Get the right element**: Make sure you're copying the entire curriculum container, not just a single lesson
2. **Include nested structure**: The HTML should include both lessons and their topics
3. **Check in developer tools**: Verify you can see the nested structure in the HTML before copying
4. **Multiple attempts okay**: If the parser doesn't find items, try selecting a different parent element

### For Text Input

1. **Use consistent indentation**: Pick either spaces or tabs and stick with it
2. **Be clear with hierarchy**: Lessons at the start of lines, topics indented
3. **Copy from existing documents**: You can paste from Word, Google Docs, or text files
4. **Review for typos**: Names are used exactly as entered

### General Tips

1. **Start small**: Test with a single course first before doing bulk migrations
2. **Backup first**: Always backup your database before creating multiple courses
3. **Content comes later**: This tool creates structure only - add content separately
4. **Edit after creation**: It's easier to fix names and order after creation than to redo parsing
5. **Use with XML import**: This tool complements (not replaces) the XML import feature
   - Use XML import for content (descriptions, text, etc.)
   - Use Structure Rebuild for fixing broken relationships

## Troubleshooting

### Parser Returns Empty Structure

**Problem**: No lessons or topics were found after parsing

**Solutions**:
1. **For HTML**: Try copying a larger section of the page (go up one level in the HTML tree)
2. **For HTML**: Verify the HTML actually contains lesson/topic names (check in developer tools)
3. **For Text**: Check your indentation - lessons should start at column 0, topics should be indented
4. **For Text**: Remove any special characters or formatting

### Wrong Hierarchy

**Problem**: Topics are showing as lessons, or vice versa

**Solutions**:
1. **For HTML**: Make sure you're copying the container that includes the nested structure
2. **For Text**: Check indentation - topics must be indented more than lessons
3. **Use the editor**: After parsing, you can drag items to rearrange them

### Missing Items

**Problem**: Some lessons or topics didn't get parsed

**Solutions**:
1. **For HTML**: Check if LearnDash is hiding items with JavaScript - try copying from a logged-in student view
2. **For Text**: Check for blank lines breaking the structure
3. **Add manually**: You can always add missing lessons/pages after creation

### Names Are Wrong

**Problem**: Lesson or topic names don't match what you expected

**Solutions**:
1. **For HTML**: The parser might be picking up extra text from the HTML - try cleaning the HTML first
2. **Edit after creation**: It's often easier to edit the post title after creation
3. **Reparse with text**: If HTML gives bad results, switch to text mode for better control

## Comparison: Structure Rebuild vs. XML Import

| Feature | Structure Rebuild | XML Import |
|---------|------------------|------------|
| **Purpose** | Rebuild structure/hierarchy | Import full content |
| **Input** | HTML or text outline | WordPress XML file |
| **Creates** | Post structure with relationships | Posts with content and metadata |
| **Content** | Empty (titles only) | Full descriptions, settings, etc. |
| **Relationships** | ✅ Always preserved | ⚠️ May be lost if exported separately |
| **Use Case** | Fix broken relationships | Migrate complete content |
| **Best For** | Quick structure recreation | Full site migration |

**Recommended Workflow**:
1. Try XML import first (if you have a complete export)
2. If relationships are broken, use Structure Rebuild to recreate them
3. Or: Use Structure Rebuild first, then manually add content

## Advanced Usage

### Batch Processing Multiple Courses

If you have many courses to recreate:

1. **Prepare text files**: Create one text file per course with the structure
2. **Use the tool repeatedly**: Process one course at a time
3. **Use consistent naming**: Makes it easier to identify and edit later
4. **Script it** (advanced): If you have dozens of courses, consider using WP-CLI with a custom script

### Integrating with Other Tools

This tool works well with:
- **WordPress Importer**: Import posts, then use Structure Rebuild to fix relationships
- **LearnDash to IELTS Importer**: Import content first, recreate structure if needed
- **Manual course creation**: Create posts manually, then link them with this tool

### Using for Non-LearnDash Courses

This tool isn't just for LearnDash migration. Use it for:
- Creating course structures from curriculum documents
- Rebuilding courses from course catalogs or syllabi
- Migrating from other LMS platforms (if you can get the structure as HTML or text)
- Quick course prototyping before adding full content

## Frequently Asked Questions

**Q: Will this import my course content and descriptions?**
A: No, this tool only creates the structure (course, lessons, lesson pages) with titles. You need to add descriptions and content separately.

**Q: What if I make a mistake?**
A: You can delete the created posts and try again, or edit them individually. The tool creates regular WordPress posts.

**Q: Can I import multiple courses at once?**
A: Currently, no. Process one course at a time through the interface.

**Q: Do I need to have LearnDash installed?**
A: No, this tool works independently. You just need the HTML or a text outline of the structure.

**Q: Will this import quizzes?**
A: No, quizzes must be created separately. This tool only handles courses, lessons, and lesson pages (topics).

**Q: Can I edit the structure after creation?**
A: Yes, you can edit, delete, or add lessons and pages using the standard WordPress post editor. You can also reassign relationships.

**Q: What happens if I run the tool twice with the same course name?**
A: It will create a second course with the same name. Use unique course names or delete duplicates manually.

**Q: Does this work with the export feature?**
A: Yes! After creating courses with this tool, you can export them using the Export to XML feature for backup or migration.

## Support

For issues or questions:
- Check the troubleshooting section above
- Review the examples for your use case
- Visit: https://github.com/impact2021/ielts-preparation-course/issues

## Version History

**v1.2** - Initial release of Structure Rebuild feature
- HTML parsing from LearnDash pages
- Plain text structure parsing
- Drag-and-drop structure editor
- Automatic relationship creation
