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
     * Regex pattern for matching short answer questions
     * Format: number. question text with {ANSWER} anywhere in the line
     * The 'm' flag enables multiline mode where ^ matches start of any line, not just start of string
     * Captures the full line including text before and after {ANSWER}
     */
    const SHORT_ANSWER_PATTERN = '/^(\d+)\.\s+(.+)$/m';
    
    /**
     * Regex pattern for reading passage blocks
     * Matches [READING PASSAGE] or [READING TEXT] with optional title and content
     * Using [\s\S] instead of . to properly capture multiline content
     * Title must be on same line as opening marker, otherwise content starts on next line
     */
    const READING_PASSAGE_PATTERN = '/\[(READING PASSAGE|READING TEXT)\]([^\n\r]*?)[\r\n]+([\s\S]*?)\[END (?:READING PASSAGE|READING TEXT)\]/i';
    
    /**
     * Regex pattern for multiple choice / multi select questions
     * Format: number. question text
     *         A) option text
     *         B) option text [CORRECT]
     */
    const MULTIPLE_CHOICE_PATTERN = '/^(\d+)\.\s+([^\n\r]+)/m';
    
    /**
     * Regex pattern for option lines
     * Format: A) option text [CORRECT] [FEEDBACK: explanation]
     */
    const OPTION_PATTERN = '/^([A-Z])\)\s+(.+?)(?:\s*\[CORRECT\])?(?:\s*\[FEEDBACK:\s*(.+?)\])?$/i';
    
    /**
     * Regex pattern for summary completion questions  
     * Format: question text with [ANSWER 1], [ANSWER 2] placeholders
     */
    const SUMMARY_COMPLETION_PATTERN = '/\[ANSWER\s+(\d+)\]/i';
    
    /**
     * Regex pattern for dropdown paragraph questions
     * Format: question text with ___1___, ___2___ or __1__, __2__ placeholders
     */
    const DROPDOWN_PLACEHOLDER_PATTERN = '/(___\d+___|__\d+__)/';
    
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
                <h2><?php _e('Text Format Guide - All Question Types', 'ielts-course-manager'); ?></h2>
                <p><?php _e('This tool supports ALL question types in text format for automated parsing. Choose the format that matches your question type below.', 'ielts-course-manager'); ?></p>
                
                <div class="notice notice-info inline" style="margin: 15px 0; padding: 10px; background: #e7f3ff; border-left: 4px solid #0969da;">
                    <p><strong><?php _e('Supported Question Types in the Plugin:', 'ielts-course-manager'); ?></strong></p>
                    <ul style="margin-left: 20px; column-count: 2; column-gap: 20px;">
                        <li><?php _e('Multiple Choice', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Multi Select', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('True/False/Not Given', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Short Answer Questions', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Sentence Completion', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Uses Short Answer format', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Summary Completion', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Table Completion', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available - uses Summary Completion format', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Labelling Style Questions', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Uses Short Answer format', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Locating Information', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Headings Questions', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Matching/Classifying', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                        <li><?php _e('Dropdown Paragraph', 'ielts-course-manager'); ?> ✅ <em>(<?php _e('Text format available', 'ielts-course-manager'); ?>)</em></li>
                    </ul>
                </div>
                
                <h3><?php _e('Format 1: Short Answer Questions', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Best for IELTS Reading comprehension with fill-in-the-blank style answers. Also works for: Sentence Completion and Labelling questions.', 'ielts-course-manager'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><strong><?php _e('Title/Instructions:', 'ielts-course-manager'); ?></strong> <?php _e('All text before the first numbered question', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Question Format:', 'ielts-course-manager'); ?></strong> <?php _e('Number. Question text {ANSWER}', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Single Answer:', 'ielts-course-manager'); ?></strong> <?php _e('Use {ANSWER} for one correct answer', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Multiple Alternatives:', 'ielts-course-manager'); ?></strong> <?php _e('Use {[ANSWER1][ANSWER2][ANSWER3]} for multiple accepted answers', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Optional Feedback:', 'ielts-course-manager'); ?></strong> <?php _e('Add explanation text on line(s) after the question (before next question)', 'ielts-course-manager'); ?>
                        <ul style="list-style-type: circle; margin-left: 20px; margin-top: 5px;">
                            <li><code>[CORRECT]</code> <?php _e('- Feedback shown when answer is correct', 'ielts-course-manager'); ?></li>
                            <li><code>[INCORRECT]</code> <?php _e('- Feedback shown when answer is incorrect (default if no marker)', 'ielts-course-manager'); ?></li>
                            <li><code>[NO ANSWER]</code> <?php _e('- Feedback shown when no answer is submitted', 'ielts-course-manager'); ?></li>
                        </ul>
                    </li>
                </ul>
                
                <h3><?php _e('Format 2: True/False Questions', 'ielts-course-manager'); ?></h3>
                <p><?php _e('The original format for True/False/Not Given exercises:', 'ielts-course-manager'); ?></p>
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
                
                <h3><?php _e('Adding Reading Passages', 'ielts-course-manager'); ?></h3>
                <p><?php _e('Include reading passages (for computer-based layout) using markers:', 'ielts-course-manager'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><strong><?php _e('Start Marker:', 'ielts-course-manager'); ?></strong> <code>[READING PASSAGE]</code> <?php _e('or', 'ielts-course-manager'); ?> <code>[READING TEXT]</code></li>
                    <li><strong><?php _e('Optional Title:', 'ielts-course-manager'); ?></strong> <?php _e('Add title text on the same line as the start marker', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('Content:', 'ielts-course-manager'); ?></strong> <?php _e('The reading passage text (line breaks are preserved)', 'ielts-course-manager'); ?></li>
                    <li><strong><?php _e('End Marker:', 'ielts-course-manager'); ?></strong> <code>[END READING PASSAGE]</code> <?php _e('or', 'ielts-course-manager'); ?> <code>[END READING TEXT]</code></li>
                    <li><strong><?php _e('Note:', 'ielts-course-manager'); ?></strong> <?php _e('Reading passages can be placed anywhere in your text (before or after questions)', 'ielts-course-manager'); ?></li>
                </ul>
                
                <h3><?php _e('Other Question Types', 'ielts-course-manager'); ?></h3>
                <p><?php _e('All question types are now supported in text format! Use the formats below:', 'ielts-course-manager'); ?></p>
                
                <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0969da;"><?php _e('Multiple Choice & Multi Select', 'ielts-course-manager'); ?></h4>
                    <p><?php _e('Students select one (Multiple Choice) or more (Multi Select) correct answers from a list of options.', 'ielts-course-manager'); ?></p>
                    <p><strong><?php _e('Format:', 'ielts-course-manager'); ?></strong></p>
                    <ul style="margin-left: 20px;">
                        <li><?php _e('Add [MULTIPLE CHOICE] or [MULTI SELECT] marker in the title to specify question type', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Number. Question text?', 'ielts-course-manager'); ?></li>
                        <li><?php _e('A) Option text', 'ielts-course-manager'); ?></li>
                        <li><?php _e('B) Option text [CORRECT]', 'ielts-course-manager'); ?></li>
                        <li><?php _e('C) Option text [FEEDBACK: Explanation]', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Blank line between questions', 'ielts-course-manager'); ?></li>
                    </ul>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; font-size: 12px;">Questions 1-2 [MULTIPLE CHOICE]

1. What is the capital of France?
A) London
B) Paris [CORRECT]
C) Berlin [FEEDBACK: Berlin is the capital of Germany]
D) Madrid

2. Which planet is known as the Red Planet?
A) Venus
B) Mars [CORRECT]
C) Jupiter
D) Saturn</pre>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0969da;"><?php _e('Headings Questions', 'ielts-course-manager'); ?></h4>
                    <p><?php _e('Students match headings to paragraphs or sections. Presented as multiple choice selections.', 'ielts-course-manager'); ?></p>
                    <p><strong><?php _e('Format:', 'ielts-course-manager'); ?></strong> <?php _e('Same as Multiple Choice but add [HEADINGS] marker in title', 'ielts-course-manager'); ?></p>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; font-size: 12px;">Match each heading to the correct paragraph [HEADINGS]

1. Paragraph A
A) The benefits of exercise
B) Diet and nutrition [CORRECT]
C) Mental health strategies
D) Sleep patterns</pre>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0969da;"><?php _e('Matching and Classifying Questions', 'ielts-course-manager'); ?></h4>
                    <p><?php _e('Students classify items into categories or match related information.', 'ielts-course-manager'); ?></p>
                    <p><strong><?php _e('Format:', 'ielts-course-manager'); ?></strong> <?php _e('Same as Multiple Choice but add [MATCHING] or [CLASSIFYING] marker in title', 'ielts-course-manager'); ?></p>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; font-size: 12px;">Classify each animal [MATCHING]

1. Lion
A) Mammal [CORRECT]
B) Reptile
C) Bird
D) Fish</pre>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0969da;"><?php _e('Locating Information Questions', 'ielts-course-manager'); ?></h4>
                    <p><?php _e('Students identify which paragraph contains specific information.', 'ielts-course-manager'); ?></p>
                    <p><strong><?php _e('Format:', 'ielts-course-manager'); ?></strong> <?php _e('Same as Multiple Choice but add [LOCATING INFORMATION] marker in title', 'ielts-course-manager'); ?></p>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; font-size: 12px;">Which paragraph contains the following information? [LOCATING INFORMATION]

1. Information about climate change
A) Paragraph A
B) Paragraph B [CORRECT]
C) Paragraph C
D) Paragraph D</pre>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0969da;"><?php _e('Summary Completion Questions', 'ielts-course-manager'); ?></h4>
                    <p><?php _e('Fill-in-the-blank questions within a paragraph using [ANSWER N] placeholders.', 'ielts-course-manager'); ?></p>
                    <p><strong><?php _e('Format:', 'ielts-course-manager'); ?></strong></p>
                    <ul style="margin-left: 20px;">
                        <li><?php _e('Use [ANSWER 1], [ANSWER 2], etc. in the question text', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Provide correct answers in format: {1:answer1|alt1|2:answer2|alt2}', 'ielts-course-manager'); ?></li>
                    </ul>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; font-size: 12px;">Summary Completion

The study found that [ANSWER 1] was the most important factor and [ANSWER 2] was secondary.

{1:education|learning|2:experience|practice}</pre>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0969da;"><?php _e('Table Completion Questions', 'ielts-course-manager'); ?></h4>
                    <p><?php _e('Fill-in-the-blank questions for table cells using [ANSWER N] placeholders. Works identically to Summary Completion.', 'ielts-course-manager'); ?></p>
                    <p><strong><?php _e('Format:', 'ielts-course-manager'); ?></strong></p>
                    <ul style="margin-left: 20px;">
                        <li><?php _e('Add [TABLE COMPLETION] marker in the title to specify question type', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Use [ANSWER 1], [ANSWER 2], etc. in the question text', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Provide correct answers in format: {1:answer1|alt1|2:answer2|alt2}', 'ielts-course-manager'); ?></li>
                    </ul>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; font-size: 12px;">Table Completion [TABLE COMPLETION]

| Animal | Type | Diet |
|--------|------|------|
| Lion | [ANSWER 1] | [ANSWER 2] |
| Eagle | [ANSWER 3] | [ANSWER 4] |

{1:mammal|2:carnivore|meat|3:bird|4:carnivore|meat}</pre>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0969da;"><?php _e('Dropdown Paragraph Questions', 'ielts-course-manager'); ?></h4>
                    <p><?php _e('Inline dropdown selections within text for testing grammar, vocabulary, or formal language.', 'ielts-course-manager'); ?></p>
                    <p><strong><?php _e('Format:', 'ielts-course-manager'); ?></strong></p>
                    <ul style="margin-left: 20px;">
                        <li><?php _e('Use ___1___, ___2___ (or __1__, __2__) as placeholders in question text', 'ielts-course-manager'); ?></li>
                        <li><?php _e('Define dropdown options using "DROPDOWN N:" followed by lettered options', 'ielts-course-manager'); ?></li>
                    </ul>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; font-size: 12px;">Dropdown Paragraph

I am writing to ___1___ that the meeting has been ___2___ until ___3___.

DROPDOWN 1:
A) inform you [CORRECT]
B) let you know
C) tell you

