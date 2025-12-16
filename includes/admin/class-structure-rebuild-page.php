<?php
/**
 * Admin Structure Rebuild Page
 * 
 * Provides UI for rebuilding course structure from LearnDash HTML or manual input
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Structure_Rebuild_Page {
    
    /**
     * Initialize the structure rebuild page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_rebuild_menu'));
        add_action('admin_post_ielts_cm_parse_structure', array($this, 'handle_parse_structure'));
        add_action('admin_post_ielts_cm_create_structure', array($this, 'handle_create_structure'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add rebuild menu page
     */
    public function add_rebuild_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Rebuild from LearnDash', 'ielts-course-manager'),
            __('Rebuild from LearnDash', 'ielts-course-manager'),
            'manage_options',
            'ielts-rebuild-structure',
            array($this, 'render_rebuild_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'ielts_course_page_ielts-rebuild-structure') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('ielts-cm-rebuild', IELTS_CM_PLUGIN_URL . 'assets/css/rebuild.css', array(), IELTS_CM_VERSION);
    }
    
    /**
     * Render rebuild page
     */
    public function render_rebuild_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Check if we have parsed structure in session
        $parsed_structure = get_transient('ielts_cm_parsed_structure');
        
        if ($parsed_structure) {
            $this->render_structure_editor($parsed_structure);
        } else {
            $this->render_input_form();
        }
    }
    
    /**
     * Render the input form
     */
    private function render_input_form() {
        ?>
        <div class="wrap">
            <h1><?php _e('Rebuild Course Structure from LearnDash', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('About This Tool:', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('This tool helps you rebuild your course structure when the XML export doesn\'t preserve relationships. You can provide LearnDash HTML showing the course structure, and this tool will reconstruct the hierarchy automatically.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="rebuild-instructions" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                <h2><?php _e('How to Use This Tool', 'ielts-course-manager'); ?></h2>
                
                <h3><?php _e('Step 1: Get LearnDash Course Structure', 'ielts-course-manager'); ?></h3>
                <p><?php _e('You have two options:', 'ielts-course-manager'); ?></p>
                <ol>
                    <li><strong><?php _e('Copy HTML (Recommended):', 'ielts-course-manager'); ?></strong>
                        <ul>
                            <li><?php _e('Open any course page in your LearnDash site', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Right-click on the course curriculum/outline section and select "Inspect" or "Inspect Element"', 'ielts-course-manager'); ?></li>
                            <li><?php _e('In the developer tools, right-click on the curriculum container element', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Select "Copy" > "Copy element" or "Copy outer HTML"', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Paste the HTML into the field below', 'ielts-course-manager'); ?></li>
                        </ul>
                    </li>
                    <li><strong><?php _e('Manual Entry:', 'ielts-course-manager'); ?></strong>
                        <ul>
                            <li><?php _e('List your courses, lessons, and lesson pages in a structured format', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Use indentation or prefixes to show hierarchy (e.g., "- Lesson", "  - Topic")', 'ielts-course-manager'); ?></li>
                        </ul>
                    </li>
                </ol>
                
                <h3><?php _e('Step 2: Parse the Structure', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Click "Parse Structure" to analyze the HTML or text and extract the course hierarchy.', 'ielts-course-manager'); ?></p>
                
                <h3><?php _e('Step 3: Review and Edit', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Review the parsed structure and make any necessary adjustments. You can drag and drop to reorder items.', 'ielts-course-manager'); ?></p>
                
                <h3><?php _e('Step 4: Create Content', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Click "Create Course Structure" to generate all courses, lessons, and lesson pages with the correct relationships.', 'ielts-course-manager'); ?></p>
            </div>
            
            <div class="rebuild-form" style="max-width: 900px; margin: 20px 0;">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('ielts_cm_parse_structure', 'ielts_cm_parse_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_parse_structure">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="course_name"><?php _e('Course Name', 'ielts-course-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="course_name" id="course_name" class="regular-text" required>
                                <p class="description">
                                    <?php _e('Enter the name of the course you want to create.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="structure_input"><?php _e('LearnDash HTML or Structure', 'ielts-course-manager'); ?></label>
                            </th>
                            <td>
                                <textarea name="structure_input" id="structure_input" rows="15" class="large-text code" required></textarea>
                                <p class="description">
                                    <?php _e('Paste the HTML from LearnDash course page, or enter a structured list of lessons and topics.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e('Input Type', 'ielts-course-manager'); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="input_type" value="html" checked>
                                        <?php _e('HTML from LearnDash', 'ielts-course-manager'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="input_type" value="text">
                                        <?php _e('Plain text structure', 'ielts-course-manager'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Select the type of input you\'re providing.', 'ielts-course-manager'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Parse Structure', 'ielts-course-manager'), 'primary', 'submit', true); ?>
                </form>
            </div>
            
            <div class="rebuild-examples" style="max-width: 900px; margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                <h3><?php _e('Example Plain Text Structure', 'ielts-course-manager'); ?></h3>
                <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">
Introduction to IELTS
- Reading Skills
  - Skimming and Scanning
  - Understanding Main Ideas
  - Detail Questions
- Writing Task 1
  - Describing Graphs
  - Comparing Data
- Writing Task 2
  - Essay Structure
  - Argument Development
- Listening Practice
  - Section 1: Conversations
  - Section 2: Monologues
- Speaking Test
  - Part 1: Introduction
  - Part 2: Long Turn
  - Part 3: Discussion
</pre>
                <p class="description">
                    <?php _e('Use this format for plain text input. Each lesson starts at the beginning of a line with "-", and topics are indented with spaces or additional dashes.', 'ielts-course-manager'); ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the structure editor
     */
    private function render_structure_editor($structure) {
        ?>
        <div class="wrap">
            <h1><?php _e('Review and Edit Course Structure', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Structure Parsed Successfully!', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('Review the course structure below. You can drag and drop items to reorder them, or click "Edit" to modify names. When ready, click "Create Course Structure" to generate the content.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="structure-editor" style="max-width: 900px; margin: 20px 0;">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="structure-create-form">
                    <?php wp_nonce_field('ielts_cm_create_structure', 'ielts_cm_create_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_create_structure">
                    <input type="hidden" name="structure_data" id="structure_data" value="<?php echo esc_attr(json_encode($structure)); ?>">
                    
                    <h2><?php echo esc_html($structure['course_name']); ?></h2>
                    
                    <div class="structure-tree" style="background: #fff; padding: 20px; border: 1px solid #ddd;">
                        <?php $this->render_structure_tree($structure['lessons']); ?>
                    </div>
                    
                    <p style="margin-top: 20px;">
                        <button type="button" class="button" onclick="window.location.href='<?php echo admin_url('edit.php?post_type=ielts_course&page=ielts-rebuild-structure'); ?>'">
                            <?php _e('← Back to Input', 'ielts-course-manager'); ?>
                        </button>
                        <?php submit_button(__('Create Course Structure', 'ielts-course-manager'), 'primary', 'submit', false, array('style' => 'margin-left: 10px;')); ?>
                    </p>
                </form>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Make lessons sortable
                $('.lesson-list').sortable({
                    handle: '.handle',
                    placeholder: 'sortable-placeholder',
                    update: function(event, ui) {
                        updateStructureData();
                    }
                });
                
                // Make topics sortable within lessons
                $('.topic-list').sortable({
                    handle: '.handle',
                    placeholder: 'sortable-placeholder',
                    update: function(event, ui) {
                        updateStructureData();
                    }
                });
                
                function updateStructureData() {
                    var structure = {
                        course_name: <?php echo json_encode($structure['course_name']); ?>,
                        lessons: []
                    };
                    
                    $('.lesson-item').each(function() {
                        var lesson = {
                            name: $(this).find('.lesson-name').first().text().trim(),
                            topics: []
                        };
                        
                        $(this).find('.topic-item').each(function() {
                            lesson.topics.push({
                                name: $(this).find('.topic-name').text().trim()
                            });
                        });
                        
                        structure.lessons.push(lesson);
                    });
                    
                    $('#structure_data').val(JSON.stringify(structure));
                }
            });
            </script>
            
            <style>
            .structure-tree {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .lesson-list, .topic-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .lesson-item, .topic-item {
                padding: 10px;
                margin: 5px 0;
                background: #f9f9f9;
                border-left: 4px solid #2271b1;
                cursor: move;
            }
            .topic-item {
                margin-left: 30px;
                border-left-color: #72aee6;
                background: #f0f6fc;
            }
            .handle {
                display: inline-block;
                width: 20px;
                height: 20px;
                background: #ddd;
                margin-right: 10px;
                cursor: move;
                text-align: center;
                line-height: 20px;
            }
            .handle:before {
                content: "⋮⋮";
                color: #666;
            }
            .sortable-placeholder {
                background: #fff3cd;
                border: 2px dashed #ffc107;
                height: 40px;
            }
            .lesson-name, .topic-name {
                font-weight: 600;
                display: inline-block;
            }
            </style>
        </div>
        <?php
        
        // Clear the transient after displaying
        delete_transient('ielts_cm_parsed_structure');
    }
    
    /**
     * Render structure tree
     */
    private function render_structure_tree($lessons) {
        echo '<ul class="lesson-list">';
        foreach ($lessons as $lesson) {
            echo '<li class="lesson-item">';
            echo '<span class="handle"></span>';
            echo '<span class="lesson-name">' . esc_html($lesson['name']) . '</span>';
            
            if (!empty($lesson['topics'])) {
                echo '<ul class="topic-list">';
                foreach ($lesson['topics'] as $topic) {
                    echo '<li class="topic-item">';
                    echo '<span class="handle"></span>';
                    echo '<span class="topic-name">' . esc_html($topic['name']) . '</span>';
                    echo '</li>';
                }
                echo '</ul>';
            }
            
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Handle structure parsing
     */
    public function handle_parse_structure() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_parse_nonce']) || !wp_verify_nonce($_POST['ielts_cm_parse_nonce'], 'ielts_cm_parse_structure')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        $course_name = sanitize_text_field($_POST['course_name']);
        $structure_input = wp_kses_post($_POST['structure_input']);
        $input_type = sanitize_text_field($_POST['input_type']);
        
        // Parse the structure
        if ($input_type === 'html') {
            $structure = $this->parse_html_structure($structure_input, $course_name);
        } else {
            $structure = $this->parse_text_structure($structure_input, $course_name);
        }
        
        // Store parsed structure in transient
        set_transient('ielts_cm_parsed_structure', $structure, 3600);
        
        // Redirect back to the page
        wp_redirect(admin_url('edit.php?post_type=ielts_course&page=ielts-rebuild-structure'));
        exit;
    }
    
    /**
     * Parse HTML structure from LearnDash
     */
    private function parse_html_structure($html, $course_name) {
        $structure = array(
            'course_name' => $course_name,
            'lessons' => array()
        );
        
        // Load HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Try different LearnDash selectors
        $lesson_selectors = array(
            '//div[contains(@class, "ld-lesson-item")]',
            '//div[contains(@class, "ld-item-list-item")]',
            '//li[contains(@class, "ld-lesson")]',
            '//tr[contains(@class, "ld-lesson-row")]',
            '//h3|//h4' // Fallback to headers
        );
        
        $lessons = null;
        foreach ($lesson_selectors as $selector) {
            $lessons = $xpath->query($selector);
            if ($lessons && $lessons->length > 0) {
                break;
            }
        }
        
        if ($lessons && $lessons->length > 0) {
            foreach ($lessons as $lesson_node) {
                $lesson_name = $this->extract_text_from_node($lesson_node);
                
                if (empty(trim($lesson_name))) {
                    continue;
                }
                
                $lesson = array(
                    'name' => trim($lesson_name),
                    'topics' => array()
                );
                
                // Look for topics/topics within this lesson
                $topic_selectors = array(
                    './/div[contains(@class, "ld-topic-item")]',
                    './/div[contains(@class, "ld-topic")]',
                    './/li[contains(@class, "ld-topic")]'
                );
                
                foreach ($topic_selectors as $topic_selector) {
                    $topics = $xpath->query($topic_selector, $lesson_node);
                    if ($topics && $topics->length > 0) {
                        foreach ($topics as $topic_node) {
                            $topic_name = $this->extract_text_from_node($topic_node);
                            if (!empty(trim($topic_name))) {
                                $lesson['topics'][] = array(
                                    'name' => trim($topic_name)
                                );
                            }
                        }
                        break;
                    }
                }
                
                $structure['lessons'][] = $lesson;
            }
        } else {
            // Fallback: try to parse as plain text
            return $this->parse_text_structure(strip_tags($html), $course_name);
        }
        
        return $structure;
    }
    
    /**
     * Extract text from a DOM node
     */
    private function extract_text_from_node($node) {
        $xpath = new DOMXPath($node->ownerDocument);
        
        // Try to find title elements
        $title_queries = array(
            './/span[contains(@class, "ld-item-title")]',
            './/span[contains(@class, "ld-topic-title")]',
            './/span[contains(@class, "ld-lesson-title")]',
            './/a[contains(@class, "ld-item-name")]',
            './/h3',
            './/h4',
            './/*[contains(@class, "title")]'
        );
        
        foreach ($title_queries as $query) {
            $results = $xpath->query($query, $node);
            if ($results && $results->length > 0) {
                return $results->item(0)->textContent;
            }
        }
        
        // Fallback: get first text node or full content
        return $node->textContent;
    }
    
    /**
     * Parse plain text structure
     */
    private function parse_text_structure($text, $course_name) {
        $structure = array(
            'course_name' => $course_name,
            'lessons' => array()
        );
        
        $lines = explode("\n", $text);
        $current_lesson_index = null;
        
        foreach ($lines as $line) {
            // Check if line is empty after trimming
            if (empty(trim($line))) {
                continue;
            }
            
            // Determine indentation level BEFORE removing spaces
            $indent_level = strlen($line) - strlen(ltrim($line));
            
            // Now clean up the line
            $line = trim($line);
            $line = preg_replace('/^[-•*]+\s*/', '', $line);
            
            if ($indent_level == 0) {
                // This is a lesson (no indentation)
                $lesson = array(
                    'name' => $line,
                    'topics' => array()
                );
                $structure['lessons'][] = $lesson;
                // Store index for adding topics
                $current_lesson_index = count($structure['lessons']) - 1;
            } else if ($current_lesson_index !== null) {
                // This is a topic (indented)
                $structure['lessons'][$current_lesson_index]['topics'][] = array(
                    'name' => $line
                );
            }
        }
        
        return $structure;
    }
    
    /**
     * Handle structure creation
     */
    public function handle_create_structure() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_create_nonce']) || !wp_verify_nonce($_POST['ielts_cm_create_nonce'], 'ielts_cm_create_structure')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        $structure_json = stripslashes($_POST['structure_data']);
        $structure = json_decode($structure_json, true);
        
        if (!$structure) {
            wp_die(__('Invalid structure data', 'ielts-course-manager'));
        }
        
        // Create the course
        $course_id = wp_insert_post(array(
            'post_title' => $structure['course_name'],
            'post_type' => 'ielts_course',
            'post_status' => 'publish',
            'post_content' => ''
        ));
        
        if (is_wp_error($course_id)) {
            wp_die(__('Failed to create course: ', 'ielts-course-manager') . $course_id->get_error_message());
        }
        
        $lessons_created = 0;
        $topics_created = 0;
        
        // Create lessons and topics
        foreach ($structure['lessons'] as $lesson_data) {
            $lesson_id = wp_insert_post(array(
                'post_title' => $lesson_data['name'],
                'post_type' => 'ielts_lesson',
                'post_status' => 'publish',
                'post_content' => ''
            ));
            
            if (!is_wp_error($lesson_id)) {
                $lessons_created++;
                
                // Link lesson to course
                update_post_meta($lesson_id, '_ielts_cm_course_id', $course_id);
                update_post_meta($lesson_id, '_ielts_cm_course_ids', array($course_id));
                
                // Create topics (lesson pages)
                if (!empty($lesson_data['topics'])) {
                    foreach ($lesson_data['topics'] as $topic_data) {
                        $topic_id = wp_insert_post(array(
                            'post_title' => $topic_data['name'],
                            'post_type' => 'ielts_resource',
                            'post_status' => 'publish',
                            'post_content' => ''
                        ));
                        
                        if (!is_wp_error($topic_id)) {
                            $topics_created++;
                            
                            // Link topic to lesson
                            update_post_meta($topic_id, '_ielts_cm_lesson_id', $lesson_id);
                            update_post_meta($topic_id, '_ielts_cm_lesson_ids', array($lesson_id));
                        }
                    }
                }
            }
        }
        
        // Store results in transient
        set_transient('ielts_cm_structure_created', array(
            'course_id' => $course_id,
            'course_name' => $structure['course_name'],
            'lessons' => $lessons_created,
            'topics' => $topics_created
        ), 60);
        
        // Redirect to course edit page
        wp_redirect(add_query_arg(array(
            'page' => 'ielts-rebuild-structure',
            'created' => '1'
        ), admin_url('edit.php?post_type=ielts_course')));
        exit;
    }
}

// Add admin notice hook for structure creation results
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'ielts-rebuild-structure' && isset($_GET['created'])) {
        $results = get_transient('ielts_cm_structure_created');
        if ($results) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Course structure created successfully!', 'ielts-course-manager') . '</strong></p>';
            echo '<ul>';
            echo '<li>' . sprintf(__('Course: %s (ID: %d)', 'ielts-course-manager'), esc_html($results['course_name']), $results['course_id']) . '</li>';
            echo '<li>' . sprintf(__('Lessons created: %d', 'ielts-course-manager'), $results['lessons']) . '</li>';
            echo '<li>' . sprintf(__('Lesson pages created: %d', 'ielts-course-manager'), $results['topics']) . '</li>';
            echo '</ul>';
            echo '<p><a href="' . get_edit_post_link($results['course_id']) . '" class="button button-primary">' . __('Edit Course', 'ielts-course-manager') . '</a></p>';
            echo '</div>';
            delete_transient('ielts_cm_structure_created');
        }
    }
});
