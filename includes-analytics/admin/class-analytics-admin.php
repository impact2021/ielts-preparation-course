<?php
/**
 * Admin functionality for Analytics plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_Analytics_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on analytics pages
        if (strpos($hook, 'ielts-analytics') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ielts-analytics-admin',
            IELTS_ANALYTICS_PLUGIN_URL . 'assets/css/analytics-admin.css',
            array(),
            IELTS_ANALYTICS_VERSION
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'ielts_analytics_settings',
            'ielts_analytics_delete_data_on_uninstall',
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
            )
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('IELTS Analytics Settings', 'ielts-analytics'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ielts_analytics_settings');
                do_settings_sections('ielts_analytics_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ielts_analytics_delete_data_on_uninstall">
                                <?php echo esc_html__('Delete Data on Uninstall', 'ielts-analytics'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   id="ielts_analytics_delete_data_on_uninstall" 
                                   name="ielts_analytics_delete_data_on_uninstall" 
                                   value="1" 
                                   <?php checked(get_option('ielts_analytics_delete_data_on_uninstall', false), true); ?> />
                            <p class="description">
                                <?php echo esc_html__('If checked, all analytics data will be permanently deleted when the plugin is uninstalled.', 'ielts-analytics'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
