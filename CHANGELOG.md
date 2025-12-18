# Changelog

All notable changes to the IELTS Course Manager plugin will be documented in this file.

## [1.13] - 2024-12-17

### Fixed
- **Exercise Validation**: Added validation to prevent publishing exercises without questions
  - Exercises without questions are automatically saved as drafts instead of being published
  - Added admin notice explaining why an exercise was not published
  - Added inline warning in quiz meta box when no questions exist
  - Dynamic UI updates warning visibility when questions are added/removed
  - Prevents creation of empty exercises that display "No questions available" to students

### Enhanced
- **Course Listing Shortcode**: Enhanced [ielts_courses] shortcode with new parameters
  - Added `columns` parameter to control grid layout (1-6 columns, default 5)
  - Existing `category` parameter now filters by category slug
  - Existing `limit` parameter controls number of courses shown
  - Example: `[ielts_courses category="beginner" columns="3" limit="9"]`
  - Responsive design automatically adjusts columns on smaller screens
- **Admin Course List**: Added Category column to courses admin list
  - Displays course categories with clickable links to filter by category
  - Shows "—" when no category is assigned

## [1.11] - 2024-12-17

### Fixed
- **Sublesson Auto-Completion**: Fixed issue where sublessons were not showing as complete when viewed
  - Modified `auto_mark_lesson_on_view()` to only record lesson access, not mark as complete
  - Lessons are now only marked complete when ALL resources are viewed AND ALL quizzes are attempted
  - Resources (sublessons) continue to be marked complete automatically when viewed by enrolled users
  
### Changed
- **Courses Grid Layout**: Improved course listing display for better consistency
  - Desktop (>1200px): 5 courses per row
  - Tablet (900-1200px): 3 courses per row
  - Small tablet (768-900px): 2 courses per row
  - Mobile (<768px): 1 course per row
  - Removed auto-fill behavior for more predictable layout

## [1.7.0] - 2024-12-17

### Added
- **Manual Enrollment System**: New admin page for managing user enrollments
  - Create new users and enroll them in courses directly from admin panel
  - Enroll existing users in multiple courses at once
  - Default course duration of 1 year (365 days)
  - Support for enrolling users in all available courses
- **Course End Date Management**: Track and modify course access expiration
  - Added `course_end_date` field to enrollment database table
  - Admins can modify end dates for individual enrollments
  - Automatic calculation of 1-year access from enrollment date
- **My Account Page**: New user-facing account dashboard
  - New shortcode `[ielts_my_account]` displays user enrollment details
  - Shows course enrollment dates and access expiration dates
  - Displays course completion progress for each enrolled course
  - Visual indication of expired courses
  - Direct links to continue learning in active courses

### Changed
- **Lesson Completion Logic**: Improved accuracy of lesson completion tracking
  - Lessons now marked complete ONLY when ALL sublesson pages are viewed AND ALL exercises are attempted
  - More accurate progress tracking and course completion percentages
  - Prevents premature lesson completion status
- **Page Layout Improvements**: Enhanced consistency across all page types
  - Reduced top padding from 60px to 30px on all course/lesson/sublesson pages
  - Fixed sublesson page width to match course and lesson pages (100% width)
  - Improved visual consistency throughout the plugin
- **Course Display**: Removed featured image from individual course pages
  - Featured images now only display on course listing pages
  - Cleaner, more focused course detail pages

### Fixed
- Enrollment table now properly tracks course end dates
- Consistent width and padding across all IELTS custom post type pages
- Lesson completion status now accurately reflects actual progress

## [1.6.0] - 2024-12-17

### Added
- **Sub Lesson (Resource) Page Template**: New dedicated template for sub lesson pages with breadcrumb navigation
  - Breadcrumb navigation showing Course > Lesson > Sub Lesson hierarchy
  - Consistent padding and width matching course and lesson pages
  - "Mark as Complete" functionality for enrolled students
  - Support for external resource links
- **Quiz Question Conversion**: Automatic conversion of LearnDash quiz questions during import/conversion
  - Converts multiple choice questions
  - Converts true/false questions
  - Converts fill-in-the-blank questions
  - Converts essay questions
  - Maintains question points and correct answers

### Changed
- **Terminology Update**: Renamed "Lesson pages" to "Sub lessons" throughout the plugin for clarity
  - Updated post type labels in admin interface
  - Updated template display labels
  - Improved consistency across the UI
- **LearnDash Import Improvements**: Enhanced order preservation and relationship handling
  - Fixed menu_order preservation in XML importer
  - Fixed menu_order preservation in direct database converter
  - Quizzes now properly linked to lessons (not just courses)
  - Sub lessons now properly ordered within lessons

### Fixed
- Sub lesson pages now display with proper breadcrumb navigation
- Sub lesson pages now have consistent styling with course and lesson pages
- LearnDash import now preserves the original order of lessons, sub lessons, and quizzes
- Quizzes are now correctly added to the lessons table during LearnDash import
- Quiz questions are now automatically converted from LearnDash format

