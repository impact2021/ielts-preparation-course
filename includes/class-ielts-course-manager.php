<?php
/**
 * Main plugin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_Course_Manager {
    
    protected $post_types;
    protected $database;
    protected $progress_tracker;
    protected $quiz_handler;
    protected $shortcodes;
    protected $enrollment;
    protected $admin;
    protected $frontend;
    protected $sync_manager;
    protected $sync_api;
    protected $sync_settings_page;
    protected $payment_receipt;
    
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }
    
    private function load_dependencies() {
        // Dependencies are already loaded in main plugin file
    }
    
    private function init_components() {
        $this->post_types = new IELTS_CM_Post_Types();
        $this->database = new IELTS_CM_Database();
        $this->progress_tracker = new IELTS_CM_Progress_Tracker();
        $this->quiz_handler = new IELTS_CM_Quiz_Handler();
        $this->shortcodes = new IELTS_CM_Shortcodes();
        $this->enrollment = new IELTS_CM_Enrollment();
        $this->admin = new IELTS_CM_Admin();
        $this->frontend = new IELTS_CM_Frontend();
        $this->sync_manager = new IELTS_CM_Multi_Site_Sync();
        $this->sync_api = new IELTS_CM_Sync_API();
        $this->sync_settings_page = new IELTS_CM_Sync_Settings_Page();
        $this->payment_receipt = IELTS_CM_Payment_Receipt::get_instance();
    }
    
    public function run() {
        // Register post types
        add_action('init', array($this->post_types, 'register_post_types'));
        
        // Check for version update and flush permalinks if needed
        add_action('init', array($this, 'check_version_update'));
        
        // Register shortcodes on init hook
        add_action('init', array($this->shortcodes, 'register'));
        
        // Register REST API routes
        add_action('rest_api_init', array($this->sync_api, 'register_routes'));
        
        // Fix serialized data during WordPress import
        add_filter('wp_import_post_meta', array($this, 'fix_imported_serialized_data'), 10, 3);
        
        // Initialize admin
        if (is_admin()) {
            $this->admin->init();
            
            // Initialize sync settings page
            add_action('admin_menu', array($this->sync_settings_page, 'add_menu_page'));
            add_action('admin_init', array($this->sync_settings_page, 'handle_form_submit'));
        }
        
        // Initialize frontend
        $this->frontend->init();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Check if plugin version has been updated and flush permalinks if needed
     * Uses a transient to avoid checking on every page load
     */
    public function check_version_update() {
        // Use a transient to avoid checking on every page load
        $version_checked = get_transient('ielts_cm_version_checked');
        
        if ($version_checked === IELTS_CM_VERSION) {
            // Version already checked and is current
            return;
        }
        
        $current_version = get_option('ielts_cm_version');
        
        // If version has changed, flush rewrite rules and update version
        if ($current_version !== IELTS_CM_VERSION) {
            // Run upgrade routine to ensure all database tables exist
            IELTS_CM_Database::create_tables();
            
            flush_rewrite_rules();
            update_option('ielts_cm_version', IELTS_CM_VERSION);
            // Set transient after flushing to confirm version is updated
            set_transient('ielts_cm_version_checked', IELTS_CM_VERSION, HOUR_IN_SECONDS);
        } else {
            // Version is current but transient expired, reset it without flushing
            set_transient('ielts_cm_version_checked', IELTS_CM_VERSION, HOUR_IN_SECONDS);
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('ielts-cm-frontend', IELTS_CM_PLUGIN_URL . 'assets/css/frontend.css', array(), IELTS_CM_VERSION);
        wp_enqueue_script('ielts-cm-frontend', IELTS_CM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), IELTS_CM_VERSION, true);
        
        wp_localize_script('ielts-cm-frontend', 'ieltsCM', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ielts_cm_nonce')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        wp_enqueue_style('ielts-cm-admin', IELTS_CM_PLUGIN_URL . 'assets/css/admin.css', array(), IELTS_CM_VERSION);
        wp_enqueue_script('ielts-cm-admin', IELTS_CM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), IELTS_CM_VERSION, true);
        
        // Localize script for course edit pages
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            global $post;
            if ($post && $post->post_type === 'ielts_course') {
                wp_localize_script('ielts-cm-admin', 'ieltsCMAdmin', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'lessonOrderNonce' => wp_create_nonce('ielts_cm_lesson_order'),
                    'courseLessonsNonce' => wp_create_nonce('ielts_cm_course_lessons'),
                    'courseMetaNonce' => wp_create_nonce('ielts_cm_course_meta'),
                    'courseId' => $post->ID,
                    'i18n' => array(
                        'orderUpdated' => __('Lesson order updated successfully!', 'ielts-course-manager'),
                        'orderFailed' => __('Failed to update lesson order. Please try again.', 'ielts-course-manager'),
                        'orderError' => __('An error occurred. Please try again.', 'ielts-course-manager'),
                        'orderLabel' => __('Order:', 'ielts-course-manager')
                    )
                ));
            } elseif ($post && $post->post_type === 'ielts_lesson') {
                wp_localize_script('ielts-cm-admin', 'ieltsCMAdmin', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'pageOrderNonce' => wp_create_nonce('ielts_cm_page_order'),
                    'contentOrderNonce' => wp_create_nonce('ielts_cm_content_order'),
                    'lessonContentNonce' => wp_create_nonce('ielts_cm_lesson_content'),
                    'lessonId' => $post->ID,
                    'i18n' => array(
                        'pageOrderUpdated' => __('Lesson page order updated successfully!', 'ielts-course-manager'),
                        'pageOrderFailed' => __('Failed to update lesson page order. Please try again.', 'ielts-course-manager'),
                        'pageOrderError' => __('An error occurred. Please try again.', 'ielts-course-manager'),
                        'contentOrderUpdated' => __('Content order updated successfully!', 'ielts-course-manager'),
                        'contentOrderFailed' => __('Failed to update content order. Please try again.', 'ielts-course-manager'),
                        'contentOrderError' => __('An error occurred. Please try again.', 'ielts-course-manager'),
                        'orderLabel' => __('Order:', 'ielts-course-manager')
                    )
                ));
            }
        }
    }
    
    /**
     * Fix broken serialized data during WordPress import
     * 
     * This fixes a common issue where serialized PHP data in XML imports has
     * incorrect string lengths due to line ending differences (LF vs CRLF).
     * 
     * @param array $postmeta Array of post meta data
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @return array Fixed post meta data
     */
    public function fix_imported_serialized_data($postmeta, $post_id, $post) {
        // Only process IELTS quiz post types
        // $post is an array in the wp_import_post_meta filter
        if (!isset($post['post_type']) || $post['post_type'] !== 'ielts_quiz') {
            return $postmeta;
        }
        
        foreach ($postmeta as $index => $meta) {
            $meta_key = $meta['key'];
            $meta_value = $meta['value'];
            
            // Only fix our plugin's serialized fields
            if (in_array($meta_key, array('_ielts_cm_questions', '_ielts_cm_reading_texts', '_ielts_cm_course_ids', '_ielts_cm_lesson_ids'))) {
                // Check if this looks like serialized data
                if (is_string($meta_value) && (substr($meta_value, 0, 2) === 'a:' || substr($meta_value, 0, 2) === 'O:')) {
                    // Try to unserialize it
                    $test = @unserialize($meta_value);
                    
                    if ($test === false && $meta_value !== serialize(false)) {
                        // Serialization is broken - try to fix it
                        $fixed_value = $this->fix_serialized_string_lengths($meta_value);
                        
                        // Test if the fix worked
                        $test_fixed = @unserialize($fixed_value);
                        if ($test_fixed !== false || $fixed_value === serialize(false)) {
                            // Fix worked - update the meta value
                            $postmeta[$index]['value'] = $fixed_value;
                        }
                    }
                }
            }
        }
        
        return $postmeta;
    }
    
    /**
     * Fix string lengths in serialized data
     * 
     * Recalculates and fixes string length declarations in serialized PHP data.
     * This handles the case where line endings have been modified during export/import.
     * 
     * @param string $data Serialized data
     * @return string Fixed serialized data
     */
    private function fix_serialized_string_lengths($data) {
        // Fix string length declarations using non-greedy matching with DOTALL modifier
        // Pattern matches: s:123:"string content"; where string content can span multiple lines
        $fixed = preg_replace_callback(
            '/s:(\d+):"(.*?)";/s',  // 's' modifier allows . to match newlines
            function($matches) {
                $declared_length = (int)$matches[1];
                $string_content = $matches[2];
                $actual_length = strlen($string_content);
                
                if ($declared_length !== $actual_length) {
                    // Length mismatch - return corrected version
                    return 's:' . $actual_length . ':"' . $string_content . '";';
                }
                return $matches[0];
            },
            $data
        );
        
        return $fixed;
    }
}
