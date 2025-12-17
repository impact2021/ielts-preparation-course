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
                
                <?php if ($user_id && $lesson_id && $course_id): ?>
                    <div class="resource-actions">
                        <?php
                        $enrollment = new IELTS_CM_Enrollment();
                        $is_enrolled = $enrollment->is_enrolled($user_id, $course_id);
                        
                        if ($is_enrolled && !$is_completed):
                        ?>
                            <button id="mark-complete-btn" class="button button-primary">
                                <?php _e('Mark as Complete', 'ielts-course-manager'); ?>
                            </button>
                            
                            <script>
                            jQuery(document).ready(function($) {
                                $('#mark-complete-btn').on('click', function() {
                                    var $btn = $(this);
                                    $btn.prop('disabled', true).text('<?php _e('Saving...', 'ielts-course-manager'); ?>');
                                    
                                    $.ajax({
                                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                                        type: 'POST',
                                        data: {
                                            action: 'ielts_cm_mark_resource_complete',
                                            nonce: '<?php echo esc_js(wp_create_nonce('ielts_cm_nonce')); ?>',
                                            lesson_id: <?php echo intval($lesson_id); ?>,
                                            resource_id: <?php echo intval($resource_id); ?>,
                                            course_id: <?php echo intval($course_id); ?>
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                location.reload();
                                            } else {
                                                alert('<?php _e('Error marking as complete', 'ielts-course-manager'); ?>');
                                                $btn.prop('disabled', false).text('<?php _e('Mark as Complete', 'ielts-course-manager'); ?>');
                                            }
                                        },
                                        error: function() {
                                            alert('<?php _e('Error marking as complete', 'ielts-course-manager'); ?>');
                                            $btn.prop('disabled', false).text('<?php _e('Mark as Complete', 'ielts-course-manager'); ?>');
                                        }
                                    });
                                });
                            });
                            </script>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
            
        <?php endwhile; ?>
    </main>
</div>

<?php
get_footer();
