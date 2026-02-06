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
     * Admin organization ID - special value indicating admin sees all data
     */
    const ADMIN_ORG_ID = 0;
    
    /**
     * Site-wide partner organization ID - all partner admins on this site share this ID
     * This ensures all partner admins see the same students and codes
     */
    const SITE_PARTNER_ORG_ID = 1;
    
    /**
     * User meta key for partner organization ID
     */
    const META_PARTNER_ORG_ID = 'iw_partner_organization_id';
    
    /**
     * Maximum number of codes to display in table
     */
    const CODES_TABLE_LIMIT = 100;
    
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
        add_action('admin_init', array($this, 'block_partner_admin_backend'));
        add_action('admin_init', array($this, 'migrate_partner_data_to_site_org'));
        
        // Partner dashboard shortcode
        add_shortcode('iw_partner_dashboard', array($this, 'partner_dashboard_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_iw_create_invite', array($this, 'ajax_create_invite'));
        add_action('wp_ajax_iw_create_user_manually', array($this, 'ajax_create_user_manually'));
        add_action('wp_ajax_iw_revoke_student', array($this, 'ajax_revoke_student'));
        add_action('wp_ajax_iw_delete_code', array($this, 'ajax_delete_code'));
        add_action('wp_ajax_iw_edit_student', array($this, 'ajax_edit_student'));
        add_action('wp_ajax_iw_resend_welcome', array($this, 'ajax_resend_welcome'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    /**
     * Migrate existing partner admin data to use site-wide organization ID
     * This is a one-time migration that consolidates all partner admin data
     * so that all partner admins see the same students and codes
     * 
     * Public visibility required because it's hooked to admin_init
     */
    public function migrate_partner_data_to_site_org() {
        // Only allow admins to run this migration
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if migration has already run
        $migration_done = get_option('iw_partner_site_org_migration_done', false);
        if ($migration_done) {
            return;
        }
        
        // Use transient lock to prevent concurrent execution
        $lock_key = 'iw_partner_migration_lock';
        if (get_transient($lock_key)) {
            return; // Another process is running the migration
        }
        
        // Set lock for 5 minutes
        set_transient($lock_key, true, 300);
        
        global $wpdb;
        
        // Get all partner admin user IDs (users with manage_partner_invites capability)
        $partner_admins = get_users(array(
            'role' => 'partner_admin',
            'fields' => 'ID'
        ));
        
        if (empty($partner_admins)) {
            // No partner admins found - skip migration but don't mark as done
            // This allows the migration to run later if partner admins are added
            delete_transient($lock_key);
            return;
        }
        
        $partner_admin_ids = $partner_admins;
        
        // Maximum batch size for migration to prevent issues with large datasets
        $max_batch_size = 1000;
        
        // Validate count for security (though get_users should always return array)
        if (count($partner_admin_ids) > $max_batch_size) {
            // Too many partner admins - log error and skip migration
            error_log("Partner admin migration skipped: too many partner admins (" . count($partner_admin_ids) . ")");
            delete_transient($lock_key);
            return;
        }
        
        // Build placeholders once for reuse
        $placeholders = implode(',', array_fill(0, count($partner_admin_ids), '%d'));
        
        // Migrate access codes: Update created_by from partner admin user IDs to SITE_PARTNER_ORG_ID
        // Table name is safe: prefix comes from WordPress core, suffix is hardcoded
        $codes_table = $wpdb->prefix . 'ielts_cm_access_codes';
        
        // Build query with table name outside of prepare, parameters inside prepare
        // Placeholders must be built dynamically since the number of IDs varies
        $query = "UPDATE {$codes_table} SET created_by = %d WHERE created_by IN ({$placeholders})";
        $prepared_query = $wpdb->prepare($query, self::SITE_PARTNER_ORG_ID, ...$partner_admin_ids);
        $codes_result = $wpdb->query($prepared_query);
        
        // Check for errors
        if ($codes_result === false) {
            // Database error occurred, don't mark as complete to retry next time
            error_log("Partner admin migration failed: codes table update error");
            delete_transient($lock_key);
            return;
        }
        
        // Migrate user meta: Update iw_created_by_partner from partner admin user IDs to SITE_PARTNER_ORG_ID
        // Note: meta_value is stored as string in usermeta table
        // Convert partner admin IDs to strings for proper comparison
        // Table name is safe: usermeta is WordPress core table
        $meta_table = $wpdb->usermeta;
        $org_id_string = (string) self::SITE_PARTNER_ORG_ID;
        
        // Convert partner admin IDs to strings for comparison
        $partner_admin_ids_str = array_map('strval', $partner_admin_ids);
        $placeholders_str = implode(',', array_fill(0, count($partner_admin_ids_str), '%s'));
        
        // Build query with table name outside of prepare, parameters inside prepare
        // Placeholders must be built dynamically since the number of IDs varies
        // meta_key is hardcoded literal (not user input), safe to include in query
        $query = "UPDATE {$meta_table} SET meta_value = %s WHERE meta_key = 'iw_created_by_partner' AND meta_value IN ({$placeholders_str})";
        $prepared_query = $wpdb->prepare($query, $org_id_string, ...$partner_admin_ids_str);
        $meta_result = $wpdb->query($prepared_query);
        
        // Check for errors
        if ($meta_result === false) {
            // Database error occurred, don't mark as complete to retry next time
            error_log("Partner admin migration failed: usermeta table update error");
            delete_transient($lock_key);
            return;
        }
        
        // Mark migration as complete only if both queries succeeded
        update_option('iw_partner_site_org_migration_done', true);
        delete_transient($lock_key);
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
    
    /**
     * Block partner admins from accessing WordPress backend
     * Only full site admins (with manage_options capability) should access wp-admin
     */
    public function block_partner_admin_backend() {
        // Allow AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        // Check if user is partner admin (has manage_partner_invites but NOT manage_options)
        if (current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            // Redirect to home page or a custom partner dashboard page
            $redirect_url = home_url('/');
            
            // If there's a custom partner dashboard URL configured, use it
            $partner_dashboard_url = get_option('iw_partner_dashboard_url', '');
            if (!empty($partner_dashboard_url)) {
                // Validate and sanitize the URL to prevent open redirect vulnerabilities
                $redirect_url = esc_url_raw($partner_dashboard_url);
                // Use wp_validate_redirect to ensure redirect is to allowed location
                $redirect_url = wp_validate_redirect($redirect_url, home_url('/'));
            }
            
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Get the partner organization ID for a user
     * This allows multiple partner admins to be part of the same organization
     * and see the same students, codes, and remaining spaces
     * 
     * For single-site installations, all partner admins share the same organization
     * (SITE_PARTNER_ORG_ID) so they see all students on the site.
     * 
     * @param int $user_id User ID (defaults to current user)
     * @return int Partner organization ID
     */
    private function get_partner_org_id($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Full site admins see all data - use ADMIN_ORG_ID constant
        // Check the specific user's capabilities, not the current user's
        if (user_can($user_id, 'manage_options')) {
            return self::ADMIN_ORG_ID;
        }
        
        // Get the partner organization ID from user meta
        // If not set, use site-wide partner org ID so all partner admins see the same data
        $org_id = get_user_meta($user_id, self::META_PARTNER_ORG_ID, true);
        
        if (empty($org_id)) {
            // Use site-wide partner organization ID
            // This means ALL partner admins on this site see the same students and codes
            $org_id = self::SITE_PARTNER_ORG_ID;
        }
        
        return absint($org_id);
    }
    
    public function add_admin_menu() {
        // Only show Partner Dashboard menu if Access Code Membership is enabled
        if (!get_option('ielts_cm_access_code_enabled', false)) {
            return;
        }
        
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
        
        // Use partner organization ID instead of individual user ID
        // This allows multiple partner admins to see the same data
        $partner_org_id = $this->get_partner_org_id();
        $max_students = get_option('iw_max_students_per_partner', 100);
        $active_students = $this->get_partner_students($partner_org_id);
        $active_count = count($active_students);
        
        // Calculate active and expired student counts for display on tabs
        $active_student_count = 0;
        $expired_student_count = 0;
        foreach ($active_students as $student) {
            $expiry = get_user_meta($student->user_id, 'iw_membership_expiry', true);
            if ($expiry) {
                $expiry_timestamp = strtotime($expiry);
                $is_active = $expiry_timestamp > time();
                if ($is_active) {
                    $active_student_count++;
                } else {
                    $expired_student_count++;
                }
            } else {
                $expired_student_count++;
            }
        }
        
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
            .iw-btn-full-width { display: block; width: 100%; margin-bottom: 5px; }
            .iw-btn:hover { background: #005177; color: #fff; }
            .iw-btn-small { padding: 4px 8px; font-size: 12px; }
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
            <p style="margin-bottom: 15px;"><strong>Students:</strong> <?php echo esc_html($active_count); ?> / <?php echo esc_html($max_students); ?></p>
            
            <div class="iw-card collapsed">
                <div class="iw-card-header">
                    <h2>Create Invite Codes (Remaining places: <?php echo esc_html($remaining_places); ?>)</h2>
                </div>
                <div class="iw-card-body">
                    <?php if ($remaining_places <= 0): ?>
                        <div class="iw-msg error">
                            You have reached your student limit (<?php echo esc_html($max_students); ?> students). 
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
                                    <input type="number" name="quantity" min="1" max="<?php echo esc_attr($remaining_places); ?>" value="1" required>
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
                                <th>Send copy to me:</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="send_copy_to_partner" value="1" checked>
                                        Send a copy of the welcome email to me as well
                                    </label>
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
                        <button class="iw-filter-btn active" data-filter="used">Used</button>
                        <button class="iw-filter-btn" data-filter="available">Unused</button>
                        <button class="iw-btn" onclick="IWDashboard.downloadCSV()" style="float: right;">Download CSV</button>
                    </div>
                    <div style="clear: both;"></div>
                    <?php echo $this->render_codes_table($partner_org_id); ?>
                </div>
            </div>
            
            <div class="iw-card expanded">
                <div class="iw-card-header">
                    <h2>Managed Students</h2>
                </div>
                <div class="iw-card-body">
                    <div style="margin-bottom: 15px;">
                        <button class="iw-filter-btn active" data-filter-students="active">Active (<?php echo esc_html($active_student_count); ?>)</button>
                        <button class="iw-filter-btn" data-filter-students="expired">Expired (<?php echo esc_html($expired_student_count); ?>)</button>
                    </div>
                    <?php echo $this->render_students_table($active_students); ?>
                </div>
            </div>
        </div>
        
        <script>
        // Define IWDashboard object outside of jQuery ready to make it immediately available
        window.IWDashboard = {
            deleteNonce: '<?php echo wp_create_nonce('iw_delete_code'); ?>',
            revokeNonce: '<?php echo wp_create_nonce('iw_revoke_student'); ?>',
            editNonce: '<?php echo wp_create_nonce('iw_edit_student'); ?>',
            resendNonce: '<?php echo wp_create_nonce('iw_resend_welcome'); ?>',
            
            filterCodes: function(status) {
                jQuery('.iw-filter-btn').removeClass('active');
                jQuery('.iw-filter-btn[data-filter="' + status + '"]').addClass('active');
                
                if (status === 'available') {
                    // Show unused/available codes (status = 'active')
                    jQuery('.iw-table tbody tr').each(function() {
                        if (jQuery(this).data('status') === 'active') {
                            jQuery(this).show();
                        } else {
                            jQuery(this).hide();
                        }
                    });
                } else if (status === 'used') {
                    // Show used codes (status = 'used')
                    jQuery('.iw-table tbody tr').each(function() {
                        if (jQuery(this).data('status') === 'used') {
                            jQuery(this).show();
                        } else {
                            jQuery(this).hide();
                        }
                    });
                } else {
                    // Default: show all matching the status
                    jQuery('.iw-table tbody tr').each(function() {
                        if (jQuery(this).data('status') === status) {
                            jQuery(this).show();
                        } else {
                            jQuery(this).hide();
                        }
                    });
                }
            },
            
            deleteCode: function(codeId) {
                if (!confirm('Delete this code?')) return;
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
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
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
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
            
            editStudent: function(userId) {
                var currentExpiry = '';
                var currentMembership = '';
                var $row = jQuery('.iw-students-table tr[data-user-id="' + userId + '"]');
                if ($row.length) {
                    var expiryText = $row.find('.expiry-display > div:first-child').text().trim();
                    currentExpiry = expiryText !== '-' ? expiryText : '';
                    currentMembership = $row.data('membership-type') || '';
                }
                
                // Create modal HTML
                var modalHtml = '<div id="iw-edit-student-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">' +
                    '<div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">' +
                    '<h3 style="margin-top: 0; margin-bottom: 20px;">Edit Student</h3>' +
                    '<div style="margin-bottom: 15px;">' +
                    '<label style="display: block; margin-bottom: 5px; font-weight: 600;">Membership Type:</label>' +
                    '<select id="iw-edit-membership-type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
                    '<option value="academic_module"' + (currentMembership === 'academic_module' ? ' selected' : '') + '>Academic Module</option>' +
                    '<option value="general_module"' + (currentMembership === 'general_module' ? ' selected' : '') + '>General Training Module</option>' +
                    '<option value="general_english"' + (currentMembership === 'general_english' ? ' selected' : '') + '>General English</option>' +
                    '</select>' +
                    '</div>' +
                    '<div style="margin-bottom: 20px;">' +
                    '<label style="display: block; margin-bottom: 5px; font-weight: 600;">Expiry Date (dd/mm/yyyy):</label>' +
                    '<input type="text" id="iw-edit-expiry" value="' + currentExpiry + '" placeholder="dd/mm/yyyy" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />' +
                    '</div>' +
                    '<div style="display: flex; gap: 10px; justify-content: flex-end;">' +
                    '<button id="iw-edit-cancel" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>' +
                    '<button id="iw-edit-save" style="padding: 10px 20px; border: none; background: #2271b1; color: white; border-radius: 4px; cursor: pointer;">Save Changes</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                // Add modal to page
                jQuery('body').append(modalHtml);
                
                // Handle cancel
                jQuery('#iw-edit-cancel, #iw-edit-student-modal').on('click', function(e) {
                    if (e.target === this) {
                        jQuery('#iw-edit-student-modal').remove();
                    }
                });
                
                // Handle save
                jQuery('#iw-edit-save').on('click', function() {
                    var newExpiry = jQuery('#iw-edit-expiry').val().trim();
                    var newMembershipType = jQuery('#iw-edit-membership-type').val();
                    
                    if (!newExpiry) {
                        alert('Expiry date is required');
                        return;
                    }
                    
                    if (!newMembershipType) {
                        alert('Membership type is required');
                        return;
                    }
                    
                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'iw_edit_student',
                        user_id: userId,
                        expiry: newExpiry,
                        membership_type: newMembershipType,
                        nonce: IWDashboard.editNonce
                    }, function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    });
                });
            },
            
            resendWelcome: function(userId) {
                if (!confirm('This will generate a new password and send a welcome email. Continue?')) return;
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'iw_resend_welcome',
                    user_id: userId,
                    nonce: IWDashboard.resendNonce
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                });
            },
            
            filterStudents: function(status) {
                jQuery('.iw-filter-btn[data-filter-students]').removeClass('active');
                jQuery('.iw-filter-btn[data-filter-students="' + status + '"]').addClass('active');
                
                var $table = jQuery('.iw-students-table');
                var $rows = $table.find('tbody tr');
                var visibleCount = 0;
                
                $rows.each(function() {
                    var rowStatus = jQuery(this).data('student-status');
                    if (rowStatus === status) {
                        jQuery(this).show();
                        visibleCount++;
                    } else {
                        jQuery(this).hide();
                    }
                });
                
                // Show/hide empty state messages
                jQuery('[data-empty-state]').hide();
                if (visibleCount === 0) {
                    $table.hide();
                    jQuery('[data-empty-state="' + status + '"]').show();
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
                jQuery('.iw-table tbody tr:visible').each(function() {
                    var cols = jQuery(this).find('td');
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
        
        jQuery(document).ready(function($) {
            // Collapsible card functionality
            $('.iw-card-header').on('click', function() {
                $(this).parent('.iw-card').toggleClass('collapsed expanded');
            });
            
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
            // Use setTimeout to ensure the table is fully rendered before filtering
            setTimeout(function() {
                IWDashboard.filterStudents('active');
            }, 0);
            
            // Initialize code filter to show used by default
            IWDashboard.filterCodes('used');
            
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
    
    private function render_codes_table($partner_org_id) {
        global $wpdb;
        // Safe: $wpdb->prefix is sanitized by WordPress core
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        
        // If org_id is ADMIN_ORG_ID (admin), show all codes, otherwise filter by org_id
        // Note: created_by field stores partner organization ID (which defaults to user_id for backward compatibility)
        if ($partner_org_id === self::ADMIN_ORG_ID) {
            // Use prepare even without parameters for consistency
            $codes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_date DESC LIMIT %d",
                self::CODES_TABLE_LIMIT
            ));
        } else {
            $codes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE created_by = %d ORDER BY created_date DESC LIMIT %d",
                $partner_org_id,
                self::CODES_TABLE_LIMIT
            ));
        }
        
        if (empty($codes)) {
            return '<p>No codes generated yet.</p>';
        }
        
        $html = '<table class="iw-table"><thead><tr>';
        $html .= '<th scope="col">Code</th><th scope="col">Membership</th><th scope="col">Days</th><th scope="col">Status</th><th scope="col">Used By</th><th scope="col">Created</th><th scope="col">Action</th>';
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
            $html .= '<td>' . esc_html($this->get_course_group_display_name($code->course_group)) . '</td>';
            $html .= '<td>' . esc_html($code->duration_days) . '</td>';
            $html .= '<td>' . esc_html($status_display) . '</td>';
            $html .= '<td>' . esc_html($used_by_display) . '</td>';
            $html .= '<td>' . esc_html(date('d/m/Y', strtotime($code->created_date))) . '</td>';
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
        $html .= '<th scope="col">User Details</th><th scope="col">Membership</th><th scope="col">Expiry</th><th scope="col">Actions</th>';
        $html .= '</tr></thead><tbody>';
        
        $has_active = false;
        $has_expired = false;
        
        foreach ($students as $student) {
            $user = get_userdata($student->user_id);
            if (!$user) continue;
            
            $group = get_user_meta($student->user_id, 'iw_course_group', true);
            $expiry = get_user_meta($student->user_id, 'iw_membership_expiry', true);
            $last_login = get_user_meta($student->user_id, 'last_login', true);
            
            // Determine if membership is active or expired
            $is_active = false;
            if ($expiry) {
                $expiry_timestamp = strtotime($expiry);
                $is_active = $expiry_timestamp > time();
            }
            
            if ($is_active) {
                $has_active = true;
            } else {
                $has_expired = true;
            }
            
            $status_class = $is_active ? 'active' : 'expired';
            
            // Get full name
            $full_name = trim($user->first_name . ' ' . $user->last_name);
            if (empty($full_name)) {
                $full_name = $user->display_name;
            }
            
            $html .= '<tr data-student-status="' . esc_attr($status_class) . '" data-user-id="' . esc_attr($student->user_id) . '" data-membership-type="' . esc_attr($group) . '">';
            
            // Col 1: User Details - Username, full name (smaller), email (smaller) - compact
            $html .= '<td style="line-height: 1.3;">';
            $html .= '<div style="font-weight: 600;">' . esc_html($user->user_login) . '</div>';
            $html .= '<div style="font-size: 0.9em; color: #666; margin-top: 2px;">' . esc_html($full_name) . '</div>';
            $html .= '<div style="font-size: 0.85em; color: #888; margin-top: 2px;">' . esc_html($user->user_email) . '</div>';
            $html .= '</td>';
            
            // Col 2: Membership (not "Group")
            $html .= '<td>' . esc_html($this->get_course_group_display_name($group)) . '</td>';
            
            // Col 3: Expiry with Last login underneath (smaller)
            $html .= '<td class="expiry-display" style="line-height: 1.3;">';
            $html .= '<div>' . esc_html($expiry ? date('d/m/Y', strtotime($expiry)) : '-') . '</div>';
            $html .= '<div style="font-size: 0.85em; color: #888; margin-top: 4px;">Last login: ' . esc_html($last_login ? date('d/m/Y', strtotime($last_login)) : 'Never') . '</div>';
            $html .= '</td>';
            
            // Col 4: Actions - full width buttons with spacing
            $html .= '<td>';
            $html .= '<button class="iw-btn iw-btn-full-width" onclick="IWDashboard.editStudent(' . $student->user_id . ')">Edit</button>';
            $html .= '<button class="iw-btn iw-btn-full-width" onclick="IWDashboard.resendWelcome(' . $student->user_id . ')">Resend Email</button>';
            $html .= '<button class="iw-btn iw-btn-danger iw-btn-full-width" style="margin-bottom: 0;" onclick="IWDashboard.revokeStudent(' . $student->user_id . ')">Revoke</button>';
            $html .= '</td>';
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
    
    private function get_partner_students($partner_org_id) {
        // Get all users managed by this partner organization
        // This includes users created manually and users who used access codes
        
        // If org_id is ADMIN_ORG_ID (admin), get all access code users
        if ($partner_org_id === self::ADMIN_ORG_ID) {
            $users_with_access_codes = get_users(array(
                'fields' => array('ID'),
                'meta_key' => 'iw_course_group',
                'meta_compare' => 'EXISTS'
            ));
            
            $user_ids = wp_list_pluck($users_with_access_codes, 'ID');
        } else {
            // Get users with the partner organization meta key
            $users_by_partner = get_users(array(
                'meta_key' => 'iw_created_by_partner',
                'meta_value' => $partner_org_id,
                'fields' => array('ID')
            ));
            
            $user_ids = wp_list_pluck($users_by_partner, 'ID');
        }
        
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
        // Use partner organization ID instead of individual user ID
        $partner_org_id = $this->get_partner_org_id();
        $max_students = get_option('iw_max_students_per_partner', 100);
        $active_students = $this->get_partner_students($partner_org_id);
        $active_count = count($active_students);
        $remaining_places = $max_students - $active_count;
        
        if ($quantity < 1) {
            wp_send_json_error(array('message' => 'Quantity must be at least 1'));
        }
        
        if ($quantity > $remaining_places) {
            wp_send_json_error(array('message' => sprintf(
                'You can only create %d more codes (tier limit: %d, current students: %d)',
                absint($remaining_places),
                absint($max_students),
                absint($active_count)
            )));
        }
        
        if (!array_key_exists($course_group, $this->course_groups)) {
            wp_send_json_error(array('message' => 'Invalid course group'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        $codes = array();
        
        for ($i = 0; $i < $quantity; $i++) {
            $code = $this->generate_unique_code();
            // Note: created_by stores partner organization ID (defaults to user_id for backward compatibility)
            $wpdb->insert($table, array(
                'code' => $code,
                'course_group' => $course_group,
                'duration_days' => $days,
                'status' => 'active',
                'created_by' => $partner_org_id,
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
        $send_copy_to_partner = isset($_POST['send_copy_to_partner']) && $_POST['send_copy_to_partner'] === '1';
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email'));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already exists'));
        }
        
        // Use email as username
        $username = $email;
        
        // Check if email already exists (which also checks username since they're the same)
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'An account with this email already exists'));
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
        
        // Use partner organization ID instead of individual user ID
        $partner_org_id = $this->get_partner_org_id();
        update_user_meta($user_id, 'iw_created_by_partner', $partner_org_id);
        
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        $code = $this->generate_unique_code();
        // Note: created_by stores partner organization ID (defaults to user_id for backward compatibility)
        $wpdb->insert($table, array(
            'code' => $code,
            'course_group' => $course_group,
            'duration_days' => $days,
            'status' => 'used',
            'created_by' => $partner_org_id,
            'used_by' => $user_id,
            'created_date' => current_time('mysql'),
            'used_date' => current_time('mysql')
        ));
        
        $this->send_welcome_email($user_id, $username, $password, $send_copy_to_partner);
        
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
    
    public function ajax_edit_student() {
        check_ajax_referer('iw_edit_student', 'nonce');
        
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $user_id = absint($_POST['user_id']);
        $new_expiry = sanitize_text_field($_POST['expiry']);
        $new_membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        
        if (empty($new_expiry)) {
            wp_send_json_error(array('message' => 'Expiry date is required'));
        }
        
        // Validate membership type - must be provided and valid
        $valid_membership_types = array('academic_module', 'general_module', 'general_english');
        if (empty($new_membership_type)) {
            wp_send_json_error(array('message' => 'Membership type is required'));
        }
        if (!in_array($new_membership_type, $valid_membership_types, true)) {
            wp_send_json_error(array('message' => 'Invalid membership type'));
        }
        
        // Convert dd/mm/yyyy to MySQL format using DateTime for better validation
        $date = DateTime::createFromFormat('d/m/Y', $new_expiry);
        if (!$date) {
            wp_send_json_error(array('message' => 'Invalid date format. Use dd/mm/yyyy'));
        }
        
        // Set to end of day
        $date->setTime(23, 59, 59);
        $mysql_date = $date->format('Y-m-d H:i:s');
        
        // Update expiry date
        update_user_meta($user_id, 'iw_membership_expiry', $mysql_date);
        
        // Track what was updated for the success message
        $updated_fields = array();
        $updated_fields[] = 'expiry date';
        
        // Update membership type
        $current_membership_type = get_user_meta($user_id, 'iw_course_group', true);
        
        // Only update if it's different
        if ($current_membership_type !== $new_membership_type) {
            update_user_meta($user_id, 'iw_course_group', $new_membership_type);
            $updated_fields[] = 'membership type';
            
            // Update user role to match the membership type
            $user = get_userdata($user_id);
            if ($user) {
                // Map course group to access code membership role
                $role_mapping = array(
                    'academic_module' => 'access_academic_module',
                    'general_module' => 'access_general_module',
                    'general_english' => 'access_general_english'
                );
                
                if (isset($role_mapping[$new_membership_type])) {
                    // Remove all access code roles first
                    foreach ($role_mapping as $role) {
                        $user->remove_role($role);
                    }
                    // Add the new role
                    $user->add_role($role_mapping[$new_membership_type]);
                }
            }
        }
        
        // Create dynamic success message
        $message = 'Student ' . implode(' and ', $updated_fields) . ' updated successfully';
        
        wp_send_json_success(array(
            'message' => $message,
            'new_expiry' => $date->format('d/m/Y')
        ));
    }
    
    public function ajax_resend_welcome() {
        check_ajax_referer('iw_resend_welcome', 'nonce');
        
        if (!current_user_can('manage_partner_invites') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $user_id = absint($_POST['user_id']);
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found'));
        }
        
        // Generate a new password
        $new_password = wp_generate_password(12);
        wp_set_password($new_password, $user_id);
        
        // Send welcome email with new password
        $this->send_welcome_email($user_id, $user->user_login, $new_password);
        
        wp_send_json_success(array('message' => 'Welcome email sent with new password'));
    }
    
    private function generate_unique_code() {
        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_access_codes';
        
        $attempts = 0;
        do {
            try {
                // Generate cryptographically secure 8-character alphanumeric code
                // Exclude visually ambiguous characters (0, O, 1, I, l) for better usability
                $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                $chars_length = strlen($chars);
                $code = '';
                for ($i = 0; $i < 8; $i++) {
                    $code .= $chars[random_int(0, $chars_length - 1)];
                }
            } catch (Exception $e) {
                // Fallback to wp_generate_password if random_int fails
                $code = strtoupper(substr(wp_generate_password(12, false), 0, 8));
            }
            
            // Check if code already exists (using efficient SELECT 1 query)
            // Table name is safe - uses wpdb->prefix which is controlled by WordPress
            $exists = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM $table WHERE code = %s LIMIT 1", $code));
            $attempts++;
        } while ($exists && $attempts < 10);
        
        return $code;
    }
    
    /**
     * Enroll user in courses based on their course group
     * Public method so it can be called from admin enrollment process
     * 
     * @param int $user_id User ID to enroll
     * @param string $course_group Course group (academic_module, general_module, general_english)
     */
    public function enroll_user_in_courses($user_id, $course_group) {
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
    
    private function send_welcome_email($user_id, $username, $password, $send_copy_to_partner = false) {
        $user = get_userdata($user_id);
        $login_url = get_option('iw_login_page_url', wp_login_url());
        
        $subject = 'Your IELTS Course Access';
        $message = "Hello,\n\n";
        $message .= "Your account has been created!\n\n";
        $message .= "Username: {$username}\n";
        $message .= "Password: {$password}\n\n";
        $message .= "Login here: {$login_url}\n\n";
        $message .= "Best regards,\nIELTS Course Team";
        
        // Send to student
        wp_mail($user->user_email, $subject, $message);
        
        // Send copy to partner if requested
        if ($send_copy_to_partner) {
            $partner = wp_get_current_user();
            if ($partner && $partner->user_email) {
                $partner_subject = 'Copy: Student Account Created - ' . $username;
                $partner_message = "This is a copy of the welcome email sent to your student.\n\n";
                $partner_message .= "Student Details:\n";
                $partner_message .= "Email: {$user->user_email}\n";
                $partner_message .= "Username: {$username}\n";
                $partner_message .= "Password: {$password}\n\n";
                $partner_message .= "Login URL: {$login_url}\n\n";
                $partner_message .= "The student has received a welcome email at {$user->user_email}.\n\n";
                $partner_message .= "Best regards,\nIELTS Course Team";
                
                wp_mail($partner->user_email, $partner_subject, $partner_message);
            }
        }
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
