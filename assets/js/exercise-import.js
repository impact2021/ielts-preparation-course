/**
 * Exercise Import JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle tab switching
        $('.ielts-import-tab-btn').on('click', function() {
            var tab = $(this).data('tab');
            var container = $(this).closest('.ielts_cm_exercise_import');
            
            // Update tab buttons
            container.find('.ielts-import-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update tab content
            container.find('.ielts-import-tab-content').hide();
            container.find('[data-tab-content="' + tab + '"]').show();
        });
        
        // Handle file import button click
        $('[id^="ielts_cm_import_btn_"]').on('click', function(e) {
            e.preventDefault();
            
            var exerciseId = $(this).data('exercise-id');
            var fileInput = $('#ielts_cm_import_file_' + exerciseId);
            var statusDiv = $('#ielts_cm_import_status_' + exerciseId);
            var button = $(this);
            
            // Check if file is selected (jQuery element check, then native DOM for files property)
            if (!fileInput.length || !fileInput[0].files || !fileInput[0].files.length) {
                statusDiv.html('<div class="notice notice-error inline"><p>' + ieltsCMImport.i18n.noFile + '</p></div>');
                return;
            }
            
            // Confirm import
            if (!confirm(ieltsCMImport.i18n.confirmImport)) {
                return;
            }
            
            // Get the nonce from the form
            var nonce = $('#ielts_cm_import_exercise_nonce').val();
            
            // Prepare form data
            var formData = new FormData();
            formData.append('action', 'ielts_cm_import_exercise_direct');
            formData.append('exercise_id', exerciseId);
            formData.append('nonce', nonce);
            formData.append('import_file', fileInput[0].files[0]);
            
            // Disable button and show status
            button.prop('disabled', true);
            statusDiv.html('<div class="notice notice-info inline"><p>' + ieltsCMImport.i18n.importing + '</p></div>');
            
            // Send AJAX request
            $.ajax({
                url: ieltsCMImport.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        statusDiv.html('<div class="notice notice-success inline"><p>' + ieltsCMImport.i18n.success + '</p></div>');
                        // Reload the page after a short delay to show the imported content
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        var message = response.data && response.data.message ? response.data.message : ieltsCMImport.i18n.error;
                        statusDiv.html('<div class="notice notice-error inline"><p>' + message + '</p></div>');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    statusDiv.html('<div class="notice notice-error inline"><p>' + ieltsCMImport.i18n.error + '</p></div>');
                    button.prop('disabled', false);
                    console.error('Import error:', error);
                }
            });
        });
        
        // Handle JSON text import button click
        $('[id^="ielts_cm_import_json_btn_"]').on('click', function(e) {
            e.preventDefault();
            
            var exerciseId = $(this).data('exercise-id');
            var jsonTextarea = $('#ielts_cm_import_json_' + exerciseId);
            var statusDiv = $('#ielts_cm_import_status_' + exerciseId);
            var button = $(this);
            
            // Get JSON content
            var jsonContent = jsonTextarea.val().trim();
            
            // Check if JSON content is provided
            if (!jsonContent) {
                statusDiv.html('<div class="notice notice-error inline"><p>' + ieltsCMImport.i18n.noJson + '</p></div>');
                return;
            }
            
            // Confirm import
            if (!confirm(ieltsCMImport.i18n.confirmImport)) {
                return;
            }
            
            // Get the nonce from the form
            var nonce = $('#ielts_cm_import_exercise_nonce').val();
            
            // Disable button and show status
            button.prop('disabled', true);
            statusDiv.html('<div class="notice notice-info inline"><p>' + ieltsCMImport.i18n.importing + '</p></div>');
            
            // Send AJAX request
            $.ajax({
                url: ieltsCMImport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_import_exercise_json_text',
                    exercise_id: exerciseId,
                    nonce: nonce,
                    json_content: jsonContent
                },
                success: function(response) {
                    if (response.success) {
                        statusDiv.html('<div class="notice notice-success inline"><p>' + ieltsCMImport.i18n.success + '</p></div>');
                        // Reload the page after a short delay to show the imported content
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        var message = response.data && response.data.message ? response.data.message : ieltsCMImport.i18n.error;
                        statusDiv.html('<div class="notice notice-error inline"><p>' + message + '</p></div>');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    statusDiv.html('<div class="notice notice-error inline"><p>' + ieltsCMImport.i18n.error + '</p></div>');
                    button.prop('disabled', false);
                    console.error('Import error:', error);
                }
            });
        });
    });
})(jQuery);
