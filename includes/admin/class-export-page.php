<?php
/**
 * Admin Export Page
 * 
 * Provides UI for exporting IELTS Course Manager content to XML
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Export_Page {
    
    /**
     * Initialize the export page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_export_menu'));
        add_action('admin_post_ielts_cm_export_xml', array($this, 'handle_export'));
    }
    
    /**
     * Add export menu page
     */
    public function add_export_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Export to XML', 'ielts-course-manager'),
            __('Export to XML', 'ielts-course-manager'),
            'manage_options',
            'ielts-export-xml',
            array($this, 'render_export_page')
        );
    }
    
    /**
     * Render export page
     */
    public function render_export_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Get counts for each post type
        $course_count = wp_count_posts('ielts_course')->publish;
        $lesson_count = wp_count_posts('ielts_lesson')->publish;
        $resource_count = wp_count_posts('ielts_resource')->publish;
        $quiz_count = wp_count_posts('ielts_quiz')->publish;
        
        ?>
        <div class="wrap">
            <h1><?php _e('Export to XML', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('About XML Export:', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('This tool exports all your IELTS Course Manager content (courses, lessons, lesson pages, and quizzes) together in a single WordPress WXR XML file. This ensures relationships between content are preserved.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="export-instructions" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                <h2><?php _e('How to Export', 'ielts-course-manager'); ?></h2>
                
                <ol>
                    <li><?php _e('Select the content types you want to export below', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Click the "Generate XML Export File" button', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Save the downloaded XML file to your computer', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Use this file with the "Import from LearnDash" tool on another site', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Use Cases:', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('<strong>Backup:</strong> Create a backup of your course content', 'ielts-course-manager'); ?></li>
                    <li><?php _e('<strong>Migration:</strong> Move content between WordPress installations', 'ielts-course-manager'); ?></li>
                    <li><?php _e('<strong>Staging:</strong> Test content on a staging site before deploying to production', 'ielts-course-manager'); ?></li>
                    <li><?php _e('<strong>Distribution:</strong> Share course content with other sites using IELTS Course Manager', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('What\'s Included:', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('Post title, content, and status', 'ielts-course-manager'); ?></li>
                    <li><?php _e('All custom fields and metadata', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Relationships between courses, lessons, and resources', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Quiz questions and settings', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Categories and taxonomies', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('What\'s NOT Included:', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('User progress and enrollment data', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Quiz submission history', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Media files (images, videos, attachments)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Plugin settings', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
            
            <div class="export-form" style="max-width: 600px; margin: 20px 0;">
                <h2><?php _e('Select Content to Export', 'ielts-course-manager'); ?></h2>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('ielts_cm_export_xml', 'ielts_cm_export_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_export_xml">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e('Content Types', 'ielts-course-manager'); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="export_types[]" value="ielts_course" checked>
                                        <?php printf(__('Courses (%d)', 'ielts-course-manager'), $course_count); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="export_types[]" value="ielts_lesson" checked>
                                        <?php printf(__('Lessons (%d)', 'ielts-course-manager'), $lesson_count); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="export_types[]" value="ielts_resource" checked>
                                        <?php printf(__('Lesson Pages (%d)', 'ielts-course-manager'), $resource_count); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="export_types[]" value="ielts_quiz" checked>
                                        <?php printf(__('Quizzes (%d)', 'ielts-course-manager'), $quiz_count); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Select which content types to include in the export. For best results, export all types together to preserve relationships.', 'ielts-course-manager'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e('Export Options', 'ielts-course-manager'); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="include_drafts" value="1">
                                        <?php _e('Include draft posts', 'ielts-course-manager'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('By default, only published content is exported. Check this to include drafts.', 'ielts-course-manager'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Generate XML Export File', 'ielts-course-manager'), 'primary', 'submit', true); ?>
                </form>
            </div>
            
            <div class="export-tips" style="max-width: 900px; margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                <h3><?php _e('Tips for Large Exports', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><strong><?php _e('File Size:', 'ielts-course-manager'); ?></strong> <?php _e('Large sites with many courses may generate large XML files. This is normal.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Export Time:', 'ielts-course-manager'); ?></strong> <?php _e('Exporting may take a few seconds to a few minutes depending on content volume.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Browser Timeout:', 'ielts-course-manager'); ?></strong> <?php _e('If the export times out, try exporting fewer content types at once or contact your hosting provider to increase PHP limits.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Batch Export:', 'ielts-course-manager'); ?></strong> <?php _e('For very large sites (25+ courses), consider exporting content types separately and importing them in sequence.', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .export-instructions h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #1d2327;
        }
        .export-instructions ul,
        .export-instructions ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        .export-tips ul {
            margin-left: 20px;
            line-height: 1.8;
        }
        </style>
        <?php
    }
    
    /**
     * Handle XML export
     */
    public function handle_export() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_export_nonce']) || !wp_verify_nonce($_POST['ielts_cm_export_nonce'], 'ielts_cm_export_xml')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        // Get export types
        $export_types = isset($_POST['export_types']) && is_array($_POST['export_types']) ? $_POST['export_types'] : array();
        if (empty($export_types)) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-export-xml',
                'error' => 'no_types'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Get options
        $include_drafts = isset($_POST['include_drafts']) && $_POST['include_drafts'] === '1';
        
        // Generate XML
        $xml = $this->generate_export_xml($export_types, $include_drafts);
        
        // Set headers for download
        $filename = 'ielts-export-' . date('Y-m-d') . '.xml';
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
        
        // Output XML
        echo $xml;
        exit;
    }
    
    /**
     * Generate export XML in WordPress WXR format
     */
    private function generate_export_xml($export_types, $include_drafts = false) {
        global $wpdb;
        
        // Start XML output
        $xml = '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '"?' . ">\n";
        $xml .= "<!-- This is a WordPress eXtended RSS file generated by IELTS Course Manager as an export of your site. -->\n";
        $xml .= "<!-- It contains information about your site's courses, lessons, resources, and quizzes. -->\n";
        $xml .= "<!-- You may use this file to transfer that content to another site. -->\n";
        $xml .= "<!-- This file is not intended to serve as a complete backup of your site. -->\n\n";
        
        $xml .= '<rss version="2.0"' . "\n";
        $xml .= "\txmlns:excerpt=\"http://wordpress.org/export/1.2/excerpt/\"\n";
        $xml .= "\txmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n";
        $xml .= "\txmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n";
        $xml .= "\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n";
        $xml .= "\txmlns:wp=\"http://wordpress.org/export/1.2/\"\n";
        $xml .= ">\n\n";
        
        $xml .= "<channel>\n";
        $xml .= "\t<title>" . $this->wxr_cdata(get_bloginfo('name')) . "</title>\n";
        $xml .= "\t<link>" . esc_url(get_bloginfo('url')) . "</link>\n";
        $xml .= "\t<description>" . $this->wxr_cdata(get_bloginfo('description')) . "</description>\n";
        $xml .= "\t<pubDate>" . date('D, d M Y H:i:s +0000') . "</pubDate>\n";
        $xml .= "\t<language>" . get_bloginfo('language') . "</language>\n";
        $xml .= "\t<wp:wxr_version>1.2</wp:wxr_version>\n";
        $xml .= "\t<wp:base_site_url>" . esc_url(get_bloginfo('url')) . "</wp:base_site_url>\n";
        $xml .= "\t<wp:base_blog_url>" . esc_url(get_bloginfo('url')) . "</wp:base_blog_url>\n\n";
        
        // Export categories and taxonomies
        $xml .= $this->export_taxonomies();
        
        // Get posts to export
        $post_statuses = array('publish');
        if ($include_drafts) {
            $post_statuses[] = 'draft';
        }
        
        $args = array(
            'post_type' => $export_types,
            'post_status' => $post_statuses,
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        );
        
        $posts = get_posts($args);
        
        // Export each post
        foreach ($posts as $post) {
            $xml .= $this->export_post($post);
        }
        
        $xml .= "</channel>\n";
        $xml .= "</rss>\n";
        
        return $xml;
    }
    
    /**
     * Export taxonomies
     */
    private function export_taxonomies() {
        $xml = '';
        
        // Export course categories
        $categories = get_terms(array(
            'taxonomy' => 'ielts_course_category',
            'hide_empty' => false
        ));
        
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $xml .= "\t<wp:category>\n";
                $xml .= "\t\t<wp:term_id>" . intval($category->term_id) . "</wp:term_id>\n";
                $xml .= "\t\t<wp:category_nicename>" . $this->wxr_cdata($category->slug) . "</wp:category_nicename>\n";
                
                // Safely get parent slug
                $parent_slug = '';
                if ($category->parent) {
                    $parent_term = get_term($category->parent);
                    if (!is_wp_error($parent_term) && $parent_term) {
                        $parent_slug = $parent_term->slug;
                    }
                }
                $xml .= "\t\t<wp:category_parent>" . $this->wxr_cdata($parent_slug) . "</wp:category_parent>\n";
                
                $xml .= "\t\t<wp:cat_name>" . $this->wxr_cdata($category->name) . "</wp:cat_name>\n";
                $xml .= "\t</wp:category>\n";
            }
        }
        
        return $xml;
    }
    
    /**
     * Export a single post
     */
    private function export_post($post) {
        $xml = "\t<item>\n";
        $xml .= "\t\t<title>" . $this->wxr_cdata($post->post_title) . "</title>\n";
        $xml .= "\t\t<link>" . esc_url(get_permalink($post->ID)) . "</link>\n";
        $xml .= "\t\t<pubDate>" . mysql2date('D, d M Y H:i:s +0000', $post->post_date, false) . "</pubDate>\n";
        $xml .= "\t\t<dc:creator>" . $this->wxr_cdata(get_the_author_meta('login', $post->post_author)) . "</dc:creator>\n";
        $xml .= "\t\t<guid isPermaLink=\"false\">" . esc_url(get_the_guid($post->ID)) . "</guid>\n";
        $xml .= "\t\t<description></description>\n";
        $xml .= "\t\t<content:encoded>" . $this->wxr_cdata($post->post_content) . "</content:encoded>\n";
        $xml .= "\t\t<excerpt:encoded>" . $this->wxr_cdata($post->post_excerpt) . "</excerpt:encoded>\n";
        $xml .= "\t\t<wp:post_id>" . intval($post->ID) . "</wp:post_id>\n";
        $xml .= "\t\t<wp:post_date>" . $this->wxr_cdata($post->post_date) . "</wp:post_date>\n";
        $xml .= "\t\t<wp:post_date_gmt>" . $this->wxr_cdata($post->post_date_gmt) . "</wp:post_date_gmt>\n";
        $xml .= "\t\t<wp:comment_status>" . $this->wxr_cdata($post->comment_status) . "</wp:comment_status>\n";
        $xml .= "\t\t<wp:ping_status>" . $this->wxr_cdata($post->ping_status) . "</wp:ping_status>\n";
        $xml .= "\t\t<wp:post_name>" . $this->wxr_cdata($post->post_name) . "</wp:post_name>\n";
        $xml .= "\t\t<wp:status>" . $this->wxr_cdata($post->post_status) . "</wp:status>\n";
        $xml .= "\t\t<wp:post_parent>" . intval($post->post_parent) . "</wp:post_parent>\n";
        $xml .= "\t\t<wp:menu_order>" . intval($post->menu_order) . "</wp:menu_order>\n";
        $xml .= "\t\t<wp:post_type>" . $this->wxr_cdata($post->post_type) . "</wp:post_type>\n";
        $xml .= "\t\t<wp:post_password>" . $this->wxr_cdata($post->post_password) . "</wp:post_password>\n";
        $xml .= "\t\t<wp:is_sticky>" . intval($post->post_type == 'post' && is_sticky($post->ID)) . "</wp:is_sticky>\n";
        
        // Export categories
        $categories = get_the_terms($post->ID, 'ielts_course_category');
        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $xml .= "\t\t<category domain=\"ielts_course_category\" nicename=\"" . esc_attr($category->slug) . "\">" . $this->wxr_cdata($category->name) . "</category>\n";
            }
        }
        
        // Export post meta
        $postmeta = get_post_meta($post->ID);
        foreach ($postmeta as $meta_key => $meta_values) {
            // Skip certain WordPress internal meta
            if (substr($meta_key, 0, 4) === '_wp_' || substr($meta_key, 0, 5) === '_edit') {
                continue;
            }
            
            foreach ($meta_values as $meta_value) {
                $xml .= "\t\t<wp:postmeta>\n";
                $xml .= "\t\t\t<wp:meta_key>" . $this->wxr_cdata($meta_key) . "</wp:meta_key>\n";
                $xml .= "\t\t\t<wp:meta_value>" . $this->wxr_cdata($meta_value) . "</wp:meta_value>\n";
                $xml .= "\t\t</wp:postmeta>\n";
            }
        }
        
        $xml .= "\t</item>\n";
        
        return $xml;
    }
    
    /**
     * Wrap string in CDATA tags
     */
    private function wxr_cdata($str) {
        if (!seems_utf8($str)) {
            $str = utf8_encode($str);
        }
        $str = '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $str) . ']]>';
        return $str;
    }
}
