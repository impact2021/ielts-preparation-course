<?php
/**
 * Template for displaying single quiz page
 * This template is loaded when viewing a quiz post directly
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if fullscreen mode is requested
$is_fullscreen = isset($_GET['fullscreen']) && $_GET['fullscreen'] === '1';

// In fullscreen mode, create minimal HTML structure with all styles
if ($is_fullscreen) {
    ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('ielts-quiz-fullscreen'); ?>>
    <?php
} else {
    // Load full header
    get_header();
}
?>

<style>
/* 
 * Inline styles are used here (instead of wp_add_inline_style) because:
 * 1. This template is loaded via template_include filter, not through standard enqueue
 * 2. Inline styles have highest specificity to override theme styles
 * 3. These styles are page-specific and should only apply to this template
 */
<?php if ($is_fullscreen): ?>
/* Fullscreen mode styles */
body {
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
}
body #page,
body #main,
body #primary,
body .content-area,
body .site-main {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: 100vh !important;
    overflow: auto !important;
}
body .ielts-computer-based-quiz {
    height: 100vh !important;
    display: flex !important;
    flex-direction: column !important;
}
body .ielts-computer-based-quiz .quiz-header {
    display: none !important;
}
body .ielts-computer-based-quiz .quiz-form {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}
body .computer-based-container {
    flex: 1 !important;
    margin-bottom: 0 !important;
    min-height: 0 !important;
    overflow: hidden !important;
}
body .question-navigation {
    flex-shrink: 0 !important;
    margin-bottom: 20px !important;
}
body .reading-column,
body .questions-column {
    max-height: 100% !important;
}
<?php else: ?>
body.ielts-quiz-single #main.site-main {
    padding: 60px 40px 20px !important;
}
/* Fallback for themes with different structure */
body.ielts-quiz-single .site-main,
body.ielts-quiz-single #primary,
body.ielts-quiz-single .content-area {
    padding-top: 60px !important;
    padding-bottom: 20px !important;
}
/* Focus mode: Remove padding to maximize space for exercises */
body.ielts-quiz-focus-mode.ielts-quiz-single #main.site-main,
body.ielts-quiz-focus-mode.ielts-quiz-single .site-main,
body.ielts-quiz-focus-mode.ielts-quiz-single #primary,
body.ielts-quiz-focus-mode.ielts-quiz-single .content-area {
    padding: 0 !important;
}
<?php endif; ?>
</style>

