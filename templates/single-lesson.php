<?php
/**
 * Template for displaying single lesson content in shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$progress_tracker = new IELTS_CM_Progress_Tracker();
$is_completed = $user_id ? $progress_tracker->is_lesson_completed($user_id, $lesson->ID) : false;
?>

<div class="ielts-single-lesson">
    <div class="lesson-header">
        <h2><?php echo esc_html($lesson->post_title); ?></h2>
        
        <?php if ($course_id): ?>
            <div class="lesson-breadcrumb">
                <?php
                $course = get_post($course_id);
                if ($course):
                ?>
                    <a href="<?php echo get_permalink($course->ID); ?>">
                        <?php echo esc_html($course->post_title); ?>
                    </a>
                    <span class="separator">&raquo;</span>
                    <span><?php echo esc_html($lesson->post_title); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($user_id): ?>
        <div class="lesson-meta">
            <div class="lesson-progress-stats">
                <?php
                // Get lesson completion percentage
                $lesson_completion = $progress_tracker->get_lesson_completion_percentage($user_id, $lesson->ID);
                
                // Get lesson average score
                $lesson_score_data = $progress_tracker->get_lesson_average_score($user_id, $lesson->ID);
                $average_score = $lesson_score_data['average_percentage'];
                $quiz_count = $lesson_score_data['quiz_count'];
                
                // Check if this is a practice test lesson (should show band scores)
                $is_practice_test = get_post_meta($lesson->ID, '_ielts_cm_is_practice_test', true);
                
                // If this is a practice test lesson, try to get average band score
                $average_band_score = null;
                if ($is_practice_test && $quiz_count > 0) {
                    // Get average band score for this lesson
                    $band_score_data = $progress_tracker->get_lesson_average_band_score($user_id, $lesson->ID);
                    if ($band_score_data['has_band_scores']) {
                        $average_band_score = $band_score_data['average_band_score'];
                    }
                }
                ?>
                <div class="lesson-stats-container">
                    <div class="lesson-stat-item lesson-stat-progress">
                        <span class="stat-label"><?php _e('Percent Complete:', 'ielts-course-manager'); ?></span>
                        <span class="stat-value"><?php echo number_format($lesson_completion, 1); ?>%</span>
                        <div class="stat-progress-bar">
                            <div class="stat-progress-fill" style="width: <?php echo min(100, $lesson_completion); ?>%;"></div>
                        </div>
                    </div>
                    <?php if ($quiz_count > 0): ?>
                        <div class="lesson-stat-item">
                            <?php if ($average_band_score !== null): ?>
                                <span class="stat-label"><?php _e('Average Band Score:', 'ielts-course-manager'); ?></span>
                                <span class="stat-value"><?php echo number_format($average_band_score, 1); ?></span>
                                <small class="stat-description">(<?php printf(_n('%d test taken', '%d tests taken', $quiz_count, 'ielts-course-manager'), $quiz_count); ?>)</small>
                            <?php else: ?>
                                <span class="stat-label"><?php _e('Average Score:', 'ielts-course-manager'); ?></span>
                                <span class="stat-value"><?php echo number_format($average_score, 1); ?>%</span>
                                <small class="stat-description">(<?php printf(_n('%d test taken', '%d tests taken', $quiz_count, 'ielts-course-manager'), $quiz_count); ?>)</small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($is_completed): ?>
            <div class="lesson-completed-badge">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Completed', 'ielts-course-manager'); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="lesson-content">
        <?php 
        // Apply WordPress content filters to process embeds and shortcodes
        echo apply_filters('the_content', $lesson->post_content);
        ?>
    </div>
    
    <?php 
    // Combine resources and quizzes into a single array for display
    $content_items = array();
    
    if (!empty($resources)) {
        foreach ($resources as $resource) {
            $content_items[] = array(
                'type' => 'resource',
                'post' => $resource,
                'order' => $resource->menu_order
            );
        }
    }
    
    if (!empty($quizzes)) {
        foreach ($quizzes as $quiz) {
            $content_items[] = array(
                'type' => 'quiz',
                'post' => $quiz,
                'order' => $quiz->menu_order
            );
        }
    }
    
    // Sort by menu_order
    usort($content_items, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    if (!empty($content_items)): 
        $quiz_handler = new IELTS_CM_Quiz_Handler();
    ?>
        <div class="lesson-content-items">
            <h3><?php _e('Lesson Content', 'ielts-course-manager'); ?></h3>
            
            <table class="ielts-content-table">
                <thead>
                    <tr>
                        <?php if ($user_id): ?>
                            <th class="content-status-col"><?php _e('Status', 'ielts-course-manager'); ?></th>
                        <?php endif; ?>
                        <th class="content-type-col"><?php _e('Type', 'ielts-course-manager'); ?></th>
                        <th class="content-title-col"><?php _e('Title', 'ielts-course-manager'); ?></th>
                        <?php if ($user_id): ?>
                            <th class="content-score-col"><?php _e('Score', 'ielts-course-manager'); ?></th>
                            <th class="content-attempts-col"><?php _e('Attempts', 'ielts-course-manager'); ?></th>
                        <?php endif; ?>
                        <th class="content-action-col"><?php _e('Action', 'ielts-course-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($content_items as $item): ?>
                        <?php
                        $post_item = $item['post'];
                        $item_type = $item['type'];
                        
                        if ($item_type === 'resource') {
                            $resource_url = get_post_meta($post_item->ID, '_ielts_cm_resource_url', true);
                            $is_completed = $user_id ? $progress_tracker->is_resource_completed($user_id, $lesson->ID, $post_item->ID) : false;
                            
                            // Check if resource has a video
                            $video_url = get_post_meta($post_item->ID, '_ielts_cm_video_url', true);
                            if (!empty($video_url)) {
                                $type_label = __('Video', 'ielts-course-manager');
                            } else {
                                $type_label = __('Sublesson', 'ielts-course-manager');
                            }
                            $type_badge_class = 'resource';
                        } else {
                            $best_result = $user_id ? $quiz_handler->get_best_quiz_result($user_id, $post_item->ID) : null;
                            $is_completed = $best_result ? true : false;
                            
                            // Get exercise label from meta or default to 'exercise'
                            $exercise_label = get_post_meta($post_item->ID, '_ielts_cm_exercise_label', true);
                            if (!$exercise_label) {
                                $exercise_label = 'exercise';
                            }
                            
                            // Convert label to display text
                            switch ($exercise_label) {
                                case 'end_of_lesson_test':
                                    $type_label = __('End of lesson test', 'ielts-course-manager');
                                    break;
                                case 'practice_test':
                                    $type_label = __('Practice test', 'ielts-course-manager');
                                    break;
                                case 'exercise':
                                default:
                                    $type_label = __('Exercise', 'ielts-course-manager');
                                    break;
                            }
                            
                            $type_badge_class = 'quiz';
                            // Check if this is a two-column quiz with popup enabled
                            $layout_type = get_post_meta($post_item->ID, '_ielts_cm_layout_type', true);
                            $open_as_popup = get_post_meta($post_item->ID, '_ielts_cm_open_as_popup', true);
                            $is_two_column = in_array($layout_type, array('two_column_reading', 'two_column_listening', 'two_column_exercise'));
                            $use_fullscreen = $is_two_column && $open_as_popup;
                        }
                        ?>
                        <tr class="content-row <?php echo $is_completed ? 'completed' : ''; ?>">
                            <?php if ($user_id): ?>
                                <td class="content-status">
                                    <div class="item-progress-circle">
                                        <svg width="34" height="34" viewBox="0 0 34 34">
                                            <circle class="progress-circle-bg" cx="17" cy="17" r="14" fill="none" stroke="#e0e0e0" stroke-width="2.5"></circle>
                                            <?php if ($is_completed): ?>
                                                <!-- Completed: Full circle (circumference = 2πr = 2 × 3.14159 × 14 ≈ 87.96) -->
                                                <circle class="progress-circle-fill" cx="17" cy="17" r="14" fill="none" stroke="#46b450" stroke-width="2.5" 
                                                        stroke-dasharray="87.96" stroke-dashoffset="0" transform="rotate(-90 17 17)"></circle>
                                                <text x="17" y="17" text-anchor="middle" dy="0.35em" font-size="18" fill="#46b450" font-weight="bold">✓</text>
                                            <?php else: ?>
                                                <text x="17" y="17" text-anchor="middle" dy="0.35em" font-size="20" fill="#999">○</text>
                                            <?php endif; ?>
                                        </svg>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td class="content-type">
                                <span class="type-badge <?php echo $type_badge_class; ?>">
                                    <?php echo esc_html($type_label); ?>
                                </span>
                            </td>
                            <td class="content-title">
                                <strong>
                                    <?php 
                                    // For CBT quizzes with popup enabled, link should go to fullscreen mode
                                    if ($item_type === 'quiz' && isset($use_fullscreen) && $use_fullscreen) {
                                        $quiz_url = add_query_arg('fullscreen', '1', get_permalink($post_item->ID));
                                    } else {
                                        $quiz_url = get_permalink($post_item->ID);
                                    }
                                    ?>
                                    <a href="<?php echo esc_url($quiz_url); ?>">
                                        <?php echo esc_html($post_item->post_title); ?>
                                    </a>
                                </strong>
                                <?php if ($item_type === 'resource' && !empty($resource_url)): ?>
                                    <br>
                                    <small>
                                        <a href="<?php echo esc_url($resource_url); ?>" target="_blank">
                                            <?php _e('External Resource', 'ielts-course-manager'); ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <?php if ($user_id): ?>
                                <td class="content-score">
                                    <?php if ($item_type === 'quiz'): ?>
                                        <?php if (isset($best_result) && $best_result): ?>
                                            <?php
                                            // Get display score (band score or percentage)
                                            $quiz_handler = new IELTS_CM_Quiz_Handler();
                                            $display_score_data = $quiz_handler->get_display_score($post_item->ID, $best_result->score, $best_result->percentage);
                                            ?>
                                            <strong><?php echo esc_html($display_score_data['display']); ?></strong>
                                            <br>
                                            <small>(<?php echo $best_result->score; ?> / <?php echo $best_result->max_score; ?>)</small>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="content-attempts">
                                    <?php if ($item_type === 'quiz'): ?>
                                        <button class="button button-small view-attempts-btn" 
                                                data-quiz-id="<?php echo $post_item->ID; ?>"
                                                data-quiz-title="<?php echo esc_attr($post_item->post_title); ?>">
                                            <?php _e('View Attempts', 'ielts-course-manager'); ?>
                                        </button>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="content-action">
                                <?php if ($item_type === 'quiz' && isset($use_fullscreen) && $use_fullscreen): ?>
                                    <!-- CBT Exercise with fullscreen mode -->
                                    <a href="<?php echo add_query_arg('fullscreen', '1', get_permalink($post_item->ID)); ?>" 
                                       class="button button-primary button-small">
                                        <?php echo isset($best_result) && $best_result ? __('Retake (Fullscreen)', 'ielts-course-manager') : __('Start CBT Exercise', 'ielts-course-manager'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo get_permalink($post_item->ID); ?>" class="button button-primary button-small">
                                        <?php 
                                        if ($item_type === 'quiz') {
                                            echo isset($best_result) && $best_result ? __('Retake', 'ielts-course-manager') : __('Take Exercise', 'ielts-course-manager');
                                        } else {
                                            echo $is_completed ? __('Review', 'ielts-course-manager') : __('View', 'ielts-course-manager');
                                        }
                                        ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Modal for viewing attempts -->
        <?php if ($user_id): ?>
        <div id="attempts-modal" class="attempts-modal" style="display: none;">
            <div class="attempts-modal-content">
                <div class="attempts-modal-header">
                    <h3 id="attempts-modal-title"><?php _e('Previous Attempts', 'ielts-course-manager'); ?></h3>
                    <button class="attempts-modal-close">&times;</button>
                </div>
                <div class="attempts-modal-body">
                    <div class="attempts-loading">
                        <?php _e('Loading attempts...', 'ielts-course-manager'); ?>
                    </div>
                    <div class="attempts-list"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <style>
        /* Lesson progress stats styling */
        .lesson-progress-stats {
            width: 100%;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .lesson-stats-container {
            display: flex;
            gap: 30px;
            flex-wrap: nowrap;
        }
        .lesson-stat-item {
            flex: 1;
            min-width: 200px;
        }
        /* Make progress bar stat item take more space on desktop */
        .lesson-stat-item.lesson-stat-progress {
            flex: 2;
        }
        
        /* Mobile: stack items vertically */
        @media (max-width: 768px) {
            .lesson-stats-container {
                flex-wrap: wrap;
            }
            .lesson-stat-item.lesson-stat-progress {
                flex: 0 0 100%;
            }
        }
        .lesson-stat-item .stat-label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .lesson-stat-item .stat-value {
            display: inline-block;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 8px;
        }
        .lesson-stat-item .stat-description {
            display: block;
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .stat-progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        .stat-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa 0%, #46b450 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .ielts-content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ielts-content-table th,
        .ielts-content-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .ielts-content-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .ielts-content-table tr:hover {
            background-color: #f5f5f5;
        }
        .ielts-content-table .content-status-col {
            width: 60px;
            text-align: center;
        }
        .ielts-content-table .content-status {
            text-align: center;
        }
        
        /* Item circular progress indicator styling */
        .item-progress-circle {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .item-progress-circle svg {
            display: block;
        }
        /* Note: No transition needed - circles are either complete or incomplete, no animation */
        .ielts-content-table .content-type-col {
            width: 180px;
        }
        .ielts-content-table .content-score-col {
            width: 200px;
            text-align: center;
        }
        .ielts-content-table .content-score {
            text-align: center;
        }
        .ielts-content-table .content-attempts-col {
            width: 160px;
            text-align: center;
        }
        .ielts-content-table .content-attempts {
            text-align: center;
        }
        .ielts-content-table .content-action-col {
            width: 180px;
            text-align: center;
        }
        .ielts-content-table .content-action {
            text-align: center;
        }
        .ielts-content-table .completed {
            background-color: #f0f9f0;
        }
        .ielts-content-table .type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .ielts-content-table .type-badge.resource {
            background: #e3f2fd;
            color: #1976d2;
        }
        .ielts-content-table .type-badge.quiz {
            background: #fff3e0;
            color: #f57c00;
        }
        
        /* Modal styles for attempts */
        .attempts-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .attempts-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .attempts-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .attempts-modal-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .attempts-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            line-height: 1;
        }
        .attempts-modal-close:hover {
            color: #333;
        }
        .attempts-modal-body {
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .attempts-loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .attempts-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attempts-table th,
        .attempts-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .attempts-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .attempts-table tr:hover {
            background-color: #f5f5f5;
        }
        .percentage-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-weight: 600;
        }
        .percentage-pass {
            background-color: #d4edda;
            color: #155724;
        }
        .percentage-fail {
            background-color: #f8d7da;
            color: #721c24;
        }
        </style>
    <?php endif; ?>
    
    <?php
    // Previous/Next lesson navigation within the course
    if ($course_id) {
        global $wpdb;
        // Check for both integer and string serialization in course_ids array
        // Integer: i:123; String: s:3:"123";
        $int_pattern = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
        
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
        ", $course_id, $int_pattern, $str_pattern));
        
        $all_lessons = array();
        if (!empty($lesson_ids)) {
            $all_lessons = get_posts(array(
                'post_type' => 'ielts_lesson',
                'posts_per_page' => -1,
                'post__in' => $lesson_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish'
            ));
        }
        
        $current_index = -1;
        foreach ($all_lessons as $index => $l) {
            if ($l->ID == $lesson->ID) {
                $current_index = $index;
                break;
            }
        }
        
        $prev_lesson = ($current_index > 0) ? $all_lessons[$current_index - 1] : null;
        $next_lesson = ($current_index >= 0 && $current_index < count($all_lessons) - 1) ? $all_lessons[$current_index + 1] : null;
        ?>
        
        <?php if ($prev_lesson || $next_lesson || $course_id): ?>
            <div class="ielts-sticky-bottom-nav">
                <div class="nav-item nav-prev">
                    <?php if ($prev_lesson): ?>
                        <a href="<?php echo get_permalink($prev_lesson->ID); ?>" class="nav-link">
                            <span class="nav-arrow">&laquo;</span>
                            <span class="nav-label">
                                <small><?php _e('Previous Lesson', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($prev_lesson->post_title); ?></strong>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-item nav-center">
                    <?php if ($course_id): ?>
                        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-link nav-back-to-course">
                            <span class="nav-label">
                                <small><?php _e('Back to the Unit', 'ielts-course-manager'); ?></small>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-item nav-next">
                    <?php if ($next_lesson): ?>
                        <a href="<?php echo get_permalink($next_lesson->ID); ?>" class="nav-link">
                            <span class="nav-label">
                                <small><?php _e('Next Lesson', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($next_lesson->post_title); ?></strong>
                            </span>
                            <span class="nav-arrow">&raquo;</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <script>
            document.body.classList.add('has-sticky-bottom-nav');
            </script>
        <?php endif; ?>
    <?php } ?>
</div>
