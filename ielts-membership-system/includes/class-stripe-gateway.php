<?php
/**
 * Stripe payment gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_MS_Stripe_Gateway extends IELTS_MS_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'stripe';
        $this->title = 'Stripe';
        $this->enabled = get_option('ielts_ms_stripe_enabled', true);
        
        // Register Stripe webhook handler
        add_action('wp_ajax_nopriv_ielts_ms_stripe_webhook', array($this, 'handle_callback'));
        add_action('wp_ajax_ielts_ms_stripe_webhook', array($this, 'handle_callback'));
        
        // Register AJAX handler for creating checkout session
        add_action('wp_ajax_ielts_ms_create_stripe_session', array($this, 'create_checkout_session'));
        add_action('wp_ajax_nopriv_ielts_ms_create_stripe_session', array($this, 'create_checkout_session'));
    }
    
    /**
     * Process payment (create Stripe checkout session)
     */
    public function process_payment($amount, $user_id, $duration_days, $payment_type = 'new') {
        if (!$this->enabled) {
            return array('success' => false, 'message' => 'Stripe is not enabled');
        }
        
        $secret_key = get_option('ielts_ms_stripe_secret_key', '');
        
        if (empty($secret_key)) {
            return array('success' => false, 'message' => 'Stripe is not configured');
        }
        
        // This will be handled via AJAX to create a checkout session
        return array(
            'success' => true,
            'use_ajax' => true,
            'gateway' => 'stripe'
        );
    }
    
    /**
     * Create Stripe checkout session (AJAX handler)
     */
    public function create_checkout_session() {
        check_ajax_referer('ielts_ms_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $amount = floatval($_POST['amount']);
        $duration_days = intval($_POST['duration_days']);
        $payment_type = sanitize_text_field($_POST['payment_type']);
        $plan_key = sanitize_text_field($_POST['plan_key']);
        
        $secret_key = get_option('ielts_ms_stripe_secret_key', '');
        
        if (empty($secret_key)) {
            wp_send_json_error(array('message' => 'Stripe is not configured'));
        }
        
        // Create pending payment record
        $payment_id = $this->record_payment($user_id, $amount, $duration_days, null, 'pending', $payment_type);
        
        // Success and cancel URLs
        $success_url = add_query_arg(array(
            'payment_status' => 'success',
            'gateway' => 'stripe',
            'session_id' => '{CHECKOUT_SESSION_ID}'
        ), get_permalink(get_option('ielts_ms_account_page_id')));
        
        $cancel_url = add_query_arg(array(
            'payment_status' => 'cancelled',
            'gateway' => 'stripe'
        ), get_permalink(get_option('ielts_ms_account_page_id')));
        
        // Prepare Stripe API request
        $pricing_options = self::get_pricing_options();
        $plan = isset($pricing_options[$plan_key]) ? $pricing_options[$plan_key] : null;
        
        if (!$plan) {
            wp_send_json_error(array('message' => 'Invalid plan'));
        }
        
        $stripe_data = array(
            'payment_method_types' => array('card'),
            'line_items' => array(
                array(
                    'price_data' => array(
                        'currency' => 'usd',
                        'product_data' => array(
                            'name' => $plan['label']
                        ),
                        'unit_amount' => intval($amount * 100) // Convert to cents
                    ),
                    'quantity' => 1
                )
            ),
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'client_reference_id' => $payment_id,
            'customer_email' => get_userdata($user_id)->user_email,
            'metadata' => array(
                'user_id' => $user_id,
                'duration_days' => $duration_days,
                'payment_type' => $payment_type,
                'payment_id' => $payment_id
            )
        );
        
        // Make API request to Stripe
        $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($stripe_data),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to create checkout session'));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id'])) {
            wp_send_json_success(array(
                'session_id' => $body['id'],
                'url' => $body['url']
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to create checkout session'));
        }
    }
    
    /**
     * Handle Stripe webhook
     */
    public function handle_callback() {
        $payload = @file_get_contents('php://input');
        $event = json_decode($payload, true);
        
        if (!$event) {
            status_header(400);
            exit;
        }
        
        // Verify webhook signature (optional but recommended)
        $webhook_secret = get_option('ielts_ms_stripe_webhook_secret', '');
        if (!empty($webhook_secret)) {
            $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
            
            // In production, verify the signature here
            // For now, we'll just process the event
        }
        
        // Handle the event
        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];
            
            $payment_id = isset($session['client_reference_id']) ? $session['client_reference_id'] : null;
            $user_id = isset($session['metadata']['user_id']) ? intval($session['metadata']['user_id']) : 0;
            $duration_days = isset($session['metadata']['duration_days']) ? intval($session['metadata']['duration_days']) : 0;
            $payment_type = isset($session['metadata']['payment_type']) ? $session['metadata']['payment_type'] : 'new';
            
            if ($user_id && $duration_days) {
                // Update payment record
                global $wpdb;
                $table = IELTS_MS_Database::get_payments_table();
                
                $wpdb->update(
                    $table,
                    array(
                        'payment_status' => 'completed',
                        'transaction_id' => $session['id']
                    ),
                    array('id' => $payment_id),
                    array('%s', '%s'),
                    array('%d')
                );
                
                // Create/extend membership
                $membership = new IELTS_MS_Membership();
                $membership->create_membership($user_id, $duration_days, $payment_id);
            }
        }
        
        status_header(200);
        exit;
    }
}
