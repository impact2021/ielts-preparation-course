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
        
        // Add trial popup for non-logged-in users
        add_action('wp_footer', array($this, 'add_trial_popup'));
        
        // Enqueue frontend styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Hide admin bar for students (non-admins)
        add_action('after_setup_theme', array($this, 'hide_admin_bar_for_students'));
        
        // Allow email login
        add_filter('authenticate', array($this, 'authenticate_with_email'), 20, 3);
        
        // Register endpoint for issue reporting mechanism
        add_action('wp_ajax_process_issue_notification', array($this, 'handle_error_report_submission'));
        add_action('wp_ajax_nopriv_process_issue_notification', array($this, 'handle_error_report_submission'));
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
                // Use a completely random username to maintain timing consistency
                $random_username = wp_generate_password(16, false, false);
                wp_authenticate_username_password(null, $random_username, $password);
            }
        }
        
        return $user;
    }
    
    /**
     * Get custom login URL from settings
     * Falls back to /membership-login/ if not set
     * 
     * @param string $redirect Optional redirect URL after login
     * @return string Login URL
     */
    public static function get_custom_login_url($redirect = '') {
        // Get custom login URL from settings, default to /membership-login/
        $login_url = get_option('iw_login_page_url', '');
        
        // If no custom URL is set, use /membership-login/ as default
        if (empty($login_url)) {
            $login_url = home_url('/membership-login/');
        }
        
        // Add redirect parameter if provided
        if (!empty($redirect)) {
            // add_query_arg already handles URL encoding, no need to urlencode
            $login_url = add_query_arg('redirect_to', $redirect, $login_url);
        }
        
        return $login_url;
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
        
        .ielts-trial-countdown.countdown-warning {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            animation: flash-warning 1s ease-in-out infinite;
        }
        
        @keyframes flash-warning {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
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
            var upgradeUrl = <?php echo json_encode($upgrade_url); ?>;
            var timerElement = document.getElementById('ielts-countdown-timer');
            var closeBtn = document.getElementById('ielts-countdown-close-btn');
            var countdownWidget = document.getElementById('ielts-trial-countdown');
            var countdownInterval;
            
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
                    // Clear the interval to stop updates
                    clearInterval(countdownInterval);
                    // Redirect to the full member page
                    setTimeout(function() {
                        window.location.href = upgradeUrl;
                    }, 1000);
                    return;
                }
                
                // Add warning class for last 2 minutes (120 seconds)
                if (diff <= 120) {
                    countdownWidget.classList.add('countdown-warning');
                } else {
                    countdownWidget.classList.remove('countdown-warning');
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
            countdownInterval = setInterval(updateCountdown, 1000);
        })();
        </script>
        <?php
    }
    
    /**
     * Add trial popup for non-logged-in users
     */
    public function add_trial_popup() {
        // Only show for non-logged-in users
        if (is_user_logged_in()) {
            return;
        }
        
        // Only show if membership system is enabled
        if (!get_option('ielts_cm_membership_enabled', false)) {
            return;
        }
        
        // Never show popup on the membership-register page
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $current_url = home_url($request_uri);
        if (strpos($current_url, '/membership-register') !== false) {
            return;
        }
        
        // Get registration page URL
        // First try custom registration page, then fall back to WordPress default
        $registration_url = get_option('ielts_cm_registration_page_url');
        if (empty($registration_url)) {
            // Check if we have a cached registration URL
            $registration_url = get_transient('ielts_cm_cached_registration_url');
            
            if (false === $registration_url) {
                // Try to find a page with the registration shortcode
                $pages = get_posts(array(
                    'post_type' => 'page',
                    'posts_per_page' => 1,
                    's' => '[ielts_registration]',
                ));
                
                if (!empty($pages)) {
                    $registration_url = get_permalink($pages[0]->ID);
                } else {
                    // Fall back to WordPress default registration if enabled
                    if (get_option('users_can_register')) {
                        $registration_url = wp_registration_url();
                    } else {
                        // If registration is disabled, use home URL as fallback
                        $registration_url = home_url();
                    }
                }
                
                // Cache the result for 24 hours to improve performance
                set_transient('ielts_cm_cached_registration_url', $registration_url, DAY_IN_SECONDS);
            }
        }
        
        ?>
        <div id="ielts-trial-popup" class="ielts-trial-popup" role="dialog" aria-labelledby="ielts-trial-popup-title">
            <div class="ielts-trial-popup-content">
                <button class="ielts-trial-popup-close" id="ielts-trial-popup-close" aria-label="<?php esc_attr_e('Minimize', 'ielts-course-manager'); ?>">−</button>
                <h2 id="ielts-trial-popup-title"><?php _e('Start Your Free 2-Hour Trial!', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Get instant access to our complete IELTS preparation course. No credit card required!', 'ielts-course-manager'); ?></p>
                <a href="<?php echo esc_url($registration_url); ?>" class="ielts-trial-popup-button">
                    <?php _e('Start Free Trial', 'ielts-course-manager'); ?>
                </a>
            </div>
            <div class="ielts-trial-popup-minimized" id="ielts-trial-popup-minimized" role="button" tabindex="0" aria-label="<?php esc_attr_e('Expand free trial information', 'ielts-course-manager'); ?>">
                <span class="minimized-icon">🎓</span>
                <span class="minimized-text"><?php _e('Free Trial', 'ielts-course-manager'); ?></span>
            </div>
        </div>
        
        <style>
        .ielts-trial-popup {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in;
            transition: background-color 0.3s ease;
        }
        
        /* Minimized state - no overlay */
        .ielts-trial-popup.minimized {
            background-color: transparent;
            pointer-events: none;
        }
        
        .ielts-trial-popup.minimized .ielts-trial-popup-content {
            display: none;
        }
        
        .ielts-trial-popup.minimized .ielts-trial-popup-minimized {
            display: flex;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .ielts-trial-popup-content {
            background: #fff;
            margin: 10% auto;
            padding: 30px 40px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease-out;
            text-align: center;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Minimized button at bottom right */
        .ielts-trial-popup-minimized {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            pointer-events: auto;
            transition: all 0.3s;
            animation: slideInFromBottom 0.5s ease-out;
        }
        
        @keyframes slideInFromBottom {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .ielts-trial-popup-minimized:hover,
        .ielts-trial-popup-minimized:focus {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
        
        .minimized-icon {
            font-size: 18px;
        }
        
        .minimized-text {
            font-size: 14px;
        }
        
        .ielts-trial-popup-content h2 {
            margin: 0 0 15px 0;
            font-size: 28px;
            color: #333;
            font-weight: 600;
        }
        
        .ielts-trial-popup-content p {
            margin: 0 0 25px 0;
            font-size: 16px;
            color: #666;
            line-height: 1.5;
        }
        
        .ielts-trial-popup-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 32px;
            color: #999;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }
        
        .ielts-trial-popup-close:hover {
            color: #333;
        }
        
        .ielts-trial-popup-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .ielts-trial-popup-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            color: white;
        }
        </style>
        
        <script>
        (function() {
            var popup = document.getElementById('ielts-trial-popup');
            var closeBtn = document.getElementById('ielts-trial-popup-close');
            var minimizedBtn = document.getElementById('ielts-trial-popup-minimized');
            var POPUP_STORAGE_KEY = 'ielts_trial_popup_last_closed';
            var POPUP_INTERVAL = 5 * 60 * 1000; // 5 minutes in milliseconds
            var isMinimized = false;
            
            function showPopup() {
                if (popup) {
                    popup.style.display = 'block';
                    popup.classList.remove('minimized');
                    popup.setAttribute('aria-modal', 'true');
                    isMinimized = false;
                    // Focus on close button for accessibility
                    if (closeBtn) {
                        closeBtn.focus();
                    }
                }
            }
            
            function minimizePopup() {
                if (popup) {
                    popup.classList.add('minimized');
                    popup.removeAttribute('aria-modal');
                    isMinimized = true;
                    // Return focus to minimized badge for keyboard navigation
                    if (minimizedBtn) {
                        minimizedBtn.focus();
                    }
                    // Store the current timestamp when popup is minimized
                    try {
                        localStorage.setItem(POPUP_STORAGE_KEY, Date.now().toString());
                    } catch (e) {
                        // localStorage may be disabled or full - silently fail
                    }
                }
            }
            
            function hidePopup() {
                if (popup) {
                    popup.style.display = 'none';
                    popup.classList.remove('minimized');
                    popup.removeAttribute('aria-modal');
                    isMinimized = false;
                }
            }
            
            function shouldShowPopup() {
                try {
                    var lastClosed = localStorage.getItem(POPUP_STORAGE_KEY);
                    
                    // If never closed before, show it
                    if (!lastClosed) {
                        return true;
                    }
                    
                    // Check if 5 minutes have passed since last close
                    var timeSinceClose = Date.now() - parseInt(lastClosed, 10);
                    return timeSinceClose >= POPUP_INTERVAL;
                } catch (e) {
                    // If localStorage is not available, always show popup
                    return true;
                }
            }
            
            // Minimize popup when close button is clicked
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    minimizePopup();
                });
            }
            
            // Expand popup when minimized button is clicked or activated with keyboard
            if (minimizedBtn) {
                minimizedBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showPopup();
                });
                
                // Add keyboard support for Enter and Space keys
                minimizedBtn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        showPopup();
                    }
                });
            }
            
            // Minimize popup when clicking outside the content
            if (popup) {
                popup.addEventListener('click', function(e) {
                    if (e.target === popup && !isMinimized) {
                        minimizePopup();
                    }
                });
            }
            
            // Minimize popup when Escape key is pressed
            function handleEscapeKey(e) {
                if (e.key === 'Escape' && popup && popup.style.display === 'block' && !isMinimized) {
                    minimizePopup();
                }
            }
            document.addEventListener('keydown', handleEscapeKey);
            
            // Check if we should show the popup on page load
            if (shouldShowPopup()) {
                // Show popup after a short delay for better UX
                setTimeout(showPopup, 2000);
            } else {
                // If not showing full popup, show minimized version immediately
                popup.style.display = 'block';
                minimizePopup();
            }
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
        $user_first_name = esc_html(get_user_meta($current_user->ID, 'first_name', true));
        $user_last_name = esc_html(get_user_meta($current_user->ID, 'last_name', true));
        
        // Start output buffering
        ob_start();
        ?>
        
        <!-- Feedback Button -->
        <button id="impact-report-issue-btn" data-full-text="Found a mistake on this page?" data-min-text="?" aria-label="Found a mistake on this page?">?</button>

        <!-- Modal -->
        <div id="impact-report-issue-modal" role="dialog" aria-modal="true" aria-labelledby="impact-modal-title">
            <div class="impact-report-issue-content">
                <h2 id="impact-modal-title" style="margin: 0 0 15px 0; font-size: 18px;">Report an Issue</h2>
                <span id="impact-close-modal" aria-label="Close">&times;</span>
                <div id="impact-form-container">
                    <form id="impact-error-report-form" class="ielts-issue-reporter">
                        <?php wp_nonce_field('ielts_report_token', 'security_token'); ?>
                        <input type="hidden" class="ctx-page-heading" value="">
                        <input type="hidden" class="ctx-page-location" value="">
                        <input type="hidden" class="ctx-reporter-login" value="">
                        <input type="hidden" class="ctx-reporter-contact" value="">
                        <input type="hidden" class="ctx-reporter-given-name" value="">
                        <input type="hidden" class="ctx-reporter-family-name" value="">
                        
                        <p style="margin: 0 0 12px;">
                            <label style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: #333;">
                                What's the problem?
                            </label>
                            <textarea class="concern-details" rows="7" 
                                style="width: 100%; box-sizing: border-box; padding: 10px; border: 2px solid #e0e0e0; 
                                border-radius: 6px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                                font-size: 14px; resize: vertical;" 
                                placeholder="Please describe the issue you encountered..." 
                                required></textarea>
                        </p>
                        
                        <p style="margin: 15px 0 0;">
                            <button type="submit" class="submit-concern-btn"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                color: #fff; padding: 12px 28px; border: 0; border-radius: 6px; 
                                cursor: pointer; font-size: 15px; font-weight: 600; 
                                box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
                                transition: transform 0.2s, box-shadow 0.2s;">
                                Submit Report
                            </button>
                        </p>
                    </form>
                </div>
                <input type="hidden" id="impact-page-title" value="<?php echo esc_attr(get_the_title()); ?>">
                <input type="hidden" id="impact-page-url" value="<?php echo esc_url(get_permalink()); ?>">
                <input type="hidden" id="impact-user-name" value="<?php echo $user_name; ?>">
                <input type="hidden" id="impact-user-email" value="<?php echo $user_email; ?>">
                <input type="hidden" id="impact-user-first-name" value="<?php echo $user_first_name; ?>">
                <input type="hidden" id="impact-user-last-name" value="<?php echo $user_last_name; ?>">
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
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
        }
        #impact-report-issue-btn:hover { 
            background: #005bb5;
            transform: translateY(-2px);
        }
        #impact-report-issue-btn.minimized {
            /* Minimized state - just show icon/question mark */
            width: 48px;
            height: 48px;
            padding: 0;
            border-radius: 50%;
            font-size: 24px;
            line-height: 48px;
            text-align: center;
            background: #0073e6;
            box-shadow: 0 3px 8px rgba(0,0,0,0.4);
        }
        #impact-report-issue-btn.minimized:hover {
            background: #005bb5;
        }
        
        /* Respect reduced motion preferences */
        @media (prefers-reduced-motion: no-preference) {
            #impact-report-issue-btn.minimized:hover {
                transform: scale(1.1);
            }
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
            const firstName = document.getElementById('impact-user-first-name').value;
            const lastName = document.getElementById('impact-user-last-name').value;
            const formContainer = document.getElementById('impact-form-container');
            
            // Get button text from data attributes
            const fullText = btn.getAttribute('data-full-text');
            const minText = btn.getAttribute('data-min-text');
            
            // Constant for localStorage key
            const FEEDBACK_EXPANDED_KEY = 'impactFeedbackExpanded';
            
            // Function to update button appearance
            function updateButtonState(minimized) {
                if (minimized) {
                    btn.classList.add('minimized');
                    btn.textContent = minText;
                    btn.setAttribute('title', fullText); // Add tooltip
                } else {
                    btn.classList.remove('minimized');
                    btn.textContent = fullText;
                    btn.removeAttribute('title');
                }
            }
            
            // Check for expanded state in localStorage (default is minimized)
            const isExpanded = localStorage.getItem(FEEDBACK_EXPANDED_KEY) === 'true';
            // Always start minimized unless explicitly expanded
            updateButtonState(!isExpanded);

            btn.addEventListener('click', () => {
                // Expand button when clicked
                updateButtonState(false);
                localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'true');
                
                // Open modal
                modal.style.display = 'block';

                // Populate context fields
                const contextFields = {
                    '.ctx-page-heading': title,
                    '.ctx-page-location': url,
                    '.ctx-reporter-login': user,
                    '.ctx-reporter-contact': email,
                    '.ctx-reporter-given-name': firstName,
                    '.ctx-reporter-family-name': lastName
                };
                
                Object.entries(contextFields).forEach(([selector, val]) => {
                    const elem = document.querySelector(selector);
                    if (elem) elem.value = val;
                });

                // Clear previous submission state
                if (formContainer) {
                    formContainer.style.display = 'block';
                    const txtArea = document.querySelector('.concern-details');
                    if (txtArea) txtArea.value = '';
                }
            });

            close.addEventListener('click', () => {
                modal.style.display = 'none';
                // Minimize the button
                updateButtonState(true);
                localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
            });
            
            window.addEventListener('click', e => { 
                if (e.target === modal) {
                    modal.style.display = 'none';
                    // Minimize the button
                    updateButtonState(true);
                    localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
                }
            });

            // Handle form submission via fetch API
            const reporterForm = document.getElementById('impact-error-report-form');
            if (reporterForm) {
                reporterForm.addEventListener('submit', async (evt) => {
                    evt.preventDefault();
                    
                    const submitBtn = reporterForm.querySelector('.submit-concern-btn');
                    const originalBtnText = submitBtn ? submitBtn.textContent : '';
                    
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Sending...';
                    }
                    
                    const payload = new FormData();
                    payload.append('action', 'process_issue_notification');
                    payload.append('security_token', reporterForm.querySelector('[name="security_token"]')?.value || '');
                    payload.append('message', reporterForm.querySelector('.concern-details')?.value || '');
                    payload.append('page_title', reporterForm.querySelector('.ctx-page-heading')?.value || '');
                    payload.append('page_url', reporterForm.querySelector('.ctx-page-location')?.value || '');
                    payload.append('user_name', reporterForm.querySelector('.ctx-reporter-login')?.value || '');
                    payload.append('user_email', reporterForm.querySelector('.ctx-reporter-contact')?.value || '');
                    payload.append('first_name', reporterForm.querySelector('.ctx-reporter-given-name')?.value || '');
                    payload.append('last_name', reporterForm.querySelector('.ctx-reporter-family-name')?.value || '');
                    
                    try {
                        const response = await fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', {
                            method: 'POST',
                            body: payload
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            if (formContainer) {
                                formContainer.innerHTML = `
                                    <div role="alert" aria-live="assertive" 
                                        style="padding: 24px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); 
                                        color: white; border-radius: 8px; text-align: center; 
                                        font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(17, 153, 142, 0.25);">
                                        <span style="font-size: 28px; display: block; margin-bottom: 8px;">✓</span>
                                        Report received! Thank you for your feedback.
                                    </div>
                                `;
                            }
                            
                            setTimeout(() => {
                                modal.style.display = 'none';
                                updateButtonState(true);
                                localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
                            }, 2000);
                        } else {
                            alert('Unable to submit report. Please try again.');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = originalBtnText;
                            }
                        }
                    } catch (err) {
                        alert('Network error. Please check your connection and retry.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        }
                    }
                });
            }
        });
        </script>

        <?php
        echo ob_get_clean();
    }
    
    /**
     * Process issue report submissions
     */
    public function handle_error_report_submission() {
        $token_field = isset($_POST['security_token']) ? sanitize_text_field($_POST['security_token']) : '';
        
        if (!wp_verify_nonce($token_field, 'ielts_report_token')) {
            wp_send_json_error(array('reason' => 'Invalid security token'));
            return;
        }
        
        $concern_text = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $heading_text = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '';
        $location_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';
        $reporter_login = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : '';
        $reporter_contact = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
        $given_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $family_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        
        if (empty($concern_text)) {
            wp_send_json_error(array('reason' => 'Message cannot be empty'));
            return;
        }
        
        $recipient = get_option('admin_email');
        $subject_line = 'Issue Report: ' . $heading_text;
        
        $email_body = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        $email_body .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px;">';
        $email_body .= '<h2 style="color: #555; border-bottom: 2px solid #667eea; padding-bottom: 10px;">New Issue Report</h2>';
        
        $email_body .= '<div style="margin: 20px 0; padding: 15px; background: white; border-radius: 6px;">';
        $email_body .= '<p style="margin: 8px 0;"><strong style="color: #667eea;">From: </strong>' . esc_html($reporter_login) . ' &lt;' . esc_html($reporter_contact) . '&gt;</p>';
        $email_body .= '<p style="margin: 8px 0;"><strong style="color: #667eea;">Name: </strong>' . esc_html($given_name) . ' ' . esc_html($family_name) . '</p>';
        $email_body .= '<p style="margin: 8px 0;"><strong style="color: #667eea;">Reported issue on: </strong>' . esc_html($heading_text) . '</p>';
        $email_body .= '<p style="margin: 8px 0;"><strong style="color: #667eea;">Message Body:</strong></p>';
        $email_body .= '<div style="padding: 12px; background: #fafafa; border-left: 4px solid #667eea; margin: 10px 0;">' . nl2br(esc_html($concern_text)) . '</div>';
        $email_body .= '<p style="margin: 8px 0;"><strong style="color: #667eea;">Page URL: </strong><a href="' . esc_url($location_url) . '" style="color: #764ba2; text-decoration: none;">' . esc_html($location_url) . '</a></p>';
        $email_body .= '</div>';
        
        $email_body .= '</div></body></html>';
        
        $from_email = 'noreply@' . wp_parse_url(home_url(), PHP_URL_HOST);
        $mail_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $from_email . '>'
        );
        
        $mail_sent = wp_mail($recipient, $subject_line, $email_body, $mail_headers);
        
        if ($mail_sent) {
            wp_send_json_success(array('status' => 'delivered'));
        } else {
            wp_send_json_error(array('reason' => 'Email delivery failed'));
        }
    }
}
