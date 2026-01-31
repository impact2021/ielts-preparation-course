# IELTS Preparation Course - External Membership System Integration Guide

## Document Purpose
This document provides comprehensive information about the IELTS Preparation Course structure to enable integration with external membership systems (e.g., LearnDash membership plugins). Use this guide to configure your membership system to regulate access to courses, lessons, sublessons, and exercises.

---

## Table of Contents
1. [Content Hierarchy Overview](#content-hierarchy-overview)
2. [WordPress Post Types](#wordpress-post-types)
3. [Content Relationships & Metadata](#content-relationships--metadata)
4. [Membership Levels](#membership-levels)
5. [Access Control Implementation](#access-control-implementation)
6. [Database Structure](#database-structure)
7. [API Endpoints & Hooks](#api-endpoints--hooks)
8. [Integration Examples](#integration-examples)

---

## Content Hierarchy Overview

The IELTS Course Manager uses a 4-level hierarchical structure:

```
Course (ielts_course)
  └── Lesson (ielts_lesson)
       ├── Sublesson/Resource (ielts_resource)
       │    └── May contain embedded video content
       └── Exercise/Quiz (ielts_quiz)
            └── Questions and assessments
```

### Hierarchy Details

**Level 1: Course** (`ielts_course`)
- Top-level container for educational content
- Contains multiple lessons
- Examples: "Academic Reading", "General Training Writing", "Listening Skills"

**Level 2: Lesson** (`ielts_lesson`)
- Belongs to one or more courses
- Contains sublessons and exercises
- Examples: "Lesson 1: Introduction to IELTS", "Lesson 5: Advanced Strategies"

**Level 3: Sublesson** (`ielts_resource`)
- Belongs to one or more lessons
- Can contain text content, PDFs, or video lessons
- Examples: "Video: Reading Techniques", "Study Material: Vocabulary List"

**Level 4: Exercise** (`ielts_quiz`)
- Belongs to one or more lessons
- Contains questions and assessments
- Examples: "Practice Test 1", "Reading Exercise 3", "End of Lesson Test"

---

## WordPress Post Types

### 1. Course (`ielts_course`)

**Labels:**
- Singular: Course
- Plural: Courses
- Menu Name: IELTS Courses

**Capabilities:**
- Public: Yes
- Has Archive: Yes
- Supports: title, editor, thumbnail, excerpt
- Rewrite Slug: `ielts-course`

**Taxonomy:**
- `ielts_course_category` - Hierarchical taxonomy for categorizing courses

**Key Features:**
- Progress tracking
- Enrollment management
- Completion percentage calculation

---

### 2. Lesson (`ielts_lesson`)

**Labels:**
- Singular: Lesson
- Plural: Lessons

**Capabilities:**
- Public: Yes
- Has Archive: No
- Supports: title, editor, thumbnail, page-attributes
- Rewrite Slug: `ielts-lesson`
- Show in Menu: Under IELTS Courses

**Key Features:**
- Belongs to one or more courses
- Contains sublessons and exercises
- Progress tracking per lesson
- Completion status

---

### 3. Sublesson/Resource (`ielts_resource`)

**Labels:**
- Singular: Sub lesson
- Plural: Sub lessons

**Capabilities:**
- Public: Yes
- Has Archive: No
- Supports: title, editor, thumbnail
- Rewrite Slug: `ielts-lesson-page`
- Show in Menu: Under IELTS Courses

**Post Meta Fields:**
- `_ielts_cm_resource_url` - External resource URL (optional)
- `_ielts_cm_video_url` - Embedded video URL (optional)
- `_ielts_cm_lesson_id` - Single parent lesson ID
- `_ielts_cm_lesson_ids` - Serialized array of parent lesson IDs (for multi-lesson resources)
- `_ielts_cm_course_ids` - Serialized array of parent course IDs

**Key Features:**
- Can contain video content
- Viewed status tracking
- External resource linking

---

### 4. Exercise/Quiz (`ielts_quiz`)

**Labels:**
- Singular: Exercise
- Plural: Exercises

**Capabilities:**
- Public: Yes
- Has Archive: No
- Supports: title, editor
- Rewrite Slug: `ielts-quiz`
- Show in Menu: Under IELTS Courses

**Post Meta Fields:**
- `_ielts_cm_questions` - Serialized array of questions and answers
- `_ielts_cm_reading_texts` - Reading passages (for reading exercises)
- `_ielts_cm_lesson_id` - Single parent lesson ID
- `_ielts_cm_lesson_ids` - Serialized array of parent lesson IDs
- `_ielts_cm_course_ids` - Serialized array of parent course IDs
- `_ielts_cm_exercise_label` - Type: 'exercise', 'end_of_lesson_test', or 'practice_test'
- `_ielts_cm_is_practice_test` - Boolean flag for practice tests
- `_ielts_cm_layout_type` - Layout: 'single_column', 'two_column_reading', 'two_column_listening', 'two_column_exercise'
- `_ielts_cm_open_as_popup` - Boolean for fullscreen/popup mode

**Key Features:**
- Question types: Open (text input), Closed (multiple choice), Dropdown
- Band score calculation for practice tests
- Attempt tracking and history
- Best score tracking
- Progress calculation

---

## Content Relationships & Metadata

### Parent-Child Relationships

Content items can belong to multiple parents using two metadata strategies:

#### Single Parent Relationship
```
Meta Key: _ielts_cm_lesson_id (for resources/quizzes)
Meta Value: 123 (integer - lesson post ID)

Meta Key: _ielts_cm_course_id (for lessons)
Meta Value: 456 (integer - course post ID)
```

#### Multiple Parent Relationship
```
Meta Key: _ielts_cm_lesson_ids (for resources/quizzes)
Meta Value: a:3:{i:0;i:123;i:1;i:124;i:2;i:125;} (serialized PHP array)

Meta Key: _ielts_cm_course_ids (for lessons)
Meta Value: a:2:{i:0;i:456;i:1;i:457;} (serialized PHP array)
```

### Retrieving Related Content

#### Get All Lessons for a Course
```php
global $wpdb;
$course_id = 123;

// Pattern matching for serialized arrays
$int_pattern = '%i:' . $course_id . ';%';
$str_pattern = '%' . serialize(strval($course_id)) . '%';

$lesson_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT post_id 
    FROM {$wpdb->postmeta} 
    WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
       OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
", $course_id, $int_pattern, $str_pattern));

$lessons = get_posts(array(
    'post_type' => 'ielts_lesson',
    'post__in' => $lesson_ids,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));
```

#### Get All Resources/Exercises for a Lesson
```php
global $wpdb;
$lesson_id = 456;

$int_pattern = '%i:' . $lesson_id . ';%';
$str_pattern = '%' . serialize(strval($lesson_id)) . '%';

// Get resources (sublessons)
$resource_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT post_id 
    FROM {$wpdb->postmeta} 
    WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
       OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
", $lesson_id, $int_pattern, $str_pattern));

$resources = get_posts(array(
    'post_type' => 'ielts_resource',
    'post__in' => $resource_ids,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));

// Get exercises (quizzes)
$quiz_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT post_id 
    FROM {$wpdb->postmeta} 
    WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
       OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
", $lesson_id, $int_pattern, $str_pattern));

$quizzes = get_posts(array(
    'post_type' => 'ielts_quiz',
    'post__in' => $quiz_ids,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));
```

---

## Membership Levels

The IELTS Course Manager supports the following membership levels:

### Membership Level Definitions

| Level Key | Display Name | Type | Default Duration |
|-----------|--------------|------|------------------|
| `academic_trial` | Academic Module - Free Trial | Trial | 6 hours |
| `general_trial` | General Training - Free Trial | Trial | 6 hours |
| `academic_full` | IELTS Core (Academic Module) | Full | 30 days |
| `general_full` | IELTS Core (General Training Module) | Full | 30 days |
| `academic_plus` | IELTS Plus (Academic Module) | Plus | 90 days |
| `general_plus` | IELTS Plus (General Training Module) | Plus | 90 days |
| `english_trial` | English Only - Free Trial | Trial | 6 hours |
| `english_full` | English Only Full Membership | Full | 30 days |

### WordPress User Meta for Membership

**Membership Type:**
```
Meta Key: _ielts_cm_membership_type
Meta Value: One of the level keys above (e.g., 'academic_full')
```

**Membership Expiry:**
```
Meta Key: _ielts_cm_membership_expiry
Meta Value: YYYY-MM-DD format (e.g., '2026-12-31')
```

**Membership Status:**
```
Meta Key: _ielts_cm_membership_status
Meta Value: 'active', 'expired', or 'none'
```

### WordPress Roles for Membership

Each membership level also has a corresponding WordPress role with the same slug:
- `academic_trial`
- `general_trial`
- `academic_full`
- `general_full`
- `academic_plus`
- `general_plus`
- `english_trial`
- `english_full`

These roles have the same capabilities as the WordPress `subscriber` role.

---

## Access Control Implementation

### Checking User Access to Content

#### Method 1: Using Built-in Membership Class
```php
// Check if membership system is enabled
$membership = new IELTS_CM_Membership();
if (!$membership->is_enabled()) {
    // External membership system should handle access
    return;
}

// Check course access
$user_id = get_current_user_id();
$course_id = 123;
$has_access = $membership->user_has_course_access($user_id, $course_id);
```

#### Method 2: Direct User Meta Check
```php
$user_id = get_current_user_id();
$membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
$membership_status = get_user_meta($user_id, '_ielts_cm_membership_status', true);
$expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);

// Check if membership is active
$is_active = ($membership_status === 'active') && 
             (!$expiry_date || strtotime($expiry_date) >= time());

// Map content to membership
// Academic content requires academic_* membership
// General Training content requires general_* membership
// English Only content requires english_* membership
```

### Course-to-Membership Mapping

**WordPress Option:**
```
Option Name: ielts_cm_membership_course_mapping
Option Value: Serialized array mapping course IDs to allowed membership levels
```

Example:
```php
array(
    123 => array('academic_trial', 'academic_full', 'academic_plus'),
    124 => array('general_trial', 'general_full', 'general_plus'),
    125 => array('english_trial', 'english_full')
)
```

### Inherited Access Rules

When implementing access control:

1. **Course Access** → User must have membership level that allows the course
2. **Lesson Access** → User must have access to at least one parent course
3. **Sublesson Access** → User must have access to the parent lesson
4. **Exercise Access** → User must have access to the parent lesson

---

## Database Structure

### Custom Tables

#### Quiz Submissions Table
```sql
Table Name: {prefix}ielts_cm_quiz_submissions

Columns:
- id (bigint, auto_increment, primary key)
- user_id (bigint) - WordPress user ID
- quiz_id (bigint) - Post ID of the quiz
- score (int) - Raw score (correct answers)
- max_score (int) - Maximum possible score
- percentage (decimal) - Percentage score
- band_score (decimal, nullable) - IELTS band score (if applicable)
- answers (longtext) - Serialized array of user answers
- submitted_at (datetime) - Submission timestamp
```

### User Meta Keys

#### Progress Tracking
```
_ielts_cm_lesson_{lesson_id}_viewed - Timestamp when lesson was viewed
_ielts_cm_resource_{resource_id}_viewed - Timestamp when resource was viewed
_ielts_cm_lesson_{lesson_id}_completed - Boolean completion flag
```

#### Session Tracking
```
_ielts_cm_last_login - Last login timestamp
_ielts_cm_login_count - Total login count
_ielts_cm_session_start - Current session start time
_ielts_cm_total_time_logged_in - Cumulative time logged in (seconds)
```

### WordPress Options

```
ielts_cm_membership_enabled - 1 or 0 (enable/disable internal membership)
ielts_cm_membership_course_mapping - Serialized array of course-to-membership mapping
ielts_cm_membership_pricing - Serialized array of membership prices
ielts_cm_membership_durations - Serialized array of membership durations
ielts_cm_english_only_enabled - 1 or 0 (enable English Only memberships)
ielts_cm_full_member_page_url - URL for upgrade page
ielts_cm_post_payment_redirect_url - Post-registration redirect URL
```

---

## API Endpoints & Hooks

### WordPress Hooks for Integration

#### Access Control Hooks
```php
// Filter: Check if user has access to specific content
apply_filters('ielts_cm_user_has_access', $has_access, $user_id, $post_id, $post_type);

// Action: User viewed content
do_action('ielts_cm_content_viewed', $user_id, $post_id, $post_type);

// Action: User completed exercise
do_action('ielts_cm_quiz_submitted', $user_id, $quiz_id, $score, $percentage);
```

#### Membership Hooks
```php
// Action: Membership created/updated
do_action('ielts_cm_membership_updated', $user_id, $membership_type, $expiry_date);

// Action: Membership expired
do_action('ielts_cm_membership_expired', $user_id, $membership_type);

// Filter: Customize membership duration
apply_filters('ielts_cm_membership_duration', $duration, $membership_type);
```

### REST API Endpoints

The plugin provides REST API endpoints under the namespace `ielts-cm/v1`:

#### Sync API (for multi-site setups)
```
POST /wp-json/ielts-cm/v1/sync/content
POST /wp-json/ielts-cm/v1/sync/batch
GET  /wp-json/ielts-cm/v1/sync/status
```

These endpoints require authentication and are primarily used for content synchronization between WordPress sites.

---

## Integration Examples

### Example 1: LearnDash Integration

Map LearnDash membership levels to IELTS content:

```php
/**
 * Map LearnDash group to IELTS courses
 */
add_action('learndash_update_group_access', 'map_learndash_to_ielts', 10, 3);

function map_learndash_to_ielts($user_id, $course_id, $access_type) {
    // Get IELTS courses mapped to this LearnDash course
    $ielts_courses = get_post_meta($course_id, '_ielts_mapped_courses', true);
    
    if (!empty($ielts_courses)) {
        // Set appropriate IELTS membership
        $membership_type = get_post_meta($course_id, '_ielts_membership_type', true);
        update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
        update_user_meta($user_id, '_ielts_cm_membership_status', 'active');
        
        // Set expiry based on LearnDash settings
        $expiry = get_user_meta($user_id, 'learndash_group_enrolled_' . $course_id, true);
        if ($expiry) {
            update_user_meta($user_id, '_ielts_cm_membership_expiry', date('Y-m-d', $expiry));
        }
    }
}
```

### Example 2: Restrict Access by Membership

```php
/**
 * Restrict IELTS content by membership level
 */
add_filter('the_content', 'restrict_ielts_content');

function restrict_ielts_content($content) {
    // Check if this is IELTS content
    $post_type = get_post_type();
    if (!in_array($post_type, array('ielts_course', 'ielts_lesson', 'ielts_resource', 'ielts_quiz'))) {
        return $content;
    }
    
    // Get user membership
    $user_id = get_current_user_id();
    if (!$user_id) {
        return '<div class="access-denied">Please log in to access this content.</div>';
    }
    
    $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
    $membership_status = get_user_meta($user_id, '_ielts_cm_membership_status', true);
    
    if ($membership_status !== 'active') {
        return '<div class="access-denied">Your membership has expired. Please renew to continue.</div>';
    }
    
    // Check access based on content type and membership
    if ($post_type === 'ielts_course') {
        $course_id = get_the_ID();
        $allowed_levels = get_option('ielts_cm_membership_course_mapping', array());
        
        if (isset($allowed_levels[$course_id])) {
            if (!in_array($membership_type, $allowed_levels[$course_id])) {
                return '<div class="access-denied">This course is not included in your membership.</div>';
            }
        }
    }
    
    // If lesson, resource, or quiz - check parent course access
    if (in_array($post_type, array('ielts_lesson', 'ielts_resource', 'ielts_quiz'))) {
        // Get parent course(s)
        $course_ids = get_post_meta(get_the_ID(), '_ielts_cm_course_ids', true);
        if (!is_array($course_ids)) {
            $course_id = get_post_meta(get_the_ID(), '_ielts_cm_course_id', true);
            $course_ids = $course_id ? array($course_id) : array();
        }
        
        // Check if user has access to any parent course
        $has_access = false;
        $allowed_levels = get_option('ielts_cm_membership_course_mapping', array());
        
        foreach ($course_ids as $course_id) {
            if (isset($allowed_levels[$course_id]) && 
                in_array($membership_type, $allowed_levels[$course_id])) {
                $has_access = true;
                break;
            }
        }
        
        if (!$has_access && !empty($course_ids)) {
            return '<div class="access-denied">This content is not included in your membership.</div>';
        }
    }
    
    return $content;
}
```

### Example 3: Sync Membership from External System

```php
/**
 * Sync membership from external system (webhook handler)
 */
add_action('rest_api_init', function() {
    register_rest_route('external-membership/v1', '/sync-user', array(
        'methods' => 'POST',
        'callback' => 'sync_external_membership',
        'permission_callback' => 'verify_external_webhook'
    ));
});

function sync_external_membership($request) {
    $params = $request->get_json_params();
    
    $user_id = $params['user_id'];
    $membership_type = $params['membership_type']; // Map to IELTS membership levels
    $expiry_date = $params['expiry_date']; // Format: YYYY-MM-DD
    
    // Update IELTS membership meta
    update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
    update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
    
    // Set status based on expiry
    if (strtotime($expiry_date) >= time()) {
        update_user_meta($user_id, '_ielts_cm_membership_status', 'active');
    } else {
        update_user_meta($user_id, '_ielts_cm_membership_status', 'expired');
    }
    
    // Update WordPress role
    $user = new WP_User($user_id);
    $user->set_role($membership_type);
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Membership synced successfully'
    ), 200);
}

function verify_external_webhook($request) {
    $auth_token = $request->get_header('X-Auth-Token');
    $valid_token = get_option('external_membership_webhook_token');
    return $auth_token === $valid_token;
}
```

---

## Best Practices for Integration

### 1. Disable Internal Membership System

If using an external membership system, disable the built-in membership:

```php
update_option('ielts_cm_membership_enabled', 0);
```

This will hide all internal membership UI and features.

### 2. Use WordPress Roles

The IELTS Course Manager creates WordPress roles for each membership level. Leverage these roles for access control:

```php
$user = wp_get_current_user();
if (in_array('academic_full', $user->roles)) {
    // User has Academic Full membership
}
```

### 3. Handle Multiple Course Assignment

Content can belong to multiple courses. Always check all parent relationships:

```php
// Get all parent courses for a lesson
$course_ids = get_post_meta($lesson_id, '_ielts_cm_course_ids', true);
if (!is_array($course_ids)) {
    $single_course = get_post_meta($lesson_id, '_ielts_cm_course_id', true);
    $course_ids = $single_course ? array($single_course) : array();
}
```

### 4. Track User Progress

Utilize the built-in progress tracking:

```php
// Mark resource as viewed
update_user_meta($user_id, "_ielts_cm_resource_{$resource_id}_viewed", current_time('timestamp'));

// Mark lesson as completed
update_user_meta($user_id, "_ielts_cm_lesson_{$lesson_id}_completed", true);
```

### 5. Preserve Quiz Data

Quiz submissions are stored in a custom table. When managing memberships, preserve this data for historical tracking.

---

## Support and Updates

### Plugin Information
- **Plugin Name:** IELTS Course Manager
- **Current Version:** 15.3
- **WordPress Version Required:** 5.8+
- **PHP Version Required:** 7.2+

### Important Notes

1. **Serialized Data:** Many relationships use PHP serialized arrays. When querying, use LIKE patterns to match both integer and string representations.

2. **Menu Order:** Content items use the `menu_order` field for ordering. Always order by `menu_order ASC` when displaying lists.

3. **Progress Calculation:** The plugin calculates completion percentages based on viewed resources and submitted quizzes. Factor this into membership reporting.

4. **Band Scores:** Practice tests can use IELTS band scores (0-9) instead of percentages. Check the `_ielts_cm_is_practice_test` meta.

5. **Multi-Site:** The plugin supports WordPress multi-site and includes sync functionality. Consider this if your membership system spans multiple sites.

---

## Contact and Feedback

For questions about integrating with the IELTS Course Manager, please:

1. Review this documentation thoroughly
2. Check the plugin's source code in the `includes/` directory
3. Test in a staging environment first
4. Document your integration approach for future reference

---

**Document Version:** 1.0  
**Last Updated:** 2026-01-31  
**Compatibility:** IELTS Course Manager v15.3+
