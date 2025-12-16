<?php
/**
 * Plugin deactivation
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Deactivator {
    
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't drop tables on deactivation to preserve data
        // Tables are only dropped on uninstall
    }
}
