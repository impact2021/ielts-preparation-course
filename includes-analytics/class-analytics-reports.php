<?php
/**
 * Analytics Reports Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_Analytics_Reports {
    
    /**
     * Database handler
     */
    protected $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new IELTS_Analytics_Database();
    }
    
    /**
     * Get user progress report
     */
    public function get_user_progress($user_id) {
        $events = $this->database->get_user_events($user_id);
        
        $report = array(
            'total_events' => count($events),
            'events' => $events
        );
        
        return $report;
    }
    
    /**
     * Get quiz performance report
     */
    public function get_quiz_performance($user_id = null) {
        if ($user_id) {
            $events = $this->database->get_user_events($user_id, 'quiz_completed');
        } else {
            // Get all quiz completion events
            global $wpdb;
            $table_name = $wpdb->prefix . 'ielts_analytics_events';
            $events = $wpdb->get_results(
                "SELECT * FROM $table_name WHERE event_type = 'quiz_completed' ORDER BY created_at DESC LIMIT 100"
            );
        }
        
        $report = array(
            'total_quizzes' => count($events),
            'quizzes' => $events
        );
        
        return $report;
    }
    
    /**
     * Get course completion report
     */
    public function get_course_completion() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_analytics_events';
        
        $events = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE event_type = 'course_completed' ORDER BY created_at DESC LIMIT 100"
        );
        
        $report = array(
            'total_completions' => count($events),
            'completions' => $events
        );
        
        return $report;
    }
}
