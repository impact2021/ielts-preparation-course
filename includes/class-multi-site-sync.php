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
        
        // Calculate appropriate timeout based on content size
        // Larger content needs more time to transmit and process
        $content_size = strlen(wp_json_encode($content_data));
        $timeout = 30; // Base timeout of 30 seconds
        
        // Add 1 second per 10KB above the base 10KB (min 30s, max 120s)
        if ($content_size > 10240) { // > 10KB
            $additional_time = ceil(($content_size - 10240) / 10240);
            $timeout = min(120, 30 + $additional_time);
        }
        
        // Make API request to subsite
        $response = wp_remote_post(
            trailingslashit($subsite->site_url) . 'wp-json/ielts-cm/v1/sync-content',
            array(
                'timeout' => $timeout,
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
            // Enhance error message with more context
            $error_message = sprintf(
                'Failed to connect to subsite "%s": %s',
                $subsite->site_name,
                $response->get_error_message()
            );
            return new WP_Error($response->get_error_code(), $error_message);
        }
        
        // Check HTTP response code
        $status_code = wp_remote_retrieve_response_code($response);
        // Ensure we have a valid status code before checking
        if (!is_numeric($status_code) || $status_code < 200 || $status_code >= 300) {
            $this->log_sync($content_id, $content_type, $content_hash, $subsite->id, 'failed');
            $error_message = sprintf(
                'Subsite "%s" returned HTTP error %d. Please check the subsite is configured correctly and the REST API endpoint is available.',
                $subsite->site_name,
                is_numeric($status_code) ? intval($status_code) : 0
            );
            return new WP_Error('http_error', $error_message);
        }
        
        // Decode and validate response body
        $response_body = wp_remote_retrieve_body($response);
        $body = json_decode($response_body, true);
        
        // Check if JSON decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
            $this->log_sync($content_id, $content_type, $content_hash, $subsite->id, 'failed');
            // First escape HTML, then redact sensitive data from the safe string
            $safe_response = esc_html(substr($response_body, 0, 200));
            $sanitized_response = preg_replace(
                '/(token|password|key|secret|auth)(["\']?\s*[:=]\s*["\']?)([^,}\s&"\']+)/i',
                '$1$2***REDACTED***',
                $safe_response
            );
            $error_message = sprintf(
                'Subsite "%s" returned invalid JSON response. Response: %s',
                $subsite->site_name,
                $sanitized_response
            );
            return new WP_Error('invalid_response', $error_message);
        }
        
        if (isset($body['success']) && $body['success']) {
            $this->log_sync($content_id, $content_type, $content_hash, $subsite->id, 'success');
            $this->update_last_sync($subsite->id);
            return array('success' => true, 'message' => $body['message'] ?? 'Content synced successfully');
        } else {
            $this->log_sync($content_id, $content_type, $content_hash, $subsite->id, 'failed');
            // Provide a meaningful error message even if the response doesn't include one
            $error_message = isset($body['message']) && !empty($body['message']) 
                ? $body['message'] 
                : sprintf('Subsite "%s" rejected the sync request. Please check authentication and permissions.', $subsite->site_name);
            return new WP_Error('sync_failed', $error_message);
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
        
        // Increase PHP execution time limit for large sync operations
        // This prevents timeouts when syncing courses with many lessons
        $original_time_limit = ini_get('max_execution_time');
        if ($original_time_limit !== '0') { // Only set if not already unlimited
            // Suppress warning if function is disabled in php.ini
            @set_time_limit(300); // 5 minutes should be enough for most courses
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
    
    /**
     * Get all courses with their complete hierarchy
     */
    public function get_all_courses_with_hierarchy() {
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        $courses_data = array();
        
        foreach ($courses as $course) {
            $course_data = array(
                'id' => $course->ID,
                'title' => $course->post_title,
                'type' => 'course',
                'lessons' => array()
            );
            
            // Get lessons for this course
            $lessons = $this->get_course_lessons($course->ID);
            
            foreach ($lessons as $lesson) {
                $lesson_data = array(
                    'id' => $lesson->ID,
                    'title' => $lesson->post_title,
                    'type' => 'lesson',
                    'resources' => array(),
                    'exercises' => array()
                );
                
                // Get resources (sublessons) for this lesson
                $resources = $this->get_lesson_resources($lesson->ID);
                foreach ($resources as $resource) {
                    $lesson_data['resources'][] = array(
                        'id' => $resource->ID,
                        'title' => $resource->post_title,
                        'type' => 'resource'
                    );
                }
                
                // Get exercises (quizzes) for this lesson
                $exercises = $this->get_lesson_exercises($lesson->ID);
                foreach ($exercises as $exercise) {
                    $lesson_data['exercises'][] = array(
                        'id' => $exercise->ID,
                        'title' => $exercise->post_title,
                        'type' => 'quiz'
                    );
                }
                
                $course_data['lessons'][] = $lesson_data;
            }
            
            $courses_data[] = $course_data;
        }
        
        return $courses_data;
    }
    
    /**
     * Get sync status for a specific content item across all subsites
     */
    public function get_content_sync_status($content_id, $content_type) {
        global $wpdb;
        
        $subsites = $this->get_connected_subsites();
        $content_hash = $this->generate_content_hash($content_id, $content_type);
        $table = $this->db->get_content_sync_table();
        
        $status_data = array(
            'content_id' => $content_id,
            'content_type' => $content_type,
            'current_hash' => $content_hash,
            'subsites' => array()
        );
        
        foreach ($subsites as $subsite) {
            // Get the latest sync record for this content and subsite
            $latest_sync = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table 
                WHERE content_id = %d 
                AND content_type = %s 
                AND site_id = %d 
                ORDER BY sync_date DESC 
                LIMIT 1",
                $content_id,
                $content_type,
                $subsite->id
            ));
            
            if ($latest_sync) {
                $is_synced = ($latest_sync->content_hash === $content_hash && $latest_sync->sync_status === 'success');
                $status_data['subsites'][$subsite->id] = array(
                    'site_name' => $subsite->site_name,
                    'synced' => $is_synced,
                    'last_sync' => $latest_sync->sync_date,
                    'sync_status' => $latest_sync->sync_status,
                    'hash' => $latest_sync->content_hash
                );
            } else {
                // Never synced
                $status_data['subsites'][$subsite->id] = array(
                    'site_name' => $subsite->site_name,
                    'synced' => false,
                    'last_sync' => null,
                    'sync_status' => 'never_synced',
                    'hash' => null
                );
            }
        }
        
        return $status_data;
    }
    
    /**
     * Get comprehensive sync status for all content
     */
    public function get_all_content_sync_status() {
        $courses_hierarchy = $this->get_all_courses_with_hierarchy();
        $subsites = $this->get_connected_subsites();
        
        $status_summary = array(
            'total_items' => 0,
            'synced_items' => 0,
            'out_of_sync_items' => 0,
            'never_synced_items' => 0,
            'subsites' => array()
        );
        
        foreach ($subsites as $subsite) {
            $status_summary['subsites'][$subsite->id] = array(
                'site_name' => $subsite->site_name,
                'total' => 0,
                'synced' => 0,
                'out_of_sync' => 0,
                'never_synced' => 0
            );
        }
        
        // Iterate through all content and check sync status
        foreach ($courses_hierarchy as $course) {
            $this->update_status_for_content($course, $status_summary);
            
            foreach ($course['lessons'] as $lesson) {
                $this->update_status_for_content($lesson, $status_summary);
                
                foreach ($lesson['resources'] as $resource) {
                    $this->update_status_for_content($resource, $status_summary);
                }
                
                foreach ($lesson['exercises'] as $exercise) {
                    $this->update_status_for_content($exercise, $status_summary);
                }
            }
        }
        
        return $status_summary;
    }
    
    /**
     * Helper to update status summary for a content item
     */
    private function update_status_for_content($content, &$status_summary) {
        $status = $this->get_content_sync_status($content['id'], $content['type']);
        $status_summary['total_items']++;
        
        $all_synced = true;
        $any_synced = false;
        
        foreach ($status['subsites'] as $site_id => $site_status) {
            $status_summary['subsites'][$site_id]['total']++;
            
            if ($site_status['synced']) {
                $status_summary['subsites'][$site_id]['synced']++;
                $any_synced = true;
            } else if ($site_status['sync_status'] === 'never_synced') {
                $status_summary['subsites'][$site_id]['never_synced']++;
                $all_synced = false;
            } else {
                $status_summary['subsites'][$site_id]['out_of_sync']++;
                $all_synced = false;
            }
        }
        
        if ($all_synced && count($status['subsites']) > 0) {
            $status_summary['synced_items']++;
        } else if (!$any_synced) {
            $status_summary['never_synced_items']++;
        } else {
            $status_summary['out_of_sync_items']++;
        }
    }
}
