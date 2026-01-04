<?php
/**
 * Template for displaying single quiz in shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_array($questions)) {
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
        
        <?php if ($timer_minutes > 0): ?>
        <div class="quiz-info">
            <p>
                <strong><?php _e('Time Limit:', 'ielts-course-manager'); ?></strong>
                <?php echo intval($timer_minutes); ?> <?php _e('minutes', 'ielts-course-manager'); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <?php if ($timer_minutes > 0 && !empty($questions) && is_user_logged_in()): ?>
        <div id="quiz-timer" class="quiz-timer">
            <strong><?php _e('Time Remaining:', 'ielts-course-manager'); ?></strong>
            <span id="timer-display">--:--</span>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (is_user_logged_in()): ?>
    <!-- Previous Attempts Section -->
    <div class="quiz-attempts-section">
        <h3 class="attempts-toggle" style="cursor: pointer; user-select: none;">
            <span class="dashicons dashicons-clipboard"></span>
            <?php _e('Previous Attempts', 'ielts-course-manager'); ?>
            <span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
        </h3>
        <div class="attempts-content" style="display: none;">
            <div class="attempts-loading">
                <?php _e('Loading your previous attempts...', 'ielts-course-manager'); ?>
            </div>
            <div class="attempts-list"></div>
        </div>
    </div>
    <?php endif; ?>
    
    <form id="ielts-quiz-form" class="quiz-form">
        <div class="quiz-questions">
            <?php if (!empty($questions)): ?>
                <?php 
                // Get starting question number (default is 1)
                $starting_question_number = get_post_meta($quiz->ID, '_ielts_cm_starting_question_number', true);
                if (!$starting_question_number) {
                    $starting_question_number = 1;
                }
                
                // Calculate display question numbers for multi-select and matching questions
                $display_question_number = intval($starting_question_number);
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
                    } elseif ($q['type'] === 'summary_completion') {
                        // For summary completion, count number of fields
                        $field_count = 0;
                        if (isset($q['summary_fields']) && is_array($q['summary_fields'])) {
                            $field_count = count($q['summary_fields']);
                        } else {
                            // Parse from question text
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
                        // For dropdown paragraph, count number of dropdowns
                        $dropdown_count = 0;
                        $paragraph_text = isset($q['question']) ? $q['question'] : '';
                        preg_match_all('/(\d+)\.\[([^\]]+)\]/i', $paragraph_text, $dropdown_matches);
                        if (!empty($dropdown_matches[0])) {
                            $dropdown_count = count($dropdown_matches[0]);
                        }
                        $question_count = max(1, $dropdown_count);
                    } elseif ($q['type'] === 'table_completion') {
                        // For table completion, count number of fields
                        $field_count = 0;
                        if (isset($q['summary_fields']) && is_array($q['summary_fields'])) {
                            $field_count = count($q['summary_fields']);
                        } else {
                            // Parse from question text
                            $question_text = isset($q['question']) ? $q['question'] : '';
                            preg_match_all('/\[field\s+(\d+)\]/i', $question_text, $field_matches);
                            if (!empty($field_matches[1])) {
                                $field_count = count(array_unique($field_matches[1]));
                            }
                        }
                        $question_count = max(1, $field_count);
                    } elseif ($q['type'] === 'closed_question') {
                        // For closed question, count number of correct answers
                        $correct_answer_count = isset($q['correct_answer_count']) ? intval($q['correct_answer_count']) : 1;
                        $question_count = max(1, $correct_answer_count);
                    } elseif ($q['type'] === 'open_question') {
                        // For open question, count number of fields
                        $field_count = isset($q['field_count']) ? intval($q['field_count']) : 1;
                        $question_count = max(1, $field_count);
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
                            // For closed and open questions, show range differently
                            if ($question['type'] === 'closed_question' || $question['type'] === 'open_question') {
                                if ($display_nums['start'] === $display_nums['end']) {
                                    printf(__('Question %d', 'ielts-course-manager'), $display_nums['start']);
                                } elseif ($display_nums['count'] == 2) {
                                    printf(__('Questions %d and %d', 'ielts-course-manager'), $display_nums['start'], $display_nums['end']);
                                } else {
                                    printf(__('Questions %d-%d', 'ielts-course-manager'), $display_nums['start'], $display_nums['end']);
                                }
                                // Show points for closed and open questions too
                                $display_points = $display_nums['count'];
                                ?>
                                <span class="question-points">(<?php printf(_n('%s point', '%s points', $display_points, 'ielts-course-manager'), $display_points); ?>)</span>
                                <?php
                            } else {
                                if ($display_nums['start'] === $display_nums['end']) {
                                    printf(__('Question %d', 'ielts-course-manager'), $display_nums['start']);
                                } else {
                                    printf(__('Questions %d – %d', 'ielts-course-manager'), $display_nums['start'], $display_nums['end']);
                                }
                                // For multi-select and matching, show the actual number of sub-questions as points
                                $display_points = $display_nums['count'];
                                ?>
                                <span class="question-points">(<?php printf(_n('%s point', '%s points', $display_points, 'ielts-course-manager'), $display_points); ?>)</span>
                                <?php
                            }
                            
                            // Display question category if set
                            if (!empty($question['ielts_question_category'])) {
                                $category_labels = array(
                                    'multiple_choice_l' => __('Multiple Choice (L)', 'ielts-course-manager'),
                                    'matching_l' => __('Matching (L)', 'ielts-course-manager'),
                                    'plan_map_diagram_l' => __('Plan/Map/Diagram Labeling (L)', 'ielts-course-manager'),
                                    'form_completion_l' => __('Form/Note/Table/Flow-chart/Summary Completion (L)', 'ielts-course-manager'),
                                    'sentence_completion_l' => __('Sentence Completion (L)', 'ielts-course-manager'),
                                    'short_answer_l' => __('Short-Answer Questions (L)', 'ielts-course-manager'),
                                    'multiple_choice_r' => __('Multiple Choice (R)', 'ielts-course-manager'),
                                    'true_false_not_given' => __('True/False/Not Given (R)', 'ielts-course-manager'),
                                    'yes_no_not_given' => __('Yes/No/Not Given (R)', 'ielts-course-manager'),
                                    'matching_information' => __('Matching Information (R)', 'ielts-course-manager'),
                                    'matching_headings' => __('Matching Headings (R)', 'ielts-course-manager'),
                                    'matching_features' => __('Matching Features (R)', 'ielts-course-manager'),
                                    'sentence_completion_r' => __('Sentence Completion (R)', 'ielts-course-manager'),
                                    'summary_completion_r' => __('Summary/Note/Table/Flow-chart Completion (R)', 'ielts-course-manager'),
                                    'diagram_label_r' => __('Diagram Label Completion (R)', 'ielts-course-manager'),
                                    'short_answer_r' => __('Short-Answer Questions (R)', 'ielts-course-manager')
                                );
                                $category_label = isset($category_labels[$question['ielts_question_category']]) ? $category_labels[$question['ielts_question_category']] : '';
                                if ($category_label) {
                                    echo '<span class="question-category" style="float: right; font-size: 0.85em; font-weight: normal; color: #666;">' . esc_html($category_label) . '</span>';
                                }
                            }
                            ?>
                        </h4>
                        
                        <?php
                        // Don't display question text for types that render it themselves with inline inputs/dropdowns
                        $skip_question_text = array('dropdown_paragraph', 'summary_completion', 'table_completion');
                        
                        // For open_question, skip if it has placeholders (inline format)
                        if ($question['type'] === 'open_question') {
                            $q_text = isset($question['question']) ? $question['question'] : '';
                            $has_placeholders = (stripos($q_text, '[blank]') !== false) || (preg_match('/\[field\s+\d+\]/i', $q_text) > 0);
                            if ($has_placeholders) {
                                $skip_question_text[] = 'open_question';
                            }
                        }
                        
                        if (!in_array($question['type'], $skip_question_text)):
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
                                
                            case 'short_answer':
                            case 'sentence_completion':
                            case 'table_completion':
                                // Table completion - parse [field N] or [ANSWER N] placeholders and replace with inline inputs (same as summary completion)
                                // New format: [field 1], [field 2], etc.
                                // Legacy format: [ANSWER 1], [ANSWER 2], etc.
                                
                                // Get the question text without wpautop processing for inline inputs
                                $table_text = isset($question['question']) ? $question['question'] : '';
                                
                                // Allow input tags in addition to standard post tags
                                $allowed_html = wp_kses_allowed_html('post');
                                $allowed_html['input'] = array(
                                    'type' => true,
                                    'name' => true,
                                    'class' => true,
                                    'data-field-num' => true,
                                    'data-answer-num' => true,
                                );
                                
                                // Find all [field N] placeholders (new format)
                                preg_match_all('/\[field\s+(\d+)\]/i', $table_text, $field_matches);
                                
                                // Find all [ANSWER N] placeholders (legacy format)
                                preg_match_all('/\[ANSWER\s+(\d+)\]/i', $table_text, $answer_matches);
                                
                                if (!empty($field_matches[0])) {
                                    // New format - multiple inline answers with [field N] placeholders
                                    $processed_text = $table_text;
                                    foreach ($field_matches[0] as $match_index => $placeholder) {
                                        $field_num = $field_matches[1][$match_index];
                                        $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_field_' . esc_attr($field_num) . '" class="answer-input-inline" data-field-num="' . esc_attr($field_num) . '" />';
                                        $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                    }
                                    echo '<div class="table-completion-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                } elseif (!empty($answer_matches[0])) {
                                    // Legacy format - multiple inline answers with [ANSWER N] placeholders
                                    $processed_text = $table_text;
                                    foreach ($answer_matches[0] as $match_index => $placeholder) {
                                        $answer_num = $answer_matches[1][$match_index];
                                        $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_' . esc_attr($answer_num) . '" class="answer-input-inline" data-answer-num="' . esc_attr($answer_num) . '" />';
                                        $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                    }
                                    echo '<div class="table-completion-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                } else {
                                    // No placeholders - single answer input below question text
                                    ?>
                                    <div class="question-answer">
                                        <input type="text" 
                                               name="answer_<?php echo $index; ?>" 
                                               class="answer-input">
                                    </div>
                                    <?php
                                }
                                break;
                            case 'labelling':
                                ?>
                                <div class="question-answer">
                                    <input type="text" 
                                           name="answer_<?php echo $index; ?>" 
                                           class="answer-input">
                                </div>
                                <?php
                                break;
                                
                            case 'locating_information':
                                // Locating Information question type - independent implementation
                                $locating_options = array();
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $locating_options = $question['mc_options'];
                                } elseif (isset($question['options']) && !empty($question['options'])) {
                                    $option_lines = array_filter(explode("\n", $question['options']));
                                    foreach ($option_lines as $opt_text) {
                                        $locating_options[] = array('text' => trim($opt_text));
                                    }
                                }
                                
                                if (!empty($locating_options)):
                                ?>
                                <div class="question-options locating-information-options">
                                    <?php foreach ($locating_options as $opt_index => $option): ?>
                                        <label class="option-label locating-information-option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>"
                                                   class="locating-information-radio">
                                            <span><?php echo wp_kses_post(isset($option['text']) ? $option['text'] : $option); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                endif;
                                break;
                                
                            case 'summary_completion':
                                // Summary completion - parse [field N] or [ANSWER N] placeholders and replace with inline inputs
                                // New format: [field 1], [field 2], etc.
                                // Legacy format: [ANSWER 1], [ANSWER 2], etc.
                                
                                // Get the question text without wpautop processing for inline inputs
                                $summary_text = isset($question['question']) ? $question['question'] : '';
                                
                                // Allow input tags in addition to standard post tags
                                $allowed_html = wp_kses_allowed_html('post');
                                $allowed_html['input'] = array(
                                    'type' => true,
                                    'name' => true,
                                    'class' => true,
                                    'data-field-num' => true,
                                    'data-answer-num' => true,
                                );
                                
                                // Find all [field N] placeholders (new format)
                                preg_match_all('/\[field\s+(\d+)\]/i', $summary_text, $field_matches);
                                
                                // Find all [ANSWER N] placeholders (legacy format)
                                preg_match_all('/\[ANSWER\s+(\d+)\]/i', $summary_text, $answer_matches);
                                
                                if (!empty($field_matches[0])) {
                                    // New format - multiple inline answers with [field N] placeholders
                                    $processed_text = $summary_text;
                                    foreach ($field_matches[0] as $match_index => $placeholder) {
                                        $field_num = $field_matches[1][$match_index];
                                        $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_field_' . esc_attr($field_num) . '" class="answer-input-inline" data-field-num="' . esc_attr($field_num) . '" />';
                                        $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                    }
                                    echo '<div class="summary-completion-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                } elseif (!empty($answer_matches[0])) {
                                    // Legacy format - multiple inline answers with [ANSWER N] placeholders
                                    $processed_text = $summary_text;
                                    foreach ($answer_matches[0] as $match_index => $placeholder) {
                                        $answer_num = $answer_matches[1][$match_index];
                                        $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_' . esc_attr($answer_num) . '" class="answer-input-inline" data-answer-num="' . esc_attr($answer_num) . '" />';
                                        $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                    }
                                    echo '<div class="summary-completion-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                } else {
                                    // No placeholders - single answer input below question text
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
                                                $select_field .= '<option value="' . esc_attr($letter) . '">' . esc_html($option_text) . '</option>';
                                            }
                                        }
                                        
                                        $select_field .= '</select>';
                                        $processed_text = str_replace($placeholder, $select_field, $processed_text);
                                    }
                                    // Allow select and option tags in addition to standard post tags
                                    $allowed_html = wp_kses_allowed_html('post');
                                    $allowed_html['select'] = array(
                                        'name' => true,
                                        'class' => true,
                                        'data-dropdown-num' => true,
                                    );
                                    $allowed_html['option'] = array(
                                        'value' => true,
                                        'selected' => true,
                                    );
                                    echo '<div class="dropdown-paragraph-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                } else {
                                    // No valid placeholders found - show question text as-is
                                    echo '<div class="dropdown-paragraph-text">' . wp_kses_post(wpautop($paragraph_text)) . '</div>';
                                }
                                break;
                            
                            case 'headings':
                                // Headings question type - independent implementation
                                $headings_options = array();
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $headings_options = $question['mc_options'];
                                } elseif (isset($question['options']) && !empty($question['options'])) {
                                    $option_lines = array_filter(explode("\n", $question['options']));
                                    foreach ($option_lines as $opt_text) {
                                        $headings_options[] = array('text' => trim($opt_text));
                                    }
                                }
                                
                                if (!empty($headings_options)):
                                ?>
                                <div class="question-options headings-options">
                                    <?php foreach ($headings_options as $opt_index => $option): ?>
                                        <label class="option-label headings-option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>"
                                                   class="headings-radio">
                                            <span><?php echo wp_kses_post(isset($option['text']) ? $option['text'] : $option); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                endif;
                                break;
                                
                            case 'matching_classifying':
                                // Matching/Classifying question type - independent implementation
                                $classifying_options = array();
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $classifying_options = $question['mc_options'];
                                } elseif (isset($question['options']) && !empty($question['options'])) {
                                    $option_lines = array_filter(explode("\n", $question['options']));
                                    foreach ($option_lines as $opt_text) {
                                        $classifying_options[] = array('text' => trim($opt_text));
                                    }
                                }
                                
                                if (!empty($classifying_options)):
                                ?>
                                <div class="question-options matching-classifying-options">
                                    <?php foreach ($classifying_options as $opt_index => $option): ?>
                                        <label class="option-label matching-classifying-option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>"
                                                   class="matching-classifying-radio">
                                            <span><?php echo wp_kses_post(isset($option['text']) ? $option['text'] : $option); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                endif;
                                break;
                                
                            case 'matching':
                                // Matching question type - independent implementation
                                $matching_options = array();
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $matching_options = $question['mc_options'];
                                } elseif (isset($question['options']) && !empty($question['options'])) {
                                    $option_lines = array_filter(explode("\n", $question['options']));
                                    foreach ($option_lines as $opt_text) {
                                        $matching_options[] = array('text' => trim($opt_text));
                                    }
                                }
                                
                                if (!empty($matching_options)):
                                ?>
                                <div class="question-options matching-options">
                                    <?php foreach ($matching_options as $opt_index => $option): ?>
                                        <label class="option-label matching-option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>"
                                                   class="matching-radio">
                                            <span><?php echo wp_kses_post(isset($option['text']) ? $option['text'] : $option); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                endif;
                                break;
                                
                            case 'closed_question':
                                // Closed Question - Multiple choice with configurable number of correct answers
                                // correct_answer_count determines how many question numbers this covers
                                $options = array();
                                if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                    $options = $question['mc_options'];
                                } elseif (isset($question['options']) && !empty($question['options'])) {
                                    $option_lines = array_filter(explode("\n", $question['options']));
                                    foreach ($option_lines as $opt_text) {
                                        $options[] = array('text' => trim($opt_text));
                                    }
                                }
                                
                                $correct_answer_count = isset($question['correct_answer_count']) ? intval($question['correct_answer_count']) : 1;
                                $is_multi_select = $correct_answer_count > 1;
                                
                                if (!empty($options)):
                                ?>
                                <div class="question-options closed-question-options" data-correct-count="<?php echo $correct_answer_count; ?>">
                                    <?php foreach ($options as $opt_index => $option): ?>
                                        <label class="option-label">
                                            <?php if ($is_multi_select): ?>
                                                <input type="checkbox" 
                                                       name="answer_<?php echo $index; ?>[]" 
                                                       value="<?php echo $opt_index; ?>"
                                                       class="closed-question-checkbox">
                                            <?php else: ?>
                                                <input type="radio" 
                                                       name="answer_<?php echo $index; ?>" 
                                                       value="<?php echo $opt_index; ?>"
                                                       class="closed-question-radio">
                                            <?php endif; ?>
                                            <?php 
                                            // Only show letter prefix if show_option_letters is true (or not set for backward compatibility)
                                            $show_letters = !isset($question['show_option_letters']) || $question['show_option_letters'];
                                            if ($show_letters): 
                                            ?>
                                                <span class="option-letter"><?php echo esc_html(chr(65 + $opt_index)); ?>:</span> 
                                            <?php endif; ?>
                                            <span><?php echo esc_html(isset($option['text']) ? $option['text'] : $option); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                endif;
                                break;
                                
                            case 'open_question':
                                // Open Question - Text input with configurable number of fields
                                // Supports two formats:
                                // 1. Inline blanks: "To complete a [blank] question..." or "Use [field 1] and [field 2]"
                                // 2. Separate inputs: Question text followed by labeled answer fields
                                
                                $field_count = isset($question['field_count']) ? intval($question['field_count']) : 1;
                                $question_text = isset($question['question']) ? $question['question'] : '';
                                
                                // Check for [blank] or [field N] placeholders
                                $has_blank_placeholders = (stripos($question_text, '[blank]') !== false);
                                $has_field_placeholders = (preg_match('/\[field\s+\d+\]/i', $question_text) > 0);
                                
                                if ($has_blank_placeholders || $has_field_placeholders) {
                                    // Inline format - replace placeholders with input fields
                                    $allowed_html = wp_kses_allowed_html('post');
                                    $allowed_html['input'] = array(
                                        'type' => true,
                                        'name' => true,
                                        'class' => true,
                                        'data-field-num' => true,
                                    );
                                    
                                    $processed_text = $question_text;
                                    
                                    if ($has_blank_placeholders) {
                                        // Replace [blank] placeholders sequentially
                                        $field_num = 1;
                                        while (stripos($processed_text, '[blank]') !== false && $field_num <= $field_count) {
                                            $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_field_' . esc_attr($field_num) . '" class="answer-input-inline open-question-input" data-field-num="' . esc_attr($field_num) . '" />';
                                            $processed_text = preg_replace('/\[blank\]/i', $input_field, $processed_text, 1);
                                            $field_num++;
                                        }
                                    } elseif ($has_field_placeholders) {
                                        // Replace [field N] placeholders
                                        preg_match_all('/\[field\s+(\d+)\]/i', $processed_text, $field_matches);
                                        foreach ($field_matches[0] as $match_index => $placeholder) {
                                            $field_num = $field_matches[1][$match_index];
                                            $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_field_' . esc_attr($field_num) . '" class="answer-input-inline open-question-input" data-field-num="' . esc_attr($field_num) . '" />';
                                            $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                        }
                                    }
                                    
                                    echo '<div class="open-question-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                } else {
                                    // Separate format - show labeled input fields (question text already displayed above)
                                    ?>
                                    <div class="open-question-fields">
                                        <?php for ($field_num = 1; $field_num <= $field_count; $field_num++): ?>
                                            <div class="open-question-field">
                                                <label>
                                                    <?php 
                                                    if ($field_count > 1) {
                                                        printf(__('Answer %d:', 'ielts-course-manager'), $display_nums['start'] + $field_num - 1);
                                                    } else {
                                                        _e('Answer:', 'ielts-course-manager');
                                                    }
                                                    ?>
                                                    <input type="text" 
                                                           name="answer_<?php echo $index; ?>_field_<?php echo $field_num; ?>" 
                                                           class="answer-input open-question-input"
                                                           data-field-num="<?php echo $field_num; ?>">
                                                </label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <?php
                                }
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
                <div class="nav-center">
                    <?php if ($course_id): ?>
                        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-link nav-back-to-course">
                            <span class="nav-label">
                                <small><?php _e('Back to', 'ielts-course-manager'); ?></small>
                                <strong><?php _e('Course', 'ielts-course-manager'); ?></strong>
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
                align-items: center;
                margin-top: 40px;
                padding-top: 30px;
                border-top: 2px solid #e0e0e0;
                gap: 15px;
            }
            .ielts-navigation .nav-prev {
                flex: 1;
            }
            .ielts-navigation .nav-center {
                flex: 0 0 auto;
                text-align: center;
            }
            .ielts-navigation .nav-next {
                flex: 1;
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
            .ielts-navigation .nav-back-to-course {
                background: #0073aa;
                color: white;
            }
            .ielts-navigation .nav-back-to-course:hover {
                background: #005a87;
            }
            .ielts-navigation .nav-back-to-course .nav-label small,
            .ielts-navigation .nav-back-to-course .nav-label strong {
                color: white;
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
            .ielts-navigation .nav-center .nav-label {
                align-items: center;
            }
            @media (max-width: 768px) {
                .ielts-navigation {
                    flex-direction: column;
                    gap: 10px;
                }
                .ielts-navigation .nav-prev,
                .ielts-navigation .nav-center,
                .ielts-navigation .nav-next {
                    width: 100%;
                    text-align: center;
                }
                .ielts-navigation .nav-label {
                    align-items: center !important;
                }
            }
            </style>
        <?php endif; ?>
    <?php } ?>
</div>
