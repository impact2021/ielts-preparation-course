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
    protected $import_page;
    protected $export_page;
    protected $structure_rebuild_page;
    protected $frontend;
    
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
        $this->import_page = new IELTS_CM_Import_Page();
        $this->export_page = new IELTS_CM_Export_Page();
        $this->structure_rebuild_page = new IELTS_CM_Structure_Rebuild_Page();
        $this->frontend = new IELTS_CM_Frontend();
    }
    
    public function run() {
        // Register post types
        add_action('init', array($this->post_types, 'register_post_types'));
        
        // Check for version update and flush permalinks if needed
        add_action('init', array($this, 'check_version_update'));
        
        // Initialize admin
        if (is_admin()) {
            $this->admin->init();
            $this->import_page->init();
            $this->export_page->init();
            $this->structure_rebuild_page->init();
        }
        
        // Initialize frontend
        $this->frontend->init();
        
        // Register shortcodes
        $this->shortcodes->register();
        
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
}
