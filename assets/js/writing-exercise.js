/* global ieltsWritingExercise, jQuery */
(function($) {
    'use strict';

    if (!$('.ielts-writing-exercise-quiz').length) return;

    var cfg            = ieltsWritingExercise;
    var progressTimers = [];
    var retryStatusByTask = {};
    var retryCountdownTimers = {};
    var submitted      = false;
    var defaultSubmitText = $('#ielts-writing-submit-btn').text() || 'Submit';
    var maxFrontendRetryAttempts = 3;

    function countWords(text) {
        var normalized = (text || '').trim();
        return normalized ? normalized.split(/\s+/).filter(Boolean).length : 0;
    }

    function getSubmitErrorBox() {
        var $box = $('#ielts-writing-submit-error');

        if (!$box.length) {
            $box = $('<div id="ielts-writing-submit-error" class="ielts-writing-error" style="display:none;" role="alert"></div>');
            $('#ielts-writing-container').before($box);
        }

        return $box;
    }

    function clearSubmitError() {
        getSubmitErrorBox().hide().empty();
    }

    function showSubmitError(messages) {
        var items = Array.isArray(messages) ? messages.filter(Boolean) : [];
        var html = '<strong>Assessment could not be completed.</strong>' +
            '<div>Your writing is still in the editor below, so you can try again without losing it.</div>';

        if (items.length) {
            html += '<ul>';
            items.forEach(function(message) {
                html += '<li>' + $('<div>').text(message).html() + '</li>';
            });
            html += '</ul>';
        }

        function getTaskLabel(task) {
            return task.task_type === 'task2' ? 'Task 2' : 'Task 1';
        }

        function getRetryStatusBox() {
            var $box = $('#ielts-exercise-retry-status');

            if (!$box.length) {
                $box = $('<div id="ielts-exercise-retry-status" class="ielts-progress-retry-status" style="display:none;" aria-live="polite"></div>');
                $('#ielts-exercise-progress-label').after($box);
            }

            return $box;
        }

        function renderRetryStatus() {
            var $box = getRetryStatusBox();
            var messages = Object.keys(retryStatusByTask).sort().map(function(key) {
                return retryStatusByTask[key];
            }).filter(Boolean);

            if (!messages.length) {
                $box.hide().text('');
                return;
            }

            $box.text(messages.join(' | ')).show();
        }

        function setRetryStatus(taskIndex, message) {
            retryStatusByTask[taskIndex] = message;
            renderRetryStatus();
        }

        function clearRetryStatus(taskIndex) {
            if (typeof taskIndex !== 'undefined' && taskIndex !== null) {
                delete retryStatusByTask[taskIndex];
            } else {
                retryStatusByTask = {};
            }

            renderRetryStatus();
        }

        function clearRetryCountdownTimer(taskIndex) {
            if (retryCountdownTimers[taskIndex]) {
                clearInterval(retryCountdownTimers[taskIndex]);
                delete retryCountdownTimers[taskIndex];
            }
        }

        function shouldRetryAssessmentFailure(message) {
            var normalized = (message || '').toLowerCase();

            if (!normalized) return false;

            return normalized.indexOf('temporarily overloaded') !== -1 ||
                normalized.indexOf('overloaded') !== -1 ||
                normalized.indexOf('rate limit') !== -1 ||
                normalized.indexOf('temporarily unavailable') !== -1 ||
                normalized.indexOf('service unavailable') !== -1 ||
                normalized.indexOf('could not connect') !== -1 ||
                normalized.indexOf('network error') !== -1 ||
                normalized.indexOf('timeout') !== -1 ||
                normalized.indexOf('request failed') !== -1;
        }

        function getRetryDelaySeconds(message, attempt) {
            var normalized = (message || '').toLowerCase();
            var match = normalized.match(/retry(?:ing)?(?:\s+after)?\s+(\d+)\s*(?:s|sec|secs|second|seconds)?/i);

            if (match && match[1]) {
                var parsed = parseInt(match[1], 10);
                if (!isNaN(parsed) && parsed > 0) {
                    return Math.min(10, parsed);
                }
            }

            return Math.min(10, Math.pow(2, Math.max(0, attempt - 1)));
        }

        function countdownToRetry(task, attempt, maxAttempts, delaySeconds) {
            var deferred = $.Deferred();
            var remaining = Math.max(1, parseInt(delaySeconds, 10) || 1);
            var label = getTaskLabel(task);

            clearRetryCountdownTimer(task.index);
            setRetryStatus(task.index, label + ' is busy. Retrying in ' + remaining + 's (' + (attempt + 1) + '/' + maxAttempts + ')…');

            retryCountdownTimers[task.index] = setInterval(function() {
                remaining -= 1;

                if (remaining <= 0) {
                    clearRetryCountdownTimer(task.index);
                    clearRetryStatus(task.index);
                    deferred.resolve();
                    return;
                }

                setRetryStatus(task.index, label + ' is busy. Retrying in ' + remaining + 's (' + (attempt + 1) + '/' + maxAttempts + ')…');
            }, 1000);

            return deferred.promise();
        }

        function submitTaskRequest(task) {
            return $.ajax({
                url:    cfg.ajaxUrl,
                method: 'POST',
                data: {
                    action:        'ielts_cm_submit_writing',
                    nonce:         cfg.nonce,
                    quiz_id:       cfg.quizId,
                    task_type:     task.task_type,
                    task_prompt:   task.task_prompt,
                    student_prompt: task.student_prompt,
                    ai_assessment_notes: task.ai_assessment_notes,
                    task_image_url: task.task_image_url,
                    essay_text:    task.essay_text,
                    exercise_mode: 1,
                },
            }).then(function(response) {
                return { task: task, response: response };
            }, function(jqXHR, textStatus, errorThrown) {
                var message = jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message
                    ? jqXHR.responseJSON.data.message
                    : (errorThrown || textStatus || 'Request failed.');

                return $.Deferred().resolve({
                    task: task,
                    response: { success: false, data: { message: message } }
                }).promise();
            });
        }

        function submitTaskWithRetry(task) {
            var attempt = 1;

            function runAttempt() {
                return submitTaskRequest(task).then(function(result) {
                    if (result.response && result.response.success) {
                        clearRetryCountdownTimer(task.index);
                        clearRetryStatus(task.index);
                        return result;
                    }

                    var message = result.response && result.response.data ? result.response.data.message : '';
                    var canRetry = attempt < maxFrontendRetryAttempts && shouldRetryAssessmentFailure(message);

                    if (!canRetry) {
                        clearRetryCountdownTimer(task.index);
                        clearRetryStatus(task.index);
                        return result;
                    }

                    var delaySeconds = getRetryDelaySeconds(message, attempt);
                    return countdownToRetry(task, attempt, maxFrontendRetryAttempts, delaySeconds).then(function() {
                        attempt += 1;
                        return runAttempt();
                    });
                });
            }

            return runAttempt();
        }

        var $box = getSubmitErrorBox();
        $box.html(html).show();

        $('html, body').animate({
            scrollTop: $box.offset().top - 30
        }, 300);
    }

    function resetResultsView() {
        $('.ielts-writing-result-col-content').empty();
        $('.ielts-writing-result-col').hide();
        $('#ielts-writing-combined-score').hide();
        $('#ielts-writing-results-area').addClass('ielts-results-hidden');
    }

    // ─── Set progress bar colour ─────────────────────────────────────
    if (cfg.progressColor) {
        document.documentElement.style.setProperty('--ielts-progress-color', cfg.progressColor);
    }

    // ─── Word & Paragraph Counter ────────────────────────────────────
    $(document).on('input', '.ielts-writing-textarea', function() {
        var $ta  = $(this);
        var idx  = $ta.data('question-index');
        var text = $ta.val();

        var words = countWords(text);
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
            var taskPrompt   = ($promptPanel.data('ai-prompt') || '').toString().trim();
            var studentPrompt = ($promptPanel.data('student-prompt') || '').toString().trim();
            var aiAssessmentNotes = ($promptPanel.data('ai-assessment-notes') || '').toString().trim();
            var taskImageUrl = ($promptPanel.data('task-image-url') || '').toString().trim();

            if (!studentPrompt) {
                studentPrompt = $promptPanel.find('.writing-task-prompt').text().trim();
            }
            if (!taskPrompt) {
                if (studentPrompt) {
                    taskPrompt = studentPrompt;
                } else {
                    // No text prompt available — use a task-appropriate generic description.
                    // Avoid $promptPanel.text() which captures the UI label ("Task 1 — Academic")
                    // and the minimum word count hint, which are not useful as AI context.
                    var taskType = $ta.data('task-type') || '';
                    if (taskType === 'task2') {
                        taskPrompt = 'IELTS Writing Task 2 essay.';
                    } else if (taskImageUrl) {
                        taskPrompt = 'IELTS Academic Writing Task 1: describe the visual data shown in the image.';
                    } else {
                        taskPrompt = 'IELTS Writing Task 1 response.';
                    }
                }
            }
            tasks.push({
                index:       idx,
                task_type:   $ta.data('task-type'),
                essay_text:  $ta.val(),
                task_prompt: taskPrompt,
                student_prompt: studentPrompt,
                ai_assessment_notes: aiAssessmentNotes,
                task_image_url: taskImageUrl,
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
            var wordCount = countWords(t.essay_text);

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
        clearSubmitError();
        resetResultsView();
        clearRetryStatus();
        $('#ielts-writing-submit-btn').prop('disabled', true).text('Assessing...');
        $('.ielts-writing-nav-btn').prop('disabled', true);
        $('#ielts-writing-assessing').css('display', 'flex');
        startProgress(tasks.length);

        // Fire parallel API calls with per-task retries for transient overloads
        var ajaxCalls = tasks.map(function(task) {
            return submitTaskWithRetry(task);
        });

        // Wait for all calls regardless of count
        $.when.apply($, ajaxCalls).then(function() {
            // $.when passes results differently for 1 vs multiple deferreds
            var results = ajaxCalls.length === 1
                ? [arguments[0]]
                : Array.prototype.slice.call(arguments);

            completeProgress();
            clearRetryStatus();
            $('#ielts-writing-assessing').hide();

            var task1Band = null;
            var task2Band = null;
            var failedMessages = [];
            var hasFailures = false;

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
                    hasFailures = true;
                    failedMessages.push(
                        (task.task_type === 'task2' ? 'Task 2' : 'Task 1') + ': ' +
                        (response.data ? response.data.message : 'Unknown error')
                    );
                }
            });

            if (hasFailures) {
                $('#ielts-writing-submit-btn').prop('disabled', false).text(defaultSubmitText);
                $('.ielts-writing-nav-btn').prop('disabled', false);
                showSubmitError(failedMessages);
                return;
            }

            $('#ielts-writing-container').hide();
            $('#ielts-writing-results-area').removeClass('ielts-results-hidden');

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
        Object.keys(retryCountdownTimers).forEach(function(key) {
            clearRetryCountdownTimer(key);
        });
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
