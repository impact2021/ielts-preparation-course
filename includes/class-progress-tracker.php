<?php
/**
 * Progress tracking functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Progress_Tracker {
    
    private $db;
    
    public function __construct() {
        $this->db = new IELTS_CM_Database();
        
        // AJAX handlers
        add_action('wp_ajax_ielts_cm_mark_complete', array($this, 'mark_complete'));
        add_action('wp_ajax_ielts_cm_get_progress', array($this, 'get_progress_ajax'));
    }
    
    /**
     * Mark a lesson/resource as complete
     */
    public function mark_complete() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $course_id = intval($_POST['course_id']);
        $lesson_id = intval($_POST['lesson_id']);
        $resource_id = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
        
        $result = $this->record_progress($user_id, $course_id, $lesson_id, $resource_id, true);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Progress saved'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save progress'));
        }
    }
    
    /**
     * Record progress in database
     */
    public function record_progress($user_id, $course_id, $lesson_id, $resource_id = null, $completed = false) {
        global $wpdb;
        $table = $this->db->get_progress_table();
        
        $data = array(
            'user_id' => $user_id,
            'course_id' => $course_id,
            'lesson_id' => $lesson_id,
            'resource_id' => $resource_id,
            'completed' => $completed ? 1 : 0,
            'last_accessed' => current_time('mysql')
        );
        
        if ($completed) {
            $data['completed_date'] = current_time('mysql');
        }
        
        // Check if record exists
        if ($resource_id) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND lesson_id = %d AND resource_id = %d",
                $user_id, $lesson_id, $resource_id
            ));
        } else {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND lesson_id = %d AND resource_id IS NULL",
                $user_id, $lesson_id
            ));
        }
        
        if ($existing) {
            return $wpdb->update($table, $data, array('id' => $existing->id));
        } else {
            return $wpdb->insert($table, $data);
        }
    }
    
    /**
     * Get progress for a course
     */
    public function get_course_progress($user_id, $course_id) {
        global $wpdb;
        $table = $this->db->get_progress_table();
        
        $progress = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND course_id = %d ORDER BY last_accessed DESC",
            $user_id, $course_id
        ));
        
        return $progress;
    }
    
    /**
     * Get progress for all courses
     */
    public function get_all_progress($user_id) {
        global $wpdb;
        $table = $this->db->get_progress_table();
        
        $progress = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY last_accessed DESC",
            $user_id
        ));
        
        return $progress;
    }
    
    /**
     * Calculate course completion percentage
     */
    public function get_course_completion_percentage($user_id, $course_id) {
        global $wpdb;
        
        // Get all lessons in the course
        $lessons = get_posts(array(
            'post_type' => 'ielts_lesson',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_ielts_cm_course_id',
                    'value' => $course_id
                )
            )
        ));
        
        $total_lessons = count($lessons);
        if ($total_lessons == 0) {
            return 0;
        }
        
        // Get completed lessons
        $table = $this->db->get_progress_table();
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT lesson_id) FROM $table WHERE user_id = %d AND course_id = %d AND completed = 1",
            $user_id, $course_id
        ));
        
        return ($completed / $total_lessons) * 100;
    }
    
    /**
     * AJAX handler for getting progress
     */
    public function get_progress_ajax() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $course_id = intval($_POST['course_id']);
        $progress = $this->get_course_progress($user_id, $course_id);
        
        wp_send_json_success(array('progress' => $progress));
    }
    
    /**
     * Check if lesson is completed
     */
    public function is_lesson_completed($user_id, $lesson_id) {
        global $wpdb;
        $table = $this->db->get_progress_table();
        
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT completed FROM $table WHERE user_id = %d AND lesson_id = %d AND completed = 1",
            $user_id, $lesson_id
        ));
        
        return (bool) $completed;
    }
}
