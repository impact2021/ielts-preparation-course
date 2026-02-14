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
// Converts [Q#] markers to anchored, highlightable spans with yellow background on answer text
function process_transcript_markers_cbt($transcript, $starting_question = 1, $is_reading = false) {
    if (empty($transcript)) {
        return $transcript;
    }
    
    // Convert [Q#] markers to invisible anchors for navigation (reading) or visible badges (listening)
    // Pattern captures: [Q1]answer text here... and wraps answer text in highlight
    // Stops at next Q marker or end of string to avoid spanning multiple questions
    $pattern = '/\[Q(\d+)\]([^\[]*?)(?=\[Q|$)/is';
    
    $processed = preg_replace_callback($pattern, function($matches) use ($starting_question, $is_reading) {
        $question_num = intval($matches[1]);
        $display_num = $question_num;
        $answer_text = isset($matches[2]) ? $matches[2] : '';
        
        // Smart answer extraction: highlight only the answer portion, not entire sentences
        $highlighted_text = $answer_text;
        
        // Try multiple strategies to find the answer boundary (in order of preference):
        // 1. Stop at comma (common for embedded answers like "It's Anne Hawberry, and I live...")
        // 2. Stop at semicolon
        // 3. Stop at period + space + capital letter (sentence boundary)
        // 4. Stop at newline
        // 5. Limit to first 50 characters (reduced from 100 for better accuracy)
        
        if (preg_match('/^([^,;]+?)(?:[,;]|\.\s+[A-Z]|\n|$)/s', $answer_text, $boundary_match)) {
            // Found a natural boundary - use text up to that point
            $highlighted_text = $boundary_match[1];
        } else {
            // No natural boundary found - take first 50 characters
            $highlighted_text = mb_substr($answer_text, 0, 50);
        }
        
        // Trim and ensure we have content
        $highlighted_text = trim($highlighted_text);
        
        // If highlighted text is still very long (>50 chars), try to trim to words
        if (mb_strlen($highlighted_text) > 50) {
            // Find the last complete word within 50 characters
            $trimmed = mb_substr($highlighted_text, 0, 50);
            if (preg_match('/^(.*)\s+\S+$/', $trimmed, $word_match)) {
                $highlighted_text = $word_match[1];
            } else {
                $highlighted_text = $trimmed;
            }
        }
        
        // Build the output with unified ID format for both reading and listening
        // Simplified: use 'q' prefix instead of 'passage-q' or 'transcript-q'
        // Both reading and listening now use the same format
        $output = '<span id="q' . esc_attr($display_num) . '" data-question="' . esc_attr($display_num) . '"></span>';
        
        // Wrap the highlighted answer text in a span for highlighting on click
        // Both reading and listening now use 'reading-answer-marker' class
        // Note: $highlighted_text may contain HTML tags from transcript (e.g., <strong>) which must be preserved
        if (!empty($highlighted_text)) {
            $output .= '<span class="reading-answer-marker" data-question="' . esc_attr($display_num) . '">' . $highlighted_text . '</span>';
        }
        
        // Add any remaining text that wasn't highlighted
        // Note: $remaining_text may also contain HTML tags which must be preserved
        $remaining_text = mb_substr($answer_text, mb_strlen($highlighted_text));
        $output .= $remaining_text;
        
        return $output;
    }, $transcript);
    
    return $processed;
}


// Determine test type from checkbox (for backward compatibility, also check layout_type)
$is_listening_exercise = get_post_meta($quiz->ID, '_ielts_cm_is_listening_exercise', true);
// Backward compatibility: if layout_type is 'two_column_listening', treat as listening
if ($layout_type === 'two_column_listening') {
    $is_listening_exercise = '1';
}

$test_type = ($is_listening_exercise === '1') ? 'listening' : 'reading';

// Get hide reading pane option
$hide_reading_pane = get_post_meta($quiz->ID, '_ielts_cm_hide_reading_pane', true);

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

// Check if user has already completed this quiz (so we know whether to show the Continue button)
$user_has_completed_quiz = false;
if ($user_id) {
    global $wpdb;
    $quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
    $user_has_completed_quiz = (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM $quiz_results_table WHERE user_id = %d AND quiz_id = %d LIMIT 1",
        $user_id,
        $quiz->ID
    ));
}

