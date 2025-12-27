<?php
/**
 * Account page template
 */

if (!defined('ABSPATH')) {
    exit;
}

$has_membership = $membership->has_active_membership($user_id);
$is_expired = $membership->is_expired($user_id);
$days_remaining = $membership->get_days_remaining($user_id);
$pricing_options = IELTS_MS_Payment_Gateway::get_pricing_options();

// Handle payment status messages
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
?>

<div class="ielts-ms-account-wrapper">
    
    <?php if ($payment_status === 'success'): ?>
        <div class="ielts-ms-alert ielts-ms-alert-success">
            <?php _e('Payment successful! Your membership has been activated/extended.', 'ielts-membership-system'); ?>
        </div>
    <?php elseif ($payment_status === 'cancelled'): ?>
        <div class="ielts-ms-alert ielts-ms-alert-warning">
            <?php _e('Payment was cancelled.', 'ielts-membership-system'); ?>
        </div>
    <?php endif; ?>
    
    <div class="ielts-ms-account-header">
        <h2><?php _e('My Account', 'ielts-membership-system'); ?></h2>
        <p><?php printf(__('Welcome back, %s!', 'ielts-membership-system'), esc_html($user->display_name)); ?></p>
    </div>
    
    <!-- Membership Status -->
    <div class="ielts-ms-section">
        <h3><?php _e('Membership Status', 'ielts-membership-system'); ?></h3>
        
        <?php if ($has_membership): ?>
            <div class="ielts-ms-membership-card active">
                <div class="membership-status">
                    <span class="status-badge active"><?php _e('Active', 'ielts-membership-system'); ?></span>
                </div>
                <div class="membership-details">
                    <p><strong><?php _e('Access Expires:', 'ielts-membership-system'); ?></strong> 
                        <?php echo date('F j, Y', strtotime($user_membership->end_date)); ?>
                    </p>
                    <p><strong><?php _e('Days Remaining:', 'ielts-membership-system'); ?></strong> 
                        <?php echo $days_remaining; ?>
                    </p>
                </div>
            </div>
        <?php elseif ($is_expired): ?>
            <div class="ielts-ms-membership-card expired">
                <div class="membership-status">
                    <span class="status-badge expired"><?php _e('Expired', 'ielts-membership-system'); ?></span>
                </div>
                <div class="membership-details">
                    <p><?php _e('Your membership expired on:', 'ielts-membership-system'); ?> 
                        <?php echo date('F j, Y', strtotime($user_membership->end_date)); ?>
                    </p>
                    <p><?php _e('Renew your membership below to continue accessing courses.', 'ielts-membership-system'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="ielts-ms-membership-card no-membership">
                <p><?php _e('You do not have an active membership.', 'ielts-membership-system'); ?></p>
                <p><?php _e('Purchase a membership below to access all IELTS preparation courses.', 'ielts-membership-system'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Purchase/Extend Membership -->
    <div class="ielts-ms-section">
        <h3><?php echo ($has_membership || $is_expired) ? __('Extend Membership', 'ielts-membership-system') : __('Purchase Membership', 'ielts-membership-system'); ?></h3>
        
        <div class="ielts-ms-pricing-grid">
            <?php if (!$has_membership && !$is_expired): ?>
                <!-- New membership option -->
                <div class="pricing-card">
                    <h4><?php echo esc_html($pricing_options['new_90']['label']); ?></h4>
                    <div class="price"><?php echo '$' . number_format($pricing_options['new_90']['price'], 2); ?> USD</div>
                    <p><?php _e('Full access to all IELTS courses', 'ielts-membership-system'); ?></p>
                    <button class="ielts-ms-btn ielts-ms-btn-primary purchase-btn" 
                            data-plan="new_90" 
                            data-amount="<?php echo $pricing_options['new_90']['price']; ?>"
                            data-days="<?php echo $pricing_options['new_90']['days']; ?>"
                            data-type="new">
                        <?php _e('Purchase', 'ielts-membership-system'); ?>
                    </button>
                </div>
            <?php else: ?>
                <!-- Extension options -->
                <div class="pricing-card">
                    <h4><?php echo esc_html($pricing_options['extend_7']['label']); ?></h4>
                    <div class="price"><?php echo '$' . number_format($pricing_options['extend_7']['price'], 2); ?> USD</div>
                    <button class="ielts-ms-btn ielts-ms-btn-primary purchase-btn" 
                            data-plan="extend_7" 
                            data-amount="<?php echo $pricing_options['extend_7']['price']; ?>"
                            data-days="<?php echo $pricing_options['extend_7']['days']; ?>"
                            data-type="extension">
                        <?php _e('Extend', 'ielts-membership-system'); ?>
                    </button>
                </div>
                
                <div class="pricing-card recommended">
                    <div class="recommended-badge"><?php _e('Recommended', 'ielts-membership-system'); ?></div>
                    <h4><?php echo esc_html($pricing_options['extend_30']['label']); ?></h4>
                    <div class="price"><?php echo '$' . number_format($pricing_options['extend_30']['price'], 2); ?> USD</div>
                    <button class="ielts-ms-btn ielts-ms-btn-primary purchase-btn" 
                            data-plan="extend_30" 
                            data-amount="<?php echo $pricing_options['extend_30']['price']; ?>"
                            data-days="<?php echo $pricing_options['extend_30']['days']; ?>"
                            data-type="extension">
                        <?php _e('Extend', 'ielts-membership-system'); ?>
                    </button>
                </div>
                
                <div class="pricing-card">
                    <h4><?php echo esc_html($pricing_options['extend_90']['label']); ?></h4>
                    <div class="price"><?php echo '$' . number_format($pricing_options['extend_90']['price'], 2); ?> USD</div>
                    <p><?php _e('Best value!', 'ielts-membership-system'); ?></p>
                    <button class="ielts-ms-btn ielts-ms-btn-primary purchase-btn" 
                            data-plan="extend_90" 
                            data-amount="<?php echo $pricing_options['extend_90']['price']; ?>"
                            data-days="<?php echo $pricing_options['extend_90']['days']; ?>"
                            data-type="extension">
                        <?php _e('Extend', 'ielts-membership-system'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Gateway Selection -->
        <div id="payment-gateway-selector" class="ielts-ms-gateway-selector" style="display: none;">
            <h4><?php _e('Select Payment Method', 'ielts-membership-system'); ?></h4>
            <div class="gateway-buttons">
                <?php if (get_option('ielts_ms_paypal_enabled', true)): ?>
                    <button class="gateway-btn" data-gateway="paypal">
                        <img src="<?php echo IELTS_MS_PLUGIN_URL; ?>assets/images/paypal-logo.png" alt="PayPal" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <span style="display:none;">PayPal</span>
                    </button>
                <?php endif; ?>
                
                <?php if (get_option('ielts_ms_stripe_enabled', true)): ?>
                    <button class="gateway-btn" data-gateway="stripe">
                        <img src="<?php echo IELTS_MS_PLUGIN_URL; ?>assets/images/stripe-logo.png" alt="Stripe"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <span style="display:none;">Stripe</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hidden PayPal form -->
        <form id="paypal-form" method="post" action="" style="display: none;">
            <input type="hidden" name="cmd" value="">
        </form>
    </div>
    
    <!-- Account Settings -->
    <div class="ielts-ms-section">
        <h3><?php _e('Account Settings', 'ielts-membership-system'); ?></h3>
        
        <div class="ielts-ms-settings-grid">
            <!-- Update Email -->
            <div class="settings-card">
                <h4><?php _e('Change Email', 'ielts-membership-system'); ?></h4>
                <form id="ielts-ms-update-email-form" class="ielts-ms-form">
                    <div class="ielts-ms-form-group">
                        <label><?php _e('Current Email:', 'ielts-membership-system'); ?></label>
                        <p><?php echo esc_html($user->user_email); ?></p>
                    </div>
                    
                    <div class="ielts-ms-form-group">
                        <label for="new_email"><?php _e('New Email', 'ielts-membership-system'); ?></label>
                        <input type="email" id="new_email" name="new_email" required>
                    </div>
                    
                    <div class="ielts-ms-form-group">
                        <label for="email_password"><?php _e('Current Password', 'ielts-membership-system'); ?></label>
                        <input type="password" id="email_password" name="password" required>
                    </div>
                    
                    <button type="submit" class="ielts-ms-btn ielts-ms-btn-secondary">
                        <?php _e('Update Email', 'ielts-membership-system'); ?>
                    </button>
                    
                    <div class="ielts-ms-message"></div>
                </form>
            </div>
            
            <!-- Update Password -->
            <div class="settings-card">
                <h4><?php _e('Change Password', 'ielts-membership-system'); ?></h4>
                <form id="ielts-ms-update-password-form" class="ielts-ms-form">
                    <div class="ielts-ms-form-group">
                        <label for="current_password"><?php _e('Current Password', 'ielts-membership-system'); ?></label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="ielts-ms-form-group">
                        <label for="new_password"><?php _e('New Password', 'ielts-membership-system'); ?></label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small><?php _e('Minimum 8 characters', 'ielts-membership-system'); ?></small>
                    </div>
                    
                    <div class="ielts-ms-form-group">
                        <label for="confirm_new_password"><?php _e('Confirm New Password', 'ielts-membership-system'); ?></label>
                        <input type="password" id="confirm_new_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="ielts-ms-btn ielts-ms-btn-secondary">
                        <?php _e('Update Password', 'ielts-membership-system'); ?>
                    </button>
                    
                    <div class="ielts-ms-message"></div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Payment History -->
    <?php if (!empty($payments)): ?>
    <div class="ielts-ms-section">
        <h3><?php _e('Payment History', 'ielts-membership-system'); ?></h3>
        
        <table class="ielts-ms-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'ielts-membership-system'); ?></th>
                    <th><?php _e('Amount', 'ielts-membership-system'); ?></th>
                    <th><?php _e('Type', 'ielts-membership-system'); ?></th>
                    <th><?php _e('Method', 'ielts-membership-system'); ?></th>
                    <th><?php _e('Status', 'ielts-membership-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($payment->payment_date)); ?></td>
                        <td><?php echo '$' . number_format($payment->amount, 2); ?> <?php echo esc_html($payment->currency); ?></td>
                        <td><?php echo ucfirst($payment->payment_type); ?></td>
                        <td><?php echo ucfirst($payment->payment_method); ?></td>
                        <td>
                            <span class="status-badge <?php echo $payment->payment_status; ?>">
                                <?php echo ucfirst($payment->payment_status); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Logout -->
    <div class="ielts-ms-section">
        <a href="<?php echo wp_logout_url(get_permalink(get_page_by_path('membership-login'))); ?>" class="ielts-ms-btn ielts-ms-btn-link">
            <?php _e('Logout', 'ielts-membership-system'); ?>
        </a>
    </div>
</div>
