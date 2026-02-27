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
        
        // AJAX endpoint for course extension payment intent
        add_action('wp_ajax_ielts_cm_create_extension_payment_intent', array($this, 'create_extension_payment_intent'));
        
        // AJAX endpoint for code purchase payment intent
        add_action('wp_ajax_ielts_cm_create_code_purchase_payment_intent', array($this, 'create_code_purchase_payment_intent'));
        
        // AJAX endpoint for checking payment status and completing purchase (webhook fallback)
        add_action('wp_ajax_ielts_cm_check_payment_status', array($this, 'check_payment_status'));
        
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
     * Ensure access codes table exists in database
     * This handles cases where the plugin was updated but not reactivated
     * Critical fix: Prevents silent failure when Stripe payment succeeds but codes can't be created
     */
    private function ensure_access_codes_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_access_codes';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if (!$table_exists) {
            error_log('IELTS Access Codes: Access codes table does not exist, creating it now');
            
            // Create the table using the same SQL from class-database.php
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                code varchar(50) NOT NULL,
                course_group varchar(50) NOT NULL,
                duration_days int(11) NOT NULL DEFAULT 30,
                created_by bigint(20) NOT NULL,
                created_date datetime DEFAULT CURRENT_TIMESTAMP,
                status varchar(20) DEFAULT 'active',
                used_by bigint(20) DEFAULT NULL,
                used_date datetime DEFAULT NULL,
                expiry_date datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY code (code),
                KEY created_by (created_by),
                KEY status (status),
                KEY used_by (used_by)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('IELTS Access Codes: Access codes table created successfully');
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
     * Safely log payment error to database
     * Wraps the logging in try-catch to prevent logging failures from breaking AJAX responses
     * 
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $error_details Additional details
     * @param int|null $user_id User ID
     * @param string|null $user_email User email
     * @param string|null $membership_type Membership type
     * @param float|null $amount Amount
     */
    private function safe_log_payment_error($error_type, $error_message, $error_details = array(), $user_id = null, $user_email = null, $membership_type = null, $amount = null) {
        // Cache the class/method check for performance
        static $logging_available = null;
        
        if ($logging_available === null) {
            $logging_available = class_exists('IELTS_CM_Database') && method_exists('IELTS_CM_Database', 'log_payment_error');
        }
        
        try {
            if ($logging_available) {
                IELTS_CM_Database::log_payment_error($error_type, $error_message, $error_details, $user_id, $user_email, $membership_type, $amount);
            } else {
                error_log("IELTS Payment: Cannot log error to database - IELTS_CM_Database class or log_payment_error method not found");
                error_log("IELTS Payment Error: [$error_type] $error_message - Details: " . wp_json_encode($error_details));
            }
        } catch (Throwable $e) {
            // Catch both Exception and Error to handle all possible failures
            // If logging fails, log to error_log and continue
            error_log("IELTS Payment: Failed to log error to database - " . $e->getMessage());
            error_log("IELTS Payment Error: [$error_type] $error_message - Details: " . wp_json_encode($error_details));
        }
    }
    
    /**
     * Verify nonce for security
     * Logs and returns error if verification fails
     */
    private function verify_nonce($context = 'unknown') {
        $nonce = isset($_POST['nonce']) ? sanitize_key($_POST['nonce']) : '';
        
        if (empty($nonce) || !wp_verify_nonce($nonce, 'ielts_payment_intent')) {
            error_log('IELTS Payment: Nonce verification failed in ' . $context);
            
            // Log to database
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
                'validation_error',
                'Email already exists',
                array('email' => $email),
                null,
                $email
            );
            
            wp_send_json_error('Email already exists. Please use a different email or log in to your existing account.');
        }
        
        // Validate membership type is required and valid
        if (empty($membership_type)) {
            error_log('IELTS Payment: Missing membership type');
            
            // Log to database
            $this->safe_log_payment_error(
                'validation_error',
                'Membership type is required',
                array('email' => $email),
                null,
                $email
            );
            
            wp_send_json_error('Membership type is required. Please select a membership option.');
        }
        
        // Validate that the membership type is valid
        if (!class_exists('IELTS_CM_Membership')) {
            error_log('IELTS Payment: CRITICAL - IELTS_CM_Membership class not found during registration');
            
            // Log to database
            $this->safe_log_payment_error(
                'system_error',
                'Membership class not available',
                array('email' => $email, 'membership_type' => $membership_type),
                null,
                $email
            );
            
            wp_send_json_error('System error: Membership system not available. Please contact support.');
        }
        
        $valid_types = IELTS_CM_Membership::get_valid_membership_types();
        if (!in_array($membership_type, $valid_types)) {
            error_log("IELTS Payment: Invalid membership type: $membership_type");
            
            // Log to database
            $this->safe_log_payment_error(
                'validation_error',
                'Invalid membership type',
                array('email' => $email, 'membership_type' => $membership_type),
                null,
                $email
            );
            
            wp_send_json_error('Invalid membership type selected. Please select a valid membership option.');
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
        
        // Suppress automatic new user notification - we'll send it after payment succeeds
        add_filter('wp_send_new_user_notifications', '__return_false');
        
        // Create user account
        // Mark this as authorized registration
        if (!defined('IELTS_CM_AUTHORIZED_REGISTRATION')) {
            define('IELTS_CM_AUTHORIZED_REGISTRATION', true);
        }
        $user_id = wp_create_user($username, $password, $email);
        
        // Re-enable new user notifications
        remove_filter('wp_send_new_user_notifications', '__return_false');
        
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
            $this->safe_log_payment_error(
                'validation_error',
                'Invalid user',
                array('user_id' => $user_id),
                $user_id
            );
            
            wp_send_json_error('Invalid user. Please refresh the page and try again.', 400);
        }
        
        // SECURITY: Validate membership type exists and get server-side price
        $pricing = get_option('ielts_cm_membership_pricing', array());
        
        // Check if it's an extension type
        $is_extension = in_array($membership_type, array('extension_1_week', 'extension_1_month', 'extension_3_months'));
        
        if ($is_extension) {
            // Get extension pricing
            $extension_pricing = get_option('ielts_cm_extension_pricing', array(
                '1_week' => 5.00,
                '1_month' => 10.00,
                '3_months' => 15.00
            ));
            
            // Map extension type to pricing key
            $extension_key_map = array(
                'extension_1_week' => '1_week',
                'extension_1_month' => '1_month',
                'extension_3_months' => '3_months'
            );
            
            $extension_key = $extension_key_map[$membership_type];
            
            if (!isset($extension_pricing[$extension_key])) {
                error_log("IELTS Payment: Invalid extension type: $membership_type");
                
                // Log to database
                $this->safe_log_payment_error(
                    'validation_error',
                    'Invalid extension type',
                    array('membership_type' => $membership_type),
                    $user_id,
                    $user->user_email
                );
                
                wp_send_json_error('Invalid extension type. Please refresh the page and try again.', 400);
            }
            
            $server_price = floatval($extension_pricing[$extension_key]);
        } else {
            // Regular membership type
            if (!isset($pricing[$membership_type])) {
                error_log("IELTS Payment: Invalid membership type: $membership_type");
                
                // Log to database
                $this->safe_log_payment_error(
                    'validation_error',
                    'Invalid membership type',
                    array('membership_type' => $membership_type),
                    $user_id,
                    $user->user_email
                );
                
                wp_send_json_error('Invalid membership type. Please refresh the page and try again.', 400);
            }
            
            $server_price = floatval($pricing[$membership_type]);
        }
        
        // Verify amount matches server-side price
        if (abs($amount - $server_price) > 0.01) {
            error_log("IELTS Payment: Amount mismatch detected");
            
            // Log to database
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
            $this->safe_log_payment_error(
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
        
        try {
            // Set status to active (this also assigns the WordPress role)
            $membership = new IELTS_CM_Membership();
            $membership->set_user_membership_status($payment->user_id, IELTS_CM_Membership::STATUS_ACTIVE);
            
            // Clear expiry email tracking when activating new membership
            delete_user_meta($payment->user_id, '_ielts_cm_expiry_email_sent');
            
            // Set expiry date
            $expiry_date = $membership->calculate_expiry_date($payment->membership_type);
            update_user_meta($payment->user_id, '_ielts_cm_membership_expiry', $expiry_date);
            
            // Store payment info
            update_user_meta($payment->user_id, '_ielts_cm_payment_intent_id', $payment_intent_id);
            update_user_meta($payment->user_id, '_ielts_cm_payment_amount', $payment->amount);
            update_user_meta($payment->user_id, '_ielts_cm_payment_date', current_time('mysql'));
            
            // Clean up registration pending flags
            delete_user_meta($payment->user_id, '_ielts_cm_pending_membership_type');
            delete_user_meta($payment->user_id, '_ielts_cm_registration_pending');
            
            // Send welcome email after successful payment
            // Note: We suppressed the automatic email during user creation
            // so we send it now after payment is confirmed
            try {
                // Send notification to both admin and user
                wp_new_user_notification($payment->user_id, null, 'both');
                error_log("IELTS Payment: Welcome email sent successfully for user {$payment->user_id}");
            } catch (Throwable $e) {
                error_log('IELTS Payment: Failed to send welcome email - ' . $e->getMessage());
                // Log the error but don't fail the payment since membership is already activated
                $this->safe_log_payment_error(
                    'email_error',
                    'Failed to send welcome email',
                    array('error' => $e->getMessage()),
                    $payment->user_id,
                    $user_email,
                    $payment->membership_type,
                    $payment->amount
                );
            }
            
        } catch (Throwable $e) {
            error_log('IELTS Payment: Error activating membership - ' . $e->getMessage());
            
            // Log to database
            $this->safe_log_payment_error(
                'system_error',
                'Failed to activate membership',
                array(
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'payment_id' => $payment_id
                ),
                $payment->user_id,
                $user_email,
                $payment->membership_type,
                $payment->amount
            );
            
            wp_send_json_error('Failed to activate membership. Please contact support with Error Code: ACT001', 500);
            return;
        }
        
        // Auto-login the user after successful payment
        wp_set_auth_cookie($payment->user_id, true);
        wp_set_current_user($payment->user_id);
        do_action('wp_login', $user ? $user->user_login : '', $user);
        
        // Get post account creation redirect URL (applies to both free and paid accounts)
        $redirect_url = get_option('ielts_cm_post_payment_redirect_url', '');
        
        // If no custom redirect is set, use admin dashboard or home page
        if (empty($redirect_url)) {
            // Check if user can access admin area
            if (current_user_can('read')) {
                $redirect_url = admin_url();
            } else {
                $redirect_url = home_url();
            }
        }
        
        wp_send_json_success(array(
            'redirect' => $redirect_url
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
        error_log('IELTS Stripe Webhook: Received webhook request');
        
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        
        $this->load_stripe();
        \Stripe\Stripe::setApiKey($stripe_secret);
        
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');
        $sig_method = 'get_header';
        $all_headers = null; // Cache for header retrieval
        
        // Comprehensive fallback chain for retrieving Stripe-Signature header
        // Different server configurations may require different methods
        if (empty($sig_header)) {
            // Method 2: Direct $_SERVER access (works on most PHP-FPM/FastCGI setups)
            if (isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
                $sig_header = sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE']));
                $sig_method = '$_SERVER';
            }
            // Method 3: getallheaders() function (available on most modern PHP setups)
            elseif (function_exists('getallheaders')) {
                $all_headers = getallheaders();
                if (isset($all_headers['Stripe-Signature'])) {
                    $sig_header = sanitize_text_field($all_headers['Stripe-Signature']);
                    $sig_method = 'getallheaders';
                } elseif (isset($all_headers['stripe-signature'])) {
                    // Try lowercase version
                    $sig_header = sanitize_text_field($all_headers['stripe-signature']);
                    $sig_method = 'getallheaders (lowercase)';
                }
            }
            // Method 4: apache_request_headers() as final fallback (alias of getallheaders in some setups)
            elseif (function_exists('apache_request_headers')) {
                $all_headers = apache_request_headers();
                if (isset($all_headers['Stripe-Signature'])) {
                    $sig_header = sanitize_text_field($all_headers['Stripe-Signature']);
                    $sig_method = 'apache_request_headers';
                } elseif (isset($all_headers['stripe-signature'])) {
                    // Try lowercase version
                    $sig_header = sanitize_text_field($all_headers['stripe-signature']);
                    $sig_method = 'apache_request_headers (lowercase)';
                }
            }
        }
        
        if (!empty($sig_header)) {
            error_log('IELTS Stripe Webhook: Retrieved signature using method: ' . $sig_method);
        } else {
            error_log('IELTS Stripe Webhook: ERROR - Signature header NOT FOUND with any method');
            // Log available headers for debugging (only keys, not values for security)
            // Reuse cached headers if available from fallback attempt
            if ($all_headers === null && function_exists('getallheaders')) {
                $all_headers = getallheaders();
            }
            if ($all_headers !== null) {
                $header_keys = array_keys($all_headers);
                error_log('IELTS Stripe Webhook: Available headers: ' . implode(', ', $header_keys));
            } elseif (isset($_SERVER)) {
                $http_headers = array_keys(array_filter($_SERVER, function($key) {
                    return strpos($key, 'HTTP_') === 0;
                }, ARRAY_FILTER_USE_KEY));
                error_log('IELTS Stripe Webhook: Available HTTP_* vars: ' . implode(', ', $http_headers));
            }
        }
        
        // Get webhook signing secret from settings
        $webhook_secret = get_option('ielts_cm_stripe_webhook_secret', '');
        
        if (empty($webhook_secret)) {
            error_log('IELTS Stripe Webhook: ERROR - Webhook secret not configured');
            
            // Log webhook event with error
            IELTS_CM_Database::log_webhook_event(
                'unknown',
                'N/A',
                'N/A',
                null,
                null,
                null,
                'failed',
                'Webhook secret not configured',
                substr($payload, 0, 1000) // Store first 1000 chars of payload for debugging
            );
            
            return new WP_Error('configuration_error', 'Webhook secret not configured', array('status' => 500));
        }
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhook_secret
            );
            error_log('IELTS Stripe Webhook: Successfully verified signature for event type: ' . $event->type);
        } catch (\Exception $e) {
            error_log('IELTS Stripe Webhook: Signature verification failed - ' . $e->getMessage());
            
            // Log webhook event with error
            IELTS_CM_Database::log_webhook_event(
                'verification_failed',
                'N/A',
                'N/A',
                null,
                null,
                null,
                'failed',
                'Signature verification failed: ' . $e->getMessage(),
                substr($payload, 0, 1000)
            );
            
            return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 400));
        }
        
        // Extract payment intent data for logging
        $payment_intent_id = 'N/A';
        $payment_type = null;
        $user_id = null;
        $amount = null;
        
        if ($event->type === 'payment_intent.succeeded' && isset($event->data->object)) {
            $payment_intent = $event->data->object;
            $payment_intent_id = $payment_intent->id;
            $amount = $payment_intent->amount / 100;
            
            if (isset($payment_intent->metadata->payment_type)) {
                $payment_type = $payment_intent->metadata->payment_type;
            }
            
            if (isset($payment_intent->metadata->user_id)) {
                $user_id = intval($payment_intent->metadata->user_id);
            }
        }
        
        // Log webhook receipt
        $log_id = IELTS_CM_Database::log_webhook_event(
            $event->type,
            $event->id ?? 'N/A',
            $payment_intent_id,
            $payment_type,
            $user_id,
            $amount,
            'received',
            null,
            null // Don't store full payload to save space
        );
        
        // Handle the event
        try {
            if ($event->type === 'payment_intent.succeeded') {
                error_log('IELTS Stripe Webhook: Processing payment_intent.succeeded event');
                $payment_intent = $event->data->object;
                $this->handle_successful_payment($payment_intent);
                
                // Update log to processed status
                if ($log_id) {
                    global $wpdb;
                    $wpdb->update(
                        $wpdb->prefix . 'ielts_cm_webhook_log',
                        array('status' => 'processed', 'processed_at' => current_time('mysql')),
                        array('id' => $log_id),
                        array('%s', '%s'),
                        array('%d')
                    );
                }
            } else {
                error_log('IELTS Stripe Webhook: Received unhandled event type: ' . $event->type);
            }
            
            return new WP_REST_Response(['status' => 'success'], 200);
        } catch (\Exception $e) {
            error_log('IELTS Stripe Webhook: Error processing webhook - ' . $e->getMessage());
            
            // Update log with error
            if ($log_id) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'ielts_cm_webhook_log',
                    array('status' => 'failed', 'error_message' => $e->getMessage()),
                    array('id' => $log_id),
                    array('%s', '%s'),
                    array('%d')
                );
            }
            
            return new WP_Error('processing_error', 'Error processing webhook: ' . $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Create user and assign membership after successful payment
     */
    private function handle_successful_payment($payment_intent) {
        $metadata = $payment_intent->metadata;
        
        error_log('IELTS Stripe Webhook: handle_successful_payment called for payment_intent: ' . $payment_intent->id);
        error_log('IELTS Stripe Webhook: Metadata payment_type: ' . (isset($metadata->payment_type) ? $metadata->payment_type : 'NOT SET'));
        
        // Check if this is a course extension payment
        if (isset($metadata->payment_type) && $metadata->payment_type === 'course_extension') {
            error_log('IELTS Stripe Webhook: Delegating to handle_extension_payment');
            $this->handle_extension_payment($payment_intent);
            return;
        }
        
        // Check if this is an access code purchase payment
        if (isset($metadata->payment_type) && $metadata->payment_type === 'access_code_purchase') {
            error_log('IELTS Stripe Webhook: Delegating to handle_code_purchase_payment');
            $this->handle_code_purchase_payment($payment_intent);
            return;
        }
        
        error_log('IELTS Stripe Webhook: Processing as standard membership payment');
        
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
        
        // Suppress automatic new user notification in webhook
        // We'll send the welcome email after membership is activated
        add_filter('wp_send_new_user_notifications', '__return_false');
        
        // Create user account
        // Mark this as authorized registration
        if (!defined('IELTS_CM_AUTHORIZED_REGISTRATION')) {
            define('IELTS_CM_AUTHORIZED_REGISTRATION', true);
        }
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        
        // Re-enable new user notifications
        remove_filter('wp_send_new_user_notifications', '__return_false');
        
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
            error_log('IELTS Payment Webhook: Membership handler not loaded');
            return;
        }
        
        try {
            // Set status to active (this also assigns the WordPress role)
            $membership = new IELTS_CM_Membership();
            $membership->set_user_membership_status($user_id, IELTS_CM_Membership::STATUS_ACTIVE);
            
            // Clear expiry email tracking when activating new membership
            delete_user_meta($user_id, '_ielts_cm_expiry_email_sent');
            
            // Set expiry date
            $expiry_date = $membership->calculate_expiry_date($membership_type);
            update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
            
            // Store payment info
            update_user_meta($user_id, '_ielts_cm_payment_intent_id', $payment_intent->id);
            update_user_meta($user_id, '_ielts_cm_payment_amount', $payment_intent->amount / 100);
            update_user_meta($user_id, '_ielts_cm_payment_date', current_time('mysql'));
            
            // Send welcome email after membership activation
            // Note: We suppressed the automatic email during user creation
            try {
                wp_new_user_notification($user_id, null, 'both');
                error_log("IELTS Payment Webhook: Welcome email sent successfully for user $user_id");
            } catch (Throwable $e) {
                error_log('IELTS Payment Webhook: Failed to send welcome email - ' . $e->getMessage());
            }
            
            error_log("Successfully created user $user_id with membership $membership_type after payment");
        } catch (Throwable $e) {
            error_log('IELTS Payment Webhook: Error activating membership - ' . $e->getMessage());
            error_log('IELTS Payment Webhook: Stack trace - ' . $e->getTraceAsString());
        }
    }
    
    /**
     * Create payment intent for course extension
     */
    public function create_extension_payment_intent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_extension_payment')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to purchase an extension'));
            return;
        }
        
        $user_id = get_current_user_id();
        $extension_type = sanitize_text_field($_POST['extension_type']);
        $price = floatval($_POST['price']);
        $days = intval($_POST['days']);
        
        // Validate inputs
        if (!in_array($extension_type, array('1_week', '1_month', '3_months'))) {
            wp_send_json_error(array('message' => 'Invalid extension type'));
            return;
        }
        
        if ($price <= 0 || $days <= 0) {
            wp_send_json_error(array('message' => 'Invalid pricing or duration'));
            return;
        }
        
        // Verify pricing matches server-side settings
        $extension_pricing = get_option('ielts_cm_extension_pricing', array());
        if (!isset($extension_pricing[$extension_type]) || floatval($extension_pricing[$extension_type]) !== $price) {
            wp_send_json_error(array('message' => 'Price mismatch. Please refresh and try again.'));
            return;
        }
        
        // Load Stripe
        $this->load_stripe();
        
        try {
            // Get Stripe secret key
            $stripe_secret = get_option('ielts_cm_stripe_secret_key');
            if (empty($stripe_secret)) {
                wp_send_json_error(array('message' => 'Payment system not configured'));
                return;
            }
            
            \Stripe\Stripe::setApiKey($stripe_secret);
            
            // Create payment intent
            $amount = intval($price * 100); // Convert to cents
            $user = get_userdata($user_id);
            
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'description' => sprintf('Course Extension - %d days', $days),
                'metadata' => [
                    'user_id' => $user_id,
                    'user_email' => $user->user_email,
                    'extension_type' => $extension_type,
                    'days' => $days,
                    'payment_type' => 'course_extension'
                ],
                'receipt_email' => $user->user_email
            ]);
            
            // Store pending extension in user meta for webhook processing
            update_user_meta($user_id, '_ielts_cm_pending_extension', array(
                'extension_type' => $extension_type,
                'days' => $days,
                'amount' => $price,
                'payment_intent_id' => $payment_intent->id,
                'created' => time()
            ));
            
            wp_send_json_success(array(
                'client_secret' => $payment_intent->client_secret
            ));
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Payment system error: ' . $e->getMessage()));
        } catch (Exception $e) {
            error_log('Extension Payment Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An error occurred. Please try again.'));
        }
    }
    
    /**
     * Handle successful course extension payment
     */
    private function handle_extension_payment($payment_intent) {
        $metadata = $payment_intent->metadata;
        
        $user_id = intval($metadata->user_id);
        $days = intval($metadata->days);
        $extension_type = $metadata->extension_type;
        
        error_log("Processing course extension payment for user $user_id - $days days");
        
        // Verify user exists
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("Extension payment failed: User $user_id not found");
            return;
        }
        
        // Get current expiry date
        $current_expiry = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);
        $iw_expiry = get_user_meta($user_id, 'iw_membership_expiry', true);
        
        // Determine which expiry to extend (prioritize access code expiry if exists)
        if (!empty($iw_expiry)) {
            // Extend access code membership
            $expiry_timestamp = strtotime($iw_expiry);
            if ($expiry_timestamp < time()) {
                // If expired, start from now
                $expiry_timestamp = time();
            }
            $new_expiry = date('Y-m-d H:i:s', strtotime("+{$days} days", $expiry_timestamp));
            update_user_meta($user_id, 'iw_membership_expiry', $new_expiry);
            error_log("Extended access code membership for user $user_id to $new_expiry");
        } elseif (!empty($current_expiry)) {
            // Extend paid membership
            $expiry_timestamp = strtotime($current_expiry);
            if ($expiry_timestamp < time()) {
                // If expired, start from now
                $expiry_timestamp = time();
            }
            $new_expiry = date('Y-m-d H:i:s', strtotime("+{$days} days", $expiry_timestamp));
            update_user_meta($user_id, '_ielts_cm_membership_expiry', $new_expiry);
            error_log("Extended paid membership for user $user_id to $new_expiry");
        } else {
            // No existing membership, create one starting now
            $new_expiry = date('Y-m-d H:i:s', strtotime("+{$days} days"));
            update_user_meta($user_id, '_ielts_cm_membership_expiry', $new_expiry);
            error_log("Created new membership for user $user_id with expiry $new_expiry");
        }
        
        // Log the payment
        $this->ensure_payment_table_exists();
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_payments';
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'membership_type' => 'extension_' . $extension_type,
                'amount' => $payment_intent->amount / 100,
                'transaction_id' => $payment_intent->id,
                'payment_status' => 'completed'
            ),
            array('%d', '%s', '%f', '%s', '%s')
        );
        
        // Clean up pending extension meta
        delete_user_meta($user_id, '_ielts_cm_pending_extension');
        
        error_log("Successfully processed course extension payment for user $user_id");
    }
    
    /**
     * Create payment intent for access code purchase
     */
    public function create_code_purchase_payment_intent() {
        error_log('IELTS Stripe: create_code_purchase_payment_intent CALLED');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_code_purchase_payment')) {
            error_log('IELTS Stripe: Code purchase failed - nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            error_log('IELTS Stripe: Code purchase failed - user not logged in');
            wp_send_json_error(array('message' => 'You must be logged in to purchase codes'));
            return;
        }
        
        // Verify user has permission (partner admin or site admin)
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            $user_id = get_current_user_id();
            error_log("IELTS Stripe: Code purchase failed - user $user_id lacks permission");
            wp_send_json_error(array('message' => 'You do not have permission to purchase codes'));
            return;
        }
        
        // Verify hybrid mode is enabled
        if (!get_option('ielts_cm_hybrid_site_enabled', false)) {
            error_log('IELTS Stripe: Code purchase failed - hybrid mode not enabled');
            wp_send_json_error(array('message' => 'Code purchasing is only available in hybrid mode'));
            return;
        }
        
        $user_id = get_current_user_id();
        error_log("IELTS Stripe: Creating payment intent for user $user_id");
        $quantity = intval($_POST['quantity']);
        $course_group = sanitize_text_field($_POST['course_group']);
        // Fixed 30-day access for hybrid sites (validated above - this function only runs on hybrid sites)
        $access_days = 30;
        $price = floatval($_POST['price']);
        
        // Validate inputs
        if ($quantity <= 0 || $price <= 0) {
            wp_send_json_error(array('message' => 'Invalid purchase parameters'));
            return;
        }
        
        // Verify course group is valid ('any' is valid for universal access codes)
        $valid_groups = array('academic_module', 'general_module', 'general_english', 'entry_test', 'any');
        if (!in_array($course_group, $valid_groups)) {
            wp_send_json_error(array('message' => 'Invalid course group'));
            return;
        }
        
        // Verify pricing matches server-side settings
        $pricing_tiers = get_option('ielts_cm_access_code_pricing_tiers', array());
        $price_valid = false;
        
        if (!empty($pricing_tiers)) {
            foreach ($pricing_tiers as $tier) {
                if (intval($tier['quantity']) === $quantity && floatval($tier['price']) === $price) {
                    $price_valid = true;
                    break;
                }
            }
        } else {
            // Fall back to old format
            $old_pricing = get_option('ielts_cm_access_code_pricing', array());
            if (isset($old_pricing[strval($quantity)]) && floatval($old_pricing[strval($quantity)]) === $price) {
                $price_valid = true;
            }
        }
        
        if (!$price_valid) {
            wp_send_json_error(array('message' => 'Price mismatch. Please refresh and try again.'));
            return;
        }
        
        // Load Stripe
        $this->load_stripe();
        
        try {
            // Get Stripe secret key
            $stripe_secret = get_option('ielts_cm_stripe_secret_key');
            if (empty($stripe_secret)) {
                wp_send_json_error(array('message' => 'Payment system not configured'));
                return;
            }
            
            \Stripe\Stripe::setApiKey($stripe_secret);
            
            // Create payment intent
            $amount = intval($price * 100); // Convert to cents
            $user = get_userdata($user_id);
            
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'description' => sprintf('Access Codes Purchase - %d codes', $quantity),
                'metadata' => [
                    'user_id' => $user_id,
                    'user_email' => $user->user_email,
                    'quantity' => $quantity,
                    'course_group' => $course_group,
                    'access_days' => $access_days,
                    'payment_type' => 'access_code_purchase'
                ],
                'receipt_email' => $user->user_email
            ]);
            
            // Store pending purchase in user meta for webhook processing
            update_user_meta($user_id, '_ielts_cm_pending_code_purchase', array(
                'quantity' => $quantity,
                'course_group' => $course_group,
                'access_days' => $access_days,
                'amount' => $price,
                'payment_intent_id' => $payment_intent->id,
                'created' => time()
            ));
            
            wp_send_json_success(array(
                'client_secret' => $payment_intent->client_secret
            ));
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Payment system error: ' . $e->getMessage()));
        } catch (Exception $e) {
            error_log('Code Purchase Payment Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An error occurred. Please try again.'));
        }
    }
    
    /**
     * Handle successful code purchase payment
     */
    private function handle_code_purchase_payment($payment_intent) {
        error_log("IELTS Webhook: handle_code_purchase_payment START - Payment Intent ID: " . $payment_intent->id);
        
        $metadata = $payment_intent->metadata;
        
        $user_id = intval($metadata->user_id);
        $quantity = intval($metadata->quantity);
        $course_group = $metadata->course_group;
        $duration_days = intval($metadata->access_days);
        $amount = $payment_intent->amount / 100;
        
        error_log("IELTS Webhook: Processing code purchase - User: $user_id, Quantity: $quantity, Group: $course_group, Days: $duration_days, Amount: $$amount");
        
        // Verify user exists
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("CRITICAL: Code purchase payment failed - User $user_id not found");
            return;
        }
        
        error_log("IELTS Webhook: User verified - Email: " . $user->user_email);
        
        // Get partner organization ID from user meta
        // For hybrid sites: Default to SITE_PARTNER_ORG_ID (1) if user doesn't have custom org ID set
        // This ensures codes are visible in the partner dashboard
        $org_id = get_user_meta($user_id, 'iw_partner_organization_id', true);
        if (!empty($org_id) && is_numeric($org_id)) {
            $partner_org_id = (int) $org_id;
            error_log("IELTS Webhook: Partner Org ID: $partner_org_id (from user meta)");
        } else {
            // HYBRID FIX: Use SITE_PARTNER_ORG_ID constant from IELTS_CM_Access_Codes class
            // This matches the default organization used by class-access-codes.php
            // Note: Access_Codes class is always loaded before Stripe_Payment in plugin init
            if (class_exists('IELTS_CM_Access_Codes')) {
                $partner_org_id = IELTS_CM_Access_Codes::SITE_PARTNER_ORG_ID;
            } else {
                // Defensive fallback (should never execute in normal operation)
                $partner_org_id = 1;
            }
            error_log("IELTS Webhook: Partner Org ID: $partner_org_id (using SITE_PARTNER_ORG_ID - no custom org_id set for user)");
        }
        
        // Create the access codes
        $generated_codes = array();
        if (class_exists('IELTS_CM_Access_Codes')) {
            error_log("IELTS Webhook: IELTS_CM_Access_Codes class found, creating codes...");
            $access_codes = new IELTS_CM_Access_Codes();
            
            // Ensure access codes table exists (critical fix for hybrid system)
            // This prevents silent failure when Stripe payment succeeds but table is missing
            $this->ensure_access_codes_table_exists();
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'ielts_cm_access_codes';
            
            error_log("IELTS Webhook: Access codes table verified, generating $quantity codes...");
            
            // Generate codes
            for ($i = 0; $i < $quantity; $i++) {
                // Generate secure random code using WordPress function
                $code = strtoupper(substr(str_replace(array('-', '_'), '', wp_generate_password(10, false)), 0, 10));
                
                $insert_result = $wpdb->insert(
                    $table_name,
                    array(
                        'code' => $code,
                        'course_group' => $course_group,
                        'duration_days' => $duration_days,
                        'created_by' => $partner_org_id,
                        'status' => 'active',
                        'created_date' => current_time('mysql')
                    ),
                    array('%s', '%s', '%d', '%d', '%s', '%s')
                );
                
                if ($insert_result === false) {
                    error_log("CRITICAL: Failed to insert code $code for user $user_id (org $partner_org_id): " . $wpdb->last_error);
                } else {
                    $generated_codes[] = $code;
                    error_log("IELTS Webhook: Code created successfully: $code");
                }
            }
            
            error_log("IELTS Webhook: Successfully created " . count($generated_codes) . "/$quantity access codes for user $user_id (org $partner_org_id)");
            
            // Send confirmation email with the codes
            if (method_exists($access_codes, 'send_purchase_confirmation_email')) {
                error_log("IELTS Webhook: Sending purchase confirmation email...");
                $email_sent = $access_codes->send_purchase_confirmation_email($user_id, $generated_codes, $course_group, $duration_days, $amount);
                if ($email_sent) {
                    error_log("IELTS Webhook: Successfully sent purchase confirmation email to user $user_id with " . count($generated_codes) . " codes");
                } else {
                    error_log("CRITICAL: Failed to send purchase confirmation email to user $user_id");
                }
            } else {
                error_log("IELTS Webhook: send_purchase_confirmation_email method not found in IELTS_CM_Access_Codes");
            }
        } else {
            error_log("CRITICAL: Code purchase payment failed - IELTS_CM_Access_Codes class not found");
        }
        
        // Log the payment
        $this->ensure_payment_table_exists();
        global $wpdb;
        $table_name = $wpdb->prefix . 'ielts_cm_payments';
        
        error_log("IELTS Webhook: Logging payment to database table: $table_name");
        
        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'membership_type' => 'access_codes_' . $quantity,
                'amount' => $amount,
                'transaction_id' => $payment_intent->id,
                'payment_status' => 'completed'
            ),
            array('%d', '%s', '%f', '%s', '%s')
        );
        
        if ($insert_result === false) {
            error_log("CRITICAL: Failed to log payment to database for user $user_id: " . $wpdb->last_error);
        } else {
            error_log("SUCCESS: Payment logged to database for user $user_id");
        }
        
        // Clean up pending purchase meta
        delete_user_meta($user_id, '_ielts_cm_pending_code_purchase');
        
        error_log("Successfully processed code purchase payment for user $user_id - Codes: " . implode(', ', $generated_codes));
    }
    
    /**
     * Check payment status directly with Stripe (webhook fallback)
     * This allows completing purchases when webhooks fail
     */
    public function check_payment_status() {
        error_log('IELTS Stripe: check_payment_status CALLED');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_check_payment_status')) {
            error_log('IELTS Stripe: Check payment status failed - nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            error_log('IELTS Stripe: Check payment status failed - user not logged in');
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);
        
        if (empty($payment_intent_id)) {
            wp_send_json_error(array('message' => 'Payment intent ID is required'));
            return;
        }
        
        error_log("IELTS Stripe: Checking payment status for intent $payment_intent_id (user $user_id)");
        
        // Load Stripe
        $this->load_stripe();
        
        try {
            // Get Stripe secret key
            $stripe_secret = get_option('ielts_cm_stripe_secret_key');
            if (empty($stripe_secret)) {
                wp_send_json_error(array('message' => 'Payment system not configured'));
                return;
            }
            
            \Stripe\Stripe::setApiKey($stripe_secret);
            
            // Retrieve payment intent from Stripe
            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            error_log("IELTS Stripe: Payment intent status: " . $payment_intent->status);
            
            // Check if payment was successful
            if ($payment_intent->status === 'succeeded') {
                // Verify this payment belongs to the current user
                $metadata = $payment_intent->metadata;
                if (!isset($metadata->user_id) || intval($metadata->user_id) !== $user_id) {
                    error_log("IELTS Stripe: Security error - payment intent user mismatch");
                    wp_send_json_error(array('message' => 'Payment verification failed'));
                    return;
                }
                
                // Check if payment was already processed (idempotency)
                global $wpdb;
                $payment_table = $wpdb->prefix . 'ielts_cm_payments';
                $existing_payment = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $payment_table WHERE transaction_id = %s",
                    $payment_intent_id
                ));
                
                if ($existing_payment) {
                    error_log("IELTS Stripe: Payment already processed (idempotency check)");
                    wp_send_json_success(array(
                        'status' => 'already_processed',
                        'message' => 'Payment was already processed'
                    ));
                    return;
                }
                
                // Process the payment based on type
                $payment_type = $metadata->payment_type ?? null;
                
                if ($payment_type === 'access_code_purchase') {
                    error_log("IELTS Stripe: Processing code purchase via fallback mechanism");
                    $this->handle_code_purchase_payment($payment_intent);
                    
                    wp_send_json_success(array(
                        'status' => 'completed',
                        'message' => 'Purchase completed successfully'
                    ));
                } elseif ($payment_type === 'course_extension') {
                    error_log("IELTS Stripe: Processing course extension via fallback mechanism");
                    $this->handle_extension_payment($payment_intent);
                    
                    wp_send_json_success(array(
                        'status' => 'completed',
                        'message' => 'Extension applied successfully'
                    ));
                } else {
                    error_log("IELTS Stripe: Unknown payment type: " . $payment_type);
                    wp_send_json_error(array('message' => 'Unknown payment type'));
                }
            } elseif ($payment_intent->status === 'processing') {
                wp_send_json_success(array(
                    'status' => 'processing',
                    'message' => 'Payment is being processed'
                ));
            } elseif ($payment_intent->status === 'requires_payment_method') {
                wp_send_json_error(array('message' => 'Payment failed - please try again'));
            } else {
                wp_send_json_error(array('message' => 'Payment not completed yet'));
            }
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('IELTS Stripe: API error checking payment status - ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error checking payment status: ' . $e->getMessage()));
        } catch (Exception $e) {
            error_log('IELTS Stripe: Error checking payment status - ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An error occurred'));
        }
    }
}
