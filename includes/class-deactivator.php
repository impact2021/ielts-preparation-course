<?php
/**
 * Plugin deactivation
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Deactivator {
    
    public static function deactivate() {
        // Register post types before flushing to ensure proper cleanup
        // This is necessary because post types are no longer registered when deactivation hook runs
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-post-types.php';
        $post_types = new IELTS_CM_Post_Types();
        $post_types->register_post_types();
        
        // Flush rewrite rules to remove custom post type permalinks
        flush_rewrite_rules();
        
        // Note: We don't drop tables on deactivation to preserve data
        // Tables are only dropped on uninstall
    }
}
