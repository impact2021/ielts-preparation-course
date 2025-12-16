<?php
/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Admin {
    
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
        
        // Resource meta box
        add_meta_box(
            'ielts_cm_resource_meta',
            __('Resource Settings', 'ielts-course-manager'),
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
        
        <script>
        jQuery(document).ready(function($) {
            <?php if (!empty($lessons)): ?>
            $('#course-lessons-sortable').sortable({
                placeholder: 'ui-sortable-placeholder',
                update: function(event, ui) {
                    var lessonOrder = [];
                    $('#course-lessons-sortable .lesson-item').each(function(index) {
                        lessonOrder.push({
                            lesson_id: $(this).data('lesson-id'),
                            order: index
                        });
                    });
                    
                    // Save the new order via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ielts_cm_update_lesson_order',
                            nonce: '<?php echo wp_create_nonce('ielts_cm_lesson_order'); ?>',
                            course_id: <?php echo intval($post->ID); ?>,
                            lesson_order: lessonOrder
                        },
                        success: function(response) {
                            if (response.success) {
                                $('.lesson-order-status')
                                    .removeClass('error')
                                    .addClass('success')
                                    .text('<?php _e('Lesson order updated successfully!', 'ielts-course-manager'); ?>')
                                    .fadeIn()
                                    .delay(3000)
                                    .fadeOut();
                                
                                // Update the order numbers in the UI
                                $('#course-lessons-sortable .lesson-item').each(function(index) {
                                    $(this).find('.lesson-order').text('<?php _e('Order:', 'ielts-course-manager'); ?> ' + index);
                                });
                            } else {
                                $('.lesson-order-status')
                                    .removeClass('success')
                                    .addClass('error')
                                    .text('<?php _e('Failed to update lesson order. Please try again.', 'ielts-course-manager'); ?>')
                                    .fadeIn()
                                    .delay(5000)
                                    .fadeOut();
                            }
                        },
                        error: function() {
                            $('.lesson-order-status')
                                .removeClass('success')
                                .addClass('error')
                                .text('<?php _e('An error occurred. Please try again.', 'ielts-course-manager'); ?>')
                                .fadeIn()
                                .delay(5000)
                                .fadeOut();
                        }
                    });
                }
            });
            <?php endif; ?>
        });
        </script>
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
     * Resource meta box
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
        $resource_type = get_post_meta($post->ID, '_ielts_cm_resource_type', true);
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
            <label for="ielts_cm_resource_type"><?php _e('Resource Type', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_resource_type" name="ielts_cm_resource_type" style="width: 100%;">
                <option value="document" <?php selected($resource_type, 'document'); ?>><?php _e('Document', 'ielts-course-manager'); ?></option>
                <option value="video" <?php selected($resource_type, 'video'); ?>><?php _e('Video', 'ielts-course-manager'); ?></option>
                <option value="audio" <?php selected($resource_type, 'audio'); ?>><?php _e('Audio', 'ielts-course-manager'); ?></option>
                <option value="link" <?php selected($resource_type, 'link'); ?>><?php _e('External Link', 'ielts-course-manager'); ?></option>
            </select>
        </p>
        <p>
            <label for="ielts_cm_resource_url"><?php _e('Resource URL', 'ielts-course-manager'); ?></label><br>
            <input type="url" id="ielts_cm_resource_url" name="ielts_cm_resource_url" value="<?php echo esc_attr($resource_url); ?>" style="width: 100%;">
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
            <div id="questions-container">
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $index => $question): ?>
                        <?php $this->render_question_field($index, $question); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="add-question"><?php _e('Add Question', 'ielts-course-manager'); ?></button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var questionIndex = <?php echo intval(count($questions)); ?>;
            
            $('#add-question').on('click', function() {
                var template = <?php echo json_encode($this->get_question_template()); ?>;
                var html = template.replace(/QUESTION_INDEX/g, questionIndex);
                $('#questions-container').append(html);
                questionIndex++;
            });
            
            $(document).on('click', '.remove-question', function() {
                $(this).closest('.question-item').remove();
            });
            
            $(document).on('change', '.question-type', function() {
                var type = $(this).val();
                var container = $(this).closest('.question-item');
                
                if (type === 'multiple_choice') {
                    container.find('.options-field').show();
                    container.find('.correct-answer-field label').text('<?php _e('Correct Answer (Option number)', 'ielts-course-manager'); ?>');
                } else if (type === 'true_false') {
                    container.find('.options-field').hide();
                    container.find('.correct-answer-field label').text('<?php _e('Correct Answer (true/false)', 'ielts-course-manager'); ?>');
                } else if (type === 'fill_blank') {
                    container.find('.options-field').hide();
                    container.find('.correct-answer-field label').text('<?php _e('Correct Answer', 'ielts-course-manager'); ?>');
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
            
            <p>
                <label><?php _e('Question Text', 'ielts-course-manager'); ?></label><br>
                <textarea name="questions[<?php echo $index; ?>][question]" rows="3" style="width: 100%;"><?php echo esc_textarea(isset($question['question']) ? $question['question'] : ''); ?></textarea>
            </p>
            
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
                <textarea name="questions[QUESTION_INDEX][question]" rows="3" style="width: 100%;"></textarea>
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
        
        // Save resource meta
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
            if (isset($_POST['ielts_cm_resource_type'])) {
                update_post_meta($post_id, '_ielts_cm_resource_type', sanitize_text_field($_POST['ielts_cm_resource_type']));
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
            if (isset($_POST['questions'])) {
                $questions = array();
                foreach ($_POST['questions'] as $question) {
                    $questions[] = array(
                        'type' => sanitize_text_field($question['type']),
                        'question' => sanitize_textarea_field($question['question']),
                        'options' => isset($question['options']) ? sanitize_textarea_field($question['options']) : '',
                        'correct_answer' => isset($question['correct_answer']) ? sanitize_text_field($question['correct_answer']) : '',
                        'points' => isset($question['points']) ? floatval($question['points']) : 1
                    );
                }
                update_post_meta($post_id, '_ielts_cm_questions', $questions);
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
        $columns['lessons'] = __('Lessons', 'ielts-course-manager');
        $columns['enrolled'] = __('Enrolled', 'ielts-course-manager');
        return $columns;
    }
    
    /**
     * Course column content
     */
    public function course_column_content($column, $post_id) {
        if ($column === 'lessons') {
            global $wpdb;
            $lesson_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_course_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_course_ids' AND meta_value LIKE %s)
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
        $columns['resources'] = __('Resources', 'ielts-course-manager');
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
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
                   OR (meta_key = '_ielts_cm_lesson_ids' AND meta_value LIKE %s)
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
                    <li><?php _e('Set duration (hours) and difficulty level', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Add a featured image (optional)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the course', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('2. Create Lessons', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Go to IELTS Courses > Lessons > Add New', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Enter lesson title and content', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Assign to one or more courses (use Ctrl/Cmd to select multiple)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Set lesson duration in minutes', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Use the menu order field to control lesson sequence', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the lesson', 'ielts-course-manager'); ?></li>
                </ol>
                
                <h3><?php _e('3. Add Resources', 'ielts-course-manager'); ?></h3>
                <ol>
                    <li><?php _e('Go to IELTS Courses > Resources > Add New', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Enter resource title and description', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Assign to one or more lessons (use Ctrl/Cmd to select multiple)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Select resource type (Document, Video, Audio, or Link)', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Add resource URL', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Publish the resource', 'ielts-course-manager'); ?></li>
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
                    <li><?php _e('Resources can be assigned to multiple lessons', 'ielts-course-manager'); ?></li>
                    <li><?php _e('Quizzes can be assigned to multiple courses and/or lessons', 'ielts-course-manager'); ?></li>
                </ul>
                <p><?php _e('This allows you to reuse content across different courses without duplicating it.', 'ielts-course-manager'); ?></p>
                
                <h2><?php _e('Available Shortcodes', 'ielts-course-manager'); ?></h2>
                
                <h3><?php _e('Display All Courses', 'ielts-course-manager'); ?></h3>
                <p><code>[ielts_courses]</code></p>
                <p><?php _e('With category filter:', 'ielts-course-manager'); ?></p>
                <p><code>[ielts_courses category="beginner" limit="10"]</code></p>
                
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
                <ul>
                    <li><strong><?php _e('Multiple Choice', 'ielts-course-manager'); ?></strong> - <?php _e('Students select from predefined options', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('True/False', 'ielts-course-manager'); ?></strong> - <?php _e('Binary choice questions', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Fill in the Blank', 'ielts-course-manager'); ?></strong> - <?php _e('Text input answers', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Essay', 'ielts-course-manager'); ?></strong> - <?php _e('Long-form responses (requires manual grading)', 'ielts-course-manager'); ?></li>
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
}
