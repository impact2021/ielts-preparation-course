<?php
/**
 * Plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_Analytics_Activator {
    
    public static function activate() {
        // Create database tables
        IELTS_Analytics_Database::create_tables();
        
        // Set or update version option
        $current_version = get_option('ielts_analytics_version');
        if (!$current_version) {
            add_option('ielts_analytics_version', IELTS_ANALYTICS_VERSION);
        } elseif ($current_version !== IELTS_ANALYTICS_VERSION) {
            update_option('ielts_analytics_version', IELTS_ANALYTICS_VERSION);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
