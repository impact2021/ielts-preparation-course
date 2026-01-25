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
            showPaymentSection(membershipType, price);
        } else {
            hidePaymentSection();
        }
    });
    
    function showPaymentSection(membershipType, price) {
        const $paymentSection = $('#ielts-payment-section');
        
        // Show the section
        $paymentSection.slideDown();
        
        // Validate required fields before creating payment intent
        const email = $('#ielts_email').val();
        const firstName = $('#ielts_first_name').val();
        const lastName = $('#ielts_last_name').val();
        
        if (!email || !firstName || !lastName) {
            showError('Please fill in your name and email first');
            return;
        }
        
        // Create Payment Intent
        $.ajax({
            url: ieltsPayment.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ielts_create_payment_intent',
                nonce: ieltsPayment.nonce,
                membership_type: membershipType,
                email: email,
                first_name: firstName,
                last_name: lastName,
            },
            success: function(response) {
                if (response.success) {
                    initializePaymentElement(response.data.clientSecret);
                } else {
                    showError(response.data || 'Failed to initialize payment');
                }
            },
            error: function() {
                showError('Network error. Please try again.');
            }
        });
    }
    
    function hidePaymentSection() {
        $('#ielts-payment-section').slideUp();
        if (elements) {
            elements = null;
            paymentElement = null;
        }
    }
    
    function initializePaymentElement(clientSecret) {
        // Clear any existing payment element
        $('#payment-element').empty();
        
        // Create Elements instance
        elements = stripe.elements({ clientSecret });
        
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
            handlePaymentSubmission();
        }
        // Otherwise, allow normal form submission for free registrations
    });
    
    async function handlePaymentSubmission() {
        setLoading(true);
        
        // Confirm payment with Stripe
        const {error} = await stripe.confirmPayment({
            elements,
            confirmParams: {
                // Return URL after successful payment
                return_url: window.location.href + '?payment=success',
            },
            redirect: 'if_required',
        });
        
        if (error) {
            // Payment failed
            showError(error.message);
            setLoading(false);
        } else {
            // Payment succeeded
            // The webhook will create the user account
            showSuccess('Payment successful! Your account is being created...');
            
            // Redirect to success page
            setTimeout(function() {
                window.location.href = window.location.href + '?registration=success';
            }, 2000);
        }
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
