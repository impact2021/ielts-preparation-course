<?php
/**
 * Template for displaying single quiz page
 * This template is loaded when viewing a quiz post directly
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<style>
/* 
 * Inline styles are used here (instead of wp_add_inline_style) because:
 * 1. This template is loaded via template_include filter, not through standard enqueue
 * 2. Inline styles have highest specificity to override theme styles
 * 3. These styles are page-specific and should only apply to this template
 */
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
get_footer();
