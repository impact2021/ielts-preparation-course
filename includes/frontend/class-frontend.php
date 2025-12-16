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
        
        if (is_singular('ielts_quiz')) {
            $classes[] = 'ielts-quiz-single';
        }
        
        if (is_post_type_archive('ielts_course')) {
            $classes[] = 'ielts-course-archive';
        }
        
        return $classes;
    }
}
