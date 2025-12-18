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
        $question_results = array();
        
        foreach ($questions as $index => $question) {
            $max_score += isset($question['points']) ? floatval($question['points']) : 1;
            
            $is_correct = false;
            $feedback = '';
            
            if (isset($answers[$index])) {
                $is_correct = $this->check_answer($question, $answers[$index]);
                if ($is_correct) {
                    $score += isset($question['points']) ? floatval($question['points']) : 1;
                    // Get correct answer feedback
                    if (isset($question['correct_feedback']) && !empty($question['correct_feedback'])) {
                        $feedback = wp_kses_post($question['correct_feedback']);
                    }
                } else {
                    // For multiple choice, check if there's specific feedback for this option
                    if ($question['type'] === 'multiple_choice') {
                        $user_answer_index = intval($answers[$index]);
                        
                        // Try new structured format first
                        if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                            // New format with mc_options
                            if ($user_answer_index >= 0 && $user_answer_index < count($question['mc_options']) 
                                && isset($question['mc_options'][$user_answer_index]['feedback']) 
                                && !empty($question['mc_options'][$user_answer_index]['feedback'])) {
                                $feedback = wp_kses_post($question['mc_options'][$user_answer_index]['feedback']);
                            }
                        } elseif (isset($question['option_feedback']) && is_array($question['option_feedback'])) {
                            // Legacy format
                            if ($user_answer_index >= 0 && $user_answer_index < count($question['option_feedback']) 
                                && isset($question['option_feedback'][$user_answer_index]) 
                                && !empty($question['option_feedback'][$user_answer_index])) {
                                $feedback = wp_kses_post($question['option_feedback'][$user_answer_index]);
                            }
                        }
                        
                        // Fallback to general incorrect feedback if no specific feedback found
                        if (empty($feedback) && isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                            $feedback = wp_kses_post($question['incorrect_feedback']);
                        }
                    } else {
                        // Get general incorrect answer feedback for non-MC questions
                        if (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                            $feedback = wp_kses_post($question['incorrect_feedback']);
                        }
                    }
                }
            }
            
            $question_results[$index] = array(
                'correct' => $is_correct,
                'feedback' => $feedback,
                'user_answer' => isset($answers[$index]) ? $answers[$index] : null,
                'correct_answer' => isset($question['correct_answer']) ? $question['correct_answer'] : null,
                'question_text' => isset($question['question']) ? $question['question'] : '',
                'question_type' => isset($question['type']) ? $question['type'] : '',
                'options' => isset($question['options']) ? $question['options'] : ''
            );
        }
        
        $percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
        
        // Save result
        $result = $this->save_quiz_result($user_id, $quiz_id, $course_id, $lesson_id, $score, $max_score, $percentage, $answers);
        
        if ($result) {
            // Get next item URL for navigation
            $next_url = $this->get_next_item_url($quiz_id, $course_id, $lesson_id);
            
            wp_send_json_success(array(
                'message' => 'Quiz submitted successfully',
                'score' => $score,
                'max_score' => $max_score,
                'percentage' => round($percentage, 2),
                'question_results' => $question_results,
                'next_url' => $next_url
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
     * Get the next item URL after completing a quiz
     */
    private function get_next_item_url($quiz_id, $course_id, $lesson_id) {
        global $wpdb;
        
        // If we have a lesson, find next item in the lesson
        if ($lesson_id) {
            // Get all resources and quizzes for this lesson
            $resource_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
            ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
            
            $quiz_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
            ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
            
            // Combine all content items
            $all_items = array();
            
            if (!empty($resource_ids)) {
                $resources = get_posts(array(
                    'post_type' => 'ielts_resource',
                    'posts_per_page' => -1,
                    'post__in' => $resource_ids,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'post_status' => 'publish'
                ));
                foreach ($resources as $resource) {
                    $all_items[] = array('post' => $resource, 'order' => $resource->menu_order);
                }
            }
            
            if (!empty($quiz_ids)) {
                $quizzes = get_posts(array(
                    'post_type' => 'ielts_quiz',
                    'posts_per_page' => -1,
                    'post__in' => $quiz_ids,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'post_status' => 'publish'
                ));
                foreach ($quizzes as $quiz) {
                    $all_items[] = array('post' => $quiz, 'order' => $quiz->menu_order);
                }
            }
            
            // Sort by menu order
            usort($all_items, function($a, $b) {
                return $a['order'] - $b['order'];
            });
            
            // Find current quiz and get next item
            $current_index = -1;
            foreach ($all_items as $index => $item) {
                if ($item['post']->ID == $quiz_id) {
                    $current_index = $index;
                    break;
                }
            }
            
            // If there's a next item in this lesson, return its URL
            if ($current_index >= 0 && $current_index < count($all_items) - 1) {
                return get_permalink($all_items[$current_index + 1]['post']->ID);
            }
            
            // If no more items in lesson, return to lesson page
            return get_permalink($lesson_id);
        }
        
        // If no lesson, try to find next quiz in the course
        if ($course_id) {
            $quiz_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_course_ids' AND meta_value LIKE %s)
            ", $course_id, '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%'));
            
            if (!empty($quiz_ids)) {
                $quizzes = get_posts(array(
                    'post_type' => 'ielts_quiz',
                    'posts_per_page' => -1,
                    'post__in' => $quiz_ids,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'post_status' => 'publish'
                ));
                
                $current_index = -1;
                foreach ($quizzes as $index => $quiz) {
                    if ($quiz->ID == $quiz_id) {
                        $current_index = $index;
                        break;
                    }
                }
                
                // If there's a next quiz, return its URL
                if ($current_index >= 0 && $current_index < count($quizzes) - 1) {
                    return get_permalink($quizzes[$current_index + 1]->ID);
                }
            }
            
            // Return to course page
            return get_permalink($course_id);
        }
        
        // Default: return null (no navigation)
        return null;
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
