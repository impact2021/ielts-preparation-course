<?php
/**
 * Base payment gateway class
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class IELTS_MS_Payment_Gateway {
    
    protected $id;
    protected $title;
    protected $enabled = false;
    
    /**
     * Process payment
     */
    abstract public function process_payment($amount, $user_id, $duration_days, $payment_type = 'new');
    
    /**
     * Handle payment callback/webhook
     */
    abstract public function handle_callback();
    
    /**
     * Record payment in database
     */
    protected function record_payment($user_id, $amount, $duration_days, $transaction_id = null, $status = 'completed', $payment_type = 'new') {
        global $wpdb;
        $table = IELTS_MS_Database::get_payments_table();
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'currency' => 'USD',
                'payment_method' => $this->id,
                'transaction_id' => $transaction_id,
                'payment_status' => $status,
                'payment_type' => $payment_type,
                'duration_days' => $duration_days,
                'payment_date' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get payment by transaction ID
     */
    protected function get_payment_by_transaction($transaction_id) {
        global $wpdb;
        $table = IELTS_MS_Database::get_payments_table();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE transaction_id = %s",
            $transaction_id
        ));
    }
    
    /**
     * Update payment status
     */
    protected function update_payment_status($payment_id, $status) {
        global $wpdb;
        $table = IELTS_MS_Database::get_payments_table();
        
        return $wpdb->update(
            $table,
            array('payment_status' => $status),
            array('id' => $payment_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Get pricing options
     */
    public static function get_pricing_options() {
        return array(
            'new_90' => array(
                'label' => '90 Days Membership',
                'price' => 24.95,
                'days' => 90,
                'type' => 'new'
            ),
            'extend_7' => array(
                'label' => '1 Week Extension',
                'price' => 5.00,
                'days' => 7,
                'type' => 'extension'
            ),
            'extend_30' => array(
                'label' => '1 Month Extension',
                'price' => 10.00,
                'days' => 30,
                'type' => 'extension'
            ),
            'extend_90' => array(
                'label' => '3 Months Extension',
                'price' => 20.00,
                'days' => 90,
                'type' => 'extension'
            )
        );
    }
}