### Security
- Added proper escaping for JavaScript output in templates
- Improved SQL query preparation with parameterized queries
- Added input validation with intval() for all ID parameters

## [1.3.0] - 2024-12-16

### Added
- **Direct LearnDash Converter**: New one-click conversion tool for sites with both LearnDash and IELTS Course Manager installed
  - Convert LearnDash courses directly from the database without XML export/import
  - Real-time progress monitoring with modal window
  - Live conversion log showing each step
  - Automatically detects and skips already-converted courses
  - Converts courses, lessons, topics (to lesson pages), and quizzes
  - Preserves course structure and relationships
  - Safe to re-run - will not create duplicates
- Modal UI for conversion progress tracking
- Comprehensive error reporting during conversion
- New JavaScript asset for converter functionality
- New CSS styling for converter interface

### Changed
- Updated plugin version to 1.3.0
- Replaced XML import functionality with direct database conversion
- Improved user experience for LearnDash migration

### Removed
- XML import page and functionality (replaced by direct converter)
- XML-based LearnDash import process (no longer needed when both plugins are on same site)

### Documentation
- Added comprehensive [LEARNDASH_CONVERSION_GUIDE.md](LEARNDASH_CONVERSION_GUIDE.md) with detailed conversion instructions
- Updated [README.md](README.md) to highlight new direct conversion feature
- Includes troubleshooting, best practices, and FAQ for conversion process

## [1.0.0] - 2024-12-16

### Added
- Initial release of IELTS Course Manager plugin
- Custom post types for Courses, Lessons, Resources, and Quizzes
- Course management system supporting 25+ courses
- Lesson structure with hierarchical organization
- Learning resources system with multiple resource types:
  - Documents
  - Videos
  - Audio files
  - External links
- Comprehensive quiz system with four question types:
  - Multiple Choice
  - True/False
  - Fill in the Blank
  - Essay (manual grading)
- Progress tracking system:
  - Per-course progress tracking
  - Lesson completion status
  - Last accessed timestamps
  - Completion percentage calculation
- Quiz results tracking:
  - Store all quiz attempts
  - Display best scores
  - Show score history
  - Automatic grading for objective questions
- Student enrollment system:
  - Enroll/unenroll functionality
  - Track enrolled students per course
  - Active/inactive status
- Admin interface:
  - Course settings meta box
  - Lesson assignment interface
  - Resource management
  - Quiz builder with dynamic question addition
  - Progress reports dashboard
  - Custom admin columns
- Frontend interface:
  - Course listing
  - Single course view with lessons
  - Lesson view with resources and quizzes
  - Quiz taking interface
  - Progress dashboard
- Shortcode system:
  - [ielts_courses] - Display all courses
  - [ielts_course id="X"] - Display single course
  - [ielts_lesson id="X"] - Display lesson
  - [ielts_quiz id="X"] - Display quiz
  - [ielts_progress] - Display progress page
- AJAX functionality:
  - Course enrollment
  - Lesson completion marking
  - Quiz submission
  - Progress updates
- Responsive CSS styling for all components
- JavaScript for interactive features
- Database tables for storing:
  - Progress data
  - Quiz results
  - Enrollment information
- Plugin activation/deactivation hooks
- Uninstall script for clean removal
- Course categories taxonomy
- Documentation:
  - README.md
  - PLUGIN_README.md
  - USAGE_GUIDE.md

### Features Overview
- Flexible alternative to LearnDash
- Support for unlimited courses, lessons, and quizzes
- Progress tracking per course
- Comprehensive student dashboard
- Multiple quiz types similar to LearnDash
- Easy content management through WordPress admin
- Shortcode support for flexible display
- Clean, modern UI design
- Mobile-responsive interface

### Technical Details
- WordPress 5.0+ compatible
- PHP 7.2+ required
- MySQL 5.6+ required
- Custom database tables for efficient data storage
- Object-oriented PHP code structure
- Follows WordPress coding standards
- Uses WordPress security best practices (nonces, sanitization, escaping)
- AJAX-powered interactive features
- Modular plugin architecture

## Future Enhancements (Planned)

### Version 1.1.0
- Certificate generation upon course completion
- Email notifications for quiz results
- Advanced progress reports with charts
- Export progress data to CSV
- Bulk enrollment functionality
- Course prerequisites

### Version 1.2.0
- Discussion forums per course
- Live video integration
- Assignment submission system
- Drip content scheduling
- Student groups and cohorts
- Instructor role and permissions

### Version 1.3.0
- Gamification features (badges, points)
- Course reviews and ratings
- Advanced quiz analytics
- AI-powered content recommendations
- Multi-language support
- Mobile app integration

---

## Legend

- **Added** - New features
- **Changed** - Changes to existing functionality
- **Deprecated** - Features marked for removal
- **Removed** - Features removed
- **Fixed** - Bug fixes
- **Security** - Security improvements
