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
        
        // Register post types before flushing to ensure proper rewrite rules
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-post-types.php';
        $post_types = new IELTS_CM_Post_Types();
        $post_types->register_post_types();
        
        // Flush rewrite rules to register custom post type permalinks
        flush_rewrite_rules();
        
        // Set or update version option
        $current_version = get_option('ielts_cm_version');
        if (!$current_version) {
            add_option('ielts_cm_version', IELTS_CM_VERSION);
        } elseif ($current_version !== IELTS_CM_VERSION) {
            update_option('ielts_cm_version', IELTS_CM_VERSION);
        }
        
        // Enable membership system by default if not already set
        if (get_option('ielts_cm_membership_enabled') === false) {
            add_option('ielts_cm_membership_enabled', 1);
        }
    }
}
