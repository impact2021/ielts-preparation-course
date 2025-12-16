<?php
/**
 * Direct LearnDash Migration Page
 * 
 * Migrates content directly from LearnDash to IELTS Course Manager
 * when both plugins are active on the same WordPress site
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Direct_Migration_Page {
    
    private $migration_log = array();
    private $migrated_courses = array();
    private $migrated_lessons = array();
    private $migrated_topics = array();
    private $migrated_quizzes = array();
    private $migrated_questions = array();
    
    /**
     * Initialize the direct migration page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_migration_menu'));
        add_action('admin_post_ielts_cm_direct_migrate', array($this, 'handle_migration'));
    }
    
    /**
     * Add migration menu page
     */
    public function add_migration_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Direct LearnDash Migration', 'ielts-course-manager'),
            __('Direct Migration', 'ielts-course-manager'),
            'manage_options',
            'ielts-direct-migration',
            array($this, 'render_migration_page')
        );
    }
    
    /**
     * Check if LearnDash is active
     */
    private function is_learndash_active() {
        return defined('LEARNDASH_VERSION') || class_exists('SFWD_LMS');
    }
    
    /**
     * Render migration page
     */
    public function render_migration_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Direct LearnDash Migration', 'ielts-course-manager'); ?></h1>
            
            <?php if (!$this->is_learndash_active()): ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('LearnDash Not Detected', 'ielts-course-manager'); ?></strong><br>
                        <?php _e('This migration tool requires LearnDash to be active on the same site. Please activate LearnDash and try again.', 'ielts-course-manager'); ?>
                    </p>
                </div>
            <?php else: ?>
                
                <div class="notice notice-success">
                    <p>
                        <strong><?php _e('LearnDash Detected', 'ielts-course-manager'); ?></strong><br>
                        <?php _e('LearnDash is active. You can proceed with direct migration.', 'ielts-course-manager'); ?>
                    </p>
                </div>
                
                <?php
                // Get counts
                $ld_courses = wp_count_posts('sfwd-courses');
                $ld_lessons = wp_count_posts('sfwd-lessons');
                $ld_topics = wp_count_posts('sfwd-topic');
                $ld_quizzes = wp_count_posts('sfwd-quiz');
                $ld_questions = wp_count_posts('sfwd-question');
                
                $course_count = isset($ld_courses->publish) ? $ld_courses->publish : 0;
                $lesson_count = isset($ld_lessons->publish) ? $ld_lessons->publish : 0;
                $topic_count = isset($ld_topics->publish) ? $ld_topics->publish : 0;
                $quiz_count = isset($ld_quizzes->publish) ? $ld_quizzes->publish : 0;
                $question_count = isset($ld_questions->publish) ? $ld_questions->publish : 0;
                ?>
                
                <div class="migration-overview" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                    <h2><?php _e('LearnDash Content Found', 'ielts-course-manager'); ?></h2>
                    <table class="widefat" style="max-width: 600px;">
                        <thead>
                            <tr>
                                <th><?php _e('Content Type', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Count', 'ielts-course-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php _e('Courses', 'ielts-course-manager'); ?></td>
                                <td><strong><?php echo $course_count; ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Lessons', 'ielts-course-manager'); ?></td>
                                <td><strong><?php echo $lesson_count; ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Topics (Lesson Pages)', 'ielts-course-manager'); ?></td>
                                <td><strong><?php echo $topic_count; ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Quizzes', 'ielts-course-manager'); ?></td>
                                <td><strong><?php echo $quiz_count; ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Questions (in question bank)', 'ielts-course-manager'); ?></td>
                                <td><strong><?php echo $question_count; ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="migration-instructions" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <h2><?php _e('How Direct Migration Works', 'ielts-course-manager'); ?></h2>
                    <p><?php _e('This tool will:', 'ielts-course-manager'); ?></p>
                    <ol>
                        <li><?php _e('Read content directly from LearnDash post types (no XML export needed)', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Create new IELTS Course Manager posts with the same content', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Preserve all relationships between courses, lessons, topics, and quizzes', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Convert LearnDash question bank questions to internal quiz questions', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Keep the original LearnDash content intact (you can delete it manually after verification)', 'ielts-course-manager'); ?></li>
                    </ol>
                    
                    <h3><?php _e('Important Notes:', 'ielts-course-manager'); ?></h3>
                    <ul>
                        <li><strong><?php _e('Non-Destructive:', 'ielts-course-manager'); ?></strong> <?php _e('Original LearnDash content is NOT deleted. You can verify the migration before removing LearnDash content.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Backup First:', 'ielts-course-manager'); ?></strong> <?php _e('Always backup your database before migration.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Skip Duplicates:', 'ielts-course-manager'); ?></strong> <?php _e('If content already exists (same title), it will be skipped.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('User Progress:', 'ielts-course-manager'); ?></strong> <?php _e('User progress and enrollment data is NOT migrated. Students will need to re-enroll.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Question Types:', 'ielts-course-manager'); ?></strong> <?php _e('Matrix Sorting and Sorting questions are converted to Essay type for manual grading.', 'ielts-course-manager'); ?></li>
                    </ul>
                </div>
                
                <div class="migration-form" style="max-width: 600px; margin: 20px 0;">
                    <h2><?php _e('Start Migration', 'ielts-course-manager'); ?></h2>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to start the migration? This may take several minutes depending on the amount of content.', 'ielts-course-manager')); ?>');">
                        <?php wp_nonce_field('ielts_cm_direct_migrate', 'ielts_cm_migrate_nonce'); ?>
                        <input type="hidden" name="action" value="ielts_cm_direct_migrate">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php _e('Migration Options', 'ielts-course-manager'); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="skip_duplicates" value="1" checked>
                                            <?php _e('Skip items with duplicate titles', 'ielts-course-manager'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Recommended: Prevents re-importing if you run the migration multiple times.', 'ielts-course-manager'); ?>
                                        </p>
                                    </fieldset>
                                    
                                    <fieldset style="margin-top: 15px;">
                                        <label>
                                            <input type="checkbox" name="include_drafts" value="1">
                                            <?php _e('Include draft content', 'ielts-course-manager'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('If unchecked, only published content will be migrated.', 'ielts-course-manager'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Start Migration Now', 'ielts-course-manager'), 'primary', 'submit', true, array('style' => 'font-size: 16px; height: auto; padding: 10px 20px;')); ?>
                    </form>
                </div>
                
                <div class="post-migration-guide" style="max-width: 900px; margin: 20px 0; padding: 20px; background: #f0f0f1; border-left: 4px solid #72aee6;">
                    <h2><?php _e('After Migration', 'ielts-course-manager'); ?></h2>
                    <ol>
                        <li><strong><?php _e('Verify Content:', 'ielts-course-manager'); ?></strong> <?php _e('Check that courses, lessons, and quizzes have been migrated correctly.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Test Quizzes:', 'ielts-course-manager'); ?></strong> <?php _e('Take a few quizzes to ensure questions are working properly.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Review Relationships:', 'ielts-course-manager'); ?></strong> <?php _e('Verify that lessons are linked to courses and resources are linked to lessons.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Delete LearnDash Content:', 'ielts-course-manager'); ?></strong> <?php _e('Once verified, you can safely delete the original LearnDash posts (Courses, Lessons, Topics, Quizzes, Questions).', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Deactivate LearnDash:', 'ielts-course-manager'); ?></strong> <?php _e('After content is deleted, you can deactivate and uninstall LearnDash.', 'ielts-course-manager'); ?></li>
                    </ol>
                </div>
                
            <?php endif; ?>
        </div>
        
        <style>
        .migration-overview table th,
        .migration-overview table td {
            padding: 10px;
        }
        .migration-overview table tbody tr:nth-child(odd) {
            background: #fff;
        }
        .migration-instructions h3 {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .migration-instructions ul,
        .migration-instructions ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        .post-migration-guide ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        </style>
        <?php
    }
    
    /**
     * Handle direct migration
     */
    public function handle_migration() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_migrate_nonce']) || !wp_verify_nonce($_POST['ielts_cm_migrate_nonce'], 'ielts_cm_direct_migrate')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        // Check if LearnDash is active
        if (!$this->is_learndash_active()) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-direct-migration',
                'error' => 'learndash_not_active'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Get options
        $options = array(
            'skip_duplicates' => isset($_POST['skip_duplicates']) ? true : false,
            'include_drafts' => isset($_POST['include_drafts']) ? true : false
        );
        
        // Increase time limit for large migrations
        set_time_limit(600); // 10 minutes
        
        // Perform migration
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-direct-migrator.php';
        $migrator = new IELTS_CM_Direct_Migrator();
        $results = $migrator->migrate($options);
        
        // Store results in transient for display
        set_transient('ielts_cm_migration_results', $results, 300); // 5 minutes
        
        // Redirect with success
        wp_redirect(add_query_arg(array(
            'page' => 'ielts-direct-migration',
            'migrated' => '1'
        ), admin_url('edit.php?post_type=ielts_course')));
        exit;
    }
    
    /**
     * Display migration results
     */
    public static function display_migration_notice() {
        if (isset($_GET['page']) && $_GET['page'] === 'ielts-direct-migration') {
            // Check for errors
            if (isset($_GET['error'])) {
                $error = sanitize_text_field($_GET['error']);
                $messages = array(
                    'learndash_not_active' => __('LearnDash is not active. Please activate LearnDash and try again.', 'ielts-course-manager')
                );
                
                if (isset($messages[$error])) {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($messages[$error]) . '</p></div>';
                }
            }
            
            // Check for success
            if (isset($_GET['migrated'])) {
                $results = get_transient('ielts_cm_migration_results');
                if ($results) {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p><strong>' . __('Migration completed successfully!', 'ielts-course-manager') . '</strong></p>';
                    echo '<ul>';
                    echo '<li>' . sprintf(__('Courses migrated: %d', 'ielts-course-manager'), $results['courses']) . '</li>';
                    echo '<li>' . sprintf(__('Lessons migrated: %d', 'ielts-course-manager'), $results['lessons']) . '</li>';
                    echo '<li>' . sprintf(__('Topics migrated: %d', 'ielts-course-manager'), $results['topics']) . '</li>';
                    echo '<li>' . sprintf(__('Quizzes migrated: %d', 'ielts-course-manager'), $results['quizzes']) . '</li>';
                    if (isset($results['questions']) && $results['questions'] > 0) {
                        echo '<li>' . sprintf(__('Questions converted: %d', 'ielts-course-manager'), $results['questions']) . '</li>';
                    }
                    echo '</ul>';
                    
                    // Display log if there were warnings or errors
                    $has_issues = false;
                    if (isset($results['log'])) {
                        foreach ($results['log'] as $log_entry) {
                            if ($log_entry['level'] !== 'info') {
                                $has_issues = true;
                                break;
                            }
                        }
                    }
                    
                    if ($has_issues) {
                        echo '<p><strong>' . __('Migration Log:', 'ielts-course-manager') . '</strong></p>';
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
                    delete_transient('ielts_cm_migration_results');
                }
            }
        }
    }
}

// Add admin notice hook
add_action('admin_notices', array('IELTS_CM_Direct_Migration_Page', 'display_migration_notice'));
