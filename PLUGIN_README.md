# IELTS Course Manager - WordPress Plugin

A flexible Learning Management System (LMS) plugin for WordPress, designed specifically for IELTS preparation courses but adaptable for any educational content.

## Features

### Course Management
- Create and manage up to 25+ courses
- Organize courses with categories
- Set course duration and difficulty levels
- Add course descriptions and featured images

### Lesson Structure
- Create lessons within courses
- Define lesson order and duration
- Add rich content to each lesson
- Track lesson completion status

### Lesson pages
- Attach multiple lesson pages to each lesson
- Add optional resource URLs for external resources
- Rich content support with WordPress editor

### Quiz System
Multiple quiz types similar to LearnDash:
- **Multiple Choice** - Select from predefined options
- **True/False/Not Given** - Standard IELTS format with three options
- **Fill in the Blank** - Text input answers with flexible matching (case-insensitive, ignores punctuation/extra spaces)
- **Essay** - Long-form responses (manual grading)

Quiz features:
- Assign quizzes to courses or specific lessons
- Set passing percentages
- Points-based scoring
- Unlimited retakes
- View best scores

### Progress Tracking
- Per-course progress tracking
- Lesson completion status
- Visual progress bars
- Last accessed timestamps
- Completion percentages

### Student Dashboard
- View all enrolled courses
- Track progress across courses
- See all quiz results in one place
- Completion statistics

### LearnDash Import
- Import courses, lessons, topics (lesson pages), and quizzes from LearnDash XML exports
- Batch processing support for large migrations (25+ courses with hundreds of lessons)
- Automatic relationship mapping between imported content
- Skip duplicate items during import
- Detailed import logging with error and warning tracking

## Installation

1. Upload the `ielts-course-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'IELTS Courses' in the admin menu to start creating courses

## Usage

### Creating a Course

1. Go to **IELTS Courses > Add New Course**
2. Enter course title and description
3. Add a featured image (optional)
4. Publish the course

### Creating Lessons

1. Go to **IELTS Courses > Lessons > Add New**
2. Enter lesson title and content
3. Assign to one or more courses (use Ctrl/Cmd to select multiple)
4. Publish the lesson

### Reordering Lessons

1. Go to the course edit page
2. Find the **Course Lessons** meta box
3. Drag and drop lessons to reorder them
4. The order is saved automatically

### Adding Resources

1. Go to **IELTS Courses > Lesson pages > Add New**
2. Enter lesson page title and description
3. Assign to a lesson
4. Optionally add a resource URL for external resources
5. Publish the lesson page

### Creating Quizzes

1. Go to **IELTS Courses > Quizzes > Add New**
2. Enter quiz title and description
3. Assign to a course (and optionally a lesson)
4. Set passing percentage
5. Add questions:
   - Click "Add Question"
   - Select question type
   - Enter question text
   - Add options (for multiple choice)
   - Set correct answer
   - Assign points
6. Publish the quiz

### Importing from LearnDash

For migrating content from LearnDash:

1. Go to **IELTS Courses > Import from LearnDash**
2. Upload your LearnDash XML export file
3. Configure import options:
   - Check "Skip items with duplicate titles" to avoid re-importing
4. Click **Import XML File**
5. Review the import results and log

**For detailed import instructions**, especially for large migrations (25+ courses), see [LEARNDASH_IMPORT_GUIDE.md](LEARNDASH_IMPORT_GUIDE.md).

### Displaying Content

Use shortcodes to display content on any page:

#### Display All Courses
```
[ielts_courses]
```

With category filter:
```
[ielts_courses category="beginner" limit="10"]
```

#### Display Single Course
```
[ielts_course id="123"]
```

#### Display Progress Page
All courses:
```
[ielts_progress]
```

Specific course:
```
[ielts_progress course_id="123"]
```

#### Display Single Lesson
```
[ielts_lesson id="456"]
```

#### Display Quiz
```
[ielts_quiz id="789"]
```

#### Display User's Own Progress
```
[ielts_my_progress]
```

Specific course:
```
[ielts_my_progress course_id="123"]
```

#### Display User Account Page (NEW in v1.7)
```
[ielts_my_account]
```

This displays:
- User information
- All course enrollments with enrollment dates
- Course access expiration dates
- Progress for each enrolled course
- Visual indication of expired courses

### Enrollment (NEW in v1.7)

**Manual Enrollment System:**
Administrators can manage user enrollments through **IELTS Courses > Manage Enrollments**:
1. **Create New User and Enroll** - Create a new WordPress user and enroll them in courses
   - Enter username, email, password, and optional first/last name
   - Select multiple courses for enrollment
   - Default access duration: 1 year from enrollment date
2. **Enroll Existing User** - Enroll existing users in courses
   - Select user from dropdown
   - Choose multiple courses for enrollment
   - Default access duration: 1 year
3. **Modify Course End Dates** - Update access expiration for any enrollment
4. **View All Enrollments** - See all active enrollments with user, course, dates, and status

**Student Self-Enrollment:**
Students can also enroll in courses by:
1. Clicking "Enroll Now" button on course pages (if enabled)

### Progress Tracking

Progress is automatically tracked when:
- Students view lessons
- Students mark lessons as complete
- Students submit quizzes

View progress reports in **IELTS Courses > Progress Reports**

## Database Tables

The plugin creates three custom tables:

1. **wp_ielts_cm_progress** - Stores lesson completion data
2. **wp_ielts_cm_quiz_results** - Stores quiz submissions and scores
3. **wp_ielts_cm_enrollment** - Tracks course enrollments

## Post Types

The plugin registers four custom post types:

1. **ielts_course** - Courses
2. **ielts_lesson** - Lessons
3. **ielts_resource** - Learning resources
4. **ielts_quiz** - Quizzes

## Hooks and Filters

### Actions
- `ielts_cm_after_course_enroll` - Fired after user enrolls in a course
- `ielts_cm_lesson_completed` - Fired when lesson is marked complete
- `ielts_cm_quiz_submitted` - Fired after quiz submission

### Filters
- `ielts_cm_course_meta` - Filter course meta data
- `ielts_cm_quiz_types` - Add custom quiz types
- `ielts_cm_passing_score` - Modify default passing score

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Support

For issues, feature requests, or contributions, please visit:
https://github.com/impact2021/ielts-preparation-course

## License

GPL v2 or later

## Credits

Developed by the IELTS Preparation Team
