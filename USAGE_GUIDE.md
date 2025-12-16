# IELTS Course Manager - Quick Start Guide

## Overview

This plugin replaces LearnDash with a more flexible system for managing IELTS preparation courses. It provides:

- Course management (support for 25+ courses)
- Lessons within courses
- Learning resources per lesson
- Quizzes with multiple question types
- Progress tracking per course
- Comprehensive progress and quiz results page

## Getting Started

### Step 1: Install and Activate

1. Upload the plugin folder to `/wp-content/plugins/ielts-course-manager/`
2. Activate through WordPress Admin > Plugins
3. You'll see a new "IELTS Courses" menu in the admin sidebar

### Step 2: Create Your First Course

1. Navigate to **IELTS Courses > Add New Course**
2. Enter:
   - Course title (e.g., "IELTS Speaking Preparation")
   - Course description (use the editor for rich content)
   - Duration in hours (e.g., 10)
   - Difficulty level (Beginner, Intermediate, Advanced)
3. Add a featured image (recommended)
4. Click **Publish**

### Step 3: Create Lessons

1. Go to **IELTS Courses > Lessons > Add New**
2. Enter:
   - Lesson title (e.g., "Introduction to IELTS Speaking")
   - Lesson content (detailed explanation, instructions)
   - Assign to the course you created
   - Set duration in minutes (e.g., 30)
   - Set menu order (controls lesson sequence: 1, 2, 3, etc.)
3. Click **Publish**
4. Repeat to create all lessons for your course

### Step 4: Add Lesson pages

1. Go to **IELTS Courses > Lesson pages > Add New**
2. Enter:
   - Lesson page title (e.g., "Speaking Sample Questions PDF")
   - Lesson page description
   - Assign to a lesson
   - Optionally add a resource URL for external resources
3. Click **Publish**
4. Add multiple lesson pages per lesson as needed

### Step 5: Create Quizzes

1. Go to **IELTS Courses > Quizzes > Add New**
2. Enter quiz title and description
3. Assign to a course (and optionally a specific lesson)
4. Set passing percentage (default is 70%)
5. Click **Add Question** to add questions:
   
   **For Multiple Choice:**
   - Select "Multiple Choice" type
   - Enter question text
   - Enter options (one per line)
   - Enter correct answer (option number: 0, 1, 2, etc.)
   - Set points
   
   **For True/False/Not Given:**
   - Select "True/False/Not Given" type
   - Enter question text
   - Enter correct answer (true, false, or not_given - lowercase)
   - Set points
   - Note: This is the standard IELTS format
   
   **For Fill in the Blank:**
   - Select "Fill in the Blank" type
   - Enter question text (include blank with _____ or similar)
   - Enter correct answer
   - Set points
   - Note: Matching is case-insensitive and ignores punctuation/extra spaces
   
   **For Essay:**
   - Select "Essay" type
   - Enter question text
   - Set points (essay questions need manual grading)

6. Click **Publish**

### Step 6: Display Content on Your Site

Create pages and add shortcodes:

#### Course Listing Page
Create a page called "Courses" and add:
```
[ielts_courses]
```

#### Individual Course Page
Option 1: Use the default single course page (automatically created)
Option 2: Create a custom page and add:
```
[ielts_course id="123"]
```

#### Student Progress Page
Create a page called "My Progress" and add:
```
[ielts_progress]
```

For specific course progress:
```
[ielts_progress course_id="123"]
```

### Step 7: Student Workflow

**How Students Use the System:**

1. **Browse Courses** - Students visit the courses page
2. **Enroll** - Click "Enroll Now" (requires login)
3. **Access Lessons** - View list of lessons in the course
4. **Study Content** - Read lesson content and access resources
5. **Mark Complete** - Click "Mark as Complete" after finishing a lesson
6. **Take Quizzes** - Complete quizzes associated with lessons
7. **Track Progress** - Visit the progress page to see:
   - Completion percentage per course
   - All completed lessons
   - All quiz results and scores
8. **Review Content** - Re-access lessons and retake quizzes anytime

## Key Features Explained

### Progress Tracking
- Automatically tracks when students access lessons
- Records lesson completion when marked complete
- Calculates overall course completion percentage
- Stores all quiz attempts (students can see improvement)

### Quiz System
- Students can take quizzes multiple times
- System shows their best score
- Multiple question types supported
- Automatic grading for objective questions
- Manual grading needed for essay questions

### Flexible Structure
Unlike LearnDash:
- No complex course prerequisites
- Simple, intuitive content organization
- Easy to reorganize lessons (just change menu order)
- No restrictions on resource types
- Unlimited courses, lessons, and quizzes

## Admin Features

### Progress Reports
View student progress:
1. Go to **IELTS Courses > Progress Reports**
2. See all enrolled students
3. View completion percentages
4. Check quiz results

### Course Management
- Organize courses with categories
- Track enrollment numbers per course
- See lesson count per course
- View resource count per lesson

## Tips and Best Practices

1. **Structure Your Courses**
   - Start with an introductory lesson
   - Order lessons logically (use menu order)
   - End with a comprehensive quiz

2. **Use Resources Effectively**
   - Add PDFs for downloadable content
   - Embed videos for visual learning
   - Link to external practice sites
   - Include audio for listening practice

3. **Design Good Quizzes**
   - Mix question types
   - Use appropriate points per question
   - Set realistic passing percentages
   - Provide immediate feedback (in quiz description)

4. **Track Progress**
   - Review progress reports regularly
   - Identify struggling students
   - Adjust content based on quiz results

5. **Organize Content**
   - Use clear, descriptive titles
   - Add excerpts for quick previews
   - Use featured images for visual appeal
   - Keep lesson duration reasonable (20-45 minutes)

## Troubleshooting

### Students Can't See Courses
- Ensure courses are published
- Check if enrollment is required
- Verify user has necessary permissions

### Progress Not Saving
- Ensure user is logged in
- Check JavaScript is enabled
- Verify AJAX is working (check browser console)

### Quiz Scores Incorrect
- Double-check correct answers in quiz settings
- For fill-in-blank, ensure exact text match
- For multiple choice, verify option numbers (start at 0)

## Support

For more detailed documentation, see [PLUGIN_README.md](PLUGIN_README.md)

For issues or questions:
- GitHub: https://github.com/impact2021/ielts-preparation-course
- Create an issue with detailed description
