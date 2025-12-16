# Structure Rebuild - Usage Examples

## Quick Start Examples

### Example 1: Basic IELTS Course (Plain Text)

**Input:**
```
Course Name: IELTS Preparation Basics

Structure:
Introduction to IELTS
  What is IELTS?
  Test Format Overview
  Scoring System
Reading Module
  Skimming Techniques
  Scanning Techniques
  Understanding Main Ideas
Listening Module
  Section 1: Social Situations
  Section 2: Monologues
  Note-taking Skills
Writing Module
  Task 1: Graphs and Charts
  Task 2: Essays
  Grammar and Vocabulary
Speaking Module
  Part 1: Interview
  Part 2: Long Turn
  Part 3: Discussion
```

**Result:**
- 1 Course: "IELTS Preparation Basics"
- 5 Lessons with 13 Lesson Pages total

### Example 2: Academic IELTS Course (Plain Text with Dashes)

**Input:**
```
Course Name: Academic IELTS Complete Guide

Structure:
- Academic Reading Skills
  - Reading for Gist
  - Reading for Detail
  - Dealing with Unknown Vocabulary
  - Paragraph Headings
- Academic Writing Task 1
  - Line Graphs
  - Bar Charts
  - Pie Charts
  - Tables
  - Process Diagrams
- Academic Writing Task 2
  - Opinion Essays
  - Discussion Essays
  - Problem-Solution Essays
  - Two-Part Questions
- General Study Skills
  - Time Management
  - Test Day Preparation
```

**Result:**
- 1 Course: "Academic IELTS Complete Guide"  
- 4 Lessons with 16 Lesson Pages total

### Example 3: From LearnDash HTML

**Scenario:** You have a LearnDash course and want to recreate its structure.

**Steps:**
1. Open the course page in LearnDash
2. Right-click on the curriculum section
3. Select "Inspect" (opens developer tools)
4. Find the div with class `ld-item-list` or similar
5. Right-click on that element in the dev tools
6. Select "Copy" → "Copy outer HTML"

**Sample HTML you might copy:**
```html
<div class="ld-item-list ld-lesson-list">
  <div class="ld-item-list-item ld-lesson-item" data-lesson-id="123">
    <div class="ld-item-list-item-preview">
      <span class="ld-item-title">Lesson 1: Introduction</span>
    </div>
    <div class="ld-lesson-topic-list">
      <div class="ld-topic-item" data-topic-id="456">
        <span class="ld-topic-title">Welcome Video</span>
      </div>
      <div class="ld-topic-item" data-topic-id="457">
        <span class="ld-topic-title">Course Overview</span>
      </div>
    </div>
  </div>
  <div class="ld-item-list-item ld-lesson-item" data-lesson-id="124">
    <div class="ld-item-list-item-preview">
      <span class="ld-item-title">Lesson 2: Getting Started</span>
    </div>
  </div>
</div>
```

**Result:**
- 2 Lessons extracted
- First lesson has 2 topics
- Second lesson has no topics

## Common Use Cases

### Use Case 1: Recreating a Course from a Syllabus

**Scenario:** You have a course syllabus in a Word document or PDF, and you want to create the course structure.

**Example Syllabus:**
```
IELTS Writing Masterclass

Week 1: Introduction to IELTS Writing
  - Understanding the Writing Test
  - Task 1 vs Task 2
  - Scoring Criteria

Week 2: Task 1 - Describing Trends
  - Line Graphs
  - Bar Charts
  - Practice Exercises

Week 3: Task 1 - Comparing Data
  - Tables
  - Pie Charts
  - Mixed Chart Types

Week 4: Task 2 - Essay Writing
  - Essay Structure
  - Introduction Paragraphs
  - Body Paragraphs
  - Conclusions

Week 5: Advanced Techniques
  - Complex Sentences
  - Academic Vocabulary
  - Common Mistakes
```

**In the tool:**
1. Course Name: "IELTS Writing Masterclass"
2. Input Type: Plain text
3. Copy and paste the structure (weeks become lessons, sub-items become lesson pages)

### Use Case 2: Migrating Multiple Courses from Documentation

**Scenario:** You have multiple courses documented in a spreadsheet or project management tool.

