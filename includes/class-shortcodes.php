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
        add_shortcode('ielts_band_scores', array($this, 'display_band_scores'));
        
        // Membership shortcodes
        add_shortcode('ielts_login', array($this, 'display_login'));
        add_shortcode('ielts_registration', array($this, 'display_registration'));
        add_shortcode('ielts_account', array($this, 'display_account'));
    }
    
    /**
     * Get module type from membership type
     * 
     * @param string $membership_type The membership type
     * @return string The module type ('academic', 'general', or empty string)
     */
    private function get_module_from_membership($membership_type) {
        if (strpos($membership_type, 'academic') !== false) {
            return 'academic';
        } elseif (strpos($membership_type, 'general') !== false) {
            return 'general';
        }
        return '';
    }
    
    /**
     * Get module type from course categories
     * 
     * @param int $course_id The course ID
     * @return string The module type ('academic', 'general', or empty string)
     */
    private function get_module_from_course($course_id) {
        $categories = wp_get_post_terms($course_id, 'ielts_course_category', array('fields' => 'slugs'));
        
        foreach ($categories as $cat_slug) {
            if (strpos(strtolower($cat_slug), 'academic') !== false) {
                return 'academic';
            } elseif (strpos(strtolower($cat_slug), 'general') !== false) {
                return 'general';
            }
        }
        
        return '';
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
            // Support comma-separated categories (e.g., "academic,general")
            $categories = array_map('trim', explode(',', $atts['category']));
            
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ielts_course_category',
                    'field' => 'slug',
                    'terms' => $categories
                )
            );
        }
        
        $courses = get_posts($args);
        
        // Filter courses based on membership if membership system is enabled
        if (get_option('ielts_cm_membership_enabled') && is_user_logged_in()) {
            $user_id = get_current_user_id();
            $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
            
            if (!empty($membership_type)) {
                // Determine the module type from membership
                $user_module = $this->get_module_from_membership($membership_type);
                
                // Filter courses by category if user has a specific module
                if (!empty($user_module)) {
                    $filtered_courses = array();
                    foreach ($courses as $course) {
                        $course_module = $this->get_module_from_course($course->ID);
                        
                        // Only include course if it matches user's module
                        // Exclude courses from opposite module (academic/general)
                        if ($course_module === $user_module) {
                            $filtered_courses[] = $course;
                        }
                    }
                    $courses = $filtered_courses;
                }
            }
        }
        
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
        
        // Check module-based access if membership is enabled
        if (get_option('ielts_cm_membership_enabled') && is_user_logged_in()) {
            $user_id = get_current_user_id();
            $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
            
            if (!empty($membership_type)) {
                // Determine the module type from membership
                $user_module = $this->get_module_from_membership($membership_type);
                
                // Check if course belongs to a different module
                if (!empty($user_module)) {
                    $course_module = $this->get_module_from_course($course_id);
                    
                    // Deny access if course is from a different module
                    if (!empty($course_module) && $course_module !== $user_module) {
                        return '<div class="ielts-access-denied"><p>' . 
                               __('This course is not available with your current membership type.', 'ielts-course-manager') . 
                               '</p></div>';
                    }
                }
            }
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
        
        // Handle email update
        $email_update_message = '';
        if (isset($_POST['ielts_update_email']) && wp_verify_nonce($_POST['ielts_email_nonce'], 'ielts_update_email')) {
            $new_email = sanitize_email($_POST['ielts_new_email']);
            if (empty($new_email)) {
                $email_update_message = '<div class="ielts-message ielts-error">' . __('Email is required.', 'ielts-course-manager') . '</div>';
            } elseif (!is_email($new_email)) {
                $email_update_message = '<div class="ielts-message ielts-error">' . __('Invalid email address.', 'ielts-course-manager') . '</div>';
            } elseif ($new_email !== $user->user_email && email_exists($new_email)) {
                $email_update_message = '<div class="ielts-message ielts-error">' . __('Email already exists.', 'ielts-course-manager') . '</div>';
            } else {
                $result = wp_update_user(array(
                    'ID' => $user_id,
                    'user_email' => $new_email
                ));
                if (is_wp_error($result)) {
                    $email_update_message = '<div class="ielts-message ielts-error">' . $result->get_error_message() . '</div>';
                } else {
                    $email_update_message = '<div class="ielts-message ielts-success">' . __('Email updated successfully!', 'ielts-course-manager') . '</div>';
                    $user = get_userdata($user_id); // Refresh user data
                }
            }
        }
        
        // Get all enrolled courses
        $enrolled_courses = $enrollment->get_user_courses($user_id);
        
        // Get membership information
        $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
        $expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);
        $is_trial = $membership_type && IELTS_CM_Membership::is_trial_membership($membership_type);
        $upgrade_url = get_option('ielts_cm_full_member_page_url', home_url());
        
        ob_start();
        ?>
        <div class="ielts-my-account">
            <h2><?php _e('My Account', 'ielts-course-manager'); ?></h2>
            
            <!-- Tabs Navigation -->
            <div class="ielts-account-tabs">
                <button class="ielts-tab-button active" data-tab="membership-info">
                    <?php _e('Membership Information', 'ielts-course-manager'); ?>
                </button>
                <button class="ielts-tab-button" data-tab="personal-details">
                    <?php _e('Personal Details', 'ielts-course-manager'); ?>
                </button>
                <?php if ($membership_type): ?>
                    <button class="ielts-tab-button" data-tab="membership-action">
                        <?php echo $is_trial ? __('Become a Full Member', 'ielts-course-manager') : __('Extend My Course', 'ielts-course-manager'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Membership Information Tab -->
            <div class="ielts-tab-content active" id="membership-info">
                <div class="account-section">
                    <h3><?php _e('Membership Status', 'ielts-course-manager'); ?></h3>
                    <?php if ($membership_type): 
                        $membership_name = isset(IELTS_CM_Membership::MEMBERSHIP_LEVELS[$membership_type]) 
                            ? IELTS_CM_Membership::MEMBERSHIP_LEVELS[$membership_type] 
                            : $membership_type;
                        $is_expired = !empty($expiry_date) && strtotime($expiry_date) < time();
                    ?>
                        <table class="account-info-table">
                            <tr>
                                <th><?php _e('Membership Type:', 'ielts-course-manager'); ?></th>
                                <td><?php echo esc_html($membership_name); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Expiry Date:', 'ielts-course-manager'); ?></th>
                                <td>
                                    <?php 
                                    if (empty($expiry_date)) {
                                        echo __('Lifetime', 'ielts-course-manager');
                                    } else {
                                        $expiry_timestamp = strtotime($expiry_date);
                                        if ($is_expired) {
                                            echo '<span class="expired-text">' . date('F j, Y g:i a', $expiry_timestamp) . ' (' . __('Expired', 'ielts-course-manager') . ')</span>';
                                        } else {
                                            echo '<span class="active-text">' . date('F j, Y g:i a', $expiry_timestamp) . '</span>';
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Status:', 'ielts-course-manager'); ?></th>
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span class="status-badge status-expired"><?php _e('Expired', 'ielts-course-manager'); ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-active"><?php _e('Active', 'ielts-course-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p><?php _e('You do not have an active membership.', 'ielts-course-manager'); ?></p>
                    <?php endif; ?>
                    
                    <h4><?php _e('My Courses', 'ielts-course-manager'); ?></h4>
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
                                $course_expired = false;
                                if ($enrollment_data->course_end_date && strtotime($enrollment_data->course_end_date) < time()) {
                                    $course_expired = true;
                                }
                            ?>
                                <div class="enrolled-course-item <?php echo $course_expired ? 'expired' : ''; ?>">
                                    <div class="course-header">
                                        <h4>
                                            <a href="<?php echo get_permalink($course->ID); ?>">
                                                <?php echo esc_html($course->post_title); ?>
                                            </a>
                                            <?php if ($course_expired): ?>
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
                                            <span class="detail-value <?php echo $course_expired ? 'expired-date' : ''; ?>">
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
                                        <?php if (!$course_expired): ?>
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
            
            <!-- Personal Details Tab -->
            <div class="ielts-tab-content" id="personal-details">
                <div class="account-section">
                    <h3><?php _e('Personal Information', 'ielts-course-manager'); ?></h3>
                    <?php echo $email_update_message; ?>
                    <table class="account-info-table">
                        <tr>
                            <th><?php _e('Email:', 'ielts-course-manager'); ?></th>
                            <td><?php echo esc_html($user->user_email); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Name:', 'ielts-course-manager'); ?></th>
                            <td><?php echo esc_html(trim($user->first_name . ' ' . $user->last_name)); ?></td>
                        </tr>
                    </table>
                    
                    <h4><?php _e('Update Email Address', 'ielts-course-manager'); ?></h4>
                    <form method="post" class="ielts-email-update-form">
                        <?php wp_nonce_field('ielts_update_email', 'ielts_email_nonce'); ?>
                        <p>
                            <label for="ielts_new_email"><?php _e('New Email Address', 'ielts-course-manager'); ?></label>
                            <input type="email" name="ielts_new_email" id="ielts_new_email" 
                                   value="<?php echo esc_attr($user->user_email); ?>" required class="ielts-input">
                        </p>
                        <p>
                            <button type="submit" name="ielts_update_email" class="ielts-button">
                                <?php _e('Update Email', 'ielts-course-manager'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            
            <!-- Membership Action Tab (Upgrade/Extend) -->
            <?php if ($membership_type): ?>
                <div class="ielts-tab-content" id="membership-action">
                    <div class="account-section">
                        <?php if ($is_trial): ?>
                            <h3><?php _e('Become a Full Member', 'ielts-course-manager'); ?></h3>
                            <p><?php _e('Upgrade to a full membership to get unlimited access to all courses and features.', 'ielts-course-manager'); ?></p>
                            <div class="membership-cta">
                                <a href="<?php echo esc_url($upgrade_url); ?>" class="ielts-button ielts-button-primary">
                                    <?php _e('Upgrade Now', 'ielts-course-manager'); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <h3><?php _e('Extend My Course', 'ielts-course-manager'); ?></h3>
                            <p><?php _e('Extend your course access to continue learning without interruption.', 'ielts-course-manager'); ?></p>
                            <div class="membership-cta">
                                <a href="<?php echo esc_url($upgrade_url); ?>" class="ielts-button ielts-button-primary">
                                    <?php _e('Extend Course', 'ielts-course-manager'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        (function() {
            var tabButtons = document.querySelectorAll('.ielts-tab-button');
            var tabContents = document.querySelectorAll('.ielts-tab-content');
            
            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    tabButtons.forEach(function(btn) {
                        btn.classList.remove('active');
                    });
                    tabContents.forEach(function(content) {
                        content.classList.remove('active');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        })();
        </script>
        
        <style>
        .ielts-my-account {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .ielts-my-account h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        
        /* Tabs Navigation */
        .ielts-account-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            flex-wrap: wrap;
        }
        .ielts-tab-button {
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
            margin-bottom: -2px;
        }
        .ielts-tab-button:hover {
            color: #0073aa;
            background: #f5f5f5;
        }
        .ielts-tab-button.active {
            color: #0073aa;
            border-bottom-color: #0073aa;
            font-weight: 600;
        }
        
        /* Tab Content */
        .ielts-tab-content {
            display: none;
        }
        .ielts-tab-content.active {
            display: block;
        }
        
        /* Messages */
        .ielts-message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .ielts-message.ielts-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .ielts-message.ielts-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .account-section {
            background: #f9f9f9;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .account-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
        }
        .account-section h4 {
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 18px;
            color: #555;
        }
        .account-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .account-info-table th {
            text-align: left;
            padding: 12px 10px;
            width: 180px;
            font-weight: 600;
            color: #555;
            vertical-align: top;
        }
        .account-info-table td {
            padding: 12px 10px;
            color: #333;
        }
        
        /* Email Update Form */
        .ielts-email-update-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        .ielts-input {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .ielts-button {
            background: #0073aa;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .ielts-button:hover {
            background: #005177;
        }
        .ielts-button-primary {
            background: #0073aa;
            font-size: 16px;
            padding: 12px 30px;
        }
        .ielts-button-primary:hover {
            background: #005177;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-badge.status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        .expired-text {
            color: #dc3232;
            font-weight: 600;
        }
        .active-text {
            color: #28a745;
            font-weight: 600;
        }
        
        /* Membership CTA */
        .membership-cta {
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 4px;
            text-align: center;
        }
        
        /* Course Enrollment List */
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
        
        // Validate and sanitize redirect URL
        $redirect_url = !empty($atts['redirect']) ? esc_url_raw($atts['redirect']) : home_url();
        // Ensure redirect is to same site for security
        $redirect_url = wp_validate_redirect($redirect_url, home_url());
        
        ob_start();
        ?>
        <div class="ielts-login-form-wrapper">
            <div class="ielts-login-form">
                <?php
                wp_login_form(array(
                    'redirect' => $redirect_url,
                    'form_id' => 'ielts-loginform',
                    'label_username' => __('Email Address', 'ielts-course-manager'),
                    'label_password' => __('Password', 'ielts-course-manager'),
                    'label_remember' => __('Remember Me', 'ielts-course-manager'),
                    'label_log_in' => __('Log In', 'ielts-course-manager'),
                    'remember' => true,
                    'value_remember' => true
                ));
                ?>
                <p class="ielts-login-links">
                    <a href="<?php echo wp_lostpassword_url($redirect_url); ?>"><?php _e('Lost your password?', 'ielts-course-manager'); ?></a>
                </p>
            </div>
        </div>
        
        <style>
        .ielts-login-form-wrapper {
            max-width: 450px;
            margin: 0 auto;
        }
        .ielts-login-form {
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ielts-login-form form {
            margin: 0;
        }
        .ielts-login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .ielts-login-form input[type="text"],
        .ielts-login-form input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .ielts-login-form input[type="text"]:focus,
        .ielts-login-form input[type="password"]:focus {
            outline: none;
            border-color: #0073aa;
        }
        .ielts-login-form .login-remember {
            margin: 15px 0;
        }
        .ielts-login-form .login-remember label {
            display: inline;
            font-weight: normal;
            font-size: 14px;
        }
        .ielts-login-form .login-remember input[type="checkbox"] {
            margin-right: 5px;
        }
        .ielts-login-form input[type="submit"] {
            width: 100%;
            padding: 14px 20px;
            background: #0073aa;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .ielts-login-form input[type="submit"]:hover {
            background: #005177;
        }
        .ielts-login-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .ielts-login-links a {
            color: #0073aa;
            text-decoration: none;
        }
        .ielts-login-links a:hover {
            text-decoration: underline;
        }
        </style>
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
        
        // Enqueue Stripe.js and payment handling scripts if membership system is enabled
        if (get_option('ielts_cm_membership_enabled')) {
            $stripe_publishable = get_option('ielts_cm_stripe_publishable_key', '');
            $pricing = get_option('ielts_cm_membership_pricing', array());
            
            if (!empty($stripe_publishable)) {
                wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
                wp_enqueue_script('ielts-registration-payment', IELTS_CM_PLUGIN_URL . 'assets/js/registration-payment.js', array('jquery', 'stripe-js'), IELTS_CM_VERSION, true);
                
                wp_localize_script('ielts-registration-payment', 'ieltsPayment', array(
                    'publishableKey' => $stripe_publishable,
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ielts_payment_intent'),
                    'pricing' => $pricing,
                ));
            }
        }
        
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
                $first_name = isset($_POST['ielts_first_name']) ? sanitize_text_field($_POST['ielts_first_name']) : '';
                $last_name = isset($_POST['ielts_last_name']) ? sanitize_text_field($_POST['ielts_last_name']) : '';
                $email = sanitize_email($_POST['ielts_email']);
                $password = $_POST['ielts_password'];
                $password_confirm = $_POST['ielts_password_confirm'];
                $membership_type = isset($_POST['ielts_membership_type']) ? sanitize_text_field($_POST['ielts_membership_type']) : '';
                
                // Validate name fields
                if (empty($first_name)) {
                    $errors[] = __('First name is required.', 'ielts-course-manager');
                }
                if (empty($last_name)) {
                    $errors[] = __('Last name is required.', 'ielts-course-manager');
                }
                
                // Validate email first before using it for username generation
                if (empty($email)) {
                    $errors[] = __('Email is required.', 'ielts-course-manager');
                } elseif (!is_email($email)) {
                    $errors[] = __('Invalid email address.', 'ielts-course-manager');
                } elseif (email_exists($email)) {
                    $errors[] = __('Email already exists.', 'ielts-course-manager');
                }
                
                // Generate username from email (more user-friendly format)
                // Only if email is valid
                if (empty($errors) && is_email($email) && strpos($email, '@') !== false) {
                    $email_parts = explode('@', $email);
                    $base_username = sanitize_user($email_parts[0], true);
                    
                    // Ensure username is not empty
                    if (empty($base_username)) {
                        $base_username = 'user';
                    }
                    
                    // If username exists, append timestamp for uniqueness
                    $username = $base_username;
                    if (username_exists($username)) {
                        $username = $base_username . '_' . time();
                        // If still exists (very unlikely), add random suffix
                        if (username_exists($username)) {
                            $username = $base_username . '_' . wp_generate_password(8, false);
                        }
                    }
                } else {
                    // Fallback username if email is invalid
                    $username = 'user_' . time();
                }
                
                // Validate password
                if (empty($password)) {
                    $errors[] = __('Password is required.', 'ielts-course-manager');
                } elseif (strlen($password) < 6) {
                    $errors[] = __('Password must be at least 6 characters.', 'ielts-course-manager');
                } elseif ($password !== $password_confirm) {
                    $errors[] = __('Passwords do not match.', 'ielts-course-manager');
                }
                
                // Validate membership type if provided
                if (get_option('ielts_cm_membership_enabled') && !empty($membership_type)) {
                    if (!in_array($membership_type, IELTS_CM_Membership::get_valid_membership_types())) {
                        $errors[] = __('Invalid membership type selected.', 'ielts-course-manager');
                    }
                    // Note: Paid memberships can now be selected during registration
                    // For paid memberships, users will be redirected to payment after registration
                }
                
                // Create user if no errors
                if (empty($errors)) {
                    $user_id = wp_create_user($username, $password, $email);
                    if (is_wp_error($user_id)) {
                        $errors[] = $user_id->get_error_message();
                    } else {
                        // Save user name fields using wp_update_user
                        wp_update_user(array(
                            'ID' => $user_id,
                            'first_name' => $first_name,
                            'last_name' => $last_name
                        ));
                        
                        // Set membership type if selected and membership system is enabled
                        $redirect_to_payment = false;
                        if (!empty($membership_type) && get_option('ielts_cm_membership_enabled')) {
                            // Validate membership type one more time before saving (defense in depth)
                            if (in_array($membership_type, IELTS_CM_Membership::get_valid_membership_types())) {
                                
                                if (IELTS_CM_Membership::is_trial_membership($membership_type)) {
                                    // Trial membership - activate immediately with expiry
                                    update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
                                    update_user_meta($user_id, '_ielts_cm_membership_status', IELTS_CM_Membership::STATUS_ACTIVE);
                                    
                                    // Clear expiry email tracking when activating new trial
                                    delete_user_meta($user_id, '_ielts_cm_expiry_email_sent');
                                    
                                    // Set expiry date based on membership duration settings
                                    $membership = new IELTS_CM_Membership();
                                    $expiry_date = $membership->calculate_expiry_date($membership_type);
                                    update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
                                    
                                    // Send enrollment email
                                    $membership->send_enrollment_email($user_id, $membership_type);
                                } else {
                                    // Paid membership - store selection and redirect to payment
                                    // These meta fields are used to track pending payments:
                                    // - _ielts_cm_membership_type_pending: stores the membership type user selected but hasn't paid for
                                    // - _ielts_cm_membership_payment_pending: flag indicating payment is pending
                                    // These should be cleared/processed by payment gateway webhook handlers
                                    update_user_meta($user_id, '_ielts_cm_membership_type_pending', $membership_type);
                                    update_user_meta($user_id, '_ielts_cm_membership_payment_pending', 1);
                                    $redirect_to_payment = true;
                                }
                            }
                        }
                        
                        // Auto login and do_action to ensure cookies are set
                        wp_set_current_user($user_id);
                        wp_set_auth_cookie($user_id, true);
                        $user_obj = get_userdata($user_id);
                        do_action('wp_login', $user_obj->user_login, $user_obj);
                        
                        // Redirect based on membership type
                        if ($redirect_to_payment) {
                            // Redirect to payment page for paid memberships
                            $payment_url = get_option('ielts_cm_full_member_page_url', home_url());
                            wp_safe_redirect(wp_validate_redirect($payment_url, home_url()));
                        } else {
                            // Regular redirect for trial memberships
                            // Validate redirect URL for security
                            $redirect_to = !empty($atts['redirect']) ? esc_url_raw($atts['redirect']) : home_url();
                            $redirect_to = wp_validate_redirect($redirect_to, home_url());
                            wp_safe_redirect($redirect_to);
                        }
                        exit;
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
                <form method="post" action="" class="ielts-form ielts-registration-form-grid">
                    <?php wp_nonce_field('ielts_register', 'ielts_register_nonce'); ?>
                    
                    <p class="form-field form-field-half">
                        <label for="ielts_first_name"><?php _e('First Name', 'ielts-course-manager'); ?> <span class="required">*</span></label>
                        <input type="text" name="ielts_first_name" id="ielts_first_name" required class="ielts-form-input"
                               value="<?php echo isset($_POST['ielts_first_name']) ? esc_attr($_POST['ielts_first_name']) : ''; ?>">
                    </p>
                    
                    <p class="form-field form-field-half">
                        <label for="ielts_last_name"><?php _e('Last Name', 'ielts-course-manager'); ?> <span class="required">*</span></label>
                        <input type="text" name="ielts_last_name" id="ielts_last_name" required class="ielts-form-input"
                               value="<?php echo isset($_POST['ielts_last_name']) ? esc_attr($_POST['ielts_last_name']) : ''; ?>">
                    </p>
                    
                    <p class="form-field form-field-full">
                        <label for="ielts_email"><?php _e('Email Address', 'ielts-course-manager'); ?> <span class="required">*</span></label>
                        <input type="email" name="ielts_email" id="ielts_email" required class="ielts-form-input"
                               value="<?php echo isset($_POST['ielts_email']) ? esc_attr($_POST['ielts_email']) : ''; ?>">
                        <small class="form-help"><?php _e('You will use this email to log in', 'ielts-course-manager'); ?></small>
                    </p>
                    
                    <p class="form-field form-field-half">
                        <label for="ielts_password"><?php _e('Password', 'ielts-course-manager'); ?> <span class="required">*</span></label>
                        <input type="password" name="ielts_password" id="ielts_password" required class="ielts-form-input">
                        <small class="form-help"><?php _e('Minimum 6 characters', 'ielts-course-manager'); ?></small>
                    </p>
                    
                    <p class="form-field form-field-half">
                        <label for="ielts_password_confirm"><?php _e('Confirm Password', 'ielts-course-manager'); ?> <span class="required">*</span></label>
                        <input type="password" name="ielts_password_confirm" id="ielts_password_confirm" required class="ielts-form-input">
                    </p>
                    
                    <?php if (get_option('ielts_cm_membership_enabled')): ?>
                        <p class="form-field form-field-full">
                            <label for="ielts_membership_type"><?php _e('Select Membership', 'ielts-course-manager'); ?> <span class="required">*</span></label>
                            <select name="ielts_membership_type" id="ielts_membership_type" required class="ielts-form-input">
                                <option value=""><?php _e('-- Select a membership option --', 'ielts-course-manager'); ?></option>
                                <?php 
                                $membership_levels = IELTS_CM_Membership::MEMBERSHIP_LEVELS;
                                $pricing = get_option('ielts_cm_membership_pricing', array());
                                $selected_membership = isset($_POST['ielts_membership_type']) ? $_POST['ielts_membership_type'] : '';
                                
                                // Group memberships by type
                                $trial_options = array();
                                $paid_options = array();
                                
                                foreach ($membership_levels as $key => $label) {
                                    $price = isset($pricing[$key]) ? floatval($pricing[$key]) : 0;
                                    $option_label = $label;
                                    
                                    if (IELTS_CM_Membership::is_trial_membership($key)) {
                                        $option_label .= ' (Free Trial)';
                                        $trial_options[$key] = $option_label;
                                    } else {
                                        if ($price > 0) {
                                            $option_label .= ' ($' . number_format($price, 2) . ')';
                                        }
                                        $paid_options[$key] = $option_label;
                                    }
                                }
                                
                                // Display trial options first
                                if (!empty($trial_options)):
                                ?>
                                    <optgroup label="<?php _e('Free Trial Options', 'ielts-course-manager'); ?>">
                                        <?php foreach ($trial_options as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($selected_membership, $key); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php 
                                endif;
                                
                                // Display paid options
                                if (!empty($paid_options)):
                                ?>
                                    <optgroup label="<?php _e('Full Membership (Payment Required)', 'ielts-course-manager'); ?>">
                                        <?php foreach ($paid_options as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($selected_membership, $key); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php 
                                endif;
                                ?>
                            </select>
                            <small class="form-help"><?php _e('Choose a free trial to get started immediately, or select a full membership (payment required after registration).', 'ielts-course-manager'); ?></small>
                        </p>
                        
                        <!-- Payment Section (Hidden by default, shown when paid membership selected) -->
                        <div id="ielts-payment-section" style="display: none;">
                            <p class="form-field form-field-full">
                                <label><?php _e('Payment Information', 'ielts-course-manager'); ?></label>
                                <div id="payment-element">
                                    <!-- Stripe Payment Element will be inserted here -->
                                </div>
                                <div id="payment-message" class="ielts-message" style="display: none; margin-top: 10px;"></div>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <p class="form-field form-field-full">
                        <button type="submit" name="ielts_register_submit" id="ielts_register_submit" class="ielts-button ielts-button-primary ielts-button-block">
                            <?php _e('Create Account', 'ielts-course-manager'); ?>
                        </button>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        
        <style>
        .ielts-registration-form {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ielts-registration-form .ielts-form {
            margin-top: 20px;
        }
        .ielts-registration-form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
        }
        @media (min-width: 768px) {
            .ielts-registration-form-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            .ielts-registration-form-grid .form-field-full {
                grid-column: 1 / -1;
            }
            .ielts-registration-form-grid .form-field-half {
                grid-column: span 1;
            }
        }
        .ielts-registration-form .form-field {
            margin-bottom: 20px;
        }
        .ielts-registration-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .ielts-registration-form .required {
            color: #dc3545;
        }
        .ielts-registration-form .ielts-form-input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 5px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .ielts-registration-form .ielts-form-input:focus {
            outline: none;
            border-color: #0073aa;
        }
        .ielts-registration-form .form-help {
            display: block;
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .ielts-registration-form .ielts-button-block {
            width: 100%;
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }
        .ielts-message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        .ielts-message.ielts-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .ielts-message.ielts-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .ielts-message ul {
            margin: 0;
            padding-left: 20px;
        }
        .ielts-message ul li {
            margin: 5px 0;
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
        $membership_status = get_user_meta($user->ID, '_ielts_cm_membership_status', true);
        
        // Get membership levels directly from constant
        $membership_levels = IELTS_CM_Membership::MEMBERSHIP_LEVELS;
        
        // Determine if this is a trial membership (or was a trial that expired)
        $is_trial = !empty($membership_type) && IELTS_CM_Membership::is_trial_membership($membership_type);
        
        // Check if membership is expired
        $is_expired = ($membership_status === 'expired') || 
                      (!empty($expiry_date) && strtotime($expiry_date) < time());
        
        // Get full member page URL
        $full_member_page_url = get_option('ielts_cm_full_member_page_url', '');
        
        // Handle profile update form submission
        $update_errors = array();
        $update_success = false;
        
        if (isset($_POST['ielts_update_profile'])) {
            if (!isset($_POST['ielts_update_profile_nonce']) || !wp_verify_nonce($_POST['ielts_update_profile_nonce'], 'ielts_update_profile')) {
                $update_errors[] = __('Security check failed.', 'ielts-course-manager');
            } else {
                $first_name = isset($_POST['ielts_first_name']) ? sanitize_text_field($_POST['ielts_first_name']) : '';
                $last_name = isset($_POST['ielts_last_name']) ? sanitize_text_field($_POST['ielts_last_name']) : '';
                $email = isset($_POST['ielts_email']) ? sanitize_email($_POST['ielts_email']) : '';
                $current_password = isset($_POST['ielts_current_password']) ? wp_unslash($_POST['ielts_current_password']) : '';
                $new_password = isset($_POST['ielts_new_password']) ? wp_unslash($_POST['ielts_new_password']) : '';
                $confirm_password = isset($_POST['ielts_confirm_password']) ? wp_unslash($_POST['ielts_confirm_password']) : '';
                
                // Validate email
                if (empty($email)) {
                    $update_errors[] = __('Email is required.', 'ielts-course-manager');
                } elseif (!is_email($email)) {
                    $update_errors[] = __('Invalid email address.', 'ielts-course-manager');
                } elseif ($email !== $user->user_email && email_exists($email)) {
                    $update_errors[] = __('Email already exists.', 'ielts-course-manager');
                }
                
                // Check if user wants to change password
                $wants_password_change = !empty($current_password) || !empty($new_password) || !empty($confirm_password);
                
                // If password change is requested, validate
                if ($wants_password_change) {
                    if (empty($current_password)) {
                        $update_errors[] = __('Current password is required to change your password.', 'ielts-course-manager');
                    } elseif (!wp_check_password($current_password, $user->user_pass, $user->ID)) {
                        $update_errors[] = __('Current password is incorrect.', 'ielts-course-manager');
                    } elseif (empty($new_password)) {
                        $update_errors[] = __('New password is required.', 'ielts-course-manager');
                    } elseif (strlen($new_password) < 6) {
                        $update_errors[] = __('New password must be at least 6 characters.', 'ielts-course-manager');
                    } elseif ($new_password !== $confirm_password) {
                        $update_errors[] = __('New passwords do not match.', 'ielts-course-manager');
                    }
                }
                
                // Update user if no errors
                if (empty($update_errors)) {
                    $user_data = array(
                        'ID' => $user->ID,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'user_email' => $email
                    );
                    
                    // Add password if changing (validated above)
                    if ($wants_password_change && !empty($new_password)) {
                        $user_data['user_pass'] = $new_password;
                    }
                    
                    $result = wp_update_user($user_data);
                    
                    if (is_wp_error($result)) {
                        $update_errors[] = $result->get_error_message();
                    } else {
                        $update_success = true;
                        // Refresh user object to show updated data
                        $user = wp_get_current_user();
                    }
                }
            }
        }
        
        ob_start();
        ?>
        <div class="ielts-account-page">
            <h2><?php _e('My Account', 'ielts-course-manager'); ?></h2>
            
            <!-- Tab Navigation -->
            <div class="ielts-account-tabs">
                <?php if (get_option('ielts_cm_membership_enabled')): ?>
                    <button class="ielts-tab-button active" data-tab="membership-info">
                        <?php _e('Membership Information', 'ielts-course-manager'); ?>
                    </button>
                <?php endif; ?>
                <button class="ielts-tab-button<?php echo !get_option('ielts_cm_membership_enabled') ? ' active' : ''; ?>" data-tab="personal-details">
                    <?php _e('Personal Details', 'ielts-course-manager'); ?>
                </button>
                <?php if (get_option('ielts_cm_membership_enabled')): ?>
                    <?php if ($is_trial): ?>
                        <button class="ielts-tab-button" data-tab="become-full-member">
                            <?php _e('Become a Full Member', 'ielts-course-manager'); ?>
                        </button>
                    <?php elseif (!empty($membership_type) && !$is_trial): ?>
                        <button class="ielts-tab-button" data-tab="extend-course">
                            <?php _e('Extend My Course', 'ielts-course-manager'); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Tab Content -->
            <?php if (get_option('ielts_cm_membership_enabled')): ?>
                <div class="ielts-tab-content active" id="membership-info">
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
                                <th><?php 
                                    if ($is_trial) {
                                        _e('Time Remaining:', 'ielts-course-manager');
                                    } else {
                                        _e('Expiry Date:', 'ielts-course-manager');
                                    }
                                ?></th>
                                <td>
                                    <?php 
                                    if (empty($expiry_date)) {
                                        echo __('Lifetime', 'ielts-course-manager');
                                    } else {
                                        $expiry_timestamp = strtotime($expiry_date);
                                        $is_expired = $expiry_timestamp < time();
                                        
                                        if ($is_trial) {
                                            // Show hours remaining for trial members
                                            if ($is_expired) {
                                                echo '<span class="ielts-expired">' . __('Expired', 'ielts-course-manager') . '</span>';
                                            } else {
                                                // Calculate hours remaining
                                                $seconds_remaining = max(0, $expiry_timestamp - time());
                                                $hours_remaining = ceil($seconds_remaining / 3600);
                                                $hours_text = sprintf(_n('%d hour', '%d hours', $hours_remaining, 'ielts-course-manager'), $hours_remaining);
                                                echo '<span class="ielts-active">' . esc_html($hours_text) . '</span>';
                                            }
                                        } else {
                                            // Show date for full members
                                            if ($is_expired) {
                                                echo '<span class="ielts-expired">' . date('F j, Y', $expiry_timestamp) . ' (' . __('Expired', 'ielts-course-manager') . ')</span>';
                                            } else {
                                                echo '<span class="ielts-active">' . date('F j, Y', $expiry_timestamp) . '</span>';
                                            }
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
            
            <!-- Personal Details Tab -->
            <div class="ielts-tab-content<?php echo !get_option('ielts_cm_membership_enabled') ? ' active' : ''; ?>" id="personal-details">
                <h3><?php _e('Personal Details', 'ielts-course-manager'); ?></h3>
                
                <?php if ($update_success): ?>
                    <div class="ielts-message ielts-success">
                        <p><?php _e('Your profile has been updated successfully!', 'ielts-course-manager'); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($update_errors)): ?>
                    <div class="ielts-message ielts-error">
                        <ul>
                            <?php foreach ($update_errors as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" class="ielts-profile-update-form">
                    <?php wp_nonce_field('ielts_update_profile', 'ielts_update_profile_nonce'); ?>
                    
                    <div class="ielts-form-grid">
                        <div class="ielts-form-group ielts-form-group-half">
                            <label for="ielts_first_name"><?php _e('First Name', 'ielts-course-manager'); ?></label>
                            <input type="text" name="ielts_first_name" id="ielts_first_name" 
                                   value="<?php echo esc_attr($user->first_name); ?>" 
                                   class="ielts-form-input">
                        </div>
                        
                        <div class="ielts-form-group ielts-form-group-half">
                            <label for="ielts_last_name"><?php _e('Last Name', 'ielts-course-manager'); ?></label>
                            <input type="text" name="ielts_last_name" id="ielts_last_name" 
                                   value="<?php echo esc_attr($user->last_name); ?>" 
                                   class="ielts-form-input">
                        </div>
                        
                        <div class="ielts-form-group ielts-form-group-full">
                            <label for="ielts_email"><?php _e('Email Address', 'ielts-course-manager'); ?> <span class="required">*</span></label>
                            <input type="email" name="ielts_email" id="ielts_email" required
                                   value="<?php echo esc_attr($user->user_email); ?>" 
                                   class="ielts-form-input">
                        </div>
                        
                        <div class="ielts-form-group ielts-form-group-full">
                            <label><?php _e('Username', 'ielts-course-manager'); ?></label>
                            <input type="text" value="<?php echo esc_attr($user->user_login); ?>" 
                                   class="ielts-form-input" disabled>
                            <small class="form-help"><?php _e('Username cannot be changed', 'ielts-course-manager'); ?></small>
                        </div>
                    </div>
                    
                    <h4><?php _e('Change Password (Optional)', 'ielts-course-manager'); ?></h4>
                    <p class="form-help"><?php _e('Leave blank to keep your current password', 'ielts-course-manager'); ?></p>
                    
                    <div class="ielts-form-grid">
                        <div class="ielts-form-group ielts-form-group-full">
                            <label for="ielts_current_password"><?php _e('Current Password', 'ielts-course-manager'); ?></label>
                            <input type="password" name="ielts_current_password" id="ielts_current_password" 
                                   class="ielts-form-input">
                        </div>
                        
                        <div class="ielts-form-group ielts-form-group-half">
                            <label for="ielts_new_password"><?php _e('New Password', 'ielts-course-manager'); ?></label>
                            <input type="password" name="ielts_new_password" id="ielts_new_password" 
                                   class="ielts-form-input">
                        </div>
                        
                        <div class="ielts-form-group ielts-form-group-half">
                            <label for="ielts_confirm_password"><?php _e('Confirm New Password', 'ielts-course-manager'); ?></label>
                            <input type="password" name="ielts_confirm_password" id="ielts_confirm_password" 
                                   class="ielts-form-input">
                        </div>
                    </div>
                    
                    <div class="ielts-form-actions">
                        <button type="submit" name="ielts_update_profile" class="ielts-button ielts-button-primary">
                            <?php _e('Update Profile', 'ielts-course-manager'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if (get_option('ielts_cm_membership_enabled')): ?>
                <?php if (!empty($membership_type)): ?>
                    <?php if ($is_trial): ?>
                        <!-- Become a Full Member Tab -->
                        <div class="ielts-tab-content" id="become-full-member">
                            <h3><?php _e('Become a Full Member', 'ielts-course-manager'); ?></h3>
                            <p><?php _e('Your trial membership gives you limited access to our courses. Upgrade to a full membership to get:', 'ielts-course-manager'); ?></p>
                            <ul>
                                <li><?php _e('Extended access time (30 days or more)', 'ielts-course-manager'); ?></li>
                                <li><?php _e('Full access to all course materials', 'ielts-course-manager'); ?></li>
                                <li><?php _e('Complete all exercises and tests', 'ielts-course-manager'); ?></li>
                                <li><?php _e('Track your progress and earn awards', 'ielts-course-manager'); ?></li>
                            </ul>
                            <?php if (!empty($full_member_page_url)): ?>
                                <p>
                                    <a href="<?php echo esc_url($full_member_page_url); ?>" class="button button-primary">
                                        <?php _e('Upgrade to Full Membership', 'ielts-course-manager'); ?>
                                    </a>
                                </p>
                            <?php else: ?>
                                <p><?php _e('Please contact us to upgrade your membership.', 'ielts-course-manager'); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Extend My Course Tab -->
                        <div class="ielts-tab-content" id="extend-course">
                            <h3><?php _e('Extend My Course', 'ielts-course-manager'); ?></h3>
                            <p>
                                <?php 
                                $membership_name = isset($membership_levels[$membership_type]) 
                                    ? $membership_levels[$membership_type] 
                                    : $membership_type;
                                printf(
                                    __('You are currently enrolled in: %s', 'ielts-course-manager'), 
                                    '<strong>' . esc_html($membership_name) . '</strong>'
                                );
                                ?>
                            </p>
                            <?php if (!empty($expiry_date)): ?>
                                <p>
                                    <?php 
                                    printf(
                                        __('Your membership will expire on: %s', 'ielts-course-manager'),
                                        '<strong>' . date('F j, Y', strtotime($expiry_date)) . '</strong>'
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            <p><?php _e('To extend your course access, please contact us or visit our membership page.', 'ielts-course-manager'); ?></p>
                            <?php if (!empty($full_member_page_url)): ?>
                                <p>
                                    <a href="<?php echo esc_url($full_member_page_url); ?>" class="button button-primary">
                                        <?php _e('Renew Membership', 'ielts-course-manager'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <style>
        .ielts-account-page {
            max-width: 900px;
        }
        .ielts-account-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            flex-wrap: wrap;
        }
        .ielts-tab-button {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-bottom: none;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            border-radius: 4px 4px 0 0;
            margin-bottom: -2px;
        }
        .ielts-tab-button:hover {
            background: #e9e9e9;
        }
        .ielts-tab-button.active {
            background: white;
            border-bottom: 2px solid white;
            color: #2271b1;
        }
        .ielts-tab-content {
            display: none;
            background: white;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 0 4px 4px 4px;
            min-height: 300px;
        }
        .ielts-tab-content.active {
            display: block;
        }
        .ielts-tab-content h3 {
            margin-top: 0;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
            color: #2271b1;
        }
        .ielts-tab-content ul {
            margin: 15px 0;
            padding-left: 25px;
        }
        .ielts-tab-content ul li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .ielts-account-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .ielts-account-table th {
            text-align: left;
            padding: 12px;
            width: 200px;
            font-weight: bold;
            background: #f5f5f5;
        }
        .ielts-account-table td {
            padding: 12px;
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
            font-weight: bold;
        }
        .ielts-active {
            color: #46b450;
            font-weight: bold;
        }
        .ielts-status-expired {
            background: #dc3232;
            color: white;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .ielts-status-active {
            background: #46b450;
            color: white;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .button-primary {
            background: #2271b1;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .button-primary:hover {
            background: #135e96;
            color: white;
        }
        /* Profile Update Form Styles */
        .ielts-profile-update-form {
            max-width: 800px;
        }
        .ielts-profile-update-form h4 {
            margin-top: 30px;
            margin-bottom: 10px;
            color: #333;
            font-size: 16px;
        }
        .ielts-form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        @media (min-width: 768px) {
            .ielts-form-grid {
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            .ielts-form-group-full {
                grid-column: 1 / -1;
            }
            .ielts-form-group-half {
                grid-column: span 1;
            }
        }
        .ielts-form-group {
            margin-bottom: 5px;
        }
        .ielts-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .ielts-form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .ielts-form-input:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .ielts-form-input:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        .ielts-form-actions {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .ielts-button {
            padding: 12px 30px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .ielts-button-primary {
            background: #2271b1;
            color: white;
        }
        .ielts-button-primary:hover {
            background: #135e96;
        }
        .form-help {
            display: block;
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .required {
            color: #dc3545;
        }
        .ielts-message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        .ielts-message.ielts-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .ielts-message.ielts-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .ielts-message ul {
            margin: 0;
            padding-left: 20px;
        }
        .ielts-message ul li {
            margin: 5px 0;
        }
        </style>
        
        <script>
        (function() {
            var tabs = document.querySelectorAll('.ielts-tab-button');
            var contents = document.querySelectorAll('.ielts-tab-content');
            
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var targetId = this.getAttribute('data-tab');
                    var targetElement = document.getElementById(targetId);
                    
                    // Only proceed if target element exists
                    if (!targetElement) {
                        return;
                    }
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    contents.forEach(function(c) { c.classList.remove('active'); });
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    targetElement.classList.add('active');
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display user's IELTS band scores in a table
     * Shows approximate band scores for Reading, Listening, Writing, Speaking based on quiz performance
     */
    public function display_band_scores($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your band scores.', 'ielts-course-manager') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'skills' => 'reading,listening,writing,speaking', // Which skills to show
            'title' => __('Your Estimated IELTS Band Scores', 'ielts-course-manager')
        ), $atts);
        
        $user_id = get_current_user_id();
        
        // Get skill scores using the gamification class
        $gamification = new IELTS_CM_Gamification();
        $skill_scores = $gamification->get_user_skill_scores($user_id);
        
        // Parse which skills to display
        $skills_to_show = array_map('trim', explode(',', $atts['skills']));
        
        // Convert percentage scores to band scores
        $band_scores = array();
        foreach ($skills_to_show as $skill) {
            $skill = strtolower($skill);
            if (isset($skill_scores[$skill])) {
                $percentage = $skill_scores[$skill];
                $band_scores[$skill] = $this->convert_percentage_to_band($percentage);
            }
        }
        
        ob_start();
        ?>
        <div class="ielts-band-scores-container">
            <?php if (!empty($atts['title'])): ?>
                <h3 class="band-scores-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <div class="band-scores-table-wrapper">
                <table class="ielts-band-scores-table">
                    <thead>
                        <tr>
                            <?php foreach ($skills_to_show as $skill): 
                                $skill = strtolower($skill);
                                $skill_label = ucfirst($skill);
                            ?>
                                <th><?php echo esc_html($skill_label); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($skills_to_show as $skill): 
                                $skill = strtolower($skill);
                                $band_score = isset($band_scores[$skill]) ? $band_scores[$skill] : 0;
                                $has_data = isset($skill_scores[$skill]) && $skill_scores[$skill] > 0;
                            ?>
                                <td class="band-score-cell <?php echo $has_data ? 'has-data' : 'no-data'; ?>">
                                    <span class="band-score-value">
                                        <?php 
                                        if ($has_data) {
                                            echo esc_html(number_format($band_score, 1));
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </span>
                                    <?php if ($has_data): ?>
                                        <span class="band-score-label"><?php _e('Band', 'ielts-course-manager'); ?></span>
                                    <?php else: ?>
                                        <span class="band-score-label no-data-label"><?php _e('No tests yet', 'ielts-course-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <p class="band-scores-note">
                <?php _e('Band scores are estimates based on your test performance. Complete more tests for more accurate results.', 'ielts-course-manager'); ?>
            </p>
        </div>
        
        <style>
        .ielts-band-scores-container {
            max-width: 100%;
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .band-scores-title {
            text-align: center;
            margin: 0 0 20px 0;
            font-size: 22px;
            color: #333;
        }
        
        .band-scores-table-wrapper {
            overflow-x: auto;
        }
        
        .ielts-band-scores-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .ielts-band-scores-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .ielts-band-scores-table td {
            padding: 25px 15px;
            text-align: center;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        .band-score-cell {
            position: relative;
        }
        
        .band-score-cell.has-data {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .band-score-value {
            display: block;
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .band-score-cell.no-data .band-score-value {
            font-size: 24px;
            color: #999;
        }
        
        .band-score-label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .no-data-label {
            font-size: 11px;
            color: #999;
        }
        
        .band-scores-note {
            text-align: center;
            font-size: 13px;
            color: #666;
            font-style: italic;
            margin: 10px 0 0 0;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .ielts-band-scores-table th,
            .ielts-band-scores-table td {
                padding: 12px 8px;
                font-size: 14px;
            }
            
            .band-score-value {
                font-size: 28px;
            }
            
            .band-scores-title {
                font-size: 18px;
            }
        }
        
        @media (max-width: 480px) {
            .ielts-band-scores-container {
                padding: 15px;
            }
            
            .ielts-band-scores-table th,
            .ielts-band-scores-table td {
                padding: 10px 5px;
                font-size: 12px;
            }
            
            .band-score-value {
                font-size: 24px;
            }
            
            .band-score-label {
                font-size: 10px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Convert percentage score to IELTS band score
     * Based on approximate IELTS scoring guidelines
     */
    private function convert_percentage_to_band($percentage) {
        // IELTS band score conversion based on percentage
        // This is an approximation based on typical IELTS score distributions
        if ($percentage >= 95) return 9.0;
        if ($percentage >= 90) return 8.5;
        if ($percentage >= 85) return 8.0;
        if ($percentage >= 80) return 7.5;
        if ($percentage >= 70) return 7.0;
        if ($percentage >= 65) return 6.5;
        if ($percentage >= 60) return 6.0;
        if ($percentage >= 55) return 5.5;
        if ($percentage >= 50) return 5.0;
        if ($percentage >= 45) return 4.5;
        if ($percentage >= 40) return 4.0;
        if ($percentage >= 35) return 3.5;
        if ($percentage >= 30) return 3.0;
        if ($percentage >= 25) return 2.5;
        if ($percentage >= 20) return 2.0;
        if ($percentage >= 15) return 1.5;
        if ($percentage >= 10) return 1.0;
        return 0.5;
    }
}
