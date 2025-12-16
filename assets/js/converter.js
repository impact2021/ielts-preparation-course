/**
 * LearnDash to IELTS Course Manager Converter
 */
(function($) {
    'use strict';
    
    let selectedCourses = [];
    let currentCourseIndex = 0;
    let totalCourses = 0;
    let conversionResults = {
        courses: 0,
        lessons: 0,
        topics: 0,
        quizzes: 0,
        errors: []
    };
    
    $(document).ready(function() {
        // Select all courses checkbox
        $('#select-all-courses').on('change', function() {
            const checked = $(this).prop('checked');
            $('.course-checkbox:not(:disabled)').prop('checked', checked);
        });
        
        // Convert button click
        $('#convert-courses-btn').on('click', function() {
            // Get selected courses
            selectedCourses = [];
            $('.course-checkbox:checked').each(function() {
                selectedCourses.push($(this).val());
            });
            
            if (selectedCourses.length === 0) {
                alert('Please select at least one course to convert.');
                return;
            }
            
            // Confirm
            if (!confirm(ieltsCMConverter.strings.confirm)) {
                return;
            }
            
            // Start conversion
            startConversion();
        });
        
        // Close modal button
        $('#close-modal-btn').on('click', function() {
            $('#conversion-modal').fadeOut();
            // Reload page to show updated course list
            location.reload();
        });
    });
    
    function startConversion() {
        // Reset state
        currentCourseIndex = 0;
        totalCourses = selectedCourses.length;
        conversionResults = {
            courses: 0,
            lessons: 0,
            topics: 0,
            quizzes: 0,
            errors: []
        };
        
        // Show modal
        $('#conversion-modal').fadeIn();
        $('#conversion-log').empty();
        $('#conversion-summary').hide();
        $('#conversion-errors').hide();
        $('#close-modal-btn').prop('disabled', true);
        
        // Update progress
        updateProgress();
        
        // Start converting
        convertNextCourse();
    }
    
    function convertNextCourse() {
        if (currentCourseIndex >= totalCourses) {
            // All done
            showCompletionSummary();
            return;
        }
        
        const courseId = selectedCourses[currentCourseIndex];
        
        addLogEntry('Converting course ID: ' + courseId + '...', 'info');
        
        // AJAX request
        $.ajax({
            url: ieltsCMConverter.ajaxurl,
            type: 'POST',
            data: {
                action: 'ielts_cm_convert_course',
                nonce: ieltsCMConverter.nonce,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    handleConversionSuccess(response.data);
                } else {
                    handleConversionError(response.data);
                }
                
                // Move to next course
                currentCourseIndex++;
                updateProgress();
                
                // Small delay before next conversion
                setTimeout(convertNextCourse, 500);
            },
            error: function(xhr, status, error) {
                addLogEntry('AJAX Error: ' + error, 'error');
                conversionResults.errors.push('AJAX Error: ' + error);
                
                // Move to next course anyway
                currentCourseIndex++;
                updateProgress();
                
                setTimeout(convertNextCourse, 500);
            }
        });
    }
    
    function handleConversionSuccess(data) {
        // Update totals
        conversionResults.courses += data.courses || 0;
        conversionResults.lessons += data.lessons || 0;
        conversionResults.topics += data.topics || 0;
        conversionResults.quizzes += data.quizzes || 0;
        
        // Add log entries
        if (data.log && data.log.length > 0) {
            data.log.forEach(function(entry) {
                addLogEntry(entry.message, entry.level);
            });
        }
        
        // Track errors
        if (data.errors && data.errors.length > 0) {
            conversionResults.errors = conversionResults.errors.concat(data.errors);
        }
    }
    
    function handleConversionError(data) {
        addLogEntry('Conversion failed!', 'error');
        
        if (data.log && data.log.length > 0) {
            data.log.forEach(function(entry) {
                addLogEntry(entry.message, entry.level);
            });
        }
        
        if (data.errors && data.errors.length > 0) {
            conversionResults.errors = conversionResults.errors.concat(data.errors);
        }
        
        if (data.message) {
            conversionResults.errors.push(data.message);
        }
    }
    
    function updateProgress() {
        const percentage = totalCourses > 0 ? (currentCourseIndex / totalCourses) * 100 : 0;
        $('.progress-bar').css('width', percentage + '%');
        $('.progress-text .current').text(currentCourseIndex);
        $('.progress-text .total').text(totalCourses);
    }
    
    function addLogEntry(message, level) {
        level = level || 'info';
        const $entry = $('<div>')
            .addClass('log-entry')
            .addClass(level)
            .text('[' + level.toUpperCase() + '] ' + message);
        
        $('#conversion-log').append($entry);
        
        // Auto-scroll to bottom
        const logContainer = $('#conversion-log')[0];
        logContainer.scrollTop = logContainer.scrollHeight;
    }
    
    function showCompletionSummary() {
        addLogEntry('All conversions completed!', 'info');
        
        // Show summary
        const $summary = $('#conversion-summary');
        $summary.html(
            '<h3>' + ieltsCMConverter.strings.success + '</h3>' +
            '<ul>' +
            '<li><strong>Courses:</strong> ' + conversionResults.courses + '</li>' +
            '<li><strong>Lessons:</strong> ' + conversionResults.lessons + '</li>' +
            '<li><strong>Topics/Lesson Pages:</strong> ' + conversionResults.topics + '</li>' +
            '<li><strong>Quizzes:</strong> ' + conversionResults.quizzes + '</li>' +
            '</ul>'
        );
        $summary.show();
        
        // Show errors if any
        if (conversionResults.errors.length > 0) {
            const $errors = $('#conversion-errors');
            let errorHtml = '<h3>' + ieltsCMConverter.strings.error + '</h3><ul>';
            conversionResults.errors.forEach(function(error) {
                errorHtml += '<li>' + error + '</li>';
            });
            errorHtml += '</ul>';
            $errors.html(errorHtml);
            $errors.show();
        }
        
        // Enable close button
        $('#close-modal-btn').prop('disabled', false);
    }
    
})(jQuery);
