<?php
/**
 * Template for displaying single course content in shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$enrollment = new IELTS_CM_Enrollment();
$progress_tracker = new IELTS_CM_Progress_Tracker();
$user_id = get_current_user_id();
$is_enrolled = $user_id ? $enrollment->is_enrolled($user_id, $course->ID) : false;
$completion = $user_id && $is_enrolled ? $progress_tracker->get_course_completion_percentage($user_id, $course->ID) : 0;
?>

<div class="ielts-single-course">
    <div class="course-header">
        <div class="course-breadcrumb">
            <a href="<?php echo home_url('/'); ?>"><?php _e('Home', 'ielts-course-manager'); ?></a>
            <span class="separator">&raquo;</span>
            <span><?php echo esc_html($course->post_title); ?></span>
        </div>
        
        <h2><?php echo esc_html($course->post_title); ?></h2>
        
        <?php if ($is_enrolled && $user_id): ?>
        <div class="course-meta">
            <?php
            // Get course completion percentage
            $completion = $progress_tracker->get_course_completion_percentage($user_id, $course->ID);
            
            // Get course average score
            $course_score_data = $progress_tracker->get_course_average_score($user_id, $course->ID);
            $average_score = $course_score_data['average_percentage'];
            $quiz_count = $course_score_data['quiz_count'];
            ?>
            <div class="course-progress-stats">
                <div class="course-stats-container">
                    <div class="course-stat-item course-stat-progress">
                        <span class="stat-label"><?php _e('Unit Progress:', 'ielts-course-manager'); ?></span>
                        <span class="stat-value"><?php echo number_format($completion, 1); ?>%</span>
                        <div class="stat-progress-bar">
                            <div class="stat-progress-fill" style="width: <?php echo min(100, $completion); ?>%;"></div>
                        </div>
                    </div>
                    <?php if ($quiz_count > 0): ?>
                        <div class="course-stat-item">
                            <span class="stat-label"><?php _e('Average Score:', 'ielts-course-manager'); ?></span>
                            <span class="stat-value"><?php echo number_format($average_score, 1); ?>%</span>
                            <small class="stat-description">(<?php printf(_n('%d test taken', '%d tests taken', $quiz_count, 'ielts-course-manager'), $quiz_count); ?>)</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    
    <div class="course-description">
        <?php 
        // Apply WordPress content filters to process embeds and shortcodes
        echo apply_filters('the_content', $course->post_content);
        ?>
    </div>
    
    <?php if (!empty($lessons)): ?>
        <div class="course-lessons">
            <h3><?php _e('Unit Lessons', 'ielts-course-manager'); ?></h3>
            
            <?php
            // Batch fetch content counts for all lessons to avoid N+1 queries
            $lesson_ids = array_map(function($lesson) { return $lesson->ID; }, $lessons);
            $all_lesson_counts = $progress_tracker->get_lessons_content_counts_batch($lesson_ids);
            ?>
            
            <table class="ielts-lessons-table">
                <thead>
                    <tr>
                        <?php if ($is_enrolled): ?>
                            <th class="lesson-status-col"><?php _e('Status', 'ielts-course-manager'); ?></th>
                        <?php endif; ?>
                        <th class="lesson-title-col"><?php _e('Lesson', 'ielts-course-manager'); ?></th>
                        <?php if ($is_enrolled): ?>
                            <th class="lesson-action-col"><?php _e('Action', 'ielts-course-manager'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lessons as $lesson): ?>
                        <?php
                        $is_completed = $is_enrolled && $progress_tracker->is_lesson_completed($user_id, $lesson->ID);
                        
                        // Get lesson content counts from batch results
                        $lesson_counts = isset($all_lesson_counts[$lesson->ID]) ? $all_lesson_counts[$lesson->ID] : array(
                            'resource_count' => 0,
                            'video_count' => 0,
                            'quiz_count' => 0
                        );
                        $resource_count = $lesson_counts['resource_count'];
                        $video_count = $lesson_counts['video_count'];
                        $quiz_count = $lesson_counts['quiz_count'];
                        
                        // Get lesson completion percentage for enrolled users
                        $lesson_completion = 0;
                        if ($is_enrolled && $user_id) {
                            $lesson_completion = $progress_tracker->get_lesson_completion_percentage($user_id, $lesson->ID);
                        }
                        
                        // Define circle circumference constant for SVG
                        $circle_circumference = 100.53; // 2 * PI * radius (radius = 16)
                        ?>
                        <tr class="lesson-row <?php echo $is_completed ? 'completed' : ''; ?>">
                            <?php if ($is_enrolled): ?>
                                <td class="lesson-status">
                                    <div class="lesson-progress-circle" data-progress="<?php echo round($lesson_completion); ?>">
                                        <svg width="40" height="40" viewBox="0 0 40 40">
                                            <circle class="progress-circle-bg" cx="20" cy="20" r="16" fill="none" stroke="#e0e0e0" stroke-width="3"></circle>
                                            <circle class="progress-circle-fill" cx="20" cy="20" r="16" fill="none" stroke="#46b450" stroke-width="3" 
                                                    stroke-dasharray="<?php echo round($lesson_completion * $circle_circumference / 100, 2); ?> <?php echo $circle_circumference; ?>" 
                                                    stroke-dashoffset="0" 
                                                    transform="rotate(-90 20 20)"></circle>
                                            <?php if ($is_completed): ?>
                                                <text x="20" y="20" text-anchor="middle" dy="0.3em" font-size="16" fill="#46b450">✓</text>
                                            <?php else: ?>
                                                <text x="20" y="20" text-anchor="middle" dy="0.3em" font-size="9" fill="#666"><?php echo round($lesson_completion); ?>%</text>
                                            <?php endif; ?>
                                        </svg>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td class="lesson-title">
                                <strong>
                                    <a href="<?php echo get_permalink($lesson->ID); ?>">
                                        <?php echo esc_html($lesson->post_title); ?>
                                    </a>
                                </strong>
                                <div class="lesson-content-counts">
                                    <?php
                                    $counts_parts = array();
                                    if ($resource_count > 0) {
                                        $counts_parts[] = sprintf(_n('%d learning resource', '%d learning resources', $resource_count, 'ielts-course-manager'), $resource_count);
                                    }
                                    if ($video_count > 0) {
                                        $counts_parts[] = sprintf(_n('%d video', '%d videos', $video_count, 'ielts-course-manager'), $video_count);
                                    }
                                    if ($quiz_count > 0) {
                                        $counts_parts[] = sprintf(_n('%d exercise', '%d exercises', $quiz_count, 'ielts-course-manager'), $quiz_count);
                                    }
                                    if (!empty($counts_parts)) {
                                        echo esc_html(implode(' • ', $counts_parts));
                                    }
                                    ?>
                                </div>
                            </td>
                            <?php if ($is_enrolled): ?>
                                <td class="lesson-action">
                                    <a href="<?php echo get_permalink($lesson->ID); ?>" class="button button-small">
                                        <?php echo $is_completed ? __('Review', 'ielts-course-manager') : __('Start', 'ielts-course-manager'); ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        /* Unit progress stats styling */
        .course-progress-stats {
            width: 100%;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .course-stats-container {
            display: flex;
            gap: 30px;
            flex-wrap: nowrap;
        }
        .course-stat-item {
            flex: 1;
            min-width: 200px;
        }
        /* Make progress bar stat item take more space on desktop */
        .course-stat-item.course-stat-progress {
            flex: 2;
        }
        
        /* Mobile: stack items vertically */
        @media (max-width: 768px) {
            .course-stats-container {
                flex-wrap: wrap;
            }
            .course-stat-item.course-stat-progress {
                flex: 0 0 100%;
            }
        }
        .course-stat-item .stat-label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .course-stat-item .stat-value {
            display: inline-block;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 8px;
        }
        .course-stat-item .stat-description {
            display: block;
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .stat-progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        .stat-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa 0%, #46b450 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .ielts-lessons-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ielts-lessons-table th,
        .ielts-lessons-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .ielts-lessons-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .ielts-lessons-table tr:hover {
            background-color: #f5f5f5;
        }
        .ielts-lessons-table .lesson-status-col {
            width: 60px;
            text-align: center;
        }
        .ielts-lessons-table .lesson-status {
            text-align: center;
        }
        .ielts-lessons-table .lesson-action-col {
            width: 100px;
            text-align: center;
        }
        .ielts-lessons-table .lesson-action {
            text-align: center;
        }
        .ielts-lessons-table .completed {
            background-color: #f0f9f0;
        }
        
        /* Lesson content counts styling */
        .lesson-content-counts {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-weight: normal;
        }
        
        /* Circular progress indicator styling */
        .lesson-progress-circle {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .lesson-progress-circle svg {
            display: block;
        }
        .progress-circle-fill {
            transition: stroke-dasharray 0.3s ease;
        }
        </style>
    <?php endif; ?>
    
    <?php
    // Previous/Next course navigation
    // Only show navigation for enrolled users
    $prev_course = null;
    $next_course = null;
    
    if ($user_id && $is_enrolled) {
        // Get current course categories
        $current_categories = wp_get_post_terms($course->ID, 'ielts_course_category', array('fields' => 'slugs'));
        if (is_wp_error($current_categories)) {
            $current_categories = array();
        }
        
        // Get all courses the user is enrolled in
        $enrolled_courses_data = $enrollment->get_user_courses($user_id);
        $enrolled_course_ids = array_column($enrolled_courses_data, 'course_id');
        
        if (!empty($enrolled_course_ids)) {
            // Get all enrolled courses
            $all_courses = get_posts(array(
                'post_type' => 'ielts_course',
                'posts_per_page' => -1,
                'post__in' => $enrolled_course_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish'
            ));
            
            // Filter courses to only include those with matching categories
            $filtered_courses = array();
            foreach ($all_courses as $c) {
                $c_categories = wp_get_post_terms($c->ID, 'ielts_course_category', array('fields' => 'slugs'));
                if (is_wp_error($c_categories)) {
                    $c_categories = array();
                }
                
                // Check if any category matches
                $has_matching_category = !empty(array_intersect($current_categories, $c_categories));
                
                // Include course if it has matching category
                // OR if both current course and this course have no categories (navigation within uncategorized courses)
                if ($has_matching_category || (empty($current_categories) && empty($c_categories))) {
                    $filtered_courses[] = $c;
                }
            }
            
            // Find current course index in filtered list
            $current_index = -1;
            foreach ($filtered_courses as $index => $c) {
                if ($c->ID == $course->ID) {
                    $current_index = $index;
                    break;
                }
            }
            
            // Set previous and next courses
            $prev_course = ($current_index > 0) ? $filtered_courses[$current_index - 1] : null;
            $next_course = ($current_index >= 0 && $current_index < count($filtered_courses) - 1) ? $filtered_courses[$current_index + 1] : null;
        }
    }
    ?>
    
    <?php if ($prev_course || $next_course): ?>
        <div class="ielts-sticky-bottom-nav">
            <div class="nav-item nav-prev">
                <?php if ($prev_course): ?>
                    <a href="<?php echo get_permalink($prev_course->ID); ?>" class="nav-link">
                        <span class="nav-arrow">&laquo;</span>
                        <span class="nav-label">
                            <small><?php _e('Previous Unit', 'ielts-course-manager'); ?></small>
                            <strong><?php echo esc_html($prev_course->post_title); ?></strong>
                        </span>
                    </a>
                <?php endif; ?>
            </div>
            <div class="nav-item nav-center">
                <a href="<?php echo home_url('/'); ?>" class="nav-link nav-back-to-course">
                    <span class="nav-label">
                        <small><?php _e('All courses', 'ielts-course-manager'); ?></small>
                    </span>
                </a>
            </div>
            <div class="nav-item nav-next">
                <?php if ($next_course): ?>
                    <a href="<?php echo get_permalink($next_course->ID); ?>" class="nav-link">
                        <span class="nav-label">
                            <small><?php _e('Next Unit', 'ielts-course-manager'); ?></small>
                            <strong><?php echo esc_html($next_course->post_title); ?></strong>
                        </span>
                        <span class="nav-arrow">&raquo;</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <script>
        document.body.classList.add('has-sticky-bottom-nav');
        </script>
    <?php endif; ?>
</div>
