/* global ielts_writing_admin, jQuery */
(function($) {
    'use strict';

    // ─── Save Override ────────────────────────────────────────────
    $('#ielts-save-override-btn').on('click', function() {
        var $form          = $('#ielts-override-form');
        var submissionId   = $form.data('submission-id');
        var $feedback      = $('#ielts-override-feedback');
        var $btn           = $(this);

        var data = {
            action:        'ielts_cm_writing_override',
            nonce:         ielts_writing_admin.nonce,
            submission_id: submissionId,
            admin_notes:   $form.find('[name="admin_notes"]').val(),
            admin_feedback:$form.find('[name="admin_feedback"]').val(),
        };

        $form.find('.ielts-score-select').each(function() {
            data[$(this).attr('name')] = $(this).val();
        });

        $btn.prop('disabled', true).text('Saving...');
        $feedback.hide();

        $.ajax({
            url: ielts_writing_admin.ajax_url,
            method: 'POST',
            data: data,
            success: function(response) {
                $btn.prop('disabled', false).text('Save Override');
                $feedback.removeClass('error success');

                if (response.success) {
                    $feedback.addClass('success').text(
                        response.data.message + ' Overall band recalculated to: ' + response.data.overall_band
                    ).show();
                } else {
                    $feedback.addClass('error').text(
                        'Error: ' + (response.data.message || 'Something went wrong.')
                    ).show();
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Save Override');
                $feedback.removeClass('error success').addClass('error')
                    .text('Network error. Please try again.').show();
            }
        });
    });

    // ─── Clear Override ───────────────────────────────────────────
    $('#ielts-clear-override-btn').on('click', function() {
        if (!confirm('Clear the manual override and revert to the original AI scores?')) return;

        var $form        = $('#ielts-override-form');
        var submissionId = $form.data('submission-id');
        var $feedback    = $('#ielts-override-feedback');
        var $btn         = $(this);

        $btn.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: ielts_writing_admin.ajax_url,
            method: 'POST',
            data: {
                action:         'ielts_cm_writing_override',
                nonce:          ielts_writing_admin.nonce,
                submission_id:  submissionId,
                clear_override: 1,
                admin_notes:    $form.find('[name="admin_notes"]').val(),
                admin_feedback: $form.find('[name="admin_feedback"]').val(),
            },
            success: function(response) {
                $btn.prop('disabled', false).text('Clear Override (revert to AI scores)');
                $feedback.removeClass('error success');

                if (response.success) {
                    $feedback.addClass('success').text(response.data.message).show();
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    $feedback.addClass('error').text('Error: ' + (response.data.message || 'Something went wrong.')).show();
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Clear Override (revert to AI scores)');
                $feedback.addClass('error').text('Network error. Please try again.').show();
            }
        });
    });

    // ─── Reset Submission Limit ───────────────────────────────────
    $('.ielts-reset-limit-btn').on('click', function() {
        if (!confirm('Reset this student\'s 24-hour submission window? They will be able to submit again immediately.')) return;

        var $btn       = $(this);
        var userId     = $btn.data('user-id');
        var submissionId = $btn.data('submission-id');
        var $feedback  = $('#ielts-reset-feedback');

        $btn.prop('disabled', true).text('Resetting...');

        $.ajax({
            url: ielts_writing_admin.ajax_url,
            method: 'POST',
            data: {
                action:        'ielts_cm_writing_reset_limit',
                nonce:         ielts_writing_admin.nonce,
                user_id:       userId,
                submission_id: submissionId,
            },
            success: function(response) {
                $btn.prop('disabled', false).text('Reset Submission Window for This Student');
                if (response.success) {
                    $feedback.css({ display: 'block', color: '#1a6b3a', background: '#e6f4ea', padding: '8px 12px', borderRadius: '4px' })
                        .text('✓ ' + response.data.message);
                } else {
                    $feedback.css({ display: 'block', color: '#7a1e1e', background: '#fce8e6', padding: '8px 12px', borderRadius: '4px' })
                        .text('Error: ' + (response.data.message || 'Something went wrong.'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Reset Submission Window for This Student');
                $feedback.css({ display: 'block', color: '#7a1e1e' }).text('Network error.');
            }
        });
    });

})(jQuery);

    // ─── Delete Submission ────────────────────────────────────────
    $('.ielts-delete-submission-btn').on('click', function() {
        if (!confirm('Permanently delete this submission? This cannot be undone.')) return;

        var $btn         = $(this);
        var submissionId = $btn.data('submission-id');
        var $feedback    = $('#ielts-delete-feedback');

        $btn.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: ielts_writing_admin.ajax_url,
            method: 'POST',
            data: {
                action:        'ielts_cm_delete_submission',
                nonce:         ielts_writing_admin.nonce,
                submission_id: submissionId,
            },
            success: function(response) {
                if (response.success) {
                    $feedback.css({ display: 'block', color: '#1a6b3a', background: '#e6f4ea', padding: '8px 12px', borderRadius: '4px' })
                        .text('Submission deleted. Redirecting...');
                    setTimeout(function() {
                        window.location.href = '?post_type=ielts_course&page=ielts-writing-submissions';
                    }, 1500);
                } else {
                    $btn.prop('disabled', false).text('Delete This Submission');
                    $feedback.css({ display: 'block', color: '#7a1e1e', background: '#fce8e6', padding: '8px 12px', borderRadius: '4px' })
                        .text('Error: ' + (response.data.message || 'Could not delete.'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Delete This Submission');
                $feedback.css({ display: 'block', color: '#7a1e1e' }).text('Network error.');
            }
        });
    });
