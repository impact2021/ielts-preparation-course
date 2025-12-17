<?php
/**
 * Template for displaying single course content in shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$enrollment = new IELTS_CM_Enrollment();
$progress_tracker = new IELTS_CM_Progress_Tracker();
$user_id = get_current_user_id();
$is_enrolled = $user_id ? $enrollment->is_enrolled($user_id, $course->ID) : false;
$completion = $user_id && $is_enrolled ? $progress_tracker->get_course_completion_percentage($user_id, $course->ID) : 0;
?>

<div class="ielts-single-course">
    <div class="course-header">
        <div class="course-breadcrumb">
            <a href="<?php echo home_url('/'); ?>"><?php _e('Home', 'ielts-course-manager'); ?></a>
            <span class="separator">&raquo;</span>
            <span><?php echo esc_html($course->post_title); ?></span>
        </div>
        
        <h2><?php echo esc_html($course->post_title); ?></h2>
        
        <?php if ($is_enrolled): ?>
        <div class="course-meta">
            <span class="course-progress">
                <?php echo round($completion, 1); ?>% <?php _e('Complete', 'ielts-course-manager'); ?>
            </span>
        </div>
        <?php endif; ?>
        
        <?php if (has_post_thumbnail($course->ID)): ?>
            <div class="course-featured-image">
                <?php echo get_the_post_thumbnail($course->ID, 'large'); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="course-description">
        <?php echo wpautop($course->post_content); ?>
    </div>
    
    <?php if (!empty($lessons)): ?>
        <div class="course-lessons">
            <h3><?php _e('Course Lessons', 'ielts-course-manager'); ?></h3>
            
            <table class="ielts-lessons-table">
                <thead>
                    <tr>
                        <?php if ($is_enrolled): ?>
                            <th class="lesson-status-col"><?php _e('Status', 'ielts-course-manager'); ?></th>
                        <?php endif; ?>
                        <th class="lesson-title-col"><?php _e('Lesson', 'ielts-course-manager'); ?></th>
                        <?php if ($is_enrolled): ?>
                            <th class="lesson-action-col"><?php _e('Action', 'ielts-course-manager'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lessons as $lesson): ?>
                        <?php
                        $is_completed = $is_enrolled && $progress_tracker->is_lesson_completed($user_id, $lesson->ID);
                        ?>
                        <tr class="lesson-row <?php echo $is_completed ? 'completed' : ''; ?>">
                            <?php if ($is_enrolled): ?>
                                <td class="lesson-status">
                                    <?php if ($is_completed): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-marker" style="color: #999;"></span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="lesson-title">
                                <strong>
                                    <a href="<?php echo get_permalink($lesson->ID); ?>">
                                        <?php echo esc_html($lesson->post_title); ?>
                                    </a>
                                </strong>
                            </td>
                            <?php if ($is_enrolled): ?>
                                <td class="lesson-action">
                                    <a href="<?php echo get_permalink($lesson->ID); ?>" class="button button-small">
                                        <?php echo $is_completed ? __('Review', 'ielts-course-manager') : __('Start', 'ielts-course-manager'); ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .ielts-lessons-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ielts-lessons-table th,
        .ielts-lessons-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .ielts-lessons-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .ielts-lessons-table tr:hover {
            background-color: #f5f5f5;
        }
        .ielts-lessons-table .lesson-status-col {
            width: 60px;
            text-align: center;
        }
        .ielts-lessons-table .lesson-status {
            text-align: center;
        }
        .ielts-lessons-table .lesson-action-col {
            width: 100px;
            text-align: center;
        }
        .ielts-lessons-table .lesson-action {
            text-align: center;
        }
        .ielts-lessons-table .completed {
            background-color: #f0f9f0;
        }
        </style>
    <?php endif; ?>
</div>
