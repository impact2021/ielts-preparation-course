<?php
/**
 * Login and registration management
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_MS_Login_Manager {
    
    public function __construct() {
        // Redirect default WordPress login to custom page
        add_action('init', array($this, 'redirect_wp_login'));
        
        // AJAX handlers
        add_action('wp_ajax_nopriv_ielts_ms_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_ielts_ms_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_ielts_ms_forgot_password', array($this, 'handle_forgot_password'));
        add_action('wp_ajax_nopriv_ielts_ms_reset_password', array($this, 'handle_reset_password'));
    }
    
    /**
     * Redirect default WordPress login to custom page
     */
    public function redirect_wp_login() {
        global $pagenow;
        
        // Check if custom login is enabled
        if (!get_option('ielts_ms_custom_login_enabled', true)) {
            return;
        }
        
        // Don't redirect if user is already logged in or if it's an AJAX request
        if (is_user_logged_in() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }
        
        // Redirect wp-login.php to custom login page
        if ($pagenow === 'wp-login.php' && !isset($_GET['action'])) {
            $login_page_id = get_option('ielts_ms_login_page_id');
            if ($login_page_id) {
                wp_redirect(get_permalink($login_page_id));
                exit;
            }
        }
    }
    
    /**
     * Handle login AJAX request
     */
    public function handle_login() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        $creds = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        );
        
        $user = wp_signon($creds, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => 'Login successful',
            'redirect' => get_permalink(get_option('ielts_ms_account_page_id'))
        ));
    }
    
    /**
     * Handle registration AJAX request
     */
    public function handle_register() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
        }
        
        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => 'Passwords do not match'));
        }
        
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
        }
        
        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Username already exists'));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already registered'));
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        // Set user role to subscriber
        $user = new WP_User($user_id);
        $user->set_role('subscriber');
        
        // Log user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // Send welcome email
        wp_new_user_notification($user_id, null, 'user');
        
        wp_send_json_success(array(
            'message' => 'Registration successful',
            'redirect' => get_permalink(get_option('ielts_ms_account_page_id'))
        ));
    }
    
    /**
     * Handle forgot password AJAX request
     */
    public function handle_forgot_password() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $user_login = sanitize_text_field($_POST['user_login']);
        
        if (empty($user_login)) {
            wp_send_json_error(array('message' => 'Please enter your username or email'));
        }
        
        // Check if user exists
        if (strpos($user_login, '@')) {
            $user = get_user_by('email', $user_login);
        } else {
            $user = get_user_by('login', $user_login);
        }
        
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found'));
        }
        
        // Generate reset key
        $reset_key = get_password_reset_key($user);
        
        if (is_wp_error($reset_key)) {
            wp_send_json_error(array('message' => 'Failed to generate reset key'));
        }
        
        // Send reset email
        $reset_url = add_query_arg(array(
            'action' => 'reset_password',
            'key' => $reset_key,
            'login' => rawurlencode($user->user_login)
        ), get_permalink(get_option('ielts_ms_login_page_id')));
        
        $subject = 'Password Reset Request';
        $message = "You requested a password reset.\n\n";
        $message .= "Click the link below to reset your password:\n";
        $message .= $reset_url . "\n\n";
        $message .= "If you did not request this, please ignore this email.";
        
        $sent = wp_mail($user->user_email, $subject, $message);
        
        if (!$sent) {
            wp_send_json_error(array('message' => 'Failed to send reset email'));
        }
        
        wp_send_json_success(array('message' => 'Password reset link sent to your email'));
    }
    
    /**
     * Handle reset password AJAX request
     */
    public function handle_reset_password() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $key = sanitize_text_field($_POST['key']);
        $login = sanitize_text_field($_POST['login']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => 'Please enter your new password'));
        }
        
        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => 'Passwords do not match'));
        }
        
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
        }
        
        $user = check_password_reset_key($key, $login);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => 'Invalid or expired reset key'));
        }
        
        reset_password($user, $password);
        
        wp_send_json_success(array(
            'message' => 'Password reset successful',
            'redirect' => get_permalink(get_option('ielts_ms_login_page_id'))
        ));
    }
}
