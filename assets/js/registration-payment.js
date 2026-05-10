/**
 * IELTS Payment Registration Handler
 * 
 * Integrates with Stripe Payment API for membership registration.
 * 
 * Note: Browser console may show informational warnings from third-party services:
 * - Stripe.js cookie partitioning warnings (expected privacy feature)
 * - hCaptcha cross-site warnings (expected for CAPTCHA iframes)
 * - Font loading restrictions (browser security feature)
 * These warnings are informational and do not indicate functional issues.
 */
(function($) {
    'use strict';
    
    let stripe;
    let elements;
    let paymentElement;
    let selectedPaymentMethod = 'stripe'; // Tracks which payment method the user has selected
    
    // Helper function to get price for membership type
    function getPriceForMembershipType(membershipType) {
        if (!ieltsPayment || !membershipType) {
            return 0;
        }
        
        // Check if this is an extension option
        if (membershipType.startsWith('extension_')) {
            if (!ieltsPayment.extensionPricing) {
                return 0;
            }
            // Extract the extension duration (e.g., 'extension_1_week' -> '1_week')
            const duration = membershipType.replace('extension_', '');
            return ieltsPayment.extensionPricing[duration] || 0;
        }
        
        // Regular membership pricing
        if (!ieltsPayment.pricing) {
            return 0;
        }
        return ieltsPayment.pricing[membershipType] || 0;
    }
    
    // Check whether PWYW mode applies to the given membership type.
    // PWYW only applies to non-trial, non-extension full memberships on the primary site.
    function isPwywMembership(membershipType) {
        if (!ieltsPayment || !ieltsPayment.pwywEnabled) {
            return false;
        }
        if (!membershipType) {
            return false;
        }
        if (membershipType.startsWith('extension_')) {
            return false;
        }
        // Trial memberships end in '_trial'
        if (membershipType.endsWith('_trial')) {
            return false;
        }
        return true;
    }
    
    // Get the effective price to use for Stripe Elements initialisation.
    // For PWYW memberships we use the minimum as a placeholder; the actual
    // user-entered amount is read at submission time.
    function getEffectivePrice(membershipType) {
        if (isPwywMembership(membershipType)) {
            return ieltsPayment.pwywMinimum || 5.00;
        }
        return getPriceForMembershipType(membershipType);
    }
    
    // Show or hide the PWYW amount field.
    function togglePwywField(show) {
        const $container = $('#ielts-pwyw-container');
        if (!$container.length) return;
        if (show) {
            $container.show();
        } else {
            $container.hide();
        }
    }
    
    // Helper function to handle AJAX errors consistently
    // Note: Logs full error details to console for debugging purposes.
    // In production, consider limiting responseText logging if security is a concern.
    function handleAjaxError(jqXHR, textStatus, errorThrown, context) {
        console.error('IELTS Payment Error - ' + context + ':', {
            status: jqXHR.status,
            statusText: jqXHR.statusText,
            textStatus: textStatus,
            errorThrown: errorThrown,
            responseText: jqXHR.responseText
        });
        
        let errorMessage = 'Network error during ' + context + '.';
        let errorCode = null;
        
        if (jqXHR.responseJSON && jqXHR.responseJSON.data) {
            errorMessage = jqXHR.responseJSON.data;
            // Extract error code if present
            const codeMatch = errorMessage.match(/Error Code: ([A-Z0-9]+)/);
            if (codeMatch) {
                errorCode = codeMatch[1];
            }
        } else if (jqXHR.responseText) {
            errorMessage = 'Server error: ' + jqXHR.statusText;
        }
        
        // Build comprehensive error message for user
        let userMessage = errorMessage;
        
        // Add helpful debugging information
        if (jqXHR.status === 500) {
            userMessage += '\n\n🔍 Debugging Help:\n';
            userMessage += '• Check the browser console (F12) for detailed error information\n';
            
            // If user is admin, provide more specific help
            if (ieltsPayment.isAdmin) {
                userMessage += '• Visit WordPress Admin → IELTS Course → Payment Error Logs for detailed error history\n';
                userMessage += '• Check WordPress debug.log (usually in wp-content/debug.log) for server-side errors\n';
            } else {
                userMessage += '• Contact the site administrator with the error code shown above\n';
            }
            
            if (errorCode) {
                userMessage += '• Error Code: ' + errorCode + ' (provide this to support)\n';
            }
        } else if (jqXHR.status === 0) {
            userMessage += '\n\nThis may be a network connectivity issue. Please check your internet connection and try again.';
        }
        
        showError(userMessage);
        setLoading(false);
    }
    
    // Initialize Stripe
    if (typeof Stripe !== 'undefined' && ieltsPayment && ieltsPayment.publishableKey) {
        stripe = Stripe(ieltsPayment.publishableKey);
        console.log('✓ IELTS Payment: Stripe initialized successfully');
    } else {
        console.error('IELTS Payment: Stripe not initialized. Check if Stripe.js is loaded and publishable key is configured.');
        if (typeof Stripe === 'undefined') {
            console.error('  - Stripe.js library not loaded');
        }
        if (!ieltsPayment) {
            console.error('  - ieltsPayment object not defined');
        } else if (!ieltsPayment.publishableKey) {
            console.error('  - Publishable key not configured');
        }
    }
    
    // Log initialization state for extension debugging
    console.group('🚀 IELTS Payment Extension Script Loaded');
    console.log('Stripe available:', typeof Stripe !== 'undefined');
    console.log('ieltsPayment object:', ieltsPayment);
    console.log('Extension form found:', $('#ielts_membership_type_extension').length > 0);
    console.log('Extension payment section found:', $('#ielts-payment-section-extension').length > 0);
    console.groupEnd();
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Listen for membership type selection (registration form)
        $('#ielts_membership_type').on('change', function() {
            const membershipType = $(this).val();
            const price = getEffectivePrice(membershipType);
            
            // Check if this is a paid membership (not a trial) or an extension
            const isPaidMembership = membershipType && (!membershipType.endsWith('_trial') || membershipType.startsWith('extension_'));
            
            // Show or hide the PWYW field
            togglePwywField(isPwywMembership(membershipType));
            
            // Show/hide payment section based on price
            if (price > 0) {
                showPaymentSection(price);
            } else if (isPaidMembership && price <= 0) {
                // Paid membership with no price configured - show error
                hidePaymentSection();
                showError('This membership option is not properly configured. Please contact the site administrator or choose a different option.');
            } else {
                // Free membership (trial or explicitly free)
                hidePaymentSection();
            }
        });
        
        // Listen for extension type selection (extension form on account page)
        $('#ielts_membership_type_extension').on('change', function() {
            const membershipType = $(this).val();
            const price = getPriceForMembershipType(membershipType);
            
            // HYBRID SITE DEBUG: Log extension selection details
            console.group('🔍 Extension Selection Changed');
            console.log('Selected membership type:', membershipType);
            console.log('Calculated price:', price);
            console.log('Extension pricing available:', ieltsPayment.extensionPricing);
            
            if (membershipType) {
                const duration = membershipType.replace('extension_', '');
                console.log('Extracted duration:', duration);
                console.log('Price lookup result:', ieltsPayment.extensionPricing ? ieltsPayment.extensionPricing[duration] : 'extensionPricing not available');
            }
            console.groupEnd();
            
            // Show/hide payment section based on price
            if (price > 0) {
                console.log('✓ Price is valid, showing payment section');
                showPaymentSectionExtension(price);
            } else {
                console.warn('⚠️ Price is 0 or invalid, hiding payment section');
                hidePaymentSectionExtension();
                if (membershipType) {
                    const duration = membershipType.replace('extension_', '');
                    const availablePricing = ieltsPayment.extensionPricing ? JSON.stringify(ieltsPayment.extensionPricing) : 'undefined';
                    console.error('❌ Extension option not configured:', {
                        selectedType: membershipType,
                        extractedDuration: duration,
                        priceFound: price,
                        availablePricing: availablePricing
                    });
                    showErrorExtension('This extension option is not properly configured. Please contact the site administrator or choose a different option.');
                }
            }
        });
        
        // Trigger change event on page load to show payment section if membership is pre-selected
        const $membershipSelect = $('#ielts_membership_type');
        if ($membershipSelect.val()) {
            $membershipSelect.trigger('change');
        }
        
        const $extensionSelect = $('#ielts_membership_type_extension');
        if ($extensionSelect.val()) {
            $extensionSelect.trigger('change');
        }
        
        // Handle payment method switching (Stripe vs PayPal)
        $(document).on('click', '.payment-method-btn', function() {
            const method = $(this).data('method');
            if (!method) return;

            selectedPaymentMethod = method;

            // Update active state on buttons
            $('.payment-method-btn').removeClass('active').attr('aria-pressed', 'false');
            $(this).addClass('active').attr('aria-pressed', 'true');

            const $stripeContainer = $('#stripe-payment-container');
            const $paypalContainer = $('#paypal-payment-container');

            if (method === 'paypal') {
                // Switch containers
                $stripeContainer.removeClass('active').attr('aria-hidden', 'true');
                $paypalContainer.addClass('active').attr('aria-hidden', 'false');

                // Hide the Stripe submit button; PayPal Buttons handle their own submission
                $('#ielts_payment_submit').hide();

                // Initialize PayPal Buttons (idempotent – re-renders if already mounted)
                const membershipType = $('#ielts_membership_type').val();
                const price = getPriceForMembershipType(membershipType);
                if (price > 0) {
                    initializePaypalButtons(membershipType, price);
                }
            } else {
                // Switching back to Stripe
                $paypalContainer.removeClass('active').attr('aria-hidden', 'true');
                $stripeContainer.addClass('active').attr('aria-hidden', 'false');
                $('#ielts_payment_submit').show();
            }
        });
        
        // Reinitialize Stripe elements when the PWYW amount changes
        $('#ielts-pwyw-amount').on('change blur', function() {
            const membershipType = $('#ielts_membership_type').val();
            if (!isPwywMembership(membershipType)) return;
            const entered = parseFloat($(this).val());
            const minimum = ieltsPayment.pwywMinimum || 5.00;
            if (entered && entered >= minimum) {
                // Reinitialize elements with the new amount
                elements = null;
                paymentElement = null;
                initializePaymentElement(entered);
            }
        });
    });
    
    function showPaymentSection(price) {
        const $paymentSection = $('#ielts-payment-section');
        const $freeSubmitContainer = $('#ielts-free-submit-container');
        
        // Show payment section, hide free submit button
        $paymentSection.slideDown();
        $freeSubmitContainer.hide();
        
        // Initialize payment element if not already done or if price changed
        if (!elements || !paymentElement) {
            initializePaymentElement(price);
        }
    }
    
    function hidePaymentSection() {
        const $paymentSection = $('#ielts-payment-section');
        const $freeSubmitContainer = $('#ielts-free-submit-container');
        
        // Hide payment section, show free submit button
        $paymentSection.slideUp();
        $freeSubmitContainer.show();
        
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
                // Note: NO payment_method_types - works with automatic_payment_methods in Payment Intent
            });
            
            // Create and mount Payment Element
            // Following Stripe best practices - no custom layout to ensure compatibility
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
        let price;
        
        if (isPwywMembership(membershipType)) {
            const entered = parseFloat($('#ielts-pwyw-amount').val());
            const minimum = ieltsPayment.pwywMinimum || 5.00;
            if (!entered || entered < minimum) {
                e.preventDefault();
                showError('Please enter an amount of at least $' + minimum.toFixed(2) + ' USD.');
                return;
            }
            price = entered;
        } else {
            price = getPriceForMembershipType(membershipType);
        }
        
        // If PayPal is selected, payment is handled by PayPal Buttons – block accidental form submit
        if (price > 0 && selectedPaymentMethod === 'paypal') {
            e.preventDefault();
            showError('Please use the PayPal button above to complete your payment.');
            return;
        }
        
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
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxError(jqXHR, textStatus, errorThrown, 'register_user');
                }
            });
        }
    }
    
    function createPaymentIntentAndConfirm(userId, membershipType, price) {
        // Create Payment Intent on server
        const data = {
            action: 'ielts_create_payment_intent',
            nonce: ieltsPayment.nonce,
            user_id: userId,
            membership_type: membershipType,
            amount: price
        };
        
        // For PWYW memberships, send the custom amount separately so the server
        // can apply its minimum validation without requiring an exact price match.
        if (isPwywMembership(membershipType)) {
            data.pwyw_amount = price;
        }
        
        $.ajax({
            url: ieltsPayment.ajaxUrl,
            method: 'POST',
            data: data,
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
            error: function(jqXHR, textStatus, errorThrown) {
                handleAjaxError(jqXHR, textStatus, errorThrown, 'create_payment_intent');
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
            error: function(jqXHR, textStatus, errorThrown) {
                handleAjaxError(jqXHR, textStatus, errorThrown, 'confirm_payment');
            }
        });
    }
    
    // ─── PayPal integration for membership registration ────────────────────────
    
    /**
     * Create a PayPal order on the server for the given user and membership.
     * Returns a Promise that resolves to the PayPal order ID.
     */
    function createPaypalMembershipOrder(userId, membershipType, price) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: ieltsPayment.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ielts_cm_create_paypal_membership_order',
                    nonce: ieltsPayment.nonce,
                    user_id: userId,
                    membership_type: membershipType,
                    amount: price
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data.order_id);
                    } else {
                        showError(response.data || 'Failed to create PayPal order.');
                        reject(new Error(response.data || 'Failed to create PayPal order.'));
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxError(jqXHR, textStatus, errorThrown, 'create_paypal_membership_order');
                    reject(new Error('Network error creating PayPal order.'));
                }
            });
        });
    }
    
    /**
     * Render PayPal Buttons inside #paypal-button-container for the registration form.
     * Clears any previously-rendered buttons before rendering fresh ones.
     */
    function initializePaypalButtons(membershipType, price) {
        if (!ieltsPayment.paypalEnabled) {
            showError('PayPal is not enabled on this site. Please use Credit/Debit Card payment.');
            return;
        }
        
        if (typeof paypal === 'undefined') {
            showError('PayPal could not be loaded. Please refresh the page or use Credit/Debit Card payment.');
            return;
        }
        
        const container = document.getElementById('paypal-button-container');
        if (!container) return;
        
        // Clear any previously-rendered buttons
        container.innerHTML = '';
        
        // userId is stored here after the user account is created in createOrder
        let registeredUserId = null;
        
        paypal.Buttons({
            createOrder: function() {
                const isLoggedIn = ieltsPayment.user && ieltsPayment.user.isLoggedIn;
                
                if (isLoggedIn) {
                    // User already exists – go straight to creating the PayPal order
                    return createPaypalMembershipOrder(ieltsPayment.user.userId, membershipType, price)
                        .then(function(orderId) {
                            registeredUserId = ieltsPayment.user.userId;
                            return orderId;
                        });
                }
                
                // New user – register the account first, then create the PayPal order
                return new Promise(function(resolve, reject) {
                    $.ajax({
                        url: ieltsPayment.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'ielts_register_user',
                            nonce: ieltsPayment.nonce,
                            first_name: $('#ielts_first_name').val(),
                            last_name: $('#ielts_last_name').val(),
                            email: $('#ielts_email').val(),
                            password: $('#ielts_password').val(),
                            membership_type: membershipType
                        },
                        success: function(response) {
                            if (response.success) {
                                registeredUserId = response.data.user_id;
                                createPaypalMembershipOrder(registeredUserId, membershipType, price)
                                    .then(resolve)
                                    .catch(reject);
                            } else {
                                showError(response.data || 'Failed to create account. Please check your details and try again.');
                                reject(new Error(response.data || 'Account creation failed.'));
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            handleAjaxError(jqXHR, textStatus, errorThrown, 'paypal_register_user');
                            reject(new Error('Network error during account creation.'));
                        }
                    });
                });
            },
            
            onApprove: function(data) {
                return new Promise(function(resolve, reject) {
                    $.ajax({
                        url: ieltsPayment.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'ielts_cm_capture_paypal_membership_order',
                            nonce: ieltsPayment.nonce,
                            order_id: data.orderID,
                            user_id: registeredUserId
                        },
                        success: function(response) {
                            if (response.success) {
                                showSuccess('Payment successful! Your account is being set up…');
                                setTimeout(function() {
                                    window.location.href = response.data.redirect || (window.location.origin + window.location.pathname + '?registration=success');
                                }, 2000);
                                resolve();
                            } else {
                                showError(response.data || 'Payment capture failed. Please contact support.');
                                reject(new Error(response.data || 'Capture failed.'));
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            handleAjaxError(jqXHR, textStatus, errorThrown, 'capture_paypal_membership_order');
                            reject(new Error('Network error capturing PayPal payment.'));
                        }
                    });
                });
            },
            
            onError: function(err) {
                console.error('IELTS Payment: PayPal error', err);
                showError('A PayPal error occurred. Please try again or use Credit/Debit Card payment.');
            },
            
            onCancel: function() {
                showError('PayPal payment was cancelled. You can try again or use Credit/Debit Card payment.');
            }
            
        }).render('#paypal-button-container');
    }
    
    // ─── End of PayPal integration ─────────────────────────────────────────────
    
    function showError(message) {
        // Use text() to avoid XSS, then add line breaks with CSS
        $('#payment-message')
            .removeClass('success')
            .addClass('error')
            .text(message)
            .css('white-space', 'pre-line') // Preserve line breaks from \n
            .show();
    }
    
    function showSuccess(message) {
        $('#payment-message')
            .removeClass('error')
            .addClass('success')
            .text(message)
            .css('white-space', 'pre-line') // Preserve line breaks from \n
            .show();
    }
    
    function setLoading(isLoading) {
        // Handle both buttons (free submit and payment submit)
        const $freeButton = $('#ielts_register_submit');
        const $paymentButton = $('#ielts_payment_submit');
        const $extensionButton = $('#ielts_payment_submit_extension');
        
        if (isLoading) {
            $freeButton.prop('disabled', true).addClass('loading');
            $paymentButton.prop('disabled', true).addClass('loading');
            $extensionButton.prop('disabled', true).addClass('loading');
        } else {
            $freeButton.prop('disabled', false).removeClass('loading');
            $paymentButton.prop('disabled', false).removeClass('loading');
            $extensionButton.prop('disabled', false).removeClass('loading');
        }
    }
    
    // Extension-specific payment functions
    let elementsExtension;
    let paymentElementExtension;
    let currentExtensionPrice = 0;
    
    function showPaymentSectionExtension(price) {
        console.log('📝 showPaymentSectionExtension called with price:', price);
        
        const $paymentSection = $('#ielts-payment-section-extension');
        console.log('Payment section element found:', $paymentSection.length > 0);
        
        // Show payment section
        $paymentSection.slideDown();
        console.log('Payment section slideDown() called');
        
        // Reinitialize payment element if price changed or not yet initialized
        if (currentExtensionPrice !== price) {
            console.log('Price changed (was:', currentExtensionPrice, 'now:', price, ') - reinitializing');
            // Clean up existing elements before reinitializing
            if (paymentElementExtension) {
                paymentElementExtension.unmount();
            }
            elementsExtension = null;
            paymentElementExtension = null;
            
            initializePaymentElementExtension(price);
            currentExtensionPrice = price;
        } else if (!elementsExtension || !paymentElementExtension) {
            // Initialize if not already done (first time)
            console.log('First time initialization');
            initializePaymentElementExtension(price);
            currentExtensionPrice = price;
        } else {
            console.log('Payment element already initialized, no action needed');
        }
    }
    
    function hidePaymentSectionExtension() {
        console.log('🔒 hidePaymentSectionExtension called');
        
        const $paymentSection = $('#ielts-payment-section-extension');
        
        // Hide payment section
        $paymentSection.slideUp();
        console.log('Payment section slideUp() called');
        
        // Clean up Stripe elements properly
        if (paymentElementExtension) {
            paymentElementExtension.unmount();
            console.log('Payment element unmounted');
        }
        // Always set to null after cleanup
        elementsExtension = null;
        paymentElementExtension = null;
        currentExtensionPrice = 0;
        console.log('Payment elements cleaned up');
    }
    
    function initializePaymentElementExtension(price) {
        console.log('💳 initializePaymentElementExtension called with price:', price);
        
        if (!stripe) {
            console.error('IELTS Payment: Cannot initialize extension payment element - Stripe not initialized');
            console.error('stripe object is:', stripe);
            showErrorExtension('Payment system is not configured. Please contact the site administrator.');
            return;
        }
        
        console.log('Stripe object is available');
        
        // Clear any existing payment element
        $('#payment-element-extension').empty();
        
        try {
            console.log('Creating Stripe elements with amount:', Math.round(parseFloat(price) * 100), 'cents');
            
            // Create Elements instance in payment mode with preset amount
            elementsExtension = stripe.elements({
                mode: 'payment',
                amount: Math.round(parseFloat(price) * 100), // Amount in cents
                currency: 'usd',
                appearance: {
                    theme: 'stripe',
                    variables: { colorPrimary: '#0073aa' }
                }
            });
            
            console.log('Elements instance created');
            
            // Create and mount Payment Element
            paymentElementExtension = elementsExtension.create('payment');
            console.log('Payment element created');
            
            paymentElementExtension.mount('#payment-element-extension');
            console.log('✓ Payment element mounted successfully');
        } catch (error) {
            console.error('IELTS Payment: Error initializing extension payment element:', error);
            showErrorExtension('Failed to initialize payment form. Please refresh the page and try again.');
        }
    }
    
    function showErrorExtension(message) {
        $('#payment-message-extension')
            .removeClass('success')
            .addClass('error')
            .text(message)
            .css('white-space', 'pre-line')
            .show();
    }
    
    function showSuccessExtension(message) {
        $('#payment-message-extension')
            .removeClass('error')
            .addClass('success')
            .text(message)
            .css('white-space', 'pre-line')
            .show();
    }
    
    // Intercept extension form submission
    $('form[name="ielts_extension_form"]').on('submit', function(e) {
        const membershipType = $('#ielts_membership_type_extension').val();
        const price = getPriceForMembershipType(membershipType);
        
        // If it's a paid extension, handle payment first
        if (price > 0 && stripe && elementsExtension) {
            e.preventDefault();
            handleExtensionPaymentSubmission(membershipType, price);
        }
    });
    
    async function handleExtensionPaymentSubmission(membershipType, price) {
        setLoading(true);
        
        // Validate card details first
        const {error: submitError} = await elementsExtension.submit();
        if (submitError) {
            showErrorExtension(submitError.message);
            setLoading(false);
            return;
        }
        
        // User must be logged in for extensions
        if (!ieltsPayment.user || !ieltsPayment.user.isLoggedIn) {
            showErrorExtension('You must be logged in to extend your membership.');
            setLoading(false);
            return;
        }
        
        createExtensionPaymentIntentAndConfirm(ieltsPayment.user.userId, membershipType, price);
    }
    
    async function createExtensionPaymentIntentAndConfirm(userId, membershipType, price) {
        try {
            // Create payment intent
            const response = await $.ajax({
                url: ieltsPayment.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_create_payment_intent',
                    nonce: ieltsPayment.nonce,
                    user_id: userId,
                    membership_type: membershipType,
                    amount: price
                }
            });
            
            if (!response.success) {
                const errorMessage = response.data || 'Payment intent creation failed';
                showErrorExtension(errorMessage);
                setLoading(false);
                return;
            }
            
            const clientSecret = response.data.client_secret;
            
            // Confirm payment
            const {error} = await stripe.confirmPayment({
                elements: elementsExtension,
                clientSecret: clientSecret,
                confirmParams: {
                    return_url: window.location.href,
                }
            });
            
            if (error) {
                showErrorExtension(error.message);
                setLoading(false);
            } else {
                // Payment successful - page will reload with success message
                showSuccessExtension('Payment successful! Your course has been extended.');
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            console.error('Extension payment error:', error);
            const errorMessage = error.responseJSON && error.responseJSON.data 
                ? error.responseJSON.data 
                : 'An error occurred during payment. Please try again.';
            showErrorExtension(errorMessage);
            setLoading(false);
        }
    }
    
})(jQuery);
