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

// Load required files
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';

// Delete all plugin data (uncomment if you want to remove data on uninstall)
// IELTS_CM_Database::drop_tables();

// Delete options
delete_option('ielts_cm_version');

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

// Clear permalinks
flush_rewrite_rules();