**Course 1: IELTS Speaking Course**
```
Speaking Fundamentals
  Pronunciation Basics
  Fluency Training
  Coherence Practice
Part 1 Preparation
  Common Questions
  Sample Answers
  Practice Sessions
Part 2 Preparation
  Cue Card Strategies
  Time Management
  Sample Topics
Part 3 Preparation
  Abstract Questions
  Extended Responses
```

**Course 2: IELTS Listening Course**
```
Listening Strategies
  Prediction Techniques
  Note-taking Methods
Section 1 Practice
  Everyday Conversations
  Form Filling
Section 2 Practice
  Monologues
  Map Labeling
Sections 3 & 4 Practice
  Academic Discussions
  Lectures
```

**Process:** Create each course one at a time using the Structure Rebuild tool.

### Use Case 3: Quick Prototyping

**Scenario:** You want to quickly create a course structure to show to stakeholders before adding full content.

**Input:**
```
IELTS Reading Speed Course

Foundation
  Reading Speed Assessment
  Setting Goals
Speed Reading Techniques
  Chunking
  Skimming Methods
  Eliminating Subvocalization
Practice Sessions
  Timed Reading 1
  Timed Reading 2
  Timed Reading 3
Progress Tracking
  Mid-Course Assessment
  Final Assessment
```

**Result:** 
- Structure created in minutes
- Stakeholders can review before you spend time on content
- Easy to adjust based on feedback

## Tips by Use Case

### For Migration from LearnDash

1. **Best approach:** Use the browser developer tools to copy HTML
2. **Fallback:** If HTML parsing fails, manually create a text outline from what you see
3. **Verify:** Always review the parsed structure before creating
4. **Content separately:** After creating structure, copy-paste content from LearnDash pages

### For Creating New Courses

1. **Start with outline:** Write your course outline in any text editor
2. **Simple format:** Just use indentation or dashes
3. **Iterate:** Create structure first, then refine lesson/topic names
4. **Content later:** Add full descriptions and materials after structure is in place

### For Bulk Migration

1. **Prepare documents:** Create one text file per course
2. **Consistent format:** Use the same outline style for all courses
3. **Process one by one:** Create courses individually through the interface
4. **Keep records:** Note which courses you've completed

## Troubleshooting Common Scenarios

### Problem: HTML parsing returns empty

**Solution:**
```
Instead of copying the HTML, switch to plain text mode and manually list the structure:

Lesson 1
  Topic 1
  Topic 2
Lesson 2
  Topic 3
```

### Problem: Topics showing as lessons

**Solution:**
Make sure topics are indented:
```
CORRECT:
Lesson Name
  Topic Name    <- indented with 2+ spaces

INCORRECT:
Lesson Name
Topic Name      <- not indented
```

### Problem: Some items missing after parsing

**Solution:**
1. Check that all items are visible in the source HTML
2. Try copying a larger section of HTML
3. Or manually add the missing items after creation

## Advanced Examples

### Mixed Content Course

**Input:**
```
Course Name: Complete IELTS Preparation

Module 1: Assessment
  Pre-Course Test
  Skills Analysis
Module 2: Reading
  Strategy Overview
  Practice Test 1
  Practice Test 2
Module 3: Writing
  Task 1 Guide
  Task 2 Guide
  Writing Practice
Module 4: Listening
  Listening Strategies
  Practice Test 1
Module 5: Speaking
  Mock Interview
  Feedback Session
Module 6: Final Preparation
  Full Practice Test
  Test Day Tips
```

### Course with Many Lessons

**Input (abbreviated):**
```
Course Name: 30-Day IELTS Challenge

Day 1: Introduction
  Welcome
  Goal Setting
Day 2: Reading Basics
  Skimming
  Scanning
Day 3: Vocabulary Building
  Word Lists
  Flashcards
[... Days 4-28 ...]
Day 29: Mock Test
  Full Test
  Review
Day 30: Final Preparation
  Test Day Checklist
  Last Tips
```

**Tip:** For courses with many lessons, consider breaking into multiple smaller courses (e.g., "IELTS Reading" and "IELTS Writing" separately).

## See Also

- [STRUCTURE_REBUILD_GUIDE.md](STRUCTURE_REBUILD_GUIDE.md) - Complete documentation
- [LEARNDASH_IMPORT_GUIDE.md](LEARNDASH_IMPORT_GUIDE.md) - XML import alternative
- [README.md](README.md) - Plugin overview
