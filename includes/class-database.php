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
    
    public function __construct() {
        global $wpdb;
        $this->progress_table = $wpdb->prefix . 'ielts_cm_progress';
        $this->quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
        $this->enrollment_table = $wpdb->prefix . 'ielts_cm_enrollment';
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
            UNIQUE KEY user_lesson (user_id, lesson_id, resource_id)
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
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            UNIQUE KEY user_course (user_id, course_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_progress);
        dbDelta($sql_quiz_results);
        dbDelta($sql_enrollment);
    }
    
    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'ielts_cm_progress',
            $wpdb->prefix . 'ielts_cm_quiz_results',
            $wpdb->prefix . 'ielts_cm_enrollment'
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
}
