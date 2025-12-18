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
$timer_minutes = get_post_meta($quiz->ID, '_ielts_cm_timer_minutes', true);
?>

<div class="ielts-computer-based-quiz" data-quiz-id="<?php echo $quiz->ID; ?>" data-course-id="<?php echo $course_id; ?>" data-lesson-id="<?php echo $lesson_id; ?>" data-timer-minutes="<?php echo esc_attr($timer_minutes); ?>">
    <?php 
    // Check if in fullscreen mode
    $is_fullscreen = isset($_GET['fullscreen']) && $_GET['fullscreen'] === '1';
    ?>
    
    <?php if (!$is_fullscreen): ?>
    <div class="quiz-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;"><?php echo esc_html($quiz->post_title); ?></h2>
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
    <?php endif; ?>
    
    <?php if (!$is_fullscreen): ?>
        <!-- Force fullscreen mode for CBT tests -->
        <div class="cbt-fullscreen-notice" style="text-align: center; padding: 40px 20px; background: #f9f9f9; border: 2px solid #0073aa; border-radius: 8px; margin: 20px 0;">
            <p style="font-size: 1.2em; margin-bottom: 20px; color: #333;">
                <?php _e('This computer-based test must be viewed in fullscreen mode for the best experience.', 'ielts-course-manager'); ?>
            </p>
            <button type="button" class="button button-primary button-large ielts-fullscreen-btn" id="open-modal-btn" style="font-size: 1.1em; padding: 12px 30px;">
                <span class="dashicons dashicons-fullscreen-alt" style="vertical-align: middle; font-size: 1.2em;"></span>
                <?php _e('Open in Fullscreen', 'ielts-course-manager'); ?>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($questions) && is_user_logged_in()): ?>
        <form id="ielts-quiz-form" class="quiz-form" style="<?php echo !$is_fullscreen ? 'display:none;' : ''; ?>">
            <?php if ($is_fullscreen && $timer_minutes > 0): ?>
            <div id="quiz-timer-fullscreen" class="quiz-timer-fullscreen">
                <strong><?php _e('Time Remaining:', 'ielts-course-manager'); ?></strong>
                <span id="timer-display-fullscreen">--:--</span>
            </div>
            <?php endif; ?>
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
                <button type="submit" class="button button-primary quiz-submit-btn">
                    <?php _e('Submit Quiz', 'ielts-course-manager'); ?>
                </button>
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

<style>
#cbt-fullscreen-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #fff;
    z-index: 999999;
    overflow: auto;
}
#cbt-fullscreen-modal.active {
    display: block;
}
#cbt-fullscreen-modal .modal-close-btn {
    position: fixed;
    top: 10px;
    right: 10px;
    z-index: 1000000;
    background: #dc3232;
    color: #fff;
    border: none;
    padding: 6px 12px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 13px;
    line-height: 1.4;
}
#cbt-fullscreen-modal .modal-close-btn:hover {
    background: #a00;
}
#cbt-fullscreen-modal #modal-content {
    padding: 50px 20px 20px;
}
#cbt-fullscreen-modal .computer-based-container {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}
#cbt-fullscreen-modal .reading-column,
#cbt-fullscreen-modal .questions-column {
    flex: 1 1 50%;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding: 20px;
    border: 1px solid #e0e0e0;
    position: relative;
}
#cbt-fullscreen-modal .quiz-timer-fullscreen {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 999;
    background: #fff;
    border-bottom: 2px solid #0073aa;
    padding: 10px 20px;
    text-align: center;
    margin: -20px -20px 20px -20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
#cbt-fullscreen-modal .computer-based-container {
    margin-top: 0;
}
#cbt-fullscreen-modal .reading-column {
    border-right: 2px solid #e0e0e0;
}
#cbt-fullscreen-modal .question-navigation {
    position: sticky;
    bottom: 0;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin: 20px 0 0 0;
    padding: 15px 20px;
}
</style>

<div id="cbt-fullscreen-modal">
    <button type="button" class="modal-close-btn" id="close-modal-btn"><?php _e('Exit Fullscreen', 'ielts-course-manager'); ?></button>
    <div id="modal-content"></div>
</div>

<script>
// Modal fullscreen for CBT exercises
jQuery(document).ready(function($) {
    var modal = $('#cbt-fullscreen-modal');
    var form = $('#ielts-quiz-form');
    var isFullscreenMode = <?php echo $is_fullscreen ? 'true' : 'false'; ?>;
    var modalTimerInterval = null;
    
    if (isFullscreenMode) {
        // Already in fullscreen, show the form
        form.show();
        
        // Initialize timer for fullscreen mode
        var timerMinutes = $('.ielts-computer-based-quiz').data('timer-minutes');
        if (timerMinutes && timerMinutes > 0) {
            initializeTimer(timerMinutes, form);
        }
    }
    
    $('#open-modal-btn').on('click', function(e) {
        e.preventDefault();
        
        // Clone the form into modal
        var formClone = form.clone(true, true);
        formClone.show();
        $('#modal-content').html(formClone);
        
        // Show modal
        modal.addClass('active');
        
        // Disable body scroll
        $('body').css('overflow', 'hidden');
        
        // Initialize timer if present
        var timerMinutes = $('.ielts-computer-based-quiz').data('timer-minutes');
        if (timerMinutes && timerMinutes > 0) {
            modalTimerInterval = initializeTimer(timerMinutes, formClone);
        }
    });
    
    $('#close-modal-btn').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to exit? Your progress will be lost.', 'ielts-course-manager'); ?>')) {
            // Clean up timer
            if (modalTimerInterval) {
                clearInterval(modalTimerInterval);
                modalTimerInterval = null;
            }
            modal.removeClass('active');
            $('body').css('overflow', '');
            $('#modal-content').html('');
        }
    });
    
    function initializeTimer(minutes, targetForm) {
        var totalSeconds = minutes * 60;
        var timerDisplay = targetForm.find('#timer-display-fullscreen');
        
        if (timerDisplay.length === 0) {
            return null;
        }
        
        var timerInterval = setInterval(function() {
            totalSeconds--;
            
            var mins = Math.floor(totalSeconds / 60);
            var secs = totalSeconds % 60;
            timerDisplay.text(mins + ':' + (secs < 10 ? '0' : '') + secs);
            
            // Warning at 5 minutes
            if (totalSeconds === 300) {
                timerDisplay.css('color', 'orange');
            }
            
            // Critical at 1 minute
            if (totalSeconds === 60) {
                timerDisplay.css('color', 'red');
            }
            
            if (totalSeconds <= 0) {
                clearInterval(timerInterval);
                timerDisplay.text('0:00').css('color', 'red');
                
                // Auto-submit
                alert('<?php _e('Time is up! The exercise will be submitted automatically.', 'ielts-course-manager'); ?>');
                targetForm.submit();
            }
        }, 1000);
        
        return timerInterval;
    }
});
</script>
