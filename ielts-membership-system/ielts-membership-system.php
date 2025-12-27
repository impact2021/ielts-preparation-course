<?php
/**
 * Plugin Name: IELTS Membership System
 * Plugin URI: https://www.ieltstestonline.com/
 * Description: Membership and payment system for IELTS preparation courses with PayPal and Stripe integration.
 * Version: 1.0.0
 * Author: IELTStestONLINE
 * Author URI: https://www.ieltstestonline.com/
 * Text Domain: ielts-membership-system
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IELTS_MS_VERSION', '1.0.0');
define('IELTS_MS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IELTS_MS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IELTS_MS_PLUGIN_FILE', __FILE__);

// Include required files
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-database.php';
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-membership.php';
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-payment-gateway.php';
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-paypal-gateway.php';
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-stripe-gateway.php';
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-login-manager.php';
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-account-manager.php';
require_once IELTS_MS_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once IELTS_MS_PLUGIN_DIR . 'admin/class-admin.php';

/**
 * Initialize the plugin
 */
function ielts_ms_init() {
    // Initialize components
    $membership = new IELTS_MS_Membership();
    $login_manager = new IELTS_MS_Login_Manager();
    $account_manager = new IELTS_MS_Account_Manager();
    $shortcodes = new IELTS_MS_Shortcodes();
    $shortcodes->register();
    
    // Initialize payment gateways
    new IELTS_MS_PayPal_Gateway();
    new IELTS_MS_Stripe_Gateway();
    
    // Initialize admin if in admin area
    if (is_admin()) {
        new IELTS_MS_Admin();
    }
}
add_action('plugins_loaded', 'ielts_ms_init');

/**
 * Activation hook
 */
function ielts_ms_activate() {
    IELTS_MS_Database::create_tables();
    
    // Create default pages if they don't exist
    ielts_ms_create_default_pages();
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ielts_ms_activate');

/**
 * Deactivation hook
 */
function ielts_ms_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ielts_ms_deactivate');

/**
 * Create default pages
 */
function ielts_ms_create_default_pages() {
    // Login page
    $login_page = get_page_by_path('membership-login');
    if (!$login_page) {
        wp_insert_post(array(
            'post_title' => 'Membership Login',
            'post_name' => 'membership-login',
            'post_content' => '[ielts_membership_login]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed'
        ));
    }
    
    // Registration page
    $register_page = get_page_by_path('membership-register');
    if (!$register_page) {
        wp_insert_post(array(
            'post_title' => 'Membership Registration',
            'post_name' => 'membership-register',
            'post_content' => '[ielts_membership_register]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed'
        ));
    }
    
    // Account page
    $account_page = get_page_by_path('my-account');
    if (!$account_page) {
        wp_insert_post(array(
            'post_title' => 'My Account',
            'post_name' => 'my-account',
            'post_content' => '[ielts_membership_account]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed'
        ));
    }
}

/**
 * Enqueue frontend assets
 */
function ielts_ms_enqueue_assets() {
    wp_enqueue_style('ielts-membership-style', IELTS_MS_PLUGIN_URL . 'assets/css/style.css', array(), IELTS_MS_VERSION);
    wp_enqueue_script('ielts-membership-script', IELTS_MS_PLUGIN_URL . 'assets/js/script.js', array('jquery'), IELTS_MS_VERSION, true);
    
    wp_localize_script('ielts-membership-script', 'ieltsMS', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ielts_ms_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'ielts_ms_enqueue_assets');
