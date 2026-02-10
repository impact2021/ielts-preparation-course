<?php
/**
 * Template for displaying progress page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ielts-progress-page">
    <h2><?php _e('My Learning Progress', 'ielts-course-manager'); ?></h2>
    
    <?php if ($course_id): ?>
        <!-- Single course progress -->
        <div class="single-course-progress">
            <div class="course-header">
                <h3><?php echo esc_html($course->post_title); ?></h3>
                <div class="completion-bar">
                    <div class="completion-label">
                        <?php printf(__('Progress: %s%%', 'ielts-course-manager'), number_format($completion, 1)); ?>
                    </div>
                    <div class="completion-bar-outer">
                        <div class="completion-bar-inner" style="width: <?php echo $completion; ?>%"></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($progress)): ?>
                <div class="lessons-progress">
                    <h4><?php _e('Lessons Progress', 'ielts-course-manager'); ?></h4>
                    <table class="progress-table">
                        <thead>
                            <tr>
                                <th><?php _e('Lesson', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Last Accessed', 'ielts-course-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progress as $item): ?>
                                <?php $lesson = get_post($item->lesson_id); ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_permalink($item->lesson_id); ?>">
                                            <?php echo esc_html($lesson->post_title); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($item->completed): ?>
                                            <span class="status-completed">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                <?php _e('Completed', 'ielts-course-manager'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-in-progress">
                                                <span class="dashicons dashicons-marker"></span>
                                                <?php _e('In Progress', 'ielts-course-manager'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->last_accessed)); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($quiz_results)): ?>
                <div class="quiz-results">
                    <h4><?php _e('Quiz Results', 'ielts-course-manager'); ?></h4>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th><?php _e('Quiz', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Score', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Percentage', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Date', 'ielts-course-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_results as $result): ?>
                                <?php $quiz = get_post($result->quiz_id); ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_permalink($result->quiz_id); ?>">
                                            <?php echo esc_html($quiz->post_title); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo $result->score; ?> / <?php echo $result->max_score; ?>
                                    </td>
                                    <td>
                                        <span class="percentage-badge percentage-<?php echo $result->percentage >= 70 ? 'pass' : 'fail'; ?>">
                                            <?php echo round($result->percentage, 1); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result->submitted_date)); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- All courses progress -->
        <div class="all-courses-progress">
            <?php if (!empty($enrolled_courses)): ?>
                <?php foreach ($enrolled_courses as $enrollment_data): ?>
                    <?php
                    $course = get_post($enrollment_data->course_id);
                    $course_progress = $progress_tracker->get_course_progress($user_id, $enrollment_data->course_id);
                    $course_completion = $progress_tracker->get_course_completion_percentage($user_id, $enrollment_data->course_id);
                    $course_quiz_results = $quiz_handler->get_quiz_results($user_id, $enrollment_data->course_id);
                    ?>
                    
                    <div class="course-progress-item">
                        <div class="course-header">
                            <h3>
                                <a href="<?php echo get_permalink($course->ID); ?>">
                                    <?php echo esc_html($course->post_title); ?>
                                </a>
                            </h3>
                            
                            <div class="completion-bar">
                                <div class="completion-label">
                                    <?php printf(__('Progress: %s%%', 'ielts-course-manager'), number_format($course_completion, 1)); ?>
                                </div>
                                <div class="completion-bar-outer">
                                    <div class="completion-bar-inner" style="width: <?php echo $course_completion; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="course-stats">
                            <div class="stat">
                                <span class="stat-label"><?php _e('Lessons Accessed:', 'ielts-course-manager'); ?></span>
                                <span class="stat-value"><?php echo count($course_progress); ?></span>
                            </div>
                            
                            <div class="stat">
                                <span class="stat-label"><?php _e('Lessons Completed:', 'ielts-course-manager'); ?></span>
                                <span class="stat-value">
                                    <?php echo count(array_filter($course_progress, function($p) { return $p->completed; })); ?>
                                </span>
                            </div>
                            
                            <div class="stat">
                                <span class="stat-label"><?php _e('Quizzes Taken:', 'ielts-course-manager'); ?></span>
                                <span class="stat-value"><?php echo count($course_quiz_results); ?></span>
                            </div>
                        </div>
                        
                        <div class="course-actions">
                            <a href="<?php echo add_query_arg('course', $course->ID, get_permalink()); ?>" class="button">
                                <?php _e('View Details', 'ielts-course-manager'); ?>
                            </a>
                            <a href="<?php echo get_permalink($course->ID); ?>" class="button button-primary">
                                <?php _e('Continue Learning', 'ielts-course-manager'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-courses-message">
                    <p><?php _e('You are not enrolled in any courses yet.', 'ielts-course-manager'); ?></p>
                    <a href="<?php echo get_post_type_archive_link('ielts_course'); ?>" class="button button-primary">
                        <?php _e('Browse Courses', 'ielts-course-manager'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
