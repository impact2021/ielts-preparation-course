<?php
/**
 * Template for displaying single resource (sub lesson) page
 * This template is loaded when viewing a resource post directly
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
body.ielts-resource-single #main.site-main {
    padding: 30px 40px !important;
}
/* Fallback for themes with different structure */
body.ielts-resource-single .site-main,
body.ielts-resource-single #primary,
body.ielts-resource-single .content-area {
    padding-top: 30px !important;
    padding-bottom: 30px !important;
}
</style>

<div id="primary" class="content-area ielts-full-width">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();
            
            // Get the resource
            $resource = get_post();
            $resource_id = $resource->ID;
            
            // Get lesson ID
            $lesson_id = get_post_meta($resource_id, '_ielts_cm_lesson_id', true);
            
            // Get course ID from lesson
            $course_id = null;
            if ($lesson_id) {
                $course_id = get_post_meta($lesson_id, '_ielts_cm_course_id', true);
            }
            
            $user_id = get_current_user_id();
            $progress_tracker = new IELTS_CM_Progress_Tracker();
            
            // Automatically mark resource as accessed when user views it
            if ($user_id && $lesson_id && $course_id) {
                $enrollment = new IELTS_CM_Enrollment();
                $is_enrolled = $enrollment->is_enrolled($user_id, $course_id);
                
                if ($is_enrolled) {
                    // Check if already completed
                    $is_already_completed = $progress_tracker->is_resource_completed($user_id, $lesson_id, $resource_id);
                    
                    // If not already completed, mark as complete automatically
                    if (!$is_already_completed) {
                        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, true);
                    }
                }
            }
            ?>
            
            <div class="ielts-single-resource">
                <?php if ($course_id && $lesson_id): ?>
                    <div class="resource-breadcrumb">
                        <?php
                        $course = get_post($course_id);
                        $lesson = get_post($lesson_id);
                        if ($course && $lesson):
                        ?>
                            <a href="<?php echo get_permalink($course->ID); ?>">
                                <?php echo esc_html($course->post_title); ?>
                            </a>
                            <span class="separator">&raquo;</span>
                            <a href="<?php echo get_permalink($lesson->ID); ?>">
                                <?php echo esc_html($lesson->post_title); ?>
                            </a>
                            <span class="separator">&raquo;</span>
                            <span><?php echo esc_html($resource->post_title); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="resource-header">
                    <h1><?php echo esc_html($resource->post_title); ?></h1>
                    
                    <?php if ($user_id && $lesson_id): ?>
                        <?php
                        $is_completed = $progress_tracker->is_resource_completed($user_id, $lesson_id, $resource_id);
                        ?>
                        <?php if ($is_completed): ?>
                            <div class="resource-completed-badge">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Completed', 'ielts-course-manager'); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="resource-content">
                    <?php echo wpautop($resource->post_content); ?>
                </div>
                
                <?php
                $resource_url = get_post_meta($resource_id, '_ielts_cm_resource_url', true);
                if (!empty($resource_url)):
                ?>
                    <div class="resource-external-link">
                        <a href="<?php echo esc_url($resource_url); ?>" target="_blank" class="button button-primary">
                            <?php _e('View External Resource', 'ielts-course-manager'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php // Removed manual "Mark as Complete" button - now auto-marks on page view ?>
            </div>
            
            <style>
            .ielts-single-resource {
                max-width: 100%;
                margin: 0 auto;
            }
            .resource-breadcrumb {
                margin-bottom: 20px;
                font-size: 14px;
                color: #666;
            }
            .resource-breadcrumb a {
                color: #0073aa;
                text-decoration: none;
            }
            .resource-breadcrumb a:hover {
                text-decoration: underline;
            }
            .resource-breadcrumb .separator {
                margin: 0 8px;
            }
            .resource-header {
                margin-bottom: 30px;
            }
            .resource-header h1 {
                margin-bottom: 10px;
            }
            .resource-completed-badge {
                display: inline-block;
                padding: 8px 12px;
                background: #46b450;
                color: white;
                border-radius: 4px;
                font-size: 14px;
            }
            .resource-completed-badge .dashicons {
                vertical-align: middle;
                margin-right: 4px;
            }
            .resource-content {
                margin-bottom: 30px;
                line-height: 1.8;
            }
            .resource-external-link {
                margin-bottom: 30px;
            }
            .resource-actions {
                margin-top: 30px;
            }
            </style>
            
            <?php
            // Previous/Next resource navigation within the lesson
            if ($lesson_id) {
                global $wpdb;
                $resource_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT post_id 
                    FROM {$wpdb->postmeta} 
                    WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                       OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
                ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
                
                $all_resources = array();
                if (!empty($resource_ids)) {
                    $all_resources = get_posts(array(
                        'post_type' => 'ielts_resource',
                        'posts_per_page' => -1,
                        'post__in' => $resource_ids,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'post_status' => 'publish'
                    ));
                }
                
                $current_index = -1;
                foreach ($all_resources as $index => $r) {
                    if ($r->ID == $resource_id) {
                        $current_index = $index;
                        break;
                    }
                }
                
                $prev_resource = ($current_index > 0) ? $all_resources[$current_index - 1] : null;
                $next_resource = ($current_index >= 0 && $current_index < count($all_resources) - 1) ? $all_resources[$current_index + 1] : null;
                ?>
                
                <?php if ($prev_resource || $next_resource): ?>
                    <div class="ielts-navigation">
                        <div class="nav-prev">
                            <?php if ($prev_resource): ?>
                                <a href="<?php echo get_permalink($prev_resource->ID); ?>" class="nav-link">
                                    <span class="nav-arrow">&laquo;</span>
                                    <span class="nav-label">
                                        <small><?php _e('Previous Sub Lesson', 'ielts-course-manager'); ?></small>
                                        <strong><?php echo esc_html($prev_resource->post_title); ?></strong>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="nav-next">
                            <?php if ($next_resource): ?>
                                <a href="<?php echo get_permalink($next_resource->ID); ?>" class="nav-link">
                                    <span class="nav-label">
                                        <small><?php _e('Next Sub Lesson', 'ielts-course-manager'); ?></small>
                                        <strong><?php echo esc_html($next_resource->post_title); ?></strong>
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
            
        <?php endwhile; ?>
    </main>
</div>

<?php
get_footer();
