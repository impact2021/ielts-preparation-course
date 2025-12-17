# Changelog

All notable changes to the IELTS Course Manager plugin will be documented in this file.

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
