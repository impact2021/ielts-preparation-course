<?php
/**
 * IELTS Speaking Assessment — Part 1
 * Auto-record, 30s countdown, TTS questions, mic check, Whisper transcription, Claude assessment
 * Shortcode: [ielts_speaking_part1]
 */

if (!defined('ABSPATH')) { exit; }

class IELTS_CM_Speaking_Assessment {

    public function init() {
        add_shortcode('ielts_speaking_part1', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 10);
        add_action('wp_enqueue_scripts', array($this, 'localize_exercise_data'), 20);
        add_action('wp_ajax_ielts_cm_speaking_transcribe',    array($this, 'ajax_transcribe'));
        add_action('wp_ajax_ielts_cm_speaking_assess',        array($this, 'ajax_assess'));
        add_action('wp_ajax_ielts_cm_speaking_assess_full',   array($this, 'ajax_assess_full'));
        add_action('wp_ajax_ielts_cm_speaking_next_question', array($this, 'ajax_next_question'));
        add_action('wp_ajax_ielts_cm_save_speaking_score',    array($this, 'ajax_save_speaking_score'));
    }

    public function enqueue_assets() {
        $v = '3.0';
        wp_enqueue_style('ielts-speaking', IELTS_CM_PLUGIN_URL . 'assets/css/speaking.css', array(), $v);

        // Enqueue standalone shortcode JS
        wp_enqueue_script('ielts-speaking', IELTS_CM_PLUGIN_URL . 'assets/js/speaking.js', array('jquery'), $v, true);
        wp_localize_script('ielts-speaking', 'ieltsSpeaking', array(
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'nonce'          => wp_create_nonce('ielts_speaking_nonce'),
            'progressColor'  => get_option('ielts_cm_vocab_header_color', '#E56C0A'),
            'hasOpenAI'      => !empty(get_option('ielts_cm_openai_api_key', '')),
            'examinerGender' => get_option('ielts_cm_examiner_gender', 'female'),
        ));

        // Enqueue exercise JS — JS guard prevents it running on non-exercise pages
        wp_enqueue_script('ielts-speaking-exercise', IELTS_CM_PLUGIN_URL . 'assets/js/speaking-exercise.js', array('jquery'), $v, true);
    }

    public function localize_exercise_data() {
        if (!is_singular('ielts_quiz')) return;

        global $post;
        $questions = get_post_meta($post->ID, '_ielts_cm_questions', true);
        if (!is_array($questions)) return;

        $speaking_q = null;
        foreach ($questions as $q) {
            if (isset($q['type']) && $q['type'] === 'speaking_test') { $speaking_q = $q; break; }
        }
        if (!$speaking_q) return;

        $course_id  = intval(get_post_meta($post->ID, '_ielts_cm_course_id', true));
        $lesson_id  = intval(get_post_meta($post->ID, '_ielts_cm_lesson_id', true));
        $p1         = isset($speaking_q['speaking_p1_questions']) ? array_values(array_filter($speaking_q['speaking_p1_questions'])) : array();
        $p2         = isset($speaking_q['speaking_p2_cuecard'])   ? $speaking_q['speaking_p2_cuecard']   : '';
        $p3         = isset($speaking_q['speaking_p3_questions']) ? array_values(array_filter($speaking_q['speaking_p3_questions'])) : array();

        // Calculate next URL
        $next_url = '';
        if ($lesson_id) {
            global $wpdb;
            $int_pat  = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
            $str_pat  = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
            $quiz_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
                 WHERE (meta_key='_ielts_cm_lesson_id' AND meta_value=%d)
                    OR (meta_key='_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))",
                $lesson_id, $int_pat, $str_pat
            ));
            if (!empty($quiz_ids)) {
                $quizzes = get_posts(array('post_type'=>'ielts_quiz','posts_per_page'=>-1,'post__in'=>$quiz_ids,'orderby'=>'menu_order','order'=>'ASC','post_status'=>'publish'));
                usort($quizzes, function($a,$b){ return $a->menu_order - $b->menu_order; });
                foreach ($quizzes as $idx => $qp) {
                    if ($qp->ID === $post->ID && isset($quizzes[$idx + 1])) {
                        $next_url = get_permalink($quizzes[$idx + 1]->ID) . '?lesson_id=' . $lesson_id . '&course_id=' . $course_id;
                        break;
                    }
                }
            }
        }

