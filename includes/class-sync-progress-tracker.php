<?php
/**
 * Sync Progress Tracker
 * Tracks the progress of content synchronization to subsites
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Sync_Progress_Tracker {
    
    private $progress_option_prefix = 'ielts_cm_sync_progress_';
    
    /**
     * Start tracking a new sync operation
     */
    public function start_sync($sync_id, $total_items, $subsites) {
        $progress = array(
            'sync_id' => $sync_id,
            'status' => 'in_progress',
            'total_items' => $total_items,
            'completed_items' => 0,
            'failed_items' => 0,
            'current_item' => '',
            'current_subsite' => '',
            'subsites' => array(),
            'items' => array(),
            'start_time' => current_time('mysql'),
            'last_update' => current_time('mysql'),
            'errors' => array()
        );
        
        // Initialize subsite tracking
        foreach ($subsites as $subsite) {
            $progress['subsites'][$subsite->id] = array(
                'id' => $subsite->id,
                'name' => $subsite->site_name,
                'url' => $subsite->site_url,
                'status' => 'pending',
                'items_synced' => 0,
                'items_failed' => 0,
                'last_error' => null
            );
        }
        
        update_option($this->progress_option_prefix . $sync_id, $progress, false);
        
        return $progress;
    }
    
    /**
     * Update progress for a specific item sync
     */
    public function update_item_progress($sync_id, $item_type, $item_id, $item_title, $subsite_id, $success, $error = null) {
        $progress = $this->get_progress($sync_id);
        if (!$progress) {
            return false;
        }
        
        // Update current item
        $progress['current_item'] = $item_title;
        $progress['current_subsite'] = isset($progress['subsites'][$subsite_id]) ? $progress['subsites'][$subsite_id]['name'] : '';
        
        // Track item completion
        $item_key = $item_type . '_' . $item_id;
        if (!isset($progress['items'][$item_key])) {
            $progress['items'][$item_key] = array(
                'type' => $item_type,
                'id' => $item_id,
                'title' => $item_title,
                'subsites' => array()
            );
        }
        
        $progress['items'][$item_key]['subsites'][$subsite_id] = array(
            'success' => $success,
            'error' => $error,
            'timestamp' => current_time('mysql')
        );
        
        // Update subsite stats
        if (isset($progress['subsites'][$subsite_id])) {
            if ($success) {
                $progress['subsites'][$subsite_id]['items_synced']++;
                $progress['subsites'][$subsite_id]['status'] = 'in_progress';
            } else {
                $progress['subsites'][$subsite_id]['items_failed']++;
                $progress['subsites'][$subsite_id]['last_error'] = $error;
            }
        }
        
        // Update overall counts
        if ($success) {
            $progress['completed_items']++;
        } else {
            $progress['failed_items']++;
            if ($error) {
                $progress['errors'][] = array(
                    'item' => $item_title,
                    'subsite' => isset($progress['subsites'][$subsite_id]) ? $progress['subsites'][$subsite_id]['name'] : '',
                    'error' => $error,
                    'timestamp' => current_time('mysql')
                );
            }
        }
        
        $progress['last_update'] = current_time('mysql');
        
        update_option($this->progress_option_prefix . $sync_id, $progress, false);
        
        return $progress;
    }
    
    /**
     * Mark a subsite as complete
     */
    public function complete_subsite($sync_id, $subsite_id) {
        $progress = $this->get_progress($sync_id);
        if (!$progress || !isset($progress['subsites'][$subsite_id])) {
            return false;
        }
        
        $progress['subsites'][$subsite_id]['status'] = 'completed';
        $progress['last_update'] = current_time('mysql');
        
        update_option($this->progress_option_prefix . $sync_id, $progress, false);
        
        return $progress;
    }
    
    /**
     * Complete the sync operation
     */
    public function complete_sync($sync_id, $success = true) {
        $progress = $this->get_progress($sync_id);
        if (!$progress) {
            return false;
        }
        
        $progress['status'] = $success ? 'completed' : 'failed';
        $progress['end_time'] = current_time('mysql');
        $progress['last_update'] = current_time('mysql');
        
        // Mark all subsites as completed
        foreach ($progress['subsites'] as $subsite_id => $subsite_data) {
            if ($subsite_data['status'] === 'in_progress' || $subsite_data['status'] === 'pending') {
                $progress['subsites'][$subsite_id]['status'] = 'completed';
            }
        }
        
        update_option($this->progress_option_prefix . $sync_id, $progress, false);
        
        return $progress;
    }
    
    /**
     * Get current progress for a sync operation
     */
    public function get_progress($sync_id) {
        return get_option($this->progress_option_prefix . $sync_id, false);
    }
    
    /**
     * Clean up old progress data (older than 24 hours)
     */
    public function cleanup_old_progress() {
        global $wpdb;
        
        $like = $wpdb->esc_like($this->progress_option_prefix) . '%';
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ));
        
        $cutoff_time = strtotime('-24 hours');
        
        foreach ($options as $option) {
            $progress = maybe_unserialize($option->option_value);
            if (is_array($progress) && isset($progress['start_time'])) {
                $start_timestamp = strtotime($progress['start_time']);
                if ($start_timestamp < $cutoff_time) {
                    delete_option($option->option_name);
                }
            }
        }
    }
    
    /**
     * Generate a unique sync ID
     */
    public function generate_sync_id() {
        return uniqid('sync_', true);
    }
}
