<?php
/**
 * LearnDash to IELTS Course Manager Importer
 * 
 * Handles importing LearnDash XML exports into IELTS Course Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_LearnDash_Importer {
    
    /**
     * Imported items tracking
     */
    private $imported_courses = array();
    private $imported_lessons = array();
    private $imported_topics = array();
    private $imported_quizzes = array();
    private $imported_questions = array();
    private $import_log = array();
    
    /**
     * LearnDash to IELTS CM post type mapping
     */
    private $post_type_map = array(
        'sfwd-courses' => 'ielts_course',
        'sfwd-lessons' => 'ielts_lesson',
        'sfwd-topic' => 'ielts_resource',
        'sfwd-quiz' => 'ielts_quiz',
        'sfwd-question' => 'question' // Questions are not posts, but stored in quiz meta
    );
    
    /**
     * Import XML file
     * 
     * @param string $file_path Path to XML file
     * @param array $options Import options
     * @return array Import results
     */
    public function import_xml($file_path, $options = array()) {
        $this->import_log = array();
        $this->log('Starting LearnDash import from: ' . basename($file_path));
        
        // Validate file exists
        if (!file_exists($file_path)) {
            $this->log('Error: File not found', 'error');
            return $this->get_results();
        }
        
        // Load XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file_path);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->log('XML Error: ' . $error->message, 'error');
            }
            libxml_clear_errors();
            return $this->get_results();
        }
        
        // Register namespaces
        $namespaces = $xml->getNamespaces(true);
        
        // Process items
        $items = $xml->channel->item;
        $total_items = count($items);
        $this->log("Found {$total_items} items to process");
        
        // Process in two passes to handle dependencies
        // Pass 1: Import courses, lessons, topics (lesson pages), quizzes
        foreach ($items as $item) {
            $this->process_item($item, $namespaces, $options);
        }
        
        // Pass 2: Update relationships and metadata
        $this->update_relationships();
        
        $this->log('Import completed successfully');
        return $this->get_results();
    }
    
    /**
     * Process a single item from XML
     */
    private function process_item($item, $namespaces, $options) {
        $post_type = (string)$item->children($namespaces['wp'])->post_type;
        
        // Skip if not a LearnDash post type
        if (!isset($this->post_type_map[$post_type])) {
            return;
        }
        
        $old_id = (int)$item->children($namespaces['wp'])->post_id;
        $title = (string)$item->title;
        $content = (string)$item->children($namespaces['content'])->encoded;
        $post_status = (string)$item->children($namespaces['wp'])->status;
        
        // Map to new post type
        $new_post_type = $this->post_type_map[$post_type];
        
        $this->log("Processing {$post_type} (ID: {$old_id}): {$title}");
        
        // Get menu order for proper ordering
        $menu_order = 0;
        if (isset($item->children($namespaces['wp'])->menu_order)) {
            $menu_order = (int)$item->children($namespaces['wp'])->menu_order;
        }
        
        // Create the post
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $post_status === 'publish' ? 'publish' : 'draft',
            'post_type' => $new_post_type,
            'post_date' => (string)$item->children($namespaces['wp'])->post_date,
            'menu_order' => $menu_order
        );
        
        // Check if already exists (by title)
        if (!empty($options['skip_duplicates'])) {
            global $wpdb;
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
                $title,
                $new_post_type
            ));
            if ($existing_id) {
                $this->log("Skipping duplicate: {$title}", 'warning');
                return;
            }
        }
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            $this->log("Error creating post: " . $new_id->get_error_message(), 'error');
            return;
        }
        
        $this->log("Created {$new_post_type} with ID: {$new_id}");
        
        // Store mapping
        switch ($post_type) {
            case 'sfwd-courses':
                $this->imported_courses[$old_id] = $new_id;
                break;
            case 'sfwd-lessons':
                $this->imported_lessons[$old_id] = $new_id;
                break;
            case 'sfwd-topic':
                $this->imported_topics[$old_id] = $new_id;
                break;
            case 'sfwd-quiz':
                $this->imported_quizzes[$old_id] = $new_id;
                break;
            case 'sfwd-question':
                // Store question data for later processing
                $this->imported_questions[$old_id] = array(
                    'title' => $title,
                    'content' => $content,
                    'meta' => array()
                );
                // We'll process questions after all items are imported
                return;
        }
        
        // Process metadata (skip for questions since we return early above)
        if ($post_type !== 'sfwd-question') {
            $this->process_postmeta($item, $namespaces, $old_id, $new_id, $post_type);
        } else {
            // For questions, just store the metadata for later processing
            foreach ($item->children($namespaces['wp'])->postmeta as $meta) {
                $key = (string)$meta->meta_key;
                $value = (string)$meta->meta_value;
                $this->imported_questions[$old_id]['meta'][$key] = maybe_unserialize($value);
            }
        }
        
        // Process taxonomies
        $this->process_taxonomies($item, $new_id);
    }
    
    /**
     * Process post meta data
     */
    private function process_postmeta($item, $namespaces, $old_id, $new_id, $post_type) {
        foreach ($item->children($namespaces['wp'])->postmeta as $meta) {
            $key = (string)$meta->meta_key;
            $value = (string)$meta->meta_value;
            
            // Skip internal WordPress meta
            if (strpos($key, '_edit_') === 0 || strpos($key, '_wp_') === 0) {
                continue;
            }
            
            // Map LearnDash meta keys to IELTS CM meta keys
            $mapped_key = $this->map_meta_key($key, $post_type);
            
            if ($mapped_key) {
                // Unserialize if needed (using WordPress built-in function)
                $value = maybe_unserialize($value);
                update_post_meta($new_id, $mapped_key, $value);
            }
            
            // Store original meta with prefix for reference
            update_post_meta($new_id, '_ld_original_' . $key, $value);
        }
        
        // Store original LearnDash ID for relationship mapping
        update_post_meta($new_id, '_ld_original_id', $old_id);
    }
    
    /**
     * Process taxonomies
     */
    private function process_taxonomies($item, $new_id) {
        foreach ($item->category as $category) {
            $domain = (string)$category['domain'];
            $term_name = (string)$category;
            
            if ($domain === 'ld_course_category' || $domain === 'category') {
                // Map to course category
                wp_set_object_terms($new_id, $term_name, 'ielts_course_category', true);
            }
        }
    }
    
    /**
     * Update relationships between imported items
     */
    private function update_relationships() {
        $this->log('Updating relationships between imported items');
        
        // Link lessons to courses
        foreach ($this->imported_lessons as $old_lesson_id => $new_lesson_id) {
            $original_course_id = get_post_meta($new_lesson_id, '_ld_original_course_id', true);
            
            if ($original_course_id && isset($this->imported_courses[$original_course_id])) {
                $new_course_id = $this->imported_courses[$original_course_id];
                $course_ids = get_post_meta($new_lesson_id, '_ielts_cm_course_ids', true);
                if (!is_array($course_ids)) {
                    $course_ids = array();
                }
                $course_ids[] = $new_course_id;
                update_post_meta($new_lesson_id, '_ielts_cm_course_ids', array_unique($course_ids));
                update_post_meta($new_lesson_id, '_ielts_cm_course_id', $new_course_id);
            }
        }
        
        // Link topics (lesson pages) to lessons
        foreach ($this->imported_topics as $old_topic_id => $new_topic_id) {
            $original_lesson_id = get_post_meta($new_topic_id, '_ld_original_lesson_id', true);
            
            if ($original_lesson_id && isset($this->imported_lessons[$original_lesson_id])) {
                $new_lesson_id = $this->imported_lessons[$original_lesson_id];
                $lesson_ids = get_post_meta($new_topic_id, '_ielts_cm_lesson_ids', true);
                if (!is_array($lesson_ids)) {
                    $lesson_ids = array();
                }
                $lesson_ids[] = $new_lesson_id;
                update_post_meta($new_topic_id, '_ielts_cm_lesson_ids', array_unique($lesson_ids));
                update_post_meta($new_topic_id, '_ielts_cm_lesson_id', $new_lesson_id);
            }
        }
        
        // Link quizzes to courses and lessons
        foreach ($this->imported_quizzes as $old_quiz_id => $new_quiz_id) {
            // Link to course
            $original_course_id = get_post_meta($new_quiz_id, '_ld_original_course_id', true);
            if ($original_course_id && isset($this->imported_courses[$original_course_id])) {
                $new_course_id = $this->imported_courses[$original_course_id];
                $course_ids = get_post_meta($new_quiz_id, '_ielts_cm_course_ids', true);
                if (!is_array($course_ids)) {
                    $course_ids = array();
                }
                $course_ids[] = $new_course_id;
                update_post_meta($new_quiz_id, '_ielts_cm_course_ids', array_unique($course_ids));
                update_post_meta($new_quiz_id, '_ielts_cm_course_id', $new_course_id);
            }
            
            // Link to lesson
            $original_lesson_id = get_post_meta($new_quiz_id, '_ld_original_lesson_id', true);
            if ($original_lesson_id && isset($this->imported_lessons[$original_lesson_id])) {
                $new_lesson_id = $this->imported_lessons[$original_lesson_id];
                $lesson_ids = get_post_meta($new_quiz_id, '_ielts_cm_lesson_ids', true);
                if (!is_array($lesson_ids)) {
                    $lesson_ids = array();
                }
                $lesson_ids[] = $new_lesson_id;
                update_post_meta($new_quiz_id, '_ielts_cm_lesson_ids', array_unique($lesson_ids));
                update_post_meta($new_quiz_id, '_ielts_cm_lesson_id', $new_lesson_id);
            }
        }
        
        // Process and convert quiz questions
        $this->convert_quiz_questions();
    }
    
    /**
     * Convert quiz questions and attach them to quizzes
     */
    private function convert_quiz_questions() {
        if (empty($this->imported_questions)) {
            $this->log('No questions found to convert', 'warning');
            return;
        }
        
        $this->log('Converting ' . count($this->imported_questions) . ' quiz questions');
        
        // Group questions by quiz
        $questions_by_quiz = array();
        $skipped_questions = 0;
        
        foreach ($this->imported_questions as $old_question_id => $question_data) {
            // Get the quiz this question belongs to - try multiple possible meta keys
            $quiz_id = null;
            if (isset($question_data['meta']['quiz_id'])) {
                $quiz_id = $question_data['meta']['quiz_id'];
            } elseif (isset($question_data['meta']['_quiz_id'])) {
                $quiz_id = $question_data['meta']['_quiz_id'];
            } elseif (isset($question_data['meta']['_question_pro_id'])) {
                // For ProQuiz questions, try to find the quiz by querying
                // This is a fallback mechanism
                $this->log("Question '{$question_data['title']}' has ProQuiz ID but no direct quiz_id link", 'warning');
            }
            
            if (!$quiz_id) {
                $this->log("Skipping question '{$question_data['title']}' - no quiz_id found in meta", 'warning');
                $skipped_questions++;
                continue;
            }
            
            if (!isset($this->imported_quizzes[$quiz_id])) {
                $this->log("Skipping question '{$question_data['title']}' - quiz ID {$quiz_id} not found in imported quizzes", 'warning');
                $skipped_questions++;
                continue;
            }
            
            $new_quiz_id = $this->imported_quizzes[$quiz_id];
            
            if (!isset($questions_by_quiz[$new_quiz_id])) {
                $questions_by_quiz[$new_quiz_id] = array();
            }
            
            // Convert question to IELTS CM format
            $converted_question = $this->convert_single_question($question_data);
            if ($converted_question) {
                $questions_by_quiz[$new_quiz_id][] = $converted_question;
            } else {
                $this->log("Failed to convert question: {$question_data['title']}", 'error');
                $skipped_questions++;
            }
        }
        
        if ($skipped_questions > 0) {
            $this->log("Skipped {$skipped_questions} questions due to missing quiz links or conversion errors", 'warning');
        }
        
        // Save questions to quizzes
        foreach ($questions_by_quiz as $quiz_id => $questions) {
            update_post_meta($quiz_id, '_ielts_cm_questions', $questions);
            $this->log("Added " . count($questions) . " questions to quiz ID: {$quiz_id}");
        }
        
        // Log quizzes without questions
        foreach ($this->imported_quizzes as $old_quiz_id => $new_quiz_id) {
            if (!isset($questions_by_quiz[$new_quiz_id])) {
                $quiz_post = get_post($new_quiz_id);
                $this->log("Warning: Quiz '{$quiz_post->post_title}' (ID: {$new_quiz_id}) has no questions", 'warning');
            }
        }
    }
    
    /**
     * Convert a single LearnDash question to IELTS CM format
     */
    private function convert_single_question($question_data) {
        $question_type = isset($question_data['meta']['_question_type']) ? $question_data['meta']['_question_type'] : 'single';
        
        // Get question text
        $question_text = wp_strip_all_tags($question_data['content']);
        if (empty($question_text)) {
            $question_text = $question_data['title'];
        }
        
        // Get points
        $points = isset($question_data['meta']['_question_points']) ? $question_data['meta']['_question_points'] : 1;
        
        $converted = array(
            'question' => $question_text,
            'points' => floatval($points)
        );
        
        // Get feedback messages for correct and incorrect answers
        $correct_feedback = isset($question_data['meta']['_correct_answer_feedback']) ? $question_data['meta']['_correct_answer_feedback'] : '';
        $incorrect_feedback = isset($question_data['meta']['_incorrect_answer_feedback']) ? $question_data['meta']['_incorrect_answer_feedback'] : '';
        
        if (!empty($correct_feedback)) {
            $converted['correct_feedback'] = wp_strip_all_tags($correct_feedback);
        }
        if (!empty($incorrect_feedback)) {
            $converted['incorrect_feedback'] = wp_strip_all_tags($incorrect_feedback);
        }
        
        // Map LearnDash question types to IELTS CM types
        switch ($question_type) {
            case 'single':
            case 'multiple':
                // Multiple choice
                $converted['type'] = 'multiple_choice';
                $answer_data = isset($question_data['meta']['_question_answer_data']) ? $question_data['meta']['_question_answer_data'] : array();
                
                if (is_array($answer_data) && !empty($answer_data)) {
                    $options_array = array();
                    $correct_index = 0;
                    
                    foreach ($answer_data as $index => $answer) {
                        if (isset($answer['answer'])) {
                            $options_array[] = $answer['answer'];
                            if (!empty($answer['correct'])) {
                                $correct_index = count($options_array) - 1;
                            }
                            
                            // Include answer-specific feedback if available
                            if (!empty($answer['feedback'])) {
                                // Store feedback for this specific answer option (future enhancement)
                                // For now, we'll use the general correct/incorrect feedback
                            }
                        }
                    }
                    
                    // Store options as newline-separated string for template compatibility
                    // The single-quiz.php template expects options as a string that gets split by newlines
                    // (see templates/single-quiz.php line 80: $options = array_filter(explode("\n", $question['options']));)
                    $converted['options'] = implode("\n", $options_array);
                    $converted['correct_answer'] = $correct_index;
                } else {
                    $converted['options'] = '';
                    $converted['correct_answer'] = 0;
                }
                break;
                
            case 'free_answer':
            case 'essay':
                // Essay
                $converted['type'] = 'essay';
                break;
                
            case 'fill_blank':
                // Fill in the blank
                $converted['type'] = 'fill_blank';
                $answer_data = isset($question_data['meta']['_question_answer_data']) ? $question_data['meta']['_question_answer_data'] : array();
                
                if (is_array($answer_data) && !empty($answer_data)) {
                    $first_answer = array_shift($answer_data);
                    $converted['correct_answer'] = isset($first_answer['answer']) ? $first_answer['answer'] : '';
                } else {
                    $converted['correct_answer'] = '';
                }
                break;
                
            default:
                // Default to multiple choice for unknown types
                $converted['type'] = 'multiple_choice';
                $converted['options'] = array();
                $converted['correct_answer'] = 0;
                $this->log("Unknown question type '{$question_type}' for question: {$question_data['title']}", 'warning');
                break;
        }
        
        return $converted;
    }
    
    /**
     * Map LearnDash meta keys to IELTS CM meta keys
     */
    private function map_meta_key($key, $post_type) {
        // Common mappings
        $mappings = array(
            // Course mappings
            'course_id' => ($post_type === 'sfwd-quiz' || $post_type === 'sfwd-topic') ? '_ld_original_course_id' : '_ielts_cm_course_id',
            'ld_course_' => '_ld_original_course_id',
            
            // Lesson mappings
            'lesson_id' => ($post_type === 'sfwd-quiz' || $post_type === 'sfwd-topic') ? '_ld_original_lesson_id' : '_ielts_cm_lesson_id',
            'course' => '_ld_original_course_id',
            
            // Quiz mappings
            'quiz_pass_percentage' => '_ielts_cm_pass_percentage',
        );
        
        foreach ($mappings as $old_key => $new_key) {
            if (strpos($key, $old_key) !== false) {
                return $new_key;
            }
        }
        
        return null;
    }
    
    /**
     * Log a message
     */
    private function log($message, $level = 'info') {
        $this->import_log[] = array(
            'message' => $message,
            'level' => $level,
            'time' => current_time('mysql')
        );
    }
    
    /**
     * Get import results
     */
    public function get_results() {
        return array(
            'success' => true,
            'courses' => count($this->imported_courses),
            'lessons' => count($this->imported_lessons),
            'topics' => count($this->imported_topics),
            'quizzes' => count($this->imported_quizzes),
            'questions' => count($this->imported_questions),
            'log' => $this->import_log
        );
    }
    
    /**
     * Get import log
     */
    public function get_log() {
        return $this->import_log;
    }
}
