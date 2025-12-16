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
            
            <div class="resources-list">
                <?php foreach ($resources as $resource): ?>
                    <?php
                    $resource_type = get_post_meta($resource->ID, '_ielts_cm_resource_type', true);
                    $resource_url = get_post_meta($resource->ID, '_ielts_cm_resource_url', true);
                    ?>
                    <div class="resource-item resource-type-<?php echo esc_attr($resource_type); ?>">
                        <div class="resource-icon">
                            <?php
                            switch ($resource_type) {
                                case 'video':
                                    echo '<span class="dashicons dashicons-video-alt3"></span>';
                                    break;
                                case 'audio':
                                    echo '<span class="dashicons dashicons-format-audio"></span>';
                                    break;
                                case 'link':
                                    echo '<span class="dashicons dashicons-admin-links"></span>';
                                    break;
                                default:
                                    echo '<span class="dashicons dashicons-media-document"></span>';
                            }
                            ?>
                        </div>
                        
                        <div class="resource-content">
                            <h4><?php echo esc_html($resource->post_title); ?></h4>
                            
                            <?php if ($resource->post_excerpt): ?>
                                <p><?php echo esc_html($resource->post_excerpt); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($resource_url): ?>
                                <a href="<?php echo esc_url($resource_url); ?>" target="_blank" class="button">
                                    <?php _e('Access Resource', 'ielts-course-manager'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($quizzes)): ?>
        <div class="lesson-quizzes">
            <h3><?php _e('Quizzes', 'ielts-course-manager'); ?></h3>
            
            <div class="quizzes-list">
                <?php
                $quiz_handler = new IELTS_CM_Quiz_Handler();
                
                foreach ($quizzes as $quiz):
                    $best_result = $user_id ? $quiz_handler->get_best_quiz_result($user_id, $quiz->ID) : null;
                ?>
                    <div class="quiz-item">
                        <div class="quiz-content">
                            <h4><?php echo esc_html($quiz->post_title); ?></h4>
                            
                            <?php if ($quiz->post_excerpt): ?>
                                <p><?php echo esc_html($quiz->post_excerpt); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($best_result): ?>
                                <div class="quiz-best-score">
                                    <?php _e('Best Score:', 'ielts-course-manager'); ?>
                                    <strong><?php echo round($best_result->percentage, 1); ?>%</strong>
                                    (<?php echo $best_result->score; ?> / <?php echo $best_result->max_score; ?>)
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="quiz-actions">
                            <a href="<?php echo get_permalink($quiz->ID); ?>" class="button button-primary">
                                <?php echo $best_result ? __('Retake Quiz', 'ielts-course-manager') : __('Take Quiz', 'ielts-course-manager'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
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
