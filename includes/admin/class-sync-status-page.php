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
        
        // Get all courses with hierarchy
        $courses_hierarchy = $this->sync_manager->get_all_courses_with_hierarchy();
        
        // Get last check time
        $last_check_time = get_option('ielts_cm_sync_last_check_time', null);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Content Sync Status', 'ielts-course-manager'); ?></h1>
            
            <div class="ielts-cm-sync-status-header" style="margin: 20px 0;">
                <button id="check-sync-status" class="button button-primary button-large">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Check Sync Status', 'ielts-course-manager'); ?>
                </button>
                <span id="sync-status-message" style="margin-left: 15px; font-weight: bold;"></span>
                <?php if ($last_check_time): ?>
                    <p style="margin-top: 10px; color: #666;">
                        <?php _e('Last checked:', 'ielts-course-manager'); ?> 
                        <strong><?php echo esc_html(human_time_diff(strtotime($last_check_time), current_time('timestamp'))); ?> <?php _e('ago', 'ielts-course-manager'); ?></strong>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="ielts-cm-sync-status-content">
                
                <?php if (empty($courses_hierarchy)): ?>
                    <div class="notice notice-info">
                        <p><?php _e('No courses found. Please create some courses first.', 'ielts-course-manager'); ?></p>
                    </div>
                <?php else: ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40%;"><?php _e('Course (Unit) Name', 'ielts-course-manager'); ?></th>
                            <?php 
                            $subsite_count = count($subsites);
                            $subsite_width = $subsite_count > 0 ? floor(60 / $subsite_count) : 60;
                            foreach ($subsites as $subsite): 
                            ?>
                                <th style="width: <?php echo $subsite_width; ?>%;">
                                    <?php echo esc_html($subsite->site_name); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="sync-status-table-body">
                        <?php 
                        foreach ($courses_hierarchy as $course): 
                            $this->render_course_row($course, $subsites);
                        endforeach; 
                        ?>
                    </tbody>
                </table>
                
                <?php endif; ?>
                
            </div>
        </div>
        
        <style>
            .ielts-cm-sync-status-content {
                margin-top: 20px;
            }
            .sync-status-icon {
                font-size: 24px;
                line-height: 1;
            }
            .sync-status-synced {
                color: #155724;
            }
            .sync-status-not-synced {
                color: #721c24;
            }
            #check-sync-status .dashicons {
                margin-top: 3px;
            }
            #check-sync-status.checking .dashicons {
                animation: rotation 1s infinite linear;
            }
            @keyframes rotation {
                from {
                    transform: rotate(0deg);
                }
                to {
                    transform: rotate(359deg);
                }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var isChecking = false;
            
            // Check sync status button
            $('#check-sync-status').on('click', function() {
                if (isChecking) return;
                
                isChecking = true;
                var $button = $(this);
                var $message = $('#sync-status-message');
                
                $button.addClass('checking').prop('disabled', true);
                $message.html('<span style="color: #0c5460;">Checking sync status...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_check_sync_status',
                        nonce: '<?php echo wp_create_nonce('ielts_cm_sync_status'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.html('<span style="color: #155724;">✓ Sync status updated</span>');
                            
                            // Reload page to show updated status
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            $message.html('<span style="color: #721c24;">✗ Error: ' + response.data.message + '</span>');
                            $button.removeClass('checking').prop('disabled', false);
                            isChecking = false;
                        }
                    },
                    error: function() {
                        $message.html('<span style="color: #721c24;">✗ An error occurred</span>');
                        $button.removeClass('checking').prop('disabled', false);
                        isChecking = false;
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
