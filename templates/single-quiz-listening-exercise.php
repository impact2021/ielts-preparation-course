<?php
/**
 * Template for displaying listening exercises
 * Two-column layout with audio player on left (WITH controls, autoplay after countdown) and questions on right
 * Shows transcript after submission, includes warning about official IELTS test
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

$audio_url = get_post_meta($quiz->ID, '_ielts_cm_audio_url', true);
$transcript = get_post_meta($quiz->ID, '_ielts_cm_transcript', true);
$timer_minutes = get_post_meta($quiz->ID, '_ielts_cm_timer_minutes', true);

// Calculate next and previous URLs for navigation
$next_url = '';
$prev_url = '';
if ($lesson_id) {
    global $wpdb;
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
        foreach ($quizzes as $q) {
            $all_items[] = array('post' => $q, 'order' => $q->menu_order);
        }
    }
    
    // Sort by menu order
    usort($all_items, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    // Find current quiz and get next/previous items
    $current_index = -1;
    foreach ($all_items as $index => $item) {
        if ($item['post']->ID == $quiz->ID) {
            $current_index = $index;
            break;
        }
    }
    
    // If there's a next item in this lesson, get its URL
    if ($current_index >= 0 && $current_index < count($all_items) - 1) {
        $next_post = $all_items[$current_index + 1]['post'];
        $next_url = get_permalink($next_post->ID);
    }
    
    // If there's a previous item in this lesson, get its URL
    if ($current_index > 0) {
        $prev_post = $all_items[$current_index - 1]['post'];
        $prev_url = get_permalink($prev_post->ID);
    }
}
?>

<div class="ielts-listening-exercise-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>" data-timer-minutes="<?php echo esc_attr($timer_minutes); ?>" data-next-url="<?php echo esc_attr($next_url); ?>" data-audio-url="<?php echo esc_attr($audio_url); ?>">
    
    <div class="quiz-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;"><?php echo esc_html($quiz->post_title); ?></h2>
        </div>
        
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
    </div>
    
    <?php if (!empty($questions) && is_user_logged_in()): ?>
        <form id="ielts-quiz-form" class="quiz-form">
            <?php 
            // Show timer bar if there's a timer OR a course link (or both)
            if ($timer_minutes > 0 || $course_id): ?>
            <div id="quiz-timer-fullscreen" class="quiz-timer-fullscreen">
                <div class="timer-left-section">
                    <?php if ($course_id): ?>
                    <?php 
                    // Show Previous page button if there's a previous item
                    if ($prev_url): ?>
                        <a href="<?php echo esc_url($prev_url); ?>" class="nav-page-link prev-page-link nav-link-clickable">
                            <?php _e('< Previous page', 'ielts-course-manager'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Show Next page button if there's a next item, otherwise Return to course
                    if ($next_url): ?>
                        <a href="<?php echo esc_url($next_url); ?>" class="nav-page-link next-page-link nav-link-clickable">
                            <?php _e('Next page >', 'ielts-course-manager'); ?>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-page-link return-course-link nav-link-clickable">
                            <?php _e('< Return to course', 'ielts-course-manager'); ?>
                        </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($timer_minutes > 0): ?>
                <div class="timer-content">
                    <strong><?php _e('Time Remaining:', 'ielts-course-manager'); ?></strong>
                    <span id="timer-display-fullscreen">--:--</span>
                </div>
                <?php endif; ?>
                <div class="timer-right-section">
                    <div class="font-size-controls">
                        <button type="button" class="font-size-btn font-decrease" title="<?php _e('Decrease font size', 'ielts-course-manager'); ?>">A-</button>
                        <button type="button" class="font-size-btn font-reset" title="<?php _e('Reset font size', 'ielts-course-manager'); ?>">A</button>
                        <button type="button" class="font-size-btn font-increase" title="<?php _e('Increase font size', 'ielts-course-manager'); ?>">A+</button>
                    </div>
                    <button type="submit" class="button button-primary quiz-submit-btn-top">
                        <?php _e('Submit for grading', 'ielts-course-manager'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            <div class="computer-based-container">
                <!-- Left Column: Audio Player & Countdown -->
                <div class="reading-column listening-audio-column">
                    <div class="reading-content listening-audio-content">
                        <!-- Countdown Display (shown before audio plays) -->
                        <div class="listening-countdown" id="listening-countdown">
                            <div class="countdown-text"><?php _e('Audio will start in:', 'ielts-course-manager'); ?></div>
                            <div class="countdown-number" id="countdown-number">1</div>
                        </div>
                        
                        <!-- Audio Player with controls visible -->
                        <div class="listening-audio-player listening-exercise-player" id="listening-audio-player" style="display: none;">
                            <div class="ielts-warning-notice">
                                <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                                <p><?php _e('Remember: In the official IELTS test you are NOT able to stop the audio recordings.', 'ielts-course-manager'); ?></p>
                            </div>
                            
                            <div class="audio-player-controls">
                                <audio id="listening-audio" controls preload="auto">
                                    <?php if ($audio_url): ?>
                                    <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                                    <?php endif; ?>
                                    <?php _e('Your browser does not support the audio element.', 'ielts-course-manager'); ?>
                                </audio>
                            </div>
                        </div>
                        
                        <!-- Transcript (shown after submission) -->
                        <div class="listening-transcript" id="listening-transcript" style="display: none;">
                            <h3><?php _e('Transcript', 'ielts-course-manager'); ?></h3>
                            <div class="transcript-content">
                                <?php echo wp_kses_post(wpautop($transcript)); ?>
                            </div>
                            
                            <!-- Audio controls shown after submission -->
                            <div class="transcript-audio-controls">
                                <audio controls>
                                    <?php if ($audio_url): ?>
                                    <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                                    <?php endif; ?>
                                    <?php _e('Your browser does not support the audio element.', 'ielts-course-manager'); ?>
                                </audio>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Questions -->
                <div class="questions-column">
                    <div class="questions-content">
                        <?php 
                        // Calculate display question numbers
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
                                $question_count = max(1, $correct_count);
                            } elseif ($q['type'] === 'summary_completion' || $q['type'] === 'table_completion') {
                                // Count number of fields
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
                                $dropdown_count = 0;
                                $paragraph_text = isset($q['question']) ? $q['question'] : '';
                                preg_match_all('/(\d+)\.\[([^\]]+)\]/i', $paragraph_text, $dropdown_matches);
                                if (!empty($dropdown_matches[0])) {
                                    $dropdown_count = count($dropdown_matches[0]);
                                }
                                $question_count = max(1, $dropdown_count);
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
                            <div class="quiz-question" id="question-<?php echo $index; ?>" data-display-start="<?php echo $display_nums['start']; ?>" data-display-end="<?php echo $display_nums['end']; ?>">
                                <?php if (!empty($question['instructions'])): ?>
                                    <div class="question-instructions"><?php echo wp_kses_post(wpautop($question['instructions'])); ?></div>
                                <?php endif; ?>
                                
                                <h4 class="question-number">
                                    <?php 
                                    if ($display_nums['start'] === $display_nums['end']) {
                                        printf(__('Question %d', 'ielts-course-manager'), $display_nums['start']);
                                    } else {
                                        printf(__('Questions %d – %d', 'ielts-course-manager'), $display_nums['start'], $display_nums['end']);
                                    }
                                    $display_points = $display_nums['count'];
                                    ?>
                                    <span class="question-points">(<?php printf(_n('%s point', '%s points', $display_points, 'ielts-course-manager'), $display_points); ?>)</span>
                                </h4>
                                
                                <?php
                                // Include question rendering from computer-based template
                                // Don't display question text for dropdown_paragraph, summary_completion, or table_completion
                                if ($question['type'] !== 'dropdown_paragraph' && $question['type'] !== 'summary_completion' && $question['type'] !== 'table_completion'):
                                ?>
                                <div class="question-text"><?php echo wp_kses_post(wpautop($question['question'])); ?></div>
                                <?php endif; ?>
                                
                                <?php
                                switch ($question['type']) {
                                    case 'multiple_choice':
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
                                        $options = array();
                                        if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                                            $options = $question['mc_options'];
                                        } elseif (isset($question['options']) && !empty($question['options'])) {
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
                                    case 'labelling':
                                        ?>
                                        <div class="question-answer">
                                            <input type="text" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   class="answer-input">
                                        </div>
                                        <?php
                                        break;
                                    
                                    case 'summary_completion':
                                    case 'table_completion':
                                        $summary_text = isset($question['question']) ? $question['question'] : '';
                                        
                                        $allowed_html = wp_kses_allowed_html('post');
                                        $allowed_html['input'] = array(
                                            'type' => true,
                                            'name' => true,
                                            'class' => true,
                                            'data-field-num' => true,
                                            'data-answer-num' => true,
                                        );
                                        
                                        preg_match_all('/\[field\s+(\d+)\]/i', $summary_text, $field_matches);
                                        preg_match_all('/\[ANSWER\s+(\d+)\]/i', $summary_text, $answer_matches);
                                        
                                        if (!empty($field_matches[0])) {
                                            $processed_text = $summary_text;
                                            foreach ($field_matches[0] as $match_index => $placeholder) {
                                                $field_num = $field_matches[1][$match_index];
                                                $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_field_' . esc_attr($field_num) . '" class="answer-input-inline" data-field-num="' . esc_attr($field_num) . '" />';
                                                $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                            }
                                            echo '<div class="summary-completion-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                        } elseif (!empty($answer_matches[0])) {
                                            $processed_text = $summary_text;
                                            foreach ($answer_matches[0] as $match_index => $placeholder) {
                                                $answer_num = $answer_matches[1][$match_index];
                                                $input_field = '<input type="text" name="answer_' . esc_attr($index) . '_' . esc_attr($answer_num) . '" class="answer-input-inline" data-answer-num="' . esc_attr($answer_num) . '" />';
                                                $processed_text = str_replace($placeholder, $input_field, $processed_text);
                                            }
                                            echo '<div class="summary-completion-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                        } else {
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
                                        $paragraph_text = isset($question['question']) ? $question['question'] : '';
                                        preg_match_all('/(\d+)\.\[([^\]]+)\]/i', $paragraph_text, $matches);
                                        
                                        if (!empty($matches[0])) {
                                            $processed_text = $paragraph_text;
                                            foreach ($matches[0] as $match_index => $placeholder) {
                                                $dropdown_num = $matches[1][$match_index];
                                                $options_text = $matches[2][$match_index];
                                                $option_parts = preg_split('/\s+(?=[A-Z]:\s)/', $options_text);
                                                
                                                $select_field = '<select name="answer_' . esc_attr($index) . '_' . esc_attr($dropdown_num) . '" class="answer-select-inline" data-dropdown-num="' . esc_attr($dropdown_num) . '">';
                                                $select_field .= '<option value="">-</option>';
                                                
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
                                            echo '<div class="dropdown-paragraph-text">' . wp_kses_post(wpautop($paragraph_text)) . '</div>';
                                        }
                                        break;
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Navigation: Jump to Questions -->
            <div class="question-navigation">
                <div class="question-buttons">
                    <?php 
                    foreach ($questions as $index => $question): 
                        $display_nums = $question_display_numbers[$index];
                        for ($btn_num = $display_nums['start']; $btn_num <= $display_nums['end']; $btn_num++):
                    ?>
                        <button type="button" class="question-nav-btn" data-question="<?php echo $index; ?>" data-display-number="<?php echo $btn_num; ?>">
                            <?php echo $btn_num; ?>
                        </button>
                    <?php 
                        endfor;
                    endforeach; 
                    ?>
                </div>
            </div>
        </form>
        
        <div id="quiz-result" class="quiz-result" style="display: none;"></div>
        
    <?php elseif (!empty($questions) && !is_user_logged_in()): ?>
        <div class="quiz-login-notice">
            <p><?php _e('Please log in to take this quiz.', 'ielts-course-manager'); ?></p>
            <a href="<?php echo wp_login_url(get_permalink($quiz->ID)); ?>" class="button button-primary">
                <?php _e('Login', 'ielts-course-manager'); ?>
            </a>
        </div>
    <?php else: ?>
        <p><?php _e('No questions available for this quiz.', 'ielts-course-manager'); ?></p>
    <?php endif; ?>
</div>
