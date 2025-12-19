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
            $points_earned = 0;
            
            // Special handling for multi-select questions
            if ($question['type'] === 'multi_select') {
                $result = $this->check_multi_select_answer($question, isset($answers[$index]) ? $answers[$index] : array());
                $points_earned = $result['points_earned'];
                $is_correct = $result['is_correct'];
                $feedback = $result['feedback'];
                $score += $points_earned;
            } elseif (isset($answers[$index])) {
                $is_correct = $this->check_answer($question, $answers[$index]);
                if ($is_correct) {
                    $points_earned = isset($question['points']) ? floatval($question['points']) : 1;
                    $score += $points_earned;
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
            } else {
                // No answer provided - show incorrect feedback
                if (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                    $feedback = wp_kses_post($question['incorrect_feedback']);
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
            
            // Get display score (band score or percentage)
            $display_score = $this->get_display_score($quiz_id, $score, $percentage);
            
            // Get course URL if course_id is provided
            $course_url = '';
            if ($course_id) {
                $course_url = get_permalink($course_id);
            }
            
            wp_send_json_success(array(
                'message' => 'Quiz submitted successfully',
                'score' => $score,
                'max_score' => $max_score,
                'percentage' => round($percentage, 2),
                'display_score' => $display_score['display'],
                'display_type' => $display_score['type'],
                'question_results' => $question_results,
                'next_url' => $next_url,
                'course_url' => $course_url
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
            case 'summary_completion':
                // Support multiple accepted answers separated by pipe |
                $correct_answers = isset($question['correct_answer']) ? $question['correct_answer'] : '';
                
                // Split by pipe if multiple answers provided
                $accepted_answers = array_map('trim', explode('|', $correct_answers));
                
                $user = strtolower(trim($user_answer));
                // Remove extra whitespace and punctuation for more flexible matching
                $user = preg_replace('/[^\w\s]/', '', $user);
                $user = preg_replace('/\s+/', ' ', $user);
                
                // Check if user answer matches any of the accepted answers
                foreach ($accepted_answers as $correct) {
                    $correct = strtolower(trim($correct));
                    $correct = preg_replace('/[^\w\s]/', '', $correct);
                    $correct = preg_replace('/\s+/', ' ', $correct);
                    
                    if ($correct === $user) {
                        return true;
                    }
                }
                
                return false;
                
            case 'essay':
                // Essay questions need manual grading
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Check multi-select answer and calculate points
     * Users get 1 point for each correct selection
     */
    private function check_multi_select_answer($question, $user_answers) {
        $points_earned = 0;
        $feedback = '';
        
        // Ensure user_answers is an array
        if (!is_array($user_answers)) {
            $user_answers = array();
        }
        
        // Get correct answers from mc_options
        $correct_indices = array();
        if (isset($question['mc_options']) && is_array($question['mc_options'])) {
            foreach ($question['mc_options'] as $idx => $option) {
                if (!empty($option['is_correct'])) {
                    $correct_indices[] = $idx;
                }
            }
        }
        
        // Calculate points: 1 point for each correct selection
        // Check if user selected any incorrect options
        $has_incorrect_selections = false;
        foreach ($user_answers as $selected_index) {
            if (in_array($selected_index, $correct_indices)) {
                $points_earned += 1;
            } else {
                $has_incorrect_selections = true;
            }
        }
        
        // Determine if fully correct (all correct answers selected, no incorrect ones)
        $is_correct = (!$has_incorrect_selections && count($user_answers) === count($correct_indices) && $points_earned === count($correct_indices));
        
        // Get feedback
        if ($is_correct && isset($question['correct_feedback']) && !empty($question['correct_feedback'])) {
            $feedback = wp_kses_post($question['correct_feedback']);
        } elseif (!$is_correct && isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
            $feedback = wp_kses_post($question['incorrect_feedback']);
        }
        
        return array(
            'points_earned' => $points_earned,
            'is_correct' => $is_correct,
            'feedback' => $feedback
        );
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
            'multi_select' => __('Multi Select', 'ielts-course-manager'),
            'true_false' => __('True/False/Not Given', 'ielts-course-manager'),
            'fill_blank' => __('Fill in the Blank', 'ielts-course-manager'),
            'summary_completion' => __('Summary Completion', 'ielts-course-manager'),
            'essay' => __('Essay', 'ielts-course-manager')
        );
    }
    
    /**
     * Get IELTS band score conversion table
     * 
     * @param string $scoring_type Type of scoring
     * @return array Conversion table with max_score key
     */
    private function get_band_score_table($scoring_type) {
        // Tables are static to avoid recreating on every call
        static $tables = null;
        
        if ($tables === null) {
            // IELTS Academic Reading conversion table (40 questions max)
            $tables['ielts_academic_reading'] = array(
                'max_score' => 39,
                'table' => array(
                    39 => 9.0, 38 => 8.5, 37 => 8.5, 36 => 8.0, 35 => 8.0,
                    34 => 7.5, 33 => 7.5, 32 => 7.0, 31 => 7.0, 30 => 7.0,
                    29 => 6.5, 28 => 6.5, 27 => 6.5, 26 => 6.0, 25 => 6.0,
                    24 => 6.0, 23 => 6.0, 22 => 5.5, 21 => 5.5, 20 => 5.5,
                    19 => 5.5, 18 => 5.0, 17 => 5.0, 16 => 5.0, 15 => 5.0,
                    14 => 4.5, 13 => 4.5, 12 => 4.0, 11 => 4.0, 10 => 4.0,
                    9 => 3.5, 8 => 3.5, 7 => 3.0, 6 => 3.0, 5 => 2.5,
                    4 => 2.5, 3 => 2.0, 2 => 2.0, 1 => 1.5, 0 => 1.0
                )
            );
            
            // IELTS General Training Reading conversion table (40 questions)
            $tables['ielts_general_reading'] = array(
                'max_score' => 40,
                'table' => array(
                    40 => 9.0, 39 => 8.5, 38 => 8.0, 37 => 8.0, 36 => 7.5,
                    35 => 7.5, 34 => 7.0, 33 => 7.0, 32 => 6.5, 31 => 6.5,
                    30 => 6.0, 29 => 6.0, 28 => 5.5, 27 => 5.5, 26 => 5.5,
                    25 => 5.0, 24 => 5.0, 23 => 5.0, 22 => 4.5, 21 => 4.5,
                    20 => 4.5, 19 => 4.5, 18 => 4.0, 17 => 4.0, 16 => 4.0,
                    15 => 4.0, 14 => 3.5, 13 => 3.5, 12 => 3.5, 11 => 3.0,
                    10 => 3.0, 9 => 3.0, 8 => 2.5, 7 => 2.5, 6 => 2.5,
                    5 => 2.0, 4 => 2.0, 3 => 2.0, 2 => 1.5, 1 => 1.5,
                    0 => 1.0
                )
            );
            
            // IELTS Listening conversion table (40 questions)
            $tables['ielts_listening'] = array(
                'max_score' => 39,
                'table' => array(
                    39 => 9.0, 38 => 8.5, 37 => 8.5, 36 => 8.0, 35 => 8.0,
                    34 => 7.5, 33 => 7.5, 32 => 7.5, 31 => 7.0, 30 => 7.0,
                    29 => 6.5, 28 => 6.5, 27 => 6.5, 26 => 6.5, 25 => 6.0,
                    24 => 6.0, 23 => 6.0, 22 => 5.5, 21 => 5.5, 20 => 5.5,
                    19 => 5.5, 18 => 5.5, 17 => 5.0, 16 => 5.0, 15 => 4.5,
                    14 => 4.5, 13 => 4.5, 12 => 4.0, 11 => 4.0, 10 => 4.0,
                    9 => 3.5, 8 => 3.5, 7 => 3.0, 6 => 3.0, 5 => 2.5,
                    4 => 2.5, 3 => 2.0, 2 => 2.0, 1 => 1.5, 0 => 1.0
                )
            );
        }
        
        return isset($tables[$scoring_type]) ? $tables[$scoring_type] : null;
    }
    
    /**
     * Convert correct answers to IELTS band score
     * 
     * @param int $correct_answers Number of correct answers
     * @param string $scoring_type Type of scoring (ielts_general_reading, ielts_academic_reading, ielts_listening)
     * @return float Band score (0-9)
     */
    public function convert_to_band_score($correct_answers, $scoring_type) {
        // Get the conversion table
        $table_data = $this->get_band_score_table($scoring_type);
        
        if ($table_data === null) {
            return 0; // Invalid scoring type
        }
        
        $table = $table_data['table'];
        $max_score = $table_data['max_score'];
        
        // Look up the band score
        if (isset($table[$correct_answers])) {
            return $table[$correct_answers];
        }
        
        // If exact match not found, use the highest available score for scores above max
        if ($correct_answers > $max_score) {
            return $table[$max_score];
        }
        
        // Default to lowest score
        return 1.0;
    }
    
    /**
     * Get display score for quiz result
     * Returns band score for IELTS exercises, percentage for others
     * 
     * @param int $quiz_id Quiz ID
     * @param int $score Number of correct answers
     * @param float $percentage Score percentage
     * @return array Array with 'display' (formatted string) and 'value' (numeric)
     */
    public function get_display_score($quiz_id, $score, $percentage) {
        $scoring_type = get_post_meta($quiz_id, '_ielts_cm_scoring_type', true);
        
        if (empty($scoring_type) || $scoring_type === 'percentage') {
            // Standard percentage display
            return array(
                'display' => round($percentage, 1) . '%',
                'value' => $percentage,
                'type' => 'percentage'
            );
        }
        
        // IELTS band score display
        $band_score = $this->convert_to_band_score($score, $scoring_type);
        return array(
            'display' => 'Band ' . number_format($band_score, 1),
            'value' => $band_score,
            'type' => 'band'
        );
    }
}
