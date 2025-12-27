/**
 * IELTS Membership System JavaScript
 */

jQuery(document).ready(function($) {
    
    // Login form
    $('#ielts-ms-login-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $message = $form.find('.ielts-ms-message');
        
        $button.addClass('loading').prop('disabled', true);
        $message.hide();
        
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_login',
                nonce: ieltsMS.nonce,
                username: $('#username').val(),
                password: $('#password').val(),
                remember: $('input[name="remember"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                    $button.removeClass('loading').prop('disabled', false);
                }
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $button.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Registration form
    $('#ielts-ms-register-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $message = $form.find('.ielts-ms-message');
        
        $button.addClass('loading').prop('disabled', true);
        $message.hide();
        
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_register',
                nonce: ieltsMS.nonce,
                username: $('#reg_username').val(),
                email: $('#reg_email').val(),
                password: $('#reg_password').val(),
                confirm_password: $('#reg_confirm_password').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                    $button.removeClass('loading').prop('disabled', false);
                }
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $button.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Forgot password form
    $('#ielts-ms-forgot-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $message = $form.find('.ielts-ms-message');
        
        $button.addClass('loading').prop('disabled', true);
        $message.hide();
        
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_forgot_password',
                nonce: ieltsMS.nonce,
                user_login: $('#user_login').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                }
                $button.removeClass('loading').prop('disabled', false);
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $button.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Reset password form
    $('#ielts-ms-reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $message = $form.find('.ielts-ms-message');
        
        $button.addClass('loading').prop('disabled', true);
        $message.hide();
        
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_reset_password',
                nonce: ieltsMS.nonce,
                key: $('input[name="key"]').val(),
                login: $('input[name="login"]').val(),
                password: $('#new_password').val(),
                confirm_password: $('#confirm_password').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1500);
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                    $button.removeClass('loading').prop('disabled', false);
                }
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $button.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Update email form
    $('#ielts-ms-update-email-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $message = $form.find('.ielts-ms-message');
        
        $button.addClass('loading').prop('disabled', true);
        $message.hide();
        
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_update_email',
                nonce: ieltsMS.nonce,
                new_email: $('#new_email').val(),
                password: $('#email_password').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                    $form[0].reset();
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                }
                $button.removeClass('loading').prop('disabled', false);
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $button.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Update password form
    $('#ielts-ms-update-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $message = $form.find('.ielts-ms-message');
        
        $button.addClass('loading').prop('disabled', true);
        $message.hide();
        
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_update_password',
                nonce: ieltsMS.nonce,
                current_password: $('#current_password').val(),
                new_password: $('#new_password').val(),
                confirm_password: $('#confirm_new_password').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                    $form[0].reset();
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                }
                $button.removeClass('loading').prop('disabled', false);
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $button.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Payment handling
    let selectedPlan = null;
    
    $('.purchase-btn').on('click', function() {
        selectedPlan = {
            plan_key: $(this).data('plan'),
            amount: $(this).data('amount'),
            days: $(this).data('days'),
            type: $(this).data('type')
        };
        
        $('#payment-gateway-selector').slideDown();
        $('html, body').animate({
            scrollTop: $('#payment-gateway-selector').offset().top - 100
        }, 500);
    });
    
    // Gateway selection
    $('.gateway-btn').on('click', function() {
        const gateway = $(this).data('gateway');
        
        if (!selectedPlan) {
            alert('Please select a plan first');
            return;
        }
        
        if (gateway === 'paypal') {
            processPayPalPayment(selectedPlan);
        } else if (gateway === 'stripe') {
            processStripePayment(selectedPlan);
        }
    });
    
    // Process PayPal payment
    function processPayPalPayment(plan) {
        // This would be handled server-side
        // For now, create a form and submit to PayPal
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_process_payment',
                nonce: ieltsMS.nonce,
                gateway: 'paypal',
                amount: plan.amount,
                duration_days: plan.days,
                payment_type: plan.type
            },
            success: function(response) {
                if (response.success && response.data.form_data) {
                    // Build PayPal form
                    const $form = $('#paypal-form');
                    $form.attr('action', response.data.redirect_url);
                    $form.empty();
                    
                    $.each(response.data.form_data, function(key, value) {
                        $form.append($('<input>').attr({
                            type: 'hidden',
                            name: key,
                            value: value
                        }));
                    });
                    
                    $form.submit();
                } else {
                    alert(response.data.message || 'Payment processing failed');
                }
            }
        });
    }
    
    // Process Stripe payment
    function processStripePayment(plan) {
        $.ajax({
            url: ieltsMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_ms_create_stripe_session',
                nonce: ieltsMS.nonce,
                plan_key: plan.plan_key,
                amount: plan.amount,
                duration_days: plan.days,
                payment_type: plan.type
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    alert(response.data.message || 'Payment processing failed');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    }
});
