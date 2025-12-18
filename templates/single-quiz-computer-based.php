<?php
/**
 * Template for displaying single quiz in computer-based IELTS layout
 * Two-column layout with reading text on left and questions on right
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

$reading_texts = get_post_meta($quiz->ID, '_ielts_cm_reading_texts', true);
if (!$reading_texts) {
    $reading_texts = array();
}
?>

<div class="ielts-computer-based-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>">
    <div class="quiz-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;"><?php echo esc_html($quiz->post_title); ?></h2>
            <?php 
            // Show fullscreen button if not already in fullscreen mode
            $is_fullscreen = isset($_GET['fullscreen']) && $_GET['fullscreen'] === '1';
            if (!$is_fullscreen): 
            ?>
                <a href="<?php echo add_query_arg('fullscreen', '1', get_permalink($quiz->ID)); ?>" 
                   class="button button-secondary ielts-fullscreen-btn"
                   data-fullscreen-url="<?php echo esc_url(add_query_arg('fullscreen', '1', get_permalink($quiz->ID))); ?>"
                   style="white-space: nowrap;">
                    <span class="dashicons dashicons-fullscreen-alt" style="vertical-align: middle;"></span>
                    <?php _e('Open Fullscreen', 'ielts-course-manager'); ?>
                </a>
            <?php endif; ?>
        </div>
        
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
    </div>
    
    <?php if (!empty($questions) && is_user_logged_in()): ?>
        <form id="ielts-quiz-form" class="quiz-form">
            <div class="computer-based-container">
                <!-- Left Column: Reading Texts -->
                <div class="reading-column">
                    <div class="reading-content">
                        <?php if (!empty($reading_texts)): ?>
                            <?php foreach ($reading_texts as $index => $text): ?>
                                <div class="reading-text-section" id="reading-text-<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display:none;' : ''; ?>">
                                    <?php if (!empty($text['title'])): ?>
                                        <h3 class="reading-title"><?php echo esc_html($text['title']); ?></h3>
                                    <?php endif; ?>
                                    <div class="reading-text">
                                        <?php echo wp_kses_post(wpautop($text['content'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="reading-text-section">
                                <p class="no-reading-text"><?php _e('No reading text provided for this exercise.', 'ielts-course-manager'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column: Questions -->
                <div class="questions-column">
                    <div class="questions-content">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="quiz-question" id="question-<?php echo $index; ?>" data-reading-text-id="<?php echo esc_attr($question['reading_text_id'] ?? ''); ?>">
                                <h4 class="question-number">
                                    <?php printf(__('Question %d', 'ielts-course-manager'), $index + 1); ?>
                                    <span class="question-points">(<?php echo $question['points']; ?> <?php _e('points', 'ielts-course-manager'); ?>)</span>
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
                                            <label class="option-label">
                                                <input type="radio" 
                                                       name="answer_<?php echo $index; ?>" 
                                                       value="not_given" 
                                                       required>
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
                                                   class="answer-input" 
                                                   required>
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
                        
                        <div class="quiz-submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Submit Quiz', 'ielts-course-manager'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Navigation: Jump to Questions -->
            <div class="question-navigation">
                <div class="nav-label"><?php _e('Jump to Question:', 'ielts-course-manager'); ?></div>
                <div class="question-buttons">
                    <?php foreach ($questions as $index => $question): ?>
                        <button type="button" class="question-nav-btn" data-question="<?php echo $index; ?>">
                            <?php echo $index + 1; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
        
        <div id="quiz-result" class="quiz-result" style="display: none;"></div>
        
    <?php elseif (!empty($questions) && !is_user_logged_in()): ?>
        <div class="quiz-login-notice">
            <p><?php _e('Please log in to take this quiz.', 'ielts-course-manager'); ?></p>
            <a href="<?php echo wp_login_url(get_permalink($quiz->ID)); ?>" class="button button-primary">
                <?php _e('Login', 'ielts-course-manager'); ?>
            </a>
        </div>
    <?php else: ?>
        <p><?php _e('No questions available for this quiz.', 'ielts-course-manager'); ?></p>
    <?php endif; ?>
    
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
        <?php endif; ?>
    <?php } ?>
</div>

<script>
// Safe fullscreen launcher for CBT exercises
jQuery(document).ready(function($) {
    $('.ielts-fullscreen-btn').on('click', function(e) {
        e.preventDefault();
        var url = $(this).data('fullscreen-url');
        if (url) {
            var width = Math.max(800, window.screen.availWidth || window.screen.width);
            var height = Math.max(600, window.screen.availHeight || window.screen.height);
            var features = 'width=' + width + ',height=' + height + ',fullscreen=yes,scrollbars=yes';
            window.open(url, '_blank', features);
        }
    });
});
</script>
