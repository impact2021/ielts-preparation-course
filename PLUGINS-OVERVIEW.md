# WordPress Plugins in This Repository

This repository now contains **two WordPress plugins**:

## 1. IELTS Course Manager (Original Plugin)

**File:** `ielts-course-manager.php`

**Version:** 13.6

**Description:** A flexible Learning Management System for IELTS preparation courses with lessons, resources, quizzes, and progress tracking.

**Key Features:**
- Course management and organization
- Lesson and resource management
- Quiz creation and management
- Progress tracking for students
- Enrollment system
- Gamification and awards
- Multi-site synchronization
- Frontend and admin interfaces

**Directory Structure:**
- Main file: `ielts-course-manager.php`
- Includes: `includes/` directory
- Templates: `templates/` directory
- Uninstall: `uninstall.php`
- Assets: Shared `assets/` directory

---

## 2. IELTS Analytics (New Plugin)

**File:** `ielts-analytics.php`

**Version:** 1.0.0

**Description:** Analytics and reporting plugin for IELTS Course Manager. Provides detailed insights into student progress, quiz performance, and course completion rates.

**Key Features:**
- Student progress tracking and analytics
- Quiz performance analysis
- Course completion reports
- Event logging system
- Custom analytics dashboard
- Detailed reporting capabilities

**Directory Structure:**
- Main file: `ielts-analytics.php`
- Includes: `includes-analytics/` directory
- Uninstall: `uninstall-analytics.php`
- CSS: `assets/css/analytics-admin.css`
- Documentation: `ANALYTICS-README.md`

---

## Plugin Relationship

The two plugins are designed to work together:

1. **IELTS Course Manager** provides the core LMS functionality
2. **IELTS Analytics** extends it with analytics and reporting features

Both plugins can be:
- Installed independently
- Activated/deactivated separately
- Maintained with separate version numbers
- Configured with individual settings

## Installation

Both plugins follow WordPress plugin standards:

1. Upload plugin files to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Configure settings as needed

## Database Tables

**IELTS Course Manager:**
- Multiple tables for courses, lessons, progress, etc.

**IELTS Analytics:**
- `wp_ielts_analytics_events` - Event tracking table

## Documentation

- **Course Manager:** See main `README.md`
- **Analytics:** See `ANALYTICS-README.md`
