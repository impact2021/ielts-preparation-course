<?php
/**
 * User Tours Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Tours_Page {
    
    /**
     * Add admin menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Tours', 'ielts-course-manager'),
            __('Tours', 'ielts-course-manager'),
            'manage_options',
            'ielts-cm-tours',
            array($this, 'render_page')
        );
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submit() {
        if (!isset($_POST['ielts_cm_tours_action'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!isset($_POST['ielts_cm_tours_nonce']) || !wp_verify_nonce($_POST['ielts_cm_tours_nonce'], 'ielts_cm_tours_settings')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['ielts_cm_tours_action']);
        
        switch ($action) {
            case 'update_settings':
                $this->handle_update_settings();
                break;
            case 'run_tour':
                $this->handle_run_tour();
                break;
            case 'reset_all_tours':
                $this->handle_reset_all_tours();
                break;
        }
    }
    
    /**
     * Update tour settings
     */
    private function handle_update_settings() {
        // Global enable/disable
        $tours_enabled = isset($_POST['tours_enabled']) ? 1 : 0;
        update_option('ielts_cm_tours_enabled', $tours_enabled);
        
        // Membership-specific settings
        $membership_levels = array(
            'academic_trial',
            'general_trial',
            'academic_full',
            'general_full',
            'english_trial',
            'english_full'
        );
        
        $enabled_for_memberships = array();
        foreach ($membership_levels as $level) {
            if (isset($_POST['tour_enabled_' . $level])) {
                $enabled_for_memberships[] = $level;
            }
        }
        
        update_option('ielts_cm_tours_enabled_memberships', $enabled_for_memberships);
        
        add_settings_error('ielts_cm_tours', 'settings_updated', 'Tour settings updated successfully', 'success');
    }
    
    /**
     * Run tour as admin
     */
    private function handle_run_tour() {
        $user_id = get_current_user_id();
        if ($user_id) {
            // Clear tour completion flag for current admin user
            delete_user_meta($user_id, 'ielts_tour_completed');
            add_settings_error('ielts_cm_tours', 'tour_reset', 'Tour reset for your account. Refresh any page to see the tour.', 'success');
        }
    }
    
    /**
     * Reset tours for all users
     */
    private function handle_reset_all_tours() {
        global $wpdb;
        
        // Delete tour completion meta for all users
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => 'ielts_tour_completed')
        );
        
        add_settings_error('ielts_cm_tours', 'tours_reset', 'Tours reset for all users. All users will see the tour on their next page load.', 'success');
    }
    
    /**
     * Render the admin page
     */
    public function render_page() {
        $tours_enabled = get_option('ielts_cm_tours_enabled', true);
        $enabled_memberships = get_option('ielts_cm_tours_enabled_memberships', array());
        
        // All membership levels
        $membership_levels = array(
            'academic_trial' => 'Academic Module - Free Trial',
            'general_trial' => 'General Training - Free Trial',
            'academic_full' => 'Academic Module IELTS',
            'general_full' => 'General Module IELTS',
            'english_trial' => 'English Only - Free Trial',
            'english_full' => 'English Only Full Membership'
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('User Tours', 'ielts-course-manager'); ?></h1>
            
            <?php settings_errors('ielts_cm_tours'); ?>
            
            <div class="ielts-tours-container">
                <div class="card" style="max-width: 800px;">
                    <h2><?php _e('Tour Settings', 'ielts-course-manager'); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('ielts_cm_tours_settings', 'ielts_cm_tours_nonce'); ?>
                        <input type="hidden" name="ielts_cm_tours_action" value="update_settings" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="tours_enabled"><?php _e('Enable Tours', 'ielts-course-manager'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="tours_enabled" 
                                               id="tours_enabled" 
                                               value="1" 
                                               <?php checked($tours_enabled, 1); ?> />
                                        <?php _e('Show guided tours to first-time users', 'ielts-course-manager'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('When enabled, users who haven\'t completed the tour will see it automatically.', 'ielts-course-manager'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <?php _e('Enable for Memberships', 'ielts-course-manager'); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <span><?php _e('Enable tours for specific membership types', 'ielts-course-manager'); ?></span>
                                        </legend>
                                        <?php foreach ($membership_levels as $level_key => $level_name) : ?>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input type="checkbox" 
                                                       name="tour_enabled_<?php echo esc_attr($level_key); ?>" 
                                                       value="1" 
                                                       <?php checked(in_array($level_key, $enabled_memberships), true); ?> />
                                                <?php echo esc_html($level_name); ?>
                                            </label>
                                        <?php endforeach; ?>
                                        <p class="description">
                                            <?php _e('Select which membership types should see the tour. If no memberships are selected, the tour will be shown to all users when enabled.', 'ielts-course-manager'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" 
                                   class="button button-primary" 
                                   value="<?php esc_attr_e('Save Settings', 'ielts-course-manager'); ?>" />
                        </p>
                    </form>
                </div>
                
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Testing & Management', 'ielts-course-manager'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e('Test Tour', 'ielts-course-manager'); ?>
                            </th>
                            <td>
                                <form method="post" action="" style="display: inline;">
                                    <?php wp_nonce_field('ielts_cm_tours_settings', 'ielts_cm_tours_nonce'); ?>
                                    <input type="hidden" name="ielts_cm_tours_action" value="run_tour" />
                                    <input type="submit" 
                                           class="button" 
                                           value="<?php esc_attr_e('Run Tour as Admin', 'ielts-course-manager'); ?>" />
                                </form>
                                <p class="description">
                                    <?php _e('Reset the tour completion flag for your account so you can test the tour. After clicking, refresh any page to see the tour.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e('Reset All Tours', 'ielts-course-manager'); ?>
                            </th>
                            <td>
                                <form method="post" action="" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to reset tours for all users? This will make all users see the tour again on their next page load.', 'ielts-course-manager'); ?>');">
                                    <?php wp_nonce_field('ielts_cm_tours_settings', 'ielts_cm_tours_nonce'); ?>
                                    <input type="hidden" name="ielts_cm_tours_action" value="reset_all_tours" />
                                    <input type="submit" 
                                           class="button button-secondary" 
                                           value="<?php esc_attr_e('Reset for All Users', 'ielts-course-manager'); ?>" />
                                </form>
                                <p class="description">
                                    <?php _e('Reset tour completion for all users. Use this if you\'ve updated the tour and want all users to see it again.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Tour Information', 'ielts-course-manager'); ?></h2>
                    <p><?php _e('The user tour is a guided walkthrough that helps new users understand the platform. It highlights key features and functionality.', 'ielts-course-manager'); ?></p>
                    
                    <h3><?php _e('How it works:', 'ielts-course-manager'); ?></h3>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php _e('First-time users automatically see the tour when they log in', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Users can skip the tour at any time', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Once completed or skipped, the tour won\'t show again unless reset', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Tours can be enabled/disabled globally or for specific membership types', 'ielts-course-manager'); ?></li>
                    </ul>
                    
                    <p>
                        <a href="<?php echo admin_url('edit.php?post_type=ielts_course&page=ielts-documentation'); ?>" class="button">
                            <?php _e('View Tour Documentation', 'ielts-course-manager'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
            .ielts-tours-container .card {
                padding: 20px;
            }
            .ielts-tours-container h2 {
                margin-top: 0;
            }
        </style>
        <?php
    }
}
