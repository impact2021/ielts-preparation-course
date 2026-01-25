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
        add_shortcode('ielts_category_progress', array($this, 'display_category_progress'));
        add_shortcode('ielts_awards', array($this, 'display_awards'));
        add_shortcode('ielts_progress_rings', array($this, 'display_progress_rings'));
        add_shortcode('ielts_skills_radar', array($this, 'display_skills_radar'));
        
        // Membership shortcodes
        add_shortcode('ielts_login', array($this, 'display_login'));
        add_shortcode('ielts_registration', array($this, 'display_registration'));
        add_shortcode('ielts_account', array($this, 'display_account'));
    }
    
    /**
     * Display all courses
     */
    public function display_courses($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => -1,
            'columns' => 5,  // Default to 5 columns
            'orderby' => 'date',  // Default orderby
            'order' => 'DESC'  // Default order
        ), $atts);
        
        // Validate orderby parameter against allowed values
        $allowed_orderby = array('date', 'title', 'menu_order', 'ID', 'rand', 'modified');
        $orderby = sanitize_text_field($atts['orderby']);
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'date';
        }
        
        // Validate order parameter
        $order = strtoupper(sanitize_text_field($atts['order']));
        if (!in_array($order, array('ASC', 'DESC'))) {
            $order = 'DESC';
        }
        
        $args = array(
            'post_type' => 'ielts_course',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => $orderby,
            'order' => $order
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
        // Check for both integer and string serialization in course_ids array
        // Integer: i:123; String: s:3:"123";
        $int_pattern = '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%';
        $str_pattern = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
        
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_course_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))
        ", $course_id, $int_pattern, $str_pattern));
        
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
                                        <th><?php _e('Result', 'ielts-course-manager'); ?></th>
                                        <th><?php _e('Date', 'ielts-course-manager'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quiz_results as $result): 
                                        $quiz = get_post($result->quiz_id);
                                        if ($quiz) {
                                            // Get display score (band score or percentage)
                                            $display_score_data = $quiz_handler->get_display_score($quiz->ID, $result->score, $result->percentage);
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html($quiz->post_title); ?></td>
                                                <td><?php echo esc_html($result->score . ' / ' . $result->max_score); ?></td>
                                                <td><?php echo esc_html($display_score_data['display']); ?></td>
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
                'post__in' => $resource_ids
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
        if (!is_array($questions)) {
            $questions = array();
        }
        $course_id = get_post_meta($quiz_id, '_ielts_cm_course_id', true);
        $lesson_id = get_post_meta($quiz_id, '_ielts_cm_lesson_id', true);
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/single-quiz.php';
        return ob_get_clean();
    }
    
    /**
     * Display category progress - shows progress for all courses in a category
     */
    public function display_category_progress($atts) {
        $atts = shortcode_atts(array(
            'category' => ''
        ), $atts);
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return '<p>' . __('Please log in to view your progress.', 'ielts-course-manager') . '</p>';
        }
        
        if (empty($atts['category'])) {
            return '<p>' . __('Please specify a category.', 'ielts-course-manager') . '</p>';
        }
        
        // Get courses in the specified category
        $args = array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ielts_course_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );
        
        $courses = get_posts($args);
        
        if (empty($courses)) {
            return '<p>' . __('No courses found in this category.', 'ielts-course-manager') . '</p>';
        }
        
        $enrollment = new IELTS_CM_Enrollment();
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        
        ob_start();
        ?>
        <div class="ielts-category-progress">
            <div class="category-courses-list">
                <?php foreach ($courses as $course): ?>
                    <?php
                    $is_enrolled = $enrollment->is_enrolled($user_id, $course->ID);
                    if (!$is_enrolled) {
                        continue; // Only show enrolled courses
                    }
                    
                    $completion = $progress_tracker->get_course_completion_percentage($user_id, $course->ID);
                    $score_data = $progress_tracker->get_course_average_score($user_id, $course->ID);
                    $average_score = $score_data['average_percentage'];
                    $quiz_count = $score_data['quiz_count'];
                    ?>
                    <div class="category-course-item">
                        <div class="course-header">
                            <h3>
                                <a href="<?php echo get_permalink($course->ID); ?>">
                                    <?php echo esc_html($course->post_title); ?>
                                </a>
                            </h3>
                        </div>
                        <div class="course-progress-stats">
                            <div class="course-stats-container">
                                <div class="course-stat-item">
                                    <span class="stat-label"><?php _e('Progress:', 'ielts-course-manager'); ?></span>
                                    <span class="stat-value"><?php echo number_format($completion, 1); ?>%</span>
                                    <div class="stat-progress-bar">
                                        <div class="stat-progress-fill" style="width: <?php echo min(100, $completion); ?>%;"></div>
                                    </div>
                                </div>
                                <?php if ($quiz_count > 0): ?>
                                    <div class="course-stat-item">
                                        <span class="stat-label"><?php _e('Avg Score:', 'ielts-course-manager'); ?></span>
                                        <span class="stat-value"><?php echo number_format($average_score, 1); ?>%</span>
                                        <small class="stat-description">(<?php printf(_n('%d test', '%d tests', $quiz_count, 'ielts-course-manager'), $quiz_count); ?>)</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .ielts-category-progress {
            margin: 20px 0;
        }
        .category-courses-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .category-course-item {
            padding: 20px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .category-course-item .course-header h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
        }
        .category-course-item .course-header h3 a {
            text-decoration: none;
            color: #0073aa;
        }
        .category-course-item .course-header h3 a:hover {
            color: #005177;
        }
        .category-course-item .course-progress-stats {
            width: 100%;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .category-course-item .course-stats-container {
            display: flex;
            gap: 30px;
            flex-wrap: nowrap;
        }
        .category-course-item .course-stat-item {
            flex: 1;
            min-width: 180px;
        }
        
        /* Mobile: stack items vertically */
        @media (max-width: 768px) {
            .category-course-item .course-stats-container {
                flex-wrap: wrap;
            }
        }
        .category-course-item .course-stat-item .stat-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .category-course-item .course-stat-item .stat-value {
            display: inline-block;
            font-size: 20px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 6px;
        }
        .category-course-item .course-stat-item .stat-description {
            display: block;
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }
        .category-course-item .stat-progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 6px;
        }
        .category-course-item .stat-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa 0%, #46b450 100%);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display awards wall
     */
    public function display_awards($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your awards.', 'ielts-course-manager') . '</p>';
        }
        
        // Awards scripts and styles are now enqueued globally for logged-in users
        // See class-ielts-course-manager.php enqueue_scripts() method
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/awards-wall.php';
        return ob_get_clean();
    }
    
    /**
     * Display progress rings
     */
    public function display_progress_rings($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your progress.', 'ielts-course-manager') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'view' => 'daily', // daily, weekly, or monthly
        ), $atts);
        
        $view = in_array($atts['view'], array('daily', 'weekly', 'monthly')) ? $atts['view'] : 'daily';
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/progress-rings.php';
        return ob_get_clean();
    }
    
    /**
     * Display skills radar chart
     */
    public function display_skills_radar($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your skills profile.', 'ielts-course-manager') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'show_target' => 'yes', // yes or no - show Band 7 target line
            'height' => '400', // chart height in pixels
        ), $atts);
        
        $show_target = $atts['show_target'] === 'yes';
        $height = intval($atts['height']);
        if ($height < 200) $height = 200;
        if ($height > 800) $height = 800;
        
        ob_start();
        include IELTS_CM_PLUGIN_DIR . 'templates/skills-radar.php';
        return ob_get_clean();
    }
    
    /**
     * Display login form
     */
    public function display_login($atts) {
        $atts = shortcode_atts(array(
            'redirect' => ''
        ), $atts);
        
        if (is_user_logged_in()) {
            return '<p>' . __('You are already logged in.', 'ielts-course-manager') . ' <a href="' . wp_logout_url() . '">' . __('Logout', 'ielts-course-manager') . '</a></p>';
        }
        
        $redirect_url = !empty($atts['redirect']) ? $atts['redirect'] : get_permalink();
        
        ob_start();
        ?>
        <div class="ielts-login-form">
            <?php
            wp_login_form(array(
                'redirect' => $redirect_url,
                'form_id' => 'ielts-loginform',
                'label_username' => __('Username or Email', 'ielts-course-manager'),
                'label_password' => __('Password', 'ielts-course-manager'),
                'label_remember' => __('Remember Me', 'ielts-course-manager'),
                'label_log_in' => __('Log In', 'ielts-course-manager'),
                'remember' => true
            ));
            ?>
            <p class="ielts-login-links">
                <a href="<?php echo wp_lostpassword_url($redirect_url); ?>"><?php _e('Lost your password?', 'ielts-course-manager'); ?></a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display registration form
     */
    public function display_registration($atts) {
        $atts = shortcode_atts(array(
            'redirect' => ''
        ), $atts);
        
        if (is_user_logged_in()) {
            return '<p>' . __('You are already registered and logged in.', 'ielts-course-manager') . '</p>';
        }
        
        if (!get_option('users_can_register')) {
            return '<p>' . __('User registration is currently not allowed.', 'ielts-course-manager') . '</p>';
        }
        
        $errors = array();
        $success = false;
        
        if (isset($_POST['ielts_register_submit'])) {
            // Verify nonce first before processing any data
            if (!isset($_POST['ielts_register_nonce']) || !wp_verify_nonce($_POST['ielts_register_nonce'], 'ielts_register')) {
                $errors[] = __('Security check failed.', 'ielts-course-manager');
            } else {
                $username = sanitize_user($_POST['ielts_username']);
                $email = sanitize_email($_POST['ielts_email']);
                $password = $_POST['ielts_password'];
                $password_confirm = $_POST['ielts_password_confirm'];
                $membership_type = isset($_POST['ielts_membership_type']) ? sanitize_text_field($_POST['ielts_membership_type']) : '';
                
                // Validation
                if (empty($username)) {
                    $errors[] = __('Username is required.', 'ielts-course-manager');
                } elseif (username_exists($username)) {
                    $errors[] = __('Username already exists.', 'ielts-course-manager');
                }
                
                if (empty($email)) {
                    $errors[] = __('Email is required.', 'ielts-course-manager');
                } elseif (!is_email($email)) {
                    $errors[] = __('Invalid email address.', 'ielts-course-manager');
                } elseif (email_exists($email)) {
                    $errors[] = __('Email already exists.', 'ielts-course-manager');
                }
                
                if (empty($password)) {
                    $errors[] = __('Password is required.', 'ielts-course-manager');
                } elseif (strlen($password) < 6) {
                    $errors[] = __('Password must be at least 6 characters.', 'ielts-course-manager');
                } elseif ($password !== $password_confirm) {
                    $errors[] = __('Passwords do not match.', 'ielts-course-manager');
                }
                
                // Validate membership type if provided
                if (get_option('ielts_cm_membership_enabled') && !empty($membership_type)) {
                    $valid_memberships = array_keys(IELTS_CM_Membership::MEMBERSHIP_LEVELS);
                    if (!in_array($membership_type, $valid_memberships)) {
                        $errors[] = __('Invalid membership type selected.', 'ielts-course-manager');
                    }
                }
                
                // Create user if no errors
                if (empty($errors)) {
                    $user_id = wp_create_user($username, $password, $email);
                    if (is_wp_error($user_id)) {
                        $errors[] = $user_id->get_error_message();
                    } else {
                        // Set membership type if selected and membership system is enabled
                        if (!empty($membership_type) && get_option('ielts_cm_membership_enabled')) {
                            // Validate membership type one more time before saving (defense in depth)
                            $valid_memberships = array_keys(IELTS_CM_Membership::MEMBERSHIP_LEVELS);
                            if (in_array($membership_type, $valid_memberships)) {
                                update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
                                
                                // Set expiry date for trial memberships (30 days)
                                if ($membership_type === 'academic_trial' || $membership_type === 'general_trial') {
                                    $expiry_date = date('Y-m-d', strtotime('+30 days'));
                                    update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
                                }
                            }
                        }
                        
                        $success = true;
                        // Auto login
                        wp_set_current_user($user_id);
                        wp_set_auth_cookie($user_id);
                        
                        // Redirect if specified
                        if (!empty($atts['redirect'])) {
                            wp_redirect($atts['redirect']);
                            exit;
                        }
                    }
                }
            }
        }
        
        ob_start();
        ?>
        <div class="ielts-registration-form">
            <?php if ($success): ?>
                <div class="ielts-message ielts-success">
                    <p><?php _e('Registration successful! You are now logged in.', 'ielts-course-manager'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="ielts-message ielts-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('ielts_register', 'ielts_register_nonce'); ?>
                    
                    <p>
                        <label for="ielts_username"><?php _e('Username', 'ielts-course-manager'); ?> *</label>
                        <input type="text" name="ielts_username" id="ielts_username" required 
                               value="<?php echo isset($_POST['ielts_username']) ? esc_attr($_POST['ielts_username']) : ''; ?>">
                    </p>
                    
                    <p>
                        <label for="ielts_email"><?php _e('Email', 'ielts-course-manager'); ?> *</label>
                        <input type="email" name="ielts_email" id="ielts_email" required 
                               value="<?php echo isset($_POST['ielts_email']) ? esc_attr($_POST['ielts_email']) : ''; ?>">
                    </p>
                    
                    <p>
                        <label for="ielts_password"><?php _e('Password', 'ielts-course-manager'); ?> *</label>
                        <input type="password" name="ielts_password" id="ielts_password" required>
                        <small><?php _e('Minimum 6 characters', 'ielts-course-manager'); ?></small>
                    </p>
                    
                    <p>
                        <label for="ielts_password_confirm"><?php _e('Confirm Password', 'ielts-course-manager'); ?> *</label>
                        <input type="password" name="ielts_password_confirm" id="ielts_password_confirm" required>
                    </p>
                    
                    <?php if (get_option('ielts_cm_membership_enabled')): ?>
                        <p>
                            <label for="ielts_membership_type"><?php _e('Select Course', 'ielts-course-manager'); ?> *</label>
                            <select name="ielts_membership_type" id="ielts_membership_type" required>
                                <option value=""><?php _e('-- Select a course --', 'ielts-course-manager'); ?></option>
                                <?php 
                                $membership_levels = IELTS_CM_Membership::MEMBERSHIP_LEVELS;
                                $selected_membership = isset($_POST['ielts_membership_type']) ? $_POST['ielts_membership_type'] : '';
                                foreach ($membership_levels as $key => $label): 
                                ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($selected_membership, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    <?php endif; ?>
                    
                    <p>
                        <input type="submit" name="ielts_register_submit" value="<?php _e('Register', 'ielts-course-manager'); ?>" class="button">
                    </p>
                </form>
            <?php endif; ?>
        </div>
        
        <style>
        .ielts-registration-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .ielts-registration-form input[type="text"],
        .ielts-registration-form input[type="email"],
        .ielts-registration-form input[type="password"],
        .ielts-registration-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 5px;
        }
        .ielts-registration-form small {
            color: #666;
            font-size: 12px;
        }
        .ielts-message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .ielts-message.ielts-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .ielts-message.ielts-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .ielts-message ul {
            margin: 0;
            padding-left: 20px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display account page with membership info
     */
    public function display_account($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your account.', 'ielts-course-manager') . '</p>';
        }
        
        $user = wp_get_current_user();
        $membership_type = get_user_meta($user->ID, '_ielts_cm_membership_type', true);
        $expiry_date = get_user_meta($user->ID, '_ielts_cm_membership_expiry', true);
        
        // Get membership levels directly from constant
        $membership_levels = IELTS_CM_Membership::MEMBERSHIP_LEVELS;
        
        ob_start();
        ?>
        <div class="ielts-account-page">
            <h2><?php _e('Account Information', 'ielts-course-manager'); ?></h2>
            
            <div class="ielts-account-section">
                <h3><?php _e('Personal Details', 'ielts-course-manager'); ?></h3>
                <table class="ielts-account-table">
                    <tr>
                        <th><?php _e('Username:', 'ielts-course-manager'); ?></th>
                        <td><?php echo esc_html($user->user_login); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email:', 'ielts-course-manager'); ?></th>
                        <td><?php echo esc_html($user->user_email); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Display Name:', 'ielts-course-manager'); ?></th>
                        <td><?php echo esc_html($user->display_name); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if (get_option('ielts_cm_membership_enabled')): ?>
                <div class="ielts-account-section">
                    <h3><?php _e('Membership Information', 'ielts-course-manager'); ?></h3>
                    <table class="ielts-account-table">
                        <tr>
                            <th><?php _e('Membership Type:', 'ielts-course-manager'); ?></th>
                            <td>
                                <?php 
                                if (empty($membership_type)) {
                                    echo '<span class="ielts-no-membership">' . __('No active membership', 'ielts-course-manager') . '</span>';
                                } else {
                                    $membership_name = isset($membership_levels[$membership_type]) 
                                        ? $membership_levels[$membership_type] 
                                        : $membership_type;
                                    echo esc_html($membership_name);
                                }
                                ?>
                            </td>
                        </tr>
                        <?php if (!empty($membership_type)): ?>
                            <tr>
                                <th><?php _e('Expiry Date:', 'ielts-course-manager'); ?></th>
                                <td>
                                    <?php 
                                    if (empty($expiry_date)) {
                                        echo __('Lifetime', 'ielts-course-manager');
                                    } else {
                                        $expiry_timestamp = strtotime($expiry_date);
                                        $is_expired = $expiry_timestamp < time();
                                        if ($is_expired) {
                                            echo '<span class="ielts-expired">' . date('F j, Y', $expiry_timestamp) . ' (' . __('Expired', 'ielts-course-manager') . ')</span>';
                                        } else {
                                            echo '<span class="ielts-active">' . date('F j, Y', $expiry_timestamp) . '</span>';
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Status:', 'ielts-course-manager'); ?></th>
                                <td>
                                    <?php 
                                    if (!empty($expiry_date) && strtotime($expiry_date) < time()) {
                                        echo '<span class="ielts-status-expired">' . __('Expired', 'ielts-course-manager') . '</span>';
                                    } else {
                                        echo '<span class="ielts-status-active">' . __('Active', 'ielts-course-manager') . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="ielts-account-section">
                <h3><?php _e('Account Actions', 'ielts-course-manager'); ?></h3>
                <p>
                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="button">
                        <?php _e('Logout', 'ielts-course-manager'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <style>
        .ielts-account-page {
            max-width: 800px;
        }
        .ielts-account-section {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .ielts-account-section h3 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .ielts-account-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ielts-account-table th {
            text-align: left;
            padding: 10px;
            width: 200px;
            font-weight: bold;
        }
        .ielts-account-table td {
            padding: 10px;
        }
        .ielts-account-table tr {
            border-bottom: 1px solid #e0e0e0;
        }
        .ielts-account-table tr:last-child {
            border-bottom: none;
        }
        .ielts-no-membership {
            color: #999;
            font-style: italic;
        }
        .ielts-expired {
            color: #dc3232;
        }
        .ielts-active {
            color: #46b450;
        }
        .ielts-status-expired {
            background: #dc3232;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .ielts-status-active {
            background: #46b450;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