        wp_localize_script('ielts-speaking-exercise', 'ieltsSpeakingExercise', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('ielts_speaking_nonce'),
            'quizId'        => $post->ID,
            'courseId'      => $course_id,
            'lessonId'      => $lesson_id,
            'nextUrl'       => $next_url,
            'progressColor' => get_option('ielts_cm_vocab_header_color', '#E56C0A'),
            'hasOpenAI'     => !empty(get_option('ielts_cm_openai_api_key', '')),
            'p1Questions'   => $p1,
            'p2Cuecard'     => $p2,
            'p3Questions'   => $p3,
        ));
    }

    public function render_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">log in</a> to use the Speaking practice tool.</p>';
        }
        $has_openai = !empty(get_option('ielts_cm_openai_api_key', ''));
        ob_start();
        ?>
        <div class="ielts-speaking-wrap" id="ielts-speaking-app">

            <!-- Header -->
            <div class="ielts-speaking-header">
                <div class="ielts-speaking-avatar">E</div>
                <div class="ielts-speaking-header-text">
                    <h3>IELTS Speaking &mdash; Part 1</h3>
                    <p>The examiner will introduce herself, then ask you six questions. Recording starts automatically after each question. You have 30 seconds per answer.</p>
                </div>
                <span class="ielts-speaking-badge <?php echo $has_openai ? '' : 'warn'; ?>"><?php echo $has_openai ? 'ready' : 'OpenAI key missing'; ?></span>
            </div>

            <!-- Mic check -->
            <div id="ielts-mic-check" class="ielts-mic-check-panel">
                <h4>Microphone check</h4>
                <p>Before we begin, let&rsquo;s make sure your microphone is working. Click <strong>Record test</strong>, say a few words, then play it back.</p>
                <div class="ielts-mic-check-actions">
                    <button class="ielts-speaking-rec-btn" id="ielts-mic-start">
                        <span class="ielts-rec-dot"></span> Record test (5 seconds)
                    </button>
                    <button class="ielts-speaking-send-btn" id="ielts-mic-play" disabled>Play back</button>
                    <button class="ielts-speaking-send-btn ielts-speaking-confirm-btn" id="ielts-mic-confirm" disabled>Confirm &amp; start test</button>
                </div>
                <div class="ielts-speaking-status" id="ielts-mic-status"></div>
            </div>

            <!-- Interview -->
            <div id="ielts-speaking-interview" style="display:none;">
                <div class="ielts-speaking-progress" id="ielts-speaking-progress"></div>
                <div class="ielts-speaking-chat">
                    <div class="ielts-speaking-messages" id="ielts-speaking-messages"></div>
                    <div class="ielts-speaking-controls">
                        <!-- Countdown bar -->
                        <div class="ielts-countdown-wrap">
                            <div class="ielts-countdown-bar-track">
                                <div class="ielts-countdown-bar-fill" id="ielts-countdown-bar"></div>
                            </div>
                            <span class="ielts-countdown-num" id="ielts-countdown"></span>
                        </div>
                        <!-- Status + finish -->
                        <div class="ielts-speaking-ctrl-row">
                            <div class="ielts-speaking-status" id="ielts-speak-status"></div>
                            <button class="ielts-speaking-send-btn ielts-finish-btn" id="ielts-finish-btn" disabled>I&rsquo;ve finished</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div id="ielts-speaking-results" style="display:none;"></div>

        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_transcribe() {
        check_ajax_referer('ielts_speaking_nonce', 'nonce');
        if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Please log in.')); }

        $openai_key = get_option('ielts_cm_openai_api_key', '');
        if (empty($openai_key)) { wp_send_json_error(array('message' => 'OpenAI API key not configured.')); }
        if (empty($_FILES['audio'])) { wp_send_json_error(array('message' => 'No audio received.')); }

        $tmp_path  = $_FILES['audio']['tmp_name'];
        $mime_type = $_FILES['audio']['type'] ?: 'audio/webm';
        $ext_map   = array('audio/webm'=>'webm','audio/ogg'=>'ogg','audio/mp4'=>'mp4','audio/mpeg'=>'mp3','audio/wav'=>'wav','video/webm'=>'webm');
        $ext       = $ext_map[$mime_type] ?? 'webm';

        $boundary = '----WP' . md5(microtime());
        $body  = '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="file"; filename="audio.' . $ext . '"' . "\r\n";
        $body .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
        $body .= file_get_contents($tmp_path) . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="model"' . "\r\n\r\nwhisper-1\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="language"' . "\r\n\r\nen\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="response_format"' . "\r\n\r\nverbose_json\r\n";
        $body .= '--' . $boundary . '--' . "\r\n";

        $response = wp_remote_post('https://api.openai.com/v1/audio/transcriptions', array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $openai_key,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ),
            'body' => $body,
        ));

        if (is_wp_error($response)) { wp_send_json_error(array('message' => 'Transcription failed: ' . $response->get_error_message())); }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['text'])) { wp_send_json_error(array('message' => $data['error']['message'] ?? 'Empty transcription.')); }

        wp_send_json_success(array('transcript' => trim($data['text'])));
    }

    public function ajax_assess() {
        check_ajax_referer('ielts_speaking_nonce', 'nonce');
        if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Please log in.')); }

        $responses_raw = isset($_POST['responses']) ? $_POST['responses'] : array();
        if (empty($responses_raw) || !is_array($responses_raw)) { wp_send_json_error(array('message' => 'No responses received.')); }

        $parts = array();
        foreach ($responses_raw as $i => $r) {
            $q = sanitize_text_field($r['q'] ?? '');
            $a = sanitize_textarea_field($r['a'] ?? '');
            if ($q && $a) $parts[] = 'Q' . ($i + 1) . ': ' . $q . "\nA" . ($i + 1) . ': ' . $a;
        }
        if (empty($parts)) { wp_send_json_error(array('message' => 'No valid responses.')); }

        $api_key = get_option('ielts_cm_anthropic_api_key', '');
        if (empty($api_key)) { wp_send_json_error(array('message' => 'Anthropic API key not configured.')); }

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ),
            'body' => json_encode(array(
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 1500,
                'system'     => $this->build_system_prompt(),
                'messages'   => array(
                    array('role' => 'user', 'content' => "Please assess this IELTS Speaking Part 1 interview:\n\n" . implode("\n\n", $parts)),
                ),
            )),
        ));

        if (is_wp_error($response)) { wp_send_json_error(array('message' => 'Assessment failed: ' . $response->get_error_message())); }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['content'][0]['text'])) { wp_send_json_error(array('message' => 'Empty assessment response.')); }

        $text       = preg_replace('/```json|```/', '', $body['content'][0]['text']);
        $assessment = json_decode(trim($text), true);
        if (!$assessment) { wp_send_json_error(array('message' => 'Could not parse assessment.')); }

        wp_send_json_success(array('html' => $this->render_results($assessment), 'assessment' => $assessment));
    }

    private function build_system_prompt() {
        return 'You are an IELTS examiner giving feedback DIRECTLY to the candidate. Use "you" and "your" throughout. Never use third person ("the candidate", "they", "their").

Write at a level the candidate can understand. A Band 2-3 candidate needs short simple sentences and plain English. A Band 7-8 candidate can handle more detail. Always match your language to their band score.

SCORING: Band 1-2=almost no communication, Band 3=very limited isolated words/phrases, Band 4=basic meaning conveyed with difficulty, Band 5=manages but limited range, Band 6=generally effective with limitations, Band 7=good range some imprecision, Band 8=wide range rare errors, Band 9=expert. Overall = average of fluency/lexical/grammar to nearest 0.5. Do NOT inflate scores.

Each answer was limited to 30 seconds — brevity is expected, do not penalise it. Transcripts from Whisper STT — filler words and false starts are fluency evidence.

For low band scores (under 5): be encouraging, specific, and constructive. Never use academic or clinical language. Never say the candidate "failed to" or "yielded no" anything.

Return ONLY valid JSON:
{
  "overall_band": 3.0,
  "fluency_score": 3.0,
  "fluency_feedback": "Two short sentences to candidate using you/your.",
  "fluency_why_not_higher": "One specific thing from transcript. Omit if Band 9.",
  "lexical_score": 3.0,
  "lexical_feedback": "Two short sentences to candidate using you/your.",
  "lexical_why_not_higher": "One specific thing from transcript. Omit if Band 9.",
  "grammar_score": 3.0,
  "grammar_feedback": "Two short sentences to candidate using you/your.",
  "grammar_why_not_higher": "One specific thing from transcript. Omit if Band 9.",
  "pronunciation_note": "Brief honest note to candidate — cannot be fully assessed from text.",
  "strengths": ["Something positive addressed to candidate", "Another"],
  "improvements": ["One simple actionable tip in plain English", "Another"],
  "examiner_note": "1-2 warm encouraging sentences appropriate to their level."
}';
    }

    private function render_results($r) {
        $overall  = $r['overall_band'] ?? 0;
        $criteria = array(
            array('name'=>'Fluency & Coherence',         'score'=>$r['fluency_score']??null, 'note'=>$r['fluency_feedback']??"", 'why'=>$r['fluency_why_not_higher']??""),
            array('name'=>'Lexical Resource',             'score'=>$r['lexical_score']??null,  'note'=>$r['lexical_feedback']??"",  'why'=>$r['lexical_why_not_higher']??""),
            array('name'=>'Grammatical Range & Accuracy', 'score'=>$r['grammar_score']??null,  'note'=>$r['grammar_feedback']??"",  'why'=>$r['grammar_why_not_higher']??""),
            array('name'=>'Pronunciation',                'score'=>null,                        'note'=>$r['pronunciation_note']??"", 'why'=>""),
        );
        ob_start();
        ?>
        <div class="ielts-speaking-results-card">
            <div class="ielts-speaking-res-header">
                <div class="ielts-speaking-res-band-col">
                    <span class="ielts-speaking-band-label">Speaking Band</span>
                    <span class="ielts-speaking-band-score"><?php echo esc_html(number_format($overall, 1)); ?></span>
                    <span class="ielts-speaking-band-sub">Part 1</span>
                </div>
                <div class="ielts-speaking-res-summary-col">
                    <p class="ielts-speaking-examiner-note"><?php echo esc_html($r['examiner_note'] ?? ''); ?></p>
                </div>
            </div>
            <div class="ielts-speaking-criteria">
                <?php foreach ($criteria as $c): ?>
                <div class="ielts-speaking-criterion">
                    <div class="ielts-speaking-crit-header">
                        <span class="ielts-speaking-crit-name"><?php echo esc_html($c['name']); ?></span>
                        <?php if ($c['score'] !== null): ?>
                        <span class="ielts-speaking-crit-score"><?php echo esc_html(number_format($c['score'], 1)); ?></span>
                        <?php else: ?>
                        <span class="ielts-speaking-crit-na">N/A</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($c['score'] !== null): ?>
                    <div class="ielts-speaking-bar"><div class="ielts-speaking-bar-fill" style="width:<?php echo esc_attr(round(($c['score']/9)*100)); ?>%"></div></div>
                    <?php endif; ?>
                    <p class="ielts-speaking-crit-note"><?php echo esc_html($c['note']); ?></p>
                    <?php if (!empty($c['why'])): ?>
                    <p class="ielts-speaking-why-not"><?php echo esc_html($c['why']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($r['strengths'])): ?>
            <div class="ielts-speaking-fb-section">
                <h4>&#10003; Strengths</h4>
                <ul><?php foreach ($r['strengths'] as $s): ?><li><?php echo esc_html($s); ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
            <?php if (!empty($r['improvements'])): ?>
            <div class="ielts-speaking-fb-section">
                <h4>Areas to develop</h4>
                <ul><?php foreach ($r['improvements'] as $s): ?><li><?php echo esc_html($s); ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    public function ajax_assess_full() {
        check_ajax_referer('ielts_speaking_nonce', 'nonce');
        if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Please log in.')); }

        $raw = isset($_POST['responses']) ? $_POST['responses'] : array();
        if (empty($raw) || !is_array($raw)) { wp_send_json_error(array('message' => 'No responses.')); }

        $api_key = get_option('ielts_cm_anthropic_api_key', '');
        if (empty($api_key)) { wp_send_json_error(array('message' => 'Anthropic API key not configured.')); }

        $parts_labels = array(1 => 'Part 1 - Interview:', 2 => 'Part 2 - Individual Long Turn:', 3 => 'Part 3 - Discussion:');
        $current_part = 0;
        $transcript   = '';

        foreach ($raw as $r) {
            $part = intval($r['part'] ?? 1);
            $q    = sanitize_text_field($r['question'] ?? '');
            $a    = sanitize_textarea_field($r['answer'] ?? '');
            if ($part !== $current_part) {
                $current_part = $part;
                $transcript .= "\n" . ($parts_labels[$part] ?? "Part $part:") . "\n";
            }
            $transcript .= 'Q: ' . $q . "\nA: " . $a . "\n\n";
        }

        $system = 'You are an IELTS examiner giving feedback DIRECTLY to the candidate. Use "you" and "your" throughout — never third person ("the candidate", "they", "their"). Write at a level the candidate can understand — match language complexity to their band score. Band 2-3 candidates need short plain English sentences, not academic or clinical language. SCORING: Band 1-2=almost no communication, Band 3=very limited, Band 4=basic, Band 5=modest limited, Band 6=generally effective, Band 7=good some imprecision, Band 8=wide range, Band 9=expert. Overall=average of fluency/lexical/grammar to nearest 0.5. Do NOT inflate scores. For low bands never write "failed to", "yielded no", or clinical assessments — be encouraging and constructive. Return ONLY valid JSON: {"overall_band":3.0,"fluency_score":3.0,"fluency_feedback":"Two sentences to candidate using you/your.","fluency_why_not_higher":"Specific from transcript. Omit if Band 9.","lexical_score":3.0,"lexical_feedback":"Two sentences to candidate using you/your.","lexical_why_not_higher":"Specific from transcript. Omit if Band 9.","grammar_score":3.0,"grammar_feedback":"Two sentences to candidate using you/your.","grammar_why_not_higher":"Specific from transcript. Omit if Band 9.","pronunciation_note":"Brief honest note to candidate.","part1_comment":"One sentence to candidate.","part2_comment":"One sentence to candidate.","part3_comment":"One sentence to candidate.","strengths":["Positive addressed to candidate","Another"],"improvements":["Simple actionable tip in plain English","Another"],"examiner_note":"1-2 warm encouraging sentences appropriate to their band level."}';

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 90,
            'headers' => array('Content-Type'=>'application/json','x-api-key'=>$api_key,'anthropic-version'=>'2023-06-01'),
            'body' => json_encode(array('model'=>'claude-sonnet-4-6','max_tokens'=>2000,'system'=>$system,'messages'=>array(array('role'=>'user','content'=>"Assess this IELTS Speaking test:\n\n".$transcript)))),
        ));

        if (is_wp_error($response)) { wp_send_json_error(array('message'=>'Assessment failed: '.$response->get_error_message())); }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['content'][0]['text'])) { wp_send_json_error(array('message'=>'Empty response.')); }
        $text = preg_replace('/```json|```/', '', $body['content'][0]['text']);
        $assessment = json_decode(trim($text), true);
        if (!$assessment) { wp_send_json_error(array('message'=>'Could not parse assessment.')); }
        wp_send_json_success(array('html'=>$this->render_full_results($assessment),'assessment'=>$assessment));
    }

    public function ajax_next_question() {
        check_ajax_referer('ielts_speaking_nonce', 'nonce');
        if (!is_user_logged_in()) { wp_send_json_error(); }
        $context = sanitize_textarea_field($_POST['context'] ?? '');
        if (empty($context)) { wp_send_json_error(); }
        $api_key = get_option('ielts_cm_anthropic_api_key', '');
        if (empty($api_key)) { wp_send_json_error(); }
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 20,
            'headers' => array('Content-Type'=>'application/json','x-api-key'=>$api_key,'anthropic-version'=>'2023-06-01'),
            'body' => json_encode(array('model'=>'claude-sonnet-4-6','max_tokens'=>80,'system'=>'You are an IELTS examiner in Part 3. Generate one natural follow-up question based on the context. Probe deeper or explore a related angle. Return ONLY the question, nothing else.','messages'=>array(array('role'=>'user','content'=>$context)))),
        ));
        if (is_wp_error($response)) { wp_send_json_error(); }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $q = trim($body['content'][0]['text'] ?? '');
        if (empty($q)) { wp_send_json_error(); }
        wp_send_json_success(array('question'=>$q));
    }

    public function ajax_save_speaking_score() {
        check_ajax_referer('ielts_speaking_nonce', 'nonce');
        $user_id   = get_current_user_id();
        $quiz_id   = intval($_POST['quiz_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $lesson_id = intval($_POST['lesson_id'] ?? 0);
        $band      = floatval($_POST['band_score'] ?? 0);
        if (!$user_id || !$quiz_id) { wp_send_json_error(array('message'=>'Invalid request.')); }
        $map = array('9.0'=>97,'8.5'=>92,'8.0'=>87,'7.5'=>82,'7.0'=>73,'6.5'=>67,'6.0'=>62,'5.5'=>57,'5.0'=>52,'4.5'=>47,'4.0'=>42,'3.5'=>37,'3.0'=>32,'2.5'=>27,'2.0'=>22,'1.5'=>17,'1.0'=>12);
        $pct = isset($map[number_format($band,1)]) ? $map[number_format($band,1)] : round(($band/9)*100,1);
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'ielts_cm_quiz_results',array('user_id'=>$user_id,'quiz_id'=>$quiz_id,'course_id'=>$course_id,'lesson_id'=>$lesson_id?:null,'score'=>$band,'max_score'=>9,'percentage'=>$pct,'answers'=>json_encode(array('speaking_exercise'=>true,'band_score'=>$band)),'submitted_date'=>current_time('mysql')),array('%d','%d','%d','%d','%f','%f','%f','%s','%s'));
        do_action('ielts_cm_quiz_submitted',$user_id,$quiz_id,$pct,time());
        wp_send_json_success(array('message'=>'Score saved.'));
    }

    private function render_full_results($r) {
        $overall = $r['overall_band'] ?? 0;
        $criteria = array(
            array('name'=>'Fluency & Coherence',         'score'=>$r['fluency_score']??null,'note'=>$r['fluency_feedback']??"", 'why'=>$r['fluency_why_not_higher']??""),
            array('name'=>'Lexical Resource',             'score'=>$r['lexical_score']??null, 'note'=>$r['lexical_feedback']??"",  'why'=>$r['lexical_why_not_higher']??""),
            array('name'=>'Grammatical Range & Accuracy','score'=>$r['grammar_score']??null, 'note'=>$r['grammar_feedback']??"",  'why'=>$r['grammar_why_not_higher']??""),
            array('name'=>'Pronunciation',                'score'=>null,                       'note'=>$r['pronunciation_note']??"", 'why'=>""),
        );
        ob_start(); ?>
        <div class="ielts-speaking-results-card">
            <div class="ielts-speaking-res-header">
                <div class="ielts-speaking-res-band-col">
                    <span class="ielts-speaking-band-label">Speaking Band</span>
                    <span class="ielts-speaking-band-score"><?php echo esc_html(number_format($overall,1)); ?></span>
                    <span class="ielts-speaking-band-sub">Parts 1, 2 &amp; 3</span>
                </div>
                <div class="ielts-speaking-res-summary-col">
                    <p class="ielts-speaking-examiner-note"><?php echo esc_html($r['examiner_note']??''); ?></p>
                </div>
            </div>
            <?php if (!empty($r['part1_comment']) || !empty($r['part2_comment']) || !empty($r['part3_comment'])): ?>
            <div class="ielts-speaking-parts-summary">
                <?php if (!empty($r['part1_comment'])): ?><div class="ielts-speaking-part-note"><strong>Part 1:</strong> <?php echo esc_html($r['part1_comment']); ?></div><?php endif; ?>
                <?php if (!empty($r['part2_comment'])): ?><div class="ielts-speaking-part-note"><strong>Part 2:</strong> <?php echo esc_html($r['part2_comment']); ?></div><?php endif; ?>
                <?php if (!empty($r['part3_comment'])): ?><div class="ielts-speaking-part-note"><strong>Part 3:</strong> <?php echo esc_html($r['part3_comment']); ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="ielts-speaking-criteria">
                <?php foreach ($criteria as $c): ?>
                <div class="ielts-speaking-criterion">
                    <div class="ielts-speaking-crit-header">
                        <span class="ielts-speaking-crit-name"><?php echo esc_html($c['name']); ?></span>
                        <?php if ($c['score']!==null): ?><span class="ielts-speaking-crit-score"><?php echo esc_html(number_format($c['score'],1)); ?></span>
                        <?php else: ?><span class="ielts-speaking-crit-na">N/A</span><?php endif; ?>
                    </div>
                    <?php if ($c['score']!==null): ?><div class="ielts-speaking-bar"><div class="ielts-speaking-bar-fill" style="width:<?php echo esc_attr(round(($c['score']/9)*100)); ?>%"></div></div><?php endif; ?>
                    <p class="ielts-speaking-crit-note"><?php echo esc_html($c['note']); ?></p>
                    <?php if (!empty($c['why'])): ?><p class="ielts-speaking-why-not"><?php echo esc_html($c['why']); ?></p><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($r['strengths'])): ?>
            <div class="ielts-speaking-fb-section"><h4>&#10003; Strengths</h4><ul><?php foreach($r['strengths'] as $s): ?><li><?php echo esc_html($s); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if (!empty($r['improvements'])): ?>
            <div class="ielts-speaking-fb-section"><h4>Areas to develop</h4><ul><?php foreach($r['improvements'] as $s): ?><li><?php echo esc_html($s); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
        </div>
        <?php return ob_get_clean();
    }

}
