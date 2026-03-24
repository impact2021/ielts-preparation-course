<?php
/**
 * Auto-Sync Log Admin Page
 * Displays the last 50 items that were automatically synced by the background job.
 * Replaces the old Sync Status page which was too slow to load.
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
            __('Auto-Sync Log', 'ielts-course-manager'),
            __('Auto-Sync Log', 'ielts-course-manager'),
            'manage_options',
            'ielts-cm-sync-status',
            array($this, 'render_page')
        );
    }
    
    /**
     * Render the auto-sync log page
     */
    public function render_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_auto_sync_log';

        // Fetch the last 50 items synced by the background job (exclude system messages)
        $logs = $wpdb->get_results(
            "SELECT * FROM $table
             WHERE content_type != 'system'
             ORDER BY log_date DESC
             LIMIT 50"
        );

        $last_run = get_option('ielts_cm_auto_sync_last_run', null);

        $status_colors = array(
            'success' => '#155724',
            'failed'  => '#721c24',
            'warning' => '#856404',
            'skipped' => '#004085',
        );
        $status_backgrounds = array(
            'success' => '#d4edda',
            'failed'  => '#f8d7da',
            'warning' => '#fff3cd',
            'skipped' => '#cce5ff',
        );
        ?>
        <div class="wrap">
            <h1><?php _e('Auto-Sync Log', 'ielts-course-manager'); ?></h1>
            <p><?php _e('This page shows the last 50 items automatically synced by the background job. Items you push manually do not appear here.', 'ielts-course-manager'); ?></p>

            <?php if ($last_run): ?>
                <p>
                    <strong><?php _e('Last auto-sync run:', 'ielts-course-manager'); ?></strong>
                    <?php echo esc_html(human_time_diff(strtotime($last_run), current_time('timestamp'))); ?>
                    <?php _e('ago', 'ielts-course-manager'); ?>
                    (<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_run))); ?>)
                </p>
            <?php endif; ?>

            <!-- Clear Stuck Sync Locks -->
            <p>
                <button id="clear-sync-locks" class="button button-secondary">
                    <span class="dashicons dashicons-dismiss" style="vertical-align:middle;"></span>
                    <?php _e('Clear Stuck Sync Locks', 'ielts-course-manager'); ?>
                </button>
                <span id="sync-lock-message" style="margin-left:10px;font-weight:bold;"></span>
            </p>

            <?php if (empty($logs)): ?>
                <p><?php _e('No items have been automatically synced yet.', 'ielts-course-manager'); ?></p>
            <?php else: ?>
                <table class="widefat fixed striped" style="max-width:900px;">
                    <thead>
                        <tr>
                            <th style="width:160px;"><?php _e('Date / Time', 'ielts-course-manager'); ?></th>
                            <th style="width:100px;"><?php _e('Type', 'ielts-course-manager'); ?></th>
                            <th><?php _e('Item', 'ielts-course-manager'); ?></th>
                            <th style="width:90px;"><?php _e('Status', 'ielts-course-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log):
                            $bg    = $status_backgrounds[$log->status] ?? '#f9f9f9';
                            $color = $status_colors[$log->status]      ?? '#333';
                        ?>
                        <tr style="background:<?php echo esc_attr($bg); ?>;">
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->log_date))); ?></td>
                            <td><?php echo esc_html(ucfirst($log->content_type)); ?></td>
                            <td><?php echo esc_html($log->message); ?></td>
                            <td style="color:<?php echo esc_attr($color); ?>;font-weight:bold;">
                                <?php echo esc_html(ucfirst($log->status)); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#clear-sync-locks').on('click', function() {
                if (!confirm('<?php echo esc_js(__('Clear all sync locks? Do this only if sync operations are stuck.', 'ielts-course-manager')); ?>')) {
                    return;
                }
                var $btn = $(this);
                var $msg = $('#sync-lock-message');
                $btn.prop('disabled', true);
                $msg.html('<span style="color:#0c5460;"><?php echo esc_js(__('Clearing…', 'ielts-course-manager')); ?></span>');
                $.post(ajaxurl, {
                    action: 'ielts_cm_clear_sync_lock',
                    nonce:  '<?php echo wp_create_nonce('ielts_cm_sync_content'); ?>'
                }, function(response) {
                    if (response.success) {
                        $msg.html('<span style="color:#155724;">\u2713 ' + response.data.message + '</span>');
                    } else {
                        $msg.html('<span style="color:#721c24;">\u2717 ' + (response.data ? response.data.message : '<?php echo esc_js(__('Error', 'ielts-course-manager')); ?>') + '</span>');
                    }
                    $btn.prop('disabled', false);
                    setTimeout(function(){ $msg.html(''); }, 5000);
                }).fail(function() {
                    $msg.html('<span style="color:#721c24;">\u2717 <?php echo esc_js(__('Request failed', 'ielts-course-manager')); ?></span>');
                    $btn.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
}
