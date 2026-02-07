<?php
/**
 * Test script for activation default content creation
 * Simulates what happens during plugin activation
 * 
 * Run via: wp eval-file test-activation-defaults.php
 */

if (!defined('ABSPATH')) {
    echo "Error: This script must be run via WP-CLI\n";
    echo "Usage: wp eval-file test-activation-defaults.php\n";
    exit(1);
}

echo "=== Testing Default Content Creation ===\n\n";

// Check current state BEFORE
echo "1. Checking current state BEFORE activation...\n";

$courses_before = get_posts(array(
    'post_type' => 'ielts_course',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
));

$categories_before = get_terms(array(
    'taxonomy' => 'ielts_course_category',
    'hide_empty' => false
));

echo "   Courses before: " . count($courses_before) . "\n";
echo "   Categories before: " . (is_array($categories_before) ? count($categories_before) : 0) . "\n\n";

// Simulate activation (but only if no courses exist)
if (empty($courses_before)) {
    echo "2. Running activation process (creating default content)...\n";
    
    require_once IELTS_CM_PLUGIN_DIR . 'includes/class-activator.php';
    
    // Use reflection to call private methods for testing
    $reflection = new ReflectionClass('IELTS_CM_Activator');
    
    // Create categories
    $create_categories = $reflection->getMethod('create_default_categories');
    $create_categories->setAccessible(true);
    $categories = $create_categories->invoke(null);
    
    echo "   Created categories: " . count($categories) . "\n";
    foreach ($categories as $slug => $term_id) {
        $term = get_term($term_id, 'ielts_course_category');
        echo "     - {$term->name} (slug: {$slug})\n";
    }
    
    // Create courses
    $create_courses = $reflection->getMethod('create_default_courses');
    $create_courses->setAccessible(true);
    $create_courses->invoke(null, $categories);
    
    echo "\n";
} else {
    echo "2. Skipping activation - courses already exist\n\n";
}

// Check state AFTER
echo "3. Checking state AFTER activation...\n";

$courses_after = get_posts(array(
    'post_type' => 'ielts_course',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
));

$categories_after = get_terms(array(
    'taxonomy' => 'ielts_course_category',
    'hide_empty' => false
));

echo "   Courses after: " . count($courses_after) . "\n";
echo "   Categories after: " . (is_array($categories_after) ? count($categories_after) : 0) . "\n\n";

// Show detailed course information
if (!empty($courses_after)) {
    echo "4. Detailed course information:\n";
    foreach ($courses_after as $course_id) {
        $title = get_the_title($course_id);
        $status = get_post_status($course_id);
        $terms = wp_get_post_terms($course_id, 'ielts_course_category', array('fields' => 'names'));
        $cats = is_array($terms) && !is_wp_error($terms) ? implode(', ', $terms) : 'None';
        
        echo "   - ID: {$course_id}\n";
        echo "     Title: {$title}\n";
        echo "     Status: {$status}\n";
        echo "     Categories: {$cats}\n\n";
    }
}

// Test bulk enrollment query
echo "5. Testing bulk enrollment query...\n";

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

echo "   Academic courses found: " . count($academic_courses) . "\n";

if (!empty($academic_courses)) {
    echo "   ✓ SUCCESS: Bulk enrollment will work!\n";
} else {
    echo "   ✗ WARNING: No academic courses found\n";
}

echo "\n=== Test Complete ===\n";
