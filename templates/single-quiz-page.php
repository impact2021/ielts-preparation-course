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
    padding: 60px 40px !important;
}
/* Fallback for themes with different structure */
body.ielts-quiz-single .site-main,
body.ielts-quiz-single #primary,
body.ielts-quiz-single .content-area {
    padding-top: 60px !important;
    padding-bottom: 60px !important;
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
            if (!$questions) {
                $questions = array();
            }
            
            // Get course and lesson IDs
            $course_id = get_post_meta($quiz_id, '_ielts_cm_course_id', true);
            $lesson_id = get_post_meta($quiz_id, '_ielts_cm_lesson_id', true);
            
            // Get layout type
            $layout_type = get_post_meta($quiz_id, '_ielts_cm_layout_type', true);
            if (!$layout_type) {
                $layout_type = 'standard';
            }
            
            // Include the appropriate template based on layout type
            if ($layout_type === 'computer_based') {
                $template = IELTS_CM_PLUGIN_DIR . 'templates/single-quiz-computer-based.php';
            } else {
                $template = IELTS_CM_PLUGIN_DIR . 'templates/single-quiz.php';
            }
            
            if (file_exists($template)) {
                include $template;
            }
            
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
