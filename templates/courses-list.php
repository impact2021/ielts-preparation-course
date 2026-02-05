<?php
/**
 * Template for displaying courses list
 */

if (!defined('ABSPATH')) {
    exit;
}

$enrollment = new IELTS_CM_Enrollment();
$progress_tracker = new IELTS_CM_Progress_Tracker();
$user_id = get_current_user_id();

// Set default columns if not provided
if (!isset($columns)) {
    $columns = 5;
}
?>

<div class="ielts-courses-list" data-columns="<?php echo esc_attr($columns); ?>" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
    <?php if (!empty($courses)): ?>
        <?php foreach ($courses as $course): ?>
            <div class="ielts-course-item">
                <?php if (has_post_thumbnail($course->ID)): ?>
                    <div class="course-thumbnail">
                        <a href="<?php echo get_permalink($course->ID); ?>">
                            <?php echo get_the_post_thumbnail($course->ID, 'medium'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="course-content">
                    <h3>
                        <a href="<?php echo get_permalink($course->ID); ?>">
                            <?php echo esc_html($course->post_title); ?>
                        </a>
                    </h3>
                    
                    <?php if ($course->post_excerpt): ?>
                        <div class="course-excerpt">
                            <?php 
                            // Apply WordPress content filters to process embeds and shortcodes
                            echo apply_filters('the_content', $course->post_excerpt);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="course-meta">
                        <?php
                        $duration = get_post_meta($course->ID, '_ielts_cm_duration', true);
                        $difficulty = get_post_meta($course->ID, '_ielts_cm_difficulty', true);
                        
                        if ($duration):
                        ?>
                            <span class="course-duration">
                                <i class="dashicons dashicons-clock"></i>
                                <?php printf(__('%s hours', 'ielts-course-manager'), $duration); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($difficulty): ?>
                            <span class="course-difficulty">
                                <i class="dashicons dashicons-star-filled"></i>
                                <?php echo esc_html(ucfirst($difficulty)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (is_user_logged_in() && $enrollment->is_enrolled($user_id, $course->ID)): ?>
                        <div class="course-progress">
                            <?php 
                            $completion = $progress_tracker->get_course_completion_percentage($user_id, $course->ID);
                            ?>
                            <div class="progress-label">
                                <?php _e('Your Progress:', 'ielts-course-manager'); ?>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo esc_attr(round($completion, 1)); ?>%;">
                                    <span class="progress-text"><?php echo esc_html(round($completion, 1)); ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="course-actions">
                        <?php if (is_user_logged_in()): ?>
                            <?php if ($enrollment->is_enrolled($user_id, $course->ID)): ?>
                                <a href="<?php echo get_permalink($course->ID); ?>" class="button button-primary">
                                    <?php _e('Continue Course', 'ielts-course-manager'); ?>
                                </a>
                            <?php else: ?>
                                <button class="button button-primary enroll-button" data-course-id="<?php echo $course->ID; ?>">
                                    <?php _e('Enroll Now', 'ielts-course-manager'); ?>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo esc_url(IELTS_CM_Frontend::get_custom_login_url(get_permalink($course->ID))); ?>" class="button">
                                <?php _e('Login to Enroll', 'ielts-course-manager'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p><?php _e('No courses found.', 'ielts-course-manager'); ?></p>
    <?php endif; ?>
</div>
