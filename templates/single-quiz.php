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
    
    <?php
    // Calculate Previous/Next items for navigation (needed for sticky nav)
    $prev_item = null;
    $next_item = null;
    $prev_label = '';
    $next_label = '';
    
    if ($lesson_id) {
        global $wpdb;
        
        // Check for both integer and string serialization in lesson_ids array
        $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
        
        // Get all resources (learning resources) for this lesson
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        // Get all quizzes (exercises) for this lesson
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
        ", $lesson_id, $int_pattern, $str_pattern));
        
        // Combine all content items (resources and quizzes)
        $all_content_items = array();
        
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
                $all_content_items[] = array('post' => $resource, 'order' => $resource->menu_order);
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
            foreach ($quizzes as $quiz_item) {
                $all_content_items[] = array('post' => $quiz_item, 'order' => $quiz_item->menu_order);
            }
        }
        
        // Sort by menu order
        usort($all_content_items, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        // Find current quiz and get previous/next items
        $current_index = -1;
        foreach ($all_content_items as $index => $item) {
            if ($item['post']->ID == $quiz->ID) {
                $current_index = $index;
                break;
            }
        }
        
        $prev_item = ($current_index > 0) ? $all_content_items[$current_index - 1]['post'] : null;
        $next_item = ($current_index >= 0 && $current_index < count($all_content_items) - 1) ? $all_content_items[$current_index + 1]['post'] : null;
        
        // Determine labels for previous/next items
        if ($prev_item) {
            $prev_label = __('Previous', 'ielts-course-manager');
        }
        if ($next_item) {
            $next_label = __('Next', 'ielts-course-manager');
        }
        
        // Check if this is the last item of the last lesson in the course (for completion message)
        $is_last_lesson = false;
        $next_lesson = null;
        if (!$next_item && $course_id && $lesson_id) {
            // Get all lessons in the course
            $int_pattern_course = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
            $str_pattern_course = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
            
            $all_lesson_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
            ", $course_id, $int_pattern_course, $str_pattern_course));
            
            if (!empty($all_lesson_ids)) {
                $all_lessons = get_posts(array(
                    'post_type' => 'ielts_lesson',
                    'posts_per_page' => -1,
                    'post__in' => $all_lesson_ids,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'post_status' => 'publish'
                ));
                
                // Check if current lesson is the last one AND we're on the last item within this lesson
                // (!$next_item already confirms we're on the last item in the lesson)
                if (!empty($all_lessons) && end($all_lessons)->ID == $lesson_id) {
                    $is_last_lesson = true;
                } else {
                    // Find the next lesson in the course
                    $current_lesson_index = -1;
                    foreach ($all_lessons as $index => $lesson) {
                        if ($lesson->ID == $lesson_id) {
                            $current_lesson_index = $index;
                            break;
                        }
                    }
                    
                    // If we found the current lesson and there's a next one, store it
                    if ($current_lesson_index >= 0 && $current_lesson_index < count($all_lessons) - 1) {
                        $next_lesson = $all_lessons[$current_lesson_index + 1];
                    }
                }
            }
        }
        
        // Find next unit if this is the last lesson
        $next_unit = null;
        $next_unit_label = __('Move on to next unit', 'ielts-course-manager');
        if ($is_last_lesson && $course_id) {
            // Get user's course group to filter units
            $user_id = get_current_user_id();
            $user = get_userdata($user_id);
            $is_admin = $user && in_array('administrator', $user->roles);
            $course_group = get_user_meta($user_id, 'iw_course_group', true);
            
            // Build query args
            $query_args = array(
                'post_type' => 'ielts_course',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'any'
            );
            
            // Only apply category filter for non-admin users with a valid course group
            if (!$is_admin && !empty($course_group)) {
                // Determine allowed categories based on course group
                $allowed_categories = array();
                switch ($course_group) {
                    case 'academic_module':
                        $allowed_categories = array('academic', 'english', 'academic-practice-tests');
                        break;
                    case 'general_module':
                        $allowed_categories = array('general', 'english', 'general-practice-tests');
                        break;
                    case 'general_english':
                        $allowed_categories = array('english');
                        break;
                    default:
                        // Unknown course group - don't show any next unit for safety
                        $allowed_categories = array();
                        break;
                }
                
                // Add category filter if we have allowed categories
                if (!empty($allowed_categories)) {
                    $query_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'ielts_course_category',
                            'field' => 'slug',
                            'terms' => $allowed_categories,
                            'operator' => 'IN'
                        )
                    );
                }
            }
            
            // Get all units (including drafts) ordered by menu_order to find position
            $all_units = get_posts($query_args);
            
            // Find the current unit and get the next published one
            $total_units = count($all_units);
            foreach ($all_units as $index => $unit) {
                if ($unit->ID == $course_id) {
                    // Look for the next published unit
                    for ($i = $index + 1; $i < $total_units; $i++) {
                        if (get_post_status($all_units[$i]->ID) === 'publish') {
                            $next_unit = $all_units[$i];
                            // Extract unit number from title (e.g., "Academic Unit 2" -> "Unit 2")
                            $sanitized_title = sanitize_text_field($next_unit->post_title);
                            if (preg_match('/Unit\s+(\d+)/i', $sanitized_title, $matches)) {
                                $next_unit_label = sprintf(__('Move to Unit %s', 'ielts-course-manager'), $matches[1]);
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        }
    }
    ?>
    
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
                    } elseif ($q['type'] === 'closed_question' || $q['type'] === 'closed_question_dropdown') {
                        // For closed question (including dropdown variant), count number of correct answers
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
                            // For closed and open questions (including dropdown variants), show range differently
                            if ($question['type'] === 'closed_question' || $question['type'] === 'closed_question_dropdown' || $question['type'] === 'open_question') {
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
                        
                        // For closed_question_dropdown, always skip since it renders inline dropdowns
                        if ($question['type'] === 'closed_question_dropdown') {
                            $skip_question_text[] = 'closed_question_dropdown';
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
                                
                            case 'closed_question_dropdown':
                                // Closed Question Dropdown - Multiple choice rendered as inline dropdowns
                                // Uses [dropdown] placeholder in question text, similar to open_question's [blank]
                                // Supports single or multiple dropdowns based on correct_answer_count
                                
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
                                $question_text = isset($question['question']) ? $question['question'] : '';
                                
                                if (!empty($options) && !empty($question_text)) {
                                    // Process question text to replace [dropdown] placeholders
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
                                    
                                    $processed_text = $question_text;
                                    $dropdown_num = 1;
                                    
                                    // Check if using numbered dropdown syntax (e.g., "1.[dropdown]", "2.[dropdown]")
                                    $has_numbered_dropdowns = preg_match('/\d+\.\s*\[dropdown\]/i', $question_text);
                                    
                                    // Replace each [dropdown] placeholder with a select element
                                    while ($dropdown_num <= $correct_answer_count) {
                                        if ($has_numbered_dropdowns) {
                                            // Check for numbered placeholder (e.g., "1.[dropdown]", "2.[dropdown]")
                                            $numbered_pattern = '/(\d+)\.\s*\[dropdown\]/i';
                                            if (!preg_match($numbered_pattern, $processed_text)) {
                                                break; // No more numbered placeholders to replace
                                            }
                                        } else {
                                            // Check for unnumbered placeholder
                                            if (stripos($processed_text, '[dropdown]') === false) {
                                                break; // No more placeholders to replace
                                            }
                                        }
                                        
                                        // Build the select dropdown
                                        $select_field = '<select name="answer_' . esc_attr($index) . '_' . esc_attr($dropdown_num) . '" class="answer-select-inline closed-question-dropdown" data-dropdown-num="' . esc_attr($dropdown_num) . '">';
                                        $select_field .= '<option value="">-</option>'; // Empty default option
                                        
                                        // Add all options to the dropdown
                                        foreach ($options as $opt_index => $option) {
                                            $option_text = isset($option['text']) ? $option['text'] : $option;
                                            // Skip empty options (they were preserved to maintain indices, but shouldn't be shown)
                                            if (empty($option_text)) {
                                                continue;
                                            }
                                            $select_field .= '<option value="' . esc_attr($opt_index) . '">' . esc_html($option_text) . '</option>';
                                        }
                                        
                                        $select_field .= '</select>';
                                        
                                        if ($has_numbered_dropdowns) {
                                            // Replace numbered placeholder, keeping the display number
                                            // This replaces "1.[dropdown]" with "1. <select>..."
                                            $new_text = preg_replace('/(\d+)\.\s*\[dropdown\]/i', '$1. ' . $select_field, $processed_text, 1);
                                        } else {
                                            // Replace the first occurrence of unnumbered [dropdown]
                                            $new_text = preg_replace('/\[dropdown\]/i', $select_field, $processed_text, 1);
                                        }
                                        
                                        if ($new_text === $processed_text) {
                                            // No replacement occurred, break to prevent infinite loop
                                            break;
                                        }
                                        $processed_text = $new_text;
                                        $dropdown_num++;
                                    }
                                    
                                    echo '<div class="closed-question-dropdown-text">' . wp_kses(wpautop($processed_text), $allowed_html) . '</div>';
                                } else {
                                    // Fallback: show question text as-is if no options or text
                                    echo '<div class="closed-question-dropdown-text">' . wp_kses_post(wpautop($question_text)) . '</div>';
                                }
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
            <!-- Sticky Bottom Navigation with Timer and Submit -->
            <div class="ielts-sticky-bottom-nav quiz-bottom-nav">
                <div class="nav-item nav-prev">
                    <?php if (isset($prev_item) && $prev_item): ?>
                        <a href="<?php echo get_permalink($prev_item->ID); ?>" class="nav-link">
                            <span class="nav-arrow">&laquo;</span>
                            <span class="nav-label">
                                <small><?php echo isset($prev_label) ? esc_html($prev_label) : __('Previous', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($prev_item->post_title); ?></strong>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-item nav-back-left">
                    <?php if ($lesson_id): ?>
                        <a href="<?php echo esc_url(get_permalink($lesson_id)); ?>" class="nav-link nav-back-to-lesson">
                            <span class="nav-label">
                                <small><?php _e('Back to the Lesson', 'ielts-course-manager'); ?></small>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-item nav-center">
                    <div class="quiz-center-controls">
                        <?php if ($timer_minutes > 0): ?>
                        <div class="timer-display">
                            <strong><?php _e('Time:', 'ielts-course-manager'); ?></strong>
                            <span id="timer-display-bottom">--:--</span>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="button button-primary quiz-submit-btn">
                            <?php _e('Submit', 'ielts-course-manager'); ?>
                        </button>
                    </div>
                </div>
                <div class="nav-item nav-back-right">
                    <?php if ($course_id): ?>
                        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-link nav-back-to-course">
                            <span class="nav-label">
                                <small><?php _e('Back to the Unit', 'ielts-course-manager'); ?></small>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-item nav-next">
                    <?php if (isset($next_item) && $next_item): ?>
                        <a href="<?php echo get_permalink($next_item->ID); ?>" class="nav-link">
                            <span class="nav-label">
                                <small><?php echo isset($next_label) ? esc_html($next_label) : __('Next', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($next_item->post_title); ?></strong>
                            </span>
                            <span class="nav-arrow">&raquo;</span>
                        </a>
                    <?php elseif (isset($next_lesson) && $next_lesson): ?>
                        <a href="<?php echo get_permalink($next_lesson->ID); ?>" class="nav-link">
                            <span class="nav-label">
                                <small><?php _e('Next Lesson', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($next_lesson->post_title); ?></strong>
                            </span>
                            <span class="nav-arrow">&raquo;</span>
                        </a>
                    <?php else: ?>
                        <div class="nav-completion-message">
                            <?php if (isset($is_last_lesson) && $is_last_lesson): ?>
                                <?php if (isset($next_unit) && $next_unit): ?>
                                    <?php
                                    // Extract unit number from title
                                    $sanitized_title = sanitize_text_field($next_unit->post_title);
                                    $unit_number = '';
                                    if (preg_match('/Unit\s+(\d+)/i', $sanitized_title, $matches)) {
                                        $unit_number = $matches[1];
                                    }
                                    ?>
                                    <span><?php _e('That is the end of this unit', 'ielts-course-manager'); ?></span>
                                    <a href="<?php echo esc_url(get_permalink($next_unit->ID)); ?>" class="button button-primary">
                                        <?php 
                                        if ($unit_number) {
                                            printf(__('Move to Unit %s', 'ielts-course-manager'), esc_html($unit_number));
                                        } else {
                                            _e('Move to next unit', 'ielts-course-manager');
                                        }
                                        ?>
                                    </a>
                                <?php else: ?>
                                    <span><?php _e('That is the end of this unit', 'ielts-course-manager'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span><?php _e('You have finished this lesson', 'ielts-course-manager'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php
                        // Visual debugger for "Next Unit" button visibility
                        // Shows when ?debug_nav=1 is in URL or IELTS_CM_DEBUG_NAV constant is true
                        $show_debug = (isset($_GET['debug_nav']) && sanitize_text_field($_GET['debug_nav']) === '1') || 
                                     (defined('IELTS_CM_DEBUG_NAV') && IELTS_CM_DEBUG_NAV);
                        
                        if ($show_debug):
                            // Gather all debug information
                            global $wpdb;
                            
                            // Get all lessons for this course (limited to 100 for performance)
                            $debug_all_lessons = array();
                            if ($course_id) {
                                $int_pattern_course = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
                                $str_pattern_course = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
                                
                                $all_lesson_ids = $wpdb->get_col($wpdb->prepare("
                                    SELECT DISTINCT post_id 
                                    FROM {$wpdb->postmeta} 
                                    WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                                       OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
                                    LIMIT 100
                                ", $course_id, $int_pattern_course, $str_pattern_course));
                                
                                if (!empty($all_lesson_ids)) {
                                    $debug_all_lessons = get_posts(array(
                                        'post_type' => 'ielts_lesson',
                                        'posts_per_page' => 100,
                                        'post__in' => $all_lesson_ids,
                                        'orderby' => 'menu_order',
                                        'order' => 'ASC',
                                        'post_status' => 'publish'
                                    ));
                                }
                            }
                            
                            // Get all units (limited to 100 for performance, including drafts for debugging)
                            $debug_all_units = array();
                            if ($course_id) {
                                $debug_all_units = get_posts(array(
                                    'post_type' => 'ielts_course',
                                    'posts_per_page' => 100,
                                    'orderby' => 'menu_order',
                                    'order' => 'ASC',
                                    'post_status' => 'any'
                                ));
                            }
                        ?>
                        <div class="ielts-nav-debugger">
                            <div class="debugger-header">
                                <h3>🔍 Next Unit Button Debugger</h3>
                                <p class="debugger-subtitle">This panel explains why the "Move to next unit" button is or isn't showing</p>
                            </div>
                            
                            <div class="debugger-section">
                                <h4>Current State</h4>
                                <table class="debugger-table">
                                    <tr>
                                        <td class="label">Quiz ID:</td>
                                        <td class="value"><?php echo esc_html($quiz->ID); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">Course ID:</td>
                                        <td class="value"><?php echo esc_html($course_id ? $course_id : 'NOT SET'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">Lesson ID:</td>
                                        <td class="value"><?php echo esc_html($lesson_id ? $lesson_id : 'NOT SET'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">Has Next Item:</td>
                                        <td class="value <?php echo $next_item ? 'success' : 'error'; ?>">
                                            <?php echo $next_item ? '✓ YES (ID: ' . esc_html($next_item->ID) . ' - ' . esc_html($next_item->post_title) . ')' : '✗ NO'; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="debugger-section">
                                <h4>Button Logic Check</h4>
                                <table class="debugger-table">
                                    <tr>
                                        <td class="label">Is Last Lesson:</td>
                                        <td class="value <?php echo (isset($is_last_lesson) && $is_last_lesson) ? 'success' : 'error'; ?>">
                                            <?php 
                                            if (isset($is_last_lesson)) {
                                                echo $is_last_lesson ? '✓ TRUE' : '✗ FALSE';
                                            } else {
                                                echo '✗ NOT SET';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Has Next Unit:</td>
                                        <td class="value <?php echo (isset($next_unit) && $next_unit) ? 'success' : 'error'; ?>">
                                            <?php 
                                            if (isset($next_unit) && $next_unit) {
                                                echo '✓ YES (ID: ' . esc_html($next_unit->ID) . ' - ' . esc_html($next_unit->post_title) . ')';
                                            } else if (isset($next_unit)) {
                                                echo '✗ NO (variable is set but empty/false)';
                                            } else {
                                                echo '✗ NOT SET';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="debugger-section debugger-decision">
                                <h4>Decision Tree</h4>
                                <div class="decision-flow">
                                    <?php if (!$next_item): ?>
                                        <div class="decision-step success">✓ No next item in lesson (last resource/quiz in lesson)</div>
                                    <?php else: ?>
                                        <div class="decision-step error">✗ Has next item in lesson → Regular "Next" button should show</div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$next_item): ?>
                                        <?php if (isset($is_last_lesson) && $is_last_lesson): ?>
                                            <div class="decision-step success">✓ This is the last lesson in the unit</div>
                                        <?php else: ?>
                                            <div class="decision-step error">✗ NOT the last lesson in unit → Shows "You have finished this lesson"</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!$next_item && isset($is_last_lesson) && $is_last_lesson): ?>
                                        <?php if (isset($next_unit) && $next_unit): ?>
                                            <div class="decision-step success">✓ Next unit found → BUTTON SHOULD BE VISIBLE</div>
                                        <?php else: ?>
                                            <div class="decision-step error">✗ No next unit found → Only shows completion message</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="debugger-section">
                                <h4>Expected Result</h4>
                                <div class="expected-result">
                                    <?php if (!$next_item && isset($is_last_lesson) && $is_last_lesson && isset($next_unit) && $next_unit): ?>
                                        <div class="result-box success">
                                            <strong>✓ BUTTON SHOULD BE VISIBLE</strong><br>
                                            The "Move to Unit X" button should appear because:
                                            <ul>
                                                <li>This is the last resource/quiz in the lesson</li>
                                                <li>This is the last lesson in the unit</li>
                                                <li>A next unit exists (<?php echo esc_html($next_unit->post_title); ?>)</li>
                                            </ul>
                                            <strong>If you don't see the button, check:</strong>
                                            <ul>
                                                <li>CSS is loaded (check browser dev tools)</li>
                                                <li>No custom CSS hiding the button</li>
                                                <li>The .button and .button-primary classes are styled</li>
                                            </ul>
                                        </div>
                                    <?php elseif (!$next_item && isset($is_last_lesson) && $is_last_lesson): ?>
                                        <div class="result-box warning">
                                            <strong>⚠ BUTTON NOT SHOWN (No Next Unit)</strong><br>
                                            Only completion message shows because:
                                            <ul>
                                                <li>This is the last resource/quiz in the lesson</li>
                                                <li>This is the last lesson in the unit</li>
                                                <li>But there is no next unit (this is the last unit in the course)</li>
                                            </ul>
                                        </div>
                                    <?php elseif (!$next_item): ?>
                                        <div class="result-box warning">
                                            <strong>⚠ BUTTON NOT SHOWN (Not Last Lesson)</strong><br>
                                            Shows "You have finished this lesson" because:
                                            <ul>
                                                <li>This is the last resource/quiz in the lesson</li>
                                                <li>But this is NOT the last lesson in the unit</li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <div class="result-box info">
                                            <strong>ℹ REGULAR NAVIGATION</strong><br>
                                            Regular "Next" button shows because:
                                            <ul>
                                                <li>There is a next resource/quiz in this lesson</li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="debugger-section">
                                <h4>All Lessons in Course (in order)</h4>
                                <?php if (!empty($debug_all_lessons)): ?>
                                    <ol class="lessons-list">
                                        <?php foreach ($debug_all_lessons as $index => $lesson_item): ?>
                                            <li class="<?php echo ($lesson_item->ID == $lesson_id) ? 'current-item' : ''; ?>">
                                                <strong><?php echo esc_html($lesson_item->post_title); ?></strong>
                                                (ID: <?php echo esc_html($lesson_item->ID); ?>)
                                                <?php if ($lesson_item->ID == $lesson_id): ?>
                                                    <span class="badge">← YOU ARE HERE</span>
                                                <?php endif; ?>
                                                <?php if ($index === count($debug_all_lessons) - 1): ?>
                                                    <span class="badge last">LAST LESSON</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <p class="no-data">No lessons found for this course</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="debugger-section">
                                <h4>All Units (in order)</h4>
                                <?php if (!empty($debug_all_units)): ?>
                                    <ol class="units-list">
                                        <?php foreach ($debug_all_units as $index => $unit_item): ?>
                                            <li class="<?php echo ($unit_item->ID == $course_id) ? 'current-item' : ''; ?>">
                                                <strong><?php echo esc_html($unit_item->post_title); ?></strong>
                                                (ID: <?php echo esc_html($unit_item->ID); ?>)
                                                <?php 
                                                $unit_status = get_post_status($unit_item->ID);
                                                if ($unit_status !== 'publish'): 
                                                ?>
                                                    <span class="badge status-<?php echo esc_attr($unit_status); ?>"><?php echo esc_html(strtoupper($unit_status)); ?></span>
                                                <?php endif; ?>
                                                <?php if ($unit_item->ID == $course_id): ?>
                                                    <span class="badge">← CURRENT UNIT</span>
                                                <?php endif; ?>
                                                <?php if (isset($next_unit) && $next_unit && $unit_item->ID == $next_unit->ID): ?>
                                                    <span class="badge next">← NEXT UNIT</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <p class="no-data">No units found</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="debugger-footer">
                                <p><strong>How to use this debugger:</strong></p>
                                <ul>
                                    <li>Add <code>?debug_nav=1</code> to the URL to enable this debugger</li>
                                    <li>Or define <code>IELTS_CM_DEBUG_NAV</code> constant as <code>true</code> in wp-config.php</li>
                                    <li>This panel shows all the logic that determines button visibility</li>
                                    <li>Use this to report exactly why the button isn't showing</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
    
    <div id="quiz-result" class="quiz-result" style="display: none;"></div>
    
    <?php if (!is_user_logged_in()): ?>
        <div class="quiz-login-notice">
            <p><?php _e('Please log in to take this quiz.', 'ielts-course-manager'); ?></p>
            <a href="<?php echo esc_url(IELTS_CM_Frontend::get_custom_login_url(get_permalink($quiz->ID))); ?>" class="button button-primary">
                <?php _e('Login', 'ielts-course-manager'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
