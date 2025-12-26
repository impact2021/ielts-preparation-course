<?php
/**
 * REST API for Multi-Site Content Sync
 * Handles incoming sync requests from primary site
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Sync_API {
    
    private $namespace = 'ielts-cm/v1';
    private $sync_manager;
    
    public function __construct() {
        $this->sync_manager = new IELTS_CM_Multi_Site_Sync();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Endpoint for receiving content from primary site
        register_rest_route($this->namespace, '/sync-content', array(
            'methods' => 'POST',
            'callback' => array($this, 'receive_content'),
            'permission_callback' => array($this, 'check_auth_token')
        ));
        
        // Endpoint for testing connection
        register_rest_route($this->namespace, '/test-connection', array(
            'methods' => 'GET',
            'callback' => array($this, 'test_connection'),
            'permission_callback' => array($this, 'check_auth_token')
        ));
        
        // Endpoint for getting site info
        register_rest_route($this->namespace, '/site-info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_site_info'),
            'permission_callback' => array($this, 'check_auth_token')
        ));
    }
    
    /**
     * Check authentication token
     */
    public function check_auth_token($request) {
        $token = $request->get_header('X-IELTS-Auth-Token');
        $stored_token = get_option('ielts_cm_subsite_auth_token', '');
        
        if (empty($stored_token)) {
            return new WP_Error('no_token', 'No authentication token configured', array('status' => 401));
        }
        
        // Use hash_equals to prevent timing attacks
        if (!hash_equals($stored_token, $token)) {
            return new WP_Error('invalid_token', 'Invalid authentication token', array('status' => 403));
        }
        
        return true;
    }
    
    /**
     * Test connection endpoint
     */
    public function test_connection($request) {
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Connection successful',
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name')
        ));
    }
    
    /**
     * Get site info endpoint
     */
    public function get_site_info($request) {
        return rest_ensure_response(array(
            'success' => true,
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'role' => get_option('ielts_cm_site_role', 'standalone'),
            'plugin_version' => IELTS_CM_VERSION
        ));
    }
    
    /**
     * Receive and process content from primary site
     */
    public function receive_content($request) {
        $params = $request->get_json_params();
        
        if (empty($params['content_data']) || empty($params['content_type'])) {
            return new WP_Error('missing_data', 'Content data and type are required', array('status' => 400));
        }
        
        $content_data = $params['content_data'];
        $content_type = $params['content_type'];
        $content_hash = $params['content_hash'] ?? '';
        
        // Validate content hash format (64-character hexadecimal for SHA-256)
        if (!empty($content_hash) && !preg_match('/^[a-f0-9]{64}$/i', $content_hash)) {
            return new WP_Error('invalid_hash', 'Invalid content hash format', array('status' => 400));
        }
        
        // Process content based on type
        $result = $this->process_incoming_content($content_data, $content_type, $content_hash);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Content synced successfully',
            'post_id' => $result
        ));
    }
    
    /**
     * Process incoming content and create/update on subsite
     */
    private function process_incoming_content($content_data, $content_type, $content_hash) {
        // Check if content already exists by matching original ID
        $existing_post_id = $this->find_existing_content($content_data['id'], $content_type);
        
        // Map content type to post type
        $post_type_map = array(
            'course' => 'ielts_course',
            'lesson' => 'ielts_lesson',
            'resource' => 'ielts_resource',
            'quiz' => 'ielts_quiz'
        );
        
        if (!isset($post_type_map[$content_type])) {
            return new WP_Error('invalid_type', 'Invalid content type', array('status' => 400));
        }
        
        $post_type = $post_type_map[$content_type];
        
        // Prepare post data
        $post_data = array(
            'post_title' => $content_data['title'],
            'post_content' => $content_data['content'],
            'post_excerpt' => $content_data['excerpt'] ?? '',
            'post_status' => $content_data['status'] ?? 'publish',
            'post_type' => $post_type,
            'menu_order' => $content_data['menu_order'] ?? 0
        );
        
        // Check if we should preserve completion status
        $preserve_completion = false;
        $user_progress = array();
        
        if ($existing_post_id) {
            // Update existing post
            $post_data['ID'] = $existing_post_id;
            
            // Preserve student progress before updating
            $user_progress = $this->get_user_progress($existing_post_id, $content_type);
            $preserve_completion = !empty($user_progress);
            
            $post_id = wp_update_post($post_data);
        } else {
            // Create new post
            $post_id = wp_insert_post($post_data);
            
            // Store original content ID for future syncs
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_ielts_cm_original_id', $content_data['id']);
                update_post_meta($post_id, '_ielts_cm_synced_from_primary', 1);
            }
        }
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Update metadata
        if (!empty($content_data['metadata'])) {
            foreach ($content_data['metadata'] as $key => $value) {
                // Remap IDs for relational metadata
                $remapped_value = $this->remap_relational_ids($key, $value);
                update_post_meta($post_id, $key, $remapped_value);
            }
        }
        
        // Handle taxonomies for courses
        if ($content_type === 'course' && !empty($content_data['categories'])) {
            wp_set_object_terms($post_id, $content_data['categories'], 'ielts_course_category');
        }
        
        // Handle featured image
        if (!empty($content_data['featured_image_url'])) {
            $this->set_featured_image_from_url($post_id, $content_data['featured_image_url']);
        }
        
        // Restore student progress if content was updated
        if ($preserve_completion && !empty($user_progress)) {
            $this->restore_user_progress($post_id, $content_type, $user_progress);
        }
        
        // Store content hash for future comparison
        update_post_meta($post_id, '_ielts_cm_content_hash', $content_hash);
        update_post_meta($post_id, '_ielts_cm_last_synced', current_time('mysql'));
        
        return $post_id;
    }
    
    /**
     * Find existing content by original ID
     */
    private function find_existing_content($original_id, $content_type) {
        $args = array(
            'post_type' => 'any',
            'meta_key' => '_ielts_cm_original_id',
            'meta_value' => $original_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        );
        
        $posts = get_posts($args);
        return !empty($posts) ? $posts[0]->ID : false;
    }
    
    /**
     * Remap IDs in relational metadata from primary site IDs to subsite IDs
     */
    private function remap_relational_ids($meta_key, $meta_value) {
        // Define which meta keys contain IDs that need to be remapped
        $id_fields = array(
            '_ielts_cm_course_id' => 'course',
            '_ielts_cm_course_ids' => 'course',
            '_ielts_cm_lesson_id' => 'lesson',
            '_ielts_cm_lesson_ids' => 'lesson'
        );
        
        // Check if this meta key needs ID remapping
        if (!isset($id_fields[$meta_key])) {
            return $meta_value;
        }
        
        $content_type = $id_fields[$meta_key];
        
        // Handle single ID
        if ($meta_key === '_ielts_cm_course_id' || $meta_key === '_ielts_cm_lesson_id') {
            if (empty($meta_value)) {
                return $meta_value;
            }
            
            $original_id = intval($meta_value);
            $mapped_id = $this->find_existing_content($original_id, $content_type);
            
            // If we found a mapped ID, use it; otherwise keep original (will be created later)
            return $mapped_id ? $mapped_id : $meta_value;
        }
        
        // Handle array of IDs (could be serialized or JSON)
        if ($meta_key === '_ielts_cm_course_ids' || $meta_key === '_ielts_cm_lesson_ids') {
            $is_serialized = false;
            $ids = false;
            
            // Check if it's serialized data first
            if (is_serialized($meta_value)) {
                $ids = unserialize($meta_value);
                if ($ids !== false && is_array($ids)) {
                    $is_serialized = true;
                }
            }
            
            // If not serialized or unserialize failed, try JSON decode
            if (!is_array($ids)) {
                $ids = json_decode($meta_value, true);
            }
            
            // If still not an array, return original value
            if (!is_array($ids)) {
                return $meta_value;
            }
            
            // Remap each ID in the array
            $mapped_ids = array();
            foreach ($ids as $id) {
                $original_id = intval($id);
                $mapped_id = $this->find_existing_content($original_id, $content_type);
                $mapped_ids[] = $mapped_id ? $mapped_id : $original_id;
            }
            
            // Return in the same format it came in
            if ($is_serialized) {
                return serialize($mapped_ids);
            } else {
                $json = wp_json_encode($mapped_ids);
                return $json !== false ? $json : $meta_value;
            }
        }
        
        return $meta_value;
    }
    
    /**
     * Get user progress for content
     */
    private function get_user_progress($post_id, $content_type) {
        global $wpdb;
        $db = new IELTS_CM_Database();
        $progress = array();
        
        switch ($content_type) {
            case 'course':
            case 'lesson':
            case 'resource':
                $table = $db->get_progress_table();
                $column = $content_type . '_id';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table WHERE $column = %d AND completed = 1",
                    $post_id
                ));
                $progress['completion'] = $results ? $results : array();
                break;
                
            case 'quiz':
                $table = $db->get_quiz_results_table();
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table WHERE quiz_id = %d",
                    $post_id
                ));
                $progress['quiz_results'] = $results ? $results : array();
                break;
        }
        
        return $progress;
    }
    
    /**
     * Restore user progress after content update
     */
    private function restore_user_progress($post_id, $content_type, $user_progress) {
        global $wpdb;
        $db = new IELTS_CM_Database();
        
        // Restore completion status
        if (!empty($user_progress['completion'])) {
            $table = $db->get_progress_table();
            foreach ($user_progress['completion'] as $record) {
                // Check if record still exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table WHERE user_id = %d AND " . $content_type . "_id = %d",
                    $record->user_id,
                    $post_id
                ));
                
                // If record doesn't exist anymore, recreate it with completed status
                if (!$exists) {
                    $data = array(
                        'user_id' => $record->user_id,
                        'completed' => 1,
                        'completed_date' => $record->completed_date,
                        'last_accessed' => $record->last_accessed
                    );
                    
                    // Add type-specific fields
                    switch ($content_type) {
                        case 'course':
                            $data['course_id'] = $post_id;
                            $data['lesson_id'] = 0;
                            break;
                        case 'lesson':
                            $data['lesson_id'] = $post_id;
                            $data['course_id'] = get_post_meta($post_id, '_ielts_cm_course_id', true) ?: 0;
                            break;
                        case 'resource':
                            $data['resource_id'] = $post_id;
                            $data['lesson_id'] = get_post_meta($post_id, '_ielts_cm_lesson_id', true) ?: 0;
                            $data['course_id'] = 0;
                            break;
                    }
                    
                    $wpdb->insert($table, $data);
                }
            }
        }
        
        // Quiz results are preserved automatically since they reference quiz_id
        // which remains the same for the existing post
    }
    
    /**
     * Set featured image from URL
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        // Validate URL to prevent SSRF attacks
        $parsed_url = wp_parse_url($image_url);
        if (!$parsed_url || empty($parsed_url['scheme']) || empty($parsed_url['host'])) {
            return false;
        }
        
        // Block localhost and internal IP addresses
        $host = $parsed_url['host'];
        if (in_array($host, array('localhost', '127.0.0.1', '0.0.0.0', '::1'))) {
            return false;
        }
        
        // Block internal IP ranges
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }
        
        // Check if image already exists in media library
        $attachment_id = attachment_url_to_postid($image_url);
        
        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }
        
        // Download and attach image
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return false;
        }
        
        set_post_thumbnail($post_id, $attachment_id);
        return $attachment_id;
    }
}
