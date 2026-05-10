<?php
/**
 * Template for IELTS Speaking Tests (Parts 1, 2 & 3)
 */

if (!defined('ABSPATH')) { exit; }
if (!is_array($questions)) $questions = array();

$speaking_question = null;
foreach ($questions as $q) {
    if (isset($q['type']) && $q['type'] === 'speaking_test') { $speaking_question = $q; break; }
}
if (!$speaking_question) { echo '<p>No speaking test configured.</p>'; return; }

$p1_questions = isset($speaking_question['speaking_p1_questions']) ? array_values(array_filter($speaking_question['speaking_p1_questions'])) : array();
$p2_cuecard   = isset($speaking_question['speaking_p2_cuecard'])   ? $speaking_question['speaking_p2_cuecard']                                : '';
$p3_questions = isset($speaking_question['speaking_p3_questions']) ? array_values(array_filter($speaking_question['speaking_p3_questions'])) : array();

// Nav URLs
$next_url = ''; $prev_url = ''; $next_title = ''; $prev_title = '';
$all_items = array(); $current_index = -1;
if ($lesson_id) {
    global $wpdb;
    $int_pat = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
    $str_pat = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';
    $quiz_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
         WHERE (meta_key='_ielts_cm_lesson_id' AND meta_value=%d)
            OR (meta_key='_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))",
        $lesson_id, $int_pat, $str_pat
    ));
    if (!empty($quiz_ids)) {
        $quizzes = get_posts(array('post_type'=>'ielts_quiz','posts_per_page'=>-1,'post__in'=>$quiz_ids,'orderby'=>'menu_order','order'=>'ASC','post_status'=>'publish'));
        foreach ($quizzes as $qp) $all_items[] = array('post'=>$qp,'order'=>$qp->menu_order);
    }
    usort($all_items, function($a,$b){return $a['order']-$b['order'];});
    foreach ($all_items as $ii => $item) {
        if ($item['post']->ID === $quiz->ID) { $current_index = $ii; break; }
    }
    if ($current_index > 0) {
        $pi = $all_items[$current_index-1];
        $prev_url = get_permalink($pi['post']->ID).'?lesson_id='.$lesson_id.'&course_id='.$course_id;
        $prev_title = $pi['post']->post_title;
    }
    if ($current_index < count($all_items)-1) {
        $ni = $all_items[$current_index+1];
        $next_url = get_permalink($ni['post']->ID).'?lesson_id='.$lesson_id.'&course_id='.$course_id;
        $next_title = $ni['post']->post_title;
    }
}
$show_completion = empty($all_items) || $current_index < 0 || $current_index === count($all_items)-1;
?>

