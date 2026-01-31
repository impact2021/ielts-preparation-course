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
    
    private $course_groups = array(
        'academic_english' => 'IELTS Academic + English',
        'general_english' => 'IELTS General Training + English',
        'english_only' => 'General English Only',
        'all_courses' => 'All Courses'
    );
    
    private $course_group_descriptions = array(
        'academic_english' => 'Includes IELTS Academic module and General English courses',
        'general_english' => 'Includes IELTS General Training module and General English courses',
        'english_only' => 'Only General English courses (no IELTS content)',
        'all_courses' => 'Full access to all courses (Academic, General Training, and English)'
    );
    
    public function __construct() {
        $this->create_partner_admin_role();
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
                        <td><input type="number" name="iw_max_students_per_partner" value="<?php echo esc_attr($max_students); ?>" min="1" class="regular-text"></td>
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
            .iw-welcome { background: #e7f3ff; padding: 15px; border-left: 4px solid #0073aa; margin-bottom: 20px; }
        </style>
        
        <div class="iw-dashboard">
            <div class="iw-welcome">
                <?php 
                $current_user = wp_get_current_user();
                $display_name = $current_user->display_name ?: $current_user->user_login;
                ?>
                <h2>Welcome, <?php echo esc_html($display_name); ?>!</h2>
                <p><strong>Active Students:</strong> <?php echo $active_count; ?> / <?php echo $max_students; ?></p>
            </div>
            
            <div class="iw-card collapsed">
                <div class="iw-card-header">
                    <h2>Create Invite Codes</h2>
                </div>
                <div class="iw-card-body">
                    <div id="create-invite-msg"></div>
                    <form id="create-invite-form">
                        <?php wp_nonce_field('iw_create_invite', 'iw_create_invite_nonce'); ?>
                        <table class="iw-form-table">
                            <tr>
                                <th>Number of Codes (1-10):</th>
                                <td><input type="number" name="quantity" min="1" max="10" value="1" required></td>
                            </tr>
                            <tr>
                                <th>Course Group:</th>
                                <td>
                                    <select name="course_group" required>
                                        <?php foreach ($this->course_groups as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                        <strong>What's included:</strong><br>
                                        • <strong>IELTS Academic + English:</strong> IELTS Academic module + General English<br>
                                        • <strong>IELTS General Training + English:</strong> IELTS General Training + General English<br>
                                        • <strong>General English Only:</strong> Only General English courses<br>
                                        • <strong>All Courses:</strong> Complete access to all modules
                                    </p>
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
            
            <div class="iw-card collapsed">
                <div class="iw-card-header">
                    <h2>Managed Students</h2>
                </div>
                <div class="iw-card-body">
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
            $('.iw-filter-btn').on('click', function() {
                var filter = $(this).data('filter');
                IWDashboard.filterCodes(filter);
            });
            
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
            "SELECT * FROM $table WHERE created_by = %d ORDER BY created_at DESC LIMIT 100",
            $partner_id
        ));
        
        if (empty($codes)) {
            return '<p>No codes generated yet.</p>';
        }
        
        $html = '<table class="iw-table"><thead><tr>';
        $html .= '<th>Code</th><th>Group</th><th>Days</th><th>Status</th><th>Used By</th><th>Created</th><th>Action</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($codes as $code) {
            $used_by = '-';
            if ($code->used_by) {
                $user = get_userdata($code->used_by);
                $used_by = $user ? $user->user_login : 'Deleted User';
            }
            
            // Determine actual status for filtering
            $filter_status = $code->status;
            
            $html .= '<tr data-status="' . esc_attr($filter_status) . '">';
            $html .= '<td>' . esc_html($code->code) . '</td>';
            $html .= '<td>' . esc_html($this->course_groups[$code->course_group] ?? $code->course_group) . '</td>';
            $html .= '<td>' . esc_html($code->access_days) . '</td>';
            $html .= '<td>' . esc_html($code->status) . '</td>';
            $html .= '<td>' . esc_html($used_by) . '</td>';
            $html .= '<td>' . esc_html(date('Y-m-d', strtotime($code->created_at))) . '</td>';
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
            return '<p>No students managed yet.</p>';
        }
        
        $html = '<table class="iw-table"><thead><tr>';
        $html .= '<th>Username</th><th>Email</th><th>Group</th><th>Expiry</th><th>Action</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($students as $student) {
            $user = get_userdata($student->user_id);
            if (!$user) continue;
            
            $group = get_user_meta($student->user_id, 'iw_course_group', true);
            $expiry = get_user_meta($student->user_id, 'iw_membership_expiry', true);
            
            $html .= '<tr>';
            $html .= '<td>' . esc_html($user->user_login) . '</td>';
            $html .= '<td>' . esc_html($user->user_email) . '</td>';
            $html .= '<td>' . esc_html($this->course_groups[$group] ?? $group) . '</td>';
            $html .= '<td>' . esc_html($expiry ? date('Y-m-d', strtotime($expiry)) : '-') . '</td>';
            $html .= '<td><button class="iw-btn iw-btn-danger" onclick="IWDashboard.revokeStudent(' . $student->user_id . ')">Revoke</button></td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
    
    private function get_partner_students($partner_id) {
        // Get all users managed by this partner
        // This includes users created manually and users who used access codes
        $users = get_users(array(
            'meta_key' => 'iw_created_by_partner',
            'meta_value' => $partner_id,
            'fields' => array('ID')
        ));
        
        // Return in format compatible with existing code
        $results = array();
        foreach ($users as $user) {
            $results[] = (object) array('user_id' => $user->ID);
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
        
        if ($quantity < 1 || $quantity > 10) {
            wp_send_json_error(array('message' => 'Quantity must be 1-10'));
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
                'access_days' => $days,
                'status' => 'active',
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
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
            'access_days' => $days,
            'status' => 'used',
            'created_by' => get_current_user_id(),
            'used_by' => $user_id,
            'created_at' => current_time('mysql'),
            'used_at' => current_time('mysql')
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
            $code = 'IELTS-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE code = %s", $code));
            $attempts++;
        } while ($exists > 0 && $attempts < 10);
        
        return $code;
    }
    
    private function enroll_user_in_courses($user_id, $course_group) {
        $courses = array();
        
        switch ($course_group) {
            case 'academic_english':
                $courses = array('ielts_academic', 'general_english');
                break;
            case 'general_english':
                $courses = array('ielts_general', 'general_english');
                break;
            case 'english_only':
                $courses = array('general_english');
                break;
            case 'all_courses':
                $courses = array('ielts_academic', 'ielts_general', 'general_english');
                break;
        }
        
        foreach ($courses as $course) {
            update_user_meta($user_id, "enrolled_{$course}", true);
        }
    }
    
    private function remove_user_enrollments($user_id) {
        delete_user_meta($user_id, 'enrolled_ielts_academic');
        delete_user_meta($user_id, 'enrolled_ielts_general');
        delete_user_meta($user_id, 'enrolled_general_english');
    }
    
    private function set_ielts_membership($user_id, $course_group, $expiry_date) {
        update_user_meta($user_id, 'iw_course_group', $course_group);
        update_user_meta($user_id, 'iw_membership_expiry', $expiry_date);
        update_user_meta($user_id, 'iw_membership_status', 'active');
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
}
