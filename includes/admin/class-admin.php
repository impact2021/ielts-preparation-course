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
        
        $duration = get_post_meta($post->ID, '_ielts_cm_duration', true);
        $difficulty = get_post_meta($post->ID, '_ielts_cm_difficulty', true);
        ?>
        <p>
            <label for="ielts_cm_duration"><?php _e('Duration (hours)', 'ielts-course-manager'); ?></label><br>
            <input type="number" id="ielts_cm_duration" name="ielts_cm_duration" value="<?php echo esc_attr($duration); ?>" min="0" step="0.5" style="width: 100%;">
        </p>
        <p>
            <label for="ielts_cm_difficulty"><?php _e('Difficulty Level', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_difficulty" name="ielts_cm_difficulty" style="width: 100%;">
                <option value="beginner" <?php selected($difficulty, 'beginner'); ?>><?php _e('Beginner', 'ielts-course-manager'); ?></option>
                <option value="intermediate" <?php selected($difficulty, 'intermediate'); ?>><?php _e('Intermediate', 'ielts-course-manager'); ?></option>
                <option value="advanced" <?php selected($difficulty, 'advanced'); ?>><?php _e('Advanced', 'ielts-course-manager'); ?></option>
            </select>
        </p>
        <?php
    }
    
    /**
     * Lesson meta box
     */
    public function lesson_meta_box($post) {
        wp_nonce_field('ielts_cm_lesson_meta', 'ielts_cm_lesson_meta_nonce');
        
        $course_id = get_post_meta($post->ID, '_ielts_cm_course_id', true);
        $duration = get_post_meta($post->ID, '_ielts_cm_duration', true);
        
        $courses = get_posts(array(
            'post_type' => 'ielts_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <p>
            <label for="ielts_cm_course_id"><?php _e('Assign to Course', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_course_id" name="ielts_cm_course_id" style="width: 100%;">
                <option value=""><?php _e('Select a course', 'ielts-course-manager'); ?></option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo esc_attr($course->ID); ?>" <?php selected($course_id, $course->ID); ?>>
                        <?php echo esc_html($course->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="ielts_cm_lesson_duration"><?php _e('Duration (minutes)', 'ielts-course-manager'); ?></label><br>
            <input type="number" id="ielts_cm_lesson_duration" name="ielts_cm_lesson_duration" value="<?php echo esc_attr($duration); ?>" min="0" style="width: 100%;">
        </p>
        <?php
    }
    
    /**
     * Resource meta box
     */
    public function resource_meta_box($post) {
        wp_nonce_field('ielts_cm_resource_meta', 'ielts_cm_resource_meta_nonce');
        
        $lesson_id = get_post_meta($post->ID, '_ielts_cm_lesson_id', true);
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
            <label for="ielts_cm_lesson_id"><?php _e('Assign to Lesson', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_lesson_id" name="ielts_cm_lesson_id" style="width: 100%;">
                <option value=""><?php _e('Select a lesson', 'ielts-course-manager'); ?></option>
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?php echo esc_attr($lesson->ID); ?>" <?php selected($lesson_id, $lesson->ID); ?>>
                        <?php echo esc_html($lesson->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
        
        $course_id = get_post_meta($post->ID, '_ielts_cm_course_id', true);
        $lesson_id = get_post_meta($post->ID, '_ielts_cm_lesson_id', true);
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
            <label for="ielts_cm_quiz_course_id"><?php _e('Assign to Course', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_quiz_course_id" name="ielts_cm_quiz_course_id" style="width: 100%;">
                <option value=""><?php _e('Select a course', 'ielts-course-manager'); ?></option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo esc_attr($course->ID); ?>" <?php selected($course_id, $course->ID); ?>>
                        <?php echo esc_html($course->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="ielts_cm_quiz_lesson_id"><?php _e('Assign to Lesson (Optional)', 'ielts-course-manager'); ?></label><br>
            <select id="ielts_cm_quiz_lesson_id" name="ielts_cm_quiz_lesson_id" style="width: 100%;">
                <option value=""><?php _e('Select a lesson', 'ielts-course-manager'); ?></option>
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?php echo esc_attr($lesson->ID); ?>" <?php selected($lesson_id, $lesson->ID); ?>>
                        <?php echo esc_html($lesson->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
            var questionIndex = <?php echo count($questions); ?>;
            
            $('#add-question').on('click', function() {
                var html = '<?php echo addslashes($this->get_question_template()); ?>';
                html = html.replace(/QUESTION_INDEX/g, questionIndex);
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
            if (isset($_POST['ielts_cm_duration'])) {
                update_post_meta($post_id, '_ielts_cm_duration', sanitize_text_field($_POST['ielts_cm_duration']));
            }
            if (isset($_POST['ielts_cm_difficulty'])) {
                update_post_meta($post_id, '_ielts_cm_difficulty', sanitize_text_field($_POST['ielts_cm_difficulty']));
            }
        }
        
        // Save lesson meta
        if (isset($_POST['ielts_cm_lesson_meta_nonce']) && wp_verify_nonce($_POST['ielts_cm_lesson_meta_nonce'], 'ielts_cm_lesson_meta')) {
            if (isset($_POST['ielts_cm_course_id'])) {
                update_post_meta($post_id, '_ielts_cm_course_id', intval($_POST['ielts_cm_course_id']));
            }
            if (isset($_POST['ielts_cm_lesson_duration'])) {
                update_post_meta($post_id, '_ielts_cm_duration', sanitize_text_field($_POST['ielts_cm_lesson_duration']));
            }
        }
        
        // Save resource meta
        if (isset($_POST['ielts_cm_resource_meta_nonce']) && wp_verify_nonce($_POST['ielts_cm_resource_meta_nonce'], 'ielts_cm_resource_meta')) {
            if (isset($_POST['ielts_cm_lesson_id'])) {
                update_post_meta($post_id, '_ielts_cm_lesson_id', intval($_POST['ielts_cm_lesson_id']));
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
            if (isset($_POST['ielts_cm_quiz_course_id'])) {
                update_post_meta($post_id, '_ielts_cm_course_id', intval($_POST['ielts_cm_quiz_course_id']));
            }
            if (isset($_POST['ielts_cm_quiz_lesson_id'])) {
                update_post_meta($post_id, '_ielts_cm_lesson_id', intval($_POST['ielts_cm_quiz_lesson_id']));
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
            $lessons = get_posts(array(
                'post_type' => 'ielts_lesson',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_ielts_cm_course_id',
                        'value' => $post_id
                    )
                )
            ));
            echo count($lessons);
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
            $course_id = get_post_meta($post_id, '_ielts_cm_course_id', true);
            if ($course_id) {
                $course = get_post($course_id);
                echo '<a href="' . get_edit_post_link($course_id) . '">' . esc_html($course->post_title) . '</a>';
            }
        } elseif ($column === 'resources') {
            $resources = get_posts(array(
                'post_type' => 'ielts_resource',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_ielts_cm_lesson_id',
                        'value' => $post_id
                    )
                )
            ));
            echo count($resources);
        }
    }
}
