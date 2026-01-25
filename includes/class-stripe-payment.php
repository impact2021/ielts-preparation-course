<?php
/**
 * Stripe Payment Processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Stripe_Payment {
    
    public function init() {
        // AJAX endpoint for creating payment intent
        add_action('wp_ajax_nopriv_ielts_create_payment_intent', array($this, 'create_payment_intent'));
        add_action('wp_ajax_ielts_create_payment_intent', array($this, 'create_payment_intent'));
        
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
     * Create Payment Intent for registration
     */
    public function create_payment_intent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_payment_intent')) {
            wp_send_json_error('Security check failed', 403);
        }
        
        $membership_type = sanitize_text_field($_POST['membership_type']);
        
        // SECURITY: Validate membership type exists and get server-side price
        $pricing = get_option('ielts_cm_membership_pricing', array());
        if (!isset($pricing[$membership_type])) {
            wp_send_json_error('Invalid membership type', 400);
        }
        
        $price = floatval($pricing[$membership_type]);
        
        // Don't create payment intent for free memberships
        if ($price <= 0) {
            wp_send_json_error('This membership is free', 400);
        }
        
        // Get Stripe secret key
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        if (empty($stripe_secret)) {
            wp_send_json_error('Payment system not configured', 500);
        }
        
        $this->load_stripe();
        \Stripe\Stripe::setApiKey($stripe_secret);
        
        try {
            // Create Payment Intent
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => intval($price * 100), // Convert to cents
                'currency' => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'membership_type' => $membership_type,
                    'email' => sanitize_email($_POST['email']),
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                ],
            ]);
            
            wp_send_json_success([
                'clientSecret' => $payment_intent->client_secret,
            ]);
            
        } catch (\Exception $e) {
            error_log('Stripe Payment Intent Error: ' . $e->getMessage());
            wp_send_json_error('Payment system error', 500);
        }
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
