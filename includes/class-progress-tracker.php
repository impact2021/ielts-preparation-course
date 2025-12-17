<?php
/**
 * Progress tracking functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Progress_Tracker {
    
    private $db;
    
    // Maximum number of items allowed in a single query to prevent potential performance issues
    const MAX_QUERY_ITEMS = 1000;
    
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
     * Based on sub lessons (resources) and quizzes only
     */
    public function get_course_completion_percentage($user_id, $course_id) {
        global $wpdb;
        
        // Get all lessons in the course first
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_lesson'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_course_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_course_ids' AND pm.meta_value LIKE %s))
        ", $course_id, '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%'));
        
        // Get all resources (sub lessons) for all lessons in this course
        // Using a single query to avoid N+1 problem
        $resource_ids = array();
        if (!empty($lesson_ids)) {
            $lesson_count = count($lesson_ids);
            if ($lesson_count > 0 && $lesson_count <= self::MAX_QUERY_ITEMS) {
                $lesson_ids = array_map('intval', $lesson_ids);
                $lesson_placeholders = implode(',', array_fill(0, $lesson_count, '%d'));
                $resource_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT pm.post_id 
                    FROM {$wpdb->postmeta} pm
                    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                    WHERE p.post_type = 'ielts_resource'
                      AND p.post_status = 'publish'
                      AND pm.meta_key = '_ielts_cm_lesson_id' 
                      AND pm.meta_value IN ($lesson_placeholders)
                ", $lesson_ids));
            }
        }
        
        $total_resources = count($resource_ids);
        
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
        
        // Total items needed for 100% completion (resources + quizzes)
        $total_items = $total_resources + $total_quizzes;
        
        if ($total_items == 0) {
            return 0;
        }
        
        // Get completed resources (sub lessons)
        $table = $this->db->get_progress_table();
        $completed_resources = 0;
        if (!empty($resource_ids)) {
            $resource_count = count($resource_ids);
            if ($resource_count > 0 && $resource_count <= self::MAX_QUERY_ITEMS) {
                $resource_ids = array_map('intval', $resource_ids);
                $resource_placeholders = implode(',', array_fill(0, $resource_count, '%d'));
                $query = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT resource_id) FROM $table WHERE user_id = %d AND course_id = %d AND resource_id IN ($resource_placeholders) AND completed = 1",
                    array_merge(array($user_id, $course_id), $resource_ids)
                );
                $completed_resources = $wpdb->get_var($query);
            }
        }
        
        // Get completed quizzes (any quiz taken counts, regardless of score)
        $quiz_results_table = $this->db->get_quiz_results_table();
        $completed_quizzes = 0;
        if (!empty($quiz_ids)) {
            // Ensure all quiz_ids are integers for safety
            $quiz_ids = array_map('intval', $quiz_ids);
            // Validate count to prevent potential issues
            $quiz_count = count($quiz_ids);
            if ($quiz_count > 0 && $quiz_count <= self::MAX_QUERY_ITEMS) {
                $quiz_ids_placeholders = implode(',', array_fill(0, $quiz_count, '%d'));
                $query = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT quiz_id) FROM $quiz_results_table WHERE user_id = %d AND quiz_id IN ($quiz_ids_placeholders)",
                    array_merge(array($user_id), $quiz_ids)
                );
                $completed_quizzes = $wpdb->get_var($query);
            }
        }
        
        $completed_items = $completed_resources + $completed_quizzes;
        
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
     * A lesson is only completed when ALL sublesson pages have been viewed 
     * AND all exercises (quizzes) for that lesson have been attempted
     */
    public function is_lesson_completed($user_id, $lesson_id) {
        global $wpdb;
        
        // Get all resources (sublesson pages) for this lesson
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_resource'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND pm.meta_value LIKE %s))
        ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
        
        // Get all quizzes for this lesson
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND pm.meta_value LIKE %s))
        ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
        
        $total_resources = count($resource_ids);
        $total_quizzes = count($quiz_ids);
        
        // If lesson has no resources and no quizzes, it's never "complete"
        if ($total_resources == 0 && $total_quizzes == 0) {
            return false;
        }
        
        // Check completed resources
        $completed_resources = 0;
        if ($total_resources > 0) {
            $progress_table = $this->db->get_progress_table();
            $resource_ids = array_map('intval', $resource_ids);
            $resource_placeholders = implode(',', array_fill(0, count($resource_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT COUNT(DISTINCT resource_id) FROM $progress_table 
                WHERE user_id = %d AND lesson_id = %d AND resource_id IN ($resource_placeholders) AND completed = 1",
                array_merge(array($user_id, $lesson_id), $resource_ids)
            );
            $completed_resources = $wpdb->get_var($query);
        }
        
        // Check attempted quizzes
        $attempted_quizzes = 0;
        if ($total_quizzes > 0) {
            $quiz_results_table = $this->db->get_quiz_results_table();
            $quiz_ids = array_map('intval', $quiz_ids);
            $quiz_placeholders = implode(',', array_fill(0, count($quiz_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT COUNT(DISTINCT quiz_id) FROM $quiz_results_table 
                WHERE user_id = %d AND lesson_id = %d AND quiz_id IN ($quiz_placeholders)",
                array_merge(array($user_id, $lesson_id), $quiz_ids)
            );
            $attempted_quizzes = $wpdb->get_var($query);
        }
        
        // Lesson is complete only if ALL resources are completed AND ALL quizzes are attempted
        return ($completed_resources == $total_resources) && ($attempted_quizzes == $total_quizzes);
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
    
    /**
     * Auto-mark lesson as complete when visited
     * This should be called when a user views a lesson page
     * 
     * @param int $user_id The user ID
     * @param int $lesson_id The lesson ID to mark complete
     * @param int $course_id The course ID the lesson belongs to
     * @return bool True if successfully marked complete or already complete, false if user_id is invalid
     * 
     * Note: This method does not verify enrollment - the caller should check enrollment before calling
     */
    public function auto_mark_lesson_complete($user_id, $lesson_id, $course_id) {
        // Only auto-mark if user is logged in and enrolled
        if (!$user_id) {
            return false;
        }
        
        // Check if already completed
        if ($this->is_lesson_completed($user_id, $lesson_id)) {
            return true;
        }
        
        // Mark as complete automatically
        return $this->record_progress($user_id, $course_id, $lesson_id, null, true);
    }
}
