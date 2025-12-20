<?php
/**
 * Text Exercises Creator
 * 
 * Creates exercise posts from pasted text with a specific format
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Text_Exercises_Creator {
    
    /**
     * Initialize the creator
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_ielts_cm_create_exercises_from_text', array($this, 'handle_create_exercises'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Create Exercises from Text', 'ielts-course-manager'),
            __('Create Exercises from Text', 'ielts-course-manager'),
            'manage_options',
            'ielts-create-exercises-text',
            array($this, 'render_creator_page')
        );
    }
    
    /**
     * Render creator page
     */
    public function render_creator_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ielts-course-manager'));
        }
        
        // Check for creation results
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $results = get_transient('ielts_cm_text_exercises_creation_results_' . get_current_user_id());
            if ($results) {
                $this->display_results($results);
                delete_transient('ielts_cm_text_exercises_creation_results_' . get_current_user_id());
            }
        }
        
        // Check for errors
        if (isset($_GET['error'])) {
            $this->display_error(sanitize_key($_GET['error']));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Create Exercises from Text', 'ielts-course-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('About This Tool:', 'ielts-course-manager'); ?></strong><br>
                    <?php _e('This tool allows you to create exercise pages by pasting specially formatted text. Simply paste your exercise text below and the tool will parse it to create a complete exercise with all questions, options, and feedback.', 'ielts-course-manager'); ?>
                </p>
            </div>
            
            <div class="card" style="max-width: 900px; margin: 20px 0;">
                <h2><?php _e('Paste Exercise Text', 'ielts-course-manager'); ?></h2>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('ielts_cm_create_exercises_text', 'ielts_cm_create_exercises_text_nonce'); ?>
                    <input type="hidden" name="action" value="ielts_cm_create_exercises_from_text">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="exercise_text"><?php _e('Exercise Text', 'ielts-course-manager'); ?> <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <textarea id="exercise_text" name="exercise_text" rows="20" style="width: 100%; font-family: monospace;" placeholder="<?php esc_attr_e('Paste your exercise text here...', 'ielts-course-manager'); ?>" required></textarea>
                                <p class="description">
                                    <?php _e('Paste the complete exercise text following the format shown in the example below.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="post_status"><?php _e('Post Status', 'ielts-course-manager'); ?></label>
                            </th>
                            <td>
                                <select id="post_status" name="post_status">
                                    <option value="draft"><?php _e('Draft (recommended)', 'ielts-course-manager'); ?></option>
                                    <option value="publish"><?php _e('Published', 'ielts-course-manager'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Draft status is recommended so you can review before publishing.', 'ielts-course-manager'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Create Exercise', 'ielts-course-manager'), 'primary'); ?>
                </form>
            </div>
            
            <div class="card" style="max-width: 900px; margin: 20px 0;">
                <h2><?php _e('Text Format Guide', 'ielts-course-manager'); ?></h2>
                <p><?php _e('This tool supports two formats:', 'ielts-course-manager'); ?></p>
                
                <h3><?php _e('Format 1: Short Answer Questions', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Best for IELTS Reading comprehension with fill-in-the-blank style answers', 'ielts-course-manager'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><strong><?php _e('Title/Instructions:', 'ielts-course-manager'); ?></strong> <?php _e('All text before the first numbered question', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Question Format:', 'ielts-course-manager'); ?></strong> <?php _e('Number. Question text {ANSWER}', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Single Answer:', 'ielts-course-manager'); ?></strong> <?php _e('Use {ANSWER} for one correct answer', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Multiple Alternatives:', 'ielts-course-manager'); ?></strong> <?php _e('Use {[ANSWER1][ANSWER2][ANSWER3]} for multiple accepted answers', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Optional Feedback:', 'ielts-course-manager'); ?></strong> <?php _e('Add explanation text on line(s) after the question (before next question)', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('Format 2: True/False Questions', 'ielts-course-manager'); ?></h3>
                <p><?php _e('The original format for true/false exercises:', 'ielts-course-manager'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><strong><?php _e('First Line:', 'ielts-course-manager'); ?></strong> <?php _e('Exercise title/instructions', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Blank Line:', 'ielts-course-manager'); ?></strong> <?php _e('Separates title from questions', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Question Text:', 'ielts-course-manager'); ?></strong> <?php _e('The question or statement to be answered', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Options:', 'ielts-course-manager'); ?></strong> <?php _e('Each option on its own line', 'ielts-course-manager'); ?>
                        <ul style="list-style-type: circle; margin-left: 20px;">
                            <li><?php _e('Line starting with "This is TRUE" or "This is FALSE" - the option text', 'ielts-course-manager'); ?></li>
                            <li><?php _e('Line with "Correct answer" or "Incorrect" - indicates if option is correct', 'ielts-course-manager'); ?></li>
                        </ul>
                    </li>
                    <li><strong><?php _e('Feedback (Optional):', 'ielts-course-manager'); ?></strong> <?php _e('Additional explanation following the options', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Blank Line:', 'ielts-course-manager'); ?></strong> <?php _e('Separates questions', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('Short Answer Example', 'ielts-course-manager'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; white-space: pre-wrap;">Reading Section 2

Questions 15 – 22

Look at the information given in the text about a Graduate Training Programme advertisement.
Answer the questions below using NO MORE THAN THREE WORDS AND/OR A NUMBER from the text for each answer.

15. What subject has the past entrant to the graduate training programme studied at university? {CHEMISTRY}

16. In how many countries does the company have offices? {[25][TWENTY FIVE][TWENTY-FIVE]}

17. Where will the successful applicants for the positions be based? {[IN THE UK][IN THE U.K.][THE UK][THE U.K.][UK][U.K.]}

18. What is the most important part of Rayland Industries' business? {MANUFACTURING}
This can be found in the third paragraph which states "our main focus and the essential part of our business is in manufacturing."

19. After how long are trainees entitled to join the company's medical scheme? {[6 MONTHS][SIX MONTHS]}</pre>
                
                <h3><?php _e('True/False Example', 'ielts-course-manager'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; white-space: pre-wrap;">Decide whether these statements about the reading test are TRUE or FALSE. Select the correct answers.

You have to answer 40 questions
This is TRUE
Correct answer
This is FALSE
Incorrect

There are 40 questions in the reading test.

There are always 5 different parts to the reading test.
This is TRUE
This is FALSE
Correct answer
Incorrect

It's FALSE because although there are commonly 5 parts (2 parts to Section 1, 2 parts in Section 2 and 1 part in Section 3), this is not ALWAYS the case – it is possible to have 6 different sections, with 3 sections in in Section 1.

You have one hour to complete the reading test
This is TRUE
Correct answer
This is FALSE
Incorrect

You have one hour for the complete test (including transferring your answers).</pre>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle create exercises from text
     */
    public function handle_create_exercises() {
        // Verify nonce
        if (!isset($_POST['ielts_cm_create_exercises_text_nonce']) || 
            !wp_verify_nonce($_POST['ielts_cm_create_exercises_text_nonce'], 'ielts_cm_create_exercises_text')) {
            wp_die(__('Security check failed', 'ielts-course-manager'));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'ielts-course-manager'));
        }
        
        // Get options
        $exercise_text = isset($_POST['exercise_text']) ? sanitize_textarea_field($_POST['exercise_text']) : '';
        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'draft';
        
        // Validate post status
        if (!in_array($post_status, array('draft', 'publish'))) {
            $post_status = 'draft';
        }
        
        if (empty($exercise_text)) {
            wp_redirect(add_query_arg(array(
                'page' => 'ielts-create-exercises-text',
                'error' => 'empty_text'
            ), admin_url('edit.php?post_type=ielts_course')));
            exit;
        }
        
        // Parse and create exercise
        $results = $this->create_exercise_from_text($exercise_text, $post_status);
        
        // Store results
        set_transient('ielts_cm_text_exercises_creation_results_' . get_current_user_id(), $results, 300);
        
        // Redirect to results
        wp_redirect(add_query_arg(array(
            'page' => 'ielts-create-exercises-text',
            'created' => '1'
        ), admin_url('edit.php?post_type=ielts_course')));
        exit;
    }
    
    /**
     * Create exercise from text
     */
    private function create_exercise_from_text($text, $post_status) {
        $results = array(
            'success' => false,
            'exercise_id' => null,
            'exercise_title' => '',
            'question_count' => 0,
            'errors' => array()
        );
        
        // Parse the text
        $parsed = $this->parse_exercise_text($text);
        
        if (!$parsed || empty($parsed['title'])) {
            $results['errors'][] = __('Could not parse exercise title from text', 'ielts-course-manager');
            return $results;
        }
        
        if (empty($parsed['questions'])) {
            $results['errors'][] = __('No questions found in the text', 'ielts-course-manager');
            return $results;
        }
        
        // Create the exercise post
        $post_data = array(
            'post_title' => $parsed['title'],
            'post_content' => '',
            'post_status' => $post_status,
            'post_type' => 'ielts_quiz'
        );
        
        $exercise_id = wp_insert_post($post_data);
        
        if (is_wp_error($exercise_id)) {
            $results['errors'][] = sprintf(__('Error creating exercise: %s', 'ielts-course-manager'), $exercise_id->get_error_message());
            return $results;
        }
        
        // Save questions
        update_post_meta($exercise_id, '_ielts_cm_questions', $parsed['questions']);
        update_post_meta($exercise_id, '_ielts_cm_pass_percentage', 70);
        
        $results['success'] = true;
        $results['exercise_id'] = $exercise_id;
        $results['exercise_title'] = $parsed['title'];
        $results['question_count'] = count($parsed['questions']);
        
        return $results;
    }
    
    /**
     * Parse exercise text into structured data
     */
    private function parse_exercise_text($text) {
        // Try to detect format type
        // Short answer format has questions like "15. Question text {ANSWER}"
        if ($this->is_short_answer_format($text)) {
            return $this->parse_short_answer_format($text);
        }
        
        // Fall back to original true/false format parser
        return $this->parse_true_false_format($text);
    }
    
    /**
     * Detect if text is in short answer format
     */
    private function is_short_answer_format($text) {
        // Look for pattern: number. question text {ANSWER}
        return preg_match('/\d+\.\s+.+?\s+\{[^}]+\}/', $text) > 0;
    }
    
    /**
     * Parse short answer format questions
     * Format: "15. Question text? {ANSWER}" or "15. Question text? {[ANS1][ANS2][ANS3]}"
     * Optional feedback can be added on the next line(s) before the next question
     */
    private function parse_short_answer_format($text) {
        // Extract title - everything before the first question number
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        $title = '';
        $question_start_index = -1;
        
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/^\d+\.\s+/', $lines[$i])) {
                // Found first question
                $question_start_index = $i;
                break;
            }
            if (!empty($lines[$i])) {
                if (empty($title)) {
                    $title = $lines[$i];
                } else {
                    $title .= ' ' . $lines[$i];
                }
            }
        }
        
        if (empty($title)) {
            $title = 'Short Answer Questions';
        }
        
        if ($question_start_index === -1) {
            return null;
        }
        
        // Parse questions with potential feedback
        $questions = array();
        $question_lines = array_slice($lines, $question_start_index);
        
        $i = 0;
        while ($i < count($question_lines)) {
            $line = $question_lines[$i];
            
            // Check if this line is a question (starts with number. and has {ANSWER})
            if (preg_match('/^(\d+)\.\s+(.+?)\s+\{([^}]+)\}/', $line, $match)) {
                $question_num = $match[1];
                $question_text = trim($match[2]);
                $answer_part = $match[3];
                
                // Parse answers - handle both simple {ANSWER} and complex {[ANS1][ANS2]}
                $answers = $this->parse_answer_alternatives($answer_part);
                
                // Look for optional feedback on following lines (before next question)
                $feedback = '';
                $j = $i + 1;
                while ($j < count($question_lines)) {
                    $next_line = $question_lines[$j];
                    
                    // Stop if we hit another question or empty line
                    if (empty($next_line) || preg_match('/^\d+\.\s+.+?\s+\{[^}]+\}/', $next_line)) {
                        break;
                    }
                    
                    // Accumulate feedback
                    if (!empty($feedback)) {
                        $feedback .= "\n";
                    }
                    $feedback .= $next_line;
                    $j++;
                }
                
                // Create question
                $questions[] = array(
                    'type' => 'short_answer',
                    'question' => $question_text,
                    'correct_answer' => implode('|', $answers), // Pipe-separated for multiple alternatives
                    'points' => 1,
                    'correct_feedback' => '',
                    'incorrect_feedback' => trim($feedback) // Feedback shows when answer is wrong
                );
                
                // Skip past any feedback lines we consumed
                $i = $j;
            } else {
                $i++;
            }
        }
        
        return array(
            'title' => $title,
            'questions' => $questions
        );
    }
    
    /**
     * Parse answer alternatives from curly braces
     * Handles: {ANSWER} or {[ANS1][ANS2][ANS3]}
     */
    private function parse_answer_alternatives($answer_part) {
        $answers = array();
        
        // Check if it has bracket notation [ANS1][ANS2]
        if (preg_match_all('/\[([^\]]+)\]/', $answer_part, $bracket_matches)) {
            // Multiple alternatives in brackets
            $answers = $bracket_matches[1];
        } else {
            // Single answer, no brackets
            $answers = array(trim($answer_part));
        }
        
        // Clean up answers
        $answers = array_map('trim', $answers);
        $answers = array_filter($answers);
        
        return $answers;
    }
    
    /**
     * Parse true/false format questions (original parser)
     */
    private function parse_true_false_format($text) {
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        // First non-empty line is the title
        $title = '';
        $start_index = 0;
        for ($i = 0; $i < count($lines); $i++) {
            if (!empty($lines[$i])) {
                $title = $lines[$i];
                $start_index = $i + 1;
                break;
            }
        }
        
        if (empty($title)) {
            return null;
        }
        
        // Parse questions - state machine approach
        $questions = array();
        $state = 'WAITING_FOR_QUESTION'; // States: WAITING_FOR_QUESTION, COLLECTING_OPTIONS, COLLECTING_FEEDBACK, MAYBE_FEEDBACK
        $current_question = null;
        $current_options = array();
        $feedback_lines = array();
        
        for ($i = $start_index; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Empty line transitions state
            if (empty($line)) {
                if ($state === 'COLLECTING_OPTIONS') {
                    // Check if next non-empty line is feedback (not a new question)
                    $state = 'MAYBE_FEEDBACK';
                } elseif ($state === 'COLLECTING_FEEDBACK' || $state === 'MAYBE_FEEDBACK') {
                    // Save current question
                    if ($current_question !== null && !empty($current_options)) {
                        if (!empty($feedback_lines)) {
                            $current_question['incorrect_feedback'] = implode("\n", $feedback_lines);
                        }
                        
                        // Find correct option
                        $correct_index = -1;
                        foreach ($current_options as $idx => $opt) {
                            if ($opt['is_correct']) {
                                $correct_index = $idx;
                                break;
                            }
                        }
                        
                        $current_question['correct_answer'] = $correct_index >= 0 ? (string)$correct_index : '0';
                        $current_question['mc_options'] = $current_options;
                        
                        $questions[] = $current_question;
                        
                        // Reset
                        $current_question = null;
                        $current_options = array();
                        $feedback_lines = array();
                    }
                    $state = 'WAITING_FOR_QUESTION';
                }
                continue;
            }
            
            // Check for option lines
            if (preg_match('/^This is (TRUE|FALSE)$/i', $line, $matches)) {
                $option_text = $matches[0];
                $is_correct = false;
                
                // Check next line for "Correct answer" or "Incorrect"
                if ($i + 1 < count($lines)) {
                    $next_line = trim($lines[$i + 1]);
                    if (stripos($next_line, 'Correct answer') !== false) {
                        $is_correct = true;
                        $i++; // Skip the next line
                    } elseif (stripos($next_line, 'Incorrect') !== false) {
                        $is_correct = false;
                        $i++; // Skip the next line
                    }
                }
                
                $current_options[] = array(
                    'text' => $option_text,
                    'is_correct' => $is_correct,
                    'feedback' => ''
                );
                
                $state = 'COLLECTING_OPTIONS';
                continue;
            }
            
            // Skip standalone status lines
            if (preg_match('/^(Correct answer|Incorrect)$/i', $line)) {
                continue;
            }
            
            // Handle based on state
            if ($state === 'WAITING_FOR_QUESTION') {
                // This is a new question
                $current_question = array(
                    'type' => 'true_false',
                    'question' => $line,
                    'points' => 1,
                    'correct_feedback' => '',
                    'incorrect_feedback' => ''
                );
                $state = 'COLLECTING_OPTIONS';
            } elseif ($state === 'MAYBE_FEEDBACK') {
                // Determine if this is feedback or a new question
                // Feedback often starts with "It's", "This is", "The", has punctuation, or is longer
                // A question is typically a statement without explanation markers
                if (preg_match('/^(It\'s|This is because|The |Because |In |Although |However )/i', $line) || 
                    strlen($line) > 100 ||
                    preg_match('/–|—/', $line)) {
                    // This looks like feedback
                    $feedback_lines[] = $line;
                    $state = 'COLLECTING_FEEDBACK';
                } else {
                    // This looks like a new question - save previous question first
                    if ($current_question !== null && !empty($current_options)) {
                        if (!empty($feedback_lines)) {
                            $current_question['incorrect_feedback'] = implode("\n", $feedback_lines);
                        }
                        
                        $correct_index = -1;
                        foreach ($current_options as $idx => $opt) {
                            if ($opt['is_correct']) {
                                $correct_index = $idx;
                                break;
                            }
                        }
                        
                        $current_question['correct_answer'] = $correct_index >= 0 ? (string)$correct_index : '0';
                        $current_question['mc_options'] = $current_options;
                        
                        $questions[] = $current_question;
                        
                        // Reset
                        $current_options = array();
                        $feedback_lines = array();
                    }
                    
                    // Start new question
                    $current_question = array(
                        'type' => 'true_false',
                        'question' => $line,
                        'points' => 1,
                        'correct_feedback' => '',
                        'incorrect_feedback' => ''
                    );
                    $state = 'COLLECTING_OPTIONS';
                }
            } elseif ($state === 'COLLECTING_OPTIONS') {
                // After we have options, any text is feedback
                if (!empty($current_options)) {
                    $feedback_lines[] = $line;
                    $state = 'COLLECTING_FEEDBACK';
                }
            } elseif ($state === 'COLLECTING_FEEDBACK') {
                // Continue collecting feedback
                $feedback_lines[] = $line;
            }
        }
        
        // Save last question if exists
        if ($current_question !== null && !empty($current_options)) {
            if (!empty($feedback_lines)) {
                $current_question['incorrect_feedback'] = implode("\n", $feedback_lines);
            }
            
            $correct_index = -1;
            foreach ($current_options as $idx => $opt) {
                if ($opt['is_correct']) {
                    $correct_index = $idx;
                    break;
                }
            }
            
            $current_question['correct_answer'] = $correct_index >= 0 ? (string)$correct_index : '0';
            $current_question['mc_options'] = $current_options;
            
            $questions[] = $current_question;
        }
        
        return array(
            'title' => $title,
            'questions' => $questions
        );
    }
    
    /**
     * Display results
     */
    private function display_results($results) {
        if ($results['success']) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('Success!', 'ielts-course-manager'); ?></strong>
                    <?php printf(__('Exercise "%s" created successfully with %d question(s).', 'ielts-course-manager'), 
                        esc_html($results['exercise_title']), 
                        intval($results['question_count'])); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $results['exercise_id'] . '&action=edit')); ?>" class="button button-primary">
                        <?php _e('Edit Exercise', 'ielts-course-manager'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=ielts_quiz')); ?>" class="button">
                        <?php _e('View All Exercises', 'ielts-course-manager'); ?>
                    </a>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong><?php _e('Error creating exercise:', 'ielts-course-manager'); ?></strong></p>
                <ul>
                    <?php foreach ($results['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }
    }
    
    /**
     * Display error
     */
    private function display_error($error_code) {
        $messages = array(
            'empty_text' => __('Please enter exercise text before submitting.', 'ielts-course-manager')
        );
        
        $message = isset($messages[$error_code]) ? $messages[$error_code] : __('An unknown error occurred.', 'ielts-course-manager');
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
}
