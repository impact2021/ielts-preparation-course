<?php
/**
 * Plugin uninstall script for IELTS Analytics
 * 
 * This file is called when the plugin is uninstalled
 */

// Exit if accessed directly or not from WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to delete all data
$delete_data = get_option('ielts_analytics_delete_data_on_uninstall', false);

if ($delete_data) {
    // Load required files
    require_once plugin_dir_path(__FILE__) . 'includes-analytics/class-analytics-database.php';
    
    // Delete all plugin data from custom tables
    IELTS_Analytics_Database::drop_tables();
    
    // Delete all plugin options
    delete_option('ielts_analytics_version');
    delete_option('ielts_analytics_delete_data_on_uninstall');
    
    // Clear permalinks
    flush_rewrite_rules();
} else {
    // Only delete the setting that controls data deletion
    // Keep all analytics data and other settings
    delete_option('ielts_analytics_delete_data_on_uninstall');
}
