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
            <?php if (empty($lessons)): ?>
                <p><?php _e('No lessons have been assigned to this course yet. Create lessons and assign them to this course in the Lesson Settings.', 'ielts-course-manager'); ?></p>
            <?php else: ?>
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
            <?php if (empty($content_items)): ?>
                <p><?php _e('No lesson pages or exercises have been assigned to this lesson yet. Create lesson pages and assign them to this lesson in the Lesson Page Settings, or create exercises and assign them in the Exercise Settings.', 'ielts-course-manager'); ?></p>
            <?php else: ?>
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
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="content-order-status"></div>
            <?php endif; ?>
        </div>
        
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
        if (empty($course_ids)) {
            // Backward compatibility - check old single course_id
            $old_course_id = get_post_meta($post->ID, '_ielts_cm_course_id', true);
            $course_ids = $old_course_id ? array($old_course_id) : array();
        }
        
        // Support for multiple lessons - store as array
        $lesson_ids = get_post_meta($post->ID, '_ielts_cm_lesson_ids', true);
        if (empty($lesson_ids)) {
            // Backward compatibility - check old single lesson_id
            $old_lesson_id = get_post_meta($post->ID, '_ielts_cm_lesson_id', true);
            $lesson_ids = $old_lesson_id ? array($old_lesson_id) : array();
        }
        
        $questions = get_post_meta($post->ID, '_ielts_cm_questions', true);
        $pass_percentage = get_post_meta($post->ID, '_ielts_cm_pass_percentage', true);
        
        if (!$questions) {
            $questions = array();
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
            <label for="ielts_cm_pass_percentage"><?php _e('Pass Percentage', 'ielts-course-manager'); ?></label><br>
            <input type="number" id="ielts_cm_pass_percentage" name="ielts_cm_pass_percentage" value="<?php echo esc_attr($pass_percentage ? $pass_percentage : 70); ?>" min="0" max="100" style="width: 100%;">
        </p>
        
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
                    <li><strong><?php _e('True/False/Not Given:', 'ielts-course-manager'); ?></strong> <?php _e('Enter correct answer as "true", "false", or "not_given" (lowercase)', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Fill in the Blank:', 'ielts-course-manager'); ?></strong> <?php _e('Enter the expected answer. Matching is case-insensitive and ignores punctuation/extra spaces.', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Essay:', 'ielts-course-manager'); ?></strong> <?php _e('No correct answer needed - requires manual grading.', 'ielts-course-manager'); ?></li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var questionIndex = <?php echo intval(count($questions)); ?>;
            
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
                
                if (type === 'multiple_choice') {
                    container.find('.options-field').show();
                    container.find('.correct-answer-field label').text('<?php _e('Correct Answer (Option number)', 'ielts-course-manager'); ?>');
                    container.find('.correct-answer-field').show();
                } else if (type === 'true_false') {
                    container.find('.options-field').hide();
                    container.find('.correct-answer-field label').text('<?php _e('Correct Answer (true/false/not_given)', 'ielts-course-manager'); ?>');
                    container.find('.correct-answer-field').show();
                } else if (type === 'fill_blank') {
                    container.find('.options-field').hide();
                    container.find('.correct-answer-field label').text('<?php _e('Correct Answer', 'ielts-course-manager'); ?>');
                    container.find('.correct-answer-field').show();
                } else if (type === 'essay') {
                    container.find('.options-field').hide();
                    container.find('.correct-answer-field').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render question field
     */
    private function render_question_field($index, $question) {
        ?>
        <div class="question-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
            <h4><?php printf(__('Question %d', 'ielts-course-manager'), $index + 1); ?></h4>
            
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
            
            <p class="options-field" style="<?php echo (isset($question['type']) && $question['type'] !== 'multiple_choice') ? 'display:none;' : ''; ?>">
                <label><?php _e('Options (one per line)', 'ielts-course-manager'); ?></label><br>
                <textarea name="questions[<?php echo $index; ?>][options]" rows="4" style="width: 100%;"><?php echo esc_textarea(isset($question['options']) ? $question['options'] : ''); ?></textarea>
            </p>
            
            <p class="correct-answer-field" style="<?php echo (isset($question['type']) && $question['type'] === 'essay') ? 'display:none;' : ''; ?>">
                <label><?php _e('Correct Answer', 'ielts-course-manager'); ?></label><br>
                <input type="text" name="questions[<?php echo $index; ?>][correct_answer]" value="<?php echo esc_attr(isset($question['correct_answer']) ? $question['correct_answer'] : ''); ?>" style="width: 100%;">
            </p>
            
            <p>
                <label><?php _e('Points', 'ielts-course-manager'); ?></label><br>
                <input type="number" name="questions[<?php echo $index; ?>][points]" value="<?php echo esc_attr(isset($question['points']) ? $question['points'] : 1); ?>" min="0" step="0.5" style="width: 100%;">
            </p>
            
            <button type="button" class="button remove-question"><?php _e('Remove Question', 'ielts-course-manager'); ?></button>
        </div>
        <?php
    }
    
    /**
     * Get question template
     */
    private function get_question_template() {
        ob_start();
        ?>
        <div class="question-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
            <h4><?php _e('New Question', 'ielts-course-manager'); ?></h4>
            
            <p>
                <label><?php _e('Question Type', 'ielts-course-manager'); ?></label><br>
                <select name="questions[QUESTION_INDEX][type]" class="question-type" style="width: 100%;">
                    <?php foreach (IELTS_CM_Quiz_Handler::get_quiz_types() as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label><?php _e('Question Text', 'ielts-course-manager'); ?></label><br>
                <textarea name="questions[QUESTION_INDEX][question]" rows="8" style="width: 100%;"></textarea>
                <small><?php _e('HTML is supported. You can paste HTML with images and formatting. Save the post to enable the visual editor for this question.', 'ielts-course-manager'); ?></small>
            </p>
            
            <p class="options-field">
                <label><?php _e('Options (one per line)', 'ielts-course-manager'); ?></label><br>
                <textarea name="questions[QUESTION_INDEX][options]" rows="4" style="width: 100%;"></textarea>
            </p>
            
            <p class="correct-answer-field">
                <label><?php _e('Correct Answer', 'ielts-course-manager'); ?></label><br>
                <input type="text" name="questions[QUESTION_INDEX][correct_answer]" style="width: 100%;">
            </p>
            
            <p>
                <label><?php _e('Points', 'ielts-course-manager'); ?></label><br>
                <input type="number" name="questions[QUESTION_INDEX][points]" value="1" min="0" step="0.5" style="width: 100%;">
            </p>
            
            <button type="button" class="button remove-question"><?php _e('Remove Question', 'ielts-course-manager'); ?></button>
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
            // Always save questions, even if empty
            $questions = array();
            if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                foreach ($_POST['questions'] as $question) {
                    // Skip empty questions
                    if (empty($question['question'])) {
                        continue;
                    }
                    $questions[] = array(
                        'type' => sanitize_text_field($question['type']),
                        'question' => wp_kses_post($question['question']), // Allow HTML with images
                        'options' => isset($question['options']) ? sanitize_textarea_field($question['options']) : '',
                        'correct_answer' => isset($question['correct_answer']) ? sanitize_text_field($question['correct_answer']) : '',
                        'points' => isset($question['points']) ? floatval($question['points']) : 1
                    );
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
                    <li><code>[ielts_courses category="advanced" columns="4" limit="8"]</code> - <?php _e('Combine multiple options', 'ielts-course-manager'); ?></li>
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
}
