<?php
/**
 * Template for displaying single quiz in shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!$questions) {
    $questions = array();
}

$user_id = get_current_user_id();
$pass_percentage = get_post_meta($quiz->ID, '_ielts_cm_pass_percentage', true);
if (!$pass_percentage) {
    $pass_percentage = 70;
}
$timer_minutes = get_post_meta($quiz->ID, '_ielts_cm_timer_minutes', true);
?>

<div class="ielts-single-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>" data-timer-minutes="<?php echo esc_attr($timer_minutes); ?>">
    <div class="quiz-header">
        <h2><?php echo esc_html($quiz->post_title); ?></h2>
        
        <?php if ($course_id): ?>
            <div class="quiz-breadcrumb">
                <?php
                $course = get_post($course_id);
                if ($course):
                ?>
                    <a href="<?php echo get_permalink($course->ID); ?>">
                        <?php echo esc_html($course->post_title); ?>
                    </a>
                    <span class="separator">&raquo;</span>
                    
                    <?php if ($lesson_id): ?>
                        <?php $lesson = get_post($lesson_id); ?>
                        <a href="<?php echo get_permalink($lesson->ID); ?>">
                            <?php echo esc_html($lesson->post_title); ?>
                        </a>
                        <span class="separator">&raquo;</span>
                    <?php endif; ?>
                    
                    <span><?php echo esc_html($quiz->post_title); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="quiz-description">
            <?php 
            // Apply WordPress content filters to process embeds and shortcodes
            echo apply_filters('the_content', $quiz->post_content);
            ?>
        </div>
        
        <div class="quiz-info">
            <p>
                <strong><?php _e('Number of Questions:', 'ielts-course-manager'); ?></strong>
                <?php echo count($questions); ?>
            </p>
            <?php if ($timer_minutes > 0): ?>
            <p>
                <strong><?php _e('Time Limit:', 'ielts-course-manager'); ?></strong>
                <?php echo intval($timer_minutes); ?> <?php _e('minutes', 'ielts-course-manager'); ?>
            </p>
            <?php endif; ?>
        </div>
        
        <?php if ($timer_minutes > 0 && !empty($questions) && is_user_logged_in()): ?>
        <div id="quiz-timer" class="quiz-timer">
            <strong><?php _e('Time Remaining:', 'ielts-course-manager'); ?></strong>
            <span id="timer-display">--:--</span>
        </div>
        <?php endif; ?>
    </div>
    
    <form id="ielts-quiz-form" class="quiz-form">
        <div class="quiz-questions">
            <?php if (!empty($questions)): ?>
                <?php 
                // Calculate display question numbers for multi-select and matching questions
                $display_question_number = 1;
                $question_display_numbers = array();
                foreach ($questions as $idx => $q) {
                    $start_num = $display_question_number;
                    // For multi-select, count number of correct answers
                    if ($q['type'] === 'multi_select' && isset($q['mc_options']) && is_array($q['mc_options'])) {
                        $correct_count = 0;
                        foreach ($q['mc_options'] as $opt) {
                            if (!empty($opt['is_correct'])) {
                                $correct_count++;
                            }
                        }
                        // Multi-select counts as multiple questions based on correct answers
                        $question_count = max(1, $correct_count);
                    } else {
                        $question_count = 1;
                    }
                    $end_num = $start_num + $question_count - 1;
                    $question_display_numbers[$idx] = array(
                        'start' => $start_num,
                        'end' => $end_num,
                        'count' => $question_count
                    );
                    $display_question_number += $question_count;
                }
                
                foreach ($questions as $index => $question): 
                    $display_nums = $question_display_numbers[$index];
                ?>
                    <div class="quiz-question" id="question-<?php echo $index; ?>">
                        <?php if (!empty($question['instructions'])): ?>
                            <div class="question-instructions"><?php echo wp_kses_post(wpautop($question['instructions'])); ?></div>
                        <?php endif; ?>
                        
                        <h4>
                            <?php 
                            if ($display_nums['start'] === $display_nums['end']) {
                                printf(__('Question %d', 'ielts-course-manager'), $display_nums['start']);
                            } else {
                                printf(__('Questions %d – %d', 'ielts-course-manager'), $display_nums['start'], $display_nums['end']);
                            }
                            // For multi-select and matching, show the actual number of sub-questions as points
                            $display_points = $display_nums['count'];
                            ?>
                            <span class="question-points">(<?php printf(_n('%s point', '%s points', $display_points, 'ielts-course-manager'), $display_points); ?>)</span>
                        </h4>
                        
                        <?php
                        // Don't display question text for dropdown_paragraph - it renders its own formatted version
                        if ($question['type'] !== 'dropdown_paragraph'):
                        ?>
                        <div class="question-text"><?php echo wp_kses_post(wpautop($question['question'])); ?></div>
                        <?php endif; ?>
                        
                        <?php
                        switch ($question['type']) {
                            case 'multiple_choice':
                                // Support both new mc_options format and legacy options format
                                $options = array();
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $options = $question['mc_options'];
                                } elseif (isset($question['options']) && !empty($question['options'])) {
                                    $option_lines = array_filter(explode("\n", $question['options']));
                                    foreach ($option_lines as $opt_text) {
                                        $options[] = array('text' => trim($opt_text));
                                    }
                                }
                                ?>
                                <div class="question-options">
                                    <?php foreach ($options as $opt_index => $option): ?>
                                        <label class="option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>">
                                            <span><?php echo esc_html(isset($option['text']) ? $option['text'] : $option); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                break;
                                
                            case 'multi_select':
                                // Multi-select question type
                                $options = array();
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $options = $question['mc_options'];
                                } elseif (isset($question['options']) && !empty($question['options'])) {
                                    // Fallback for legacy format
                                    $option_lines = array_filter(explode("\n", $question['options']));
                                    foreach ($option_lines as $opt_text) {
                                        $options[] = array('text' => trim($opt_text), 'is_correct' => false);
                                    }
                                }
                                $max_selections = isset($question['max_selections']) ? intval($question['max_selections']) : 2;
                                ?>
                                <div class="question-options multi-select-options" data-max-selections="<?php echo $max_selections; ?>">
                                    <?php foreach ($options as $opt_index => $option): ?>
                                        <label class="option-label">
                                            <input type="checkbox" 
                                                   name="answer_<?php echo $index; ?>[]" 
                                                   value="<?php echo $opt_index; ?>"
                                                   class="multi-select-checkbox">
                                            <span><?php echo esc_html($option['text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <small class="multi-select-hint"><?php printf(__('Select up to %d options', 'ielts-course-manager'), $max_selections); ?></small>
                                <?php
                                break;
                                
                            case 'true_false':
                                ?>
                                <div class="question-options">
                                    <label class="option-label">
                                        <input type="radio" 
                                               name="answer_<?php echo $index; ?>" 
                                               value="true">
                                        <span><?php _e('True', 'ielts-course-manager'); ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" 
                                               name="answer_<?php echo $index; ?>" 
                                               value="false">
                                        <span><?php _e('False', 'ielts-course-manager'); ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" 
                                               name="answer_<?php echo $index; ?>" 
                                               value="not_given">
                                        <span><?php _e('Not Given', 'ielts-course-manager'); ?></span>
                                    </label>
                                </div>
                                <?php
                                break;
                                
                            case 'fill_blank':
                            case 'short_answer':
                            case 'sentence_completion':
                            case 'table_completion':
                            case 'labelling':
                            case 'locating_information':
                                ?>
                                <div class="question-answer">
                                    <input type="text" 
                                           name="answer_<?php echo $index; ?>" 
                                           class="answer-input">
                                </div>
                                <?php
                                break;
                                
                            case 'summary_completion':
                                // Summary completion - parse [ANSWER N] placeholders and replace with inline inputs
                                // Question text should contain placeholders like [ANSWER 1], [ANSWER 2], etc.
                                
                                // Get the question text without wpautop processing for inline inputs
                                $summary_text = isset($question['question']) ? $question['question'] : '';
                                
                                // Find all [ANSWER N] placeholders
                                preg_match_all('/\[ANSWER\s+(\d+)\]/i', $summary_text, $matches);
                                
                                if (!empty($matches[0])) {
                                    // Multiple inline answers - replace placeholders with input fields
                                    $processed_text = $summary_text;
                                    foreach ($matches[0] as $match_index => $placeholder) {
                                        $answer_num = $matches[1][$match_index];
                                        $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_' . esc_attr($answer_num) . '" class="answer-input-inline" data-answer-num="' . esc_attr($answer_num) . '" />';
                                        $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                    }
                                    echo '<div class="summary-completion-text">' . wp_kses_post(wpautop($processed_text)) . '</div>';
                                } else {
                                    // Legacy format - single answer input below question text
                                    ?>
                                    <div class="question-answer">
                                        <input type="text" 
                                               name="answer_<?php echo $index; ?>" 
                                               class="answer-input">
                                    </div>
                                    <?php
                                }
                                break;
                            
                            case 'dropdown_paragraph':
                                // Dropdown paragraph - parse N.[A: option1 B: option2] placeholders and replace with inline dropdowns
                                // Example: "I am writing to 1.[A: let you know B: inform you] that I will be unable to meet."
                                
                                $paragraph_text = isset($question['question']) ? $question['question'] : '';
                                
                                // Find all N.[A: option1 B: option2 C: option3] placeholders
                                // Pattern: number followed by period, then square bracket with options
                                preg_match_all('/(\d+)\.\[([^\]]+)\]/i', $paragraph_text, $matches);
                                
                                if (!empty($matches[0])) {
                                    // Multiple inline dropdowns - replace placeholders with select fields
                                    $processed_text = $paragraph_text;
                                    foreach ($matches[0] as $match_index => $placeholder) {
                                        $dropdown_num = $matches[1][$match_index];
                                        $options_text = $matches[2][$match_index];
                                        
                                        // Parse options: "A: option1 B: option2 C: option3"
                                        // Split by space followed by uppercase letter, colon, and space
                                        $option_parts = preg_split('/\s+(?=[A-Z]:\s)/', $options_text);
                                        
                                        // Build the select dropdown
                                        $select_field = '<select name="answer_' . esc_attr($index) . '_' . esc_attr($dropdown_num) . '" class="answer-select-inline" data-dropdown-num="' . esc_attr($dropdown_num) . '">';
                                        $select_field .= '<option value="">-</option>'; // Empty default option
                                        
                                        foreach ($option_parts as $option_part) {
                                            if (preg_match('/^([A-Z]):\s*(.+)$/i', trim($option_part), $opt_match)) {
                                                $letter = $opt_match[1];
                                                $option_text = trim($opt_match[2]);
                                                $select_field .= '<option value="' . esc_attr($letter) . '">' . esc_html($letter) . ': ' . esc_html($option_text) . '</option>';
                                            }
                                        }
                                        
                                        $select_field .= '</select>';
                                        $processed_text = str_replace($placeholder, $select_field, $processed_text);
                                    }
                                    echo '<div class="dropdown-paragraph-text">' . wp_kses_post(wpautop($processed_text)) . '</div>';
                                } else {
                                    // No valid placeholders found - show question text as-is
                                    echo '<div class="dropdown-paragraph-text">' . wp_kses_post(wpautop($paragraph_text)) . '</div>';
                                }
                                break;
                            
                            case 'headings':
                            case 'matching_classifying':
                                // These use multiple choice format
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $mc_options = $question['mc_options'];
                                } elseif (isset($question['options'])) {
                                    $options_array = explode("\n", $question['options']);
                                    $mc_options = array();
                                    foreach ($options_array as $idx => $option_text) {
                                        $mc_options[] = array('text' => trim($option_text));
                                    }
                                }
                                
                                if (!empty($mc_options)):
                                ?>
                                <div class="question-options">
                                    <?php foreach ($mc_options as $opt_index => $option): ?>
                                        <label class="option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>">
                                            <span><?php echo wp_kses_post($option['text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                endif;
                                break;
                                
                            case 'essay':
                                ?>
                                <div class="question-answer">
                                    <textarea name="answer_<?php echo $index; ?>" 
                                              class="answer-textarea" 
                                              rows="6"></textarea>
                                    <p class="essay-note">
                                        <?php _e('Note: Essay questions will be reviewed manually.', 'ielts-course-manager'); ?>
                                    </p>
                                </div>
                                <?php
                                break;
                                
                            case 'matching':
                                // Matching question type - now uses multiple choice format (radio buttons)
                                // Similar to matching_classifying, headings, and multiple_choice
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $mc_options = $question['mc_options'];
                                } elseif (isset($question['options'])) {
                                    $options_array = explode("\n", $question['options']);
                                    $mc_options = array();
                                    foreach ($options_array as $idx => $option_text) {
                                        $mc_options[] = array('text' => trim($option_text));
                                    }
                                }
                                
                                if (!empty($mc_options)):
                                ?>
                                <div class="question-options">
                                    <?php foreach ($mc_options as $opt_index => $option): ?>
                                        <label class="option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>">
                                            <span><?php echo wp_kses_post($option['text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                endif;
                                break;
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No questions available for this quiz.', 'ielts-course-manager'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($questions) && is_user_logged_in()): ?>
            <div class="quiz-submit">
                <button type="submit" class="button button-primary">
                    <?php _e('Submit Quiz', 'ielts-course-manager'); ?>
                </button>
            </div>
        <?php elseif (!is_user_logged_in()): ?>
            <div class="quiz-login-notice">
                <p><?php _e('Please log in to take this quiz.', 'ielts-course-manager'); ?></p>
                <a href="<?php echo wp_login_url(get_permalink($quiz->ID)); ?>" class="button button-primary">
                    <?php _e('Login', 'ielts-course-manager'); ?>
                </a>
            </div>
        <?php endif; ?>
    </form>
    
    <div id="quiz-result" class="quiz-result" style="display: none;"></div>
    
    <?php
    // Previous/Next quiz navigation within the lesson
    if ($lesson_id) {
        global $wpdb;
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
        ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
        
        $all_quizzes = array();
        if (!empty($quiz_ids)) {
            $all_quizzes = get_posts(array(
                'post_type' => 'ielts_quiz',
                'posts_per_page' => -1,
                'post__in' => $quiz_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish'
            ));
        }
        
        $current_index = -1;
        foreach ($all_quizzes as $index => $q) {
            if ($q->ID == $quiz->ID) {
                $current_index = $index;
                break;
            }
        }
        
        $prev_quiz = ($current_index > 0) ? $all_quizzes[$current_index - 1] : null;
        $next_quiz = ($current_index >= 0 && $current_index < count($all_quizzes) - 1) ? $all_quizzes[$current_index + 1] : null;
        ?>
        
        <?php if ($prev_quiz || $next_quiz): ?>
            <div class="ielts-navigation">
                <div class="nav-prev">
                    <?php if ($prev_quiz): ?>
                        <a href="<?php echo get_permalink($prev_quiz->ID); ?>" class="nav-link">
                            <span class="nav-arrow">&laquo;</span>
                            <span class="nav-label">
                                <small><?php _e('Previous Exercise', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($prev_quiz->post_title); ?></strong>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-next">
                    <?php if ($next_quiz): ?>
                        <a href="<?php echo get_permalink($next_quiz->ID); ?>" class="nav-link">
                            <span class="nav-label">
                                <small><?php _e('Next Exercise', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($next_quiz->post_title); ?></strong>
                            </span>
                            <span class="nav-arrow">&raquo;</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <style>
            .ielts-navigation {
                display: flex;
                justify-content: space-between;
                margin-top: 40px;
                padding-top: 30px;
                border-top: 2px solid #e0e0e0;
            }
            .ielts-navigation .nav-prev {
                flex: 0 0 48%;
            }
            .ielts-navigation .nav-next {
                flex: 0 0 48%;
                text-align: right;
            }
            .ielts-navigation .nav-link {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 15px 20px;
                background: #f5f5f5;
                border-radius: 5px;
                text-decoration: none;
                color: #333;
                transition: all 0.3s ease;
            }
            .ielts-navigation .nav-link:hover {
                background: #e0e0e0;
                transform: translateY(-2px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .ielts-navigation .nav-arrow {
                font-size: 24px;
                color: #0073aa;
                font-weight: bold;
            }
            .ielts-navigation .nav-label {
                display: flex;
                flex-direction: column;
            }
            .ielts-navigation .nav-label small {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
            }
            .ielts-navigation .nav-label strong {
                font-size: 14px;
                color: #333;
                margin-top: 3px;
            }
            .ielts-navigation .nav-next .nav-label {
                align-items: flex-end;
            }
            </style>
        <?php endif; ?>
    <?php } ?>
</div>
