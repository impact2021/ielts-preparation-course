<?php
/**
 * Template for displaying IELTS Writing exercises
 * Two-column layout: left = task prompt/image, right = essay textarea
 * Mirrors the CBT template structure exactly for consistent styling
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_array($questions)) {
    $questions = array();
}

// Filter to only writing_task questions
$writing_questions = array();
foreach ($questions as $idx => $q) {
    if (isset($q['type']) && $q['type'] === 'writing_task') {
        $writing_questions[$idx] = $q;
    }
}

$user_id       = get_current_user_id();
$timer_minutes = get_post_meta($quiz->ID, '_ielts_cm_timer_minutes', true);

// Navigation URLs — mirror CBT template logic
$next_url   = '';
$prev_url   = '';
$next_title = '';
$prev_title = '';
$all_items  = array();
$current_index = -1;

if ($lesson_id) {
    global $wpdb;
    $int_pattern = '%' . $wpdb->esc_like('i:' . $lesson_id . ';') . '%';
    $str_pattern = '%' . $wpdb->esc_like(serialize(strval($lesson_id))) . '%';

    $quiz_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
         WHERE (meta_key = '_ielts_cm_lesson_id' AND meta_value = %d)
            OR (meta_key = '_ielts_cm_lesson_ids' AND (meta_value LIKE %s OR meta_value LIKE %s))",
        $lesson_id, $int_pattern, $str_pattern
    ));

    if (!empty($quiz_ids)) {
        $quizzes = get_posts(array(
            'post_type'      => 'ielts_quiz',
            'posts_per_page' => -1,
            'post__in'       => $quiz_ids,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ));
        foreach ($quizzes as $q_post) {
            $all_items[] = array('post' => $q_post, 'order' => $q_post->menu_order);
        }
    }

    usort($all_items, function($a, $b) { return $a['order'] - $b['order']; });

    foreach ($all_items as $item_index => $item) {
        if ($item['post']->ID === $quiz->ID) {
            $current_index = $item_index;
            break;
        }
    }

    if ($current_index > 0) {
        $prev_item  = $all_items[$current_index - 1];
        $prev_url   = get_permalink($prev_item['post']->ID) . '?lesson_id=' . $lesson_id . '&course_id=' . $course_id;
        $prev_title = $prev_item['post']->post_title;
    }

    if ($current_index < count($all_items) - 1) {
        $next_item  = $all_items[$current_index + 1];
        $next_url   = get_permalink($next_item['post']->ID) . '?lesson_id=' . $lesson_id . '&course_id=' . $course_id;
        $next_title = $next_item['post']->post_title;
    }
}

$first_index = array_key_first($writing_questions);
$show_completion_message = !isset($current_index) || empty($all_items) || $current_index < 0 || $current_index === count($all_items) - 1;
?>

<div class="ielts-computer-based-quiz ielts-writing-exercise-quiz"
     data-quiz-id="<?php echo esc_attr($quiz->ID); ?>"
     data-course-id="<?php echo esc_attr($course_id); ?>"
     data-lesson-id="<?php echo esc_attr($lesson_id); ?>"
     data-test-type="writing"
     data-timer-minutes="<?php echo esc_attr($timer_minutes ?: 0); ?>">

    <!-- Admin header toggle -->
    <?php if (current_user_can('manage_options')): ?>
    <button type="button" id="header-toggle-btn" class="header-toggle-btn" title="<?php _e('Toggle header visibility', 'ielts-course-manager'); ?>">
        <span class="toggle-icon">▼</span>
    </button>
    <?php endif; ?>

    <!-- Quiz header -->
    <div class="quiz-header">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0;"><?php echo esc_html($quiz->post_title); ?></h2>
        </div>
        <?php if ($course_id):
            $course = get_post($course_id);
            if ($course): ?>
            <div class="quiz-breadcrumb">
                <a href="<?php echo get_permalink($course->ID); ?>"><?php echo esc_html($course->post_title); ?></a>
                <span class="separator">&raquo;</span>
                <?php if ($lesson_id): $lesson = get_post($lesson_id); ?>
                    <a href="<?php echo get_permalink($lesson->ID); ?>"><?php echo esc_html($lesson->post_title); ?></a>
                    <span class="separator">&raquo;</span>
                <?php endif; ?>
                <span><?php echo esc_html($quiz->post_title); ?></span>
            </div>
        <?php endif; endif; ?>
    </div>

    <form id="ielts-quiz-form" class="quiz-form" data-quiz-id="<?php echo esc_attr($quiz->ID); ?>" data-has-writing="1" data-auto-submit="0">

        <!-- No top nav bar for writing exercises — timer and submit are in the bottom nav only -->

        <!-- Two-column layout (hidden after submission) -->
        <div class="computer-based-container" id="ielts-writing-container">

            <!-- Left: Task Prompt + Image -->
            <div class="reading-column" id="ielts-writing-left-col">
                <?php foreach ($writing_questions as $idx => $question):
                    $task_type     = isset($question['task_type']) ? $question['task_type'] : 'task2';
                    $task_prompt   = isset($question['question']) ? $question['question'] : '';
                    $task_image_url = isset($question['task_image_url']) ? $question['task_image_url'] : '';
                    $show_task_prompt_to_student = !($task_type === 'task1_academic' && !empty($task_image_url));
                    $student_task_prompt = $show_task_prompt_to_student ? $task_prompt : '';
                    $task_labels   = array(
                        'task2'          => 'Task 2',
                        'task1_academic' => 'Task 1 — Academic',
                        'task1_general'  => 'Task 1 — General Training',
                    );
                    $task_label = isset($task_labels[$task_type]) ? $task_labels[$task_type] : 'Writing Task';
                ?>
                <div class="ielts-writing-prompt-panel"
                     id="writing-prompt-<?php echo esc_attr($idx); ?>"
                     data-ai-prompt="<?php echo esc_attr(wp_strip_all_tags($task_prompt)); ?>"
                     data-student-prompt="<?php echo esc_attr(wp_strip_all_tags($student_task_prompt)); ?>"
                     data-task-image-url="<?php echo esc_attr($task_image_url); ?>"
                     style="<?php echo ($idx === $first_index) ? '' : 'display:none;'; ?>">
                    <div class="writing-task-label"><?php echo esc_html($task_label); ?></div>
                    <?php if ($task_image_url): ?>
                    <div class="writing-task-image">
                        <img src="<?php echo esc_url($task_image_url); ?>" alt="" class="writing-chart-image">
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($student_task_prompt)): ?>
                    <div class="writing-task-prompt">
                        <?php echo wp_kses_post(wpautop($student_task_prompt)); ?>
                    </div>
                    <?php endif; ?>
                    <div class="writing-task-minimums">
                        <p><strong><?php _e('Minimum:', 'ielts-course-manager'); ?></strong>
                        <?php echo ($task_type === 'task2') ? '250 words' : '150 words'; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right: Essay Textarea -->
            <div class="questions-column" id="ielts-writing-right-col">

                <!-- Compose area -->
                <div id="ielts-writing-compose-area">
                    <?php foreach ($writing_questions as $idx => $question):
                        $task_type = isset($question['task_type']) ? $question['task_type'] : 'task2';
                    ?>
                    <div class="ielts-writing-task-area" id="writing-area-<?php echo esc_attr($idx); ?>" style="<?php echo ($idx === $first_index) ? '' : 'display:none;'; ?>">
                        <div class="writing-area-header">
                            <span class="writing-area-title">
                                <?php echo esc_html($task_type === 'task2' ? 'Task 2 Response' : 'Task 1 Response'); ?>
                            </span>
                            <span class="writing-area-counts">
                                Words: <span class="writing-word-count" id="word-count-<?php echo esc_attr($idx); ?>">0</span>
                                &nbsp;|&nbsp;
                                Paragraphs: <span class="writing-para-count" id="para-count-<?php echo esc_attr($idx); ?>">0</span>
                            </span>
                        </div>
                        <textarea
                            id="writing-essay-<?php echo esc_attr($idx); ?>"
                            name="writing_essay_<?php echo esc_attr($idx); ?>"
                            class="ielts-writing-textarea"
                            data-question-index="<?php echo esc_attr($idx); ?>"
                            data-task-type="<?php echo esc_attr($task_type); ?>"
                            data-min-words="<?php echo esc_attr($task_type === 'task2' ? 250 : 150); ?>"
                            placeholder="Write your <?php echo esc_attr($task_type === 'task2' ? 'Task 2' : 'Task 1'); ?> response here..."></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Progress bar (during assessment) -->
                <div id="ielts-writing-assessing" style="display:none;">
                    <div class="ielts-writing-progress-wrap">
                        <div class="ielts-progress-bar-track">
                            <div class="ielts-progress-bar-fill" id="ielts-exercise-progress-fill"></div>
                        </div>
                        <div class="ielts-progress-label" id="ielts-exercise-progress-label">Submitting your essays...</div>
                    </div>
                </div>

            </div><!-- /.questions-column -->

        </div><!-- /.computer-based-container -->

        <!-- Results area — full width, shown after assessment, replaces container -->
        <div id="ielts-writing-results-area" class="ielts-results-hidden">

            <!-- Combined score pinned at top -->
            <div id="ielts-writing-combined-score" style="display:none; flex-shrink: 0;">
                <div class="ielts-writing-combined-band">
                    <span class="combined-band-label">Overall Writing Score</span>
                    <span class="combined-band-value" id="ielts-combined-band-value">-</span>
                    <span class="combined-band-note">Task 1 = one third &nbsp;|&nbsp; Task 2 = two thirds</span>
                </div>
            </div>

            <!-- Side-by-side scrolling columns -->
            <div class="ielts-writing-results-grid">
                <?php foreach ($writing_questions as $idx => $question):
                    $q_task_type = isset($question['task_type']) ? $question['task_type'] : 'task2';
                    $q_label = ($q_task_type === 'task2') ? 'Task 2' : 'Task 1';
                ?>
                <div class="ielts-writing-result-col" id="writing-result-<?php echo esc_attr($idx); ?>">
                    <div class="ielts-writing-result-col-header"><?php echo esc_html($q_label); ?> Feedback</div>
                    <div class="ielts-writing-result-col-content" id="writing-result-content-<?php echo esc_attr($idx); ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- Sticky bottom nav — identical to CBT template -->
        <div class="ielts-sticky-bottom-nav quiz-bottom-nav">
            <div class="nav-item nav-prev">
                <?php if ($prev_url): ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="nav-link">
                    <span class="nav-arrow">&laquo;</span>
                    <span class="nav-label">
                        <small><?php _e('Previous', 'ielts-course-manager'); ?></small>
                        <strong><?php echo esc_html($prev_title); ?></strong>
                    </span>
                </a>
                <?php endif; ?>
            </div>
            <div class="nav-item nav-back-left">
                <?php if ($lesson_id): ?>
                <a href="<?php echo esc_url(get_permalink($lesson_id)); ?>" class="nav-link nav-back-to-lesson">
                    <span class="nav-label"><small><?php _e('Back to the Lesson', 'ielts-course-manager'); ?></small></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="nav-item nav-center">
                <div class="quiz-center-controls">
                    <!-- Task navigation buttons — sit inline with submit -->
                    <div class="ielts-writing-task-btns">
                        <?php foreach ($writing_questions as $idx => $question):
                            $q_task_type = isset($question['task_type']) ? $question['task_type'] : 'task2';
                            $q_nav_label = ($q_task_type === 'task2') ? 'Task 2' : 'Task 1';
                        ?>
                        <button type="button"
                                class="ielts-writing-nav-btn <?php echo ($idx === $first_index) ? 'active' : ''; ?>"
                                data-question="<?php echo esc_attr($idx); ?>"
                                data-task-type="<?php echo esc_attr($q_task_type); ?>">
                            <?php echo esc_html($q_nav_label); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($timer_minutes > 0): ?>
                    <div class="timer-display">
                        <strong><?php _e('Time:', 'ielts-course-manager'); ?></strong>
                        <span id="timer-display-bottom">--:--</span>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="button button-primary quiz-submit-btn" id="ielts-writing-submit-btn">
                        <?php _e('Submit', 'ielts-course-manager'); ?>
                    </button>
                </div>
            </div>
            <div class="nav-item nav-back-right">
                <?php if ($course_id): ?>
                <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="nav-link nav-back-to-course">
                    <span class="nav-label"><small><?php _e('Back to the Unit', 'ielts-course-manager'); ?></small></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="nav-item nav-next">
                <?php if ($next_url): ?>
                <a href="<?php echo esc_url($next_url); ?>" class="nav-link" id="ielts-writing-next-link" style="opacity:0.4; pointer-events:none;">
                    <span class="nav-label">
                        <small><?php _e('Next', 'ielts-course-manager'); ?></small>
                        <strong><?php echo esc_html($next_title); ?></strong>
                    </span>
                    <span class="nav-arrow">&raquo;</span>
                </a>
                <?php elseif ($show_completion_message): ?>
                <div class="nav-completion-message" id="ielts-writing-completion" style="display:none;">
                    <span><?php _e('You have finished this lesson', 'ielts-course-manager'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </form>

</div><!-- /.ielts-writing-exercise-quiz -->

<?php
wp_localize_script('ielts-writing-exercise', 'ieltsWritingExercise', array(
    'ajaxUrl'       => admin_url('admin-ajax.php'),
    'nonce'         => wp_create_nonce('ielts_writing_nonce'),
    'quizId'        => $quiz->ID,
    'courseId'      => $course_id,
    'lessonId'      => $lesson_id,
    'nextUrl'       => $next_url,
    'progressColor' => get_option('ielts_cm_vocab_header_color', '#2271b1'),
));
?>
