<?php
/**
 * Plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Activator {
    
    public static function activate() {
        // Create database tables
        IELTS_CM_Database::create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        if (!get_option('ielts_cm_version')) {
            add_option('ielts_cm_version', IELTS_CM_VERSION);
        }
    }
}
