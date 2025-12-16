<?php
/**
 * Admin Import Page
 * 
 * Provides UI for importing LearnDash XML exports
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Import_Page {
    
    /**
     * Initialize the import page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_import_menu'));
        add_action('admin_post_ielts_cm_import_xml', array($this, 'handle_import'));
    }
    
    /**
     * Add import menu page
     */
    public function add_import_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Import from LearnDash', 'ielts-course-manager'),
            __('Import from LearnDash', 'ielts-course-manager'),
            'manage_options',
            'ielts-import-learndash',
            array($this, 'render_import_page')
        );
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Import from LearnDash', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('About LearnDash Import:', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('This tool allows you to import courses, lessons, lesson pages (topics), and quizzes from LearnDash XML export files.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="import-instructions" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                <h2><?php _e('How to Import from LearnDash', 'ielts-course-manager'); ?></h2>
                
                <h3><?php _e('Step 1: Export from LearnDash', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('In your LearnDash site, go to <strong>Tools > Export</strong>', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Select the content types you want to export:', 'ielts-course-manager'); ?>
                        <ul>
                            <li><?php _e('Courses (sfwd-courses)', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Lessons (sfwd-lessons)', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Topics (sfwd-topic) - these become Lesson pages', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Quizzes (sfwd-quiz)', 'ielts-course-manager'); ?></li>
                        </ul>
                    </li>
                    <li><?php _e('Click <strong>Download Export File</strong> to save the XML file', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Step 2: Import to IELTS Course Manager', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Use the form below to upload your LearnDash XML export file', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Choose your import options', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Click <strong>Import XML File</strong>', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Review the import results', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Important Notes:', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('<strong>Large Imports:</strong> For sites with 25 courses and hundreds of lessons, consider splitting your export into smaller files (e.g., 5-10 courses per file).', 'ielts-course-manager'); ?></li>
                    <li><?php _e('<strong>Quiz Questions:</strong> LearnDash quiz questions may need manual review after import due to different quiz systems.', 'ielts-course-manager'); ?></li>
                    <li><?php _e('<strong>Course Structure:</strong> The importer automatically maintains the relationships between courses, lessons, and lesson pages.', 'ielts-course-manager'); ?></li>
                    <li><?php _e('<strong>Backup First:</strong> Always backup your database before importing to ensure you can rollback if needed.', 'ielts-course-manager'); ?></li>
                    <li><?php _e('<strong>Progress Data:</strong> User progress and enrollment data from LearnDash is not imported. Students will need to re-enroll.', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
            
            <div class="import-form" style="max-width: 600px; margin: 20px 0;">
                <h2><?php _e('Upload LearnDash XML Export', 'ielts-course-manager'); ?></h2>
                
                <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('ielts_cm_import_xml', 'ielts_cm_import_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_import_xml">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_file"><?php _e('XML File', 'ielts-course-manager'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".xml" required>
                                <p class="description">
                                    <?php _e('Select the LearnDash XML export file to import.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e('Import Options', 'ielts-course-manager'); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                                        <?php _e('Skip items with duplicate titles', 'ielts-course-manager'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('If checked, items with the same title as existing content will be skipped.', 'ielts-course-manager'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Import XML File', 'ielts-course-manager'), 'primary', 'submit', true); ?>
                </form>
            </div>
            
            <div class="import-tips" style="max-width: 900px; margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                <h3><?php _e('Tips for Large Imports (25+ Courses)', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><strong><?php _e('Split Your Export:', 'ielts-course-manager'); ?></strong> <?php _e('Export courses in batches (e.g., 5-10 courses at a time) to avoid timeouts and memory issues.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Increase PHP Limits:', 'ielts-course-manager'); ?></strong> <?php _e('For very large imports, temporarily increase PHP memory_limit and max_execution_time in your php.ini or wp-config.php:', 'ielts-course-manager'); ?>
                        <pre style="background: #fff; padding: 10px; margin: 10px 0;">
define('WP_MEMORY_LIMIT', '256M');
set_time_limit(300); // 5 minutes</pre>
                    </li>
                    <li><strong><?php _e('Import in Order:', 'ielts-course-manager'); ?></strong> <?php _e('Import courses first, then lessons, then topics and quizzes to maintain relationships.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Test First:', 'ielts-course-manager'); ?></strong> <?php _e('Import a single course first to verify the process works correctly before importing all content.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Use Command Line:', 'ielts-course-manager'); ?></strong> <?php _e('For very large imports, consider using WP-CLI for better performance and no timeout issues.', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .import-instructions h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #1d2327;
        }
        .import-instructions ul,
        .import-instructions ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        .import-instructions ul ul,
        .import-instructions ol ul {
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .import-tips ul {
            margin-left: 20px;
            line-height: 1.8;
        }
        .import-tips pre {
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        </style>
        <?php
    }
    
    /**
     * Handle XML import
     */
    public function handle_import() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_import_nonce']) || !wp_verify_nonce($_POST['ielts_cm_import_nonce'], 'ielts_cm_import_xml')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-learndash',
                'error' => 'upload_failed'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Validate file type (both extension and MIME type)
        $file_info = pathinfo($_FILES['import_file']['name']);
        $file_type = wp_check_filetype($_FILES['import_file']['name'], array('xml' => 'application/xml', 'xml' => 'text/xml'));
        
        if (!isset($file_info['extension']) || strtolower($file_info['extension']) !== 'xml' || 
            !in_array($file_type['type'], array('application/xml', 'text/xml'))) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-learndash',
                'error' => 'invalid_file_type'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Move uploaded file to temp location
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/ielts-import-' . time() . '.xml';
        
        if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $temp_file)) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-learndash',
                'error' => 'file_move_failed'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Prepare import options
        $options = array(
            'skip_duplicates' => isset($_POST['skip_duplicates']) ? true : false
        );
        
        // Perform import
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-learndash-importer.php';
        $importer = new IELTS_CM_LearnDash_Importer();
        $results = $importer->import_xml($temp_file, $options);
        
        // Clean up temp file
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        // Store results in transient for display
        set_transient('ielts_cm_import_results', $results, 60);
        
        // Redirect with success
        wp_redirect(add_query_arg(array(
            'page' => 'ielts-import-learndash',
            'imported' => '1'
        ), admin_url('edit.php?post_type=ielts_course')));
        exit;
    }
    
    /**
     * Display import results
     */
    public static function display_import_notice() {
        if (isset($_GET['page']) && $_GET['page'] === 'ielts-import-learndash') {
            // Check for errors
            if (isset($_GET['error'])) {
                $error = sanitize_text_field($_GET['error']);
                $messages = array(
                    'upload_failed' => __('File upload failed. Please try again.', 'ielts-course-manager'),
                    'invalid_file_type' => __('Invalid file type. Please upload an XML file.', 'ielts-course-manager'),
                    'file_move_failed' => __('Failed to process uploaded file. Please check file permissions.', 'ielts-course-manager')
                );
                
                if (isset($messages[$error])) {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($messages[$error]) . '</p></div>';
                }
            }
            
            // Check for success
            if (isset($_GET['imported'])) {
                $results = get_transient('ielts_cm_import_results');
                if ($results) {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p><strong>' . __('Import completed successfully!', 'ielts-course-manager') . '</strong></p>';
                    echo '<ul>';
                    echo '<li>' . sprintf(__('Courses imported: %d', 'ielts-course-manager'), $results['courses']) . '</li>';
                    echo '<li>' . sprintf(__('Lessons imported: %d', 'ielts-course-manager'), $results['lessons']) . '</li>';
                    echo '<li>' . sprintf(__('Lesson pages imported: %d', 'ielts-course-manager'), $results['topics']) . '</li>';
                    echo '<li>' . sprintf(__('Quizzes imported: %d', 'ielts-course-manager'), $results['quizzes']) . '</li>';
                    echo '</ul>';
                    
                    // Display log if there were warnings or errors
                    $has_issues = false;
                    foreach ($results['log'] as $log_entry) {
                        if ($log_entry['level'] !== 'info') {
                            $has_issues = true;
                            break;
                        }
                    }
                    
                    if ($has_issues) {
                        echo '<p><strong>' . __('Import Log:', 'ielts-course-manager') . '</strong></p>';
                        echo '<div style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">';
                        foreach ($results['log'] as $log_entry) {
                            $color = $log_entry['level'] === 'error' ? 'red' : ($log_entry['level'] === 'warning' ? 'orange' : 'black');
                            echo '<div style="color: ' . $color . '; margin: 5px 0;">';
                            echo '[' . esc_html($log_entry['level']) . '] ' . esc_html($log_entry['message']);
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    delete_transient('ielts_cm_import_results');
                }
            }
        }
    }
}

// Add admin notice hook
add_action('admin_notices', array('IELTS_CM_Import_Page', 'display_import_notice'));
