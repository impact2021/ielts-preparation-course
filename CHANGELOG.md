# Changelog

All notable changes to the IELTS Course Manager plugin will be documented in this file.

## [2.3] - 2025-12-18

### Fixed
- **Lesson Meta Box Error**: Fixed critical error "Uncaught TypeError: in_array(): Argument #2 ($haystack) must be of type array, string given"
  - Issue occurred when retrieving lesson course assignments that were stored as serialized strings
  - Added proper unserialization handling for `_ielts_cm_course_ids`, `_ielts_cm_lesson_ids` metadata
  - Applied fix to lesson_meta_box, resource_meta_box, and quiz_meta_box functions
  - Maintains backward compatibility with both array and serialized string formats

### Changed
- Updated plugin version to 2.3

## [2.2] - 2025-12-18

### Added
- **Question Duplication**: Added duplicate button for questions in exercises
  - One-click duplication of any question type
  - Duplicates all question data including options, feedback, and points
  - Automatically assigns new unique index to duplicated question
  - Works with multiple choice, true/false, fill-in-blank, and essay questions

- **Question Drag-and-Drop**: Implemented drag-and-drop reordering for questions
  - Drag handle icon on each question for easy reordering
  - Visual feedback during drag operation
  - Automatic reindexing of question names after reordering
  - Preserves question content and settings during reorder
  - Improves exercise organization workflow

- **WYSIWYG Editor for Reading Texts**: Enhanced reading text editor in computer-based layout
  - Rich text editor (wp_editor) for reading passages
  - Full formatting toolbar with bold, italic, lists, links, etc.
  - Support for images and media embedding
  - Preserves HTML formatting in reading passages
  - Better content authoring experience for CBT exercises

### Changed
- **Pass Percentage Field**: Hidden pass percentage field from admin UI
  - Field still saves to database for future features
  - Reduces UI clutter in quiz settings
  - Pass percentage remains configurable but not prominently displayed

### Fixed
- **Database Table Creation**: Fixed issue where `wp_ielts_cm_site_connections` table would not exist on sites that installed the plugin before version 2.0
  - Added automatic database table creation check on plugin version update
  - All required tables are now created or verified when version changes
  - Ensures multi-site sync functionality works on all installations

- **Computer-Based Layout Heights**: Fixed viewport height issue for computer-based IELTS test layout
  - Changed from fixed `max-height: 700px` to viewport-relative heights using `calc(100vh - Xpx)`
  - Desktop layout now uses `calc(100vh - 300px)` to properly account for headers and page elements
  - Tablet layout uses `calc(100vh - 400px)` for stacked columns
  - Mobile layout uses `calc(100vh - 450px)` for optimal mobile viewing
  - Layout now properly fills the screen regardless of header size

### Technical
- Updated plugin version to 2.2
- Database upgrade routine now runs on version update to ensure all tables exist
- Enhanced JavaScript for question management with jQuery sortable
- Improved handling of TinyMCE editor instances in dynamic content
- Added null checking for regex matches to prevent errors

## [2.1] - 2025-12-18

### Added - Computer-Based IELTS Test Layout
- **New Layout Type**: Added computer-based IELTS test layout option for exercises
  - Two-column full-width design mimicking actual IELTS computer test
  - Left column displays reading passages with scrollable content
  - Right column shows questions with answer inputs
  - Bottom navigation bar for quick question jumping
  - Smooth scrolling and question highlighting
  
- **Reading Text Management**: New reading text fields for exercises
  - Support for multiple reading passages per exercise
  - Optional titles for each reading text (e.g., "Passage 1")
  - Rich text content support with proper formatting
  - Reading texts displayed in left column of computer-based layout
  
- **Question Navigation**: Interactive question navigation system
  - Navigation buttons for all questions at bottom of page
  - Click any button to jump directly to that question
  - Visual indication when questions are answered (green highlight)
  - Smooth scrolling animation to selected question
  - Question highlight animation on navigation
  
- **Layout Selection**: Exercise can use either standard or computer-based layout
  - Default layout remains unchanged (backward compatible)
  - Computer-based layout selected via dropdown in exercise settings
  - Separate templates for each layout type
  
- **Responsive Design**: Computer-based layout adapts to different screen sizes
  - Desktop: Side-by-side columns with fixed heights and scrollbars
  - Tablet: Vertical stacking with separate scroll areas
  - Mobile: Optimized column heights and navigation layout
  
### Changed
- Updated plugin version to 2.1
- Enhanced quiz meta box with layout type selection
- Modified single quiz page template to support multiple layouts

### Technical Details
- New template: `templates/single-quiz-computer-based.php`
- New CSS styles for two-column layout and navigation
- JavaScript handlers for question navigation and answer tracking
- New meta fields: `_ielts_cm_layout_type` and `_ielts_cm_reading_texts`

