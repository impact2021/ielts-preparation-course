<?php
/**
 * Database management for progress tracking and quiz results
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Database {
    
    private $progress_table;
    private $quiz_results_table;
    private $enrollment_table;
    private $site_connections_table;
    private $content_sync_table;
    private $user_awards_table;
    
    public function __construct() {
        global $wpdb;
        $this->progress_table = $wpdb->prefix . 'ielts_cm_progress';
        $this->quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
        $this->enrollment_table = $wpdb->prefix . 'ielts_cm_enrollment';
        $this->site_connections_table = $wpdb->prefix . 'ielts_cm_site_connections';
        $this->content_sync_table = $wpdb->prefix . 'ielts_cm_content_sync';
        $this->user_awards_table = $wpdb->prefix . 'ielts_cm_user_awards';
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Progress table
        $progress_table = $wpdb->prefix . 'ielts_cm_progress';
        $sql_progress = "CREATE TABLE IF NOT EXISTS $progress_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            resource_id bigint(20) DEFAULT NULL,
            completed tinyint(1) DEFAULT 0,
            completed_date datetime DEFAULT NULL,
            last_accessed datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY lesson_id (lesson_id),
            KEY user_lesson_resource (user_id, lesson_id, resource_id)
        ) $charset_collate;";
        
        // Quiz results table
        $quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
        $sql_quiz_results = "CREATE TABLE IF NOT EXISTS $quiz_results_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            quiz_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            lesson_id bigint(20) DEFAULT NULL,
            score decimal(5,2) NOT NULL,
            max_score decimal(5,2) NOT NULL,
            percentage decimal(5,2) NOT NULL,
            answers longtext,
            submitted_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY quiz_id (quiz_id),
            KEY course_id (course_id)
        ) $charset_collate;";
        
        // Enrollment table
        $enrollment_table = $wpdb->prefix . 'ielts_cm_enrollment';
        $sql_enrollment = "CREATE TABLE IF NOT EXISTS $enrollment_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            enrolled_date datetime DEFAULT CURRENT_TIMESTAMP,
            course_end_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            UNIQUE KEY user_course (user_id, course_id)
        ) $charset_collate;";
        
        // Site connections table (for multi-site content sync)
        $site_connections_table = $wpdb->prefix . 'ielts_cm_site_connections';
        $sql_site_connections = "CREATE TABLE IF NOT EXISTS $site_connections_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_name varchar(255) NOT NULL,
            site_url varchar(255) NOT NULL,
            auth_token varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'active',
            last_sync datetime DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY site_url (site_url)
        ) $charset_collate;";
        
        // Content sync tracking table
        $content_sync_table = $wpdb->prefix . 'ielts_cm_content_sync';
        $sql_content_sync = "CREATE TABLE IF NOT EXISTS $content_sync_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL,
            content_hash varchar(64) NOT NULL,
            site_id bigint(20) NOT NULL,
            sync_date datetime DEFAULT CURRENT_TIMESTAMP,
            sync_status varchar(20) DEFAULT 'success',
            PRIMARY KEY  (id),
            KEY content_id (content_id),
            KEY site_id (site_id),
            KEY content_type (content_type)
        ) $charset_collate;";
        
        // User awards table
        $user_awards_table = $wpdb->prefix . 'ielts_cm_user_awards';
        $sql_user_awards = "CREATE TABLE IF NOT EXISTS $user_awards_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            award_id varchar(100) NOT NULL,
            earned_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY award_id (award_id),
            UNIQUE KEY user_award (user_id, award_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_progress);
        dbDelta($sql_quiz_results);
        dbDelta($sql_enrollment);
        dbDelta($sql_site_connections);
        dbDelta($sql_content_sync);
        dbDelta($sql_user_awards);
    }
    
    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'ielts_cm_progress',
            $wpdb->prefix . 'ielts_cm_quiz_results',
            $wpdb->prefix . 'ielts_cm_enrollment',
            $wpdb->prefix . 'ielts_cm_site_connections',
            $wpdb->prefix . 'ielts_cm_content_sync',
            $wpdb->prefix . 'ielts_cm_user_awards'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Get table names
     */
    public function get_progress_table() {
        return $this->progress_table;
    }
    
    public function get_quiz_results_table() {
        return $this->quiz_results_table;
    }
    
    public function get_enrollment_table() {
        return $this->enrollment_table;
    }
    
    public function get_site_connections_table() {
        return $this->site_connections_table;
    }
    
    public function get_content_sync_table() {
        return $this->content_sync_table;
    }
    
    public function get_user_awards_table() {
        return $this->user_awards_table;
    }
}
