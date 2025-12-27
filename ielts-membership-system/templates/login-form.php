<?php
/**
 * Login form template
 */

if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'login';
?>

<div class="ielts-ms-login-wrapper">
    <?php if ($action === 'reset_password' && isset($_GET['key']) && isset($_GET['login'])): ?>
        <!-- Reset Password Form -->
        <div class="ielts-ms-form-container">
            <h2><?php _e('Reset Password', 'ielts-membership-system'); ?></h2>
            
            <form id="ielts-ms-reset-password-form" class="ielts-ms-form">
                <div class="ielts-ms-form-group">
                    <label for="new_password"><?php _e('New Password', 'ielts-membership-system'); ?></label>
                    <input type="password" id="new_password" name="password" required>
                </div>
                
                <div class="ielts-ms-form-group">
                    <label for="confirm_password"><?php _e('Confirm Password', 'ielts-membership-system'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <input type="hidden" name="key" value="<?php echo esc_attr($_GET['key']); ?>">
                <input type="hidden" name="login" value="<?php echo esc_attr($_GET['login']); ?>">
                
                <div class="ielts-ms-form-group">
                    <button type="submit" class="ielts-ms-btn ielts-ms-btn-primary">
                        <?php _e('Reset Password', 'ielts-membership-system'); ?>
                    </button>
                </div>
                
                <div class="ielts-ms-message"></div>
            </form>
        </div>
    
    <?php elseif ($action === 'forgot_password'): ?>
        <!-- Forgot Password Form -->
        <div class="ielts-ms-form-container">
            <h2><?php _e('Forgot Password', 'ielts-membership-system'); ?></h2>
            <p><?php _e('Enter your username or email address. You will receive a link to create a new password via email.', 'ielts-membership-system'); ?></p>
            
            <form id="ielts-ms-forgot-password-form" class="ielts-ms-form">
                <div class="ielts-ms-form-group">
                    <label for="user_login"><?php _e('Username or Email', 'ielts-membership-system'); ?></label>
                    <input type="text" id="user_login" name="user_login" required>
                </div>
                
                <div class="ielts-ms-form-group">
                    <button type="submit" class="ielts-ms-btn ielts-ms-btn-primary">
                        <?php _e('Send Reset Link', 'ielts-membership-system'); ?>
                    </button>
                </div>
                
                <div class="ielts-ms-message"></div>
            </form>
            
            <div class="ielts-ms-form-footer">
                <a href="<?php echo remove_query_arg('action'); ?>"><?php _e('Back to Login', 'ielts-membership-system'); ?></a>
            </div>
        </div>
    
    <?php else: ?>
        <!-- Login Form -->
        <div class="ielts-ms-form-container">
            <h2><?php _e('Login', 'ielts-membership-system'); ?></h2>
            
            <form id="ielts-ms-login-form" class="ielts-ms-form">
                <div class="ielts-ms-form-group">
                    <label for="username"><?php _e('Username or Email', 'ielts-membership-system'); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="ielts-ms-form-group">
                    <label for="password"><?php _e('Password', 'ielts-membership-system'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="ielts-ms-form-group">
                    <label class="ielts-ms-checkbox">
                        <input type="checkbox" name="remember" value="1">
                        <?php _e('Remember Me', 'ielts-membership-system'); ?>
                    </label>
                </div>
                
                <div class="ielts-ms-form-group">
                    <button type="submit" class="ielts-ms-btn ielts-ms-btn-primary">
                        <?php _e('Login', 'ielts-membership-system'); ?>
                    </button>
                </div>
                
                <div class="ielts-ms-message"></div>
            </form>
            
            <div class="ielts-ms-form-footer">
                <a href="<?php echo add_query_arg('action', 'forgot_password'); ?>"><?php _e('Forgot Password?', 'ielts-membership-system'); ?></a>
                |
                <a href="<?php echo get_permalink(get_page_by_path('membership-register')); ?>"><?php _e('Register', 'ielts-membership-system'); ?></a>
            </div>
            
            <div class="ielts-ms-legacy-notice">
                <p>
                    <a href="https://www.ieltstestonline.com/older-version/" class="ielts-ms-legacy-link">
                        <?php _e('I enrolled before 1st January 2026', 'ielts-membership-system'); ?>
                    </a>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
