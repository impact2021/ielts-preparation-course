<?php
/**
 * Plugin uninstall script
 * 
 * This file is called when the plugin is uninstalled
 */

// Exit if accessed directly or not from WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to delete all data
$delete_data = get_option('ielts_cm_delete_data_on_uninstall', false);

if ($delete_data) {
    // Load required files
    require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
    
    // Delete all plugin data from custom tables
    IELTS_CM_Database::drop_tables();
    
    // Delete all posts of custom post types
    $post_types = array('ielts_course', 'ielts_lesson', 'ielts_resource', 'ielts_quiz');
    
    foreach ($post_types as $post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($posts as $post) {
            // Force delete (skip trash)
            wp_delete_post($post->ID, true);
        }
    }
    
    // Delete taxonomy terms
    $taxonomy = 'ielts_course_category';
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false
    ));
    
    foreach ($terms as $term) {
        wp_delete_term($term->term_id, $taxonomy);
    }
    
    // Delete all plugin options
    delete_option('ielts_cm_version');
    delete_option('ielts_cm_delete_data_on_uninstall');
    
    // Clear permalinks
    flush_rewrite_rules();
} else {
    // Only delete the setting that controls data deletion
    // Keep all course data, progress, and other settings
    delete_option('ielts_cm_delete_data_on_uninstall');
}