DROPDOWN 2:
A) postponed [CORRECT]
B) delayed
C) rescheduled

DROPDOWN 3:
A) next week [CORRECT]
B) tomorrow
C) next month</pre>
                </div>
                
                <div class="notice notice-success inline" style="margin: 15px 0; padding: 10px; background: #d4edda; border-left: 4px solid #28a745;">
                    <p><strong><?php _e('All Question Types Supported!', 'ielts-course-manager'); ?></strong></p>
                    <p><?php _e('As of version 3.0, all current question types can be created using text format. Choose the method that works best for you:', 'ielts-course-manager'); ?></p>
                    <ul style="margin-left: 20px;">
                        <li><strong><?php _e('Text Format:', 'ielts-course-manager'); ?></strong> <?php _e('Fast and efficient for bulk creation', 'ielts-course-manager'); ?></li>
                        <li><strong><?php _e('Exercise Editor:', 'ielts-course-manager'); ?></strong> <?php _e('Visual interface with full control', 'ielts-course-manager'); ?></li>
                    </ul>
                </div>
                
                <h3><?php _e('Short Answer Example', 'ielts-course-manager'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; white-space: pre-wrap;">Reading Section 2

Questions 15 – 22

Look at the information given in the text about a Graduate Training Programme advertisement.
Answer the questions below using NO MORE THAN THREE WORDS AND/OR A NUMBER from the text for each answer.

15. What subject has the past entrant to the graduate training programme studied at university? {CHEMISTRY}

16. In how many countries does the company have offices? {[25][TWENTY FIVE][TWENTY-FIVE]}

17. Where will the successful applicants for the positions be based? {[IN THE UK][IN THE U.K.][THE UK][THE U.K.][UK][U.K.]}

18. What is the most important part of Rayland Industries' business? {MANUFACTURING}
[INCORRECT] This can be found in the third paragraph which states "our main focus and the essential part of our business is in manufacturing."
[CORRECT] Well done! You correctly identified the manufacturing focus.
[NO ANSWER] Please provide an answer based on the reading passage.

19. After how long are trainees entitled to join the company's medical scheme? {[6 MONTHS][SIX MONTHS]}</pre>
                
                <h3><?php _e('Example with Reading Passage', 'ielts-course-manager'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; white-space: pre-wrap;">[READING PASSAGE] Passage 1
The Industrial Revolution began in Britain during the late 18th century and transformed society from an agricultural economy to one dominated by industry and machine manufacturing. This transformation led to significant population growth, urbanization, and changes in living conditions.

The textile industry was one of the first to benefit from mechanization. Inventions such as the spinning jenny and power loom revolutionized cloth production, making it faster and cheaper than ever before.
[END READING PASSAGE]

Reading Comprehension Questions

Answer the following questions based on Passage 1.

1. Where did the Industrial Revolution begin? {[BRITAIN][THE UK][UNITED KINGDOM][GREAT BRITAIN]}
[CORRECT] Excellent! You found the correct location.
[INCORRECT] Check the first sentence of the passage.

2. Which industry was mentioned as one of the first to use machines? {[TEXTILE][TEXTILES][THE TEXTILE INDUSTRY]}

3. Name one invention mentioned in the passage. {[SPINNING JENNY][POWER LOOM]}</pre>
                
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
        // Use wp_strip_all_tags to remove HTML while preserving newlines (sanitize_textarea_field removes newlines)
        $exercise_text = isset($_POST['exercise_text']) ? wp_strip_all_tags($_POST['exercise_text']) : '';
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
        
        // Save reading texts if present
        if (!empty($parsed['reading_texts'])) {
            update_post_meta($exercise_id, '_ielts_cm_reading_texts', $parsed['reading_texts']);
            // Set layout to computer_based if reading texts are included
            update_post_meta($exercise_id, '_ielts_cm_layout_type', 'computer_based');
        }
        
        $results['success'] = true;
        $results['exercise_id'] = $exercise_id;
        $results['exercise_title'] = $parsed['title'];
        $results['question_count'] = count($parsed['questions']);
        
        return $results;
    }
    
    /**
     * Find reading text index by title (helper for parsing)
     * 
     * @param string $title Title to search for
     * @param array $reading_texts Array of reading text objects with 'title' field
     * @return int|null Reading text array index if found, null otherwise
     */
    private function find_reading_text_by_title($title, $reading_texts) {
        foreach ($reading_texts as $rt_idx => $rt) {
            $rt_title = !empty($rt['title']) ? $rt['title'] : 'Reading Text ' . ($rt_idx + 1);
            if (strcasecmp($rt_title, $title) === 0) {
                return $rt_idx;
            }
        }
        return null;
    }
    
    /**
     * Format reading text link for export (helper for text export)
     * 
     * @param array $question Question array with potential reading_text_id field
     * @param array $reading_texts Array of reading text objects
     * @return string|null Formatted link string '[LINKED TO: ...]' or null if no link
     */
    private function format_reading_text_link($question, $reading_texts) {
        if (isset($question['reading_text_id']) && $question['reading_text_id'] !== '' && $question['reading_text_id'] !== null) {
            $reading_text_index = intval($question['reading_text_id']);
            if (isset($reading_texts[$reading_text_index])) {
                $linked_text_title = !empty($reading_texts[$reading_text_index]['title']) ? 
                    $reading_texts[$reading_text_index]['title'] : 
                    'Reading Text ' . ($reading_text_index + 1);
                return '[LINKED TO: ' . $linked_text_title . ']';
            }
        }
        return null;
    }
    
    /**
     * Convert true/false option to correct answer value
     * 
     * @param array $options Array of options with 'text' and 'is_correct' fields
     * @return string Correct answer value: 'true', 'false', 'not_given', or fallback
     */
    private function get_correct_answer_from_options($options) {
        $correct_answer_value = '';
        
        foreach ($options as $idx => $opt) {
            if ($opt['is_correct']) {
                // For true_false questions, convert option text to expected value
                // Check for NOT GIVEN first (more specific) before TRUE
                if (stripos($opt['text'], 'NOT GIVEN') !== false) {
                    $correct_answer_value = 'not_given';
                } elseif (stripos($opt['text'], 'TRUE') !== false) {
                    $correct_answer_value = 'true';
                } elseif (stripos($opt['text'], 'FALSE') !== false) {
                    $correct_answer_value = 'false';
                } else {
                    $correct_answer_value = (string)$idx;
                }
                break;
            }
        }
        
        // Ensure we have a valid correct_answer value
        if (empty($correct_answer_value)) {
            $correct_answer_value = 'true'; // Default fallback
        }
        
        return $correct_answer_value;
    }
    
    /**
     * Parse exercise text into structured data
     */
    public function parse_exercise_text($text) {
        // Check if this is a mixed format file (has multiple question types)
        // Mixed format is detected by having multiple different question format patterns
        if ($this->is_mixed_format($text)) {
            return $this->parse_mixed_format($text);
        }
        
        // Try to detect format type based on patterns in the text
        
        // Check for summary completion or table completion format (has [ANSWER N] placeholders)
        if (preg_match(self::SUMMARY_COMPLETION_PATTERN, $text)) {
            // Check if title contains [TABLE COMPLETION] marker
            if (preg_match('/\[TABLE COMPLETION\]/i', $text)) {
                return $this->parse_table_completion_format($text);
            }
            return $this->parse_summary_completion_format($text);
        }
        
        // Check for dropdown paragraph format (has ___N___ or __N__ placeholders)
        if (preg_match(self::DROPDOWN_PLACEHOLDER_PATTERN, $text)) {
            return $this->parse_dropdown_paragraph_format($text);
        }
        
        // Check for short answer format (has {ANSWER} markers)
        if ($this->is_short_answer_format($text)) {
            return $this->parse_short_answer_format($text);
        }
        
        // Check for multiple choice / multi select / headings / matching / locating format
        // (has numbered questions with lettered options like "A) option")
        if ($this->is_multiple_choice_format($text)) {
            return $this->parse_multiple_choice_format($text);
        }
        
        // Fall back to original true/false format parser
        return $this->parse_true_false_format($text);
    }
    
    /**
     * Detect if text contains multiple question format types (mixed format)
     */
    private function is_mixed_format($text) {
        $format_count = 0;
        
        // Count different format types present
        if ($this->is_multiple_choice_format($text)) {
            $format_count++;
        }
        
        if ($this->is_short_answer_format($text)) {
            $format_count++;
        }
        
        // Check for true/false format (has "This is TRUE" or "This is FALSE")
        if (preg_match('/^This is (TRUE|FALSE)/m', $text)) {
            $format_count++;
        }
        
        // Mixed format is when we have 2 or more different format types
        return $format_count >= 2;
    }
    
    /**
     * Parse mixed format text (contains multiple question types)
     * This is common in IELTS tests where one test has headings, true/false, matching, and short answer questions
     */
    private function parse_mixed_format($text) {
        // Extract reading passages first
        $reading_texts = $this->extract_reading_passages($text);
        
        // Remove reading passages from text for question parsing
        $text = $this->remove_reading_passages($text);
        
        // Extract title - everything before the first question section
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        $title = '';
        $questions_start_index = -1;
        
        for ($i = 0; $i < count($lines); $i++) {
            // Look for question section markers like "Questions 1-5" or numbered questions
            if (preg_match('/^Questions\s+\d+/', $lines[$i]) || preg_match('/^\d+\.\s+/', $lines[$i])) {
                $questions_start_index = $i;
                break;
            }
            // Skip "=== QUESTION TYPE: ... ===" header lines
            if (preg_match('/^===.*===$/i', $lines[$i])) {
                continue;
            }
            if (!empty($lines[$i])) {
                if (empty($title)) {
                    $title = $lines[$i];
                } else {
                    $title .= ' ' . trim($lines[$i]);
                }
            }
        }
        
        if (empty($title)) {
            $title = 'Mixed Format Exercise';
        }
        
        if ($questions_start_index === -1) {
            return null;
        }
        
        // Parse questions by sections
        // Split the text into sections based on "Questions X-Y" headers
        $all_questions = array();
        $sections = $this->split_into_question_sections($lines, $questions_start_index);
        
        foreach ($sections as $section) {
            $section_text = implode("\n", $section['lines']);
            $section_questions = $this->parse_question_section($section_text, $section['marker']);
            
            if (!empty($section_questions)) {
                $all_questions = array_merge($all_questions, $section_questions);
            }
        }
        
        return array(
            'title' => $title,
            'questions' => $all_questions,
            'reading_texts' => $reading_texts
        );
    }
    
    /**
     * Split text into question sections based on "Questions X-Y" headers
     */
    private function split_into_question_sections($lines, $start_index) {
        $sections = array();
        $current_section = null;
        
        for ($i = $start_index; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Check if this is a section header like "Questions 1-5 [HEADINGS]"
            if (preg_match('/^Questions\s+\d+/i', $line)) {
                // Save previous section if exists
                if ($current_section !== null) {
                    $sections[] = $current_section;
                }
                
                // Extract marker if present (e.g., [HEADINGS], [MATCHING])
                $marker = '';
                if (preg_match('/\[([^\]]+)\]/', $line, $marker_match)) {
                    $marker = '[' . $marker_match[1] . ']';
                }
                
                // Start new section
                $current_section = array(
                    'header' => $line,
                    'marker' => $marker,
                    'lines' => array()
                );
            } elseif ($current_section !== null) {
                // Add line to current section
                $current_section['lines'][] = $line;
            }
        }
        
        // Save last section
        if ($current_section !== null) {
            $sections[] = $current_section;
        }
        
        return $sections;
    }
    
    /**
     * Parse a single question section based on its format
     */
    private function parse_question_section($text, $marker) {
        // Determine format based on marker and content
        
        // For multiple choice variants (headings, matching, etc.), prepend a title with the marker
        // so the parser can detect the specific subtype
        if (stripos($marker, 'HEADINGS') !== false || 
            stripos($marker, 'MATCHING') !== false || 
            stripos($marker, 'LOCATING') !== false ||
            stripos($marker, 'MULTI SELECT') !== false ||
            stripos($marker, 'MULTIPLE CHOICE') !== false) {
            // Prepend a title line with the marker so parse_multiple_choice_format can detect the type
            $text_with_marker = "Questions " . $marker . "\n\n" . $text;
            $parsed = $this->parse_multiple_choice_format($text_with_marker);
            return isset($parsed['questions']) ? $parsed['questions'] : array();
        }
        
        // Check for short answer format (must have both numbered questions AND {ANSWER} placeholders)
        if ($this->is_short_answer_format($text)) {
            $parsed = $this->parse_short_answer_format($text);
            return isset($parsed['questions']) ? $parsed['questions'] : array();
        }
        
        // Check for true/false format
        if (preg_match('/^This is (TRUE|FALSE)/m', $text)) {
            $parsed = $this->parse_true_false_format($text);
            return isset($parsed['questions']) ? $parsed['questions'] : array();
        }
        
        // Default to multiple choice if it has the pattern
        if ($this->is_multiple_choice_format($text)) {
            $parsed = $this->parse_multiple_choice_format($text);
            return isset($parsed['questions']) ? $parsed['questions'] : array();
        }
        
        return array();
    }
    
    /**
     * Detect if text is in short answer format
     */
    private function is_short_answer_format($text) {
        // Look for pattern: number. text with {ANSWER} placeholder
        return preg_match(self::SHORT_ANSWER_PATTERN, $text) > 0 && preg_match('/\{[^}]+\}/', $text) > 0;
    }
    
    /**
     * Detect if text is in multiple choice format
     */
    private function is_multiple_choice_format($text) {
        // Look for pattern: numbered questions followed by lettered options
        // e.g., "1. Question?\nA) Option"
        // Use non-greedy .+? to avoid performance issues with large inputs
        return preg_match('/^\d+\.\s+.+?\n\s*[A-Z]\)\s+/m', $text) > 0;
    }
    
    /**
     * Parse short answer format questions
     * Format: "15. Question text? {ANSWER}" or "15. Question text? {[ANS1][ANS2][ANS3]}"
     * Optional feedback can be added on the next line(s) before the next question
     * Reading passages can be added with [READING PASSAGE] or [READING TEXT] markers
     */
    private function parse_short_answer_format($text) {
        // Extract reading passages first
        $reading_texts = $this->extract_reading_passages($text);
        
        // Remove reading passages from text for question parsing
        $text = $this->remove_reading_passages($text);
        
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
            // Skip "=== QUESTION TYPE: ... ===" header lines
            if (preg_match('/^===.*===$/i', $lines[$i])) {
                continue;
            }
            if (!empty($lines[$i])) {
                if (empty($title)) {
                    $title = $lines[$i];
                } else {
                    // Avoid excessive whitespace when concatenating
                    $title .= ' ' . trim($lines[$i]);
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
            
            // Check if this line is a question using the pattern constant
            // Pattern now matches: "number. full line text"
            // We need to check if the line contains {ANSWER} pattern
            if (preg_match(self::SHORT_ANSWER_PATTERN, $line, $match) && preg_match('/\{([^}]+)\}/', $line)) {
                $question_num = $match[1];
                $full_line = trim($match[2]);
                
                // Check for linked reading text on previous line
                $reading_text_id = null;
                if ($i > 0 && preg_match('/\[LINKED TO:\s*(.+?)\]/i', $question_lines[$i - 1], $link_match)) {
                    $linked_title = trim($link_match[1]);
                    $reading_text_id = $this->find_reading_text_by_title($linked_title, $reading_texts);
                }
                
                // Extract the answer part from within the curly braces
                if (preg_match('/\{([^}]+)\}/', $full_line, $answer_match)) {
                    $answer_part = $answer_match[1];
                    
                    // The question text is the full line (the answer placeholder will be replaced by a text box in the frontend)
                    $question_text = $full_line;
                    
                    // Parse answers - handle both simple {ANSWER} and complex {[ANS1][ANS2]}
                    $answers = $this->parse_answer_alternatives($answer_part);
                    
                    // Look for optional feedback on following lines (before next question)
                    // Feedback can be prefixed with [CORRECT], [INCORRECT], or [NO ANSWER] to specify type
                    $feedback_data = $this->parse_feedback_lines($question_lines, $i + 1);
                    $j = $feedback_data['next_index'];
                    
                    // Create question
                    $question_data = array(
                        'type' => 'short_answer',
                        'question' => sanitize_text_field($question_text),
                        // Multiple correct answers separated by pipe (|) for flexible matching
                        // The quiz handler checks user input against each alternative (case-insensitive)
                        'correct_answer' => sanitize_text_field(implode('|', $answers)),
                        'points' => 1,
                        'correct_feedback' => sanitize_textarea_field($feedback_data['correct']),
                        'incorrect_feedback' => sanitize_textarea_field($feedback_data['incorrect']),
                        'no_answer_feedback' => sanitize_textarea_field($feedback_data['no_answer'])
                    );
                    
                    // Add reading text link if found
                    if ($reading_text_id !== null) {
                        $question_data['reading_text_id'] = $reading_text_id;
                    }
                    
                    $questions[] = $question_data;
                    
                    // Skip past any feedback lines we consumed
                    $i = $j;
                } else {
                    $i++;
                }
            } else {
                $i++;
            }
        }
        
        return array(
            'title' => $title,
            'questions' => $questions,
            'reading_texts' => $reading_texts
        );
    }
    
    /**
     * Extract reading passages from text
     * Format: [READING PASSAGE] Title (optional)
     *         Content...
     *         [END READING PASSAGE]
     */
    private function extract_reading_passages($text) {
        $reading_texts = array();
        
        if (preg_match_all(self::READING_PASSAGE_PATTERN, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                // Trim title once and check if not empty
                $title_trimmed = trim($match[2]);
                $title = !empty($title_trimmed) ? $title_trimmed : '';
                $content = trim($match[3]);
                
                $reading_texts[] = array(
                    'title' => sanitize_text_field($title),
                    'content' => wp_kses_post(nl2br($content))
                );
            }
        }
        
        return $reading_texts;
    }
    
    /**
     * Remove reading passages from text for question parsing
     */
    private function remove_reading_passages($text) {
        // Remove reading passage blocks to avoid interference with question parsing
        return preg_replace(self::READING_PASSAGE_PATTERN, '', $text);
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
     * Parse feedback lines with type indicators
     * Supports: [CORRECT], [INCORRECT], [NO ANSWER] prefixes
     * Lines without prefix are treated as incorrect feedback (backward compatibility)
     * 
     * @param array $lines All question lines
     * @param int $start_index Index to start parsing from
     * @return array Array with 'correct', 'incorrect', 'no_answer' feedback and 'next_index'
     */
    private function parse_feedback_lines($lines, $start_index) {
        $feedback = array(
            'correct' => '',
            'incorrect' => '',
            'no_answer' => '',
            'next_index' => $start_index
        );
        
        $current_type = 'incorrect'; // Default for backward compatibility
        $current_text = array();
        
        // Map of feedback markers to their internal type names
        $feedback_markers = array(
            'CORRECT' => 'correct',
            'INCORRECT' => 'incorrect',
            'NO ANSWER' => 'no_answer'
        );
        
        $j = $start_index;
        while ($j < count($lines)) {
            $line = $lines[$j];
            
            // Skip [LINKED TO: ...] marker lines
            if (preg_match('/^\[LINKED TO:/i', $line)) {
                $j++;
                continue;
            }
            
            // Stop if we hit another question or empty line
            if (empty($line) || preg_match(self::SHORT_ANSWER_PATTERN, $line)) {
                break;
            }
            
            // Check for feedback type markers using a unified pattern
            $marker_found = false;
            foreach ($feedback_markers as $marker => $type) {
                // Pattern matches [MARKER] or [MARKER]: followed by optional text
                if (preg_match('/^\[' . preg_quote($marker, '/') . '\]:?\s*(.*)/i', $line, $match)) {
                    // Save previous feedback if any
                    if (!empty($current_text)) {
                        $feedback[$current_type] = trim(implode("\n", $current_text));
                        $current_text = array();
                    }
                    $current_type = $type;
                    // If there's text after the marker on same line, include it
                    if (!empty(trim($match[1]))) {
                        $current_text[] = trim($match[1]);
                    }
                    $marker_found = true;
                    break;
                }
            }
            
            // If no marker found, treat as regular feedback line
            if (!$marker_found) {
                $current_text[] = $line;
            }
            
            $j++;
        }
        
        // Save last accumulated feedback
        if (!empty($current_text)) {
            $feedback[$current_type] = trim(implode("\n", $current_text));
        }
        
        $feedback['next_index'] = $j;
        
        return $feedback;
    }
    
    /**
     * Parse true/false format questions (original parser)
     */
    private function parse_true_false_format($text) {
        // Extract reading passages first
        $reading_texts = $this->extract_reading_passages($text);
        
        // Remove reading passages from text for question parsing
        $text = $this->remove_reading_passages($text);
        
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        // First non-empty line is the title
        $title = '';
        $start_index = 0;
        for ($i = 0; $i < count($lines); $i++) {
            // Skip "=== QUESTION TYPE: ... ===" header lines
            if (preg_match('/^===.*===$/i', $lines[$i])) {
                continue;
            }
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
        $current_feedback_type = 'incorrect'; // Track which feedback type we're collecting: 'correct', 'incorrect', 'no_answer'
        
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
                        // Save any remaining feedback lines to the appropriate feedback type
                        if (!empty($feedback_lines)) {
                            $feedback_text = sanitize_textarea_field(implode("\n", $feedback_lines));
                            if ($current_feedback_type === 'correct') {
                                // Append to existing correct feedback if any
                                $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                    ? $current_question['correct_feedback'] . "\n" . $feedback_text 
                                    : $feedback_text;
                            } elseif ($current_feedback_type === 'no_answer') {
                                // Append to existing no answer feedback if any
                                $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                    ? $current_question['no_answer_feedback'] . "\n" . $feedback_text 
                                    : $feedback_text;
                            } else {
                                // Default to incorrect feedback
                                $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                    ? $current_question['incorrect_feedback'] . "\n" . $feedback_text 
                                    : $feedback_text;
                            }
                        }
                        
                        // Find correct option and convert to proper format for true_false questions
                        $correct_answer_value = $this->get_correct_answer_from_options($current_options);
                        
                        $current_question['correct_answer'] = $correct_answer_value;
                        // True/false questions don't need mc_options as they have fixed options in the template
                        // Remove mc_options for true_false questions
                        if ($correct_answer_value === 'true' || $correct_answer_value === 'false' || $correct_answer_value === 'not_given') {
                            // Don't set mc_options for true_false questions
                        } else {
                            $current_question['mc_options'] = $current_options;
                        }
                        
                        $questions[] = $current_question;
                        
                        // Reset
                        $current_question = null;
                        $current_options = array();
                        $feedback_lines = array();
                        $current_feedback_type = 'incorrect';
                    }
                    $state = 'WAITING_FOR_QUESTION';
                }
                continue;
            }
            
            // Skip "=== QUESTION TYPE: ... ===" header lines that appear in question section
            if (preg_match('/^===.*===$/i', $line)) {
                continue;
            }
            
            // Skip "CORRECT ANSWER:" metadata lines (from export format)
            if (preg_match('/^CORRECT ANSWER:/i', $line)) {
                continue;
            }
            
            // Check for option lines (TRUE, FALSE, or NOT GIVEN)
            if (preg_match('/^This is (TRUE|FALSE|NOT GIVEN)$/i', $line, $matches)) {
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
                    'text' => sanitize_text_field($option_text),
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
                // Strip question number prefix if present (e.g., "1. Question text" -> "Question text")
                $question_text = preg_replace('/^\d+\.\s+/', '', $line);
                
                // This is a new question
                $current_question = array(
                    'type' => 'true_false',
                    'question' => sanitize_text_field($question_text),
                    'points' => 1,
                    'correct_feedback' => '',
                    'incorrect_feedback' => '',
                    'no_answer_feedback' => ''
                );
                $state = 'COLLECTING_OPTIONS';
            } elseif ($state === 'MAYBE_FEEDBACK') {
                // Check for feedback markers first
                if (preg_match('/^\[GENERAL CORRECT FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    // Save any previous feedback first
                    if (!empty($feedback_lines)) {
                        $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                        if ($current_feedback_type === 'correct') {
                            $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                ? $current_question['correct_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } elseif ($current_feedback_type === 'no_answer') {
                            $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } else {
                            $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        }
                    }
                    $feedback_lines = array();
                    $current_feedback_type = 'correct';
                    if (!empty($fb_match[1])) {
                        $current_question['correct_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    $state = 'COLLECTING_FEEDBACK';
                    continue;
                } else if (preg_match('/^\[GENERAL INCORRECT FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    // Save any previous feedback first
                    if (!empty($feedback_lines)) {
                        $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                        if ($current_feedback_type === 'correct') {
                            $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                ? $current_question['correct_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } elseif ($current_feedback_type === 'no_answer') {
                            $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } else {
                            $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        }
                    }
                    $feedback_lines = array();
                    $current_feedback_type = 'incorrect';
                    if (!empty($fb_match[1])) {
                        $current_question['incorrect_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    $state = 'COLLECTING_FEEDBACK';
                    continue;
                } else if (preg_match('/^\[NO ANSWER FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    // Save any previous feedback first
                    if (!empty($feedback_lines)) {
                        $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                        if ($current_feedback_type === 'correct') {
                            $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                ? $current_question['correct_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } elseif ($current_feedback_type === 'no_answer') {
                            $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } else {
                            $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        }
                    }
                    $feedback_lines = array();
                    $current_feedback_type = 'no_answer';
                    if (!empty($fb_match[1])) {
                        $current_question['no_answer_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    $state = 'COLLECTING_FEEDBACK';
                    continue;
                }
                
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
                        // Save any remaining feedback
                        if (!empty($feedback_lines)) {
                            $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                            if ($current_feedback_type === 'correct') {
                                $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                    ? $current_question['correct_feedback'] . " " . $feedback_text 
                                    : $feedback_text;
                            } elseif ($current_feedback_type === 'no_answer') {
                                $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                    ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                                    : $feedback_text;
                            } else {
                                $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                    ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                                    : $feedback_text;
                            }
                        }
                        
                        // Find correct option and convert to proper format for true_false questions
                        $correct_answer_value = $this->get_correct_answer_from_options($current_options);
                        
                        $current_question['correct_answer'] = $correct_answer_value;
                        // True/false questions don't need mc_options as they have fixed options in the template
                        if ($correct_answer_value === 'true' || $correct_answer_value === 'false' || $correct_answer_value === 'not_given') {
                            // Don't set mc_options for true_false questions
                        } else {
                            $current_question['mc_options'] = $current_options;
                        }
                        
                        $questions[] = $current_question;
                        
                        // Reset
                        $current_options = array();
                        $feedback_lines = array();
                        $current_feedback_type = 'incorrect';
                    }
                    
                    // Start new question - strip question number prefix if present
                    $question_text = preg_replace('/^\d+\.\s+/', '', $line);
                    $current_question = array(
                        'type' => 'true_false',
                        'question' => sanitize_text_field($question_text),
                        'points' => 1,
                        'correct_feedback' => '',
                        'incorrect_feedback' => '',
                        'no_answer_feedback' => ''
                    );
                    $state = 'COLLECTING_OPTIONS';
                }
            } elseif ($state === 'COLLECTING_OPTIONS') {
                // Check for feedback markers
                if (preg_match('/^\[GENERAL CORRECT FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    $current_feedback_type = 'correct';
                    if (!empty($fb_match[1])) {
                        $current_question['correct_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    $feedback_lines = array();
                    $state = 'COLLECTING_FEEDBACK';
                    continue;
                } else if (preg_match('/^\[GENERAL INCORRECT FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    $current_feedback_type = 'incorrect';
                    if (!empty($fb_match[1])) {
                        $current_question['incorrect_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    $feedback_lines = array();
                    $state = 'COLLECTING_FEEDBACK';
                    continue;
                } else if (preg_match('/^\[NO ANSWER FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    $current_feedback_type = 'no_answer';
                    if (!empty($fb_match[1])) {
                        $current_question['no_answer_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    $feedback_lines = array();
                    $state = 'COLLECTING_FEEDBACK';
                    continue;
                }
                
                // After we have options, any text is feedback
                if (!empty($current_options)) {
                    $feedback_lines[] = $line;
                    $state = 'COLLECTING_FEEDBACK';
                }
            } elseif ($state === 'COLLECTING_FEEDBACK') {
                // Check for feedback markers - these can appear anywhere in feedback section
                if (preg_match('/^\[GENERAL CORRECT FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    // Save any previous feedback first
                    if (!empty($feedback_lines)) {
                        $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                        if ($current_feedback_type === 'correct') {
                            $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                ? $current_question['correct_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } elseif ($current_feedback_type === 'no_answer') {
                            $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } else {
                            $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        }
                    }
                    $feedback_lines = array();
                    $current_feedback_type = 'correct';
                    if (!empty($fb_match[1])) {
                        $current_question['correct_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    continue;
                } else if (preg_match('/^\[GENERAL INCORRECT FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    // Save any previous feedback first
                    if (!empty($feedback_lines)) {
                        $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                        if ($current_feedback_type === 'correct') {
                            $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                ? $current_question['correct_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } elseif ($current_feedback_type === 'no_answer') {
                            $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } else {
                            $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        }
                    }
                    $feedback_lines = array();
                    $current_feedback_type = 'incorrect';
                    if (!empty($fb_match[1])) {
                        $current_question['incorrect_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    continue;
                } else if (preg_match('/^\[NO ANSWER FEEDBACK\]\s*(.*)$/i', $line, $fb_match)) {
                    // Save any previous feedback first
                    if (!empty($feedback_lines)) {
                        $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                        if ($current_feedback_type === 'correct') {
                            $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                                ? $current_question['correct_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } elseif ($current_feedback_type === 'no_answer') {
                            $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                                ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        } else {
                            $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                                ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                                : $feedback_text;
                        }
                    }
                    $feedback_lines = array();
                    $current_feedback_type = 'no_answer';
                    if (!empty($fb_match[1])) {
                        $current_question['no_answer_feedback'] = sanitize_textarea_field($fb_match[1]);
                    }
                    continue;
                }
                
                // Continue collecting feedback - append to appropriate feedback buffer
                $feedback_lines[] = $line;
            }
        }
        
        // Save last question if exists
        if ($current_question !== null && !empty($current_options)) {
            // Save any remaining feedback lines to the appropriate feedback type
            if (!empty($feedback_lines)) {
                $feedback_text = sanitize_textarea_field(implode(" ", $feedback_lines));
                if ($current_feedback_type === 'correct') {
                    $current_question['correct_feedback'] = !empty($current_question['correct_feedback']) 
                        ? $current_question['correct_feedback'] . " " . $feedback_text 
                        : $feedback_text;
                } elseif ($current_feedback_type === 'no_answer') {
                    $current_question['no_answer_feedback'] = !empty($current_question['no_answer_feedback']) 
                        ? $current_question['no_answer_feedback'] . " " . $feedback_text 
                        : $feedback_text;
                } else {
                    $current_question['incorrect_feedback'] = !empty($current_question['incorrect_feedback']) 
                        ? $current_question['incorrect_feedback'] . " " . $feedback_text 
                        : $feedback_text;
                }
            }
            
            // Find correct option and convert to proper format for true_false questions
            $correct_answer_value = $this->get_correct_answer_from_options($current_options);
            
            $current_question['correct_answer'] = $correct_answer_value;
            // True/false questions don't need mc_options as they have fixed options in the template
            if ($correct_answer_value === 'true' || $correct_answer_value === 'false' || $correct_answer_value === 'not_given') {
                // Don't set mc_options for true_false questions
            } else {
                $current_question['mc_options'] = $current_options;
            }
            
            $questions[] = $current_question;
        }
        
        return array(
            'title' => $title,
            'questions' => $questions,
            'reading_texts' => $reading_texts
        );
    }
    
    /**
     * Parse multiple choice / multi select format questions
     * Format:
     * Question Type: [MULTIPLE CHOICE] or [MULTI SELECT] or [HEADINGS] or [MATCHING] or [LOCATING INFORMATION]
     * 
     * 1. Question text?
     * A) First option
     * B) Second option [CORRECT]
     * C) Third option [FEEDBACK: This is wrong because...]
     * D) Fourth option [CORRECT] [FEEDBACK: Great choice!]
     * 
     * Multiple questions separated by blank lines
     */
    private function parse_multiple_choice_format($text) {
        // Extract reading passages first
        $reading_texts = $this->extract_reading_passages($text);
        $text = $this->remove_reading_passages($text);
        
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        // Detect question type from marker
        $question_type = 'multiple_choice'; // default
        $type_markers = array(
            'MULTI SELECT' => 'multi_select',
            'MULTI-SELECT' => 'multi_select',
            'MULTISELECT' => 'multi_select',
            'HEADINGS' => 'headings',
            'MATCHING' => 'matching_classifying',
            'CLASSIFYING' => 'matching_classifying',
            'LOCATING INFORMATION' => 'locating_information',
            'LOCATING' => 'locating_information'
        );
        
        // Extract title and detect question type
        $title = '';
        $start_index = 0;
        for ($i = 0; $i < count($lines); $i++) {
            if (empty($lines[$i])) {
                continue;
            }
            
            // Skip "=== QUESTION TYPE: ... ===" header lines
            if (preg_match('/^===.*===$/i', $lines[$i])) {
                continue;
            }
            
            // Check for type marker
            foreach ($type_markers as $marker => $type) {
                if (preg_match('/\[' . preg_quote($marker, '/') . '\]/i', $lines[$i])) {
                    $question_type = $type;
                    // Remove marker from title
                    $lines[$i] = trim(preg_replace('/\[' . preg_quote($marker, '/') . '\]/i', '', $lines[$i]));
                    break;
                }
            }
            
            if (!empty($lines[$i]) && !preg_match('/^\d+\./', $lines[$i])) {
                if (empty($title)) {
                    $title = $lines[$i];
                } else {
                    $title .= ' ' . $lines[$i];
                }
            } else if (preg_match('/^\d+\./', $lines[$i])) {
                $start_index = $i;
                break;
            }
        }
        
        if (empty($title)) {
            $title = 'Multiple Choice Questions';
        }
        
        // Parse questions
        $questions = array();
        $current_question = null;
        $current_options = array();
        
        for ($i = $start_index; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Skip [LINKED TO: ...] marker lines
            if (preg_match('/^\[LINKED TO:/i', $line)) {
                continue;
            }
            
            if (empty($line)) {
                // Blank line - save current question if exists
                if ($current_question !== null && !empty($current_options)) {
                    $current_question['mc_options'] = $current_options;
                    
                    // Determine correct answer(s)
                    $correct_indices = array();
                    foreach ($current_options as $idx => $opt) {
                        if ($opt['is_correct']) {
                            $correct_indices[] = $idx;
                        }
                    }
                    
                    if ($question_type === 'multi_select') {
                        $current_question['correct_answer'] = implode(',', $correct_indices);
                    } else {
                        $current_question['correct_answer'] = !empty($correct_indices) ? (string)$correct_indices[0] : '0';
                    }
                    
                    $questions[] = $current_question;
                    $current_question = null;
                    $current_options = array();
                }
                continue;
            }
            
            // Check if this is a new question
            if (preg_match('/^(\d+)\.\s+(.+)/', $line, $match)) {
                // Save previous question if exists
                if ($current_question !== null && !empty($current_options)) {
                    $current_question['mc_options'] = $current_options;
                    
                    $correct_indices = array();
                    foreach ($current_options as $idx => $opt) {
                        if ($opt['is_correct']) {
                            $correct_indices[] = $idx;
                        }
                    }
                    
                    if ($question_type === 'multi_select') {
                        $current_question['correct_answer'] = implode(',', $correct_indices);
                    } else {
                        $current_question['correct_answer'] = !empty($correct_indices) ? (string)$correct_indices[0] : '0';
                    }
                    
                    $questions[] = $current_question;
                    $current_options = array();
                }
                
                // Check for linked reading text on previous line
                $reading_text_id = null;
                if ($i > 0 && preg_match('/\[LINKED TO:\s*(.+?)\]/i', $lines[$i - 1], $link_match)) {
                    $linked_title = trim($link_match[1]);
                    $reading_text_id = $this->find_reading_text_by_title($linked_title, $reading_texts);
                }
                
                // Start new question
                $current_question = array(
                    'type' => $question_type,
                    'question' => sanitize_text_field($match[2]),
                    'points' => 1,
                    'correct_feedback' => '',
                    'incorrect_feedback' => '',
                    'no_answer_feedback' => ''
                );
                
                // Add reading text link if found
                if ($reading_text_id !== null) {
                    $current_question['reading_text_id'] = $reading_text_id;
                }
            }
            // Check if this is an option line
            else if (preg_match('/^([A-Z])\)\s+(.+)$/i', $line, $match)) {
                $option_letter = strtoupper($match[1]);
                $option_text = $match[2];
                
                // Check for [CORRECT] marker
                $is_correct = false;
                if (preg_match('/\[CORRECT\]/i', $option_text)) {
                    $is_correct = true;
                    $option_text = trim(preg_replace('/\[CORRECT\]/i', '', $option_text));
                }
                
                // Check for [FEEDBACK: ...] marker
                $feedback = '';
                if (preg_match('/\[FEEDBACK:\s*(.+?)\]/i', $option_text, $fb_match)) {
                    $feedback = $fb_match[1];
                    $option_text = trim(preg_replace('/\[FEEDBACK:\s*.+?\]/i', '', $option_text));
                }
                
                $current_options[] = array(
                    'text' => sanitize_text_field($option_text),
                    'is_correct' => $is_correct,
                    'feedback' => sanitize_text_field($feedback)
                );
            }
            // Check for general feedback markers
            else if ($current_question !== null) {
                if (preg_match('/^\[GENERAL CORRECT FEEDBACK\]\s*(.+)$/i', $line, $fb_match)) {
                    $current_question['correct_feedback'] = sanitize_textarea_field($fb_match[1]);
                } else if (preg_match('/^\[GENERAL INCORRECT FEEDBACK\]\s*(.+)$/i', $line, $fb_match)) {
                    $current_question['incorrect_feedback'] = sanitize_textarea_field($fb_match[1]);
                } else if (preg_match('/^\[NO ANSWER FEEDBACK\]\s*(.+)$/i', $line, $fb_match)) {
                    $current_question['no_answer_feedback'] = sanitize_textarea_field($fb_match[1]);
                }
            }
        }
        
        // Save last question if exists
        if ($current_question !== null && !empty($current_options)) {
            $current_question['mc_options'] = $current_options;
            
            $correct_indices = array();
            foreach ($current_options as $idx => $opt) {
                if ($opt['is_correct']) {
                    $correct_indices[] = $idx;
                }
            }
            
            if ($question_type === 'multi_select') {
                $current_question['correct_answer'] = implode(',', $correct_indices);
            } else {
                $current_question['correct_answer'] = !empty($correct_indices) ? (string)$correct_indices[0] : '0';
            }
            
            $questions[] = $current_question;
        }
        
        return array(
            'title' => $title,
            'questions' => $questions,
            'reading_texts' => $reading_texts
        );
    }
    
    /**
     * Parse summary completion format questions
     * Format:
     * Title/Instructions
     * 
     * Question text with [ANSWER 1] and [ANSWER 2] placeholders.
     * More text with [ANSWER 3] here.
     * 
     * {1:correct1|alt1|2:correct2|alt2|3:correct3}
     */
    private function parse_summary_completion_format($text) {
        return $this->parse_summary_or_table_completion_format($text, 'summary_completion');
    }
    
    /**
     * Parse table completion format questions
     * Format: Same as summary completion but with [TABLE COMPLETION] marker
     */
    private function parse_table_completion_format($text) {
        return $this->parse_summary_or_table_completion_format($text, 'table_completion');
    }
    
    /**
     * Parse summary or table completion format questions
     * Both use the same format with [ANSWER N] placeholders
     */
    private function parse_summary_or_table_completion_format($text, $question_type) {
        // Extract reading passages first
        $reading_texts = $this->extract_reading_passages($text);
        $text = $this->remove_reading_passages($text);
        
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        // Extract title - first non-empty line, remove type marker if present
        $title = '';
        $content_start = 0;
        for ($i = 0; $i < count($lines); $i++) {
            // Skip "=== QUESTION TYPE: ... ===" header lines
            if (preg_match('/^===.*===$/i', $lines[$i])) {
                continue;
            }
            if (!empty($lines[$i])) {
                $title = $lines[$i];
                // Remove [TABLE COMPLETION] or [SUMMARY COMPLETION] marker if present
                $title = preg_replace('/\[(TABLE COMPLETION|SUMMARY COMPLETION)\]/i', '', $title);
                $title = trim($title);
                $content_start = $i + 1;
                break;
            }
        }
        
        if (empty($title)) {
            $title = ($question_type === 'table_completion') ? 'Table Completion' : 'Summary Completion';
        }
        
        // Find the question text (lines with [ANSWER N]) and answer key
        $question_lines = array();
        $answer_key = '';
        
        for ($i = $content_start; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Check if this is the answer key line (starts with { and contains :)
            if (preg_match('/^\{(.+)\}$/', $line, $match)) {
                $answer_key = $match[1];
                continue;
            }
            
            // Otherwise it's part of the question text
            $question_lines[] = $line;
        }
        
        $question_text = implode("\n", $question_lines);
        
        // Parse answer key to extract summary_fields
        // Format: {1:answer1|alt1|2:answer2|alt2|3:answer3}
        $summary_fields = array();
        if (!empty($answer_key)) {
            // Split by number: pattern
            $parts = preg_split('/(\d+):/', $answer_key, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            // Process pairs of (number, answers)
            for ($i = 1; $i < count($parts); $i += 2) {
                if (isset($parts[$i]) && isset($parts[$i + 1])) {
                    $field_num = $parts[$i];
                    $answers_str = trim($parts[$i + 1], '| ');
                    
                    // Split answers by pipe
                    $answers = explode('|', $answers_str);
                    $answers = array_map('trim', $answers);
                    $answers = array_filter($answers);
                    
                    $summary_fields[$field_num] = array(
                        'answer' => implode('|', $answers),
                        'correct_feedback' => '',
                        'incorrect_feedback' => '',
                        'no_answer_feedback' => __("In the IELTS test, you should always take a guess. You don't lose points for a wrong answer.", 'ielts-course-manager')
                    );
                }
            }
        }
        
        // Convert [ANSWER N] to [field N] format for consistency with admin UI
        $question_text = preg_replace('/\[ANSWER\s+(\d+)\]/i', '[field $1]', $question_text);
        
        // Create the question
        $questions = array();
        if (!empty($question_text) && preg_match('/\[field\s+\d+\]/i', $question_text)) {
            $questions[] = array(
                'type' => $question_type,
                'question' => sanitize_textarea_field($question_text),
                'summary_fields' => $summary_fields,
                'points' => 1,
                'correct_feedback' => '',
                'incorrect_feedback' => '',
                'no_answer_feedback' => ''
            );
        }
        
        return array(
            'title' => $title,
            'questions' => $questions,
            'reading_texts' => $reading_texts
        );
    }
    
    /**
     * Parse dropdown paragraph format questions
     * Format:
     * Title/Instructions
     * 
     * Question text with ___1___ and ___2___ placeholders.
     * 
     * DROPDOWN 1:
     * A) option1 [CORRECT]
     * B) option2
     * 
     * DROPDOWN 2:
     * A) option3
     * B) option4 [CORRECT]
     */
    private function parse_dropdown_paragraph_format($text) {
        // Extract reading passages first
        $reading_texts = $this->extract_reading_passages($text);
        $text = $this->remove_reading_passages($text);
        
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        // Extract title
        $title = '';
        $content_start = 0;
        for ($i = 0; $i < count($lines); $i++) {
            // Skip "=== QUESTION TYPE: ... ===" header lines
            if (preg_match('/^===.*===$/i', $lines[$i])) {
                continue;
            }
            if (!empty($lines[$i]) && !preg_match(self::DROPDOWN_PLACEHOLDER_PATTERN, $lines[$i])) {
                $title = $lines[$i];
                $content_start = $i + 1;
                break;
            } else if (preg_match(self::DROPDOWN_PLACEHOLDER_PATTERN, $lines[$i])) {
                $content_start = $i;
                break;
            }
        }
        
        if (empty($title)) {
            $title = 'Dropdown Paragraph';
        }
        
        // Find the question text (lines with ___N___ or __N__)
        $question_lines = array();
        $dropdown_start = -1;
        
        for ($i = $content_start; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            if (empty($line)) {
                continue;
            }
            
            // Check if we've reached the dropdown definitions
            if (preg_match('/^DROPDOWN\s+(\d+):/i', $line)) {
                $dropdown_start = $i;
                break;
            }
            
            // Part of question text
            $question_lines[] = $line;
        }
        
        $question_text = implode(' ', $question_lines);
        
        // Parse dropdown options
        $dropdown_options = array();
        $current_dropdown_num = null;
        $current_options = array();
        
        if ($dropdown_start >= 0) {
            for ($i = $dropdown_start; $i < count($lines); $i++) {
                $line = $lines[$i];
                
                if (empty($line)) {
                    // Save current dropdown if exists
                    if ($current_dropdown_num !== null && !empty($current_options)) {
                        $dropdown_options[$current_dropdown_num] = $current_options;
                        $current_dropdown_num = null;
                        $current_options = array();
                    }
                    continue;
                }
                
                // Check for dropdown header
                if (preg_match('/^DROPDOWN\s+(\d+):/i', $line, $match)) {
                    // Save previous dropdown if exists
                    if ($current_dropdown_num !== null && !empty($current_options)) {
                        $dropdown_options[$current_dropdown_num] = $current_options;
                        $current_options = array();
                    }
                    $current_dropdown_num = $match[1];
                }
                // Check for option line
                else if (preg_match('/^([A-Z])\)\s+(.+)$/i', $line, $match)) {
                    $option_text = $match[2];
                    $is_correct = false;
                    
                    if (preg_match('/\[CORRECT\]/i', $option_text)) {
                        $is_correct = true;
                        $option_text = trim(preg_replace('/\[CORRECT\]/i', '', $option_text));
                    }
                    
                    $current_options[] = array(
                        'text' => sanitize_text_field($option_text),
                        'letter' => strtoupper($match[1]),
                        'is_correct' => $is_correct
                    );
                }
            }
            
            // Save last dropdown
            if ($current_dropdown_num !== null && !empty($current_options)) {
                $dropdown_options[$current_dropdown_num] = $current_options;
            }
        }
        
        // Build the formatted question text and correct answer
        if (!empty($dropdown_options)) {
            // Convert ___N___ or __N__ to N.[A: option1 B: option2] format
            $formatted_question = $question_text;
            $correct_answer_parts = array();
            
            foreach ($dropdown_options as $num => $options) {
                $option_parts = array();
                $correct_letter = '';
                
                foreach ($options as $opt) {
                    $option_parts[] = $opt['letter'] . ': ' . $opt['text'];
                    if ($opt['is_correct']) {
                        $correct_letter = $opt['letter'];
                    }
                }
                
                $dropdown_format = $num . '.[' . implode(' ', $option_parts) . ']';
                
                // Replace ___N___ or __N__ with the formatted dropdown
                // Use preg_quote to prevent regex injection
                $formatted_question = preg_replace('/(___' . preg_quote($num, '/') . '___|__' . preg_quote($num, '/') . '__)/', $dropdown_format, $formatted_question);
                
                if (!empty($correct_letter)) {
                    $correct_answer_parts[] = $num . ':' . $correct_letter;
                }
            }
            
            $questions = array();
            $questions[] = array(
                'type' => 'dropdown_paragraph',
                'question' => sanitize_textarea_field($formatted_question),
                'correct_answer' => sanitize_text_field(implode('|', $correct_answer_parts)),
                'dropdown_options' => $dropdown_options,
                'points' => 1,
                'correct_feedback' => '',
                'incorrect_feedback' => '',
                'no_answer_feedback' => ''
            );
            
            return array(
                'title' => $title,
                'questions' => $questions,
                'reading_texts' => $reading_texts
            );
        }
        
        return null;
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
    
    /**
     * Convert questions array to text format (reverse conversion)
     * 
     * @param array $questions Array of questions from quiz meta
     * @param string $title Exercise title
     * @param array $reading_texts Optional reading texts
     * @return string Text format representation
     */
    public function convert_to_text_format($questions, $title = '', $reading_texts = array()) {
        if (empty($questions)) {
            return '';
        }
        
        // Check if this is a mixed format exercise (multiple question types)
        $question_types = array();
        foreach ($questions as $q) {
            $type = isset($q['type']) ? $q['type'] : 'short_answer';
            $question_types[$type] = true;
        }
        
        // If mixed format, use special mixed format converter
        if (count($question_types) > 1) {
            return $this->convert_mixed_format_to_text($questions, $title, $reading_texts);
        }
        
        $output = array();
        
        // Add title if provided
        if (!empty($title)) {
            $output[] = $title;
            $output[] = '';
        }
        
        // Add reading texts if any
        if (!empty($reading_texts)) {
            foreach ($reading_texts as $text) {
                $text_title = isset($text['title']) ? $text['title'] : '';
                $text_content = isset($text['content']) ? $text['content'] : '';
                
                if (!empty($text_content)) {
                    if (!empty($text_title)) {
                        $output[] = '[READING PASSAGE] ' . $text_title;
                    } else {
                        $output[] = '[READING PASSAGE]';
                    }
                    $output[] = $text_content;
                    $output[] = '[END READING PASSAGE]';
                    $output[] = '';
                }
            }
        }
        
        // Detect question type and convert accordingly
        $question_type = isset($questions[0]['type']) ? $questions[0]['type'] : '';
        
        if ($question_type === 'summary_completion' || $question_type === 'table_completion') {
            return $this->convert_summary_completion_to_text($questions, $title, $reading_texts);
        } elseif ($question_type === 'dropdown_paragraph') {
            return $this->convert_dropdown_paragraph_to_text($questions, $title, $reading_texts);
        } elseif ($question_type === 'multiple_choice' || $question_type === 'multi_select' || 
                  $question_type === 'headings' || $question_type === 'matching_classifying' || 
                  $question_type === 'matching' || $question_type === 'locating_information') {
            return $this->convert_multiple_choice_to_text($questions, $title, $reading_texts);
        } elseif ($question_type === 'true_false') {
            return $this->convert_true_false_to_text($questions, $title, $reading_texts);
        } else {
            // Default: short answer format
            return $this->convert_short_answer_to_text($questions, $title, $reading_texts);
        }
    }
    
    /**
     * Convert mixed format questions (multiple question types) to text format
     * Used when an exercise contains heterogeneous question types
     * 
     * @param array $questions Array of questions with mixed types
     * @param string $title Exercise title
     * @param array $reading_texts Array of reading text objects
     * @return string Text format representation with section headers
     */
    private function convert_mixed_format_to_text($questions, $title, $reading_texts) {
        $output = array();
        
        // Add title
        if (!empty($title)) {
            $output[] = $title;
        } else {
            $output[] = 'Mixed Format Exercise';
        }
        $output[] = '';
        
        // Add reading texts
        if (!empty($reading_texts)) {
            foreach ($reading_texts as $text) {
                $text_title = isset($text['title']) ? $text['title'] : '';
                $text_content = isset($text['content']) ? $text['content'] : '';
                
                if (!empty($text_content)) {
                    if (!empty($text_title)) {
                        $output[] = '[READING PASSAGE] ' . $text_title;
                    } else {
                        $output[] = '[READING PASSAGE]';
                    }
                    $output[] = strip_tags($text_content);
                    $output[] = '[END READING PASSAGE]';
                    $output[] = '';
                }
            }
        }
        
        // Group questions by type
        $grouped_questions = array();
        foreach ($questions as $question) {
            $type = isset($question['type']) ? $question['type'] : 'short_answer';
            if (!isset($grouped_questions[$type])) {
                $grouped_questions[$type] = array();
            }
            $grouped_questions[$type][] = $question;
        }
        
        // Convert each group
        $question_num = 1;
        foreach ($grouped_questions as $type => $type_questions) {
            // Calculate question range for this section
            $start_num = $question_num;
            $question_count = count($type_questions);
            
            // For multi-point questions, calculate actual count
            foreach ($type_questions as $q) {
                if ($type === 'multi_select' && isset($q['mc_options'])) {
                    $correct_count = 0;
                    foreach ($q['mc_options'] as $opt) {
                        if (!empty($opt['is_correct'])) {
                            $correct_count++;
                        }
                    }
                    $question_count += ($correct_count - 1);
                } elseif (($type === 'summary_completion' || $type === 'table_completion') && isset($q['summary_fields'])) {
                    $question_count += (count($q['summary_fields']) - 1);
                } elseif ($type === 'dropdown_paragraph') {
                    preg_match_all('/\d+\.\[([^\]]+)\]/i', $q['question'], $dropdown_matches);
                    $dropdown_count = !empty($dropdown_matches[0]) ? count($dropdown_matches[0]) : 1;
                    $question_count += ($dropdown_count - 1);
                }
            }
            
            $end_num = $start_num + $question_count - 1;
            
            // Add section header
            $type_labels = array(
                'multiple_choice' => 'MULTIPLE CHOICE',
                'multi_select' => 'MULTI SELECT',
                'headings' => 'HEADINGS',
                'matching_classifying' => 'MATCHING',
                'matching' => 'MATCHING',
                'locating_information' => 'LOCATING INFORMATION',
                'true_false' => 'TRUE/FALSE',
                'short_answer' => 'SHORT ANSWER',
                'sentence_completion' => 'SENTENCE COMPLETION',
                'summary_completion' => 'SUMMARY COMPLETION',
                'table_completion' => 'TABLE COMPLETION',
                'dropdown_paragraph' => 'DROPDOWN PARAGRAPH',
                'labelling' => 'LABELLING'
            );
            
            $type_label = isset($type_labels[$type]) ? $type_labels[$type] : strtoupper(str_replace('_', ' ', $type));
            
            if ($start_num === $end_num) {
                $output[] = 'Question ' . $start_num . ' [' . $type_label . ']';
            } else {
                $output[] = 'Questions ' . $start_num . '-' . $end_num . ' [' . $type_label . ']';
            }
            $output[] = '';
            
            // Convert questions of this type
            if ($type === 'summary_completion' || $type === 'table_completion') {
                $section_text = $this->convert_summary_completion_section($type_questions, $reading_texts, $question_num);
            } elseif ($type === 'dropdown_paragraph') {
                $section_text = $this->convert_dropdown_paragraph_section($type_questions, $reading_texts, $question_num);
            } elseif ($type === 'multiple_choice' || $type === 'multi_select' || 
                      $type === 'headings' || $type === 'matching_classifying' || 
                      $type === 'matching' || $type === 'locating_information') {
                $section_text = $this->convert_multiple_choice_section($type_questions, $reading_texts, $question_num);
            } elseif ($type === 'true_false') {
                $section_text = $this->convert_true_false_section($type_questions, $reading_texts, $question_num);
            } else {
                // Default: short answer format
                $section_text = $this->convert_short_answer_section($type_questions, $reading_texts, $question_num);
            }
            
            $output[] = $section_text;
            $output[] = '';
            
            $question_num = $end_num + 1;
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert a section of short answer questions (for mixed format)
     * 
     * @param array $questions Array of short answer questions
     * @param array $reading_texts Array of reading text objects
     * @param int $start_num Starting question number for this section
     * @return string Text format representation of this section
     */
    private function convert_short_answer_section($questions, $reading_texts, $start_num) {
        $output = array();
        
        foreach ($questions as $index => $question) {
            $question_num = $start_num + $index;
            $question_text = isset($question['question']) ? $question['question'] : '';
            $correct_answer = isset($question['correct_answer']) ? $question['correct_answer'] : '';
            
            // Add linked reading text if present
            if (isset($question['reading_text_id']) && $question['reading_text_id'] !== '' && $question['reading_text_id'] !== null) {
                $reading_text_index = intval($question['reading_text_id']);
                if (isset($reading_texts[$reading_text_index])) {
                    $linked_text_title = !empty($reading_texts[$reading_text_index]['title']) ? 
                        $reading_texts[$reading_text_index]['title'] : 
                        'Reading Text ' . ($reading_text_index + 1);
                    $output[] = '[LINKED TO: ' . $linked_text_title . ']';
                }
            }
            
            // Format: number. question text {ANSWER}
            if (strpos($correct_answer, '|') !== false) {
                // Multiple alternatives
                $answers = explode('|', $correct_answer);
                $answer_str = '{[' . implode('][', $answers) . ']}';
                $output[] = $question_num . '. ' . $question_text . ' ' . $answer_str;
            } else {
                $output[] = $question_num . '. ' . $question_text . ' {' . $correct_answer . '}';
            }
            
            // Add feedback if present
            if (!empty($question['correct_feedback'])) {
                $output[] = '[CORRECT] ' . strip_tags($question['correct_feedback']);
            }
            if (!empty($question['incorrect_feedback'])) {
                $output[] = '[INCORRECT] ' . strip_tags($question['incorrect_feedback']);
            }
            if (!empty($question['no_answer_feedback'])) {
                $output[] = '[NO ANSWER] ' . strip_tags($question['no_answer_feedback']);
            }
            
            $output[] = '';
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert a section of multiple choice questions (for mixed format)
     * 
     * @param array $questions Array of multiple choice/headings/matching questions
     * @param array $reading_texts Array of reading text objects
     * @param int $start_num Starting question number for this section
     * @return string Text format representation of this section
     */
    private function convert_multiple_choice_section($questions, $reading_texts, $start_num) {
        $output = array();
        
        foreach ($questions as $index => $question) {
            $question_num = $start_num + $index;
            $question_text = isset($question['question']) ? $question['question'] : '';
            $options = isset($question['mc_options']) ? $question['mc_options'] : array();
            
            // Add linked reading text if present
            if (isset($question['reading_text_id']) && $question['reading_text_id'] !== '' && $question['reading_text_id'] !== null) {
                $reading_text_index = intval($question['reading_text_id']);
                if (isset($reading_texts[$reading_text_index])) {
                    $linked_text_title = !empty($reading_texts[$reading_text_index]['title']) ? 
                        $reading_texts[$reading_text_index]['title'] : 
                        'Reading Text ' . ($reading_text_index + 1);
                    $output[] = '[LINKED TO: ' . $linked_text_title . ']';
                }
            }
            
            $output[] = $question_num . '. ' . $question_text;
            
            // Add options with correct answer markers
            if (is_array($options)) {
                $letters = range('A', 'Z');
                foreach ($options as $opt_index => $option) {
                    $option_text = is_array($option) ? (isset($option['text']) ? $option['text'] : '') : $option;
                    $is_correct = false;
                    $feedback = '';
                    
                    if (is_array($option)) {
                        $is_correct = isset($option['is_correct']) && $option['is_correct'];
                        $feedback = isset($option['feedback']) ? strip_tags($option['feedback']) : '';
                    }
                    
                    $line = $letters[$opt_index] . ') ' . $option_text;
                    if ($is_correct) {
                        $line .= ' [CORRECT]';
                    }
                    if (!empty($feedback)) {
                        $line .= ' [FEEDBACK: ' . $feedback . ']';
                    }
                    $output[] = $line;
                }
            }
            
            // Add general question feedback if present
            if (!empty($question['correct_feedback'])) {
                $output[] = '';
                $output[] = '[GENERAL CORRECT FEEDBACK] ' . strip_tags($question['correct_feedback']);
            }
            if (!empty($question['incorrect_feedback'])) {
                $output[] = '[GENERAL INCORRECT FEEDBACK] ' . strip_tags($question['incorrect_feedback']);
            }
            if (!empty($question['no_answer_feedback'])) {
                $output[] = '[NO ANSWER FEEDBACK] ' . strip_tags($question['no_answer_feedback']);
            }
            
            $output[] = '';
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert a section of true/false questions (for mixed format)
     * 
     * @param array $questions Array of true/false questions
     * @param array $reading_texts Array of reading text objects
     * @param int $start_num Starting question number for this section
     * @return string Text format representation of this section
     */
    private function convert_true_false_section($questions, $reading_texts, $start_num) {
        $output = array();
        
        foreach ($questions as $index => $question) {
            $question_num = $start_num + $index;
            $question_text = isset($question['question']) ? $question['question'] : '';
            $correct_answer = isset($question['correct_answer']) ? $question['correct_answer'] : '';
            
            // Add linked reading text if present
            if (isset($question['reading_text_id']) && $question['reading_text_id'] !== '' && $question['reading_text_id'] !== null) {
                $reading_text_index = intval($question['reading_text_id']);
                if (isset($reading_texts[$reading_text_index])) {
                    $linked_text_title = !empty($reading_texts[$reading_text_index]['title']) ? 
                        $reading_texts[$reading_text_index]['title'] : 
                        'Reading Text ' . ($reading_text_index + 1);
                    $output[] = '[LINKED TO: ' . $linked_text_title . ']';
                }
            }
            
            $output[] = $question_num . '. ' . $question_text;
            $output[] = '';
            $output[] = 'CORRECT ANSWER: ' . strtoupper(str_replace('_', ' ', $correct_answer));
            
            // Add options based on correct answer (for reference)
            $options = array('true', 'false', 'not_given');
            foreach ($options as $option) {
                $option_display = ucfirst(str_replace('_', ' ', $option));
                if ($option === $correct_answer) {
                    $output[] = 'This is ' . strtoupper($option_display);
                    $output[] = 'Correct answer';
                } else {
                    $output[] = 'This is ' . strtoupper($option_display);
                    $output[] = 'Incorrect';
                }
            }
            $output[] = '';
            
            // Add feedback if present
            if (!empty($question['correct_feedback'])) {
                $output[] = '[GENERAL CORRECT FEEDBACK] ' . strip_tags($question['correct_feedback']);
            }
            if (!empty($question['incorrect_feedback'])) {
                $output[] = '[GENERAL INCORRECT FEEDBACK] ' . strip_tags($question['incorrect_feedback']);
            }
            if (!empty($question['no_answer_feedback'])) {
                $output[] = '[NO ANSWER FEEDBACK] ' . strip_tags($question['no_answer_feedback']);
            }
            $output[] = '';
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert a section of summary/table completion questions (for mixed format)
     * 
     * @param array $questions Array of summary/table completion questions
     * @param array $reading_texts Array of reading text objects
     * @param int $start_num Starting question number for this section
     * @return string Text format representation of this section
     */
    private function convert_summary_completion_section($questions, $reading_texts, $start_num) {
        $output = array();
        
        foreach ($questions as $index => $question) {
            $question_text = isset($question['question']) ? $question['question'] : '';
            
            // Add linked reading text if present
            if (isset($question['reading_text_id']) && $question['reading_text_id'] !== '' && $question['reading_text_id'] !== null) {
                $reading_text_index = intval($question['reading_text_id']);
                if (isset($reading_texts[$reading_text_index])) {
                    $linked_text_title = !empty($reading_texts[$reading_text_index]['title']) ? 
                        $reading_texts[$reading_text_index]['title'] : 
                        'Reading Text ' . ($reading_text_index + 1);
                    $output[] = '[LINKED TO: ' . $linked_text_title . ']';
                }
            }
            
            // Convert [field N] back to [ANSWER N]
            $question_text = preg_replace('/\[field\s+(\d+)\]/i', '[ANSWER $1]', $question_text);
            $output[] = $question_text;
            $output[] = '';
            
            // Build answer key
            if (isset($question['summary_fields']) && is_array($question['summary_fields'])) {
                $answer_parts = array();
                foreach ($question['summary_fields'] as $field_num => $field_data) {
                    $answer = isset($field_data['answer']) ? $field_data['answer'] : '';
                    if (!empty($answer)) {
                        $answer_parts[] = $field_num . ':' . $answer;
                    }
                }
                if (!empty($answer_parts)) {
                    $output[] = '{' . implode('|', $answer_parts) . '}';
                    $output[] = '';
                }
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert a section of dropdown paragraph questions (for mixed format)
     * 
     * @param array $questions Array of dropdown paragraph questions
     * @param array $reading_texts Array of reading text objects
     * @param int $start_num Starting question number for this section
     * @return string Text format representation of this section
     */
    private function convert_dropdown_paragraph_section($questions, $reading_texts, $start_num) {
        $output = array();
        
        foreach ($questions as $index => $question) {
            $question_text = isset($question['question']) ? $question['question'] : '';
            $dropdown_options = isset($question['dropdown_options']) ? $question['dropdown_options'] : array();
            
            // Add linked reading text if present
            if (isset($question['reading_text_id']) && $question['reading_text_id'] !== '' && $question['reading_text_id'] !== null) {
                $reading_text_index = intval($question['reading_text_id']);
                if (isset($reading_texts[$reading_text_index])) {
                    $linked_text_title = !empty($reading_texts[$reading_text_index]['title']) ? 
                        $reading_texts[$reading_text_index]['title'] : 
                        'Reading Text ' . ($reading_text_index + 1);
                    $output[] = '[LINKED TO: ' . $linked_text_title . ']';
                }
            }
            
            // Convert formatted question text back to simple ___N___ placeholders
            $simple_text = preg_replace('/(\d+)\.\[([^\]]+)\]/', '___$1___', $question_text);
            $output[] = $simple_text;
            $output[] = '';
            
            // Add dropdown definitions
            if (is_array($dropdown_options)) {
                ksort($dropdown_options);
                foreach ($dropdown_options as $dropdown_num => $dropdown_data) {
                    $output[] = 'DROPDOWN ' . $dropdown_num . ':';
                    
                    if (isset($dropdown_data) && is_array($dropdown_data)) {
                        $options = isset($dropdown_data['options']) ? $dropdown_data['options'] : $dropdown_data;
                        $letters = range('A', 'Z');
                        
                        foreach ($options as $opt_index => $option) {
                            $option_text = is_array($option) ? (isset($option['text']) ? $option['text'] : '') : $option;
                            $is_correct = false;
                            
                            if (is_array($option)) {
                                $is_correct = isset($option['is_correct']) && $option['is_correct'];
                            }
                            
                            $line = $letters[$opt_index] . ') ' . $option_text;
                            if ($is_correct) {
                                $line .= ' [CORRECT]';
                            }
                            $output[] = $line;
                        }
                    }
                    
                    $output[] = '';
                }
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert summary completion or table completion questions to text format
     */
    private function convert_summary_completion_to_text($questions, $title, $reading_texts) {
        $output = array();
        
        // Get question type
        $question_type = isset($questions[0]['type']) ? $questions[0]['type'] : 'summary_completion';
        $type_marker = ($question_type === 'table_completion') ? ' [TABLE COMPLETION]' : '';
        $type_label = ($question_type === 'table_completion') ? 'Table Completion' : 'Summary Completion';
        
        // Add title with type information
        if (!empty($title)) {
            $output[] = $title . $type_marker;
        } else {
            $output[] = $type_label . $type_marker;
        }
        $output[] = '';
        
        // Add type header for clarity
        $output[] = '=== QUESTION TYPE: ' . strtoupper($type_label) . ' ===';
        $output[] = '';
        
        // Add reading texts
        if (!empty($reading_texts)) {
            foreach ($reading_texts as $text) {
                $text_title = isset($text['title']) ? $text['title'] : '';
                $text_content = isset($text['content']) ? $text['content'] : '';
                
                if (!empty($text_content)) {
                    if (!empty($text_title)) {
                        $output[] = '[READING PASSAGE] ' . $text_title;
                    } else {
                        $output[] = '[READING PASSAGE]';
                    }
                    $output[] = strip_tags($text_content);
                    $output[] = '[END READING PASSAGE]';
                    $output[] = '';
                }
            }
        }
        
        // Get the question (usually just one for summary/table completion)
        $question = $questions[0];
        $question_text = isset($question['question']) ? $question['question'] : '';
        
        // Convert [field N] back to [ANSWER N] for text format
        $question_text = preg_replace('/\[field\s+(\d+)\]/i', '[ANSWER $1]', $question_text);
        $output[] = $question_text;
        $output[] = '';
        
        // Build answer key from summary_fields and add feedback
        if (isset($question['summary_fields']) && is_array($question['summary_fields'])) {
            $answer_parts = array();
            foreach ($question['summary_fields'] as $field_num => $field_data) {
                $answer = isset($field_data['answer']) ? $field_data['answer'] : '';
                if (!empty($answer)) {
                    $answer_parts[] = $field_num . ':' . $answer;
                }
            }
            if (!empty($answer_parts)) {
                $output[] = '{' . implode('|', $answer_parts) . '}';
                $output[] = '';
            }
            
            // Add field-specific feedback
            $output[] = '=== CORRECT ANSWERS & FEEDBACK ===';
            foreach ($question['summary_fields'] as $field_num => $field_data) {
                $answer = isset($field_data['answer']) ? $field_data['answer'] : '';
                $output[] = 'Field ' . $field_num . ': ' . $answer;
                
                if (!empty($field_data['correct_feedback'])) {
                    $output[] = '  [CORRECT] ' . strip_tags($field_data['correct_feedback']);
                }
                if (!empty($field_data['incorrect_feedback'])) {
                    $output[] = '  [INCORRECT] ' . strip_tags($field_data['incorrect_feedback']);
                }
                if (!empty($field_data['no_answer_feedback'])) {
                    $output[] = '  [NO ANSWER] ' . strip_tags($field_data['no_answer_feedback']);
                }
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert short answer questions to text format
     */
    private function convert_short_answer_to_text($questions, $title, $reading_texts) {
        $output = array();
        
        // Detect question type
        $question_type = isset($questions[0]['type']) ? $questions[0]['type'] : 'short_answer';
        $type_labels = array(
            'short_answer' => 'Short Answer',
            'sentence_completion' => 'Sentence Completion',
            'labelling' => 'Labelling'
        );
        $type_label = isset($type_labels[$question_type]) ? $type_labels[$question_type] : 'Short Answer';
        
        // Add title
        if (!empty($title)) {
            $output[] = $title;
        } else {
            $output[] = 'Questions ' . (count($questions) > 1 ? '1-' . count($questions) : '1');
        }
        $output[] = '';
        
        // Add type header for clarity
        $output[] = '=== QUESTION TYPE: ' . strtoupper($type_label) . ' ===';
        $output[] = '';
        
        // Add reading texts
        if (!empty($reading_texts)) {
            foreach ($reading_texts as $text) {
                $text_title = isset($text['title']) ? $text['title'] : '';
                $text_content = isset($text['content']) ? $text['content'] : '';
                
                if (!empty($text_content)) {
                    if (!empty($text_title)) {
                        $output[] = '[READING PASSAGE] ' . $text_title;
                    } else {
                        $output[] = '[READING PASSAGE]';
                    }
                    $output[] = strip_tags($text_content);
                    $output[] = '[END READING PASSAGE]';
                    $output[] = '';
                }
            }
        }
        
        // Add questions
        foreach ($questions as $index => $question) {
            $question_num = $index + 1;
            $question_text = isset($question['question']) ? $question['question'] : '';
            $correct_answer = isset($question['correct_answer']) ? $question['correct_answer'] : '';
            
            // Add linked reading text if present
            if (isset($question['reading_text_id']) && $question['reading_text_id'] !== '' && $question['reading_text_id'] !== null) {
                $reading_text_index = intval($question['reading_text_id']);
                if (isset($reading_texts[$reading_text_index])) {
                    $linked_text_title = !empty($reading_texts[$reading_text_index]['title']) ? 
                        $reading_texts[$reading_text_index]['title'] : 
                        'Reading Text ' . ($reading_text_index + 1);
                    $output[] = '[LINKED TO: ' . $linked_text_title . ']';
                }
            }
            
            // Format: number. question text {ANSWER}
            if (strpos($correct_answer, '|') !== false) {
                // Multiple alternatives
                $answers = explode('|', $correct_answer);
                $answer_str = '{[' . implode('][', $answers) . ']}';
                $output[] = $question_num . '. ' . $question_text . ' ' . $answer_str;
            } else {
                $output[] = $question_num . '. ' . $question_text . ' {' . $correct_answer . '}';
            }
            
            // Add feedback if present
            if (!empty($question['correct_feedback'])) {
                $output[] = '[CORRECT] ' . strip_tags($question['correct_feedback']);
            }
            if (!empty($question['incorrect_feedback'])) {
                $output[] = '[INCORRECT] ' . strip_tags($question['incorrect_feedback']);
            }
            if (!empty($question['no_answer_feedback'])) {
                $output[] = '[NO ANSWER] ' . strip_tags($question['no_answer_feedback']);
            }
            
            $output[] = '';
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert multiple choice questions to text format
     */
    private function convert_multiple_choice_to_text($questions, $title, $reading_texts) {
        $output = array();
        
        // Determine type marker and label
        $type_marker = '';
        $type_label = 'Multiple Choice';
        if (!empty($questions)) {
            $question_type = isset($questions[0]['type']) ? $questions[0]['type'] : '';
            switch ($question_type) {
                case 'multi_select':
                    $type_marker = '[MULTI SELECT]';
                    $type_label = 'Multi Select';
                    break;
                case 'headings':
                    $type_marker = '[HEADINGS]';
                    $type_label = 'Headings';
                    break;
                case 'matching_classifying':
                case 'matching':
                    $type_marker = '[MATCHING]';
                    $type_label = 'Matching/Classifying';
                    break;
                case 'locating_information':
                    $type_marker = '[LOCATING INFORMATION]';
                    $type_label = 'Locating Information';
                    break;
                default:
                    $type_marker = '[MULTIPLE CHOICE]';
            }
        }
        
        // Add title
        if (!empty($title)) {
            $output[] = $title . ' ' . $type_marker;
        } else {
            $output[] = 'Questions ' . (count($questions) > 1 ? '1-' . count($questions) : '1') . ' ' . $type_marker;
        }
        $output[] = '';
        
        // Add type header for clarity
        $output[] = '=== QUESTION TYPE: ' . strtoupper($type_label) . ' ===';
        $output[] = '';
        
        // Add reading texts
        if (!empty($reading_texts)) {
            foreach ($reading_texts as $text) {
                $text_title = isset($text['title']) ? $text['title'] : '';
                $text_content = isset($text['content']) ? $text['content'] : '';
                
                if (!empty($text_content)) {
                    if (!empty($text_title)) {
                        $output[] = '[READING PASSAGE] ' . $text_title;
                    } else {
                        $output[] = '[READING PASSAGE]';
                    }
                    $output[] = strip_tags($text_content);
                    $output[] = '[END READING PASSAGE]';
                    $output[] = '';
                }
            }
        }
        
        // Add questions
        foreach ($questions as $index => $question) {
            $question_num = $index + 1;
            $question_text = isset($question['question']) ? $question['question'] : '';
            $options = isset($question['mc_options']) ? $question['mc_options'] : array();
            
            // Add linked reading text if present
            $link_text = $this->format_reading_text_link($question, $reading_texts);
            if ($link_text !== null) {
                $output[] = $link_text;
            }
            
            $output[] = $question_num . '. ' . $question_text;
            
            // Add options with correct answer markers and feedback
            if (is_array($options)) {
                $letters = range('A', 'Z');
                foreach ($options as $opt_index => $option) {
                    $option_text = is_array($option) ? (isset($option['text']) ? $option['text'] : '') : $option;
                    $is_correct = false;
                    $feedback = '';
                    
                    if (is_array($option)) {
                        $is_correct = isset($option['is_correct']) && $option['is_correct'];
                        $feedback = isset($option['feedback']) ? strip_tags($option['feedback']) : '';
                    }
                    
                    $line = $letters[$opt_index] . ') ' . $option_text;
                    if ($is_correct) {
                        $line .= ' [CORRECT]';
                    }
                    if (!empty($feedback)) {
                        $line .= ' [FEEDBACK: ' . $feedback . ']';
                    }
                    $output[] = $line;
                }
            }
            
            // Add general question feedback if present
            if (!empty($question['correct_feedback'])) {
                $output[] = '';
                $output[] = '[GENERAL CORRECT FEEDBACK] ' . strip_tags($question['correct_feedback']);
            }
            if (!empty($question['incorrect_feedback'])) {
                $output[] = '[GENERAL INCORRECT FEEDBACK] ' . strip_tags($question['incorrect_feedback']);
            }
            if (!empty($question['no_answer_feedback'])) {
                $output[] = '[NO ANSWER FEEDBACK] ' . strip_tags($question['no_answer_feedback']);
            }
            
            $output[] = '';
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert true/false questions to text format
     */
    private function convert_true_false_to_text($questions, $title, $reading_texts) {
        $output = array();
        
        // Add title
        if (!empty($title)) {
            $output[] = $title;
        } else {
            $output[] = 'True/False Questions';
        }
        $output[] = '';
        
        // Add type header for clarity
        $output[] = '=== QUESTION TYPE: TRUE/FALSE/NOT GIVEN ===';
        $output[] = '';
        
        // Add reading texts
        if (!empty($reading_texts)) {
            foreach ($reading_texts as $text) {
                $text_title = isset($text['title']) ? $text['title'] : '';
                $text_content = isset($text['content']) ? $text['content'] : '';
                
                if (!empty($text_content)) {
                    if (!empty($text_title)) {
                        $output[] = '[READING PASSAGE] ' . $text_title;
                    } else {
                        $output[] = '[READING PASSAGE]';
                    }
                    $output[] = strip_tags($text_content);
                    $output[] = '[END READING PASSAGE]';
                    $output[] = '';
                }
            }
        }
        
        // Add questions
        foreach ($questions as $index => $question) {
            $question_num = $index + 1;
            $question_text = isset($question['question']) ? $question['question'] : '';
            $correct_answer = isset($question['correct_answer']) ? $question['correct_answer'] : '';
            
            $output[] = $question_num . '. ' . $question_text;
            $output[] = '';
            
            // Show which answer is correct
            $output[] = 'CORRECT ANSWER: ' . strtoupper(str_replace('_', ' ', $correct_answer));
            
            // Add options based on correct answer (for reference)
            $options = array('true', 'false', 'not_given');
            foreach ($options as $option) {
                $option_display = ucfirst(str_replace('_', ' ', $option));
                if ($option === $correct_answer) {
                    $output[] = 'This is ' . strtoupper($option_display);
                    $output[] = 'Correct answer';
                } else {
                    $output[] = 'This is ' . strtoupper($option_display);
                    $output[] = 'Incorrect';
                }
            }
            $output[] = '';
            
            // Add feedback if present
            if (!empty($question['correct_feedback'])) {
                $output[] = '[GENERAL CORRECT FEEDBACK] ' . strip_tags($question['correct_feedback']);
            }
            if (!empty($question['incorrect_feedback'])) {
                $output[] = '[GENERAL INCORRECT FEEDBACK] ' . strip_tags($question['incorrect_feedback']);
            }
            if (!empty($question['no_answer_feedback'])) {
                $output[] = '[NO ANSWER FEEDBACK] ' . strip_tags($question['no_answer_feedback']);
            }
            $output[] = '';
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Convert dropdown paragraph questions to text format
     */
    private function convert_dropdown_paragraph_to_text($questions, $title, $reading_texts) {
        $output = array();
        
        // Add title
        if (!empty($title)) {
            $output[] = $title;
        } else {
            $output[] = 'Dropdown Paragraph';
        }
        $output[] = '';
        
        // Add type header for clarity
        $output[] = '=== QUESTION TYPE: DROPDOWN PARAGRAPH ===';
        $output[] = '';
        
        // Add reading texts
        if (!empty($reading_texts)) {
            foreach ($reading_texts as $text) {
                $text_title = isset($text['title']) ? $text['title'] : '';
                $text_content = isset($text['content']) ? $text['content'] : '';
                
                if (!empty($text_content)) {
                    if (!empty($text_title)) {
                        $output[] = '[READING PASSAGE] ' . $text_title;
                    } else {
                        $output[] = '[READING PASSAGE]';
                    }
                    $output[] = strip_tags($text_content);
                    $output[] = '[END READING PASSAGE]';
                    $output[] = '';
                }
            }
        }
        
        // Get the question (usually just one for dropdown paragraph)
        $question = $questions[0];
        $question_text = isset($question['question']) ? $question['question'] : '';
        $dropdown_options = isset($question['dropdown_options']) ? $question['dropdown_options'] : array();
        
        // Convert formatted question text back to simple ___N___ placeholders
        $simple_text = preg_replace('/\d+\.\[[^\]]+\]/', '___$0___', $question_text);
        // More accurately, extract the dropdown number and replace
        $simple_text = preg_replace('/(\d+)\.\[([^\]]+)\]/', '___$1___', $question_text);
        
        $output[] = $simple_text;
        $output[] = '';
        
        // Add dropdown definitions with correct answers
        if (is_array($dropdown_options)) {
            ksort($dropdown_options);
            foreach ($dropdown_options as $dropdown_num => $dropdown_data) {
                $output[] = 'DROPDOWN ' . $dropdown_num . ':';
                
                if (isset($dropdown_data) && is_array($dropdown_data)) {
                    $options = isset($dropdown_data['options']) ? $dropdown_data['options'] : $dropdown_data;
                    $letters = range('A', 'Z');
                    $correct_option = '';
                    
                    foreach ($options as $opt_index => $option) {
                        $option_text = is_array($option) ? (isset($option['text']) ? $option['text'] : '') : $option;
                        $is_correct = false;
                        
                        if (is_array($option)) {
                            $is_correct = isset($option['is_correct']) && $option['is_correct'];
                        }
                        
                        $line = $letters[$opt_index] . ') ' . $option_text;
                        if ($is_correct) {
                            $line .= ' [CORRECT]';
                            $correct_option = $option_text;
                        }
                        $output[] = $line;
                    }
                    
                    if (!empty($correct_option)) {
                        $output[] = '';
                        $output[] = 'CORRECT ANSWER: ' . $correct_option;
                    }
                }
                
                $output[] = '';
            }
        }
        
        // Add general question feedback if present
        if (!empty($question['correct_feedback'])) {
            $output[] = '[GENERAL CORRECT FEEDBACK] ' . strip_tags($question['correct_feedback']);
        }
        if (!empty($question['incorrect_feedback'])) {
            $output[] = '[GENERAL INCORRECT FEEDBACK] ' . strip_tags($question['incorrect_feedback']);
        }
        if (!empty($question['no_answer_feedback'])) {
            $output[] = '[NO ANSWER FEEDBACK] ' . strip_tags($question['no_answer_feedback']);
        }
        
        return implode("\n", $output);
    }
}
