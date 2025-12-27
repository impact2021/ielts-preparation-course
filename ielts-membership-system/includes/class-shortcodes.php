<?php
/**
 * Shortcodes for membership system
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_MS_Shortcodes {
    
    public function register() {
        add_shortcode('ielts_membership_login', array($this, 'login_form'));
        add_shortcode('ielts_membership_register', array($this, 'register_form'));
        add_shortcode('ielts_membership_account', array($this, 'account_page'));
    }
    
    /**
     * Login form shortcode
     */
    public function login_form($atts) {
        if (is_user_logged_in()) {
            $account_page_id = get_option('ielts_ms_account_page_id');
            if ($account_page_id) {
                wp_redirect(get_permalink($account_page_id));
                exit;
            }
        }
        
        // Check for reset password action
        $action = isset($_GET['action']) ? $_GET['action'] : 'login';
        
        ob_start();
        include IELTS_MS_PLUGIN_DIR . 'templates/login-form.php';
        return ob_get_clean();
    }
    
    /**
     * Register form shortcode
     */
    public function register_form($atts) {
        if (is_user_logged_in()) {
            $account_page_id = get_option('ielts_ms_account_page_id');
            if ($account_page_id) {
                wp_redirect(get_permalink($account_page_id));
                exit;
            }
        }
        
        ob_start();
        include IELTS_MS_PLUGIN_DIR . 'templates/register-form.php';
        return ob_get_clean();
    }
    
    /**
     * Account page shortcode
     */
    public function account_page($atts) {
        if (!is_user_logged_in()) {
            $login_page_id = get_option('ielts_ms_login_page_id');
            if ($login_page_id) {
                wp_redirect(get_permalink($login_page_id));
                exit;
            }
            return '<p>Please log in to view your account.</p>';
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $membership = new IELTS_MS_Membership();
        $user_membership = $membership->get_user_membership($user_id);
        
        // Get payment history
        global $wpdb;
        $payments_table = IELTS_MS_Database::get_payments_table();
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $payments_table WHERE user_id = %d ORDER BY payment_date DESC LIMIT 10",
            $user_id
        ));
        
        ob_start();
        include IELTS_MS_PLUGIN_DIR . 'templates/account-page.php';
        return ob_get_clean();
    }
}
