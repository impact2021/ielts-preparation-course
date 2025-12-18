<?php
/**
 * Shortcode functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Shortcodes {
    
    public function register() {
        add_shortcode('ielts_courses', array($this, 'display_courses'));
        add_shortcode('ielts_course', array($this, 'display_single_course'));
        add_shortcode('ielts_progress', array($this, 'display_progress'));
        add_shortcode('ielts_my_progress', array($this, 'display_my_progress'));
        add_shortcode('ielts_my_account', array($this, 'display_my_account'));
        add_shortcode('ielts_lesson', array($this, 'display_lesson'));
        add_shortcode('ielts_quiz', array($this, 'display_quiz'));
    }
    
    /**
     * Display all courses
     */
    public function display_courses($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => -1,
            'columns' => 5  // Default to 5 columns
        ), $atts);
        
        $args = array(
            'post_type' => 'ielts_course',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ielts_course_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        $courses = get_posts($args);
        
        // Pass columns setting to template
        $columns = intval($atts['columns']);
        if ($columns < 1) {
            $columns = 1;
        } elseif ($columns > 6) {
            $columns = 6;
        }
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/courses-list.php';
        return ob_get_clean();
    }
    
    /**
     * Display single course
     */
    public function display_single_course($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $course_id = intval($atts['id']);
        if (!$course_id) {
            return '<p>' . __('Course not found', 'ielts-course-manager') . '</p>';
        }
        
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'ielts_course') {
            return '<p>' . __('Course not found', 'ielts-course-manager') . '</p>';
        }
        
        // Get lessons for this course - check both old and new meta keys
        global $wpdb;
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_course_ids' AND meta_value LIKE %s)
        ", $course_id, '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%'));
        
        $lessons = array();
        if (!empty($lesson_ids)) {
            $lessons = get_posts(array(
                'post_type' => 'ielts_lesson',
                'posts_per_page' => -1,
                'post__in' => $lesson_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ));
        }
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/single-course.php';
        return ob_get_clean();
    }
    
    /**
     * Display progress page
     */
    public function display_progress($atts) {
        $atts = shortcode_atts(array(
            'course_id' => 0
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your progress.', 'ielts-course-manager') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $course_id = intval($atts['course_id']);
        
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        $quiz_handler = new IELTS_CM_Quiz_Handler();
        $enrollment = new IELTS_CM_Enrollment();
        
        if ($course_id) {
            // Display progress for specific course
            $course = get_post($course_id);
            $progress = $progress_tracker->get_course_progress($user_id, $course_id);
            $quiz_results = $quiz_handler->get_quiz_results($user_id, $course_id);
            $completion = $progress_tracker->get_course_completion_percentage($user_id, $course_id);
        } else {
            // Display progress for all courses
            $enrolled_courses = $enrollment->get_user_courses($user_id);
            $all_progress = $progress_tracker->get_all_progress($user_id);
            $all_quiz_results = $quiz_handler->get_quiz_results($user_id);
        }
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/progress-page.php';
        return ob_get_clean();
    }
    
    /**
     * Display current user's own progress report
     */
    public function display_my_progress($atts) {
        $atts = shortcode_atts(array(
            'course_id' => 0
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="ielts-my-progress">' . 
                   '<p>' . __('Please log in to view your progress.', 'ielts-course-manager') . '</p>' . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $course_id = intval($atts['course_id']);
        
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        $quiz_handler = new IELTS_CM_Quiz_Handler();
        $enrollment = new IELTS_CM_Enrollment();
        
        ob_start();
        ?>
        <div class="ielts-my-progress">
            <h2><?php _e('My Progress', 'ielts-course-manager'); ?></h2>
            
            <?php
            if ($course_id) {
                // Display progress for specific course
                $course = get_post($course_id);
                if (!$course || !$enrollment->is_enrolled($user_id, $course_id)) {
                    echo '<p>' . __('You are not enrolled in this course.', 'ielts-course-manager') . '</p>';
                } else {
                    $completion = $progress_tracker->get_course_completion_percentage($user_id, $course_id);
                    $progress = $progress_tracker->get_course_progress($user_id, $course_id);
                    $quiz_results = $quiz_handler->get_quiz_results($user_id, $course_id);
                    ?>
                    <div class="course-progress-summary">
                        <h3><?php echo esc_html($course->post_title); ?></h3>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo round($completion, 1); ?>%;">
                                <?php echo round($completion, 1); ?>%
                            </div>
                        </div>
                        
                        <?php if (!empty($progress)): ?>
                            <h4><?php _e('Completed Lessons', 'ielts-course-manager'); ?></h4>
                            <ul class="completed-lessons-list">
                                <?php foreach ($progress as $item): 
                                    if ($item->completed) {
                                        $lesson = get_post($item->lesson_id);
                                        if ($lesson) {
                                            echo '<li>' . esc_html($lesson->post_title) . ' - ' . 
                                                 esc_html(mysql2date('F j, Y', $item->completed_date)) . '</li>';
                                        }
                                    }
                                endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <?php if (!empty($quiz_results)): ?>
                            <h4><?php _e('Quiz Results', 'ielts-course-manager'); ?></h4>
                            <table class="quiz-results-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Quiz', 'ielts-course-manager'); ?></th>
                                        <th><?php _e('Score', 'ielts-course-manager'); ?></th>
                                        <th><?php _e('Percentage', 'ielts-course-manager'); ?></th>
                                        <th><?php _e('Date', 'ielts-course-manager'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quiz_results as $result): 
                                        $quiz = get_post($result->quiz_id);
                                        if ($quiz) {
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html($quiz->post_title); ?></td>
                                                <td><?php echo esc_html($result->score . ' / ' . $result->max_score); ?></td>
                                                <td><?php echo round($result->percentage, 1); ?>%</td>
                                                <td><?php echo esc_html(mysql2date('F j, Y', $result->submitted_date)); ?></td>
                                            </tr>
                                            <?php
                                        }
                                    endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                // Display progress for all courses
                $all_courses = get_posts(array(
                    'post_type' => 'ielts_course',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                if (empty($all_courses)) {
                    echo '<p>' . __('No courses available yet.', 'ielts-course-manager') . '</p>';
                } else {
                    ?>
                    <div class="all-courses-progress">
                        <?php foreach ($all_courses as $course): 
                            $is_enrolled = $enrollment->is_enrolled($user_id, $course->ID);
                            $completion = $progress_tracker->get_course_completion_percentage($user_id, $course->ID);
                            $quiz_results = $quiz_handler->get_quiz_results($user_id, $course->ID);
                            ?>
                            <div class="course-progress-item <?php echo !$is_enrolled ? 'not-enrolled' : ''; ?>">
                                <h3>
                                    <a href="<?php echo get_permalink($course->ID); ?>">
                                        <?php echo esc_html($course->post_title); ?>
                                    </a>
                                    <?php if (!$is_enrolled): ?>
                                        <span class="enrollment-badge not-enrolled"><?php _e('Not Enrolled', 'ielts-course-manager'); ?></span>
                                    <?php else: ?>
                                        <span class="enrollment-badge enrolled"><?php _e('Enrolled', 'ielts-course-manager'); ?></span>
                                    <?php endif; ?>
                                </h3>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo round($completion, 1); ?>%;">
                                        <?php echo round($completion, 1); ?>%
                                    </div>
                                </div>
                                <p>
                                    <?php 
                                    printf(
                                        __('%d quizzes completed', 'ielts-course-manager'), 
                                        count($quiz_results)
                                    );
                                    ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        
        <style>
        .ielts-my-progress {
            padding: 20px;
        }
        .ielts-my-progress .progress-bar {
            width: 100%;
            height: 30px;
            background-color: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .ielts-my-progress .progress-fill {
            height: 100%;
            background-color: #4CAF50;
            text-align: center;
            line-height: 30px;
            color: white;
            font-weight: bold;
            transition: width 0.3s ease;
        }
        .ielts-my-progress .course-progress-item {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .ielts-my-progress .course-progress-item.not-enrolled {
            background-color: #f9f9f9;
            border-color: #ccc;
        }
        .ielts-my-progress .course-progress-item h3 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ielts-my-progress .enrollment-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .ielts-my-progress .enrollment-badge.enrolled {
            background-color: #d4edda;
            color: #155724;
        }
        .ielts-my-progress .enrollment-badge.not-enrolled {
            background-color: #fff3cd;
            color: #856404;
        }
        .ielts-my-progress .completed-lessons-list {
            list-style: disc;
            margin-left: 20px;
        }
        .ielts-my-progress .quiz-results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .ielts-my-progress .quiz-results-table th,
        .ielts-my-progress .quiz-results-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .ielts-my-progress .quiz-results-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display user's account page with enrollment details
     */
    public function display_my_account($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ielts-my-account">' . 
                   '<p>' . __('Please log in to view your account.', 'ielts-course-manager') . '</p>' . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $enrollment = new IELTS_CM_Enrollment();
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        
        // Get all enrolled courses
        $enrolled_courses = $enrollment->get_user_courses($user_id);
        
        ob_start();
        ?>
        <div class="ielts-my-account">
            <h2><?php _e('My Account', 'ielts-course-manager'); ?></h2>
            
            <div class="account-section user-details">
                <h3><?php _e('User Information', 'ielts-course-manager'); ?></h3>
                <table class="account-info-table">
                    <tr>
                        <th><?php _e('Username:', 'ielts-course-manager'); ?></th>
                        <td><?php echo esc_html($user->user_login); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email:', 'ielts-course-manager'); ?></th>
                        <td><?php echo esc_html($user->user_email); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Name:', 'ielts-course-manager'); ?></th>
                        <td><?php echo esc_html(trim($user->first_name . ' ' . $user->last_name)); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="account-section course-enrollments">
                <h3><?php _e('My Course Enrollments', 'ielts-course-manager'); ?></h3>
                
                <?php if (empty($enrolled_courses)): ?>
                    <p><?php _e('You are not currently enrolled in any courses.', 'ielts-course-manager'); ?></p>
                <?php else: ?>
                    <div class="enrolled-courses-list">
                        <?php foreach ($enrolled_courses as $enrollment_data): 
                            $course = get_post($enrollment_data->course_id);
                            if (!$course) continue;
                            
                            $completion = $progress_tracker->get_course_completion_percentage($user_id, $enrollment_data->course_id);
                            $enrolled_date = date('F j, Y', strtotime($enrollment_data->enrolled_date));
                            $end_date = $enrollment_data->course_end_date ? date('F j, Y', strtotime($enrollment_data->course_end_date)) : __('No end date set', 'ielts-course-manager');
                            
                            // Check if course access has expired
                            $is_expired = false;
                            if ($enrollment_data->course_end_date && strtotime($enrollment_data->course_end_date) < time()) {
                                $is_expired = true;
                            }
                        ?>
                            <div class="enrolled-course-item <?php echo $is_expired ? 'expired' : ''; ?>">
                                <div class="course-header">
                                    <h4>
                                        <a href="<?php echo get_permalink($course->ID); ?>">
                                            <?php echo esc_html($course->post_title); ?>
                                        </a>
                                        <?php if ($is_expired): ?>
                                            <span class="expired-badge"><?php _e('Expired', 'ielts-course-manager'); ?></span>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                
                                <div class="course-details">
                                    <div class="course-detail-row">
                                        <span class="detail-label"><?php _e('Enrolled:', 'ielts-course-manager'); ?></span>
                                        <span class="detail-value"><?php echo esc_html($enrolled_date); ?></span>
                                    </div>
                                    <div class="course-detail-row">
                                        <span class="detail-label"><?php _e('Access Until:', 'ielts-course-manager'); ?></span>
                                        <span class="detail-value <?php echo $is_expired ? 'expired-date' : ''; ?>">
                                            <?php echo esc_html($end_date); ?>
                                        </span>
                                    </div>
                                    <div class="course-detail-row">
                                        <span class="detail-label"><?php _e('Progress:', 'ielts-course-manager'); ?></span>
                                        <span class="detail-value"><?php echo round($completion, 1); ?>%</span>
                                    </div>
                                </div>
                                
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo round($completion, 1); ?>%;">
                                        <span class="progress-text"><?php echo round($completion, 1); ?>%</span>
                                    </div>
                                </div>
                                
                                <div class="course-actions">
                                    <?php if (!$is_expired): ?>
                                        <a href="<?php echo get_permalink($course->ID); ?>" class="button">
                                            <?php _e('Continue Learning', 'ielts-course-manager'); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="expired-notice">
                                            <?php _e('Your access to this course has expired. Please contact support to renew.', 'ielts-course-manager'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .ielts-my-account {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .ielts-my-account h2 {
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        .account-section {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .account-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .account-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .account-info-table th {
            text-align: left;
            padding: 10px;
            width: 150px;
            font-weight: 600;
            color: #555;
        }
        .account-info-table td {
            padding: 10px;
            color: #333;
        }
        .enrolled-courses-list {
            display: grid;
            gap: 20px;
        }
        .enrolled-course-item {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            transition: box-shadow 0.3s;
        }
        .enrolled-course-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .enrolled-course-item.expired {
            opacity: 0.7;
            background: #f8f8f8;
        }
        .course-header h4 {
            margin: 0 0 15px 0;
            font-size: 20px;
        }
        .course-header h4 a {
            color: #0073aa;
            text-decoration: none;
        }
        .course-header h4 a:hover {
            text-decoration: underline;
        }
        .expired-badge {
            display: inline-block;
            background: #dc3232;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: normal;
            margin-left: 10px;
        }
        .course-details {
            margin-bottom: 15px;
        }
        .course-detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .course-detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
            width: 150px;
        }
        .detail-value {
            color: #333;
        }
        .detail-value.expired-date {
            color: #dc3232;
            font-weight: 600;
        }
        .progress-bar {
            background: #e0e0e0;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
            position: relative;
        }
        .progress-fill {
            background: linear-gradient(to right, #4caf50, #66bb6a);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: width 0.5s ease;
        }
        .progress-text {
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }
        .course-actions {
            text-align: left;
        }
        .course-actions .button {
            background: #0073aa;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            transition: background 0.3s;
        }
        .course-actions .button:hover {
            background: #005177;
        }
        .expired-notice {
            color: #dc3232;
            font-style: italic;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display lesson
     */
    public function display_lesson($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $lesson_id = intval($atts['id']);
        if (!$lesson_id) {
            return '<p>' . __('Lesson not found', 'ielts-course-manager') . '</p>';
        }
        
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'ielts_lesson') {
            return '<p>' . __('Lesson not found', 'ielts-course-manager') . '</p>';
        }
        
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
                'post__in' => $resource_ids
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
                'post__in' => $quiz_ids
            ));
        }
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/single-lesson.php';
        return ob_get_clean();
    }
    
    /**
     * Display quiz
     */
    public function display_quiz($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $quiz_id = intval($atts['id']);
        if (!$quiz_id) {
            return '<p>' . __('Quiz not found', 'ielts-course-manager') . '</p>';
        }
        
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'ielts_quiz') {
            return '<p>' . __('Quiz not found', 'ielts-course-manager') . '</p>';
        }
        
        $questions = get_post_meta($quiz_id, '_ielts_cm_questions', true);
        $course_id = get_post_meta($quiz_id, '_ielts_cm_course_id', true);
        $lesson_id = get_post_meta($quiz_id, '_ielts_cm_lesson_id', true);
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/single-quiz.php';
        return ob_get_clean();
    }
}
