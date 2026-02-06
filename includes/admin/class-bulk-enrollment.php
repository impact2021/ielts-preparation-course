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
        $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $enrolled_count = 0;
        $course_id = $courses[0]; // Enroll in the first course found
        
        // Enroll each selected user
        foreach ($user_ids as $user_id) {
            $result = $this->enrollment->enroll($user_id, $course_id, 'active', $expiry_date);
            if ($result !== false) {
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
        // Check if we have enrolled users
        if (!isset($_GET['ielts_bulk_enrolled'])) {
            return;
        }
        
        $enrolled_count = intval($_GET['ielts_bulk_enrolled']);
        
        // Check for no courses error
        if (isset($_GET['ielts_bulk_enroll']) && $_GET['ielts_bulk_enroll'] === 'no_courses') {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('No IELTS courses found. Please create a course first.', 'ielts-course-manager'); ?></p>
            </div>
            <?php
            return;
        }
        
        // Show success message
        if ($enrolled_count > 0) {
            $course_id = isset($_GET['ielts_course_id']) ? intval($_GET['ielts_course_id']) : 0;
            $course_title = $course_id ? get_the_title($course_id) : 'course';
            $expiry_date = date('F j, Y', strtotime('+30 days'));
            
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
}
