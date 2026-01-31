<?php
/**
 * Access Code Membership System
 * 
 * Manages access code-based enrollment as an alternative to payment-based memberships.
 * Users can enroll in courses by entering valid access codes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Access_Codes {
    
    /**
     * Access code status values
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_USED = 'used';
    const STATUS_EXPIRED = 'expired';
    const STATUS_DISABLED = 'disabled';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor can be used for early hook registration if needed
    }
    
    /**
     * Initialize access code functionality
     */
    public function init() {
        // Only initialize if access code system is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Add admin menu (only shows if system is enabled)
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add frontend shortcode for code redemption
        add_shortcode('ielts_access_code_form', array($this, 'access_code_form_shortcode'));
        
        // Handle code redemption
        add_action('wp_ajax_ielts_redeem_access_code', array($this, 'handle_code_redemption'));
        add_action('wp_ajax_nopriv_ielts_redeem_access_code', array($this, 'handle_code_redemption'));
    }
    
    /**
     * Check if access code system is enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function is_enabled() {
        return (bool) get_option('ielts_cm_access_code_enabled', false);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Only add the menu if the system is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Main access codes menu
        add_menu_page(
            __('Access Codes', 'ielts-course-manager'),
            __('Access Codes', 'ielts-course-manager'),
            'manage_options',
            'ielts-access-codes',
            array($this, 'access_codes_page'),
            'dashicons-tickets-alt',
            31 // Position (right after Memberships which is 30)
        );
        
        // Access Codes submenu (same as parent)
        add_submenu_page(
            'ielts-access-codes',
            __('Manage Access Codes', 'ielts-course-manager'),
            __('Access Codes', 'ielts-course-manager'),
            'manage_options',
            'ielts-access-codes',
            array($this, 'access_codes_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'ielts-access-codes',
            __('Access Code Settings', 'ielts-course-manager'),
            __('Settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-access-code-settings',
            array($this, 'settings_page')
        );
        
        // Documentation submenu
        add_submenu_page(
            'ielts-access-codes',
            __('Access Code Documentation', 'ielts-course-manager'),
            __('Documentation', 'ielts-course-manager'),
            'manage_options',
            'ielts-access-code-docs',
            array($this, 'documentation_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ielts_access_code_settings', 'ielts_cm_access_code_prefix');
        register_setting('ielts_access_code_settings', 'ielts_cm_access_code_length');
        register_setting('ielts_access_code_settings', 'ielts_cm_access_code_expiry_days');
    }
    
    /**
     * Main access codes page
     */
    public function access_codes_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Access Code Management', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Access Code System', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('The access code system is currently enabled. Users can redeem access codes to enroll in courses.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <h2><?php _e('Generate New Access Codes', 'ielts-course-manager'); ?></h2>
            <p><?php _e('Generate access codes for course enrollment. This feature will be implemented in the next phase.', 'ielts-course-manager'); ?></p>
            
            <div class="card" style="max-width: 600px;">
                <h3><?php _e('Quick Start', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Configure access code settings', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Generate access codes for your courses', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Distribute codes to users', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Users redeem codes to access courses', 'ielts-course-manager'); ?></li>
                </ol>
            </div>
            
            <h2><?php _e('Existing Access Codes', 'ielts-course-manager'); ?></h2>
            <p><?php _e('No access codes have been generated yet.', 'ielts-course-manager'); ?></p>
            
            <p style="margin-top: 30px;">
                <em><?php _e('Note: This is a skeleton implementation. Full access code generation, management, and redemption features will be added based on your requirements from the other repository.', 'ielts-course-manager'); ?></em>
            </p>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Save settings if form submitted
        if (isset($_POST['submit']) && check_admin_referer('ielts_access_code_settings')) {
            update_option('ielts_cm_access_code_prefix', sanitize_text_field($_POST['ielts_cm_access_code_prefix']));
            update_option('ielts_cm_access_code_length', absint($_POST['ielts_cm_access_code_length']));
            update_option('ielts_cm_access_code_expiry_days', absint($_POST['ielts_cm_access_code_expiry_days']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'ielts-course-manager') . '</p></div>';
        }
        
        $prefix = get_option('ielts_cm_access_code_prefix', 'IELTS');
        $length = get_option('ielts_cm_access_code_length', 8);
        $expiry_days = get_option('ielts_cm_access_code_expiry_days', 365);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Access Code Settings', 'ielts-course-manager'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_access_code_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Code Prefix', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_access_code_prefix" value="<?php echo esc_attr($prefix); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Prefix for generated access codes (e.g., IELTS-XXXX-XXXX)', 'ielts-course-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Code Length', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" name="ielts_cm_access_code_length" value="<?php echo esc_attr($length); ?>" min="4" max="20" class="small-text">
                            <p class="description">
                                <?php _e('Length of the random portion of access codes (4-20 characters)', 'ielts-course-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Expiry (Days)', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" name="ielts_cm_access_code_expiry_days" value="<?php echo esc_attr($expiry_days); ?>" min="1" max="3650" class="small-text">
                            <p class="description">
                                <?php _e('Default number of days before access codes expire (1-3650 days)', 'ielts-course-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Documentation page
     */
    public function documentation_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Access Code Documentation', 'ielts-course-manager'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Overview', 'ielts-course-manager'); ?></h2>
                <p>
                    <?php _e('The Access Code system provides an alternative enrollment method for your IELTS courses. Instead of requiring payment, users can enter access codes to gain course access.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="card">
                <h2><?php _e('Use Cases', 'ielts-course-manager'); ?></h2>
                <ul>
                    <li><?php _e('Bulk enrollment for schools or organizations', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Promotional campaigns and giveaways', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Partner and affiliate programs', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Free trials and sample access', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
            
            <div class="card">
                <h2><?php _e('Frontend Shortcode', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Add an access code redemption form to any page using:', 'ielts-course-manager'); ?></p>
                <code style="padding: 10px; background: #f0f0f0; display: inline-block;">[ielts_access_code_form]</code>
            </div>
            
            <div class="card">
                <h2><?php _e('Next Steps', 'ielts-course-manager'); ?></h2>
                <ol>
                    <li><?php _e('Configure your access code settings', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Generate access codes for your courses', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Add the redemption form to your site', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Distribute codes to your users', 'ielts-course-manager'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Access code form shortcode
     * 
     * @return string HTML for access code redemption form
     */
    public function access_code_form_shortcode() {
        if (!$this->is_enabled()) {
            return '<p>' . __('Access code system is currently disabled.', 'ielts-course-manager') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="ielts-access-code-form">
            <h3><?php _e('Redeem Access Code', 'ielts-course-manager'); ?></h3>
            <form id="ielts-access-code-form" method="post">
                <?php wp_nonce_field('ielts_redeem_access_code'); ?>
                <div class="form-group">
                    <label for="access_code"><?php _e('Enter your access code:', 'ielts-course-manager'); ?></label>
                    <input type="text" id="access_code" name="access_code" class="form-control" required placeholder="<?php esc_attr_e('IELTS-XXXX-XXXX', 'ielts-course-manager'); ?>">
                </div>
                <button type="submit" class="button button-primary"><?php _e('Redeem Code', 'ielts-course-manager'); ?></button>
            </form>
            <div id="ielts-access-code-message"></div>
        </div>
        
        <style>
            .ielts-access-code-form {
                max-width: 400px;
                margin: 20px 0;
            }
            .ielts-access-code-form .form-group {
                margin-bottom: 15px;
            }
            .ielts-access-code-form label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .ielts-access-code-form input[type="text"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .ielts-access-code-form .button {
                margin-top: 10px;
            }
            #ielts-access-code-message {
                margin-top: 15px;
                padding: 10px;
                border-radius: 4px;
            }
            #ielts-access-code-message.success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            #ielts-access-code-message.error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                $('#ielts-access-code-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var $form = $(this);
                    var $message = $('#ielts-access-code-message');
                    var accessCode = $('#access_code').val();
                    
                    $message.removeClass('success error').html('<?php _e('Processing...', 'ielts-course-manager'); ?>');
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'ielts_redeem_access_code',
                            access_code: accessCode,
                            nonce: $('input[name="_wpnonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                $message.addClass('success').html(response.data.message);
                                $form[0].reset();
                            } else {
                                $message.addClass('error').html(response.data.message);
                            }
                        },
                        error: function() {
                            $message.addClass('error').html('<?php _e('An error occurred. Please try again.', 'ielts-course-manager'); ?>');
                        }
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle access code redemption (AJAX handler)
     */
    public function handle_code_redemption() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_redeem_access_code')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'ielts-course-manager')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to redeem an access code.', 'ielts-course-manager')
            ));
        }
        
        // Get the access code
        $access_code = sanitize_text_field($_POST['access_code']);
        
        if (empty($access_code)) {
            wp_send_json_error(array(
                'message' => __('Please enter an access code.', 'ielts-course-manager')
            ));
        }
        
        // Placeholder response - actual validation will be implemented later
        wp_send_json_error(array(
            'message' => __('Access code redemption is not yet implemented. This is a placeholder for future functionality.', 'ielts-course-manager')
        ));
    }
}
