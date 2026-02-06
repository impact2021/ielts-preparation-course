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
    
    public function __construct() {
        $this->enrollment = new IELTS_CM_Enrollment();
        
        // Add bulk action to users page
        add_filter('bulk_actions-users', array($this, 'add_bulk_action'));
        
        // Handle bulk action
        add_filter('handle_bulk_actions-users', array($this, 'handle_bulk_action'), 10, 3);
        
        // Show admin notice after bulk enrollment
        add_action('admin_notices', array($this, 'bulk_enrollment_admin_notice'));
    }
    
    /**
     * Add bulk enrollment action to users page
     */
    public function add_bulk_action($bulk_actions) {
        $bulk_actions['ielts_bulk_enroll'] = __('Enroll in IELTS Course (30 days)', 'ielts-course-manager');
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
        
        // Get all published IELTS courses
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        if (empty($courses)) {
            // No courses found, redirect with error
            $redirect_to = add_query_arg('ielts_bulk_enroll', 'no_courses', $redirect_to);
            return $redirect_to;
        }
        
        // Calculate expiry date (30 days from today)
        // Using WordPress timezone-aware function
        $expiry_timestamp = strtotime('+30 days', current_time('timestamp'));
        $expiry_date = date('Y-m-d H:i:s', $expiry_timestamp);
        
        $enrolled_count = 0;
        $course_id = $courses[0]; // Enroll in the first course found
        
        // Determine course group based on the course being enrolled
        $course_group = $this->get_course_group_from_course($course_id);
        
        // Enroll each selected user
        foreach ($user_ids as $user_id) {
            $result = $this->enrollment->enroll($user_id, $course_id, 'active', $expiry_date);
            if ($result !== false) {
                // Set user meta fields required for partner dashboard and access control
                $this->set_user_membership($user_id, $course_group, $expiry_date);
                $enrolled_count++;
            }
        }
        
        // Redirect with success message
        $redirect_to = add_query_arg('ielts_bulk_enrolled', $enrolled_count, $redirect_to);
        $redirect_to = add_query_arg('ielts_course_id', $course_id, $redirect_to);
        
        return $redirect_to;
    }
    
    /**
     * Show admin notice after bulk enrollment
     */
    public function bulk_enrollment_admin_notice() {
        // Check if we have enrolled users - sanitize the input
        if (!isset($_REQUEST['ielts_bulk_enrolled'])) {
            return;
        }
        
        $enrolled_count = intval($_REQUEST['ielts_bulk_enrolled']);
        
        // Check for no courses error - sanitize the input
        if (isset($_REQUEST['ielts_bulk_enroll']) && sanitize_key($_REQUEST['ielts_bulk_enroll']) === 'no_courses') {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('No IELTS courses found. Please create a course first.', 'ielts-course-manager'); ?></p>
            </div>
            <?php
            return;
        }
        
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
}
