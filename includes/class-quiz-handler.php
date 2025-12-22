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
        
        // Calculate display question numbers for feedback (same logic as in template)
        $question_display_numbers = array();
        $display_question_number = 1;
        foreach ($questions as $idx => $q) {
            $start_num = $display_question_number;
            $question_count = 1;
            
            if ($q['type'] === 'multi_select' && isset($q['mc_options']) && is_array($q['mc_options'])) {
                $correct_count = 0;
                foreach ($q['mc_options'] as $opt) {
                    if (!empty($opt['is_correct'])) {
                        $correct_count++;
                    }
                }
                $question_count = max(1, $correct_count);
            } elseif ($q['type'] === 'summary_completion') {
                $field_count = 0;
                if (isset($q['summary_fields']) && is_array($q['summary_fields'])) {
                    $field_count = count($q['summary_fields']);
                } else {
                    $question_text = isset($q['question']) ? $q['question'] : '';
                    preg_match_all('/\[field\s+(\d+)\]/i', $question_text, $field_matches);
                    preg_match_all('/\[ANSWER\s+(\d+)\]/i', $question_text, $answer_matches);
                    if (!empty($field_matches[1])) {
                        $field_count = count(array_unique($field_matches[1]));
                    } elseif (!empty($answer_matches[1])) {
                        $field_count = count(array_unique($answer_matches[1]));
                    }
                }
                $question_count = max(1, $field_count);
            } elseif ($q['type'] === 'dropdown_paragraph') {
                $paragraph_text = isset($q['question']) ? $q['question'] : '';
                preg_match_all('/(\d+)\.\[([^\]]+)\]/i', $paragraph_text, $dropdown_matches);
                $dropdown_count = !empty($dropdown_matches[0]) ? count($dropdown_matches[0]) : 1;
                $question_count = max(1, $dropdown_count);
            } elseif ($q['type'] === 'table_completion') {
                $field_count = 0;
                if (isset($q['summary_fields']) && is_array($q['summary_fields'])) {
                    $field_count = count($q['summary_fields']);
                } else {
                    $question_text = isset($q['question']) ? $q['question'] : '';
                    preg_match_all('/\[field\s+(\d+)\]/i', $question_text, $field_matches);
                    if (!empty($field_matches[1])) {
                        $field_count = count(array_unique($field_matches[1]));
                    }
                }
                $question_count = max(1, $field_count);
            }
            
            $question_display_numbers[$idx] = array(
                'start' => $start_num,
                'end' => $start_num + $question_count - 1,
                'count' => $question_count
            );
            $display_question_number += $question_count;
        }
        
        foreach ($questions as $index => $question) {
            // Calculate max score for each question type
            if ($question['type'] === 'multi_select') {
                $correct_count = 0;
                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                    foreach ($question['mc_options'] as $option) {
                        if (!empty($option['is_correct'])) {
                            $correct_count++;
                        }
                    }
                }
                $max_score += max(1, $correct_count); // At least 1 point
            } elseif ($question['type'] === 'summary_completion' && isset($question['summary_fields']) && is_array($question['summary_fields'])) {
                // Summary completion with fields - each field counts as 1 point
                $max_score += count($question['summary_fields']);
            } elseif ($question['type'] === 'dropdown_paragraph') {
                // Dropdown paragraph - count each dropdown as 1 point
                // Parse question text to count dropdowns
                $paragraph_text = isset($question['question']) ? $question['question'] : '';
                preg_match_all('/(\d+)\.\[([^\]]+)\]/i', $paragraph_text, $matches);
                $dropdown_count = !empty($matches[0]) ? count($matches[0]) : 1;
                $max_score += $dropdown_count;
            } elseif ($question['type'] === 'table_completion' && isset($question['summary_fields']) && is_array($question['summary_fields'])) {
                // Table completion with fields - each field counts as 1 point (same as summary completion)
                $max_score += count($question['summary_fields']);
            } elseif ($question['type'] === 'headings') {
                // Headings questions - independent implementation
                $max_score += isset($question['points']) ? floatval($question['points']) : 1;
            } elseif ($question['type'] === 'matching_classifying') {
                // Matching/Classifying questions - independent implementation
                $max_score += isset($question['points']) ? floatval($question['points']) : 1;
            } elseif ($question['type'] === 'matching') {
                // Matching questions - independent implementation
                $max_score += isset($question['points']) ? floatval($question['points']) : 1;
            } else {
                $max_score += isset($question['points']) ? floatval($question['points']) : 1;
            }
            
            $is_correct = false;
            $feedback = '';
            $points_earned = 0;
            $correct_answer = null;
            
            // Handle each question type independently
            if ($question['type'] === 'multi_select') {
                $result = $this->check_multi_select_answer($question, isset($answers[$index]) ? $answers[$index] : array());
                $points_earned = $result['points_earned'];
                $is_correct = $result['is_correct'];
                $feedback = $result['feedback'];
                $score += $points_earned;
                
                // Store correct indices for multi-select so frontend can highlight them
                $correct_answer = isset($result['correct_indices']) ? $result['correct_indices'] : array();
            } elseif ($question['type'] === 'summary_completion' && isset($question['summary_fields']) && is_array($question['summary_fields'])) {
                // Summary completion with fields - check each field separately
                $field_results = array();
                $all_correct = true;
                $any_answered = false;
                
                // Get user answers - handle both nested format (from JavaScript) and flat format
                $user_answers = array();
                if (isset($answers[$index]) && is_array($answers[$index])) {
                    // Nested format from JavaScript: answers[0][1], answers[0][2]
                    $user_answers = $answers[$index];
                } else {
                    // Flat format: answers['0_field_1'], answers['0_field_2']
                    foreach ($question['summary_fields'] as $field_num => $field_data) {
                        $field_answer_key = $index . '_field_' . $field_num;
                        if (isset($answers[$field_answer_key])) {
                            $user_answers[$field_num] = $answers[$field_answer_key];
                        }
                    }
                }
                
                foreach ($question['summary_fields'] as $field_num => $field_data) {
                    $user_field_answer = isset($user_answers[$field_num]) ? trim($user_answers[$field_num]) : '';
                    
                    $field_correct = false;
                    $field_feedback = '';
                    
                    if (!empty($user_field_answer)) {
                        $any_answered = true;
                        // Check if answer is correct
                        $accepted_answers = isset($field_data['answer']) ? explode('|', $field_data['answer']) : array();
                        foreach ($accepted_answers as $accepted) {
                            if (strcasecmp(trim($accepted), $user_field_answer) === 0) {
                                $field_correct = true;
                                break;
                            }
                        }
                        
                        if ($field_correct) {
                            $points_earned += 1;
                            $field_feedback = isset($field_data['correct_feedback']) && !empty($field_data['correct_feedback']) 
                                ? wp_kses_post($field_data['correct_feedback']) 
                                : '';
                        } else {
                            $all_correct = false;
                            $field_feedback = isset($field_data['incorrect_feedback']) && !empty($field_data['incorrect_feedback']) 
                                ? wp_kses_post($field_data['incorrect_feedback']) 
                                : '';
                        }
                    } else {
                        // No answer provided for this field
                        $all_correct = false;
                        $field_feedback = isset($field_data['no_answer_feedback']) && !empty($field_data['no_answer_feedback']) 
                            ? wp_kses_post($field_data['no_answer_feedback']) 
                            : '';
                    }
                    
                    $field_results[$field_num] = array(
                        'correct' => $field_correct,
                        'feedback' => $field_feedback,
                        'user_answer' => $user_field_answer
                    );
                }
                
                $score += $points_earned;
                $is_correct = $all_correct && $any_answered;
                
                // Build combined feedback
                $feedback_parts = array();
                $display_nums = $question_display_numbers[$index];
                foreach ($field_results as $field_num => $field_result) {
                    if (!empty($field_result['feedback'])) {
                        // Calculate the actual question number for this field
                        $question_number = $display_nums['start'] + intval($field_num) - 1;
                        $feedback_parts[] = '<strong>' . sprintf(__('Question %s:', 'ielts-course-manager'), $question_number) . '</strong> ' . $field_result['feedback'];
                    }
                }
                $feedback = !empty($feedback_parts) ? implode('<br>', $feedback_parts) : '';
                
                // Store field results for display
                $correct_answer = array('field_results' => $field_results);
            } elseif ($question['type'] === 'dropdown_paragraph') {
                // Dropdown paragraph - score each dropdown separately (1 point each)
                $user_answer = isset($answers[$index]) ? $answers[$index] : array();
                
                // Ensure user_answer is an array
                if (!is_array($user_answer)) {
                    $user_answer = array();
                }
                
                // Parse correct answers from question's correct_answer field
                $correct_answers = isset($question['correct_answer']) ? $question['correct_answer'] : '';
                $answer_map = array();
                $parts = explode('|', $correct_answers);
                foreach ($parts as $part) {
                    $part = trim($part);
                    $parts_split = explode(':', $part, 2);
                    if (count($parts_split) === 2) {
                        $num = trim($parts_split[0]);
                        $letter = strtoupper(trim($parts_split[1]));
                        $answer_map[$num] = $letter;
                    }
                }
                
                // Check each dropdown and award 1 point for each correct answer
                $all_correct = true;
                $any_answered = false;
                foreach ($answer_map as $dropdown_num => $correct_letter) {
                    if (isset($user_answer[$dropdown_num])) {
                        $user_letter = strtoupper(trim($user_answer[$dropdown_num]));
                        if (!empty($user_letter)) {
                            $any_answered = true;
                            if ($user_letter === $correct_letter) {
                                $points_earned += 1;
                            } else {
                                $all_correct = false;
                            }
                        } else {
                            $all_correct = false;
                        }
                    } else {
                        $all_correct = false;
                    }
                }
                
                $score += $points_earned;
                $is_correct = $all_correct && $any_answered;
                
                // Get feedback
                if ($is_correct && isset($question['correct_feedback']) && !empty($question['correct_feedback'])) {
                    $feedback = wp_kses_post($question['correct_feedback']);
                } elseif (!$is_correct && isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                    $feedback = wp_kses_post($question['incorrect_feedback']);
                }
                
                // Store correct answer for display
                if ($correct_answer === null && isset($question['correct_answer'])) {
                    $correct_answer = $question['correct_answer'];
                }
            } elseif ($question['type'] === 'table_completion' && isset($question['summary_fields']) && is_array($question['summary_fields'])) {
                // Table completion with fields - score each field separately (same as summary completion)
                $field_results = array();
                $all_correct = true;
                $any_answered = false;
                
                // Get user answers - handle both nested format (from JavaScript) and flat format
                $user_answers = array();
                if (isset($answers[$index]) && is_array($answers[$index])) {
                    // Nested format from JavaScript: answers[0][1], answers[0][2]
                    $user_answers = $answers[$index];
                } else {
                    // Flat format: answers['0_field_1'], answers['0_field_2']
                    foreach ($question['summary_fields'] as $field_num => $field_data) {
                        $field_answer_key = $index . '_field_' . $field_num;
                        if (isset($answers[$field_answer_key])) {
                            $user_answers[$field_num] = $answers[$field_answer_key];
                        }
                    }
                }
                
                foreach ($question['summary_fields'] as $field_num => $field_data) {
                    $user_field_answer = isset($user_answers[$field_num]) ? trim($user_answers[$field_num]) : '';
                    
                    $field_correct = false;
                    $field_feedback = '';
                    
                    if (!empty($user_field_answer)) {
                        $any_answered = true;
                        // Check if answer is correct
                        $accepted_answers = isset($field_data['answer']) ? explode('|', $field_data['answer']) : array();
                        foreach ($accepted_answers as $accepted) {
                            if (strcasecmp(trim($accepted), $user_field_answer) === 0) {
                                $field_correct = true;
                                break;
                            }
                        }
                        
                        if ($field_correct) {
                            $points_earned += 1;
                            $field_feedback = isset($field_data['correct_feedback']) && !empty($field_data['correct_feedback']) 
                                ? wp_kses_post($field_data['correct_feedback']) 
                                : '';
                        } else {
                            $all_correct = false;
                            $field_feedback = isset($field_data['incorrect_feedback']) && !empty($field_data['incorrect_feedback']) 
                                ? wp_kses_post($field_data['incorrect_feedback']) 
                                : '';
                        }
                    } else {
                        // No answer provided for this field
                        $all_correct = false;
                        $field_feedback = isset($field_data['no_answer_feedback']) && !empty($field_data['no_answer_feedback']) 
                            ? wp_kses_post($field_data['no_answer_feedback']) 
                            : '';
                    }
                    
                    $field_results[$field_num] = array(
                        'correct' => $field_correct,
                        'feedback' => $field_feedback,
                        'user_answer' => $user_field_answer
                    );
                }
                
                $score += $points_earned;
                $is_correct = $all_correct && $any_answered;
                
                // Build combined feedback
                $feedback_parts = array();
                $display_nums = $question_display_numbers[$index];
                foreach ($field_results as $field_num => $field_result) {
                    if (!empty($field_result['feedback'])) {
                        // Calculate the actual question number for this field
                        $question_number = $display_nums['start'] + intval($field_num) - 1;
                        $feedback_parts[] = '<strong>' . sprintf(__('Question %s:', 'ielts-course-manager'), $question_number) . '</strong> ' . $field_result['feedback'];
                    }
                }
                $feedback = !empty($feedback_parts) ? implode('<br>', $feedback_parts) : '';
                
                // Store field results for display
                $correct_answer = array('field_results' => $field_results);
            } elseif ($question['type'] === 'headings') {
                // Headings - independent implementation
                $user_answer = isset($answers[$index]) ? $answers[$index] : null;
                $is_correct = $this->check_answer($question, $user_answer);
                
                if ($is_correct) {
                    $points_earned = isset($question['points']) ? floatval($question['points']) : 1;
                    $feedback = __('Correct!', 'ielts-course-manager');
                } else {
                    $points_earned = 0;
                    $feedback = __('Incorrect', 'ielts-course-manager');
                }
                
                $score += $points_earned;
            } elseif ($question['type'] === 'matching_classifying') {
                // Matching/Classifying - independent implementation
                $user_answer = isset($answers[$index]) ? $answers[$index] : null;
                $is_correct = $this->check_answer($question, $user_answer);
                
                if ($is_correct) {
                    $points_earned = isset($question['points']) ? floatval($question['points']) : 1;
                    // Use custom correct feedback if available
                    if (isset($question['correct_feedback']) && !empty($question['correct_feedback'])) {
                        $feedback = wp_kses_post($question['correct_feedback']);
                    }
                } else {
                    $points_earned = 0;
                    // Check if no answer was provided
                    if ($user_answer === null || $user_answer === '') {
                        // Use no_answer_feedback if available
                        if (isset($question['no_answer_feedback']) && !empty($question['no_answer_feedback'])) {
                            $feedback = wp_kses_post($question['no_answer_feedback']);
                        } elseif (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                            $feedback = wp_kses_post($question['incorrect_feedback']);
                        }
                    } else {
                        // Use incorrect_feedback for wrong answers
                        if (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                            $feedback = wp_kses_post($question['incorrect_feedback']);
                        }
                    }
                }
                
                $score += $points_earned;
            } elseif ($question['type'] === 'matching') {
                // Matching - independent implementation
                $user_answer = isset($answers[$index]) ? $answers[$index] : null;
                $is_correct = $this->check_answer($question, $user_answer);
                
                if ($is_correct) {
                    $points_earned = isset($question['points']) ? floatval($question['points']) : 1;
                    // Use custom correct feedback if available
                    if (isset($question['correct_feedback']) && !empty($question['correct_feedback'])) {
                        $feedback = wp_kses_post($question['correct_feedback']);
                    }
                } else {
                    $points_earned = 0;
                    // Check if no answer was provided
                    if ($user_answer === null || $user_answer === '') {
                        // Use no_answer_feedback if available
                        if (isset($question['no_answer_feedback']) && !empty($question['no_answer_feedback'])) {
                            $feedback = wp_kses_post($question['no_answer_feedback']);
                        } elseif (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                            $feedback = wp_kses_post($question['incorrect_feedback']);
                        }
                    } else {
                        // Use incorrect_feedback for wrong answers
                        if (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                            $feedback = wp_kses_post($question['incorrect_feedback']);
                        }
                    }
                }
                
                $score += $points_earned;
            } elseif ($question['type'] === 'locating_information') {
                // Locating Information - independent implementation
                $user_answer = isset($answers[$index]) ? $answers[$index] : null;
                $is_correct = $this->check_answer($question, $user_answer);
                
                if ($is_correct) {
                    $points_earned = isset($question['points']) ? floatval($question['points']) : 1;
                    $feedback = __('Correct!', 'ielts-course-manager');
                } else {
                    $points_earned = 0;
                    $feedback = __('Incorrect', 'ielts-course-manager');
                }
                
                $score += $points_earned;
            } elseif (isset($answers[$index])) {
                $is_correct = $this->check_answer($question, $answers[$index]);
                if ($is_correct) {
                    $points_earned = isset($question['points']) ? floatval($question['points']) : 1;
                    $score += $points_earned;
                    // Get correct answer feedback
                    if (isset($question['correct_feedback']) && !empty($question['correct_feedback'])) {
                        $feedback = wp_kses_post($question['correct_feedback']);
                    } else {
                        // Provide default feedback for correct answers when no custom feedback is set
                        $feedback = __('Correct!', 'ielts-course-manager');
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
                
                // Set correct_answer for non-multi-select questions  
                if ($correct_answer === null && isset($question['correct_answer'])) {
                    $correct_answer = $question['correct_answer'];
                }
            } else {
                // No answer provided - show no_answer_feedback if available, otherwise incorrect feedback
                if (isset($question['no_answer_feedback']) && !empty($question['no_answer_feedback'])) {
                    $feedback = wp_kses_post($question['no_answer_feedback']);
                } elseif (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                    $feedback = wp_kses_post($question['incorrect_feedback']);
                }
            }
            
            $question_results[$index] = array(
                'correct' => $is_correct,
                'feedback' => $feedback,
                'user_answer' => isset($answers[$index]) ? $answers[$index] : null,
                'correct_answer' => $correct_answer,
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
                // Multiple choice - independent implementation
                return isset($question['correct_answer']) && $question['correct_answer'] == $user_answer;
                
            case 'headings':
                // Headings - independent implementation
                return isset($question['correct_answer']) && $question['correct_answer'] == $user_answer;
                
            case 'matching_classifying':
                // Matching/Classifying - independent implementation
                return isset($question['correct_answer']) && $question['correct_answer'] == $user_answer;
                
            case 'matching':
                // Matching - independent implementation
                return isset($question['correct_answer']) && $question['correct_answer'] == $user_answer;
                
            case 'locating_information':
                // Locating Information - independent implementation
                return isset($question['correct_answer']) && $question['correct_answer'] == $user_answer;
                
            case 'true_false':
                // True/False - independent implementation
                return isset($question['correct_answer']) && $question['correct_answer'] == $user_answer;
                
            case 'summary_completion':
            case 'short_answer':
            case 'sentence_completion':
            case 'table_completion':
            case 'labelling':
                // Check if this is a summary completion with multiple inline answers
                if (is_array($user_answer) && $type === 'summary_completion') {
                    // Handle inline answers - correct_answer should be in format "1:answer1|answer1alt|2:answer2|answer2alt"
                    $correct_answers = isset($question['correct_answer']) ? $question['correct_answer'] : '';
                    
                    // Parse correct answers by number (e.g., "1:paris|france|2:london|uk")
                    $answer_groups = array();
                    $parts = explode('|', $correct_answers);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (strpos($part, ':') !== false) {
                            $parts_split = explode(':', $part, 2);
                            if (count($parts_split) === 2) {
                                $num = trim($parts_split[0]);
                                $ans = trim($parts_split[1]);
                                $answer_groups[$num][] = $ans;
                            }
                        } elseif (!empty($answer_groups)) {
                            // No colon means it's an alternative for the last number
                            end($answer_groups);
                            $last_key = key($answer_groups);
                            $answer_groups[$last_key][] = trim($part);
                        }
                    }
                    
                    // Check each user answer against its corresponding correct answers
                    foreach ($user_answer as $answer_num => $user_ans) {
                        if (!isset($answer_groups[$answer_num])) {
                            return false; // Unknown answer number
                        }
                        
                        $user = strtolower(trim($user_ans));
                        $user = preg_replace('/[^\w\s]/', '', $user);
                        $user = preg_replace('/\s+/', ' ', $user);
                        
                        $found_match = false;
                        foreach ($answer_groups[$answer_num] as $correct) {
                            $correct = strtolower(trim($correct));
                            $correct = preg_replace('/[^\w\s]/', '', $correct);
                            $correct = preg_replace('/\s+/', ' ', $correct);
                            
                            if ($correct === $user) {
                                $found_match = true;
                                break;
                            }
                        }
                        
                        if (!$found_match) {
                            return false; // One of the answers was incorrect
                        }
                    }
                    
                    return true; // All answers were correct
                }
                
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
            
            case 'dropdown_paragraph':
                // Check if this is a dropdown paragraph with multiple inline dropdowns
                if (is_array($user_answer)) {
                    // Handle inline dropdowns - correct_answer format: "1:A|2:B|3:A"
                    // where the number is the dropdown position and the letter is the correct option
                    $correct_answers = isset($question['correct_answer']) ? $question['correct_answer'] : '';
                    
                    // Parse correct answers by number (e.g., "1:A|2:B|3:C")
                    $answer_map = array();
                    $parts = explode('|', $correct_answers);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        $parts_split = explode(':', $part, 2);
                        if (count($parts_split) === 2) {
                            $num = trim($parts_split[0]);
                            $letter = strtoupper(trim($parts_split[1]));
                            $answer_map[$num] = $letter;
                        }
                    }
                    
                    // Check that user has answered all required dropdowns
                    if (count($user_answer) !== count($answer_map)) {
                        return false; // Not all dropdowns answered
                    }
                    
                    // Check each user answer against the correct answer
                    foreach ($user_answer as $dropdown_num => $user_letter) {
                        if (!isset($answer_map[$dropdown_num])) {
                            return false; // Unknown dropdown number
                        }
                        
                        // Skip empty answers
                        $user_letter = trim($user_letter);
                        if (empty($user_letter)) {
                            return false; // Empty answer
                        }
                        
                        $user_letter = strtoupper($user_letter);
                        
                        if ($answer_map[$dropdown_num] !== $user_letter) {
                            return false; // Wrong answer for this dropdown
                        }
                    }
                    
                    return true; // All answers were correct
                }
                
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
        
        // Check if no answer was provided
        if (empty($user_answers)) {
            // Use no_answer_feedback if available
            if (isset($question['no_answer_feedback']) && !empty($question['no_answer_feedback'])) {
                $feedback = wp_kses_post($question['no_answer_feedback']);
            } elseif (isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
                $feedback = wp_kses_post($question['incorrect_feedback']);
            }
            
            return array(
                'points_earned' => 0,
                'is_correct' => false,
                'feedback' => $feedback,
                'correct_indices' => $correct_indices
            );
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
            'feedback' => $feedback,
            'correct_indices' => $correct_indices
        );
    }
    
    /**
     * Check matching answer and calculate points
     * 
     * @deprecated 2.29 This method is no longer used for matching questions as they now work like multiple choice.
     *                  Kept for backward compatibility with legacy data that may still use the 'matches' array format.
     * 
     * Validates answers for matching-type questions where students match items
     * from a list (e.g., A-J) to complete statements. Each match item is checked
     * independently and awards 1 point if correct.
     * 
     * @param array $question The question data including 'matches' array with match items
     * @param array $all_answers All user answers from the form submission, keyed by field name
     * @param int $question_index The index of the question in the questions array
     * @return array {
     *     @type int $points_earned Number of points earned (1 per correct match)
     *     @type bool $is_correct True if all matches are correct, false otherwise
     *     @type string $feedback Feedback message based on correct/incorrect status
     *     @type array $correct_answers Array of correct answers indexed by match_index
     * }
     */
    private function check_matching_answer($question, $all_answers, $question_index) {
        $points_earned = 0;
        $feedback = '';
        $correct_answers = array();
        
        // Check if matches array exists
        if (!isset($question['matches']) || !is_array($question['matches'])) {
            return array(
                'points_earned' => 0,
                'is_correct' => false,
                'feedback' => '',
                'correct_answers' => array()
            );
        }
        
        // Check each match item
        foreach ($question['matches'] as $match_index => $match) {
            // Build the answer key for this match item
            // Using 'match_' prefix to avoid potential conflicts with other answer types
            $answer_key = 'match_' . $question_index . '_' . $match_index;
            
            // Store correct answer for this match
            $correct_answer_value = isset($match['correct_answer']) ? $match['correct_answer'] : '';
            $correct_answers[$match_index] = $correct_answer_value;
            
            // Check if user provided an answer for this match
            if (isset($all_answers[$answer_key])) {
                $user_answer = trim(strtoupper($all_answers[$answer_key]));
                $correct = trim(strtoupper($correct_answer_value));
                
                if ($user_answer === $correct) {
                    $points_earned += 1;
                }
            }
        }
        
        // Determine if fully correct (all matches correct)
        $total_matches = count($question['matches']);
        $is_correct = ($points_earned === $total_matches);
        
        // Get feedback
        if ($is_correct && isset($question['correct_feedback']) && !empty($question['correct_feedback'])) {
            $feedback = wp_kses_post($question['correct_feedback']);
        } elseif (!$is_correct && isset($question['incorrect_feedback']) && !empty($question['incorrect_feedback'])) {
            $feedback = wp_kses_post($question['incorrect_feedback']);
        }
        
        return array(
            'points_earned' => $points_earned,
            'is_correct' => $is_correct,
            'feedback' => $feedback,
            'correct_answers' => $correct_answers
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
            // Check for both integer and string serialization in course_ids array
            // Integer: i:123; String: s:3:"123";
            $int_pattern = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
            $str_pattern = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
            
            $quiz_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
            ", $course_id, $int_pattern, $str_pattern));
            
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
            'matching_classifying' => __('Matching and classifying questions', 'ielts-course-manager'),
            'multi_select' => __('Multi Select', 'ielts-course-manager'),
            'true_false' => __('True/False/Not Given', 'ielts-course-manager'),
            'headings' => __('Headings Questions', 'ielts-course-manager'),
            'short_answer' => __('Short Answer Questions', 'ielts-course-manager'),
            'sentence_completion' => __('Sentence Completion Questions', 'ielts-course-manager'),
            'summary_completion' => __('Summary Completion Questions', 'ielts-course-manager'),
            'dropdown_paragraph' => __('Dropdown Paragraph Questions', 'ielts-course-manager'),
            'table_completion' => __('Table Completion Questions', 'ielts-course-manager'),
            'labelling' => __('Labelling Style Questions', 'ielts-course-manager'),
            'locating_information' => __('Locating Information Questions', 'ielts-course-manager')
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
