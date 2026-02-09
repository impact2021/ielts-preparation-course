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
    private $payments_table;
    private $payment_error_log_table;
    private $auto_sync_log_table;
    private $webhook_log_table;
    
    public function __construct() {
        global $wpdb;
        $this->progress_table = $wpdb->prefix . 'ielts_cm_progress';
        $this->quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
        $this->enrollment_table = $wpdb->prefix . 'ielts_cm_enrollment';
        $this->site_connections_table = $wpdb->prefix . 'ielts_cm_site_connections';
        $this->content_sync_table = $wpdb->prefix . 'ielts_cm_content_sync';
        $this->user_awards_table = $wpdb->prefix . 'ielts_cm_user_awards';
        $this->payments_table = $wpdb->prefix . 'ielts_cm_payments';
        $this->payment_error_log_table = $wpdb->prefix . 'ielts_cm_payment_errors';
        $this->auto_sync_log_table = $wpdb->prefix . 'ielts_cm_auto_sync_log';
        $this->webhook_log_table = $wpdb->prefix . 'ielts_cm_webhook_log';
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
        
        // Payments table
        $payments_table = $wpdb->prefix . 'ielts_cm_payments';
        $sql_payments = "CREATE TABLE IF NOT EXISTS $payments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            membership_type varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            payment_status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY payment_status (payment_status),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";
        
        // Payment error log table
        $payment_error_log_table = $wpdb->prefix . 'ielts_cm_payment_errors';
        $sql_payment_errors = "CREATE TABLE IF NOT EXISTS $payment_error_log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            error_type varchar(100) NOT NULL,
            error_message text NOT NULL,
            error_details longtext DEFAULT NULL,
            user_email varchar(255) DEFAULT NULL,
            membership_type varchar(50) DEFAULT NULL,
            amount decimal(10,2) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY error_type (error_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Auto-sync log table
        $auto_sync_log_table = $wpdb->prefix . 'ielts_cm_auto_sync_log';
        $sql_auto_sync_log = "CREATE TABLE IF NOT EXISTS $auto_sync_log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_type varchar(50) NOT NULL,
            message text NOT NULL,
            status varchar(20) DEFAULT 'success',
            log_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY content_type (content_type),
            KEY status (status),
            KEY log_date (log_date)
        ) $charset_collate;";
        
        // Access codes table
        $access_codes_table = $wpdb->prefix . 'ielts_cm_access_codes';
        $sql_access_codes = "CREATE TABLE IF NOT EXISTS $access_codes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            course_group varchar(50) NOT NULL,
            duration_days int(11) NOT NULL DEFAULT 30,
            created_by bigint(20) NOT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            used_by bigint(20) DEFAULT NULL,
            used_date datetime DEFAULT NULL,
            expiry_date datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY created_by (created_by),
            KEY status (status),
            KEY used_by (used_by)
        ) $charset_collate;";
        
        // Access code course mapping table
        $access_code_courses_table = $wpdb->prefix . 'ielts_cm_access_code_courses';
        $sql_access_code_courses = "CREATE TABLE IF NOT EXISTS $access_code_courses_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            course_group varchar(50) NOT NULL,
            course_id bigint(20) NOT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY course_group (course_group),
            KEY course_id (course_id),
            UNIQUE KEY group_course (course_group, course_id)
        ) $charset_collate;";
        
        // Webhook event log table
        $webhook_log_table = $wpdb->prefix . 'ielts_cm_webhook_log';
        $sql_webhook_log = "CREATE TABLE IF NOT EXISTS $webhook_log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            event_id varchar(255) DEFAULT NULL,
            payment_intent_id varchar(255) DEFAULT NULL,
            payment_type varchar(50) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            amount decimal(10,2) DEFAULT NULL,
            status varchar(20) DEFAULT 'received',
            error_message text DEFAULT NULL,
            raw_payload longtext DEFAULT NULL,
            processed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY payment_intent_id (payment_intent_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_progress);
        dbDelta($sql_quiz_results);
        dbDelta($sql_enrollment);
        dbDelta($sql_site_connections);
        dbDelta($sql_content_sync);
        dbDelta($sql_user_awards);
        dbDelta($sql_payments);
        dbDelta($sql_payment_errors);
        dbDelta($sql_auto_sync_log);
        dbDelta($sql_access_codes);
        dbDelta($sql_access_code_courses);
        dbDelta($sql_webhook_log);
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
            $wpdb->prefix . 'ielts_cm_user_awards',
            $wpdb->prefix . 'ielts_cm_payments',
            $wpdb->prefix . 'ielts_cm_payment_errors',
            $wpdb->prefix . 'ielts_cm_auto_sync_log',
            $wpdb->prefix . 'ielts_cm_access_codes',
            $wpdb->prefix . 'ielts_cm_access_code_courses',
            $wpdb->prefix . 'ielts_cm_webhook_log'
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
    
    public function get_payments_table() {
        return $this->payments_table;
    }
    
    public function get_payment_error_log_table() {
        return $this->payment_error_log_table;
    }
    
    public function get_auto_sync_log_table() {
        return $this->auto_sync_log_table;
    }
    
    public function get_webhook_log_table() {
        return $this->webhook_log_table;
    }
    
    /**
     * Log a webhook event to the database
     * 
     * @param string $event_type Type of webhook event (e.g., 'payment_intent.succeeded')
     * @param string $event_id Stripe event ID
     * @param string $payment_intent_id Payment intent ID
     * @param string $payment_type Type of payment (e.g., 'access_code_purchase')
     * @param int|null $user_id User ID if applicable
     * @param float|null $amount Payment amount
     * @param string $status Event processing status (received, processed, failed)
     * @param string|null $error_message Error message if failed
     * @param string|null $raw_payload Raw JSON payload for debugging
     * @return int|false Insert ID on success, false on failure
     */
    public static function log_webhook_event($event_type, $event_id, $payment_intent_id, $payment_type, $user_id, $amount, $status, $error_message = null, $raw_payload = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_webhook_log';
        
        $data = array(
            'event_type' => sanitize_text_field($event_type),
            'event_id' => sanitize_text_field($event_id),
            'payment_intent_id' => sanitize_text_field($payment_intent_id),
            'payment_type' => $payment_type ? sanitize_text_field($payment_type) : null,
            'user_id' => $user_id ? intval($user_id) : null,
            'amount' => $amount ? floatval($amount) : null,
            'status' => sanitize_text_field($status),
            'error_message' => $error_message,
            'raw_payload' => $raw_payload,
        );
        
        $formats = array('%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s');
        
        if ($status === 'processed') {
            $data['processed_at'] = current_time('mysql');
            $formats[] = '%s';
        }
        
        $result = $wpdb->insert($table_name, $data, $formats);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Log a payment error to the database
     * 
     * @param string $error_type Type of error (e.g., 'stripe_api_error', 'database_error', 'validation_error')
     * @param string $error_message User-friendly error message
     * @param array $error_details Additional error details (will be JSON encoded)
     * @param int|null $user_id Optional user ID
     * @param string|null $user_email Optional user email
     * @param string|null $membership_type Optional membership type
     * @param float|null $amount Optional payment amount
     * @return int|false Insert ID on success, false on failure
     */
    public static function log_payment_error($error_type, $error_message, $error_details = array(), $user_id = null, $user_email = null, $membership_type = null, $amount = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_payment_errors';
        
        // Ensure table exists before logging
        self::ensure_payment_error_table_exists();
        
        // Get client IP address (sanitized)
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        
        // Get user agent (sanitized)
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        // Prepare error details as JSON
        $error_details_json = is_array($error_details) ? wp_json_encode($error_details) : $error_details;
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'error_type' => $error_type,
                'error_message' => $error_message,
                'error_details' => $error_details_json,
                'user_email' => $user_email,
                'membership_type' => $membership_type,
                'amount' => $amount,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('IELTS Payment: Failed to log error to database - ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Ensure payment error log table exists
     * Creates the table if it doesn't exist
     */
    private static function ensure_payment_error_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_payment_errors';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) DEFAULT NULL,
                error_type varchar(100) NOT NULL,
                error_message text NOT NULL,
                error_details longtext DEFAULT NULL,
                user_email varchar(255) DEFAULT NULL,
                membership_type varchar(50) DEFAULT NULL,
                amount decimal(10,2) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY error_type (error_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}