## [2.0] - 2025-12-18

### Added - Multi-Site Content Sync
- **Primary/Subsite Architecture**: New multi-site content synchronization system
  - Configure sites as primary (content source) or subsite (content receiver)
  - Primary sites can push content updates to multiple subsites
  - Standalone mode available for sites not using sync features

- **Site Connection Management**: New admin page for managing site connections
  - Navigate to "IELTS Courses > Multi-Site Sync" to configure
  - Add/remove subsite connections with authentication tokens
  - Test connections to verify subsite availability
  - View last sync time and status for each connected subsite

- **Content Push Functionality**: Push content from primary to subsites
  - "Push to Subsites" button on course, lesson, lesson page, and exercise edit pages
  - One-click push to all connected subsites
  - Real-time sync status display with per-subsite results
  - Automatic content change detection using hash comparison

- **Progress Preservation**: Student progress protected during content updates
  - Completed items remain marked as complete after content updates
  - Completion percentages automatically recalculated when new content added
  - Quiz results preserved and linked to updated content
  - User enrollment data maintained across syncs

- **REST API for Sync**: New REST API endpoints for inter-site communication
  - `/wp-json/ielts-cm/v1/sync-content` - Receive content from primary site
  - `/wp-json/ielts-cm/v1/test-connection` - Test connectivity and authentication
  - `/wp-json/ielts-cm/v1/site-info` - Get site information and configuration
  - Token-based authentication for secure communication

- **Database Tables**: New tables for sync management
  - `ielts_cm_site_connections` - Store connected subsite information
  - `ielts_cm_content_sync` - Track sync history and content hashes

- **Sync Logging and History**: Track all sync operations
  - View sync history for each content item
  - Per-subsite sync status (success/failed)
  - Content hash tracking for change detection
  - Last sync timestamp for each subsite

### Changed
- Updated plugin version to 2.0
- Enhanced database schema with multi-site sync tables

### Use Cases Supported
1. **Update Existing Content**: When you update a course, lesson, or exercise on the primary site, push changes to subsites. Student completion status is preserved.

2. **Add New Content**: When you add new lessons or exercises to an existing course, push updates to subsites. Completion percentages automatically adjust for both master and subsites.

3. **Centralized Content Management**: Manage all course content from a single primary site and distribute to multiple learning sites.

## [1.18] - 2025-12-18

### Added
- **Text-Based Exercise Import**: New admin page for creating exercises from pasted text
  - Navigate to "IELTS Courses > Create Exercises from Text"
  - Paste specially formatted text with questions, options, and feedback
  - Automatically parses True/False questions with correct/incorrect indicators
  - Supports multi-question exercises in a single paste
  - Extracts question text, answer options, correct answers, and feedback
  - Creates exercises as drafts or published posts
  - Ideal for quickly importing exercises from formatted documents
  - Example format supported:
    ```
    Exercise Title/Instructions
    
    Question 1 text
    This is TRUE
    Correct answer
    This is FALSE
    Incorrect
    
    Optional feedback text
    
    Question 2 text
    This is TRUE
    This is FALSE
    Correct answer
    ```

### Changed
- Updated plugin version to 1.18

## [1.17] - 2025-12-18

### Enhanced
- **Exercise Results Display**: Complete question feedback now shown after submission
  - Shows full question text for each question
  - Displays user's answer alongside the question
  - Shows correct answer when user answered incorrectly
  - Displays configured feedback for each question/option
  - Supports all question types: multiple choice, true/false, fill-in-blank, essay

- **Auto-Navigation**: Automatic progression to next content after exercise completion
  - 5-second countdown with visual indicator
  - "Continue" button for immediate navigation
  - "Cancel" button to stop auto-redirect and review results
  - Intelligently determines next item: quiz, resource page, lesson, or course
  - Improves learning flow and user experience

- **Multiple Choice Backend Redesign**: New structured interface for creating options
  - Individual input field for each option text
  - Checkbox to mark correct answer for each option
  - Dedicated feedback field for each option
  - Add/Remove option buttons with minimum 2 options enforced
  - More intuitive than previous textarea-based approach
  - Maintains backward compatibility with existing questions

### Changed
- **Passing Score**: Removed passing score display from exercise frontend
  - Exercises no longer show "Passing Score: XX%" to students
  - Focus on learning and feedback rather than pass/fail
  - Passing percentage still configurable in admin for future features

### Fixed
- Improved operator precedence in multiple choice field visibility logic
- Fixed JavaScript string concatenation in dynamic option generation
- Enhanced auto-redirect accessibility with cancel option

