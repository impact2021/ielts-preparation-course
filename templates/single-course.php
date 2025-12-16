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
        <h2><?php echo esc_html($course->post_title); ?></h2>
        
        <?php if (has_post_thumbnail($course->ID)): ?>
            <div class="course-featured-image">
                <?php echo get_the_post_thumbnail($course->ID, 'large'); ?>
            </div>
        <?php endif; ?>
        
        <div class="course-meta">
            <?php
            $duration = get_post_meta($course->ID, '_ielts_cm_duration', true);
            $difficulty = get_post_meta($course->ID, '_ielts_cm_difficulty', true);
            
            if ($duration):
            ?>
                <span class="course-duration">
                    <strong><?php _e('Duration:', 'ielts-course-manager'); ?></strong>
                    <?php printf(__('%s hours', 'ielts-course-manager'), $duration); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($difficulty): ?>
                <span class="course-difficulty">
                    <strong><?php _e('Level:', 'ielts-course-manager'); ?></strong>
                    <?php echo esc_html(ucfirst($difficulty)); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($is_enrolled): ?>
                <span class="course-progress">
                    <strong><?php _e('Progress:', 'ielts-course-manager'); ?></strong>
                    <?php echo round($completion, 1); ?>%
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (!$is_enrolled && is_user_logged_in()): ?>
            <div class="enrollment-section">
                <button class="button button-primary enroll-button" data-course-id="<?php echo $course->ID; ?>">
                    <?php _e('Enroll in this Course', 'ielts-course-manager'); ?>
                </button>
            </div>
        <?php elseif (!is_user_logged_in()): ?>
            <div class="enrollment-section">
                <a href="<?php echo wp_login_url(get_permalink($course->ID)); ?>" class="button button-primary">
                    <?php _e('Login to Enroll', 'ielts-course-manager'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="course-description">
        <?php echo wpautop($course->post_content); ?>
    </div>
    
    <?php if ($is_enrolled && !empty($lessons)): ?>
        <div class="course-lessons">
            <h3><?php _e('Course Lessons', 'ielts-course-manager'); ?></h3>
            
            <div class="lessons-list">
                <?php foreach ($lessons as $lesson): ?>
                    <?php
                    $is_completed = $progress_tracker->is_lesson_completed($user_id, $lesson->ID);
                    $lesson_duration = get_post_meta($lesson->ID, '_ielts_cm_duration', true);
                    ?>
                    <div class="lesson-item <?php echo $is_completed ? 'completed' : ''; ?>">
                        <div class="lesson-status">
                            <?php if ($is_completed): ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-marker"></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="lesson-content">
                            <h4>
                                <a href="<?php echo get_permalink($lesson->ID); ?>">
                                    <?php echo esc_html($lesson->post_title); ?>
                                </a>
                            </h4>
                            
                            <?php if ($lesson->post_excerpt): ?>
                                <p><?php echo esc_html($lesson->post_excerpt); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($lesson_duration): ?>
                                <span class="lesson-duration">
                                    <?php printf(__('%s minutes', 'ielts-course-manager'), $lesson_duration); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="lesson-actions">
                            <a href="<?php echo get_permalink($lesson->ID); ?>" class="button">
                                <?php echo $is_completed ? __('Review', 'ielts-course-manager') : __('Start', 'ielts-course-manager'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif (!empty($lessons)): ?>
        <div class="course-curriculum">
            <h3><?php _e('Course Curriculum', 'ielts-course-manager'); ?></h3>
            <p><?php printf(__('This course contains %d lessons.', 'ielts-course-manager'), count($lessons)); ?></p>
        </div>
    <?php endif; ?>
</div>
