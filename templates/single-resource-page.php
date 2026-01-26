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
            
            // Check if user has access to this resource
            $has_access = false;
            $is_completed = false;
            
            if ($user_id && $course_id) {
                $enrollment = new IELTS_CM_Enrollment();
                $has_access = $enrollment->is_enrolled($user_id, $course_id);
                
                if ($has_access && $lesson_id) {
                    // Check if already completed
                    $is_completed = $progress_tracker->is_resource_completed($user_id, $lesson_id, $resource_id);
                    
                    // If not already completed, mark as complete automatically
                    if (!$is_completed) {
                        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, true);
                        $is_completed = true;
                    }
                }
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
                        <p><?php _e('You have an active trial membership, but you need to enroll in this course to access its resources.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="button button-primary">
                                <?php _e('Enroll in Course', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php elseif (!is_user_logged_in()): ?>
                        <p><?php _e('You need to be enrolled in this course to access this resource.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                                <?php _e('Login', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php else: ?>
                        <p><?php _e('You need to be enrolled in this course to access this resource.', 'ielts-course-manager'); ?></p>
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
                // User has access, show the resource content
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
                    
                    <?php if ($user_id && $lesson_id && $is_completed): ?>
                        <div class="resource-completed-badge">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Completed', 'ielts-course-manager'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Get video URL for two-column layout
                $video_url = get_post_meta($resource_id, '_ielts_cm_video_url', true);
                $has_video = !empty($video_url);
                
                // Check if this is a vocabulary page
                $is_vocabulary = get_post_meta($resource_id, '_ielts_cm_is_vocabulary', true);
                $vocabulary_items = get_post_meta($resource_id, '_ielts_cm_vocabulary_items', true);
                if (!is_array($vocabulary_items)) {
                    $vocabulary_items = array();
                }
                ?>
                
                <?php if ($is_vocabulary && !empty($vocabulary_items)): ?>
                    <!-- Vocabulary page layout -->
                    <div class="resource-vocabulary-content">
                        <p><?php _e('To complete this lesson, you will need to know the following vocabulary. When you are sure you know all the words, continue to the next page.', 'ielts-course-manager'); ?></p>
                        
                        <table class="vocabulary-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Vocabulary', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Description', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Example Sentence', 'ielts-course-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vocabulary_items as $item): ?>
                                    <?php if (is_array($item) && !empty($item['word'])): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($item['word']); ?></strong></td>
                                            <td><?php echo isset($item['definition']) ? esc_html($item['definition']) : ''; ?></td>
                                            <td><?php echo isset($item['example']) ? esc_html($item['example']) : ''; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($has_video): ?>
                    <!-- Two-column layout when video is present -->
                    <div class="resource-two-column-layout">
                        <div class="resource-video-column">
                            <div class="resource-video-wrapper">
                                <?php
                                // Use WordPress auto-embed functionality
                                global $wp_embed;
                                // Process shortcodes that autoembed might generate (e.g., [video] for direct MP4 files)
                                echo do_shortcode($wp_embed->autoembed($video_url));
                                ?>
                            </div>
                        </div>
                        <div class="resource-content-column">
                            <?php 
                            // Apply WordPress content filters to process embeds and shortcodes
                            $content = apply_filters('the_content', $resource->post_content);
                            echo $content;
                            ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Standard full-width layout when no video -->
                    <div class="resource-content">
                        <?php 
                        // Apply WordPress content filters to process embeds and shortcodes
                        $content = apply_filters('the_content', $resource->post_content);
                        echo $content;
                        ?>
                    </div>
                <?php endif; ?>
                
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
                
                <?php // Auto-completion implemented: resources are automatically marked complete when viewed by enrolled users ?>
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
            
            /* Two-column layout for desktop when video is present */
            .resource-two-column-layout {
                display: flex;
                gap: 30px;
                margin-bottom: 30px;
            }
            .resource-video-column {
                flex: 0 0 45%;
                min-width: 0;
            }
            .resource-video-wrapper {
                position: sticky;
                top: 20px;
            }
            .resource-video-wrapper iframe,
            .resource-video-wrapper video {
                width: 100%;
                max-width: 100%;
                height: auto;
            }
            /* Modern browsers with aspect-ratio support */
            @supports (aspect-ratio: 16/9) {
                .resource-video-wrapper iframe,
                .resource-video-wrapper video {
                    aspect-ratio: 16/9;
                }
            }
            /* Fallback for older browsers using padding technique */
            @supports not (aspect-ratio: 16/9) {
                .resource-video-wrapper {
                    position: relative;
                    padding-bottom: 56.25%; /* 16:9 aspect ratio */
                    height: 0;
                    overflow: hidden;
                }
                .resource-video-wrapper iframe,
                .resource-video-wrapper video {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
            }
            .resource-content-column {
                flex: 1;
                min-width: 0;
                line-height: 1.8;
            }
            
            /* Mobile: Stack columns vertically */
            @media (max-width: 768px) {
                .resource-two-column-layout {
                    flex-direction: column;
                }
                .resource-video-column {
                    flex: 1 1 100%;
                }
                .resource-video-wrapper {
                    position: static;
                }
                .resource-content-column {
                    flex: 1 1 100%;
                }
            }
            
            /* Standard full-width layout when no video */
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
            
            /* Vocabulary table styling */
            .resource-vocabulary-content {
                margin-bottom: 30px;
                line-height: 1.8;
            }
            .resource-vocabulary-content p {
                margin-bottom: 20px;
                font-size: 16px;
                color: #333;
            }
            .vocabulary-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            .vocabulary-table th,
            .vocabulary-table td {
                padding: 12px 15px;
                text-align: left;
                border: 1px solid #ddd;
            }
            .vocabulary-table th {
                background-color: #f8f9fa;
                font-weight: bold;
                color: #333;
            }
            .vocabulary-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .vocabulary-table tr:hover {
                background-color: #f0f7ff;
            }
            .vocabulary-table td:first-child {
                font-weight: 600;
                color: #0073aa;
                width: 20%;
            }
            .vocabulary-table td:nth-child(2) {
                width: 40%;
            }
            .vocabulary-table td:nth-child(3) {
                width: 40%;
                font-style: italic;
            }
            @media (max-width: 768px) {
                .vocabulary-table {
                    font-size: 14px;
                }
                .vocabulary-table th,
                .vocabulary-table td {
                    padding: 8px 10px;
                }
            }
            </style>
            
            <?php
            // Previous/Next navigation within the lesson (includes both resources and exercises)
            if ($lesson_id) {
                global $wpdb;
                
                // Check for both integer and string serialization in lesson_ids array
                // Integer: i:123; String: s:3:"123";
                $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
                $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
                
                // Get all resources for this lesson
                $resource_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT post_id 
                    FROM {$wpdb->postmeta} 
                    WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                       OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
                ", $lesson_id, $int_pattern, $str_pattern));
                
                // Get all quizzes for this lesson
                $quiz_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT post_id 
                    FROM {$wpdb->postmeta} 
                    WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                       OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
                ", $lesson_id, $int_pattern, $str_pattern));
                
                // Combine all content items
                $all_items = array();
                
                if (!empty($resource_ids)) {
                    $resources = get_posts(array(
                        'post_type' => 'ielts_resource',
                        'posts_per_page' => -1,
                        'post__in' => $resource_ids,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'post_status' => 'publish'
                    ));
                    foreach ($resources as $resource_item) {
                        $all_items[] = array(
                            'post' => $resource_item,
                            'type' => 'resource',
                            'order' => $resource_item->menu_order
                        );
                    }
                }
                
                if (!empty($quiz_ids)) {
                    $quizzes = get_posts(array(
                        'post_type' => 'ielts_quiz',
                        'posts_per_page' => -1,
                        'post__in' => $quiz_ids,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'post_status' => 'publish'
                    ));
                    foreach ($quizzes as $quiz) {
                        $all_items[] = array(
                            'post' => $quiz,
                            'type' => 'quiz',
                            'order' => $quiz->menu_order
                        );
                    }
                }
                
                // Sort by menu order
                usort($all_items, function($a, $b) {
                    return $a['order'] - $b['order'];
                });
                
                // Find current resource and get previous/next items
                $current_index = -1;
                foreach ($all_items as $index => $item) {
                    if ($item['post']->ID == $resource_id) {
                        $current_index = $index;
                        break;
                    }
                }
                
                $prev_item = ($current_index > 0) ? $all_items[$current_index - 1] : null;
                $next_item = ($current_index >= 0 && $current_index < count($all_items) - 1) ? $all_items[$current_index + 1] : null;
                ?>
                
                <?php if ($prev_item || $next_item): ?>
                    <div class="ielts-navigation">
                        <div class="nav-prev">
                            <?php if ($prev_item): ?>
                                <?php
                                // Get appropriate URL for the previous item
                                if ($prev_item['type'] === 'quiz') {
                                    // Check if quiz should open in fullscreen
                                    $layout_type = get_post_meta($prev_item['post']->ID, '_ielts_cm_layout_type', true);
                                    $open_as_popup = get_post_meta($prev_item['post']->ID, '_ielts_cm_open_as_popup', true);
                                    $is_two_column = in_array($layout_type, array('two_column_reading', 'two_column_listening', 'two_column_exercise'));
                                    $use_fullscreen = $is_two_column && $open_as_popup;
                                    
                                    if ($use_fullscreen) {
                                        $prev_url = add_query_arg('fullscreen', '1', get_permalink($prev_item['post']->ID));
                                    } else {
                                        $prev_url = get_permalink($prev_item['post']->ID);
                                    }
                                    $prev_label = __('Previous Exercise', 'ielts-course-manager');
                                } else {
                                    $prev_url = get_permalink($prev_item['post']->ID);
                                    $prev_label = __('Previous Sub Lesson', 'ielts-course-manager');
                                }
                                ?>
                                <a href="<?php echo esc_url($prev_url); ?>" class="nav-link">
                                    <span class="nav-arrow">&laquo;</span>
                                    <span class="nav-label">
                                        <small><?php echo esc_html($prev_label); ?></small>
                                        <strong><?php echo esc_html($prev_item['post']->post_title); ?></strong>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="nav-center">
                            <?php if ($course_id): ?>
                                <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-link nav-back-to-course">
                                    <span class="nav-label">
                                        <small><?php _e('Back to', 'ielts-course-manager'); ?></small>
                                        <strong><?php _e('Course', 'ielts-course-manager'); ?></strong>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="nav-next">
                            <?php if ($next_item): ?>
                                <?php
                                // Get appropriate URL for the next item
                                if ($next_item['type'] === 'quiz') {
                                    // Check if quiz should open in fullscreen
                                    $layout_type = get_post_meta($next_item['post']->ID, '_ielts_cm_layout_type', true);
                                    $open_as_popup = get_post_meta($next_item['post']->ID, '_ielts_cm_open_as_popup', true);
                                    $is_two_column = in_array($layout_type, array('two_column_reading', 'two_column_listening', 'two_column_exercise'));
                                    $use_fullscreen = $is_two_column && $open_as_popup;
                                    
                                    if ($use_fullscreen) {
                                        $next_url = add_query_arg('fullscreen', '1', get_permalink($next_item['post']->ID));
                                    } else {
                                        $next_url = get_permalink($next_item['post']->ID);
                                    }
                                    $next_label = __('Next Exercise', 'ielts-course-manager');
                                } else {
                                    $next_url = get_permalink($next_item['post']->ID);
                                    $next_label = __('Next Sub Lesson', 'ielts-course-manager');
                                }
                                ?>
                                <a href="<?php echo esc_url($next_url); ?>" class="nav-link">
                                    <span class="nav-label">
                                        <small><?php echo esc_html($next_label); ?></small>
                                        <strong><?php echo esc_html($next_item['post']->post_title); ?></strong>
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
                        align-items: center;
                        margin-top: 40px;
                        padding-top: 30px;
                        border-top: 2px solid #e0e0e0;
                        gap: 15px;
                    }
                    .ielts-navigation .nav-prev {
                        flex: 1;
                    }
                    .ielts-navigation .nav-center {
                        flex: 0 0 auto;
                        text-align: center;
                    }
                    .ielts-navigation .nav-next {
                        flex: 1;
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
                    .ielts-navigation .nav-back-to-course {
                        background: #0073aa;
                        color: white;
                    }
                    .ielts-navigation .nav-back-to-course:hover {
                        background: #005a87;
                    }
                    .ielts-navigation .nav-back-to-course .nav-label small,
                    .ielts-navigation .nav-back-to-course .nav-label strong {
                        color: white;
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
                    .ielts-navigation .nav-center .nav-label {
                        align-items: center;
                    }
                    @media (max-width: 768px) {
                        .ielts-navigation {
                            flex-direction: column;
                            gap: 10px;
                        }
                        .ielts-navigation .nav-prev,
                        .ielts-navigation .nav-center,
                        .ielts-navigation .nav-next {
                            width: 100%;
                            text-align: center;
                        }
                        .ielts-navigation .nav-label {
                            align-items: center !important;
                        }
                    }
                    </style>
                <?php endif; ?>
            <?php } ?>
            
            <?php endif; // end has_access check ?>
            
        <?php endwhile; ?>
    </main>
</div>

<?php
get_footer();
