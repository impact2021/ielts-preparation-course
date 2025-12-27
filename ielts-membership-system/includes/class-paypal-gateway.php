<?php
/**
 * PayPal payment gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_MS_PayPal_Gateway extends IELTS_MS_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'paypal';
        $this->title = 'PayPal';
        $this->enabled = get_option('ielts_ms_paypal_enabled', true);
        
        // Register PayPal IPN handler
        add_action('wp_ajax_nopriv_ielts_ms_paypal_ipn', array($this, 'handle_callback'));
        add_action('wp_ajax_ielts_ms_paypal_ipn', array($this, 'handle_callback'));
        
        // Register AJAX handler for processing payment
        add_action('wp_ajax_ielts_ms_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_ielts_ms_process_payment', array($this, 'ajax_process_payment'));
    }
    
    /**
     * AJAX handler for processing payment
     */
    public function ajax_process_payment() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $gateway = sanitize_text_field($_POST['gateway']);
        
        if ($gateway !== 'paypal') {
            return; // Not for this gateway
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $amount = floatval($_POST['amount']);
        $duration_days = intval($_POST['duration_days']);
        $payment_type = sanitize_text_field($_POST['payment_type']);
        
        $result = $this->process_payment($amount, $user_id, $duration_days, $payment_type);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Process payment
     */
    public function process_payment($amount, $user_id, $duration_days, $payment_type = 'new') {
        if (!$this->enabled) {
            return array('success' => false, 'message' => 'PayPal is not enabled');
        }
        
        $paypal_email = get_option('ielts_ms_paypal_email', '');
        $sandbox = get_option('ielts_ms_paypal_sandbox', false);
        
        if (empty($paypal_email)) {
            return array('success' => false, 'message' => 'PayPal is not configured');
        }
        
        $user = get_userdata($user_id);
        
        // PayPal URL
        $paypal_url = $sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        
        // Return and notify URLs
        $return_url = add_query_arg(array(
            'payment_status' => 'success',
            'gateway' => 'paypal'
        ), get_permalink(get_option('ielts_ms_account_page_id')));
        
        $cancel_url = add_query_arg(array(
            'payment_status' => 'cancelled',
            'gateway' => 'paypal'
        ), get_permalink(get_option('ielts_ms_account_page_id')));
        
        $notify_url = admin_url('admin-ajax.php?action=ielts_ms_paypal_ipn');
        
        // Create pending payment record
        $payment_id = $this->record_payment($user_id, $amount, $duration_days, null, 'pending', $payment_type);
        
        // Build PayPal form
        $paypal_args = array(
            'cmd' => '_xclick',
            'business' => $paypal_email,
            'item_name' => 'IELTS Course Membership - ' . $duration_days . ' days',
            'item_number' => $payment_id,
            'amount' => number_format($amount, 2, '.', ''),
            'currency_code' => 'USD',
            'custom' => $user_id . '|' . $duration_days . '|' . $payment_type,
            'return' => $return_url,
            'cancel_return' => $cancel_url,
            'notify_url' => $notify_url,
            'no_shipping' => '1',
            'no_note' => '1',
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        );
        
        return array(
            'success' => true,
            'redirect_url' => $paypal_url,
            'form_data' => $paypal_args,
            'payment_id' => $payment_id
        );
    }
    
    /**
     * Handle PayPal IPN
     */
    public function handle_callback() {
        // Read POST data
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $paypal_data = array();
        
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                $paypal_data[$keyval[0]] = urldecode($keyval[1]);
            }
        }
        
        // Verify with PayPal
        $sandbox = get_option('ielts_ms_paypal_sandbox', false);
        $paypal_url = $sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        
        $req = 'cmd=_notify-validate';
        foreach ($paypal_data as $key => $value) {
            $req .= '&' . $key . '=' . urlencode($value);
        }
        
        $response = wp_remote_post($paypal_url, array(
            'body' => $req,
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            status_header(500);
            exit;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if ($body === 'VERIFIED') {
            // Process the payment
            $payment_status = isset($paypal_data['payment_status']) ? $paypal_data['payment_status'] : '';
            $txn_id = isset($paypal_data['txn_id']) ? $paypal_data['txn_id'] : '';
            $custom = isset($paypal_data['custom']) ? $paypal_data['custom'] : '';
            $amount = isset($paypal_data['mc_gross']) ? floatval($paypal_data['mc_gross']) : 0;
            
            // Parse custom data
            $custom_parts = explode('|', $custom);
            if (count($custom_parts) >= 2) {
                $user_id = intval($custom_parts[0]);
                $duration_days = intval($custom_parts[1]);
                $payment_type = isset($custom_parts[2]) ? $custom_parts[2] : 'new';
                
                if ($payment_status === 'Completed') {
                    // Check if transaction already processed
                    $existing = $this->get_payment_by_transaction($txn_id);
                    if (!$existing) {
                        // Record payment
                        $payment_id = $this->record_payment($user_id, $amount, $duration_days, $txn_id, 'completed', $payment_type);
                        
                        // Create/extend membership
                        $membership = new IELTS_MS_Membership();
                        $membership->create_membership($user_id, $duration_days, $payment_id);
                    }
                }
            }
        }
        
        status_header(200);
        exit;
    }
}
