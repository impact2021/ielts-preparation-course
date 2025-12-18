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
$timer_minutes = get_post_meta($quiz->ID, '_ielts_cm_timer_minutes', true);
?>

<div class="ielts-single-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>" data-timer-minutes="<?php echo esc_attr($timer_minutes); ?>">
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
            <?php 
            // Apply WordPress content filters to process embeds and shortcodes
            echo apply_filters('the_content', $quiz->post_content);
            ?>
        </div>
        
        <div class="quiz-info">
            <p>
                <strong><?php _e('Number of Questions:', 'ielts-course-manager'); ?></strong>
                <?php echo count($questions); ?>
            </p>
            <?php if ($timer_minutes > 0): ?>
            <p>
                <strong><?php _e('Time Limit:', 'ielts-course-manager'); ?></strong>
                <?php echo intval($timer_minutes); ?> <?php _e('minutes', 'ielts-course-manager'); ?>
            </p>
            <?php endif; ?>
        </div>
        
        <?php if ($timer_minutes > 0 && !empty($questions) && is_user_logged_in()): ?>
        <div id="quiz-timer" class="quiz-timer">
            <strong><?php _e('Time Remaining:', 'ielts-course-manager'); ?></strong>
            <span id="timer-display">--:--</span>
        </div>
        <?php endif; ?>
    </div>
    
    <form id="ielts-quiz-form" class="quiz-form">
        <div class="quiz-questions">
            <?php if (!empty($questions)): ?>
                <?php foreach ($questions as $index => $question): ?>
                    <div class="quiz-question">
                        <h4>
                            <?php printf(__('Question %d', 'ielts-course-manager'), $index + 1); ?>
                            <span class="question-points">(<?php printf(_n('%s point', '%s points', $question['points'], 'ielts-course-manager'), $question['points']); ?>)</span>
                        </h4>
                        
                        <div class="question-text"><?php echo wp_kses_post($question['question']); ?></div>
                        
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
                                                   value="<?php echo $opt_index; ?>">
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
                                               value="true">
                                        <span><?php _e('True', 'ielts-course-manager'); ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" 
                                               name="answer_<?php echo $index; ?>" 
                                               value="false">
                                        <span><?php _e('False', 'ielts-course-manager'); ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" 
                                               name="answer_<?php echo $index; ?>" 
                                               value="not_given">
                                        <span><?php _e('Not Given', 'ielts-course-manager'); ?></span>
                                    </label>
                                </div>
                                <?php
                                break;
                                
                            case 'fill_blank':
                                ?>
                                <div class="question-answer">
                                    <input type="text" 
                                           name="answer_<?php echo $index; ?>" 
                                           class="answer-input">
                                </div>
                                <?php
                                break;
                                
                            case 'summary_completion':
                                // Summary completion - similar to fill in the blank but for paragraph/summary contexts
                                // The question text should contain the paragraph with a blank indicated
                                ?>
                                <div class="question-answer">
                                    <input type="text" 
                                           name="answer_<?php echo $index; ?>" 
                                           class="answer-input">
                                </div>
                                <?php
                                break;
                                
                            case 'essay':
                                ?>
                                <div class="question-answer">
                                    <textarea name="answer_<?php echo $index; ?>" 
                                              class="answer-textarea" 
                                              rows="6"></textarea>
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
    
    <?php
    // Previous/Next quiz navigation within the lesson
    if ($lesson_id) {
        global $wpdb;
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
        ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
        
        $all_quizzes = array();
        if (!empty($quiz_ids)) {
            $all_quizzes = get_posts(array(
                'post_type' => 'ielts_quiz',
                'posts_per_page' => -1,
                'post__in' => $quiz_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish'
            ));
        }
        
        $current_index = -1;
        foreach ($all_quizzes as $index => $q) {
            if ($q->ID == $quiz->ID) {
                $current_index = $index;
                break;
            }
        }
        
        $prev_quiz = ($current_index > 0) ? $all_quizzes[$current_index - 1] : null;
        $next_quiz = ($current_index >= 0 && $current_index < count($all_quizzes) - 1) ? $all_quizzes[$current_index + 1] : null;
        ?>
        
        <?php if ($prev_quiz || $next_quiz): ?>
            <div class="ielts-navigation">
                <div class="nav-prev">
                    <?php if ($prev_quiz): ?>
                        <a href="<?php echo get_permalink($prev_quiz->ID); ?>" class="nav-link">
                            <span class="nav-arrow">&laquo;</span>
                            <span class="nav-label">
                                <small><?php _e('Previous Exercise', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($prev_quiz->post_title); ?></strong>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-next">
                    <?php if ($next_quiz): ?>
                        <a href="<?php echo get_permalink($next_quiz->ID); ?>" class="nav-link">
                            <span class="nav-label">
                                <small><?php _e('Next Exercise', 'ielts-course-manager'); ?></small>
                                <strong><?php echo esc_html($next_quiz->post_title); ?></strong>
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
    <?php } ?>
</div>
