<?php
/**
 * Hybrid Site Settings functionality
 * Manages hybrid site mode with configurable access code pricing and course extensions
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Hybrid_Settings {
    
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu for hybrid settings
     */
    public function add_admin_menu() {
        // Only show Hybrid Settings menu if hybrid mode is enabled
        if (!get_option('ielts_cm_hybrid_site_enabled', false)) {
            return;
        }
        
        // Main hybrid settings menu
        add_menu_page(
            __('Hybrid site settings', 'ielts-course-manager'),
            __('Hybrid site settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-hybrid-settings',
            array($this, 'settings_page'),
            'dashicons-admin-site',
            32
        );
        
        // Settings submenu (same as parent)
        add_submenu_page(
            'ielts-hybrid-settings',
            __('Hybrid Settings', 'ielts-course-manager'),
            __('Settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-hybrid-settings',
            array($this, 'settings_page')
        );
        
        // Documentation submenu
        add_submenu_page(
            'ielts-hybrid-settings',
            __('Hybrid Site Documentation', 'ielts-course-manager'),
            __('Documentation', 'ielts-course-manager'),
            'manage_options',
            'ielts-hybrid-documentation',
            array($this, 'documentation_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ielts_hybrid_settings', 'ielts_cm_access_code_pricing_tiers');
        register_setting('ielts_hybrid_settings', 'ielts_cm_extension_pricing');
        register_setting('ielts_hybrid_settings', 'ielts_cm_stripe_enabled');
        register_setting('ielts_hybrid_settings', 'ielts_cm_stripe_publishable_key');
        register_setting('ielts_hybrid_settings', 'ielts_cm_stripe_secret_key');
        register_setting('ielts_hybrid_settings', 'ielts_cm_stripe_webhook_secret');
        register_setting('ielts_hybrid_settings', 'ielts_cm_paypal_enabled');
        register_setting('ielts_hybrid_settings', 'ielts_cm_paypal_client_id');
        register_setting('ielts_hybrid_settings', 'ielts_cm_paypal_secret');
        register_setting('ielts_hybrid_settings', 'ielts_cm_paypal_address');
    }
    
    /**
     * Hybrid Site Settings page
     */
    public function settings_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Save settings if form submitted
        if (isset($_POST['ielts_cm_hybrid_settings_nonce']) && wp_verify_nonce($_POST['ielts_cm_hybrid_settings_nonce'], 'ielts_cm_hybrid_settings')) {
            $errors = array();
            
            // Save Stripe settings for hybrid mode
            update_option('ielts_cm_stripe_enabled', isset($_POST['ielts_cm_stripe_enabled']) ? 1 : 0);
            update_option('ielts_cm_stripe_publishable_key', sanitize_text_field($_POST['ielts_cm_stripe_publishable_key']));
            update_option('ielts_cm_stripe_secret_key', sanitize_text_field($_POST['ielts_cm_stripe_secret_key']));
            update_option('ielts_cm_stripe_webhook_secret', sanitize_text_field($_POST['ielts_cm_stripe_webhook_secret']));
            
            // Save PayPal settings for hybrid mode
            update_option('ielts_cm_paypal_enabled', isset($_POST['ielts_cm_paypal_enabled']) ? 1 : 0);
            update_option('ielts_cm_paypal_client_id', sanitize_text_field($_POST['ielts_cm_paypal_client_id']));
            update_option('ielts_cm_paypal_secret', sanitize_text_field($_POST['ielts_cm_paypal_secret']));
            
            // Validate and save PayPal address
            if (isset($_POST['ielts_cm_paypal_address'])) {
                $paypal_address = sanitize_email($_POST['ielts_cm_paypal_address']);
                if (!empty($_POST['ielts_cm_paypal_address']) && !is_email($paypal_address)) {
                    $errors[] = __('Invalid PayPal email address. Please enter a valid email address.', 'ielts-course-manager');
                    $paypal_address = '';
                }
                update_option('ielts_cm_paypal_address', $paypal_address);
            }
            
            // Save access code pricing tiers
            $pricing_tiers = array();
            if (isset($_POST['pricing_tiers']) && is_array($_POST['pricing_tiers'])) {
                foreach ($_POST['pricing_tiers'] as $tier) {
                    $quantity = intval($tier['quantity']);
                    $price = floatval($tier['price']);
                    
                    if ($quantity > 0 && $price > 0) {
                        $pricing_tiers[] = array(
                            'quantity' => $quantity,
                            'price' => $price
                        );
                    }
                }
                
                // Sort by quantity
                usort($pricing_tiers, function($a, $b) {
                    return $a['quantity'] - $b['quantity'];
                });
            }
            
            // Ensure we have at least some default pricing tiers
            if (empty($pricing_tiers)) {
                $pricing_tiers = $this->get_default_pricing_tiers();
                $errors[] = __('At least one pricing tier is required. Default pricing tiers have been set.', 'ielts-course-manager');
            }
            
            update_option('ielts_cm_access_code_pricing_tiers', $pricing_tiers);
            
            // Save course extension pricing
            $extension_pricing = array();
            if (isset($_POST['extension_1_week'])) {
                $price = floatval($_POST['extension_1_week']);
                if ($price <= 0) {
                    $errors[] = __('1 Week Extension must have a price greater than $0.', 'ielts-course-manager');
                    $price = 10.00;
                }
                $extension_pricing['1_week'] = $price;
            }
            if (isset($_POST['extension_1_month'])) {
                $price = floatval($_POST['extension_1_month']);
                if ($price <= 0) {
                    $errors[] = __('1 Month Extension must have a price greater than $0.', 'ielts-course-manager');
                    $price = 15.00;
                }
                $extension_pricing['1_month'] = $price;
            }
            if (isset($_POST['extension_3_months'])) {
                $price = floatval($_POST['extension_3_months']);
                if ($price <= 0) {
                    $errors[] = __('3 Months Extension must have a price greater than $0.', 'ielts-course-manager');
                    $price = 20.00;
                }
                $extension_pricing['3_months'] = $price;
            }
            update_option('ielts_cm_extension_pricing', $extension_pricing);
            
            if (!empty($errors)) {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>' . __('Warning:', 'ielts-course-manager') . '</strong></p><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul><p>' . __('Please review and update the settings.', 'ielts-course-manager') . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved.', 'ielts-course-manager') . '</p></div>';
            }
        }
        
        // Get current settings
        $stripe_enabled = get_option('ielts_cm_stripe_enabled', false);
        $stripe_publishable = get_option('ielts_cm_stripe_publishable_key', '');
        $stripe_secret = get_option('ielts_cm_stripe_secret_key', '');
        $stripe_webhook_secret = get_option('ielts_cm_stripe_webhook_secret', '');
        $paypal_enabled = get_option('ielts_cm_paypal_enabled', false);
        $paypal_client_id = get_option('ielts_cm_paypal_client_id', '');
        $paypal_secret = get_option('ielts_cm_paypal_secret', '');
        $paypal_address = get_option('ielts_cm_paypal_address', '');
        
        // Get pricing tiers
        $pricing_tiers = get_option('ielts_cm_access_code_pricing_tiers', $this->get_default_pricing_tiers());
        
        // Get extension pricing with defaults
        $extension_pricing = get_option('ielts_cm_extension_pricing', array(
            '1_week' => 10.00,
            '1_month' => 15.00,
            '3_months' => 20.00
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('Hybrid Site Settings', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('What is Hybrid Site Mode?', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('Hybrid site mode allows multiple companies to operate on the same site. Partners purchase access codes for their students, and students can purchase course extensions. This page configures payment settings for both partners and students.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <?php
            // Display webhook configuration status
            if ($stripe_enabled) {
                $webhook_configured = !empty($stripe_webhook_secret);
                $api_keys_configured = !empty($stripe_publishable) && !empty($stripe_secret);
                
                if (!$webhook_configured || !$api_keys_configured) {
                    echo '<div class="notice notice-warning">';
                    echo '<p><strong><span class="screen-reader-text">Warning: </span>⚠️ ' . __('Stripe Configuration Incomplete', 'ielts-course-manager') . '</strong></p>';
                    echo '<ul style="list-style-type: disc; margin-left: 20px;">';
                    
                    if (!$api_keys_configured) {
                        echo '<li>' . __('Stripe API keys (Publishable and Secret) are not configured. Payments will not work.', 'ielts-course-manager') . '</li>';
                    }
                    
                    if (!$webhook_configured) {
                        echo '<li><strong>' . __('Webhook Secret is not configured. Access codes and payments will NOT be processed after purchase!', 'ielts-course-manager') . '</strong></li>';
                        echo '<li>' . __('To fix: Configure a webhook in Stripe Dashboard with the URL shown below, then paste the webhook secret here.', 'ielts-course-manager') . '</li>';
                    }
                    
                    echo '</ul>';
                    echo '</div>';
                } else {
                    echo '<div class="notice notice-success">';
                    echo '<p><strong><span class="screen-reader-text">Success: </span>✅ ' . __('Stripe appears to be configured correctly', 'ielts-course-manager') . '</strong></p>';
                    echo '<p style="margin: 5px 0;"><small>' . __('If payments are not working, check the error logs for webhook issues.', 'ielts-course-manager') . '</small></p>';
                    echo '</div>';
                }
            }
            ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_cm_hybrid_settings', 'ielts_cm_hybrid_settings_nonce'); ?>
                
                <h2><?php _e('Payment Gateway Settings', 'ielts-course-manager'); ?></h2>
                <p class="description"><?php _e('Configure Stripe and PayPal for processing payments from both partners (for access codes) and students (for course extensions).', 'ielts-course-manager'); ?></p>
                
                <h3><?php _e('Stripe Settings', 'ielts-course-manager'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Stripe', 'ielts-course-manager'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="ielts_cm_stripe_enabled" value="1" <?php checked($stripe_enabled, true); ?>>
                                    <?php _e('Enable Stripe payments', 'ielts-course-manager'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Publishable Key', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable); ?>" class="regular-text" placeholder="pk_test_...">
                            <p class="description"><?php _e('Your Stripe publishable API key (starts with pk_)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Secret Key', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="password" name="ielts_cm_stripe_secret_key" value="<?php echo esc_attr($stripe_secret); ?>" class="regular-text" placeholder="sk_test_...">
                            <p class="description"><?php _e('Your Stripe secret API key (starts with sk_)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Webhook Secret', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="password" name="ielts_cm_stripe_webhook_secret" value="<?php echo esc_attr($stripe_webhook_secret); ?>" class="regular-text" placeholder="whsec_...">
                            <p class="description"><?php _e('Your Stripe webhook signing secret (starts with whsec_)', 'ielts-course-manager'); ?></p>
                            <div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #0073aa; border-radius: 4px;">
                                <strong><?php _e('Webhook Endpoint URL:', 'ielts-course-manager'); ?></strong><br>
                                <code style="background: #fff; padding: 5px; display: inline-block; margin-top: 5px;"><?php echo esc_html(rest_url('ielts-cm/v1/stripe-webhook')); ?></code><br>
                                <small style="color: #666;"><?php _e('Configure this URL in your Stripe Dashboard → Developers → Webhooks. Listen for event: payment_intent.succeeded', 'ielts-course-manager'); ?></small>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('PayPal Settings', 'ielts-course-manager'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable PayPal', 'ielts-course-manager'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="ielts_cm_paypal_enabled" value="1" <?php checked($paypal_enabled, true); ?>>
                                    <?php _e('Enable PayPal payments', 'ielts-course-manager'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Client ID', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="text" name="ielts_cm_paypal_client_id" value="<?php echo esc_attr($paypal_client_id); ?>" class="regular-text">
                            <p class="description"><?php _e('Your PayPal REST API Client ID', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Secret', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="password" name="ielts_cm_paypal_secret" value="<?php echo esc_attr($paypal_secret); ?>" class="regular-text">
                            <p class="description"><?php _e('Your PayPal REST API Secret', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('PayPal Email Address', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="email" name="ielts_cm_paypal_address" value="<?php echo esc_attr($paypal_address); ?>" class="regular-text" placeholder="your-business@example.com">
                            <p class="description"><?php _e('Your PayPal business email address for receiving payments', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Access Code Pricing (for Partners)', 'ielts-course-manager'); ?></h2>
                <p class="description"><?php _e('Set the prices partners pay to purchase access codes in bulk. Partners will be able to purchase these code packages and distribute them to their students. You can add up to 10 different pricing tiers.', 'ielts-course-manager'); ?></p>
                
                <div id="pricing-tiers-container">
                    <?php foreach ($pricing_tiers as $index => $tier): ?>
                        <div class="pricing-tier-row" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="display: flex; gap: 20px; align-items: center;">
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px;"><strong><?php _e('Number of Codes', 'ielts-course-manager'); ?></strong></label>
                                    <input type="number" 
                                           name="pricing_tiers[<?php echo $index; ?>][quantity]" 
                                           value="<?php echo esc_attr($tier['quantity']); ?>" 
                                           min="1" 
                                           step="1" 
                                           class="regular-text" 
                                           placeholder="e.g., 50">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px;"><strong><?php _e('Price (USD)', 'ielts-course-manager'); ?></strong></label>
                                    <input type="number" 
                                           name="pricing_tiers[<?php echo $index; ?>][price]" 
                                           value="<?php echo esc_attr($tier['price']); ?>" 
                                           min="0.01" 
                                           step="0.01" 
                                           class="regular-text" 
                                           placeholder="e.g., 50.00">
                                </div>
                                <div style="padding-top: 25px;">
                                    <button type="button" class="button remove-tier-btn" data-tier-index="<?php echo $index; ?>"><?php _e('Remove', 'ielts-course-manager'); ?></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p>
                    <button type="button" id="add-tier-btn" class="button" style="margin-top: 10px;"><?php _e('Add Pricing Tier', 'ielts-course-manager'); ?></button>
                    <span class="description" style="margin-left: 10px;"><?php _e('Maximum 10 pricing tiers', 'ielts-course-manager'); ?></span>
                </p>
                
                <script>
                jQuery(document).ready(function($) {
                    var tierIndex = <?php echo count($pricing_tiers); ?>;
                    var maxTiers = 10;
                    
                    // Add new pricing tier
                    $('#add-tier-btn').on('click', function() {
                        if ($('.pricing-tier-row').length >= maxTiers) {
                            alert('<?php _e('Maximum 10 pricing tiers allowed.', 'ielts-course-manager'); ?>');
                            return;
                        }
                        
                        var newTier = '<div class="pricing-tier-row" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">' +
                            '<div style="display: flex; gap: 20px; align-items: center;">' +
                                '<div style="flex: 1;">' +
                                    '<label style="display: block; margin-bottom: 5px;"><strong><?php _e('Number of Codes', 'ielts-course-manager'); ?></strong></label>' +
                                    '<input type="number" name="pricing_tiers[' + tierIndex + '][quantity]" value="" min="1" step="1" class="regular-text" placeholder="e.g., 50">' +
                                '</div>' +
                                '<div style="flex: 1;">' +
                                    '<label style="display: block; margin-bottom: 5px;"><strong><?php _e('Price (USD)', 'ielts-course-manager'); ?></strong></label>' +
                                    '<input type="number" name="pricing_tiers[' + tierIndex + '][price]" value="" min="0.01" step="0.01" class="regular-text" placeholder="e.g., 50.00">' +
                                '</div>' +
                                '<div style="padding-top: 25px;">' +
                                    '<button type="button" class="button remove-tier-btn"><?php _e('Remove', 'ielts-course-manager'); ?></button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                        
                        $('#pricing-tiers-container').append(newTier);
                        tierIndex++;
                    });
                    
                    // Remove pricing tier
                    $(document).on('click', '.remove-tier-btn', function() {
                        if ($('.pricing-tier-row').length <= 1) {
                            alert('<?php _e('At least one pricing tier is required.', 'ielts-course-manager'); ?>');
                            return;
                        }
                        $(this).closest('.pricing-tier-row').remove();
                    });
                });
                </script>
                
                <h2><?php _e('Course Extension Pricing (for Students)', 'ielts-course-manager'); ?></h2>
                <p class="description"><?php _e('Set pricing for course extensions that students can purchase. These allow students to extend their course access. Note: The labels below are for marketing purposes - the actual durations granted are 5 days, 10 days, and 30 days respectively.', 'ielts-course-manager'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('1 Week Extension (5 Days)', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" name="extension_1_week" value="<?php echo esc_attr($extension_pricing['1_week'] ?? 10.00); ?>" class="regular-text">
                            <p class="description"><?php _e('Price in USD for 5-day extension (default: $10)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('1 Month Extension (10 Days)', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" name="extension_1_month" value="<?php echo esc_attr($extension_pricing['1_month'] ?? 15.00); ?>" class="regular-text">
                            <p class="description"><?php _e('Price in USD for 10-day extension (default: $15)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('3 Months Extension (30 Days)', 'ielts-course-manager'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" name="extension_3_months" value="<?php echo esc_attr($extension_pricing['3_months'] ?? 20.00); ?>" class="regular-text">
                            <p class="description"><?php _e('Price in USD for 30-day extension (default: $20)', 'ielts-course-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Documentation page
     */
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Hybrid Site Documentation', 'ielts-course-manager'); ?></h1>
            
            <div class="card" style="max-width: 900px;">
                <h2><?php _e('What is Hybrid Site Mode?', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Hybrid site mode enables your WordPress installation to serve multiple companies simultaneously. Each partner organization can purchase access codes for their students, while individual students can purchase course extensions.', 'ielts-course-manager'); ?></p>
                
                <h3><?php _e('Key Features', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('Multiple companies can coexist on one site', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Partners purchase access codes in bulk for their students', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Partner admins only see codes and users from their organization', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Students can purchase course extensions (5, 10, or 30 days)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Separate payment processing for partners and students', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('How to Configure', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><strong><?php _e('Enable Hybrid Mode:', 'ielts-course-manager'); ?></strong> <?php _e('Go to IELTS Courses → Settings → Site Configuration and select "Hybrid Site"', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Configure Payment Gateways:', 'ielts-course-manager'); ?></strong> <?php _e('Set up Stripe and/or PayPal credentials in the Hybrid Site Settings', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Set Access Code Pricing:', 'ielts-course-manager'); ?></strong> <?php _e('Define pricing tiers for partners to purchase access codes in bulk', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Set Extension Pricing:', 'ielts-course-manager'); ?></strong> <?php _e('Configure pricing for students to extend their course access', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Partner Workflow', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Partner selects a pricing tier and purchases access codes', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Codes are automatically created and associated with the partner organization', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Partner receives codes via email', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Partner distributes codes to their students', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Students redeem codes to access courses', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Student Workflow', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Student logs into their account and visits My Account page', 'ielts-course-manager'); ?></li>
                    <li><?php _e('If enrollment is nearing expiry, student can extend course access', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Student selects extension duration and completes payment', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Course access is extended automatically', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Important Notes', 'ielts-course-manager'); ?></h3>
                <ul>
                    <li><?php _e('Hybrid mode does NOT affect existing single-company sites', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Partner admins cannot extend student access or manipulate enrollments', 'ielts-course-manager'); ?></li>
                    <li><?php _e('All payments are processed through your configured payment gateways', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Extension pricing labels are for marketing - actual durations are 5, 10, and 30 days', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get default pricing tiers
     */
    private function get_default_pricing_tiers() {
        return array(
            array('quantity' => 1, 'price' => 3.00),
            array('quantity' => 5, 'price' => 12.00),
            array('quantity' => 10, 'price' => 20.00),
            array('quantity' => 25, 'price' => 45.00),
            array('quantity' => 50, 'price' => 80.00),
            array('quantity' => 100, 'price' => 150.00),
            array('quantity' => 150, 'price' => 210.00),
            array('quantity' => 200, 'price' => 260.00),
        );
    }
}
