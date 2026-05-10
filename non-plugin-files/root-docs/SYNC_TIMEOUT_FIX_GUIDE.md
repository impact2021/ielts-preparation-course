# Course Sync Timeout Fix Implementation Guide

## Problem Summary
When syncing a course from the primary site to subsites, the operation causes all pages on subsites to hang for a long time before timeout errors occur. The root cause is in `includes/class-multi-site-sync.php` where the `push_content_to_subsites()` method pushes content to all subsites synchronously in a sequential loop.

## Root Cause Analysis
1. **Current Flow**: When syncing a course:
   - Push course → wait for all subsites to respond
   - For each lesson: Push resources/quizzes → wait for all subsites
   - Then push lesson → wait for all subsites
   - This creates N * M blocking operations (N items × M subsites)

2. **Why It Hangs**:
   - Each HTTP request to a subsite blocks until complete
   - Network latency multiplies (3 subsites × 10 second timeout = 30 seconds minimum)
   - Large courses with many lessons/resources cause exponential delays
   - PHP max_execution_time of 300 seconds (5 minutes) is insufficient

## Solution Implemented (Phase 1)

### 1. Sync Progress Tracker (`includes/class-sync-progress-tracker.php`) ✅ COMPLETE
- Tracks sync operations with unique IDs
- Records progress for each item and subsite
- Stores success/failure status and error messages
- Auto-cleans up old progress data after 24 hours

## Solution To Be Implemented (Phase 2)

### 2. Modify Sync Methods to Report Progress

**File**: `includes/class-multi-site-sync.php`

Update these methods to use the progress tracker:

```php
public function push_content_with_children($content_id, $content_type, $sync_id = null) {
    // Create sync ID if not provided
    if (!$sync_id) {
        $progress_tracker = new IELTS_CM_Sync_Progress_Tracker();
        $sync_id = $progress_tracker->generate_sync_id();
    }
    
    // Calculate total items before starting
    $total_items = 1; // Main content
    if ($content_type === 'course') {
        $lessons = $this->get_course_lessons($content_id);
        foreach ($lessons as $lesson) {
            $total_items++; // Lesson
            $total_items += count($this->get_lesson_resources($lesson->ID));
            $total_items += count($this->get_lesson_exercises($lesson->ID));
        }
    } elseif ($content_type === 'lesson') {
        $total_items += count($this->get_lesson_resources($content_id));
        $total_items += count($this->get_lesson_exercises($content_id));
    }
    
    // Start tracking
    $progress_tracker->start_sync($sync_id, $total_items, $this->get_connected_subsites());
    
    // Rest of sync logic...
    // Update progress after each item syncs
}
```

Update `push_to_subsite()` to report progress:

```php
private function push_to_subsite($content_id, $content_type, $content_hash, $subsite, $sync_id = null) {
    // Existing code...
    
    // After sync attempt
    if ($sync_id) {
        $progress_tracker = new IELTS_CM_Sync_Progress_Tracker();
        $post = get_post($content_id);
        $progress_tracker->update_item_progress(
            $sync_id,
            $content_type,
            $content_id,
            $post->post_title,
            $subsite->id,
            $success,
            $error_message
        );
    }
    
    // Return result...
}
```

### 3. Add AJAX Endpoints for Progress Monitoring

**File**: `includes/admin/class-admin.php`

Add new AJAX handlers:

```php
// Register AJAX actions
add_action('wp_ajax_ielts_cm_start_async_sync', array($this, 'ajax_start_async_sync'));
add_action('wp_ajax_ielts_cm_get_sync_progress', array($this, 'ajax_get_sync_progress'));

/**
 * Start an async sync operation
 */
public function ajax_start_async_sync() {
    check_ajax_referer('ielts_cm_sync_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : '';
    
    if (!$post_id || !$content_type) {
        wp_send_json_error(array('message' => 'Invalid parameters'));
        return;
    }
    
    $sync_manager = new IELTS_CM_Multi_Site_Sync();
    $progress_tracker = new IELTS_CM_Sync_Progress_Tracker();
    
    // Generate sync ID
    $sync_id = $progress_tracker->generate_sync_id();
    
    // Start sync in background using WordPress scheduling
    // This allows the AJAX call to return immediately
    wp_schedule_single_event(time(), 'ielts_cm_do_sync', array(
        'sync_id' => $sync_id,
        'post_id' => $post_id,
        'content_type' => $content_type
    ));
    
    // Return sync ID for progress tracking
    wp_send_json_success(array(
        'sync_id' => $sync_id,
        'message' => 'Sync started'
    ));
}

/**
 * Get progress for a sync operation
 */
public function ajax_get_sync_progress() {
    check_ajax_referer('ielts_cm_sync_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $sync_id = isset($_POST['sync_id']) ? sanitize_text_field($_POST['sync_id']) : '';
    
    if (!$sync_id) {
        wp_send_json_error(array('message' => 'Invalid sync ID'));
        return;
    }
    
    $progress_tracker = new IELTS_CM_Sync_Progress_Tracker();
    $progress = $progress_tracker->get_progress($sync_id);
    
    if (!$progress) {
        wp_send_json_error(array('message' => 'Sync not found'));
        return;
    }
    
    wp_send_json_success($progress);
}
```

