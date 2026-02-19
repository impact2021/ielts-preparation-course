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
            $result = $wpdb->update($table, $data, array('id' => $existing->id));
        } else {
            $result = $wpdb->insert($table, $data);
        }
        
        // Trigger award hooks if completed
        if ($result && $completed) {
            // resource_id is set when tracking individual page/resource completion
            // resource_id is null when tracking overall lesson completion
            if ($resource_id) {
                // Page/resource completion
                do_action('ielts_cm_page_completed', $user_id, $resource_id);
            } else {
                // Lesson completion (when all resources in lesson are done)
                do_action('ielts_cm_lesson_completed', $user_id, $lesson_id);
            }
        }
        
        return $result;
    }
    
    /**
     * Get the progress table name
     */
    public function get_progress_table() {
        return $this->db->get_progress_table();
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
        // Check for both integer and string serialization in course_ids array
        // Integer: i:123; String: s:3:"123";
        $int_pattern = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
        
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_lesson'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_course_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_course_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $course_id, $int_pattern, $str_pattern));
        
        // Get all resources (sub lessons) for all lessons in this course
        // Using a single query to avoid N+1 problem
        $resource_ids = array();
        if (!empty($lesson_ids)) {
            $lesson_count = count($lesson_ids);
            if ($lesson_count <= self::MAX_QUERY_ITEMS) {
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
        
        // Get all quizzes that belong to lessons in this course (not course-level quizzes that aren't in lessons)
        // This ensures we only count quizzes that are actually part of lessons, matching what users see
        $quiz_ids = array();
        if (!empty($lesson_ids)) {
            $lesson_count = count($lesson_ids);
            // Note: For courses exceeding MAX_QUERY_ITEMS lessons, quiz counting is skipped
            // This is an edge case limitation affecting very large courses (1000+ lessons)
            if ($lesson_count <= self::MAX_QUERY_ITEMS) {
                // Ensure all lesson IDs are integers for security
                $lesson_ids = array_map('intval', $lesson_ids);
                
                // Build safe OR conditions for each lesson ID (checking both single lesson_id and serialized lesson_ids)
                // Each condition is individually escaped using wpdb->prepare() before concatenation
                $quiz_conditions = array();
                foreach ($lesson_ids as $lid) {
                    // Prepared statement for single lesson_id - safe integer substitution
                    $quiz_conditions[] = $wpdb->prepare(
                        "(pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)",
                        $lid
                    );
                    // Prepared statement for serialized lesson_ids - safe string substitution with escaped LIKE patterns
                    $int_pattern_lesson = '%' . $wpdb->esc_like('i:' . $lid . ';') . '%';
                    $str_pattern_lesson = '%' . $wpdb->esc_like(serialize(strval($lid))) . '%';
                    $quiz_conditions[] = $wpdb->prepare(
                        "(pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s))",
                        $int_pattern_lesson,
                        $str_pattern_lesson
                    );
                }
                // Safe to concatenate: all conditions are already escaped via wpdb->prepare()
                // This pattern is necessary because wpdb->prepare() doesn't support dynamic OR clause construction
                $quiz_where_clause = implode(' OR ', $quiz_conditions);
                
                // Execute query - no additional prepare needed as all inputs are already sanitized above
                $quiz_ids = $wpdb->get_col("
                    SELECT DISTINCT pm.post_id 
                    FROM {$wpdb->postmeta} pm
                    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                    WHERE p.post_type = 'ielts_quiz'
                      AND p.post_status = 'publish'
                      AND ($quiz_where_clause)
                ");
            }
        }
        
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
            if ($resource_count <= self::MAX_QUERY_ITEMS) {
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
        // Check for both integer and string serialization in lesson_ids array
        // Integer: i:123; String: s:3:"123";
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_resource'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        // Get all quizzes for this lesson
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
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
    
    /**
     * Calculate lesson completion percentage
     * Based on sub lessons (resources) and quizzes only
     */
    public function get_lesson_completion_percentage($user_id, $lesson_id) {
        global $wpdb;
        
        // Check for both integer and string serialization in lesson_ids array
        // Integer: i:123; String: s:3:"123";
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        // Get all resources (sub lessons) for this lesson
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_resource'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        $total_resources = count($resource_ids);
        
        // Get all quizzes for this lesson
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        $total_quizzes = count($quiz_ids);
        
        // Total items needed for 100% completion (resources + quizzes)
        $total_items = $total_resources + $total_quizzes;
        
        if ($total_items == 0) {
            return 0;
        }
        
        // Get completed resources
        $table = $this->db->get_progress_table();
        $completed_resources = 0;
        if (!empty($resource_ids)) {
            $resource_ids = array_map('intval', $resource_ids);
            $resource_placeholders = implode(',', array_fill(0, count($resource_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT COUNT(DISTINCT resource_id) FROM $table WHERE user_id = %d AND lesson_id = %d AND resource_id IN ($resource_placeholders) AND completed = 1",
                array_merge(array($user_id, $lesson_id), $resource_ids)
            );
            $completed_resources = $wpdb->get_var($query);
        }
        
        // Get attempted quizzes (any quiz taken counts, regardless of score)
        $quiz_results_table = $this->db->get_quiz_results_table();
        $attempted_quizzes = 0;
        if (!empty($quiz_ids)) {
            $quiz_ids = array_map('intval', $quiz_ids);
            $quiz_placeholders = implode(',', array_fill(0, count($quiz_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT COUNT(DISTINCT quiz_id) FROM $quiz_results_table WHERE user_id = %d AND lesson_id = %d AND quiz_id IN ($quiz_placeholders)",
                array_merge(array($user_id, $lesson_id), $quiz_ids)
            );
            $attempted_quizzes = $wpdb->get_var($query);
        }
        
        $completed_items = $completed_resources + $attempted_quizzes;
        
        return round(($completed_items / $total_items) * 100, 1);
    }
    
    /**
     * Get average score from all tests/quizzes taken in a lesson
     * Returns array with 'average_percentage' and 'quiz_count'
     */
    public function get_lesson_average_score($user_id, $lesson_id) {
        global $wpdb;
        
        // Check for both integer and string serialization in lesson_ids array
        // Integer: i:123; String: s:3:"123";
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        // Get all quizzes for this lesson
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        if (empty($quiz_ids)) {
            return array(
                'average_percentage' => 0,
                'quiz_count' => 0
            );
        }
        
        // Get best results for each quiz in this lesson
        $quiz_results_table = $this->db->get_quiz_results_table();
        $quiz_ids = array_map('intval', $quiz_ids);
        $quiz_placeholders = implode(',', array_fill(0, count($quiz_ids), '%d'));
        
        // Get best percentage for each quiz
        $query = $wpdb->prepare("
            SELECT quiz_id, MAX(percentage) as best_percentage
            FROM $quiz_results_table 
            WHERE user_id = %d 
              AND lesson_id = %d 
              AND quiz_id IN ($quiz_placeholders)
            GROUP BY quiz_id
        ", array_merge(array($user_id, $lesson_id), $quiz_ids));
        
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            return array(
                'average_percentage' => 0,
                'quiz_count' => 0
            );
        }
        
        // Calculate average of best percentages
        $total_percentage = 0;
        $count = 0;
        foreach ($results as $result) {
            $total_percentage += $result->best_percentage;
            $count++;
        }
        
        $average = $count > 0 ? round($total_percentage / $count, 1) : 0;
        
        return array(
            'average_percentage' => $average,
            'quiz_count' => $count
        );
    }
    
    /**
     * Get average band score from all tests/quizzes with band scoring in a lesson
     * Returns array with 'average_band_score', 'quiz_count', and 'has_band_scores'
     */
    public function get_lesson_average_band_score($user_id, $lesson_id) {
        global $wpdb;
        
        // Check for both integer and string serialization in lesson_ids array
        // Integer: i:123; String: s:3:"123";
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        // Get all quizzes for this lesson
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        if (empty($quiz_ids)) {
            return array(
                'average_band_score' => 0,
                'quiz_count' => 0,
                'has_band_scores' => false
            );
        }
        
        // Get best results for each quiz that uses band scoring
        $quiz_results_table = $this->db->get_quiz_results_table();
        $quiz_handler = new IELTS_CM_Quiz_Handler();
        
        $band_scores = array();
        foreach ($quiz_ids as $quiz_id) {
            // Check if this quiz uses band scoring
            $scoring_type = get_post_meta($quiz_id, '_ielts_cm_scoring_type', true);
            if (empty($scoring_type) || $scoring_type === 'percentage') {
                continue; // Skip percentage-based quizzes
            }
            
            // Get best result for this quiz
            $best_result = $quiz_handler->get_best_quiz_result($user_id, $quiz_id);
            if ($best_result) {
                // Convert to band score
                $band_score = $quiz_handler->convert_to_band_score($best_result->score, $scoring_type);
                $band_scores[] = $band_score;
            }
        }
        
        if (empty($band_scores)) {
            return array(
                'average_band_score' => 0,
                'quiz_count' => 0,
                'has_band_scores' => false
            );
        }
        
        // Calculate average
        $total = array_sum($band_scores);
        $average = round($total / count($band_scores), 1);
        
        return array(
            'average_band_score' => $average,
            'quiz_count' => count($band_scores),
            'has_band_scores' => true
        );
    }
    
    /**
     * Get average score from all tests/quizzes taken in a course
     * Returns array with 'average_percentage' and 'quiz_count'
     */
    public function get_course_average_score($user_id, $course_id) {
        global $wpdb;
        
        // Get all quizzes in the course
        $int_pattern = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
        
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_course_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_course_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $course_id, $int_pattern, $str_pattern));
        
        if (empty($quiz_ids)) {
            return array(
                'average_percentage' => 0,
                'quiz_count' => 0
            );
        }
        
        // Get best results for each quiz in this course
        $quiz_results_table = $this->db->get_quiz_results_table();
        $quiz_ids = array_map('intval', $quiz_ids);
        $quiz_placeholders = implode(',', array_fill(0, count($quiz_ids), '%d'));
        
        // Get best percentage for each quiz
        $query = $wpdb->prepare("
            SELECT quiz_id, MAX(percentage) as best_percentage
            FROM $quiz_results_table 
            WHERE user_id = %d 
              AND quiz_id IN ($quiz_placeholders)
            GROUP BY quiz_id
        ", array_merge(array($user_id), $quiz_ids));
        
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            return array(
                'average_percentage' => 0,
                'quiz_count' => 0
            );
        }
        
        // Calculate average of best percentages
        $total_percentage = 0;
        $count = 0;
        foreach ($results as $result) {
            $total_percentage += $result->best_percentage;
            $count++;
        }
        
        $average = $count > 0 ? round($total_percentage / $count, 1) : 0;
        
        return array(
            'average_percentage' => $average,
            'quiz_count' => $count
        );
    }
    
    /**
     * Get the number of resources (sublessons) in a lesson
     * 
     * @param int $lesson_id The lesson ID
     * @return int Number of resources
     */
    public function get_lesson_resource_count($lesson_id) {
        global $wpdb;
        
        // Check for both integer and string serialization in lesson_ids array
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        $resource_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT pm.post_id) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_resource'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        return intval($resource_count);
    }
    
    /**
     * Get the number of resources with videos in a lesson
     * 
     * @param int $lesson_id The lesson ID
     * @return int Number of resources with videos
     */
    public function get_lesson_video_count($lesson_id) {
        global $wpdb;
        
        // Check for both integer and string serialization in lesson_ids array
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        // Get resource IDs for this lesson
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_resource'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        if (empty($resource_ids)) {
            return 0;
        }
        
        // Count resources with non-empty video URLs
        $resource_ids = array_map('intval', $resource_ids);
        $resource_placeholders = implode(',', array_fill(0, count($resource_ids), '%d'));
        
        $video_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE post_id IN ($resource_placeholders)
              AND meta_key = '_ielts_cm_video_url'
              AND meta_value != ''
        ", $resource_ids));
        
        return intval($video_count);
    }
    
    /**
     * Get the number of quizzes (exercises) in a lesson
     * 
     * @param int $lesson_id The lesson ID
     * @return int Number of quizzes
     */
    public function get_lesson_quiz_count($lesson_id) {
        global $wpdb;
        
        // Check for both integer and string serialization in lesson_ids array
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        $quiz_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT pm.post_id) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'ielts_quiz'
              AND p.post_status = 'publish'
              AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                OR (pm.meta_key = '_ielts_cm_lesson_ids' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        return intval($quiz_count);
    }
    
    /**
     * Get content counts for multiple lessons in a single batch query
     * This is more efficient than calling individual count methods in a loop
     * 
     * @param array $lesson_ids Array of lesson IDs
     * @return array Associative array with lesson_id as key and counts array as value
     *               Each counts array contains: resource_count, video_count, quiz_count
     */
    public function get_lessons_content_counts_batch($lesson_ids) {
        global $wpdb;
        
        if (empty($lesson_ids)) {
            return array();
        }
        
        // Sanitize lesson IDs
        $lesson_ids = array_map('intval', $lesson_ids);
        $lesson_placeholders = implode(',', array_fill(0, count($lesson_ids), '%d'));
        
        // Initialize result array
        $counts = array();
        foreach ($lesson_ids as $lesson_id) {
            $counts[$lesson_id] = array(
                'resource_count' => 0,
                'video_count' => 0,
                'quiz_count' => 0
            );
        }
        
        // Get resource counts for all lessons
        // Need to check both singular (_ielts_cm_lesson_id) and plural (_ielts_cm_lesson_ids)
        foreach ($lesson_ids as $lesson_id) {
            // For singular lesson_id
            $singular_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'ielts_resource'
                  AND p.post_status = 'publish'
                  AND pm.meta_key = '_ielts_cm_lesson_id'
                  AND pm.meta_value = %d
            ", $lesson_id));
            
            // For plural lesson_ids - check serialized array patterns
            $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
            $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
            
            $plural_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'ielts_resource'
                  AND p.post_status = 'publish'
                  AND pm.meta_key = '_ielts_cm_lesson_ids'
                  AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
            ", $int_pattern, $str_pattern));
            
            $counts[$lesson_id]['resource_count'] = intval($singular_count) + intval($plural_count);
        }
        
        // Get video counts for all lessons
        // Videos are resources with a video URL, need to check both singular and plural lesson associations
        foreach ($lesson_ids as $lesson_id) {
            // For singular lesson_id
            $singular_video_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                INNER JOIN {$wpdb->postmeta} pm_video ON pm.post_id = pm_video.post_id
                WHERE p.post_type = 'ielts_resource'
                  AND p.post_status = 'publish'
                  AND pm.meta_key = '_ielts_cm_lesson_id'
                  AND pm.meta_value = %d
                  AND pm_video.meta_key = '_ielts_cm_video_url'
                  AND pm_video.meta_value != ''
            ", $lesson_id));
            
            // For plural lesson_ids - check serialized array patterns
            $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
            $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
            
            $plural_video_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                INNER JOIN {$wpdb->postmeta} pm_video ON pm.post_id = pm_video.post_id
                WHERE p.post_type = 'ielts_resource'
                  AND p.post_status = 'publish'
                  AND pm.meta_key = '_ielts_cm_lesson_ids'
                  AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
                  AND pm_video.meta_key = '_ielts_cm_video_url'
                  AND pm_video.meta_value != ''
            ", $int_pattern, $str_pattern));
            
            $counts[$lesson_id]['video_count'] = intval($singular_video_count) + intval($plural_video_count);
        }
        
        // Get quiz counts for all lessons
        // Need to check both singular (_ielts_cm_lesson_id) and plural (_ielts_cm_lesson_ids)
        foreach ($lesson_ids as $lesson_id) {
            // For singular lesson_id
            $singular_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'ielts_quiz'
                  AND p.post_status = 'publish'
                  AND pm.meta_key = '_ielts_cm_lesson_id'
                  AND pm.meta_value = %d
            ", $lesson_id));
            
            // For plural lesson_ids - check serialized array patterns
            $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
            $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
            
            $plural_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'ielts_quiz'
                  AND p.post_status = 'publish'
                  AND pm.meta_key = '_ielts_cm_lesson_ids'
                  AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
            ", $int_pattern, $str_pattern));
            
            $counts[$lesson_id]['quiz_count'] = intval($singular_count) + intval($plural_count);
        }
        
        return $counts;
    }
}
