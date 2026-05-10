/* global ielts_writing, jQuery */
(function($) {
    'use strict';

    var currentSubmissionId = null;

    function countWords(text) {
        var normalized = (text || '').trim();
        return normalized ? normalized.split(/\s+/).filter(Boolean).length : 0;
    }

    // ─── Word & Paragraph Counter ────────────────────────────────────
    $('#ielts-essay-text').on('input', function() {
        var text  = $(this).val();
        var words = countWords(text);
        $('#ielts-word-count-num').text(words);

        // Count paragraphs — split on one or more blank lines
        var paras = text.trim().length > 0
            ? text.trim().split(/\n\s*\n|\r\n\s*\r\n|\r\s*\r/).filter(function(p) { return p.trim().length > 0; }).length
            : 0;
        var $paraCount = $('#ielts-para-count-num');
        $paraCount.text(paras);

        // Warn visually if only 1 paragraph detected and there's enough text
        if (paras <= 1 && words > 80) {
            $paraCount.css('color', '#c0392b').attr('title', 'No paragraph breaks detected — your essay may have lost its formatting when pasted.');
        } else {
            $paraCount.css('color', '').attr('title', '');
        }
    });

    // ─── Countdown Timer ─────────────────────────────────────────────
    var $countdown = $('#ielts-writing-countdown');
    if ($countdown.length) {
        var availableAt = parseInt($countdown.data('available'), 10) * 1000;

        function updateCountdown() {
            var remaining = availableAt - Date.now();
            if (remaining <= 0) {
                $countdown.text('now — please refresh the page.');
                return;
            }
            var h = Math.floor(remaining / 3600000);
            var m = Math.floor((remaining % 3600000) / 60000);
            var s = Math.floor((remaining % 60000) / 1000);
            $countdown.text(
                (h > 0 ? h + 'h ' : '') +
                (m > 0 ? m + 'm ' : '') +
                s + 's'
            );
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // ─── Progress Bar ────────────────────────────────────────────────
    var progressTimers = [];

    function startProgress() {
        var steps = [
            { pct: 8,  label: 'Submitting your essay...',                  delay: 0     },
            { pct: 22, label: 'Assessing Task Achievement...',             delay: 2500  },
            { pct: 38, label: 'Assessing Coherence & Cohesion...',         delay: 5500  },
            { pct: 54, label: 'Assessing Lexical Resource...',             delay: 9000  },
            { pct: 70, label: 'Assessing Grammatical Range & Accuracy...', delay: 12500 },
            { pct: 83, label: 'Checking spelling & grammar issues...',     delay: 16000 },
            { pct: 93, label: 'Compiling your feedback report...',         delay: 20000 },
        ];

        $('#ielts-progress-fill').css('width', '0%');
        $('#ielts-progress-label').text(steps[0].label).css('opacity', 1);

        steps.forEach(function(step) {
            var t = setTimeout(function() {
                $('#ielts-progress-fill').css('width', step.pct + '%');
                $('#ielts-progress-label').css('opacity', 0);
                setTimeout(function() {
                    $('#ielts-progress-label').text(step.label).css('opacity', 1);
                }, 200);
            }, step.delay);
            progressTimers.push(t);
        });
    }

    function completeProgress() {
        progressTimers.forEach(function(t) { clearTimeout(t); });
        progressTimers = [];
        $('#ielts-progress-fill').css('width', '100%');
        $('#ielts-progress-label').css('opacity', 0);
        setTimeout(function() {
            $('#ielts-progress-label').text('Done — loading your results...').css('opacity', 1);
        }, 200);
    }

    // ─── Submit Essay ────────────────────────────────────────────────
    $('#ielts-submit-btn').on('click', function() {
        var taskType   = $('#ielts-task-type').val();
        var taskPrompt = $.trim($('#ielts-task-prompt').val());
        var essayText  = $.trim($('#ielts-essay-text').val());

        if (!taskPrompt) {
            alert('Please enter the task prompt before submitting.');
            $('#ielts-task-prompt').focus();
            return;
        }

        if (!essayText) {
            alert('Please enter your essay before submitting.');
            $('#ielts-essay-text').focus();
            return;
        }

        var wordCount = countWords(essayText);
        if (wordCount < 50) {
            alert(
                'Your essay is too short to submit.\n\n' +
                'You have written ' + wordCount + ' word' + (wordCount === 1 ? '' : 's') + '. ' +
                'The minimum this system will accept is 50 words.\n\n' +
                'Important: Reaching the IELTS minimum word count directly affects your score. ' +
                'Task 2 requires at least 250 words, Task 1 requires at least 150 words. ' +
                'Writing under the limit will result in a penalty to your Task Achievement score.'
            );
            return;
        }

        // Check for paragraph breaks
        var paraCount = essayText.trim().split(/\n\s*\n|\r\n\s*\r\n|\r\s*\r/).filter(function(p) { return p.trim().length > 0; }).length;
        if (paraCount <= 1 && wordCount > 80) {
            var proceed = confirm(
                'Warning: No paragraph breaks detected in your essay.\n\n' +
                'This may have happened if you pasted your essay from Word or another document and the formatting was lost.\n\n' +
                'In IELTS, paragraphing is required and affects your Coherence & Cohesion score. An essay without paragraph breaks cannot score above Band 6 for this criterion.\n\n' +
                'To fix this, make sure there is a blank line between each paragraph.\n\n' +
                'Press OK to submit anyway, or Cancel to go back and check your formatting.'
            );
            if (!proceed) return;
        }

        $('#ielts-writing-form-section').hide();
        $('#ielts-writing-loading').show();
        $('#ielts-writing-results').hide();
        startProgress();

        $.ajax({
            url: ielts_writing.ajax_url,
            method: 'POST',
            data: {
                action:      'ielts_cm_submit_writing',
                nonce:       ielts_writing.nonce,
                task_type:   taskType,
                task_prompt: taskPrompt,
                essay_text:  essayText,
            },
            success: function(response) {
                completeProgress();
                $('#ielts-writing-loading').hide();

                if (response.success) {
                    currentSubmissionId = response.data.submission_id;
                    $('#ielts-results-content').html(response.data.html);
                    $('#ielts-writing-results').show();
                    animateBars();
                    loadHistory();
                    $('html, body').animate({
                        scrollTop: $('#ielts-writing-results').offset().top - 30
                    }, 400);
                } else {
                    $('#ielts-writing-form-section').show();
                    alert('Error: ' + (response.data.message || 'Something went wrong. Please try again.'));
                }
            },
            error: function() {
                completeProgress();
                $('#ielts-writing-loading').hide();
                $('#ielts-writing-form-section').show();
                alert('A network error occurred. Please check your connection and try again.');
            }
        });
    });

    // ─── Animate criteria bars after results load ────────────────────
    function animateBars() {
        $('.ielts-criterion-fill').each(function() {
            var $bar  = $(this);
            var width = $bar.css('width');
            $bar.css('width', 0).animate({ width: width }, 800);
        });
    }

    // ─── Dispute Flow ────────────────────────────────────────────────
    $(document).on('click', '#ielts-dispute-btn', function() {
        $('#ielts-dispute-form').slideDown(200);
        $(this).hide();
    });

    $(document).on('click', '#ielts-dispute-cancel-btn', function() {
        $('#ielts-dispute-form').slideUp(200);
        $('#ielts-dispute-btn').show();
    });

    $(document).on('click', '#ielts-dispute-submit-btn', function() {
        var reason = $.trim($('#ielts-dispute-reason').val());

        if (!currentSubmissionId) {
            alert('Could not identify your submission. Please refresh and try again.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Submitting...');

        $.ajax({
            url: ielts_writing.ajax_url,
            method: 'POST',
            data: {
                action:        'ielts_cm_dispute_writing',
                nonce:         ielts_writing.nonce,
                submission_id: currentSubmissionId,
                reason:        reason,
            },
            success: function(response) {
                if (response.success) {
                    $('#ielts-dispute-form').hide();
                    $('#ielts-dispute-btn').hide();
                    $('#ielts-dispute-success').show();
                } else {
                    $btn.prop('disabled', false).text('Submit Dispute');
                    alert('Error: ' + (response.data.message || 'Could not submit dispute.'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Submit Dispute');
                alert('A network error occurred. Please try again.');
            }
        });
    });

    // ─── History Row Toggle ──────────────────────────────────────────
    $(document).on('click', '.ielts-history-toggle-btn', function() {
        var targetId = $(this).data('target');
        var $row = $('#' + targetId);
        var isVisible = $row.is(':visible');
        $row.slideToggle(200);
        $(this).text(isVisible ? 'View Feedback' : 'Hide Feedback');
    });

    // ─── Show All History ────────────────────────────────────────────
    $(document).on('click', '#ielts-show-all-history', function() {
        loadHistory(true);
    });

    // ─── Load History ────────────────────────────────────────────────
    function loadHistory(showAll) {
        $.ajax({
            url: ielts_writing.ajax_url,
            method: 'POST',
            data: {
                action:   'ielts_cm_get_writing_history',
                nonce:    ielts_writing.nonce,
                show_all: showAll ? 1 : 0,
            },
            success: function(response) {
                if (response.success) {
                    $('#ielts-writing-history').html(response.data.html);
                }
            }
        });
    }

    // Load history on page load
    loadHistory();

})(jQuery);
