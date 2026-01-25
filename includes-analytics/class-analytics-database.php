<?php
/**
 * Analytics Database Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_Analytics_Database {
    
    /**
     * Create analytics tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Analytics events table
        $table_name = $wpdb->prefix . 'ielts_analytics_events';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Drop analytics tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ielts_analytics_events';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    
    /**
     * Log an analytics event
     */
    public function log_event($user_id, $event_type, $event_data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ielts_analytics_events';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'event_type' => $event_type,
                'event_data' => json_encode($event_data)
            ),
            array('%d', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get events for a user
     */
    public function get_user_events($user_id, $event_type = null, $limit = 100) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ielts_analytics_events';
        
        if ($event_type) {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND event_type = %s ORDER BY created_at DESC LIMIT %d",
                $user_id,
                $event_type,
                $limit
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
                $user_id,
                $limit
            );
        }
        
        return $wpdb->get_results($sql);
    }
}
