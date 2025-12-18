<?php
/**
 * XML Exercises Creator
 * 
 * Creates exercise posts from the converted LearnDash XML file
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_XML_Exercises_Creator {
    
    /**
     * Track created exercises
     */
    private $created_exercises = array();
    private $skipped_exercises = array();
    private $error_log = array();
    
    /**
     * Initialize the creator
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_ielts_cm_create_exercises_from_xml', array($this, 'handle_create_exercises'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Create Exercises from XML', 'ielts-course-manager'),
            __('Create Exercises from XML', 'ielts-course-manager'),
            'manage_options',
            'ielts-create-exercises-xml',
            array($this, 'render_creator_page')
        );
    }
    
    /**
     * Render creator page
     */
    public function render_creator_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Check for creation results
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $results = get_transient('ielts_cm_exercises_creation_results_' . get_current_user_id());
            if ($results) {
                $this->display_results($results);
                delete_transient('ielts_cm_exercises_creation_results_' . get_current_user_id());
            }
        }
        
        // Check for errors
        if (isset($_GET['error'])) {
            $this->display_error($_GET['error']);
        }
        
        // Find the XML file
        $xml_file = IELTS_CM_PLUGIN_DIR . 'ieltstestonline.WordPress.2025-12-17.xml';
        $xml_exists = file_exists($xml_file);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Create Exercises from XML', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('About This Tool:', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('This tool creates exercise pages from the converted LearnDash XML file. Each question in the XML will become an exercise post with a single question that can be edited later.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <?php if (!$xml_exists): ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('XML File Not Found', 'ielts-course-manager'); ?></strong><br>
                        <?php printf(
                            __('The XML file was not found at: %s', 'ielts-course-manager'),
                            '<code>' . esc_html($xml_file) . '</code>'
                        ); ?>
                    </p>
                    <p>
                        <?php _e('Please ensure the file "ieltstestonline.WordPress.2025-12-17.xml" is in the plugin root directory.', 'ielts-course-manager'); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="card" style="max-width: 900px; margin: 20px 0;">
                    <h2><?php _e('XML File Found', 'ielts-course-manager'); ?></h2>
                    <p>
                        <?php printf(
                            __('XML file location: %s', 'ielts-course-manager'),
                            '<code>' . esc_html($xml_file) . '</code>'
                        ); ?>
                    </p>
                    <p>
                        <?php printf(
                            __('File size: %s', 'ielts-course-manager'),
                            '<strong>' . size_format(filesize($xml_file)) . '</strong>'
                        ); ?>
                    </p>
                    
                    <h3><?php _e('What Will Be Created', 'ielts-course-manager'); ?></h3>
                    <ul>
                        <li><?php _e('Each ielts_quiz item in the XML will become an exercise post', 'ielts-course-manager'); ?></li>
                        <li><?php _e('The question content will be extracted and added as a single question', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Question type will be preserved (single choice, multiple choice, etc.)', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Points will be preserved from the XML metadata', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Exercises will be created as drafts for review and editing', 'ielts-course-manager'); ?></li>
                        <li><?php _e('You will need to manually add answer options and correct answers', 'ielts-course-manager'); ?></li>
                    </ul>
                    
                    <div class="notice notice-warning inline">
                        <p>
                            <strong><?php _e('Important:', 'ielts-course-manager'); ?></strong>
                            <?php _e('This process may take several minutes for large XML files. Do not close this browser window while the import is running.', 'ielts-course-manager'); ?>
                        </p>
                    </div>
                    
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('ielts_cm_create_exercises', 'ielts_cm_create_exercises_nonce'); ?>
                        <input type="hidden" name="action" value="ielts_cm_create_exercises_from_xml">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="skip_existing"><?php _e('Skip Existing', 'ielts-course-manager'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="skip_existing" name="skip_existing" value="1" checked>
                                        <?php _e('Skip exercises that already exist (matched by title)', 'ielts-course-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="post_status"><?php _e('Post Status', 'ielts-course-manager'); ?></label>
                                </th>
                                <td>
                                    <select id="post_status" name="post_status">
                                        <option value="draft"><?php _e('Draft (recommended)', 'ielts-course-manager'); ?></option>
                                        <option value="publish"><?php _e('Published', 'ielts-course-manager'); ?></option>
                                    </select>
                                    <p class="description">
                                        <?php _e('Draft status is recommended so you can review and add answer options before publishing.', 'ielts-course-manager'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="limit"><?php _e('Limit (Optional)', 'ielts-course-manager'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="limit" name="limit" min="1" max="5000" placeholder="Leave empty to process all">
                                    <p class="description">
                                        <?php _e('Process only this many exercises (leave empty to process all). Useful for testing.', 'ielts-course-manager'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Create Exercises from XML', 'ielts-course-manager'), 'primary', 'submit', true, array('onclick' => 'return confirm("' . esc_js(__('This will create exercise posts from the XML file. Continue?', 'ielts-course-manager')) . '");')); ?>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 900px; margin: 20px 0;">
                <h2><?php _e('Instructions', 'ielts-course-manager'); ?></h2>
                <ol>
                    <li><?php _e('Click "Create Exercises from XML" above to start the process', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Wait for the process to complete (do not close the browser)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Review the results and any errors', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Go to IELTS Courses > Exercises to view and edit the created exercises', 'ielts-course-manager'); ?></li>
                    <li><?php _e('For each exercise, add answer options and correct answers as needed', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the exercises when ready', 'ielts-course-manager'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle create exercises from XML
     */
    public function handle_create_exercises() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_create_exercises_nonce']) || 
            !wp_verify_nonce($_POST['ielts_cm_create_exercises_nonce'], 'ielts_cm_create_exercises')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        // Get options
        $skip_existing = isset($_POST['skip_existing']) && $_POST['skip_existing'] === '1';
        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'draft';
        $limit = isset($_POST['limit']) && !empty($_POST['limit']) ? intval($_POST['limit']) : null;
        
        // Validate post status
        if (!in_array($post_status, array('draft', 'publish'))) {
            $post_status = 'draft';
        }
        
        // Find XML file
        $xml_file = IELTS_CM_PLUGIN_DIR . 'ieltstestonline.WordPress.2025-12-17.xml';
        
        if (!file_exists($xml_file)) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-create-exercises-xml',
                'error' => 'file_not_found'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Increase limits for large imports
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '600');
        
        // Process XML
        $results = $this->create_exercises_from_xml($xml_file, array(
            'skip_existing' => $skip_existing,
            'post_status' => $post_status,
            'limit' => $limit
        ));
        
        // Store results
        set_transient('ielts_cm_exercises_creation_results_' . get_current_user_id(), $results, 300);
        
        // Redirect to results
        wp_redirect(add_query_arg(array(
            'page' => 'ielts-create-exercises-xml',
            'created' => '1'
        ), admin_url('edit.php?post_type=ielts_course')));
        exit;
    }
    
    /**
     * Create exercises from XML file
     */
    private function create_exercises_from_xml($xml_file, $options = array()) {
        $this->created_exercises = array();
        $this->skipped_exercises = array();
        $this->error_log = array();
        
        // Load XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xml_file);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->error_log[] = 'XML Error: ' . $error->message;
            }
            libxml_clear_errors();
            return $this->get_results();
        }
        
        // Register namespaces
        $namespaces = $xml->getNamespaces(true);
        
        // Process items
        $items = $xml->channel->item;
        $processed = 0;
        $limit = isset($options['limit']) ? $options['limit'] : null;
        
        foreach ($items as $item) {
            // Check limit
            if ($limit && $processed >= $limit) {
                break;
            }
            
            // Only process ielts_quiz items
            $post_type = (string)$item->children($namespaces['wp'])->post_type;
            if ($post_type !== 'ielts_quiz') {
                continue;
            }
            
            $this->process_exercise_item($item, $namespaces, $options);
            $processed++;
        }
        
        return $this->get_results();
    }
    
    /**
     * Process a single exercise item from XML
     */
    private function process_exercise_item($item, $namespaces, $options) {
        $title = (string)$item->title;
        $content = (string)$item->children($namespaces['content'])->encoded;
        $old_post_id = (int)$item->children($namespaces['wp'])->post_id;
        
        // Check if already exists
        if ($options['skip_existing']) {
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'ielts_quiz' AND post_status != 'trash' LIMIT 1",
                $title
            ));
            
            if ($existing) {
                $this->skipped_exercises[] = array(
                    'title' => $title,
                    'reason' => 'Already exists (ID: ' . $existing . ')'
                );
                return;
            }
        }
        
        // Extract metadata
        $metadata = array();
        foreach ($item->children($namespaces['wp'])->postmeta as $meta) {
            $key = (string)$meta->meta_key;
            $value = (string)$meta->meta_value;
            $metadata[$key] = $value;
        }
        
        // Get question type and points
        $question_type = isset($metadata['question_type']) ? $metadata['question_type'] : 'single';
        $question_points = isset($metadata['question_points']) ? floatval($metadata['question_points']) : 1;
        
        // Map LearnDash question types to IELTS CM types
        $type_map = array(
            'single' => 'multiple_choice',
            'multiple' => 'multiple_choice',
            'free_answer' => 'fill_blank',
            'essay' => 'essay',
            'cloze_answer' => 'fill_blank',
            'assessment_answer' => 'essay'
        );
        
        $ielts_type = isset($type_map[$question_type]) ? $type_map[$question_type] : 'multiple_choice';
        
        // Create the exercise post
        $post_data = array(
            'post_title' => $title,
            'post_content' => '', // Empty content, question goes in meta
            'post_status' => $options['post_status'],
            'post_type' => 'ielts_quiz',
            'post_date' => (string)$item->children($namespaces['wp'])->post_date,
            'menu_order' => (int)$item->children($namespaces['wp'])->menu_order
        );
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            $this->error_log[] = 'Error creating exercise "' . $title . '": ' . $new_id->get_error_message();
            return;
        }
        
        // Create a single question from the content
        $question_data = array(
            array(
                'type' => $ielts_type,
                'question' => $this->clean_content($content),
                'options' => '', // To be filled in manually
                'correct_answer' => '', // To be filled in manually
                'points' => $question_points
            )
        );
        
        // Save question data
        update_post_meta($new_id, '_ielts_cm_questions', $question_data);
        
        // Save default pass percentage
        update_post_meta($new_id, '_ielts_cm_pass_percentage', 70);
        
        // Save reference to original LearnDash question
        update_post_meta($new_id, '_ielts_cm_ld_question_id', $old_post_id);
        
        // Save quiz association if available
        foreach ($metadata as $key => $value) {
            if (strpos($key, 'ld_quiz_') === 0) {
                update_post_meta($new_id, '_ielts_cm_ld_quiz_id', $value);
                break;
            }
        }
        
        $this->created_exercises[] = array(
            'id' => $new_id,
            'title' => $title,
            'type' => $ielts_type,
            'points' => $question_points
        );
    }
    
    /**
     * Clean HTML content for question text
     */
    private function clean_content($content) {
        // Remove wrapping CDATA if present
        $content = strip_tags($content, '<p><br><strong><em><ul><ol><li><img><a><span><div>');
        $content = trim($content);
        
        // Limit length to avoid overly long questions
        if (strlen($content) > 5000) {
            $content = substr($content, 0, 5000) . '...';
        }
        
        return $content;
    }
    
    /**
     * Get results
     */
    private function get_results() {
        return array(
            'created' => $this->created_exercises,
            'skipped' => $this->skipped_exercises,
            'errors' => $this->error_log,
            'created_count' => count($this->created_exercises),
            'skipped_count' => count($this->skipped_exercises),
            'error_count' => count($this->error_log)
        );
    }
    
    /**
     * Display results
     */
    private function display_results($results) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php _e('Exercises Creation Complete!', 'ielts-course-manager'); ?></strong>
            </p>
            <ul>
                <li><?php printf(__('Created: %d exercises', 'ielts-course-manager'), $results['created_count']); ?></li>
                <li><?php printf(__('Skipped: %d exercises', 'ielts-course-manager'), $results['skipped_count']); ?></li>
                <?php if ($results['error_count'] > 0): ?>
                    <li style="color: #d63638;"><?php printf(__('Errors: %d', 'ielts-course-manager'), $results['error_count']); ?></li>
                <?php endif; ?>
            </ul>
            <p>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=ielts_quiz')); ?>" class="button button-primary">
                    <?php _e('View Exercises', 'ielts-course-manager'); ?>
                </a>
            </p>
        </div>
        
        <?php if (!empty($results['created']) && count($results['created']) <= 50): ?>
            <div class="card" style="max-width: 900px; margin: 20px 0;">
                <h2><?php _e('Created Exercises', 'ielts-course-manager'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Type', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Points', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Actions', 'ielts-course-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['created'] as $exercise): ?>
                            <tr>
                                <td><?php echo esc_html($exercise['title']); ?></td>
                                <td><?php echo esc_html($exercise['type']); ?></td>
                                <td><?php echo esc_html($exercise['points']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($exercise['id'])); ?>" class="button button-small">
                                        <?php _e('Edit', 'ielts-course-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif (!empty($results['created'])): ?>
            <div class="notice notice-info">
                <p><?php printf(__('%d exercises were created. Visit the exercises list to view and edit them.', 'ielts-course-manager'), count($results['created'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($results['skipped'])): ?>
            <div class="card" style="max-width: 900px; margin: 20px 0;">
                <h2><?php _e('Skipped Exercises', 'ielts-course-manager'); ?></h2>
                <p><?php printf(__('%d exercises were skipped', 'ielts-course-manager'), count($results['skipped'])); ?></p>
                <?php if (count($results['skipped']) <= 20): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Reason', 'ielts-course-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['skipped'] as $skipped): ?>
                                <tr>
                                    <td><?php echo esc_html($skipped['title']); ?></td>
                                    <td><?php echo esc_html($skipped['reason']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($results['errors'])): ?>
            <div class="notice notice-error">
                <p><strong><?php _e('Errors:', 'ielts-course-manager'); ?></strong></p>
                <ul>
                    <?php foreach ($results['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Display error
     */
    private function display_error($error_code) {
        $messages = array(
            'file_not_found' => __('XML file not found. Please ensure the file exists in the plugin directory.', 'ielts-course-manager')
        );
        
        $message = isset($messages[$error_code]) ? $messages[$error_code] : __('An unknown error occurred.', 'ielts-course-manager');
        
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong><?php _e('Error:', 'ielts-course-manager'); ?></strong> <?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
}
