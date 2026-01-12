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
            $error_message = 'Failed to add subsite connection';
            if (!empty($wpdb->last_error)) {
                // Sanitize the database error for safe display
                $error_message .= ': ' . esc_html($wpdb->last_error);
            }
            return new WP_Error('db_error', $error_message);
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
                // Use get_post_meta with single=true to ensure proper unserialization
                $data['metadata'][$key] = get_post_meta($content_id, $key, true);
            }
        }
        
        // Get taxonomies for courses
        if ($content_type === 'course') {
            $categories = wp_get_post_terms($content_id, 'ielts_course_category');
            $data['categories'] = wp_list_pluck($categories, 'slug');
            
            // Get list of current lesson IDs for this course
            // This allows subsites to remove lessons that are no longer in the course
            $lessons = $this->get_course_lessons($content_id);
            $data['current_lesson_ids'] = wp_list_pluck($lessons, 'ID');
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
    
    /**
     * Get all lessons associated with a course
     */
    private function get_course_lessons($course_id) {
        global $wpdb;
        
        // Get lessons that have this course in their course_ids
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_course_ids' AND (
                   meta_value LIKE %s OR
                   meta_value LIKE %s OR
                   meta_value LIKE %s OR
                   meta_value = %s
               ))
        ", 
            $course_id,
            '%' . $wpdb->esc_like('i:' . $course_id . ';') . '%',  // Serialized array format
            '%' . $wpdb->esc_like('"' . $course_id . '"') . '%',   // JSON format
            '%' . $wpdb->esc_like(':' . $course_id . '}') . '%',   // End of serialized array
            serialize(array($course_id))                            // Single item array
        ));
        
        if (empty($lesson_ids)) {
            return array();
        }
        
        return get_posts(array(
            'post_type' => 'ielts_lesson',
            'posts_per_page' => -1,
            'post__in' => $lesson_ids,
            'post_status' => 'any',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
    }
    
    /**
     * Get all resources (sublessons) associated with a lesson
     */
    private function get_lesson_resources($lesson_id) {
        global $wpdb;
        
        // Get resources that have this lesson in their lesson_ids
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND (
                   meta_value LIKE %s OR
                   meta_value LIKE %s OR
                   meta_value LIKE %s OR
                   meta_value = %s
               ))
        ", 
            $lesson_id,
            '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%',
            '%' . $wpdb->esc_like('"' . $lesson_id . '"') . '%',
            '%' . $wpdb->esc_like(':' . $lesson_id . '}') . '%',
            serialize(array($lesson_id))
        ));
        
        if (empty($resource_ids)) {
            return array();
        }
        
        return get_posts(array(
            'post_type' => 'ielts_resource',
            'posts_per_page' => -1,
            'post__in' => $resource_ids,
            'post_status' => 'any',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
    }
    
    /**
     * Get all exercises (quizzes) associated with a lesson
     */
    private function get_lesson_exercises($lesson_id) {
        global $wpdb;
        
        // Get quizzes that have this lesson in their lesson_ids
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND (
                   meta_value LIKE %s OR
                   meta_value LIKE %s OR
                   meta_value LIKE %s OR
                   meta_value = %s
               ))
        ", 
            $lesson_id,
            '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%',
            '%' . $wpdb->esc_like('"' . $lesson_id . '"') . '%',
            '%' . $wpdb->esc_like(':' . $lesson_id . '}') . '%',
            serialize(array($lesson_id))
        ));
        
        if (empty($quiz_ids)) {
            return array();
        }
        
        return get_posts(array(
            'post_type' => 'ielts_quiz',
            'posts_per_page' => -1,
            'post__in' => $quiz_ids,
            'post_status' => 'any',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
    }
    
    /**
     * Push lesson children (resources and exercises) to subsites
     * Helper method to avoid code duplication
     * 
     * @param int $lesson_id Lesson ID
     * @return array Array with 'resources' and 'exercises' results
     */
    private function push_lesson_children($lesson_id) {
        $result = array(
            'resources' => array(),
            'exercises' => array()
        );
        
        // Push all resources (sub-lessons) for this lesson
        $resources = $this->get_lesson_resources($lesson_id);
        foreach ($resources as $resource) {
            $resource_results = $this->push_content_to_subsites($resource->ID, 'resource');
            $result['resources'][$resource->ID] = array(
                'title' => $resource->post_title,
                'sync_results' => $resource_results
            );
        }
        
        // Push all exercises (quizzes) for this lesson
        $exercises = $this->get_lesson_exercises($lesson_id);
        foreach ($exercises as $exercise) {
            $exercise_results = $this->push_content_to_subsites($exercise->ID, 'quiz');
            $result['exercises'][$exercise->ID] = array(
                'title' => $exercise->post_title,
                'sync_results' => $exercise_results
            );
        }
        
        return $result;
    }
    
    /**
     * Push content and all its children to subsites
     */
    public function push_content_with_children($content_id, $content_type) {
        if (!$this->is_primary_site()) {
            return new WP_Error('not_primary', 'Only primary sites can push content');
        }
        
        $subsites = $this->get_connected_subsites();
        if (empty($subsites)) {
            return new WP_Error('no_subsites', 'No connected subsites found');
        }
        
        $results = array();
        
        // Push the main content first
        $main_results = $this->push_content_to_subsites($content_id, $content_type);
        $results['main'] = $main_results;
        
        // If it's a course, push all lessons, resources, and exercises
        if ($content_type === 'course') {
            $lessons = $this->get_course_lessons($content_id);
            $results['lessons'] = array();
            
            foreach ($lessons as $lesson) {
                $lesson_results = $this->push_content_to_subsites($lesson->ID, 'lesson');
                $results['lessons'][$lesson->ID] = array(
                    'title' => $lesson->post_title,
                    'sync_results' => $lesson_results
                );
                
                // Push all resources and exercises for this lesson using helper
                $lesson_children = $this->push_lesson_children($lesson->ID);
                $results['lessons'][$lesson->ID]['resources'] = $lesson_children['resources'];
                $results['lessons'][$lesson->ID]['exercises'] = $lesson_children['exercises'];
            }
        }
        
        // If it's a lesson, push all resources and exercises for this lesson
        if ($content_type === 'lesson') {
            // Use helper method to push lesson children
            $lesson_children = $this->push_lesson_children($content_id);
            $results['resources'] = $lesson_children['resources'];
            $results['exercises'] = $lesson_children['exercises'];
        }
        
        return $results;
    }
}
