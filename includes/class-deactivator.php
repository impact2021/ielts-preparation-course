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
        
        // Clear scheduled cron jobs
        $timestamp = wp_next_scheduled('ielts_cm_check_expired_memberships');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ielts_cm_check_expired_memberships');
        }
        
        // Clear auto-sync cron
        $auto_sync_timestamp = wp_next_scheduled('ielts_cm_auto_sync_content');
        if ($auto_sync_timestamp) {
            wp_unschedule_event($auto_sync_timestamp, 'ielts_cm_auto_sync_content');
        }
        
        // Note: We don't drop tables on deactivation to preserve data
        // Tables are only dropped on uninstall
    }
}
