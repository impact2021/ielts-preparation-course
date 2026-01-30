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
        
        $status_summary = $this->sync_manager->get_all_content_sync_status();
        
        wp_send_json_success(array(
            'summary' => $status_summary,
            'message' => 'Sync status checked successfully'
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
        
        ?>
        <div class="wrap">
            <h1><?php _e('Content Sync Status', 'ielts-course-manager'); ?></h1>
            
            <div class="ielts-cm-sync-status-header" style="margin: 20px 0;">
                <button id="check-sync-status" class="button button-primary button-large">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Check Sync Status', 'ielts-course-manager'); ?>
                </button>
                <span id="sync-status-message" style="margin-left: 15px; font-weight: bold;"></span>
            </div>
            
            <div id="sync-status-summary" style="display:none; margin: 20px 0;">
                <!-- Summary will be inserted here via JS -->
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
                            <th style="width: 40%;"><?php _e('Content Item', 'ielts-course-manager'); ?></th>
                            <th style="width: 15%;"><?php _e('Type', 'ielts-course-manager'); ?></th>
                            <?php foreach ($subsites as $subsite): ?>
                                <th style="width: <?php echo floor(45 / count($subsites)); ?>%;">
                                    <?php echo esc_html($subsite->site_name); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses_hierarchy as $course): ?>
                            <?php $this->render_content_row($course, 0, $subsites); ?>
                            
                            <?php foreach ($course['lessons'] as $lesson): ?>
                                <?php $this->render_content_row($lesson, 1, $subsites); ?>
                                
                                <?php foreach ($lesson['resources'] as $resource): ?>
                                    <?php $this->render_content_row($resource, 2, $subsites); ?>
                                <?php endforeach; ?>
                                
                                <?php foreach ($lesson['exercises'] as $exercise): ?>
                                    <?php $this->render_content_row($exercise, 2, $subsites); ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php endif; ?>
                
            </div>
        </div>
        
        <style>
            .ielts-cm-sync-status-content {
                margin-top: 20px;
            }
            .ielts-cm-sync-status-content .indent-1 {
                padding-left: 30px;
            }
            .ielts-cm-sync-status-content .indent-2 {
                padding-left: 60px;
            }
            .sync-status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .sync-status-synced {
                background-color: #d4edda;
                color: #155724;
            }
            .sync-status-out-of-sync {
                background-color: #fff3cd;
                color: #856404;
            }
            .sync-status-never-synced {
                background-color: #f8d7da;
                color: #721c24;
            }
            .sync-status-checking {
                background-color: #d1ecf1;
                color: #0c5460;
            }
            .ielts-cm-sync-summary {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }
            .sync-summary-card {
                flex: 1;
                background: white;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 15px;
            }
            .sync-summary-card h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #666;
            }
            .sync-summary-card .number {
                font-size: 32px;
                font-weight: bold;
                line-height: 1;
            }
            .sync-summary-card.synced .number {
                color: #155724;
            }
            .sync-summary-card.out-of-sync .number {
                color: #856404;
            }
            .sync-summary-card.never-synced .number {
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
            
            $('#check-sync-status').on('click', function() {
                if (isChecking) {
                    return;
                }
                
                isChecking = true;
                var $button = $(this);
                var $message = $('#sync-status-message');
                var $summary = $('#sync-status-summary');
                
                $button.addClass('checking').prop('disabled', true);
                $message.html('<span class="sync-status-badge sync-status-checking">Checking...</span>');
                
                // Update all status cells to show checking state
                $('.sync-status-cell').html('<span class="sync-status-badge sync-status-checking">Checking...</span>');
                
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
                            
                            // Display summary
                            var summary = response.data.summary;
                            var summaryHtml = '<div class="ielts-cm-sync-summary">';
                            summaryHtml += '<div class="sync-summary-card synced"><h3>Synced Items</h3><div class="number">' + summary.synced_items + '</div></div>';
                            summaryHtml += '<div class="sync-summary-card out-of-sync"><h3>Out of Sync</h3><div class="number">' + summary.out_of_sync_items + '</div></div>';
                            summaryHtml += '<div class="sync-summary-card never-synced"><h3>Never Synced</h3><div class="number">' + summary.never_synced_items + '</div></div>';
                            summaryHtml += '<div class="sync-summary-card"><h3>Total Items</h3><div class="number">' + summary.total_items + '</div></div>';
                            summaryHtml += '</div>';
                            
                            $summary.html(summaryHtml).show();
                            
                            // Reload the page to show updated status
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            $message.html('<span style="color: #721c24;">✗ ' + response.data.message + '</span>');
                        }
                    },
                    error: function() {
                        $message.html('<span style="color: #721c24;">✗ An error occurred</span>');
                    },
                    complete: function() {
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
     * Render a content row with sync status for all subsites
     */
    private function render_content_row($content, $indent_level, $subsites) {
        $status = $this->sync_manager->get_content_sync_status($content['id'], $content['type']);
        
        ?>
        <tr>
            <td class="indent-<?php echo $indent_level; ?>">
                <strong><?php echo esc_html($content['title']); ?></strong>
            </td>
            <td>
                <?php 
                $type_labels = array(
                    'course' => __('Course', 'ielts-course-manager'),
                    'lesson' => __('Lesson', 'ielts-course-manager'),
                    'resource' => __('Sub-lesson', 'ielts-course-manager'),
                    'quiz' => __('Exercise', 'ielts-course-manager')
                );
                echo esc_html($type_labels[$content['type']] ?? ucfirst($content['type']));
                ?>
            </td>
            <?php foreach ($subsites as $subsite): ?>
                <td class="sync-status-cell">
                    <?php 
                    $site_status = $status['subsites'][$subsite->id] ?? null;
                    if ($site_status):
                        if ($site_status['synced']): ?>
                            <span class="sync-status-badge sync-status-synced">
                                <span class="dashicons dashicons-yes" style="font-size: 12px; width: 12px; height: 12px; margin-top: 2px;"></span>
                                Synced
                            </span>
                            <br><small style="color: #666;">
                                <?php echo $site_status['last_sync'] ? human_time_diff(strtotime($site_status['last_sync']), current_time('timestamp')) . ' ago' : ''; ?>
                            </small>
                        <?php elseif ($site_status['sync_status'] === 'never_synced'): ?>
                            <span class="sync-status-badge sync-status-never-synced">
                                <span class="dashicons dashicons-warning" style="font-size: 12px; width: 12px; height: 12px; margin-top: 2px;"></span>
                                Never Synced
                            </span>
                        <?php else: ?>
                            <span class="sync-status-badge sync-status-out-of-sync">
                                <span class="dashicons dashicons-update" style="font-size: 12px; width: 12px; height: 12px; margin-top: 2px;"></span>
                                Out of Sync
                            </span>
                            <br><small style="color: #666;">
                                <?php echo $site_status['last_sync'] ? human_time_diff(strtotime($site_status['last_sync']), current_time('timestamp')) . ' ago' : ''; ?>
                            </small>
                        <?php endif;
                    else: ?>
                        <span class="sync-status-badge sync-status-never-synced">Unknown</span>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
        <?php
    }
}
