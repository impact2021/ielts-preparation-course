<?php
/**
 * Membership Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Membership {
    
    private $memberships_table;
    private $payments_table;
    
    public function __construct() {
        global $wpdb;
        $this->memberships_table = $wpdb->prefix . 'ielts_cm_memberships';
        $this->payments_table = $wpdb->prefix . 'ielts_cm_payments';
    }
    
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add AJAX handlers
        add_action('wp_ajax_ielts_cm_resend_receipt', array($this, 'ajax_resend_receipt'));
        
        // Hook to send receipt when membership is created/activated
        add_action('ielts_cm_membership_activated', array($this, 'send_receipt_email'), 10, 2);
    }
    
    /**
     * Create membership tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Memberships table
        $memberships_table = $wpdb->prefix . 'ielts_cm_memberships';
        $sql_memberships = "CREATE TABLE IF NOT EXISTS $memberships_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Payments table
        $payments_table = $wpdb->prefix . 'ielts_cm_payments';
        $sql_payments = "CREATE TABLE IF NOT EXISTS $payments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            membership_id bigint(20) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) DEFAULT 'USD',
            payment_method varchar(50) DEFAULT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            payment_date datetime NOT NULL,
            status varchar(20) DEFAULT 'completed',
            payment_type varchar(50) DEFAULT 'new',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY membership_id (membership_id),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_memberships);
        dbDelta($sql_payments);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Memberships', 'ielts-course-manager'),
            __('Memberships', 'ielts-course-manager'),
            'manage_options',
            'ielts-memberships',
            array($this, 'memberships_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'ielts-memberships',
            __('All Memberships', 'ielts-course-manager'),
            __('All Memberships', 'ielts-course-manager'),
            'manage_options',
            'ielts-memberships',
            array($this, 'memberships_page')
        );
        
        add_submenu_page(
            'ielts-memberships',
            __('Payments', 'ielts-course-manager'),
            __('Payments', 'ielts-course-manager'),
            'manage_options',
            'ielts-payments',
            array($this, 'payments_page')
        );
        
        add_submenu_page(
            'ielts-memberships',
            __('Company Settings', 'ielts-course-manager'),
            __('Company Settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-company-settings',
            array($this, 'company_settings_page')
        );
    }
    
    /**
     * Memberships page
     */
    public function memberships_page() {
        global $wpdb;
        
        // Handle form submissions
        if (isset($_POST['ielts_cm_membership_action']) && check_admin_referer('ielts_cm_membership_nonce')) {
            // Handle actions if needed
        }
        
        // Get all memberships
        $memberships = $wpdb->get_results("
            SELECT m.*, u.user_login, u.user_email, u.display_name
            FROM {$this->memberships_table} m
            LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
            ORDER BY m.created_date DESC
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Memberships', 'ielts-course-manager'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'ielts-course-manager'); ?></th>
                        <th><?php _e('User', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Email', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Start Date', 'ielts-course-manager'); ?></th>
                        <th><?php _e('End Date', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Actions', 'ielts-course-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memberships)) : ?>
                        <tr>
                            <td colspan="7"><?php _e('No memberships found.', 'ielts-course-manager'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($memberships as $membership) : 
                            // Update status based on dates
                            $now = current_time('mysql');
                            $is_expired = $membership->end_date < $now;
                            $actual_status = $is_expired ? 'expired' : 'active';
                            ?>
                            <tr>
                                <td><?php echo esc_html($membership->id); ?></td>
                                <td><?php echo esc_html($membership->display_name ?: $membership->user_login); ?></td>
                                <td><?php echo esc_html($membership->user_email); ?></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($membership->start_date))); ?></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($membership->end_date))); ?></td>
                                <td>
                                    <span class="membership-status-<?php echo esc_attr($actual_status); ?>">
                                        <?php echo esc_html(ucfirst($actual_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="button button-small resend-receipt-btn" 
                                            data-membership-id="<?php echo esc_attr($membership->id); ?>"
                                            data-user-id="<?php echo esc_attr($membership->user_id); ?>">
                                        <?php _e('Resend Receipt', 'ielts-course-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.resend-receipt-btn').on('click', function() {
                var btn = $(this);
                var membershipId = btn.data('membership-id');
                var userId = btn.data('user-id');
                
                btn.prop('disabled', true).text('<?php _e('Sending...', 'ielts-course-manager'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_resend_receipt',
                        membership_id: membershipId,
                        user_id: userId,
                        nonce: '<?php echo wp_create_nonce('ielts_cm_resend_receipt'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Receipt sent successfully!', 'ielts-course-manager'); ?>');
                        } else {
                            alert('<?php _e('Error sending receipt: ', 'ielts-course-manager'); ?>' + response.data);
                        }
                        btn.prop('disabled', false).text('<?php _e('Resend Receipt', 'ielts-course-manager'); ?>');
                    },
                    error: function() {
                        alert('<?php _e('Error sending receipt.', 'ielts-course-manager'); ?>');
                        btn.prop('disabled', false).text('<?php _e('Resend Receipt', 'ielts-course-manager'); ?>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .membership-status-active {
            color: #46b450;
            font-weight: bold;
        }
        .membership-status-expired {
            color: #dc3232;
            font-weight: bold;
        }
        </style>
        <?php
    }
    
    /**
     * Payments page
     */
    public function payments_page() {
        global $wpdb;
        
        $payments = $wpdb->get_results("
            SELECT p.*, u.user_login, u.user_email, u.display_name
            FROM {$this->payments_table} p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            ORDER BY p.payment_date DESC
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Payment History', 'ielts-course-manager'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'ielts-course-manager'); ?></th>
                        <th><?php _e('User', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Amount', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Currency', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Payment Method', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Transaction ID', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Payment Date', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Type', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)) : ?>
                        <tr>
                            <td colspan="9"><?php _e('No payments found.', 'ielts-course-manager'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($payments as $payment) : ?>
                            <tr>
                                <td><?php echo esc_html($payment->id); ?></td>
                                <td><?php echo esc_html($payment->display_name ?: $payment->user_login); ?></td>
                                <td><?php echo esc_html(number_format($payment->amount, 2)); ?></td>
                                <td><?php echo esc_html($payment->currency); ?></td>
                                <td><?php echo esc_html($payment->payment_method ?: '—'); ?></td>
                                <td><?php echo esc_html($payment->transaction_id ?: '—'); ?></td>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($payment->payment_date))); ?></td>
                                <td><?php echo esc_html(ucfirst($payment->payment_type)); ?></td>
                                <td><?php echo esc_html(ucfirst($payment->status)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Company settings page
     */
    public function company_settings_page() {
        // Handle form submission
        if (isset($_POST['ielts_cm_company_settings']) && check_admin_referer('ielts_cm_company_settings_nonce')) {
            update_option('ielts_cm_company_name', sanitize_text_field($_POST['company_name']));
            update_option('ielts_cm_company_address', sanitize_textarea_field($_POST['company_address']));
            update_option('ielts_cm_company_gst', sanitize_text_field($_POST['company_gst']));
            update_option('ielts_cm_company_phone', sanitize_text_field($_POST['company_phone']));
            update_option('ielts_cm_company_email', sanitize_email($_POST['company_email']));
            update_option('ielts_cm_company_website', esc_url_raw($_POST['company_website']));
            
            // Handle logo upload
            if (!empty($_FILES['company_logo']['name'])) {
                $upload = wp_handle_upload($_FILES['company_logo'], array('test_form' => false));
                if (!isset($upload['error'])) {
                    update_option('ielts_cm_company_logo', $upload['url']);
                }
            }
            
            echo '<div class="updated"><p>' . __('Settings saved successfully.', 'ielts-course-manager') . '</p></div>';
        }
        
        // Get current settings
        $company_name = get_option('ielts_cm_company_name', '');
        $company_address = get_option('ielts_cm_company_address', '');
        $company_gst = get_option('ielts_cm_company_gst', '');
        $company_phone = get_option('ielts_cm_company_phone', '');
        $company_email = get_option('ielts_cm_company_email', '');
        $company_website = get_option('ielts_cm_company_website', '');
        $company_logo = get_option('ielts_cm_company_logo', '');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Company Settings', 'ielts-course-manager'); ?></h1>
            <p><?php _e('Configure your company details for use in receipts and invoices.', 'ielts-course-manager'); ?></p>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('ielts_cm_company_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="company_name"><?php _e('Company Name', 'ielts-course-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text" required aria-required="true">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company_address"><?php _e('Company Address', 'ielts-course-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="company_address" name="company_address" rows="4" class="large-text" required aria-required="true" aria-describedby="company-address-description"><?php echo esc_textarea($company_address); ?></textarea>
                            <p class="description" id="company-address-description"><?php _e('Full address including city, state, and postal code.', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company_gst"><?php _e('GST/Tax Number', 'ielts-course-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company_gst" name="company_gst" value="<?php echo esc_attr($company_gst); ?>" class="regular-text">
                            <p class="description"><?php _e('Your GST, VAT, or Tax ID number.', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company_phone"><?php _e('Phone Number', 'ielts-course-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company_phone" name="company_phone" value="<?php echo esc_attr($company_phone); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company_email"><?php _e('Email Address', 'ielts-course-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="company_email" name="company_email" value="<?php echo esc_attr($company_email); ?>" class="regular-text" required aria-required="true">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company_website"><?php _e('Website', 'ielts-course-manager'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="company_website" name="company_website" value="<?php echo esc_attr($company_website); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company_logo"><?php _e('Company Logo', 'ielts-course-manager'); ?></label>
                        </th>
                        <td>
                            <?php if ($company_logo) : ?>
                                <div style="margin-bottom: 10px;">
                                    <img src="<?php echo esc_url($company_logo); ?>" alt="Company Logo" style="max-width: 200px; height: auto;">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="company_logo" name="company_logo" accept="image/*">
                            <p class="description"><?php _e('Upload a logo for use in receipts (recommended size: 200x80px).', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <input type="hidden" name="ielts_cm_company_settings" value="1">
                <?php submit_button(__('Save Settings', 'ielts-course-manager')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * AJAX handler to resend receipt
     */
    public function ajax_resend_receipt() {
        check_ajax_referer('ielts_cm_resend_receipt', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $membership_id = intval($_POST['membership_id']);
        $user_id = intval($_POST['user_id']);
        
        if (!$membership_id || !$user_id) {
            wp_send_json_error('Invalid parameters');
            return;
        }
        
        // Send the receipt
        $result = $this->send_receipt_email($membership_id, $user_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to send email');
        }
    }
    
    /**
     * Send receipt email with PDF attachment
     */
    public function send_receipt_email($membership_id, $user_id) {
        global $wpdb;
        
        // Get membership details
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->memberships_table} WHERE id = %d AND user_id = %d",
            $membership_id,
            $user_id
        ));
        
        if (!$membership) {
            return false;
        }
        
        // Get payment details
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->payments_table} WHERE membership_id = %d ORDER BY payment_date DESC LIMIT 1",
            $membership_id
        ));
        
        // Get user details
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Generate PDF
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        $pdf_generator = new IELTS_CM_PDF_Generator();
        $pdf_path = $pdf_generator->generate_receipt_pdf($membership, $payment, $user);
        
        if (!$pdf_path || !file_exists($pdf_path)) {
            return false;
        }
        
        // Send email
        $to = $user->user_email;
        $subject = __('Your IELTS Membership Receipt', 'ielts-course-manager');
        $message = $this->get_receipt_email_message($user, $membership, $payment);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $attachments = array($pdf_path);
        
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Clean up temporary PDF file
        @unlink($pdf_path);
        
        return $sent;
    }
    
    /**
     * Get receipt email message
     */
    private function get_receipt_email_message($user, $membership, $payment) {
        $company_name = get_option('ielts_cm_company_name', 'IELTS Preparation Course');
        
        $message = '<html><body>';
        $message .= '<p>' . sprintf(__('Dear %s,', 'ielts-course-manager'), esc_html($user->display_name ?: $user->user_login)) . '</p>';
        $message .= '<p>' . __('Thank you for your payment. Please find your receipt attached to this email.', 'ielts-course-manager') . '</p>';
        $message .= '<p><strong>' . __('Membership Details:', 'ielts-course-manager') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li>' . __('Start Date:', 'ielts-course-manager') . ' ' . date('F j, Y', strtotime($membership->start_date)) . '</li>';
        $message .= '<li>' . __('End Date:', 'ielts-course-manager') . ' ' . date('F j, Y', strtotime($membership->end_date)) . '</li>';
        if ($payment) {
            $message .= '<li>' . __('Amount Paid:', 'ielts-course-manager') . ' ' . $payment->currency . ' ' . number_format($payment->amount, 2) . '</li>';
            $message .= '<li>' . __('Payment Date:', 'ielts-course-manager') . ' ' . date('F j, Y', strtotime($payment->payment_date)) . '</li>';
        }
        $message .= '</ul>';
        $message .= '<p>' . __('If you have any questions, please don\'t hesitate to contact us.', 'ielts-course-manager') . '</p>';
        $message .= '<p>' . __('Best regards,', 'ielts-course-manager') . '<br>' . esc_html($company_name) . '</p>';
        $message .= '</body></html>';
        
        return $message;
    }
}
