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
        
        // Get all lessons in the course (check both old and new meta keys)
        // Join with wp_posts to ensure we only get lessons
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_lesson'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_course_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_course_ids' AND pm.meta_value LIKE %s))
        ", $course_id, '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%'));
        
        $total_lessons = count($lesson_ids);
        
        // Get all quizzes in the course (check both old and new meta keys)
        // Join with wp_posts to ensure we only get quizzes
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_course_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_course_ids' AND pm.meta_value LIKE %s))
        ", $course_id, '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%'));
        
        $total_quizzes = count($quiz_ids);
        
        // Total items needed for 100% completion
        $total_items = $total_lessons + $total_quizzes;
        
        if ($total_items == 0) {
            return 0;
        }
        
        // Get completed lessons
        $table = $this->db->get_progress_table();
        $completed_lessons = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT lesson_id) FROM $table WHERE user_id = %d AND course_id = %d AND completed = 1",
            $user_id, $course_id
        ));
        
        // Get completed quizzes (any quiz taken counts, regardless of score)
        $quiz_results_table = $this->db->get_quiz_results_table();
        $completed_quizzes = 0;
        if (!empty($quiz_ids)) {
            // Ensure all quiz_ids are integers for safety
            $quiz_ids = array_map('intval', $quiz_ids);
            // Validate count to prevent potential issues
            $quiz_count = count($quiz_ids);
            if ($quiz_count > 0 && $quiz_count <= 1000) {
                $quiz_ids_placeholders = implode(',', array_fill(0, $quiz_count, '%d'));
                $query = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT quiz_id) FROM $quiz_results_table WHERE user_id = %d AND quiz_id IN ($quiz_ids_placeholders)",
                    array_merge(array($user_id), $quiz_ids)
                );
                $completed_quizzes = $wpdb->get_var($query);
            }
        }
        
        $completed_items = $completed_lessons + $completed_quizzes;
        
        return ($completed_items / $total_items) * 100;
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
    
    /**
     * Check if resource is completed
     */
    public function is_resource_completed($user_id, $lesson_id, $resource_id) {
        global $wpdb;
        $table = $this->db->get_progress_table();
        
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT completed FROM $table WHERE user_id = %d AND lesson_id = %d AND resource_id = %d AND completed = 1",
            $user_id, $lesson_id, $resource_id
        ));
        
        return (bool) $completed;
    }
    
    /**
     * Check if a course is 100% complete
     * Requires all lessons completed AND all quizzes taken (regardless of score)
     */
    public function is_course_complete($user_id, $course_id) {
        $percentage = $this->get_course_completion_percentage($user_id, $course_id);
        return $percentage >= 100;
    }
}