Also add the cron hook handler:

```php
add_action('ielts_cm_do_sync', array($this, 'do_background_sync'), 10, 1);

public function do_background_sync($args) {
    $sync_id = $args['sync_id'];
    $post_id = $args['post_id'];
    $content_type = $args['content_type'];
    
    // Increase time limit
    @set_time_limit(0); // Unlimited
    
    $sync_manager = new IELTS_CM_Multi_Site_Sync();
    
    // Perform sync with progress tracking
    if ($content_type === 'course' || $content_type === 'lesson') {
        $sync_manager->push_content_with_children($post_id, $content_type, $sync_id);
    } else {
        $sync_manager->push_content_to_subsites($post_id, $content_type, $sync_id);
    }
}
```

### 4. Create Visual Progress Panel UI

**File**: Create `includes/admin/assets/js/sync-progress-panel.js`

```javascript
(function($) {
    'use strict';
    
    var SyncProgressPanel = {
        syncId: null,
        pollInterval: null,
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Intercept existing sync buttons
            $(document).on('click', '.ielts-push-content', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $button = $(this);
                var postId = $button.data('post-id');
                var contentType = $button.data('content-type');
                
                SyncProgressPanel.startSync(postId, contentType);
            });
        },
        
        startSync: function(postId, contentType) {
            // Show progress modal
            this.showProgressModal();
            
            // Start async sync
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_start_async_sync',
                    nonce: ielts_cm_admin.sync_nonce,
                    post_id: postId,
                    content_type: contentType
                },
                success: function(response) {
                    if (response.success) {
                        SyncProgressPanel.syncId = response.data.sync_id;
                        SyncProgressPanel.startPolling();
                    } else {
                        SyncProgressPanel.showError(response.data.message);
                    }
                },
                error: function() {
                    SyncProgressPanel.showError('Failed to start sync');
                }
            });
        },
        
        startPolling: function() {
            // Poll every 2 seconds
            this.pollInterval = setInterval(function() {
                SyncProgressPanel.updateProgress();
            }, 2000);
        },
        
        updateProgress: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_get_sync_progress',
                    nonce: ielts_cm_admin.sync_nonce,
                    sync_id: this.syncId
                },
                success: function(response) {
                    if (response.success) {
                        SyncProgressPanel.renderProgress(response.data);
                        
                        // Stop polling if complete
                        if (response.data.status === 'completed' || response.data.status === 'failed') {
                            clearInterval(SyncProgressPanel.pollInterval);
                        }
                    }
                }
            });
        },
        
        renderProgress: function(progress) {
            var percent = (progress.completed_items / progress.total_items) * 100;
            
            // Update progress bar
            $('#sync-progress-bar').css('width', percent + '%');
            $('#sync-progress-text').text(
                progress.completed_items + ' / ' + progress.total_items + ' items synced'
            );
            
            // Update current item
            if (progress.current_item && progress.current_subsite) {
                $('#sync-current-item').text(
                    'Syncing: ' + progress.current_item + ' to ' + progress.current_subsite
                );
            }
            
            // Update subsite statuses
            var subsitesHtml = '';
            $.each(progress.subsites, function(id, subsite) {
                var statusClass = 'status-' + subsite.status;
                subsitesHtml += '<div class="subsite-status ' + statusClass + '">';
                subsitesHtml += '<strong>' + subsite.name + '</strong>: ';
                subsitesHtml += subsite.items_synced + ' synced, ';
                subsitesHtml += subsite.items_failed + ' failed';
                if (subsite.last_error) {
                    subsitesHtml += '<br><span class="error">' + subsite.last_error + '</span>';
                }
                subsitesHtml += '</div>';
            });
            $('#sync-subsites-status').html(subsitesHtml);
            
            // Show errors if any
            if (progress.errors && progress.errors.length > 0) {
                var errorsHtml = '<h4>Errors:</h4><ul>';
                $.each(progress.errors.slice(-5), function(i, error) {
                    errorsHtml += '<li><strong>' + error.item + '</strong> to ' + error.subsite + ': ' + error.error + '</li>';
                });
                errorsHtml += '</ul>';
                $('#sync-errors').html(errorsHtml);
            }
        },
        
        showProgressModal: function() {
            // Create modal HTML
            var modalHtml = `
                <div id="sync-progress-modal" class="sync-progress-modal">
                    <div class="sync-progress-content">
                        <h2>Syncing Content to Subsites</h2>
                        <div class="progress-bar-container">
                            <div id="sync-progress-bar" class="progress-bar"></div>
                        </div>
                        <div id="sync-progress-text" class="progress-text">Starting sync...</div>
                        <div id="sync-current-item" class="current-item"></div>
                        <div id="sync-subsites-status" class="subsites-status"></div>
                        <div id="sync-errors" class="sync-errors"></div>
                        <button id="sync-close-button" class="button" disabled>Close</button>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
        },
        
        showError: function(message) {
            alert('Sync Error: ' + message);
            this.hideProgressModal();
        },
        
        hideProgressModal: function() {
            $('#sync-progress-modal').remove();
        }
    };
    
    $(document).ready(function() {
        SyncProgressPanel.init();
    });
    
})(jQuery);
```

### 5. Add Modal Styles

**File**: Create `includes/admin/assets/css/sync-progress-panel.css`

```css
.sync-progress-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100000;
}

