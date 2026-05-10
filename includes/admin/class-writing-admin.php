<?php
/**
 * Admin functionality for IELTS Writing Assessment
 * Handles submissions list, dispute management, score overrides and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Writing_Admin {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ielts_cm_writing_submissions';
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_ielts_cm_writing_override', array($this, 'ajax_save_override'));
        add_action('wp_ajax_ielts_cm_writing_reset_limit', array($this, 'ajax_reset_user_limit'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin submenu pages
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Writing Submissions', 'ielts-course-manager'),
            __('Writing Submissions', 'ielts-course-manager'),
            'manage_options',
            'ielts-writing-submissions',
            array($this, 'submissions_page')
        );

        add_submenu_page(
            'edit.php?post_type=ielts_course',
            __('Writing Assessment Settings', 'ielts-course-manager'),
            __('Writing Settings', 'ielts-course-manager'),
            'manage_options',
            'ielts-writing-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Enqueue admin assets only on our pages
     */
    public function enqueue_admin_assets($hook) {
        $our_pages = array(
            'ielts_course_page_ielts-writing-submissions',
            'ielts_course_page_ielts-writing-settings',
        );
        if (!in_array($hook, $our_pages)) return;

        wp_enqueue_style(
            'ielts-writing-admin',
            IELTS_CM_PLUGIN_URL . 'assets/css/writing-admin.css',
            array(),
            IELTS_CM_VERSION
        );

        wp_enqueue_script(
            'ielts-writing-admin',
            IELTS_CM_PLUGIN_URL . 'assets/js/writing-admin.js',
            array('jquery'),
            IELTS_CM_VERSION,
            true
        );

        wp_localize_script('ielts-writing-admin', 'ielts_writing_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ielts_writing_admin_nonce'),
        ));
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('ielts_writing_settings', 'ielts_cm_anthropic_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('ielts_writing_settings', 'ielts_cm_openai_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('ielts_writing_settings', 'ielts_cm_examiner_gender', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'female',
        ));
        register_setting('ielts_writing_settings', 'ielts_cm_writing_strictness', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'balanced',
        ));
        register_setting('ielts_writing_settings', 'ielts_cm_writing_custom_instructions', array(
            'sanitize_callback' => 'sanitize_textarea_field',
        ));
    }

    /**
     * Submissions list/detail page
     */
    public function submissions_page() {
        $action = sanitize_text_field($_GET['action'] ?? 'list');
        $id     = intval($_GET['id'] ?? 0);

        if ($action === 'view' && $id) {
            $this->render_submission_detail($id);
        } else {
            $this->render_submissions_list();
        }
    }

    /**
     * Render the submissions list
     */
    private function render_submissions_list() {
        global $wpdb;

        $status_filter = sanitize_text_field($_GET['status'] ?? '');
        $where         = $status_filter ? $wpdb->prepare("WHERE s.status = %s", $status_filter) : '';

        $submissions = $wpdb->get_results(
            "SELECT s.*, u.display_name, u.user_email
             FROM {$this->table_name} s
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
             {$where}
             ORDER BY
               CASE WHEN s.status = 'disputed' THEN 0 ELSE 1 END,
               s.submitted_at DESC
             LIMIT 100"
        );

        $disputed_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'disputed'"
        );
        ?>
        <div class="wrap ielts-writing-admin-wrap">
            <h1 class="wp-heading-inline">Writing Assessment Submissions</h1>

            <?php if ($disputed_count > 0): ?>
            <div class="notice notice-warning inline">
                <p><strong><?php echo intval($disputed_count); ?> disputed submission(s)</strong> require your review.</p>
            </div>
            <?php endif; ?>

            <div class="ielts-admin-filters">
                <a href="?post_type=ielts_course&page=ielts-writing-submissions" class="button <?php echo !$status_filter ? 'button-primary' : ''; ?>">All</a>
                <a href="?post_type=ielts_course&page=ielts-writing-submissions&status=disputed" class="button <?php echo $status_filter === 'disputed' ? 'button-primary' : ''; ?>">
                    Disputed <?php if ($disputed_count > 0): ?><span class="ielts-badge"><?php echo intval($disputed_count); ?></span><?php endif; ?>
                </a>
                <a href="?post_type=ielts_course&page=ielts-writing-submissions&status=manually_reviewed" class="button <?php echo $status_filter === 'manually_reviewed' ? 'button-primary' : ''; ?>">Reviewed</a>
                <a href="?post_type=ielts_course&page=ielts-writing-submissions&status=auto_scored" class="button <?php echo $status_filter === 'auto_scored' ? 'button-primary' : ''; ?>">Auto-scored</a>
            </div>

            <table class="wp-list-table widefat fixed striped ielts-submissions-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Task Type</th>
                        <th>Overall Band</th>
                        <th>TA</th>
                        <th>CC</th>
                        <th>LR</th>
                        <th>GRA</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="10">No submissions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $s):
                        $override = !empty($s->admin_override_scores) ? json_decode($s->admin_override_scores, true) : null;
                        $band = $override ? ($override['overall_band'] ?? $s->overall_band) : $s->overall_band;
                        $ta   = $override ? ($override['score_task_achievement'] ?? $s->score_task_achievement) : $s->score_task_achievement;
                        $cc   = $override ? ($override['score_coherence'] ?? $s->score_coherence) : $s->score_coherence;
                        $lr   = $override ? ($override['score_lexical'] ?? $s->score_lexical) : $s->score_lexical;
                        $gra  = $override ? ($override['score_grammar'] ?? $s->score_grammar) : $s->score_grammar;
                        $row_class = $s->status === 'disputed' ? 'ielts-row-disputed' : '';
                    ?>
                    <tr class="<?php echo esc_attr($row_class); ?>">
                        <td>
                            <strong><?php echo esc_html($s->display_name); ?></strong><br>
                            <small><?php echo esc_html($s->user_email); ?></small>
                        </td>
                        <td><?php echo esc_html(date('d M Y H:i', strtotime($s->submitted_at))); ?></td>
                        <td><?php echo esc_html($this->task_type_label($s->task_type)); ?></td>
                        <td><strong><?php echo esc_html($band); ?></strong><?php echo $override ? ' <span class="ielts-override-indicator" title="Manually overridden">✏️</span>' : ''; ?></td>
                        <td><?php echo esc_html($ta); ?></td>
                        <td><?php echo esc_html($cc); ?></td>
                        <td><?php echo esc_html($lr); ?></td>
                        <td><?php echo esc_html($gra); ?></td>
                        <td><?php echo $this->status_badge($s->status); ?></td>
                        <td>
                            <a href="?post_type=ielts_course&page=ielts-writing-submissions&action=view&id=<?php echo intval($s->id); ?>" class="button button-small">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render submission detail/override page
     */
    private function render_submission_detail($id) {
        global $wpdb;

        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.display_name, u.user_email
             FROM {$this->table_name} s
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
             WHERE s.id = %d",
            $id
        ));

        if (!$submission) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Submission not found.</p></div></div>';
            return;
        }

        $ai_feedback = json_decode($submission->ai_feedback, true) ?? array();
        $override    = !empty($submission->admin_override_scores) ? json_decode($submission->admin_override_scores, true) : null;

        $display_ta  = $override ? ($override['score_task_achievement'] ?? $submission->score_task_achievement) : $submission->score_task_achievement;
        $display_cc  = $override ? ($override['score_coherence'] ?? $submission->score_coherence) : $submission->score_coherence;
        $display_lr  = $override ? ($override['score_lexical'] ?? $submission->score_lexical) : $submission->score_lexical;
        $display_gra = $override ? ($override['score_grammar'] ?? $submission->score_grammar) : $submission->score_grammar;
        $display_band= $override ? ($override['overall_band'] ?? $submission->overall_band) : $submission->overall_band;

        $band_options = array(1,1.5,2,2.5,3,3.5,4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9);
        ?>
        <div class="wrap ielts-writing-admin-wrap">
            <h1>
                <a href="?post_type=ielts_course&page=ielts-writing-submissions" class="ielts-back-link">← Writing Submissions</a>
                Submission #<?php echo intval($id); ?> — <?php echo esc_html($submission->display_name); ?>
            </h1>

            <div class="ielts-detail-grid">

                <!-- Left: Essay + Prompt -->
                <div class="ielts-detail-essay">
                    <div class="ielts-detail-meta">
                        <span><strong>Student:</strong> <?php echo esc_html($submission->display_name); ?> (<?php echo esc_html($submission->user_email); ?>)</span>
                        <span><strong>Submitted:</strong> <?php echo esc_html(date('d M Y H:i', strtotime($submission->submitted_at))); ?></span>
                        <span><strong>Task Type:</strong> <?php echo esc_html($this->task_type_label($submission->task_type)); ?></span>
                        <span><strong>Status:</strong> <?php echo $this->status_badge($submission->status); ?></span>
                    </div>

                    <?php if ($submission->status === 'disputed' && !empty($submission->dispute_reason)): ?>
                    <div class="ielts-dispute-notice">
                        <strong>⚠ Student's dispute reason:</strong>
                        <p><?php echo esc_html($submission->dispute_reason); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="ielts-detail-box">
                        <h3>Task Prompt</h3>
                        <div class="ielts-essay-text"><?php echo nl2br(esc_html($submission->task_prompt)); ?></div>
                    </div>

                    <div class="ielts-detail-box">
                        <h3>Student's Essay</h3>
                        <div class="ielts-essay-text"><?php echo nl2br(esc_html($submission->essay_text)); ?></div>
                    </div>
                </div>

                <!-- Right: Scores + Override -->
                <div class="ielts-detail-sidebar">

                    <!-- Current Scores -->
                    <div class="ielts-detail-box">
                        <h3>AI Scores <?php echo $override ? '<span class="ielts-override-indicator">(overridden ✏️)</span>' : ''; ?></h3>
                        <table class="ielts-scores-table">
                            <tr><td>Overall Band</td><td><strong><?php echo esc_html($display_band); ?></strong></td></tr>
                            <tr><td>Task Achievement</td><td><?php echo esc_html($display_ta); ?></td></tr>
                            <tr><td>Coherence &amp; Cohesion</td><td><?php echo esc_html($display_cc); ?></td></tr>
                            <tr><td>Lexical Resource</td><td><?php echo esc_html($display_lr); ?></td></tr>
                            <tr><td>Grammatical Range &amp; Accuracy</td><td><?php echo esc_html($display_gra); ?></td></tr>
                        </table>
                    </div>

                    <!-- AI Feedback Summary -->
                    <?php if (!empty($ai_feedback['summary'])): ?>
                    <div class="ielts-detail-box">
                        <h3>AI Summary</h3>
                        <p><?php echo esc_html($ai_feedback['summary']); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Override Form -->
                    <div class="ielts-detail-box ielts-override-box">
                        <h3>Score Override</h3>
                        <p class="description">Adjust scores below. These will replace the AI scores shown to the student.</p>

                        <div class="ielts-override-form" id="ielts-override-form" data-submission-id="<?php echo intval($id); ?>">
                            <?php
                            $score_fields = array(
                                'score_task_achievement' => array('label' => 'Task Achievement', 'current' => $display_ta, 'ai' => $submission->score_task_achievement),
                                'score_coherence'        => array('label' => 'Coherence & Cohesion', 'current' => $display_cc, 'ai' => $submission->score_coherence),
                                'score_lexical'          => array('label' => 'Lexical Resource', 'current' => $display_lr, 'ai' => $submission->score_lexical),
                                'score_grammar'          => array('label' => 'Grammatical Range & Accuracy', 'current' => $display_gra, 'ai' => $submission->score_grammar),
                            );
                            foreach ($score_fields as $key => $field): ?>
                            <div class="ielts-override-field">
                                <label><?php echo esc_html($field['label']); ?>
                                    <small>(AI: <?php echo esc_html($field['ai']); ?>)</small>
                                </label>
                                <select name="<?php echo esc_attr($key); ?>" class="ielts-score-select">
                                    <?php foreach ($band_options as $opt): ?>
                                    <option value="<?php echo esc_attr($opt); ?>" <?php selected($field['current'], $opt); ?>><?php echo esc_html($opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endforeach; ?>

                            <div class="ielts-override-field">
                                <label>Internal Notes <small>(admin only — not shown to student)</small></label>
                                <textarea name="admin_notes" rows="3" placeholder="Your internal notes about this submission..."><?php echo esc_textarea($submission->admin_notes ?? ''); ?></textarea>
                            </div>

                            <div class="ielts-override-field">
                                <label>Feedback to Student <small>(shown to student in their history)</small></label>
                                <textarea name="admin_feedback" rows="3" placeholder="Explanation to share with the student..."><?php echo esc_textarea($submission->admin_feedback ?? ''); ?></textarea>
                            </div>

                            <div class="ielts-override-actions">
                                <button id="ielts-save-override-btn" class="button button-primary">Save Override</button>
                                <?php if ($override): ?>
                                <button id="ielts-clear-override-btn" class="button">Clear Override (revert to AI scores)</button>
                                <?php endif; ?>
                            </div>

                            <div id="ielts-override-feedback" class="ielts-override-feedback" style="display:none;"></div>
                        </div>
                    </div>

                    <!-- Reset submission limit -->
                    <div class="ielts-detail-box">
                        <h3>Submission Limit</h3>
                        <p class="description">If the student had a technical error, you can reset their 24-hour window so they can resubmit.</p>
                        <button class="button ielts-reset-limit-btn" data-user-id="<?php echo intval($submission->user_id); ?>" data-submission-id="<?php echo intval($id); ?>">
                            Reset Submission Window for This Student
                        </button>
                        <div id="ielts-reset-feedback" style="display:none; margin-top:8px;"></div>
                    </div>

                    <!-- Delete submission -->
                    <div class="ielts-detail-box" style="border-color: #c0392b;">
                        <h3>Delete Submission</h3>
                        <p class="description">Permanently delete this submission and all associated data. This cannot be undone.</p>
                        <button class="button ielts-delete-submission-btn" data-submission-id="<?php echo intval($id); ?>" style="color:#c0392b; border-color:#c0392b;">
                            Delete This Submission
                        </button>
                        <div id="ielts-delete-feedback" style="display:none; margin-top:8px;"></div>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap ielts-writing-admin-wrap">
            <h1>Writing Assessment Settings</h1>

            <form method="post" action="options.php">
                <?php settings_fields('ielts_writing_settings'); ?>

                <div class="ielts-settings-section">
                    <h2>API Configuration</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="ielts_cm_anthropic_api_key">Anthropic API Key</label></th>
                            <td>
                                <input type="password" id="ielts_cm_anthropic_api_key" name="ielts_cm_anthropic_api_key"
                                    value="<?php echo esc_attr(get_option('ielts_cm_anthropic_api_key', '')); ?>"
                                    class="regular-text" autocomplete="new-password">
                                <p class="description">Your Anthropic API key. Get one at <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a>. Used for writing and speaking assessment.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ielts_cm_openai_api_key">OpenAI API Key</label></th>
                            <td>
                                <input type="password" id="ielts_cm_openai_api_key" name="ielts_cm_openai_api_key"
                                    value="<?php echo esc_attr(get_option('ielts_cm_openai_api_key', '')); ?>"
                                    class="regular-text" autocomplete="new-password">
                                <p class="description">Your OpenAI API key. Used for Whisper speech-to-text in Speaking assessment. Get one at <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>. Cost is approximately $0.006 per minute of audio — negligible for practice sessions.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Examiner Voice</label></th>
                            <td>
                                <?php $gender = get_option('ielts_cm_examiner_gender', 'female'); ?>
                                <label style="margin-right:20px;">
                                    <input type="radio" name="ielts_cm_examiner_gender" value="female" <?php checked($gender, 'female'); ?>>
                                    Female examiner
                                </label>
                                <label>
                                    <input type="radio" name="ielts_cm_examiner_gender" value="male" <?php checked($gender, 'male'); ?>>
                                    Male examiner
                                </label>
                                <p class="description">Sets the preferred voice gender for the speaking test examiner. Availability depends on the student's browser and operating system.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="ielts-settings-section">
                    <h2>Scoring Behaviour</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="ielts_cm_writing_strictness">Scoring Strictness</label></th>
                            <td>
                                <?php
                                $strictness = get_option('ielts_cm_writing_strictness', 'balanced');
                                $options = array(
                                    'lenient'  => 'Lenient — Benefit of the doubt on borderline scores',
                                    'balanced' => 'Balanced — Faithful to the official band descriptors (recommended)',
                                    'strict'   => 'Strict — Only award a band if it is clearly demonstrated',
                                    'examiner' => 'Examiner-level — Maximum rigour, cite specific evidence for every score',
                                );
                                foreach ($options as $val => $label): ?>
                                <label style="display:block; margin-bottom:6px;">
                                    <input type="radio" name="ielts_cm_writing_strictness" value="<?php echo esc_attr($val); ?>" <?php checked($strictness, $val); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                                <?php endforeach; ?>
                                <p class="description">This controls how strictly the AI applies the IELTS band descriptors. Start with Balanced and adjust based on your testing.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="ielts-settings-section">
                    <h2>Custom Scoring Instructions</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="ielts_cm_writing_custom_instructions">Additional Instructions</label></th>
                            <td>
                                <textarea id="ielts_cm_writing_custom_instructions" name="ielts_cm_writing_custom_instructions"
                                    rows="8" class="large-text"><?php echo esc_textarea(get_option('ielts_cm_writing_custom_instructions', '')); ?></textarea>
                                <p class="description">
                                    Add any additional guidance here to fine-tune the AI's scoring behaviour. Examples:<br>
                                    <em>"Always identify whether a topic sentence is present in each body paragraph."</em><br>
                                    <em>"Do not award Task Achievement Band 7 or above unless all parts of the task are explicitly and fully addressed."</em><br>
                                    <em>"Pay particular attention to the student's use of cohesive devices — flag overuse of 'however', 'furthermore' and 'in conclusion'."</em><br>
                                    <em>"You tend to score Lexical Resource too generously. Be stricter — only award Band 7 if a genuine range of less common vocabulary is used accurately."</em>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX: Save score override
     */
    public function ajax_save_override() {
        check_ajax_referer('ielts_writing_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorised.'));

        $submission_id = intval($_POST['submission_id'] ?? 0);
        $clear         = !empty($_POST['clear_override']);
        $admin_notes   = sanitize_textarea_field($_POST['admin_notes'] ?? '');
        $admin_feedback= sanitize_textarea_field($_POST['admin_feedback'] ?? '');

        global $wpdb;

        if ($clear) {
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'admin_override_scores' => null,
                    'admin_notes'           => $admin_notes,
                    'admin_feedback'        => $admin_feedback,
                    'status'                => 'auto_scored',
                    'reviewed_at'           => null,
                    'reviewed_by'           => null,
                ),
                array('id' => $submission_id),
                array('%s','%s','%s','%s','%s','%s'),
                array('%d')
            );
            wp_send_json_success(array('message' => 'Override cleared. AI scores restored.'));
        }

        $band_options = array(1,1.5,2,2.5,3,3.5,4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9);

        $ta  = in_array((float)$_POST['score_task_achievement'], $band_options) ? (float)$_POST['score_task_achievement'] : 0;
        $cc  = in_array((float)$_POST['score_coherence'], $band_options) ? (float)$_POST['score_coherence'] : 0;
        $lr  = in_array((float)$_POST['score_lexical'], $band_options) ? (float)$_POST['score_lexical'] : 0;
        $gra = in_array((float)$_POST['score_grammar'], $band_options) ? (float)$_POST['score_grammar'] : 0;

        // Calculate overall band
        $overall = round(($ta + $cc + $lr + $gra) / 4 * 2) / 2;

        $override_scores = json_encode(array(
            'score_task_achievement' => $ta,
            'score_coherence'        => $cc,
            'score_lexical'          => $lr,
            'score_grammar'          => $gra,
            'overall_band'           => $overall,
        ));

        $result = $wpdb->update(
            $this->table_name,
            array(
                'admin_override_scores' => $override_scores,
                'admin_notes'           => $admin_notes,
                'admin_feedback'        => $admin_feedback,
                'status'                => 'manually_reviewed',
                'reviewed_at'           => current_time('mysql'),
                'reviewed_by'           => get_current_user_id(),
            ),
            array('id' => $submission_id),
            array('%s','%s','%s','%s','%s','%d'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error. Please try again.'));
        }

        wp_send_json_success(array(
            'message'      => 'Override saved successfully.',
            'overall_band' => $overall,
        ));
    }

    /**
     * AJAX: Reset a user's 24-hour submission window
     */
    public function ajax_reset_user_limit() {
        check_ajax_referer('ielts_writing_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorised.'));

        $user_id       = intval($_POST['user_id'] ?? 0);
        $submission_id = intval($_POST['submission_id'] ?? 0);

        if (!$user_id || !$submission_id) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }

        global $wpdb;

        // Update the submission's submitted_at to 25 hours ago so the window has passed
        $new_time = date('Y-m-d H:i:s', time() - (25 * 3600));
        $wpdb->update(
            $this->table_name,
            array('submitted_at' => $new_time),
            array('id' => $submission_id),
            array('%s'),
            array('%d')
        );

        wp_send_json_success(array('message' => 'Submission window reset. The student can now submit again.'));
    }

    /**
     * Helper: Status badge HTML
     */
    private function status_badge($status) {
        $badges = array(
            'auto_scored'       => '<span class="ielts-status ielts-status--auto">Auto-scored</span>',
            'disputed'          => '<span class="ielts-status ielts-status--disputed">⚠ Disputed</span>',
            'manually_reviewed' => '<span class="ielts-status ielts-status--reviewed">✓ Reviewed</span>',
        );
        return $badges[$status] ?? esc_html($status);
    }

    /**
     * Helper: Task type label
     */
    private function task_type_label($task_type) {
        $labels = array(
            'task2'          => 'Task 2 Essay',
            'task1_academic' => 'Task 1 Academic',
            'task1_general'  => 'Task 1 General',
        );
        return $labels[$task_type] ?? $task_type;
    }
}
