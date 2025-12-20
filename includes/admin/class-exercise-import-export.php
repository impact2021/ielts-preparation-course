<?php
/**
 * Admin Exercise Import/Export Page
 * 
 * Provides UI for exporting and importing individual exercises
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Exercise_Import_Export {
    
    /**
     * Initialize the import/export page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('add_meta_boxes', array($this, 'add_export_meta_box'));
        add_action('add_meta_boxes', array($this, 'add_import_meta_box'));
        add_action('admin_post_ielts_cm_export_exercise', array($this, 'handle_export'));
        add_action('admin_post_ielts_cm_import_exercise', array($this, 'handle_import'));
        add_action('wp_ajax_ielts_cm_import_exercise_direct', array($this, 'handle_import_direct'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Import Exercise', 'ielts-course-manager'),
            __('Import Exercise', 'ielts-course-manager'),
            'manage_options',
            'ielts-import-exercise',
            array($this, 'render_import_page')
        );
    }
    
    /**
     * Add export meta box to exercise edit pages
     */
    public function add_export_meta_box() {
        add_meta_box(
            'ielts_cm_exercise_export',
            __('Export Exercise', 'ielts-course-manager'),
            array($this, 'render_export_meta_box'),
            'ielts_quiz',
            'side',
            'low'
        );
    }
    
    /**
     * Add import meta box to exercise edit pages
     */
    public function add_import_meta_box() {
        add_meta_box(
            'ielts_cm_exercise_import',
            __('Import Exercise', 'ielts-course-manager'),
            array($this, 'render_import_meta_box'),
            'ielts_quiz',
            'side',
            'low'
        );
    }
    
    /**
     * Enqueue admin scripts for import functionality
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on exercise edit pages
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        if (!$post || $post->post_type !== 'ielts_quiz') {
            return;
        }
        
        wp_enqueue_script(
            'ielts-cm-exercise-import',
            IELTS_CM_PLUGIN_URL . 'assets/js/exercise-import.js',
            array('jquery'),
            IELTS_CM_VERSION,
            true
        );
        
        wp_localize_script('ielts-cm-exercise-import', 'ieltsCMImport', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'i18n' => array(
                'uploading' => __('Uploading...', 'ielts-course-manager'),
                'importing' => __('Importing...', 'ielts-course-manager'),
                'success' => __('Import successful! Reloading page...', 'ielts-course-manager'),
                'error' => __('Import failed. Please try again.', 'ielts-course-manager'),
                'noFile' => __('Please select a JSON file.', 'ielts-course-manager'),
                'confirmImport' => __('This will replace all current questions and settings. Are you sure you want to continue?', 'ielts-course-manager'),
            )
        ));
    }
    
    /**
     * Render export meta box on exercise edit page
     */
    public function render_export_meta_box($post) {
        $export_url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'ielts_cm_export_exercise',
                    'exercise_id' => $post->ID
                ),
                admin_url('admin-post.php')
            ),
            'ielts_cm_export_exercise_' . $post->ID,
            'nonce'
        );
        ?>
        <p><?php _e('Export this exercise to a JSON file that can be imported into another exercise.', 'ielts-course-manager'); ?></p>
        <p>
            <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">
                <?php _e('Export Exercise', 'ielts-course-manager'); ?>
            </a>
        </p>
        <p class="description">
            <?php _e('This will download a JSON file containing all questions, settings, and reading texts.', 'ielts-course-manager'); ?>
        </p>
        <?php
    }
    
    /**
     * Render import meta box on exercise edit page
     */
    public function render_import_meta_box($post) {
        wp_nonce_field('ielts_cm_import_exercise_direct_' . $post->ID, 'ielts_cm_import_exercise_nonce');
        ?>
        <p><?php _e('Upload a JSON file to import exercise content. This will overwrite the current exercise content.', 'ielts-course-manager'); ?></p>
        <p>
            <input type="file" id="ielts_cm_import_file_<?php echo esc_attr($post->ID); ?>" accept=".json" style="width: 100%; margin-bottom: 10px;">
        </p>
        <p>
            <button type="button" class="button button-secondary" id="ielts_cm_import_btn_<?php echo esc_attr($post->ID); ?>" data-exercise-id="<?php echo esc_attr($post->ID); ?>">
                <?php _e('Import from JSON', 'ielts-course-manager'); ?>
            </button>
        </p>
        <div id="ielts_cm_import_status_<?php echo esc_attr($post->ID); ?>" style="margin-top: 10px;"></div>
        <p class="description">
            <?php _e('Select a JSON file exported from another exercise. All current questions and settings will be replaced.', 'ielts-course-manager'); ?>
        </p>
        <?php
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Check for import results
        if (isset($_GET['imported']) && $_GET['imported'] === '1') {
            $exercise_id = isset($_GET['exercise_id']) ? intval($_GET['exercise_id']) : 0;
            if ($exercise_id) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong><?php _e('Import Completed Successfully!', 'ielts-course-manager'); ?></strong><br>
                        <?php _e('The exercise has been imported.', 'ielts-course-manager'); ?>
                    </p>
                    <p>
                        <a href="<?php echo get_edit_post_link($exercise_id); ?>" class="button button-primary">
                            <?php _e('Edit Imported Exercise', 'ielts-course-manager'); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
        
        // Check for errors
        if (isset($_GET['error'])) {
            $this->display_error($_GET['error']);
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Import Exercise', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('About Exercise Import/Export:', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('This tool allows you to export an exercise to a JSON file, modify it, and import it into an empty or existing exercise. This is useful for creating similar practice tests quickly.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="import-instructions" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                <h2><?php _e('How to Use Exercise Import/Export', 'ielts-course-manager'); ?></h2>
                
                <h3><?php _e('Step 1: Export an Existing Exercise', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Navigate to the exercise you want to export (e.g., a practice test you\'ve created)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Scroll down to the right sidebar and find the <strong>"Export Exercise"</strong> meta box', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Click the <strong>"Export Exercise"</strong> button', 'ielts-course-manager'); ?></li>
                    <li><?php _e('A JSON file will be downloaded to your computer (e.g., <code>exercise-[title]-[date].json</code>)', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Step 2: Modify the Exported File (Optional)', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Open the downloaded JSON file in a text editor (Notepad, TextEdit, VS Code, etc.)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('You can modify:', 'ielts-course-manager'); ?>
                        <ul>
                            <li><?php _e('<strong>Question text</strong>: Change the question content', 'ielts-course-manager'); ?></li>
                            <li><?php _e('<strong>Answer options</strong>: Modify multiple choice options', 'ielts-course-manager'); ?></li>
                            <li><?php _e('<strong>Correct answers</strong>: Update the correct answer', 'ielts-course-manager'); ?></li>
                            <li><?php _e('<strong>Reading texts</strong>: Change the reading passages', 'ielts-course-manager'); ?></li>
                            <li><?php _e('<strong>Feedback</strong>: Update correct/incorrect feedback messages', 'ielts-course-manager'); ?></li>
                            <li><?php _e('<strong>Points</strong>: Adjust question point values', 'ielts-course-manager'); ?></li>
                        </ul>
                    </li>
                    <li><?php _e('Save the modified JSON file', 'ielts-course-manager'); ?></li>
                </ol>
                
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0;">
                    <strong><?php _e('Important:', 'ielts-course-manager'); ?></strong>
                    <?php _e('Make sure the JSON file remains valid after editing. Invalid JSON will cause import errors. You can validate your JSON using online tools like jsonlint.com', 'ielts-course-manager'); ?>
                </div>
                
                <h3><?php _e('Step 3: Create Target Exercise', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Go to <strong>IELTS Courses > Exercises</strong>', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Click <strong>"Add New"</strong> to create a new exercise', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Give it a title (e.g., "Practice Test 2")', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Click <strong>"Save Draft"</strong> or <strong>"Publish"</strong>', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Note the exercise ID from the URL (e.g., post=123)', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Step 4: Import the Exercise', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Use the form below to select your target exercise', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Upload the JSON file (original or modified)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Click <strong>"Import Exercise"</strong>', 'ielts-course-manager'); ?></li>
                    <li><?php _e('The questions, settings, and reading texts will be imported into the selected exercise', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('What Gets Imported:', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('All questions (multiple choice, true/false, fill-in-blank, essay)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Question settings (type, points, correct answers, options)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Feedback for correct and incorrect answers', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Reading texts (for computer-based layout)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Exercise settings (layout type, timer, scoring type)', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('What Does NOT Get Imported:', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('Exercise title (you set this when creating the target exercise)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Course and lesson assignments', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Previous quiz submissions/results', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
            
            <div class="import-form" style="max-width: 600px; margin: 20px 0;">
                <h2><?php _e('Import Exercise from JSON', 'ielts-course-manager'); ?></h2>
                
                <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('ielts_cm_import_exercise', 'ielts_cm_import_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_import_exercise">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="target_exercise_id"><?php _e('Target Exercise', 'ielts-course-manager'); ?></label>
                            </th>
                            <td>
                                <select name="target_exercise_id" id="target_exercise_id" required style="width: 100%;">
                                    <option value=""><?php _e('-- Select an Exercise --', 'ielts-course-manager'); ?></option>
                                    <?php
                                    // Limit to 500 most recent exercises for performance
                                    $exercises = get_posts(array(
                                        'post_type' => 'ielts_quiz',
                                        'posts_per_page' => 500,
                                        'orderby' => 'modified',
                                        'order' => 'DESC',
                                        'post_status' => array('publish', 'draft')
                                    ));
                                    
                                    foreach ($exercises as $exercise) {
                                        echo '<option value="' . esc_attr($exercise->ID) . '">' . esc_html($exercise->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description">
                                    <?php _e('Select the exercise where you want to import the content. Any existing questions in this exercise will be replaced.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="import_file"><?php _e('JSON File', 'ielts-course-manager'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".json" required>
                                <p class="description">
                                    <?php _e('Select the exercise JSON file to import.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Import Exercise', 'ielts-course-manager'), 'primary', 'submit', true); ?>
                </form>
            </div>
            
            <div class="import-tips" style="max-width: 900px; margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                <h3><?php _e('Tips for Success', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><strong><?php _e('Backup First:', 'ielts-course-manager'); ?></strong> <?php _e('Export important exercises before importing to have a backup.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Test on Draft:', 'ielts-course-manager'); ?></strong> <?php _e('Import into a draft exercise first to verify the content before publishing.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Validate JSON:', 'ielts-course-manager'); ?></strong> <?php _e('If you edit the JSON file, validate it with an online JSON validator to avoid import errors.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Save Time:', 'ielts-course-manager'); ?></strong> <?php _e('Use this feature to create variations of practice tests by exporting, modifying questions, and re-importing.', 'ielts-course-manager'); ?></li>
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
        </style>
        <?php
    }
    
    /**
     * Display error message
     */
    private function display_error($error_code) {
        $messages = array(
            'upload_failed' => __('File upload failed. Please try again.', 'ielts-course-manager'),
            'invalid_file_type' => __('Invalid file type. Please upload a JSON file.', 'ielts-course-manager'),
            'invalid_json' => __('Invalid JSON file. Please check the file format.', 'ielts-course-manager'),
            'no_exercise' => __('Please select a target exercise.', 'ielts-course-manager'),
            'import_failed' => __('Import failed. Please check the file and try again.', 'ielts-course-manager'),
            'file_too_large' => __('File is too large. Maximum size is 10MB.', 'ielts-course-manager'),
        );
        
        $message = isset($messages[$error_code]) ? $messages[$error_code] : __('An unknown error occurred.', 'ielts-course-manager');
        
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong><?php _e('Import Error:', 'ielts-course-manager'); ?></strong> <?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
    
    /**
     * Handle exercise export
     */
    public function handle_export() {
        // Get exercise ID from request
        $exercise_id = isset($_GET['exercise_id']) ? intval($_GET['exercise_id']) : 0;
        
        if (!$exercise_id) {
            wp_die(__('Invalid exercise ID', 'ielts-course-manager'));
        }
        
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'ielts_cm_export_exercise_' . $exercise_id)) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        // Get exercise data
        $exercise = get_post($exercise_id);
        if (!$exercise || $exercise->post_type !== 'ielts_quiz') {
            wp_die(__('Exercise not found', 'ielts-course-manager'));
        }
        
        // Build export data
        $export_data = array(
            'version' => '1.0',
            'exported_at' => current_time('mysql'),
            'source_exercise_id' => $exercise_id,
            'source_exercise_title' => $exercise->post_title,
            'settings' => array(
                'pass_percentage' => get_post_meta($exercise_id, '_ielts_cm_pass_percentage', true),
                'layout_type' => get_post_meta($exercise_id, '_ielts_cm_layout_type', true),
                'open_as_popup' => get_post_meta($exercise_id, '_ielts_cm_open_as_popup', true),
                'scoring_type' => get_post_meta($exercise_id, '_ielts_cm_scoring_type', true),
                'timer_minutes' => get_post_meta($exercise_id, '_ielts_cm_timer_minutes', true),
                'exercise_label' => get_post_meta($exercise_id, '_ielts_cm_exercise_label', true),
            ),
            'reading_texts' => get_post_meta($exercise_id, '_ielts_cm_reading_texts', true),
            'questions' => get_post_meta($exercise_id, '_ielts_cm_questions', true),
        );
        
        // Convert to JSON
        $json = wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Check for encoding errors
        if ($json === false) {
            wp_die(__('Failed to generate export file. Please try again.', 'ielts-course-manager'));
        }
        
        // Clean (erase) all output buffers and turn off output buffering
        // WordPress may have multiple nested buffers, so we need to clear them all
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        $filename = sanitize_file_name('exercise-' . sanitize_title($exercise->post_title) . '-' . gmdate('Y-m-d') . '.json');
        nocache_headers();
        header('Content-Description: File Transfer');
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Content-Length: ' . mb_strlen($json, '8bit'));
        
        // Output JSON
        echo $json;
        exit;
    }
    
    /**
     * Import exercise settings from validated import data
     * 
     * @param int $exercise_id The exercise ID to import into
     * @param array $import_data The validated import data array
     */
    private function import_exercise_settings($exercise_id, $import_data) {
        // Whitelist of allowed setting keys for security
        $allowed_settings = array(
            'pass_percentage',
            'layout_type',
            'open_as_popup',
            'scoring_type',
            'timer_minutes',
            'exercise_label'
        );
        
        // Import settings with validation
        if (isset($import_data['settings']) && is_array($import_data['settings'])) {
            foreach ($import_data['settings'] as $key => $value) {
                // Only allow whitelisted keys
                if (in_array($key, $allowed_settings, true) && $value !== '' && $value !== null) {
                    // Additional validation based on key
                    switch ($key) {
                        case 'pass_percentage':
                        case 'timer_minutes':
                            $value = intval($value);
                            break;
                        case 'open_as_popup':
                            // Convert to '1' or '0' for consistency with WordPress checkbox values
                            $value = rest_sanitize_boolean($value) ? '1' : '0';
                            break;
                        case 'layout_type':
                            $value = sanitize_text_field($value);
                            break;
                        case 'scoring_type':
                            $valid_types = array('percentage', 'ielts_general_reading', 'ielts_academic_reading', 'ielts_listening');
                            if (!in_array($value, $valid_types, true)) {
                                continue 2; // Skip this setting
                            }
                            break;
                        case 'exercise_label':
                            $valid_labels = array('exercise', 'end_of_lesson_test', 'practice_test');
                            if (!in_array($value, $valid_labels, true)) {
                                continue 2; // Skip this setting
                            }
                            break;
                        default:
                            $value = sanitize_text_field($value);
                    }
                    update_post_meta($exercise_id, '_ielts_cm_' . $key, $value);
                }
            }
        }
    }
    
    /**
     * Import reading texts from validated import data
     * 
     * @param int $exercise_id The exercise ID to import into
     * @param array $import_data The validated import data array
     */
    private function import_reading_texts($exercise_id, $import_data) {
        if (isset($import_data['reading_texts']) && is_array($import_data['reading_texts'])) {
            $sanitized_texts = array();
            foreach ($import_data['reading_texts'] as $text) {
                if (is_array($text)) {
                    $sanitized_texts[] = array(
                        'title' => isset($text['title']) ? sanitize_text_field($text['title']) : '',
                        'content' => isset($text['content']) ? wp_kses_post($text['content']) : ''
                    );
                }
            }
            update_post_meta($exercise_id, '_ielts_cm_reading_texts', $sanitized_texts);
        }
    }
    
    /**
     * Import questions from validated import data
     * 
     * @param int $exercise_id The exercise ID to import into
     * @param array $import_data The validated import data array
     */
    private function import_questions($exercise_id, $import_data) {
        if (isset($import_data['questions']) && is_array($import_data['questions'])) {
            $sanitized_questions = array();
            foreach ($import_data['questions'] as $question) {
                if (is_array($question)) {
                    $sanitized_question = array(
                        'type' => isset($question['type']) ? sanitize_text_field($question['type']) : 'multiple_choice',
                        'question_text' => isset($question['question_text']) ? wp_kses_post($question['question_text']) : '',
                        'points' => isset($question['points']) ? intval($question['points']) : 1,
                        'correct_answer' => isset($question['correct_answer']) ? sanitize_text_field($question['correct_answer']) : '',
                        'correct_feedback' => isset($question['correct_feedback']) ? sanitize_text_field($question['correct_feedback']) : '',
                        'incorrect_feedback' => isset($question['incorrect_feedback']) ? sanitize_text_field($question['incorrect_feedback']) : '',
                    );
                    
                    // Sanitize instructions if present
                    if (isset($question['instructions'])) {
                        $sanitized_question['instructions'] = wp_kses_post($question['instructions']);
                    }
                    
                    // Sanitize question field (alternative to question_text) if present
                    if (isset($question['question'])) {
                        $sanitized_question['question'] = wp_kses_post($question['question']);
                    }
                    
                    // Sanitize options array if present
                    if (isset($question['options'])) {
                        if (is_array($question['options'])) {
                            $sanitized_question['options'] = array_map('sanitize_text_field', $question['options']);
                        } else {
                            $sanitized_question['options'] = sanitize_textarea_field($question['options']);
                        }
                    }
                    
                    // Sanitize options_key object if present (for multiple choice matching)
                    if (isset($question['options_key']) && is_array($question['options_key'])) {
                        $sanitized_options_key = array();
                        foreach ($question['options_key'] as $key => $value) {
                            $sanitized_options_key[sanitize_text_field($key)] = sanitize_text_field($value);
                        }
                        $sanitized_question['options_key'] = $sanitized_options_key;
                    }
                    
                    // Sanitize headings object if present (for heading matching)
                    if (isset($question['headings']) && is_array($question['headings'])) {
                        $sanitized_headings = array();
                        foreach ($question['headings'] as $key => $value) {
                            $sanitized_headings[sanitize_text_field($key)] = sanitize_text_field($value);
                        }
                        $sanitized_question['headings'] = $sanitized_headings;
                    }
                    
                    // Sanitize matches array if present (for matching questions)
                    if (isset($question['matches']) && is_array($question['matches'])) {
                        $sanitized_matches = array();
                        foreach ($question['matches'] as $match) {
                            if (is_array($match)) {
                                $sanitized_match = array();
                                // Preserve all fields in match
                                if (isset($match['question_id'])) {
                                    $sanitized_match['question_id'] = intval($match['question_id']);
                                }
                                if (isset($match['id'])) {
                                    $sanitized_match['id'] = intval($match['id']);
                                }
                                if (isset($match['text'])) {
                                    $sanitized_match['text'] = wp_kses_post($match['text']);
                                }
                                if (isset($match['correct_answer'])) {
                                    $sanitized_match['correct_answer'] = sanitize_text_field($match['correct_answer']);
                                }
                                if (isset($match['correct_heading'])) {
                                    $sanitized_match['correct_heading'] = sanitize_text_field($match['correct_heading']);
                                }
                                if (isset($match['correct_paragraph'])) {
                                    $sanitized_match['correct_paragraph'] = sanitize_text_field($match['correct_paragraph']);
                                }
                                if (isset($match['paragraph'])) {
                                    $sanitized_match['paragraph'] = sanitize_text_field($match['paragraph']);
                                }
                                $sanitized_matches[] = $sanitized_match;
                            }
                        }
                        $sanitized_question['matches'] = $sanitized_matches;
                    }
                    
                    // Sanitize reading_text_id if present
                    if (isset($question['reading_text_id'])) {
                        $sanitized_question['reading_text_id'] = intval($question['reading_text_id']);
                    }
                    
                    $sanitized_questions[] = $sanitized_question;
                }
            }
            update_post_meta($exercise_id, '_ielts_cm_questions', $sanitized_questions);
        }
    }
    
    /**
     * Handle exercise import
     */
    public function handle_import() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_import_nonce']) || !wp_verify_nonce($_POST['ielts_cm_import_nonce'], 'ielts_cm_import_exercise')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability - require manage_options for security
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        // Get target exercise ID
        $target_exercise_id = isset($_POST['target_exercise_id']) ? intval($_POST['target_exercise_id']) : 0;
        
        if (!$target_exercise_id) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'no_exercise'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Verify target exercise exists
        $target_exercise = get_post($target_exercise_id);
        if (!$target_exercise || $target_exercise->post_type !== 'ielts_quiz') {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'no_exercise'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'upload_failed'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Validate file type
        $file_info = pathinfo($_FILES['import_file']['name']);
        if (!isset($file_info['extension']) || strtolower($file_info['extension']) !== 'json') {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'invalid_file_type'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Validate file size (limit to 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        if ($_FILES['import_file']['size'] > $max_size) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'file_too_large'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Read file contents
        $json_content = file_get_contents($_FILES['import_file']['tmp_name']);
        
        if ($json_content === false) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'upload_failed'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Parse JSON
        $import_data = json_decode($json_content, true);
        
        if ($import_data === null || json_last_error() !== JSON_ERROR_NONE) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'invalid_json'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Validate JSON structure - ensure it's an exercise export
        if (!isset($import_data['version']) || !isset($import_data['questions'])) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-import-exercise',
                'error' => 'invalid_json'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Import settings, reading texts, and questions using helper methods
        $this->import_exercise_settings($target_exercise_id, $import_data);
        $this->import_reading_texts($target_exercise_id, $import_data);
        $this->import_questions($target_exercise_id, $import_data);
        
        // Redirect with success
        wp_redirect(add_query_arg(array(
            'page' => 'ielts-import-exercise',
            'imported' => '1',
            'exercise_id' => $target_exercise_id
        ), admin_url('edit.php?post_type=ielts_course')));
        exit;
    }
    
    /**
     * Handle direct exercise import via AJAX (from exercise edit page)
     */
    public function handle_import_direct() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !isset($_POST['exercise_id'])) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        $exercise_id = intval($_POST['exercise_id']);
        
        if (!wp_verify_nonce($_POST['nonce'], 'ielts_cm_import_exercise_direct_' . $exercise_id)) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capability - require manage_options for security
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to perform this action.', 'ielts-course-manager')));
        }
        
        // Verify target exercise exists
        $target_exercise = get_post($exercise_id);
        if (!$target_exercise || $target_exercise->post_type !== 'ielts_quiz') {
            wp_send_json_error(array('message' => __('Exercise not found', 'ielts-course-manager')));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('File upload failed. Please try again.', 'ielts-course-manager')));
        }
        
        // Validate file type
        $file_info = pathinfo($_FILES['import_file']['name']);
        if (!isset($file_info['extension']) || strtolower($file_info['extension']) !== 'json') {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload a JSON file.', 'ielts-course-manager')));
        }
        
        // Validate file size (limit to 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        if ($_FILES['import_file']['size'] > $max_size) {
            wp_send_json_error(array('message' => __('File is too large. Maximum size is 10MB.', 'ielts-course-manager')));
        }
        
        // Read file contents
        $json_content = file_get_contents($_FILES['import_file']['tmp_name']);
        
        if ($json_content === false) {
            wp_send_json_error(array('message' => __('Failed to read file. Please try again.', 'ielts-course-manager')));
        }
        
        // Parse JSON
        $import_data = json_decode($json_content, true);
        
        if ($import_data === null || json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid JSON file. Please check the file format.', 'ielts-course-manager')));
        }
        
        // Validate JSON structure - ensure it's an exercise export
        if (!isset($import_data['version']) || !isset($import_data['questions'])) {
            wp_send_json_error(array('message' => __('Invalid exercise JSON file. Please check the file format.', 'ielts-course-manager')));
        }
        
        // Import settings, reading texts, and questions using helper methods
        $this->import_exercise_settings($exercise_id, $import_data);
        $this->import_reading_texts($exercise_id, $import_data);
        $this->import_questions($exercise_id, $import_data);
        
        // Return success
        wp_send_json_success(array(
            'message' => __('Exercise imported successfully!', 'ielts-course-manager'),
            'exercise_id' => $exercise_id
        ));
    }
}
