<?php
/**
 * Admin settings and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_MS_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Membership System',
            'Membership',
            'manage_options',
            'ielts-membership',
            array($this, 'settings_page'),
            'dashicons-id-alt',
            30
        );
        
        add_submenu_page(
            'ielts-membership',
            'Settings',
            'Settings',
            'manage_options',
            'ielts-membership',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'ielts-membership',
            'Members',
            'Members',
            'manage_options',
            'ielts-membership-members',
            array($this, 'members_page')
        );
        
        add_submenu_page(
            'ielts-membership',
            'Payments',
            'Payments',
            'manage_options',
            'ielts-membership-payments',
            array($this, 'payments_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // PayPal settings
        register_setting('ielts_ms_settings', 'ielts_ms_paypal_enabled');
        register_setting('ielts_ms_settings', 'ielts_ms_paypal_email');
        register_setting('ielts_ms_settings', 'ielts_ms_paypal_sandbox');
        
        // Stripe settings
        register_setting('ielts_ms_settings', 'ielts_ms_stripe_enabled');
        register_setting('ielts_ms_settings', 'ielts_ms_stripe_publishable_key');
        register_setting('ielts_ms_settings', 'ielts_ms_stripe_secret_key');
        register_setting('ielts_ms_settings', 'ielts_ms_stripe_webhook_secret');
        
        // General settings
        register_setting('ielts_ms_settings', 'ielts_ms_custom_login_enabled');
        register_setting('ielts_ms_settings', 'ielts_ms_login_page_id');
        register_setting('ielts_ms_settings', 'ielts_ms_account_page_id');
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('ielts_ms_settings_nonce');
            
            update_option('ielts_ms_paypal_enabled', isset($_POST['paypal_enabled']));
            update_option('ielts_ms_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('ielts_ms_paypal_sandbox', isset($_POST['paypal_sandbox']));
            
            update_option('ielts_ms_stripe_enabled', isset($_POST['stripe_enabled']));
            update_option('ielts_ms_stripe_publishable_key', sanitize_text_field($_POST['stripe_publishable_key']));
            update_option('ielts_ms_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key']));
            update_option('ielts_ms_stripe_webhook_secret', sanitize_text_field($_POST['stripe_webhook_secret']));
            
            update_option('ielts_ms_custom_login_enabled', isset($_POST['custom_login_enabled']));
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        $paypal_enabled = get_option('ielts_ms_paypal_enabled', true);
        $paypal_email = get_option('ielts_ms_paypal_email', '');
        $paypal_sandbox = get_option('ielts_ms_paypal_sandbox', false);
        
        $stripe_enabled = get_option('ielts_ms_stripe_enabled', true);
        $stripe_publishable_key = get_option('ielts_ms_stripe_publishable_key', '');
        $stripe_secret_key = get_option('ielts_ms_stripe_secret_key', '');
        $stripe_webhook_secret = get_option('ielts_ms_stripe_webhook_secret', '');
        
        $custom_login_enabled = get_option('ielts_ms_custom_login_enabled', true);
        
        ?>
        <div class="wrap">
            <h1>Membership System Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_ms_settings_nonce'); ?>
                
                <h2>PayPal Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable PayPal</th>
                        <td>
                            <label>
                                <input type="checkbox" name="paypal_enabled" value="1" <?php checked($paypal_enabled); ?>>
                                Enable PayPal payments
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Email</th>
                        <td>
                            <input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text">
                            <p class="description">Your PayPal business email address</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sandbox Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="paypal_sandbox" value="1" <?php checked($paypal_sandbox); ?>>
                                Enable PayPal sandbox for testing
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>Stripe Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Stripe</th>
                        <td>
                            <label>
                                <input type="checkbox" name="stripe_enabled" value="1" <?php checked($stripe_enabled); ?>>
                                Enable Stripe payments
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Publishable Key</th>
                        <td>
                            <input type="text" name="stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable_key); ?>" class="regular-text">
                            <p class="description">Your Stripe publishable key</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Secret Key</th>
                        <td>
                            <input type="password" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>" class="regular-text">
                            <p class="description">Your Stripe secret key</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook Secret</th>
                        <td>
                            <input type="text" name="stripe_webhook_secret" value="<?php echo esc_attr($stripe_webhook_secret); ?>" class="regular-text">
                            <p class="description">Webhook endpoint: <?php echo admin_url('admin-ajax.php?action=ielts_ms_stripe_webhook'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2>Login Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Custom Login</th>
                        <td>
                            <label>
                                <input type="checkbox" name="custom_login_enabled" value="1" <?php checked($custom_login_enabled); ?>>
                                Replace WordPress login with custom login page
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Login Page</th>
                        <td>
                            <?php
                            $login_page = get_page_by_path('membership-login');
                            if ($login_page) {
                                echo '<a href="' . get_permalink($login_page) . '" target="_blank">View Login Page</a>';
                            } else {
                                echo 'Login page not found';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Account Page</th>
                        <td>
                            <?php
                            $account_page = get_page_by_path('my-account');
                            if ($account_page) {
                                echo '<a href="' . get_permalink($account_page) . '" target="_blank">View Account Page</a>';
                            } else {
                                echo 'Account page not found';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Members page
     */
    public function members_page() {
        $membership = new IELTS_MS_Membership();
        $memberships = $membership->get_all_memberships();
        
        ?>
        <div class="wrap">
            <h1>Members</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memberships)): ?>
                        <tr>
                            <td colspan="6">No memberships found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($memberships as $member): 
                            $user = get_userdata($member->user_id);
                            if (!$user) continue;
                            
                            $now = time();
                            $end = strtotime($member->end_date);
                            $days_remaining = $end > $now ? ceil(($end - $now) / (60 * 60 * 24)) : 0;
                            ?>
                            <tr>
                                <td><?php echo esc_html($user->user_login); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($member->status); ?>">
                                        <?php echo ucfirst($member->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($member->start_date)); ?></td>
                                <td><?php echo date('M j, Y', strtotime($member->end_date)); ?></td>
                                <td><?php echo $days_remaining; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Payments page
     */
    public function payments_page() {
        global $wpdb;
        $table = IELTS_MS_Database::get_payments_table();
        
        $payments = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY payment_date DESC LIMIT 100"
        );
        
        ?>
        <div class="wrap">
            <h1>Payments</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="7">No payments found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): 
                            $user = get_userdata($payment->user_id);
                            ?>
                            <tr>
                                <td><?php echo date('M j, Y H:i', strtotime($payment->payment_date)); ?></td>
                                <td><?php echo $user ? esc_html($user->user_login) : 'N/A'; ?></td>
                                <td><?php echo '$' . number_format($payment->amount, 2); ?> <?php echo esc_html($payment->currency); ?></td>
                                <td><?php echo ucfirst($payment->payment_type); ?></td>
                                <td><?php echo ucfirst($payment->payment_method); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($payment->payment_status); ?>">
                                        <?php echo ucfirst($payment->payment_status); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($payment->transaction_id ?: 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
