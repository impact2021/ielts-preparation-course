<?php
/**
 * Bulk enrollment functionality for WordPress users page
 * This is a one-time feature for legacy users migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Bulk_Enrollment {
    
    private $enrollment;
    
    // Role mapping for course groups
    private $role_mapping = array(
        'academic_module' => 'access_academic_module',
        'general_module' => 'access_general_module',
        'general_english' => 'access_general_english'
    );
    
    // Cache for course group lookups to avoid repeated database queries
    private $course_group_cache = array();
    
    // Debug log for visible debugger
    private $debug_log = array();
    
    public function __construct() {
        $this->enrollment = new IELTS_CM_Enrollment();
        
        // Add bulk action to users page
        add_filter('bulk_actions-users', array($this, 'add_bulk_action'));
        
        // Handle bulk action
        add_filter('handle_bulk_actions-users', array($this, 'handle_bulk_action'), 10, 3);
        
        // Show admin notice after bulk enrollment
        add_action('admin_notices', array($this, 'bulk_enrollment_admin_notice'));
        
        // Add visible debugger panel to users page
        add_action('admin_footer-users.php', array($this, 'render_debug_panel'));
        
        // AJAX handler for clearing debug log
        add_action('wp_ajax_clear_bulk_enrollment_debug_log', array($this, 'clear_debug_log_ajax'));
    }
    
    /**
     * Add bulk enrollment action to users page
     */
    public function add_bulk_action($bulk_actions) {
        $bulk_actions['ielts_bulk_enroll'] = __('Enroll in Academic Module (Access Code) - 30 days', 'ielts-course-manager');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk enrollment action
     */
    public function handle_bulk_action($redirect_to, $action, $user_ids) {
        // Only proceed if our bulk action was triggered
        if ($action !== 'ielts_bulk_enroll') {
            return $redirect_to;
        }
        
        // Log the start of the bulk enrollment process
        $this->log_debug('Bulk enrollment started for ' . count($user_ids) . ' user(s)');
        
        // DIAGNOSTIC: Check if post type exists
        if (!post_type_exists('ielts_course')) {
            $this->log_debug('CRITICAL ERROR: Post type ielts_course does not exist!');
            error_log('IELTS Bulk Enrollment CRITICAL: Post type ielts_course not registered when bulk action triggered');
            $redirect_to = add_query_arg('ielts_bulk_enroll', 'post_type_not_registered', $redirect_to);
            return $redirect_to;
        }
        
        // DIAGNOSTIC: Check if taxonomy exists
        if (!taxonomy_exists('ielts_course_category')) {
            $this->log_debug('WARNING: Taxonomy ielts_course_category does not exist');
            error_log('IELTS Bulk Enrollment WARNING: Taxonomy not registered, will skip category filtering');
        }
        
        // Get Academic module courses first (with academic or academic-practice-tests category)
        $academic_courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ielts_course_category',
                    'field' => 'slug',
                    'terms' => array('academic', 'academic-practice-tests'),
                    'operator' => 'IN'
                )
            )
        ));
        
        $this->log_debug('Academic courses found: ' . count($academic_courses));
        
        // If no academic courses found, get any published course as fallback
        // NOTE: This is intentional - we still want to enroll users even if no Academic 
        // courses exist yet, and they'll still get Academic Module membership
        if (empty($academic_courses)) {
            $this->log_debug('WARNING: No Academic courses found. Falling back to any available course.');
            error_log('IELTS Bulk Enrollment WARNING: No Academic courses found. Falling back to any available course but users will still get Academic Module membership.');
            $academic_courses = get_posts(array(
                'post_type' => 'ielts_course',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'fields' => 'ids'
            ));
            $this->log_debug('Fallback: Total courses found: ' . count($academic_courses));
        }
        
        if (empty($academic_courses)) {
            // No courses found at all, redirect with error
            $this->log_debug('ERROR: No courses found at all in the database');
            error_log('IELTS Bulk Enrollment ERROR: No courses found at all in the database');
            $redirect_to = add_query_arg('ielts_bulk_enroll', 'no_courses_at_all', $redirect_to);
            return $redirect_to;
        }
        
        // Calculate expiry date (30 days from today)
        // Using WordPress timezone-aware function
        $expiry_timestamp = strtotime('+30 days', current_time('timestamp'));
        $expiry_date = date('Y-m-d H:i:s', $expiry_timestamp);
        
        $enrolled_count = 0;
        $course_id = $academic_courses[0]; // Enroll in the first Academic course found
        $this->log_debug('Selected course ID: ' . $course_id . ' (' . get_the_title($course_id) . ')');
        
        // Always use academic_module for this bulk enrollment
        // This ensures users appear in the partner dashboard with the correct membership
        $course_group = 'academic_module';
        
        // Enroll each selected user
        foreach ($user_ids as $user_id) {
            $result = $this->enrollment->enroll($user_id, $course_id, 'active', $expiry_date);
            if ($result !== false) {
                // Set user meta fields required for partner dashboard and access control
                $this->set_user_membership($user_id, $course_group, $expiry_date);
                $enrolled_count++;
                $this->log_debug('User ID ' . $user_id . ' enrolled successfully');
            } else {
                $this->log_debug('ERROR: Failed to enroll user ID ' . $user_id);
            }
        }
        
        $this->log_debug('Bulk enrollment completed. Total enrolled: ' . $enrolled_count);
        
        // Redirect with success message
        $redirect_to = add_query_arg('ielts_bulk_enrolled', $enrolled_count, $redirect_to);
        $redirect_to = add_query_arg('ielts_course_id', $course_id, $redirect_to);
        
        return $redirect_to;
    }
    
    /**
     * Show admin notice after bulk enrollment
     */
    public function bulk_enrollment_admin_notice() {
        // Check for error conditions first
        if (isset($_REQUEST['ielts_bulk_enroll'])) {
            $error_type = sanitize_key($_REQUEST['ielts_bulk_enroll']);
            
            if ($error_type === 'no_courses_at_all') {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php _e('Bulk Enrollment Failed:', 'ielts-course-manager'); ?></strong> <?php _e('No IELTS courses found. Please create and publish at least one course first.', 'ielts-course-manager'); ?></p>
                    <p><?php _e('Check the debug panel at the bottom-right of this page for more details.', 'ielts-course-manager'); ?></p>
                </div>
                <?php
                return;
            }
            
            if ($error_type === 'post_type_not_registered') {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php _e('Bulk Enrollment Failed:', 'ielts-course-manager'); ?></strong> <?php _e('IELTS Course post type is not registered. This is a critical plugin error.', 'ielts-course-manager'); ?></p>
                    <p><?php _e('Please deactivate and reactivate the IELTS Course Manager plugin, or contact support.', 'ielts-course-manager'); ?></p>
                </div>
                <?php
                return;
            }
        }
        
        // Check if we have enrolled users - sanitize the input
        if (!isset($_REQUEST['ielts_bulk_enrolled'])) {
            return;
        }
        
        $enrolled_count = intval($_REQUEST['ielts_bulk_enrolled']);
        
        // Show success message
        if ($enrolled_count > 0) {
            $course_id = isset($_REQUEST['ielts_course_id']) ? intval($_REQUEST['ielts_course_id']) : 0;
            $course_title = $course_id ? get_the_title($course_id) : 'course';
            // Use WordPress timezone-aware date function
            $expiry_timestamp = strtotime('+30 days', current_time('timestamp'));
            $expiry_date = date_i18n('F j, Y', $expiry_timestamp);
            
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php 
                    printf(
                        _n(
                            '%d user enrolled in %s with expiry date: %s',
                            '%d users enrolled in %s with expiry date: %s',
                            $enrolled_count,
                            'ielts-course-manager'
                        ),
                        $enrolled_count,
                        '<strong>' . esc_html($course_title) . '</strong>',
                        '<strong>' . esc_html($expiry_date) . '</strong>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Determine course group from course categories
     * Priority: academic_module > general_module > general_english
     * 
     * @param int $course_id The WordPress post ID of the IELTS course
     * @return string One of 'academic_module', 'general_module', or 'general_english'
     */
    private function get_course_group_from_course($course_id) {
        // Check cache first to avoid repeated database queries
        if (isset($this->course_group_cache[$course_id])) {
            return $this->course_group_cache[$course_id];
        }
        
        $categories = wp_get_post_terms($course_id, 'ielts_course_category', array('fields' => 'slugs'));
        
        // Handle error from wp_get_post_terms
        if (is_wp_error($categories)) {
            // Log error details for debugging
            error_log('IELTS Bulk Enrollment: Failed to get course categories for course ' . $course_id . ': ' . $categories->get_error_message());
            // Default to academic_module if we can't determine the category
            $this->course_group_cache[$course_id] = 'academic_module';
            return 'academic_module';
        }
        
        // Check all categories in a single loop with priority order
        $has_general = false;
        $has_english = false;
        
        foreach ($categories as $cat_slug) {
            // Check for academic-specific categories first (highest priority)
            if ($cat_slug === 'academic' || $cat_slug === 'academic-practice-tests') {
                $this->course_group_cache[$course_id] = 'academic_module';
                return 'academic_module';
            }
            
            // Track general and english for later evaluation
            if ($cat_slug === 'general' || $cat_slug === 'general-practice-tests') {
                $has_general = true;
            }
            if ($cat_slug === 'english') {
                $has_english = true;
            }
        }
        
        // Return based on what we found (priority: general > english)
        $result = 'academic_module'; // default
        if ($has_general) {
            $result = 'general_module';
        } elseif ($has_english) {
            $result = 'general_english';
        }
        
        $this->course_group_cache[$course_id] = $result;
        return $result;
    }
    
    /**
     * Set user membership meta fields and assign role
     * 
     * @param int $user_id WordPress user ID
     * @param string $course_group Course group type (academic_module, general_module, or general_english)
     * @param string $expiry_date Membership expiry date in Y-m-d H:i:s format
     */
    private function set_user_membership($user_id, $course_group, $expiry_date) {
        // Fallback: if course_group is not recognized, default to academic_module
        if (!isset($this->role_mapping[$course_group])) {
            error_log("IELTS Bulk Enrollment: Unknown course group '{$course_group}' for user {$user_id}, defaulting to academic_module");
            $course_group = 'academic_module';
        }
        
        // Set legacy user meta fields (required for partner dashboard)
        update_user_meta($user_id, 'iw_course_group', $course_group);
        update_user_meta($user_id, 'iw_membership_expiry', $expiry_date);
        update_user_meta($user_id, 'iw_membership_status', 'active');
        
        $membership_type = $this->role_mapping[$course_group];
        
        // Set new membership meta fields (used by is_enrolled check)
        update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
        update_user_meta($user_id, '_ielts_cm_membership_status', 'active');
        update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
        
        // Assign WordPress role
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("IELTS Bulk Enrollment: Failed to get user data for user ID {$user_id}");
            return;
        }
        
        // Remove any existing access code membership roles first
        foreach ($this->role_mapping as $role) {
            $user->remove_role($role);
        }
        
        // Add the new role
        $user->add_role($membership_type);
    }
    
    /**
     * Log debug message for visible debugger
     */
    private function log_debug($message) {
        $log_entry = array(
            'time' => current_time('Y-m-d H:i:s'),
            'message' => $message
        );
        
        // Add to current session log
        $this->debug_log[] = $log_entry;
        
        // Store in transient for persistence across page loads (30 minutes)
        $stored_logs = get_transient('ielts_bulk_enrollment_debug_log');
        if (!is_array($stored_logs)) {
            $stored_logs = array();
        }
        $stored_logs[] = $log_entry;
        
        // Keep only last 50 log entries
        if (count($stored_logs) > 50) {
            $stored_logs = array_slice($stored_logs, -50);
        }
        
        set_transient('ielts_bulk_enrollment_debug_log', $stored_logs, 30 * MINUTE_IN_SECONDS);
        
        error_log('IELTS Bulk Enrollment Debug: ' . $message);
    }
    
    /**
     * Render visible debug panel on users page
     */
    public function render_debug_panel() {
        // Get diagnostic information
        $all_courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ));
        
        $published_courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        $academic_courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ielts_course_category',
                    'field' => 'slug',
                    'terms' => array('academic', 'academic-practice-tests'),
                    'operator' => 'IN'
                )
            )
        ));
        
        // Get all course categories
        $all_categories = get_terms(array(
            'taxonomy' => 'ielts_course_category',
            'hide_empty' => false
        ));
        
        // Get course details for published courses
        $course_details = array();
        foreach ($published_courses as $course_id) {
            $categories = wp_get_post_terms($course_id, 'ielts_course_category', array('fields' => 'names'));
            $course_details[] = array(
                'id' => $course_id,
                'title' => get_the_title($course_id),
                'status' => get_post_status($course_id),
                'categories' => is_array($categories) ? implode(', ', $categories) : 'None'
            );
        }
        
        ?>
        <div id="ielts-bulk-enrollment-debugger" style="position: fixed; bottom: 20px; right: 20px; width: 400px; max-height: 600px; overflow-y: auto; background: #fff; border: 2px solid #2271b1; box-shadow: 0 2px 10px rgba(0,0,0,0.2); z-index: 99999; border-radius: 4px;">
            <div style="background: #2271b1; color: #fff; padding: 12px 15px; font-weight: bold; cursor: move; display: flex; justify-content: space-between; align-items: center;" id="debug-panel-header">
                <span>📊 Bulk Enrollment Debugger</span>
                <button id="toggle-debug-panel" style="background: none; border: none; color: #fff; cursor: pointer; font-size: 18px; padding: 0;">−</button>
            </div>
            <div id="debug-panel-content" style="padding: 15px; font-size: 13px; line-height: 1.6;">
                
                <div style="margin-bottom: 15px; padding: 10px; background: <?php echo empty($published_courses) ? '#f8d7da' : '#d1ecf1'; ?>; border-left: 4px solid <?php echo empty($published_courses) ? '#dc3545' : '#17a2b8'; ?>;">
                    <strong>System Status:</strong>
                    <?php if (empty($published_courses)): ?>
                        <span style="color: #dc3545;">⚠️ No published courses found!</span>
                    <?php else: ?>
                        <span style="color: #28a745;">✓ System operational</span>
                    <?php endif; ?>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <strong>📚 Course Statistics:</strong><br>
                    <div style="margin-left: 15px; margin-top: 5px;">
                        • Total courses (all statuses): <strong><?php echo count($all_courses); ?></strong><br>
                        • Published courses: <strong><?php echo count($published_courses); ?></strong><br>
                        • Academic module courses: <strong><?php echo count($academic_courses); ?></strong>
                    </div>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <strong>🏷️ Available Categories:</strong><br>
                    <div style="margin-left: 15px; margin-top: 5px;">
                        <?php if (empty($all_categories) || is_wp_error($all_categories)): ?>
                            <em style="color: #dc3545;">No categories found</em>
                        <?php else: ?>
                            <?php foreach ($all_categories as $cat): ?>
                                • <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->slug); ?>)<br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($course_details)): ?>
                <div style="margin-bottom: 10px;">
                    <strong>📖 Published Courses:</strong><br>
                    <div style="margin-left: 15px; margin-top: 5px; max-height: 150px; overflow-y: auto; font-size: 11px;">
                        <?php foreach ($course_details as $detail): ?>
                            • ID: <?php echo $detail['id']; ?> - <?php echo esc_html($detail['title']); ?><br>
                            &nbsp;&nbsp;Categories: <em><?php echo esc_html($detail['categories']); ?></em><br>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php 
                // Get stored logs from transient
                $stored_logs = get_transient('ielts_bulk_enrollment_debug_log');
                if (!empty($stored_logs) && is_array($stored_logs)): 
                ?>
                <div style="margin-bottom: 10px;">
                    <strong>📝 Recent Activity Log:</strong>
                    <button onclick="clearDebugLog()" style="float: right; font-size: 10px; padding: 2px 6px; cursor: pointer;">Clear Log</button>
                    <br>
                    <div style="margin-left: 15px; margin-top: 5px; max-height: 150px; overflow-y: auto; background: #f5f5f5; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 11px;">
                        <?php foreach (array_reverse($stored_logs) as $log): ?>
                            <div style="margin-bottom: 5px; <?php echo strpos($log['message'], 'ERROR') !== false ? 'color: #dc3545;' : (strpos($log['message'], 'WARNING') !== false ? 'color: #ffc107;' : ''); ?>">
                                [<?php echo esc_html($log['time']); ?>] <?php echo esc_html($log['message']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php
                // Display URL parameters if present
                if (isset($_GET['ielts_bulk_enroll']) || isset($_GET['ielts_bulk_enrolled'])):
                ?>
                <div style="margin-bottom: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <strong>⚡ Current Action:</strong><br>
                    <?php if (isset($_GET['ielts_bulk_enroll']) && $_GET['ielts_bulk_enroll'] === 'no_courses_at_all'): ?>
                        <span style="color: #dc3545;">❌ Error: No courses found in database</span>
                    <?php elseif (isset($_GET['ielts_bulk_enrolled'])): ?>
                        <span style="color: #28a745;">✓ Successfully enrolled <?php echo intval($_GET['ielts_bulk_enrolled']); ?> user(s)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 11px; color: #666;">
                    <strong>💡 Troubleshooting:</strong><br>
                    • If no courses are found, create an IELTS course first<br>
                    • Ensure courses are published (not draft)<br>
                    • Add "academic" or "academic-practice-tests" category<br>
                    • Check WordPress error logs for details
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Make panel draggable
            var isDragging = false;
            var currentX;
            var currentY;
            var initialX;
            var initialY;
            var xOffset = 0;
            var yOffset = 0;
            
            var container = document.getElementById("ielts-bulk-enrollment-debugger");
            var header = document.getElementById("debug-panel-header");
            
            header.addEventListener("mousedown", dragStart);
            document.addEventListener("mousemove", drag);
            document.addEventListener("mouseup", dragEnd);
            
            function dragStart(e) {
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;
                
                if (e.target === header || header.contains(e.target)) {
                    isDragging = true;
                }
            }
            
            function drag(e) {
                if (isDragging) {
                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                    xOffset = currentX;
                    yOffset = currentY;
                    
                    setTranslate(currentX, currentY, container);
                }
            }
            
            function dragEnd(e) {
                initialX = currentX;
                initialY = currentY;
                isDragging = false;
            }
            
            function setTranslate(xPos, yPos, el) {
                el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)";
            }
            
            // Toggle panel
            $('#toggle-debug-panel').click(function(e) {
                e.stopPropagation();
                var content = $('#debug-panel-content');
                if (content.is(':visible')) {
                    content.slideUp();
                    $(this).text('+');
                } else {
                    content.slideDown();
                    $(this).text('−');
                }
            });
        });
        
        // Clear debug log function
        function clearDebugLog() {
            if (confirm('Clear all debug logs?')) {
                jQuery.post(ajaxurl, {
                    action: 'clear_bulk_enrollment_debug_log',
                    nonce: '<?php echo wp_create_nonce('clear_debug_log'); ?>'
                }, function() {
                    location.reload();
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * AJAX handler to clear debug log
     */
    public function clear_debug_log_ajax() {
        // Verify nonce
        check_ajax_referer('clear_debug_log', 'nonce');
        
        // Clear the transient
        delete_transient('ielts_bulk_enrollment_debug_log');
        
        wp_send_json_success();
    }
}
