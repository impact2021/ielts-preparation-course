<?php
/**
 * Main plugin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_Course_Manager {
    
    protected $post_types;
    protected $database;
    protected $progress_tracker;
    protected $quiz_handler;
    protected $shortcodes;
    protected $enrollment;
    protected $admin;
    protected $frontend;
    protected $sync_manager;
    protected $sync_api;
    protected $sync_settings_page;
    protected $sync_status_page;
    protected $tours_page;
    protected $auto_sync_manager;
    protected $awards;
    protected $gamification;
    protected $membership;
    
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }
    
    private function load_dependencies() {
        // Dependencies are already loaded in main plugin file
    }
    
    private function init_components() {
        $this->post_types = new IELTS_CM_Post_Types();
        $this->database = new IELTS_CM_Database();
        $this->progress_tracker = new IELTS_CM_Progress_Tracker();
        $this->quiz_handler = new IELTS_CM_Quiz_Handler();
        $this->shortcodes = new IELTS_CM_Shortcodes();
        $this->enrollment = new IELTS_CM_Enrollment();
        $this->admin = new IELTS_CM_Admin();
        $this->frontend = new IELTS_CM_Frontend();
        $this->sync_manager = new IELTS_CM_Multi_Site_Sync();
        $this->sync_api = new IELTS_CM_Sync_API();
        $this->sync_settings_page = new IELTS_CM_Sync_Settings_Page();
        $this->sync_status_page = new IELTS_CM_Sync_Status_Page();
        $this->tours_page = new IELTS_CM_Tours_Page();
        $this->auto_sync_manager = new IELTS_CM_Auto_Sync_Manager();
        $this->awards = new IELTS_CM_Awards();
        $this->gamification = new IELTS_CM_Gamification();
        $this->membership = new IELTS_CM_Membership();
    }
    
    public function run() {
        // Register post types
        add_action('init', array($this->post_types, 'register_post_types'));
        
        // Check for version update and flush permalinks if needed
        add_action('init', array($this, 'check_version_update'));
        
        // SECURITY: Block ALL unauthorized user registration
        add_action('init', array($this, 'block_unauthorized_registration'));
        add_filter('registration_errors', array($this, 'block_default_registration'), 10, 3);
        add_action('user_register', array($this, 'verify_authorized_registration'), 1);
        
        // Register shortcodes on init hook
        add_action('init', array($this->shortcodes, 'register'));
        
        // Register REST API routes
        add_action('rest_api_init', array($this->sync_api, 'register_routes'));
        
        // Fix serialized data during WordPress import
        add_filter('wp_import_post_meta', array($this, 'fix_imported_serialized_data'), 10, 3);
        
        // Initialize membership
        $this->membership->init();
        
        // Initialize auto-sync manager
        $this->auto_sync_manager->init();
        
        // Initialize admin
        if (is_admin()) {
            $this->admin->init();
            
            // Initialize sync settings page
            add_action('admin_menu', array($this->sync_settings_page, 'add_menu_page'));
            add_action('admin_init', array($this->sync_settings_page, 'handle_form_submit'));
            
            // Initialize sync status page
            add_action('admin_menu', array($this->sync_status_page, 'add_menu_page'));
            add_action('wp_ajax_ielts_cm_check_sync_status', array($this->sync_status_page, 'handle_ajax_check_sync'));
            add_action('wp_ajax_ielts_cm_bulk_sync', array($this->sync_status_page, 'handle_ajax_bulk_sync'));
            
            // Initialize tours page
            add_action('admin_menu', array($this->tours_page, 'add_menu_page'));
            add_action('admin_init', array($this->tours_page, 'handle_form_submit'));
        }
        
        // Initialize frontend
        $this->frontend->init();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add award notification element to footer for logged-in users
        add_action('wp_footer', array($this, 'add_award_notification'));
        
        // Track user login
        add_action('wp_login', array($this, 'track_user_login'), 10, 2);
        
        // Track session time on page load
        add_action('wp_footer', array($this, 'track_session_time'));
        
        // Add hooks for syncing deletions to subsites
        add_action('wp_trash_post', array($this, 'sync_content_deletion'), 10, 1);
        add_action('before_delete_post', array($this, 'sync_content_deletion'), 10, 1);
        
        // Log password reset events for auditing (tracks why users need to reset passwords)
        add_action('retrieve_password', array($this, 'log_password_reset_request'), 10, 1);
        add_action('after_password_reset', array($this, 'log_password_reset_complete'), 10, 2);
        
        // Add plugin version to admin bar
        add_action('admin_bar_menu', array($this, 'add_version_to_admin_bar'), 100);
    }
    
    /**
     * Check if plugin version has been updated and flush permalinks if needed
     * Uses a transient to avoid checking on every page load
     */
    public function check_version_update() {
        // Use a transient to avoid checking on every page load
        $version_checked = get_transient('ielts_cm_version_checked');
        
        if ($version_checked === IELTS_CM_VERSION) {
            // Version already checked and is current
            return;
        }
        
        $current_version = get_option('ielts_cm_version');
        
        // If version has changed, flush rewrite rules and update version
        if ($current_version !== IELTS_CM_VERSION) {
            // Run upgrade routine to ensure all database tables exist
            IELTS_CM_Database::create_tables();
            
            flush_rewrite_rules();
            update_option('ielts_cm_version', IELTS_CM_VERSION);
            // Set transient after flushing to confirm version is updated
            set_transient('ielts_cm_version_checked', IELTS_CM_VERSION, HOUR_IN_SECONDS);
        } else {
            // Version is current but transient expired, reset it without flushing
            set_transient('ielts_cm_version_checked', IELTS_CM_VERSION, HOUR_IN_SECONDS);
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('ielts-cm-frontend', IELTS_CM_PLUGIN_URL . 'assets/css/frontend.css', array(), IELTS_CM_VERSION);
        wp_enqueue_script('ielts-cm-frontend', IELTS_CM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), IELTS_CM_VERSION, true);
        
        // Add inline CSS for primary color on sticky bottom navigation
        $primary_color = get_option('ielts_cm_vocab_header_color', '#E56C0A');
        $nav_css = "
            .ielts-sticky-bottom-nav {
                background: {$primary_color} !important;
            }
            .ielts-sticky-bottom-nav .nav-link {
                background: rgba(255, 255, 255, 0.95);
            }
            .ielts-sticky-bottom-nav .nav-link:hover {
                background: rgba(255, 255, 255, 1);
            }
        ";
        wp_add_inline_style('ielts-cm-frontend', $nav_css);
        
        // Enqueue awards scripts globally for all logged-in users
        if (is_user_logged_in()) {
            wp_enqueue_style('ielts-cm-awards-css', IELTS_CM_PLUGIN_URL . 'assets/css/awards.css', array(), IELTS_CM_VERSION);
            wp_enqueue_script('ielts-cm-awards-js', IELTS_CM_PLUGIN_URL . 'assets/js/awards.js', array('jquery'), IELTS_CM_VERSION, true);
            
            wp_localize_script('ielts-cm-awards-js', 'ieltsAwardsConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ielts_cm_nonce'),
                'userId' => get_current_user_id()
            ));
        }
        
        wp_localize_script('ielts-cm-frontend', 'ieltsCM', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ielts_cm_nonce')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        wp_enqueue_style('ielts-cm-admin', IELTS_CM_PLUGIN_URL . 'assets/css/admin.css', array(), IELTS_CM_VERSION);
        wp_enqueue_script('ielts-cm-admin', IELTS_CM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), IELTS_CM_VERSION, true);
        
        // Localize script for course edit pages
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            global $post;
            $screen = get_current_screen();
            $post_type = '';
            
            // Determine post type from screen or post object
            if ($screen && isset($screen->post_type)) {
                $post_type = $screen->post_type;
            } elseif ($post && isset($post->post_type)) {
                $post_type = $post->post_type;
            }
            
            // Only process our custom post types
            if ($post_type === 'ielts_course') {
                wp_localize_script('ielts-cm-admin', 'ieltsCMAdmin', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'lessonOrderNonce' => wp_create_nonce('ielts_cm_lesson_order'),
                    'courseLessonsNonce' => wp_create_nonce('ielts_cm_course_lessons'),
                    'courseMetaNonce' => wp_create_nonce('ielts_cm_course_meta'),
                    'courseId' => $post->ID ?? 0,
                    'i18n' => array(
                        'orderUpdated' => __('Lesson order updated successfully!', 'ielts-course-manager'),
                        'orderFailed' => __('Failed to update lesson order. Please try again.', 'ielts-course-manager'),
                        'orderError' => __('An error occurred. Please try again.', 'ielts-course-manager'),
                        'orderLabel' => __('Order:', 'ielts-course-manager')
                    )
                ));
            } elseif ($post_type === 'ielts_lesson') {
                wp_localize_script('ielts-cm-admin', 'ieltsCMAdmin', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'pageOrderNonce' => wp_create_nonce('ielts_cm_page_order'),
                    'contentOrderNonce' => wp_create_nonce('ielts_cm_content_order'),
                    'lessonContentNonce' => wp_create_nonce('ielts_cm_lesson_content'),
                    'lessonId' => $post->ID ?? 0,
                    'i18n' => array(
                        'pageOrderUpdated' => __('Lesson page order updated successfully!', 'ielts-course-manager'),
                        'pageOrderFailed' => __('Failed to update lesson page order. Please try again.', 'ielts-course-manager'),
                        'pageOrderError' => __('An error occurred. Please try again.', 'ielts-course-manager'),
                        'contentOrderUpdated' => __('Content order updated successfully!', 'ielts-course-manager'),
                        'contentOrderFailed' => __('Failed to update content order. Please try again.', 'ielts-course-manager'),
                        'contentOrderError' => __('An error occurred. Please try again.', 'ielts-course-manager'),
                        'orderLabel' => __('Order:', 'ielts-course-manager')
                    )
                ));
            }
        }
    }
    
    /**
     * Fix broken serialized data during WordPress import
     * 
     * This fixes a common issue where serialized PHP data in XML imports has
     * incorrect string lengths due to line ending differences (LF vs CRLF).
     * 
     * @param array $postmeta Array of post meta data
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @return array Fixed post meta data
     */
    public function fix_imported_serialized_data($postmeta, $post_id, $post) {
        // Only process IELTS quiz post types
        // $post is an array in the wp_import_post_meta filter
        if (!isset($post['post_type']) || $post['post_type'] !== 'ielts_quiz') {
            return $postmeta;
        }
        
        foreach ($postmeta as $index => $meta) {
            $meta_key = $meta['key'];
            $meta_value = $meta['value'];
            
            // Only fix our plugin's serialized fields
            if (in_array($meta_key, array('_ielts_cm_questions', '_ielts_cm_reading_texts', '_ielts_cm_course_ids', '_ielts_cm_lesson_ids'))) {
                // Check if this looks like serialized data
                if (is_string($meta_value) && (substr($meta_value, 0, 2) === 'a:' || substr($meta_value, 0, 2) === 'O:')) {
                    // Try to unserialize it
                    $test = @unserialize($meta_value);
                    
                    if ($test === false && $meta_value !== serialize(false)) {
                        // Serialization is broken - try to fix it
                        $fixed_value = $this->fix_serialized_string_lengths($meta_value);
                        
                        // Test if the fix worked
                        $test_fixed = @unserialize($fixed_value);
                        if ($test_fixed !== false || $fixed_value === serialize(false)) {
                            // Fix worked - update the meta value
                            $postmeta[$index]['value'] = $fixed_value;
                        }
                    }
                }
            }
        }
        
        return $postmeta;
    }
    
    /**
     * Fix string lengths in serialized data
     * 
     * Recalculates and fixes string length declarations in serialized PHP data.
     * This handles the case where line endings have been modified during export/import.
     * 
     * @param string $data Serialized data
     * @return string Fixed serialized data
     */
    private function fix_serialized_string_lengths($data) {
        // Fix string length declarations using non-greedy matching with DOTALL modifier
        // Pattern matches: s:123:"string content"; where string content can span multiple lines
        $fixed = preg_replace_callback(
            '/s:(\d+):"(.*?)";/s',  // 's' modifier allows . to match newlines
            function($matches) {
                $declared_length = (int)$matches[1];
                $string_content = $matches[2];
                $actual_length = strlen($string_content);
                
                if ($declared_length !== $actual_length) {
                    // Length mismatch - return corrected version
                    return 's:' . $actual_length . ':"' . $string_content . '";';
                }
                return $matches[0];
            },
            $data
        );
        
        return $fixed;
    }
    
    /**
     * Add award notification element to footer for logged-in users
     */
    public function add_award_notification() {
        if (!is_user_logged_in()) {
            return;
        }
        ?>
        <!-- Award notification template -->
        <div id="ielts-award-notification" class="ielts-award-notification" style="display: none;">
            <div class="award-notification-content">
                <div class="award-notification-icon"></div>
                <div class="award-notification-text">
                    <h3><?php _e('Award Earned!', 'ielts-course-manager'); ?></h3>
                    <p class="award-notification-name"></p>
                    <p class="award-notification-description"></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Track user login
     * 
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function track_user_login($user_login, $user) {
        // Update last login time
        $current_time = current_time('mysql');
        update_user_meta($user->ID, '_ielts_cm_last_login', current_time('timestamp'));
        update_user_meta($user->ID, 'last_login', $current_time);
        
        // Update login count
        $login_count = get_user_meta($user->ID, '_ielts_cm_login_count', true);
        $login_count = $login_count ? intval($login_count) + 1 : 1;
        update_user_meta($user->ID, '_ielts_cm_login_count', $login_count);
        
        // Set session start time
        update_user_meta($user->ID, '_ielts_cm_session_start', current_time('timestamp'));
    }
    
    /**
     * Track session time on page load
     */
    public function track_session_time() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $session_start = get_user_meta($user_id, '_ielts_cm_session_start', true);
        
        if ($session_start) {
            $current_time = current_time('timestamp');
            $session_duration = $current_time - $session_start;
            
            // Define session timeout (1 hour)
            $session_timeout = 3600;
            
            // Only count if session is less than timeout (to avoid counting inactive sessions)
            if ($session_duration < $session_timeout) {
                // Add the incremental time since last page load
                $total_time = get_user_meta($user_id, '_ielts_cm_total_time_logged_in', true);
                $total_time = $total_time ? intval($total_time) + $session_duration : $session_duration;
                update_user_meta($user_id, '_ielts_cm_total_time_logged_in', $total_time);
            }
            
            // Reset session start to current time for next page load
            update_user_meta($user_id, '_ielts_cm_session_start', $current_time);
        }
    }
    
    /**
     * Sync content deletion to subsites when content is trashed or deleted
     * 
     * @param int $post_id The ID of the post being deleted/trashed
     */
    public function sync_content_deletion($post_id) {
        // Only sync if this is a primary site
        if (!$this->sync_manager->is_primary_site()) {
            return;
        }
        
        // Get the post to check its type
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        // Map post types to content types
        $type_mapping = array(
            'ielts_course' => 'course',
            'ielts_lesson' => 'lesson',
            'ielts_resource' => 'resource',
            'ielts_quiz' => 'quiz'
        );
        
        // Check if this is a content type we sync
        if (!isset($type_mapping[$post->post_type])) {
            return;
        }
        
        $content_type = $type_mapping[$post->post_type];
        
        // Push deletion notification to all subsites
        $results = $this->sync_manager->push_deletion_to_subsites($post_id, $content_type);
        
        // Log the results
        if (is_wp_error($results)) {
            error_log("IELTS Sync: Failed to push deletion notification: " . $results->get_error_message());
        } else {
            $success_count = 0;
            $fail_count = 0;
            
            foreach ($results as $site_id => $result) {
                if (is_wp_error($result)) {
                    $fail_count++;
                } else {
                    $success_count++;
                }
            }
            
            error_log("IELTS Sync: Deletion notification for {$content_type} {$post_id} sent to {$success_count} subsite(s), {$fail_count} failed");
        }
    }
    
    /**
     * Add plugin version to admin bar
     */
    public function add_version_to_admin_bar($wp_admin_bar) {
        // Only show for admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $args = array(
            'id'    => 'ielts-cm-version',
            'title' => 'IELTS v' . IELTS_CM_VERSION,
            'meta'  => array('class' => 'ielts-cm-version-node')
        );
        $wp_admin_bar->add_node($args);
    }
    
    /**
     * Log when a user requests a password reset link ("Lost your password?").
     *
     * @param string $user_login The user's login name.
     */
    public function log_password_reset_request( $user_login ) {
        $user = get_user_by( 'login', $user_login );
        if ( ! $user ) {
            return;
        }
        IELTS_CM_Database::log_password_reset_event(
            $user->ID,
            $user->user_email,
            'user_reset_request',
            null,
            null,
            'User requested a password reset link via "Lost your password?" form.'
        );
    }

    /**
     * Log when a user successfully completes a password reset.
     *
     * @param WP_User $user     The user object.
     * @param string  $new_pass The new password (not logged for security).
     */
    public function log_password_reset_complete( $user, $new_pass ) {
        IELTS_CM_Database::log_password_reset_event(
            $user->ID,
            $user->user_email,
            'user_initiated',
            null,
            null,
            'User completed password reset via the WordPress password reset form.'
        );
    }

    /**
     * SECURITY: Block ALL unauthorized user registration
     * Force disable WordPress default registration
     */
    public function block_unauthorized_registration() {
        // Force disable WordPress's built-in registration (only update if changed)
        if (get_option('users_can_register') != 0) {
            update_option('users_can_register', 0);
        }
        
        // Log unauthorized registration attempts
        if (isset($_GET['action']) && $_GET['action'] === 'register' && !is_admin()) {
            $ip = $this->get_client_ip();
            error_log("IELTS Security: Blocked unauthorized registration attempt from IP: {$ip}");
        }
    }
    
    /**
     * SECURITY: Block default WordPress registration form
     * Users can ONLY register through payment or trial system
     */
    public function block_default_registration($errors, $sanitized_user_login, $user_email) {
        // Check if this is coming from our authorized registration handlers
        $is_authorized = $this->is_authorized_registration_context();
        
        if (!$is_authorized) {
            $ip = $this->get_client_ip();
            error_log("IELTS Security: Blocked default registration attempt - User: {$sanitized_user_login}, Email: {$user_email}, IP: {$ip}");
            
            $errors->add('registration_blocked', __('<strong>ERROR</strong>: Account creation is only available through our payment or trial registration system.', 'ielts-course-manager'));
        }
        
        return $errors;
    }
    
    /**
     * SECURITY: Verify user registration is authorized
     * Kill any registration not coming from our payment/trial system
     */
    public function verify_authorized_registration($user_id) {
        // Check if this is coming from our authorized registration handlers
        $is_authorized = $this->is_authorized_registration_context();
        
        if (!$is_authorized && !current_user_can('create_users')) {
            // This is an unauthorized registration - delete the user immediately
            $user = get_userdata($user_id);
            $email = $user ? $user->user_email : 'unknown';
            $ip = $this->get_client_ip();
            
            error_log("IELTS Security ALERT: Unauthorized user created and DELETED - ID: {$user_id}, Email: {$email}, IP: {$ip}");
            
            // Delete the unauthorized user
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            
            // Prevent any further hooks from running
            wp_die(__('Unauthorized registration attempt. This incident has been logged.', 'ielts-course-manager'), 'Registration Blocked', array('response' => 403));
        }
    }
    
    /**
     * SECURITY: Get client IP address reliably
     * Handles proxies and load balancers while preventing header injection
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Standard proxy header
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR'            // Direct connection
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                // For X-Forwarded-For, get the first IP in the list
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                
                // Validate IP format (IPv4 or IPv6)
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * SECURITY: Check if registration is coming from authorized sources
     * Only our payment/trial system and admins can create accounts
     * Uses a marker set by authorized registration flows for efficiency
     */
    private function is_authorized_registration_context() {
        // Allow admin-created users
        if (is_admin() && current_user_can('create_users')) {
            return true;
        }
        
        // Check for authorization marker set by authorized handlers
        if (defined('IELTS_CM_AUTHORIZED_REGISTRATION') && IELTS_CM_AUTHORIZED_REGISTRATION === true) {
            return true;
        }
        
        // Fallback: Check backtrace (less efficient but catches edge cases)
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        
        foreach ($backtrace as $trace) {
            // Allow registration from these authorized classes only:
            // - IELTS_CM_Shortcodes (trial/paid registration form)
            // - IELTS_CM_Stripe_Payment (Stripe webhook payment confirmation)
            // - IELTS_CM_Access_Codes (access code registration)
            if (isset($trace['class']) && in_array($trace['class'], array(
                'IELTS_CM_Shortcodes',
                'IELTS_CM_Stripe_Payment',
                'IELTS_CM_Access_Codes'
            ))) {
                return true;
            }
        }
        
        return false;
    }
}
