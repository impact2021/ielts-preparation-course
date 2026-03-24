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

            <!-- Pending Sync Items -->
            <h2><?php _e('Pending Sync Items', 'ielts-course-manager'); ?></h2>
            <p><?php _e('These items have not yet been synced to all subsites, or have changed since their last sync.', 'ielts-course-manager'); ?></p>
            <p>
                <button id="load-pending-items" class="button button-primary">
                    <?php _e('Load Pending Items', 'ielts-course-manager'); ?>
                </button>
                <button id="push-all-pending" class="button button-secondary" style="display:none;margin-left:8px;">
                    <?php _e('Push All', 'ielts-course-manager'); ?>
                </button>
                <span id="pending-status-message" style="margin-left:10px;font-weight:bold;"></span>
            </p>
            <div id="pending-items-container"></div>

            <?php if (empty($logs)): ?>
                <h2><?php _e('Sync Log', 'ielts-course-manager'); ?></h2>
                <p><?php _e('No items have been automatically synced yet.', 'ielts-course-manager'); ?></p>
            <?php else: ?>
                <h2><?php _e('Sync Log', 'ielts-course-manager'); ?></h2>
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
            var syncNonce = '<?php echo wp_create_nonce('ielts_cm_sync_content'); ?>';

            // ── Clear Stuck Sync Locks ────────────────────────────────────────────
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
                    nonce:  syncNonce
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

            // ── Pending Items ─────────────────────────────────────────────────────
            var pendingItems = [];

            $('#load-pending-items').on('click', function() {
                var $btn = $(this);
                var $msg = $('#pending-status-message');
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Loading…', 'ielts-course-manager')); ?>');
                $msg.html('');
                $('#push-all-pending').hide();

                $.post(ajaxurl, {
                    action: 'ielts_cm_get_pending_items',
                    nonce:  syncNonce
                }, function(response) {
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Reload', 'ielts-course-manager')); ?>');
                    if (!response.success) {
                        $msg.html('<span style="color:#721c24;">\u2717 ' + (response.data ? response.data.message : '<?php echo esc_js(__('Error', 'ielts-course-manager')); ?>') + '</span>');
                        return;
                    }
                    pendingItems = response.data.items;
                    renderPendingTable(pendingItems);
                    if (pendingItems.length > 0) {
                        $('#push-all-pending').show();
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Load Pending Items', 'ielts-course-manager')); ?>');
                    $msg.html('<span style="color:#721c24;">\u2717 <?php echo esc_js(__('Request failed', 'ielts-course-manager')); ?></span>');
                });
            });

            function renderPendingTable(items) {
                var $container = $('#pending-items-container');
                if (items.length === 0) {
                    $container.html('<p style="color:#155724;font-weight:bold;">\u2713 <?php echo esc_js(__('All items are up to date — nothing pending.', 'ielts-course-manager')); ?></p>');
                    return;
                }
                var html = '<p><strong>' + items.length + ' <?php echo esc_js(__('item(s) pending sync:', 'ielts-course-manager')); ?></strong></p>';
                html += '<table class="widefat fixed striped" style="max-width:900px;">';
                html += '<thead><tr>';
                html += '<th><?php echo esc_js(__('Title', 'ielts-course-manager')); ?></th>';
                html += '<th style="width:100px;"><?php echo esc_js(__('Type', 'ielts-course-manager')); ?></th>';
                html += '<th style="width:90px;"></th>';
                html += '</tr></thead><tbody>';
                $.each(items, function(i, item) {
                    html += '<tr id="pending-row-' + item.id + '-' + item.type + '">';
                    html += '<td>' + $('<span>').text(item.title).html() + '</td>';
                    html += '<td>' + $('<span>').text(item.type.charAt(0).toUpperCase() + item.type.slice(1)).html() + '</td>';
                    html += '<td><button class="button button-small push-single-item" data-id="' + item.id + '" data-type="' + item.type + '"><?php echo esc_js(__('Push Now', 'ielts-course-manager')); ?></button></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                $container.html(html);
            }

            // Push a single item
            $('#pending-items-container').on('click', '.push-single-item', function() {
                var $btn  = $(this);
                var id    = $btn.data('id');
                var type  = $btn.data('type');
                var $row  = $('#pending-row-' + id + '-' + type);
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Pushing…', 'ielts-course-manager')); ?>');
                $.post(ajaxurl, {
                    action:       'ielts_cm_push_to_subsites',
                    post_id:      id,
                    content_type: type,
                    nonce:        syncNonce
                }, function(response) {
                    if (response.success) {
                        $row.find('td').last().html('<span style="color:#155724;font-weight:bold;">\u2713 <?php echo esc_js(__('Pushed', 'ielts-course-manager')); ?></span>');
                        setTimeout(function(){ $row.fadeOut(400, function(){ $(this).remove(); }); }, 1500);
                    } else {
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Push Now', 'ielts-course-manager')); ?>');
                        $row.find('td').last().append('<span style="color:#721c24;margin-left:6px;">\u2717 <?php echo esc_js(__('Failed', 'ielts-course-manager')); ?></span>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Push Now', 'ielts-course-manager')); ?>');
                });
            });

            // Push all pending items sequentially
            $('#push-all-pending').on('click', function() {
                if (!confirm('<?php echo esc_js(__('Push all pending items to subsites? This may take a while.', 'ielts-course-manager')); ?>')) {
                    return;
                }
                var $allBtns = $('#pending-items-container').find('.push-single-item');
                $allBtns.each(function() { $(this).trigger('click'); });
            });
        });
        </script>
        <?php
    }
}
