<?php
/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Admin {
    
    private $processing_quiz_save = false;
    
    public function init() {
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add columns to post lists
        add_filter('manage_ielts_course_posts_columns', array($this, 'course_columns'));
        add_action('manage_ielts_course_posts_custom_column', array($this, 'course_column_content'), 10, 2);
        
        add_filter('manage_ielts_lesson_posts_columns', array($this, 'lesson_columns'));
        add_action('manage_ielts_lesson_posts_custom_column', array($this, 'lesson_column_content'), 10, 2);
        
        // Add AJAX handlers
        add_action('wp_ajax_ielts_cm_update_lesson_order', array($this, 'ajax_update_lesson_order'));
        add_action('wp_ajax_ielts_cm_update_page_order', array($this, 'ajax_update_page_order'));
        add_action('wp_ajax_ielts_cm_update_content_order', array($this, 'ajax_update_content_order'));
        add_action('wp_ajax_ielts_cm_push_to_subsites', array($this, 'ajax_push_to_subsites'));
        add_action('wp_ajax_ielts_cm_get_lessons_by_courses', array($this, 'ajax_get_lessons_by_courses'));
        add_action('wp_ajax_ielts_cm_add_lesson_to_course', array($this, 'ajax_add_lesson_to_course'));
        add_action('wp_ajax_ielts_cm_remove_lesson_from_course', array($this, 'ajax_remove_lesson_from_course'));
        add_action('wp_ajax_ielts_cm_remove_content_from_lesson', array($this, 'ajax_remove_content_from_lesson'));
        add_action('wp_ajax_ielts_cm_get_available_exercises', array($this, 'ajax_get_available_exercises'));
        add_action('wp_ajax_ielts_cm_get_available_sublessons', array($this, 'ajax_get_available_sublessons'));
        add_action('wp_ajax_ielts_cm_add_content_to_lesson', array($this, 'ajax_add_content_to_lesson'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add LearnDash quiz conversion button
        add_filter('manage_sfwd-quiz_posts_columns', array($this, 'learndash_quiz_columns'));
        add_action('manage_sfwd-quiz_posts_custom_column', array($this, 'learndash_quiz_column_content'), 10, 2);
        add_action('admin_footer', array($this, 'learndash_quiz_conversion_scripts'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'quiz_validation_notices'));
    }
    
    /**
     * Display admin notices for quiz validation
     */
    public function quiz_validation_notices() {
        // Check for validation notice transient
        $user_id = get_current_user_id();
        if (get_transient('ielts_cm_no_questions_' . $user_id)) {
            delete_transient('ielts_cm_no_questions_' . $user_id);
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php _e('Exercise not published:', 'ielts-course-manager'); ?></strong>
                    <?php _e('You must add at least one question before publishing an exercise. The exercise has been saved as a draft.', 'ielts-course-manager'); ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Course meta box
        add_meta_box(
            'ielts_cm_course_meta',
            __('Course Settings', 'ielts-course-manager'),
            array($this, 'course_meta_box'),
            'ielts_course',
            'normal',
            'high'
        );
        
        // Course lessons meta box
        add_meta_box(
            'ielts_cm_course_lessons',
            __('Course Lessons', 'ielts-course-manager'),
            array($this, 'course_lessons_meta_box'),
            'ielts_course',
            'normal',
            'high'
        );
        
        // Lesson meta box
        add_meta_box(
            'ielts_cm_lesson_meta',
            __('Lesson Settings', 'ielts-course-manager'),
            array($this, 'lesson_meta_box'),
            'ielts_lesson',
            'normal',
            'high'
        );
        
        // Lesson pages and exercises meta box
        add_meta_box(
            'ielts_cm_lesson_content',
            __('Lesson Content (Pages & Exercises)', 'ielts-course-manager'),
            array($this, 'lesson_content_meta_box'),
            'ielts_lesson',
            'normal',
            'high'
        );
        
        // Lesson page meta box
        add_meta_box(
            'ielts_cm_resource_meta',
            __('Lesson page Settings', 'ielts-course-manager'),
            array($this, 'resource_meta_box'),
            'ielts_resource',
            'normal',
            'high'
        );
        
        // Quiz meta box
        add_meta_box(
            'ielts_cm_quiz_meta',
            __('Quiz Settings', 'ielts-course-manager'),
            array($this, 'quiz_meta_box'),
            'ielts_quiz',
            'normal',
            'high'
        );
        
        // Multi-site sync meta box (only for primary sites)
        $sync_manager = new IELTS_CM_Multi_Site_Sync();
        if ($sync_manager->is_primary_site()) {
            add_meta_box(
                'ielts_cm_sync_meta',
                __('Push to Subsites', 'ielts-course-manager'),
                array($this, 'sync_meta_box'),
                array('ielts_course', 'ielts_lesson', 'ielts_resource', 'ielts_quiz'),
                'side',
                'default'
            );
        }
    }
    
    /**
     * Course meta box
     */
    public function course_meta_box($post) {
        wp_nonce_field('ielts_cm_course_meta', 'ielts_cm_course_meta_nonce');
        ?>
        <p>
            <?php _e('Use the Course Lessons meta box below to manage and reorder lessons for this course.', 'ielts-course-manager'); ?>
        </p>
        <?php
    }
    
    /**
     * Course lessons meta box - display and reorder lessons
     */
    public function course_lessons_meta_box($post) {
        // Get lessons for this course
        global $wpdb;
        $lesson_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_course_ids' AND meta_value LIKE %s)
        ", $post->ID, '%' . $wpdb->esc_like(serialize(strval($post->ID))) . '%'));
        
        $lessons = array();
        if (!empty($lesson_ids)) {
            $lessons = get_posts(array(
                'post_type' => 'ielts_lesson',
                'posts_per_page' => -1,
                'post__in' => $lesson_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ));
        }
        ?>
        <div id="ielts-cm-course-lessons">
            <div style="margin-bottom: 15px;">
                <h4><?php _e('Add Lessons to Course', 'ielts-course-manager'); ?></h4>
                <input type="text" id="course-lesson-search" placeholder="<?php _e('Search lessons...', 'ielts-course-manager'); ?>" style="width: 100%; margin-bottom: 10px;">
                <select id="course-lesson-selector" style="width: 100%; height: 100px;" size="5">
                    <?php
                    // Get all lessons not already in this course
                    $all_lessons = get_posts(array(
                        'post_type' => 'ielts_lesson',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'post_status' => array('publish', 'draft')
                    ));
                    
                    $current_lesson_ids = array_map(function($l) { return $l->ID; }, $lessons);
                    foreach ($all_lessons as $all_lesson):
                        if (!in_array($all_lesson->ID, $current_lesson_ids)):
                    ?>
                        <option value="<?php echo esc_attr($all_lesson->ID); ?>"><?php echo esc_html($all_lesson->post_title); ?></option>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </select>
                <button type="button" class="button" id="add-lesson-to-course" style="margin-top: 5px;"><?php _e('Add Selected Lesson', 'ielts-course-manager'); ?></button>
            </div>
            
            <?php if (empty($lessons)): ?>
                <p><?php _e('No lessons have been assigned to this course yet.', 'ielts-course-manager'); ?></p>
            <?php else: ?>
                <h4><?php _e('Course Lessons', 'ielts-course-manager'); ?></h4>
                <p><?php _e('Drag and drop lessons to reorder them:', 'ielts-course-manager'); ?></p>
                <ul id="course-lessons-sortable" class="course-lessons-list">
                    <?php foreach ($lessons as $lesson): ?>
                        <li class="lesson-item" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                            <span class="dashicons dashicons-menu"></span>
                            <span class="lesson-title"><?php echo esc_html($lesson->post_title); ?></span>
                            <span class="lesson-order"><?php printf(__('Order: %d', 'ielts-course-manager'), $lesson->menu_order); ?></span>
                            <a href="<?php echo get_edit_post_link($lesson->ID); ?>" class="button button-small" target="_blank">
                                <?php _e('Edit', 'ielts-course-manager'); ?>
                            </a>
                            <button type="button" class="button button-small remove-lesson-from-course" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                                <?php _e('Remove', 'ielts-course-manager'); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="lesson-order-status"></div>
            <?php endif; ?>
        </div>
        
        <style>
        #ielts-cm-course-lessons .course-lessons-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        #ielts-cm-course-lessons .lesson-item {
            padding: 12px;
            margin-bottom: 5px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            cursor: move;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #ielts-cm-course-lessons .lesson-item:hover {
            background: #f0f0f0;
        }
        #ielts-cm-course-lessons .lesson-item .dashicons-menu {
            color: #999;
        }
        #ielts-cm-course-lessons .lesson-item .lesson-title {
            flex: 1;
            font-weight: 500;
        }
        #ielts-cm-course-lessons .lesson-item .lesson-order {
            color: #666;
            font-size: 12px;
        }
        #ielts-cm-course-lessons .lesson-item.ui-sortable-helper {
            opacity: 0.8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        #ielts-cm-course-lessons .lesson-item.ui-sortable-placeholder {
            background: #e0e0e0;
            border: 2px dashed #999;
            visibility: visible !important;
        }
        #ielts-cm-course-lessons .lesson-order-status {
            margin-top: 10px;
            padding: 8px;
            display: none;
        }
        #ielts-cm-course-lessons .lesson-order-status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            display: block;
        }
        #ielts-cm-course-lessons .lesson-order-status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            display: block;
        }
        </style>
        <?php
    }
    
    /**
     * Lesson meta box
     */
    public function lesson_meta_box($post) {
        wp_nonce_field('ielts_cm_lesson_meta', 'ielts_cm_lesson_meta_nonce');
        
        // Support for multiple courses - store as array
        $course_ids = get_post_meta($post->ID, '_ielts_cm_course_ids', true);
        
        // Ensure we have an array - handle serialized strings
        if (is_string($course_ids) && !empty($course_ids)) {
            // If it's a serialized string, unserialize it
            $unserialized = maybe_unserialize($course_ids);
            $course_ids = is_array($unserialized) ? $unserialized : array();
        } elseif (!is_array($course_ids)) {
            $course_ids = array();
        }
        
        if (empty($course_ids)) {
            // Backward compatibility - check old single course_id
            $old_course_id = get_post_meta($post->ID, '_ielts_cm_course_id', true);
            $course_ids = $old_course_id ? array($old_course_id) : array();
        }
        
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <p>
            <label for="ielts_cm_course_ids"><?php _e('Assign to Courses', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_course_ids" name="ielts_cm_course_ids[]" multiple style="width: 100%; height: 150px;">
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo esc_attr($course->ID); ?>" <?php echo in_array($course->ID, $course_ids) ? 'selected' : ''; ?>>
                        <?php echo esc_html($course->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small><?php _e('Hold Ctrl (Cmd on Mac) to select multiple courses', 'ielts-course-manager'); ?></small>
        </p>
        <?php
    }
    
    /**
     * Lesson content meta box - display and reorder lesson pages and exercises together
     */
    public function lesson_content_meta_box($post) {
        // Get lesson pages for this lesson
        global $wpdb;
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
        ", $post->ID, '%' . $wpdb->esc_like(serialize(strval($post->ID))) . '%'));
        
        $resources = array();
        if (!empty($resource_ids)) {
            $resources = get_posts(array(
                'post_type' => 'ielts_resource',
                'posts_per_page' => -1,
                'post__in' => $resource_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ));
        }
        
        // Get quizzes for this lesson
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
        ", $post->ID, '%' . $wpdb->esc_like(serialize(strval($post->ID))) . '%'));
        
        $quizzes = array();
        if (!empty($quiz_ids)) {
            $quizzes = get_posts(array(
                'post_type' => 'ielts_quiz',
                'posts_per_page' => -1,
                'post__in' => $quiz_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ));
        }
        
        // Combine resources and quizzes into a single array
        $content_items = array();
        foreach ($resources as $resource) {
            $content_items[] = array(
                'id' => $resource->ID,
                'title' => $resource->post_title,
                'type' => 'resource',
                'order' => $resource->menu_order
            );
        }
        foreach ($quizzes as $quiz) {
            $content_items[] = array(
                'id' => $quiz->ID,
                'title' => $quiz->post_title,
                'type' => 'quiz',
                'order' => $quiz->menu_order
            );
        }
        
        // Sort by menu_order
        usort($content_items, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        ?>
        <div id="ielts-cm-lesson-content">
            <div style="margin-bottom: 15px;">
                <h4><?php _e('Add Content to Lesson', 'ielts-course-manager'); ?></h4>
                <label>
                    <input type="radio" name="content-type-selector" value="sublesson" checked> <?php _e('Sub Lessons', 'ielts-course-manager'); ?>
                </label>
                <label style="margin-left: 15px;">
                    <input type="radio" name="content-type-selector" value="exercise"> <?php _e('Exercises', 'ielts-course-manager'); ?>
                </label>
                
                <div style="margin-top: 10px;">
                    <input type="text" id="lesson-content-search" placeholder="<?php _e('Search...', 'ielts-course-manager'); ?>" style="width: 100%; margin-bottom: 10px;">
                    <select id="lesson-content-selector" style="width: 100%; height: 100px;" size="5">
                        <?php
                        // Get all resources (sublessons) not already in this lesson
                        $all_resources = get_posts(array(
                            'post_type' => 'ielts_resource',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC',
                            'post_status' => array('publish', 'draft')
                        ));
                        
                        $current_resource_ids = array_filter(array_map(function($i) { 
                            return $i['type'] === 'resource' ? $i['id'] : null; 
                        }, $content_items));
                        
                        foreach ($all_resources as $resource):
                            if (!in_array($resource->ID, $current_resource_ids)):
                        ?>
                            <option value="<?php echo esc_attr($resource->ID); ?>" data-type="sublesson"><?php echo esc_html($resource->post_title); ?></option>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </select>
                    <button type="button" class="button" id="add-content-to-lesson" style="margin-top: 5px;"><?php _e('Add Selected Content', 'ielts-course-manager'); ?></button>
                </div>
            </div>
            
            <?php if (empty($content_items)): ?>
                <p><?php _e('No lesson pages or exercises have been assigned to this lesson yet.', 'ielts-course-manager'); ?></p>
            <?php else: ?>
                <h4><?php _e('Lesson Content', 'ielts-course-manager'); ?></h4>
                <p><?php _e('Drag and drop items to reorder them. You can mix lesson pages and exercises in any order:', 'ielts-course-manager'); ?></p>
                <ul id="lesson-content-sortable" class="lesson-content-list">
                    <?php foreach ($content_items as $item): ?>
                        <li class="content-item content-item-<?php echo esc_attr($item['type']); ?>" 
                            data-item-id="<?php echo esc_attr($item['id']); ?>" 
                            data-item-type="<?php echo esc_attr($item['type']); ?>">
                            <span class="dashicons dashicons-menu"></span>
                            <span class="item-type-badge <?php echo esc_attr($item['type']); ?>">
                                <?php echo $item['type'] === 'quiz' ? __('Exercise', 'ielts-course-manager') : __('Page', 'ielts-course-manager'); ?>
                            </span>
                            <span class="item-title"><?php echo esc_html($item['title']); ?></span>
                            <span class="item-order"><?php printf(__('Order: %d', 'ielts-course-manager'), $item['order']); ?></span>
                            <a href="<?php echo get_edit_post_link($item['id']); ?>" class="button button-small" target="_blank">
                                <?php _e('Edit', 'ielts-course-manager'); ?>
                            </a>
                            <button type="button" class="button button-small remove-content-from-lesson" data-content-id="<?php echo esc_attr($item['id']); ?>" data-content-type="<?php echo esc_attr($item['type']); ?>">
                                <?php _e('Remove', 'ielts-course-manager'); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="content-order-status"></div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load exercises when radio button is changed
            $('input[name="content-type-selector"]').on('change', function() {
                var contentType = $(this).val();
                var lessonId = $('#post_ID').val();
                
                if (contentType === 'exercise') {
                    // Load exercises via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ielts_cm_get_available_exercises',
                            nonce: '<?php echo wp_create_nonce('ielts_cm_lesson_content'); ?>',
                            lesson_id: lessonId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#lesson-content-selector').empty();
                                $.each(response.data.exercises, function(i, exercise) {
                                    $('#lesson-content-selector').append(
                                        $('<option></option>')
                                            .attr('value', exercise.id)
                                            .attr('data-type', 'exercise')
                                            .text(exercise.title)
                                    );
                                });
                            }
                        }
                    });
                } else {
                    // Load sublessons (resources)
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ielts_cm_get_available_sublessons',
                            nonce: '<?php echo wp_create_nonce('ielts_cm_lesson_content'); ?>',
                            lesson_id: lessonId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#lesson-content-selector').empty();
                                $.each(response.data.sublessons, function(i, sublesson) {
                                    $('#lesson-content-selector').append(
                                        $('<option></option>')
                                            .attr('value', sublesson.id)
                                            .attr('data-type', 'sublesson')
                                            .text(sublesson.title)
                                    );
                                });
                            }
                        }
                    });
                }
            });
        });
        </script>
        
        <style>
        #ielts-cm-lesson-content .lesson-content-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        #ielts-cm-lesson-content .content-item {
            padding: 12px;
            margin-bottom: 5px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            cursor: move;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #ielts-cm-lesson-content .content-item:hover {
            background: #f0f0f0;
        }
        #ielts-cm-lesson-content .content-item .dashicons-menu {
            color: #999;
        }
        #ielts-cm-lesson-content .content-item .item-type-badge {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        #ielts-cm-lesson-content .content-item .item-type-badge.resource {
            background: #e3f2fd;
            color: #1976d2;
        }
        #ielts-cm-lesson-content .content-item .item-type-badge.quiz {
            background: #fff3e0;
            color: #f57c00;
        }
        #ielts-cm-lesson-content .content-item .item-title {
            flex: 1;
            font-weight: 500;
        }
        #ielts-cm-lesson-content .content-item .item-order {
            color: #666;
            font-size: 12px;
        }
        #ielts-cm-lesson-content .content-item.ui-sortable-helper {
            opacity: 0.8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        #ielts-cm-lesson-content .content-item.ui-sortable-placeholder {
            background: #e0e0e0;
            border: 2px dashed #999;
            visibility: visible !important;
        }
        #ielts-cm-lesson-content .content-order-status {
            margin-top: 10px;
            padding: 8px;
            display: none;
        }
        #ielts-cm-lesson-content .content-order-status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            display: block;
        }
        #ielts-cm-lesson-content .content-order-status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            display: block;
        }
        </style>
        <?php
    }
    
    /**
     * Lesson page meta box
     */
    public function resource_meta_box($post) {
        wp_nonce_field('ielts_cm_resource_meta', 'ielts_cm_resource_meta_nonce');
        
        // Support for multiple lessons - store as array
        $lesson_ids = get_post_meta($post->ID, '_ielts_cm_lesson_ids', true);
        
        // Ensure we have an array - handle serialized strings
        if (is_string($lesson_ids) && !empty($lesson_ids)) {
            // If it's a serialized string, unserialize it
            $unserialized = maybe_unserialize($lesson_ids);
            $lesson_ids = is_array($unserialized) ? $unserialized : array();
        } elseif (!is_array($lesson_ids)) {
            $lesson_ids = array();
        }
        
        if (empty($lesson_ids)) {
            // Backward compatibility - check old single lesson_id
            $old_lesson_id = get_post_meta($post->ID, '_ielts_cm_lesson_id', true);
            $lesson_ids = $old_lesson_id ? array($old_lesson_id) : array();
        }
        $resource_url = get_post_meta($post->ID, '_ielts_cm_resource_url', true);
        
        $lessons = get_posts(array(
            'post_type' => 'ielts_lesson',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <p>
            <label for="ielts_cm_lesson_ids"><?php _e('Assign to Lessons', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_lesson_ids" name="ielts_cm_lesson_ids[]" multiple style="width: 100%; height: 150px;">
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?php echo esc_attr($lesson->ID); ?>" <?php echo in_array($lesson->ID, $lesson_ids) ? 'selected' : ''; ?>>
                        <?php echo esc_html($lesson->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small><?php _e('Hold Ctrl (Cmd on Mac) to select multiple lessons', 'ielts-course-manager'); ?></small>
        </p>
        <p>
            <label for="ielts_cm_resource_url"><?php _e('Resource URL (Optional)', 'ielts-course-manager'); ?></label><br>
            <input type="url" id="ielts_cm_resource_url" name="ielts_cm_resource_url" value="<?php echo esc_attr($resource_url); ?>" style="width: 100%;">
            <small><?php _e('Add a URL for external resources if needed', 'ielts-course-manager'); ?></small>
        </p>
        <?php
    }
    
    /**
     * Quiz meta box
     */
    public function quiz_meta_box($post) {
        wp_nonce_field('ielts_cm_quiz_meta', 'ielts_cm_quiz_meta_nonce');
        
        // Support for multiple courses - store as array
        $course_ids = get_post_meta($post->ID, '_ielts_cm_course_ids', true);
        
        // Ensure we have an array - handle serialized strings
        if (is_string($course_ids) && !empty($course_ids)) {
            // If it's a serialized string, unserialize it
            $unserialized = maybe_unserialize($course_ids);
            $course_ids = is_array($unserialized) ? $unserialized : array();
        } elseif (!is_array($course_ids)) {
            $course_ids = array();
        }
        
        if (empty($course_ids)) {
            // Backward compatibility - check old single course_id
            $old_course_id = get_post_meta($post->ID, '_ielts_cm_course_id', true);
            $course_ids = $old_course_id ? array($old_course_id) : array();
        }
        
        // Support for multiple lessons - store as array
        $lesson_ids = get_post_meta($post->ID, '_ielts_cm_lesson_ids', true);
        
        // Ensure we have an array - handle serialized strings
        if (is_string($lesson_ids) && !empty($lesson_ids)) {
            // If it's a serialized string, unserialize it
            $unserialized = maybe_unserialize($lesson_ids);
            $lesson_ids = is_array($unserialized) ? $unserialized : array();
        } elseif (!is_array($lesson_ids)) {
            $lesson_ids = array();
        }
        
        if (empty($lesson_ids)) {
            // Backward compatibility - check old single lesson_id
            $old_lesson_id = get_post_meta($post->ID, '_ielts_cm_lesson_id', true);
            $lesson_ids = $old_lesson_id ? array($old_lesson_id) : array();
        }
        
        $questions = get_post_meta($post->ID, '_ielts_cm_questions', true);
        $pass_percentage = get_post_meta($post->ID, '_ielts_cm_pass_percentage', true);
        $layout_type = get_post_meta($post->ID, '_ielts_cm_layout_type', true);
        $reading_texts = get_post_meta($post->ID, '_ielts_cm_reading_texts', true);
        $exercise_label = get_post_meta($post->ID, '_ielts_cm_exercise_label', true);
        
        if (!$questions) {
            $questions = array();
        }
        
        if (!$layout_type) {
            $layout_type = 'standard';
        }
        
        if (!$reading_texts) {
            $reading_texts = array();
        }
        
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $lessons = get_posts(array(
            'post_type' => 'ielts_lesson',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <p>
            <label for="ielts_cm_quiz_course_ids"><?php _e('Assign to Courses', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_quiz_course_ids" name="ielts_cm_quiz_course_ids[]" multiple style="width: 100%; height: 150px;">
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo esc_attr($course->ID); ?>" <?php echo in_array($course->ID, $course_ids) ? 'selected' : ''; ?>>
                        <?php echo esc_html($course->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small><?php _e('Hold Ctrl (Cmd on Mac) to select multiple courses', 'ielts-course-manager'); ?></small>
        </p>
        <p>
            <label for="ielts_cm_quiz_lesson_ids"><?php _e('Assign to Lessons (Optional)', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_quiz_lesson_ids" name="ielts_cm_quiz_lesson_ids[]" multiple style="width: 100%; height: 150px;">
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?php echo esc_attr($lesson->ID); ?>" <?php echo in_array($lesson->ID, $lesson_ids) ? 'selected' : ''; ?>>
                        <?php echo esc_html($lesson->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small><?php _e('Hold Ctrl (Cmd on Mac) to select multiple lessons', 'ielts-course-manager'); ?></small>
        </p>
        
        <p>
            <label for="ielts_cm_exercise_label"><?php _e('Display Label for Students', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_exercise_label" name="ielts_cm_exercise_label" style="width: 100%;">
                <option value="exercise" <?php selected($exercise_label, 'exercise'); ?>><?php _e('Exercise', 'ielts-course-manager'); ?></option>
                <option value="end_of_lesson_test" <?php selected($exercise_label, 'end_of_lesson_test'); ?>><?php _e('End of lesson test', 'ielts-course-manager'); ?></option>
                <option value="practice_test" <?php selected($exercise_label, 'practice_test'); ?>><?php _e('Practice test', 'ielts-course-manager'); ?></option>
            </select>
            <small><?php _e('Choose how this exercise will be labeled on the frontend for students. This does not change the backend label.', 'ielts-course-manager'); ?></small>
        </p>
        
        <p style="display: none;">
            <label for="ielts_cm_pass_percentage"><?php _e('Pass Percentage', 'ielts-course-manager'); ?></label><br>
            <input type="number" id="ielts_cm_pass_percentage" name="ielts_cm_pass_percentage" value="<?php echo esc_attr($pass_percentage ? $pass_percentage : 70); ?>" min="0" max="100" style="width: 100%;">
        </p>
        
        <p>
            <label for="ielts_cm_layout_type"><?php _e('Layout Type', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_layout_type" name="ielts_cm_layout_type" style="width: 100%;">
                <option value="standard" <?php selected($layout_type, 'standard'); ?>><?php _e('Standard Layout', 'ielts-course-manager'); ?></option>
                <option value="computer_based" <?php selected($layout_type, 'computer_based'); ?>><?php _e('Computer-Based IELTS Layout (Two Columns)', 'ielts-course-manager'); ?></option>
            </select>
            <small><?php _e('Computer-Based layout displays reading text on the left and questions on the right, similar to the actual IELTS computer test.', 'ielts-course-manager'); ?></small>
        </p>
        
        <?php
        $open_as_popup = get_post_meta($post->ID, '_ielts_cm_open_as_popup', true);
        ?>
        <div id="cbt-popup-option" style="<?php echo ($layout_type !== 'computer_based') ? 'display:none;' : ''; ?>">
            <p>
                <label>
                    <input type="checkbox" id="ielts_cm_open_as_popup" name="ielts_cm_open_as_popup" value="1" <?php checked($open_as_popup, '1'); ?>>
                    <?php _e('Open as Popup/Fullscreen Modal', 'ielts-course-manager'); ?>
                </label><br>
                <small><?php _e('When checked, the CBT exercise will open in a fullscreen popup modal. When unchecked, it opens in the same window.', 'ielts-course-manager'); ?></small>
            </p>
        </div>
        
        <?php
        $scoring_type = get_post_meta($post->ID, '_ielts_cm_scoring_type', true);
        if (!$scoring_type) {
            $scoring_type = 'percentage';
        }
        ?>
        <p>
            <label for="ielts_cm_scoring_type"><?php _e('Scoring Type', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_scoring_type" name="ielts_cm_scoring_type" style="width: 100%;">
                <option value="percentage" <?php selected($scoring_type, 'percentage'); ?>><?php _e('Percentage (Standard)', 'ielts-course-manager'); ?></option>
                <option value="ielts_general_reading" <?php selected($scoring_type, 'ielts_general_reading'); ?>><?php _e('IELTS General Training Reading (Band Score)', 'ielts-course-manager'); ?></option>
                <option value="ielts_academic_reading" <?php selected($scoring_type, 'ielts_academic_reading'); ?>><?php _e('IELTS Academic Reading (Band Score)', 'ielts-course-manager'); ?></option>
                <option value="ielts_listening" <?php selected($scoring_type, 'ielts_listening'); ?>><?php _e('IELTS Listening (Band Score)', 'ielts-course-manager'); ?></option>
            </select>
            <small><?php _e('Choose how results are displayed. For IELTS Reading and Listening exercises, results will show as band scores (0-9) instead of percentages.', 'ielts-course-manager'); ?></small>
        </p>
        
        <?php
        $timer_minutes = get_post_meta($post->ID, '_ielts_cm_timer_minutes', true);
        ?>
        <p>
            <label for="ielts_cm_timer_minutes"><?php _e('Timer (Minutes)', 'ielts-course-manager'); ?></label><br>
            <input type="number" id="ielts_cm_timer_minutes" name="ielts_cm_timer_minutes" value="<?php echo esc_attr($timer_minutes); ?>" min="0" step="1" style="width: 100%;" placeholder="<?php _e('Leave empty for no timer', 'ielts-course-manager'); ?>">
            <small><?php _e('Set a time limit in minutes. The exercise will automatically submit when time expires, regardless of completion status. Leave empty for no timer.', 'ielts-course-manager'); ?></small>
        </p>
        
        <div id="reading-texts-section" style="<?php echo ($layout_type !== 'computer_based') ? 'display:none;' : ''; ?>">
            <h3><?php _e('Reading Texts', 'ielts-course-manager'); ?></h3>
            <p><small><?php _e('Add reading passages that will be displayed in the left column. You can link specific questions to each reading text.', 'ielts-course-manager'); ?></small></p>
            
            <div id="reading-texts-container">
                <?php if (!empty($reading_texts)): ?>
                    <?php foreach ($reading_texts as $index => $text): ?>
                        <?php $this->render_reading_text_field($index, $text); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="add-reading-text"><?php _e('Add Reading Text', 'ielts-course-manager'); ?></button>
        </div>
        
        <div id="ielts-cm-questions">
            <h3><?php _e('Questions', 'ielts-course-manager'); ?></h3>
            
            <?php if (empty($questions)): ?>
                <div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px;">
                    <p>
                        <strong><?php _e('Important:', 'ielts-course-manager'); ?></strong>
                        <?php _e('You must add at least one question before this exercise can be published. Click "Add Question" below to get started.', 'ielts-course-manager'); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <p><small><?php _e('Drag and drop questions to reorder them:', 'ielts-course-manager'); ?></small></p>
            
            <div id="questions-container">
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $index => $question): ?>
                        <?php $this->render_question_field($index, $question); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="add-question"><?php _e('Add Question', 'ielts-course-manager'); ?></button>
            
            <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #72aee6;">
                <h4 style="margin-top: 0;"><?php _e('Question Type Guidelines:', 'ielts-course-manager'); ?></h4>
                <ul style="margin-bottom: 0;">
                    <li><strong><?php _e('Multiple Choice:', 'ielts-course-manager'); ?></strong> <?php _e('Enter options one per line. Correct answer is the option number (0 for first, 1 for second, etc.)', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Multi Select:', 'ielts-course-manager'); ?></strong> <?php _e('Students can select multiple answers. Mark all correct options. Students earn 1 point for each correct selection. Set the maximum number of selections and total points accordingly.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('True/False/Not Given:', 'ielts-course-manager'); ?></strong> <?php _e('Enter correct answer as "true", "false", or "not_given" (lowercase)', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Fill in the Blank:', 'ielts-course-manager'); ?></strong> <?php _e('Enter the expected answer. Matching is case-insensitive and ignores punctuation/extra spaces.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Essay:', 'ielts-course-manager'); ?></strong> <?php _e('No correct answer needed - requires manual grading.', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        #questions-container .ui-sortable-placeholder {
            background: #e0e0e0;
            border: 2px dashed #999;
            visibility: visible !important;
            height: 100px;
            margin-bottom: 15px;
        }
        #questions-container .question-item.ui-sortable-helper {
            opacity: 0.8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .question-drag-handle:hover {
            color: #555 !important;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var questionIndex = <?php echo intval(count($questions)); ?>;
            var readingTextIndex = <?php echo intval(count($reading_texts)); ?>;
            
            // Localized strings
            var i18n = {
                readingText: <?php echo json_encode(__('Reading Text', 'ielts-course-manager')); ?>,
                titleOptional: <?php echo json_encode(__('Title (Optional)', 'ielts-course-manager')); ?>,
                placeholderPassage: <?php echo json_encode(__('e.g., Passage 1', 'ielts-course-manager')); ?>,
                placeholderEnterText: <?php echo json_encode(__('Enter the reading passage here...', 'ielts-course-manager')); ?>,
                removeReadingText: <?php echo json_encode(__('Remove Reading Text', 'ielts-course-manager')); ?>
            };
            
            // Layout type change handler
            $('#ielts_cm_layout_type').on('change', function() {
                if ($(this).val() === 'computer_based') {
                    $('#reading-texts-section').show();
                    $('#cbt-popup-option').show();
                } else {
                    $('#reading-texts-section').hide();
                    $('#cbt-popup-option').hide();
                }
            });
            
            // Add reading text
            $('#add-reading-text').on('click', function() {
                var html = '<div class="reading-text-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9; position: relative;">' +
                    '<div class="reading-text-header" style="display: flex; align-items: center; cursor: pointer; margin-bottom: 15px;">' +
                    '<span class="dashicons dashicons-arrow-down-alt2 reading-text-toggle" style="color: #666; margin-right: 8px; transition: transform 0.2s;"></span>' +
                    '<h4 style="margin: 0; flex: 1;">' + i18n.readingText + ' ' + (readingTextIndex + 1) + '</h4>' +
                    '</div>' +
                    '<div class="reading-text-content">' +
                    '<p>' +
                    '<label>' + i18n.titleOptional + '</label><br>' +
                    '<input type="text" name="reading_texts[' + readingTextIndex + '][title]" style="width: 100%;" placeholder="' + i18n.placeholderPassage + '">' +
                    '</p>' +
                    '<p>' +
                    '<label>' + i18n.readingText + '</label><br>' +
                    '<textarea name="reading_texts[' + readingTextIndex + '][content]" rows="10" style="width: 100%;" placeholder="' + i18n.placeholderEnterText + '"></textarea>' +
                    '</p>' +
                    '<button type="button" class="button remove-reading-text">' + i18n.removeReadingText + '</button>' +
                    '</div>' +
                    '</div>';
                $('#reading-texts-container').append(html);
                readingTextIndex++;
                updateReadingTextSelectors();
            });
            
            // Remove reading text
            $(document).on('click', '.remove-reading-text', function() {
                $(this).closest('.reading-text-item').remove();
                updateReadingTextSelectors();
            });
            
            // Localized reading text label for fallback
            var readingTextLabel = <?php echo json_encode(__('Reading Text', 'ielts-course-manager')); ?>;
            
            // Function to build reading text selector HTML
            function buildReadingTextSelector(questionIdx) {
                var readingTexts = [];
                $('.reading-text-item').each(function(idx) {
                    var title = $(this).find('input[name*="[title]"]').val() || (readingTextLabel + ' ' + (idx + 1));
                    readingTexts.push({index: idx, title: title});
                });
                
                if (readingTexts.length === 0) {
                    return '';
                }
                
                var html = '<p class="reading-text-link-field">' +
                    '<label><?php _e('Linked Reading Text (Optional)', 'ielts-course-manager'); ?></label><br>' +
                    '<select name="questions[' + questionIdx + '][reading_text_id]" style="width: 100%;">' +
                    '<option value=""><?php _e('-- No specific reading text --', 'ielts-course-manager'); ?></option>';
                
                $.each(readingTexts, function(i, rt) {
                    html += '<option value="' + rt.index + '">' + rt.title + '</option>';
                });
                
                html += '</select>' +
                    '<small><?php _e('When this question is scrolled into view, the selected reading text will be displayed on the left.', 'ielts-course-manager'); ?></small>' +
                    '</p>';
                
                return html;
            }
            
            // Function to update all reading text selectors
            function updateReadingTextSelectors() {
                $('.question-item').each(function(idx) {
                    var $question = $(this);
                    var $existingSelector = $question.find('.reading-text-link-field');
                    var currentValue = $existingSelector.find('select').val();
                    
                    // Remove existing selector
                    $existingSelector.remove();
                    
                    // Add new selector after question type
                    var newSelector = buildReadingTextSelector(idx);
                    if (newSelector) {
                        $question.find('select.question-type').closest('p').after(newSelector);
                        // Restore previous value if it still exists
                        if (currentValue) {
                            $question.find('.reading-text-link-field select').val(currentValue);
                        }
                    }
                });
            }
            
            // Update selectors when reading text titles change
            $(document).on('input', '.reading-text-item input[name*="[title]"]', function() {
                updateReadingTextSelectors();
            });
            
            // Function to check and update warning visibility
            function updateQuestionWarning() {
                var questionCount = $('#questions-container .question-item').length;
                var $warning = $('#ielts-cm-questions .notice-warning');
                
                if (questionCount === 0) {
                    if ($warning.length === 0) {
                        $('#questions-container').before(
                            '<div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px;">' +
                            '<p><strong><?php _e('Important:', 'ielts-course-manager'); ?></strong> ' +
                            '<?php _e('You must add at least one question before this exercise can be published. Click "Add Question" below to get started.', 'ielts-course-manager'); ?>' +
                            '</p></div>'
                        );
                    }
                } else {
                    $warning.remove();
                }
            }
            
            $('#add-question').on('click', function() {
                var template = <?php echo json_encode($this->get_question_template()); ?>;
                var html = template.replace(/QUESTION_INDEX/g, questionIndex);
                $('#questions-container').append(html);
                
                // Add reading text selector if reading texts exist
                var readingTextSelector = buildReadingTextSelector(questionIndex);
                if (readingTextSelector) {
                    $('#questions-container .question-item:last').find('select.question-type').closest('p').after(readingTextSelector);
                }
                
                questionIndex++;
                updateQuestionWarning();
            });
            
            $(document).on('click', '.remove-question', function() {
                $(this).closest('.question-item').remove();
                updateQuestionWarning();
            });
            
            $(document).on('change', '.question-type', function() {
                var type = $(this).val();
                var container = $(this).closest('.question-item');
                var correctAnswerField = container.find('.correct-answer-field');
                var correctAnswerInput = correctAnswerField.find('input, select');
                var currentValue = correctAnswerInput.val() || '';
                
                if (type === 'multiple_choice') {
                    container.find('.mc-options-field').show();
                    container.find('.multi-select-settings').hide();
                    container.find('.general-feedback-field').hide();
                    correctAnswerField.hide();
                } else if (type === 'multi_select') {
                    container.find('.mc-options-field').show();
                    container.find('.multi-select-settings').show();
                    container.find('.general-feedback-field').show();
                    correctAnswerField.hide();
                } else if (type === 'true_false') {
                    container.find('.mc-options-field').hide();
                    container.find('.multi-select-settings').hide();
                    container.find('.general-feedback-field').show();
                    correctAnswerField.find('label').text('<?php _e('Correct Answer', 'ielts-course-manager'); ?>');
                    
                    // Convert to dropdown if it's currently an input
                    if (correctAnswerInput.is('input')) {
                        var fieldName = correctAnswerInput.attr('name');
                        var selectHtml = '<select name="' + fieldName + '" style="width: 100%;">' +
                            '<option value=""><?php _e('-- Select correct answer --', 'ielts-course-manager'); ?></option>' +
                            '<option value="true"><?php _e('True', 'ielts-course-manager'); ?></option>' +
                            '<option value="false"><?php _e('False', 'ielts-course-manager'); ?></option>' +
                            '<option value="not_given"><?php _e('Not Given', 'ielts-course-manager'); ?></option>' +
                            '</select>';
                        correctAnswerInput.replaceWith(selectHtml);
                        correctAnswerField.find('select').val(currentValue);
                    }
                    correctAnswerField.show();
                } else if (type === 'fill_blank') {
                    container.find('.mc-options-field').hide();
                    container.find('.multi-select-settings').hide();
                    container.find('.general-feedback-field').show();
                    correctAnswerField.find('label').text('<?php _e('Correct Answer (use | to separate multiple accepted answers)', 'ielts-course-manager'); ?>');
                    
                    // Convert to input if it's currently a dropdown
                    if (correctAnswerInput.is('select')) {
                        var fieldName = correctAnswerInput.attr('name');
                        var inputHtml = '<input type="text" name="' + fieldName + '" value="' + currentValue + '" style="width: 100%;">';
                        correctAnswerInput.replaceWith(inputHtml);
                    }
                    correctAnswerField.show();
                } else if (type === 'summary_completion') {
                    container.find('.mc-options-field').hide();
                    container.find('.multi-select-settings').hide();
                    container.find('.general-feedback-field').show();
                    correctAnswerField.find('label').text('<?php _e('Correct Answer (use | to separate multiple accepted answers)', 'ielts-course-manager'); ?>');
                    
                    // Convert to input if it's currently a dropdown
                    if (correctAnswerInput.is('select')) {
                        var fieldName = correctAnswerInput.attr('name');
                        var inputHtml = '<input type="text" name="' + fieldName + '" value="' + currentValue + '" style="width: 100%;">';
                        correctAnswerInput.replaceWith(inputHtml);
                    }
                    correctAnswerField.show();
                } else if (type === 'essay') {
                    container.find('.mc-options-field').hide();
                    container.find('.multi-select-settings').hide();
                    container.find('.general-feedback-field').show();
                    correctAnswerField.hide();
                }
            });
            
            // Add multiple choice option
            $(document).on('click', '.add-mc-option', function() {
                var questionIndex = $(this).data('question-index');
                var container = $(this).siblings('.mc-options-container');
                var optionIndex = container.find('.mc-option-item').length;
                
                var optionHtml = '<div class="mc-option-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #fff;">' +
                    '<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px;">' +
                    '<div style="flex: 0 0 30px;">' +
                    '<label style="cursor: pointer; display: block;">' +
                    '<input type="checkbox" name="questions[' + questionIndex + '][mc_options][' + optionIndex + '][is_correct]" value="1" style="margin: 5px 0 0 0;">' +
                    '<small style="display: block; margin-top: 3px;"><?php _e('Correct', 'ielts-course-manager'); ?></small>' +
                    '</label>' +
                    '</div>' +
                    '<div style="flex: 1;">' +
                    '<label><?php _e('Option', 'ielts-course-manager'); ?> ' + (optionIndex + 1) + '</label>' +
                    '<input type="text" name="questions[' + questionIndex + '][mc_options][' + optionIndex + '][text]" placeholder="<?php _e('Enter option text', 'ielts-course-manager'); ?>" style="width: 100%; margin-bottom: 5px;">' +
                    '<label><?php _e('Feedback (optional)', 'ielts-course-manager'); ?></label>' +
                    '<textarea name="questions[' + questionIndex + '][mc_options][' + optionIndex + '][feedback]" rows="2" placeholder="<?php _e('Feedback shown when this option is selected', 'ielts-course-manager'); ?>" style="width: 100%;"></textarea>' +
                    '</div>' +
                    '<button type="button" class="button remove-mc-option" style="flex: 0 0 auto;"><?php _e('Remove', 'ielts-course-manager'); ?></button>' +
                    '</div>' +
                    '</div>';
                
                container.append(optionHtml);
            });
            
            // Remove multiple choice option
            $(document).on('click', '.remove-mc-option', function() {
                var container = $(this).closest('.mc-options-container');
                // Ensure at least 2 options remain
                if (container.find('.mc-option-item').length > 2) {
                    $(this).closest('.mc-option-item').remove();
                } else {
                    alert('<?php _e('You must have at least 2 options for a multiple choice question.', 'ielts-course-manager'); ?>');
                }
            });
            
            // Initialize drag and drop for questions
            $('#questions-container').sortable({
                handle: '.question-drag-handle',
                placeholder: 'ui-sortable-placeholder',
                update: function(event, ui) {
                    // Update question numbers
                    $('#questions-container .question-item').each(function(index) {
                        $(this).find('h4').text('<?php _e('Question', 'ielts-course-manager'); ?> ' + (index + 1));
                        
                        // Update all input/select/textarea names to reflect new index
                        var nameMatch = $(this).find('select[name^="questions["]').first().attr('name');
                        if (!nameMatch) {
                            return; // Skip if no match found
                        }
                        
                        var matches = nameMatch.match(/questions\[(\d+)\]/);
                        if (!matches || !matches[1]) {
                            return; // Skip if regex doesn't match
                        }
                        
                        var oldIndex = matches[1];
                        var newIndex = index;
                        
                        if (oldIndex != newIndex) {
                            $(this).find('input, select, textarea').each(function() {
                                var name = $(this).attr('name');
                                if (name && name.indexOf('questions[' + oldIndex + ']') === 0) {
                                    $(this).attr('name', name.replace('questions[' + oldIndex + ']', 'questions[' + newIndex + ']'));
                                }
                            });
                            
                            // Update data-question-index attributes
                            $(this).find('[data-question-index]').attr('data-question-index', newIndex);
                            
                            // Update editor IDs if they exist
                            var editorId = 'question_' + oldIndex;
                            var newEditorId = 'question_' + newIndex;
                            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                                var editorContent = tinymce.get(editorId).getContent();
                                tinymce.get(editorId).remove();
                                var $textarea = $(this).find('textarea[id="' + editorId + '"]');
                                $textarea.attr('id', newEditorId);
                                $textarea.val(editorContent); // Restore content to textarea
                            }
                        }
                    });
                }
            });
            
            // Handle question expand/collapse
            $(document).on('click', '.question-header', function(e) {
                // Don't toggle if clicking on drag handle
                if ($(e.target).hasClass('question-drag-handle')) {
                    return;
                }
                
                var $questionItem = $(this).closest('.question-item');
                var $content = $questionItem.find('.question-content');
                var $toggle = $(this).find('.question-toggle');
                
                if ($content.is(':visible')) {
                    $content.slideUp(200);
                    $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                } else {
                    $content.slideDown(200);
                    $toggle.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                }
            });
            
            // Collapse all questions by default on page load
            $('.question-item').each(function() {
                var $content = $(this).find('.question-content');
                var $toggle = $(this).find('.question-toggle');
                if ($content.length && $toggle.length) {
                    $content.hide();
                    $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                }
            });
            
            // Collapse all reading texts by default on page load
            $('.reading-text-item').each(function() {
                var $content = $(this).find('.reading-text-content');
                var $toggle = $(this).find('.reading-text-toggle');
                if ($content.length && $toggle.length) {
                    $content.hide();
                    $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                }
            });
            
            // Handle reading text expand/collapse
            $(document).on('click', '.reading-text-header', function(e) {
                var $readingTextItem = $(this).closest('.reading-text-item');
                var $content = $readingTextItem.find('.reading-text-content');
                var $toggle = $(this).find('.reading-text-toggle');
                
                if ($content.is(':visible')) {
                    $content.slideUp(200);
                    $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                } else {
                    $content.slideDown(200);
                    $toggle.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                }
            });
            
            // Duplicate question
            $(document).on('click', '.duplicate-question', function() {
                var $question = $(this).closest('.question-item');
                var $clone = $question.clone(true);
                
                // Get the next available question index
                var nextIndex = questionIndex;
                questionIndex++;
                
                // Update cloned question names and IDs
                $clone.find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name && name.indexOf('questions[') === 0) {
                        // Extract current index and replace with next index
                        var newName = name.replace(/questions\[\d+\]/, 'questions[' + nextIndex + ']');
                        $(this).attr('name', newName);
                        
                        // Clear file inputs and some specific fields
                        if ($(this).attr('type') === 'file') {
                            $(this).val('');
                        }
                    }
                    
                    // Update editor IDs
                    var id = $(this).attr('id');
                    if (id && id.indexOf('question_') === 0) {
                        $(this).attr('id', 'question_' + nextIndex);
                    }
                });
                
                // Update data-question-index attributes
                $clone.find('[data-question-index]').attr('data-question-index', nextIndex);
                
                // Update heading
                $clone.find('h4').text('<?php _e('Question', 'ielts-course-manager'); ?> ' + (nextIndex + 1) + ' (<?php _e('Duplicated', 'ielts-course-manager'); ?>)');
                
                // Handle TinyMCE instances in cloned element
                var oldEditorId = 'question_' + $question.find('select[name^="questions["]').first().attr('name').match(/questions\[(\d+)\]/)[1];
                if (typeof tinymce !== 'undefined' && tinymce.get(oldEditorId)) {
                    // Get content from original editor
                    var content = tinymce.get(oldEditorId).getContent();
                    // Set content to cloned textarea
                    $clone.find('textarea[id^="question_"]').val(content);
                }
                
                // Remove TinyMCE UI elements from clone and show textarea
                $clone.find('.mce-tinymce').remove();
                $clone.find('textarea[id^="question_"]').show();
                
                // Insert after current question
                $question.after($clone);
                
                updateQuestionWarning();
            });
            
            // Handle course selection change to filter lessons
            $('#ielts_cm_quiz_course_ids').on('change', function() {
                var selectedCourseIds = $(this).val() || [];
                var selectedLessonIds = $('#ielts_cm_quiz_lesson_ids').val() || [];
                
                // Show loading indicator
                var $lessonSelect = $('#ielts_cm_quiz_lesson_ids');
                var $loading = $('<div class="spinner is-active" style="float: none; margin: 10px 0;"></div>');
                $lessonSelect.after($loading);
                $lessonSelect.prop('disabled', true);
                
                // Fetch filtered lessons
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_get_lessons_by_courses',
                        nonce: '<?php echo wp_create_nonce('ielts_cm_quiz_lessons_filter'); ?>',
                        course_ids: selectedCourseIds,
                        selected_lesson_ids: selectedLessonIds
                    },
                    success: function(response) {
                        if (response.success && response.data.lessons) {
                            // Clear and repopulate lessons dropdown
                            $lessonSelect.empty();
                            
                            $.each(response.data.lessons, function(i, lesson) {
                                var option = $('<option></option>')
                                    .attr('value', lesson.id)
                                    .text(lesson.title);
                                if (lesson.selected) {
                                    option.prop('selected', true);
                                }
                                $lessonSelect.append(option);
                            });
                        }
                        
                        // Remove loading indicator
                        $loading.remove();
                        $lessonSelect.prop('disabled', false);
                    },
                    error: function() {
                        $loading.remove();
                        $lessonSelect.prop('disabled', false);
                        alert('<?php _e('Failed to load lessons. Please try again.', 'ielts-course-manager'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render reading text field
     */
    private function render_reading_text_field($index, $text) {
        ?>
        <div class="reading-text-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9; position: relative;">
            <div class="reading-text-header" style="display: flex; align-items: center; cursor: pointer; margin-bottom: 15px;">
                <span class="dashicons dashicons-arrow-right-alt2 reading-text-toggle" style="color: #666; margin-right: 8px; transition: transform 0.2s;"></span>
                <h4 style="margin: 0; flex: 1;"><?php printf(__('Reading Text %d', 'ielts-course-manager'), $index + 1); ?></h4>
            </div>
            
            <div class="reading-text-content">
                <p>
                    <label><?php _e('Title (Optional)', 'ielts-course-manager'); ?></label><br>
                    <input type="text" name="reading_texts[<?php echo $index; ?>][title]" value="<?php echo esc_attr(isset($text['title']) ? $text['title'] : ''); ?>" style="width: 100%;" placeholder="<?php _e('e.g., Passage 1', 'ielts-course-manager'); ?>">
                </p>
                
                <div>
                    <label><?php _e('Reading Text', 'ielts-course-manager'); ?></label>
                    <?php
                    $editor_id = 'reading_text_' . $index;
                    $content = isset($text['content']) ? $text['content'] : '';
                    wp_editor($content, $editor_id, array(
                        'textarea_name' => 'reading_texts[' . $index . '][content]',
                        'textarea_rows' => 10,
                        'media_buttons' => true,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                            'toolbar2' => 'formatselect,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
                        )
                    ));
                    ?>
                </div>
                
                <button type="button" class="button remove-reading-text" style="margin-top: 10px;"><?php _e('Remove Reading Text', 'ielts-course-manager'); ?></button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render question field
     */
    private function render_question_field($index, $question) {
        global $post;
        $reading_texts = get_post_meta($post->ID, '_ielts_cm_reading_texts', true);
        if (!$reading_texts) {
            $reading_texts = array();
        }
        ?>
        <div class="question-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9; position: relative;">
            <span class="dashicons dashicons-menu question-drag-handle" style="position: absolute; top: 15px; left: 15px; color: #999; cursor: move;"></span>
            <div class="question-header" style="display: flex; align-items: center; margin-left: 30px; cursor: pointer; margin-bottom: 15px;">
                <span class="dashicons dashicons-arrow-right-alt2 question-toggle" style="color: #666; margin-right: 8px; transition: transform 0.2s;"></span>
                <h4 style="margin: 0; flex: 1;"><?php printf(__('Question %d', 'ielts-course-manager'), $index + 1); ?></h4>
            </div>
            
            <div class="question-content" style="display: none;">
            <p>
                <label><?php _e('Question Type', 'ielts-course-manager'); ?></label><br>
                <select name="questions[<?php echo $index; ?>][type]" class="question-type" style="width: 100%;">
                    <?php foreach (IELTS_CM_Quiz_Handler::get_quiz_types() as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected(isset($question['type']) ? $question['type'] : '', $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <?php if (!empty($reading_texts)): ?>
            <p class="reading-text-link-field">
                <label><?php _e('Linked Reading Text (Optional)', 'ielts-course-manager'); ?></label><br>
                <select name="questions[<?php echo $index; ?>][reading_text_id]" style="width: 100%;">
                    <option value=""><?php _e('-- No specific reading text --', 'ielts-course-manager'); ?></option>
                    <?php foreach ($reading_texts as $rt_index => $text): ?>
                        <option value="<?php echo esc_attr($rt_index); ?>" <?php selected(isset($question['reading_text_id']) ? $question['reading_text_id'] : '', $rt_index); ?>>
                            <?php echo esc_html(!empty($text['title']) ? $text['title'] : sprintf(__('Reading Text %d', 'ielts-course-manager'), $rt_index + 1)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small><?php _e('When this question is scrolled into view, the selected reading text will be displayed on the left.', 'ielts-course-manager'); ?></small>
            </p>
            <?php endif; ?>
            
            <div>
                <label><?php _e('Instructions (Optional)', 'ielts-course-manager'); ?></label>
                <?php
                $instructions_editor_id = 'question_instructions_' . $index;
                $instructions_content = isset($question['instructions']) ? $question['instructions'] : '';
                wp_editor($instructions_content, $instructions_editor_id, array(
                    'textarea_name' => 'questions[' . $index . '][instructions]',
                    'textarea_rows' => 4,
                    'media_buttons' => false,
                    'teeny' => true,
                    'tinymce' => array(
                        'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink'
                    )
                ));
                ?>
                <small><?php _e('Optional introductory instructions or explanation shown above the question. Use this for grouping instructions like "Questions 14-20: Choose the most suitable headings..."', 'ielts-course-manager'); ?></small>
            </div>
            
            <div>
                <label><?php _e('Question Text', 'ielts-course-manager'); ?></label>
                <?php
                $editor_id = 'question_' . $index;
                $content = isset($question['question']) ? $question['question'] : '';
                wp_editor($content, $editor_id, array(
                    'textarea_name' => 'questions[' . $index . '][question]',
                    'textarea_rows' => 8,
                    'media_buttons' => true,
                    'teeny' => false,
                    'tinymce' => array(
                        'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                        'toolbar2' => 'formatselect,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
                    )
                ));
                ?>
            </div>
            
            <!-- Multi-select settings -->
            <div class="multi-select-settings" style="<?php echo (isset($question['type']) && $question['type'] !== 'multi_select') ? 'display:none;' : ''; ?>padding: 10px; background: #f0f0f1; margin-bottom: 15px; border-left: 4px solid #72aee6;">
                <p>
                    <label><?php _e('Maximum Number of Selections', 'ielts-course-manager'); ?></label><br>
                    <input type="number" 
                           name="questions[<?php echo $index; ?>][max_selections]" 
                           value="<?php echo esc_attr(isset($question['max_selections']) ? $question['max_selections'] : 2); ?>" 
                           min="1" 
                           style="width: 100px;">
                    <small><?php _e('Number of options students can select. This should equal the number of correct answers marked below.', 'ielts-course-manager'); ?></small>
                </p>
            </div>
            
            <!-- New structured options for multiple choice -->
            <div class="mc-options-field" style="<?php echo (isset($question['type']) && $question['type'] !== 'multiple_choice' && $question['type'] !== 'multi_select') ? 'display:none;' : ''; ?>">
                <h5><?php _e('Answer Options', 'ielts-course-manager'); ?></h5>
                <div class="mc-options-container" data-question-index="<?php echo $index; ?>">
                    <?php
                    // Parse existing options structure
                    $mc_options = array();
                    if (isset($question['mc_options']) && is_array($question['mc_options'])) {
                        // New format
                        $mc_options = $question['mc_options'];
                    } elseif (isset($question['options']) && !empty($question['options'])) {
                        // Legacy format - convert
                        $option_lines = array_filter(explode("\n", $question['options']));
                        $correct_answer = isset($question['correct_answer']) ? intval($question['correct_answer']) : 0;
                        $option_feedbacks = isset($question['option_feedback']) && is_array($question['option_feedback']) ? $question['option_feedback'] : array();
                        
                        foreach ($option_lines as $opt_idx => $option_text) {
                            $mc_options[] = array(
                                'text' => trim($option_text),
                                'is_correct' => ($opt_idx == $correct_answer),
                                'feedback' => isset($option_feedbacks[$opt_idx]) ? $option_feedbacks[$opt_idx] : ''
                            );
                        }
                    }
                    
                    // Ensure at least 2 options
                    if (empty($mc_options)) {
                        $mc_options = array(
                            array('text' => '', 'is_correct' => true, 'feedback' => ''),
                            array('text' => '', 'is_correct' => false, 'feedback' => '')
                        );
                    }
                    
                    foreach ($mc_options as $opt_idx => $option):
                    ?>
                        <div class="mc-option-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #fff;">
                            <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px;">
                                <div style="flex: 0 0 30px;">
                                    <label style="cursor: pointer; display: block;">
                                        <input type="checkbox" 
                                               name="questions[<?php echo $index; ?>][mc_options][<?php echo $opt_idx; ?>][is_correct]" 
                                               value="1"
                                               <?php checked(!empty($option['is_correct'])); ?>
                                               style="margin: 5px 0 0 0;">
                                        <small style="display: block; margin-top: 3px;"><?php _e('Correct', 'ielts-course-manager'); ?></small>
                                    </label>
                                </div>
                                <div style="flex: 1;">
                                    <label><?php printf(__('Option %d', 'ielts-course-manager'), $opt_idx + 1); ?></label>
                                    <input type="text" 
                                           name="questions[<?php echo $index; ?>][mc_options][<?php echo $opt_idx; ?>][text]" 
                                           value="<?php echo esc_attr($option['text']); ?>" 
                                           placeholder="<?php _e('Enter option text', 'ielts-course-manager'); ?>"
                                           style="width: 100%; margin-bottom: 5px;">
                                    <label><?php _e('Feedback (optional)', 'ielts-course-manager'); ?></label>
                                    <textarea name="questions[<?php echo $index; ?>][mc_options][<?php echo $opt_idx; ?>][feedback]" 
                                              rows="2" 
                                              placeholder="<?php _e('Feedback shown when this option is selected', 'ielts-course-manager'); ?>"
                                              style="width: 100%;"><?php echo esc_textarea(isset($option['feedback']) ? $option['feedback'] : ''); ?></textarea>
                                </div>
                                <button type="button" class="button remove-mc-option" style="flex: 0 0 auto;"><?php _e('Remove', 'ielts-course-manager'); ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-mc-option" data-question-index="<?php echo $index; ?>"><?php _e('Add Option', 'ielts-course-manager'); ?></button>
            </div>
            
            <!-- Legacy options field (hidden, kept for non-MC questions) -->
            <p class="options-field-legacy" style="display: none;">
                <label><?php _e('Options (one per line)', 'ielts-course-manager'); ?></label><br>
                <textarea name="questions[<?php echo $index; ?>][options]" rows="4" style="width: 100%;"><?php echo esc_textarea(isset($question['options']) ? $question['options'] : ''); ?></textarea>
            </p>
            
            <p class="correct-answer-field" style="<?php echo (isset($question['type']) && ($question['type'] === 'essay' || $question['type'] === 'multiple_choice')) ? 'display:none;' : ''; ?>">
                <label><?php _e('Correct Answer', 'ielts-course-manager'); ?></label><br>
                <?php if (isset($question['type']) && $question['type'] === 'true_false'): ?>
                    <select name="questions[<?php echo $index; ?>][correct_answer]" style="width: 100%;">
                        <option value=""><?php _e('-- Select correct answer --', 'ielts-course-manager'); ?></option>
                        <option value="true" <?php selected(isset($question['correct_answer']) ? $question['correct_answer'] : '', 'true'); ?>><?php _e('True', 'ielts-course-manager'); ?></option>
                        <option value="false" <?php selected(isset($question['correct_answer']) ? $question['correct_answer'] : '', 'false'); ?>><?php _e('False', 'ielts-course-manager'); ?></option>
                        <option value="not_given" <?php selected(isset($question['correct_answer']) ? $question['correct_answer'] : '', 'not_given'); ?>><?php _e('Not Given', 'ielts-course-manager'); ?></option>
                    </select>
                <?php else: ?>
                    <input type="text" name="questions[<?php echo $index; ?>][correct_answer]" value="<?php echo esc_attr(isset($question['correct_answer']) ? $question['correct_answer'] : ''); ?>" style="width: 100%;">
                <?php endif; ?>
            </p>
            
            <p>
                <label><?php _e('Points', 'ielts-course-manager'); ?></label><br>
                <input type="number" name="questions[<?php echo $index; ?>][points]" value="<?php echo esc_attr(isset($question['points']) ? $question['points'] : 1); ?>" min="0" step="0.5" style="width: 100%;">
            </p>
            
            <div class="general-feedback-field" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ccc; <?php echo (isset($question['type']) && $question['type'] === 'multiple_choice') ? 'display:none;' : ''; ?>">
                <h5 style="margin-top: 0;"><?php _e('Feedback Messages', 'ielts-course-manager'); ?></h5>
                
                <p>
                    <label><?php _e('Correct Answer Feedback', 'ielts-course-manager'); ?></label><br>
                    <textarea name="questions[<?php echo $index; ?>][correct_feedback]" rows="3" style="width: 100%;"><?php echo esc_textarea(isset($question['correct_feedback']) ? $question['correct_feedback'] : ''); ?></textarea>
                    <small><?php _e('Shown when the student answers correctly. HTML is supported.', 'ielts-course-manager'); ?></small>
                </p>
                
                <p class="incorrect-feedback-field">
                    <label><?php _e('Incorrect Answer Feedback', 'ielts-course-manager'); ?></label><br>
                    <textarea name="questions[<?php echo $index; ?>][incorrect_feedback]" rows="3" style="width: 100%;"><?php echo esc_textarea(isset($question['incorrect_feedback']) ? $question['incorrect_feedback'] : ''); ?></textarea>
                    <small><?php _e('Shown when the student answers incorrectly. HTML is supported.', 'ielts-course-manager'); ?></small>
                </p>
            </div>
            
            <div class="no-answer-feedback-field" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ccc;">
                <h5 style="margin-top: 0;"><?php _e('No Answer Feedback', 'ielts-course-manager'); ?></h5>
                <p>
                    <label><?php _e('No Answer Selected Feedback', 'ielts-course-manager'); ?></label><br>
                    <textarea name="questions[<?php echo $index; ?>][no_answer_feedback]" rows="3" style="width: 100%;"><?php echo esc_textarea(isset($question['no_answer_feedback']) ? $question['no_answer_feedback'] : ''); ?></textarea>
                    <small><?php _e('Shown when the student submits without selecting an answer. HTML is supported.', 'ielts-course-manager'); ?></small>
                </p>
            </div>
            
            <div style="margin-top: 10px;">
                <button type="button" class="button duplicate-question"><?php _e('Duplicate Question', 'ielts-course-manager'); ?></button>
                <button type="button" class="button remove-question"><?php _e('Remove Question', 'ielts-course-manager'); ?></button>
            </div>
            </div><!-- .question-content -->
        </div>
        <?php
    }
    
    /**
     * Get question template
     */
    private function get_question_template() {
        ob_start();
        ?>
        <div class="question-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9; position: relative;">
            <span class="dashicons dashicons-menu question-drag-handle" style="position: absolute; top: 15px; left: 15px; color: #999; cursor: move;"></span>
            <div class="question-header" style="display: flex; align-items: center; margin-left: 30px; cursor: pointer; margin-bottom: 15px;">
                <span class="dashicons dashicons-arrow-down-alt2 question-toggle" style="color: #666; margin-right: 8px; transition: transform 0.2s;"></span>
                <h4 style="margin: 0; flex: 1;"><?php _e('New Question', 'ielts-course-manager'); ?></h4>
            </div>
            
            <div class="question-content">
            <p>
                <label><?php _e('Question Type', 'ielts-course-manager'); ?></label><br>
                <select name="questions[QUESTION_INDEX][type]" class="question-type" style="width: 100%;">
                    <?php foreach (IELTS_CM_Quiz_Handler::get_quiz_types() as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label><?php _e('Instructions (Optional)', 'ielts-course-manager'); ?></label><br>
                <textarea name="questions[QUESTION_INDEX][instructions]" rows="4" style="width: 100%;"></textarea>
                <small><?php _e('Optional introductory instructions or explanation shown above the question. Use this for grouping instructions like "Questions 14-20: Choose the most suitable headings..."', 'ielts-course-manager'); ?></small>
            </p>
            
            <p>
                <label><?php _e('Question Text', 'ielts-course-manager'); ?></label><br>
                <textarea name="questions[QUESTION_INDEX][question]" rows="8" style="width: 100%;"></textarea>
                <small><?php _e('HTML is supported. You can paste HTML with images and formatting. Save the post to enable the visual editor for this question.', 'ielts-course-manager'); ?></small>
            </p>
            
            <!-- Multi-select settings -->
            <div class="multi-select-settings" style="display:none; padding: 10px; background: #f0f0f1; margin-bottom: 15px; border-left: 4px solid #72aee6;">
                <p>
                    <label><?php _e('Maximum Number of Selections', 'ielts-course-manager'); ?></label><br>
                    <input type="number" 
                           name="questions[QUESTION_INDEX][max_selections]" 
                           value="2" 
                           min="1" 
                           style="width: 100px;">
                    <small><?php _e('Number of options students can select. This should equal the number of correct answers marked below.', 'ielts-course-manager'); ?></small>
                </p>
            </div>
            
            <!-- New structured options for multiple choice -->
            <div class="mc-options-field">
                <h5><?php _e('Answer Options', 'ielts-course-manager'); ?></h5>
                <div class="mc-options-container" data-question-index="QUESTION_INDEX">
                    <div class="mc-option-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #fff;">
                        <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px;">
                            <div style="flex: 0 0 30px;">
                                <label style="cursor: pointer; display: block;">
                                    <input type="checkbox" name="questions[QUESTION_INDEX][mc_options][0][is_correct]" value="1" checked style="margin: 5px 0 0 0;">
                                    <small style="display: block; margin-top: 3px;"><?php _e('Correct', 'ielts-course-manager'); ?></small>
                                </label>
                            </div>
                            <div style="flex: 1;">
                                <label><?php _e('Option 1', 'ielts-course-manager'); ?></label>
                                <input type="text" name="questions[QUESTION_INDEX][mc_options][0][text]" placeholder="<?php _e('Enter option text', 'ielts-course-manager'); ?>" style="width: 100%; margin-bottom: 5px;">
                                <label><?php _e('Feedback (optional)', 'ielts-course-manager'); ?></label>
                                <textarea name="questions[QUESTION_INDEX][mc_options][0][feedback]" rows="2" placeholder="<?php _e('Feedback shown when this option is selected', 'ielts-course-manager'); ?>" style="width: 100%;"></textarea>
                            </div>
                            <button type="button" class="button remove-mc-option" style="flex: 0 0 auto;"><?php _e('Remove', 'ielts-course-manager'); ?></button>
                        </div>
                    </div>
                    <div class="mc-option-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #fff;">
                        <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px;">
                            <div style="flex: 0 0 30px;">
                                <label style="cursor: pointer; display: block;">
                                    <input type="checkbox" name="questions[QUESTION_INDEX][mc_options][1][is_correct]" value="1" style="margin: 5px 0 0 0;">
                                    <small style="display: block; margin-top: 3px;"><?php _e('Correct', 'ielts-course-manager'); ?></small>
                                </label>
                            </div>
                            <div style="flex: 1;">
                                <label><?php _e('Option 2', 'ielts-course-manager'); ?></label>
                                <input type="text" name="questions[QUESTION_INDEX][mc_options][1][text]" placeholder="<?php _e('Enter option text', 'ielts-course-manager'); ?>" style="width: 100%; margin-bottom: 5px;">
                                <label><?php _e('Feedback (optional)', 'ielts-course-manager'); ?></label>
                                <textarea name="questions[QUESTION_INDEX][mc_options][1][feedback]" rows="2" placeholder="<?php _e('Feedback shown when this option is selected', 'ielts-course-manager'); ?>" style="width: 100%;"></textarea>
                            </div>
                            <button type="button" class="button remove-mc-option" style="flex: 0 0 auto;"><?php _e('Remove', 'ielts-course-manager'); ?></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="button add-mc-option" data-question-index="QUESTION_INDEX"><?php _e('Add Option', 'ielts-course-manager'); ?></button>
            </div>
            
            <p class="correct-answer-field" style="display: none;">
                <label><?php _e('Correct Answer', 'ielts-course-manager'); ?></label><br>
                <input type="text" name="questions[QUESTION_INDEX][correct_answer]" style="width: 100%;">
            </p>
            
            <p>
                <label><?php _e('Points', 'ielts-course-manager'); ?></label><br>
                <input type="number" name="questions[QUESTION_INDEX][points]" value="1" min="0" step="0.5" style="width: 100%;">
            </p>
            
            <div class="general-feedback-field" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ccc; display: none;">
                <h5 style="margin-top: 0;"><?php _e('Feedback Messages', 'ielts-course-manager'); ?></h5>
                
                <p>
                    <label><?php _e('Correct Answer Feedback', 'ielts-course-manager'); ?></label><br>
                    <textarea name="questions[QUESTION_INDEX][correct_feedback]" rows="3" style="width: 100%;"></textarea>
                    <small><?php _e('Shown when the student answers correctly. HTML is supported.', 'ielts-course-manager'); ?></small>
                </p>
                
                <p class="incorrect-feedback-field">
                    <label><?php _e('Incorrect Answer Feedback', 'ielts-course-manager'); ?></label><br>
                    <textarea name="questions[QUESTION_INDEX][incorrect_feedback]" rows="3" style="width: 100%;"></textarea>
                    <small><?php _e('Shown when the student answers incorrectly. HTML is supported.', 'ielts-course-manager'); ?></small>
                </p>
            </div>
            
            <div class="no-answer-feedback-field" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ccc;">
                <h5 style="margin-top: 0;"><?php _e('No Answer Feedback', 'ielts-course-manager'); ?></h5>
                <p>
                    <label><?php _e('No Answer Selected Feedback', 'ielts-course-manager'); ?></label><br>
                    <textarea name="questions[QUESTION_INDEX][no_answer_feedback]" rows="3" style="width: 100%;"></textarea>
                    <small><?php _e('Shown when the student submits without selecting an answer. HTML is supported.', 'ielts-course-manager'); ?></small>
                </p>
            </div>
            
            <div style="margin-top: 10px;">
                <button type="button" class="button duplicate-question"><?php _e('Duplicate Question', 'ielts-course-manager'); ?></button>
                <button type="button" class="button remove-question"><?php _e('Remove Question', 'ielts-course-manager'); ?></button>
            </div>
            </div><!-- .question-content -->
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save course meta
        if (isset($_POST['ielts_cm_course_meta_nonce']) && wp_verify_nonce($_POST['ielts_cm_course_meta_nonce'], 'ielts_cm_course_meta')) {
            // Course meta is now minimal - just verify nonce
        }
        
        // Save lesson meta
        if (isset($_POST['ielts_cm_lesson_meta_nonce']) && wp_verify_nonce($_POST['ielts_cm_lesson_meta_nonce'], 'ielts_cm_lesson_meta')) {
            // Save multiple course IDs
            if (isset($_POST['ielts_cm_course_ids']) && is_array($_POST['ielts_cm_course_ids'])) {
                $course_ids = array_map('intval', $_POST['ielts_cm_course_ids']);
                update_post_meta($post_id, '_ielts_cm_course_ids', $course_ids);
                // Keep first course for backward compatibility
                if (!empty($course_ids)) {
                    update_post_meta($post_id, '_ielts_cm_course_id', $course_ids[0]);
                }
            } else {
                update_post_meta($post_id, '_ielts_cm_course_ids', array());
                delete_post_meta($post_id, '_ielts_cm_course_id');
            }
        }
        
        // Save lesson page meta
        if (isset($_POST['ielts_cm_resource_meta_nonce']) && wp_verify_nonce($_POST['ielts_cm_resource_meta_nonce'], 'ielts_cm_resource_meta')) {
            // Save multiple lesson IDs
            if (isset($_POST['ielts_cm_lesson_ids']) && is_array($_POST['ielts_cm_lesson_ids'])) {
                $lesson_ids = array_map('intval', $_POST['ielts_cm_lesson_ids']);
                update_post_meta($post_id, '_ielts_cm_lesson_ids', $lesson_ids);
                // Keep first lesson for backward compatibility
                if (!empty($lesson_ids)) {
                    update_post_meta($post_id, '_ielts_cm_lesson_id', $lesson_ids[0]);
                }
            } else {
                update_post_meta($post_id, '_ielts_cm_lesson_ids', array());
                delete_post_meta($post_id, '_ielts_cm_lesson_id');
            }
            if (isset($_POST['ielts_cm_resource_url'])) {
                update_post_meta($post_id, '_ielts_cm_resource_url', esc_url_raw($_POST['ielts_cm_resource_url']));
            }
        }
        
        // Save quiz meta
        if (isset($_POST['ielts_cm_quiz_meta_nonce']) && wp_verify_nonce($_POST['ielts_cm_quiz_meta_nonce'], 'ielts_cm_quiz_meta')) {
            // Save multiple course IDs
            if (isset($_POST['ielts_cm_quiz_course_ids']) && is_array($_POST['ielts_cm_quiz_course_ids'])) {
                $course_ids = array_map('intval', $_POST['ielts_cm_quiz_course_ids']);
                update_post_meta($post_id, '_ielts_cm_course_ids', $course_ids);
                // Keep first course for backward compatibility
                if (!empty($course_ids)) {
                    update_post_meta($post_id, '_ielts_cm_course_id', $course_ids[0]);
                }
            } else {
                update_post_meta($post_id, '_ielts_cm_course_ids', array());
                delete_post_meta($post_id, '_ielts_cm_course_id');
            }
            
            // Save multiple lesson IDs
            if (isset($_POST['ielts_cm_quiz_lesson_ids']) && is_array($_POST['ielts_cm_quiz_lesson_ids'])) {
                $lesson_ids = array_map('intval', $_POST['ielts_cm_quiz_lesson_ids']);
                update_post_meta($post_id, '_ielts_cm_lesson_ids', $lesson_ids);
                // Keep first lesson for backward compatibility
                if (!empty($lesson_ids)) {
                    update_post_meta($post_id, '_ielts_cm_lesson_id', $lesson_ids[0]);
                }
            } else {
                update_post_meta($post_id, '_ielts_cm_lesson_ids', array());
                delete_post_meta($post_id, '_ielts_cm_lesson_id');
            }
            
            if (isset($_POST['ielts_cm_pass_percentage'])) {
                update_post_meta($post_id, '_ielts_cm_pass_percentage', intval($_POST['ielts_cm_pass_percentage']));
            }
            
            // Save layout type
            if (isset($_POST['ielts_cm_layout_type'])) {
                update_post_meta($post_id, '_ielts_cm_layout_type', sanitize_text_field($_POST['ielts_cm_layout_type']));
            }
            
            // Save open as popup option (only for CBT layout)
            if (isset($_POST['ielts_cm_open_as_popup'])) {
                update_post_meta($post_id, '_ielts_cm_open_as_popup', '1');
            } else {
                delete_post_meta($post_id, '_ielts_cm_open_as_popup');
            }
            
            // Save scoring type with validation
            if (isset($_POST['ielts_cm_scoring_type'])) {
                $scoring_type = sanitize_text_field($_POST['ielts_cm_scoring_type']);
                $valid_types = array('percentage', 'ielts_general_reading', 'ielts_academic_reading', 'ielts_listening');
                if (in_array($scoring_type, $valid_types)) {
                    update_post_meta($post_id, '_ielts_cm_scoring_type', $scoring_type);
                }
            }
            
            // Save timer minutes
            if (isset($_POST['ielts_cm_timer_minutes'])) {
                $timer_minutes = intval($_POST['ielts_cm_timer_minutes']);
                if ($timer_minutes > 0) {
                    update_post_meta($post_id, '_ielts_cm_timer_minutes', $timer_minutes);
                } else {
                    delete_post_meta($post_id, '_ielts_cm_timer_minutes');
                }
            }
            
            // Save exercise label with validation
            if (isset($_POST['ielts_cm_exercise_label'])) {
                $exercise_label = sanitize_text_field($_POST['ielts_cm_exercise_label']);
                $valid_labels = array('exercise', 'end_of_lesson_test', 'practice_test');
                if (in_array($exercise_label, $valid_labels)) {
                    update_post_meta($post_id, '_ielts_cm_exercise_label', $exercise_label);
                } else {
                    update_post_meta($post_id, '_ielts_cm_exercise_label', 'exercise');
                }
            }
            
            // Save reading texts
            $reading_texts = array();
            if (isset($_POST['reading_texts']) && is_array($_POST['reading_texts'])) {
                foreach ($_POST['reading_texts'] as $text) {
                    if (!empty($text['content'])) {
                        $reading_texts[] = array(
                            'title' => sanitize_text_field($text['title']),
                            'content' => wp_kses_post($text['content'])
                        );
                    }
                }
            }
            update_post_meta($post_id, '_ielts_cm_reading_texts', $reading_texts);
            // Always save questions, even if empty
            $questions = array();
            if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                foreach ($_POST['questions'] as $question) {
                    // Skip empty questions
                    if (empty($question['question'])) {
                        continue;
                    }
                    
                    $question_data = array(
                        'type' => sanitize_text_field($question['type']),
                        'instructions' => isset($question['instructions']) ? wp_kses_post($question['instructions']) : '',
                        'question' => wp_kses_post($question['question']), // Allow HTML with images
                        'points' => isset($question['points']) ? floatval($question['points']) : 1,
                        'correct_feedback' => isset($question['correct_feedback']) ? wp_kses_post($question['correct_feedback']) : '',
                        'incorrect_feedback' => isset($question['incorrect_feedback']) ? wp_kses_post($question['incorrect_feedback']) : '',
                        'no_answer_feedback' => isset($question['no_answer_feedback']) ? wp_kses_post($question['no_answer_feedback']) : '',
                        'reading_text_id' => isset($question['reading_text_id']) && $question['reading_text_id'] !== '' ? intval($question['reading_text_id']) : null
                    );
                    
                    // Handle multiple choice and multi-select with new structured format
                    if (($question['type'] === 'multiple_choice' || $question['type'] === 'multi_select') && isset($question['mc_options']) && is_array($question['mc_options'])) {
                        $mc_options = array();
                        $options_text = array();
                        $option_feedback = array();
                        $correct_answer = null;
                        
                        foreach ($question['mc_options'] as $idx => $option) {
                            if (empty($option['text'])) {
                                continue; // Skip empty options
                            }
                            
                            $mc_options[] = array(
                                'text' => sanitize_text_field($option['text']),
                                'is_correct' => !empty($option['is_correct']),
                                'feedback' => isset($option['feedback']) ? wp_kses_post($option['feedback']) : ''
                            );
                            
                            // Also create legacy format for backward compatibility
                            $options_text[] = sanitize_text_field($option['text']);
                            $option_feedback[] = isset($option['feedback']) ? wp_kses_post($option['feedback']) : '';
                            
                            // Track first correct answer for legacy format (for multiple_choice)
                            if (!empty($option['is_correct']) && $correct_answer === null) {
                                $correct_answer = count($options_text) - 1;
                            }
                        }
                        
                        // Store new structured format
                        $question_data['mc_options'] = $mc_options;
                        
                        // Also store legacy format for backward compatibility with existing quiz display
                        $question_data['options'] = implode("\n", $options_text);
                        $question_data['correct_answer'] = ($correct_answer !== null) ? strval($correct_answer) : '0';
                        $question_data['option_feedback'] = $option_feedback;
                        
                        // For multi_select, also save max_selections
                        if ($question['type'] === 'multi_select' && isset($question['max_selections'])) {
                            $question_data['max_selections'] = intval($question['max_selections']);
                        }
                    } else {
                        // Non-multiple choice questions
                        $question_data['options'] = isset($question['options']) ? sanitize_textarea_field($question['options']) : '';
                        $question_data['correct_answer'] = isset($question['correct_answer']) ? sanitize_text_field($question['correct_answer']) : '';
                    }
                    
                    $questions[] = $question_data;
                }
            }
            update_post_meta($post_id, '_ielts_cm_questions', $questions);
            
            // Validate that quiz has at least one question before publishing
            $post = get_post($post_id);
            if ($post && $post->post_type === 'ielts_quiz' && $post->post_status === 'publish' && empty($questions)) {
                // Use flag to prevent infinite loop instead of removing/re-adding hook
                if (!$this->processing_quiz_save) {
                    $this->processing_quiz_save = true;
                    
                    // Change status to draft if no questions
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'draft'
                    ));
                    
                    $this->processing_quiz_save = false;
                    
                    // Set admin notice via transient to avoid multiple filter calls
                    set_transient('ielts_cm_no_questions_' . get_current_user_id(), '1', 60);
                }
            }
        }
    }
    
    /**
     * AJAX handler for updating lesson order
     */
    public function ajax_update_lesson_order() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_lesson_order')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        // Get the lesson order data
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $lesson_order = isset($_POST['lesson_order']) ? $_POST['lesson_order'] : array();
        
        if (!$course_id || empty($lesson_order)) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Update menu_order for each lesson
        foreach ($lesson_order as $item) {
            $lesson_id = intval($item['lesson_id']);
            $order = intval($item['order']);
            
            wp_update_post(array(
                'ID' => $lesson_id,
                'menu_order' => $order
            ));
        }
        
        wp_send_json_success(array('message' => __('Lesson order updated successfully', 'ielts-course-manager')));
    }
    
    /**
     * AJAX handler for updating lesson page order
     */
    public function ajax_update_page_order() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_page_order')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        // Get the page order data
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $page_order = isset($_POST['page_order']) ? $_POST['page_order'] : array();
        
        if (!$lesson_id || empty($page_order)) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Update menu_order for each page
        foreach ($page_order as $item) {
            $page_id = intval($item['page_id']);
            $order = intval($item['order']);
            
            wp_update_post(array(
                'ID' => $page_id,
                'menu_order' => $order
            ));
        }
        
        wp_send_json_success(array('message' => __('Lesson page order updated successfully', 'ielts-course-manager')));
    }
    
    /**
     * AJAX handler for updating lesson content (pages and exercises) order
     */
    public function ajax_update_content_order() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_content_order')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        // Get the content order data
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $content_order = isset($_POST['content_order']) ? $_POST['content_order'] : array();
        
        if (!$lesson_id || empty($content_order)) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Update menu_order for each content item (page or quiz)
        foreach ($content_order as $item) {
            $item_id = intval($item['item_id']);
            $order = intval($item['order']);
            
            wp_update_post(array(
                'ID' => $item_id,
                'menu_order' => $order
            ));
        }
        
        wp_send_json_success(array('message' => __('Content order updated successfully', 'ielts-course-manager')));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Progress Reports', 'ielts-course-manager'),
            __('Progress Reports', 'ielts-course-manager'),
            'manage_options',
            'ielts-progress-reports',
            array($this, 'progress_reports_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Documentation', 'ielts-course-manager'),
            __('Documentation', 'ielts-course-manager'),
            'manage_options',
            'ielts-documentation',
            array($this, 'documentation_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Settings', 'ielts-course-manager'),
            __('Settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Progress reports page
     */
    public function progress_reports_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Progress Reports', 'ielts-course-manager'); ?></h1>
            <p><?php _e('View student progress and quiz results.', 'ielts-course-manager'); ?></p>
            
            <?php
            // Get all users
            $users = get_users();
            
            foreach ($users as $user) {
                $enrollment = new IELTS_CM_Enrollment();
                $enrolled_courses = $enrollment->get_user_courses($user->ID);
                
                if (!empty($enrolled_courses)) {
                    echo '<h3>' . esc_html($user->display_name) . '</h3>';
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>Course</th><th>Progress</th><th>Quiz Results</th></tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($enrolled_courses as $enrollment_data) {
                        $course = get_post($enrollment_data->course_id);
                        $progress_tracker = new IELTS_CM_Progress_Tracker();
                        $completion = $progress_tracker->get_course_completion_percentage($user->ID, $enrollment_data->course_id);
                        
                        $quiz_handler = new IELTS_CM_Quiz_Handler();
                        $quiz_results = $quiz_handler->get_quiz_results($user->ID, $enrollment_data->course_id);
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($course->post_title) . '</td>';
                        echo '<td>' . round($completion, 2) . '%</td>';
                        echo '<td>' . count($quiz_results) . ' quizzes taken</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                }
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Course columns
     */
    public function course_columns($columns) {
        $columns['category'] = __('Category', 'ielts-course-manager');
        $columns['lessons'] = __('Lessons', 'ielts-course-manager');
        $columns['enrolled'] = __('Enrolled', 'ielts-course-manager');
        return $columns;
    }
    
    /**
     * Course column content
     */
    public function course_column_content($column, $post_id) {
        if ($column === 'category') {
            $terms = get_the_terms($post_id, 'ielts_course_category');
            if (!empty($terms) && !is_wp_error($terms)) {
                $category_names = array();
                foreach ($terms as $term) {
                    $category_names[] = '<a href="' . esc_url(admin_url('edit.php?post_type=ielts_course&ielts_course_category=' . $term->slug)) . '">' . esc_html($term->name) . '</a>';
                }
                echo implode(', ', $category_names);
            } else {
                echo '—';
            }
        } elseif ($column === 'lessons') {
            global $wpdb;
            $lesson_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT pm.post_id 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'ielts_lesson'
                  AND p.post_status = 'publish'
                  AND ((pm.meta_key = '_ielts_cm_course_id' AND pm.meta_value = %d)
                    OR (pm.meta_key = '_ielts_cm_course_ids' AND pm.meta_value LIKE %s))
            ", $post_id, '%' . $wpdb->esc_like(serialize(strval($post_id))) . '%'));
            echo count($lesson_ids);
        } elseif ($column === 'enrolled') {
            $enrollment = new IELTS_CM_Enrollment();
            $users = $enrollment->get_course_users($post_id);
            echo count($users);
        }
    }
    
    /**
     * Lesson columns
     */
    public function lesson_columns($columns) {
        $columns['course'] = __('Course', 'ielts-course-manager');
        $columns['resources'] = __('Lesson pages', 'ielts-course-manager');
        return $columns;
    }
    
    /**
     * Lesson column content
     */
    public function lesson_column_content($column, $post_id) {
        if ($column === 'course') {
            // Check for multiple courses
            $course_ids = get_post_meta($post_id, '_ielts_cm_course_ids', true);
            if (empty($course_ids)) {
                // Backward compatibility - check old single course_id
                $old_course_id = get_post_meta($post_id, '_ielts_cm_course_id', true);
                $course_ids = $old_course_id ? array($old_course_id) : array();
            }
            
            if (!empty($course_ids)) {
                $course_links = array();
                foreach ($course_ids as $course_id) {
                    $course = get_post($course_id);
                    if ($course) {
                        $course_links[] = '<a href="' . get_edit_post_link($course_id) . '">' . esc_html($course->post_title) . '</a>';
                    }
                }
                echo implode(', ', $course_links);
            }
        } elseif ($column === 'resources') {
            global $wpdb;
            $resource_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT pm.post_id 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'ielts_resource'
                  AND p.post_status = 'publish'
                  AND ((pm.meta_key = '_ielts_cm_lesson_id' AND pm.meta_value = %d)
                    OR (pm.meta_key = '_ielts_cm_lesson_ids' AND pm.meta_value LIKE %s))
            ", $post_id, '%' . $wpdb->esc_like(serialize(strval($post_id))) . '%'));
            echo count($resource_ids);
        }
    }
    
    /**
     * Documentation page
     */
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('IELTS Course Manager Documentation', 'ielts-course-manager'); ?></h1>
            
            <div class="documentation-content" style="max-width: 900px;">
                
                <h2><?php _e('Getting Started', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Welcome to IELTS Course Manager! This plugin provides a complete learning management system for IELTS preparation courses.', 'ielts-course-manager'); ?></p>
                
                <h2><?php _e('Creating Content', 'ielts-course-manager'); ?></h2>
                
                <h3><?php _e('1. Create a Course', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Go to IELTS Courses > Add New Course', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Enter course title and description', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Add a featured image (optional)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the course', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('2. Create Lessons', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Go to IELTS Courses > Lessons > Add New', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Enter lesson title and content', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Assign to one or more courses (use Ctrl/Cmd to select multiple)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the lesson', 'ielts-course-manager'); ?></li>
                </ol>
                <p><strong><?php _e('Reordering Lessons:', 'ielts-course-manager'); ?></strong> <?php _e('Go to the course edit page and use the "Course Lessons" meta box to drag and drop lessons into the desired order.', 'ielts-course-manager'); ?></p>
                
                <h3><?php _e('3. Add Lesson pages', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Go to IELTS Courses > Lesson pages > Add New', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Enter lesson page title and description', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Assign to one or more lessons (use Ctrl/Cmd to select multiple)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Optionally add a resource URL for external resources', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the lesson page', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('4. Create Quizzes', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Go to IELTS Courses > Quizzes > Add New', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Enter quiz title and description', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Assign to one or more courses (use Ctrl/Cmd to select multiple)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Optionally assign to one or more lessons', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Set passing percentage', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Click "Add Question" to add quiz questions', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Select question type, enter question text, add options, set correct answer, and assign points', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the quiz', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h2><?php _e('Using Multiple Courses/Lessons', 'ielts-course-manager'); ?></h2>
                <p><?php _e('The plugin now supports many-to-many relationships:', 'ielts-course-manager'); ?></p>
                <ul>
                    <li><?php _e('Lessons can be assigned to multiple courses', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Lesson pages can be assigned to multiple lessons', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Quizzes can be assigned to multiple courses and/or lessons', 'ielts-course-manager'); ?></li>
                </ul>
                <p><?php _e('This allows you to reuse content across different courses without duplicating it.', 'ielts-course-manager'); ?></p>
                
                <h2><?php _e('Available Shortcodes', 'ielts-course-manager'); ?></h2>
                
                <h3><?php _e('Display All Courses', 'ielts-course-manager'); ?></h3>
                <p><code>[ielts_courses]</code></p>
                <p><?php _e('With options:', 'ielts-course-manager'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><code>[ielts_courses category="beginner"]</code> - <?php _e('Filter by category slug', 'ielts-course-manager'); ?></li>
                    <li><code>[ielts_courses limit="10"]</code> - <?php _e('Limit number of courses displayed', 'ielts-course-manager'); ?></li>
                    <li><code>[ielts_courses columns="3"]</code> - <?php _e('Set number of columns (1-6, default is 5)', 'ielts-course-manager'); ?></li>
                    <li><code>[ielts_courses orderby="title"]</code> - <?php _e('Sort by title, date, menu_order, etc.', 'ielts-course-manager'); ?></li>
                    <li><code>[ielts_courses order="ASC"]</code> - <?php _e('Sort order: ASC (ascending) or DESC (descending)', 'ielts-course-manager'); ?></li>
                    <li><code>[ielts_courses category="academic" columns="5" orderby="title" order="ASC"]</code> - <?php _e('Combine multiple options', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('Display Single Course', 'ielts-course-manager'); ?></h3>
                <p><code>[ielts_course id="123"]</code></p>
                
                <h3><?php _e('Display Progress Page (Admin View)', 'ielts-course-manager'); ?></h3>
                <p><?php _e('All courses:', 'ielts-course-manager'); ?></p>
                <p><code>[ielts_progress]</code></p>
                <p><?php _e('Specific course:', 'ielts-course-manager'); ?></p>
                <p><code>[ielts_progress course_id="123"]</code></p>
                
                <h3><?php _e('Display Student\'s Own Progress', 'ielts-course-manager'); ?></h3>
                <p><?php _e('All enrolled courses:', 'ielts-course-manager'); ?></p>
                <p><code>[ielts_my_progress]</code></p>
                <p><?php _e('Specific course:', 'ielts-course-manager'); ?></p>
                <p><code>[ielts_my_progress course_id="123"]</code></p>
                
                <h3><?php _e('Display Single Lesson', 'ielts-course-manager'); ?></h3>
                <p><code>[ielts_lesson id="456"]</code></p>
                
                <h3><?php _e('Display Quiz', 'ielts-course-manager'); ?></h3>
                <p><code>[ielts_quiz id="789"]</code></p>
                
                <h2><?php _e('Student Enrollment', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Students can enroll in courses by:', 'ielts-course-manager'); ?></p>
                <ul>
                    <li><?php _e('Clicking the "Enroll in this Course" button on course pages', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Administrators can manually enroll users through the backend', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h2><?php _e('Progress Tracking', 'ielts-course-manager'); ?></h2>
                <p><?php _e('Progress is automatically tracked when:', 'ielts-course-manager'); ?></p>
                <ul>
                    <li><?php _e('Students view lessons', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Students mark lessons as complete', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Students submit quizzes', 'ielts-course-manager'); ?></li>
                </ul>
                <p><?php _e('View all progress reports in IELTS Courses > Progress Reports', 'ielts-course-manager'); ?></p>
                
                <h2><?php _e('Quiz Types', 'ielts-course-manager'); ?></h2>
                
                <h3><?php _e('Multiple Choice', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Students select from predefined options.', 'ielts-course-manager'); ?></p>
                <ul>
                    <li><strong><?php _e('Options:', 'ielts-course-manager'); ?></strong> <?php _e('Enter each option on a new line', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Correct Answer:', 'ielts-course-manager'); ?></strong> <?php _e('Enter the option number (0 for first option, 1 for second, etc.)', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('True/False/Not Given', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Common in IELTS Reading tests. Students choose whether a statement is True, False, or Not Given based on the passage.', 'ielts-course-manager'); ?></p>
                <ul>
                    <li><strong><?php _e('Correct Answer:', 'ielts-course-manager'); ?></strong> <?php _e('Enter "true", "false", or "not_given" (lowercase)', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('Fill in the Blank', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Students type their answer directly. The system automatically compares answers with flexible matching.', 'ielts-course-manager'); ?></p>
                <ul>
                    <li><strong><?php _e('Question Text:', 'ielts-course-manager'); ?></strong> <?php _e('Include the blank in your question (e.g., "The capital of France is _____")', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Correct Answer:', 'ielts-course-manager'); ?></strong> <?php _e('Enter the expected answer. The system will ignore case, extra spaces, and punctuation when comparing.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Tips:', 'ielts-course-manager'); ?></strong>
                        <ul>
                            <li><?php _e('Be specific with your expected answer', 'ielts-course-manager'); ?></li>
                            <li><?php _e('The matching is case-insensitive (Paris = PARIS = paris)', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Extra spaces and punctuation are ignored', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Consider using multiple choice if you need exact formatting', 'ielts-course-manager'); ?></li>
                        </ul>
                    </li>
                </ul>
                
                <h3><?php _e('Essay', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Long-form written responses requiring manual grading by instructors.', 'ielts-course-manager'); ?></p>
                <ul>
                    <li><strong><?php _e('Note:', 'ielts-course-manager'); ?></strong> <?php _e('Essay questions are not automatically graded. They are saved for instructor review.', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h2><?php _e('Support', 'ielts-course-manager'); ?></h2>
                <p><?php _e('For issues or feature requests, please visit:', 'ielts-course-manager'); ?></p>
                <p><a href="https://github.com/impact2021/ielts-preparation-course" target="_blank">https://github.com/impact2021/ielts-preparation-course</a></p>
                
            </div>
            
            <style>
            .documentation-content h2 {
                margin-top: 30px;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #ddd;
            }
            .documentation-content h3 {
                margin-top: 20px;
                margin-bottom: 10px;
            }
            .documentation-content ol,
            .documentation-content ul {
                margin-left: 20px;
                line-height: 1.8;
            }
            .documentation-content code {
                background-color: #f4f4f4;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
                font-size: 14px;
            }
            .documentation-content p code {
                display: inline-block;
                margin: 5px 0;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ielts_cm_settings', 'ielts_cm_delete_data_on_uninstall', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        // Check user capability first
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Save settings if form submitted
        if (isset($_POST['ielts_cm_settings_nonce']) && wp_verify_nonce($_POST['ielts_cm_settings_nonce'], 'ielts_cm_settings')) {
            if (isset($_POST['ielts_cm_delete_data_on_uninstall'])) {
                update_option('ielts_cm_delete_data_on_uninstall', true);
            } else {
                update_option('ielts_cm_delete_data_on_uninstall', false);
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved.', 'ielts-course-manager') . '</p></div>';
        }
        
        $delete_data_on_uninstall = get_option('ielts_cm_delete_data_on_uninstall', false);
        ?>
        <div class="wrap">
            <h1><?php _e('IELTS Course Manager Settings', 'ielts-course-manager'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ielts_cm_settings', 'ielts_cm_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php _e('Data Management', 'ielts-course-manager'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="ielts_cm_delete_data_on_uninstall" value="1" <?php checked($delete_data_on_uninstall, true); ?>>
                                    <?php _e('Delete all plugin data when uninstalling', 'ielts-course-manager'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, all courses, lessons, resources, quizzes, progress data, and settings will be permanently deleted when you uninstall the plugin. When disabled (recommended), your data will be preserved.', 'ielts-course-manager'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Add custom column to LearnDash quiz list
     */
    public function learndash_quiz_columns($columns) {
        // Only add if LearnDash is active
        if (!post_type_exists('sfwd-quiz')) {
            return $columns;
        }
        
        // Add conversion column before date
        $new_columns = array();
        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['ielts_cm_convert'] = __('Convert to IELTS CM', 'ielts-course-manager');
            }
            $new_columns[$key] = $value;
        }
        
        return $new_columns;
    }
    
    /**
     * Display content for custom column
     */
    public function learndash_quiz_column_content($column, $post_id) {
        if ($column === 'ielts_cm_convert') {
            require_once IELTS_CM_PLUGIN_DIR . 'includes/class-learndash-converter.php';
            $converter = new IELTS_CM_LearnDash_Converter();
            
            // Check if already converted
            $existing_id = $converter->find_existing_quiz($post_id);
            
            if ($existing_id) {
                echo '<span style="color: #46b450;">✓ ' . esc_html__('Converted', 'ielts-course-manager') . '</span><br>';
                echo '<a href="' . esc_url(get_edit_post_link($existing_id)) . '" target="_blank">' . esc_html__('Edit IELTS Quiz', 'ielts-course-manager') . '</a>';
            } else {
                echo '<button type="button" class="button button-small ielts-convert-quiz-btn" data-quiz-id="' . esc_attr($post_id) . '">';
                echo esc_html__('Convert Quiz', 'ielts-course-manager');
                echo '</button>';
                echo '<span class="ielts-convert-status" style="display:none; margin-left: 10px;"></span>';
            }
        }
    }
    
    /**
     * Add JavaScript for quiz conversion on LearnDash quiz admin page
     */
    public function learndash_quiz_conversion_scripts() {
        $screen = get_current_screen();
        
        // Only load on LearnDash quiz list page
        if (!$screen || $screen->post_type !== 'sfwd-quiz' || $screen->base !== 'edit') {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle individual quiz conversion
            $(document).on('click', '.ielts-convert-quiz-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var quizId = $btn.data('quiz-id');
                var $status = $btn.siblings('.ielts-convert-status');
                
                if (!confirm('<?php echo esc_js(__('Convert this quiz to IELTS format?', 'ielts-course-manager')); ?>')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Converting...', 'ielts-course-manager')); ?>');
                $status.show().text('⏳').css('color', '#666');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_convert_single_quiz',
                        nonce: '<?php echo wp_create_nonce('ielts_cm_convert_quiz'); ?>',
                        quiz_id: quizId
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('✓ <span style="color: #46b450;"><?php echo esc_js(__('Converted!', 'ielts-course-manager')); ?></span>');
                            $btn.remove();
                            
                            // Show link to edit the new quiz
                            if (response.data.new_quiz_id) {
                                var editUrl = '<?php echo admin_url('post.php?action=edit&post='); ?>' + response.data.new_quiz_id;
                                $status.after('<br><a href="' + editUrl + '" target="_blank"><?php echo esc_js(__('Edit IELTS Quiz', 'ielts-course-manager')); ?></a>');
                            }
                            
                            // Show success message
                            if (response.data.message) {
                                $status.after('<br><small>' + response.data.message + '</small>');
                            }
                        } else {
                            $status.html('✗ <span style="color: #d63638;"><?php echo esc_js(__('Failed', 'ielts-course-manager')); ?></span>');
                            $btn.prop('disabled', false).text('<?php echo esc_js(__('Convert Quiz', 'ielts-course-manager')); ?>');
                            
                            var errorMsg = response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Conversion failed', 'ielts-course-manager')); ?>';
                            alert(errorMsg);
                        }
                    },
                    error: function(xhr, status, error) {
                        $status.html('✗ <span style="color: #d63638;"><?php echo esc_js(__('Error', 'ielts-course-manager')); ?></span>');
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Convert Quiz', 'ielts-course-manager')); ?>');
                        alert('<?php echo esc_js(__('AJAX error:', 'ielts-course-manager')); ?> ' + error);
                    }
                });
            });
        });
        </script>
        
        <style>
        .ielts-convert-quiz-btn {
            font-size: 12px;
            height: auto;
            line-height: 1.5;
            padding: 4px 8px;
        }
        </style>
        <?php
    }
    
    /**
     * Multi-site sync meta box
     */
    public function sync_meta_box($post) {
        $sync_manager = new IELTS_CM_Multi_Site_Sync();
        $subsites = $sync_manager->get_connected_subsites();
        
        // Determine content type
        $content_type_map = array(
            'ielts_course' => 'course',
            'ielts_lesson' => 'lesson',
            'ielts_resource' => 'resource',
            'ielts_quiz' => 'quiz'
        );
        $content_type = $content_type_map[$post->post_type] ?? '';
        
        // Get sync history
        $sync_history = $sync_manager->get_sync_history($post->ID, $content_type);
        $last_sync = !empty($sync_history) ? $sync_history[0] : null;
        
        wp_nonce_field('ielts_cm_sync_content', 'ielts_cm_sync_nonce');
        ?>
        <div class="ielts-cm-sync-meta">
            <?php if (empty($subsites)): ?>
                <p class="description">
                    <?php _e('No subsites connected. ', 'ielts-course-manager'); ?>
                    <a href="<?php echo admin_url('edit.php?post_type=ielts_course&page=ielts-cm-sync-settings'); ?>">
                        <?php _e('Add subsites', 'ielts-course-manager'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p class="description">
                    <?php printf(__('Push this %s to %d connected subsite(s).', 'ielts-course-manager'), $content_type, count($subsites)); ?>
                </p>
                
                <?php if ($last_sync): ?>
                    <p class="last-sync-info" style="font-size: 12px; color: #666;">
                        <strong><?php _e('Last synced:', 'ielts-course-manager'); ?></strong>
                        <?php echo human_time_diff(strtotime($last_sync->sync_date), current_time('timestamp')); ?> ago
                        <br>
                        <strong><?php _e('Status:', 'ielts-course-manager'); ?></strong>
                        <span class="sync-status-<?php echo esc_attr($last_sync->sync_status); ?>">
                            <?php echo esc_html($last_sync->sync_status); ?>
                        </span>
                    </p>
                <?php endif; ?>
                
                <button type="button" class="button button-primary button-large" 
                        id="ielts-cm-push-content"
                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                        data-content-type="<?php echo esc_attr($content_type); ?>">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Push to Subsites', 'ielts-course-manager'); ?>
                </button>
                
                <div id="ielts-cm-sync-status" style="margin-top: 10px;"></div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ielts-cm-push-content').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var postId = button.data('post-id');
                var contentType = button.data('content-type');
                var statusDiv = $('#ielts-cm-sync-status');
                
                // Confirm action
                if (!confirm('<?php _e('Are you sure you want to push this content to all connected subsites? This will update content on all subsites while preserving student progress.', 'ielts-course-manager'); ?>')) {
                    return;
                }
                
                // Disable button and show loading
                button.prop('disabled', true);
                statusDiv.html('<p class="sync-loading"><span class="spinner is-active" style="float: none;"></span> <?php _e('Pushing content to subsites...', 'ielts-course-manager'); ?></p>');
                
                // Make AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_push_to_subsites',
                        post_id: postId,
                        content_type: contentType,
                        nonce: $('#ielts_cm_sync_nonce').val()
                    },
                    success: function(response) {
                        button.prop('disabled', false);
                        
                        if (response.success) {
                            var html = '<div class="notice notice-success inline"><p><strong><?php _e('Success!', 'ielts-course-manager'); ?></strong> ' + response.data.message + '</p>';
                            
                            if (response.data.results) {
                                html += '<ul style="margin: 10px 0 0 20px;">';
                                $.each(response.data.results, function(siteId, result) {
                                    var icon = result.success ? '✓' : '✗';
                                    var color = result.success ? 'green' : 'red';
                                    html += '<li style="color: ' + color + ';">' + icon + ' ' + result.site_name + ': ' + (result.message || result.error) + '</li>';
                                });
                                html += '</ul>';
                            }
                            
                            html += '</div>';
                            statusDiv.html(html);
                        } else {
                            statusDiv.html('<div class="notice notice-error inline"><p><strong><?php _e('Error:', 'ielts-course-manager'); ?></strong> ' + response.data.message + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        button.prop('disabled', false);
                        statusDiv.html('<div class="notice notice-error inline"><p><strong><?php _e('Error:', 'ielts-course-manager'); ?></strong> ' + error + '</p></div>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .ielts-cm-sync-meta .button-large {
            width: 100%;
            height: auto;
            padding: 10px;
            margin-bottom: 10px;
        }
        .ielts-cm-sync-meta .button-large .dashicons {
            line-height: inherit;
        }
        .sync-status-success {
            color: green;
        }
        .sync-status-failed {
            color: red;
        }
        #ielts-cm-sync-status .notice {
            margin: 0;
            padding: 8px 12px;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for pushing content to subsites
     */
    public function ajax_push_to_subsites() {
        check_ajax_referer('ielts_cm_sync_content', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : '';
        
        if (!$post_id || !$content_type) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }
        
        $sync_manager = new IELTS_CM_Multi_Site_Sync();
        
        // Check if this is a primary site
        if (!$sync_manager->is_primary_site()) {
            wp_send_json_error(array('message' => 'Only primary sites can push content'));
        }
        
        // Push content to all subsites
        // For courses, push all child content (lessons, sublessons, exercises)
        if ($content_type === 'course') {
            $results = $sync_manager->push_content_with_children($post_id, $content_type);
        } else {
            $results = $sync_manager->push_content_to_subsites($post_id, $content_type);
        }
        
        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
        }
        
        // Get subsite names for detailed results
        $subsites = $sync_manager->get_connected_subsites();
        $subsite_names = array();
        foreach ($subsites as $subsite) {
            $subsite_names[$subsite->id] = $subsite->site_name;
        }
        
        // Format results with site names
        $formatted_results = array();
        
        // Handle course results with children
        if ($content_type === 'course' && isset($results['main'])) {
            // Format main course results
            foreach ($results['main'] as $site_id => $result) {
                $formatted_results[$site_id] = array(
                    'site_name' => $subsite_names[$site_id] ?? 'Unknown Site',
                    'success' => !is_wp_error($result) && isset($result['success']) && $result['success'],
                    'message' => is_wp_error($result) ? $result->get_error_message() : ($result['message'] ?? 'Success'),
                    'error' => is_wp_error($result) ? $result->get_error_message() : null
                );
            }
            
            // Count synced items
            $lesson_count = isset($results['lessons']) ? count($results['lessons']) : 0;
            $resource_count = 0;
            $exercise_count = 0;
            
            if (isset($results['lessons'])) {
                foreach ($results['lessons'] as $lesson_data) {
                    if (isset($lesson_data['resources'])) {
                        $resource_count += count($lesson_data['resources']);
                    }
                    if (isset($lesson_data['exercises'])) {
                        $exercise_count += count($lesson_data['exercises']);
                    }
                }
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Course and all child content pushed successfully: %d lesson(s), %d sublesson(s), %d exercise(s)', 'ielts-course-manager'),
                    $lesson_count,
                    $resource_count,
                    $exercise_count
                ),
                'results' => $formatted_results,
                'stats' => array(
                    'lessons' => $lesson_count,
                    'resources' => $resource_count,
                    'exercises' => $exercise_count
                )
            ));
        } else {
            // Handle regular content results
            foreach ($results as $site_id => $result) {
                $formatted_results[$site_id] = array(
                    'site_name' => $subsite_names[$site_id] ?? 'Unknown Site',
                    'success' => !is_wp_error($result) && isset($result['success']) && $result['success'],
                    'message' => is_wp_error($result) ? $result->get_error_message() : ($result['message'] ?? 'Success'),
                    'error' => is_wp_error($result) ? $result->get_error_message() : null
                );
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Content pushed to %d subsite(s)', 'ielts-course-manager'), count($results)),
                'results' => $formatted_results
            ));
        }
    }
    
    /**
     * AJAX handler to get lessons filtered by course IDs
     */
    public function ajax_get_lessons_by_courses() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_quiz_lessons_filter')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
        $selected_lesson_ids = isset($_POST['selected_lesson_ids']) ? array_map('intval', $_POST['selected_lesson_ids']) : array();
        
        if (empty($course_ids)) {
            // Return all lessons if no courses selected
            $lessons = get_posts(array(
                'post_type' => 'ielts_lesson',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => array('publish', 'draft', 'pending', 'private')
            ));
        } else {
            // Get lessons associated with selected courses
            global $wpdb;
            
            // Build the WHERE clause with proper prepared statements
            $like_conditions = array();
            $prepare_args = array('_ielts_cm_course_ids');
            
            foreach ($course_ids as $course_id) {
                $like_conditions[] = "meta_value LIKE %s";
                $prepare_args[] = '%' . $wpdb->esc_like(serialize(strval($course_id))) . '%';
            }
            
            $where_clause = implode(' OR ', $like_conditions);
            
            $query = "
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = %s 
                AND (" . $where_clause . ")
            ";
            
            $lesson_ids = $wpdb->get_col($wpdb->prepare($query, $prepare_args));
            
            if (empty($lesson_ids)) {
                wp_send_json_success(array('lessons' => array()));
                return;
            }
            
            $lessons = get_posts(array(
                'post_type' => 'ielts_lesson',
                'posts_per_page' => -1,
                'post__in' => $lesson_ids,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => array('publish', 'draft', 'pending', 'private')
            ));
        }
        
        $lessons_data = array();
        foreach ($lessons as $lesson) {
            $lessons_data[] = array(
                'id' => $lesson->ID,
                'title' => $lesson->post_title,
                'selected' => in_array($lesson->ID, $selected_lesson_ids)
            );
        }
        
        wp_send_json_success(array('lessons' => $lessons_data));
    }
    
    /**
     * AJAX handler to add a lesson to a course
     */
    public function ajax_add_lesson_to_course() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_course_lessons')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$course_id || !$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Get current course IDs for the lesson
        $course_ids = get_post_meta($lesson_id, '_ielts_cm_course_ids', true);
        if (!is_array($course_ids)) {
            $course_ids = array();
        }
        
        // Add course to lesson if not already added
        if (!in_array($course_id, $course_ids)) {
            $course_ids[] = $course_id;
            update_post_meta($lesson_id, '_ielts_cm_course_ids', $course_ids);
        }
        
        // Get lesson details
        $lesson = get_post($lesson_id);
        
        wp_send_json_success(array(
            'message' => __('Lesson added successfully', 'ielts-course-manager'),
            'lesson' => array(
                'id' => $lesson->ID,
                'title' => $lesson->post_title,
                'order' => $lesson->menu_order,
                'edit_link' => get_edit_post_link($lesson->ID)
            )
        ));
    }
    
    /**
     * AJAX handler to remove a lesson from a course
     */
    public function ajax_remove_lesson_from_course() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_course_lessons')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$course_id || !$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Get current course IDs for the lesson
        $course_ids = get_post_meta($lesson_id, '_ielts_cm_course_ids', true);
        if (!is_array($course_ids)) {
            $course_ids = array();
        }
        
        // Remove course from lesson
        $course_ids = array_diff($course_ids, array($course_id));
        update_post_meta($lesson_id, '_ielts_cm_course_ids', array_values($course_ids));
        
        wp_send_json_success(array('message' => __('Lesson removed successfully', 'ielts-course-manager')));
    }
    
    /**
     * AJAX handler to remove content (sublesson or exercise) from a lesson
     */
    public function ajax_remove_content_from_lesson() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_lesson_content')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
        
        if (!$lesson_id || !$content_id) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Get current lesson IDs for the content
        $lesson_ids = get_post_meta($content_id, '_ielts_cm_lesson_ids', true);
        if (!is_array($lesson_ids)) {
            $lesson_ids = array();
        }
        
        // Remove lesson from content
        $lesson_ids = array_diff($lesson_ids, array($lesson_id));
        update_post_meta($content_id, '_ielts_cm_lesson_ids', array_values($lesson_ids));
        
        wp_send_json_success(array('message' => __('Content removed successfully', 'ielts-course-manager')));
    }
    
    /**
     * AJAX handler to get available exercises not already in a lesson
     */
    public function ajax_get_available_exercises() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_lesson_content')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Get exercises not already in this lesson
        global $wpdb;
        $quiz_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
        ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
        
        $args = array(
            'post_type' => 'ielts_quiz',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => array('publish', 'draft')
        );
        
        if (!empty($quiz_ids)) {
            $args['post__not_in'] = $quiz_ids;
        }
        
        $exercises = get_posts($args);
        
        $exercises_data = array();
        foreach ($exercises as $exercise) {
            $exercises_data[] = array(
                'id' => $exercise->ID,
                'title' => $exercise->post_title
            );
        }
        
        wp_send_json_success(array('exercises' => $exercises_data));
    }
    
    /**
     * AJAX handler to get available sublessons not already in a lesson
     */
    public function ajax_get_available_sublessons() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_lesson_content')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Get sublessons not already in this lesson
        global $wpdb;
        $resource_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
               OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
        ", $lesson_id, '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%'));
        
        $args = array(
            'post_type' => 'ielts_resource',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => array('publish', 'draft')
        );
        
        if (!empty($resource_ids)) {
            $args['post__not_in'] = $resource_ids;
        }
        
        $sublessons = get_posts($args);
        
        $sublessons_data = array();
        foreach ($sublessons as $sublesson) {
            $sublessons_data[] = array(
                'id' => $sublesson->ID,
                'title' => $sublesson->post_title
            );
        }
        
        wp_send_json_success(array('sublessons' => $sublessons_data));
    }
    
    /**
     * AJAX handler to add content (sublesson or exercise) to a lesson
     */
    public function ajax_add_content_to_lesson() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ielts_cm_lesson_content')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ielts-course-manager')));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'ielts-course-manager')));
        }
        
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
        $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : '';
        
        if (!$lesson_id || !$content_id || !$content_type) {
            wp_send_json_error(array('message' => __('Invalid data', 'ielts-course-manager')));
        }
        
        // Get current lesson IDs for the content
        $lesson_ids = get_post_meta($content_id, '_ielts_cm_lesson_ids', true);
        if (!is_array($lesson_ids)) {
            $lesson_ids = array();
        }
        
        // Add lesson to content if not already added
        if (!in_array($lesson_id, $lesson_ids)) {
            $lesson_ids[] = $lesson_id;
            update_post_meta($content_id, '_ielts_cm_lesson_ids', $lesson_ids);
        }
        
        // Get content details
        $content = get_post($content_id);
        
        wp_send_json_success(array(
            'message' => __('Content added successfully', 'ielts-course-manager'),
            'content' => array(
                'id' => $content->ID,
                'title' => $content->post_title,
                'type' => $content_type,
                'order' => $content->menu_order,
                'edit_link' => get_edit_post_link($content->ID)
            )
        ));
    }
}
