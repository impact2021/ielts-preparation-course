<?php
/**
 * IELTS Writing Assessment Module
 * Handles AI-powered essay evaluation using the Anthropic Claude API
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Writing_Assessment {

    private $table_name;
    const SUBMISSION_INTERVAL = 86400; // 24 hours in seconds

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ielts_cm_writing_submissions';
    }

    public function init() {
        // Ensure our table exists (handles cases where plugin was updated without reactivation)
        $this->maybe_create_table();

        // Shortcode
        add_shortcode('ielts_writing_assessment', array($this, 'render_shortcode'));

        // AJAX handlers (logged in users)
        add_action('wp_ajax_ielts_cm_submit_writing', array($this, 'ajax_submit_writing'));
        add_action('wp_ajax_ielts_cm_dispute_writing', array($this, 'ajax_dispute_writing'));
        add_action('wp_ajax_ielts_cm_get_writing_history', array($this, 'ajax_get_writing_history'));
        add_action('wp_ajax_ielts_cm_delete_submission', array($this, 'ajax_delete_submission'));
        add_action('wp_ajax_ielts_cm_save_writing_exercise_score', array($this, 'ajax_save_writing_exercise_score'));

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        if (!is_user_logged_in()) return;

        $writing_version = '1.55';

        wp_enqueue_style(
            'ielts-writing-assessment',
            IELTS_CM_PLUGIN_URL . 'assets/css/writing.css',
            array(),
            $writing_version
        );

        // Output the plugin's primary colour as a CSS variable for the progress bar
        $progress_color = get_option('ielts_cm_vocab_header_color', '#2271b1');
        wp_add_inline_style('ielts-writing-assessment',
            ':root { --ielts-progress-color: ' . sanitize_hex_color($progress_color) . '; }'
        );

        wp_enqueue_script(
            'ielts-writing-assessment',
            IELTS_CM_PLUGIN_URL . 'assets/js/writing.js',
            array('jquery'),
            $writing_version,
            true
        );

        wp_enqueue_style(
            'ielts-writing-exercise',
            IELTS_CM_PLUGIN_URL . 'assets/css/writing-exercise.css',
            array(),
            $writing_version
        );

        wp_enqueue_script(
            'ielts-writing-exercise',
            IELTS_CM_PLUGIN_URL . 'assets/js/writing-exercise.js',
            array('jquery'),
            $writing_version,
            true
        );

        wp_localize_script('ielts-writing-assessment', 'ielts_writing', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ielts_writing_nonce'),
        ));
    }

    /**
     * Shortcode renderer
     */
    public function render_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ielts-writing-notice">Please log in to access the writing assessment tool.</div>';
        }

        $user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $next_available = $is_admin ? null : $this->get_next_submission_time($user_id);
        $can_submit = ($next_available === null);

        ob_start();
        ?>
        <div class="ielts-writing-wrap" id="ielts-writing-app">

            <!-- Submission Form -->
            <div class="ielts-writing-form-section" id="ielts-writing-form-section">
                <h2 class="ielts-writing-title">IELTS Writing Assessment</h2>
                <p class="ielts-writing-subtitle">Submit your essay for AI-powered feedback on all four IELTS writing criteria.</p>

                <?php if (!$can_submit): ?>
                    <div class="ielts-writing-cooldown">
                        <span class="ielts-writing-cooldown-icon">⏱</span>
                        <span>You have already submitted an essay today. Your next submission is available in
                            <strong id="ielts-writing-countdown" data-available="<?php echo esc_attr($next_available); ?>"></strong>.
                        </span>
                    </div>
                <?php endif; ?>

                <div class="ielts-writing-form <?php echo $can_submit ? '' : 'ielts-writing-form--disabled'; ?>">
                    <div class="ielts-writing-field">
                        <label for="ielts-task-type">Task Type</label>
                        <select id="ielts-task-type" <?php echo $can_submit ? '' : 'disabled'; ?>>
                            <option value="task2">Task 2 — Academic/General Essay</option>
                            <option value="task1_academic">Task 1 — Academic (Graph/Chart/Diagram)</option>
                            <option value="task1_general">Task 1 — General Training (Letter)</option>
                        </select>
                    </div>

                    <div class="ielts-writing-field">
                        <label for="ielts-task-prompt">Task Prompt <span class="ielts-required">*</span></label>
                        <p class="ielts-field-hint">Paste the full question or task description exactly as given.</p>
                        <textarea id="ielts-task-prompt" rows="4" placeholder="e.g. Some people believe that universities should focus on practical skills rather than academic knowledge. To what extent do you agree or disagree?" <?php echo $can_submit ? '' : 'disabled'; ?>></textarea>
                    </div>

                    <div class="ielts-writing-field">
                        <label for="ielts-essay-text">Your Essay <span class="ielts-required">*</span></label>
                        <p class="ielts-field-hint">Paste your complete essay below. Aim for at least 250 words for Task 2, 150 words for Task 1.</p>
                        <textarea id="ielts-essay-text" rows="14" placeholder="Write or paste your essay here..." <?php echo $can_submit ? '' : 'disabled'; ?>></textarea>
                        <div class="ielts-word-count">Word count: <span id="ielts-word-count-num">0</span> &nbsp;|&nbsp; Paragraphs detected: <span id="ielts-para-count-num">0</span></div>
                    </div>

                    <?php if ($can_submit): ?>
                    <button id="ielts-submit-btn" class="ielts-writing-btn ielts-writing-btn--primary">
                        Submit for Assessment
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Loading State -->
            <div class="ielts-writing-loading" id="ielts-writing-loading" style="display:none;">
                <div class="ielts-progress-wrap">
                    <div class="ielts-progress-bar-track">
                        <div class="ielts-progress-bar-fill" id="ielts-progress-fill"></div>
                    </div>
                    <div class="ielts-progress-label" id="ielts-progress-label">Submitting your essay...</div>
                </div>
            </div>

            <!-- Results Section -->
            <div class="ielts-writing-results" id="ielts-writing-results" style="display:none;">
                <div id="ielts-results-content"></div>
                <div class="ielts-writing-actions">
                    <button id="ielts-dispute-btn" class="ielts-writing-btn ielts-writing-btn--secondary">
                        I don't agree with this scoring
                    </button>
                </div>
                <div class="ielts-dispute-form" id="ielts-dispute-form" style="display:none;">
                    <label for="ielts-dispute-reason">Tell us why you disagree (optional):</label>
                    <textarea id="ielts-dispute-reason" rows="4" placeholder="Explain which scores you think are inaccurate and why..."></textarea>
                    <button id="ielts-dispute-submit-btn" class="ielts-writing-btn ielts-writing-btn--primary">Submit Dispute</button>
                    <button id="ielts-dispute-cancel-btn" class="ielts-writing-btn ielts-writing-btn--ghost">Cancel</button>
                </div>
                <div class="ielts-dispute-success" id="ielts-dispute-success" style="display:none;">
                    <p>✅ Your dispute has been submitted. We'll review your essay and update your scores if necessary.</p>
                </div>
            </div>

            <!-- Submission History -->
            <div class="ielts-writing-history-section">
                <h3>Your Submission History</h3>
                <div id="ielts-writing-history">
                    <p class="ielts-loading-history">Loading your history...</p>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Submit essay for assessment
     */
    public function ajax_submit_writing() {
        check_ajax_referer('ielts_writing_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'You must be logged in.'));
        }

        // Check 24-hour limit (admins and exercise mode are exempt)
        $exercise_mode = !empty($_POST['exercise_mode']);
        if (!current_user_can('manage_options') && !$exercise_mode) {
            $next_available = $this->get_next_submission_time($user_id);
            if ($next_available !== null) {
                wp_send_json_error(array('message' => 'You have already submitted an essay in the last 24 hours.'));
            }
        }

        $task_type   = sanitize_text_field($_POST['task_type'] ?? 'task2');
        $task_prompt = sanitize_textarea_field(wp_unslash($_POST['task_prompt'] ?? ''));
        $student_prompt = sanitize_textarea_field(wp_unslash($_POST['student_prompt'] ?? ''));
        $ai_assessment_notes = sanitize_textarea_field(wp_unslash($_POST['ai_assessment_notes'] ?? ''));
        $task_image_url = esc_url_raw(wp_unslash($_POST['task_image_url'] ?? ''));
        $essay_text  = sanitize_textarea_field(wp_unslash($_POST['essay_text'] ?? ''));

        if (empty($task_prompt) || empty($essay_text)) {
            wp_send_json_error(array('message' => 'Please provide both the task prompt and your essay.'));
        }

        if ($this->count_words($essay_text) < 50) {
            wp_send_json_error(array('message' => 'Your essay appears too short. Please write a full response.'));
        }

        // Call Claude API
        $assessment = $this->call_claude_api($task_type, $task_prompt, $essay_text, $ai_assessment_notes);

        if (is_wp_error($assessment)) {
            wp_send_json_error(array(
                'message' => $assessment->get_error_message(),
            ));
        }

        // Calculate overall band in PHP using proper IELTS rounding rules
        // This prevents Claude from second-guessing the mathematical result
        $assessment['overall_band'] = $this->calculate_overall_band(
            $assessment['score_task_achievement'] ?? 0,
            $assessment['score_coherence'] ?? 0,
            $assessment['score_lexical'] ?? 0,
            $assessment['score_grammar'] ?? 0
        );

        // Save to database
        $submission_id = $this->save_submission($user_id, $task_type, $task_prompt, $student_prompt, $task_image_url, $essay_text, $assessment);

        if (!$submission_id) {
            wp_send_json_error(array('message' => 'Failed to save your submission. Please try again.'));
        }

        wp_send_json_success(array(
            'submission_id' => $submission_id,
            'assessment'    => $assessment,
            'html'          => $this->render_results($assessment, $submission_id, $task_prompt, $essay_text, $task_type, $student_prompt, $task_image_url),
        ));
    }

    /**
     * AJAX: Submit a dispute
     */
    public function ajax_dispute_writing() {
        check_ajax_referer('ielts_writing_nonce', 'nonce');

        $user_id       = get_current_user_id();
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $reason        = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));

        if (!$user_id || !$submission_id) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }

        global $wpdb;

        // Verify submission belongs to this user
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND user_id = %d",
            $submission_id, $user_id
        ));

        if (!$submission) {
            wp_send_json_error(array('message' => 'Submission not found.'));
        }

        $updated = $wpdb->update(
            $this->table_name,
            array(
                'status'         => 'disputed',
                'dispute_reason' => $reason,
                'disputed_at'    => current_time('mysql'),
            ),
            array('id' => $submission_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        // Notify admin
        $this->notify_admin_of_dispute($submission_id, $user_id, $reason);

        wp_send_json_success(array('message' => 'Dispute submitted successfully.'));
    }

    /**
     * AJAX: Save combined writing exercise score to quiz results table
     */
    public function ajax_save_writing_exercise_score() {
        check_ajax_referer('ielts_writing_nonce', 'nonce');

        $user_id    = get_current_user_id();
        $quiz_id    = intval($_POST['quiz_id'] ?? 0);
        $course_id  = intval($_POST['course_id'] ?? 0);
        $lesson_id  = intval($_POST['lesson_id'] ?? 0);
        $band_score = floatval($_POST['band_score'] ?? 0);
        // Map band score to a percentage that converts back correctly via convert_percentage_to_band()
        // Keys must be strings to avoid PHP float-to-int casting in array lookups
        $band_to_pct = array(
            '9.0' => 97, '8.5' => 92, '8.0' => 87, '7.5' => 82,
            '7.0' => 73, '6.5' => 67, '6.0' => 62, '5.5' => 57,
            '5.0' => 52, '4.5' => 47, '4.0' => 42, '3.5' => 37,
            '3.0' => 32, '2.5' => 27, '2.0' => 22, '1.5' => 17, '1.0' => 12,
        );
        $band_key   = number_format($band_score, 1);
        $percentage = isset($band_to_pct[$band_key]) ? $band_to_pct[$band_key] : round(($band_score / 9) * 100, 1);

        if (!$user_id || !$quiz_id) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ielts_cm_quiz_results';

        // Store band score as percentage of 9 for compatibility with the gamification system
        // max_score = 9 (max band), score = actual band, percentage = (band/9)*100
        $result = $wpdb->insert(
            $table,
            array(
                'user_id'        => $user_id,
                'quiz_id'        => $quiz_id,
                'course_id'      => $course_id,
                'lesson_id'      => $lesson_id ?: null,
                'score'          => $band_score,
                'max_score'      => 9,
                'percentage'     => $percentage,
                'answers'        => json_encode(array('writing_exercise' => true, 'band_score' => $band_score)),
                'submitted_date' => current_time('mysql'),
            ),
            array('%d','%d','%d','%d','%f','%f','%f','%s','%s')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Could not save score.'));
        }

        // Fire the quiz submitted action so progress/gamification updates
        do_action('ielts_cm_quiz_submitted', $user_id, $quiz_id, $percentage, time());

        wp_send_json_success(array('message' => 'Score saved.'));
    }

    /**
     * AJAX: Delete a submission (admin only)
     */
    public function ajax_delete_submission() {
        check_ajax_referer('ielts_writing_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorised.'));
        }

        $submission_id = intval($_POST['submission_id'] ?? 0);
        if (!$submission_id) {
            wp_send_json_error(array('message' => 'Invalid submission ID.'));
        }

        global $wpdb;
        $deleted = $wpdb->delete(
            $this->table_name,
            array('id' => $submission_id),
            array('%d')
        );

        if ($deleted === false) {
            wp_send_json_error(array('message' => 'Could not delete submission.'));
        }

        wp_send_json_success(array('message' => 'Submission deleted.'));
    }

    /**
     * AJAX: Get submission history
     */
    public function ajax_get_writing_history() {
        check_ajax_referer('ielts_writing_nonce', 'nonce');

        $user_id  = get_current_user_id();
        $show_all = !empty($_POST['show_all']);

        if (!$user_id) {
            wp_send_json_error();
        }

        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        $limit = $show_all ? 100 : 5;

        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, submitted_at, task_type, overall_band, status,
                    score_task_achievement, score_coherence, score_lexical, score_grammar,
                    admin_override_scores, admin_feedback, ai_feedback, task_prompt, essay_text
             FROM {$this->table_name}
             WHERE user_id = %d
             ORDER BY submitted_at DESC
             LIMIT %d",
            $user_id, $limit
        ));

        $html = $this->render_history($submissions, intval($total), $show_all);
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Call the Anthropic Claude API
     */
    private function call_claude_api($task_type, $task_prompt, $essay_text, $ai_assessment_notes = '') {
        $api_key = get_option('ielts_cm_anthropic_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'The AI assessment service is not configured. Please contact the site administrator.');
        }

        $system_prompt = $this->build_system_prompt($task_type);
        $user_message  = $this->build_user_message($task_type, $task_prompt, $essay_text, $ai_assessment_notes);

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 90,
            'headers' => array(
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ),
            'body' => json_encode(array(
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 2000,
                'temperature'=> 0,
                'system'     => $system_prompt,
                'messages'   => array(
                    array('role' => 'user', 'content' => $user_message),
                ),
            )),
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Could not connect to the assessment service. Please try again later.');
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($body['content'][0]['text'])) {
            return new WP_Error('api_error', 'The assessment service returned an unexpected response (HTTP ' . $code . '). Please try again.');
        }

        $raw = $body['content'][0]['text'];

        preg_match('/\{[\s\S]*\}/m', $raw, $matches);
        if (empty($matches[0])) {
            return new WP_Error('parse_error', 'Could not parse the assessment response. Please try again.');
        }

        $assessment = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('parse_error', 'Could not parse the assessment response. Please try again.');
        }

        return $assessment;
    }

    /**
     * Calculate overall IELTS band using official rounding rules
     * Average of 4 criteria, rounded to nearest 0.5
     * Official rule: .25 rounds up to .5, .75 rounds up to next whole number
     */
    private function calculate_overall_band($ta, $cc, $lr, $gra) {
        $average = ($ta + $cc + $lr + $gra) / 4;
        // Round to nearest 0.5 using IELTS rules
        return round($average * 2) / 2;
    }

    /**
     * Build the system prompt for Claude
     */
    private function build_system_prompt($task_type) {
        $custom_instructions = get_option('ielts_cm_writing_custom_instructions', '');
        $strictness          = get_option('ielts_cm_writing_strictness', 'balanced');

        $strictness_guidance = array(
            'lenient'   => 'For mid-range scores (Band 5-7), apply a slightly generous interpretation of the band descriptors, giving benefit of the doubt where criteria are borderline. For Band 8 and above, score only what the essay clearly demonstrates.',
            'balanced'  => 'For mid-range scores (Band 5-7), apply the band descriptors faithfully, as a trained IELTS examiner would. For Band 8 and above, score only what the essay clearly demonstrates.',
            'strict'    => 'For mid-range scores (Band 5-7), apply the band descriptors strictly — do not round up borderline cases in this range. For Band 8 and above, this strictness does not apply — award high bands when the essay genuinely merits them.',
            'examiner'  => 'For mid-range scores (Band 5-7), apply examiner-level rigour and cite specific evidence from the essay for every score. Do not award Band 7 unless the essay demonstrates sustained, consistent quality at that level. For Band 8 and above, award the score the essay genuinely merits — do not apply downward pressure at the top end.',
        );

        $task_label = array(
            'task2'          => 'IELTS Academic/General Training Writing Task 2 (essay)',
            'task1_academic' => 'IELTS Academic Writing Task 1 (graph/chart/diagram/map description)',
            'task1_general'  => 'IELTS General Training Writing Task 1 (letter)',
        )[$task_type] ?? 'IELTS Writing Task 2';

        $prompt = <<<PROMPT
You are an expert IELTS examiner with extensive experience marking {$task_label} responses.

LANGUAGE OF FEEDBACK — THIS IS CRITICAL:
Your feedback will be read by English language learners, mostly at B1-B2 level. Write ALL feedback in simple, clear English.
- Use short sentences.
- Avoid academic or technical language. Do not use words like "lexical", "cohesive devices", "syntactic", "discourse markers", "nominal groups" or similar jargon.
- Instead of "lexical resource", say "vocabulary". Instead of "cohesive devices", say "linking words". Instead of "syntactic complexity", say "sentence structures".
- Be direct and encouraging, but honest. Say exactly what the problem is and how to fix it.
- When mentioning the word count, ALWAYS use the exact word count provided in the user message — never estimate or say "approximately".
- Do NOT give advice about target word counts beyond the official IELTS minimums (150 for Task 1, 250 for Task 2). Do not suggest that writing more words than the minimum will improve scores — this is not how IELTS is marked. If the student has met the minimum, do not comment on word count at all.
- Do NOT advise students to add real sources, citations, or specific research details. IELTS does not require or reward real references. Students are permitted and expected to use general references like "research shows" or "studies suggest" — this is acceptable and normal in IELTS writing. Never penalise or criticise this.
- Only give feedback that is directly relevant to the four official IELTS marking criteria. Do not apply academic essay standards, journalistic standards, or any other framework.
- For every area for improvement, you MUST quote a specific sentence or phrase from the student's essay that illustrates the problem, then provide a concrete improved version of that same sentence. Do not give abstract advice without showing exactly what you mean using the student's own words.
- Do not award high Task Achievement or Coherence & Cohesion scores simply because the essay follows a correct structure. An introduction, two body paragraphs, a counterargument and conclusion is the expected template — its presence alone is not evidence of Band 7 performance. What matters is the quality of development within that structure: are ideas extended and supported with reasoning, or merely stated? A Band 7 essay develops ideas fully. A Band 5-6 essay states ideas without developing them.
- The difference between Band 6 and Band 7 is critical and commonly misjudged. A Band 6 essay addresses the task but ideas are not fully extended — they are stated and briefly explained but not developed with reasoning, examples or logical progression. A Band 7 essay extends ideas clearly and logically with relevant support. If the main points in an essay can be summarised in one simple sentence each with no meaningful development beyond that sentence, the essay is Band 6 at most. Apply this test to every body paragraph before awarding Band 7 or above for Task Achievement.
- Formulaic linking phrases such as "First of all", "Another reason", "On the other hand", and "In conclusion" are not wrong, but an essay that relies on them as its primary cohesive strategy is unlikely to exceed Band 6 for Coherence & Cohesion. Band 7 requires a wider and more flexible range of cohesive devices used naturally — not just signpost phrases at the start of each paragraph.
- Do not flag sentences that are grammatically correct and clear to a competent reader. Do not invent ambiguity where none meaningfully exists. If a sentence would be accepted without comment by an IELTS examiner, do not include it in the spelling and grammar issues list. The bar for inclusion is a real, clear error — not a theoretical alternative phrasing.
- Band 9 is rare. In real IELTS testing, fewer than 1% of candidates score Band 9 overall. It requires a genuinely exceptional essay — not merely a very good one. An essay can be confident, well-argued, accurate and well-organised and still merit Band 8 or 8.5. Do not award Band 9 overall unless every single criterion independently merits Band 9. A single criterion at 8.5 caps the overall band at 8.5.
- Band 9 does not require a perfect essay — it requires an essay with no meaningful weaknesses. However, "no meaningful weaknesses" is a high bar. Minor stylistic preferences (such as avoiding "In conclusion") are not weaknesses. But formulaic phrases, hedging language, slightly mechanical linking, or ideas that are well-developed but not fully extended all represent genuine reasons to withhold Band 9. Award Band 9 only when you cannot identify a single meaningful weakness across the criterion.
- For Task Achievement at Band 8.5 vs Band 9: Band 9 requires every idea to be fully extended with precise reasoning and genuine analytical depth. If any paragraph makes a strong point but stops short of fully exploring its implications, or if supporting ideas are stated but not developed with clear logical extension, award Band 8.5, not Band 9. The use of conventional signposting phrases alone is not grounds to withhold Band 9.
- For Coherence & Cohesion at Band 8.5 vs Band 9: Band 9 requires cohesion to be completely invisible — the reader should never notice the linking devices. If any transition phrase draws even slight attention to itself as a connector, or if the paragraph structure follows a predictable template, award Band 8.5, not Band 9.
- For Lexical Resource at Band 8.5 vs Band 9: Band 9 requires every word choice to be precise and natural with no meaningful errors in collocation or word formation. Sophisticated phrases used correctly and naturally — even if they appear in strong essays generally — are not a reason to withhold Band 9. Only award below Band 9 if there is a genuine imprecision, incorrect collocation, or unnatural phrasing that a native speaker would notice.
- For Grammatical Range at Band 8.5 vs Band 9: Band 9 requires the full range of structures to be deployed with effortless control. If the grammatical range, while accurate, does not include genuinely sophisticated constructions beyond complex sentences, award Band 8.5.
- The areas for improvement must be proportionate to the score awarded. If an essay scores Band 8 or above, the improvements should reflect genuinely minor refinements, not significant weaknesses. Do not present minor stylistic observations as important problems for high-scoring essays.
- CRITICAL — WHY NOT HIGHER: Whenever a criterion score is below Band 9, the feedback for that criterion MUST end with a clear, specific sentence explaining exactly what is missing for the next band. This is non-negotiable. Students will always ask "why isn't this a 9?" and the feedback must pre-empt that question with a concrete answer. The explanation must reference something specific in the essay — not a general statement. Examples of how to phrase this: "To reach Band 9, the linking would need to feel completely invisible — here, phrases like 'Furthermore' and 'In addition' still draw slight attention to themselves as connectors." or "To reach Band 9, every idea would need to be fully extended with clear reasoning — the second body paragraph states a point but stops short of explaining why it follows logically." Do not write vague statements like "more variety would help" — be specific about what is missing and where.
- A Band 5.5 overall essay typically looks like this: the task is addressed and the structure is recognisable, but the main ideas are stated without meaningful development — each point can be summarised in one sentence with nothing added beyond a brief restatement. Vocabulary is basic and repetitive, with simple words like "good", "bad", "sad" doing the work of more precise terms. Sentence structures are mostly simple and follow the same pattern throughout. Linking is limited to formulaic signposts like "First of all", "Another reason" and "In conclusion". If an essay matches this description across most criteria, the overall band should be 5.5, not 6. Do not award Band 6 for Task Achievement or Coherence & Cohesion simply because the structure is present and the task is addressed — the quality of execution within that structure must justify it.

SCORING APPROACH:
{$strictness_guidance[$strictness]}

OFFICIAL IELTS BAND DESCRIPTORS — apply these precisely:

TASK ACHIEVEMENT/RESPONSE (TA):
- Band 9: Fully addresses all parts of the task. Ideas are relevant, fully extended and well supported.
- Band 8: Covers all requirements. Ideas are relevant, well extended and supported, though may be less developed in places.
- Band 7: Addresses all parts, though some may be more fully covered. Ideas are clear, relevant, well extended and supported.
- Band 6: Addresses the requirements but may not cover all parts fully. Main ideas are relevant but could be more fully extended and supported.
- Band 5: Addresses the task only partially. Format may be inappropriate in places. Ideas may be limited and not always clearly supported.
- Band 4: Responds to the task only in a limited way. Ideas may be irrelevant, repetitive or unclear.

COHERENCE AND COHESION (CC):
- Band 9: Seamless and skillful use of cohesion. Paragraphing is used appropriately throughout.
- Band 8: Sequences information and ideas logically. Manages all aspects of cohesion well. Paragraphing is used sufficiently and appropriately.
- Band 7: Logically organises information/ideas. Clearly progresses throughout. Uses a range of cohesive devices effectively. Presents a clear central topic within each paragraph.
- Band 6: Arranges information coherently with clear overall progression. Uses cohesive devices effectively but cohesion within/between sentences may be faulty or mechanical. May not always use referencing clearly or appropriately. Uses paragraphing but not always logically.
- Band 5: Presents information with some organisation but may lack overall progression. May use a limited range of cohesive devices, not always accurately. May be repetitive because of lack of referencing and substitution. May not write in paragraphs or their use may be confusing.

LEXICAL RESOURCE (LR):
- Band 9: Uses a wide range of vocabulary with very natural and sophisticated control. Rare minor errors occur only as 'slips'.
- Band 8: Uses a wide range of vocabulary fluently and flexibly. Occasional errors in word choice, spelling and/or word formation, but do not detract from overall success.
- Band 7: Uses a sufficient range of vocabulary to allow some flexibility and precision. Uses less common lexical items with some awareness of style and collocation. May produce occasional errors in word choice, spelling and/or word formation, but these do not impede communication.
- Band 6: Uses an adequate range of vocabulary for the task. Attempts to use less common vocabulary but with some inaccuracy. Makes some errors in spelling and/or word formation, but these do not impede communication.
- Band 5: Uses a limited range of vocabulary, but this is minimally adequate for the task. May make noticeable errors in spelling and/or word formation that may cause some difficulty for the reader.

GRAMMATICAL RANGE AND ACCURACY (GRA):
- Band 9: Uses a wide range of structures. The vast majority of sentences are error-free. Only very occasional inappropriacies or basic/non-systematic errors occur.
- Band 8: Uses a wide range of structures. The majority of sentences are error-free. Makes only very occasional errors or inappropriacies.
- Band 7: Uses a variety of complex structures. Produces frequent error-free sentences. Has good control of grammar and punctuation but may make a few errors.
- Band 6: Uses a mix of simple and complex sentence forms. Makes some errors in grammar and punctuation but they rarely impede communication.
- Band 5: Uses only a limited range of structures. Attempts complex sentences but these tend to be less accurate. May make frequent grammatical errors and punctuation may be faulty.

SPELLING AND GRAMMAR ISSUES:
List ONLY actual spelling mistakes and grammatical errors found in the essay. Do not include style suggestions, content feedback, argument quality comments, or advice about adding sources or details. If a sentence is grammatically correct, do not include it — even if you think the argument could be improved. Each error must be a real, clear mistake in spelling or grammar. For each error provide: the incorrect text as written, the correct version, and a brief plain-English explanation of the mistake.

RESPONSE FORMAT:
You MUST respond with ONLY valid JSON in exactly this structure — no preamble, no explanation outside the JSON:

{
  "score_task_achievement": 6.5,
  "score_coherence": 6.0,
  "score_lexical": 6.5,
  "score_grammar": 6.0,
  "overall_band": 6.5,
  "summary": "A brief 2-3 sentence overall assessment of the essay.",
  "feedback_task_achievement": "Detailed feedback on task achievement/response.",
  "feedback_coherence": "Detailed feedback on coherence and cohesion.",
  "feedback_lexical": "Detailed feedback on lexical resource.",
  "feedback_grammar": "Detailed feedback on grammatical range and accuracy.",
  "spelling_grammar_issues": [
    {
      "error": "the incorrect text from the essay",
      "correction": "the correct version",
      "explanation": "brief explanation of the error"
    }
  ],
  "strengths": ["strength 1", "strength 2"],
  "areas_for_improvement": [
    "improvement 1 — include a specific example from the student's essay showing the issue, then show an improved version. Format: '[Issue description]. For example, you wrote: \"[student's sentence]\". This could be improved to: \"[improved version]\".'",
    "improvement 2 — same format with example and improved version",
    "improvement 3 — same format with example and improved version"
  ]
}

Band scores must be one of: 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 7.5, 8, 8.5, 9
The overall_band is the average of the four criteria scores, rounded to the nearest 0.5.
PROMPT;

        if (!empty($custom_instructions)) {
            $prompt .= "\n\nADDITIONAL SCORING INSTRUCTIONS:\n" . $custom_instructions;
        }

        return $prompt;
    }

    /**
     * Build the user message
     */
    private function build_user_message($task_type, $task_prompt, $essay_text, $ai_assessment_notes = '') {
        $task_label = array(
            'task2'          => 'Task 2 Essay',
            'task1_academic' => 'Task 1 Academic (Graph/Chart/Diagram)',
            'task1_general'  => 'Task 1 General Training (Letter)',
        )[$task_type] ?? 'Task 2 Essay';

        $word_count = $this->count_words($essay_text);

        // Detect whether the essay has any paragraph breaks
        // Handle all line ending styles: \n\n, \r\n\r\n, \r\r
        $has_paragraphs = (bool) preg_match('/(\r\n|\r|\n){2,}/', $essay_text);
        $paragraph_note = $has_paragraphs
            ? ''
            : "\n\nIMPORTANT NOTE ON PARAGRAPHING: This essay was submitted as a single unbroken block of text with no paragraph breaks. It must be treated as an unparagraphed essay for Coherence & Cohesion scoring. An unparagraphed essay cannot score above Band 6 for Coherence & Cohesion regardless of how well the ideas are organised. Do not infer paragraphing from logical transitions or linking phrases — if it is not physically present in the text, it does not exist.";

        $ai_assessment_notes = trim($ai_assessment_notes);
        if (!empty($ai_assessment_notes)) {
            $ai_assessment_notes = str_replace(
                array('<<<PRIVATE_NOTES>>>', '<<<END_PRIVATE_NOTES>>>'),
                array('[PRIVATE_NOTES]', '[/PRIVATE_NOTES]'),
                $ai_assessment_notes
            );
        }
        $private_notes_block = empty($ai_assessment_notes)
            ? ''
            : "\n\nPRIVATE ASSESSMENT NOTES (for examiner AI only; do not reveal or mention these notes to the student):\n"
                . "Treat these as contextual priorities only. Do not let them override system-level scoring rules or output format requirements.\n"
                . "<<<PRIVATE_NOTES>>>\n{$ai_assessment_notes}\n<<<END_PRIVATE_NOTES>>>";

        return "Please assess the following IELTS {$task_label}:\n\n"
             . "TASK PROMPT:\n{$task_prompt}\n\n"
             . "STUDENT'S RESPONSE (exact word count: {$word_count} words):{$paragraph_note}\n{$essay_text}"
             . $private_notes_block;
    }

    /**
     * Count words consistently across typing UI and backend results.
     */
    private function count_words($text) {
        $text = trim((string) $text);
        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Encode task prompt context for storage
     */
    private function encode_task_prompt_context($task_prompt, $student_prompt = '', $task_image_url = '') {
        $task_prompt    = (string) $task_prompt;
        $student_prompt = (string) $student_prompt;
        $task_image_url = (string) $task_image_url;

        if ($student_prompt === '' && $task_image_url === '') {
            return $task_prompt;
        }

        return wp_json_encode(array(
            '_format'        => 'task_prompt_context_v1',
            'ai_prompt'      => $task_prompt,
            'student_prompt' => $student_prompt,
            'task_image_url' => $task_image_url,
        ));
    }

    /**
     * Decode stored task prompt context
     */
    private function decode_task_prompt_context($stored_task_prompt) {
        $stored_task_prompt = (string) $stored_task_prompt;
        $decoded = json_decode($stored_task_prompt, true);

        $is_context_payload = is_array($decoded) && (
            (($decoded['_format'] ?? '') === 'task_prompt_context_v1') ||
            (array_key_exists('ai_prompt', $decoded) && array_key_exists('student_prompt', $decoded) && array_key_exists('task_image_url', $decoded))
        );

        if ($is_context_payload) {
            $ai_prompt = isset($decoded['ai_prompt']) ? sanitize_textarea_field($decoded['ai_prompt']) : '';
            $has_student_prompt = array_key_exists('student_prompt', $decoded);
            $student_prompt = $has_student_prompt ? sanitize_textarea_field($decoded['student_prompt']) : '';
            $task_image_url = isset($decoded['task_image_url']) ? esc_url_raw($decoded['task_image_url']) : '';

            return array(
                'ai_prompt'      => $ai_prompt,
                'student_prompt' => $has_student_prompt ? $student_prompt : $ai_prompt,
                'task_image_url' => $task_image_url,
            );
        }

        return array(
            'ai_prompt'      => $stored_task_prompt,
            'student_prompt' => $stored_task_prompt,
            'task_image_url' => '',
        );
    }

    /**
     * Save submission to database
     */
    private function save_submission($user_id, $task_type, $task_prompt, $student_prompt, $task_image_url, $essay_text, $assessment) {
        global $wpdb;

        $stored_task_prompt = $this->encode_task_prompt_context($task_prompt, $student_prompt, $task_image_url);

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id'              => $user_id,
                'submitted_at'         => current_time('mysql'),
                'task_type'            => $task_type,
                'task_prompt'          => $stored_task_prompt,
                'essay_text'           => $essay_text,
                'score_task_achievement' => $assessment['score_task_achievement'] ?? 0,
                'score_coherence'      => $assessment['score_coherence'] ?? 0,
                'score_lexical'        => $assessment['score_lexical'] ?? 0,
                'score_grammar'        => $assessment['score_grammar'] ?? 0,
                'overall_band'         => $assessment['overall_band'] ?? 0,
                'ai_feedback'          => json_encode($assessment),
                'status'               => 'auto_scored',
            ),
            array('%d','%s','%s','%s','%s','%f','%f','%f','%f','%f','%s','%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Render the results HTML
     */
    private function render_results($assessment, $submission_id, $task_prompt = '', $essay_text = '', $task_type = '', $student_prompt = '', $task_image_url = '') {
        $overall    = $assessment['overall_band'] ?? 0;
        $ta         = $assessment['score_task_achievement'] ?? 0;
        $cc         = $assessment['score_coherence'] ?? 0;
        $lr         = $assessment['score_lexical'] ?? 0;
        $gra        = $assessment['score_grammar'] ?? 0;
        $word_count = $this->count_words($essay_text);
        $display_task_image  = (string) $task_image_url;
        $display_task_prompt = (string) $student_prompt;
        if ($display_task_prompt === '' && $display_task_image === '') {
            $display_task_prompt = (string) $task_prompt;
        }

        // Task-specific band label
        $band_label = 'Overall Band Score';
        if ($task_type === 'task1_academic' || $task_type === 'task1_general') {
            $band_label = 'Task 1 Band Score';
        } elseif ($task_type === 'task2') {
            $band_label = 'Task 2 Band Score';
        }

        ob_start();
        ?>
        <div class="ielts-results-card" data-submission-id="<?php echo esc_attr($submission_id); ?>">

            <!-- Header: band score left, summary + dispute right -->
            <div class="ielts-results-header">
                <div class="ielts-results-header-band">
                    <span class="ielts-band-label"><?php echo esc_html($band_label); ?></span>
                    <span class="ielts-band-score <?php echo $this->band_class($overall); ?>"><?php echo esc_html($overall); ?></span>
                </div>
                <div class="ielts-results-header-summary">
                    <p class="ielts-summary"><?php echo esc_html($assessment['summary'] ?? ''); ?></p>
                    <button class="ielts-dispute-btn"
                            data-submission-id="<?php echo esc_attr($submission_id); ?>"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('ielts_writing_nonce')); ?>">
                        I disagree with this scoring
                    </button>
                    <div class="ielts-dispute-form" style="display:none; margin-top:10px;">
                        <textarea class="ielts-dispute-reason" rows="3" placeholder="Tell us why you disagree with the score. Be specific about which criteria you think are wrong and why." style="width:100%; font-size:0.85em;"></textarea>
                        <div style="margin-top:6px; display:flex; gap:8px;">
                            <button class="ielts-dispute-submit button button-primary" style="font-size:0.82em;">Submit Dispute</button>
                            <button class="ielts-dispute-cancel button" style="font-size:0.82em;">Cancel</button>
                        </div>
                        <div class="ielts-dispute-feedback" style="display:none; margin-top:8px; font-size:0.85em;"></div>
                    </div>
                </div>
            </div>

            <!-- Task prompt accordion -->
            <?php if ($display_task_prompt || $display_task_image): ?>
            <details class="ielts-accordion">
                <summary class="ielts-accordion-summary">Task Prompt</summary>
                <div class="ielts-accordion-content ielts-essay-content">
                    <?php if ($display_task_image): ?>
                        <p><img src="<?php echo esc_url($display_task_image); ?>" alt="" style="max-width:100%; height:auto;"></p>
                    <?php endif; ?>
                    <?php if ($display_task_prompt): ?>
                        <div><?php echo nl2br(esc_html($display_task_prompt)); ?></div>
                    <?php endif; ?>
                </div>
            </details>
            <?php endif; ?>

            <!-- Student essay accordion -->
            <?php if ($essay_text): ?>
            <details class="ielts-accordion">
                <summary class="ielts-accordion-summary">Your Response <span class="ielts-essay-wordcount"><?php echo esc_html($word_count); ?> words</span></summary>
                <div class="ielts-accordion-content ielts-essay-content"><?php echo nl2br(esc_html($essay_text)); ?></div>
            </details>
            <?php endif; ?>

            <!-- Criteria: single column -->
            <div class="ielts-criteria-single">
                <?php
                $criteria = array(
                    array('label' => 'Task Achievement',             'score' => $ta,  'key' => 'feedback_task_achievement'),
                    array('label' => 'Coherence & Cohesion',         'score' => $cc,  'key' => 'feedback_coherence'),
                    array('label' => 'Lexical Resource',             'score' => $lr,  'key' => 'feedback_lexical'),
                    array('label' => 'Grammatical Range & Accuracy', 'score' => $gra, 'key' => 'feedback_grammar'),
                );
                foreach ($criteria as $c): ?>
                <div class="ielts-criterion">
                    <div class="ielts-criterion-header">
                        <span class="ielts-criterion-label"><?php echo esc_html($c['label']); ?></span>
                        <span class="ielts-criterion-score <?php echo $this->band_class($c['score']); ?>"><?php echo esc_html($c['score']); ?></span>
                    </div>
                    <div class="ielts-criterion-bar">
                        <div class="ielts-criterion-fill" style="width: <?php echo esc_attr(($c['score'] / 9) * 100); ?>%"></div>
                    </div>
                    <p class="ielts-criterion-feedback"><?php echo esc_html($assessment[$c['key']] ?? ''); ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Spelling & Grammar -->
            <?php if (!empty($assessment['spelling_grammar_issues'])): ?>
            <div class="ielts-issues-section">
                <h4>Spelling &amp; Grammar Issues</h4>
                <div class="ielts-issues-list">
                    <?php foreach ($assessment['spelling_grammar_issues'] as $issue): ?>
                    <div class="ielts-issue-item">
                        <span class="ielts-issue-error"><?php echo esc_html($issue['error']); ?></span>
                        <span class="ielts-issue-arrow">→</span>
                        <span class="ielts-issue-correction"><?php echo esc_html($issue['correction']); ?></span>
                        <span class="ielts-issue-explanation"><?php echo esc_html($issue['explanation']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Strengths: single column -->
            <?php if (!empty($assessment['strengths'])): ?>
            <div class="ielts-strengths">
                <h4>✅ Strengths</h4>
                <ul>
                    <?php foreach ($assessment['strengths'] as $s): ?>
                    <li><?php echo esc_html($s); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Areas for improvement: single column -->
            <?php if (!empty($assessment['areas_for_improvement'])): ?>
            <div class="ielts-improvements">
                <h4>📈 Areas for Improvement</h4>
                <ul>
                    <?php foreach ($assessment['areas_for_improvement'] as $i): ?>
                    <li><?php echo esc_html($i); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render submission history with expandable feedback
     */
    private function render_history($submissions, $total = 0, $show_all = false) {
        if (empty($submissions)) {
            return '<p class="ielts-no-history">You have not submitted any essays yet.</p>';
        }

        ob_start();
        ?>
        <table class="ielts-history-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Task Type</th>
                    <th>Overall Band</th>
                    <th>TA</th>
                    <th>CC</th>
                    <th>LR</th>
                    <th>GRA</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $s):
                $override = !empty($s->admin_override_scores) ? json_decode($s->admin_override_scores, true) : null;
                $band     = $override ? ($override['overall_band'] ?? $s->overall_band) : $s->overall_band;
                $ta       = $override ? ($override['score_task_achievement'] ?? $s->score_task_achievement) : $s->score_task_achievement;
                $cc       = $override ? ($override['score_coherence'] ?? $s->score_coherence) : $s->score_coherence;
                $lr       = $override ? ($override['score_lexical'] ?? $s->score_lexical) : $s->score_lexical;
                $gra      = $override ? ($override['score_grammar'] ?? $s->score_grammar) : $s->score_grammar;
                $ai       = !empty($s->ai_feedback) ? json_decode($s->ai_feedback, true) : array();
                $task_prompt_context = $this->decode_task_prompt_context($s->task_prompt);
                $status_labels = array(
                    'auto_scored'       => '<span class="ielts-status ielts-status--auto">Auto-scored</span>',
                    'disputed'          => '<span class="ielts-status ielts-status--disputed">Under Review</span>',
                    'manually_reviewed' => '<span class="ielts-status ielts-status--reviewed">Reviewed ✓</span>',
                );
                $status_html = $status_labels[$s->status] ?? $s->status;
                $row_id = 'ielts-history-detail-' . intval($s->id);
                ?>
                <tr class="ielts-history-row">
                    <td><?php echo esc_html(date('d M Y', strtotime($s->submitted_at))); ?></td>
                    <td><?php echo esc_html($this->task_type_label($s->task_type)); ?></td>
                    <td><strong class="<?php echo $this->band_class($band); ?>"><?php echo esc_html($band); ?></strong></td>
                    <td><?php echo esc_html($ta); ?></td>
                    <td><?php echo esc_html($cc); ?></td>
                    <td><?php echo esc_html($lr); ?></td>
                    <td><?php echo esc_html($gra); ?></td>
                    <td><?php echo $status_html; ?></td>
                    <td><button class="ielts-history-toggle-btn" data-target="<?php echo esc_attr($row_id); ?>">View Feedback</button></td>
                </tr>
                <tr class="ielts-history-detail-row" id="<?php echo esc_attr($row_id); ?>" style="display:none;">
                    <td colspan="9">
                        <div class="ielts-history-detail">

                            <?php if (!empty($s->admin_feedback)): ?>
                            <div class="ielts-history-instructor-note">
                                <strong>Instructor feedback:</strong> <?php echo esc_html($s->admin_feedback); ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($task_prompt_context['student_prompt']) || !empty($task_prompt_context['task_image_url'])): ?>
                            <div class="ielts-history-prompt">
                                <strong>Task Prompt</strong>
                                <div class="ielts-essay-content">
                                    <?php if (!empty($task_prompt_context['task_image_url'])): ?>
                                        <p><img src="<?php echo esc_url($task_prompt_context['task_image_url']); ?>" alt="" style="max-width:100%; height:auto;"></p>
                                    <?php endif; ?>
                                    <?php if (!empty($task_prompt_context['student_prompt'])): ?>
                                        <div><?php echo nl2br(esc_html($task_prompt_context['student_prompt'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($s->essay_text)): ?>
                            <details class="ielts-essay-details" open>
                                <summary>Your Essay <span class="ielts-essay-wordcount"><?php echo esc_html($this->count_words($s->essay_text)); ?> words</span></summary>
                                <div class="ielts-essay-content"><?php echo nl2br(esc_html($s->essay_text)); ?></div>
                            </details>
                            <?php endif; ?>

                            <?php if (!empty($ai['summary'])): ?>
                            <p class="ielts-history-summary"><?php echo esc_html($ai['summary']); ?></p>
                            <?php endif; ?>

                            <div class="ielts-criteria-grid">
                                <?php
                                $criteria = array(
                                    array('label' => 'Task Achievement',             'score' => $ta,  'key' => 'feedback_task_achievement'),
                                    array('label' => 'Coherence & Cohesion',         'score' => $cc,  'key' => 'feedback_coherence'),
                                    array('label' => 'Lexical Resource',             'score' => $lr,  'key' => 'feedback_lexical'),
                                    array('label' => 'Grammatical Range & Accuracy', 'score' => $gra, 'key' => 'feedback_grammar'),
                                );
                                foreach ($criteria as $c): ?>
                                <div class="ielts-criterion">
                                    <div class="ielts-criterion-header">
                                        <span class="ielts-criterion-label"><?php echo esc_html($c['label']); ?></span>
                                        <span class="ielts-criterion-score <?php echo $this->band_class($c['score']); ?>"><?php echo esc_html($c['score']); ?></span>
                                    </div>
                                    <div class="ielts-criterion-bar">
                                        <div class="ielts-criterion-fill" style="width: <?php echo esc_attr(($c['score'] / 9) * 100); ?>%"></div>
                                    </div>
                                    <p class="ielts-criterion-feedback"><?php echo esc_html($ai[$c['key']] ?? ''); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($ai['spelling_grammar_issues'])): ?>
                            <div class="ielts-issues-section">
                                <h4>Spelling &amp; Grammar Issues</h4>
                                <div class="ielts-issues-list">
                                    <?php foreach ($ai['spelling_grammar_issues'] as $issue): ?>
                                    <div class="ielts-issue-item">
                                        <span class="ielts-issue-error"><?php echo esc_html($issue['error']); ?></span>
                                        <span class="ielts-issue-arrow">→</span>
                                        <span class="ielts-issue-correction"><?php echo esc_html($issue['correction']); ?></span>
                                        <span class="ielts-issue-explanation"><?php echo esc_html($issue['explanation']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="ielts-feedback-columns">
                                <?php if (!empty($ai['strengths'])): ?>
                                <div class="ielts-strengths">
                                    <h4>✅ Strengths</h4>
                                    <ul><?php foreach ($ai['strengths'] as $str): ?><li><?php echo esc_html($str); ?></li><?php endforeach; ?></ul>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($ai['areas_for_improvement'])): ?>
                                <div class="ielts-improvements">
                                    <h4>📈 Areas for Improvement</h4>
                                    <ul><?php foreach ($ai['areas_for_improvement'] as $imp): ?><li><?php echo esc_html($imp); ?></li><?php endforeach; ?></ul>
                                </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$show_all && $total > 5): ?>
        <div class="ielts-history-show-all">
            <button id="ielts-show-all-history" class="ielts-writing-btn ielts-writing-btn--ghost">
                Show all <?php echo intval($total); ?> submissions
            </button>
        </div>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
    /**
     * Get next available submission time for user (null if can submit now)
     */
    public function get_next_submission_time($user_id) {
        // Admins are never limited
        if (user_can($user_id, 'manage_options')) return null;

        global $wpdb;
        $last = $wpdb->get_var($wpdb->prepare(
            "SELECT submitted_at FROM {$this->table_name} WHERE user_id = %d ORDER BY submitted_at DESC LIMIT 1",
            $user_id
        ));

        if (!$last) return null;

        // Use strtotime with get_option('timezone_string') to ensure consistent timezone handling
        $last_ts = strtotime($last . ' ' . wp_timezone_string());
        $next_ts = $last_ts + self::SUBMISSION_INTERVAL;

        return (time() < $next_ts) ? $next_ts : null;
    }

    /**
     * Notify admin of a dispute
     */
    private function notify_admin_of_dispute($submission_id, $user_id, $reason) {
        $admin_email = get_option('admin_email');
        $user        = get_userdata($user_id);
        $admin_url   = admin_url('admin.php?page=ielts-writing-submissions&action=view&id=' . $submission_id);

        $subject = '[IELTS Writing] Score dispute submitted by ' . $user->display_name;
        $message = "A student has disputed their writing assessment score.\n\n"
                 . "Student: {$user->display_name} ({$user->user_email})\n"
                 . "Submission ID: {$submission_id}\n"
                 . "Reason: " . ($reason ?: 'No reason given') . "\n\n"
                 . "Review here: {$admin_url}";

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Helper: CSS class based on band score
     */
    public function band_class($score) {
        if ($score >= 7.5) return 'band-high';
        if ($score >= 6.0) return 'band-mid';
        if ($score >= 4.5) return 'band-low';
        return 'band-very-low';
    }

    /**
     * Helper: Human-readable task type
     */
    public function task_type_label($task_type) {
        $labels = array(
            'task2'          => 'Task 2 Essay',
            'task1_academic' => 'Task 1 Academic',
            'task1_general'  => 'Task 1 General',
        );
        return $labels[$task_type] ?? $task_type;
    }

    /**
     * Create table only if it doesn't already exist
     */
    private function maybe_create_table() {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") !== $this->table_name) {
            self::create_table();
        }
    }

    /**
     * Create the database table
     */
    public static function create_table() {
        global $wpdb;
        $table_name      = $wpdb->prefix . 'ielts_cm_writing_submissions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            task_type varchar(30) NOT NULL DEFAULT 'task2',
            task_prompt longtext NOT NULL,
            essay_text longtext NOT NULL,
            score_task_achievement decimal(3,1) DEFAULT 0,
            score_coherence decimal(3,1) DEFAULT 0,
            score_lexical decimal(3,1) DEFAULT 0,
            score_grammar decimal(3,1) DEFAULT 0,
            overall_band decimal(3,1) DEFAULT 0,
            ai_feedback longtext,
            status varchar(30) DEFAULT 'auto_scored',
            dispute_reason text,
            disputed_at datetime DEFAULT NULL,
            admin_override_scores longtext DEFAULT NULL,
            admin_notes text DEFAULT NULL,
            admin_feedback text DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            reviewed_by bigint(20) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY submitted_at (submitted_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
