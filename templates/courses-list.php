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
?>

<div class="ielts-courses-list">
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
                            <?php echo wpautop($course->post_excerpt); ?>
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
                                <div class="progress-fill" style="width: <?php echo round($completion, 1); ?>%;">
                                    <span class="progress-text"><?php echo round($completion, 1); ?>%</span>
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
                            <a href="<?php echo wp_login_url(get_permalink($course->ID)); ?>" class="button">
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

<style>
.ielts-courses-list .course-progress {
    margin: 15px 0;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 5px;
}

.ielts-courses-list .progress-label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 14px;
}

.ielts-courses-list .progress-bar {
    width: 100%;
    height: 24px;
    background-color: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.ielts-courses-list .progress-fill {
    height: 100%;
    background: linear-gradient(to right, #4caf50, #66bb6a);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: width 0.5s ease;
    min-width: 30px;
}

.ielts-courses-list .progress-text {
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
</style>
