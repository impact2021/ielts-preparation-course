<?php
/**
 * Registration form template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ielts-ms-register-wrapper">
    <div class="ielts-ms-form-container">
        <h2><?php _e('Register', 'ielts-membership-system'); ?></h2>
        <p><?php _e('Create an account to purchase a membership and access IELTS preparation courses.', 'ielts-membership-system'); ?></p>
        
        <form id="ielts-ms-register-form" class="ielts-ms-form">
            <div class="ielts-ms-form-group">
                <label for="reg_username"><?php _e('Username', 'ielts-membership-system'); ?> *</label>
                <input type="text" id="reg_username" name="username" required>
            </div>
            
            <div class="ielts-ms-form-group">
                <label for="reg_email"><?php _e('Email', 'ielts-membership-system'); ?> *</label>
                <input type="email" id="reg_email" name="email" required>
            </div>
            
            <div class="ielts-ms-form-group">
                <label for="reg_password"><?php _e('Password', 'ielts-membership-system'); ?> *</label>
                <input type="password" id="reg_password" name="password" required>
                <small><?php _e('Minimum 8 characters', 'ielts-membership-system'); ?></small>
            </div>
            
            <div class="ielts-ms-form-group">
                <label for="reg_confirm_password"><?php _e('Confirm Password', 'ielts-membership-system'); ?> *</label>
                <input type="password" id="reg_confirm_password" name="confirm_password" required>
            </div>
            
            <div class="ielts-ms-form-group">
                <button type="submit" class="ielts-ms-btn ielts-ms-btn-primary">
                    <?php _e('Register', 'ielts-membership-system'); ?>
                </button>
            </div>
            
            <div class="ielts-ms-message"></div>
        </form>
        
        <div class="ielts-ms-form-footer">
            <p><?php _e('Already have an account?', 'ielts-membership-system'); ?> 
                <a href="<?php echo get_permalink(get_page_by_path('membership-login')); ?>">
                    <?php _e('Login', 'ielts-membership-system'); ?>
                </a>
            </p>
        </div>
    </div>
</div>
