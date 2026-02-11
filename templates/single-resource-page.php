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
            $has_visited_before = false;  // Track if user has visited this resource before
            
            if ($user_id && $course_id) {
                $enrollment = new IELTS_CM_Enrollment();
                $has_access = $enrollment->is_enrolled($user_id, $course_id);
                
                if ($has_access && $lesson_id) {
                    // Check if this resource has been accessed before
                    global $wpdb;
                    $table = $progress_tracker->get_progress_table();
                    $existing = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, completed FROM {$table} WHERE user_id = %d AND lesson_id = %d AND resource_id = %d",
                        $user_id, $lesson_id, $resource_id
                    ));
                    
                    if ($existing) {
                        // Resource has been accessed before
                        $has_visited_before = true;
                        $is_completed = (bool) $existing->completed;
                        
                        // Update last_accessed but keep completed status as-is
                        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, $existing->completed);
                    } else {
                        // First time viewing this resource
                        $has_visited_before = false;
                        $is_completed = false;
                        
                        // Track access without marking as completed
                        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, false);
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
                        <p><?php _e('You need to be enrolled in this unit to access this resource.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo esc_url(IELTS_CM_Frontend::get_custom_login_url(get_permalink())); ?>" class="button button-primary">
                                <?php _e('Login', 'ielts-course-manager'); ?>
                            </a>
                        </p>
                    <?php else: ?>
                        <p><?php _e('You need to be enrolled in this unit to access this resource.', 'ielts-course-manager'); ?></p>
                        <p>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="button button-primary">
                                <?php _e('View Unit', 'ielts-course-manager'); ?>
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
                    
                    <?php 
                    // Only show completed badge if:
                    // 1. User is logged in
                    // 2. Resource is part of a lesson
                    // 3. User has visited this resource before (not first visit)
                    // 4. Resource is marked as completed
                    if ($user_id && $lesson_id && $has_visited_before && $is_completed): 
                    ?>
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
                        
                        <?php 
                        // Get vocabulary header color
                        $vocab_header_color = get_option('ielts_cm_vocab_header_color', '#E56C0A');
                        ?>
                        
                        <table class="vocabulary-table">
                            <thead>
                                <tr style="background-color: <?php echo esc_attr($vocab_header_color); ?>;">
                                    <th><?php _e('Vocabulary', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Description', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Example Sentence', 'ielts-course-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vocabulary_items as $item): ?>
                                    <?php if (is_array($item) && !empty($item['word'])): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($item['word']); ?></strong>
                                                <?php 
                                                // Build metadata string for display
                                                $metadata_parts = array();
                                                if (!empty($item['part_of_speech'])) {
                                                    $metadata_parts[] = mb_strtoupper($item['part_of_speech']);
                                                }
                                                if (!empty($item['cefr_level'])) {
                                                    $metadata_parts[] = 'CEFR Level ' . $item['cefr_level'];
                                                }
                                                
                                                if (!empty($metadata_parts)) {
                                                    echo '<div class="vocab-meta">' . esc_html(implode(' • ', $metadata_parts)) . '</div>';
                                                }
                                                ?>
                                            </td>
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
                font-size: 14px; /* Slightly smaller font */
            }
            .vocabulary-table th,
            .vocabulary-table td {
                padding: 8px 12px; /* Reduced padding */
                text-align: left;
                border: 1px solid #ddd;
            }
            .vocabulary-table th {
                font-weight: bold;
                color: #fff; /* White text for header */
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
            .vocab-meta {
                font-size: 11px; /* Smaller font for meta info */
                color: #666;
                font-weight: normal;
                margin-top: 4px;
            }
            @media (max-width: 768px) {
                .vocabulary-table {
                    font-size: 13px;
                }
                .vocabulary-table th,
                .vocabulary-table td {
                    padding: 6px 8px;
                }
                .vocab-meta {
                    font-size: 10px;
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
                
                // Check if this is the last lesson in the course (for completion message)
                $is_last_lesson = false;
                if (!$next_item && $course_id && $lesson_id) {
                    // Get all lessons in the course
                    $int_pattern_course = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
                    $str_pattern_course = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
                    
                    $all_lesson_ids = $wpdb->get_col($wpdb->prepare("
                        SELECT DISTINCT post_id 
                        FROM {$wpdb->postmeta} 
                        WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                           OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
                    ", $course_id, $int_pattern_course, $str_pattern_course));
                    
                    if (!empty($all_lesson_ids)) {
                        $all_lessons = get_posts(array(
                            'post_type' => 'ielts_lesson',
                            'posts_per_page' => -1,
                            'post__in' => $all_lesson_ids,
                            'orderby' => 'menu_order',
                            'order' => 'ASC',
                            'post_status' => 'publish'
                        ));
                        
                        // Check if current lesson is the last one
                        if (!empty($all_lessons) && end($all_lessons)->ID == $lesson_id) {
                            $is_last_lesson = true;
                        }
                    }
                }
                
                // Find next unit if this is the last lesson
                $next_unit = null;
                $next_unit_label = __('Move on to next unit', 'ielts-course-manager');
                if ($is_last_lesson && $course_id) {
                    // Get all units (including drafts) ordered by menu_order to find position
                    $all_units = get_posts(array(
                        'post_type' => 'ielts_course',
                        'posts_per_page' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'post_status' => 'any'
                    ));
                    
                    // Find the current unit and get the next published one
                    $total_units = count($all_units);
                    foreach ($all_units as $index => $unit) {
                        if ($unit->ID === $course_id) {
                            // Look for the next published unit
                            for ($i = $index + 1; $i < $total_units; $i++) {
                                if (get_post_status($all_units[$i]->ID) === 'publish') {
                                    $next_unit = $all_units[$i];
                                    // Extract unit number from title (e.g., "Academic Unit 2" -> "Unit 2")
                                    $sanitized_title = sanitize_text_field($next_unit->post_title);
                                    if (preg_match('/Unit\s+(\d+)/i', $sanitized_title, $matches)) {
                                        $next_unit_label = sprintf(__('Move to Unit %s', 'ielts-course-manager'), $matches[1]);
                                    }
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
                ?>
                
                <?php if ($prev_item || $next_item || $lesson_id || $course_id): ?>
                    <div class="ielts-sticky-bottom-nav">
                        <div class="nav-item nav-prev">
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
                                    $prev_label = __('Previous', 'ielts-course-manager');
                                }
                                ?>
                                <a href="<?php echo esc_url($prev_url); ?>" class="nav-link resource-nav-link" data-course-id="<?php echo esc_attr($course_id); ?>" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-resource-id="<?php echo esc_attr($resource_id); ?>">
                                    <span class="nav-arrow">&laquo;</span>
                                    <span class="nav-label">
                                        <small><?php echo esc_html($prev_label); ?></small>
                                        <strong><?php echo esc_html($prev_item['post']->post_title); ?></strong>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="nav-item nav-center-left">
                            <?php if ($lesson_id): ?>
                                <a href="<?php echo esc_url(get_permalink($lesson_id)); ?>" class="nav-link nav-back-to-lesson resource-nav-link" data-course-id="<?php echo esc_attr($course_id); ?>" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-resource-id="<?php echo esc_attr($resource_id); ?>">
                                    <span class="nav-label">
                                        <small><?php _e('Back to the Lesson', 'ielts-course-manager'); ?></small>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="nav-item nav-center-right">
                            <?php if ($course_id): ?>
                                <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-link nav-back-to-course resource-nav-link" data-course-id="<?php echo esc_attr($course_id); ?>" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-resource-id="<?php echo esc_attr($resource_id); ?>">
                                    <span class="nav-label">
                                        <small><?php _e('Back to the Unit', 'ielts-course-manager'); ?></small>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="nav-item nav-next">
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
                                    $next_label = __('Next', 'ielts-course-manager');
                                }
                                ?>
                                <a href="<?php echo esc_url($next_url); ?>" class="nav-link resource-nav-link" data-course-id="<?php echo esc_attr($course_id); ?>" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-resource-id="<?php echo esc_attr($resource_id); ?>">
                                    <span class="nav-label">
                                        <small><?php echo esc_html($next_label); ?></small>
                                        <strong><?php echo esc_html($next_item['post']->post_title); ?></strong>
                                    </span>
                                    <span class="nav-arrow">&raquo;</span>
                                </a>
                            <?php else: ?>
                                <div class="nav-completion-message">
                                    <?php if ($is_last_lesson): ?>
                                        <?php if (isset($next_unit) && $next_unit): ?>
                                            <?php
                                            // Extract unit number from title
                                            $sanitized_title = sanitize_text_field($next_unit->post_title);
                                            $unit_number = '';
                                            if (preg_match('/Unit\s+(\d+)/i', $sanitized_title, $matches)) {
                                                $unit_number = $matches[1];
                                            }
                                            ?>
                                            <span><?php _e('That is the end of this unit', 'ielts-course-manager'); ?></span>
                                            <a href="<?php echo esc_url(get_permalink($next_unit->ID)); ?>" class="button button-primary">
                                                <?php 
                                                if ($unit_number) {
                                                    printf(__('Move to Unit %s', 'ielts-course-manager'), esc_html($unit_number));
                                                } else {
                                                    _e('Move to next unit', 'ielts-course-manager');
                                                }
                                                ?>
                                            </a>
                                        <?php else: ?>
                                            <span><?php _e('That is the end of this unit', 'ielts-course-manager'); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span><?php _e('You have finished this lesson', 'ielts-course-manager'); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php
                                // Visual debugger for "Next Unit" button visibility
                                // Shows when ?debug_nav=1 is in URL or IELTS_CM_DEBUG_NAV constant is true
                                $show_debug = (isset($_GET['debug_nav']) && sanitize_text_field($_GET['debug_nav']) === '1') || 
                                             (defined('IELTS_CM_DEBUG_NAV') && IELTS_CM_DEBUG_NAV);
                                
                                if ($show_debug):
                                    // Gather all debug information
                                    global $wpdb;
                                    
                                    // Get all lessons for this course (limited to 100 for performance)
                                    $debug_all_lessons = array();
                                    if ($course_id) {
                                        $int_pattern_course = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
                                        $str_pattern_course = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
                                        
                                        $all_lesson_ids = $wpdb->get_col($wpdb->prepare("
                                            SELECT DISTINCT post_id 
                                            FROM {$wpdb->postmeta} 
                                            WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                                               OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
                                            LIMIT 100
                                        ", $course_id, $int_pattern_course, $str_pattern_course));
                                        
                                        if (!empty($all_lesson_ids)) {
                                            $debug_all_lessons = get_posts(array(
                                                'post_type' => 'ielts_lesson',
                                                'posts_per_page' => 100,
                                                'post__in' => $all_lesson_ids,
                                                'orderby' => 'menu_order',
                                                'order' => 'ASC',
                                                'post_status' => 'publish'
                                            ));
                                        }
                                    }
                                    
                                    // Get all units (limited to 100 for performance, including drafts for debugging)
                                    $debug_all_units = array();
                                    if ($course_id) {
                                        $debug_all_units = get_posts(array(
                                            'post_type' => 'ielts_course',
                                            'posts_per_page' => 100,
                                            'orderby' => 'menu_order',
                                            'order' => 'ASC',
                                            'post_status' => 'any'
                                        ));
                                    }
                                ?>
                                <div class="ielts-nav-debugger">
                                    <div class="debugger-header">
                                        <h3>🔍 Next Unit Button Debugger (Resource Page)</h3>
                                        <p class="debugger-subtitle">This panel explains why the "Move to next unit" button is or isn't showing</p>
                                    </div>
                                    
                                    <div class="debugger-section">
                                        <h4>Current State</h4>
                                        <table class="debugger-table">
                                            <tr>
                                                <td class="label">Resource ID:</td>
                                                <td class="value"><?php echo esc_html($resource->ID); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="label">Course ID:</td>
                                                <td class="value"><?php echo esc_html($course_id ? $course_id : 'NOT SET'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="label">Lesson ID:</td>
                                                <td class="value"><?php echo esc_html($lesson_id ? $lesson_id : 'NOT SET'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="label">Has Next Item:</td>
                                                <td class="value <?php echo $next_item ? 'success' : 'error'; ?>">
                                                    <?php echo $next_item ? '✓ YES (ID: ' . esc_html($next_item->ID) . ' - ' . esc_html($next_item->post_title) . ')' : '✗ NO'; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="debugger-section">
                                        <h4>Button Logic Check</h4>
                                        <table class="debugger-table">
                                            <tr>
                                                <td class="label">Is Last Lesson:</td>
                                                <td class="value <?php echo $is_last_lesson ? 'success' : 'error'; ?>">
                                                    <?php echo $is_last_lesson ? '✓ TRUE' : '✗ FALSE'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label">Course ID Type:</td>
                                                <td class="value">
                                                    <?php 
                                                    if ($course_id) {
                                                        echo esc_html(gettype($course_id)) . ' (' . esc_html($course_id) . ')';
                                                    } else {
                                                        echo 'NO COURSE ID';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label">Course Status:</td>
                                                <td class="value">
                                                    <?php 
                                                    if ($course_id) {
                                                        $course_status = get_post_status($course_id);
                                                        echo esc_html($course_status);
                                                        if ($course_status !== 'publish') {
                                                            echo ' <span style="color: #d63638; font-weight: bold;">← THIS IS WHY! Course must be "publish" status</span>';
                                                        }
                                                    } else {
                                                        echo 'NO COURSE ID';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label">Units Query Result:</td>
                                                <td class="value">
                                                    <?php 
                                                    // Re-run the same query to debug
                                                    if ($is_last_lesson && $course_id) {
                                                        $debug_units = get_posts(array(
                                                            'post_type' => 'ielts_course',
                                                            'posts_per_page' => -1,
                                                            'orderby' => 'menu_order',
                                                            'order' => 'ASC',
                                                            'post_status' => 'any'
                                                        ));
                                                        echo 'Found ' . count($debug_units) . ' units. ';
                                                        $found_current = false;
                                                        $current_index = -1;
                                                        foreach ($debug_units as $idx => $u) {
                                                            if ($u->ID == $course_id) {
                                                                $found_current = true;
                                                                $current_index = $idx;
                                                                break;
                                                            }
                                                        }
                                                        if ($found_current) {
                                                            echo 'Current unit found at index ' . $current_index . '. ';
                                                            if (isset($debug_units[$current_index + 1])) {
                                                                $next_u = $debug_units[$current_index + 1];
                                                                echo 'Next unit: ' . esc_html($next_u->post_title) . ' (status: ' . get_post_status($next_u->ID) . ')';
                                                            } else {
                                                                echo 'No unit at index ' . ($current_index + 1);
                                                            }
                                                        } else {
                                                            echo '<span style="color: #d63638; font-weight: bold;">← PROBLEM: Current unit ID ' . $course_id . ' NOT FOUND in units array!</span>';
                                                        }
                                                    } else {
                                                        echo 'Query not run';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label">Has Next Unit:</td>
                                                <td class="value <?php echo (isset($next_unit) && $next_unit) ? 'success' : 'error'; ?>">
                                                    <?php 
                                                    if (isset($next_unit) && $next_unit) {
                                                        echo '✓ YES (ID: ' . esc_html($next_unit->ID) . ' - ' . esc_html($next_unit->post_title) . ')';
                                                    } else if (isset($next_unit)) {
                                                        echo '✗ NO (variable is set but empty/false)';
                                                    } else {
                                                        echo '✗ NOT SET';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="debugger-section debugger-decision">
                                        <h4>Decision Tree</h4>
                                        <div class="decision-flow">
                                            <?php if (!$next_item): ?>
                                                <div class="decision-step success">✓ No next item in lesson (last resource/quiz in lesson)</div>
                                            <?php else: ?>
                                                <div class="decision-step error">✗ Has next item in lesson → Regular "Next" button should show</div>
                                            <?php endif; ?>
                                            
                                            <?php if (!$next_item): ?>
                                                <?php if ($is_last_lesson): ?>
                                                    <div class="decision-step success">✓ This is the last lesson in the unit</div>
                                                <?php else: ?>
                                                    <div class="decision-step error">✗ NOT the last lesson in unit → Shows "You have finished this lesson"</div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if (!$next_item && $is_last_lesson): ?>
                                                <?php if (isset($next_unit) && $next_unit): ?>
                                                    <div class="decision-step success">✓ Next unit found → BUTTON SHOULD BE VISIBLE</div>
                                                <?php else: ?>
                                                    <div class="decision-step error">✗ No next unit found → Only shows completion message</div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="debugger-section">
                                        <h4>Expected Result</h4>
                                        <div class="expected-result">
                                            <?php if (!$next_item && $is_last_lesson && isset($next_unit) && $next_unit): ?>
                                                <div class="result-box success">
                                                    <strong>✓ BUTTON SHOULD BE VISIBLE</strong><br>
                                                    The "Move to Unit X" button should appear because:
                                                    <ul>
                                                        <li>This is the last resource/quiz in the lesson</li>
                                                        <li>This is the last lesson in the unit</li>
                                                        <li>A next unit exists (<?php echo esc_html($next_unit->post_title); ?>)</li>
                                                    </ul>
                                                    <strong>If you don't see the button, check:</strong>
                                                    <ul>
                                                        <li>CSS is loaded (check browser dev tools)</li>
                                                        <li>No custom CSS hiding the button</li>
                                                        <li>The .button and .button-primary classes are styled</li>
                                                    </ul>
                                                </div>
                                            <?php elseif (!$next_item && $is_last_lesson): ?>
                                                <div class="result-box warning">
                                                    <strong>⚠ BUTTON NOT SHOWN (No Next Unit)</strong><br>
                                                    Only completion message shows because:
                                                    <ul>
                                                        <li>This is the last resource/quiz in the lesson</li>
                                                        <li>This is the last lesson in the unit</li>
                                                        <li>But there is no next unit (this is the last unit in the course)</li>
                                                    </ul>
                                                </div>
                                            <?php elseif (!$next_item): ?>
                                                <div class="result-box warning">
                                                    <strong>⚠ BUTTON NOT SHOWN (Not Last Lesson)</strong><br>
                                                    Shows "You have finished this lesson" because:
                                                    <ul>
                                                        <li>This is the last resource/quiz in the lesson</li>
                                                        <li>But this is NOT the last lesson in the unit</li>
                                                    </ul>
                                                </div>
                                            <?php else: ?>
                                                <div class="result-box info">
                                                    <strong>ℹ REGULAR NAVIGATION</strong><br>
                                                    Regular "Next" button shows because:
                                                    <ul>
                                                        <li>There is a next resource/quiz in this lesson</li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="debugger-section">
                                        <h4>All Lessons in Course (in order)</h4>
                                        <?php if (!empty($debug_all_lessons)): ?>
                                            <ol class="lessons-list">
                                                <?php foreach ($debug_all_lessons as $index => $lesson_item): ?>
                                                    <li class="<?php echo ($lesson_item->ID == $lesson_id) ? 'current-item' : ''; ?>">
                                                        <strong><?php echo esc_html($lesson_item->post_title); ?></strong>
                                                        (ID: <?php echo esc_html($lesson_item->ID); ?>)
                                                        <?php if ($lesson_item->ID == $lesson_id): ?>
                                                            <span class="badge">← YOU ARE HERE</span>
                                                        <?php endif; ?>
                                                        <?php if ($index === count($debug_all_lessons) - 1): ?>
                                                            <span class="badge last">LAST LESSON</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ol>
                                        <?php else: ?>
                                            <p class="no-data">No lessons found for this course</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="debugger-section">
                                        <h4>All Units (in order)</h4>
                                        <?php if (!empty($debug_all_units)): ?>
                                            <ol class="units-list">
                                                <?php foreach ($debug_all_units as $index => $unit_item): ?>
                                                    <li class="<?php echo ($unit_item->ID == $course_id) ? 'current-item' : ''; ?>">
                                                        <strong><?php echo esc_html($unit_item->post_title); ?></strong>
                                                        (ID: <?php echo esc_html($unit_item->ID); ?>)
                                                        <?php 
                                                        $unit_status = get_post_status($unit_item->ID);
                                                        if ($unit_status !== 'publish'): 
                                                        ?>
                                                            <span class="badge status-<?php echo esc_attr($unit_status); ?>"><?php echo esc_html(strtoupper($unit_status)); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($unit_item->ID == $course_id): ?>
                                                            <span class="badge">← CURRENT UNIT</span>
                                                        <?php endif; ?>
                                                        <?php if (isset($next_unit) && $next_unit && $unit_item->ID == $next_unit->ID): ?>
                                                            <span class="badge next">← NEXT UNIT</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ol>
                                        <?php else: ?>
                                            <p class="no-data">No units found</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="debugger-footer">
                                        <p><strong>How to use this debugger:</strong></p>
                                        <ul>
                                            <li>Add <code>?debug_nav=1</code> to the URL to enable this debugger</li>
                                            <li>Or define <code>IELTS_CM_DEBUG_NAV</code> constant as <code>true</code> in wp-config.php</li>
                                            <li>This panel shows all the logic that determines button visibility</li>
                                            <li>Use this to report exactly why the button isn't showing</li>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <script>
                    document.body.classList.add('has-sticky-bottom-nav');
                    </script>
                <?php endif; ?>
            <?php } ?>
            
            <?php endif; // end has_access check ?>
            
        <?php endwhile; ?>
    </main>
</div>

<?php
get_footer();
