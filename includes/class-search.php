<?php
/**
 * Search functionality for IELTS Course Manager
 * 
 * Extends WordPress search to search inside:
 * - Transcripts (_ielts_cm_transcript)
 * - Reading passages (_ielts_cm_reading_passage)
 * - Quiz questions (_ielts_cm_questions)
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Search {
    
    /**
     * Initialize search functionality
     */
    public function init() {
        add_action('pre_get_posts', array($this, 'search_custom_post_types'));
        add_filter('posts_join', array($this, 'search_join'), 10, 2);
        add_filter('posts_where', array($this, 'search_where'), 10, 2);
        add_filter('posts_groupby', array($this, 'search_groupby'), 10, 2);
    }
    
    /**
     * Limit search to specific custom post types
     */
    public function search_custom_post_types($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $query->set('post_type', array(
                'ielts_resource',
                'ielts_lesson',
                'ielts_course',
                'ielts_quiz'
            ));
        }
    }
    
    /**
     * Join postmeta table to search meta fields
     */
    public function search_join($join, $query) {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $join .= " LEFT JOIN {$wpdb->postmeta} AS search_meta ON {$wpdb->posts}.ID = search_meta.post_id";
        }
        
        return $join;
    }
    
    /**
     * Extend WHERE clause to search in meta fields
     */
    public function search_where($where, $query) {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $search_term = $query->get('s');
            
            if (!empty($search_term)) {
                // Escape the search term
                $search_term = $wpdb->esc_like($search_term);
                $search_term = '%' . $search_term . '%';
                
                // Add meta fields to search
                // Search in: transcripts, reading passages, and questions
                $meta_where = " OR (";
                $meta_where .= "search_meta.meta_key = '_ielts_cm_transcript' AND search_meta.meta_value LIKE %s";
                $meta_where .= " OR search_meta.meta_key = '_ielts_cm_reading_passage' AND search_meta.meta_value LIKE %s";
                $meta_where .= " OR search_meta.meta_key = '_ielts_cm_questions' AND search_meta.meta_value LIKE %s";
                $meta_where .= ")";
                
                $meta_where = $wpdb->prepare($meta_where, $search_term, $search_term, $search_term);
                
                // Insert the meta search before the last closing parenthesis
                $where = preg_replace(
                    '/\)\s*$/',
                    $meta_where . ')',
                    $where,
                    1
                );
            }
        }
        
        return $where;
    }
    
    /**
     * Group by post ID to prevent duplicates from meta join
     */
    public function search_groupby($groupby, $query) {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $groupby = "{$wpdb->posts}.ID";
        }
        
        return $groupby;
    }
}
