<?php
/**
 * Plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Activator {
    
    public static function activate() {
        // Create database tables
        IELTS_CM_Database::create_tables();
        
        // Register post types before flushing to ensure proper rewrite rules
        require_once IELTS_CM_PLUGIN_DIR . 'includes/class-post-types.php';
        $post_types = new IELTS_CM_Post_Types();
        $post_types->register_post_types();
        
        // Flush rewrite rules to register custom post type permalinks
        flush_rewrite_rules();
        
        // Set or update version option
        $current_version = get_option('ielts_cm_version');
        if (!$current_version) {
            add_option('ielts_cm_version', IELTS_CM_VERSION);
        } elseif ($current_version !== IELTS_CM_VERSION) {
            update_option('ielts_cm_version', IELTS_CM_VERSION);
        }
        
        // Enable membership system by default if not already set
        if (get_option('ielts_cm_membership_enabled') === false) {
            add_option('ielts_cm_membership_enabled', 1);
        }
        
        // Create default categories and courses if they don't exist
        self::create_default_content();
    }
    
    /**
     * Create default categories and sample courses
     * Only runs if no courses exist in the system
     */
    private static function create_default_content() {
        // Check if courses already exist
        $existing_courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => 1,
            'post_status' => 'any',
            'fields' => 'ids'
        ));
        
        // If courses already exist, don't create defaults
        if (!empty($existing_courses)) {
            return;
        }
        
        // Create default categories
        $categories = self::create_default_categories();
        
        // Create default sample courses
        $courses_created = self::create_default_courses($categories);
        
        // Set a transient to show admin notice about created content
        if ($courses_created > 0) {
            set_transient('ielts_cm_default_content_created', array(
                'courses' => $courses_created,
                'categories' => count($categories)
            ), 300); // 5 minutes
        }
    }
    
    /**
     * Create default course categories
     * @return array Array of category term IDs keyed by slug
     */
    private static function create_default_categories() {
        $categories_to_create = array(
            'academic' => array(
                'name' => 'Academic',
                'description' => 'Academic IELTS courses for university admission and professional registration'
            ),
            'general' => array(
                'name' => 'General Training',
                'description' => 'General Training IELTS courses for work and migration'
            ),
            'academic-practice-tests' => array(
                'name' => 'Academic Practice Tests',
                'description' => 'Full-length practice tests for Academic IELTS'
            ),
            'general-practice-tests' => array(
                'name' => 'General Practice Tests',
                'description' => 'Full-length practice tests for General Training IELTS'
            )
        );
        
        $created_categories = array();
        
        foreach ($categories_to_create as $slug => $category_data) {
            // Check if category already exists
            $existing = term_exists($slug, 'ielts_course_category');
            
            if ($existing) {
                $created_categories[$slug] = (int) $existing['term_id'];
            } else {
                // Create the category
                $result = wp_insert_term(
                    $category_data['name'],
                    'ielts_course_category',
                    array(
                        'description' => $category_data['description'],
                        'slug' => $slug
                    )
                );
                
                if (!is_wp_error($result)) {
                    $created_categories[$slug] = (int) $result['term_id'];
                }
            }
        }
        
        return $created_categories;
    }
    
    /**
     * Create default sample courses
     * @param array $categories Array of category term IDs
     * @return int Number of courses created
     */
    private static function create_default_courses($categories) {
        $courses_to_create = array(
            array(
                'title' => 'Academic IELTS Reading Skills',
                'content' => '<p>Master the essential reading skills needed for Academic IELTS success.</p><p>This comprehensive course covers all question types, strategies, and practice materials you need to achieve your target band score.</p><h3>What You\'ll Learn:</h3><ul><li>Understanding different question types</li><li>Time management strategies</li><li>Vocabulary building techniques</li><li>Practice with authentic materials</li></ul>',
                'categories' => array('academic'),
                'status' => 'publish'
            ),
            array(
                'title' => 'Academic IELTS Writing Task 1 & 2',
                'content' => '<p>Develop your academic writing skills for both Task 1 (describing charts, graphs, and diagrams) and Task 2 (essay writing).</p><h3>Course Contents:</h3><ul><li>Task 1: Charts, graphs, tables, and processes</li><li>Task 2: Opinion, discussion, and problem-solution essays</li><li>Grammar and vocabulary for academic writing</li><li>Sample answers and feedback</li></ul>',
                'categories' => array('academic'),
                'status' => 'publish'
            ),
            array(
                'title' => 'Academic IELTS Practice Test 1',
                'content' => '<p>Full-length Academic IELTS practice test with all four sections: Listening, Reading, Writing, and Speaking.</p><p>Complete this test under exam conditions to assess your current level and identify areas for improvement.</p>',
                'categories' => array('academic-practice-tests', 'academic'),
                'status' => 'publish'
            )
        );
        
        $created_count = 0;
        
        foreach ($courses_to_create as $course_data) {
            // Create the course post
            $post_id = wp_insert_post(array(
                'post_title' => $course_data['title'],
                'post_content' => $course_data['content'],
                'post_status' => $course_data['status'],
                'post_type' => 'ielts_course',
                'post_author' => 1 // Admin user
            ));
            
            if (!is_wp_error($post_id) && $post_id > 0) {
                // Assign categories to the course
                $term_ids = array();
                foreach ($course_data['categories'] as $category_slug) {
                    if (isset($categories[$category_slug])) {
                        $term_ids[] = $categories[$category_slug];
                    }
                }
                
                if (!empty($term_ids)) {
                    wp_set_post_terms($post_id, $term_ids, 'ielts_course_category');
                }
                
                $created_count++;
                
                // Log successful creation
                error_log("IELTS Course Manager: Created default course - {$course_data['title']} (ID: {$post_id})");
            }
        }
        
        return $created_count;
    }
}
