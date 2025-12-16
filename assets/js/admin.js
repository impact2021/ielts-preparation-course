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
    
})(jQuery);
