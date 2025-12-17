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
        
        <?php if ($is_completed): ?>
            <div class="lesson-completed-badge">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Completed', 'ielts-course-manager'); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="lesson-content">
        <?php echo wpautop($lesson->post_content); ?>
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
                            $type_label = __('Page', 'ielts-course-manager');
                            $type_badge_class = 'resource';
                        } else {
                            $best_result = $user_id ? $quiz_handler->get_best_quiz_result($user_id, $post_item->ID) : null;
                            $is_completed = $best_result ? true : false;
                            $type_label = __('Exercise', 'ielts-course-manager');
                            $type_badge_class = 'quiz';
                        }
                        ?>
                        <tr class="content-row <?php echo $is_completed ? 'completed' : ''; ?>">
                            <?php if ($user_id): ?>
                                <td class="content-status">
                                    <?php if ($is_completed): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-marker" style="color: #999;"></span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="content-type">
                                <span class="type-badge <?php echo $type_badge_class; ?>">
                                    <?php echo esc_html($type_label); ?>
                                </span>
                            </td>
                            <td class="content-title">
                                <strong>
                                    <a href="<?php echo get_permalink($post_item->ID); ?>">
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
                                            <strong><?php echo round($best_result->percentage, 1); ?>%</strong>
                                            <br>
                                            <small>(<?php echo $best_result->score; ?> / <?php echo $best_result->max_score; ?>)</small>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="content-action">
                                <a href="<?php echo get_permalink($post_item->ID); ?>" class="button button-primary button-small">
                                    <?php 
                                    if ($item_type === 'quiz') {
                                        echo isset($best_result) && $best_result ? __('Retake', 'ielts-course-manager') : __('Take Exercise', 'ielts-course-manager');
                                    } else {
                                        echo $is_completed ? __('Review', 'ielts-course-manager') : __('View', 'ielts-course-manager');
                                    }
                                    ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
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
        .ielts-content-table .content-type-col {
            width: 90px;
        }
        .ielts-content-table .content-score-col {
            width: 120px;
            text-align: center;
        }
        .ielts-content-table .content-score {
            text-align: center;
        }
        .ielts-content-table .content-action-col {
            width: 120px;
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
        </style>
    <?php endif; ?>
</div>
