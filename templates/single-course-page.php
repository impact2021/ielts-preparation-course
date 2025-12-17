<?php
/**
 * Template for displaying single course page
 * This template is loaded when viewing a course post directly
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
body.ielts-course-single #main.site-main {
    padding: 30px 40px !important;
}
/* Fallback for themes with different structure */
body.ielts-course-single .site-main,
body.ielts-course-single #primary,
body.ielts-course-single .content-area {
    padding-top: 30px !important;
    padding-bottom: 30px !important;
}
</style>

<div id="primary" class="content-area ielts-full-width">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();
            
            // Get the course
            $course = get_post();
            
            // Get lessons for this course - check both old and new meta keys
            global $wpdb;
            $lesson_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_course_ids' AND meta_value LIKE %s)
            ", $course->ID, '%' . $wpdb->esc_like(serialize(strval($course->ID))) . '%'));
            
            $lessons = array();
            if (!empty($lesson_ids)) {
                $lessons = get_posts(array(
                    'post_type' => 'ielts_lesson',
                    'posts_per_page' => -1,
                    'post__in' => $lesson_ids,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'post_status' => 'publish'
                ));
            }
            
            // Include the single course template
            include IELTS_CM_PLUGIN_DIR . 'templates/single-course.php';
            
        endwhile;
        ?>
    </main>
</div>

<?php
get_footer();