if ($lesson_id) {
    global $wpdb;
    // Check for both integer and string serialization in lesson_ids array
    // Integer: i:123; String: s:3:"123";
    $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
    $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
    
    // Get all resources and quizzes for this lesson
    $resource_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT post_id 
        FROM {$wpdb->postmeta} 
        WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
           OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
    ", $lesson_id, $int_pattern, $str_pattern));
    
    $quiz_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT post_id 
        FROM {$wpdb->postmeta} 
        WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
           OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
    ", $lesson_id, $int_pattern, $str_pattern));
    
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
    // Only show the next button if user has already completed this quiz
    if ($user_has_completed_quiz && $current_index >= 0 && $current_index < count($all_items) - 1) {
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
    
    // Check if this is the last item of the last lesson in the course (for completion message)
    $is_last_lesson = false;
    if (empty($next_url) && $course_id && $lesson_id) {
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
            // (empty($next_url) already confirms we're on the last item in the lesson)
            if (!empty($all_lessons) && end($all_lessons)->ID == $lesson_id) {
                $is_last_lesson = true;
            }
        }
    }
    
    // Find next unit if this is the last lesson
    $next_unit = null;
    $next_unit_label = __('Move on to next unit', 'ielts-course-manager');
    if ($is_last_lesson && $course_id) {
        // Get all units (including drafts) ordered by menu_order to find position
        $all_units = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_status' => 'any'
        ));
        
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

