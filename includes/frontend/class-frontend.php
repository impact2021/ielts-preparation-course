<?php
/**
 * Frontend functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Frontend {
    
    public function init() {
        // Add custom templates
        add_filter('template_include', array($this, 'load_custom_templates'));
        
        // Add body classes
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Record lesson access when viewed (not marking as complete automatically)
        add_action('wp', array($this, 'auto_mark_lesson_on_view'));
        
        // Auto-mark resources (sublessons) as complete when viewed
        add_action('wp', array($this, 'auto_mark_resource_on_view'));
        
        // Add feedback button to footer
        add_action('wp_footer', array($this, 'add_feedback_button'));
        
        // Add trial countdown widget to footer
        add_action('wp_footer', array($this, 'add_trial_countdown_widget'));
        
        // Enqueue frontend styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Hide admin bar for students (non-admins)
        add_action('after_setup_theme', array($this, 'hide_admin_bar_for_students'));
        
        // Allow email login
        add_filter('authenticate', array($this, 'authenticate_with_email'), 20, 3);
    }
    
    /**
     * Hide admin bar for non-admin users
     */
    public function hide_admin_bar_for_students() {
        if (!current_user_can('administrator') && !is_admin()) {
            show_admin_bar(false);
        }
    }
    
    /**
     * Allow users to login with email address
     */
    public function authenticate_with_email($user, $username, $password) {
        // If user already authenticated or no credentials provided, return early
        if ($user instanceof WP_User || empty($username) || empty($password)) {
            return $user;
        }
        
        // Check if username is an email - if so, get the user by email
        if (is_email($username)) {
            $user_obj = get_user_by('email', $username);
            if ($user_obj) {
                // Authenticate using the username instead of email
                // This ensures consistent timing regardless of whether email exists
                $user = wp_authenticate_username_password(null, $user_obj->user_login, $password);
            }
            // If email doesn't exist, still call wp_authenticate_username_password
            // to maintain consistent timing and avoid user enumeration
            else {
                // Use a random username to maintain timing consistency without revealing patterns
                $random_username = 'user_' . wp_generate_password(12, false, false);
                wp_authenticate_username_password(null, $random_username, $password);
            }
        }
        
        return $user;
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Register and enqueue styles for trial countdown widget
        wp_register_style('ielts-cm-countdown', false);
        wp_enqueue_style('ielts-cm-countdown');
        wp_add_inline_style('ielts-cm-countdown', $this->get_countdown_widget_styles());
    }
    
    /**
     * Get CSS styles for countdown widget
     */
    private function get_countdown_widget_styles() {
        return '
        .ielts-trial-countdown {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            max-width: 300px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .ielts-trial-countdown h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
            color: white;
        }
        
        .ielts-trial-countdown-time {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            text-align: center;
            color: white;
        }
        
        .ielts-trial-countdown-upgrade {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .ielts-trial-countdown-upgrade:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .ielts-trial-countdown-close {
            position: absolute;
            top: 5px;
            right: 10px;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
            padding: 0;
            line-height: 1;
        }
        
        .ielts-trial-countdown-close:hover {
            opacity: 1;
        }
        ';
    }
    
    /**
     * Add trial countdown widget to footer
     */
    public function add_trial_countdown_widget() {
        // Only show for logged-in users with trial memberships
        if (!is_user_logged_in()) {
            return;
        }
        
        // Check if membership system is enabled
        if (!get_option('ielts_cm_membership_enabled')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
        
        // Only show for trial memberships
        if (empty($membership_type) || !IELTS_CM_Membership::is_trial_membership($membership_type)) {
            return;
        }
        
        $expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);
        if (empty($expiry_date)) {
            return;
        }
        
        // Expiry date is stored in UTC, convert to timestamp
        $expiry_timestamp = strtotime($expiry_date . ' UTC');
        $now_utc = time(); // Current UTC timestamp
        
        // Don't show if already expired
        if ($expiry_timestamp <= $now_utc) {
            return;
        }
        
        $upgrade_url = get_option('ielts_cm_full_member_page_url', home_url());
        
        ?>
        <div class="ielts-trial-countdown" id="ielts-trial-countdown">
            <button class="ielts-trial-countdown-close" id="ielts-countdown-close-btn">&times;</button>
            <h4><?php _e('Free Trial', 'ielts-course-manager'); ?></h4>
            <div class="ielts-trial-countdown-time" id="ielts-countdown-timer"></div>
            <?php if ($upgrade_url): ?>
                <a href="<?php echo esc_url($upgrade_url); ?>" class="ielts-trial-countdown-upgrade">
                    <?php _e('Become a Full Member', 'ielts-course-manager'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <script>
        (function() {
            var expiryTimestamp = <?php echo absint($expiry_timestamp); ?>;
            var timerElement = document.getElementById('ielts-countdown-timer');
            var closeBtn = document.getElementById('ielts-countdown-close-btn');
            var countdownWidget = document.getElementById('ielts-trial-countdown');
            
            if (closeBtn && countdownWidget) {
                closeBtn.addEventListener('click', function() {
                    countdownWidget.style.display = 'none';
                });
            }
            
            function updateCountdown() {
                var now = Math.floor(Date.now() / 1000);
                var diff = expiryTimestamp - now;
                
                if (diff <= 0) {
                    timerElement.textContent = '<?php _e('Expired', 'ielts-course-manager'); ?>';
                    return;
                }
                
                var days = Math.floor(diff / 86400);
                var hours = Math.floor((diff % 86400) / 3600);
                var minutes = Math.floor((diff % 3600) / 60);
                var seconds = diff % 60;
                
                var parts = [];
                if (days > 0) parts.push(days + 'd');
                if (hours > 0 || days > 0) parts.push(hours + 'h');
                if (minutes > 0 || hours > 0 || days > 0) parts.push(minutes + 'm');
                parts.push(seconds + 's');
                
                timerElement.textContent = parts.join(' ');
            }
            
            updateCountdown();
            setInterval(updateCountdown, 1000);
        })();
        </script>
        <?php
    }
    
    /**
     * Load custom templates
     */
    public function load_custom_templates($template) {
        if (is_singular('ielts_course')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-course-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_singular('ielts_lesson')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-lesson-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_singular('ielts_resource')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-resource-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_singular('ielts_quiz')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/single-quiz-page.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('ielts_course')) {
            $custom_template = IELTS_CM_PLUGIN_DIR . 'templates/archive-courses.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Add body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('ielts_course')) {
            $classes[] = 'ielts-course-single';
        }
        
        if (is_singular('ielts_lesson')) {
            $classes[] = 'ielts-lesson-single';
        }
        
        if (is_singular('ielts_resource')) {
            $classes[] = 'ielts-resource-single';
        }
        
        if (is_singular('ielts_quiz')) {
            $classes[] = 'ielts-quiz-single';
        }
        
        if (is_post_type_archive('ielts_course')) {
            $classes[] = 'ielts-course-archive';
        }
        
        return $classes;
    }
    
    /**
     * Record lesson access when user views it (but don't mark as complete)
     * Lessons are only marked as complete when ALL resources are viewed and ALL quizzes are attempted
     * This runs on every page load, but only acts on lesson pages
     */
    public function auto_mark_lesson_on_view() {
        // Only process for lesson pages
        if (!is_singular('ielts_lesson')) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        $lesson_id = get_the_ID();
        $course_id = get_post_meta($lesson_id, '_ielts_cm_course_id', true);
        
        if (!$course_id) {
            return;
        }
        
        // Check if user is enrolled
        $enrollment = new IELTS_CM_Enrollment();
        if (!$enrollment->is_enrolled($user_id, $course_id)) {
            return;
        }
        
        // Record lesson access (but don't mark as complete - that happens automatically when all requirements are met)
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, null, false);
    }
    
    /**
     * Auto-mark resource (sublesson) as complete when user views it
     * This runs on every page load, but only acts on resource pages
     */
    public function auto_mark_resource_on_view() {
        // Only process for resource pages
        if (!is_singular('ielts_resource')) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        $resource_id = get_the_ID();
        $lesson_id = get_post_meta($resource_id, '_ielts_cm_lesson_id', true);
        
        if (!$lesson_id) {
            return;
        }
        
        $course_id = get_post_meta($lesson_id, '_ielts_cm_course_id', true);
        
        if (!$course_id) {
            return;
        }
        
        // Check if user is enrolled
        $enrollment = new IELTS_CM_Enrollment();
        if (!$enrollment->is_enrolled($user_id, $course_id)) {
            return;
        }
        
        // Mark the resource as complete
        $progress_tracker = new IELTS_CM_Progress_Tracker();
        $progress_tracker->record_progress($user_id, $course_id, $lesson_id, $resource_id, true);
    }
    
    /**
     * Add feedback button to footer on course/lesson/resource/quiz pages
     */
    public function add_feedback_button() {
        // Only show on IELTS plugin pages or LearnDash pages
        if (!function_exists('is_singular')) {
            return;
        }
        
        $show_button = is_singular('ielts_course') || 
                      is_singular('ielts_lesson') || 
                      is_singular('ielts_resource') || 
                      is_singular('ielts_quiz') ||
                      is_singular('sfwd-courses') || 
                      is_singular('sfwd-lessons') || 
                      is_singular('sfwd-topic');
        
        if (!$show_button) {
            return;
        }
        
        // Only show to logged in users
        if (!is_user_logged_in()) {
            return;
        }
        
        // Get current user info
        $current_user = wp_get_current_user();
        $user_name = esc_html($current_user->display_name);
        $user_email = esc_html($current_user->user_email);
        
        // Start output buffering
        ob_start();
        ?>
        
        <!-- Feedback Button -->
        <button id="impact-report-issue-btn">Found a mistake on this page?</button>

        <!-- Modal -->
        <div id="impact-report-issue-modal">
            <div class="impact-report-issue-content">
                <span id="impact-close-modal">&times;</span>
                <div id="impact-form-container">
                    <?php echo do_shortcode('[contact-form-7 id="930fa24" title="Report an issue"]'); ?>
                </div>
                <input type="hidden" id="impact-page-title" value="<?php echo esc_attr(get_the_title()); ?>">
                <input type="hidden" id="impact-page-url" value="<?php echo esc_url(get_permalink()); ?>">
                <input type="hidden" id="impact-user-name" value="<?php echo $user_name; ?>">
                <input type="hidden" id="impact-user-email" value="<?php echo $user_email; ?>">
            </div>
        </div>

        <style>
        /* Button styling */
        #impact-report-issue-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0073e6;
            color: #fff;
            border: none;
            padding: 12px 18px;
            border-radius: 6px;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
            transition: background 0.2s ease, transform 0.2s ease;
        }
        #impact-report-issue-btn:hover { 
            background: #005bb5;
            transform: translateY(-2px);
        }
        #impact-report-issue-btn.minimized {
            width: 50px;
            height: 50px;
            padding: 0;
            border-radius: 50%;
            font-size: 0;
            background: #0073e6 url('data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22white%22%3E%3Cpath d=%22M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z%22/%3E%3C/svg%3E') center center no-repeat;
            background-size: 60%;
        }

        /* Modal styling */
        #impact-report-issue-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .impact-report-issue-content {
            background: #fff;
            width: 90%;
            max-width: 500px;
            margin: 80px auto;
            padding: 20px;
            border-radius: 8px;
            position: relative;
        }
        #impact-close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            cursor: pointer;
            font-size: 22px;
        }

        /* Fix textarea and inputs inside modal */
        #impact-report-issue-modal textarea,
        #impact-report-issue-modal input {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 8px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }
        #impact-report-issue-modal textarea {
            min-height: 120px;
            resize: vertical;
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('impact-report-issue-btn');
            const modal = document.getElementById('impact-report-issue-modal');
            const close = document.getElementById('impact-close-modal');
            const title = document.getElementById('impact-page-title').value;
            const url = document.getElementById('impact-page-url').value;
            const user = document.getElementById('impact-user-name').value;
            const email = document.getElementById('impact-user-email').value;
            const formContainer = document.getElementById('impact-form-container');
            
            // Constant for localStorage key
            const FEEDBACK_EXPANDED_KEY = 'impactFeedbackExpanded';
            
            // Check for expanded state in localStorage (default is minimized)
            const isExpanded = localStorage.getItem(FEEDBACK_EXPANDED_KEY) === 'true';
            if (!isExpanded) {
                btn.classList.add('minimized');
            }

            btn.addEventListener('click', () => {
                // If minimized, restore it
                if (btn.classList.contains('minimized')) {
                    btn.classList.remove('minimized');
                    localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'true');
                    return;
                }
                
                // Otherwise, open modal
                modal.style.display = 'block';

                // Auto-fill Contact Form 7 hidden fields
                const titleField = document.querySelector('[name="page-title"]');
                const urlField = document.querySelector('[name="page-url"]');
                const userField = document.querySelector('[name="user-name"]');
                const emailField = document.querySelector('[name="user-email"]');
                if (titleField) titleField.value = title;
                if (urlField) urlField.value = url;
                if (userField) userField.value = user;
                if (emailField) emailField.value = email;

                // Reset form container in case user sent previously
                if (formContainer) {
                    formContainer.style.display = 'block';
                    formContainer.innerHTML = formContainer.querySelector('form')?.outerHTML || formContainer.innerHTML;
                }
            });

            close.addEventListener('click', () => {
                modal.style.display = 'none';
                // Minimize the button instead of closing it
                btn.classList.add('minimized');
                localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
            });
            
            window.addEventListener('click', e => { 
                if (e.target === modal) {
                    modal.style.display = 'none';
                    // Minimize the button instead of closing it
                    btn.classList.add('minimized');
                    localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
                }
            });

            // Contact Form 7 successful submission
            document.addEventListener('wpcf7mailsent', function(event) {
                if (!formContainer) return;
                formContainer.innerHTML = '<p style="font-size:16px; font-weight:bold;">✅ Thanks for letting us know!</p>';
            }, false);
        });
        </script>

        <?php
        echo ob_get_clean();
    }
}
