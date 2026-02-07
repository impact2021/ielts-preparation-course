<?php
/**
 * Test script for bulk enrollment functionality
 * Run this via: wp eval-file test-bulk-enrollment.php
 */

// Check if we're in WordPress context
if (!defined('ABSPATH')) {
    echo "Error: This script must be run via WP-CLI\n";
    echo "Usage: wp eval-file test-bulk-enrollment.php\n";
    exit(1);
}

echo "=== Bulk Enrollment Test ===\n\n";

// 1. Check if post type is registered
echo "1. Checking if 'ielts_course' post type is registered...\n";
if (post_type_exists('ielts_course')) {
    echo "   ✓ Post type 'ielts_course' is registered\n\n";
} else {
    echo "   ✗ FAIL: Post type 'ielts_course' is NOT registered\n";
    echo "   This is a critical error - courses cannot exist without this.\n\n";
}

// 2. Check if taxonomy is registered
echo "2. Checking if 'ielts_course_category' taxonomy is registered...\n";
if (taxonomy_exists('ielts_course_category')) {
    echo "   ✓ Taxonomy 'ielts_course_category' is registered\n\n";
} else {
    echo "   ✗ FAIL: Taxonomy 'ielts_course_category' is NOT registered\n\n";
}

// 3. Count all courses (any status)
echo "3. Counting all courses (any status)...\n";
$all_courses = get_posts(array(
    'post_type' => 'ielts_course',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
));
echo "   Found " . count($all_courses) . " course(s) total\n\n";

// 4. Count published courses
echo "4. Counting published courses...\n";
$published_courses = get_posts(array(
    'post_type' => 'ielts_course',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
));
echo "   Found " . count($published_courses) . " published course(s)\n\n";

// 5. Count academic courses
echo "5. Counting academic courses...\n";
$academic_courses = get_posts(array(
    'post_type' => 'ielts_course',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids',
    'tax_query' => array(
        array(
            'taxonomy' => 'ielts_course_category',
            'field' => 'slug',
            'terms' => array('academic', 'academic-practice-tests'),
            'operator' => 'IN'
        )
    )
));
echo "   Found " . count($academic_courses) . " academic course(s)\n\n";

// 6. List all course categories
echo "6. Listing all course categories...\n";
$categories = get_terms(array(
    'taxonomy' => 'ielts_course_category',
    'hide_empty' => false
));

if (empty($categories) || is_wp_error($categories)) {
    echo "   No categories found\n\n";
} else {
    foreach ($categories as $cat) {
        echo "   - {$cat->name} (slug: {$cat->slug}, count: {$cat->count})\n";
    }
    echo "\n";
}

// 7. List published courses with their categories
if (!empty($published_courses)) {
    echo "7. Listing published courses with categories...\n";
    foreach ($published_courses as $course_id) {
        $title = get_the_title($course_id);
        $status = get_post_status($course_id);
        $terms = wp_get_post_terms($course_id, 'ielts_course_category', array('fields' => 'names'));
        $cats = is_array($terms) && !is_wp_error($terms) ? implode(', ', $terms) : 'None';
        
        echo "   - ID: {$course_id}, Title: {$title}, Status: {$status}, Categories: {$cats}\n";
    }
    echo "\n";
} else {
    echo "7. No published courses to list\n\n";
}

// 8. Check IELTS_CM_Enrollment class
echo "8. Checking if IELTS_CM_Enrollment class exists...\n";
if (class_exists('IELTS_CM_Enrollment')) {
    echo "   ✓ IELTS_CM_Enrollment class exists\n\n";
} else {
    echo "   ✗ FAIL: IELTS_CM_Enrollment class does NOT exist\n\n";
}

// 9. Check IELTS_CM_Bulk_Enrollment class
echo "9. Checking if IELTS_CM_Bulk_Enrollment class exists...\n";
if (class_exists('IELTS_CM_Bulk_Enrollment')) {
    echo "   ✓ IELTS_CM_Bulk_Enrollment class exists\n\n";
} else {
    echo "   ✗ FAIL: IELTS_CM_Bulk_Enrollment class does NOT exist\n\n";
}

// 10. Summary and recommendations
echo "=== SUMMARY ===\n\n";

if (empty($published_courses)) {
    echo "⚠️  CRITICAL ISSUE: No published courses found!\n";
    echo "   This is why bulk enrollment returns 'no_courses_at_all'\n\n";
    echo "   SOLUTION:\n";
    echo "   1. Create at least one IELTS course\n";
    echo "   2. Set its status to 'Published'\n";
    echo "   3. Add 'academic' or 'academic-practice-tests' category to it\n\n";
} else if (empty($academic_courses)) {
    echo "⚠️  WARNING: No academic courses found!\n";
    echo "   Bulk enrollment will work but will use a non-academic course\n";
    echo "   Users will still get Academic Module membership\n\n";
    echo "   RECOMMENDATION:\n";
    echo "   Add 'academic' or 'academic-practice-tests' category to at least one course\n\n";
} else {
    echo "✓ System appears to be configured correctly\n";
    echo "  - Academic courses found: " . count($academic_courses) . "\n";
    echo "  - Bulk enrollment should work\n\n";
}

echo "Test complete.\n";
