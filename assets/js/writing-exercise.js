/* global ieltsWritingExercise, jQuery */
(function($) {
    'use strict';

    if (!$('.ielts-writing-exercise-quiz').length) return;

    var cfg            = ieltsWritingExercise;
    var progressTimers = [];
    var submitted      = false;

    // ─── Set progress bar colour ─────────────────────────────────────
    if (cfg.progressColor) {
        document.documentElement.style.setProperty('--ielts-progress-color', cfg.progressColor);
    }

    // ─── Word & Paragraph Counter ────────────────────────────────────
    $(document).on('input', '.ielts-writing-textarea', function() {
        var $ta  = $(this);
        var idx  = $ta.data('question-index');
        var text = $ta.val();

        var words = text.trim().length > 0 ? text.trim().split(/\s+/).filter(Boolean).length : 0;
        $('#word-count-' + idx).text(words);

        var paras = text.trim().length > 0
            ? text.trim().split(/\n\s*\n|\r\n\s*\r\n|\r\s*\r/).filter(function(p) { return p.trim().length > 0; }).length
            : 0;
        var $paraCount = $('#para-count-' + idx);
        $paraCount.text(paras);
        $paraCount.css('color', (paras <= 1 && words > 80) ? '#c0392b' : '');
    });

    // ─── Writing nav button: switch task ─────────────────────────────
    $(document).on('click', '.ielts-writing-nav-btn', function() {
        var idx = $(this).data('question');

        // Switch prompt panel
        $('.ielts-writing-prompt-panel').hide();
        $('#writing-prompt-' + idx).show();

        // Switch textarea panel
        $('.ielts-writing-task-area').hide();
        $('#writing-area-' + idx).show();

        // Switch result panel if submitted
        if (submitted) {
            $('.ielts-writing-result-panel').hide();
            $('#writing-result-' + idx).show();
        }

        // Active state
        $('.ielts-writing-nav-btn').removeClass('active');
        $(this).addClass('active');
    });

    // ─── Intercept form submit ────────────────────────────────────────
    $(document).on('submit', '#ielts-quiz-form[data-has-writing="1"]', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        if (submitted) return;

        var isAutoSubmit = $(this).data('auto-submit') === 1;

        // Collect writing tasks
        var tasks = [];
        $('.ielts-writing-textarea').each(function() {
            var $ta  = $(this);
            var idx  = $ta.data('question-index');
            var $promptPanel = $('#writing-prompt-' + idx);
            var taskPrompt   = $promptPanel.find('.writing-task-prompt-source').val();

            if (taskPrompt) {
                taskPrompt = taskPrompt.trim();
            }

            // Fallback: extract the visible prompt text
            if (!taskPrompt) {
                taskPrompt = $promptPanel.find('.writing-task-prompt').text().trim();
            }

            // Final fallback: try the entire prompt panel text minus the label and minimums
            if (!taskPrompt) {
                taskPrompt = $promptPanel.text().trim();
            }
            tasks.push({
                index:       idx,
                task_type:   $ta.data('task-type'),
                essay_text:  $ta.val(),
                task_prompt: taskPrompt,
            });
        });

        // Validate — at least one essay must have content
        var hasContent = tasks.some(function(t) { return t.essay_text.trim().length > 0; });
        if (!hasContent && !isAutoSubmit) {
            alert('Please write your essays before submitting.');
            return;
        }
        if (!hasContent) return; // Auto-submit with nothing written — do nothing

        // Validate each task (skip alerts for auto-submit)
        if (!isAutoSubmit) {
        for (var i = 0; i < tasks.length; i++) {
            var t = tasks[i];
            var wordCount = t.essay_text.trim().split(/\s+/).filter(Boolean).length;

            if (wordCount < 50) {
                alert('Your ' + (t.task_type === 'task2' ? 'Task 2' : 'Task 1') + ' response is too short (' + wordCount + ' words). The minimum to submit is 50 words.');
                return;
            }

            // Paragraph check
            var paraCount = t.essay_text.trim().split(/\n\s*\n|\r\n\s*\r\n|\r\s*\r/).filter(function(p) { return p.trim().length > 0; }).length;
            if (paraCount <= 1 && wordCount > 80) {
                var proceed = confirm(
                    'Warning: No paragraph breaks detected in your ' + (t.task_type === 'task2' ? 'Task 2' : 'Task 1') + ' response.\n\n' +
                    'In IELTS, paragraphing is required and affects your Coherence & Cohesion score. An essay without paragraph breaks cannot score above Band 6 for this criterion.\n\n' +
                    'Press OK to submit anyway, or Cancel to go back and check your formatting.'
                );
                if (!proceed) return;
            }
        }
        }

        // Disable submit button and show progress
        $('#ielts-writing-submit-btn').prop('disabled', true).text('Assessing...');
        $('#ielts-writing-compose-area').hide();
        $('#ielts-writing-assessing').show();
        startProgress(tasks.length);

        // Fire parallel API calls — collect as plain array of promises
        var ajaxCalls = tasks.map(function(task) {
            return $.ajax({
                url:    cfg.ajaxUrl,
                method: 'POST',
                data: {
                    action:        'ielts_cm_submit_writing',
                    nonce:         cfg.nonce,
                    task_type:     task.task_type,
                    task_prompt:   task.task_prompt,
                    essay_text:    task.essay_text,
                    exercise_mode: 1,
                },
            }).then(function(response) {
                return { task: task, response: response };
            }, function(jqXHR, textStatus, errorThrown) {
                return $.Deferred().resolve({ task: task, response: { success: false, data: { message: textStatus + ': ' + errorThrown } } }).promise();
            });
        });

        // Wait for all calls regardless of count
        $.when.apply($, ajaxCalls).then(function() {
            // $.when passes results differently for 1 vs multiple deferreds
            var results = ajaxCalls.length === 1
                ? [arguments[0]]
                : Array.prototype.slice.call(arguments);

            completeProgress();
            $('#ielts-writing-assessing').hide();
            $('#ielts-writing-container').hide();
            $('#ielts-writing-results-area').removeClass('ielts-results-hidden');

            var task1Band = null;
            var task2Band = null;

            results.forEach(function(result) {
                var task     = result.task;
                var response = result.response;
                var idx      = task.index;

                if (response.success) {
                    var assessment = response.data.assessment;
                    var html       = response.data.html;

                    $('#writing-result-content-' + idx).html(html);
                    $('#writing-result-' + idx).show();

                    if (task.task_type === 'task2') {
                        task2Band = parseFloat(assessment.overall_band);
                    } else {
                        task1Band = parseFloat(assessment.overall_band);
                    }
                } else {
                    $('#writing-result-content-' + idx).html(
                        '<div class="ielts-writing-error">Assessment failed: ' + (response.data ? response.data.message : 'Unknown error') + '</div>'
                    );
                    $('#writing-result-' + idx).show();
                }
            });

            // Calculate combined IELTS writing band
            var combinedBand = null;
            if (task1Band !== null && task2Band !== null) {
                var raw = (task1Band + (task2Band * 2)) / 3;
                combinedBand = Math.round(raw * 2) / 2;
            } else if (task2Band !== null) {
                combinedBand = task2Band;
            } else if (task1Band !== null) {
                combinedBand = task1Band;
            }

            if (combinedBand !== null) {
                $('#ielts-combined-band-value').text(combinedBand.toFixed(1));
                $('#ielts-writing-combined-score').show();
                saveWritingScore(combinedBand);
            }

            submitted = true;
            // Reset submit button
            $('#ielts-writing-submit-btn').prop('disabled', true).text('Submitted');
            // Hide task nav buttons
            $('.ielts-writing-task-btns').hide();
            enableNextNav();
        });
    });

    // ─── Save combined writing score ─────────────────────────────────
    function saveWritingScore(combinedBand) {
        $.ajax({
            url: cfg.ajaxUrl,
            method: 'POST',
            data: {
                action:     'ielts_cm_save_writing_exercise_score',
                nonce:      cfg.nonce,
                quiz_id:    cfg.quizId,
                course_id:  cfg.courseId,
                lesson_id:  cfg.lessonId,
                band_score: combinedBand,
            },
        });
    }

    // ─── Enable next navigation after assessment ──────────────────────
    function enableNextNav() {
        var $nextLink = $('#ielts-writing-next-link');
        $nextLink.css({ opacity: 1, 'pointer-events': 'auto' });
        $('#ielts-writing-completion').show();
    }

    // ─── Progress bar ─────────────────────────────────────────────────
    function startProgress(taskCount) {
        var steps = [
            { pct: 8,  label: 'Submitting your essays...',                 delay: 0     },
            { pct: 22, label: 'Assessing Task Achievement...',             delay: 2500  },
            { pct: 38, label: 'Assessing Coherence & Cohesion...',         delay: 5500  },
            { pct: 54, label: 'Assessing Lexical Resource...',             delay: 9000  },
            { pct: 70, label: 'Assessing Grammatical Range & Accuracy...', delay: 12500 },
            { pct: 83, label: 'Checking spelling & grammar issues...',     delay: 16000 },
            { pct: 93, label: 'Compiling your feedback...',                delay: 20000 },
        ];

        if (taskCount > 1) {
            steps[0].label = 'Submitting your essays...';
            steps[6].label = 'Compiling feedback for both tasks...';
        }

        $('#ielts-exercise-progress-fill').css('width', '0%');
        $('#ielts-exercise-progress-label').text(steps[0].label).css('opacity', 1);

        steps.forEach(function(step) {
            var t = setTimeout(function() {
                $('#ielts-exercise-progress-fill').css('width', step.pct + '%');
                $('#ielts-exercise-progress-label').css('opacity', 0);
                setTimeout(function() {
                    $('#ielts-exercise-progress-label').text(step.label).css('opacity', 1);
                }, 200);
            }, step.delay);
            progressTimers.push(t);
        });
    }

    function completeProgress() {
        progressTimers.forEach(function(t) { clearTimeout(t); });
        progressTimers = [];
        $('#ielts-exercise-progress-fill').css('width', '100%');
        $('#ielts-exercise-progress-label').css('opacity', 0);
        setTimeout(function() {
            $('#ielts-exercise-progress-label').text('Done — loading your results...').css('opacity', 1);
        }, 200);
    }

    // ─── Dispute handling ─────────────────────────────────────────────
    $(document).on('click', '.ielts-dispute-btn', function() {
        $(this).siblings('.ielts-dispute-form').slideToggle(200);
    });

    $(document).on('click', '.ielts-dispute-cancel', function() {
        $(this).closest('.ielts-dispute-form').slideUp(200);
    });

    $(document).on('click', '.ielts-dispute-submit', function() {
        var $submitBtn    = $(this);
        var $form         = $submitBtn.closest('.ielts-dispute-form');
        var $disputeBtn   = $form.siblings('.ielts-dispute-btn');
        var submissionId  = $disputeBtn.data('submission-id');
        var nonce         = $disputeBtn.data('nonce');
        var reason        = $form.find('.ielts-dispute-reason').val().trim();
        var $feedback     = $form.find('.ielts-dispute-feedback');

        if (!reason) {
            $feedback.css({ display: 'block', color: '#c0392b' }).text('Please explain why you disagree before submitting.');
            return;
        }

        $submitBtn.prop('disabled', true).text('Submitting...');

        $.ajax({
            url:    cfg.ajaxUrl,
            method: 'POST',
            data: {
                action:        'ielts_cm_dispute_writing',
                nonce:         nonce,
                submission_id: submissionId,
                reason:        reason,
            },
            success: function(response) {
                if (response.success) {
                    $form.find('.ielts-dispute-reason').hide();
                    $form.find('.ielts-dispute-submit, .ielts-dispute-cancel').hide();
                    $feedback.css({ display: 'block', color: '#1a6b3a', background: '#e6f4ea', padding: '8px 12px', borderRadius: '4px' })
                        .text('Your dispute has been submitted. An instructor will review your score.');
                } else {
                    $submitBtn.prop('disabled', false).text('Submit Dispute');
                    $feedback.css({ display: 'block', color: '#c0392b' })
                        .text('Could not submit dispute. Please try again.');
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text('Submit Dispute');
                $feedback.css({ display: 'block', color: '#c0392b' }).text('Network error. Please try again.');
            }
        });
    });

})(jQuery);
