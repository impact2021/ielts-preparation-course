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
            
            // Check if user has access to this lesson
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
                        <p><?php _e('This course is not included in your trial membership.', 'ielts-course-manager'); ?></p>
                        <p>
                            <?php 
                            $upgrade_url = get_option('ielts_cm_full_member_page_url', home_url());
                            ?>
                            <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-primary">
                                <?php _e('Upgrade to Full Membership', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php elseif (!is_user_logged_in()): ?>
                        <p><?php _e('You need to be enrolled in this course to access this lesson.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                                <?php _e('Login', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php else: ?>
                        <p><?php _e('You need to be enrolled in this course to access this lesson.', 'ielts-course-manager'); ?></p>
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
                // User has access, show the lesson content
            
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
            
            endif; // end has_access check
            
        endwhile;
        ?>
    </main>
</div>

<?php
get_footer();
