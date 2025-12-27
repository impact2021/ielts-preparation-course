<?php
/**
 * Account management
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_MS_Account_Manager {
    
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_ielts_ms_update_email', array($this, 'update_email'));
        add_action('wp_ajax_ielts_ms_update_password', array($this, 'update_password'));
    }
    
    /**
     * Update user email
     */
    public function update_email() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $new_email = sanitize_email($_POST['new_email']);
        $password = $_POST['password'];
        
        // Validation
        if (empty($new_email) || empty($password)) {
            wp_send_json_error(array('message' => 'Please fill in all fields'));
        }
        
        if (!is_email($new_email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
        }
        
        // Check if email already exists
        $existing_user_id = email_exists($new_email);
        if ($existing_user_id && $existing_user_id != $user_id) {
            wp_send_json_error(array('message' => 'Email already in use'));
        }
        
        // Verify password
        $user = get_userdata($user_id);
        if (!wp_check_password($password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => 'Incorrect password'));
        }
        
        // Update email
        $result = wp_update_user(array(
            'ID' => $user_id,
            'user_email' => $new_email
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Email updated successfully'));
    }
    
    /**
     * Update user password
     */
    public function update_password() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => 'Please fill in all fields'));
        }
        
        if ($new_password !== $confirm_password) {
            wp_send_json_error(array('message' => 'New passwords do not match'));
        }
        
        if (strlen($new_password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
        }
        
        // Verify current password
        $user = get_userdata($user_id);
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => 'Current password is incorrect'));
        }
        
        // Update password
        wp_set_password($new_password, $user_id);
        
        // Re-authenticate user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array('message' => 'Password updated successfully'));
    }
}