<div class="ielts-computer-based-quiz ielts-speaking-exercise-quiz"
     data-quiz-id="<?php echo esc_attr($quiz->ID); ?>"
     data-course-id="<?php echo esc_attr($course_id); ?>"
     data-lesson-id="<?php echo esc_attr($lesson_id); ?>">

    <div class="quiz-header">
        <h2 style="margin:0;"><?php echo esc_html($quiz->post_title); ?></h2>
    </div>

    <form id="ielts-quiz-form" class="quiz-form" data-quiz-id="<?php echo esc_attr($quiz->ID); ?>" data-has-speaking="1">

        <div class="ielts-speaking-exercise-container">
            <div class="ielts-speaking-wrap" id="ielts-speaking-exercise-app">

                <!-- Version label -->
                <div style="background:#dc2626;color:#fff;font-size:11px;font-weight:700;text-align:center;padding:4px 0;letter-spacing:0.05em;border-radius:6px 6px 0 0;">
                    Claude Is Stealing From Me — Version 3.0
                </div>

                <!-- Header -->
                <div class="ielts-speaking-header">
                    <div class="ielts-speaking-avatar" id="ielts-examiner-avatar">E</div>
                    <div class="ielts-speaking-header-text">
                        <h3>IELTS Speaking Test</h3>
                        <p>Parts 1, 2 and 3. The examiner will speak each question. Recording starts automatically.</p>
                    </div>
                    <span class="ielts-speaking-badge" id="ielts-speech-badge">ready</span>
                </div>

                <!-- Gender choice -->
                <div id="ielts-gender-choice" class="ielts-mic-check-panel">
                    <h4>Choose your examiner</h4>
                    <p>Would you prefer a male or female examiner voice?</p>
                    <div class="ielts-gender-btns">
                        <button type="button" class="ielts-gender-btn" data-gender="female">
                            <span class="ielts-gender-icon">&#9792;</span> Female examiner
                        </button>
                        <button type="button" class="ielts-gender-btn" data-gender="male">
                            <span class="ielts-gender-icon">&#9794;</span> Male examiner
                        </button>
                    </div>
                    <p class="ielts-gender-note">Voice quality depends on your browser and device.</p>
                </div>

                <!-- Mic check -->
                <div id="ielts-mic-check" class="ielts-mic-check-panel" style="display:none;">
                    <h4>Microphone check</h4>
                    <p>Click <strong>Record test</strong>, say a few words, then play it back to confirm your mic is working.</p>
                    <div class="ielts-mic-check-actions">
                        <button type="button" class="ielts-speaking-rec-btn" id="ielts-mic-start">
                            <span class="ielts-rec-dot"></span> Record test (5 seconds)
                        </button>
                        <button type="button" class="ielts-speaking-send-btn" id="ielts-mic-play" disabled>Play back</button>
                        <button type="button" class="ielts-speaking-send-btn ielts-speaking-confirm-btn" id="ielts-mic-confirm" disabled>Confirm &amp; start test</button>
                    </div>
                    <div class="ielts-speaking-status" id="ielts-mic-status"></div>
                </div>

                <!-- Noise calibration -->
                <div id="ielts-noise-cal" class="ielts-mic-check-panel" style="display:none;">
                    <h4>Set your silence threshold</h4>
                    <p>Drag the slider so the line sits <strong>above</strong> your background noise but <strong>below</strong> your speaking voice. The bar turns <strong style="color:#16a34a;">green</strong> when you speak above it.</p>
                    <div class="ielts-level-meter-wrap">
                        <div class="ielts-level-meter" id="ielts-level-meter">
                            <div class="ielts-level-fill-red"   id="ielts-level-fill-red"></div>
                            <div class="ielts-level-fill-green" id="ielts-level-fill-green"></div>
                            <div class="ielts-level-threshold"  id="ielts-level-threshold"></div>
                        </div>
                        <input type="range" id="ielts-noise-slider" class="ielts-cal-slider-vertical" min="1" max="100" value="30" step="1" orient="vertical">
                    </div>
                    <p class="ielts-cal-hint" id="ielts-cal-hint">Stay quiet for a moment — measuring background noise...</p>
                    <div class="ielts-mic-check-actions">
                        <button type="button" class="ielts-speaking-send-btn ielts-speaking-confirm-btn" id="ielts-cal-confirm">Looks good — start test</button>
                    </div>
                </div>

                <!-- Interview -->
                <div id="ielts-speaking-interview" style="display:none;position:relative;padding-left:22px;">
                    <!-- Persistent vertical level meter — absolute left edge -->
                    <div class="ielts-live-meter-wrap">
                        <div class="ielts-live-meter" id="ielts-live-meter">
                            <div class="ielts-level-fill-red"   id="ielts-live-fill-red"></div>
                            <div class="ielts-level-fill-green" id="ielts-live-fill-green"></div>
                            <div class="ielts-level-threshold"  id="ielts-live-threshold"></div>
                        </div>
                    </div>
                    <div class="ielts-speaking-part-label" id="ielts-part-label"></div>
                    <div class="ielts-speaking-progress" id="ielts-speaking-progress"></div>

                    <!-- Single question display (fades in/out) -->
                    <div class="ielts-speaking-question-display">
                        <div id="ielts-current-question" class="ielts-current-question"></div>
                    </div>

                    <!-- Part 2 cue card + notes side by side -->
                    <div id="ielts-p2-layout" style="display:none;">
                        <div class="ielts-p2-split">
                            <div id="ielts-p2-cuecard" class="ielts-p2-cuecard-panel">
                                <div class="ielts-p2-cuecard-content"><?php echo esc_html($p2_cuecard); ?></div>
                            </div>
                            <div id="ielts-p2-notes-wrap">
                                <div class="ielts-p2-notes-label">Your preparation notes (not submitted — stays visible while you speak):</div>
                                <textarea id="ielts-p2-notes" class="ielts-p2-notes-textarea" rows="6" placeholder="Jot down your ideas here..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="ielts-speaking-controls">
                        <div class="ielts-countdown-wrap" id="ielts-countdown-wrap" style="display:none;">
                            <div class="ielts-countdown-bar-track">
                                <div class="ielts-countdown-bar-fill" id="ielts-countdown-bar"></div>
                            </div>
                            <span class="ielts-countdown-num" id="ielts-countdown"></span>
                        </div>
                        <div class="ielts-speaking-ctrl-row">
                            <div class="ielts-speaking-status" id="ielts-speak-status"></div>
                            <button type="button" class="ielts-speaking-send-btn" id="ielts-skip-prep-btn" style="display:none;">Skip preparation</button>
                            <button type="button" class="ielts-speaking-send-btn ielts-finish-btn" id="ielts-finish-btn" style="display:none;">I&rsquo;ve finished</button>
                        </div>
                    </div><!-- /.ielts-speaking-controls -->
                </div><!-- /#ielts-speaking-interview -->

                <!-- Results -->
                <div id="ielts-speaking-results" style="display:none;"></div>

            </div>
        </div>

        <!-- Bottom nav -->
        <div class="ielts-sticky-bottom-nav quiz-bottom-nav">
            <div class="nav-item nav-prev">
                <?php if ($prev_url): ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="nav-link">
                    <span class="nav-arrow">&laquo;</span>
                    <span class="nav-label"><small>Previous</small><strong><?php echo esc_html($prev_title); ?></strong></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="nav-item nav-back-left">
                <?php if ($lesson_id): ?>
                <a href="<?php echo esc_url(get_permalink($lesson_id)); ?>" class="nav-link">
                    <span class="nav-label"><small>Back to the Lesson</small></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="nav-item nav-center">
                <span id="ielts-speaking-nav-status" style="font-size:13px;color:#fff;opacity:0.8;"></span>
            </div>
            <div class="nav-item nav-back-right">
                <?php if ($course_id): ?>
                <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-link">
                    <span class="nav-label"><small>Back to the Unit</small></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="nav-item nav-next">
                <?php if ($next_url): ?>
                <a href="<?php echo esc_url($next_url); ?>" class="nav-link" id="ielts-speaking-next-link" style="opacity:0.4;pointer-events:none;">
                    <span class="nav-label"><small>Next</small><strong><?php echo esc_html($next_title); ?></strong></span>
                    <span class="nav-arrow">&raquo;</span>
                </a>
                <?php elseif ($show_completion): ?>
                <div class="nav-completion-message" id="ielts-speaking-completion" style="display:none;">
                    <span>You have finished this lesson</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Config for speaking-exercise.js — must appear before footer scripts -->
        <script>
        var ieltsSpeakingExercise = <?php echo json_encode(array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('ielts_speaking_nonce'),
            'quizId'        => $quiz->ID,
            'courseId'      => $course_id,
            'lessonId'      => $lesson_id,
            'nextUrl'       => $next_url,
            'progressColor' => get_option('ielts_cm_vocab_header_color', '#E56C0A'),
            'hasOpenAI'     => !empty(get_option('ielts_cm_openai_api_key', '')),
            'p1Questions'   => $p1_questions,
            'p2Cuecard'     => $p2_cuecard,
            'p3Questions'   => $p3_questions,
        )); ?>;
        </script>

    </form>
</div>
