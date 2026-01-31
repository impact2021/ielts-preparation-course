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
        
        foreach ($content_items as $item) {
            $content_id = isset($item['id']) ? intval($item['id']) : 0;
            $content_type = isset($item['type']) ? sanitize_text_field($item['type']) : '';
            
            if (!$content_id || !$content_type) {
                continue;
            }
            
            // Push content to all subsites
            $sync_result = $this->sync_manager->push_to_all_subsites($content_id, $content_type);
            
            if ($sync_result) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = sprintf('Failed to sync %s (ID: %d)', $content_type, $content_id);
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
            
            <!-- Tabs for filtering by sync status -->
            <div class="ielts-cm-sync-tabs" style="margin: 20px 0;">
                <ul class="subsubsub">
                    <li><a href="#" data-filter="all" class="current"><?php _e('All', 'ielts-course-manager'); ?> <span class="count" id="count-all">(0)</span></a> |</li>
                    <li><a href="#" data-filter="synced"><?php _e('Synced', 'ielts-course-manager'); ?> <span class="count" id="count-synced">(0)</span></a> |</li>
                    <li><a href="#" data-filter="out-of-sync"><?php _e('Out of Sync', 'ielts-course-manager'); ?> <span class="count" id="count-out-of-sync">(0)</span></a> |</li>
                    <li><a href="#" data-filter="never-synced"><?php _e('Never Synced', 'ielts-course-manager'); ?> <span class="count" id="count-never-synced">(0)</span></a></li>
                </ul>
            </div>
            
            <!-- Bulk actions -->
            <div class="ielts-cm-bulk-actions" style="margin: 10px 0;">
                <button id="bulk-sync-selected" class="button button-secondary" disabled>
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Sync Selected to All Subsites', 'ielts-course-manager'); ?>
                </button>
                <span id="bulk-sync-message" style="margin-left: 15px; font-weight: bold;"></span>
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
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all-items" />
                            </th>
                            <th style="width: 35%;"><?php _e('Content Item', 'ielts-course-manager'); ?></th>
                            <th style="width: 15%;"><?php _e('Type', 'ielts-course-manager'); ?></th>
                            <?php foreach ($subsites as $subsite): ?>
                                <th style="width: <?php echo floor(40 / count($subsites)); ?>%;">
                                    <?php echo esc_html($subsite->site_name); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="sync-status-table-body">
                        <?php 
                        $row_index = 0;
                        foreach ($courses_hierarchy as $course): 
                            $this->render_content_row($course, 0, $subsites, $row_index);
                            $row_index++;
                            
                            foreach ($course['lessons'] as $lesson): 
                                $this->render_content_row($lesson, 1, $subsites, $row_index);
                                $row_index++;
                                
                                foreach ($lesson['resources'] as $resource): 
                                    $this->render_content_row($resource, 2, $subsites, $row_index);
                                    $row_index++;
                                endforeach;
                                
                                foreach ($lesson['exercises'] as $exercise): 
                                    $this->render_content_row($exercise, 2, $subsites, $row_index);
                                    $row_index++;
                                endforeach;
                            endforeach;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="displaying-num"></span>
                        <span class="pagination-links">
                            <button class="button" id="first-page" disabled>&laquo;</button>
                            <button class="button" id="prev-page" disabled>&lsaquo;</button>
                            <span class="paging-input">
                                <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'ielts-course-manager'); ?></label>
                                <input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="2" aria-describedby="table-paging">
                                <span class="tablenav-paging-text"> of <span class="total-pages" id="total-pages">1</span></span>
                            </span>
                            <button class="button" id="next-page" disabled>&rsaquo;</button>
                            <button class="button" id="last-page" disabled>&raquo;</button>
                        </span>
                    </div>
                </div>
                
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
            var currentFilter = 'all';
            var currentPage = 1;
            var itemsPerPage = 100;
            var allRows = [];
            
            // Initialize
            function init() {
                allRows = $('.sync-status-row').toArray();
                updateCounts();
                applyFilterAndPagination();
            }
            
            // Update counts for each tab
            function updateCounts() {
                var counts = {
                    all: allRows.length,
                    synced: 0,
                    'out-of-sync': 0,
                    'never-synced': 0
                };
                
                allRows.forEach(function(row) {
                    var status = $(row).data('status');
                    if (status in counts) {
                        counts[status]++;
                    }
                });
                
                $('#count-all').text('(' + counts.all + ')');
                $('#count-synced').text('(' + counts.synced + ')');
                $('#count-out-of-sync').text('(' + counts['out-of-sync'] + ')');
                $('#count-never-synced').text('(' + counts['never-synced'] + ')');
            }
            
            // Filter and paginate rows
            function applyFilterAndPagination() {
                var filteredRows = allRows;
                
                // Apply filter
                if (currentFilter !== 'all') {
                    filteredRows = allRows.filter(function(row) {
                        return $(row).data('status') === currentFilter;
                    });
                }
                
                // Hide all rows first
                $(allRows).hide();
                
                // Calculate pagination
                var totalItems = filteredRows.length;
                var totalPages = Math.ceil(totalItems / itemsPerPage);
                var startIndex = (currentPage - 1) * itemsPerPage;
                var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
                
                // Show only current page items
                for (var i = startIndex; i < endIndex; i++) {
                    $(filteredRows[i]).show();
                }
                
                // Update pagination controls
                updatePagination(totalItems, totalPages);
                
                // Update display message
                if (totalItems === 0) {
                    $('#displaying-num').text('No items found');
                } else {
                    $('#displaying-num').text((startIndex + 1) + '–' + endIndex + ' of ' + totalItems + ' items');
                }
            }
            
            // Update pagination controls
            function updatePagination(totalItems, totalPages) {
                $('#total-pages').text(totalPages);
                $('#current-page-selector').val(currentPage);
                
                // Enable/disable pagination buttons
                $('#first-page, #prev-page').prop('disabled', currentPage === 1);
                $('#next-page, #last-page').prop('disabled', currentPage >= totalPages || totalPages === 0);
            }
            
            // Tab click handler
            $('.ielts-cm-sync-tabs a').on('click', function(e) {
                e.preventDefault();
                $('.ielts-cm-sync-tabs a').removeClass('current');
                $(this).addClass('current');
                
                currentFilter = $(this).data('filter');
                currentPage = 1;
                applyFilterAndPagination();
            });
            
            // Pagination handlers
            $('#first-page').on('click', function() {
                currentPage = 1;
                applyFilterAndPagination();
            });
            
            $('#prev-page').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    applyFilterAndPagination();
                }
            });
            
            $('#next-page').on('click', function() {
                var totalPages = parseInt($('#total-pages').text());
                if (currentPage < totalPages) {
                    currentPage++;
                    applyFilterAndPagination();
                }
            });
            
            $('#last-page').on('click', function() {
                var totalPages = parseInt($('#total-pages').text());
                currentPage = totalPages;
                applyFilterAndPagination();
            });
            
            $('#current-page-selector').on('change', function() {
                var totalPages = parseInt($('#total-pages').text());
                var newPage = parseInt($(this).val());
                if (newPage >= 1 && newPage <= totalPages) {
                    currentPage = newPage;
                    applyFilterAndPagination();
                } else {
                    $(this).val(currentPage);
                }
            });
            
            // Select all checkbox
            $('#select-all-items').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('.sync-status-row:visible .sync-item-checkbox').prop('checked', isChecked);
                updateBulkActionButton();
            });
            
            // Individual checkbox handler
            $(document).on('change', '.sync-item-checkbox', function() {
                updateBulkActionButton();
                
                // Update select-all checkbox state
                var totalVisible = $('.sync-status-row:visible .sync-item-checkbox').length;
                var totalChecked = $('.sync-status-row:visible .sync-item-checkbox:checked').length;
                $('#select-all-items').prop('checked', totalVisible > 0 && totalVisible === totalChecked);
            });
            
            // Update bulk action button state
            function updateBulkActionButton() {
                var checkedCount = $('.sync-item-checkbox:checked').length;
                $('#bulk-sync-selected').prop('disabled', checkedCount === 0);
            }
            
            // Check sync status button
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
                            
                            // Reload the page to show updated status without hiding the summary
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
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
            
            // Bulk sync button
            $('#bulk-sync-selected').on('click', function() {
                var $button = $(this);
                var $message = $('#bulk-sync-message');
                var selectedItems = [];
                
                $('.sync-item-checkbox:checked').each(function() {
                    selectedItems.push({
                        id: $(this).data('content-id'),
                        type: $(this).data('content-type')
                    });
                });
                
                if (selectedItems.length === 0) {
                    return;
                }
                
                if (!confirm('Are you sure you want to sync ' + selectedItems.length + ' item(s) to all subsites?')) {
                    return;
                }
                
                $button.prop('disabled', true);
                $message.html('<span class="sync-status-badge sync-status-checking">Syncing...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_bulk_sync',
                        nonce: '<?php echo wp_create_nonce('ielts_cm_sync_status'); ?>',
                        content_items: JSON.stringify(selectedItems)
                    },
                    success: function(response) {
                        if (response.success) {
                            var results = response.data.results;
                            var messageHtml = '<span style="color: #155724;">✓ ' + results.success + ' item(s) synced successfully';
                            if (results.failed > 0) {
                                messageHtml += ', ' + results.failed + ' failed';
                            }
                            messageHtml += '</span>';
                            $message.html(messageHtml);
                            
                            // Reload after 2 seconds to show updated status
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $message.html('<span style="color: #721c24;">✗ ' + response.data.message + '</span>');
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        $message.html('<span style="color: #721c24;">✗ An error occurred</span>');
                        $button.prop('disabled', false);
                    }
                });
            });
            
            // Initialize on page load
            init();
        });
        </script>
        <?php
    }
    
    /**
     * Render a content row with sync status for all subsites
     */
    private function render_content_row($content, $indent_level, $subsites, $row_index = 0) {
        $status = $this->sync_manager->get_content_sync_status($content['id'], $content['type']);
        
        // Determine overall sync status for filtering
        $overall_status = 'synced';
        $has_never_synced = false;
        $has_out_of_sync = false;
        
        foreach ($subsites as $subsite) {
            $site_status = $status['subsites'][$subsite->id] ?? null;
            if ($site_status) {
                if ($site_status['sync_status'] === 'never_synced') {
                    $has_never_synced = true;
                } elseif (!$site_status['synced']) {
                    $has_out_of_sync = true;
                }
            }
        }
        
        if ($has_never_synced) {
            $overall_status = 'never-synced';
        } elseif ($has_out_of_sync) {
            $overall_status = 'out-of-sync';
        }
        
        ?>
        <tr class="sync-status-row" 
            data-status="<?php echo esc_attr($overall_status); ?>" 
            data-row-index="<?php echo esc_attr($row_index); ?>"
            data-content-id="<?php echo esc_attr($content['id']); ?>"
            data-content-type="<?php echo esc_attr($content['type']); ?>">
            <td>
                <input type="checkbox" class="sync-item-checkbox" 
                       data-content-id="<?php echo esc_attr($content['id']); ?>"
                       data-content-type="<?php echo esc_attr($content['type']); ?>" />
            </td>
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
