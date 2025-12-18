<?php
/**
 * Multi-Site Content Sync Manager
 * Handles content synchronization between primary and subsites
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Multi_Site_Sync {
    
    private $db;
    
    public function __construct() {
        $this->db = new IELTS_CM_Database();
    }
    
    /**
     * Check if current site is designated as primary
     */
    public function is_primary_site() {
        return get_option('ielts_cm_site_role', 'standalone') === 'primary';
    }
    
    /**
     * Check if current site is a subsite
     */
    public function is_subsite() {
        return get_option('ielts_cm_site_role', 'standalone') === 'subsite';
    }
    
    /**
     * Get all connected subsites
     */
    public function get_connected_subsites() {
        global $wpdb;
        $table = $this->db->get_site_connections_table();
        
        $results = $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'active' ORDER BY site_name ASC"
        );
        
        return $results ? $results : array();
    }
    
    /**
     * Add a new subsite connection
     */
    public function add_subsite($site_name, $site_url, $auth_token) {
        global $wpdb;
        $table = $this->db->get_site_connections_table();
        
        // Validate inputs
        if (empty($site_name) || empty($site_url) || empty($auth_token)) {
            return new WP_Error('invalid_data', 'All fields are required');
        }
        
        // Sanitize URL
        $site_url = esc_url_raw($site_url);
        
        $result = $wpdb->insert(
            $table,
            array(
                'site_name' => sanitize_text_field($site_name),
                'site_url' => $site_url,
                'auth_token' => sanitize_text_field($auth_token),
                'status' => 'active',
                'created_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to add subsite connection');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Remove a subsite connection
     */
    public function remove_subsite($site_id) {
        global $wpdb;
        $table = $this->db->get_site_connections_table();
        
        $result = $wpdb->delete(
            $table,
            array('id' => intval($site_id)),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Update subsite status
     */
    public function update_subsite_status($site_id, $status) {
        global $wpdb;
        $table = $this->db->get_site_connections_table();
        
        $result = $wpdb->update(
            $table,
            array('status' => sanitize_text_field($status)),
            array('id' => intval($site_id)),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Generate content hash for change detection
     */
    public function generate_content_hash($content_id, $content_type) {
        $post = get_post($content_id);
        if (!$post) {
            return false;
        }
        
        // Build content signature
        $signature = array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'modified' => $post->post_modified,
            'type' => $content_type
        );
        
        // Add type-specific metadata
        switch ($content_type) {
            case 'course':
                $signature['lessons'] = get_post_meta($content_id, '_ielts_cm_lessons', true);
                break;
            case 'lesson':
                $signature['course'] = get_post_meta($content_id, '_ielts_cm_course_id', true);
                $signature['pages'] = get_post_meta($content_id, '_ielts_cm_lesson_pages', true);
                $signature['quizzes'] = get_post_meta($content_id, '_ielts_cm_lesson_quizzes', true);
                break;
            case 'resource':
                $signature['lesson'] = get_post_meta($content_id, '_ielts_cm_lesson_id', true);
                $signature['url'] = get_post_meta($content_id, '_ielts_cm_resource_url', true);
                break;
            case 'quiz':
                $signature['questions'] = get_post_meta($content_id, '_ielts_cm_questions', true);
                $signature['passing'] = get_post_meta($content_id, '_ielts_cm_passing_percentage', true);
                break;
        }
        
        // Use json_encode instead of serialize for safer serialization
        return hash('sha256', wp_json_encode($signature));
    }
    
    /**
     * Push content to all connected subsites
     */
    public function push_content_to_subsites($content_id, $content_type) {
        if (!$this->is_primary_site()) {
            return new WP_Error('not_primary', 'Only primary sites can push content');
        }
        
        $subsites = $this->get_connected_subsites();
        if (empty($subsites)) {
            return new WP_Error('no_subsites', 'No connected subsites found');
        }
        
        $results = array();
        $content_hash = $this->generate_content_hash($content_id, $content_type);
        
        foreach ($subsites as $subsite) {
            $result = $this->push_to_subsite($content_id, $content_type, $content_hash, $subsite);
            $results[$subsite->id] = $result;
        }
        
        return $results;
    }
    
    /**
     * Push content to a specific subsite
     */
    private function push_to_subsite($content_id, $content_type, $content_hash, $subsite) {
        // Prepare content data
        $content_data = $this->serialize_content($content_id, $content_type);
        if (is_wp_error($content_data)) {
            return $content_data;
        }
        
        // Make API request to subsite
        $response = wp_remote_post(
            trailingslashit($subsite->site_url) . 'wp-json/ielts-cm/v1/sync-content',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-IELTS-Auth-Token' => $subsite->auth_token
                ),
                'body' => wp_json_encode(array(
                    'content_data' => $content_data,
                    'content_hash' => $content_hash,
                    'content_type' => $content_type
                ))
            )
        );
        
        if (is_wp_error($response)) {
            $this->log_sync($content_id, $content_type, $content_hash, $subsite->id, 'failed');
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['success']) && $body['success']) {
            $this->log_sync($content_id, $content_type, $content_hash, $subsite->id, 'success');
            $this->update_last_sync($subsite->id);
            return array('success' => true, 'message' => $body['message']);
        } else {
            $this->log_sync($content_id, $content_type, $content_hash, $subsite->id, 'failed');
            return new WP_Error('sync_failed', $body['message'] ?? 'Unknown error');
        }
    }
    
    /**
     * Serialize content for transmission
     */
    private function serialize_content($content_id, $content_type) {
        $post = get_post($content_id);
        if (!$post) {
            return new WP_Error('invalid_content', 'Content not found');
        }
        
        $data = array(
            'id' => $content_id,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'menu_order' => $post->menu_order,
            'type' => $content_type,
            'metadata' => array()
        );
        
        // Get all post meta
        $all_meta = get_post_meta($content_id);
        foreach ($all_meta as $key => $values) {
            if (strpos($key, '_ielts_cm_') === 0) {
                $data['metadata'][$key] = $values[0];
            }
        }
        
        // Get taxonomies for courses
        if ($content_type === 'course') {
            $categories = wp_get_post_terms($content_id, 'ielts_course_category');
            $data['categories'] = wp_list_pluck($categories, 'slug');
        }
        
        // Get featured image
        if (has_post_thumbnail($content_id)) {
            $thumbnail_id = get_post_thumbnail_id($content_id);
            $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            $data['featured_image_url'] = $thumbnail_url;
        }
        
        return $data;
    }
    
    /**
     * Log sync activity
     */
    private function log_sync($content_id, $content_type, $content_hash, $site_id, $status) {
        global $wpdb;
        $table = $this->db->get_content_sync_table();
        
        $wpdb->insert(
            $table,
            array(
                'content_id' => intval($content_id),
                'content_type' => sanitize_text_field($content_type),
                'content_hash' => sanitize_text_field($content_hash),
                'site_id' => intval($site_id),
                'sync_date' => current_time('mysql'),
                'sync_status' => sanitize_text_field($status)
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Update last sync time for a subsite
     */
    private function update_last_sync($site_id) {
        global $wpdb;
        $table = $this->db->get_site_connections_table();
        
        $wpdb->update(
            $table,
            array('last_sync' => current_time('mysql')),
            array('id' => intval($site_id)),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Get sync history for content
     */
    public function get_sync_history($content_id, $content_type = null) {
        global $wpdb;
        $table = $this->db->get_content_sync_table();
        
        if ($content_type) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE content_id = %d AND content_type = %s ORDER BY sync_date DESC",
                $content_id,
                $content_type
            ));
        } else {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE content_id = %d ORDER BY sync_date DESC",
                $content_id
            ));
        }
        
        return $results ? $results : array();
    }
}