### Removed
- **Documentation Cleanup**: Removed 37 unnecessary .md documentation files
  - Kept only essential files: README.md, CHANGELOG.md, SECURITY_SUMMARY.md
  - Cleaner repository structure

## [1.16] - 2025-12-18

### Enhanced
- **XML Exercises Creator - Question Grouping**: Questions that belong to the same quiz are now grouped into multi-question exercises
  - Automatically detects quiz associations via `ld_quiz_*` metadata
  - Creates single exercises with multiple questions instead of separate exercises per question
  - Intelligently extracts base quiz titles (e.g., "Quiz Name Q1" → "Quiz Name")
  - Displays question count in results table
  - Significantly reduces number of exercise posts created

- **XML Exercises Creator - Placeholder Values**: Pre-fills options and correct answers with helpful examples
  - Multiple choice: Pre-fills with "Option A", "Option B", "Option C", "Option D" and correct answer "0"
  - True/False: Pre-fills correct answer with "true" (placeholder to be updated)
  - Fill in the blank: Pre-fills with "[Enter the expected answer here]"
  - Makes it clear what format is expected for each question type

- **Question Editor - WYSIWYG Support**: Question text now uses WordPress visual editor
  - HTML content including images is properly preserved from XML
  - Full visual editing capabilities with formatting toolbar
  - Media buttons for adding/editing images
  - Existing questions display in rich text editor
  - New questions show helper text about HTML support

- **Data Sanitization**: Improved security for question content
  - Uses `wp_kses_post()` instead of `sanitize_textarea_field()` for question text
  - Allows safe HTML while preventing XSS attacks
  - Preserves formatting, images, and other HTML elements

### Updated
- Documentation updated to reflect new features
- XML_EXERCISES_CREATOR_GUIDE.md updated with placeholder information
- README.md highlights v1.16 improvements

## [1.15] - 2025-12-18

### Added
- **LearnDash XML Conversion**: New XML conversion script for LearnDash exports
  - Converts `sfwd-question` post types to `ielts_quiz` format
  - Automatically updates URLs and GUIDs to match new structure
  - Processes 4,500+ questions in under 2 minutes
  - Preserves all metadata and relationships
  - Creates backup of original file automatically

### Documentation
- **New Guide**: Added `XML_CONVERSION_README.md`
  - Complete documentation of XML conversion process
  - Verification steps to ensure successful conversion
  - Import instructions for converted files
  - Troubleshooting guide for common issues
  - File structure and metadata preservation details

### Tools
- **Conversion Script**: Added `convert-xml.php`
  - Standalone PHP script for XML transformation
  - Can process large XML files (13+ MB)
  - Reusable for multiple LearnDash exports
  - Detailed progress reporting and statistics

## [1.14] - 2024-12-18

### Fixed
- **LearnDash XML Import - Question Import**: Enhanced quiz question import from LearnDash XML exports
  - Fixed question-to-quiz linking with multiple meta key fallbacks (`quiz_id`, `_quiz_id`)
  - Fixed options storage format (now stores as newline-separated string instead of array)
  - Added extraction of correct/incorrect answer feedback from question meta
  - Added comprehensive logging to track question import process with skip counters
  - Added warnings for quizzes that end up with no questions after import

- **LearnDash XML Import - Relationship Linking**: Fixed critical relationship linking issues
  - Fixed lessons not linking to courses automatically
  - Fixed lesson pages (topics) not linking to lessons automatically
  - Fixed quizzes not linking to courses and lessons automatically
  - Root cause: `map_meta_key()` was mapping `course_id` inconsistently across post types
  - Now all relationship IDs are stored with `_ld_original_` prefix for consistent lookup
  - Added multiple fallback meta key lookups in `update_relationships()`
  - No longer need to manually open and save items to establish relationships

### Enhanced
- **Import UI Feedback**: Improved user experience for LearnDash XML imports
  - Added detailed import results display showing counts for all imported items
  - Added question count to import summary
  - Added expandable log viewer with color-coded messages (info, warning, error)
  - Added helpful error messages for common import failures
  - Updated import instructions to emphasize exporting `sfwd-question` post types
  - Added XML verification step to guide users

- **Import Logging**: Comprehensive relationship logging
  - Shows count of lessons linked to courses
  - Shows count of lesson pages linked to lessons
  - Shows count of quizzes linked to courses and lessons
  - Individual warnings for items that couldn't be linked with reasons
  - Helps troubleshoot import issues quickly

### Documentation
- **New Guide**: Added `QUIZ_QUESTIONS_IMPORT_GUIDE.md`
  - Comprehensive troubleshooting guide for quiz question import issues
  - Step-by-step solutions for common problems
  - XML structure reference for advanced users
  - Import log interpretation guide
  - Success verification methods
  - Covers both question import and relationship linking issues

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
