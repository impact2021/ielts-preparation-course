<?php
/**
 * Template for displaying single quiz in computer-based IELTS layout
 * Two-column layout with reading text on left and questions on right
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

$reading_texts = get_post_meta($quiz->ID, '_ielts_cm_reading_texts', true);
if (!is_array($reading_texts)) {
    $reading_texts = array();
}
$timer_minutes = get_post_meta($quiz->ID, '_ielts_cm_timer_minutes', true);

// Helper function to process transcript question markers
// Converts [Q#] markers to anchored, highlightable spans
function process_transcript_markers_cbt($transcript, $starting_question = 1) {
    if (empty($transcript)) {
        return $transcript;
    }
    
    // Convert [Q#] markers to visible badges with anchor for hyperlinking
    $pattern = '/\[Q(\d+)\]/i';
    
    $processed = preg_replace_callback($pattern, function($matches) use ($starting_question) {
        $question_num = intval($matches[1]);
        $display_num = $question_num;
        
        // Return the anchor span with visible Q badge - simple button with anchor link
        return '<span id="transcript-q' . esc_attr($display_num) . '" data-question="' . esc_attr($display_num) . '"><span class="question-marker-badge">Q' . esc_html($display_num) . '</span></span>';
    }, $transcript);
    
    return $processed;
}


// Determine test type from layout_type
$test_type = 'exercise'; // default
if ($layout_type === 'two_column_reading') {
    $test_type = 'reading';
} elseif ($layout_type === 'two_column_listening') {
    $test_type = 'listening';
}

$audio_url = get_post_meta($quiz->ID, '_ielts_cm_audio_url', true);
$transcript = get_post_meta($quiz->ID, '_ielts_cm_transcript', true);
$audio_sections = get_post_meta($quiz->ID, '_ielts_cm_audio_sections', true);
if (!is_array($audio_sections)) {
    $audio_sections = array();
}
// Sort audio sections by section_number for display
if (!empty($audio_sections)) {
    uasort($audio_sections, function($a, $b) {
        $num_a = isset($a['section_number']) ? intval($a['section_number']) : 999;
        $num_b = isset($b['section_number']) ? intval($b['section_number']) : 999;
        return $num_a - $num_b;
    });
    // Get first section key for default active tab
    reset($audio_sections);
    $first_section_key = key($audio_sections);
    if ($first_section_key === null) {
        $first_section_key = 0;
    }
} else {
    $first_section_key = 0;
}

// Calculate next and previous URLs for navigation
$next_url = '';
$prev_url = '';
$next_title = '';
$prev_title = '';
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
    
    // If there's a next item in this lesson, get its URL and title
    if ($current_index >= 0 && $current_index < count($all_items) - 1) {
        $next_post = $all_items[$current_index + 1]['post'];
        $next_title = $next_post->post_title;
        // Truncate to 15 characters
        if (strlen($next_title) > 15) {
            $next_title = substr($next_title, 0, 15) . '...';
        }
        $next_url = get_permalink($next_post->ID);
    }
    
    // If there's a previous item in this lesson, get its URL and title
    if ($current_index > 0) {
        $prev_post = $all_items[$current_index - 1]['post'];
        $prev_title = $prev_post->post_title;
        // Truncate to 15 characters
        if (strlen($prev_title) > 15) {
            $prev_title = substr($prev_title, 0, 15) . '...';
        }
        $prev_url = get_permalink($prev_post->ID);
    }
}
?>

<div class="ielts-computer-based-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>" data-timer-minutes="<?php echo esc_attr($timer_minutes); ?>" data-next-url="<?php echo esc_attr($next_url); ?>" data-test-type="<?php echo esc_attr($test_type); ?>">
    
    <!-- Header toggle button -->
    <button type="button" id="header-toggle-btn" class="header-toggle-btn" title="<?php _e('Toggle header visibility', 'ielts-course-manager'); ?>">
        <span class="toggle-icon">▼</span>
    </button>
    
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
            <div id="quiz-timer-fullscreen" class="quiz-timer-fullscreen">
                <div class="timer-left-section">
                    <?php if ($course_id): ?>
                    <?php 
                    // Show Previous page button if there's a previous item
                    if ($prev_url): ?>
                        <a href="<?php echo esc_url($prev_url); ?>" class="nav-page-link prev-page-link nav-link-clickable">
                            <div class="nav-arrow">&lt;</div>
                            <div class="nav-label">
                                <small><?php _e('Previous', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($prev_title); ?></strong>
                            </div>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Always show Return to course button -->
                    <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-page-link return-course-link nav-link-clickable">
                        <?php _e('Return to course', 'ielts-course-manager'); ?>
                    </a>
                    
                    <?php 
                    // Show Next page button if there's a next item
                    if ($next_url): ?>
                        <a href="<?php echo esc_url($next_url); ?>" class="nav-page-link next-page-link nav-link-clickable">
                            <div class="nav-label">
                                <small><?php _e('Next', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($next_title); ?></strong>
                            </div>
                            <div class="nav-arrow">&gt;</div>
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
            <div class="computer-based-container">
                <!-- Left Column: Reading Texts or Audio Player -->
                <div class="reading-column">
                    <?php if ($test_type === 'listening'): ?>
                        <!-- Audio Player for Listening Test -->
                        <div class="cbt-audio-player" id="listening-audio-player">
                            <?php if (!empty($audio_url)): ?>
                                <!-- Single Audio Player for entire test -->
                                <div class="audio-player-wrapper">
                                    <h3><?php _e('Listening Audio', 'ielts-course-manager'); ?></h3>
                                    <audio id="cbt-audio-element" controls controlsList="nodownload">
                                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                                        <?php _e('Your browser does not support the audio element.', 'ielts-course-manager'); ?>
                                    </audio>
                                </div>
                            <?php else: ?>
                                <div class="no-audio-message">
                                    <p><?php _e('No audio file provided for this listening test.', 'ielts-course-manager'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Transcript (shown after submission) -->
                        <?php if (!empty($audio_sections)): ?>
                            <!-- Multiple Transcripts -->
                            <div id="listening-transcripts" class="listening-transcripts" style="display: none;">
                                <h3><?php _e('Audio Transcripts', 'ielts-course-manager'); ?></h3>
                                
                                <!-- Audio control shown at the top, above transcripts -->
                                <?php if (!empty($audio_url)): ?>
                                    <div class="transcript-audio-controls">
                                        <audio controls controlsList="nodownload">
                                            <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                                        </audio>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="transcript-section-tabs">
                                    <?php foreach ($audio_sections as $index => $section): ?>
                                        <button type="button" class="transcript-section-tab<?php echo ($index === $first_section_key) ? ' active' : ''; ?>" data-section="<?php echo esc_attr($index); ?>">
                                            <?php printf(__('Section %d', 'ielts-course-manager'), isset($section['section_number']) ? $section['section_number'] : ($index + 1)); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <?php foreach ($audio_sections as $index => $section): ?>
                                    <div class="transcript-section-content" id="transcript-section-<?php echo esc_attr($index); ?>" style="<?php echo ($index !== $first_section_key) ? 'display:none;' : ''; ?>">
                                        <?php if (!empty($section['transcript'])): ?>
                                            <div class="transcript-content">
                                                <?php 
                                                // Process transcript to add question markers
                                                $processed_transcript = process_transcript_markers_cbt($section['transcript']);
                                                // Allow the question marker spans through wp_kses
                                                $allowed_html = wp_kses_allowed_html('post');
                                                $allowed_html['span']['id'] = true;
                                                $allowed_html['span']['class'] = true;
                                                $allowed_html['span']['data-question'] = true;
                                                echo wp_kses(wpautop($processed_transcript), $allowed_html);
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <p><?php _e('No transcript available for this section.', 'ielts-course-manager'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!empty($transcript)): ?>
                            <!-- Fallback: Single Transcript (backward compatibility) -->
                            <div id="listening-transcript" class="listening-transcript" style="display: none;">
                                <h3><?php _e('Audio Transcript', 'ielts-course-manager'); ?></h3>
                                
                                <!-- Audio controls shown above transcript after submission -->
                                <?php if (!empty($audio_url)): ?>
                                    <div class="transcript-audio-controls">
                                        <audio controls controlsList="nodownload">
                                            <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                                        </audio>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="transcript-content">
                                    <?php echo wp_kses_post(wpautop($transcript)); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Reading Texts for Reading Test or Exercise Content for Exercise -->
                        <div class="reading-content">
                            <button type="button" class="clear-highlights-btn" style="display:none;">
                                <?php _e('Clear', 'ielts-course-manager'); ?>
                            </button>
                            <?php 
                            // Check if this is a two_column_exercise layout and has exercise content
                            $exercise_content = get_post_meta($quiz->ID, '_ielts_cm_exercise_content', true);
                            if ($layout_type === 'two_column_exercise' && !empty($exercise_content)): ?>
                                <div class="exercise-content-section">
                                    <?php echo apply_filters('the_content', $exercise_content); ?>
                                </div>
                            <?php elseif (!empty($reading_texts)): ?>
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
                    <?php endif; ?>
                </div>
                
                <!-- Right Column: Questions -->
                <div class="questions-column">
                    <div class="questions-content">
                        <?php 
                        // Get starting question number (default is 1)
                        $starting_question_number = get_post_meta($quiz->ID, '_ielts_cm_starting_question_number', true);
                        if (!$starting_question_number) {
                            $starting_question_number = 1;
                        }
                        
                        // Calculate display question numbers for multi-select, summary completion, and matching questions
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
                                    // Check for new format [field N]
                                    preg_match_all('/\[field\s+(\d+)\]/i', $question_text, $field_matches);
                                    // Check for legacy format [ANSWER N]
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
                            <div class="quiz-question" id="question-<?php echo $index; ?>" data-reading-text-id="<?php echo esc_attr($question['reading_text_id'] ?? ''); ?>" data-audio-section-id="<?php echo esc_attr($question['audio_section_id'] ?? ''); ?>" data-display-start="<?php echo $display_nums['start']; ?>" data-display-end="<?php echo $display_nums['end']; ?>">
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
                                    <?php
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
                                // Don't display question text for certain types that render their own formatted version
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
                                        <div class="question-options closed-question-options" data-correct-count="<?php echo esc_attr($correct_answer_count); ?>">
                                            <?php foreach ($options as $opt_index => $option): ?>
                                                <label class="option-label">
                                                    <?php if ($is_multi_select): ?>
                                                        <input type="checkbox" 
                                                               name="answer_<?php echo esc_attr($index); ?>[]" 
                                                               value="<?php echo esc_attr($opt_index); ?>"
                                                               class="closed-question-checkbox">
                                                    <?php else: ?>
                                                        <input type="radio" 
                                                               name="answer_<?php echo esc_attr($index); ?>" 
                                                               value="<?php echo esc_attr($opt_index); ?>"
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
                                                                   name="answer_<?php echo esc_attr($index); ?>_field_<?php echo esc_attr($field_num); ?>" 
                                                                   class="answer-input open-question-input"
                                                                   data-field-num="<?php echo esc_attr($field_num); ?>">
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
</div>
