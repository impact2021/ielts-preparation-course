<?php
/**
 * LearnDash to IELTS Course Manager Direct Converter
 * 
 * Handles converting LearnDash courses directly from the database
 * when both plugins are installed on the same site
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_LearnDash_Converter {
    
    /**
     * Converted items tracking
     */
    private $converted_courses = array();
    private $converted_lessons = array();
    private $converted_topics = array();
    private $converted_quizzes = array();
    private $conversion_log = array();
    private $errors = array();
    
    /**
     * LearnDash to IELTS CM post type mapping
     */
    private $post_type_map = array(
        'sfwd-courses' => 'ielts_course',
        'sfwd-lessons' => 'ielts_lesson',
        'sfwd-topic' => 'ielts_resource',
        'sfwd-quiz' => 'ielts_quiz'
    );
    
    /**
     * Check if LearnDash is installed
     */
    public function is_learndash_active() {
        return class_exists('SFWD_LMS') || post_type_exists('sfwd-courses');
    }
    
    /**
     * Get all LearnDash courses
     */
    public function get_learndash_courses() {
        if (!$this->is_learndash_active()) {
            return array();
        }
        
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft'),
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        return $courses;
    }
    
    /**
     * Convert a single LearnDash course
     * 
     * @param int $course_id LearnDash course ID
     * @return array Conversion results
     */
    public function convert_course($course_id) {
        $this->conversion_log = array();
        $this->errors = array();
        
        $course = get_post($course_id);
        
        if (!$course || $course->post_type !== 'sfwd-courses') {
            $this->log('Error: Invalid LearnDash course ID', 'error');
            return $this->get_results();
        }
        
        $this->log('Starting conversion of course: ' . esc_html($course->post_title) . ' (ID: ' . $course_id . ')');
        
        // Check if already converted
        $existing_id = $this->find_existing_course($course_id);
        if ($existing_id) {
            $this->log("Course already converted (IELTS CM ID: {$existing_id}). Skipping.", 'warning');
            return $this->get_results();
        }
        
        // Convert the course
        $new_course_id = $this->convert_course_post($course);
        
        if (!$new_course_id) {
            $this->log('Failed to convert course', 'error');
            return $this->get_results();
        }
        
        $this->converted_courses[$course_id] = $new_course_id;
        
        // Get and convert lessons
        $lessons = $this->get_course_lessons($course_id);
        foreach ($lessons as $lesson) {
            $this->convert_lesson($lesson, $course_id, $new_course_id);
        }
        
        // Get and convert quizzes associated with course
        $quizzes = $this->get_course_quizzes($course_id);
        foreach ($quizzes as $quiz) {
            $this->convert_quiz($quiz, $course_id, $new_course_id, null);
        }
        
        $this->log('Course conversion completed successfully');
        return $this->get_results();
    }
    
    /**
     * Convert course post
     */
    private function convert_course_post($course) {
        $this->log('Converting course: ' . esc_html($course->post_title));
        
        // Validate post status
        $valid_statuses = array('publish', 'draft', 'pending', 'private');
        $post_status = in_array($course->post_status, $valid_statuses) ? $course->post_status : 'draft';
        
        $post_data = array(
            'post_title' => sanitize_text_field($course->post_title),
            'post_content' => wp_kses_post($course->post_content),
            'post_excerpt' => sanitize_textarea_field($course->post_excerpt),
            'post_status' => $post_status,
            'post_type' => 'ielts_course',
            'post_date' => sanitize_text_field($course->post_date)
        );
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            $this->log("Error creating course: " . $new_id->get_error_message(), 'error');
            return false;
        }
        
        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id($course->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_id, $thumbnail_id);
        }
        
        // Store original LearnDash ID for reference
        update_post_meta($new_id, '_ld_original_id', $course->ID);
        update_post_meta($new_id, '_converted_from_learndash', current_time('mysql'));
        
        $this->log("Course converted successfully (New ID: {$new_id})");
        return $new_id;
    }
    
    /**
     * Get lessons for a course
     */
    private function get_course_lessons($course_id) {
        global $wpdb;
        
        // Get lessons associated with this course
        $lesson_ids = get_post_meta($course_id, 'ld_course_' . $course_id, true);
        
        if (empty($lesson_ids)) {
            // Try alternative method
            $lesson_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = 'course_id' AND meta_value = %d",
                $course_id
            ));
        }
        
        if (empty($lesson_ids)) {
            return array();
        }
        
        $lessons = get_posts(array(
            'post_type' => 'sfwd-lessons',
            'posts_per_page' => -1,
            'post__in' => $lesson_ids,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        return $lessons;
    }
    
    /**
     * Convert a lesson
     */
    private function convert_lesson($lesson, $old_course_id, $new_course_id) {
        $this->log('Converting lesson: ' . esc_html($lesson->post_title));
        
        // Check if already converted
        $existing_id = $this->find_existing_lesson($lesson->ID);
        if ($existing_id) {
            $this->log("Lesson already converted (ID: {$existing_id}). Linking to course.", 'warning');
            $this->link_lesson_to_course($existing_id, $new_course_id);
            $this->converted_lessons[$lesson->ID] = $existing_id;
            return $existing_id;
        }
        
        // Validate post status
        $valid_statuses = array('publish', 'draft', 'pending', 'private');
        $post_status = in_array($lesson->post_status, $valid_statuses) ? $lesson->post_status : 'draft';
        
        $post_data = array(
            'post_title' => sanitize_text_field($lesson->post_title),
            'post_content' => wp_kses_post($lesson->post_content),
            'post_excerpt' => sanitize_textarea_field($lesson->post_excerpt),
            'post_status' => $post_status,
            'post_type' => 'ielts_lesson',
            'post_date' => sanitize_text_field($lesson->post_date),
            'menu_order' => intval($lesson->menu_order)
        );
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            $this->log("Error creating lesson: " . $new_id->get_error_message(), 'error');
            return false;
        }
        
        // Link to course
        $this->link_lesson_to_course($new_id, $new_course_id);
        
        // Store original LearnDash ID
        update_post_meta($new_id, '_ld_original_id', $lesson->ID);
        update_post_meta($new_id, '_converted_from_learndash', current_time('mysql'));
        
        $this->converted_lessons[$lesson->ID] = $new_id;
        $this->log("Lesson converted successfully (New ID: {$new_id})");
        
        // Convert topics (lesson pages)
        $topics = $this->get_lesson_topics($lesson->ID);
        foreach ($topics as $topic) {
            $this->convert_topic($topic, $lesson->ID, $new_id);
        }
        
        // Convert quizzes associated with this lesson
        $lesson_quizzes = $this->get_lesson_quizzes($lesson->ID);
        foreach ($lesson_quizzes as $quiz) {
            $this->convert_quiz($quiz, $old_course_id, $new_course_id, $new_id);
        }
        
        return $new_id;
    }
    
    /**
     * Link lesson to course
     */
    private function link_lesson_to_course($lesson_id, $course_id) {
        $course_ids = get_post_meta($lesson_id, '_ielts_cm_course_ids', true);
        if (!is_array($course_ids)) {
            $course_ids = array();
        }
        
        if (!in_array($course_id, $course_ids)) {
            $course_ids[] = $course_id;
            update_post_meta($lesson_id, '_ielts_cm_course_ids', $course_ids);
            update_post_meta($lesson_id, '_ielts_cm_course_id', $course_id);
        }
    }
    
    /**
     * Get topics (lesson pages) for a lesson
     */
    private function get_lesson_topics($lesson_id) {
        global $wpdb;
        
        $topic_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'lesson_id' AND meta_value = %d",
            $lesson_id
        ));
        
        if (empty($topic_ids)) {
            return array();
        }
        
        $topics = get_posts(array(
            'post_type' => 'sfwd-topic',
            'posts_per_page' => -1,
            'post__in' => $topic_ids,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        return $topics;
    }
    
    /**
     * Convert a topic (lesson page)
     */
    private function convert_topic($topic, $old_lesson_id, $new_lesson_id) {
        $this->log('Converting topic: ' . esc_html($topic->post_title));
        
        // Check if already converted
        $existing_id = $this->find_existing_topic($topic->ID);
        if ($existing_id) {
            $this->log("Topic already converted (ID: {$existing_id}). Linking to lesson.", 'warning');
            $this->link_resource_to_lesson($existing_id, $new_lesson_id);
            $this->converted_topics[$topic->ID] = $existing_id;
            return $existing_id;
        }
        
        // Validate post status
        $valid_statuses = array('publish', 'draft', 'pending', 'private');
        $post_status = in_array($topic->post_status, $valid_statuses) ? $topic->post_status : 'draft';
        
        $post_data = array(
            'post_title' => sanitize_text_field($topic->post_title),
            'post_content' => wp_kses_post($topic->post_content),
            'post_excerpt' => sanitize_textarea_field($topic->post_excerpt),
            'post_status' => $post_status,
            'post_type' => 'ielts_resource',
            'post_date' => sanitize_text_field($topic->post_date),
            'menu_order' => intval($topic->menu_order)
        );
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            $this->log("Error creating topic: " . $new_id->get_error_message(), 'error');
            return false;
        }
        
        // Link to lesson
        $this->link_resource_to_lesson($new_id, $new_lesson_id);
        
        // Store original LearnDash ID
        update_post_meta($new_id, '_ld_original_id', $topic->ID);
        update_post_meta($new_id, '_converted_from_learndash', current_time('mysql'));
        
        $this->converted_topics[$topic->ID] = $new_id;
        $this->log("Topic converted successfully (New ID: {$new_id})");
        
        return $new_id;
    }
    
    /**
     * Link resource to lesson
     */
    private function link_resource_to_lesson($resource_id, $lesson_id) {
        $lesson_ids = get_post_meta($resource_id, '_ielts_cm_lesson_ids', true);
        if (!is_array($lesson_ids)) {
            $lesson_ids = array();
        }
        
        if (!in_array($lesson_id, $lesson_ids)) {
            $lesson_ids[] = $lesson_id;
            update_post_meta($resource_id, '_ielts_cm_lesson_ids', $lesson_ids);
            update_post_meta($resource_id, '_ielts_cm_lesson_id', $lesson_id);
        }
    }
    
    /**
     * Get quizzes for a course (not associated with specific lessons)
     */
    private function get_course_quizzes($course_id) {
        global $wpdb;
        
        // Get all quizzes for this course
        $all_quiz_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'course_id' AND meta_value = %d",
            $course_id
        ));
        
        if (empty($all_quiz_ids)) {
            return array();
        }
        
        // Get quizzes that are associated with lessons
        $lesson_quiz_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = 'lesson_id' 
            AND pm.post_id IN (" . implode(',', array_map('intval', $all_quiz_ids)) . ")"
        ));
        
        // Return only quizzes that are NOT associated with lessons
        $course_only_quiz_ids = array_diff($all_quiz_ids, $lesson_quiz_ids);
        
        if (empty($course_only_quiz_ids)) {
            return array();
        }
        
        $quizzes = get_posts(array(
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1,
            'post__in' => $course_only_quiz_ids,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        return $quizzes;
    }
    
    /**
     * Get quizzes for a lesson
     */
    private function get_lesson_quizzes($lesson_id) {
        global $wpdb;
        
        $quiz_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'lesson_id' AND meta_value = %d",
            $lesson_id
        ));
        
        if (empty($quiz_ids)) {
            return array();
        }
        
        $quizzes = get_posts(array(
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1,
            'post__in' => $quiz_ids,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        return $quizzes;
    }
    
    /**
     * Convert a quiz
     */
    private function convert_quiz($quiz, $old_course_id, $new_course_id, $new_lesson_id = null) {
        $this->log('Converting quiz: ' . esc_html($quiz->post_title));
        
        // Check if already converted
        $existing_id = $this->find_existing_quiz($quiz->ID);
        if ($existing_id) {
            $this->log("Quiz already converted (ID: {$existing_id}). Linking to lesson if needed.", 'warning');
            
            // If we have a lesson_id and the quiz is already converted, link it to the lesson
            if ($new_lesson_id) {
                $this->link_quiz_to_lesson($existing_id, $new_lesson_id);
            }
            
            $this->converted_quizzes[$quiz->ID] = $existing_id;
            return $existing_id;
        }
        
        // Validate post status
        $valid_statuses = array('publish', 'draft', 'pending', 'private');
        $post_status = in_array($quiz->post_status, $valid_statuses) ? $quiz->post_status : 'draft';
        
        $post_data = array(
            'post_title' => sanitize_text_field($quiz->post_title),
            'post_content' => wp_kses_post($quiz->post_content),
            'post_excerpt' => sanitize_textarea_field($quiz->post_excerpt),
            'post_status' => $post_status,
            'post_type' => 'ielts_quiz',
            'post_date' => sanitize_text_field($quiz->post_date),
            'menu_order' => intval($quiz->menu_order)
        );
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            $this->log("Error creating quiz: " . $new_id->get_error_message(), 'error');
            return false;
        }
        
        // Link to course
        update_post_meta($new_id, '_ielts_cm_course_ids', array($new_course_id));
        update_post_meta($new_id, '_ielts_cm_course_id', $new_course_id);
        
        // Link to lesson if provided
        if ($new_lesson_id) {
            $this->link_quiz_to_lesson($new_id, $new_lesson_id);
        }
        
        // Copy pass percentage if exists
        $pass_percentage = get_post_meta($quiz->ID, 'quiz_pass_percentage', true);
        if ($pass_percentage) {
            update_post_meta($new_id, '_ielts_cm_pass_percentage', $pass_percentage);
        } else {
            update_post_meta($new_id, '_ielts_cm_pass_percentage', 70);
        }
        
        // Store original LearnDash ID
        update_post_meta($new_id, '_ld_original_id', $quiz->ID);
        update_post_meta($new_id, '_converted_from_learndash', current_time('mysql'));
        
        // Note: LearnDash quiz questions use a different system
        // They need to be manually recreated
        $this->log("Quiz converted (questions need manual review)", 'warning');
        
        $this->converted_quizzes[$quiz->ID] = $new_id;
        $this->log("Quiz converted successfully (New ID: {$new_id})");
        
        return $new_id;
    }
    
    /**
     * Link quiz to lesson
     */
    private function link_quiz_to_lesson($quiz_id, $lesson_id) {
        $lesson_ids = get_post_meta($quiz_id, '_ielts_cm_lesson_ids', true);
        if (!is_array($lesson_ids)) {
            $lesson_ids = array();
        }
        
        if (!in_array($lesson_id, $lesson_ids)) {
            $lesson_ids[] = $lesson_id;
            update_post_meta($quiz_id, '_ielts_cm_lesson_ids', $lesson_ids);
            update_post_meta($quiz_id, '_ielts_cm_lesson_id', $lesson_id);
        }
    }
    
    /**
     * Find existing converted course
     */
    public function find_existing_course($ld_course_id) {
        global $wpdb;
        
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_ld_original_id' 
            AND pm.meta_value = %d 
            AND p.post_type = 'ielts_course'
            LIMIT 1",
            $ld_course_id
        ));
        
        return $existing_id ? intval($existing_id) : false;
    }
    
    /**
     * Find existing converted lesson
     */
    private function find_existing_lesson($ld_lesson_id) {
        global $wpdb;
        
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_ld_original_id' 
            AND pm.meta_value = %d 
            AND p.post_type = 'ielts_lesson'
            LIMIT 1",
            $ld_lesson_id
        ));
        
        return $existing_id ? intval($existing_id) : false;
    }
    
    /**
     * Find existing converted topic
     */
    private function find_existing_topic($ld_topic_id) {
        global $wpdb;
        
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_ld_original_id' 
            AND pm.meta_value = %d 
            AND p.post_type = 'ielts_resource'
            LIMIT 1",
            $ld_topic_id
        ));
        
        return $existing_id ? intval($existing_id) : false;
    }
    
    /**
     * Find existing converted quiz
     */
    private function find_existing_quiz($ld_quiz_id) {
        global $wpdb;
        
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_ld_original_id' 
            AND pm.meta_value = %d 
            AND p.post_type = 'ielts_quiz'
            LIMIT 1",
            $ld_quiz_id
        ));
        
        return $existing_id ? intval($existing_id) : false;
    }
    
    /**
     * Log a message
     */
    private function log($message, $level = 'info') {
        $this->conversion_log[] = array(
            'message' => $message,
            'level' => $level,
            'time' => current_time('mysql')
        );
        
        if ($level === 'error') {
            $this->errors[] = $message;
        }
    }
    
    /**
     * Get conversion results
     */
    public function get_results() {
        return array(
            'success' => empty($this->errors),
            'courses' => count($this->converted_courses),
            'lessons' => count($this->converted_lessons),
            'topics' => count($this->converted_topics),
            'quizzes' => count($this->converted_quizzes),
            'log' => $this->conversion_log,
            'errors' => $this->errors
        );
    }
}
