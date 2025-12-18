# IELTS Preparation Course - WordPress LMS Plugin

A comprehensive Learning Management System (LMS) plugin for WordPress, designed as a flexible alternative to LearnDash for IELTS preparation courses.

## Overview

This plugin provides a complete course management system with:

- **Course Management** - Create and organize up to 25+ courses
- **Lesson Structure** - Build structured lessons within each course
- **Lesson pages** - Attach lesson pages with optional resource URLs to lessons
- **Quiz System** - Multiple quiz types (Multiple Choice, True/False/Not Given, Fill in the Blank, Essay)
- **Progress Tracking** - Per-course progress tracking for students
- **Student Dashboard** - Comprehensive progress and quiz results page
- **Manual Enrollment System** - Admin tools for creating users and managing course enrollments
- **User Account Page** - Students can view their enrollments, access dates, and progress

## Key Features

✅ **Flexible Course Structure** - More flexible than LearnDash with customizable course organization  
✅ **Multiple Quiz Types** - IELTS-friendly quiz types (Multiple Choice, True/False/Not Given, Fill in the Blank with flexible matching, Essay)  
✅ **Progress Tracking** - Automatic tracking of lesson completion and quiz results  
✅ **Student Progress Page** - Consolidated view of all progress and quiz results  
✅ **Manual Enrollment System** - Create users and enroll them in courses with 1-year access duration  
✅ **User Account Dashboard** - Students can view enrollment details, access dates, and progress  
✅ **Easy Content Management** - Simple WordPress post types for courses, lessons, lesson pages, and quizzes  
✅ **Shortcode Support** - Display courses and progress anywhere on your site  
✅ **LearnDash Import Tool** - Easily migrate from LearnDash with built-in XML import functionality  

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Navigate to "IELTS Courses" to start creating content

## Quick Start

See [PLUGIN_README.md](PLUGIN_README.md) for detailed documentation on:
- Creating courses, lessons, and quizzes
- Adding lesson pages
- Using shortcodes
- Managing student progress
- Quiz question type guidelines

### Migrating from LearnDash?

**Option 1: Direct Conversion (Same Site)** - See [LEARNDASH_CONVERSION_GUIDE.md](LEARNDASH_CONVERSION_GUIDE.md)
- Convert LearnDash courses directly with one click
- No XML export/import needed
- Real-time progress monitoring with modal display
- Automatically detects already-converted courses
- Perfect when LearnDash and IELTS Course Manager are on the same site

**Option 2: XML Import (Different Sites)** - See [LEARNDASH_IMPORT_GUIDE.md](LEARNDASH_IMPORT_GUIDE.md)
- Export from LearnDash, import to IELTS Course Manager
- **NEW in v1.15:** XML conversion script included
- Converts 4,500+ questions in under 2 minutes
- See [XML_CONVERSION_README.md](XML_CONVERSION_README.md) for conversion details
- Perfect for migrating between different servers or sites

**NEW in v1.16: Enhanced XML Exercises Creator** - See [XML_EXERCISES_CREATOR_GUIDE.md](XML_EXERCISES_CREATOR_GUIDE.md)
- Automatically create exercise pages from converted XML
- **Groups related questions** into multi-question exercises
- **WYSIWYG editor** preserves HTML, images, and formatting
- **Placeholder values** pre-filled for options and correct answers
- Navigate to "IELTS Courses > Create Exercises from XML"
- Process all 4,547 exercises or test with a smaller batch
- Auto-detects True/False questions and maps question types
- Creates exercises as drafts for review and completion
- Manual step: Add answer options and correct answers to each exercise

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+

## License

GPL v2 or later

## Documentation

For detailed documentation, see [PLUGIN_README.md](PLUGIN_README.md)
