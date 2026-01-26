<?php
/**
 * Stripe Payment Processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Stripe_Payment {
    
    public function init() {
        // AJAX endpoint for creating user account
        add_action('wp_ajax_nopriv_ielts_register_user', array($this, 'register_user'));
        add_action('wp_ajax_ielts_register_user', array($this, 'register_user'));
        
        // AJAX endpoint for creating payment intent
        add_action('wp_ajax_nopriv_ielts_create_payment_intent', array($this, 'create_payment_intent'));
        add_action('wp_ajax_ielts_create_payment_intent', array($this, 'create_payment_intent'));
        
        // AJAX endpoint for confirming payment
        add_action('wp_ajax_nopriv_ielts_confirm_payment', array($this, 'confirm_payment'));
        add_action('wp_ajax_ielts_confirm_payment', array($this, 'confirm_payment'));
        
        // Webhook handler for payment confirmation
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }
    
    /**
     * Load Stripe library
     */
    private function load_stripe() {
        if (!class_exists('\Stripe\Stripe')) {
            require_once IELTS_CM_PLUGIN_DIR . 'vendor/autoload.php';
        }
    }
    
    /**
     * Register user account (called before payment)
     */
    public function register_user() {
        // Log the start of registration attempt
        error_log('IELTS Payment: register_user called');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_payment_intent')) {
            error_log('IELTS Payment: Nonce verification failed');
            wp_send_json_error('Security check failed', 403);
        }
        
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        
        error_log("IELTS Payment: Received data - User ID: (new registration), Type: $membership_type");
        
        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            error_log('IELTS Payment: Missing required fields');
            wp_send_json_error('All fields are required');
        }
        
        if (!is_email($email)) {
            error_log('IELTS Payment: Invalid email format');
            wp_send_json_error('Invalid email address');
        }
        
        if (email_exists($email)) {
            error_log("IELTS Payment: Email already exists: $email");
            wp_send_json_error('Email already exists');
        }
        
        // Generate username from email
        $email_parts = explode('@', $email);
        $base_username = sanitize_user($email_parts[0], true);
        $username = $base_username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Create user account
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        ));
        
        // Store membership type temporarily (will be activated after payment)
        update_user_meta($user_id, '_ielts_cm_pending_membership_type', $membership_type);
        update_user_meta($user_id, '_ielts_cm_registration_pending', true);
        
        wp_send_json_success(array(
            'user_id' => $user_id
        ));
    }
    
    /**
     * Create Payment Intent for registration
     */
    public function create_payment_intent() {
        error_log('IELTS Payment: create_payment_intent called');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_payment_intent')) {
            error_log('IELTS Payment: Nonce verification failed in create_payment_intent');
            wp_send_json_error('Security check failed', 403);
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        error_log("IELTS Payment: create_payment_intent - User: $user_id, Membership type selected");
        
        // Validate user exists
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("IELTS Payment: Invalid user ID: $user_id");
            wp_send_json_error('Invalid user', 400);
        }
        
        // SECURITY: Validate membership type exists and get server-side price
        $pricing = get_option('ielts_cm_membership_pricing', array());
        if (!isset($pricing[$membership_type])) {
            error_log("IELTS Payment: Invalid membership type: $membership_type");
            wp_send_json_error('Invalid membership type', 400);
        }
        
        $server_price = floatval($pricing[$membership_type]);
        
        // Verify amount matches server-side price
        if (abs($amount - $server_price) > 0.01) {
            error_log("IELTS Payment: Amount mismatch detected");
            wp_send_json_error('Amount mismatch', 400);
        }
        
        // Don't create payment intent for free memberships
        if ($amount <= 0) {
            error_log('IELTS Payment: Attempted to create payment intent for free membership');
            wp_send_json_error('This membership is free', 400);
        }
        
        // Get Stripe secret key
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        if (empty($stripe_secret)) {
            wp_send_json_error('Payment system not configured', 500);
        }
        
        $this->load_stripe();
        \Stripe\Stripe::setApiKey($stripe_secret);
        
        // Create payment record in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_payments';
        
        $insert_result = $wpdb->insert($table_name, array(
            'user_id' => $user_id,
            'membership_type' => $membership_type,
            'amount' => $amount,
            'payment_status' => 'pending',
            'created_at' => current_time('mysql')
        ));
        
        if ($insert_result === false) {
            // Log detailed error internally but return generic message
            error_log('IELTS Payment: Database error creating payment record - ' . $wpdb->last_error);
            wp_send_json_error('Unable to process payment. Please try again or contact support.', 500);
        }
        
        $payment_id = $wpdb->insert_id;
        
        if (!$payment_id) {
            error_log('IELTS Payment: Failed to get payment ID after insert');
            wp_send_json_error('Unable to process payment. Please try again or contact support.', 500);
        }
        
        try {
            // Create Payment Intent
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => intval($amount * 100), // Convert to cents
                'currency' => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'user_id' => $user_id,
                    'membership_type' => $membership_type,
                    'payment_id' => $payment_id,
                    'is_registration' => 'true',
                ],
            ]);
            
            wp_send_json_success([
                'clientSecret' => $payment_intent->client_secret,
                'payment_id' => $payment_id,
            ]);
            
        } catch (\Exception $e) {
            error_log('Stripe Payment Intent Error: ' . $e->getMessage());
            wp_send_json_error('Payment system error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Confirm payment and activate membership
     */
    public function confirm_payment() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_payment_intent')) {
            wp_send_json_error('Security check failed', 403);
        }
        
        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);
        $payment_id = intval($_POST['payment_id']);
        
        // Get payment from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_payments';
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $payment_id
        ));
        
        if (!$payment) {
            wp_send_json_error('Payment not found', 404);
        }
        
        // Update payment status
        $wpdb->update(
            $table_name,
            array(
                'payment_status' => 'completed',
                'transaction_id' => $payment_intent_id
            ),
            array('id' => $payment_id)
        );
        
        // Assign paid membership to user
        update_user_meta($payment->user_id, '_ielts_cm_membership_type', $payment->membership_type);
        
        // Set status to active (this also assigns the WordPress role)
        $membership = new IELTS_CM_Membership();
        $membership->set_user_membership_status($payment->user_id, IELTS_CM_Membership::STATUS_ACTIVE);
        
        // Clear expiry email tracking when activating new membership
        delete_user_meta($payment->user_id, '_ielts_cm_expiry_email_sent');
        
        // Set expiry date
        $expiry_date = IELTS_CM_Membership::calculate_expiry_date($payment->membership_type);
        update_user_meta($payment->user_id, '_ielts_cm_membership_expiry', $expiry_date);
        
        // Store payment info
        update_user_meta($payment->user_id, '_ielts_cm_payment_intent_id', $payment_intent_id);
        update_user_meta($payment->user_id, '_ielts_cm_payment_amount', $payment->amount);
        update_user_meta($payment->user_id, '_ielts_cm_payment_date', current_time('mysql'));
        
        // Clean up registration pending flags
        delete_user_meta($payment->user_id, '_ielts_cm_pending_membership_type');
        delete_user_meta($payment->user_id, '_ielts_cm_registration_pending');
        
        // Send welcome email
        wp_new_user_notification($payment->user_id, null, 'user');
        
        // Get login page URL for redirect
        $login_url = wp_login_url();
        if (get_option('ielts_cm_membership_enabled')) {
            $login_page_id = get_option('ielts_cm_login_page_id');
            if ($login_page_id) {
                $login_url = get_permalink($login_page_id);
            }
        }
        
        wp_send_json_success(array(
            'redirect' => $login_url
        ));
    }
    
    /**
     * Register Stripe webhook endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('ielts-cm/v1', '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Webhook has signature verification
        ));
    }
    
    /**
     * Handle Stripe webhook events
     */
    public function handle_webhook($request) {
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        
        $this->load_stripe();
        \Stripe\Stripe::setApiKey($stripe_secret);
        
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');
        
        // Get webhook signing secret from settings
        $webhook_secret = get_option('ielts_cm_stripe_webhook_secret', '');
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhook_secret
            );
        } catch (\Exception $e) {
            error_log('Webhook signature verification failed: ' . $e->getMessage());
            return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 400));
        }
        
        // Handle the event
        if ($event->type === 'payment_intent.succeeded') {
            $payment_intent = $event->data->object;
            $this->handle_successful_payment($payment_intent);
        }
        
        return new WP_REST_Response(['status' => 'success'], 200);
    }
    
    /**
     * Create user and assign membership after successful payment
     */
    private function handle_successful_payment($payment_intent) {
        $metadata = $payment_intent->metadata;
        
        // Extract user data from metadata
        $email = $metadata->email;
        $first_name = $metadata->first_name;
        $last_name = $metadata->last_name;
        $membership_type = $metadata->membership_type;
        
        // Check if user already exists (idempotency)
        if (email_exists($email)) {
            error_log("User already exists for email: $email");
            return;
        }
        
        // Generate username from email
        $email_parts = explode('@', $email);
        $base_username = sanitize_user($email_parts[0], true);
        $username = $base_username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Create user account
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        
        if (is_wp_error($user_id)) {
            error_log('Failed to create user: ' . $user_id->get_error_message());
            return;
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        ));
        
        // Assign paid membership
        update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
        
        // Set status to active (this also assigns the WordPress role)
        $membership = new IELTS_CM_Membership();
        $membership->set_user_membership_status($user_id, IELTS_CM_Membership::STATUS_ACTIVE);
        
        // Clear expiry email tracking when activating new membership
        delete_user_meta($user_id, '_ielts_cm_expiry_email_sent');
        
        // Set expiry date
        $expiry_date = IELTS_CM_Membership::calculate_expiry_date($membership_type);
        update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
        
        // Store payment info
        update_user_meta($user_id, '_ielts_cm_payment_intent_id', $payment_intent->id);
        update_user_meta($user_id, '_ielts_cm_payment_amount', $payment_intent->amount / 100);
        update_user_meta($user_id, '_ielts_cm_payment_date', current_time('mysql'));
        
        // Send welcome email
        wp_new_user_notification($user_id, null, 'user');
        
        error_log("Successfully created user $user_id with membership $membership_type after payment");
    }
}
