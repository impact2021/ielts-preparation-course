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

<div id="primary" class="content-area">
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
            $resource_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
            ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
            
            $resources = array();
            if (!empty($resource_ids)) {
                $resources = get_posts(array(
                    'post_type' => 'ielts_resource',
                    'posts_per_page' => -1,
                    'post__in' => $resource_ids,
                    'post_status' => 'publish'
                ));
            }
            
            // Get quizzes for this lesson - check both old and new meta keys
            $quiz_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
            ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
            
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
get_sidebar();
get_footer();
