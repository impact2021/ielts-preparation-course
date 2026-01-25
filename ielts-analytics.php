<?php
/**
 * Plugin Name: IELTS Analytics
 * Plugin URI: https://www.ieltstestonline.com/
 * Description: Analytics and reporting plugin for IELTS Course Manager. Provides detailed insights into student progress, quiz performance, and course completion rates.
 * Version: 1.0.0
 * Author: IELTStestONLINE
 * Author URI: https://www.ieltstestonline.com/
 * Text Domain: ielts-analytics
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
define('IELTS_ANALYTICS_VERSION', '1.0.0');
define('IELTS_ANALYTICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IELTS_ANALYTICS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IELTS_ANALYTICS_PLUGIN_FILE', __FILE__);

// Include required files
require_once IELTS_ANALYTICS_PLUGIN_DIR . 'includes-analytics/class-ielts-analytics.php';
require_once IELTS_ANALYTICS_PLUGIN_DIR . 'includes-analytics/class-analytics-database.php';
require_once IELTS_ANALYTICS_PLUGIN_DIR . 'includes-analytics/class-analytics-reports.php';
require_once IELTS_ANALYTICS_PLUGIN_DIR . 'includes-analytics/admin/class-analytics-admin.php';

/**
 * Initialize the plugin
 */
function ielts_analytics_init() {
    $plugin = new IELTS_Analytics();
    $plugin->run();
}
add_action('plugins_loaded', 'ielts_analytics_init');

/**
 * Activation hook
 */
function ielts_analytics_activate() {
    require_once IELTS_ANALYTICS_PLUGIN_DIR . 'includes-analytics/class-analytics-activator.php';
    IELTS_Analytics_Activator::activate();
}
register_activation_hook(__FILE__, 'ielts_analytics_activate');

/**
 * Deactivation hook
 */
function ielts_analytics_deactivate() {
    require_once IELTS_ANALYTICS_PLUGIN_DIR . 'includes-analytics/class-analytics-deactivator.php';
    IELTS_Analytics_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'ielts_analytics_deactivate');
