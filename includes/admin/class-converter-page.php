<?php
/**
 * Admin LearnDash Converter Page
 * 
 * Provides UI for converting LearnDash courses directly
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Converter_Page {
    
    /**
     * Initialize the converter page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_converter_menu'));
        add_action('wp_ajax_ielts_cm_convert_course', array($this, 'ajax_convert_course'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add converter menu page
     */
    public function add_converter_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Convert from LearnDash', 'ielts-course-manager'),
            __('Convert from LearnDash', 'ielts-course-manager'),
            'manage_options',
            'ielts-convert-learndash',
            array($this, 'render_converter_page')
        );
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'ielts_course_page_ielts-convert-learndash') {
            return;
        }
        
        wp_enqueue_style(
            'ielts-cm-converter',
            IELTS_CM_PLUGIN_URL . 'assets/css/converter.css',
            array(),
            IELTS_CM_VERSION
        );
        
        wp_enqueue_script(
            'ielts-cm-converter',
            IELTS_CM_PLUGIN_URL . 'assets/js/converter.js',
            array('jquery'),
            IELTS_CM_VERSION,
            true
        );
        
        wp_localize_script('ielts-cm-converter', 'ieltsCMConverter', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ielts_cm_convert_course'),
            'strings' => array(
                'converting' => __('Converting...', 'ielts-course-manager'),
                'success' => __('Conversion completed successfully!', 'ielts-course-manager'),
                'error' => __('Conversion failed. See errors below.', 'ielts-course-manager'),
                'confirm' => __('Are you sure you want to convert this course? This will create new IELTS Course Manager content.', 'ielts-course-manager')
            )
        ));
    }
    
    /**
     * Render converter page
     */
    public function render_converter_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-learndash-converter.php';
        $converter = new IELTS_CM_LearnDash_Converter();
        
        // Check if LearnDash is active
        $learndash_active = $converter->is_learndash_active();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Convert from LearnDash', 'ielts-course-manager'); ?></h1>
            
            <?php if (!$learndash_active): ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('LearnDash Not Detected', 'ielts-course-manager'); ?></strong><br>
                        <?php _e('LearnDash does not appear to be installed or active on this site. This tool requires LearnDash to be installed to convert courses.', 'ielts-course-manager'); ?>
                    </p>
                </div>
            <?php else: ?>
                
                <div class="notice notice-info">
                    <p>
                        <strong><?php _e('About Direct Course Conversion:', 'ielts-course-manager'); ?></strong><br>
                        <?php _e('This tool converts LearnDash courses directly from your database to IELTS Course Manager format. Select the courses you want to convert below.', 'ielts-course-manager'); ?>
                    </p>
                </div>
                
                <div class="converter-instructions" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                    <h2><?php _e('How to Convert LearnDash Courses', 'ielts-course-manager'); ?></h2>
                    
                    <ol>
                        <li><?php _e('Select one or more LearnDash courses from the list below', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Click the "Convert Selected Courses" button', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Monitor the progress in the modal window', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Review any warnings or errors after conversion', 'ielts-course-manager'); ?></li>
                    </ol>
                    
                    <h3><?php _e('What Gets Converted:', 'ielts-course-manager'); ?></h3>
                    <ul>
                        <li><?php _e('<strong>Courses:</strong> Course title, content, and featured image', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Lessons:</strong> All lessons in the course with their content and order', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Topics:</strong> Converted to Lesson Pages with content preserved', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Quizzes:</strong> Quiz structure (questions need manual review)', 'ielts-course-manager'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Important Notes:', 'ielts-course-manager'); ?></h3>
                    <ul>
                        <li><?php _e('<strong>Safe to Re-run:</strong> Already converted content is detected and skipped', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Original Content:</strong> Your LearnDash content remains unchanged', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Quiz Questions:</strong> Quiz questions use different formats and need manual review after conversion', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>User Progress:</strong> User progress is not converted. Students will need to re-enroll.', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>After Conversion:</strong> Once all courses are converted and verified, you can safely delete LearnDash', 'ielts-course-manager'); ?></li>
                    </ul>
                </div>
                
                <?php
                // Get LearnDash courses
                $ld_courses = $converter->get_learndash_courses();
                
                if (empty($ld_courses)): ?>
                    <div class="notice notice-warning">
                        <p><?php _e('No LearnDash courses found.', 'ielts-course-manager'); ?></p>
                    </div>
                <?php else: ?>
                    
                    <div class="converter-form" style="max-width: 900px; margin: 20px 0;">
                        <h2><?php _e('Select Courses to Convert', 'ielts-course-manager'); ?></h2>
                        
                        <div style="margin-bottom: 20px;">
                            <label>
                                <input type="checkbox" id="select-all-courses"> 
                                <strong><?php _e('Select All', 'ielts-course-manager'); ?></strong>
                            </label>
                        </div>
                        
                        <div class="courses-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: white;">
                            <?php foreach ($ld_courses as $course): 
                                $already_converted = $converter->find_existing_course($course->ID);
                                $course_lessons = $this->count_course_content($course->ID);
                            ?>
                                <div class="course-item" style="padding: 10px; margin-bottom: 10px; background: #f9f9f9; border-left: 3px solid <?php echo $already_converted ? '#46b450' : '#2271b1'; ?>;">
                                    <label style="display: flex; align-items: flex-start; gap: 10px;">
                                        <input type="checkbox" class="course-checkbox" value="<?php echo esc_attr($course->ID); ?>" 
                                               <?php echo $already_converted ? 'disabled' : ''; ?>>
                                        <div style="flex: 1;">
                                            <strong style="font-size: 14px;"><?php echo esc_html($course->post_title); ?></strong>
                                            <?php if ($already_converted): ?>
                                                <span style="color: #46b450; margin-left: 10px;">✓ <?php _e('Already Converted', 'ielts-course-manager'); ?></span>
                                            <?php endif; ?>
                                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                                <?php printf(
                                                    __('ID: %d | Lessons: %d | Topics: %d | Quizzes: %d | Status: %s', 'ielts-course-manager'),
                                                    $course->ID,
                                                    $course_lessons['lessons'],
                                                    $course_lessons['topics'],
                                                    $course_lessons['quizzes'],
                                                    $course->post_status
                                                ); ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <button type="button" id="convert-courses-btn" class="button button-primary button-hero">
                                <?php _e('Convert Selected Courses', 'ielts-course-manager'); ?>
                            </button>
                            <span class="spinner" style="float: none; margin: 8px;"></span>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
        
        <!-- Conversion Modal -->
        <div id="conversion-modal" class="ielts-cm-modal" style="display: none;">
            <div class="ielts-cm-modal-content">
                <div class="ielts-cm-modal-header">
                    <h2><?php _e('Converting Courses', 'ielts-course-manager'); ?></h2>
                </div>
                <div class="ielts-cm-modal-body">
                    <div id="conversion-progress">
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: 0%;"></div>
                        </div>
                        <div class="progress-text">
                            <span class="current">0</span> / <span class="total">0</span> <?php _e('courses converted', 'ielts-course-manager'); ?>
                        </div>
                    </div>
                    <div id="conversion-log" style="margin-top: 20px; max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; font-family: monospace; font-size: 12px;">
                    </div>
                    <div id="conversion-summary" style="display: none; margin-top: 20px; padding: 15px; border-left: 4px solid #46b450; background: #f0f9ff;">
                    </div>
                    <div id="conversion-errors" style="display: none; margin-top: 20px; padding: 15px; border-left: 4px solid #dc3232; background: #ffebee;">
                    </div>
                </div>
                <div class="ielts-cm-modal-footer">
                    <button type="button" id="close-modal-btn" class="button button-primary" disabled>
                        <?php _e('Close', 'ielts-course-manager'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .ielts-cm-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .ielts-cm-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .ielts-cm-modal-header {
            padding: 20px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }
        
        .ielts-cm-modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .ielts-cm-modal-body {
            padding: 20px;
        }
        
        .ielts-cm-modal-footer {
            padding: 15px 20px;
            background: #f9f9f9;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2271b1, #72aee6);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .progress-text {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .log-entry {
            margin: 5px 0;
            padding: 5px;
        }
        
        .log-entry.info {
            color: #333;
        }
        
        .log-entry.warning {
            color: #f57c00;
            font-weight: 500;
        }
        
        .log-entry.error {
            color: #d32f2f;
            font-weight: bold;
        }
        
        .converter-instructions h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #1d2327;
        }
        
        .converter-instructions ul,
        .converter-instructions ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        </style>
        <?php
    }
    
    /**
     * Count course content
     * Uses the same logic as the converter to ensure accurate counts
     */
    private function count_course_content($course_id) {
        global $wpdb;
        
        // Get lessons using the same logic as the converter
        // First try to get from ld_course_{course_id} meta (LearnDash's primary storage method)
        $lesson_ids = get_post_meta($course_id, 'ld_course_' . $course_id, true);
        
        // Fallback to course_id meta query if ld_course_ meta is empty
        if (empty($lesson_ids)) {
            $lesson_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = 'course_id' AND meta_value = %d",
                $course_id
            ));
        }
        
        // Filter to only valid lesson posts
        $lessons_count = 0;
        $topics_count = 0;
        
        if (!empty($lesson_ids)) {
            $placeholders = implode(',', array_fill(0, count($lesson_ids), '%d'));
            
            // Count actual lesson posts
            $lessons_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->posts}
                WHERE ID IN ({$placeholders})
                AND post_type = 'sfwd-lessons'",
                $lesson_ids
            ));
            
            // Count topics (lesson pages) that belong to these lessons
            $topics_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = 'lesson_id' 
                AND pm.meta_value IN ({$placeholders})
                AND p.post_type = 'sfwd-topic'",
                $lesson_ids
            ));
        }
        
        // Count quizzes - both course-level and lesson-level
        $quizzes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'course_id' 
            AND pm.meta_value = %d 
            AND p.post_type = 'sfwd-quiz'",
            $course_id
        ));
        
        return array(
            'lessons' => intval($lessons_count),
            'topics' => intval($topics_count),
            'quizzes' => intval($quizzes)
        );
    }
    
    /**
     * AJAX handler for course conversion
     */
    public function ajax_convert_course() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_convert_course')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        // Get course ID
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        if (!$course_id) {
            wp_send_json_error(array('message' => __('Invalid course ID', 'ielts-course-manager')));
        }
        
        // Perform conversion
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-learndash-converter.php';
        $converter = new IELTS_CM_LearnDash_Converter();
        $results = $converter->convert_course($course_id);
        
        if ($results['success']) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error($results);
        }
    }
}
