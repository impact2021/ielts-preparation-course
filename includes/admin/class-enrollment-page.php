<?php
/**
 * Admin Enrollment Management Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Enrollment_Page {
    
    private $enrollment;
    
    public function __construct() {
        $this->enrollment = new IELTS_CM_Enrollment();
    }
    
    /**
     * Initialize the page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_post_ielts_cm_create_and_enroll_user', array($this, 'handle_create_and_enroll'));
        add_action('admin_post_ielts_cm_enroll_existing_user', array($this, 'handle_enroll_existing'));
        add_action('admin_post_ielts_cm_update_enrollment', array($this, 'handle_update_enrollment'));
        add_action('admin_post_ielts_cm_delete_enrollment', array($this, 'handle_delete_enrollment'));
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Manage Enrollments', 'ielts-course-manager'),
            __('Manage Enrollments', 'ielts-course-manager'),
            'manage_options',
            'ielts-manage-enrollments',
            array($this, 'render_page')
        );
    }
    
    /**
     * Render the enrollment management page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Get all courses
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get all users
        $users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));
        
        // Get all enrollments
        $enrollments = $this->enrollment->get_all_enrollments();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Manage Enrollments', 'ielts-course-manager'); ?></h1>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        switch ($_GET['message']) {
                            case 'user_created':
                                _e('User created and enrolled successfully!', 'ielts-course-manager');
                                break;
                            case 'user_enrolled':
                                _e('User enrolled successfully!', 'ielts-course-manager');
                                break;
                            case 'enrollment_updated':
                                _e('Enrollment updated successfully!', 'ielts-course-manager');
                                break;
                            case 'enrollment_deleted':
                                _e('Enrollment deleted successfully!', 'ielts-course-manager');
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p>
                        <?php
                        switch ($_GET['error']) {
                            case 'user_exists':
                                _e('Error: Username or email already exists.', 'ielts-course-manager');
                                break;
                            case 'create_failed':
                                _e('Error: Failed to create user.', 'ielts-course-manager');
                                break;
                            case 'enroll_failed':
                                _e('Error: Failed to enroll user.', 'ielts-course-manager');
                                break;
                            case 'missing_data':
                                _e('Error: Missing required data.', 'ielts-course-manager');
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Create New User and Enroll -->
            <div class="ielts-cm-enrollment-section">
                <h2><?php _e('Create New User and Enroll', 'ielts-course-manager'); ?></h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="ielts-cm-enrollment-form">
                    <?php wp_nonce_field('ielts_cm_create_and_enroll', 'ielts_cm_create_and_enroll_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_create_and_enroll_user">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="new_username"><?php _e('Username', 'ielts-course-manager'); ?> *</label></th>
                            <td><input type="text" id="new_username" name="username" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_email"><?php _e('Email', 'ielts-course-manager'); ?> *</label></th>
                            <td><input type="email" id="new_email" name="email" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_password"><?php _e('Password', 'ielts-course-manager'); ?> *</label></th>
                            <td><input type="password" id="new_password" name="password" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_first_name"><?php _e('First Name', 'ielts-course-manager'); ?></label></th>
                            <td><input type="text" id="new_first_name" name="first_name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_last_name"><?php _e('Last Name', 'ielts-course-manager'); ?></label></th>
                            <td><input type="text" id="new_last_name" name="last_name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="new_courses"><?php _e('Enroll in Courses', 'ielts-course-manager'); ?> *</label></th>
                            <td>
                                <select id="new_courses" name="courses[]" multiple size="10" required style="width: 100%; max-width: 400px;">
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo esc_attr($course->ID); ?>">
                                            <?php echo esc_html($course->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Hold Ctrl (Cmd on Mac) to select multiple courses. Default duration: 1 year.', 'ielts-course-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Create User and Enroll', 'ielts-course-manager')); ?>
                </form>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <!-- Enroll Existing User -->
            <div class="ielts-cm-enrollment-section">
                <h2><?php _e('Enroll Existing User', 'ielts-course-manager'); ?></h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="ielts-cm-enrollment-form">
                    <?php wp_nonce_field('ielts_cm_enroll_existing', 'ielts_cm_enroll_existing_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_enroll_existing_user">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="existing_user"><?php _e('Select User', 'ielts-course-manager'); ?> *</label></th>
                            <td>
                                <select id="existing_user" name="user_id" required class="regular-text">
                                    <option value=""><?php _e('-- Select User --', 'ielts-course-manager'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>">
                                            <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="existing_courses"><?php _e('Enroll in Courses', 'ielts-course-manager'); ?> *</label></th>
                            <td>
                                <select id="existing_courses" name="courses[]" multiple size="10" required style="width: 100%; max-width: 400px;">
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo esc_attr($course->ID); ?>">
                                            <?php echo esc_html($course->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Hold Ctrl (Cmd on Mac) to select multiple courses. Default duration: 1 year.', 'ielts-course-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Enroll User', 'ielts-course-manager')); ?>
                </form>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <!-- Current Enrollments -->
            <div class="ielts-cm-enrollment-section">
                <h2><?php _e('Current Enrollments', 'ielts-course-manager'); ?></h2>
                
                <?php if (empty($enrollments)): ?>
                    <p><?php _e('No enrollments found.', 'ielts-course-manager'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('User', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Course', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Enrolled Date', 'ielts-course-manager'); ?></th>
                                <th><?php _e('End Date', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                                <th><?php _e('Actions', 'ielts-course-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): 
                                $user = get_userdata($enrollment->user_id);
                                $course = get_post($enrollment->course_id);
                                if (!$user || !$course) continue;
                            ?>
                                <tr>
                                    <td><?php echo esc_html($user->display_name); ?></td>
                                    <td><?php echo esc_html($course->post_title); ?></td>
                                    <td><?php echo esc_html(date('Y-m-d', strtotime($enrollment->enrolled_date))); ?></td>
                                    <td>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                                            <?php wp_nonce_field('ielts_cm_update_enrollment', 'ielts_cm_update_enrollment_nonce'); ?>
                                            <input type="hidden" name="action" value="ielts_cm_update_enrollment">
                                            <input type="hidden" name="user_id" value="<?php echo esc_attr($enrollment->user_id); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo esc_attr($enrollment->course_id); ?>">
                                            <input type="date" name="end_date" value="<?php echo $enrollment->course_end_date ? esc_attr(date('Y-m-d', strtotime($enrollment->course_end_date))) : ''; ?>" style="width: 150px;">
                                            <button type="submit" class="button button-small"><?php _e('Update', 'ielts-course-manager'); ?></button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="enrollment-status status-<?php echo esc_attr($enrollment->status); ?>">
                                            <?php echo esc_html(ucfirst($enrollment->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this enrollment?', 'ielts-course-manager'); ?>');">
                                            <?php wp_nonce_field('ielts_cm_delete_enrollment', 'ielts_cm_delete_enrollment_nonce'); ?>
                                            <input type="hidden" name="action" value="ielts_cm_delete_enrollment">
                                            <input type="hidden" name="user_id" value="<?php echo esc_attr($enrollment->user_id); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo esc_attr($enrollment->course_id); ?>">
                                            <button type="submit" class="button button-small button-link-delete"><?php _e('Delete', 'ielts-course-manager'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .ielts-cm-enrollment-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        .ielts-cm-enrollment-section h2 {
            margin-top: 0;
        }
        .enrollment-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .enrollment-status.status-active {
            background: #d4edda;
            color: #155724;
        }
        .enrollment-status.status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        </style>
        <?php
    }
    
    /**
     * Handle create and enroll user
     */
    public function handle_create_and_enroll() {
        // Check nonce
        if (!isset($_POST['ielts_cm_create_and_enroll_nonce']) || 
            !wp_verify_nonce($_POST['ielts_cm_create_and_enroll_nonce'], 'ielts_cm_create_and_enroll')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'ielts-course-manager'));
        }
        
        // Validate input
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['courses'])) {
            wp_redirect(add_query_arg('error', 'missing_data', wp_get_referer()));
            exit;
        }
        
        // Create user
        $user_id = wp_create_user(
            sanitize_user($_POST['username']),
            $_POST['password'],
            sanitize_email($_POST['email'])
        );
        
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('error', 'user_exists', wp_get_referer()));
            exit;
        }
        
        // Update user meta
        if (!empty($_POST['first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        }
        if (!empty($_POST['last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
        }
        
        // Enroll in courses
        $courses = array_map('intval', $_POST['courses']);
        foreach ($courses as $course_id) {
            $this->enrollment->enroll($user_id, $course_id);
        }
        
        wp_redirect(add_query_arg('message', 'user_created', wp_get_referer()));
        exit;
    }
    
    /**
     * Handle enroll existing user
     */
    public function handle_enroll_existing() {
        // Check nonce
        if (!isset($_POST['ielts_cm_enroll_existing_nonce']) || 
            !wp_verify_nonce($_POST['ielts_cm_enroll_existing_nonce'], 'ielts_cm_enroll_existing')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'ielts-course-manager'));
        }
        
        // Validate input
        if (empty($_POST['user_id']) || empty($_POST['courses'])) {
            wp_redirect(add_query_arg('error', 'missing_data', wp_get_referer()));
            exit;
        }
        
        $user_id = intval($_POST['user_id']);
        $courses = array_map('intval', $_POST['courses']);
        
        // Enroll in courses
        foreach ($courses as $course_id) {
            $this->enrollment->enroll($user_id, $course_id);
        }
        
        wp_redirect(add_query_arg('message', 'user_enrolled', wp_get_referer()));
        exit;
    }
    
    /**
     * Handle update enrollment
     */
    public function handle_update_enrollment() {
        // Check nonce
        if (!isset($_POST['ielts_cm_update_enrollment_nonce']) || 
            !wp_verify_nonce($_POST['ielts_cm_update_enrollment_nonce'], 'ielts_cm_update_enrollment')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'ielts-course-manager'));
        }
        
        // Validate input
        if (empty($_POST['user_id']) || empty($_POST['course_id']) || empty($_POST['end_date'])) {
            wp_redirect(add_query_arg('error', 'missing_data', wp_get_referer()));
            exit;
        }
        
        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        // Set end time to 23:59:59 so course access extends through the full end date
        $end_date = sanitize_text_field($_POST['end_date']) . ' 23:59:59';
        
        // Update enrollment
        $this->enrollment->update_course_end_date($user_id, $course_id, $end_date);
        
        wp_redirect(add_query_arg('message', 'enrollment_updated', wp_get_referer()));
        exit;
    }
    
    /**
     * Handle delete enrollment
     */
    public function handle_delete_enrollment() {
        // Check nonce
        if (!isset($_POST['ielts_cm_delete_enrollment_nonce']) || 
            !wp_verify_nonce($_POST['ielts_cm_delete_enrollment_nonce'], 'ielts_cm_delete_enrollment')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions', 'ielts-course-manager'));
        }
        
        // Validate input
        if (empty($_POST['user_id']) || empty($_POST['course_id'])) {
            wp_redirect(add_query_arg('error', 'missing_data', wp_get_referer()));
            exit;
        }
        
        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        
        // Delete enrollment
        $this->enrollment->unenroll($user_id, $course_id);
        
        wp_redirect(add_query_arg('message', 'enrollment_deleted', wp_get_referer()));
        exit;
    }
}
