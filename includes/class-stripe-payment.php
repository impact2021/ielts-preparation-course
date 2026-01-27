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
     * Ensure payment table exists in database
     * This handles cases where the plugin was updated but not reactivated
     */
    private function ensure_payment_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_payments';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if (!$table_exists) {
            error_log('IELTS Payment: Payments table does not exist, creating it now');
            
            // Create the table using the same SQL from class-database.php
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                membership_type varchar(50) NOT NULL,
                amount decimal(10,2) NOT NULL,
                transaction_id varchar(255) DEFAULT NULL,
                payment_status varchar(20) DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY payment_status (payment_status),
                KEY transaction_id (transaction_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('IELTS Payment: Payments table created successfully');
        }
    }
    
    /**
     * Verify IELTS_CM_Membership class is loaded
     * Returns true if available, sends error and returns false otherwise
     * 
     * @param string $context Context for error logging (e.g., 'confirm_payment', 'webhook', 'payment')
     * @return bool True if class exists, false otherwise
     */
    private function verify_membership_class($context = 'unknown') {
        if (!class_exists('IELTS_CM_Membership')) {
            error_log("IELTS Payment: CRITICAL - IELTS_CM_Membership class not found in $context");
            return false;
        }
        return true;
    }
    
    /**
     * Verify nonce for security
     * Logs and returns error if verification fails
     */
    private function verify_nonce($context = 'unknown') {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        if (empty($nonce) || !wp_verify_nonce($nonce, 'ielts_payment_intent')) {
            error_log('IELTS Payment: Nonce verification failed in ' . $context);
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'security_error',
                'Security check failed',
                array('context' => $context, 'action' => 'nonce_verification_failed')
            );
            
            wp_send_json_error('Security check failed. Please refresh the page and try again. If this error persists, contact support.', 403);
        }
    }
    
    /**
     * Register user account (called before payment)
     */
    public function register_user() {
        // Log the start of registration attempt
        error_log('IELTS Payment: register_user called');
        
        // Verify nonce
        $this->verify_nonce('register_user');
        
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        
        error_log("IELTS Payment: Received data - User ID: (new registration), Type: $membership_type");
        
        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            error_log('IELTS Payment: Missing required fields');
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'validation_error',
                'All fields are required',
                array('missing_fields' => true),
                null,
                $email
            );
            
            wp_send_json_error('All fields are required. Please fill in all registration fields.');
        }
        
        if (!is_email($email)) {
            error_log('IELTS Payment: Invalid email format');
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'validation_error',
                'Invalid email address',
                array('email' => $email),
                null,
                $email
            );
            
            wp_send_json_error('Invalid email address. Please enter a valid email.');
        }
        
        if (email_exists($email)) {
            error_log("IELTS Payment: Email already exists: $email");
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'validation_error',
                'Email already exists',
                array('email' => $email),
                null,
                $email
            );
            
            wp_send_json_error('Email already exists. Please use a different email or log in to your existing account.');
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
        $this->verify_nonce('create_payment_intent');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        error_log("IELTS Payment: create_payment_intent - User: $user_id, Membership type selected");
        
        // Validate user exists
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("IELTS Payment: Invalid user ID: $user_id");
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'validation_error',
                'Invalid user',
                array('user_id' => $user_id),
                $user_id
            );
            
            wp_send_json_error('Invalid user. Please refresh the page and try again.', 400);
        }
        
        // SECURITY: Validate membership type exists and get server-side price
        $pricing = get_option('ielts_cm_membership_pricing', array());
        if (!isset($pricing[$membership_type])) {
            error_log("IELTS Payment: Invalid membership type: $membership_type");
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'validation_error',
                'Invalid membership type',
                array('membership_type' => $membership_type),
                $user_id,
                $user->user_email
            );
            
            wp_send_json_error('Invalid membership type. Please refresh the page and try again.', 400);
        }
        
        $server_price = floatval($pricing[$membership_type]);
        
        // Verify amount matches server-side price
        if (abs($amount - $server_price) > 0.01) {
            error_log("IELTS Payment: Amount mismatch detected");
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'security_error',
                'Amount mismatch',
                array('client_amount' => $amount, 'server_price' => $server_price, 'membership_type' => $membership_type),
                $user_id,
                $user->user_email,
                $membership_type
            );
            
            wp_send_json_error('Amount mismatch detected. Please refresh the page and try again.', 400);
        }
        
        // Don't create payment intent for free memberships
        if ($amount <= 0) {
            error_log('IELTS Payment: Attempted to create payment intent for free membership');
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'validation_error',
                'Attempted to create payment intent for free membership',
                array('membership_type' => $membership_type),
                $user_id,
                $user->user_email,
                $membership_type,
                $amount
            );
            
            wp_send_json_error('This membership is free. No payment is required.', 400);
        }
        
        // Get Stripe secret key
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        if (empty($stripe_secret)) {
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'configuration_error',
                'Payment system not configured',
                array('missing' => 'stripe_secret_key'),
                $user_id,
                $user->user_email,
                $membership_type,
                $amount
            );
            
            wp_send_json_error('Payment system not configured. Please contact the site administrator.', 500);
        }
        
        $this->load_stripe();
        \Stripe\Stripe::setApiKey($stripe_secret);
        
        // Ensure payment table exists
        $this->ensure_payment_table_exists();
        
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
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'database_error',
                'Unable to process payment',
                array('error' => $wpdb->last_error, 'context' => 'insert_payment_record'),
                $user_id,
                $user->user_email,
                $membership_type,
                $amount
            );
            
            wp_send_json_error('Unable to process payment. Please try again or contact support. For assistance, please mention Error Code: DB001', 500);
        }
        
        $payment_id = $wpdb->insert_id;
        
        if (!$payment_id) {
            error_log('IELTS Payment: Failed to get payment ID after insert');
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'database_error',
                'Failed to get payment ID after insert',
                array('context' => 'insert_payment_record'),
                $user_id,
                $user->user_email,
                $membership_type,
                $amount
            );
            
            wp_send_json_error('Unable to process payment. Please try again or contact support. For assistance, please mention Error Code: DB002', 500);
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
            
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'stripe_api_error',
                'Stripe API error',
                array(
                    'message' => $e->getMessage(),
                    'code' => method_exists($e, 'getCode') ? $e->getCode() : null,
                    'type' => get_class($e)
                ),
                $user_id,
                $user->user_email,
                $membership_type,
                $amount
            );
            
            wp_send_json_error('Payment system error: ' . $e->getMessage() . ' For assistance, please mention Error Code: STRIPE001', 500);
        }
    }
    
    /**
     * Confirm payment and activate membership
     */
    public function confirm_payment() {
        // Verify nonce
        $this->verify_nonce('confirm_payment');
        
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
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'validation_error',
                'Payment not found',
                array('payment_id' => $payment_id, 'payment_intent_id' => $payment_intent_id)
            );
            
            wp_send_json_error('Payment not found. Please try again or contact support. For assistance, please mention Error Code: PAY001', 404);
        }
        
        // Get user email for logging
        $user = get_userdata($payment->user_id);
        $user_email = $user ? $user->user_email : null;
        
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
        
        // Verify IELTS_CM_Membership class is available
        if (!$this->verify_membership_class('confirm_payment')) {
            // Log to database
            IELTS_CM_Database::log_payment_error(
                'system_error',
                'Membership handler not loaded',
                array('payment_id' => $payment_id, 'user_id' => $payment->user_id),
                $payment->user_id,
                $user_email,
                $payment->membership_type,
                $payment->amount
            );
            
            wp_send_json_error('System error: Membership handler not loaded. Please contact administrator. For assistance, please mention Error Code: SYS001', 500);
            return;
        }
        
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
        
        // Verify IELTS_CM_Membership class is available
        if (!$this->verify_membership_class('webhook')) {
            return array('success' => false, 'error' => 'Membership handler not loaded');
        }
        
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
