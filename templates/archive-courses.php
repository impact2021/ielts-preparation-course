<?php
/**
 * Template for displaying course archives
 * This template is loaded when viewing the course post type archive
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<style>
/* Ensure proper padding for course archive page */
body.ielts-course-archive .site-main,
body.ielts-course-archive #primary,
body.ielts-course-archive .content-area {
    padding-top: 60px !important;
    padding-bottom: 60px !important;
}
</style>

<div id="primary" class="content-area ielts-full-width">
    <main id="main" class="site-main" style="padding: 60px 40px;">
        
        <header class="page-header">
            <h1 class="page-title"><?php _e('IELTS Courses', 'ielts-course-manager'); ?></h1>
        </header>
        
        <?php
        if (have_posts()) :
            
            // Get all courses
            $courses = array();
            while (have_posts()) :
                the_post();
                $courses[] = get_post();
            endwhile;
            
            // Include the courses list template
            include IELTS_CM_PLUGIN_DIR . 'templates/courses-list.php';
            
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('Previous', 'ielts-course-manager'),
                'next_text' => __('Next', 'ielts-course-manager'),
            ));
            
        else :
            ?>
            <p><?php _e('No courses found.', 'ielts-course-manager'); ?></p>
            <?php
        endif;
        ?>
        
    </main>
</div>

<?php
get_footer();
