<?php
/**
 * Template for displaying single quiz in shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!$questions) {
    $questions = array();
}

$user_id = get_current_user_id();
$pass_percentage = get_post_meta($quiz->ID, '_ielts_cm_pass_percentage', true);
if (!$pass_percentage) {
    $pass_percentage = 70;
}
?>

<div class="ielts-single-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>">
    <div class="quiz-header">
        <h2><?php echo esc_html($quiz->post_title); ?></h2>
        
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
            <?php echo wpautop($quiz->post_content); ?>
        </div>
        
        <div class="quiz-info">
            <p>
                <strong><?php _e('Passing Score:', 'ielts-course-manager'); ?></strong>
                <?php echo $pass_percentage; ?>%
            </p>
            <p>
                <strong><?php _e('Number of Questions:', 'ielts-course-manager'); ?></strong>
                <?php echo count($questions); ?>
            </p>
        </div>
    </div>
    
    <form id="ielts-quiz-form" class="quiz-form">
        <div class="quiz-questions">
            <?php if (!empty($questions)): ?>
                <?php foreach ($questions as $index => $question): ?>
                    <div class="quiz-question">
                        <h4>
                            <?php printf(__('Question %d', 'ielts-course-manager'), $index + 1); ?>
                            <span class="question-points">(<?php echo $question['points']; ?> <?php _e('points', 'ielts-course-manager'); ?>)</span>
                        </h4>
                        
                        <p class="question-text"><?php echo esc_html($question['question']); ?></p>
                        
                        <?php
                        switch ($question['type']) {
                            case 'multiple_choice':
                                $options = array_filter(explode("\n", $question['options']));
                                ?>
                                <div class="question-options">
                                    <?php foreach ($options as $opt_index => $option): ?>
                                        <label class="option-label">
                                            <input type="radio" 
                                                   name="answer_<?php echo $index; ?>" 
                                                   value="<?php echo $opt_index; ?>" 
                                                   required>
                                            <span><?php echo esc_html(trim($option)); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                break;
                                
                            case 'true_false':
                                ?>
                                <div class="question-options">
                                    <label class="option-label">
                                        <input type="radio" 
                                               name="answer_<?php echo $index; ?>" 
                                               value="true" 
                                               required>
                                        <span><?php _e('True', 'ielts-course-manager'); ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" 
                                               name="answer_<?php echo $index; ?>" 
                                               value="false" 
                                               required>
                                        <span><?php _e('False', 'ielts-course-manager'); ?></span>
                                    </label>
                                </div>
                                <?php
                                break;
                                
                            case 'fill_blank':
                                ?>
                                <div class="question-answer">
                                    <input type="text" 
                                           name="answer_<?php echo $index; ?>" 
                                           class="answer-input" 
                                           required>
                                </div>
                                <?php
                                break;
                                
                            case 'essay':
                                ?>
                                <div class="question-answer">
                                    <textarea name="answer_<?php echo $index; ?>" 
                                              class="answer-textarea" 
                                              rows="6" 
                                              required></textarea>
                                    <p class="essay-note">
                                        <?php _e('Note: Essay questions will be reviewed manually.', 'ielts-course-manager'); ?>
                                    </p>
                                </div>
                                <?php
                                break;
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No questions available for this quiz.', 'ielts-course-manager'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($questions) && is_user_logged_in()): ?>
            <div class="quiz-submit">
                <button type="submit" class="button button-primary">
                    <?php _e('Submit Quiz', 'ielts-course-manager'); ?>
                </button>
            </div>
        <?php elseif (!is_user_logged_in()): ?>
            <div class="quiz-login-notice">
                <p><?php _e('Please log in to take this quiz.', 'ielts-course-manager'); ?></p>
                <a href="<?php echo wp_login_url(get_permalink($quiz->ID)); ?>" class="button button-primary">
                    <?php _e('Login', 'ielts-course-manager'); ?>
                </a>
            </div>
        <?php endif; ?>
    </form>
    
    <div id="quiz-result" class="quiz-result" style="display: none;"></div>
</div>
