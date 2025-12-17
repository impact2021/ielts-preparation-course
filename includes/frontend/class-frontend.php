<?php
/**
 * Frontend functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Frontend {
    
    public function init() {
        // Add custom templates
        add_filter('template_include', array($this, 'load_custom_templates'));
        
        // Add body classes
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Record lesson access when viewed (not marking as complete automatically)
        add_action('wp', array($this, 'auto_mark_lesson_on_view'));
        
        // Auto-mark resources (sublessons) as complete when viewed
        add_action('wp', array($this, 'auto_mark_resource_on_view'));
    }
    
    /**
     * Load custom templates
     */
    public function load_custom_templates($template) {
        if (is_singular('ielts_course')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-course-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_singular('ielts_lesson')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-lesson-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_singular('ielts_resource')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-resource-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_singular('ielts_quiz')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-quiz-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('ielts_course')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/archive-courses.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Add body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('ielts_course')) {
            $classes[] = 'ielts-course-single';
        }
        
        if (is_singular('ielts_lesson')) {
            $classes[] = 'ielts-lesson-single';
        }
        
        if (is_singular('ielts_resource')) {
            $classes[] = 'ielts-resource-single';
        }
        
        if (is_singular('ielts_quiz')) {
            $classes[] = 'ielts-quiz-single';
        }
        
        if (is_post_type_archive('ielts_course')) {
            $classes[] = 'ielts-course-archive';
        }
        
        return $classes;
    }
    
    /**
     * Record lesson access when user views it (but don't mark as complete)
     * Lessons are only marked as complete when ALL resources are viewed and ALL quizzes are attempted
     * This runs on every page load, but only acts on lesson pages
     */
    public function auto_mark_lesson_on_view() {
        // Only process for lesson pages
        if (!is_singular('ielts_lesson')) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        $lesson_id = get_the_ID();
        $course_id = get_post_meta($lesson_id, '_ielts_cm_course_id', true);
        
        if (!$course_id) {
            return;
        }
        
        // Check if user is enrolled
        $enrollment = new IELTS_CM_Enrollment();
        if (!$enrollment->is_enrolled($user_id, $course_id)) {
            return;
        }
        
        // Record lesson access (but don't mark as complete - that happens automatically when all requirements are met)
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, null, false);
    }
    
    /**
     * Auto-mark resource (sublesson) as complete when user views it
     * This runs on every page load, but only acts on resource pages
     */
    public function auto_mark_resource_on_view() {
        // Only process for resource pages
        if (!is_singular('ielts_resource')) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        $resource_id = get_the_ID();
        $lesson_id = get_post_meta($resource_id, '_ielts_cm_lesson_id', true);
        
        if (!$lesson_id) {
            return;
        }
        
        $course_id = get_post_meta($lesson_id, '_ielts_cm_course_id', true);
        
        if (!$course_id) {
            return;
        }
        
        // Check if user is enrolled
        $enrollment = new IELTS_CM_Enrollment();
        if (!$enrollment->is_enrolled($user_id, $course_id)) {
            return;
        }
        
        // Mark the resource as complete
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, true);
    }
}
