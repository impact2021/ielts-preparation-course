<?php
/**
 * Direct LearnDash to IELTS Course Manager Migrator
 * 
 * Migrates content directly from LearnDash when both plugins are active
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Direct_Migrator {
    
    private $migrated_courses = array();
    private $migrated_lessons = array();
    private $migrated_topics = array();
    private $migrated_quizzes = array();
    private $migrated_questions = array();
    private $migration_log = array();
    
    /**
     * Perform the migration
     */
    public function migrate($options = array()) {
        $this->log('Starting direct LearnDash migration');
        
        $default_options = array(
            'skip_duplicates' => true,
            'include_drafts' => false
        );
        
        $options = wp_parse_args($options, $default_options);
        
        // Step 1: Migrate courses
        $this->migrate_courses($options);
        
        // Step 2: Migrate lessons
        $this->migrate_lessons($options);
        
        // Step 3: Migrate topics (lesson pages)
        $this->migrate_topics($options);
        
        // Step 4: Migrate questions first (needed for quizzes)
        $this->migrate_questions($options);
        
        // Step 5: Migrate quizzes
        $this->migrate_quizzes($options);
        
        // Step 6: Update relationships
        $this->update_relationships();
        
        $this->log('Migration completed');
        
        return $this->get_results();
    }
    
    /**
     * Migrate courses
     */
    private function migrate_courses($options) {
        $this->log('Migrating courses...');
        
        $post_status = $options['include_drafts'] ? array('publish', 'draft') : array('publish');
        
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => $post_status,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        foreach ($courses as $course) {
            $this->migrate_course($course, $options);
        }
        
        $this->log(sprintf('Migrated %d courses', count($this->migrated_courses)));
    }
    
    /**
     * Migrate a single course
     */
    private function migrate_course($ld_course, $options) {
        // Check for duplicates
        if ($options['skip_duplicates']) {
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'ielts_course' AND post_status != 'trash' LIMIT 1",
                $ld_course->post_title
            ));
            
            if ($existing) {
                $this->log(sprintf('Skipping duplicate course: %s', $ld_course->post_title), 'warning');
                return;
            }
        }
        
        // Create new course
        $new_course_id = wp_insert_post(array(
            'post_title' => $ld_course->post_title,
            'post_content' => $ld_course->post_content,
            'post_excerpt' => $ld_course->post_excerpt,
            'post_status' => $ld_course->post_status,
            'post_type' => 'ielts_course',
            'post_author' => $ld_course->post_author,
            'post_date' => $ld_course->post_date,
            'menu_order' => $ld_course->menu_order
        ));
        
        if (is_wp_error($new_course_id)) {
            $this->log(sprintf('Error migrating course %s: %s', $ld_course->post_title, $new_course_id->get_error_message()), 'error');
            return;
        }
        
        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id($ld_course->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_course_id, $thumbnail_id);
        }
        
        // Copy taxonomies
        $this->copy_taxonomies($ld_course->ID, $new_course_id, 'ld_course_category', 'ielts_course_category');
        
        // Store mapping
        $this->migrated_courses[$ld_course->ID] = $new_course_id;
        
        // Store original ID for reference
        update_post_meta($new_course_id, '_ld_original_id', $ld_course->ID);
        
        $this->log(sprintf('Migrated course: %s (ID: %d → %d)', $ld_course->post_title, $ld_course->ID, $new_course_id));
    }
    
    /**
     * Migrate lessons
     */
    private function migrate_lessons($options) {
        $this->log('Migrating lessons...');
        
        $post_status = $options['include_drafts'] ? array('publish', 'draft') : array('publish');
        
        $lessons = get_posts(array(
            'post_type' => 'sfwd-lessons',
            'posts_per_page' => -1,
            'post_status' => $post_status,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        foreach ($lessons as $lesson) {
            $this->migrate_lesson($lesson, $options);
        }
        
        $this->log(sprintf('Migrated %d lessons', count($this->migrated_lessons)));
    }
    
    /**
     * Migrate a single lesson
     */
    private function migrate_lesson($ld_lesson, $options) {
        // Check for duplicates
        if ($options['skip_duplicates']) {
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'ielts_lesson' AND post_status != 'trash' LIMIT 1",
                $ld_lesson->post_title
            ));
            
            if ($existing) {
                $this->log(sprintf('Skipping duplicate lesson: %s', $ld_lesson->post_title), 'warning');
                return;
            }
        }
        
        // Create new lesson
        $new_lesson_id = wp_insert_post(array(
            'post_title' => $ld_lesson->post_title,
            'post_content' => $ld_lesson->post_content,
            'post_excerpt' => $ld_lesson->post_excerpt,
            'post_status' => $ld_lesson->post_status,
            'post_type' => 'ielts_lesson',
            'post_author' => $ld_lesson->post_author,
            'post_date' => $ld_lesson->post_date,
            'menu_order' => $ld_lesson->menu_order
        ));
        
        if (is_wp_error($new_lesson_id)) {
            $this->log(sprintf('Error migrating lesson %s: %s', $ld_lesson->post_title, $new_lesson_id->get_error_message()), 'error');
            return;
        }
        
        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id($ld_lesson->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_lesson_id, $thumbnail_id);
        }
        
        // Store mapping
        $this->migrated_lessons[$ld_lesson->ID] = $new_lesson_id;
        
        // Store original ID and course association
        update_post_meta($new_lesson_id, '_ld_original_id', $ld_lesson->ID);
        
        // Get associated course from LearnDash
        $course_id = learndash_get_course_id($ld_lesson->ID);
        if ($course_id) {
            update_post_meta($new_lesson_id, '_ld_original_course_id', $course_id);
        }
        
        $this->log(sprintf('Migrated lesson: %s (ID: %d → %d)', $ld_lesson->post_title, $ld_lesson->ID, $new_lesson_id));
    }
    
    /**
     * Migrate topics (lesson pages)
     */
    private function migrate_topics($options) {
        $this->log('Migrating topics (lesson pages)...');
        
        $post_status = $options['include_drafts'] ? array('publish', 'draft') : array('publish');
        
        $topics = get_posts(array(
            'post_type' => 'sfwd-topic',
            'posts_per_page' => -1,
            'post_status' => $post_status,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        foreach ($topics as $topic) {
            $this->migrate_topic($topic, $options);
        }
        
        $this->log(sprintf('Migrated %d topics', count($this->migrated_topics)));
    }
    
    /**
     * Migrate a single topic
     */
    private function migrate_topic($ld_topic, $options) {
        // Check for duplicates
        if ($options['skip_duplicates']) {
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'ielts_resource' AND post_status != 'trash' LIMIT 1",
                $ld_topic->post_title
            ));
            
            if ($existing) {
                $this->log(sprintf('Skipping duplicate topic: %s', $ld_topic->post_title), 'warning');
                return;
            }
        }
        
        // Create new resource (topic becomes lesson page)
        $new_topic_id = wp_insert_post(array(
            'post_title' => $ld_topic->post_title,
            'post_content' => $ld_topic->post_content,
            'post_excerpt' => $ld_topic->post_excerpt,
            'post_status' => $ld_topic->post_status,
            'post_type' => 'ielts_resource',
            'post_author' => $ld_topic->post_author,
            'post_date' => $ld_topic->post_date,
            'menu_order' => $ld_topic->menu_order
        ));
        
        if (is_wp_error($new_topic_id)) {
            $this->log(sprintf('Error migrating topic %s: %s', $ld_topic->post_title, $new_topic_id->get_error_message()), 'error');
            return;
        }
        
        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id($ld_topic->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_topic_id, $thumbnail_id);
        }
        
        // Store mapping
        $this->migrated_topics[$ld_topic->ID] = $new_topic_id;
        
        // Store original ID and lesson association
        update_post_meta($new_topic_id, '_ld_original_id', $ld_topic->ID);
        
        // Get associated lesson from LearnDash
        $lesson_id = learndash_get_lesson_id($ld_topic->ID);
        if ($lesson_id) {
            update_post_meta($new_topic_id, '_ld_original_lesson_id', $lesson_id);
        }
        
        $this->log(sprintf('Migrated topic: %s (ID: %d → %d)', $ld_topic->post_title, $ld_topic->ID, $new_topic_id));
    }
    
    /**
     * Migrate questions from question bank
     */
    private function migrate_questions($options) {
        $this->log('Migrating questions from question bank...');
        
        $post_status = $options['include_drafts'] ? array('publish', 'draft') : array('publish');
        
        $questions = get_posts(array(
            'post_type' => 'sfwd-question',
            'posts_per_page' => -1,
            'post_status' => $post_status,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        foreach ($questions as $question) {
            $this->migrate_question($question);
        }
        
        $this->log(sprintf('Processed %d questions', count($this->migrated_questions)));
    }
    
    /**
     * Migrate a single question (store for later attachment to quiz)
     */
    private function migrate_question($ld_question) {
        $question_data = array(
            'id' => $ld_question->ID,
            'title' => $ld_question->post_title,
            'content' => $ld_question->post_content,
            'meta' => array()
        );
        
        // Get all meta data
        $all_meta = get_post_meta($ld_question->ID);
        foreach ($all_meta as $key => $values) {
            $question_data['meta'][$key] = maybe_unserialize($values[0]);
        }
        
        $this->migrated_questions[$ld_question->ID] = $question_data;
    }
    
    /**
     * Migrate quizzes
     */
    private function migrate_quizzes($options) {
        $this->log('Migrating quizzes...');
        
        $post_status = $options['include_drafts'] ? array('publish', 'draft') : array('publish');
        
        $quizzes = get_posts(array(
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1,
            'post_status' => $post_status,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        foreach ($quizzes as $quiz) {
            $this->migrate_quiz($quiz, $options);
        }
        
        $this->log(sprintf('Migrated %d quizzes', count($this->migrated_quizzes)));
    }
    
    /**
     * Migrate a single quiz
     */
    private function migrate_quiz($ld_quiz, $options) {
        // Check for duplicates
        if ($options['skip_duplicates']) {
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'ielts_quiz' AND post_status != 'trash' LIMIT 1",
                $ld_quiz->post_title
            ));
            
            if ($existing) {
                $this->log(sprintf('Skipping duplicate quiz: %s', $ld_quiz->post_title), 'warning');
                return;
            }
        }
        
        // Create new quiz
        $new_quiz_id = wp_insert_post(array(
            'post_title' => $ld_quiz->post_title,
            'post_content' => $ld_quiz->post_content,
            'post_excerpt' => $ld_quiz->post_excerpt,
            'post_status' => $ld_quiz->post_status,
            'post_type' => 'ielts_quiz',
            'post_author' => $ld_quiz->post_author,
            'post_date' => $ld_quiz->post_date,
            'menu_order' => $ld_quiz->menu_order
        ));
        
        if (is_wp_error($new_quiz_id)) {
            $this->log(sprintf('Error migrating quiz %s: %s', $ld_quiz->post_title, $new_quiz_id->get_error_message()), 'error');
            return;
        }
        
        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id($ld_quiz->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_quiz_id, $thumbnail_id);
        }
        
        // Store mapping
        $this->migrated_quizzes[$ld_quiz->ID] = $new_quiz_id;
        
        // Store original ID
        update_post_meta($new_quiz_id, '_ld_original_id', $ld_quiz->ID);
        
        // Get associated course and lesson
        $course_id = learndash_get_course_id($ld_quiz->ID);
        if ($course_id) {
            update_post_meta($new_quiz_id, '_ld_original_course_id', $course_id);
        }
        
        $lesson_id = learndash_get_lesson_id($ld_quiz->ID);
        if ($lesson_id) {
            update_post_meta($new_quiz_id, '_ld_original_lesson_id', $lesson_id);
        }
        
        // Convert and attach questions to this quiz
        $this->convert_quiz_questions($ld_quiz->ID, $new_quiz_id);
        
        $this->log(sprintf('Migrated quiz: %s (ID: %d → %d)', $ld_quiz->post_title, $ld_quiz->ID, $new_quiz_id));
    }
    
    /**
     * Convert LearnDash questions to IELTS format and attach to quiz
     */
    private function convert_quiz_questions($ld_quiz_id, $new_quiz_id) {
        // Get questions associated with this quiz using LearnDash function
        $question_ids = learndash_get_quiz_questions($ld_quiz_id);
        
        if (empty($question_ids)) {
            return;
        }
        
        $converted_questions = array();
        
        foreach ($question_ids as $question_id) {
            if (!isset($this->migrated_questions[$question_id])) {
                $this->log(sprintf('Question ID %d not found in migrated questions', $question_id), 'warning');
                continue;
            }
            
            $question_data = $this->migrated_questions[$question_id];
            $converted = $this->convert_question_to_ielts_format($question_data);
            
            if ($converted) {
                $converted_questions[] = $converted;
            }
        }
        
        if (!empty($converted_questions)) {
            update_post_meta($new_quiz_id, '_ielts_cm_questions', $converted_questions);
            $this->log(sprintf('Converted and attached %d questions to quiz ID: %d', count($converted_questions), $new_quiz_id));
        }
    }
    
    /**
     * Convert a LearnDash question to IELTS CM format
     * (Reusing logic from XML importer)
     */
    private function convert_question_to_ielts_format($question_data) {
        $meta = $question_data['meta'];
        
        // Determine question type
        $ld_type = isset($meta['question_type']) ? $meta['question_type'] : 'single';
        
        // Map to IELTS question types
        $type_map = array(
            'single' => 'multiple_choice',
            'multiple' => 'multiple_choice',
            'free_answer' => 'fill_blank',
            'essay' => 'essay',
            'fill_in_blank' => 'fill_blank',
            'cloze_answer' => 'fill_blank',
            'sort_answer' => 'essay',
            'matrix_sort_answer' => 'essay',
        );
        
        $ielts_type = isset($type_map[$ld_type]) ? $type_map[$ld_type] : 'multiple_choice';
        
        // Build IELTS question
        $ielts_question = array(
            'type' => $ielts_type,
            'question' => $question_data['title'],
            'points' => isset($meta['question_points']) ? floatval($meta['question_points']) : 1
        );
        
        // Add question content if available
        if (!empty($question_data['content'])) {
            $ielts_question['question'] .= "\n\n" . strip_tags($question_data['content']);
        }
        
        // Handle answers based on type
        if ($ielts_type === 'multiple_choice') {
            $answers = isset($meta['_answer']) ? $meta['_answer'] : array();
            if (!is_array($answers)) {
                $answers = maybe_unserialize($answers);
            }
            
            $options = array();
            $correct_index = 0;
            
            if (is_array($answers)) {
                foreach ($answers as $index => $answer) {
                    if (is_array($answer)) {
                        $answer_text = isset($answer['answer']) ? $answer['answer'] : '';
                        $is_correct = isset($answer['correct']) && $answer['correct'];
                        
                        if ($is_correct) {
                            $correct_index = count($options);
                        }
                        
                        $options[] = strip_tags($answer_text);
                    }
                }
            }
            
            $ielts_question['options'] = implode("\n", $options);
            $ielts_question['correct_answer'] = (string)$correct_index;
            
        } elseif ($ielts_type === 'fill_blank') {
            $correct = isset($meta['_question_answer_correct']) ? $meta['_question_answer_correct'] : '';
            if (empty($correct) && isset($meta['correct_answer'])) {
                $correct = $meta['correct_answer'];
            }
            $ielts_question['correct_answer'] = strip_tags($correct);
            
        } elseif ($ielts_type === 'essay') {
            if ($ld_type === 'matrix_sort_answer' || $ld_type === 'sort_answer') {
                $ielts_question['question'] .= "\n\n[NOTE: This was a " . ($ld_type === 'matrix_sort_answer' ? 'Matrix Sorting' : 'Sorting') . " question in LearnDash. Manual grading required.]";
                
                if (isset($meta['_answer']) && is_array($meta['_answer'])) {
                    $ielts_question['question'] .= "\n\nExpected answer elements:";
                    foreach ($meta['_answer'] as $index => $answer) {
                        if (is_array($answer)) {
                            $answer_text = isset($answer['answer']) ? strip_tags($answer['answer']) : '';
                            $sort_order = isset($answer['sort_pos']) ? $answer['sort_pos'] : ($index + 1);
                            if (!empty($answer_text)) {
                                $ielts_question['question'] .= "\n" . $sort_order . ". " . $answer_text;
                            }
                        }
                    }
                }
                
                $this->log(sprintf('Converted %s question to essay format (requires manual grading)', $ld_type), 'warning');
            }
        }
        
        return $ielts_question;
    }
    
    /**
     * Update relationships between migrated items
     */
    private function update_relationships() {
        $this->log('Updating relationships...');
        
        // Link lessons to courses
        foreach ($this->migrated_lessons as $old_lesson_id => $new_lesson_id) {
            $original_course_id = get_post_meta($new_lesson_id, '_ld_original_course_id', true);
            
            if ($original_course_id && isset($this->migrated_courses[$original_course_id])) {
                $new_course_id = $this->migrated_courses[$original_course_id];
                update_post_meta($new_lesson_id, '_ielts_cm_course_id', $new_course_id);
                update_post_meta($new_lesson_id, '_ielts_cm_course_ids', array($new_course_id));
            }
        }
        
        // Link topics to lessons
        foreach ($this->migrated_topics as $old_topic_id => $new_topic_id) {
            $original_lesson_id = get_post_meta($new_topic_id, '_ld_original_lesson_id', true);
            
            if ($original_lesson_id && isset($this->migrated_lessons[$original_lesson_id])) {
                $new_lesson_id = $this->migrated_lessons[$original_lesson_id];
                update_post_meta($new_topic_id, '_ielts_cm_lesson_id', $new_lesson_id);
                update_post_meta($new_topic_id, '_ielts_cm_lesson_ids', array($new_lesson_id));
            }
        }
        
        // Link quizzes to courses and lessons
        foreach ($this->migrated_quizzes as $old_quiz_id => $new_quiz_id) {
            $original_course_id = get_post_meta($new_quiz_id, '_ld_original_course_id', true);
            if ($original_course_id && isset($this->migrated_courses[$original_course_id])) {
                $new_course_id = $this->migrated_courses[$original_course_id];
                update_post_meta($new_quiz_id, '_ielts_cm_course_id', $new_course_id);
                update_post_meta($new_quiz_id, '_ielts_cm_course_ids', array($new_course_id));
            }
            
            $original_lesson_id = get_post_meta($new_quiz_id, '_ld_original_lesson_id', true);
            if ($original_lesson_id && isset($this->migrated_lessons[$original_lesson_id])) {
                $new_lesson_id = $this->migrated_lessons[$original_lesson_id];
                update_post_meta($new_quiz_id, '_ielts_cm_lesson_id', $new_lesson_id);
                update_post_meta($new_quiz_id, '_ielts_cm_lesson_ids', array($new_lesson_id));
            }
        }
        
        $this->log('Relationships updated successfully');
    }
    
    /**
     * Copy taxonomies from one post to another
     */
    private function copy_taxonomies($old_post_id, $new_post_id, $old_taxonomy, $new_taxonomy) {
        $terms = wp_get_object_terms($old_post_id, $old_taxonomy);
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $term_names = array();
            foreach ($terms as $term) {
                $term_names[] = $term->name;
            }
            
            if (!empty($term_names)) {
                wp_set_object_terms($new_post_id, $term_names, $new_taxonomy);
            }
        }
    }
    
    /**
     * Log a message
     */
    private function log($message, $level = 'info') {
        $this->migration_log[] = array(
            'message' => $message,
            'level' => $level,
            'time' => current_time('mysql')
        );
    }
    
    /**
     * Get migration results
     */
    public function get_results() {
        return array(
            'success' => true,
            'courses' => count($this->migrated_courses),
            'lessons' => count($this->migrated_lessons),
            'topics' => count($this->migrated_topics),
            'quizzes' => count($this->migrated_quizzes),
            'questions' => count($this->migrated_questions),
            'log' => $this->migration_log
        );
    }
}
