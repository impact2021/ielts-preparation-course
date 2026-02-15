<?php
/**
 * Plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Activator {
    
    public static function activate() {
        // Use file-based locking to prevent concurrent activation on same server
        // This helps when multiple sites update simultaneously via WP Pusher
        $lock_file = WP_CONTENT_DIR . '/ielts-cm-activation.lock';
        $lock_handle = fopen($lock_file, 'c+');
        
        if (!$lock_handle) {
            // Could not create lock file - log error and defer
            error_log('IELTS CM: Could not create activation lock file. Deferring activation.');
            set_transient('ielts_cm_needs_activation', 1, 300); // 5 minutes
            return;
        }
        
        if (flock($lock_handle, LOCK_EX | LOCK_NB)) {
            try {
                self::do_activation();
            } finally {
                flock($lock_handle, LOCK_UN);
                fclose($lock_handle);
                // Clean up lock file
                if (file_exists($lock_file)) {
                    unlink($lock_file);
                }
            }
        } else {
            // Another activation is in progress, defer this one
            // Set a transient to retry activation on next admin page load
            set_transient('ielts_cm_needs_activation', 1, 300); // 5 minutes
            fclose($lock_handle);
        }
    }
    
    private static function do_activation() {
        $current_version = get_option('ielts_cm_version');
        
        // Create database tables (only if needed - dbDelta is smart about this)
        IELTS_CM_Database::create_tables();
        
        // Register post types before flushing to ensure proper rewrite rules
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-post-types.php';
        $post_types = new IELTS_CM_Post_Types();
        $post_types->register_post_types();
        
        // Defer flush_rewrite_rules() to avoid concurrent .htaccess writes
        // This is especially important when WP Pusher deploys to multiple sites simultaneously
        // The flush will happen on next admin page load via a scheduled action
        if (!$current_version || $current_version !== IELTS_CM_VERSION) {
            // Schedule rewrite flush for next admin page load
            set_transient('ielts_cm_flush_rewrite_rules', 1, 3600); // 1 hour
        }
        
        // Set or update version option
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
