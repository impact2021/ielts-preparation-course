<?php
/**
 * Awards wall template
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
?>

<div class="ielts-awards-container">
    <div class="ielts-awards-header">
        <h2><?php _e('Your Achievements', 'ielts-course-manager'); ?></h2>
        <p class="awards-subtitle"><?php _e('Earn badges, shields, and trophies as you progress through your IELTS preparation', 'ielts-course-manager'); ?></p>
    </div>
    
    <div class="ielts-awards-tabs">
        <button class="awards-tab active" data-tab="all"><?php _e('All Awards', 'ielts-course-manager'); ?></button>
        <button class="awards-tab" data-tab="badge"><?php _e('Badges', 'ielts-course-manager'); ?></button>
        <button class="awards-tab" data-tab="shield"><?php _e('Shields', 'ielts-course-manager'); ?></button>
        <button class="awards-tab" data-tab="trophy"><?php _e('Trophies', 'ielts-course-manager'); ?></button>
    </div>
    
    <div class="ielts-awards-wall" id="ielts-awards-wall">
        <div class="awards-loading">
            <?php _e('Loading awards...', 'ielts-course-manager'); ?>
        </div>
    </div>
</div>
