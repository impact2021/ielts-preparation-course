(function($) {
    'use strict';
    
    let stripe;
    let elements;
    let paymentElement;
    
    // Initialize Stripe
    if (typeof Stripe !== 'undefined' && ieltsPayment && ieltsPayment.publishableKey) {
        stripe = Stripe(ieltsPayment.publishableKey);
    } else {
        console.error('IELTS Payment: Stripe not initialized. Check if Stripe.js is loaded and publishable key is configured.');
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Listen for membership type selection
        $('#ielts_membership_type').on('change', function() {
            const membershipType = $(this).val();
            const price = ieltsPayment.pricing[membershipType] || 0;
            
            console.log('IELTS Payment: Membership type selected:', membershipType, 'Price:', price);
            
            // Show/hide payment section based on price
            if (price > 0) {
                showPaymentSection(price);
            } else {
                hidePaymentSection();
            }
        });
        
        // Trigger change event on page load to show payment section if membership is pre-selected
        const $membershipSelect = $('#ielts_membership_type');
        if ($membershipSelect.val()) {
            $membershipSelect.trigger('change');
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
        if (!stripe) {
            console.error('IELTS Payment: Cannot initialize payment element - Stripe not initialized');
            showError('Payment system is not configured. Please contact the site administrator.');
            return;
        }
        
        // Clear any existing payment element
        $('#payment-element').empty();
        
        try {
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
        } catch (error) {
            console.error('IELTS Payment: Error initializing payment element:', error);
            showError('Failed to initialize payment form. Please refresh the page and try again.');
        }
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
        
        // Check if user is logged in
        const isLoggedIn = ieltsPayment.user && ieltsPayment.user.isLoggedIn;
        
        if (isLoggedIn) {
            // User is already logged in (upgrading), skip user creation and go straight to payment
            createPaymentIntentAndConfirm(ieltsPayment.user.userId, membershipType, price);
        } else {
            // Get form data for new user registration
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
        const $button = $('#ielts_register_submit');
        const originalText = $button.data('original-text') || $button.text();
        
        // Store original text if not already stored
        if (!$button.data('original-text')) {
            $button.data('original-text', originalText);
        }
        
        if (isLoading) {
            $button.prop('disabled', true).text('Processing...');
        } else {
            $button.prop('disabled', false).text(originalText);
        }
    }
    
})(jQuery);
