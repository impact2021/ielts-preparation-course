/**
 * Admin JavaScript for IELTS Course Manager
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize lesson ordering sortable
        if ($('#course-lessons-sortable').length && typeof ieltsCMAdmin !== 'undefined') {
            initLessonOrdering();
        }
        
        // Initialize lesson page ordering sortable
        if ($('#lesson-pages-sortable').length && typeof ieltsCMAdmin !== 'undefined') {
            initPageOrdering();
        }
        
        // Initialize lesson content (pages and exercises) ordering sortable
        if ($('#lesson-content-sortable').length && typeof ieltsCMAdmin !== 'undefined') {
            initContentOrdering();
        }
    });
    
    /**
     * Initialize drag-and-drop lesson ordering
     */
    function initLessonOrdering() {
        $('#course-lessons-sortable').sortable({
            placeholder: 'ui-sortable-placeholder',
            update: function(event, ui) {
                var lessonOrder = [];
                $('#course-lessons-sortable .lesson-item').each(function(index) {
                    lessonOrder.push({
                        lesson_id: $(this).data('lesson-id'),
                        order: index
                    });
                });
                
                // Save the new order via AJAX
                $.ajax({
                    url: ieltsCMAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_update_lesson_order',
                        nonce: ieltsCMAdmin.lessonOrderNonce,
                        course_id: ieltsCMAdmin.courseId,
                        lesson_order: lessonOrder
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.lesson-order-status')
                                .removeClass('error')
                                .addClass('success')
                                .text(ieltsCMAdmin.i18n.orderUpdated)
                                .fadeIn()
                                .delay(3000)
                                .fadeOut();
                            
                            // Update the order numbers in the UI
                            $('#course-lessons-sortable .lesson-item').each(function(index) {
                                $(this).find('.lesson-order').text(ieltsCMAdmin.i18n.orderLabel + ' ' + (index + 1));
                            });
                        } else {
                            $('.lesson-order-status')
                                .removeClass('success')
                                .addClass('error')
                                .text(ieltsCMAdmin.i18n.orderFailed)
                                .fadeIn()
                                .delay(5000)
                                .fadeOut();
                        }
                    },
                    error: function() {
                        $('.lesson-order-status')
                            .removeClass('success')
                            .addClass('error')
                            .text(ieltsCMAdmin.i18n.orderError)
                            .fadeIn()
                            .delay(5000)
                            .fadeOut();
                    }
                });
            }
        });
    }
    
    /**
     * Initialize drag-and-drop lesson page ordering
     */
    function initPageOrdering() {
        $('#lesson-pages-sortable').sortable({
            placeholder: 'ui-sortable-placeholder',
            update: function(event, ui) {
                var pageOrder = [];
                $('#lesson-pages-sortable .page-item').each(function(index) {
                    pageOrder.push({
                        page_id: $(this).data('page-id'),
                        order: index
                    });
                });
                
                // Save the new order via AJAX
                $.ajax({
                    url: ieltsCMAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_update_page_order',
                        nonce: ieltsCMAdmin.pageOrderNonce,
                        lesson_id: ieltsCMAdmin.lessonId,
                        page_order: pageOrder
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.page-order-status')
                                .removeClass('error')
                                .addClass('success')
                                .text(ieltsCMAdmin.i18n.pageOrderUpdated)
                                .fadeIn()
                                .delay(3000)
                                .fadeOut();
                            
                            // Update the order numbers in the UI
                            $('#lesson-pages-sortable .page-item').each(function(index) {
                                $(this).find('.page-order').text(ieltsCMAdmin.i18n.orderLabel + ' ' + (index + 1));
                            });
                        } else {
                            $('.page-order-status')
                                .removeClass('success')
                                .addClass('error')
                                .text(ieltsCMAdmin.i18n.pageOrderFailed)
                                .fadeIn()
                                .delay(5000)
                                .fadeOut();
                        }
                    },
                    error: function() {
                        $('.page-order-status')
                            .removeClass('success')
                            .addClass('error')
                            .text(ieltsCMAdmin.i18n.pageOrderError)
                            .fadeIn()
                            .delay(5000)
                            .fadeOut();
                    }
                });
            }
        });
    }
    
    /**
     * Initialize drag-and-drop lesson content (pages and exercises) ordering
     */
    function initContentOrdering() {
        $('#lesson-content-sortable').sortable({
            placeholder: 'ui-sortable-placeholder',
            update: function(event, ui) {
                var contentOrder = [];
                $('#lesson-content-sortable .content-item').each(function(index) {
                    contentOrder.push({
                        item_id: $(this).data('item-id'),
                        item_type: $(this).data('item-type'),
                        order: index
                    });
                });
                
                // Save the new order via AJAX
                $.ajax({
                    url: ieltsCMAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_update_content_order',
                        nonce: ieltsCMAdmin.contentOrderNonce,
                        lesson_id: ieltsCMAdmin.lessonId,
                        content_order: contentOrder
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.content-order-status')
                                .removeClass('error')
                                .addClass('success')
                                .text(ieltsCMAdmin.i18n.contentOrderUpdated)
                                .fadeIn()
                                .delay(3000)
                                .fadeOut();
                            
                            // Update the order numbers in the UI
                            $('#lesson-content-sortable .content-item').each(function(index) {
                                $(this).find('.item-order').text(ieltsCMAdmin.i18n.orderLabel + ' ' + index);
                            });
                        } else {
                            $('.content-order-status')
                                .removeClass('success')
                                .addClass('error')
                                .text(ieltsCMAdmin.i18n.contentOrderFailed)
                                .fadeIn()
                                .delay(5000)
                                .fadeOut();
                        }
                    },
                    error: function() {
                        $('.content-order-status')
                            .removeClass('success')
                            .addClass('error')
                            .text(ieltsCMAdmin.i18n.contentOrderError)
                            .fadeIn()
                            .delay(5000)
                            .fadeOut();
                    }
                });
            }
        });
    }
    
    /**
     * Initialize course lessons management (add, remove, search)
     */
    $(document).ready(function() {
        // Search functionality for lessons
        $('#course-lesson-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            $('#course-lesson-selector option').each(function() {
                var lessonTitle = $(this).text().toLowerCase();
                if (lessonTitle.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Add lesson to course
        $('#add-lesson-to-course').on('click', function() {
            var lessonId = $('#course-lesson-selector').val();
            var courseId = typeof ieltsCMAdmin !== 'undefined' ? ieltsCMAdmin.courseId : $('#post_ID').val();
            
            if (!lessonId) {
                alert('Please select a lesson to add.');
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_add_lesson_to_course',
                    nonce: typeof ieltsCMAdmin !== 'undefined' ? ieltsCMAdmin.courseLessonsNonce : '',
                    course_id: courseId,
                    lesson_id: lessonId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove from selector
                        $('#course-lesson-selector option:selected').remove();
                        
                        // Add to lessons list
                        var lesson = response.data.lesson;
                        var lessonHtml = '<li class="lesson-item" data-lesson-id="' + lesson.id + '">' +
                            '<span class="dashicons dashicons-menu"></span>' +
                            '<span class="lesson-title">' + lesson.title + '</span>' +
                            '<span class="lesson-order">Order: ' + lesson.order + '</span>' +
                            '<a href="' + lesson.edit_link + '" class="button button-small" target="_blank">Edit</a>' +
                            '<button type="button" class="button button-small remove-lesson-from-course" data-lesson-id="' + lesson.id + '">Remove</button>' +
                            '</li>';
                        
                        if ($('#course-lessons-sortable').length === 0) {
                            // Create the list if it doesn't exist
                            var listHtml = '<h4>Course Lessons</h4>' +
                                '<p>Drag and drop lessons to reorder them:</p>' +
                                '<ul id="course-lessons-sortable" class="course-lessons-list">' + lessonHtml + '</ul>' +
                                '<div class="lesson-order-status"></div>';
                            $('#ielts-cm-course-lessons p:first').replaceWith(listHtml);
                            
                            // Reinitialize sortable
                            if (typeof initLessonOrdering !== 'undefined') {
                                initLessonOrdering();
                            }
                        } else {
                            $('#course-lessons-sortable').append(lessonHtml);
                        }
                        
                        // Show success message
                        $('.lesson-order-status')
                            .removeClass('error')
                            .addClass('success')
                            .text(response.data.message)
                            .fadeIn()
                            .delay(3000)
                            .fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to add lesson. Please try again.');
                }
            });
        });
        
        // Remove lesson from course
        $(document).on('click', '.remove-lesson-from-course', function() {
            if (!confirm('Are you sure you want to remove this lesson from the course?')) {
                return;
            }
            
            var lessonId = $(this).data('lesson-id');
            var courseId = typeof ieltsCMAdmin !== 'undefined' ? ieltsCMAdmin.courseId : $('#post_ID').val();
            var $lessonItem = $(this).closest('.lesson-item');
            var lessonTitle = $lessonItem.find('.lesson-title').text();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_remove_lesson_from_course',
                    nonce: typeof ieltsCMAdmin !== 'undefined' ? ieltsCMAdmin.courseLessonsNonce : '',
                    course_id: courseId,
                    lesson_id: lessonId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove from list
                        $lessonItem.remove();
                        
                        // Add back to selector
                        $('#course-lesson-selector').append('<option value="' + lessonId + '">' + lessonTitle + '</option>');
                        
                        // Show success message
                        $('.lesson-order-status')
                            .removeClass('error')
                            .addClass('success')
                            .text(response.data.message)
                            .fadeIn()
                            .delay(3000)
                            .fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to remove lesson. Please try again.');
                }
            });
        });
        
        // Search functionality for lesson content
        $('#lesson-content-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            $('#lesson-content-selector option').each(function() {
                var contentTitle = $(this).text().toLowerCase();
                if (contentTitle.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Add content to lesson
        $('#add-content-to-lesson').on('click', function() {
            var contentId = $('#lesson-content-selector').val();
            var lessonId = $('#post_ID').val();
            var contentType = $('input[name="content-type-selector"]:checked').val();
            
            if (!contentId) {
                alert('Please select content to add.');
                return;
            }
            
            // Determine post type based on content type
            var postType = contentType === 'exercise' ? 'quiz' : 'resource';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_add_content_to_lesson',
                    nonce: typeof ieltsCMAdmin !== 'undefined' ? ieltsCMAdmin.lessonContentNonce : '',
                    lesson_id: lessonId,
                    content_id: contentId,
                    content_type: postType
                },
                success: function(response) {
                    if (response.success) {
                        // Remove from selector
                        $('#lesson-content-selector option:selected').remove();
                        
                        // Add to content list
                        var content = response.data.content;
                        var typeLabel = content.type === 'quiz' ? 'Exercise' : 'Page';
                        var contentHtml = '<li class="content-item content-item-' + content.type + '" ' +
                            'data-item-id="' + content.id + '" data-item-type="' + content.type + '">' +
                            '<span class="dashicons dashicons-menu"></span>' +
                            '<span class="item-type-badge ' + content.type + '">' + typeLabel + '</span>' +
                            '<span class="item-title">' + content.title + '</span>' +
                            '<span class="item-order">Order: ' + content.order + '</span>' +
                            '<a href="' + content.edit_link + '" class="button button-small" target="_blank">Edit</a>' +
                            '<button type="button" class="button button-small remove-content-from-lesson" data-content-id="' + content.id + '" data-content-type="' + content.type + '">Remove</button>' +
                            '</li>';
                        
                        if ($('#lesson-content-sortable').length === 0) {
                            // Create the list if it doesn't exist
                            var listHtml = '<h4>Lesson Content</h4>' +
                                '<p>Drag and drop items to reorder them. You can mix lesson pages and exercises in any order:</p>' +
                                '<ul id="lesson-content-sortable" class="lesson-content-list">' + contentHtml + '</ul>' +
                                '<div class="content-order-status"></div>';
                            $('#ielts-cm-lesson-content p:first').replaceWith(listHtml);
                            
                            // Reinitialize sortable
                            if (typeof initContentOrdering !== 'undefined') {
                                initContentOrdering();
                            }
                        } else {
                            $('#lesson-content-sortable').append(contentHtml);
                        }
                        
                        // Show success message
                        $('.content-order-status')
                            .removeClass('error')
                            .addClass('success')
                            .text(response.data.message)
                            .fadeIn()
                            .delay(3000)
                            .fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to add content. Please try again.');
                }
            });
        });
        
        // Remove content from lesson
        $(document).on('click', '.remove-content-from-lesson', function() {
            if (!confirm('Are you sure you want to remove this content from the lesson?')) {
                return;
            }
            
            var contentId = $(this).data('content-id');
            var contentType = $(this).data('content-type');
            var lessonId = $('#post_ID').val();
            var $contentItem = $(this).closest('.content-item');
            var contentTitle = $contentItem.find('.item-title').text();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_remove_content_from_lesson',
                    nonce: typeof ieltsCMAdmin !== 'undefined' ? ieltsCMAdmin.lessonContentNonce : '',
                    lesson_id: lessonId,
                    content_id: contentId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove from list
                        $contentItem.remove();
                        
                        // Add back to selector if it matches current content type
                        var currentContentType = $('input[name="content-type-selector"]:checked').val();
                        var shouldAddBack = (currentContentType === 'sublesson' && contentType === 'resource') ||
                                          (currentContentType === 'exercise' && contentType === 'quiz');
                        
                        if (shouldAddBack) {
                            var optionType = contentType === 'quiz' ? 'exercise' : 'sublesson';
                            $('#lesson-content-selector').append('<option value="' + contentId + '" data-type="' + optionType + '">' + contentTitle + '</option>');
                        }
                        
                        // Show success message
                        $('.content-order-status')
                            .removeClass('error')
                            .addClass('success')
                            .text(response.data.message)
                            .fadeIn()
                            .delay(3000)
                            .fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to remove content. Please try again.');
                }
            });
        });
    });
    
    /**
     * Clone course functionality
     */
    $(document).on('click', '#ielts-cm-clone-course-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $status = $('#ielts-cm-clone-status');
        
        // Confirm with user
        if (!confirm('Are you sure you want to clone this course? This will create a copy with the same table of contents, referencing the same lessons, sub-lessons, and exercises (not creating duplicates).')) {
            return;
        }
        
        // Disable button and show loading
        $button.prop('disabled', true).text('Cloning...');
        $status.removeClass('success error').hide();
        
        // Get course ID from the page
        var courseId = $('#post_ID').val();
        
        if (!courseId) {
            $status.addClass('error').text('Error: Course ID not found').fadeIn();
            $button.prop('disabled', false).text('Clone Course');
            return;
        }
        
        // Make AJAX request
        $.ajax({
            url: ieltsCMAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_cm_clone_course',
                nonce: ieltsCMAdmin.courseMetaNonce,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    $status
                        .addClass('success')
                        .html(response.data.message + '<br><a href="' + response.data.edit_link + '" target="_blank">Edit cloned course: ' + response.data.course_title + '</a>')
                        .fadeIn();
                    
                    // Reset button after delay
                    setTimeout(function() {
                        $button.prop('disabled', false).text('Clone Course');
                    }, 3000);
                } else {
                    $status.addClass('error').text('Error: ' + response.data.message).fadeIn();
                    $button.prop('disabled', false).text('Clone Course');
                }
            },
            error: function() {
                $status.addClass('error').text('Error: Failed to clone course. Please try again.').fadeIn();
                $button.prop('disabled', false).text('Clone Course');
            }
        });
    });
    
    /**
     * Convert audio time format (M:SS or MM:SS or seconds) to seconds
     * Accepts: "2:36", "12:45", "156", "156.5"
     * Returns: numeric seconds value or empty string for invalid input
     */
    function convertAudioTimeToSeconds(timeStr) {
        if (!timeStr || timeStr.trim() === '') {
            return '';
        }
        
        timeStr = timeStr.trim();
        
        // If it contains a colon, it's in M:SS or MM:SS format
        if (timeStr.indexOf(':') !== -1) {
            var parts = timeStr.split(':');
            if (parts.length === 2) {
                var minutes = parseInt(parts[0], 10);  // Parse as integer for whole minutes
                var seconds = parseFloat(parts[1]);
                // Validate: minutes >= 0, seconds >= 0 and < 60 (valid time format)
                if (!isNaN(minutes) && !isNaN(seconds) && minutes >= 0 && seconds >= 0 && seconds < 60) {
                    return (minutes * 60 + seconds).toString();
                }
            }
            // Invalid M:SS format - return empty string
            return '';
        }
        
        // Otherwise, assume it's already in seconds
        var numValue = parseFloat(timeStr);
        if (!isNaN(numValue) && numValue >= 0) {
            return numValue.toString();
        }
        
        // Invalid input - return empty string instead of original value
        return '';
    }
    
    /**
     * Process all audio time inputs before form submission
     * Converts M:SS format to seconds
     */
    function processAudioTimeInputs() {
        $('.audio-time-input').each(function() {
            var $input = $(this);
            var value = $input.val();
            if (value) {
                var converted = convertAudioTimeToSeconds(value);
                $input.val(converted);
            }
        });
    }
    
    // Process audio time inputs when quiz form is submitted
    $('form#post').on('submit', function() {
        if ($('#post_type').val() === 'ielts_quiz') {
            processAudioTimeInputs();
        }
    });
    
})(jQuery);
