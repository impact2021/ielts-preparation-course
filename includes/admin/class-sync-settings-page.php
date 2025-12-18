<?php
/**
 * Multi-Site Sync Settings Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Sync_Settings_Page {
    
    private $sync_manager;
    
    public function __construct() {
        $this->sync_manager = new IELTS_CM_Multi_Site_Sync();
    }
    
    /**
     * Add admin menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Multi-Site Sync', 'ielts-course-manager'),
            __('Multi-Site Sync', 'ielts-course-manager'),
            'manage_options',
            'ielts-cm-sync-settings',
            array($this, 'render_page')
        );
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submit() {
        if (!isset($_POST['ielts_cm_sync_action'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!isset($_POST['ielts_cm_sync_nonce']) || !wp_verify_nonce($_POST['ielts_cm_sync_nonce'], 'ielts_cm_sync_settings')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['ielts_cm_sync_action']);
        
        switch ($action) {
            case 'set_site_role':
                $this->handle_set_site_role();
                break;
            case 'add_subsite':
                $this->handle_add_subsite();
                break;
            case 'remove_subsite':
                $this->handle_remove_subsite();
                break;
            case 'test_connection':
                $this->handle_test_connection();
                break;
            case 'generate_token':
                $this->handle_generate_token();
                break;
        }
    }
    
    /**
     * Set site role (primary/subsite)
     */
    private function handle_set_site_role() {
        if (!isset($_POST['site_role'])) {
            return;
        }
        
        $role = sanitize_text_field($_POST['site_role']);
        
        if (!in_array($role, array('primary', 'subsite', 'standalone'))) {
            add_settings_error('ielts_cm_sync', 'invalid_role', 'Invalid site role');
            return;
        }
        
        update_option('ielts_cm_site_role', $role);
        
        // Generate auth token if setting as subsite
        if ($role === 'subsite') {
            $existing_token = get_option('ielts_cm_subsite_auth_token', '');
            if (empty($existing_token)) {
                $token = wp_generate_password(32, false);
                update_option('ielts_cm_subsite_auth_token', $token);
            }
        }
        
        add_settings_error('ielts_cm_sync', 'role_updated', 'Site role updated successfully', 'success');
    }
    
    /**
     * Add subsite connection
     */
    private function handle_add_subsite() {
        $site_name = sanitize_text_field($_POST['subsite_name'] ?? '');
        $site_url = esc_url_raw($_POST['subsite_url'] ?? '');
        $auth_token = sanitize_text_field($_POST['subsite_token'] ?? '');
        
        if (empty($site_name) || empty($site_url) || empty($auth_token)) {
            add_settings_error('ielts_cm_sync', 'missing_fields', 'All fields are required');
            return;
        }
        
        $result = $this->sync_manager->add_subsite($site_name, $site_url, $auth_token);
        
        if (is_wp_error($result)) {
            add_settings_error('ielts_cm_sync', 'add_failed', $result->get_error_message());
        } else {
            add_settings_error('ielts_cm_sync', 'subsite_added', 'Subsite added successfully', 'success');
        }
    }
    
    /**
     * Remove subsite connection
     */
    private function handle_remove_subsite() {
        if (!isset($_POST['site_id'])) {
            return;
        }
        
        $site_id = intval($_POST['site_id']);
        $result = $this->sync_manager->remove_subsite($site_id);
        
        if ($result) {
            add_settings_error('ielts_cm_sync', 'subsite_removed', 'Subsite removed successfully', 'success');
        } else {
            add_settings_error('ielts_cm_sync', 'remove_failed', 'Failed to remove subsite');
        }
    }
    
    /**
     * Test connection to a subsite
     */
    private function handle_test_connection() {
        if (!isset($_POST['site_id'])) {
            return;
        }
        
        $site_id = intval($_POST['site_id']);
        $subsites = $this->sync_manager->get_connected_subsites();
        $subsite = null;
        
        foreach ($subsites as $site) {
            if ($site->id == $site_id) {
                $subsite = $site;
                break;
            }
        }
        
        if (!$subsite) {
            add_settings_error('ielts_cm_sync', 'site_not_found', 'Subsite not found');
            return;
        }
        
        // Make test request
        $response = wp_remote_get(
            trailingslashit($subsite->site_url) . 'wp-json/ielts-cm/v1/test-connection',
            array(
                'headers' => array(
                    'X-IELTS-Auth-Token' => $subsite->auth_token
                )
            )
        );
        
        if (is_wp_error($response)) {
            add_settings_error('ielts_cm_sync', 'connection_failed', 'Connection failed: ' . $response->get_error_message());
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['success']) && $body['success']) {
                add_settings_error('ielts_cm_sync', 'connection_success', 'Connection successful!', 'success');
            } else {
                add_settings_error('ielts_cm_sync', 'connection_failed', 'Connection failed: Invalid response');
            }
        }
    }
    
    /**
     * Generate new auth token for subsite
     */
    private function handle_generate_token() {
        $token = wp_generate_password(32, false);
        update_option('ielts_cm_subsite_auth_token', $token);
        add_settings_error('ielts_cm_sync', 'token_generated', 'New authentication token generated', 'success');
    }
    
    /**
     * Render the settings page
     */
    public function render_page() {
        $site_role = get_option('ielts_cm_site_role', 'standalone');
        $auth_token = get_option('ielts_cm_subsite_auth_token', '');
        $subsites = $this->sync_manager->get_connected_subsites();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Multi-Site Sync Settings', 'ielts-course-manager'); ?></h1>
            
            <?php settings_errors('ielts_cm_sync'); ?>
            
            <div class="ielts-cm-sync-settings">
                
                <!-- Site Role Configuration -->
                <div class="postbox" style="margin-top: 20px;">
                    <div class="inside">
                        <h2><?php _e('Site Configuration', 'ielts-course-manager'); ?></h2>
                        <p class="description">
                            <?php _e('Configure this site as a primary site (to push content) or a subsite (to receive content).', 'ielts-course-manager'); ?>
                        </p>
                        
                        <form method="post" action="">
                            <?php wp_nonce_field('ielts_cm_sync_settings', 'ielts_cm_sync_nonce'); ?>
                            <input type="hidden" name="ielts_cm_sync_action" value="set_site_role">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Site Role', 'ielts-course-manager'); ?></th>
                                    <td>
                                        <fieldset>
                                            <label>
                                                <input type="radio" name="site_role" value="standalone" <?php checked($site_role, 'standalone'); ?>>
                                                <?php _e('Standalone (no sync)', 'ielts-course-manager'); ?>
                                            </label><br>
                                            <label>
                                                <input type="radio" name="site_role" value="primary" <?php checked($site_role, 'primary'); ?>>
                                                <?php _e('Primary Site (push content to subsites)', 'ielts-course-manager'); ?>
                                            </label><br>
                                            <label>
                                                <input type="radio" name="site_role" value="subsite" <?php checked($site_role, 'subsite'); ?>>
                                                <?php _e('Subsite (receive content from primary)', 'ielts-course-manager'); ?>
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button(__('Save Site Role', 'ielts-course-manager')); ?>
                        </form>
                    </div>
                </div>
                
                <?php if ($site_role === 'subsite'): ?>
                <!-- Subsite Authentication Token -->
                <div class="postbox">
                    <div class="inside">
                        <h2><?php _e('Authentication Token', 'ielts-course-manager'); ?></h2>
                        <p class="description">
                            <?php _e('Provide this token to the primary site to allow content sync.', 'ielts-course-manager'); ?>
                        </p>
                        
                        <?php if (!empty($auth_token)): ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Auth Token', 'ielts-course-manager'); ?></th>
                                <td>
                                    <input type="text" readonly value="<?php echo esc_attr($auth_token); ?>" 
                                           class="large-text code" onclick="this.select()">
                                    <p class="description">
                                        <?php _e('Click to select and copy this token.', 'ielts-course-manager'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Site URL', 'ielts-course-manager'); ?></th>
                                <td>
                                    <input type="text" readonly value="<?php echo esc_url(get_site_url()); ?>" 
                                           class="large-text code" onclick="this.select()">
                                </td>
                            </tr>
                        </table>
                        
                        <form method="post" action="" style="margin-top: 10px;">
                            <?php wp_nonce_field('ielts_cm_sync_settings', 'ielts_cm_sync_nonce'); ?>
                            <input type="hidden" name="ielts_cm_sync_action" value="generate_token">
                            <?php submit_button(__('Generate New Token', 'ielts-course-manager'), 'secondary', 'submit', false); ?>
                            <span class="description" style="margin-left: 10px;">
                                <?php _e('Warning: This will invalidate the old token.', 'ielts-course-manager'); ?>
                            </span>
                        </form>
                        <?php else: ?>
                        <p><?php _e('No token generated yet. Save the site role as "Subsite" to generate one.', 'ielts-course-manager'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($site_role === 'primary'): ?>
                <!-- Connected Subsites -->
                <div class="postbox">
                    <div class="inside">
                        <h2><?php _e('Connected Subsites', 'ielts-course-manager'); ?></h2>
                        
                        <?php if (!empty($subsites)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Site Name', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Site URL', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Last Sync', 'ielts-course-manager'); ?></th>
                                    <th><?php _e('Actions', 'ielts-course-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subsites as $subsite): ?>
                                <tr>
                                    <td><?php echo esc_html($subsite->site_name); ?></td>
                                    <td><?php echo esc_html($subsite->site_url); ?></td>
                                    <td>
                                        <span class="<?php echo $subsite->status === 'active' ? 'dashicons dashicons-yes' : 'dashicons dashicons-no'; ?>"></span>
                                        <?php echo esc_html($subsite->status); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($subsite->last_sync) {
                                            echo esc_html(human_time_diff(strtotime($subsite->last_sync), current_time('timestamp'))) . ' ago';
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <form method="post" action="" style="display: inline;">
                                            <?php wp_nonce_field('ielts_cm_sync_settings', 'ielts_cm_sync_nonce'); ?>
                                            <input type="hidden" name="ielts_cm_sync_action" value="test_connection">
                                            <input type="hidden" name="site_id" value="<?php echo intval($subsite->id); ?>">
                                            <button type="submit" class="button button-small">
                                                <?php _e('Test', 'ielts-course-manager'); ?>
                                            </button>
                                        </form>
                                        
                                        <form method="post" action="" style="display: inline;" 
                                              onsubmit="return confirm('<?php _e('Are you sure you want to remove this subsite?', 'ielts-course-manager'); ?>');">
                                            <?php wp_nonce_field('ielts_cm_sync_settings', 'ielts_cm_sync_nonce'); ?>
                                            <input type="hidden" name="ielts_cm_sync_action" value="remove_subsite">
                                            <input type="hidden" name="site_id" value="<?php echo intval($subsite->id); ?>">
                                            <button type="submit" class="button button-small button-link-delete">
                                                <?php _e('Remove', 'ielts-course-manager'); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p><?php _e('No subsites connected yet.', 'ielts-course-manager'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Add New Subsite -->
                <div class="postbox">
                    <div class="inside">
                        <h2><?php _e('Add Subsite', 'ielts-course-manager'); ?></h2>
                        
                        <form method="post" action="">
                            <?php wp_nonce_field('ielts_cm_sync_settings', 'ielts_cm_sync_nonce'); ?>
                            <input type="hidden" name="ielts_cm_sync_action" value="add_subsite">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="subsite_name"><?php _e('Site Name', 'ielts-course-manager'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="subsite_name" id="subsite_name" 
                                               class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="subsite_url"><?php _e('Site URL', 'ielts-course-manager'); ?></label>
                                    </th>
                                    <td>
                                        <input type="url" name="subsite_url" id="subsite_url" 
                                               class="regular-text" placeholder="https://example.com" required>
                                        <p class="description">
                                            <?php _e('The base URL of the subsite (without trailing slash).', 'ielts-course-manager'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="subsite_token"><?php _e('Authentication Token', 'ielts-course-manager'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="subsite_token" id="subsite_token" 
                                               class="large-text code" required>
                                        <p class="description">
                                            <?php _e('Get this token from the subsite\'s Multi-Site Sync settings page.', 'ielts-course-manager'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button(__('Add Subsite', 'ielts-course-manager')); ?>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <style>
            .ielts-cm-sync-settings .postbox {
                margin-bottom: 20px;
            }
            .ielts-cm-sync-settings .inside {
                padding: 20px;
            }
            .ielts-cm-sync-settings h2 {
                margin-top: 0;
            }
        </style>
        <?php
    }
}