<div class="ielts-computer-based-quiz<?php echo ($hide_reading_pane === '1') ? ' hide-reading-pane' : ''; ?>" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>" data-timer-minutes="<?php echo esc_attr($timer_minutes); ?>" data-next-url="<?php echo esc_attr($next_url); ?>" data-test-type="<?php echo esc_attr($test_type); ?>">
    
    <!-- Header toggle button (admins only) -->
    <?php if (current_user_can('manage_options')): ?>
    <button type="button" id="header-toggle-btn" class="header-toggle-btn" title="<?php _e('Toggle header visibility', 'ielts-course-manager'); ?>">
        <span class="toggle-icon">▼</span>
    </button>
    <?php endif; ?>
    
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
        <?php
        // Get starting question number early as it's used in reading text processing
        $starting_question_number = get_post_meta($quiz->ID, '_ielts_cm_starting_question_number', true);
        if (!$starting_question_number) {
            $starting_question_number = 1;
        }
        ?>
        <form id="ielts-quiz-form" class="quiz-form">
            <div id="quiz-timer-fullscreen" class="quiz-timer-fullscreen" style="display: none;">
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
                    
                    <!-- Always show Return to unit button -->
                    <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-page-link return-course-link nav-link-clickable">
                        <?php _e('Return to unit', 'ielts-course-manager'); ?>
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
                            <?php if (!empty($reading_texts)): ?>
                                <?php foreach ($reading_texts as $index => $text): ?>
                                    <div class="reading-text-section" id="reading-text-<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display:none;' : ''; ?>">
                                        <?php if (!empty($text['title'])): ?>
                                            <h3 class="reading-title"><?php echo esc_html($text['title']); ?></h3>
                                        <?php endif; ?>
                                        <div class="reading-text">
                                            <?php 
                                            // Process [Q#] markers in reading text content to enable highlighting
                                            // For reading tests, question numbers are hidden (only invisible markers remain)
                                            $reading_content = isset($text['content']) ? $text['content'] : '';
                                            $processed_content = process_transcript_markers_cbt($reading_content, $starting_question_number, true);
                                            echo wp_kses_post(wpautop($processed_content)); 
                                            ?>
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
                                        
                                        // If no options provided but instructions mention TRUE/FALSE, render as true_false type
                                        // This handles legacy questions that were incorrectly typed as closed_question
                                        $instructions_text = isset($question['instructions']) ? strtoupper($question['instructions']) : '';
                                        $is_true_false_question = (empty($options) && 
                                                                   (strpos($instructions_text, 'TRUE') !== false || 
                                                                    strpos($instructions_text, 'FALSE') !== false ||
                                                                    strpos($instructions_text, 'NOT GIVEN') !== false ||
                                                                    strpos($instructions_text, 'YES') !== false ||
                                                                    strpos($instructions_text, 'NO') !== false));
                                        
                                        if ($is_true_false_question) {
                                            // Render as TRUE/FALSE/NOT GIVEN or YES/NO/NOT GIVEN based on instructions
                                            $is_yes_no = (strpos($instructions_text, 'YES') !== false);
                                            ?>
                                            <div class="question-options">
                                                <label class="option-label">
                                                    <input type="radio" 
                                                           name="answer_<?php echo esc_attr($index); ?>" 
                                                           value="<?php echo esc_attr($is_yes_no ? 'yes' : 'true'); ?>">
                                                    <span><?php echo $is_yes_no ? __('Yes', 'ielts-course-manager') : __('True', 'ielts-course-manager'); ?></span>
                                                </label>
                                                <label class="option-label">
                                                    <input type="radio" 
                                                           name="answer_<?php echo esc_attr($index); ?>" 
                                                           value="<?php echo esc_attr($is_yes_no ? 'no' : 'false'); ?>">
                                                    <span><?php echo $is_yes_no ? __('No', 'ielts-course-manager') : __('False', 'ielts-course-manager'); ?></span>
                                                </label>
                                                <label class="option-label">
                                                    <input type="radio" 
                                                           name="answer_<?php echo esc_attr($index); ?>" 
                                                           value="not_given">
                                                    <span><?php _e('Not Given', 'ielts-course-manager'); ?></span>
                                                </label>
                                            </div>
                                            <?php
                                        } else {
                                            // Normal closed_question with options
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
                                        }
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
            
            <!-- Sticky Bottom Navigation with Timer and Submit -->
            <div class="ielts-sticky-bottom-nav quiz-bottom-nav">
                <div class="nav-item nav-prev">
                    <?php if ($prev_url): ?>
                        <a href="<?php echo esc_url($prev_url); ?>" class="nav-link">
                            <span class="nav-arrow">&laquo;</span>
                            <span class="nav-label">
                                <small><?php _e('Previous', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($prev_title); ?></strong>
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
                    <?php if ($next_url): ?>
                        <a href="<?php echo esc_url($next_url); ?>" class="nav-link">
                            <span class="nav-label">
                                <small><?php _e('Next', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($next_title); ?></strong>
                            </span>
                            <span class="nav-arrow">&raquo;</span>
                        </a>
                    <?php else: ?>
                        <div class="nav-completion-message">
                            <?php if ($is_last_lesson): ?>
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
                                <h3>🔍 Next Unit Button Debugger (Computer-Based Quiz)</h3>
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
                                        <td class="value <?php echo $is_last_lesson ? 'success' : 'error'; ?>">
                                            <?php echo $is_last_lesson ? '✓ TRUE' : '✗ FALSE'; ?>
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
                                        <?php if ($is_last_lesson): ?>
                                            <div class="decision-step success">✓ This is the last lesson in the unit</div>
                                        <?php else: ?>
                                            <div class="decision-step error">✗ NOT the last lesson in unit → Shows "You have finished this lesson"</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!$next_item && $is_last_lesson): ?>
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
                                    <?php if (!$next_item && $is_last_lesson && isset($next_unit) && $next_unit): ?>
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
                                    <?php elseif (!$next_item && $is_last_lesson): ?>
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
        </form>
        
        <div id="quiz-result" class="quiz-result" style="display: none;"></div>
        
    <?php elseif (!empty($questions) && !is_user_logged_in()): ?>
        <div class="quiz-login-notice">
            <p><?php _e('Please log in to take this quiz.', 'ielts-course-manager'); ?></p>
            <a href="<?php echo esc_url(IELTS_CM_Frontend::get_custom_login_url(get_permalink($quiz->ID))); ?>" class="button button-primary">
                <?php _e('Login', 'ielts-course-manager'); ?>
            </a>
        </div>
    <?php else: ?>
        <p><?php _e('No questions available for this quiz.', 'ielts-course-manager'); ?></p>
    <?php endif; ?>
</div>
