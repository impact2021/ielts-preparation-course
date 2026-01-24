<?php
/**
 * Template for displaying single lesson page
 * This template is loaded when viewing a lesson post directly
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
body.ielts-lesson-single #main.site-main {
    padding: 30px 40px !important;
}
/* Fallback for themes with different structure */
body.ielts-lesson-single .site-main,
body.ielts-lesson-single #primary,
body.ielts-lesson-single .content-area {
    padding-top: 30px !important;
    padding-bottom: 30px !important;
}
</style>

<div id="primary" class="content-area ielts-full-width">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();
            
            // Get the lesson
            $lesson = get_post();
            $lesson_id = $lesson->ID;
            
            // Get course ID
            $course_id = get_post_meta($lesson_id, '_ielts_cm_course_id', true);
            
            // Get resources for this lesson - check both old and new meta keys
            global $wpdb;
            // Check for both integer and string serialization in lesson_ids array
            // Integer: i:123; String: s:3:"123";
            $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
            $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
            
            $resource_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
            ", $lesson_id, $int_pattern, $str_pattern));
            
            $resources = array();
            if (!empty($resource_ids)) {
                $resources = get_posts(array(
                    'post_type' => 'ielts_resource',
                    'posts_per_page' => -1,
                    'post__in' => $resource_ids,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'post_status' => 'publish'
                ));
            }
            
            // Get quizzes for this lesson - check both old and new meta keys
            $quiz_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
            ", $lesson_id, $int_pattern, $str_pattern));
            
            $quizzes = array();
            if (!empty($quiz_ids)) {
                $quizzes = get_posts(array(
                    'post_type' => 'ielts_quiz',
                    'posts_per_page' => -1,
                    'post__in' => $quiz_ids,
                    'post_status' => 'publish'
                ));
            }
            
            // Include the single lesson template
            include IELTS_CM_PLUGIN_DIR . 'templates/single-lesson.php';
            
        endwhile;
        ?>
    </main>
</div>

<?php
get_footer();
