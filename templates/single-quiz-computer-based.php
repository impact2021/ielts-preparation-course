<?php
/**
 * Template for displaying single quiz in computer-based IELTS layout
 * Two-column layout with reading text on left and questions on right
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

$reading_texts = get_post_meta($quiz->ID, '_ielts_cm_reading_texts', true);
if (!$reading_texts) {
    $reading_texts = array();
}
$timer_minutes = get_post_meta($quiz->ID, '_ielts_cm_timer_minutes', true);
$open_as_popup = get_post_meta($quiz->ID, '_ielts_cm_open_as_popup', true);
// Check if we're in fullscreen mode
$is_fullscreen = isset($_GET['fullscreen']) && $_GET['fullscreen'] === '1';

// Calculate next URL for navigation (same logic as in quiz-handler)
$next_url = '';
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
    
    // Find current quiz and get next item
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
        // For CBT quizzes with fullscreen enabled, add fullscreen parameter
        if ($next_post->post_type === 'ielts_quiz') {
            $next_layout_type = get_post_meta($next_post->ID, '_ielts_cm_layout_type', true);
            $next_open_as_popup = get_post_meta($next_post->ID, '_ielts_cm_open_as_popup', true);
            if ($next_layout_type === 'computer_based' && $next_open_as_popup) {
                $next_url = add_query_arg('fullscreen', '1', get_permalink($next_post->ID));
            } else {
                $next_url = get_permalink($next_post->ID);
            }
        } else {
            $next_url = get_permalink($next_post->ID);
        }
    }
}
?>

<div class="ielts-computer-based-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>" data-timer-minutes="<?php echo esc_attr($timer_minutes); ?>" data-next-url="<?php echo esc_attr($next_url); ?>">
    <?php 
    // Show fullscreen notice only if popup is enabled AND we're not already in fullscreen mode
    $show_fullscreen_notice = $open_as_popup && !$is_fullscreen;
    ?>
    
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
    
    <?php if ($show_fullscreen_notice): ?>
        <!-- Force fullscreen mode for CBT tests (when popup is enabled) -->
        <div class="cbt-fullscreen-notice" style="text-align: center; padding: 40px 20px; background: #f9f9f9; border: 2px solid #0073aa; border-radius: 8px; margin: 20px 0;">
            <p style="font-size: 1.2em; margin-bottom: 20px; color: #333;">
                <?php _e('This computer-based test must be viewed in fullscreen mode for the best experience.', 'ielts-course-manager'); ?>
            </p>
            <a href="<?php echo add_query_arg('fullscreen', '1', get_permalink($quiz->ID)); ?>" class="button button-primary button-large ielts-fullscreen-btn" style="font-size: 1.1em; padding: 12px 30px; text-decoration: none;">
                <span class="dashicons dashicons-fullscreen-alt" style="vertical-align: middle; font-size: 1.2em;"></span>
                <?php _e('Open in Fullscreen', 'ielts-course-manager'); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($questions) && is_user_logged_in()): ?>
        <form id="ielts-quiz-form" class="quiz-form" style="<?php echo $show_fullscreen_notice ? 'display:none;' : ''; ?>">
            <?php 
            // Show timer bar if there's a timer OR a course link (or both)
            // This ensures the return to course link is always visible when available
            if ($timer_minutes > 0 || $course_id): ?>
            <div id="quiz-timer-fullscreen" class="quiz-timer-fullscreen">
                <div class="timer-left-section">
                    <?php if ($course_id): ?>
                    <?php 
                    // Use next_url if available, otherwise return to course
                    $return_link_url = $next_url ? $next_url : get_permalink($course_id);
                    $return_link_text = $next_url ? __('Next page >', 'ielts-course-manager') : __('< Return to course', 'ielts-course-manager');
                    ?>
                    <a href="<?php echo esc_url($return_link_url); ?>" class="return-to-course-link" id="return-to-course-link">
                        <?php echo esc_html($return_link_text); ?>
                    </a>
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
                <!-- Left Column: Reading Texts -->
                <div class="reading-column">
                    <div class="reading-content">
                        <button type="button" class="clear-highlights-btn" style="display:none;">
                            <?php _e('Clear', 'ielts-course-manager'); ?>
                        </button>
                        <?php if (!empty($reading_texts)): ?>
                            <?php foreach ($reading_texts as $index => $text): ?>
                                <div class="reading-text-section" id="reading-text-<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display:none;' : ''; ?>">
                                    <?php if (!empty($text['title'])): ?>
                                        <h3 class="reading-title"><?php echo esc_html($text['title']); ?></h3>
                                    <?php endif; ?>
                                    <div class="reading-text">
                                        <?php echo wp_kses_post(wpautop($text['content'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="reading-text-section">
                                <p class="no-reading-text"><?php _e('No reading text provided for this exercise.', 'ielts-course-manager'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column: Questions -->
                <div class="questions-column">
                    <div class="questions-content">
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
                            <div class="quiz-question" id="question-<?php echo $index; ?>" data-reading-text-id="<?php echo esc_attr($question['reading_text_id'] ?? ''); ?>" data-display-start="<?php echo $display_nums['start']; ?>" data-display-end="<?php echo $display_nums['end']; ?>">
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
                    // Group questions by reading passage
                    $last_reading_text_id = null;
                    foreach ($questions as $index => $question): 
                        $current_reading_text_id = isset($question['reading_text_id']) ? $question['reading_text_id'] : null;
                        
                        // If reading text changed, show separator
                        if ($current_reading_text_id !== $last_reading_text_id && $current_reading_text_id !== null && !empty($reading_texts) && $index > 0):
                    ?>
                        <span class="passage-separator">|</span>
                    <?php 
                        endif;
                        $last_reading_text_id = $current_reading_text_id;
                        
                        // Get display numbers for this question
                        $display_nums = $question_display_numbers[$index];
                        
                        // For multi-select with multiple correct answers, show multiple buttons
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
        <?php endif; ?>
    <?php } ?>
</div>
