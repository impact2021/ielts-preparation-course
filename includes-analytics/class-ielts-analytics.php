<?php
/**
 * Main Analytics Plugin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_Analytics {
    
    /**
     * The plugin version
     */
    protected $version;
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->version = IELTS_ANALYTICS_VERSION;
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        // Initialize database
        $database = new IELTS_Analytics_Database();
        
        // Initialize reports
        $reports = new IELTS_Analytics_Reports();
        
        // Initialize admin
        if (is_admin()) {
            $admin = new IELTS_Analytics_Admin();
        }
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('IELTS Analytics', 'ielts-analytics'),
            __('IELTS Analytics', 'ielts-analytics'),
            'manage_options',
            'ielts-analytics',
            array($this, 'render_analytics_page'),
            'dashicons-chart-bar',
            30
        );
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('IELTS Analytics Dashboard', 'ielts-analytics'); ?></h1>
            <div class="analytics-dashboard">
                <p><?php echo esc_html__('Welcome to IELTS Analytics! This plugin provides comprehensive analytics and reporting for your IELTS courses.', 'ielts-analytics'); ?></p>
                
                <div class="analytics-section">
                    <h2><?php echo esc_html__('Overview', 'ielts-analytics'); ?></h2>
                    <p><?php echo esc_html__('Track student progress, quiz performance, and course completion rates.', 'ielts-analytics'); ?></p>
                </div>
                
                <div class="analytics-section">
                    <h2><?php echo esc_html__('Features', 'ielts-analytics'); ?></h2>
                    <ul>
                        <li><?php echo esc_html__('Student progress tracking', 'ielts-analytics'); ?></li>
                        <li><?php echo esc_html__('Quiz performance analytics', 'ielts-analytics'); ?></li>
                        <li><?php echo esc_html__('Course completion reports', 'ielts-analytics'); ?></li>
                        <li><?php echo esc_html__('Detailed insights and visualizations', 'ielts-analytics'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
}
