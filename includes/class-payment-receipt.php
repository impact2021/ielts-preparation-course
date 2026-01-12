<?php
/**
 * Payment receipt generation and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Payment_Receipt {
    
    private $db;
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return IELTS_CM_Payment_Receipt
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->db = new IELTS_CM_Database();
        
        // AJAX handler - only for logged in users
        add_action('wp_ajax_ielts_cm_download_receipt', array($this, 'download_receipt'));
    }
    
    /**
     * Add a payment record
     * 
     * @param int $user_id User ID
     * @param float $amount Payment amount
     * @param array $args Additional payment details
     * @return int|false Payment ID or false on failure
     */
    public function add_payment($user_id, $amount, $args = array()) {
        global $wpdb;
        $table = $this->db->get_payments_table();
        
        $defaults = array(
            'course_id' => null,
            'currency' => 'USD',
            'payment_method' => null,
            'transaction_id' => null,
            'status' => 'completed',
            'description' => null,
            'payment_date' => current_time('mysql')
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $result = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'course_id' => $args['course_id'],
            'amount' => $amount,
            'currency' => $args['currency'],
            'payment_method' => $args['payment_method'],
            'transaction_id' => $args['transaction_id'],
            'status' => $args['status'],
            'description' => $args['description'],
            'payment_date' => $args['payment_date']
        ));
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get payment by ID
     * 
     * @param int $payment_id Payment ID
     * @return object|null Payment object or null
     */
    public function get_payment($payment_id) {
        global $wpdb;
        $table = $this->db->get_payments_table();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $payment_id
        ));
    }
    
    /**
     * Get user payments
     * 
     * @param int $user_id User ID
     * @return array Array of payment objects
     */
    public function get_user_payments($user_id) {
        global $wpdb;
        $table = $this->db->get_payments_table();
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY payment_date DESC",
            $user_id
        ));
    }
    
    /**
     * Generate receipt PDF and download
     */
    public function download_receipt() {
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to download receipts.', 'ielts-course-manager'));
        }
        
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'download_receipt_' . (isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0))) {
            wp_die(__('Invalid security token.', 'ielts-course-manager'));
        }
        
        $payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
        
        if (!$payment_id) {
            wp_die(__('Invalid payment ID.', 'ielts-course-manager'));
        }
        
        $payment = $this->get_payment($payment_id);
        
        if (!$payment) {
            wp_die(__('Payment not found.', 'ielts-course-manager'));
        }
        
        // Verify the payment belongs to the current user
        $current_user_id = get_current_user_id();
        if ($payment->user_id != $current_user_id && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to view this receipt.', 'ielts-course-manager'));
        }
        
        // Generate and output the receipt PDF
        $this->generate_receipt_pdf($payment);
        exit;
    }
    
    /**
     * Generate receipt PDF
     * 
     * @param object $payment Payment object
     */
    private function generate_receipt_pdf($payment) {
        // Get user data
        $user = get_userdata($payment->user_id);
        
        // Get course data if applicable
        $course_name = '';
        if ($payment->course_id) {
            $course = get_post($payment->course_id);
            $course_name = $course ? $course->post_title : '';
        }
        
        // Generate HTML receipt that can be printed as PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="receipt-' . $payment->id . '.html"');
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Payment Receipt', 'ielts-course-manager'); ?> #<?php echo $payment->id; ?></title>
            <style>
                @media print {
                    .no-print { display: none; }
                }
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 40px 20px;
                    color: #333;
                }
                .receipt-header {
                    text-align: center;
                    margin-bottom: 40px;
                    border-bottom: 3px solid #0073aa;
                    padding-bottom: 20px;
                }
                .receipt-header h1 {
                    margin: 0;
                    color: #0073aa;
                    font-size: 28px;
                }
                .receipt-header p {
                    margin: 5px 0;
                    color: #666;
                }
                .receipt-info {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-bottom: 30px;
                }
                .info-section h3 {
                    margin: 0 0 15px 0;
                    font-size: 16px;
                    color: #0073aa;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 8px;
                }
                .info-row {
                    display: flex;
                    padding: 8px 0;
                }
                .info-label {
                    font-weight: 600;
                    width: 140px;
                    color: #555;
                }
                .info-value {
                    color: #333;
                }
                .payment-details {
                    background: #f9f9f9;
                    padding: 20px;
                    border-radius: 5px;
                    margin-bottom: 30px;
                    border: 1px solid #ddd;
                }
                .payment-details h3 {
                    margin: 0 0 20px 0;
                    color: #0073aa;
                }
                .amount-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 12px 0;
                    border-bottom: 1px solid #ddd;
                    font-size: 16px;
                }
                .amount-row.total {
                    font-size: 20px;
                    font-weight: bold;
                    border-top: 2px solid #333;
                    border-bottom: 2px solid #333;
                    margin-top: 10px;
                    color: #0073aa;
                }
                .receipt-footer {
                    text-align: center;
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 14px;
                }
                .print-button {
                    text-align: center;
                    margin: 30px 0;
                }
                .print-button button {
                    background: #0073aa;
                    color: #fff;
                    padding: 12px 30px;
                    border: none;
                    border-radius: 3px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background 0.3s;
                }
                .print-button button:hover {
                    background: #005177;
                }
            </style>
            <script>
                function printReceipt() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="receipt-header">
                <h1><?php _e('PAYMENT RECEIPT', 'ielts-course-manager'); ?></h1>
                <p><?php echo esc_html(get_bloginfo('name')); ?></p>
                <p><?php _e('Receipt Number:', 'ielts-course-manager'); ?> #<?php echo str_pad($payment->id, 6, '0', STR_PAD_LEFT); ?></p>
            </div>
            
            <div class="receipt-info">
                <div class="info-section">
                    <h3><?php _e('Customer Information', 'ielts-course-manager'); ?></h3>
                    <div class="info-row">
                        <span class="info-label"><?php _e('Name:', 'ielts-course-manager'); ?></span>
                        <span class="info-value"><?php 
                            $full_name = trim($user->first_name . ' ' . $user->last_name);
                            echo esc_html($full_name ? $full_name : $user->display_name); 
                        ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?php _e('Email:', 'ielts-course-manager'); ?></span>
                        <span class="info-value"><?php echo esc_html($user->user_email); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?php _e('Username:', 'ielts-course-manager'); ?></span>
                        <span class="info-value"><?php echo esc_html($user->user_login); ?></span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><?php _e('Payment Information', 'ielts-course-manager'); ?></h3>
                    <div class="info-row">
                        <span class="info-label"><?php _e('Date:', 'ielts-course-manager'); ?></span>
                        <span class="info-value"><?php echo date_i18n(get_option('date_format'), strtotime($payment->payment_date)); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?php _e('Time:', 'ielts-course-manager'); ?></span>
                        <span class="info-value"><?php echo date_i18n(get_option('time_format'), strtotime($payment->payment_date)); ?></span>
                    </div>
                    <?php if ($payment->transaction_id): ?>
                    <div class="info-row">
                        <span class="info-label"><?php _e('Transaction ID:', 'ielts-course-manager'); ?></span>
                        <span class="info-value"><?php echo esc_html($payment->transaction_id); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($payment->payment_method): ?>
                    <div class="info-row">
                        <span class="info-label"><?php _e('Payment Method:', 'ielts-course-manager'); ?></span>
                        <span class="info-value"><?php echo esc_html(ucfirst($payment->payment_method)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="payment-details">
                <h3><?php _e('Payment Details', 'ielts-course-manager'); ?></h3>
                <div class="amount-row">
                    <span><?php echo $payment->description ?: ($course_name ? esc_html($course_name) : __('Course Payment', 'ielts-course-manager')); ?></span>
                    <span><?php echo esc_html($payment->currency . ' ' . number_format($payment->amount, 2)); ?></span>
                </div>
                <div class="amount-row total">
                    <span><?php _e('TOTAL PAID', 'ielts-course-manager'); ?></span>
                    <span><?php echo esc_html($payment->currency . ' ' . number_format($payment->amount, 2)); ?></span>
                </div>
            </div>
            
            <div class="receipt-footer">
                <p><strong><?php _e('Thank you for your payment!', 'ielts-course-manager'); ?></strong></p>
                <p><?php _e('This is an automatically generated receipt for your records.', 'ielts-course-manager'); ?></p>
                <p><?php _e('If you have any questions, please contact our support team.', 'ielts-course-manager'); ?></p>
            </div>
            
            <div class="print-button no-print">
                <button onclick="printReceipt()"><?php _e('Print / Save as PDF', 'ielts-course-manager'); ?></button>
            </div>
        </body>
        </html>
        <?php
    }
}
