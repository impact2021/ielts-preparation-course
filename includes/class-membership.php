<?php
/**
 * Membership functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Membership {
    
    /**
     * Membership levels
     */
    const MEMBERSHIP_LEVELS = array(
        'academic_trial' => 'Academic Module - Free Trial',
        'general_trial' => 'General Training - Free Trial',
        'academic_full' => 'IELTS Core (Academic Module)',
        'general_full' => 'IELTS Core (General Training Module)',
        'academic_plus' => 'IELTS Plus (Academic Module)',
        'general_plus' => 'IELTS Plus (General Training Module)',
        'english_trial' => 'English Only - Free Trial',
        'english_full' => 'English Only Full Membership'
    );
    
    /**
     * Membership benefits/descriptions
     */
    const MEMBERSHIP_BENEFITS = array(
        'academic_trial' => 'Free 6-hour trial access',
        'general_trial' => 'Free 6-hour trial access',
        'academic_full' => '30 days full access to Academic Module',
        'general_full' => '30 days full access to General Training Module',
        'academic_plus' => '90 days full access + 2 live speaking assessments',
        'general_plus' => '90 days full access + 2 live speaking assessments',
        'english_trial' => 'Free 6-hour trial access',
        'english_full' => '30 days full access to English Only content'
    );
    
    /**
     * Extension types for paid members
     */
    const EXTENSION_TYPES = array('extension_1_week', 'extension_1_month', 'extension_3_months');
    
    /**
     * Default access code expiry duration (1 year)
     * Used when admin sets access code enrollment without specifying expiry date
     */
    const DEFAULT_ACCESS_CODE_DURATION = '+1 year';
    
    /**
     * Trial period in days
     */
    const TRIAL_PERIOD_DAYS = 30;
    
    /**
     * Membership status values
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_NONE = 'none';
    
    /**
     * Constructor - Register cron action early
     */
    public function __construct() {
        // Register cron action hook early so it's available when cron fires
        // This must be registered before WordPress tries to execute the cron event
        add_action('ielts_cm_check_expired_memberships', array($this, 'check_and_update_expired_memberships'));
        
        // Also add a fallback check on init to catch any memberships that expired between cron runs
        add_action('init', array($this, 'check_expired_on_access'), 20);
    }
    
    /**
     * Initialize membership functionality
     */
    public function init() {
        // Create custom roles for membership levels on init
        $this->create_membership_roles();
        
        // Add admin menu (only shows if system is enabled) and register settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add email filters to customize sender name and address
        // Note: These filters apply to all WordPress emails for consistent branding
        add_filter('wp_mail_from_name', array($this, 'custom_email_from_name'));
        add_filter('wp_mail_from', array($this, 'custom_email_from_address'));
        
        // Schedule daily cron job to check for expired memberships
        if (!wp_next_scheduled('ielts_cm_check_expired_memberships')) {
            wp_schedule_event(time(), 'daily', 'ielts_cm_check_expired_memberships');
        }
        
        // Add user edit fields (always show, including Access Code Enrollment section)
        add_action('show_user_profile', array($this, 'user_membership_fields'));
        add_action('edit_user_profile', array($this, 'user_membership_fields'));
        add_action('personal_options_update', array($this, 'save_user_membership_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_membership_fields'));
        
        // Only initialize other features if membership system is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Add user columns
        add_filter('manage_users_columns', array($this, 'add_user_columns'));
        add_filter('manage_users_custom_column', array($this, 'user_column_content'), 10, 3);
    }
    
    /**
     * Create custom WordPress roles for each membership level
     * This ensures role-based access control instead of relying on fragile meta fields
     * Only creates roles when Paid Membership system is enabled
     */
    public function create_membership_roles() {
        // Only create membership roles if paid membership system is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Get subscriber capabilities as base
        $subscriber = get_role('subscriber');
        if (!$subscriber) {
            return;
        }
        
        $base_caps = $subscriber->capabilities;
        
        // Create role for each membership level if it doesn't exist
        foreach (self::MEMBERSHIP_LEVELS as $role_slug => $role_name) {
            if (!get_role($role_slug)) {
                add_role($role_slug, $role_name, $base_caps);
            }
        }
    }
    
    /**
     * Check if membership system is enabled
     */
    public function is_enabled() {
        return get_option('ielts_cm_membership_enabled', false);
    }
    
    /**
     * Customize email from name
     * 
     * @param string $from_name Default from name
     * @return string Custom from name or default if not set
     */
    public function custom_email_from_name($from_name) {
        $custom_name = get_option('ielts_cm_email_from_name', '');
        return !empty($custom_name) ? $custom_name : $from_name;
    }
    
    /**
     * Customize email from address
     * 
     * @param string $from_email Default from email address
     * @return string Custom from email or default if not set
     */
    public function custom_email_from_address($from_email) {
        $custom_email = get_option('ielts_cm_email_from_address', '');
        return !empty($custom_email) ? $custom_email : $from_email;
    }
    
    /**
     * Add membership columns to users list
     */
    public function add_user_columns($columns) {
        $columns['membership'] = __('Membership', 'ielts-course-manager');
        return $columns;
    }
    
    /**
     * Display membership column content
     */
    public function user_column_content($value, $column_name, $user_id) {
        if ($column_name === 'membership') {
            $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
            $expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);
            $status = get_user_meta($user_id, '_ielts_cm_membership_status', true);
            
            if (empty($membership_type)) {
                return __('None', 'ielts-course-manager');
            }
            
            $membership_name = isset(self::MEMBERSHIP_LEVELS[$membership_type]) 
                ? self::MEMBERSHIP_LEVELS[$membership_type] 
                : $membership_type;
            
            // Check status first, then fall back to date comparison for legacy data
            $is_expired = ($status === self::STATUS_EXPIRED) || 
                          (!empty($expiry_date) && strtotime($expiry_date) < time());
            
            if ($is_expired) {
                return '<span style="color: #dc3232;">' . esc_html($membership_name) . ' (Expired)</span>';
            }
            
            if (!empty($expiry_date)) {
                $expiry_timestamp = strtotime($expiry_date);
                return esc_html($membership_name) . '<br><small>' . date('Y-m-d', $expiry_timestamp) . '</small>';
            }
            
            return esc_html($membership_name);
        }
        return $value;
    }
    
    /**
     * Display membership fields on user profile
     */
    public function user_membership_fields($user) {
        if (!current_user_can('edit_users')) {
            return;
        }
        
        $membership_type = get_user_meta($user->ID, '_ielts_cm_membership_type', true);
        $expiry_date = get_user_meta($user->ID, '_ielts_cm_membership_expiry', true);
        
        // Access code system fields
        $course_group = get_user_meta($user->ID, 'iw_course_group', true);
        $iw_expiry = get_user_meta($user->ID, 'iw_membership_expiry', true);
        $enrolled_academic = get_user_meta($user->ID, 'enrolled_ielts_academic', true);
        $enrolled_general = get_user_meta($user->ID, 'enrolled_ielts_general', true);
        $enrolled_english = get_user_meta($user->ID, 'enrolled_general_english', true);
        
        // Updated course groups to match requirements
        $course_groups = array(
            'academic_module' => 'Academic Module',
            'general_module' => 'General Training Module',
            'general_english' => 'General English'
        );
        
        // Check if paid membership system is enabled
        $membership_enabled = get_option('ielts_cm_membership_enabled', false);
        ?>
        <h2><?php _e('Membership Information', 'ielts-course-manager'); ?></h2>
        <?php if ($membership_enabled): ?>
        <table class="form-table">
            <tr>
                <th><label for="ielts_cm_membership_type"><?php _e('Membership Type', 'ielts-course-manager'); ?></label></th>
                <td>
                    <select name="ielts_cm_membership_type" id="ielts_cm_membership_type">
                        <option value=""><?php _e('None', 'ielts-course-manager'); ?></option>
                        <?php foreach (self::MEMBERSHIP_LEVELS as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($membership_type, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ielts_cm_membership_expiry"><?php _e('Expiry Date', 'ielts-course-manager'); ?></label></th>
                <td>
                    <input type="date" name="ielts_cm_membership_expiry" id="ielts_cm_membership_expiry" 
                           value="<?php echo esc_attr($expiry_date); ?>" class="regular-text">
                    <p class="description"><?php _e('Leave empty for lifetime membership', 'ielts-course-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php else: ?>
        <p class="description"><?php _e('Paid membership system is disabled. Use Access Code Enrollment below to manage user access.', 'ielts-course-manager'); ?></p>
        <?php endif; ?>
        
        <h2><?php _e('Access Code Enrollment', 'ielts-course-manager'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="iw_course_group"><?php _e('Course Group', 'ielts-course-manager'); ?></label></th>
                <td>
                    <select name="iw_course_group" id="iw_course_group">
                        <option value=""><?php _e('None', 'ielts-course-manager'); ?></option>
                        <?php foreach ($course_groups as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($course_group, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <strong><?php _e('Academic Module:', 'ielts-course-manager'); ?></strong> <?php _e('Includes courses with slugs: academic, english, academic-practice-tests', 'ielts-course-manager'); ?><br>
                        <strong><?php _e('General Training Module:', 'ielts-course-manager'); ?></strong> <?php _e('Includes courses with slugs: general, english, general-practice-tests', 'ielts-course-manager'); ?><br>
                        <strong><?php _e('General English:', 'ielts-course-manager'); ?></strong> <?php _e('Only includes course with slug: english', 'ielts-course-manager'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="iw_membership_expiry"><?php _e('Access Code Expiry', 'ielts-course-manager'); ?></label></th>
                <td>
                    <?php
                    $date_value = '';
                    if ($iw_expiry) {
                        $timestamp = strtotime($iw_expiry);
                        if ($timestamp !== false) {
                            $date_value = date('Y-m-d', $timestamp);
                        }
                    }
                    ?>
                    <input type="date" name="iw_membership_expiry" id="iw_membership_expiry" 
                           value="<?php echo esc_attr($date_value); ?>" class="regular-text">
                    <p class="description"><?php _e('Expiry date for access code based enrollment. Dates are displayed in dd/mm/yyyy format throughout the system.', 'ielts-course-manager'); ?></p>
                </td>
            </tr>
        </table>
        <p class="description"><em><?php _e('Note: Course access is determined by the Course Group selection above. The checkboxes below are legacy fields and will be deprecated.', 'ielts-course-manager'); ?></em></p>
        <?php
    }
    
    /**
     * Save user membership fields
     */
    public function save_user_membership_fields($user_id) {
        if (!current_user_can('edit_users')) {
            return;
        }
        
        if (isset($_POST['ielts_cm_membership_type'])) {
            $membership_type = sanitize_text_field($_POST['ielts_cm_membership_type']);
            update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
            
            // Set status based on whether membership type is set and expiry date
            if (!empty($membership_type)) {
                $expiry_date = isset($_POST['ielts_cm_membership_expiry']) ? sanitize_text_field($_POST['ielts_cm_membership_expiry']) : '';
                
                // If no expiry or expiry is in the future, set as active
                if (empty($expiry_date) || strtotime($expiry_date) > time()) {
                    $this->set_user_membership_status($user_id, self::STATUS_ACTIVE);
                    // Clear expiry email tracking when setting to active
                    delete_user_meta($user_id, '_ielts_cm_expiry_email_sent');
                } else {
                    // Expiry is in the past, set as expired
                    $this->set_user_membership_status($user_id, self::STATUS_EXPIRED);
                }
            } else {
                // No membership type, set status to none
                $this->set_user_membership_status($user_id, self::STATUS_NONE);
                // Clear expiry email tracking when no membership
                delete_user_meta($user_id, '_ielts_cm_expiry_email_sent');
            }
        }
        
        if (isset($_POST['ielts_cm_membership_expiry'])) {
            update_user_meta($user_id, '_ielts_cm_membership_expiry', sanitize_text_field($_POST['ielts_cm_membership_expiry']));
        }
        
        // Save access code enrollment fields
        $course_group = isset($_POST['iw_course_group']) ? sanitize_text_field($_POST['iw_course_group']) : null;
        $course_group_changed = false;
        
        if ($course_group !== null) {
            $old_course_group = get_user_meta($user_id, 'iw_course_group', true);
            
            // Check if course group changed or is being set for first time
            if ($course_group !== $old_course_group) {
                $course_group_changed = true;
                update_user_meta($user_id, 'iw_course_group', $course_group);
                
                // If a course group is selected, assign role and enroll in courses
                if (!empty($course_group) && class_exists('IELTS_CM_Access_Codes')) {
                    // Get and validate expiry date
                    $iw_expiry_input = isset($_POST['iw_membership_expiry']) ? sanitize_text_field($_POST['iw_membership_expiry']) : '';
                    
                    if (!empty($iw_expiry_input)) {
                        // Validate date format (Y-m-d or Y-m-d H:i:s)
                        $timestamp = strtotime($iw_expiry_input);
                        if ($timestamp === false) {
                            // Invalid date format, use default
                            $iw_expiry = date('Y-m-d H:i:s', strtotime(self::DEFAULT_ACCESS_CODE_DURATION));
                        } else {
                            // Valid date, convert to end of day
                            $iw_expiry = date('Y-m-d', $timestamp) . ' 23:59:59';
                        }
                    } else {
                        // Use default duration if no expiry specified
                        $iw_expiry = date('Y-m-d H:i:s', strtotime(self::DEFAULT_ACCESS_CODE_DURATION));
                    }
                    
                    // Use the access codes class methods to properly set up the user
                    $access_codes = new IELTS_CM_Access_Codes();
                    
                    // Map course group to role
                    $role_mapping = array(
                        'academic_module' => 'access_academic_module',
                        'general_module' => 'access_general_module',
                        'general_english' => 'access_general_english'
                    );
                    
                    if (isset($role_mapping[$course_group])) {
                        $membership_type = $role_mapping[$course_group];
                        
                        // Set meta fields
                        update_user_meta($user_id, 'iw_membership_expiry', $iw_expiry);
                        update_user_meta($user_id, 'iw_membership_status', 'active');
                        update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
                        update_user_meta($user_id, '_ielts_cm_membership_status', 'active');
                        update_user_meta($user_id, '_ielts_cm_membership_expiry', $iw_expiry);
                        
                        // Assign WordPress role
                        $user = get_userdata($user_id);
                        if ($user) {
                            // Remove any existing access code membership roles first
                            $access_code_roles = array_keys(IELTS_CM_Access_Codes::ACCESS_CODE_MEMBERSHIP_TYPES);
                            foreach ($access_code_roles as $role_slug) {
                                $user->remove_role($role_slug);
                            }
                            // Add the new role
                            $user->add_role($membership_type);
                        }
                        
                        // Enroll user in courses based on course group
                        $access_codes->enroll_user_in_courses($user_id, $course_group);
                    }
                } elseif (empty($course_group)) {
                    // Course group cleared - remove role and clear all access code meta fields
                    $user = get_userdata($user_id);
                    if ($user && class_exists('IELTS_CM_Access_Codes')) {
                        $access_code_roles = array_keys(IELTS_CM_Access_Codes::ACCESS_CODE_MEMBERSHIP_TYPES);
                        foreach ($access_code_roles as $role_slug) {
                            $user->remove_role($role_slug);
                        }
                    }
                    // Clear all access code related meta fields
                    delete_user_meta($user_id, 'iw_membership_status');
                    delete_user_meta($user_id, 'iw_membership_expiry');
                    delete_user_meta($user_id, '_ielts_cm_membership_type');
                    delete_user_meta($user_id, '_ielts_cm_membership_status');
                    delete_user_meta($user_id, '_ielts_cm_membership_expiry');
                }
            }
        }
        
        // Only update expiry separately if course group didn't change
        // (to avoid overwriting the expiry that was just set above)
        if (isset($_POST['iw_membership_expiry']) && !$course_group_changed) {
            $iw_expiry_input = sanitize_text_field($_POST['iw_membership_expiry']);
            // Validate and convert from date format (Y-m-d) to MySQL datetime at end of day
            if (!empty($iw_expiry_input)) {
                $timestamp = strtotime($iw_expiry_input);
                if ($timestamp !== false) {
                    $iw_expiry = date('Y-m-d', $timestamp) . ' 23:59:59';
                    update_user_meta($user_id, 'iw_membership_expiry', $iw_expiry);
                }
            }
        }
        
        // Save course enrollments (legacy fields - maintained for backward compatibility)
        if (isset($_POST['enrolled_ielts_academic'])) {
            update_user_meta($user_id, 'enrolled_ielts_academic', 'true');
        } else {
            delete_user_meta($user_id, 'enrolled_ielts_academic');
        }
        
        if (isset($_POST['enrolled_ielts_general'])) {
            update_user_meta($user_id, 'enrolled_ielts_general', 'true');
        } else {
            delete_user_meta($user_id, 'enrolled_ielts_general');
        }
        
        if (isset($_POST['enrolled_general_english'])) {
            update_user_meta($user_id, 'enrolled_general_english', 'true');
        } else {
            delete_user_meta($user_id, 'enrolled_general_english');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Only add the membership menu if the system is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Main memberships menu
        add_menu_page(
            __('Memberships', 'ielts-course-manager'),
            __('Memberships', 'ielts-course-manager'),
            'manage_options',
            'ielts-memberships',
            array($this, 'memberships_page'),
            'dashicons-groups',
            30
        );
        
        // Memberships submenu (same as parent)
        add_submenu_page(
            'ielts-memberships',
            __('Current Memberships', 'ielts-course-manager'),
            __('Memberships', 'ielts-course-manager'),
            'manage_options',
            'ielts-memberships',
            array($this, 'memberships_page')
        );
        
        // Docs submenu
        add_submenu_page(
            'ielts-memberships',
            __('Membership Documentation', 'ielts-course-manager'),
            __('Docs', 'ielts-course-manager'),
            'manage_options',
            'ielts-membership-docs',
            array($this, 'docs_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'ielts-memberships',
            __('Membership Settings', 'ielts-course-manager'),
            __('Settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-membership-settings',
            array($this, 'settings_page')
        );
        
        // Courses submenu
        add_submenu_page(
            'ielts-memberships',
            __('Membership Courses', 'ielts-course-manager'),
            __('Courses', 'ielts-course-manager'),
            'manage_options',
            'ielts-membership-courses',
            array($this, 'courses_page')
        );
        
        // Payment Settings submenu
        add_submenu_page(
            'ielts-memberships',
            __('Payment Settings', 'ielts-course-manager'),
            __('Payment Settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-membership-payment',
            array($this, 'payment_settings_page')
        );
        
        // Email Templates submenu
        add_submenu_page(
            'ielts-memberships',
            __('Email Templates', 'ielts-course-manager'),
            __('Emails', 'ielts-course-manager'),
            'manage_options',
            'ielts-membership-emails',
            array($this, 'emails_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register membership settings
        register_setting('ielts_membership_settings', 'ielts_cm_membership_enabled');
        register_setting('ielts_membership_settings', 'ielts_cm_membership_course_mapping');
        register_setting('ielts_membership_settings', 'ielts_cm_membership_durations');
        register_setting('ielts_membership_settings', 'ielts_cm_full_member_page_url');
        register_setting('ielts_membership_settings', 'ielts_cm_post_payment_redirect_url');
        register_setting('ielts_membership_settings', 'ielts_cm_english_only_enabled');
        
        // Register payment settings
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_enabled');
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_publishable_key');
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_secret_key');
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_webhook_secret');
        register_setting('ielts_membership_payment', 'ielts_cm_paypal_enabled');
        register_setting('ielts_membership_payment', 'ielts_cm_paypal_client_id');
        register_setting('ielts_membership_payment', 'ielts_cm_paypal_secret');
        register_setting('ielts_membership_payment', 'ielts_cm_membership_pricing');
        register_setting('ielts_membership_payment', 'ielts_cm_extension_pricing');
        
        // Register email templates
        register_setting('ielts_membership_emails', 'ielts_cm_email_trial_enrollment');
        register_setting('ielts_membership_emails', 'ielts_cm_email_full_enrollment');
        register_setting('ielts_membership_emails', 'ielts_cm_email_trial_expired');
        register_setting('ielts_membership_emails', 'ielts_cm_email_full_expired');
        
        // Register email sender settings
        register_setting('ielts_membership_emails', 'ielts_cm_email_from_name');
        register_setting('ielts_membership_emails', 'ielts_cm_email_from_address');
    }
    
    /**
     * Display memberships list page
     */
    public function memberships_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Current Memberships', 'ielts-course-manager'); ?></h1>
            <p><?php _e('View and manage all user memberships.', 'ielts-course-manager'); ?></p>
            
            <?php
            // Get all users with memberships
            $users = get_users(array(
                'meta_query' => array(
                    array(
                        'key' => '_ielts_cm_membership_type',
                        'compare' => 'EXISTS'
                    )
                )
            ));
            
            if (empty($users)): ?>
                <p><?php _e('No memberships found.', 'ielts-course-manager'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Email', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Membership Type', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Expiry Date', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Actions', 'ielts-course-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user):
                            $membership_type = get_user_meta($user->ID, '_ielts_cm_membership_type', true);
                            $expiry_date = get_user_meta($user->ID, '_ielts_cm_membership_expiry', true);
                            $is_expired = !empty($expiry_date) && strtotime($expiry_date) < time();
                            $membership_name = isset(self::MEMBERSHIP_LEVELS[$membership_type]) 
                                ? self::MEMBERSHIP_LEVELS[$membership_type] 
                                : $membership_type;
                        ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html($membership_name); ?></td>
                                <td><?php echo $expiry_date ? esc_html($expiry_date) : __('Lifetime', 'ielts-course-manager'); ?></td>
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span style="color: #dc3232;"><?php _e('Expired', 'ielts-course-manager'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #46b450;"><?php _e('Active', 'ielts-course-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user->ID)); ?>" class="button button-small">
                                        <?php _e('Edit', 'ielts-course-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Display membership docs page
     */
    public function docs_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Membership Documentation', 'ielts-course-manager'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Overview', 'ielts-course-manager'); ?></h2>
                <p><?php _e('The membership system allows you to control access to courses based on user membership levels.', 'ielts-course-manager'); ?></p>
            </div>
            
            <div class="card">
                <h2><?php _e('Membership Levels', 'ielts-course-manager'); ?></h2>
                <ul>
                    <?php foreach (self::MEMBERSHIP_LEVELS as $key => $label): ?>
                        <li><strong><?php echo esc_html($label); ?></strong> (<?php echo esc_html($key); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="card">
                <h2><?php _e('Shortcodes', 'ielts-course-manager'); ?></h2>
                <ul>
                    <li><code>[ielts_login]</code> - <?php _e('Displays login form', 'ielts-course-manager'); ?></li>
                    <li><code>[ielts_registration]</code> - <?php _e('Displays registration form', 'ielts-course-manager'); ?></li>
                    <li><code>[ielts_account]</code> - <?php _e('Displays user account page with membership info', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
            
            <div class="card">
                <h2><?php _e('Managing Memberships', 'ielts-course-manager'); ?></h2>
                <p><?php _e('You can manage user memberships in two ways:', 'ielts-course-manager'); ?></p>
                <ol>
                    <li><?php _e('Go to Users → All Users and view the Membership column', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Edit individual users to set their membership type and expiry date', 'ielts-course-manager'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display membership settings page
     */
    public function settings_page() {
        // Handle manual check for expired memberships
        if (isset($_POST['check_expired']) && check_admin_referer('ielts_membership_manual_check')) {
            $this->check_and_update_expired_memberships();
            echo '<div class="notice notice-success"><p>' . __('Checked for expired memberships. See error logs for details.', 'ielts-course-manager') . '</p></div>';
        }
        
        if (isset($_POST['submit']) && check_admin_referer('ielts_membership_settings')) {
            update_option('ielts_cm_english_only_enabled', isset($_POST['ielts_cm_english_only_enabled']) ? 1 : 0);
            update_option('ielts_cm_full_member_page_url', sanitize_text_field($_POST['ielts_cm_full_member_page_url']));
            update_option('ielts_cm_post_payment_redirect_url', sanitize_text_field($_POST['ielts_cm_post_payment_redirect_url']));
            
            // Save duration settings
            $durations = array();
            foreach (self::MEMBERSHIP_LEVELS as $key => $label) {
                if (isset($_POST['duration_value_' . $key]) && isset($_POST['duration_unit_' . $key])) {
                    $durations[$key] = array(
                        'value' => absint($_POST['duration_value_' . $key]),
                        'unit' => sanitize_text_field($_POST['duration_unit_' . $key])
                    );
                }
            }
            update_option('ielts_cm_membership_durations', $durations);
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'ielts-course-manager') . '</p></div>';
        }
        
        $english_only_enabled = (bool) get_option('ielts_cm_english_only_enabled', false);
        $full_member_page_url = get_option('ielts_cm_full_member_page_url', '');
        $post_payment_redirect_url = get_option('ielts_cm_post_payment_redirect_url', '');
        $durations = get_option('ielts_cm_membership_durations', array());
        
        // Set default durations
        $default_durations = array(
            'academic_trial' => array('value' => 6, 'unit' => 'hours'),
            'general_trial' => array('value' => 6, 'unit' => 'hours'),
            'academic_full' => array('value' => 30, 'unit' => 'days'),
            'general_full' => array('value' => 30, 'unit' => 'days'),
            'academic_plus' => array('value' => 90, 'unit' => 'days'),
            'general_plus' => array('value' => 90, 'unit' => 'days'),
            'english_trial' => array('value' => 6, 'unit' => 'hours'),
            'english_full' => array('value' => 30, 'unit' => 'days')
        );
        
        // Merge with defaults
        foreach ($default_durations as $key => $default) {
            if (!isset($durations[$key])) {
                $durations[$key] = $default;
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Membership Settings', 'ielts-course-manager'); ?></h1>
            
            <!-- Manual Expiry Check -->
            <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                <h2><?php _e('Manual Expiry Check', 'ielts-course-manager'); ?></h2>
                <p><?php _e('This plugin checks for expired memberships automatically once per day. Use this button to manually trigger the check and send any pending expiry emails.', 'ielts-course-manager'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('ielts_membership_manual_check'); ?>
                    <button type="submit" name="check_expired" class="button button-secondary">
                        <?php _e('Check for Expired Memberships Now', 'ielts-course-manager'); ?>
                    </button>
                </form>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_membership_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable English Only Membership', 'ielts-course-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ielts_cm_english_only_enabled" value="1" <?php checked($english_only_enabled, 1); ?>>
                                <?php _e('Enable English Only membership option (in addition to Academic and General Training)', 'ielts-course-manager'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, users can sign up for English Only trial/full memberships. This is useful for sites that offer General English courses separately.', 'ielts-course-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Become a Full Member Page', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="url" name="ielts_cm_full_member_page_url" 
                                   value="<?php echo esc_attr($full_member_page_url); ?>" 
                                   class="regular-text" 
                                   placeholder="https://www.ieltstestonline.com/become-a-member">
                            <p class="description">
                                <?php _e('URL for users to upgrade to full membership (shown in trial countdown widget)', 'ielts-course-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Post Account Creation Redirect Page', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="url" name="ielts_cm_post_payment_redirect_url" 
                                   value="<?php echo esc_attr($post_payment_redirect_url); ?>" 
                                   class="regular-text" 
                                   placeholder="https://www.ieltstestonline.com/dashboard">
                            <p class="description">
                                <?php _e('Where to redirect users after successful account creation (both free trial and paid accounts). Users will be automatically logged in. Leave empty to use the default WordPress dashboard.', 'ielts-course-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Membership Durations', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Set the duration for each membership type.', 'ielts-course-manager'); ?></p>
                <table class="form-table">
                    <?php 
                    // Filter membership levels based on settings
                    $membership_levels_for_duration = self::MEMBERSHIP_LEVELS;
                    if (!$english_only_enabled) {
                        unset($membership_levels_for_duration['english_trial']);
                        unset($membership_levels_for_duration['english_full']);
                    }
                    foreach ($membership_levels_for_duration as $key => $label): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($label); ?></th>
                            <td>
                                <input type="number" min="1" step="1" 
                                       name="duration_value_<?php echo esc_attr($key); ?>" 
                                       value="<?php echo isset($durations[$key]['value']) ? esc_attr($durations[$key]['value']) : ''; ?>" 
                                       style="width: 80px;">
                                <select name="duration_unit_<?php echo esc_attr($key); ?>">
                                    <option value="minutes" <?php selected(isset($durations[$key]['unit']) ? $durations[$key]['unit'] : '', 'minutes'); ?>>
                                        <?php _e('Minutes', 'ielts-course-manager'); ?>
                                    </option>
                                    <option value="hours" <?php selected(isset($durations[$key]['unit']) ? $durations[$key]['unit'] : '', 'hours'); ?>>
                                        <?php _e('Hours', 'ielts-course-manager'); ?>
                                    </option>
                                    <option value="days" <?php selected(isset($durations[$key]['unit']) ? $durations[$key]['unit'] : '', 'days'); ?>>
                                        <?php _e('Days', 'ielts-course-manager'); ?>
                                    </option>
                                    <option value="weeks" <?php selected(isset($durations[$key]['unit']) ? $durations[$key]['unit'] : '', 'weeks'); ?>>
                                        <?php _e('Weeks', 'ielts-course-manager'); ?>
                                    </option>
                                    <option value="months" <?php selected(isset($durations[$key]['unit']) ? $durations[$key]['unit'] : '', 'months'); ?>>
                                        <?php _e('Months', 'ielts-course-manager'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display courses mapping page
     */
    public function courses_page() {
        if (isset($_POST['submit']) && check_admin_referer('ielts_membership_courses')) {
            $mapping = isset($_POST['course_membership']) ? $_POST['course_membership'] : array();
            update_option('ielts_cm_membership_course_mapping', $mapping);
            echo '<div class="notice notice-success"><p>' . __('Course mappings saved.', 'ielts-course-manager') . '</p></div>';
        }
        
        $mapping = get_option('ielts_cm_membership_course_mapping', array());
        $english_only_enabled = (bool) get_option('ielts_cm_english_only_enabled', false);
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Filter membership levels based on settings
        $membership_levels = self::MEMBERSHIP_LEVELS;
        if (!$english_only_enabled) {
            unset($membership_levels['english_trial']);
            unset($membership_levels['english_full']);
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Membership Courses', 'ielts-course-manager'); ?></h1>
            <p><?php _e('Select which courses are included in each membership level.', 'ielts-course-manager'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_membership_courses'); ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Course', 'ielts-course-manager'); ?></th>
                            <?php foreach ($membership_levels as $key => $label): ?>
                                <th><?php echo esc_html($label); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><strong><?php echo esc_html($course->post_title); ?></strong></td>
                                <?php foreach ($membership_levels as $key => $label): ?>
                                    <td>
                                        <input type="checkbox" 
                                               name="course_membership[<?php echo esc_attr($course->ID); ?>][]" 
                                               value="<?php echo esc_attr($key); ?>"
                                               <?php 
                                               if (isset($mapping[$course->ID]) && is_array($mapping[$course->ID]) && in_array($key, $mapping[$course->ID])) {
                                                   echo 'checked';
                                               }
                                               ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display payment settings page
     */
    public function payment_settings_page() {
        if (isset($_POST['submit']) && check_admin_referer('ielts_membership_payment')) {
            update_option('ielts_cm_stripe_enabled', isset($_POST['ielts_cm_stripe_enabled']) ? 1 : 0);
            // Note: In production, consider using environment variables or WordPress Secrets API for API keys
            update_option('ielts_cm_stripe_publishable_key', sanitize_text_field($_POST['ielts_cm_stripe_publishable_key']));
            update_option('ielts_cm_stripe_secret_key', sanitize_text_field($_POST['ielts_cm_stripe_secret_key']));
            update_option('ielts_cm_stripe_webhook_secret', sanitize_text_field($_POST['ielts_cm_stripe_webhook_secret']));
            update_option('ielts_cm_paypal_enabled', isset($_POST['ielts_cm_paypal_enabled']) ? 1 : 0);
            update_option('ielts_cm_paypal_client_id', sanitize_text_field($_POST['ielts_cm_paypal_client_id']));
            update_option('ielts_cm_paypal_secret', sanitize_text_field($_POST['ielts_cm_paypal_secret']));
            
            // Validate and save PayPal address
            if (isset($_POST['ielts_cm_paypal_address'])) {
                $paypal_address = sanitize_email($_POST['ielts_cm_paypal_address']);
                if (!empty($_POST['ielts_cm_paypal_address']) && !is_email($paypal_address)) {
                    // Show error for invalid email
                    echo '<div class="notice notice-error"><p>' . __('Invalid PayPal email address. Please enter a valid email address.', 'ielts-course-manager') . '</p></div>';
                    $paypal_address = ''; // Don't save invalid email
                }
                update_option('ielts_cm_paypal_address', $paypal_address);
            }
            
            $pricing = array();
            $errors = array();
            foreach (self::MEMBERSHIP_LEVELS as $key => $label) {
                if (isset($_POST['pricing_' . $key])) {
                    $price = floatval($_POST['pricing_' . $key]);
                    
                    // Validate that paid (non-trial) memberships have a price > 0
                    if (!self::is_trial_membership($key) && $price <= 0) {
                        $errors[] = sprintf(
                            __('%s must have a price greater than $0. Please set a price or users won\'t be able to purchase this membership.', 'ielts-course-manager'),
                            $label
                        );
                        // Set a default minimum price of $1 to prevent $0 paid memberships
                        $price = 1.00;
                    }
                    
                    $pricing[$key] = $price;
                }
            }
            update_option('ielts_cm_membership_pricing', $pricing);
            
            // Save and validate course extension pricing
            $extension_pricing = array();
            $extension_errors = array();
            
            if (isset($_POST['extension_1_week'])) {
                $price = floatval($_POST['extension_1_week']);
                if ($price <= 0) {
                    $extension_errors[] = __('1 Week Extension must have a price greater than $0.', 'ielts-course-manager');
                    $price = 5.00; // Default
                }
                $extension_pricing['1_week'] = $price;
            }
            if (isset($_POST['extension_1_month'])) {
                $price = floatval($_POST['extension_1_month']);
                if ($price <= 0) {
                    $extension_errors[] = __('1 Month Extension must have a price greater than $0.', 'ielts-course-manager');
                    $price = 10.00; // Default
                }
                $extension_pricing['1_month'] = $price;
            }
            if (isset($_POST['extension_3_months'])) {
                $price = floatval($_POST['extension_3_months']);
                if ($price <= 0) {
                    $extension_errors[] = __('3 Months Extension must have a price greater than $0.', 'ielts-course-manager');
                    $price = 15.00; // Default
                }
                $extension_pricing['3_months'] = $price;
            }
            update_option('ielts_cm_extension_pricing', $extension_pricing);
            
            if (!empty($errors)) {
                echo '<div class="notice notice-warning"><p><strong>' . __('Warning:', 'ielts-course-manager') . '</strong></p><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul><p>' . __('Paid memberships have been set to minimum price of $1.00. Please update to the correct price.', 'ielts-course-manager') . '</p></div>';
            }
            
            if (!empty($extension_errors)) {
                echo '<div class="notice notice-warning"><p><strong>' . __('Warning:', 'ielts-course-manager') . '</strong></p><ul>';
                foreach ($extension_errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul><p>' . __('Course extensions have been set to default prices. Please update to the correct price.', 'ielts-course-manager') . '</p></div>';
            }
            
            if (empty($errors) && empty($extension_errors)) {
                echo '<div class="notice notice-success"><p>' . __('Payment settings saved.', 'ielts-course-manager') . '</p></div>';
            }
        }
        
        $stripe_enabled = get_option('ielts_cm_stripe_enabled', false);
        $stripe_publishable = get_option('ielts_cm_stripe_publishable_key', '');
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        $stripe_webhook_secret = get_option('ielts_cm_stripe_webhook_secret', '');
        $paypal_enabled = get_option('ielts_cm_paypal_enabled', false);
        $paypal_client_id = get_option('ielts_cm_paypal_client_id', '');
        $paypal_secret = get_option('ielts_cm_paypal_secret', '');
        $paypal_address = get_option('ielts_cm_paypal_address', '');
        $pricing = get_option('ielts_cm_membership_pricing', array());
        
        // Get extension pricing with defaults
        $extension_pricing = get_option('ielts_cm_extension_pricing', array(
            '1_week' => 5.00,
            '1_month' => 10.00,
            '3_months' => 15.00
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('Payment Settings', 'ielts-course-manager'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_membership_payment'); ?>
                
                <h2><?php _e('Membership Pricing', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <?php foreach (self::MEMBERSHIP_LEVELS as $key => $label): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($label); ?></th>
                            <td>
                                <input type="number" step="0.01" min="0" 
                                       name="pricing_<?php echo esc_attr($key); ?>" 
                                       value="<?php echo isset($pricing[$key]) ? esc_attr($pricing[$key]) : '0'; ?>" 
                                       class="regular-text">
                                <p class="description"><?php _e('Price in USD (0 for free)', 'ielts-course-manager'); ?></p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <h2><?php _e('Course Extension Pricing', 'ielts-course-manager'); ?></h2>
                <p class="description"><?php _e('Set pricing for course extensions available to paid members. These options allow existing paid members to extend their course access.', 'ielts-course-manager'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('1 Week Extension', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" 
                                   name="extension_1_week" 
                                   value="<?php echo esc_attr($extension_pricing['1_week'] ?? 5.00); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Price in USD (default: $5)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('1 Month Extension', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" 
                                   name="extension_1_month" 
                                   value="<?php echo esc_attr($extension_pricing['1_month'] ?? 10.00); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Price in USD (default: $10)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('3 Months Extension', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" 
                                   name="extension_3_months" 
                                   value="<?php echo esc_attr($extension_pricing['3_months'] ?? 15.00); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Price in USD (default: $15)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Stripe Settings', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Stripe', 'ielts-course-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ielts_cm_stripe_enabled" value="1" <?php checked($stripe_enabled, 1); ?>>
                                <?php _e('Enable Stripe payment processing', 'ielts-course-manager'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Stripe Publishable Key', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_stripe_publishable_key" 
                                   value="<?php echo esc_attr($stripe_publishable); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Stripe Secret Key', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="password" name="ielts_cm_stripe_secret_key" 
                                   value="<?php echo esc_attr($stripe_secret); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Stripe Webhook Secret', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="password" name="ielts_cm_stripe_webhook_secret" 
                                   value="<?php echo esc_attr($stripe_webhook_secret); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Get this from Stripe Dashboard → Developers → Webhooks. Required for payment verification and automatic user creation.', 'ielts-course-manager'); ?><br>
                                <?php _e('Webhook URL:', 'ielts-course-manager'); ?> <code><?php echo esc_html(rest_url('ielts-cm/v1/stripe-webhook')); ?></code>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('PayPal Settings', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable PayPal', 'ielts-course-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ielts_cm_paypal_enabled" value="1" <?php checked($paypal_enabled, 1); ?>>
                                <?php _e('Enable PayPal payment processing', 'ielts-course-manager'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('PayPal Client ID', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_paypal_client_id" 
                                   value="<?php echo esc_attr($paypal_client_id); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('PayPal Secret', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="password" name="ielts_cm_paypal_secret" 
                                   value="<?php echo esc_attr($paypal_secret); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('PayPal Email Address', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="email" name="ielts_cm_paypal_address" 
                                   value="<?php echo esc_attr($paypal_address); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Your PayPal account email address for receiving payments', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get user membership type
     */
    public function get_user_membership($user_id) {
        return get_user_meta($user_id, '_ielts_cm_membership_type', true);
    }
    
    /**
     * Get user membership status
     * 
     * @param int $user_id User ID
     * @return string Status: 'active', 'expired', or 'none'
     */
    public function get_user_membership_status($user_id) {
        return get_user_meta($user_id, '_ielts_cm_membership_status', true) ?: self::STATUS_NONE;
    }
    
    /**
     * Set user membership status and sync WordPress role
     * 
     * @param int $user_id User ID
     * @param string $status Status to set: 'active', 'expired', or 'none'
     */
    public function set_user_membership_status($user_id, $status) {
        if (in_array($status, array(self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_NONE))) {
            update_user_meta($user_id, '_ielts_cm_membership_status', $status);
            
            // Sync WordPress role based on status
            $this->sync_user_role($user_id, $status);
        }
    }
    
    /**
     * Sync WordPress role with membership status
     * When active: assign membership role (e.g., academic_trial, general_full)
     * When expired/none: demote to subscriber
     * 
     * @param int $user_id User ID
     * @param string $status Current membership status
     */
    public function sync_user_role($user_id, $status) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        // Don't change admin roles
        if (in_array('administrator', $user->roles)) {
            return;
        }
        
        $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
        
        if ($status === self::STATUS_ACTIVE && !empty($membership_type)) {
            // Active membership: assign the membership role
            $user->set_role($membership_type);
        } else {
            // Expired or no membership: demote to subscriber
            $user->set_role('subscriber');
        }
    }
    
    /**
     * Check if a membership type is a trial membership
     * 
     * @param string $membership_type The membership type to check
     * @return bool True if it's a trial membership, false otherwise
     */
    public static function is_trial_membership($membership_type) {
        return substr($membership_type, -6) === '_trial';
    }
    
    /**
     * Get array of valid membership type keys
     * 
     * @return array Array of valid membership type keys
     */
    public static function get_valid_membership_types() {
        return array_keys(self::MEMBERSHIP_LEVELS);
    }
    
    /**
     * Check if user has access to course
     * Works for both Paid Membership and Access Code Membership systems
     */
    public function user_has_course_access($user_id, $course_id) {
        // First check if user has an access code membership role
        // Access code users don't use the membership meta fields, they use roles + enrollment table
        $user = get_userdata($user_id);
        if ($user && class_exists('IELTS_CM_Access_Codes')) {
            $access_code_roles = array_keys(IELTS_CM_Access_Codes::ACCESS_CODE_MEMBERSHIP_TYPES);
            foreach ($user->roles as $role) {
                if (in_array($role, $access_code_roles)) {
                    // Access code users rely on enrollment table, not course mapping
                    // Check their expiry via iw_membership_expiry meta
                    $expiry_date = get_user_meta($user_id, 'iw_membership_expiry', true);
                    if (!empty($expiry_date)) {
                        $expiry_timestamp = strtotime($expiry_date);
                        if ($expiry_timestamp <= time()) {
                            return false; // Expired - deny access
                        }
                    }
                    
                    // IMPORTANT: Return false to skip paid membership course mapping
                    // Access code users will be validated via enrollment table + role check in is_enrolled()
                    // This ensures they only access courses in their specific course group
                    return false;
                }
            }
        }
        
        // For paid membership users, continue with the original logic
        $membership_type = $this->get_user_membership($user_id);
        if (empty($membership_type)) {
            return false;
        }
        
        // Check membership status - if expired, deny access immediately
        $status = $this->get_user_membership_status($user_id);
        if ($status === self::STATUS_EXPIRED) {
            return false;
        }
        
        // Check if membership is expired by date (fallback for legacy data)
        $expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);
        if (!empty($expiry_date)) {
            // Expiry date is stored in UTC format, convert properly
            $expiry_timestamp = strtotime($expiry_date . ' UTC');
            $now_utc = time(); // Current UTC timestamp
            
            // Return false if membership has expired
            if ($expiry_timestamp <= $now_utc) {
                // Update status to expired if not already set
                if ($status !== self::STATUS_EXPIRED) {
                    $this->set_user_membership_status($user_id, self::STATUS_EXPIRED);
                }
                return false;
            }
        }
        
        // Check course mapping
        $mapping = get_option('ielts_cm_membership_course_mapping', array());
        if (isset($mapping[$course_id]) && is_array($mapping[$course_id])) {
            return in_array($membership_type, $mapping[$course_id]);
        }
        
        return false;
    }
    
    /**
     * Display email templates page
     */
    public function emails_page() {
        if (isset($_POST['submit']) && check_admin_referer('ielts_membership_emails')) {
            // Save email sender settings
            if (isset($_POST['ielts_cm_email_from_name'])) {
                update_option('ielts_cm_email_from_name', sanitize_text_field($_POST['ielts_cm_email_from_name']));
            }
            if (isset($_POST['ielts_cm_email_from_address'])) {
                $email_address = sanitize_email($_POST['ielts_cm_email_from_address']);
                // Validate email address and show error if invalid
                if (!empty($_POST['ielts_cm_email_from_address']) && empty($email_address)) {
                    echo '<div class="notice notice-error"><p>' . __('Invalid email address. Please enter a valid email address.', 'ielts-course-manager') . '</p></div>';
                } else {
                    update_option('ielts_cm_email_from_address', $email_address);
                }
            }
            
            // Save email templates
            $email_fields = array(
                'trial_enrollment' => array('subject', 'message'),
                'full_enrollment' => array('subject', 'message'),
                'trial_expired' => array('subject', 'message'),
                'full_expired' => array('subject', 'message')
            );
            
            foreach ($email_fields as $type => $fields) {
                $email_data = array();
                foreach ($fields as $field) {
                    $post_key = 'ielts_cm_email_' . $type . '_' . $field;
                    if (isset($_POST[$post_key])) {
                        $email_data[$field] = $field === 'message' ? wp_kses_post($_POST[$post_key]) : sanitize_text_field($_POST[$post_key]);
                    }
                }
                update_option('ielts_cm_email_' . $type, $email_data);
            }
            
            echo '<div class="notice notice-success"><p>' . __('Email templates saved.', 'ielts-course-manager') . '</p></div>';
        }
        
        // Get saved email templates or set defaults
        $trial_enrollment = get_option('ielts_cm_email_trial_enrollment', array(
            'subject' => 'Welcome to Your Free Trial!',
            'message' => 'Hi {username},

Welcome to your free trial of {membership_name}!

Your trial will expire on {expiry_date}.

To continue accessing all our courses after your trial ends, please upgrade to a full membership.

Best regards,
The IELTS Team'
        ));
        
        $full_enrollment = get_option('ielts_cm_email_full_enrollment', array(
            'subject' => 'Welcome to Your Full Membership!',
            'message' => 'Hi {username},

Welcome to {membership_name}!

You now have full access to all courses and features.

Your membership will expire on {expiry_date}.

Best regards,
The IELTS Team'
        ));
        
        $trial_expired = get_option('ielts_cm_email_trial_expired', array(
            'subject' => 'Your Trial Has Expired',
            'message' => 'Hi {username},

Your trial membership has expired.

To continue accessing our courses, please upgrade to a full membership.

Visit: {upgrade_url}

Best regards,
The IELTS Team'
        ));
        
        $full_expired = get_option('ielts_cm_email_full_expired', array(
            'subject' => 'Your Membership Has Expired',
            'message' => 'Hi {username},

Your membership has expired.

To renew your membership and continue accessing our courses, please visit your account page.

Visit: {renewal_url}

Best regards,
The IELTS Team'
        ));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Email Templates', 'ielts-course-manager'); ?></h1>
            <p><?php _e('Configure the default emails sent to users for different membership events.', 'ielts-course-manager'); ?></p>
            <p><?php _e('Available placeholders:', 'ielts-course-manager'); ?></p>
            <ul>
                <li><code>{username}</code> - <?php _e('User\'s display name', 'ielts-course-manager'); ?></li>
                <li><code>{email}</code> - <?php _e('User\'s email address', 'ielts-course-manager'); ?></li>
                <li><code>{membership_name}</code> - <?php _e('Name of the membership plan', 'ielts-course-manager'); ?></li>
                <li><code>{expiry_date}</code> - <?php _e('Membership expiry date', 'ielts-course-manager'); ?></li>
                <li><code>{upgrade_url}</code> - <?php _e('URL to upgrade membership', 'ielts-course-manager'); ?></li>
                <li><code>{renewal_url}</code> - <?php _e('URL to renew membership', 'ielts-course-manager'); ?></li>
                <li><code>{videos_completed}</code> - <?php _e('Number of videos watched', 'ielts-course-manager'); ?></li>
                <li><code>{total_videos}</code> - <?php _e('Total number of videos available', 'ielts-course-manager'); ?></li>
                <li><code>{exercises_completed}</code> - <?php _e('Number of exercises completed', 'ielts-course-manager'); ?></li>
                <li><code>{total_exercises}</code> - <?php _e('Total number of exercises available', 'ielts-course-manager'); ?></li>
                <li><code>{tests_completed}</code> - <?php _e('Number of practice tests completed', 'ielts-course-manager'); ?></li>
                <li><code>{total_tests}</code> - <?php _e('Total number of practice tests available', 'ielts-course-manager'); ?></li>
                <li><code>{band_score}</code> - <?php _e('Current estimated overall band score', 'ielts-course-manager'); ?></li>
            </ul>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_membership_emails'); ?>
                
                <h2><?php _e('Email Sender Settings', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('From Name', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_email_from_name" 
                                   value="<?php echo esc_attr(get_option('ielts_cm_email_from_name', get_bloginfo('name'))); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('The name that will appear in the "From" field of emails (e.g., "IELTS Team"). Leave blank to use your site name.', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('From Email Address', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="email" name="ielts_cm_email_from_address" 
                                   value="<?php echo esc_attr(get_option('ielts_cm_email_from_address', get_option('admin_email'))); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('The email address that will appear in the "From" field of emails. Leave blank to use your WordPress admin email.', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('New Trial Enrollment', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_email_trial_enrollment_subject" 
                                   value="<?php echo esc_attr($trial_enrollment['subject']); ?>" 
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'ielts-course-manager'); ?></th>
                        <td>
                            <textarea name="ielts_cm_email_trial_enrollment_message" 
                                      rows="8" class="large-text"><?php echo esc_textarea($trial_enrollment['message']); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Full Membership Enrollment', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_email_full_enrollment_subject" 
                                   value="<?php echo esc_attr($full_enrollment['subject']); ?>" 
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'ielts-course-manager'); ?></th>
                        <td>
                            <textarea name="ielts_cm_email_full_enrollment_message" 
                                      rows="8" class="large-text"><?php echo esc_textarea($full_enrollment['message']); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Trial Course Expired', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_email_trial_expired_subject" 
                                   value="<?php echo esc_attr($trial_expired['subject']); ?>" 
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'ielts-course-manager'); ?></th>
                        <td>
                            <textarea name="ielts_cm_email_trial_expired_message" 
                                      rows="8" class="large-text"><?php echo esc_textarea($trial_expired['message']); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Full Membership Expired', 'ielts-course-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_email_full_expired_subject" 
                                   value="<?php echo esc_attr($full_expired['subject']); ?>" 
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'ielts-course-manager'); ?></th>
                        <td>
                            <textarea name="ielts_cm_email_full_expired_message" 
                                      rows="8" class="large-text"><?php echo esc_textarea($full_expired['message']); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Send enrollment email
     */
    public function send_enrollment_email($user_id, $membership_type) {
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("IELTS Course Manager: Cannot send enrollment email - user {$user_id} not found");
            return;
        }
        
        $is_trial = self::is_trial_membership($membership_type);
        $template_key = $is_trial ? 'trial_enrollment' : 'full_enrollment';
        $template = get_option('ielts_cm_email_' . $template_key, array());
        
        // If no template exists, use default
        if (empty($template) || empty($template['subject']) || empty($template['message'])) {
            error_log("IELTS Course Manager: No email template configured for {$template_key}, using default");
            
            // Set default templates
            if ($is_trial) {
                $template = array(
                    'subject' => 'Welcome to Your Free Trial!',
                    'message' => 'Hi {username},

Welcome to your free trial of {membership_name}!

Your trial will expire on {expiry_date}.

To continue accessing all our courses after your trial ends, please upgrade to a full membership.

Best regards,
The IELTS Team'
                );
            } else {
                $template = array(
                    'subject' => 'Welcome to Your Full Membership!',
                    'message' => 'Hi {username},

Welcome to {membership_name}!

You now have full access to all our courses.

Best regards,
The IELTS Team'
                );
            }
            
            // Save the default template for future use
            update_option('ielts_cm_email_' . $template_key, $template);
        }
        
        $membership_name = isset(self::MEMBERSHIP_LEVELS[$membership_type]) 
            ? self::MEMBERSHIP_LEVELS[$membership_type] 
            : $membership_type;
        
        $expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);
        $upgrade_url = get_option('ielts_cm_full_member_page_url', home_url());
        
        // Calculate progress statistics
        global $wpdb;
        
        // Count videos watched (lessons/resources viewed)
        $videos_completed = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->usermeta} 
            WHERE user_id = %d 
            AND (meta_key LIKE '_ielts_cm_lesson_%%_viewed' OR meta_key LIKE '_ielts_cm_resource_%%_viewed')
        ", $user_id));
        
        // Count total videos available (lessons + resources)
        $total_videos = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type IN ('ielts_lesson', 'ielts_resource') 
            AND post_status = 'publish'
        ");
        
        // Count exercises completed (quizzes submitted)
        $exercises_completed = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT quiz_id) 
            FROM {$wpdb->prefix}ielts_cm_quiz_submissions 
            WHERE user_id = %d
        ", $user_id));
        
        // Count total exercises available
        $total_exercises = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'ielts_quiz' 
            AND post_status = 'publish'
        ");
        
        // Count practice tests completed (quizzes with 'practice' or 'test' in category)
        $tests_completed = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT qs.quiz_id)
            FROM {$wpdb->prefix}ielts_cm_quiz_submissions qs
            INNER JOIN {$wpdb->posts} p ON qs.quiz_id = p.ID
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE qs.user_id = %d
            AND tt.taxonomy = 'ielts_quiz_category'
            AND (t.slug LIKE '%%practice%%' OR t.slug LIKE '%%test%%')
        ", $user_id));
        
        // Count total practice tests available
        $total_tests = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'ielts_quiz'
            AND p.post_status = 'publish'
            AND tt.taxonomy = 'ielts_quiz_category'
            AND (t.slug LIKE '%%practice%%' OR t.slug LIKE '%%test%%')
        ");
        
        // Calculate overall band score
        $gamification = new IELTS_CM_Gamification();
        $skill_scores = $gamification->get_user_skill_scores($user_id);
        $total_score = 0;
        $score_count = 0;
        foreach ($skill_scores as $skill => $percentage) {
            if ($percentage > 0) {
                // Convert percentage to band score
                $band = $this->convert_percentage_to_band($percentage);
                $total_score += $band;
                $score_count++;
            }
        }
        $overall_band = $score_count > 0 ? round(($total_score / $score_count) * 2) / 2 : 0;
        $band_score_text = $overall_band > 0 ? number_format($overall_band, 1) : 'N/A';
        
        // Replace placeholders
        $placeholders = array(
            '{username}' => $user->display_name,
            '{email}' => $user->user_email,
            '{membership_name}' => $membership_name,
            '{expiry_date}' => $expiry_date ? $expiry_date : 'N/A',
            '{upgrade_url}' => $upgrade_url,
            '{renewal_url}' => $upgrade_url,
            '{videos_completed}' => $videos_completed ?: 0,
            '{total_videos}' => $total_videos ?: 0,
            '{exercises_completed}' => $exercises_completed ?: 0,
            '{total_exercises}' => $total_exercises ?: 0,
            '{tests_completed}' => $tests_completed ?: 0,
            '{total_tests}' => $total_tests ?: 0,
            '{band_score}' => $band_score_text
        );
        
        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $template['subject']);
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template['message']);
        
        // Send email and log failures
        $result = wp_mail($user->user_email, $subject, $message);
        if (!$result) {
            error_log(sprintf('IELTS Course Manager: Failed to send enrollment email to user %d (%s) for membership type %s', 
                $user_id, $user->user_email, $membership_type));
        } else {
            error_log(sprintf('IELTS Course Manager: Successfully sent enrollment email to user %d (%s) for membership type %s', 
                $user_id, $user->user_email, $membership_type));
        }
    }
    
    /**
     * Calculate expiry date based on membership duration settings
     * 
     * @param string $membership_type The membership type key
     * @return string Expiry date in 'Y-m-d H:i:s' format (UTC)
     */
    public function calculate_expiry_date($membership_type) {
        $durations = get_option('ielts_cm_membership_durations', array());
        
        if (!isset($durations[$membership_type])) {
            // Fallback to defaults
            if (self::is_trial_membership($membership_type)) {
                $value = 6;
                $unit = 'hours';
            } else {
                $value = 30;
                $unit = 'days';
            }
        } else {
            $value = $durations[$membership_type]['value'];
            $unit = $durations[$membership_type]['unit'];
        }
        
        // Use UTC time consistently to avoid timezone issues
        $current_utc = gmdate('Y-m-d H:i:s');
        $current_timestamp = strtotime($current_utc);
        
        switch ($unit) {
            case 'minutes':
                $expiry_timestamp = strtotime("+{$value} minutes", $current_timestamp);
                break;
            case 'hours':
                $expiry_timestamp = strtotime("+{$value} hours", $current_timestamp);
                break;
            case 'days':
                $expiry_timestamp = strtotime("+{$value} days", $current_timestamp);
                break;
            case 'weeks':
                $expiry_timestamp = strtotime("+{$value} weeks", $current_timestamp);
                break;
            case 'months':
                $expiry_timestamp = strtotime("+{$value} months", $current_timestamp);
                break;
            default:
                $expiry_timestamp = strtotime("+30 days", $current_timestamp);
        }
        
        return gmdate('Y-m-d H:i:s', $expiry_timestamp);
    }
    
    /**
     * Check and update expired memberships
     * This function is called by a daily cron job
     * 
     * IMPORTANT: This runs even if membership system is disabled because
     * the enrollment check enforces expiry dates regardless of system status.
     * This ensures users get expiry emails and status updates.
     */
    public function check_and_update_expired_memberships() {
        global $wpdb;
        
        error_log("IELTS Course Manager: Starting expired membership check");
        
        // Get all users with memberships
        $users = get_users(array(
            'meta_key' => '_ielts_cm_membership_type',
            'meta_compare' => 'EXISTS'
        ));
        
        error_log("IELTS Course Manager: Found " . count($users) . " users with memberships");
        
        $now_utc = time();
        $updated_count = 0;
        
        foreach ($users as $user) {
            $membership_type = get_user_meta($user->ID, '_ielts_cm_membership_type', true);
            $expiry_date = get_user_meta($user->ID, '_ielts_cm_membership_expiry', true);
            $current_status = get_user_meta($user->ID, '_ielts_cm_membership_status', true);
            
            // Skip if no membership or already marked as expired
            if (empty($membership_type) || $current_status === self::STATUS_EXPIRED) {
                continue;
            }
            
            // Check if membership has expired
            if (!empty($expiry_date)) {
                // Validate expiry date format before processing
                $expiry_timestamp = strtotime($expiry_date . ' UTC');
                
                // Skip if invalid date format
                if ($expiry_timestamp === false) {
                    error_log("IELTS Course Manager: Invalid expiry date format for user {$user->ID}: {$expiry_date}");
                    continue;
                }
                
                if ($expiry_timestamp <= $now_utc) {
                    // Send expiry notification email before updating status
                    // Check if email has already been sent
                    $email_sent = get_user_meta($user->ID, '_ielts_cm_expiry_email_sent', true);
                    
                    if (!$email_sent) {
                        if (self::is_trial_membership($membership_type)) {
                            $this->send_expiry_email($user->ID, $membership_type, 'trial');
                        } else {
                            $this->send_expiry_email($user->ID, $membership_type, 'full');
                        }
                        
                        // Mark email as sent
                        update_user_meta($user->ID, '_ielts_cm_expiry_email_sent', time());
                    }
                    
                    // Membership has expired, update status
                    $this->set_user_membership_status($user->ID, self::STATUS_EXPIRED);
                    $updated_count++;
                }
            }
        }
        
        // Log the update if any memberships were expired
        if ($updated_count > 0) {
            error_log("IELTS Course Manager: Updated {$updated_count} expired memberships");
        }
    }
    
    /**
     * Fallback check for expired memberships on user access
     * This catches memberships that expired between cron runs
     * Only checks the current user to avoid performance issues
     * Rate limited to once per hour per user
     */
    public function check_expired_on_access() {
        // Only check for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Rate limiting: Only check once per hour per user to avoid excessive queries
        $last_check = get_user_meta($user_id, '_ielts_cm_last_expiry_check', true);
        if ($last_check && (time() - intval($last_check)) < HOUR_IN_SECONDS) {
            return; // Already checked within the last hour
        }
        
        // Update last check time
        update_user_meta($user_id, '_ielts_cm_last_expiry_check', time());
        
        $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
        $expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);
        $current_status = get_user_meta($user_id, '_ielts_cm_membership_status', true);
        
        // Skip if no membership or already marked as expired
        if (empty($membership_type) || $current_status === self::STATUS_EXPIRED) {
            return;
        }
        
        // Check if membership has expired
        if (!empty($expiry_date)) {
            $expiry_timestamp = strtotime($expiry_date . ' UTC');
            $now_utc = time();
            
            // Skip if invalid date format
            if ($expiry_timestamp === false) {
                return;
            }
            
            if ($expiry_timestamp <= $now_utc) {
                // Send expiry notification email before updating status
                $email_sent = get_user_meta($user_id, '_ielts_cm_expiry_email_sent', true);
                
                if (!$email_sent) {
                    if (self::is_trial_membership($membership_type)) {
                        $this->send_expiry_email($user_id, $membership_type, 'trial');
                    } else {
                        $this->send_expiry_email($user_id, $membership_type, 'full');
                    }
                    
                    // Mark email as sent
                    update_user_meta($user_id, '_ielts_cm_expiry_email_sent', time());
                    error_log("IELTS Course Manager: Sent expiry email to user {$user_id} via fallback check");
                }
                
                // Membership has expired, update status
                $this->set_user_membership_status($user_id, self::STATUS_EXPIRED);
                error_log("IELTS Course Manager: Updated user {$user_id} to expired status via fallback check");
            }
        }
    }
    
    /**
     * Send expiry notification email
     * 
     * @param int $user_id User ID
     * @param string $membership_type Membership type
     * @param string $type 'trial' or 'full'
     */
    private function send_expiry_email($user_id, $membership_type, $type) {
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("IELTS Course Manager: Cannot send expiry email - user {$user_id} not found");
            return;
        }
        
        $email_type = $type === 'trial' ? 'trial_expired' : 'full_expired';
        $email_template = get_option('ielts_cm_email_' . $email_type, array());
        
        // If no template exists, use default
        if (empty($email_template) || empty($email_template['subject']) || empty($email_template['message'])) {
            error_log("IELTS Course Manager: No email template configured for {$email_type}, using default");
            
            // Set default templates
            if ($type === 'trial') {
                $email_template = array(
                    'subject' => 'Your Trial Has Expired',
                    'message' => 'Hi {username},

Your trial membership for {membership_name} has expired.

To continue accessing our courses, please upgrade to a full membership.

Visit: {upgrade_url}

Best regards,
The IELTS Team'
                );
            } else {
                $email_template = array(
                    'subject' => 'Your Membership Has Expired',
                    'message' => 'Hi {username},

Your membership for {membership_name} has expired.

To renew your membership and continue accessing our courses, please visit your account page.

Visit: {upgrade_url}

Best regards,
The IELTS Team'
                );
            }
            
            // Save the default template for future use
            update_option('ielts_cm_email_' . $email_type, $email_template);
        }
        
        // Replace placeholders
        $membership_name = isset(self::MEMBERSHIP_LEVELS[$membership_type]) 
            ? self::MEMBERSHIP_LEVELS[$membership_type] 
            : $membership_type;
        
        $upgrade_url = get_option('ielts_cm_full_member_page_url', home_url());
        
        $subject = $email_template['subject'];
        $message = str_replace(
            array('{username}', '{membership_name}', '{upgrade_url}', '{renewal_url}'),
            array($user->display_name, $membership_name, $upgrade_url, $upgrade_url),
            $email_template['message']
        );
        
        // Send email and log failures
        $sent = wp_mail($user->user_email, $subject, $message);
        if (!$sent) {
            error_log("IELTS Course Manager: Failed to send {$type} expiry email to user {$user_id} ({$user->user_email})");
        } else {
            error_log("IELTS Course Manager: Successfully sent {$type} expiry email to user {$user_id} ({$user->user_email})");
        }
    }
    
    /**
     * Convert percentage score to IELTS band score
     * 
     * @param float $percentage Percentage score
     * @return float Band score (0.5 to 9.0)
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