<div id="primary" class="content-area ielts-full-width">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();
            
            // Get the quiz
            $quiz = get_post();
            $quiz_id = $quiz->ID;
            
            // Get quiz questions
            $questions = get_post_meta($quiz_id, '_ielts_cm_questions', true);
            if (!is_array($questions)) {
                $questions = array();
            }
            
            // Get course and lesson IDs
            $course_id = get_post_meta($quiz_id, '_ielts_cm_course_id', true);
            $lesson_id = get_post_meta($quiz_id, '_ielts_cm_lesson_id', true);
            
            // Check if user has access to this quiz
            $user_id = get_current_user_id();
            $has_access = false;
            
            if ($user_id && $course_id) {
                $enrollment = new IELTS_CM_Enrollment();
                $has_access = $enrollment->is_enrolled($user_id, $course_id);
            }
            
            if (!$has_access && $course_id):
                // Show access restricted message
                
                // Check if user has a trial membership
                $is_expired_trial = false;
                $is_active_trial = false;
                if ($user_id) {
                    $membership = new IELTS_CM_Membership();
                    $membership_type = $membership->get_user_membership($user_id);
                    $membership_status = $membership->get_user_membership_status($user_id);
                    
                    if ($membership_type && IELTS_CM_Membership::is_trial_membership($membership_type)) {
                        if ($membership_status === IELTS_CM_Membership::STATUS_EXPIRED) {
                            $is_expired_trial = true;
                        } elseif ($membership_status === IELTS_CM_Membership::STATUS_ACTIVE) {
                            $is_active_trial = true;
                        }
                    }
                }
                
                ?>
                <div class="ielts-access-restricted">
                    <div class="access-restricted-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 1a11 11 0 1 0 0 22 11 11 0 0 0 0-22zm0 20a9 9 0 1 1 0-18 9 9 0 0 1 0 18z" fill="#666"/>
                            <path d="M12 7a1 1 0 0 1 1 1v6a1 1 0 0 1-2 0V8a1 1 0 0 1 1-1zm0 10a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" fill="#666"/>
                        </svg>
                    </div>
                    <h2><?php _e('Access Restricted', 'ielts-course-manager'); ?></h2>
                    <?php if ($is_expired_trial): ?>
                        <p><?php _e('Your trial membership has expired.', 'ielts-course-manager'); ?></p>
                        <p>
                            <?php 
                            $upgrade_url = get_option('ielts_cm_full_member_page_url', home_url());
                            ?>
                            <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-primary">
                                <?php _e('Become a Member Now', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php elseif ($is_active_trial): ?>
                        <p><?php _e('You have an active trial membership, but you need to enroll in this course to access its exercises.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="button button-primary">
                                <?php _e('Enroll in Course', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php elseif (!is_user_logged_in()): ?>
                        <p><?php _e('You need to be enrolled in this course to access this exercise.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                                <?php _e('Login', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php else: ?>
                        <p><?php _e('You need to be enrolled in this course to access this exercise.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="button button-primary">
                                <?php _e('View Course', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                <style>
                .ielts-access-restricted {
                    text-align: center;
                    padding: 60px 20px;
                    max-width: 600px;
                    margin: 0 auto;
                }
                .access-restricted-icon {
                    margin-bottom: 20px;
                }
                .ielts-access-restricted h2 {
                    font-size: 28px;
                    margin-bottom: 15px;
                    color: #333;
                }
                .ielts-access-restricted p {
                    font-size: 16px;
                    color: #666;
                    margin-bottom: 20px;
                }
                .ielts-access-restricted .button {
                    padding: 12px 30px;
                    font-size: 16px;
                }
                </style>
                <?php
            else:
                // User has access, show the quiz content
            
            // Get layout type
            $layout_type = get_post_meta($quiz_id, '_ielts_cm_layout_type', true);
            if (!$layout_type) {
                $layout_type = 'two_column_reading';
            }
            
            // Determine which template to use
            // For backward compatibility, map old values to new ones
            if ($layout_type === 'computer_based') {
                // Check old cbt_test_type for backward compatibility
                $old_cbt_test_type = get_post_meta($quiz_id, '_ielts_cm_cbt_test_type', true);
                if ($old_cbt_test_type === 'listening') {
                    $layout_type = 'two_column_listening';
                } elseif ($old_cbt_test_type === 'reading') {
                    $layout_type = 'two_column_reading';
                } else {
                    $layout_type = 'two_column_reading';
                }
            } elseif ($layout_type === 'standard' || $layout_type === 'one_column_exercise' || $layout_type === 'two_column_exercise') {
                // Map deprecated templates to two_column_reading
                $layout_type = 'two_column_reading';
            } elseif ($layout_type === 'listening_practice' || $layout_type === 'listening_exercise') {
                $layout_type = 'two_column_listening';
            }
            
            // Include the appropriate template based on layout type
            // Both two_column_reading and two_column_listening use the same template
            if (in_array($layout_type, array('two_column_reading', 'two_column_listening'))) {
                $template = IELTS_CM_PLUGIN_DIR . 'templates/single-quiz-computer-based.php';
            } else {
                $template = IELTS_CM_PLUGIN_DIR . 'templates/single-quiz.php';
            }
            
            if (file_exists($template)) {
                include $template;
            }
            
            endif; // end has_access check
            
        endwhile;
        ?>
    </main>
</div>

<?php
// Load footer or close HTML structure based on mode
if ($is_fullscreen) {
    // Close fullscreen HTML structure
    wp_footer();
    ?>
</body>
</html>
    <?php
} else {
    // Load full footer
    get_footer();
}
?>
