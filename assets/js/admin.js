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
    
})(jQuery);
