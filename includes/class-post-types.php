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
        $this->register_menu_item();
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
     * Register Sub lesson post type
     */
    private function register_resource() {
        $labels = array(
            'name' => __('Sub lessons', 'ielts-course-manager'),
            'singular_name' => __('Sub lesson', 'ielts-course-manager'),
            'menu_name' => __('Sub lessons', 'ielts-course-manager'),
            'add_new' => __('Add New Sub lesson', 'ielts-course-manager'),
            'add_new_item' => __('Add New Sub lesson', 'ielts-course-manager'),
            'edit_item' => __('Edit Sub lesson', 'ielts-course-manager'),
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
            'name' => __('Exercises', 'ielts-course-manager'),
            'singular_name' => __('Exercise', 'ielts-course-manager'),
            'menu_name' => __('Exercises', 'ielts-course-manager'),
            'add_new' => __('Add New Exercise', 'ielts-course-manager'),
            'add_new_item' => __('Add New Exercise', 'ielts-course-manager'),
            'edit_item' => __('Edit Exercise', 'ielts-course-manager'),
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
    
    /**
     * Register Menu Item post type for Huapai Menu
     */
    private function register_menu_item() {
        $labels = array(
            'name' => __('Menu Items', 'ielts-course-manager'),
            'singular_name' => __('Menu Item', 'ielts-course-manager'),
            'menu_name' => __('Huapai Menu', 'ielts-course-manager'),
            'add_new' => __('Add New Menu Item', 'ielts-course-manager'),
            'add_new_item' => __('Add New Menu Item', 'ielts-course-manager'),
            'edit_item' => __('Edit Menu Item', 'ielts-course-manager'),
            'new_item' => __('New Menu Item', 'ielts-course-manager'),
            'view_item' => __('View Menu Item', 'ielts-course-manager'),
            'search_items' => __('Search Menu Items', 'ielts-course-manager'),
            'not_found' => __('No menu items found', 'ielts-course-manager'),
            'not_found_in_trash' => __('No menu items found in trash', 'ielts-course-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-food',
            'menu_position' => 24,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'menu-item'),
            'capability_type' => 'post',
        );
        
        register_post_type('huapai_menu_item', $args);
        
        // Register taxonomy for menu groups
        register_taxonomy('huapai_menu_group', 'huapai_menu_item', array(
            'labels' => array(
                'name' => __('Menu Groups', 'ielts-course-manager'),
                'singular_name' => __('Menu Group', 'ielts-course-manager'),
                'add_new_item' => __('Add New Menu Group', 'ielts-course-manager'),
                'edit_item' => __('Edit Menu Group', 'ielts-course-manager'),
                'update_item' => __('Update Menu Group', 'ielts-course-manager'),
                'search_items' => __('Search Menu Groups', 'ielts-course-manager'),
                'menu_name' => __('Menu Groups', 'ielts-course-manager'),
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'menu-group'),
        ));
    }
}
