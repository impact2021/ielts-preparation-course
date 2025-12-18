=== IELTS Course Manager ===
Contributors: ieltstestonline
Tags: lms, learning-management, ielts, courses, education
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.15
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A flexible Learning Management System for IELTS preparation courses with lessons, resources, quizzes, and progress tracking.

== Description ==

IELTS Course Manager is a comprehensive Learning Management System (LMS) plugin for WordPress, designed as a flexible alternative to LearnDash for IELTS preparation courses.

= Key Features =

* **Course Management** - Create and organize up to 25+ courses
* **Lesson Structure** - Build structured lessons within each course
* **Lesson Pages** - Attach lesson pages with optional resource URLs to lessons
* **Quiz System** - Multiple quiz types (Multiple Choice, True/False/Not Given, Fill in the Blank, Essay)
* **Progress Tracking** - Per-course progress tracking for students
* **Student Dashboard** - Comprehensive progress and quiz results page
* **Manual Enrollment System** - Admin tools for creating users and managing course enrollments
* **User Account Page** - Students can view their enrollments, access dates, and progress
* **LearnDash Import Tool** - Easily migrate from LearnDash with built-in XML import functionality
* **XML Conversion Tools** - Convert LearnDash exports with dedicated conversion script

= Quiz Types =

* **Multiple Choice** - Select from predefined options
* **True/False/Not Given** - Standard IELTS format with three options
* **Fill in the Blank** - Text input answers with flexible matching (case-insensitive, ignores punctuation/extra spaces)
* **Essay** - Long-form responses (manual grading)

= LearnDash Migration =

The plugin includes two migration options:

1. **Direct Conversion** - Convert LearnDash courses directly with one click (same site)
2. **XML Import** - Export from LearnDash, convert XML format, and import to IELTS Course Manager (different sites)

Version 1.15+ includes a powerful XML conversion script that can process 4,500+ questions in under 2 minutes.

= Documentation =

For detailed documentation, see the included README.md and PLUGIN_README.md files, or visit [www.ieltstestonline.com](https://www.ieltstestonline.com/)

== Installation ==

1. Upload the `ielts-course-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to "IELTS Courses" in the WordPress admin to start creating content
4. Use the shortcodes `[ielts_courses]` and `[ielts_student_progress]` to display content on your pages

== Frequently Asked Questions ==

= Can I migrate from LearnDash? =

Yes! The plugin includes built-in LearnDash migration tools. You can either:
* Convert courses directly if both plugins are on the same site
* Export from LearnDash and import the XML file after conversion

= What quiz types are supported? =

The plugin supports Multiple Choice, True/False/Not Given, Fill in the Blank, and Essay question types.

= How does progress tracking work? =

Progress is tracked automatically as students complete lessons and take quizzes. Students can view their progress on a dedicated dashboard page.

= Can I manually enroll students? =

Yes! The plugin includes an enrollment management system where admins can create users and enroll them in courses with configurable access durations.

== Screenshots ==

1. Course management interface
2. Lesson structure builder
3. Quiz editor with multiple question types
4. Student progress dashboard
5. LearnDash import interface

== Changelog ==

= 1.15 - 2025-12-18 =
* Added: LearnDash XML conversion script for post type transformation
* Added: Converts `sfwd-question` to `ielts_quiz` format automatically
* Added: XML_CONVERSION_README.md documentation
* Added: convert-xml.php standalone conversion script
* Fixed: Video embed support in all content templates using `apply_filters('the_content')`
* Fixed: Video embeds now work in sub-lessons, lessons, courses, and quizzes
* Enhanced: Processes 4,500+ questions in under 2 minutes
* Enhanced: Automatic backup creation during XML conversion

= 1.14 - 2024-12-18 =
* Fixed: Enhanced quiz question import from LearnDash XML exports
* Fixed: Question-to-quiz linking with multiple meta key fallbacks
* Fixed: Options storage format for question answers
* Fixed: Lessons not linking to courses automatically
* Fixed: Lesson pages (topics) not linking to lessons automatically
* Fixed: Quizzes not linking to courses and lessons automatically
* Enhanced: Import UI feedback with detailed results display
* Enhanced: Comprehensive relationship logging

= 1.13 - 2024-12-17 =
* Previous version updates
* Various bug fixes and improvements

= 1.12 =
* Fixed quiz question display issues
* Added conversion button in LearnDash quiz admin page
* Added comprehensive feedback support from LearnDash quizzes

= 1.7 =
* Added membership system with lesson completion tracking
* Added user account page for enrollment management
* Enhanced enrollment system with access duration control

= 1.3 =
* Added direct LearnDash conversion tool
* Real-time progress monitoring
* Automatic detection of already-converted courses

= 1.2 =
* Initial release of Structure Rebuild feature

= 1.1 =
* Initial public release

== Upgrade Notice ==

= 1.15 =
This version adds powerful XML conversion tools for LearnDash migrations and fixes video embed support. No database changes required.

= 1.14 =
Enhanced LearnDash import functionality with automatic relationship linking. Recommended for anyone migrating from LearnDash.

== Requirements ==

* WordPress 5.8 or higher
* PHP 7.2 or higher
* MySQL 5.6 or higher

== Support ==

For support, documentation, and feature requests, please visit [www.ieltstestonline.com](https://www.ieltstestonline.com/)

== License ==

This plugin is licensed under the GPL v2 or later.
