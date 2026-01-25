(function($) {
    'use strict';
    
    let stripe;
    let elements;
    let paymentElement;
    
    // Initialize Stripe
    if (typeof Stripe !== 'undefined' && ieltsPayment.publishableKey) {
        stripe = Stripe(ieltsPayment.publishableKey);
    }
    
    // Listen for membership type selection
    $('#ielts_membership_type').on('change', function() {
        const membershipType = $(this).val();
        const price = ieltsPayment.pricing[membershipType] || 0;
        
        // Show/hide payment section based on price
        if (price > 0) {
            showPaymentSection(price);
        } else {
            hidePaymentSection();
        }
    });
    
    function showPaymentSection(price) {
        const $paymentSection = $('#ielts-payment-section');
        
        // Show the section
        $paymentSection.slideDown();
        
        // Initialize payment element if not already done or if price changed
        if (!elements || !paymentElement) {
            initializePaymentElement(price);
        }
    }
    
    function hidePaymentSection() {
        $('#ielts-payment-section').slideUp();
        if (elements) {
            elements = null;
            paymentElement = null;
        }
    }
    
    function initializePaymentElement(price) {
        // Clear any existing payment element
        $('#payment-element').empty();
        
        // Create Elements instance in payment mode with preset amount
        elements = stripe.elements({
            mode: 'payment',
            amount: Math.round(parseFloat(price) * 100), // Amount in cents
            currency: 'usd',
            appearance: {
                theme: 'stripe',
                variables: { colorPrimary: '#0073aa' }
            }
        });
        
        // Create and mount Payment Element
        paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
    }
    
    // Intercept form submission
    $('form[name="ielts_registration_form"]').on('submit', function(e) {
        const membershipType = $('#ielts_membership_type').val();
        const price = ieltsPayment.pricing[membershipType] || 0;
        
        // If it's a paid membership, handle payment first
        if (price > 0 && stripe && elements) {
            e.preventDefault();
            handlePaymentSubmission(membershipType, price);
        }
        // Otherwise, allow normal form submission for free registrations
    });
    
    async function handlePaymentSubmission(membershipType, price) {
        setLoading(true);
        
        // Validate card details first
        const {error: submitError} = await elements.submit();
        if (submitError) {
            showError(submitError.message);
            setLoading(false);
            return;
        }
        
        // Get form data
        const formData = {
            action: 'ielts_register_user',
            nonce: ieltsPayment.nonce,
            first_name: $('#ielts_first_name').val(),
            last_name: $('#ielts_last_name').val(),
            email: $('#ielts_email').val(),
            password: $('#ielts_password').val(),
            membership_type: membershipType,
            amount: price
        };
        
        // Create user account first
        $.ajax({
            url: ieltsPayment.ajaxUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // User created, now create payment intent
                    createPaymentIntentAndConfirm(response.data.user_id, membershipType, price);
                } else {
                    showError(response.data || 'Failed to create account');
                    setLoading(false);
                }
            },
            error: function() {
                showError('Network error. Please try again.');
                setLoading(false);
            }
        });
    }
    
    function createPaymentIntentAndConfirm(userId, membershipType, price) {
        // Create Payment Intent on server
        $.ajax({
            url: ieltsPayment.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ielts_create_payment_intent',
                nonce: ieltsPayment.nonce,
                user_id: userId,
                membership_type: membershipType,
                amount: price
            },
            success: async function(response) {
                if (response.success) {
                    // Confirm payment with Stripe
                    const {error, paymentIntent} = await stripe.confirmPayment({
                        elements,
                        clientSecret: response.data.clientSecret,
                        confirmParams: {
                            return_url: window.location.origin + window.location.pathname
                        },
                        redirect: 'if_required'
                    });
                    
                    if (error) {
                        showError(error.message);
                        setLoading(false);
                    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                        // Payment successful, confirm on server
                        confirmPaymentOnServer(paymentIntent.id, response.data.payment_id);
                    }
                } else {
                    showError(response.data || 'Failed to initialize payment');
                    setLoading(false);
                }
            },
            error: function() {
                showError('Network error. Please try again.');
                setLoading(false);
            }
        });
    }
    
    function confirmPaymentOnServer(paymentIntentId, paymentId) {
        $.ajax({
            url: ieltsPayment.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ielts_confirm_payment',
                nonce: ieltsPayment.nonce,
                payment_intent_id: paymentIntentId,
                payment_id: paymentId
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Payment successful! Your account is being created...');
                    setTimeout(function() {
                        window.location.href = response.data.redirect || (window.location.href + '?registration=success');
                    }, 2000);
                } else {
                    showError(response.data || 'Failed to confirm payment');
                    setLoading(false);
                }
            },
            error: function() {
                showError('Network error. Please try again.');
                setLoading(false);
            }
        });
    }
    
    function showError(message) {
        $('#payment-message').removeClass('success').addClass('error').text(message).show();
    }
    
    function showSuccess(message) {
        $('#payment-message').removeClass('error').addClass('success').text(message).show();
    }
    
    function setLoading(isLoading) {
        if (isLoading) {
            $('#ielts_register_submit').prop('disabled', true).text('Processing...');
        } else {
            $('#ielts_register_submit').prop('disabled', false).text('Create Account');
        }
    }
    
})(jQuery);
