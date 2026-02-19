<?php
/**
 * Sync Status Admin Page
 * Displays sync status of all content across subsites
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Sync_Status_Page {
    
    private $sync_manager;
    
    public function __construct() {
        $this->sync_manager = new IELTS_CM_Multi_Site_Sync();
    }
    
    /**
     * Add admin menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Sync Status', 'ielts-course-manager'),
            __('Sync Status', 'ielts-course-manager'),
            'manage_options',
            'ielts-cm-sync-status',
            array($this, 'render_page')
        );
    }
    
    /**
     * Handle AJAX request to check sync status
     */
    public function handle_ajax_check_sync() {
        check_ajax_referer('ielts_cm_sync_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        // Update the last check time
        update_option('ielts_cm_sync_last_check_time', current_time('mysql'));
        
        $status_summary = $this->sync_manager->get_all_content_sync_status();
        
        wp_send_json_success(array(
            'summary' => $status_summary,
            'message' => 'Sync status checked successfully'
        ));
    }
    
    /**
     * Handle AJAX request to bulk sync content
     */
    public function handle_ajax_bulk_sync() {
        check_ajax_referer('ielts_cm_sync_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $content_items = isset($_POST['content_items']) ? json_decode(stripslashes($_POST['content_items']), true) : array();
        
        if (empty($content_items)) {
            wp_send_json_error(array('message' => 'No items selected'));
            return;
        }
        
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        // Sort items by type to ensure correct sync order:
        // 1. Courses first (so lessons can reference them)
        // 2. Resources and quizzes (so lessons don't trash them)
        // 3. Lessons (after their children)
        $type_priority = array(
            'course' => 1,
            'resource' => 2,
            'quiz' => 2,
            'lesson' => 3
        );
        
        usort($content_items, function($a, $b) use ($type_priority) {
            $priority_a = isset($type_priority[$a['type']]) ? $type_priority[$a['type']] : 999;
            $priority_b = isset($type_priority[$b['type']]) ? $type_priority[$b['type']] : 999;
            return $priority_a - $priority_b;
        });
        
        foreach ($content_items as $item) {
            $content_id = isset($item['id']) ? intval($item['id']) : 0;
            $content_type = isset($item['type']) ? sanitize_text_field($item['type']) : '';
            
            if (!$content_id || !$content_type) {
                continue;
            }
            
            // Push content to all subsites
            $sync_result = $this->sync_manager->push_content_to_subsites($content_id, $content_type);
            
            if (!is_wp_error($sync_result)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = sprintf('Failed to sync %s (ID: %d): %s', $content_type, $content_id, $sync_result->get_error_message());
            }
        }
        
        wp_send_json_success(array(
            'results' => $results,
            'message' => sprintf('%d items synced successfully, %d failed', $results['success'], $results['failed'])
        ));
    }
    
    /**
     * Render the sync status page
     */
    public function render_page() {
        // Check if this is a primary site
        if (!$this->sync_manager->is_primary_site()) {
            ?>
            <div class="wrap">
                <h1><?php _e('Sync Status', 'ielts-course-manager'); ?></h1>
                <div class="notice notice-warning">
                    <p><?php _e('This page is only available on primary sites. Please configure this site as a primary site in Multi-Site Sync settings.', 'ielts-course-manager'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        
        $subsites = $this->sync_manager->get_connected_subsites();
        
        if (empty($subsites)) {
            ?>
            <div class="wrap">
                <h1><?php _e('Sync Status', 'ielts-course-manager'); ?></h1>
                <div class="notice notice-warning">
                    <p><?php _e('No subsites are connected. Please add subsites in Multi-Site Sync settings first.', 'ielts-course-manager'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Get simplified summary instead of full hierarchy
        $last_sync_time = get_option('ielts_cm_last_successful_sync', null);
        $total_courses = wp_count_posts('ielts_course')->publish;
        $total_lessons = wp_count_posts('ielts_lesson')->publish;
        $total_resources = wp_count_posts('ielts_resource')->publish;
        $total_quizzes = wp_count_posts('ielts_quiz')->publish;
        $total_items = $total_courses + $total_lessons + $total_resources + $total_quizzes;
        
        ?>
        <div class="wrap">
            <h1><?php _e('Content Sync Management', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info" style="padding: 15px;">
                <h2 style="margin-top: 0;"><?php _e('⚡ Quick Sync Actions', 'ielts-course-manager'); ?></h2>
                <p style="font-size: 14px; margin-bottom: 15px;">
                    <?php _e('Use these tools to manage content synchronization with your subsites.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="ielts-cm-sync-status-header" style="margin: 20px 0;">
                
                <!-- Clear Stuck Sync Locks Button -->
                <button id="clear-sync-locks" class="button button-secondary button-large">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Clear Stuck Sync Locks', 'ielts-course-manager'); ?>
                </button>
                
                <span id="sync-status-message" style="margin-left: 15px; font-weight: bold;"></span>
                
                <div style="margin-top: 20px; padding: 20px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                    <h3 style="margin-top: 0;"><?php _e('Content Summary', 'ielts-course-manager'); ?></h3>
                    <table style="width: 100%; max-width: 600px;">
                        <tr>
                            <td><strong><?php _e('Total Courses (Units):', 'ielts-course-manager'); ?></strong></td>
                            <td><?php echo number_format($total_courses); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Total Lessons:', 'ielts-course-manager'); ?></strong></td>
                            <td><?php echo number_format($total_lessons); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Total Resources (Sublessons):', 'ielts-course-manager'); ?></strong></td>
                            <td><?php echo number_format($total_resources); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Total Exercises (Quizzes):', 'ielts-course-manager'); ?></strong></td>
                            <td><?php echo number_format($total_quizzes); ?></td>
                        </tr>
                        <tr style="border-top: 2px solid #ddd;">
                            <td><strong><?php _e('Total Items:', 'ielts-course-manager'); ?></strong></td>
                            <td><strong><?php echo number_format($total_items); ?></strong></td>
                        </tr>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 20px; background: #f0f9ff; border-left: 4px solid #00a0d2;">
                    <h3 style="margin-top: 0;"><?php _e('Connected Subsites', 'ielts-course-manager'); ?></h3>
                    <ul style="margin: 10px 0;">
                        <?php foreach ($subsites as $subsite): ?>
                            <li>
                                <strong><?php echo esc_html($subsite->site_name); ?></strong> - 
                                <code><?php echo esc_html($subsite->site_url); ?></code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <?php if ($last_sync_time): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745;">
                        <p style="margin: 0;">
                            <strong><?php _e('Last Successful Sync:', 'ielts-course-manager'); ?></strong> 
                            <?php echo esc_html(human_time_diff(strtotime($last_sync_time), current_time('timestamp'))); ?> 
                            <?php _e('ago', 'ielts-course-manager'); ?>
                            (<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync_time))); ?>)
                        </p>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <h3 style="margin-top: 0;"><?php _e('⚠️ Important Notes', 'ielts-course-manager'); ?></h3>
                    <ul style="margin: 10px 0; line-height: 1.8;">
                        <li><?php _e('<strong>Sync Individual Items:</strong> Go to Courses, Lessons, Resources, or Quizzes in the admin menu and use the "Push to Subsites" button on each item.', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Course Sync Limit:</strong> Courses are limited to syncing 10 lessons at a time to prevent timeouts.', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Lesson Sync:</strong> If a lesson has many resources/quizzes, sync them individually first, then sync the lesson.', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Timeout Issues:</strong> If sync operations are stuck or taking too long, click "Clear Stuck Sync Locks" above.', 'ielts-course-manager'); ?></li>
                        <li><?php _e('<strong>Best Practice:</strong> Sync content in small batches rather than all at once to avoid timeouts.', 'ielts-course-manager'); ?></li>
                    </ul>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8d7da; border-left: 4px solid #dc3545;">
                    <h3 style="margin-top: 0;"><?php _e('🔧 Troubleshooting', 'ielts-course-manager'); ?></h3>
                    <ul style="margin: 10px 0; line-height: 1.8;">
                        <li><strong><?php _e('Subsites Unreachable?', 'ielts-course-manager'); ?></strong> <?php _e('Click "Clear Stuck Sync Locks" and wait 30 seconds before trying again.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Timeout Errors?', 'ielts-course-manager'); ?></strong> <?php _e('Sync smaller batches. For large lessons, sync resources/quizzes individually first.', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Already Syncing?', 'ielts-course-manager'); ?></strong> <?php _e('Only one sync operation can run at a time. Wait for it to complete or clear locks.', 'ielts-course-manager'); ?></li>
                    </ul>
                </div>
                
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var isClearing = false;
            
            // Clear sync locks button
            $('#clear-sync-locks').on('click', function() {
                if (isClearing) return;
                
                if (!confirm('Are you sure you want to clear all sync locks? Do this only if sync operations are stuck or subsites are unresponsive.')) {
                    return;
                }
                
                isClearing = true;
                var $button = $(this);
                var $message = $('#sync-status-message');
                
                $button.prop('disabled', true);
                $message.html('<span style="color: #0c5460;">Clearing sync locks...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_clear_sync_lock',
                        nonce: '<?php echo wp_create_nonce('ielts_cm_sync_content'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.html('<span style="color: #155724;">✓ ' + response.data.message + '</span>');
                            $button.prop('disabled', false);
                            isClearing = false;
                            
                            // Clear message after 5 seconds
                            setTimeout(function() {
                                $message.html('');
                            }, 5000);
                        } else {
                            $message.html('<span style="color: #721c24;">✗ Error: ' + response.data.message + '</span>');
                            $button.prop('disabled', false);
                            isClearing = false;
                        }
                    },
                    error: function() {
                        $message.html('<span style="color: #721c24;">✗ An error occurred while clearing locks</span>');
                        $button.prop('disabled', false);
                        isClearing = false;
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render a course row with overall sync status
     */
    private function render_course_row($course, $subsites) {
        // Check if entire course (including all children) is synced
        $course_sync_status = $this->check_course_complete_sync($course, $subsites);
        
        ?>
        <tr>
            <td>
                <strong><?php echo esc_html($course['title']); ?></strong>
            </td>
            <?php foreach ($subsites as $subsite): ?>
                <td style="text-align: center;">
                    <?php 
                    $is_synced = $course_sync_status[$subsite->id] ?? false;
                    if ($is_synced): ?>
                        <span class="dashicons dashicons-yes sync-status-icon sync-status-synced" title="Course is fully synced"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-no sync-status-icon sync-status-not-synced" title="Course is not fully synced"></span>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
        <?php
    }
    
    /**
     * Check if entire course (including all children) is synced to a subsite
     */
    private function check_course_complete_sync($course, $subsites) {
        $sync_status = array();
        
        foreach ($subsites as $subsite) {
            $is_fully_synced = true;
            
            // Check course itself
            $course_status = $this->sync_manager->get_content_sync_status($course['id'], 'course');
            $course_site_status = $course_status['subsites'][$subsite->id] ?? null;
            
            if (!$course_site_status || !$course_site_status['synced']) {
                $is_fully_synced = false;
            } else {
                // Check all lessons
                foreach ($course['lessons'] as $lesson) {
                    $lesson_status = $this->sync_manager->get_content_sync_status($lesson['id'], 'lesson');
                    $lesson_site_status = $lesson_status['subsites'][$subsite->id] ?? null;
                    
                    if (!$lesson_site_status || !$lesson_site_status['synced']) {
                        $is_fully_synced = false;
                        break;
                    }
                    
                    // Check all resources in this lesson
                    foreach ($lesson['resources'] as $resource) {
                        $resource_status = $this->sync_manager->get_content_sync_status($resource['id'], 'resource');
                        $resource_site_status = $resource_status['subsites'][$subsite->id] ?? null;
                        
                        if (!$resource_site_status || !$resource_site_status['synced']) {
                            $is_fully_synced = false;
                            break 2;
                        }
                    }
                    
                    // Check all exercises in this lesson
                    foreach ($lesson['exercises'] as $exercise) {
                        $exercise_status = $this->sync_manager->get_content_sync_status($exercise['id'], 'quiz');
                        $exercise_site_status = $exercise_status['subsites'][$subsite->id] ?? null;
                        
                        if (!$exercise_site_status || !$exercise_site_status['synced']) {
                            $is_fully_synced = false;
                            break 2;
                        }
                    }
                }
            }
            
            $sync_status[$subsite->id] = $is_fully_synced;
        }
        
        return $sync_status;
    }
}
