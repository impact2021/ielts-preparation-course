# External Membership Integration - Quick Reference

This is a condensed reference for integrating external membership systems with the IELTS Course Manager. For complete details, see [EXTERNAL_MEMBERSHIP_INTEGRATION_GUIDE.md](EXTERNAL_MEMBERSHIP_INTEGRATION_GUIDE.md).

---

## Content Hierarchy

```
Course (ielts_course)
  └── Lesson (ielts_lesson)
       ├── Sublesson (ielts_resource)
       └── Exercise (ielts_quiz)
```

---

## Post Types & Slugs

| Content Type | Post Type | URL Slug |
|--------------|-----------|----------|
| Course | `ielts_course` | `ielts-course` |
| Lesson | `ielts_lesson` | `ielts-lesson` |
| Sublesson | `ielts_resource` | `ielts-lesson-page` |
| Exercise | `ielts_quiz` | `ielts-quiz` |

---

## Metadata Keys for Relationships

### Lessons (belongs to courses)
```
_ielts_cm_course_id (single course)
_ielts_cm_course_ids (multiple courses - serialized array)
```

### Sublessons & Exercises (belongs to lessons)
```
_ielts_cm_lesson_id (single lesson)
_ielts_cm_lesson_ids (multiple lessons - serialized array)
_ielts_cm_course_ids (parent courses - serialized array)
```

---

## Membership Levels

| Level Key | Display Name | Default Duration |
|-----------|--------------|------------------|
| `academic_trial` | Academic Module - Free Trial | 6 hours |
| `general_trial` | General Training - Free Trial | 6 hours |
| `academic_full` | IELTS Core (Academic Module) | 30 days |
| `general_full` | IELTS Core (General Training Module) | 30 days |
| `academic_plus` | IELTS Plus (Academic Module) | 90 days |
| `general_plus` | IELTS Plus (General Training Module) | 90 days |
| `english_trial` | English Only - Free Trial | 6 hours |
| `english_full` | English Only Full Membership | 30 days |

---

## User Meta Keys

### Membership Data
```php
_ielts_cm_membership_type     // Level key (e.g., 'academic_full')
_ielts_cm_membership_expiry   // Date: YYYY-MM-DD
_ielts_cm_membership_status   // 'active', 'expired', or 'none'
```

### Progress Tracking
```php
_ielts_cm_lesson_{id}_viewed     // Timestamp
_ielts_cm_resource_{id}_viewed   // Timestamp
_ielts_cm_lesson_{id}_completed  // Boolean
```

---

## Quick Integration Steps

### 1. Disable Internal Membership (if using external system)
```php
update_option('ielts_cm_membership_enabled', 0);
```

### 2. Set User Membership from External System
```php
update_user_meta($user_id, '_ielts_cm_membership_type', 'academic_full');
update_user_meta($user_id, '_ielts_cm_membership_expiry', '2026-12-31');
update_user_meta($user_id, '_ielts_cm_membership_status', 'active');

// Also set WordPress role
$user = new WP_User($user_id);
$user->set_role('academic_full');
```

### 3. Map Courses to Membership Levels
```php
$course_mapping = array(
    123 => array('academic_trial', 'academic_full', 'academic_plus'),
    124 => array('general_trial', 'general_full', 'general_plus'),
);
update_option('ielts_cm_membership_course_mapping', $course_mapping);
```

### 4. Check User Access
```php
$user_id = get_current_user_id();
$membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
$status = get_user_meta($user_id, '_ielts_cm_membership_status', true);

if ($status === 'active') {
    // User has active membership
    // Check course mapping to allow/deny access
}
```

---

## Retrieving Related Content

### Get Lessons for a Course
```php
global $wpdb;
$course_id = 123;
$int_pattern = '%i:' . $course_id . ';%';
$str_pattern = '%' . serialize(strval($course_id)) . '%';

$lesson_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
    WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
       OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
", $course_id, $int_pattern, $str_pattern));
```

### Get Sublessons/Exercises for a Lesson
```php
global $wpdb;
$lesson_id = 456;
$int_pattern = '%i:' . $lesson_id . ';%';
$str_pattern = '%' . serialize(strval($lesson_id)) . '%';

// For resources (sublessons)
$resource_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
    WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
       OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
", $lesson_id, $int_pattern, $str_pattern));

// For quizzes (exercises) - same query with ielts_quiz post type
```

---

## Access Control Logic

```
1. Course Access
   └─> Check if user's membership type is in allowed levels for course

2. Lesson Access
   └─> Check if user has access to ANY parent course

3. Sublesson Access
   └─> Check if user has access to parent lesson

4. Exercise Access
   └─> Check if user has access to parent lesson
```

---

## WordPress Hooks

### Filter: Check Access
```php
apply_filters('ielts_cm_user_has_access', $has_access, $user_id, $post_id, $post_type);
```

### Action: Content Viewed
```php
do_action('ielts_cm_content_viewed', $user_id, $post_id, $post_type);
```

### Action: Quiz Submitted
```php
do_action('ielts_cm_quiz_submitted', $user_id, $quiz_id, $score, $percentage);
```

### Action: Membership Updated
```php
do_action('ielts_cm_membership_updated', $user_id, $membership_type, $expiry_date);
```

---

## Database Tables

### Quiz Submissions
```
Table: {prefix}ielts_cm_quiz_submissions
Columns: id, user_id, quiz_id, score, max_score, percentage, band_score, answers, submitted_at
```

---

## Important Notes

1. **Serialized Arrays**: Many relationships use PHP serialized arrays. Use LIKE patterns for queries.
2. **Multiple Parents**: Content can belong to multiple courses/lessons.
3. **Menu Order**: Always order content by `menu_order ASC`.
4. **WordPress Roles**: Each membership level has a corresponding WordPress role.
5. **Progress Tracking**: User progress is tracked via user meta keys.

---

## Example: Restrict Content

```php
add_filter('the_content', 'restrict_ielts_content');

function restrict_ielts_content($content) {
    $post_type = get_post_type();
    if (!in_array($post_type, array('ielts_course', 'ielts_lesson', 'ielts_resource', 'ielts_quiz'))) {
        return $content;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        return '<div>Please log in.</div>';
    }
    
    $membership_status = get_user_meta($user_id, '_ielts_cm_membership_status', true);
    if ($membership_status !== 'active') {
        return '<div>Your membership has expired.</div>';
    }
    
    // Add your access control logic here
    
    return $content;
}
```

---

**For complete details and examples, see [EXTERNAL_MEMBERSHIP_INTEGRATION_GUIDE.md](EXTERNAL_MEMBERSHIP_INTEGRATION_GUIDE.md)**

**Document Version:** 1.0  
**Last Updated:** 2026-01-31  
**Plugin Version:** 15.3+
