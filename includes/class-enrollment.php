<?php
/**
 * Course enrollment functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Enrollment {
    
    private $db;
    
    public function __construct() {
        $this->db = new IELTS_CM_Database();
        
        // AJAX handlers
        add_action('wp_ajax_ielts_cm_enroll', array($this, 'enroll_user'));
        add_action('wp_ajax_ielts_cm_unenroll', array($this, 'unenroll_user'));
    }
    
    /**
     * Enroll a user in a course
     */
    public function enroll($user_id, $course_id, $status = 'active') {
        global $wpdb;
        $table = $this->db->get_enrollment_table();
        
        // Check if already enrolled
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND course_id = %d",
            $user_id, $course_id
        ));
        
        if ($existing) {
            // Update status
            return $wpdb->update(
                $table,
                array('status' => $status),
                array('id' => $existing->id)
            );
        } else {
            // Insert new enrollment
            return $wpdb->insert($table, array(
                'user_id' => $user_id,
                'course_id' => $course_id,
                'status' => $status,
                'enrolled_date' => current_time('mysql')
            ));
        }
    }
    
    /**
     * Unenroll a user from a course
     */
    public function unenroll($user_id, $course_id) {
        global $wpdb;
        $table = $this->db->get_enrollment_table();
        
        return $wpdb->update(
            $table,
            array('status' => 'inactive'),
            array('user_id' => $user_id, 'course_id' => $course_id)
        );
    }
    
    /**
     * Check if user is enrolled in a course
     */
    public function is_enrolled($user_id, $course_id) {
        global $wpdb;
        $table = $this->db->get_enrollment_table();
        
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM $table WHERE user_id = %d AND course_id = %d",
            $user_id, $course_id
        ));
        
        return $enrollment && $enrollment->status === 'active';
    }
    
    /**
     * Get all enrolled courses for a user
     */
    public function get_user_courses($user_id) {
        global $wpdb;
        $table = $this->db->get_enrollment_table();
        
        $courses = $wpdb->get_results($wpdb->prepare(
            "SELECT course_id, enrolled_date FROM $table WHERE user_id = %d AND status = 'active' ORDER BY enrolled_date DESC",
            $user_id
        ));
        
        return $courses;
    }
    
    /**
     * Get all enrolled users for a course
     */
    public function get_course_users($course_id) {
        global $wpdb;
        $table = $this->db->get_enrollment_table();
        
        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, enrolled_date FROM $table WHERE course_id = %d AND status = 'active' ORDER BY enrolled_date DESC",
            $course_id
        ));
        
        return $users;
    }
    
    /**
     * AJAX handler for enrolling a user
     */
    public function enroll_user() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $course_id = intval($_POST['course_id']);
        
        $result = $this->enroll($user_id, $course_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Enrolled successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to enroll'));
        }
    }
    
    /**
     * AJAX handler for unenrolling a user
     */
    public function unenroll_user() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $course_id = intval($_POST['course_id']);
        
        $result = $this->unenroll($user_id, $course_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Unenrolled successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to unenroll'));
        }
    }
}
