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
    
    <?php if (!empty($resources)): ?>
        <div class="lesson-resources">
            <h3><?php _e('Learning Resources', 'ielts-course-manager'); ?></h3>
            
            <table class="ielts-resources-table">
                <thead>
                    <tr>
                        <th class="resource-type-col"><?php _e('Type', 'ielts-course-manager'); ?></th>
                        <th class="resource-title-col"><?php _e('Resource', 'ielts-course-manager'); ?></th>
                        <th class="resource-description-col"><?php _e('Description', 'ielts-course-manager'); ?></th>
                        <th class="resource-action-col"><?php _e('Action', 'ielts-course-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <?php
                        $resource_type = get_post_meta($resource->ID, '_ielts_cm_resource_type', true);
                        $resource_url = get_post_meta($resource->ID, '_ielts_cm_resource_url', true);
                        ?>
                        <tr class="resource-row">
                            <td class="resource-type">
                                <?php
                                switch ($resource_type) {
                                    case 'video':
                                        echo '<span class="dashicons dashicons-video-alt3"></span> ' . __('Video', 'ielts-course-manager');
                                        break;
                                    case 'audio':
                                        echo '<span class="dashicons dashicons-format-audio"></span> ' . __('Audio', 'ielts-course-manager');
                                        break;
                                    case 'link':
                                        echo '<span class="dashicons dashicons-admin-links"></span> ' . __('Link', 'ielts-course-manager');
                                        break;
                                    default:
                                        echo '<span class="dashicons dashicons-media-document"></span> ' . __('Document', 'ielts-course-manager');
                                }
                                ?>
                            </td>
                            <td class="resource-title">
                                <strong><?php echo esc_html($resource->post_title); ?></strong>
                            </td>
                            <td class="resource-description">
                                <?php echo $resource->post_excerpt ? esc_html($resource->post_excerpt) : ''; ?>
                            </td>
                            <td class="resource-action">
                                <?php if ($resource_url): ?>
                                    <a href="<?php echo esc_url($resource_url); ?>" target="_blank" class="button button-small">
                                        <?php _e('Access', 'ielts-course-manager'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .ielts-resources-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ielts-resources-table th,
        .ielts-resources-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .ielts-resources-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .ielts-resources-table tr:hover {
            background-color: #f5f5f5;
        }
        .ielts-resources-table .resource-type-col {
            width: 120px;
        }
        .ielts-resources-table .resource-action-col {
            width: 100px;
            text-align: center;
        }
        .ielts-resources-table .resource-action {
            text-align: center;
        }
        </style>
    <?php endif; ?>
    
    <?php if (!empty($quizzes)): ?>
        <div class="lesson-quizzes">
            <h3><?php _e('Quizzes', 'ielts-course-manager'); ?></h3>
            
            <table class="ielts-quizzes-table">
                <thead>
                    <tr>
                        <th class="quiz-title-col"><?php _e('Quiz', 'ielts-course-manager'); ?></th>
                        <th class="quiz-description-col"><?php _e('Description', 'ielts-course-manager'); ?></th>
                        <?php if ($user_id): ?>
                            <th class="quiz-score-col"><?php _e('Best Score', 'ielts-course-manager'); ?></th>
                        <?php endif; ?>
                        <th class="quiz-action-col"><?php _e('Action', 'ielts-course-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $quiz_handler = new IELTS_CM_Quiz_Handler();
                    
                    foreach ($quizzes as $quiz):
                        $best_result = $user_id ? $quiz_handler->get_best_quiz_result($user_id, $quiz->ID) : null;
                    ?>
                        <tr class="quiz-row">
                            <td class="quiz-title">
                                <strong><?php echo esc_html($quiz->post_title); ?></strong>
                            </td>
                            <td class="quiz-description">
                                <?php echo $quiz->post_excerpt ? esc_html($quiz->post_excerpt) : ''; ?>
                            </td>
                            <?php if ($user_id): ?>
                                <td class="quiz-score">
                                    <?php if ($best_result): ?>
                                        <strong><?php echo round($best_result->percentage, 1); ?>%</strong>
                                        <br>
                                        <small>(<?php echo $best_result->score; ?> / <?php echo $best_result->max_score; ?>)</small>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="quiz-action">
                                <a href="<?php echo get_permalink($quiz->ID); ?>" class="button button-primary button-small">
                                    <?php echo $best_result ? __('Retake', 'ielts-course-manager') : __('Take Quiz', 'ielts-course-manager'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .ielts-quizzes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ielts-quizzes-table th,
        .ielts-quizzes-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .ielts-quizzes-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .ielts-quizzes-table tr:hover {
            background-color: #f5f5f5;
        }
        .ielts-quizzes-table .quiz-score-col {
            width: 120px;
            text-align: center;
        }
        .ielts-quizzes-table .quiz-score {
            text-align: center;
        }
        .ielts-quizzes-table .quiz-action-col {
            width: 120px;
            text-align: center;
        }
        .ielts-quizzes-table .quiz-action {
            text-align: center;
        }
        </style>
    <?php endif; ?>
    
    <?php if (is_user_logged_in() && !$is_completed): ?>
        <div class="lesson-actions">
            <button class="button button-primary mark-complete-button" 
                    data-course-id="<?php echo esc_attr($course_id); ?>" 
                    data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                <?php _e('Mark as Complete', 'ielts-course-manager'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
