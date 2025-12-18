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
        

    </div>
    
    <div class="course-description">
        <?php 
        // Apply WordPress content filters to process embeds and shortcodes
        echo apply_filters('the_content', $course->post_content);
        ?>
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
    
    <?php
    // Previous/Next course navigation
    $all_courses = get_posts(array(
        'post_type' => 'ielts_course',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'post_status' => 'publish'
    ));
    
    $current_index = -1;
    foreach ($all_courses as $index => $c) {
        if ($c->ID == $course->ID) {
            $current_index = $index;
            break;
        }
    }
    
    $prev_course = ($current_index > 0) ? $all_courses[$current_index - 1] : null;
    $next_course = ($current_index >= 0 && $current_index < count($all_courses) - 1) ? $all_courses[$current_index + 1] : null;
    ?>
    
    <?php if ($prev_course || $next_course): ?>
        <div class="ielts-navigation">
            <div class="nav-prev">
                <?php if ($prev_course): ?>
                    <a href="<?php echo get_permalink($prev_course->ID); ?>" class="nav-link">
                        <span class="nav-arrow">&laquo;</span>
                        <span class="nav-label">
                            <small><?php _e('Previous Course', 'ielts-course-manager'); ?></small>
                            <strong><?php echo esc_html($prev_course->post_title); ?></strong>
                        </span>
                    </a>
                <?php endif; ?>
            </div>
            <div class="nav-next">
                <?php if ($next_course): ?>
                    <a href="<?php echo get_permalink($next_course->ID); ?>" class="nav-link">
                        <span class="nav-label">
                            <small><?php _e('Next Course', 'ielts-course-manager'); ?></small>
                            <strong><?php echo esc_html($next_course->post_title); ?></strong>
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
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        .ielts-navigation .nav-prev {
            flex: 0 0 48%;
        }
        .ielts-navigation .nav-next {
            flex: 0 0 48%;
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
        </style>
    <?php endif; ?>
</div>
