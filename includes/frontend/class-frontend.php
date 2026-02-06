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
        
        // AJAX handler for error report submission
        add_action('wp_ajax_ielts_cm_submit_error_report', array($this, 'handle_error_report_submission'));
        
        // User tour completion handler
        add_action('wp_ajax_ielts_complete_tour', array($this, 'handle_tour_completion'));
        
        // Register tour settings (admin only)
        if (is_admin()) {
            add_action('admin_init', array($this, 'register_tour_settings'));
            add_action('admin_menu', array($this, 'add_tour_admin_menu'));
        }
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
        
        // User tour for first-time users
        if (is_user_logged_in()) {
            // Check if tours are enabled globally
            $tours_enabled = get_option('ielts_cm_tour_enabled', true);
            
            if ($tours_enabled) {
                $user_id = get_current_user_id();
                
                // Get user's membership type
                $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
                
                // Determine tour type based on membership
                $tour_type = '';
                if (strpos($membership_type, 'academic') !== false) {
                    $tour_type = 'academic';
                } elseif (strpos($membership_type, 'general') !== false) {
                    $tour_type = 'general';
                } elseif (strpos($membership_type, 'english') !== false) {
                    $tour_type = 'english';
                }
                
                // Only load tour if user has a valid membership type and hasn't completed it
                if (!empty($tour_type)) {
                    // Check if this specific tour type is enabled
                    $tour_type_enabled = get_option('ielts_cm_tour_enabled_' . $tour_type, true);
                    
                    if ($tour_type_enabled) {
                        // Check if user has completed tour for their membership type
                        $tour_completed = get_user_meta($user_id, 'ielts_tour_completed_' . $tour_type, true);
                        
                        if (!$tour_completed) {
                            // Enqueue Shepherd.js library from CDN
                            wp_enqueue_style(
                                'shepherd-theme',
                                'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css',
                                array(),
                                '11.2.0'
                            );
                            
                            wp_enqueue_script(
                                'shepherd-js',
                                'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js',
                                array(),
                                '11.2.0',
                                true
                            );
                            
                            // Enqueue custom tour configuration
                            wp_enqueue_script(
                                'ielts-user-tour',
                                IELTS_CM_PLUGIN_URL . 'assets/js/user-tour.js',
                                array('jquery', 'shepherd-js'),
                                IELTS_CM_VERSION,
                                true
                            );
                            
                            // Pass data to JavaScript
                            wp_localize_script('ielts-user-tour', 'ieltsTourData', array(
                                'ajaxUrl' => admin_url('admin-ajax.php'),
                                'nonce' => wp_create_nonce('ielts_tour_complete'),
                                'userId' => $user_id,
                                'membershipType' => $membership_type,
                                'tourType' => $tour_type
                            ));
                        }
                    }
                }
            }
        }
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
                    <form id="impact-error-report-form">
                        <div style="margin-bottom: 12px;">
                            <label for="impact-message-field" style="display: block; margin-bottom: 5px; font-weight: 600;">Your Message <span style="color: red;">*</span></label>
                            <textarea id="impact-message-field" name="report_message" required style="width: 100%; min-height: 120px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; resize: vertical;" placeholder="Describe the issue you found..."></textarea>
                        </div>
                        <input type="hidden" name="report_page_title" value="<?php echo esc_attr(get_the_title()); ?>">
                        <input type="hidden" name="report_page_url" value="<?php echo esc_url(get_permalink()); ?>">
                        <input type="hidden" name="report_user_name" value="<?php echo esc_attr($user_name); ?>">
                        <input type="hidden" name="report_user_email" value="<?php echo esc_attr($user_email); ?>">
                        <input type="hidden" name="report_first_name" value="<?php echo esc_attr($user_first_name); ?>">
                        <input type="hidden" name="report_last_name" value="<?php echo esc_attr($user_last_name); ?>">
                        <input type="hidden" name="action" value="ielts_cm_submit_error_report">
                        <input type="hidden" name="error_report_nonce" value="<?php echo wp_create_nonce('ielts_error_report_nonce'); ?>">
                        <button type="submit" style="background: #0073e6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600;">Submit Report</button>
                    </form>
                </div>
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
            const formContainer = document.getElementById('impact-form-container');
            const errorForm = document.getElementById('impact-error-report-form');
            
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
                    btn.setAttribute('title', fullText);
                } else {
                    btn.classList.remove('minimized');
                    btn.textContent = fullText;
                    btn.removeAttribute('title');
                }
            }
            
            // Check for expanded state in localStorage (default is minimized)
            const isExpanded = localStorage.getItem(FEEDBACK_EXPANDED_KEY) === 'true';
            updateButtonState(!isExpanded);

            btn.addEventListener('click', () => {
                updateButtonState(false);
                localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'true');
                modal.style.display = 'block';
                
                // Reset form if it was previously submitted
                if (errorForm && errorForm.querySelector) {
                    const originalFormHtml = errorForm.outerHTML;
                    formContainer.innerHTML = originalFormHtml;
                }
            });

            close.addEventListener('click', () => {
                modal.style.display = 'none';
                updateButtonState(true);
                localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
            });
            
            window.addEventListener('click', e => { 
                if (e.target === modal) {
                    modal.style.display = 'none';
                    updateButtonState(true);
                    localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
                }
            });

            // Handle form submission via AJAX
            document.addEventListener('submit', function(e) {
                if (e.target && e.target.id === 'impact-error-report-form') {
                    e.preventDefault();
                    
                    const submitBtn = e.target.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Sending...';
                    
                    const formData = new FormData(e.target);
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            formContainer.innerHTML = '<div role="status" aria-live="polite" style="font-size:16px; font-weight:bold; color: #28a745; text-align: center; padding: 20px; border: 2px solid #28a745; background: #d4edda; border-radius: 4px;"><strong>Success:</strong> ✅ Thanks for letting us know!</div>';
                            setTimeout(() => {
                                modal.style.display = 'none';
                                updateButtonState(true);
                                localStorage.setItem(FEEDBACK_EXPANDED_KEY, 'false');
                            }, 2000);
                        } else {
                            formContainer.innerHTML = '<div role="alert" aria-live="assertive" style="font-size:14px; color: #d32f2f; text-align: center; padding: 15px; border: 2px solid #d32f2f; background: #ffebee; border-radius: 4px;"><strong>Error:</strong> ' + (data.data || 'Failed to submit report. Please try again.') + '</div>';
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        }
                    })
                    .catch(error => {
                        formContainer.innerHTML = '<div role="alert" aria-live="assertive" style="font-size:14px; color: #d32f2f; text-align: center; padding: 15px; border: 2px solid #d32f2f; background: #ffebee; border-radius: 4px;"><strong>Error:</strong> Network error. Please try again.</div>';
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalBtnText;
                    });
                }
            });
        });
        </script>

        <?php
        echo ob_get_clean();
    }
    
    public function handle_error_report_submission() {
        // Explicitly extract POST variables instead of using extract()
        $nonce = isset($_POST['error_report_nonce']) ? $_POST['error_report_nonce'] : '';
        $message = isset($_POST['report_message']) ? $_POST['report_message'] : '';
        $page_title = isset($_POST['report_page_title']) ? $_POST['report_page_title'] : '';
        $page_url = isset($_POST['report_page_url']) ? $_POST['report_page_url'] : '';
        $user_name = isset($_POST['report_user_name']) ? $_POST['report_user_name'] : '';
        $user_email = isset($_POST['report_user_email']) ? $_POST['report_user_email'] : '';
        $first_name = isset($_POST['report_first_name']) ? $_POST['report_first_name'] : '';
        $last_name = isset($_POST['report_last_name']) ? $_POST['report_last_name'] : '';
        
        // Validate nonce
        if (!wp_verify_nonce($nonce, 'ielts_error_report_nonce')) {
            wp_send_json_error('Security verification failed. Please refresh the page and try again.');
        }
        
        // Validate user is logged in
        if (get_current_user_id() === 0) {
            wp_send_json_error('You must be logged in to report an error.');
        }
        
        // Sanitize and validate message
        $message_clean = wp_kses_post($message);
        if (strlen(trim(strip_tags($message_clean))) === 0) {
            wp_send_json_error('Message cannot be empty. Please describe the error you found.');
        }
        
        // Prepare email
        $recipient = get_option('admin_email');
        $subject = 'Error Report: ' . sanitize_text_field($page_title);
        
        // Prepare data for email (sanitize for storage)
        $from_first = sanitize_text_field($first_name);
        $from_last = sanitize_text_field($last_name);
        $from_username = sanitize_text_field($user_name);
        $from_email = sanitize_email($user_email);
        $page_title_safe = sanitize_text_field($page_title);
        $page_url_safe = esc_url_raw($page_url);
        
        // Build email content as HTML table with 2 columns
        $email_body = '
        <html>
        <head>
            <style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                    max-width: 600px;
                    font-family: Arial, sans-serif;
                }
                td {
                    padding: 10px;
                    border: 1px solid #ddd;
                    vertical-align: top;
                }
                td:first-child {
                    font-weight: bold;
                    width: 30%;
                    background-color: #f5f5f5;
                }
            </style>
        </head>
        <body>
            <table>
                <tr>
                    <td>From</td>
                    <td>' . esc_html($from_first . ' ' . $from_last . ' (' . $from_username . ')') . ' &lt;' . esc_html($from_email) . '&gt;</td>
                </tr>
                <tr>
                    <td>Reported error on</td>
                    <td>' . esc_html($page_title_safe) . '</td>
                </tr>
                <tr>
                    <td>Message Body</td>
                    <td>' . wp_kses_post($message_clean) . '</td>
                </tr>
                <tr>
                    <td>Page URL</td>
                    <td><a href="' . $page_url_safe . '">' . esc_html($page_url_safe) . '</a></td>
                </tr>
            </table>
        </body>
        </html>';
        
        // Set email headers with HTML content type
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $recipient . '>',
            'Reply-To: ' . sanitize_text_field($user_name) . ' <' . sanitize_email($user_email) . '>'
        );
        
        // Send email
        $sent = wp_mail($recipient, $subject, $email_body, $headers);
        
        if ($sent) {
            wp_send_json_success('Your error report has been sent successfully. Thank you!');
        } else {
            wp_send_json_error('Failed to send error report. Please try again later.');
        }
    }
    
    /**
     * Handle tour completion AJAX request
     */
    public function handle_tour_completion() {
        check_ajax_referer('ielts_tour_complete', 'nonce');
        
        $user_id = get_current_user_id();
        $tour_type = isset($_POST['tour_type']) ? sanitize_text_field($_POST['tour_type']) : '';
        
        if ($user_id && in_array($tour_type, array('academic', 'general', 'english'))) {
            // Save completion with tour type suffix for cross-device persistence
            update_user_meta($user_id, 'ielts_tour_completed_' . $tour_type, true);
            
            // Also save timestamp
            update_user_meta($user_id, 'ielts_tour_completed_' . $tour_type . '_date', current_time('mysql'));
            
            wp_send_json_success(array(
                'message' => 'Tour completed successfully',
                'tour_type' => $tour_type
            ));
        } else {
            wp_send_json_error(array('message' => 'Invalid request'));
        }
    }
    
    /**
     * Register tour settings
     */
    public function register_tour_settings() {
        // Register global enable setting
        register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        // Register per-membership settings
        register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled_academic', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled_general', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled_english', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        // Add settings section
        add_settings_section(
            'ielts_cm_tour_section',
            __('User Tour Controls', 'ielts-course-manager'),
            array($this, 'tour_settings_section_callback'),
            'ielts_cm_tour_settings'
        );
        
        // Add global enable field
        add_settings_field(
            'ielts_cm_tour_enabled',
            __('Enable All Tours', 'ielts-course-manager'),
            array($this, 'tour_enabled_field_callback'),
            'ielts_cm_tour_settings',
            'ielts_cm_tour_section'
        );
        
        // Add per-membership fields
        add_settings_field(
            'ielts_cm_tour_enabled_academic',
            __('Academic Module Tour', 'ielts-course-manager'),
            array($this, 'tour_enabled_academic_callback'),
            'ielts_cm_tour_settings',
            'ielts_cm_tour_section'
        );
        
        add_settings_field(
            'ielts_cm_tour_enabled_general',
            __('General Training Tour', 'ielts-course-manager'),
            array($this, 'tour_enabled_general_callback'),
            'ielts_cm_tour_settings',
            'ielts_cm_tour_section'
        );
        
        add_settings_field(
            'ielts_cm_tour_enabled_english',
            __('English Only Tour', 'ielts-course-manager'),
            array($this, 'tour_enabled_english_callback'),
            'ielts_cm_tour_settings',
            'ielts_cm_tour_section'
        );
    }
    
    /**
     * Settings section description
     */
    public function tour_settings_section_callback() {
        echo '<p>' . __('Control whether guided tours are shown to first-time users. Tours help new members understand the platform.', 'ielts-course-manager') . '</p>';
    }
    
    /**
     * Global enable/disable field
     */
    public function tour_enabled_field_callback() {
        $enabled = get_option('ielts_cm_tour_enabled', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="ielts_cm_tour_enabled" 
                   value="1" 
                   <?php checked($enabled, true); ?> />
            <?php _e('Show guided tours to first-time users', 'ielts-course-manager'); ?>
        </label>
        <p class="description">
            <?php _e('Uncheck to disable ALL user tours globally. Individual tours below will be ignored if this is disabled.', 'ielts-course-manager'); ?>
        </p>
        <?php
    }
    
    /**
     * Academic tour enable field
     */
    public function tour_enabled_academic_callback() {
        $enabled = get_option('ielts_cm_tour_enabled_academic', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="ielts_cm_tour_enabled_academic" 
                   value="1" 
                   <?php checked($enabled, true); ?> />
            <?php _e('Show tour for Academic module members', 'ielts-course-manager'); ?>
        </label>
        <?php
    }
    
    /**
     * General Training tour enable field
     */
    public function tour_enabled_general_callback() {
        $enabled = get_option('ielts_cm_tour_enabled_general', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="ielts_cm_tour_enabled_general" 
                   value="1" 
                   <?php checked($enabled, true); ?> />
            <?php _e('Show tour for General Training members', 'ielts-course-manager'); ?>
        </label>
        <?php
    }
    
    /**
     * English tour enable field
     */
    public function tour_enabled_english_callback() {
        $enabled = get_option('ielts_cm_tour_enabled_english', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="ielts_cm_tour_enabled_english" 
                   value="1" 
                   <?php checked($enabled, true); ?> />
            <?php _e('Show tour for English-only members', 'ielts-course-manager'); ?>
        </label>
        <?php
    }
    
    /**
     * Add admin menu page
     */
    public function add_tour_admin_menu() {
        add_options_page(
            __('User Tour Settings', 'ielts-course-manager'),
            __('User Tours', 'ielts-course-manager'),
            'manage_options',
            'ielts-tour-settings',
            array($this, 'render_tour_settings_page')
        );
    }
    
    /**
     * Render tour settings page
     */
    public function render_tour_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Handle reset actions
        if (isset($_POST['reset_all_tours']) && check_admin_referer('reset_tours_nonce')) {
            $this->reset_all_user_tours();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('All user tours have been reset! Users will see tours again on their next login.', 'ielts-course-manager') . '</p></div>';
        }
        
        if (isset($_POST['reset_academic_tours']) && check_admin_referer('reset_tours_nonce')) {
            $this->reset_tours_by_type('academic');
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Academic tours have been reset!', 'ielts-course-manager') . '</p></div>';
        }
        
        if (isset($_POST['reset_general_tours']) && check_admin_referer('reset_tours_nonce')) {
            $this->reset_tours_by_type('general');
            echo '<div class="notice notice-success is-dismissible"><p>' . __('General Training tours have been reset!', 'ielts-course-manager') . '</p></div>';
        }
        
        if (isset($_POST['reset_english_tours']) && check_admin_referer('reset_tours_nonce')) {
            $this->reset_tours_by_type('english');
            echo '<div class="notice notice-success is-dismissible"><p>' . __('English tours have been reset!', 'ielts-course-manager') . '</p></div>';
        }
        
        // Get tour statistics
        $stats = $this->get_tour_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Tour Settings', 'ielts-course-manager'); ?></h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ielts_cm_tour_settings');
                    do_settings_sections('ielts_cm_tour_settings');
                    submit_button(__('Save Settings', 'ielts-course-manager'));
                    ?>
                </form>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Tour Statistics', 'ielts-course-manager'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Tour Type', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Users Completed', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Academic Module', 'ielts-course-manager'); ?></td>
                            <td><?php echo esc_html($stats['academic']); ?></td>
                            <td>
                                <?php echo get_option('ielts_cm_tour_enabled_academic', true) 
                                    ? '<span style="color: green;">●</span> ' . __('Enabled', 'ielts-course-manager')
                                    : '<span style="color: red;">●</span> ' . __('Disabled', 'ielts-course-manager'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('General Training', 'ielts-course-manager'); ?></td>
                            <td><?php echo esc_html($stats['general']); ?></td>
                            <td>
                                <?php echo get_option('ielts_cm_tour_enabled_general', true) 
                                    ? '<span style="color: green;">●</span> ' . __('Enabled', 'ielts-course-manager')
                                    : '<span style="color: red;">●</span> ' . __('Disabled', 'ielts-course-manager'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('English Only', 'ielts-course-manager'); ?></td>
                            <td><?php echo esc_html($stats['english']); ?></td>
                            <td>
                                <?php echo get_option('ielts_cm_tour_enabled_english', true) 
                                    ? '<span style="color: green;">●</span> ' . __('Enabled', 'ielts-course-manager')
                                    : '<span style="color: red;">●</span> ' . __('Disabled', 'ielts-course-manager'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Reset Tours', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Force users to see tours again by resetting completion status. This is useful after updating tour content.', 'ielts-course-manager'); ?></p>
                
                <form method="post" style="display: inline; margin-right: 10px;">
                    <?php wp_nonce_field('reset_tours_nonce'); ?>
                    <button type="submit" 
                            name="reset_all_tours" 
                            class="button button-secondary"
                            onclick="return confirm('<?php esc_attr_e('Reset ALL user tours? All users will see tours again on their next login.', 'ielts-course-manager'); ?>');">
                        <?php _e('Reset All Tours', 'ielts-course-manager'); ?>
                    </button>
                </form>
                
                <form method="post" style="display: inline; margin-right: 10px;">
                    <?php wp_nonce_field('reset_tours_nonce'); ?>
                    <button type="submit" 
                            name="reset_academic_tours" 
                            class="button button-secondary"
                            onclick="return confirm('<?php esc_attr_e('Reset Academic tours only?', 'ielts-course-manager'); ?>');">
                        <?php _e('Reset Academic', 'ielts-course-manager'); ?>
                    </button>
                </form>
                
                <form method="post" style="display: inline; margin-right: 10px;">
                    <?php wp_nonce_field('reset_tours_nonce'); ?>
                    <button type="submit" 
                            name="reset_general_tours" 
                            class="button button-secondary"
                            onclick="return confirm('<?php esc_attr_e('Reset General Training tours?', 'ielts-course-manager'); ?>');">
                        <?php _e('Reset General', 'ielts-course-manager'); ?>
                    </button>
                </form>
                
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('reset_tours_nonce'); ?>
                    <button type="submit" 
                            name="reset_english_tours" 
                            class="button button-secondary"
                            onclick="return confirm('<?php esc_attr_e('Reset English tours?', 'ielts-course-manager'); ?>');">
                        <?php _e('Reset English', 'ielts-course-manager'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get tour completion statistics
     */
    private function get_tour_statistics() {
        global $wpdb;
        
        $stats = array(
            'academic' => 0,
            'general' => 0,
            'english' => 0
        );
        
        // Count users who completed each tour type
        $stats['academic'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'ielts_tour_completed_academic' AND meta_value = '1'"
        );
        
        $stats['general'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'ielts_tour_completed_general' AND meta_value = '1'"
        );
        
        $stats['english'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'ielts_tour_completed_english' AND meta_value = '1'"
        );
        
        return $stats;
    }
    
    /**
     * Reset all user tours
     */
    private function reset_all_user_tours() {
        global $wpdb;
        
        // Delete all tour completion meta
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ielts_tour_completed_%'");
    }
    
    /**
     * Reset tours by type
     */
    private function reset_tours_by_type($type) {
        global $wpdb;
        
        $meta_key = 'ielts_tour_completed_' . sanitize_key($type);
        $date_key = $meta_key . '_date';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s, %s)",
            $meta_key,
            $date_key
        ));
    }
}
