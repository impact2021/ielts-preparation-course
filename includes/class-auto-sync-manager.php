<?php
/**
 * Automatic Content Sync Manager
 * Handles scheduled automatic synchronization between primary and subsites
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Auto_Sync_Manager {
    
    private $sync_manager;
    private $db;
    
    /**
     * Cron hook name
     */
    const CRON_HOOK = 'ielts_cm_auto_sync_content';
    
    /**
     * Maximum items to sync per run (to prevent timeouts)
     */
    const MAX_ITEMS_PER_RUN = 50;
    
    /**
     * Memory threshold (in MB) - stop if memory usage exceeds this
     */
    const MEMORY_THRESHOLD_MB = 100;
    
    /**
     * Maximum consecutive failures before auto-disabling
     */
    const MAX_FAILURES = 5;
    
    public function __construct() {
        $this->sync_manager = new IELTS_CM_Multi_Site_Sync();
        $this->db = new IELTS_CM_Database();
        
        // Register cron action early so it's available when cron fires
        add_action(self::CRON_HOOK, array($this, 'run_auto_sync'));
    }
    
    /**
     * Initialize auto-sync functionality
     */
    public function init() {
        // Schedule cron if auto-sync is enabled
        $this->schedule_auto_sync();
        
        // Add hook to reschedule when settings change
        add_action('update_option_ielts_cm_auto_sync_enabled', array($this, 'schedule_auto_sync'));
        add_action('update_option_ielts_cm_auto_sync_interval', array($this, 'schedule_auto_sync'));
    }
    
    /**
     * Check if auto-sync is enabled
     */
    public function is_enabled() {
        return get_option('ielts_cm_auto_sync_enabled', false) === '1';
    }
    
    /**
     * Get auto-sync interval in minutes
     */
    public function get_interval() {
        return absint(get_option('ielts_cm_auto_sync_interval', 15));
    }
    
    /**
     * Schedule or unschedule the auto-sync cron job
     */
    public function schedule_auto_sync() {
        // Clear existing schedule
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
        
        // Only schedule if enabled and on primary site
        if (!$this->is_enabled() || !$this->sync_manager->is_primary_site()) {
            return;
        }
        
        // Get interval in minutes
        $interval_minutes = $this->get_interval();
        
        // Register custom cron interval if needed
        add_filter('cron_schedules', function($schedules) use ($interval_minutes) {
            $schedules['ielts_cm_auto_sync'] = array(
                'interval' => $interval_minutes * 60,
                'display'  => sprintf(__('Every %d minutes', 'ielts-course-manager'), $interval_minutes)
            );
            return $schedules;
        });
        
        // Schedule the event
        wp_schedule_event(time(), 'ielts_cm_auto_sync', self::CRON_HOOK);
    }
    
    /**
     * Run the automatic sync process
     */
    public function run_auto_sync() {
        // Double-check it's enabled and on primary site
        if (!$this->is_enabled() || !$this->sync_manager->is_primary_site()) {
            return;
        }
        
        // Check if we've had too many consecutive failures
        $consecutive_failures = absint(get_option('ielts_cm_auto_sync_failures', 0));
        if ($consecutive_failures >= self::MAX_FAILURES) {
            // Disable auto-sync
            update_option('ielts_cm_auto_sync_enabled', '0');
            $this->log_sync('system', 'Auto-sync disabled due to too many consecutive failures', 'failed');
            
            // Send notification to admin (optional - can be implemented later)
            return;
        }
        
        // Get connected subsites
        $subsites = $this->sync_manager->get_connected_subsites();
        if (empty($subsites)) {
            $this->log_sync('system', 'No subsites connected', 'skipped');
            return;
        }
        
        // Start sync process
        $start_time = microtime(true);
        $this->log_sync('system', 'Auto-sync started', 'running');
        
        try {
            // Get all content that needs checking
            $changed_items = $this->get_changed_content();
            
            if (empty($changed_items)) {
                $this->log_sync('system', 'No content changes detected', 'success');
                update_option('ielts_cm_auto_sync_last_run', current_time('mysql'));
                update_option('ielts_cm_auto_sync_failures', 0); // Reset failure counter
                return;
            }
            
            // Limit items per run
            $items_to_sync = array_slice($changed_items, 0, self::MAX_ITEMS_PER_RUN);
            $synced_count = 0;
            $failed_count = 0;
            
            foreach ($items_to_sync as $item) {
                // Check memory usage
                if ($this->is_memory_exceeded()) {
                    $this->log_sync('system', sprintf('Memory threshold exceeded. Synced %d items.', $synced_count), 'warning');
                    break;
                }
                
                // Push content to all subsites
                $result = $this->sync_manager->push_content_to_subsites($item['id'], $item['type']);
                
                if (!is_wp_error($result)) {
                    $synced_count++;
                    $this->log_sync($item['type'], sprintf('Synced: %s (ID: %d)', $item['title'], $item['id']), 'success');
                } else {
                    $failed_count++;
                    $this->log_sync($item['type'], sprintf('Failed: %s (ID: %d) - %s', $item['title'], $item['id'], $result->get_error_message()), 'failed');
                }
            }
            
            $duration = microtime(true) - $start_time;
            $summary = sprintf(
                'Auto-sync completed in %.2f seconds. Synced: %d, Failed: %d, Remaining: %d',
                $duration,
                $synced_count,
                $failed_count,
                count($changed_items) - count($items_to_sync)
            );
            
            $this->log_sync('system', $summary, $failed_count > 0 ? 'warning' : 'success');
            update_option('ielts_cm_auto_sync_last_run', current_time('mysql'));
            
            // Update failure counter
            if ($failed_count > 0) {
                update_option('ielts_cm_auto_sync_failures', $consecutive_failures + 1);
            } else {
                update_option('ielts_cm_auto_sync_failures', 0);
            }
            
        } catch (Exception $e) {
            $this->log_sync('system', 'Auto-sync failed with exception: ' . $e->getMessage(), 'failed');
            update_option('ielts_cm_auto_sync_failures', $consecutive_failures + 1);
        }
    }
    
    /**
     * Get all content that has changed since last sync
     * Returns items in the correct order to prevent progress loss:
     * 1. Courses (so lessons can reference them)
     * 2. Resources and quizzes (so sync_lesson_pages doesn't trash them)
     * 3. Lessons (after their children exist)
     */
    private function get_changed_content() {
        $changed_items = array();
        
        // Get all courses with hierarchy
        $courses_hierarchy = $this->sync_manager->get_all_courses_with_hierarchy();
        
        // First pass: Collect all courses
        $courses = array();
        $lessons_with_children = array();
        
        foreach ($courses_hierarchy as $course) {
            if ($this->is_content_changed($course['id'], $course['type'])) {
                $courses[] = array(
                    'id' => $course['id'],
                    'type' => $course['type'],
                    'title' => $course['title']
                );
            }
            
            foreach ($course['lessons'] as $lesson) {
                $lesson_children = array();
                
                // Collect resources for this lesson
                foreach ($lesson['resources'] as $resource) {
                    if ($this->is_content_changed($resource['id'], $resource['type'])) {
                        $lesson_children[] = array(
                            'id' => $resource['id'],
                            'type' => $resource['type'],
                            'title' => $resource['title']
                        );
                    }
                }
                
                // Collect quizzes for this lesson
                foreach ($lesson['exercises'] as $exercise) {
                    if ($this->is_content_changed($exercise['id'], $exercise['type'])) {
                        $lesson_children[] = array(
                            'id' => $exercise['id'],
                            'type' => $exercise['type'],
                            'title' => $exercise['title']
                        );
                    }
                }
                
                // Store lesson with its children
                $lessons_with_children[] = array(
                    'lesson' => $lesson,
                    'children' => $lesson_children
                );
            }
        }
        
        // Add items in the correct order:
        // 1. All courses first
        $changed_items = $courses;
        
        // 2. Then for each lesson: add children first, then the lesson
        foreach ($lessons_with_children as $item) {
            // Add children (resources and quizzes) first
            foreach ($item['children'] as $child) {
                $changed_items[] = $child;
            }
            
            // Then add the lesson itself if it has changed
            if ($this->is_content_changed($item['lesson']['id'], $item['lesson']['type'])) {
                $changed_items[] = array(
                    'id' => $item['lesson']['id'],
                    'type' => $item['lesson']['type'],
                    'title' => $item['lesson']['title']
                );
            }
        }
        
        return $changed_items;
    }
    
    /**
     * Check if content has changed since last successful sync
     */
    private function is_content_changed($content_id, $content_type) {
        global $wpdb;
        
        $current_hash = $this->sync_manager->generate_content_hash($content_id, $content_type);
        if (!$current_hash) {
            return false; // Content doesn't exist
        }
        
        $subsites = $this->sync_manager->get_connected_subsites();
        
        // Check if ANY subsite is out of sync
        foreach ($subsites as $subsite) {
            $table = $this->db->get_content_sync_table();
            
            $last_sync = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table 
                WHERE content_id = %d 
                AND content_type = %s 
                AND site_id = %d 
                AND sync_status = 'success'
                ORDER BY sync_date DESC 
                LIMIT 1",
                $content_id,
                $content_type,
                $subsite->id
            ));
            
            // If never synced or hash doesn't match, content has changed
            if (!$last_sync || $last_sync->content_hash !== $current_hash) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if memory usage exceeds threshold
     */
    private function is_memory_exceeded() {
        $memory_limit = ini_get('memory_limit');
        $memory_limit_mb = $this->parse_memory_limit($memory_limit);
        
        $current_usage = memory_get_usage(true) / 1024 / 1024; // in MB
        
        // If we're using more than the threshold OR more than 80% of limit, stop
        return ($current_usage > self::MEMORY_THRESHOLD_MB) || 
               ($memory_limit_mb > 0 && $current_usage > ($memory_limit_mb * 0.8));
    }
    
    /**
     * Parse memory limit from PHP ini format (e.g., "128M", "1G")
     */
    private function parse_memory_limit($limit) {
        if ($limit == '-1') {
            return -1; // Unlimited
        }
        
        $value = intval($limit);
        $unit = strtoupper(substr($limit, -1));
        
        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                break;
            case 'K':
                $value /= 1024;
                break;
        }
        
        return $value;
    }
    
    /**
     * Log sync activity to database
     */
    private function log_sync($content_type, $message, $status = 'success') {
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_auto_sync_log';
        
        $wpdb->insert(
            $table,
            array(
                'content_type' => sanitize_text_field($content_type),
                'message' => sanitize_text_field($message),
                'status' => sanitize_text_field($status),
                'log_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        // Keep only last 100 log entries to prevent table bloat
        $wpdb->query("DELETE FROM $table WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM $table ORDER BY log_date DESC LIMIT 100
            ) as temp
        )");
    }
    
    /**
     * Get recent sync logs
     */
    public function get_sync_logs($limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_auto_sync_log';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY log_date DESC LIMIT %d",
            $limit
        ));
        
        return $results ? $results : array();
    }
    
    /**
     * Clear all sync logs
     */
    public function clear_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_auto_sync_log';
        $wpdb->query("TRUNCATE TABLE $table");
    }
    
    /**
     * Get last run time
     */
    public function get_last_run() {
        return get_option('ielts_cm_auto_sync_last_run', null);
    }
    
    /**
     * Get next scheduled run time
     */
    public function get_next_run() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
    
    /**
     * Manually trigger a sync run
     */
    public function trigger_manual_sync() {
        $this->run_auto_sync();
    }
}
