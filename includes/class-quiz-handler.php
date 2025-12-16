<?php
/**
 * Quiz handling functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Quiz_Handler {
    
    private $db;
    
    public function __construct() {
        $this->db = new IELTS_CM_Database();
        
        // AJAX handlers
        add_action('wp_ajax_ielts_cm_submit_quiz', array($this, 'submit_quiz'));
        add_action('wp_ajax_ielts_cm_get_quiz_results', array($this, 'get_quiz_results_ajax'));
    }
    
    /**
     * Submit quiz answers
     */
    public function submit_quiz() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $course_id = intval($_POST['course_id']);
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : null;
        $answers = isset($_POST['answers']) ? json_decode(stripslashes($_POST['answers']), true) : array();
        
        // Get quiz questions and calculate score
        $questions = get_post_meta($quiz_id, '_ielts_cm_questions', true);
        if (!$questions) {
            $questions = array();
        }
        
        $score = 0;
        $max_score = 0;
        
        foreach ($questions as $index => $question) {
            $max_score += isset($question['points']) ? floatval($question['points']) : 1;
            
            if (isset($answers[$index])) {
                $is_correct = $this->check_answer($question, $answers[$index]);
                if ($is_correct) {
                    $score += isset($question['points']) ? floatval($question['points']) : 1;
                }
            }
        }
        
        $percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
        
        // Save result
        $result = $this->save_quiz_result($user_id, $quiz_id, $course_id, $lesson_id, $score, $max_score, $percentage, $answers);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Quiz submitted successfully',
                'score' => $score,
                'max_score' => $max_score,
                'percentage' => round($percentage, 2)
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save quiz result'));
        }
    }
    
    /**
     * Check if an answer is correct
     */
    private function check_answer($question, $user_answer) {
        $type = $question['type'];
        
        switch ($type) {
            case 'multiple_choice':
            case 'true_false':
                return isset($question['correct_answer']) && $question['correct_answer'] == $user_answer;
                
            case 'fill_blank':
                $correct = isset($question['correct_answer']) ? strtolower(trim($question['correct_answer'])) : '';
                $user = strtolower(trim($user_answer));
                // Remove extra whitespace and punctuation for more flexible matching
                $correct = preg_replace('/[^\w\s]/', '', $correct);
                $user = preg_replace('/[^\w\s]/', '', $user);
                $correct = preg_replace('/\s+/', ' ', $correct);
                $user = preg_replace('/\s+/', ' ', $user);
                return $correct === $user;
                
            case 'essay':
                // Essay questions need manual grading
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Save quiz result to database
     */
    public function save_quiz_result($user_id, $quiz_id, $course_id, $lesson_id, $score, $max_score, $percentage, $answers) {
        global $wpdb;
        $table = $this->db->get_quiz_results_table();
        
        $data = array(
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'course_id' => $course_id,
            'lesson_id' => $lesson_id,
            'score' => $score,
            'max_score' => $max_score,
            'percentage' => $percentage,
            'answers' => json_encode($answers),
            'submitted_date' => current_time('mysql')
        );
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Get quiz results for a user
     */
    public function get_quiz_results($user_id, $course_id = null) {
        global $wpdb;
        $table = $this->db->get_quiz_results_table();
        
        if ($course_id) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND course_id = %d ORDER BY submitted_date DESC",
                $user_id, $course_id
            ));
        } else {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY submitted_date DESC",
                $user_id
            ));
        }
        
        return $results;
    }
    
    /**
     * Get best quiz result for a specific quiz
     */
    public function get_best_quiz_result($user_id, $quiz_id) {
        global $wpdb;
        $table = $this->db->get_quiz_results_table();
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND quiz_id = %d ORDER BY percentage DESC LIMIT 1",
            $user_id, $quiz_id
        ));
        
        return $result;
    }
    
    /**
     * AJAX handler for getting quiz results
     */
    public function get_quiz_results_ajax() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;
        $results = $this->get_quiz_results($user_id, $course_id);
        
        wp_send_json_success(array('results' => $results));
    }
    
    /**
     * Get quiz types
     */
    public static function get_quiz_types() {
        return array(
            'multiple_choice' => __('Multiple Choice', 'ielts-course-manager'),
            'true_false' => __('True/False/Not Given', 'ielts-course-manager'),
            'fill_blank' => __('Fill in the Blank', 'ielts-course-manager'),
            'essay' => __('Essay', 'ielts-course-manager')
        );
    }
}
