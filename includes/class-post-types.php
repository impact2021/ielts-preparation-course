<?php
/**
 * Register custom post types
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Post_Types {
    
    public function register_post_types() {
        $this->register_course();
        $this->register_lesson();
        $this->register_resource();
        $this->register_quiz();
    }
    
    /**
     * Register Course post type
     */
    private function register_course() {
        $labels = array(
            'name' => __('Courses', 'ielts-course-manager'),
            'singular_name' => __('Course', 'ielts-course-manager'),
            'menu_name' => __('IELTS Courses', 'ielts-course-manager'),
            'add_new' => __('Add New Course', 'ielts-course-manager'),
            'add_new_item' => __('Add New Course', 'ielts-course-manager'),
            'edit_item' => __('Edit Course', 'ielts-course-manager'),
            'new_item' => __('New Course', 'ielts-course-manager'),
            'view_item' => __('View Course', 'ielts-course-manager'),
            'search_items' => __('Search Courses', 'ielts-course-manager'),
            'not_found' => __('No courses found', 'ielts-course-manager'),
            'not_found_in_trash' => __('No courses found in trash', 'ielts-course-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'rewrite' => array('slug' => 'ielts-course'),
            'capability_type' => 'post',
        );
        
        register_post_type('ielts_course', $args);
        
        // Register taxonomy for course categories
        register_taxonomy('ielts_course_category', 'ielts_course', array(
            'labels' => array(
                'name' => __('Course Categories', 'ielts-course-manager'),
                'singular_name' => __('Course Category', 'ielts-course-manager'),
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'course-category'),
        ));
    }
    
    /**
     * Register Lesson post type
     */
    private function register_lesson() {
        $labels = array(
            'name' => __('Lessons', 'ielts-course-manager'),
            'singular_name' => __('Lesson', 'ielts-course-manager'),
            'menu_name' => __('Lessons', 'ielts-course-manager'),
            'add_new' => __('Add New Lesson', 'ielts-course-manager'),
            'add_new_item' => __('Add New Lesson', 'ielts-course-manager'),
            'edit_item' => __('Edit Lesson', 'ielts-course-manager'),
            'new_item' => __('New Lesson', 'ielts-course-manager'),
            'view_item' => __('View Lesson', 'ielts-course-manager'),
            'search_items' => __('Search Lessons', 'ielts-course-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'show_in_menu' => 'edit.php?post_type=ielts_course',
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-book',
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'rewrite' => array('slug' => 'ielts-lesson'),
            'capability_type' => 'post',
        );
        
        register_post_type('ielts_lesson', $args);
    }
    
    /**
     * Register Lesson page post type
     */
    private function register_resource() {
        $labels = array(
            'name' => __('Lesson pages', 'ielts-course-manager'),
            'singular_name' => __('Lesson page', 'ielts-course-manager'),
            'menu_name' => __('Lesson pages', 'ielts-course-manager'),
            'add_new' => __('Add New Lesson page', 'ielts-course-manager'),
            'add_new_item' => __('Add New Lesson page', 'ielts-course-manager'),
            'edit_item' => __('Edit Lesson page', 'ielts-course-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'show_in_menu' => 'edit.php?post_type=ielts_course',
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-media-document',
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'ielts-lesson-page'),
            'capability_type' => 'post',
        );
        
        register_post_type('ielts_resource', $args);
    }
    
    /**
     * Register Quiz post type
     */
    private function register_quiz() {
        $labels = array(
            'name' => __('Quizzes', 'ielts-course-manager'),
            'singular_name' => __('Quiz', 'ielts-course-manager'),
            'menu_name' => __('Quizzes', 'ielts-course-manager'),
            'add_new' => __('Add New Quiz', 'ielts-course-manager'),
            'add_new_item' => __('Add New Quiz', 'ielts-course-manager'),
            'edit_item' => __('Edit Quiz', 'ielts-course-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'show_in_menu' => 'edit.php?post_type=ielts_course',
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-edit',
            'supports' => array('title', 'editor'),
            'rewrite' => array('slug' => 'ielts-quiz'),
            'capability_type' => 'post',
        );
        
        register_post_type('ielts_quiz', $args);
    }
}
