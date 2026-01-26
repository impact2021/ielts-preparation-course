<?php
/**
 * Plugin Name: IELTS Course Manager
 * Plugin URI: https://www.ieltstestonline.com/
 * Description: A flexible Learning Management System for IELTS preparation courses with lessons, resources, quizzes, and progress tracking.
 * Version: 14.11
 * Author: IELTStestONLINE
 * Author URI: https://www.ieltstestonline.com/
 * Text Domain: ielts-course-manager
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
define('IELTS_CM_VERSION', '14.11');
define('IELTS_CM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IELTS_CM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IELTS_CM_PLUGIN_FILE', __FILE__);

// Include required files
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-ielts-course-manager.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-post-types.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-database.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-progress-tracker.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-quiz-handler.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-enrollment.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-multi-site-sync.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-sync-api.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-awards.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-gamification.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-membership.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-stripe-payment.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/admin/class-admin.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/admin/class-sync-settings-page.php';
require_once IELTS_CM_PLUGIN_DIR . 'includes/frontend/class-frontend.php';

/**
 * Initialize the plugin
 */
function ielts_cm_init() {
    $plugin = new IELTS_Course_Manager();
    $plugin->run();
    
    // Initialize Stripe payment handler
    $stripe_payment = new IELTS_CM_Stripe_Payment();
    $stripe_payment->init();
}
add_action('plugins_loaded', 'ielts_cm_init');

/**
 * Activation hook
 */
function ielts_cm_activate() {
    require_once IELTS_CM_PLUGIN_DIR . 'includes/class-activator.php';
    IELTS_CM_Activator::activate();
}
register_activation_hook(__FILE__, 'ielts_cm_activate');

/**
 * Deactivation hook
 */
function ielts_cm_deactivate() {
    require_once IELTS_CM_PLUGIN_DIR . 'includes/class-deactivator.php';
    IELTS_CM_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'ielts_cm_deactivate');