.sync-progress-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.sync-progress-content h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.progress-bar-container {
    width: 100%;
    height: 30px;
    background: #e0e0e0;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 15px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #0073aa 0%, #46b450 100%);
    transition: width 0.3s ease;
    width: 0%;
}

.progress-text {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.current-item {
    font-size: 13px;
    color: #333;
    margin-bottom: 20px;
    padding: 10px;
    background: #f5f5f5;
    border-radius: 4px;
}

.subsites-status {
    margin-bottom: 20px;
}

.subsite-status {
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    border-left: 4px solid #ccc;
}

.subsite-status.status-pending {
    background: #f9f9f9;
    border-left-color: #999;
}

.subsite-status.status-in_progress {
    background: #e3f2fd;
    border-left-color: #0073aa;
}

.subsite-status.status-completed {
    background: #d4edda;
    border-left-color: #46b450;
}

.subsite-status.status-failed {
    background: #f8d7da;
    border-left-color: #dc3232;
}

.subsite-status .error {
    color: #dc3232;
    font-size: 12px;
}

.sync-errors {
    margin-bottom: 20px;
    padding: 15px;
    background: #fff3cd;
    border-radius: 4px;
    border-left: 4px solid #ffc107;
}

.sync-errors h4 {
    margin-top: 0;
    color: #856404;
}

.sync-errors ul {
    margin: 0;
    padding-left: 20px;
}

.sync-errors li {
    color: #856404;
    margin-bottom: 5px;
}

#sync-close-button {
    margin-top: 15px;
}

#sync-close-button:not(:disabled) {
    background: #46b450;
    color: #fff;
    border-color: #46b450;
}
```

### 6. Enqueue Assets

**File**: `includes/admin/class-admin.php`

In the `enqueue_admin_scripts` method, add:

```php
// Enqueue sync progress panel assets on sync pages
if ($screen->id === 'ielts_course' || $screen->id === 'ielts_lesson' || $screen->id === 'edit-ielts_course' || $screen->id === 'edit-ielts_lesson' || $screen->id === 'toplevel_page_ielts-cm-sync-status') {
    wp_enqueue_style(
        'ielts-cm-sync-progress-panel',
        IELTS_CM_PLUGIN_URL . 'includes/admin/assets/css/sync-progress-panel.css',
        array(),
        IELTS_CM_VERSION
    );
    
    wp_enqueue_script(
        'ielts-cm-sync-progress-panel',
        IELTS_CM_PLUGIN_URL . 'includes/admin/assets/js/sync-progress-panel.js',
        array('jquery'),
        IELTS_CM_VERSION,
        true
    );
}
```

## Benefits of This Solution

1. **Non-blocking**: Sync starts immediately and runs in background
2. **Real-time feedback**: Users see exactly what's syncing where
3. **Error visibility**: Errors are displayed per subsite with details
4. **No timeouts**: Background process has unlimited execution time
5. **Better UX**: Visual progress bar and status updates
6. **Staggered option**: Can be enhanced to sync one subsite at a time if needed

## Testing Checklist

- [ ] Test syncing a course with 1 subsite
- [ ] Test syncing a course with multiple subsites
- [ ] Test sync with network failures (disconnect a subsite)
- [ ] Verify progress updates in real-time
- [ ] Test closing and reopening progress modal
- [ ] Verify old progress data cleanup (24 hours)
- [ ] Test concurrent sync operations
- [ ] Verify no PHP timeouts occur
- [ ] Check that subsites don't hang during sync

## Alternative: Staggered Sync (One Subsite at a Time)

If you want to sync one subsite at a time instead of all at once, modify `push_content_to_subsites`:

```php
public function push_content_to_subsites($content_id, $content_type, $sync_id = null, $stagger = false) {
    // ...existing code...
    
    if ($stagger) {
        // Sync one at a time with delays
        foreach ($subsites as $subsite) {
            $result = $this->push_to_subsite($content_id, $content_type, $content_hash, $subsite, $sync_id);
            $results[$subsite->id] = $result;
            
            // Wait 2 seconds between subsites to reduce load
            sleep(2);
        }
    } else {
        // Original parallel sync
        foreach ($subsites as $subsite) {
            $result = $this->push_to_subsite($content_id, $content_type, $content_hash, $subsite, $sync_id);
            $results[$subsite->id] = $result;
        }
    }
    
    return $results;
}
```

Add a checkbox in the sync UI to enable staggered mode if desired.
