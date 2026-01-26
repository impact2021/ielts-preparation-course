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
        'academic_full' => 'Academic Module Full Membership',
        'general_full' => 'General Training Full Membership'
    );
    
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
     * Initialize membership functionality
     */
    public function init() {
        // Always add admin menu and register settings so users can enable/disable the system
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Schedule daily cron job to check for expired memberships
        if (!wp_next_scheduled('ielts_cm_check_expired_memberships')) {
            wp_schedule_event(time(), 'daily', 'ielts_cm_check_expired_memberships');
        }
        add_action('ielts_cm_check_expired_memberships', array($this, 'check_and_update_expired_memberships'));
        
        // Only initialize other features if membership system is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Add user columns
        add_filter('manage_users_columns', array($this, 'add_user_columns'));
        add_filter('manage_users_custom_column', array($this, 'user_column_content'), 10, 3);
        
        // Add user edit fields
        add_action('show_user_profile', array($this, 'user_membership_fields'));
        add_action('edit_user_profile', array($this, 'user_membership_fields'));
        add_action('personal_options_update', array($this, 'save_user_membership_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_membership_fields'));
    }
    
    /**
     * Check if membership system is enabled
     */
    public function is_enabled() {
        return get_option('ielts_cm_membership_enabled', false);
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
        ?>
        <h2><?php _e('Membership Information', 'ielts-course-manager'); ?></h2>
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
                    update_user_meta($user_id, '_ielts_cm_membership_status', self::STATUS_ACTIVE);
                    // Clear expiry email tracking when setting to active
                    delete_user_meta($user_id, '_ielts_cm_expiry_email_sent');
                } else {
                    // Expiry is in the past, set as expired
                    update_user_meta($user_id, '_ielts_cm_membership_status', self::STATUS_EXPIRED);
                }
            } else {
                // No membership type, set status to none
                update_user_meta($user_id, '_ielts_cm_membership_status', self::STATUS_NONE);
                // Clear expiry email tracking when no membership
                delete_user_meta($user_id, '_ielts_cm_expiry_email_sent');
            }
        }
        
        if (isset($_POST['ielts_cm_membership_expiry'])) {
            update_user_meta($user_id, '_ielts_cm_membership_expiry', sanitize_text_field($_POST['ielts_cm_membership_expiry']));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
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
        
        // Register payment settings
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_enabled');
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_publishable_key');
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_secret_key');
        register_setting('ielts_membership_payment', 'ielts_cm_stripe_webhook_secret');
        register_setting('ielts_membership_payment', 'ielts_cm_paypal_enabled');
        register_setting('ielts_membership_payment', 'ielts_cm_paypal_client_id');
        register_setting('ielts_membership_payment', 'ielts_cm_paypal_secret');
        register_setting('ielts_membership_payment', 'ielts_cm_membership_pricing');
        
        // Register email templates
        register_setting('ielts_membership_emails', 'ielts_cm_email_trial_enrollment');
        register_setting('ielts_membership_emails', 'ielts_cm_email_full_enrollment');
        register_setting('ielts_membership_emails', 'ielts_cm_email_trial_expired');
        register_setting('ielts_membership_emails', 'ielts_cm_email_full_expired');
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
        if (isset($_POST['submit']) && check_admin_referer('ielts_membership_settings')) {
            update_option('ielts_cm_membership_enabled', isset($_POST['ielts_cm_membership_enabled']) ? 1 : 0);
            update_option('ielts_cm_full_member_page_url', sanitize_text_field($_POST['ielts_cm_full_member_page_url']));
            
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
        
        $enabled = get_option('ielts_cm_membership_enabled', false);
        $full_member_page_url = get_option('ielts_cm_full_member_page_url', '');
        $durations = get_option('ielts_cm_membership_durations', array());
        
        // Set default durations
        $default_durations = array(
            'academic_trial' => array('value' => 6, 'unit' => 'hours'),
            'general_trial' => array('value' => 6, 'unit' => 'hours'),
            'academic_full' => array('value' => 30, 'unit' => 'days'),
            'general_full' => array('value' => 30, 'unit' => 'days')
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
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_membership_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Membership System', 'ielts-course-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ielts_cm_membership_enabled" value="1" <?php checked($enabled, 1); ?>>
                                <?php _e('Enable the membership system (disable if using external membership system)', 'ielts-course-manager'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When disabled, all membership features will be hidden. Use this if your site has its own membership system.', 'ielts-course-manager'); ?>
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
                </table>
                
                <h2><?php _e('Membership Durations', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Set the duration for each membership type.', 'ielts-course-manager'); ?></p>
                <table class="form-table">
                    <?php foreach (self::MEMBERSHIP_LEVELS as $key => $label): ?>
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
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
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
                            <?php foreach (self::MEMBERSHIP_LEVELS as $key => $label): ?>
                                <th><?php echo esc_html($label); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><strong><?php echo esc_html($course->post_title); ?></strong></td>
                                <?php foreach (self::MEMBERSHIP_LEVELS as $key => $label): ?>
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
            
            $pricing = array();
            foreach (self::MEMBERSHIP_LEVELS as $key => $label) {
                if (isset($_POST['pricing_' . $key])) {
                    $pricing[$key] = floatval($_POST['pricing_' . $key]);
                }
            }
            update_option('ielts_cm_membership_pricing', $pricing);
            
            echo '<div class="notice notice-success"><p>' . __('Payment settings saved.', 'ielts-course-manager') . '</p></div>';
        }
        
        $stripe_enabled = get_option('ielts_cm_stripe_enabled', false);
        $stripe_publishable = get_option('ielts_cm_stripe_publishable_key', '');
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        $stripe_webhook_secret = get_option('ielts_cm_stripe_webhook_secret', '');
        $paypal_enabled = get_option('ielts_cm_paypal_enabled', false);
        $paypal_client_id = get_option('ielts_cm_paypal_client_id', '');
        $paypal_secret = get_option('ielts_cm_paypal_secret', '');
        $pricing = get_option('ielts_cm_membership_pricing', array());
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
     * Set user membership status
     * 
     * @param int $user_id User ID
     * @param string $status Status to set: 'active', 'expired', or 'none'
     */
    public function set_user_membership_status($user_id, $status) {
        if (in_array($status, array(self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_NONE))) {
            update_user_meta($user_id, '_ielts_cm_membership_status', $status);
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
     */
    public function user_has_course_access($user_id, $course_id) {
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
            <p><?php _e('Available placeholders: {username}, {email}, {membership_name}, {expiry_date}, {upgrade_url}, {renewal_url}', 'ielts-course-manager'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_membership_emails'); ?>
                
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
        if (empty($template['subject']) || empty($template['message'])) {
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
        
        // Replace placeholders
        $placeholders = array(
            '{username}' => $user->display_name,
            '{email}' => $user->user_email,
            '{membership_name}' => $membership_name,
            '{expiry_date}' => $expiry_date ? $expiry_date : 'N/A',
            '{upgrade_url}' => $upgrade_url,
            '{renewal_url}' => $upgrade_url
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
     */
    public function check_and_update_expired_memberships() {
        global $wpdb;
        
        // Get all users with memberships
        $users = get_users(array(
            'meta_key' => '_ielts_cm_membership_type',
            'meta_compare' => 'EXISTS'
        ));
        
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
        if (empty($email_template['subject']) || empty($email_template['message'])) {
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
            array('{username}', '{membership_name}', '{upgrade_url}'),
            array($user->display_name, $membership_name, $upgrade_url),
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
}
