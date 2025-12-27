<?php
/**
 * Uninstall IELTS Membership System
 * 
 * This file is executed when the plugin is deleted via WordPress admin.
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('ielts_ms_paypal_enabled');
delete_option('ielts_ms_paypal_email');
delete_option('ielts_ms_paypal_sandbox');
delete_option('ielts_ms_stripe_enabled');
delete_option('ielts_ms_stripe_publishable_key');
delete_option('ielts_ms_stripe_secret_key');
delete_option('ielts_ms_stripe_webhook_secret');
delete_option('ielts_ms_custom_login_enabled');
delete_option('ielts_ms_login_page_id');
delete_option('ielts_ms_account_page_id');

// Optional: Drop database tables (commented out for safety)
// Uncomment the following lines if you want to remove all data on uninstall
/*
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ielts_ms_memberships");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ielts_ms_payments");
*/

// Optional: Delete plugin pages (commented out for safety)
// Uncomment if you want to remove pages on uninstall
/*
$login_page_id = get_option('ielts_ms_login_page_id');
if ($login_page_id) {
    wp_delete_post($login_page_id, true);
}

$register_page = get_page_by_path('membership-register');
if ($register_page) {
    wp_delete_post($register_page->ID, true);
}

$account_page_id = get_option('ielts_ms_account_page_id');
if ($account_page_id) {
    wp_delete_post($account_page_id, true);
}
*/
