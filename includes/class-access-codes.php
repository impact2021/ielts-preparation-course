<?php
/**
 * Partner Dashboard & Access Code Management System
 * 
 * Complete partner dashboard implementation with invite code generation,
 * user management, and course enrollment functionality.
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Access_Codes {
    
    const STATUS_ACTIVE = 'active';
    const STATUS_USED = 'used';
    const STATUS_EXPIRED = 'expired';
    
    /**
     * Access code membership types - separate from paid memberships
     * These are created when Access Code Membership system is enabled
     */
    const ACCESS_CODE_MEMBERSHIP_TYPES = array(
        'access_academic_module' => 'Academic Module (Access Code)',
        'access_general_module' => 'General Training Module (Access Code)',
        'access_general_english' => 'General English (Access Code)'
    );
    
    private $course_groups = array(
        'academic_module' => 'Academic Module',
        'general_module' => 'General Training Module',
        'general_english' => 'General English'
    );
    
    private $course_group_descriptions = array(
        'academic_module' => 'Includes courses with category slugs: academic, english, academic-practice-tests',
        'general_module' => 'Includes courses with category slugs: general, english, general-practice-tests',
        'general_english' => 'Includes courses with category slug: english only'
    );
    
    public function __construct() {
        $this->create_partner_admin_role();
        $this->create_access_code_membership_roles();
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Partner dashboard shortcode
        add_shortcode('iw_partner_dashboard', array($this, 'partner_dashboard_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_iw_create_invite', array($this, 'ajax_create_invite'));
        add_action('wp_ajax_iw_create_user_manually', array($this, 'ajax_create_user_manually'));
        add_action('wp_ajax_iw_revoke_student', array($this, 'ajax_revoke_student'));
        add_action('wp_ajax_iw_delete_code', array($this, 'ajax_delete_code'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    public function create_partner_admin_role() {
        $role = get_role('partner_admin');
        if (!$role) {
            add_role('partner_admin', 'Partner Admin', array(
                'read' => true,
                'manage_partner_invites' => true
            ));
        }
    }
    
    /**
     * Create custom WordPress roles for access code membership types
     * Only creates roles when Access Code Membership system is enabled
     */
    public function create_access_code_membership_roles() {
        // Only create roles if access code system is enabled
        if (!get_option('ielts_cm_access_code_enabled', false)) {
            return;
        }
        
        // Get subscriber capabilities as base
        $subscriber = get_role('subscriber');
        if (!$subscriber) {
            return;
        }
        
        $base_caps = $subscriber->capabilities;
        
        // Create role for each access code membership type if it doesn't exist
        foreach (self::ACCESS_CODE_MEMBERSHIP_TYPES as $role_slug => $role_name) {
            if (!get_role($role_slug)) {
                add_role($role_slug, $role_name, $base_caps);
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Partner Dashboard',
            'Partner Dashboard',
            'manage_options',
            'ielts-partner-dashboard',
            array($this, 'admin_dashboard_page'),
            'dashicons-groups',
            31
        );
        
        add_submenu_page(
            'ielts-partner-dashboard',
            'How It Works',
            'How It Works',
            'manage_options',
            'ielts-partner-documentation',
            array($this, 'documentation_page')
        );
        
        add_submenu_page(
            'ielts-partner-dashboard',
            'Partner Settings',
            'Settings',
            'manage_options',
            'ielts-partner-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('ielts_partner_settings', 'iw_default_invite_days');
        register_setting('ielts_partner_settings', 'iw_max_students_per_partner');
        register_setting('ielts_partner_settings', 'iw_expiry_action');
        register_setting('ielts_partner_settings', 'iw_notify_days_before');
        register_setting('ielts_partner_settings', 'iw_redirect_after_creation');
        register_setting('ielts_partner_settings', 'iw_login_page_url');
        register_setting('ielts_partner_settings', 'iw_registration_page_url');
    }
    
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (isset($_POST['submit']) && check_admin_referer('iw_partner_settings')) {
            update_option('iw_default_invite_days', absint($_POST['iw_default_invite_days']));
            update_option('iw_max_students_per_partner', absint($_POST['iw_max_students_per_partner']));
            update_option('iw_expiry_action', sanitize_text_field($_POST['iw_expiry_action']));
            update_option('iw_notify_days_before', absint($_POST['iw_notify_days_before']));
            update_option('iw_redirect_after_creation', esc_url_raw($_POST['iw_redirect_after_creation']));
            update_option('iw_login_page_url', esc_url_raw($_POST['iw_login_page_url']));
            update_option('iw_registration_page_url', esc_url_raw($_POST['iw_registration_page_url']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        
        $default_days = get_option('iw_default_invite_days', 365);
        $max_students = get_option('iw_max_students_per_partner', 100);
        $expiry_action = get_option('iw_expiry_action', 'remove_enrollments');
        $notify_days = get_option('iw_notify_days_before', 7);
        $redirect_url = get_option('iw_redirect_after_creation', '');
        $login_url = get_option('iw_login_page_url', wp_login_url());
        $register_url = get_option('iw_registration_page_url', wp_registration_url());
        
        ?>
        <div class="wrap">
            <h1>Partner Dashboard Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('iw_partner_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Default Invite Length (Days)</th>
                        <td><input type="number" name="iw_default_invite_days" value="<?php echo esc_attr($default_days); ?>" min="1" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Max Students Per Partner</th>
                        <td>
                            <select name="iw_max_students_per_partner">
                                <option value="50" <?php selected($max_students, 50); ?>>Tier 1: Up to 50 active students</option>
                                <option value="100" <?php selected($max_students, 100); ?>>Tier 2: Up to 100 active students</option>
                                <option value="200" <?php selected($max_students, 200); ?>>Tier 3: Up to 200 active students</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Expiry Action</th>
                        <td>
                            <select name="iw_expiry_action">
                                <option value="remove_enrollments" <?php selected($expiry_action, 'remove_enrollments'); ?>>Remove Enrollments</option>
                                <option value="delete_user" <?php selected($expiry_action, 'delete_user'); ?>>Delete User</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Notify Days Before Expiry</th>
                        <td><input type="number" name="iw_notify_days_before" value="<?php echo esc_attr($notify_days); ?>" min="0" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Redirect After User Creation</th>
                        <td><input type="url" name="iw_redirect_after_creation" value="<?php echo esc_attr($redirect_url); ?>" class="regular-text" placeholder="https://"></td>
                    </tr>
                    <tr>
                        <th>Login Page URL</th>
                        <td><input type="url" name="iw_login_page_url" value="<?php echo esc_attr($login_url); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Registration Page URL</th>
                        <td><input type="url" name="iw_registration_page_url" value="<?php echo esc_attr($register_url); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function admin_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        echo '<div class="wrap"><h1>Partner Admin Dashboard</h1>';
        echo '<p>Use the <code>[iw_partner_dashboard]</code> shortcode on a page to display the partner dashboard for partners.</p>';
        echo '</div>';
    }
    
    /**
     * Documentation page explaining how the Access Code Membership system works
     */
    public function documentation_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>Access Code Membership System - How It Works</h1>
            
            <div style="max-width: 900px;">
                <h2>Overview</h2>
                <p>The Access Code Membership system allows partners to manually create users and manage course access without payments. This is separate from the Paid Membership system.</p>
                
                <h2>Membership System Comparison</h2>
                <table class="widefat" style="margin: 20px 0;">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Paid Membership</th>
                            <th>Access Code Membership</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Activation</strong></td>
                            <td>IELTS Courses → Settings → Enable Paid Membership System</td>
                            <td>IELTS Courses → Settings → Enable Access Code Membership System</td>
                        </tr>
                        <tr>
                            <td><strong>User Creation</strong></td>
                            <td>Self-signup with Stripe payment</td>
                            <td>Partner creates users manually or via invite codes</td>
                        </tr>
                        <tr>
                            <td><strong>Membership Types</strong></td>
                            <td>Academic Trial, General Trial, Academic Full, General Full, Academic Plus, General Plus, English Trial, English Full</td>
                            <td>Academic Module, General Training Module, General English</td>
                        </tr>
                        <tr>
                            <td><strong>WordPress Roles</strong></td>
                            <td>academic_trial, general_trial, academic_full, general_full, academic_plus, general_plus, english_trial, english_full</td>
                            <td>access_academic_module, access_general_module, access_general_english</td>
                        </tr>
                        <tr>
                            <td><strong>Course Access</strong></td>
                            <td>Based on payment tier and course mapping</td>
                            <td>Based on course category slugs assigned to membership type</td>
                        </tr>
                        <tr>
                            <td><strong>Expiry Management</strong></td>
                            <td>Automatic via Stripe subscription</td>
                            <td>Manual via partner dashboard or user edit page</td>
                        </tr>
                    </tbody>
                </table>
                
                <h2>Access Code Membership Types</h2>
                <p>When the Access Code Membership system is enabled, three membership types are created:</p>
                
                <h3>1. Academic Module</h3>
                <ul>
                    <li><strong>WordPress Role:</strong> <code>access_academic_module</code></li>
                    <li><strong>Courses Included:</strong> All courses with category slugs: <code>academic</code>, <code>english</code>, <code>academic-practice-tests</code></li>
                    <li><strong>Typical Use:</strong> Students preparing for IELTS Academic exam + General English content</li>
                </ul>
                
                <h3>2. General Training Module</h3>
                <ul>
                    <li><strong>WordPress Role:</strong> <code>access_general_module</code></li>
                    <li><strong>Courses Included:</strong> All courses with category slugs: <code>general</code>, <code>english</code>, <code>general-practice-tests</code></li>
                    <li><strong>Typical Use:</strong> Students preparing for IELTS General Training exam + General English content</li>
                </ul>
                
                <h3>3. General English</h3>
                <ul>
                    <li><strong>WordPress Role:</strong> <code>access_general_english</code></li>
                    <li><strong>Courses Included:</strong> Only courses with category slug: <code>english</code></li>
                    <li><strong>Typical Use:</strong> Students who only want General English courses without IELTS-specific content</li>
                </ul>
                
                <h2>How Users Get Access to Courses</h2>
                <p>When a user is created via the Partner Dashboard or an invite code is used:</p>
                <ol>
                    <li><strong>WordPress Role Assigned:</strong> User gets one of the three access code membership roles (e.g., <code>access_academic_module</code>)</li>
                    <li><strong>Courses Queried:</strong> System finds all published courses with matching category slugs</li>
                    <li><strong>Enrollment Created:</strong> User is enrolled in each matching course in the enrollment table</li>
                    <li><strong>Expiry Set:</strong> All enrollments inherit the expiry date specified during creation</li>
                    <li><strong>Access Check:</strong> When user tries to access a course, system checks:
                        <ul>
                            <li>Does user have a valid membership role?</li>
                            <li>Is there an active enrollment record?</li>
                            <li>Has the membership expired?</li>
                        </ul>
                    </li>
                </ol>
                
                <h2>Managing Users</h2>
                
                <h3>Creating a User Manually</h3>
                <ol>
                    <li>Go to Partner Dashboard (frontend) using <code>[iw_partner_dashboard]</code> shortcode</li>
                    <li>Expand "Create User Manually" section</li>
                    <li>Fill in email, first name, last name</li>
                    <li>Select course group (Academic Module, General Training Module, or General English)</li>
                    <li>Set access days (e.g., 365 for one year)</li>
                    <li>Click "Create User" - user receives welcome email with credentials</li>
                </ol>
                
                <h3>Editing User Access (Admin)</h3>
                <p>Go to Users → Edit User → scroll to "Access Code Enrollment" section:</p>
                <ul>
                    <li><strong>Course Group:</strong> Change which membership type user has</li>
                    <li><strong>Access Code Expiry:</strong> Extend or shorten access period</li>
                    <li><strong>Enrolled Courses:</strong> Manually toggle specific course enrollments (for backward compatibility only)</li>
                </ul>
                
                <h3>Revoking Access</h3>
                <p>From the Partner Dashboard, in the "Managed Students" section, click "Revoke" next to a student. Action depends on settings:</p>
                <ul>
                    <li><strong>Remove Enrollments:</strong> User remains in database but loses all course access</li>
                    <li><strong>Delete User:</strong> User account is completely deleted from WordPress</li>
                </ul>
                
                <h2>Important Notes</h2>
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                    <h3 style="margin-top: 0;">System Separation</h3>
                    <p><strong>Paid Membership and Access Code Membership are completely separate systems.</strong></p>
                    <ul>
                        <li>They use different WordPress roles</li>
                        <li>They have different membership types</li>
                        <li>They can be enabled/disabled independently</li>
                        <li>Users can only have ONE type of membership (not both)</li>
                    </ul>
                </div>
                
                <div style="background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0;">
                    <h3 style="margin-top: 0;">Course Categories Matter</h3>
                    <p>For the Access Code Membership system to work correctly:</p>
                    <ul>
                        <li>Courses must be properly categorized with slugs: <code>academic</code>, <code>general</code>, <code>english</code>, etc.</li>
                        <li>If a course has no categories or wrong categories, users won't be enrolled in it</li>
                        <li>Check: IELTS Courses → Categories to manage course categories</li>
                    </ul>
                </div>
                
                <h2>Troubleshooting</h2>
                
                <h3>User Created But Shows "Enrol Now" on All Courses</h3>
                <p><strong>Possible causes:</strong></p>
                <ul>
                    <li>Courses don't have proper category slugs assigned</li>
                    <li>User's WordPress role wasn't set (check Users → Edit User → Role)</li>
                    <li>Enrollment records weren't created in database</li>
                </ul>
                <p><strong>Solution:</strong> Edit the user (Users → Edit User), change their Course Group to something else, save, then change it back to the correct group and save again. This will re-trigger the enrollment process.</p>
                
                <h3>User Has Access But Shouldn't</h3>
                <p>Check if user has an admin role or if they have both access code AND paid membership roles (they should only have one).</p>
                
                <h3>Access Expired But User Still Has Access</h3>
                <p>The system runs a daily cron job to check for expired memberships. To manually trigger it, go to Tools → Site Health → Scheduled Events and run <code>ielts_cm_check_expired_memberships</code>.</p>
            </div>
        </div>
        <?php
    }
    
    public function enqueue_frontend_scripts() {
        global $post;
        if ($post && has_shortcode($post->post_content, 'iw_partner_dashboard')) {
            wp_enqueue_script('jquery');
        }
    }
    
    public function partner_dashboard_shortcode() {
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            return '<p>You do not have permission to access this dashboard.</p>';
        }
        
        $partner_id = get_current_user_id();
        $max_students = get_option('iw_max_students_per_partner', 100);
        $active_students = $this->get_partner_students($partner_id);
        $active_count = count($active_students);
        
        ob_start();
        ?>
        <style>
            .iw-dashboard { max-width: 1200px; margin: 20px auto; }
            .iw-card { background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .iw-card-header { padding: 20px; cursor: pointer; border-bottom: 1px solid #eee; position: relative; user-select: none; }
            .iw-card-header:hover { background: #f9f9f9; }
            .iw-card-header h2 { margin: 0; padding-right: 30px; }
            .iw-card-header::after { content: '▼'; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); transition: transform 0.3s; font-size: 12px; }
            .iw-card.collapsed .iw-card-header::after { transform: translateY(-50%) rotate(-90deg); }
            .iw-card-body { padding: 20px; display: none; }
            .iw-card.expanded .iw-card-body { display: block; }
            .iw-form-table { width: 100%; }
            .iw-form-table th { text-align: left; padding: 10px; width: 200px; }
            .iw-form-table td { padding: 10px; }
            .iw-btn { display: inline-block; padding: 8px 16px; background: #0073aa; color: #fff; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; }
            .iw-btn:hover { background: #005177; color: #fff; }
            .iw-btn-danger { background: #dc3545; }
            .iw-btn-danger:hover { background: #c82333; }
            .iw-filter-btn { display: inline-block; padding: 6px 12px; background: #f1f1f1; color: #333; border: 1px solid #ddd; border-radius: 3px; cursor: pointer; margin-right: 5px; }
            .iw-filter-btn:hover { background: #e1e1e1; }
            .iw-filter-btn.active { background: #0073aa; color: #fff; border-color: #0073aa; }
            .iw-msg { padding: 12px; border-radius: 3px; margin: 15px 0; }
            .iw-msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .iw-msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .iw-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .iw-table th, .iw-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            .iw-table th { background: #f9f9f9; font-weight: 600; }
            .iw-table tr:hover { background: #f5f5f5; }
            input[type="text"], input[type="email"], input[type="number"], select { padding: 6px 10px; border: 1px solid #ddd; border-radius: 3px; width: 100%; }
        </style>
        
        <div class="iw-dashboard">
            <?php 
            $remaining_places = $max_students - $active_count;
            ?>
            <p style="margin-bottom: 15px;"><strong>Students:</strong> <?php echo $active_count; ?> / <?php echo $max_students; ?></p>
            
            <div class="iw-card collapsed">
                <div class="iw-card-header">
                    <h2>Create Invite Codes</h2>
                </div>
                <div class="iw-card-body">
                    <?php if ($remaining_places <= 0): ?>
                        <div class="iw-msg error">
                            You have reached your student limit (<?php echo $max_students; ?> students). 
                            Please contact support to upgrade your tier or remove expired students.
                        </div>
                    <?php else: ?>
                    <div id="create-invite-msg"></div>
                    <form id="create-invite-form">
                        <?php wp_nonce_field('iw_create_invite', 'iw_create_invite_nonce'); ?>
                        <table class="iw-form-table">
                            <tr>
                                <th>Number of Codes:</th>
                                <td>
                                    <input type="number" name="quantity" min="1" max="<?php echo $remaining_places; ?>" value="1" required>
                                    <p class="description">Remaining places: <?php echo $remaining_places; ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>Course Group:</th>
                                <td>
                                    <select name="course_group" required>
                                        <?php foreach ($this->course_groups as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Access Days:</th>
                                <td><input type="number" name="days" value="<?php echo get_option('iw_default_invite_days', 365); ?>" min="1" required></td>
                            </tr>
                            <tr>
                                <td colspan="2"><button type="submit" class="iw-btn">Generate Codes</button></td>
                            </tr>
                        </table>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="iw-card collapsed">
                <div class="iw-card-header">
                    <h2>Create User Manually</h2>
                </div>
                <div class="iw-card-body">
                    <div id="create-user-msg"></div>
                    <form id="create-user-form">
                        <?php wp_nonce_field('iw_create_user', 'iw_create_user_nonce'); ?>
                        <table class="iw-form-table">
                            <tr>
                                <th>Email:</th>
                                <td><input type="email" name="email" required></td>
                            </tr>
                            <tr>
                                <th>First Name:</th>
                                <td><input type="text" name="first_name" required></td>
                            </tr>
                            <tr>
                                <th>Last Name:</th>
                                <td><input type="text" name="last_name" required></td>
                            </tr>
                            <tr>
                                <th>Access Days:</th>
                                <td><input type="number" name="days" value="<?php echo get_option('iw_default_invite_days', 365); ?>" min="1" required></td>
                            </tr>
                            <tr>
                                <th>Course Group:</th>
                                <td>
                                    <select name="course_group" required>
                                        <?php foreach ($this->course_groups as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><button type="submit" class="iw-btn">Create User</button></td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
            
            <div class="iw-card collapsed">
                <div class="iw-card-header">
                    <h2>Your Codes</h2>
                </div>
                <div class="iw-card-body">
                    <div style="margin-bottom: 15px;">
                        <button class="iw-filter-btn active" data-filter="all">All</button>
                        <button class="iw-filter-btn" data-filter="active">Active</button>
                        <button class="iw-filter-btn" data-filter="available">Available</button>
                        <button class="iw-filter-btn" data-filter="expired">Expired</button>
                        <button class="iw-btn" onclick="IWDashboard.downloadCSV()" style="float: right;">Download CSV</button>
                    </div>
                    <div style="clear: both;"></div>
                    <?php echo $this->render_codes_table($partner_id); ?>
                </div>
            </div>
            
            <div class="iw-card expanded">
                <div class="iw-card-header">
                    <h2>Managed Students</h2>
                </div>
                <div class="iw-card-body">
                    <div style="margin-bottom: 15px;">
                        <button class="iw-filter-btn active" data-filter-students="active">Active</button>
                        <button class="iw-filter-btn" data-filter-students="expired">Expired</button>
                    </div>
                    <?php echo $this->render_students_table($active_students); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Collapsible card functionality
            $('.iw-card-header').on('click', function() {
                $(this).parent('.iw-card').toggleClass('collapsed expanded');
            });
            
            window.IWDashboard = {
                deleteNonce: '<?php echo wp_create_nonce('iw_delete_code'); ?>',
                revokeNonce: '<?php echo wp_create_nonce('iw_revoke_student'); ?>',
                
                filterCodes: function(status) {
                    $('.iw-filter-btn').removeClass('active');
                    $('.iw-filter-btn[data-filter="' + status + '"]').addClass('active');
                    
                    if (status === 'all') {
                        $('.iw-table tbody tr').show();
                    } else if (status === 'available') {
                        $('.iw-table tbody tr').each(function() {
                            if ($(this).data('status') === 'active') {
                                $(this).show();
                            } else {
                                $(this).hide();
                            }
                        });
                    } else {
                        $('.iw-table tbody tr').each(function() {
                            if ($(this).data('status') === status) {
                                $(this).show();
                            } else {
                                $(this).hide();
                            }
                        });
                    }
                },
                
                deleteCode: function(codeId) {
                    if (!confirm('Delete this code?')) return;
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'iw_delete_code',
                        code_id: codeId,
                        nonce: IWDashboard.deleteNonce
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    });
                },
                
                revokeStudent: function(userId) {
                    if (!confirm('Revoke access for this student?')) return;
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'iw_revoke_student',
                        user_id: userId,
                        nonce: IWDashboard.revokeNonce
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    });
                },
                
                filterStudents: function(status) {
                    $('.iw-filter-btn[data-filter-students]').removeClass('active');
                    $('.iw-filter-btn[data-filter-students="' + status + '"]').addClass('active');
                    
                    var $table = $('.iw-students-table');
                    var $rows = $table.find('tbody tr');
                    var visibleCount = 0;
                    
                    $rows.each(function() {
                        var rowStatus = $(this).data('student-status');
                        if (rowStatus === status) {
                            $(this).show();
                            visibleCount++;
                        } else {
                            $(this).hide();
                        }
                    });
                    
                    // Show/hide empty state messages
                    $('[data-empty-state]').hide();
                    if (visibleCount === 0) {
                        $table.hide();
                        $('[data-empty-state="' + status + '"]').show();
                    } else {
                        $table.show();
                    }
                },
                
                downloadCSV: function() {
                    function escapeCSV(val) {
                        if (val.indexOf(',') >= 0 || val.indexOf('"') >= 0 || val.indexOf('\n') >= 0) {
                            return '"' + val.replace(/"/g, '""') + '"';
                        }
                        return val;
                    }
                    
                    var csv = 'Code,Group,Days,Status,Used By,Created\n';
                    $('.iw-table tbody tr:visible').each(function() {
                        var cols = $(this).find('td');
                        if (cols.length >= 6) {
                            csv += escapeCSV(cols.eq(0).text()) + ',';
                            csv += escapeCSV(cols.eq(1).text()) + ',';
                            csv += escapeCSV(cols.eq(2).text()) + ',';
                            csv += escapeCSV(cols.eq(3).text()) + ',';
                            csv += escapeCSV(cols.eq(4).text()) + ',';
                            csv += escapeCSV(cols.eq(5).text()) + '\n';
                        }
                    });
                    var blob = new Blob([csv], { type: 'text/csv' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'invite-codes.csv';
                    a.click();
                }
            };
            
            // Filter button handlers
            $('.iw-filter-btn[data-filter]').on('click', function() {
                var filter = $(this).data('filter');
                IWDashboard.filterCodes(filter);
            });
            
            // Student filter button handlers
            $('.iw-filter-btn[data-filter-students]').on('click', function() {
                var filter = $(this).data('filter-students');
                IWDashboard.filterStudents(filter);
            });
            
            // Initialize student filter to show active by default
            IWDashboard.filterStudents('active');
            
            $('#create-invite-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $msg = $('#create-invite-msg');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: $form.serialize() + '&action=iw_create_invite',
                    success: function(response) {
                        if (response.success) {
                            var msg = $('<div class="iw-msg success">').text(response.data.message);
                            $msg.html(msg);
                            if (response.data.codes) {
                                var codesText = response.data.codes.join('\n');
                                var textarea = $('<textarea readonly style="width:100%;height:100px;">').text(codesText);
                                var copyBtn = $('<button class="iw-btn">').text('Copy Codes').on('click', function(e) {
                                    e.preventDefault();
                                    navigator.clipboard.writeText(codesText);
                                });
                                $msg.append($('<div style="margin-top:10px;">').append(textarea).append(copyBtn));
                            }
                            setTimeout(function() { location.reload(); }, 3000);
                        } else {
                            $msg.html($('<div class="iw-msg error">').text(response.data.message));
                        }
                    }
                });
            });
            
            $('#create-user-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $msg = $('#create-user-msg');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: $form.serialize() + '&action=iw_create_user_manually',
                    success: function(response) {
                        if (response.success) {
                            $msg.html($('<div class="iw-msg success">').text(response.data.message));
                            $form[0].reset();
                            setTimeout(function() { location.reload(); }, 3000);
                        } else {
                            $msg.html($('<div class="iw-msg error">').text(response.data.message));
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function render_codes_table($partner_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        $codes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE created_by = %d ORDER BY created_date DESC LIMIT 100",
            $partner_id
        ));
        
        if (empty($codes)) {
            return '<p>No codes generated yet.</p>';
        }
        
        $html = '<table class="iw-table"><thead><tr>';
        $html .= '<th>Code</th><th>Membership</th><th>Days</th><th>Status</th><th>Used By</th><th>Created</th><th>Action</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($codes as $code) {
            $used_by_display = '-';
            
            // Determine status display based on code status
            if ($code->status === 'used' && $code->used_by) {
                $user = get_userdata($code->used_by);
                if ($user) {
                    $used_by_display = $user->display_name . ' (' . $user->user_email . ')';
                    $status_display = 'Used';
                } else {
                    $used_by_display = 'Deleted User';
                    $status_display = 'Used';
                }
            } else if ($code->status === 'expired') {
                $status_display = 'Expired';
            } else if ($code->status === 'active') {
                $status_display = 'Available';
            } else {
                // Fallback for any other status
                $status_display = ucfirst($code->status);
            }
            
            // Determine actual status for filtering
            $filter_status = $code->status;
            
            $html .= '<tr data-status="' . esc_attr($filter_status) . '">';
            $html .= '<td>' . esc_html($code->code) . '</td>';
            $html .= '<td>' . esc_html($this->course_groups[$code->course_group] ?? $code->course_group) . '</td>';
            $html .= '<td>' . esc_html($code->duration_days) . '</td>';
            $html .= '<td>' . esc_html($status_display) . '</td>';
            $html .= '<td>' . esc_html($used_by_display) . '</td>';
            $html .= '<td>' . esc_html(date('Y-m-d', strtotime($code->created_date))) . '</td>';
            $html .= '<td>';
            if ($code->status === 'active') {
                $html .= '<button class="iw-btn iw-btn-danger" onclick="IWDashboard.deleteCode(' . $code->id . ')">Delete</button>';
            }
            $html .= '</td></tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
    
    private function render_students_table($students) {
        if (empty($students)) {
            return '<p class="no-students-msg">No students managed yet.</p>';
        }
        
        $html = '<table class="iw-table iw-students-table"><thead><tr>';
        $html .= '<th>Username</th><th>Email</th><th>Group</th><th>Expiry</th><th>Status</th><th>Action</th>';
        $html .= '</tr></thead><tbody>';
        
        $has_active = false;
        $has_expired = false;
        
        foreach ($students as $student) {
            $user = get_userdata($student->user_id);
            if (!$user) continue;
            
            $group = get_user_meta($student->user_id, 'iw_course_group', true);
            $expiry = get_user_meta($student->user_id, 'iw_membership_expiry', true);
            
            // Determine if membership is active or expired
            $is_active = false;
            $status_label = 'No Membership';
            if ($expiry) {
                $expiry_timestamp = strtotime($expiry);
                $is_active = $expiry_timestamp > time();
                $status_label = $is_active ? 'Active' : 'Expired';
            }
            
            if ($is_active) {
                $has_active = true;
            } else {
                $has_expired = true;
            }
            
            $status_class = $is_active ? 'active' : 'expired';
            
            $html .= '<tr data-student-status="' . esc_attr($status_class) . '">';
            $html .= '<td>' . esc_html($user->user_login) . '</td>';
            $html .= '<td>' . esc_html($user->user_email) . '</td>';
            $html .= '<td>' . esc_html($this->get_course_group_display_name($group)) . '</td>';
            $html .= '<td>' . esc_html($expiry ? date('Y-m-d H:i', strtotime($expiry)) : '-') . '</td>';
            $html .= '<td><span style="color: ' . ($is_active ? 'green' : 'red') . ';">' . esc_html($status_label) . '</span></td>';
            $html .= '<td><button class="iw-btn iw-btn-danger" onclick="IWDashboard.revokeStudent(' . $student->user_id . ')">Revoke</button></td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Add messages for empty states
        if (!$has_active) {
            $html .= '<p class="no-students-msg" data-empty-state="active" style="display: none;">No active students.</p>';
        }
        if (!$has_expired) {
            $html .= '<p class="no-students-msg" data-empty-state="expired" style="display: none;">No expired students.</p>';
        }
        
        return $html;
    }
    
    private function get_partner_students($partner_id) {
        // Get all users managed by this partner
        // This includes users created manually and users who used access codes
        
        // First get users with the partner meta key
        $users_by_partner = get_users(array(
            'meta_key' => 'iw_created_by_partner',
            'meta_value' => $partner_id,
            'fields' => array('ID')
        ));
        
        // Also get all users with access code memberships but NO partner assignment
        // This catches legacy students created before the partner system was fully implemented
        // Only administrators can see legacy users (those without partner assignment)
        $users_with_access_codes = array();
        if (current_user_can('manage_options')) {
            // Admin can see all legacy access code users
            // Use meta_query to efficiently get users with iw_course_group but without iw_created_by_partner
            $users_with_access_codes = get_users(array(
                'fields' => array('ID'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'iw_course_group',
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => 'iw_created_by_partner',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ));
        }
        
        // Merge and deduplicate user IDs using WordPress-optimized function
        $user_ids = array_unique(array_merge(
            wp_list_pluck($users_by_partner, 'ID'),
            wp_list_pluck($users_with_access_codes, 'ID')
        ));
        
        // Return in format compatible with existing code
        $results = array();
        foreach ($user_ids as $user_id) {
            $results[] = (object) array('user_id' => $user_id);
        }
        return $results;
    }
    
    public function ajax_create_invite() {
        check_ajax_referer('iw_create_invite', 'iw_create_invite_nonce');
        
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $quantity = absint($_POST['quantity']);
        $course_group = sanitize_text_field($_POST['course_group']);
        $days = absint($_POST['days']);
        
        // Check remaining places based on tier level
        $partner_id = get_current_user_id();
        $max_students = get_option('iw_max_students_per_partner', 100);
        $active_students = $this->get_partner_students($partner_id);
        $active_count = count($active_students);
        $remaining_places = $max_students - $active_count;
        
        if ($quantity < 1) {
            wp_send_json_error(array('message' => 'Quantity must be at least 1'));
        }
        
        if ($quantity > $remaining_places) {
            wp_send_json_error(array('message' => "You can only create {$remaining_places} more codes (tier limit: {$max_students}, current students: {$active_count})"));
        }
        
        if (!array_key_exists($course_group, $this->course_groups)) {
            wp_send_json_error(array('message' => 'Invalid course group'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        $codes = array();
        
        for ($i = 0; $i < $quantity; $i++) {
            $code = $this->generate_unique_code();
            $wpdb->insert($table, array(
                'code' => $code,
                'course_group' => $course_group,
                'duration_days' => $days,
                'status' => 'active',
                'created_by' => get_current_user_id(),
                'created_date' => current_time('mysql')
            ));
            $codes[] = $code;
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d codes generated successfully!', $quantity),
            'codes' => $codes
        ));
    }
    
    public function ajax_create_user_manually() {
        check_ajax_referer('iw_create_user', 'iw_create_user_nonce');
        
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $days = absint($_POST['days']);
        $course_group = sanitize_text_field($_POST['course_group']);
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email'));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already exists'));
        }
        
        $base_username = sanitize_user(strtolower($first_name . $last_name));
        $username = $base_username . rand(1000, 99999);
        
        $attempts = 0;
        while (username_exists($username) && $attempts < 10) {
            $username = $base_username . rand(1000, 99999);
            $attempts++;
        }
        
        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Could not generate unique username'));
        }
        $password = wp_generate_password(12);
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        $expiry_date = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $this->set_ielts_membership($user_id, $course_group, $expiry_date);
        $this->enroll_user_in_courses($user_id, $course_group);
        
        update_user_meta($user_id, 'iw_created_by_partner', get_current_user_id());
        
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        $code = $this->generate_unique_code();
        $wpdb->insert($table, array(
            'code' => $code,
            'course_group' => $course_group,
            'duration_days' => $days,
            'status' => 'used',
            'created_by' => get_current_user_id(),
            'used_by' => $user_id,
            'created_date' => current_time('mysql'),
            'used_date' => current_time('mysql')
        ));
        
        $this->send_welcome_email($user_id, $username, $password);
        
        wp_send_json_success(array(
            'message' => "User created! Username: {$username}, Password sent to email."
        ));
    }
    
    public function ajax_revoke_student() {
        check_ajax_referer('iw_revoke_student', 'nonce');
        
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $user_id = absint($_POST['user_id']);
        
        $action = get_option('iw_expiry_action', 'remove_enrollments');
        
        if ($action === 'delete_user') {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
        } else {
            $this->remove_user_enrollments($user_id);
            delete_user_meta($user_id, 'iw_membership_expiry');
            delete_user_meta($user_id, 'iw_course_group');
        }
        
        wp_send_json_success(array('message' => 'Student access revoked'));
    }
    
    public function ajax_delete_code() {
        check_ajax_referer('iw_delete_code', 'nonce');
        
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $code_id = absint($_POST['code_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        
        $code = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $code_id));
        
        if (!$code || $code->status !== 'active') {
            wp_send_json_error(array('message' => 'Cannot delete this code'));
        }
        
        $wpdb->delete($table, array('id' => $code_id));
        
        wp_send_json_success(array('message' => 'Code deleted'));
    }
    
    private function generate_unique_code() {
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        
        $attempts = 0;
        do {
            // Generate 8 digit alphanumeric code without prefix
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE code = %s", $code));
            $attempts++;
        } while ($exists > 0 && $attempts < 10);
        
        return $code;
    }
    
    private function enroll_user_in_courses($user_id, $course_group) {
        // Handle backward compatibility with old course group names
        $legacy_mapping = array(
            'academic_english' => 'academic_module',
            'english_only' => 'general_english',
            'all_courses' => 'academic_module'  // Map to academic as it was the most common
        );
        
        if (isset($legacy_mapping[$course_group])) {
            $course_group = $legacy_mapping[$course_group];
            // Update the user meta to the new value
            update_user_meta($user_id, 'iw_course_group', $course_group);
        }
        
        // Determine which category slugs to query based on course group
        $category_slugs = array();
        
        switch ($course_group) {
            case 'academic_module':
                $category_slugs = array('academic', 'english', 'academic-practice-tests');
                break;
            case 'general_module':
                $category_slugs = array('general', 'english', 'general-practice-tests');
                break;
            case 'general_english':
                $category_slugs = array('english');
                break;
        }
        
        // Query courses by category slugs
        $course_ids = $this->get_courses_by_category_slugs($category_slugs);
        
        // Enroll user in each course using the enrollment table
        if (!empty($course_ids)) {
            require_once IELTS_CM_PLUGIN_DIR . 'includes/class-enrollment.php';
            $enrollment = new IELTS_CM_Enrollment();
            
            // Calculate expiry date from user meta
            $expiry_date = get_user_meta($user_id, 'iw_membership_expiry', true);
            
            foreach ($course_ids as $course_id) {
                $enrollment->enroll($user_id, $course_id, 'active', $expiry_date);
            }
        }
        
        // Keep legacy meta fields for backward compatibility (but they're not used for access checks)
        $legacy_courses = array();
        switch ($course_group) {
            case 'academic_module':
                $legacy_courses = array('ielts_academic', 'general_english');
                break;
            case 'general_module':
                $legacy_courses = array('ielts_general', 'general_english');
                break;
            case 'general_english':
                $legacy_courses = array('general_english');
                break;
        }
        
        foreach ($legacy_courses as $course) {
            update_user_meta($user_id, "enrolled_{$course}", true);
        }
    }
    
    /**
     * Get course IDs by category slugs
     * 
     * Queries the database for published courses that have any of the specified category slugs.
     * This is used to dynamically enroll users in courses based on their membership type.
     * 
     * @param array $category_slugs Array of category slugs to query (e.g., ['academic', 'english'])
     * @return array Array of course IDs. Returns empty array if no category slugs provided or no matching courses found.
     */
    private function get_courses_by_category_slugs($category_slugs) {
        if (empty($category_slugs)) {
            return array();
        }
        
        $args = array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ielts_course_category',
                    'field' => 'slug',
                    'terms' => $category_slugs,
                    'operator' => 'IN'
                )
            ),
            'fields' => 'ids' // Only get IDs for efficiency
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    private function remove_user_enrollments($user_id) {
        // Remove from enrollment table
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-enrollment.php';
        $enrollment = new IELTS_CM_Enrollment();
        
        // Get all user's courses and unenroll
        $user_courses = $enrollment->get_user_courses($user_id);
        foreach ($user_courses as $course) {
            $enrollment->unenroll($user_id, $course->course_id);
        }
        
        // Remove legacy meta fields
        delete_user_meta($user_id, 'enrolled_ielts_academic');
        delete_user_meta($user_id, 'enrolled_ielts_general');
        delete_user_meta($user_id, 'enrolled_general_english');
    }
    
    private function set_ielts_membership($user_id, $course_group, $expiry_date) {
        update_user_meta($user_id, 'iw_course_group', $course_group);
        update_user_meta($user_id, 'iw_membership_expiry', $expiry_date);
        update_user_meta($user_id, 'iw_membership_status', 'active');
        
        // Map course group to access code membership type and assign role
        $role_mapping = array(
            'academic_module' => 'access_academic_module',
            'general_module' => 'access_general_module',
            'general_english' => 'access_general_english'
        );
        
        if (isset($role_mapping[$course_group])) {
            $membership_type = $role_mapping[$course_group];
            
            // Set membership type meta (used by is_enrolled check)
            update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
            update_user_meta($user_id, '_ielts_cm_membership_status', 'active');
            update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
            
            // Assign WordPress role
            $user = get_userdata($user_id);
            if ($user) {
                // Remove any existing membership roles first
                foreach (self::ACCESS_CODE_MEMBERSHIP_TYPES as $role_slug => $role_name) {
                    $user->remove_role($role_slug);
                }
                // Add the new role
                $user->add_role($membership_type);
            }
        }
    }
    
    private function send_welcome_email($user_id, $username, $password) {
        $user = get_userdata($user_id);
        $login_url = get_option('iw_login_page_url', wp_login_url());
        
        $subject = 'Your IELTS Course Access';
        $message = "Hello,\n\n";
        $message .= "Your account has been created!\n\n";
        $message .= "Username: {$username}\n";
        $message .= "Password: {$password}\n\n";
        $message .= "Login here: {$login_url}\n\n";
        $message .= "Best regards,\nIELTS Course Team";
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Get display name for course group, including legacy values
     */
    private function get_course_group_display_name($group) {
        // Legacy course group names for backward compatibility
        $legacy_groups = array(
            'academic_english' => 'IELTS Academic + English (Legacy)',
            'english_only' => 'General English Only (Legacy)',
            'all_courses' => 'All Courses (Legacy)'
        );
        
        if (isset($this->course_groups[$group])) {
            return $this->course_groups[$group];
        } elseif (isset($legacy_groups[$group])) {
            return $legacy_groups[$group];
        } else {
            return $group;
        }
    }
}
