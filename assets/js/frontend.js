/**
 * Frontend JavaScript for Cleaning Booking widget - Modern Design
 */

// Global initialization to prevent conflicts with other scripts (especially Elementor)
(function() {
    'use strict';
    
    // Ensure cb_frontend object exists globally IMMEDIATELY before any other scripts try to access it
    if (typeof window.cb_frontend === 'undefined') {
        window.cb_frontend = {
            tools: {},
            ajax_url: '',
            nonce: '',
            current_language: 'en',
            translations: {},
            strings: {},
            // Add all possible properties that other scripts might expect
            init: function() {},
            ready: function() {},
            onReady: function() {},
            components: {},
            modules: {},
            utils: {},
            helpers: {}
        };
    }
    
    // Also ensure it's available on document ready for any scripts that check later
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            if (typeof window.cb_frontend === 'undefined') {
                window.cb_frontend = {
                    tools: {},
                    ajax_url: '',
                    nonce: '',
                    current_language: 'en',
                    translations: {},
                    strings: {},
                    init: function() {},
                    ready: function() {},
                    onReady: function() {},
                    components: {},
                    modules: {},
                    utils: {},
                    helpers: {}
                };
            }
        });
    }
})();

jQuery(document).ready(function($) {
    
    // Safety check for cb_frontend object
    if (typeof cb_frontend === 'undefined') {
        console.error('cb_frontend object not found! This will cause errors.');
        return;
    }
    
    // Ensure cb_frontend has ALL required properties to prevent conflicts with other scripts
    if (!cb_frontend.tools) {
        cb_frontend.tools = {};
    }
    if (!cb_frontend.ajax_url) {
        cb_frontend.ajax_url = '';
    }
    if (!cb_frontend.nonce) {
        cb_frontend.nonce = '';
    }
    if (!cb_frontend.current_language) {
        cb_frontend.current_language = 'en';
    }
    if (!cb_frontend.translations) {
        cb_frontend.translations = {};
    }
    if (!cb_frontend.strings) {
        cb_frontend.strings = {};
    }
    
    // Make cb_frontend globally available to prevent conflicts
    window.cb_frontend = cb_frontend;
    
    let currentStep = 1;
    let bookingData = {
        zip_code: '',
        service_id: null,
        square_meters: null,
        extras: [],
        booking_date: '',
        booking_time: '',
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        address: '',
        notes: '',
        pricing: {
            service_price: 0,
            extras_price: 0,
            zip_surcharge: 0,
            total_price: 0,
            service_duration: 0,
            extras_duration: 0,
            total_duration: 0
        },
        payment_method: 'cash'
    };
    
    // Ensure extras array is always properly initialized
    if (!Array.isArray(bookingData.extras)) {
        bookingData.extras = [];
    }
    
    // Debug: Track extras array modifications
    const originalExtras = bookingData.extras;
    let extrasModificationCount = 0;
    
    function logExtrasChange(operation, value) {
        extrasModificationCount++;
        console.log(`Extras modification #${extrasModificationCount}: ${operation}`, {
            newValue: value,
            currentValue: [...originalExtras],
            stack: new Error().stack
        });
    }
    
    // Override array methods to track changes
    const originalPush = originalExtras.push;
    const originalFilter = originalExtras.filter;
    const originalSplice = originalExtras.splice;
    
    originalExtras.push = function(...args) {
        logExtrasChange('push', args);
        return originalPush.apply(this, args);
    };
    
    originalExtras.filter = function(callback) {
        logExtrasChange('filter', 'filtering array');
        return originalFilter.apply(this, arguments);
    };
    
    originalExtras.splice = function(...args) {
        logExtrasChange('splice', args);
        return originalSplice.apply(this, args);
    };
    
    let services = [];
    let extras = [];
    let availableSlots = [];
    let heldSessionId = null;
    
    // Initialize
    init();
    
    function init() {
        loadServices();
        loadExtras();
        setupEventListeners();
        initializeDatePicker();
        updateSidebarDisplay();
        initializeLanguage();
    }
    
    function initializeLanguage() {
        // Check for saved language preference
        const savedLanguage = localStorage.getItem('cb_language') || cb_frontend.current_language;
        
        console.log('Initializing language. Saved:', savedLanguage, 'Current:', cb_frontend.current_language);
        
        // Set active language in selector
        $('.cb-language-selector').val(savedLanguage);
        
        // Update UI with current translations
        if (cb_frontend.translations && Object.keys(cb_frontend.translations).length > 0) {
            updateUITranslations();
        }
        
        // If saved language is different from current, switch to it
        if (savedLanguage !== cb_frontend.current_language) {
            console.log('Language mismatch, switching to saved language:', savedLanguage);
            switchLanguage(savedLanguage);
        }
    }
    
    function initializeDatePicker() {
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        $('#cb-booking-date').val(today);
        bookingData.booking_date = today;
        
        // Auto-load slots for today's date
            loadAvailableSlots();
    }
    
    function updateSidebarDisplay() {
        updateServiceTitle();
        updateDuration();
        updatePricing();
        updateCheckoutButton();
        
        // Only update selected extras when we're on step 3 (extras step)
        if (currentStep === 3) {
            updateSelectedExtras();
        }
    }
    
    function updateSelectedExtras() {
        const $selectedExtrasSection = $('#cb-selected-extras');
        const $selectedExtrasList = $('#cb-selected-extras-list');
        
        if (bookingData.extras && bookingData.extras.length > 0) {
            const selectedExtrasNames = bookingData.extras.map(extraId => {
                const extra = extras.find(e => e.id == extraId);
                return extra ? extra.name : 'Unknown';
            });
            
            // Show the section
            $selectedExtrasSection.show();
            
            // Update the list
            $selectedExtrasList.html(selectedExtrasNames.map(name => 
                `<div class="cb-selected-extra-item">✓ ${name}</div>`
            ).join(''));
            
            console.log('Selected extras:', selectedExtrasNames);
        } else {
            // Hide the section if no extras selected
            $selectedExtrasSection.hide();
        }
    }
    
    function updateServiceTitle() {
        let title = cb_frontend.strings.select_service;
        if (bookingData.service_id) {
            const service = services.find(s => s.id == bookingData.service_id);
            if (service) {
                title = service.name;
                if (bookingData.square_meters) {
                    title += ' (' + bookingData.square_meters + ' m²)';
                }
            }
        }
        $('#cb-sidebar-service-title').text(title);
    }
    
    function updateDuration() {
        // Use default duration since pricing is disabled
        $('#cb-sidebar-duration').text('2 hours');
    }
    
    function updatePricing() {
        // Pricing disabled - show placeholder
        $('#cb-sidebar-total').text('Price TBD');
        $('#cb-checkout-price').text('Price TBD');
            $('#cb-sidebar-original').hide();
    }
    
    function updateCheckoutButton() {
        const isValid = bookingData.service_id && bookingData.booking_date && bookingData.booking_time;
        $('#cb-sidebar-checkout').prop('disabled', !isValid);
    }
    
    function setupEventListeners() {
        // Language switching
        $('.cb-language-selector').on('change', handleLanguageSwitch);
        
        // Step navigation
        $('.cb-next-step').on('click', handleNextStep);
        $('.cb-prev-step').on('click', handlePrevStep);
        
        // Form inputs
        $('#cb-zip-code').on('input', handleZipCodeChange);
        $('#cb-square-meters').on('input', handleSquareMetersChange);
        $('#cb-booking-date').on('change', handleDateChange);
        
        // Customer details
        $('#cb-customer-name, #cb-customer-email, #cb-customer-phone').on('input', updateCheckoutButton);
        
        // Promocode
        $('#cb-apply-promocode').on('click', handlePromocodeApply);
        $('#cb-promocode').on('keypress', function(e) {
            if (e.which === 13) {
                handlePromocodeApply();
            }
        });
        
        // Sidebar checkout button
        $('#cb-sidebar-checkout').on('click', proceedToCheckout);
        
        // Form submission
        $('#cb-booking-form').on('submit', handleFormSubmit);
        
        // Service selection
        $(document).on('click', '.cb-service-card', handleServiceSelect);
        
        // Extra selection
        $(document).on('click', '.cb-extra-item', handleExtraSelect);
        
        // Time slot selection
        $(document).on('click', '.cb-time-slot', handleTimeSlotSelect);
    }
    
    function handleNextStep() {
        const $button = $(this);
        const originalText = $button.text();
        
        // Show loading state with proper spinner
        $button.prop('disabled', true)
               .addClass('loading')
               .text(cb_frontend.strings.processing);
        
        // Add timeout to prevent infinite loading
        const timeoutId = setTimeout(function() {
            $button.prop('disabled', false)
                   .removeClass('loading')
                   .text(originalText);
        }, 5000); // 5 second timeout
        
        validateCurrentStep().then(function(isValid) {
            clearTimeout(timeoutId); // Clear timeout since validation completed
            
            if (isValid) {
                currentStep++;
                updateStepDisplay();
            }
        }).catch(function(error) {
            console.error('Validation error:', error);
            clearTimeout(timeoutId); // Clear timeout since validation failed
            showNotification('Validation failed. Please try again.', 'error');
        }).finally(function() {
            // Restore button state
            $button.prop('disabled', false)
                   .removeClass('loading')
                   .text(originalText);
        });
    }
    
    function handlePrevStep() {
        currentStep--;
        updateStepDisplay();
    }
    
    function handleZipCodeChange() {
        bookingData.zip_code = $(this).val().trim();
    }
    
    function checkAndCalculatePrice() {
        // Price calculations removed - just update sidebar display
        updateSidebarDisplay();
    }
    
    function handleSquareMetersChange() {
        const squareMeters = parseInt($(this).val()) || null;
        bookingData.square_meters = squareMeters;
        
        // Calculate price if service is selected
        checkAndCalculatePrice();
        
        // Update button states
        updateButtonStates();
    }
    
    function handleDateChange() {
        bookingData.booking_date = $(this).val();
        bookingData.booking_time = '';
        loadAvailableSlots();
        
        // Update checkout button state
        updateCheckoutButton();
    }
    
    function handlePromocodeApply() {
        const promocode = $('#cb-promocode').val().trim();
        
        if (!promocode) {
            showNotification(cb_frontend.strings.enter_promocode, 'error');
            return;
        }
        
        // Promocode functionality removed - just show success message
        showNotification('Promocode functionality temporarily disabled', 'info');
    }
    
    function handleServiceSelect(e) {
        e.preventDefault();
        
        // Remove previous selection
        $('.cb-service-card').removeClass('selected');
        
        // Add selection to clicked card
        $(this).addClass('selected');
        
        bookingData.service_id = $(this).data('service-id');
        console.log('Service selected - service_id:', bookingData.service_id);
        console.log('Current bookingData:', bookingData);
        
        // Enable next button
        $('.cb-step-2 .cb-next-step').prop('disabled', false);
        
        // Calculate price if square meters are available
        checkAndCalculatePrice();
        
        // Update sidebar display
        updateSidebarDisplay();
    }
    
    function handleExtraSelect(e) {
        e.preventDefault();
        
        // Prevent double-clicking
        if ($(this).hasClass('cb-processing')) {
            console.log('Extra item is being processed, ignoring click');
            return;
        }
        
        $(this).addClass('cb-processing');
        
        const extraId = parseInt($(this).data('extra-id')); // Convert to number immediately
        const extraPrice = parseFloat($(this).data('extra-price'));
        
        console.log('Extra item clicked:', $(this), 'extraId:', extraId, 'type:', typeof extraId);
        
        // Ensure extras array exists
        if (!Array.isArray(bookingData.extras)) {
            bookingData.extras = [];
        }
        
        console.log('Current extras array:', bookingData.extras, 'Looking for ID:', extraId);
        
        if ($(this).hasClass('selected')) {
            // Remove extra
            console.log('Removing extra:', extraId);
            console.log('Element classes before removal:', $(this).attr('class'));
            $(this).removeClass('selected');
            console.log('Element classes after removal:', $(this).attr('class'));
            bookingData.extras = bookingData.extras.filter(id => parseInt(id) !== extraId);
        } else {
            // Add extra
            console.log('Adding extra:', extraId);
            console.log('Element classes before adding:', $(this).attr('class'));
            $(this).addClass('selected');
            console.log('Element classes after adding:', $(this).attr('class'));
            bookingData.extras.push(extraId);
            
            // Debug: Check if class was actually added
            setTimeout(() => {
                console.log('Element has selected class after adding:', $(this).hasClass('selected'));
                console.log('Element classes:', $(this).attr('class'));
            }, 100);
        }
        
        console.log('Updated extras array:', bookingData.extras);
        
        // Recalculate price with new extras
        checkAndCalculatePrice();
        
        // Remove processing class after a short delay
        setTimeout(() => {
            $(this).removeClass('cb-processing');
        }, 300);
    }
    
    function handleTimeSlotSelect(e) {
        e.preventDefault();
        
        if ($(this).hasClass('unavailable')) {
            return;
        }
        
        // Remove previous selection
        $('.cb-time-slot').removeClass('selected');
        
        // Add selection to clicked slot
        $(this).addClass('selected');
        
        bookingData.booking_time = $(this).data('time');
        
        // Enable next button
        $('.cb-step-4 .cb-next-step').prop('disabled', false);
        
        // Update checkout button state
        updateCheckoutButton();
        
        // Update booking summary
        updateBookingSummary();
    }
    
    function handleFormSubmit(e) {
        e.preventDefault();
        proceedToCheckout();
    }
    
    function handleLanguageSwitch(e) {
        e.preventDefault();
        
        const targetLanguage = $(this).val();
        
        if (targetLanguage === cb_frontend.current_language) {
            return; // Already in this language
        }
        
        switchLanguage(targetLanguage);
    }
    
    function switchLanguage(targetLanguage) {
        // Show loading state
        $('.cb-language-selector').prop('disabled', true);
        
        console.log('Switching to language:', targetLanguage);
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_switch_language',
                language: targetLanguage
            },
            success: function(response) {
                console.log('Language switch response:', response);
                
                if (response.success) {
                    // Update translations
                    cb_frontend.translations = response.data.translations;
                    console.log('Updated translations:', cb_frontend.translations);
                    
                    // Update services with translated data
                    if (response.data.services) {
                        services = response.data.services;
                        console.log('Updated services with translations:', services);
                        displayServices(); // Re-render service cards with translations
                        
                        // Update sidebar service title if a service is selected
                        updateServiceTitle();
                    }
                    
                    // Update UI text
                    updateUITranslations();
                    
                    // Update current language
                    cb_frontend.current_language = targetLanguage;
                    
                    // Save language preference
                    localStorage.setItem('cb_language', targetLanguage);
                    
                    // Show success message
                    const message = response.data.translations['Language switched successfully'] || response.data.message || 'Language switched successfully';
                    showNotification(message, 'success');
                } else {
                    console.error('Language switch failed:', response);
                    showNotification('Failed to switch language', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Language switch AJAX error:', {xhr, status, error});
                showNotification('Error switching language: ' + error, 'error');
            },
            complete: function() {
                $('.cb-language-selector').prop('disabled', false);
            }
        });
    }
    
    function updateUITranslations() {
        const translations = cb_frontend.translations;
        
        console.log('Updating UI translations with:', translations);
        
        if (!translations || Object.keys(translations).length === 0) {
            console.log('No translations available, skipping update');
            return; // No translations available
        }
        
        // Update step labels
        if (translations['Location']) $('.cb-step[data-step="1"] .cb-step-label').text(translations['Location']);
        if (translations['Service']) $('.cb-step[data-step="2"] .cb-step-label').text(translations['Service']);
        if (translations['Details']) $('.cb-step[data-step="3"] .cb-step-label').text(translations['Details']);
        if (translations['Date & Time']) $('.cb-step[data-step="4"] .cb-step-label').text(translations['Date & Time']);
        if (translations['Checkout']) $('.cb-step[data-step="5"] .cb-step-label').text(translations['Checkout']);
        
        // Update step headers
        if (translations['Enter Your ZIP Code']) $('.cb-step-1 .cb-step-header h2').text(translations['Enter Your ZIP Code']);
        if (translations['Let us know your location to check availability']) $('.cb-step-1 .cb-step-header p').text(translations['Let us know your location to check availability']);
        if (translations['Select Your Service']) $('.cb-step-2 .cb-step-header h2').text(translations['Select Your Service']);
        if (translations['Choose the type of cleaning service you need']) $('.cb-step-2 .cb-step-header p').text(translations['Choose the type of cleaning service you need']);
        if (translations['Service Details']) $('.cb-step-3 .cb-step-header h2').text(translations['Service Details']);
        if (translations['Tell us about your space and any additional services']) $('.cb-step-3 .cb-step-header p').text(translations['Tell us about your space and any additional services']);
        if (translations['Select Date & Time']) $('.cb-step-4 .cb-step-header h2').text(translations['Select Date & Time']);
        if (translations['Choose your preferred date and time slot']) $('.cb-step-4 .cb-step-header p').text(translations['Choose your preferred date and time slot']);
        if (translations['Your Details']) $('.cb-step-5 .cb-step-header h2').text(translations['Your Details']);
        if (translations['Please provide your contact information to complete the booking']) $('.cb-step-5 .cb-step-header p').text(translations['Please provide your contact information to complete the booking']);
        
        // Update main title
        if (translations['Book Your Cleaning Service']) $('.cb-title').text(translations['Book Your Cleaning Service']);
        
        // Update form labels
        if (translations['ZIP Code']) $('label[for="cb-zip-code"]').text(translations['ZIP Code']);
        if (translations['Square Meters']) $('label[for="cb-square-meters"]').text(translations['Square Meters']);
        if (translations['Additional Services']) $('.cb-step-3 .cb-form-group label').text(translations['Additional Services']);
        if (translations['Date']) $('label[for="cb-booking-date"]').text(translations['Date']);
        if (translations['Available Time Slots']) $('.cb-step-4 .cb-form-group label').text(translations['Available Time Slots']);
        if (translations['Full Name']) $('label[for="cb-customer-name"]').text(translations['Full Name']);
        if (translations['Email Address']) $('label[for="cb-customer-email"]').text(translations['Email Address']);
        if (translations['Phone Number']) $('label[for="cb-customer-phone"]').text(translations['Phone Number']);
        if (translations['Address']) $('label[for="cb-customer-address"]').text(translations['Address']);
        if (translations['Special Instructions']) $('label[for="cb-notes"]').text(translations['Special Instructions']);
        
        // Update placeholder text
        if (translations['Street address, apartment, etc.']) {
            $('#cb-customer-address').attr('placeholder', translations['Street address, apartment, etc.']);
        }
        if (translations['Any special requests or instructions...']) {
            $('#cb-notes').attr('placeholder', translations['Any special requests or instructions...']);
        }
        
        // Update button text
        if (translations['Check Availability']) $('.cb-next-step[data-next="2"]').text(translations['Check Availability']);
        if (translations['Back']) $('.cb-prev-step').text(translations['Back']);
        if (translations['Continue']) $('.cb-next-step:not([data-next="2"])').text(translations['Continue']);
        if (translations['I am ordering']) $('#cb-sidebar-checkout').text(translations['I am ordering']);
        if (translations['Proceed to Checkout']) $('.cb-btn-checkout').text(translations['Proceed to Checkout']);
        
        // Update sidebar text
        if (translations['Please select a service to see pricing']) $('#cb-sidebar-service-title').text(translations['Please select a service to see pricing']);
        if (translations['Our contractors have all the necessary cleaning products and equipment']) $('.cb-info-text').text(translations['Our contractors have all the necessary cleaning products and equipment']);
        if (translations['Approximate working time']) $('.cb-duration-label').text(translations['Approximate working time']);
        if (translations['Promocode']) $('#cb-promocode').attr('placeholder', translations['Promocode']);
        if (translations['Apply']) $('#cb-apply-promocode').text(translations['Apply']);
        if (translations['To be paid:']) $('.cb-price-label').text(translations['To be paid:']);
        
        // Update all buttons more comprehensively
        if (translations['Check Availability']) {
            $('.cb-next-step[data-next="2"]').text(translations['Check Availability']);
            $('button:contains("Check Availability")').text(translations['Check Availability']);
        }
        if (translations['Back']) {
            $('.cb-prev-step').text(translations['Back']);
            $('button:contains("Back")').text(translations['Back']);
        }
        if (translations['Continue']) {
            $('.cb-next-step:not([data-next="2"])').text(translations['Continue']);
            $('button:contains("Continue")').text(translations['Continue']);
        }
        if (translations['I am ordering']) {
            $('#cb-sidebar-checkout').text(translations['I am ordering']);
            $('button:contains("I am ordering")').text(translations['I am ordering']);
        }
        if (translations['Proceed to Checkout']) {
            $('.cb-btn-checkout').text(translations['Proceed to Checkout']);
            $('button:contains("Proceed to Checkout")').text(translations['Proceed to Checkout']);
        }
        
        // Update booking summary
        if (translations['Booking Summary']) $('.cb-booking-summary h4').text(translations['Booking Summary']);
        if (translations['Service:']) $('.cb-summary-item:contains("Service:") span:first').text(translations['Service:']);
        if (translations['Date:']) $('.cb-summary-item:contains("Date:") span:first').text(translations['Date:']);
        if (translations['Time:']) $('.cb-summary-item:contains("Time:") span:first').text(translations['Time:']);
        if (translations['Duration:']) $('.cb-summary-item:contains("Duration:") span:first').text(translations['Duration:']);
        if (translations['Total Price:']) $('.cb-summary-item:contains("Total Price:") span:first').text(translations['Total Price:']);
        
        // Update loading messages
        if (translations['Loading services...']) $('.cb-loading').text(translations['Loading services...']);
        if (translations['Loading extras...']) $('.cb-loading').text(translations['Loading extras...']);
        if (translations['Loading available times...']) $('.cb-loading').text(translations['Loading available times...']);
        if (translations['Select a date to see available times']) $('.cb-loading').text(translations['Select a date to see available times']);
        
        // Update time units
        if (translations['hours']) {
            $('.cb-duration-value').text(function(i, text) {
                return text.replace(/hours?/g, translations['hours']);
            });
        }
        if (translations['hour']) {
            $('.cb-duration-value').text(function(i, text) {
                return text.replace(/hour/g, translations['hour']);
            });
        }
        
        // Force update any remaining English text with more aggressive selectors
        console.log('Translation update completed. Checking for remaining English text...');
        
        // Update any text that might have been missed
        setTimeout(function() {
            // Force update main title if it's still in English
            if (translations['Book Your Cleaning Service'] && $('.cb-title').text() === 'Book Your Cleaning Service') {
                $('.cb-title').text(translations['Book Your Cleaning Service']);
                console.log('Updated main title to:', translations['Book Your Cleaning Service']);
            }
            
            // Force update step headers
            if (translations['Select Your Service'] && $('.cb-step-2 .cb-step-header h2').text() === 'Select Your Service') {
                $('.cb-step-2 .cb-step-header h2').text(translations['Select Your Service']);
                console.log('Updated step 2 header to:', translations['Select Your Service']);
            }
            
            // Force update sidebar title
            if (translations['Please select a service to see pricing'] && $('#cb-sidebar-service-title').text() === 'Please select a service to see pricing') {
                $('#cb-sidebar-service-title').text(translations['Please select a service to see pricing']);
                console.log('Updated sidebar title to:', translations['Please select a service to see pricing']);
            }
        }, 100);
    }
    
    function proceedToCheckout() {
        if (!validateBookingData()) {
            return;
        }
        
        // Show loading state
        $('#cb-sidebar-checkout').prop('disabled', true).text(cb_frontend.strings.processing);
        
        // Collect all form data
        const formData = {
            action: 'cb_create_booking',
            nonce: cb_frontend.nonce,
            ...bookingData,
            ...collectPaymentData(),
            extras: bookingData.extras || []
        };
        
        console.log('Submitting booking data:', formData);
        console.log('Extras being sent:', formData.extras, 'Type:', typeof formData.extras, 'Length:', formData.extras.length);
        
        // Submit booking
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Booking response:', response);
                
                if (response.success) {
                    // Show success message
                    showSuccessMessage(response.data.message || 'Booking created successfully!');
                    
                    // Redirect to WooCommerce checkout immediately
                        if (response.data.checkout_url) {
                        console.log('Redirecting to checkout URL:', response.data.checkout_url);
                        // Use window.location.replace for immediate redirect
                        window.location.replace(response.data.checkout_url);
                        } else {
                        console.error('No checkout URL provided');
                        showNotification('Checkout URL not available. Please try again.', 'error');
                        // Restore button state
                        $('#cb-sidebar-checkout').prop('disabled', false).text('Proceed to Checkout');
                        }
                } else {
                    console.error('Booking failed:', response);
                    showNotification(response.data.message || cb_frontend.strings.booking_error, 'error');
                    // Restore button state
                    $('#cb-sidebar-checkout').prop('disabled', false).text('Proceed to Checkout');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                
                // Try to extract JSON from mixed HTML/JSON response
                let errorMessage = cb_frontend.strings.server_error;
                try {
                    const responseText = xhr.responseText;
                    console.log('Response text:', responseText);
                    
                    // Look for JSON in the response
                    const jsonMatch = responseText.match(/\{.*\}/s);
                    if (jsonMatch) {
                        const jsonResponse = JSON.parse(jsonMatch[0]);
                        if (jsonResponse.data && jsonResponse.data.message) {
                            errorMessage = jsonResponse.data.message;
                        }
                    }
                } catch (e) {
                    console.log('Could not parse JSON from response');
                }
                
                showNotification(errorMessage, 'error');
                // Restore button state
                $('#cb-sidebar-checkout').prop('disabled', false).text('Proceed to Checkout');
            }
        });
    }
    
    function validateCurrentStep() {
        return new Promise(function(resolve, reject) {
            let validationPromise;
            
            switch(currentStep) {
                case 1:
                    validationPromise = validateZipCode();
                    break;
                case 2:
                    validationPromise = Promise.resolve(validateServiceSelection());
                    break;
                case 3:
                    validationPromise = validateServiceDetails();
                    break;
                case 4:
                    validationPromise = Promise.resolve(validateDateTimeSelection());
                    break;
                case 5:
                    validationPromise = Promise.resolve(validateCustomerDetails());
                    break;
                default:
                    validationPromise = Promise.resolve(true);
            }
            
            validationPromise.then(resolve).catch(reject);
        });
    }
    
    function validateZipCode() {
        return new Promise(function(resolve, reject) {
            const zipCode = $('#cb-zip-code').val().trim();
            
            if (!zipCode) {
                showError('cb-zip-error', cb_frontend.strings.zip_required);
                showNotification(cb_frontend.strings.zip_required, 'error');
                resolve(false);
                return;
            }
            
            if (!/^\d{5}$/.test(zipCode)) {
                showError('cb-zip-error', cb_frontend.strings.zip_invalid);
                showNotification(cb_frontend.strings.zip_invalid, 'error');
                resolve(false);
                return;
            }
            
            hideError('cb-zip-error');
            
            // Check availability
            $.ajax({
                url: cb_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'cb_check_zip_availability',
                    zip_code: zipCode
                },
                success: function(response) {
                    if (response.success && response.data.available) {
                        hideError('cb-zip-error');
                        bookingData.zip_code = zipCode; // Save valid ZIP code
                        showNotification('Great! We provide services in your area.', 'success');
                        resolve(true);
                    } else {
                        showError('cb-zip-error', response.data.message || cb_frontend.strings.zip_unavailable);
                        showNotification(response.data.message || cb_frontend.strings.zip_unavailable, 'error');
                        resolve(false);
                    }
                },
                error: function() {
                    showError('cb-zip-error', cb_frontend.strings.server_error);
                    showNotification(cb_frontend.strings.server_error, 'error');
                    reject(new Error('ZIP code validation failed'));
                }
            });
        });
    }
    
    function validateServiceSelection() {
        if (!bookingData.service_id) {
            showNotification(cb_frontend.strings.select_service, 'error');
            return false;
        }
        
        // Clear any previous errors
        hideError('cb-service-error');
        return true;
    }
    
    function validateServiceDetails() {
        return new Promise(function(resolve, reject) {
            const squareMeters = parseInt($('#cb-square-meters').val());
            
            if (!squareMeters || squareMeters < 1) {
                showError('cb-sqm-error', cb_frontend.strings.square_meters_required);
                resolve(false);
                return;
            }
            
            if (squareMeters > 1000) {
                showError('cb-sqm-error', 'Maximum 1000 square meters allowed');
                resolve(false);
                return;
            }
            
            hideError('cb-sqm-error');
            bookingData.square_meters = squareMeters;
            
            // Extras are optional - no validation needed
            resolve(true);
        });
    }
    
    function validateDateTimeSelection() {
        if (!bookingData.booking_date) {
            showError('cb-date-error', cb_frontend.strings.select_date);
            return false;
        }
        
        if (!bookingData.booking_time) {
            showError('cb-time-error', cb_frontend.strings.select_time);
            return false;
        }
        
        hideError('cb-date-error');
        hideError('cb-time-error');
        return true;
    }
    
    function validateCustomerDetails() {
        let isValid = true;
        
        const customerName = $('#cb-customer-name').val().trim();
        const customerEmail = $('#cb-customer-email').val().trim();
        const customerPhone = $('#cb-customer-phone').val().trim();
        
        if (!customerName) {
            showError('cb-name-error', cb_frontend.strings.name_required);
            isValid = false;
        } else {
            hideError('cb-name-error');
        }
        
        if (!customerEmail) {
            showError('cb-email-error', cb_frontend.strings.email_required);
            isValid = false;
        } else if (!isValidEmail(customerEmail)) {
            showError('cb-email-error', cb_frontend.strings.email_invalid);
            isValid = false;
        } else {
            hideError('cb-email-error');
        }
        
        if (!customerPhone) {
            showError('cb-phone-error', cb_frontend.strings.phone_required);
            isValid = false;
        } else {
            hideError('cb-phone-error');
        }
        
        // Save valid customer data
        if (isValid) {
            bookingData.customer_name = customerName;
            bookingData.customer_email = customerEmail;
            bookingData.customer_phone = customerPhone;
            bookingData.address = $('#cb-customer-address').val().trim();
            bookingData.notes = $('#cb-notes').val().trim();
        }
        
        return isValid;
    }
    
    function validateBookingData() {
        return bookingData.service_id && 
               bookingData.square_meters && 
               bookingData.booking_date && 
               bookingData.booking_time &&
               bookingData.payment_method &&
               $('#cb-customer-name').val().trim() &&
               $('#cb-customer-email').val().trim() &&
               $('#cb-customer-phone').val().trim();
    }
    
    function collectPaymentData() {
        return {
            customer_name: $('#cb-customer-name').val().trim(),
            customer_email: $('#cb-customer-email').val().trim(),
            customer_phone: $('#cb-customer-phone').val().trim(),
            address: $('#cb-customer-address').val().trim(),
            notes: $('#cb-notes').val().trim(),
            payment_method: bookingData.payment_method || 'cash'
        };
    }
    
    // Price calculation function removed - pricing disabled for now
    
    function loadAvailableSlots() {
        if (!bookingData.booking_date) {
            return;
        }
        
        const $timeSlotsContainer = $('#cb-time-slots');
        $timeSlotsContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_slots + '</div>');
        
        // Use default duration since pricing is disabled
        const defaultDuration = 120; // 2 hours default
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_get_available_slots',
                booking_date: bookingData.booking_date,
                duration: defaultDuration
            },
            success: function(response) {
                if (response.success && response.data.slots) {
                    availableSlots = response.data.slots;
                    displayTimeSlots();
                } else {
                    $timeSlotsContainer.html('<div class="cb-loading">' + (response.data.message || cb_frontend.strings.no_slots_available) + '</div>');
                }
            },
            error: function() {
                $timeSlotsContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_error + '</div>');
            }
        });
    }
    
    function displayTimeSlots() {
        const $timeSlotsContainer = $('#cb-time-slots');
        $timeSlotsContainer.empty();
        
        if (availableSlots.length === 0) {
            $timeSlotsContainer.html('<div class="cb-loading">' + cb_frontend.strings.no_slots_available + '</div>');
            return;
        }
        
        availableSlots.forEach(function(slot) {
            const $slot = $('<div class="cb-time-slot" data-time="' + slot.time + '">' + slot.time + '</div>');
            
            if (!slot.available) {
                $slot.addClass('unavailable');
            }
            
            $timeSlotsContainer.append($slot);
        });
    }
    
    function updateBookingSummary() {
        const service = services.find(s => s.id == bookingData.service_id);
        
        if (service) {
            $('#cb-summary-service').text(service.name);
        }
        
        if (bookingData.booking_date) {
            $('#cb-summary-date').text(formatDate(bookingData.booking_date));
        }
        
        if (bookingData.booking_time) {
            $('#cb-summary-time').text(formatTime(bookingData.booking_time));
        }
        
        // Use calculated pricing data if available
        if (bookingData.pricing) {
            $('#cb-summary-duration').text(formatDuration(bookingData.pricing.total_duration));
            $('#cb-summary-price').text(formatPrice(bookingData.pricing.total_price));
        } else {
            $('#cb-summary-duration').text('2 hours');
            $('#cb-summary-price').text('Price will be calculated');
        }
    }
    
    function updateStepDisplay() {
        // Hide all steps
        $('.cb-step-content').removeClass('cb-step-active');
        
        // Show current step
        $(`.cb-step-${currentStep}`).addClass('cb-step-active');
        
        // Update step indicators
        $('.cb-step').removeClass('cb-step-active');
        $(`.cb-step[data-step="${currentStep}"]`).addClass('cb-step-active');
        
        // Handle step-specific logic
        switch(currentStep) {
            case 1:
                // ZIP code step - already handled by setup
                break;
            case 2:
                // Service selection step
                if (services.length === 0) {
                    loadServices();
                } else {
                    displayServices();
                }
                break;
            case 3:
                // Service details step
                console.log('Step 3 - service_id:', bookingData.service_id, 'extras.length:', extras.length, 'bookingData.extras:', bookingData.extras);
                if (bookingData.service_id && extras.length === 0) {
                    console.log('Step 3 - calling loadExtras');
                    loadExtras();
                } else {
                    console.log('Step 3 - calling displayExtras, current selected extras:', bookingData.extras);
                    displayExtras();
                    
                    // Force update visual state after displaying extras
                    setTimeout(() => {
                        forceUpdateExtrasVisualState();
                    }, 100);
                }
                
                // Update sidebar display
                updateSidebarDisplay();
                break;
            case 4:
                // Date & time step
                if (bookingData.booking_date && availableSlots.length === 0) {
                    loadAvailableSlots();
                } else {
                    displayTimeSlots();
                }
                break;
            case 5:
                // Customer details step
                updateBookingSummary();
                initializePaymentMethod();
                break;
        }
        
        // Update sidebar display
        updateSidebarDisplay();
        
        // Update button states
        updateButtonStates();
        
        // Auto-scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    function updateButtonStates() {
        // Enable/disable buttons based on current step and validation
        switch(currentStep) {
            case 1:
                // ZIP code step - enable if ZIP code is valid
                $('.cb-next-step').prop('disabled', !bookingData.zip_code);
                break;
            case 2:
                // Service selection step - enable if service is selected
                $('.cb-next-step').prop('disabled', !bookingData.service_id);
                break;
            case 3:
                // Service details step - enable if square meters is valid
                const squareMeters = parseInt($('#cb-square-meters').val());
                $('.cb-next-step').prop('disabled', !squareMeters || squareMeters < 1);
                break;
            case 4:
                // Date & time step - enable if date and time are selected
                $('.cb-next-step').prop('disabled', !bookingData.booking_date || !bookingData.booking_time);
                break;
            case 5:
                // Customer details step - enable if all required fields are filled AND payment method is selected
                const hasRequiredFields = bookingData.customer_name && 
                                        bookingData.customer_email && 
                                        bookingData.customer_phone;
                const hasPaymentMethod = bookingData.payment_method;
                $('.cb-btn-checkout').prop('disabled', !hasRequiredFields || !hasPaymentMethod);
                break;
        }
    }
    
    function loadServices() {
        if (services.length > 0) {
            displayServices();
            return;
        }
        
        const $servicesContainer = $('#cb-services-container');
        $servicesContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_services + '</div>');
        
        // First test debug endpoint
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_debug'
            },
            beforeSend: function(xhr) {
                console.log('Debug request - Action:', 'cb_debug');
            },
            success: function(response) {
                // Now try to get services
                loadServicesReal();
            },
            error: function(xhr, status, error) {
                console.error('Debug error:', {xhr, status, error});
                $servicesContainer.html('<div class="cb-loading">Debug Error: ' + error + '</div>');
            }
        });
    }
    
    function loadServicesReal() {
        const $servicesContainer = $('#cb-services-container');
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_get_services'
            },
            beforeSend: function(xhr) {
                console.log('Services AJAX request - URL:', cb_frontend.ajax_url);
                console.log('Services AJAX request - Action:', 'cb_get_services');
            },
            success: function(response) {
                console.log('Services AJAX response:', response);
                if (response.success && response.data.services) {
                    services = response.data.services;
                    displayServices();
                } else {
                    $servicesContainer.html('<div class="cb-loading">' + cb_frontend.strings.no_services_available + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Services AJAX error:', {xhr, status, error});
                console.log('Trying REST API fallback...');
                
                // Fallback to REST API
                $.ajax({
                    url: cb_frontend.rest_url + 'services',
                    type: 'GET',
                    beforeSend: function(xhr) {
                        console.log('Services REST request - URL:', cb_frontend.rest_url + 'services');
                    },
                    success: function(response) {
                        console.log('Services REST response:', response);
                        if (response.success && response.services) {
                            services = response.services;
                            displayServices();
                        } else {
                            $servicesContainer.html('<div class="cb-loading">' + cb_frontend.strings.no_services_available + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Services REST error:', {xhr, status, error});
                        $servicesContainer.html('<div class="cb-loading">Connection Error: ' + error + '</div>');
                    }
                });
            }
        });
    }
    
    function displayServices() {
        const $servicesContainer = $('#cb-services-container');
        $servicesContainer.empty();
        
        services.forEach(function(service) {
            const isSelected = bookingData.service_id == service.id;
            const selectedClass = isSelected ? ' selected' : '';
            
            const $card = $(`
                <div class="cb-service-card${selectedClass}" data-service-id="${service.id}">
                    <div class="cb-service-icon">🧹</div>
                    <h4>${service.name}</h4>
                    <p>${service.description}</p>
                    <div class="cb-service-price">
                        <span class="cb-price-current">$${parseFloat(service.base_price).toFixed(2)}</span>
                    </div>
                </div>
            `);
            
            $servicesContainer.append($card);
        });
    }
    
    function loadExtras() {
        console.log('loadExtras called - service_id:', bookingData.service_id, 'extras.length:', extras.length);
        
        if (!bookingData.service_id || extras.length > 0) {
            console.log('Skipping loadExtras - calling displayExtras directly');
            displayExtras();
            return;
        }
        
        const $extrasContainer = $('#cb-extras-container');
        $extrasContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_extras + '</div>');
        
        console.log('Making AJAX call to load extras for service_id:', bookingData.service_id);
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_get_extras',
                service_id: bookingData.service_id
            },
            success: function(response) {
                console.log('Extras AJAX response:', response);
                if (response.success && response.data.extras) {
                    extras = response.data.extras;
                    console.log('Loaded extras:', extras);
                    displayExtras();
                } else {
                    console.log('No extras available or error:', response.data.message);
                    $extrasContainer.html('<div class="cb-loading">' + (response.data.message || cb_frontend.strings.no_extras_available) + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Extras AJAX error:', {xhr, status, error});
                $extrasContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_error + '</div>');
            }
        });
    }
    
    function displayExtras() {
        const $extrasContainer = $('#cb-extras-container');
        
        // Preserve current state before clearing
        const preservedExtras = preserveExtrasState();
        
        $extrasContainer.empty();
        
        if (extras.length === 0) {
            $extrasContainer.html('<div class="cb-extras-optional">' + 
                '<p style="color: #666; font-style: italic; text-align: center; padding: 20px;">' +
                'No additional services available for this service. You can proceed to the next step.' +
                '</p></div>');
            return;
        }
        
        // Restore state after clearing
        restoreExtrasState(preservedExtras);
        
        extras.forEach(function(extra) {
            // Check if this extra is already selected - use loose comparison for type safety
            const isSelected = bookingData.extras && bookingData.extras.some(id => id == extra.id);
            const selectedClass = isSelected ? ' selected' : '';
            
            const $item = $(`
                <div class="cb-extra-item${selectedClass}" data-extra-id="${extra.id}" data-extra-price="${extra.price}" style="cursor: pointer;">
                    <div class="cb-extra-checkbox"></div>
                    <div class="cb-extra-info">
                        <div class="cb-extra-name">${extra.name}</div>
                        <div class="cb-extra-description">${extra.description}</div>
                        <div class="cb-extra-price">$${parseFloat(extra.price).toFixed(2)}</div>
                    </div>
                </div>
            `);
            
            // Click handler is already attached via document delegation
            
            $extrasContainer.append($item);
        });
    }
    
    function preserveExtrasState() {
        // Store current selected extras before any DOM manipulation
        const currentSelectedExtras = [...(bookingData.extras || [])];
        console.log('Preserving extras state:', currentSelectedExtras);
        return currentSelectedExtras;
    }
    
    function restoreExtrasState(preservedExtras) {
        // Restore the selected extras after DOM manipulation
        if (preservedExtras && preservedExtras.length > 0) {
            bookingData.extras = [...preservedExtras];
            console.log('Restored extras state:', bookingData.extras);
        }
    }
    
    function forceUpdateExtrasVisualState() {
        console.log('Force updating extras visual state - bookingData.extras:', bookingData.extras);
        
        // Update all extra items to reflect current state
        $('.cb-extra-item').each(function() {
            const $item = $(this);
            const extraId = parseInt($item.data('extra-id'));
            const isSelected = bookingData.extras && bookingData.extras.some(id => id == extraId);
            
            console.log('Updating visual state for extra ID:', extraId, 'Selected:', isSelected);
            
            if (isSelected) {
                // Only add class if not already present
                if (!$item.hasClass('selected')) {
                    $item.addClass('selected');
                }
                console.log('Applied selected class to extra ID:', extraId);
            } else {
                // Only remove class if present
                if ($item.hasClass('selected')) {
                    $item.removeClass('selected');
                }
            }
        });
        
        // Update sidebar
        updateSelectedExtras();
    }
    
    // Price calculation functions
    function calculatePrice() {
        if (!bookingData.service_id || !bookingData.square_meters) {
            return;
        }
        
        console.log('Calculating price for service:', bookingData.service_id, 'sqm:', bookingData.square_meters, 'extras:', bookingData.extras);
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_calculate_price',
                nonce: cb_frontend.nonce,
                service_id: bookingData.service_id,
                square_meters: bookingData.square_meters,
                extras: bookingData.extras,
                zip_code: bookingData.zip_code
            },
            success: function(response) {
                console.log('Price calculation response:', response);
                if (response.success) {
                    bookingData.pricing = response.data.pricing;
                    console.log('Price calculated:', bookingData.pricing);
                    updatePricingDisplay();
                    updateSidebarDisplay();
                } else {
                    console.error('Price calculation failed:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Price calculation error:', {xhr, status, error});
                console.error('Response text:', xhr.responseText);
                
                // Try to extract JSON from mixed response
                try {
                    const responseText = xhr.responseText;
                    const jsonStart = responseText.lastIndexOf('{');
                    if (jsonStart !== -1) {
                        const jsonPart = responseText.substring(jsonStart);
                        const response = JSON.parse(jsonPart);
                        
                        if (response.success) {
                            console.log('Extracted successful response from mixed content');
                            bookingData.pricing = response.data.pricing;
                            updatePricingDisplay();
                            updateSidebarDisplay();
                        }
                    }
                } catch (e) {
                    console.error('Could not extract JSON from response:', e);
                }
            }
        });
    }
    
    function updatePricingDisplay() {
        const pricing = bookingData.pricing;
        
        // Update main pricing display (Step 5)
        $('#cb-summary-price').text(formatPrice(pricing.total_price));
        $('#cb-summary-duration').text(formatDuration(pricing.total_duration));
        
        // Update sidebar pricing
        $('#cb-sidebar-total').text(formatPrice(pricing.total_price));
        
        // Update checkout button price
        $('#cb-checkout-price').text(formatPrice(pricing.total_price));
        
        // Update Step 3 pricing display
        $('#cb-step3-service-price').text(formatPrice(pricing.service_price));
        $('#cb-step3-extras-price').text(formatPrice(pricing.extras_price));
        $('#cb-step3-total-price').text(formatPrice(pricing.total_price));
        
        // Update Step 3 duration display
        $('#cb-step3-service-duration').text(formatDuration(pricing.service_duration));
        $('#cb-step3-extras-duration').text(formatDuration(pricing.extras_duration));
        $('#cb-step3-total-duration').text(formatDuration(pricing.total_duration));
        
        // Update booking summary to ensure all fields are current
        updateBookingSummary();
    }
    
    function formatPrice(price) {
        return '$' + parseFloat(price).toFixed(2);
    }
    
    function formatDuration(minutes) {
        if (minutes < 60) {
            return minutes + ' min';
        } else {
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            
            if (remainingMinutes === 0) {
                return hours + ' hour' + (hours > 1 ? 's' : '');
            } else {
                return hours + 'h ' + remainingMinutes + 'm';
            }
        }
    }
    
    function checkAndCalculatePrice() {
        if (bookingData.service_id && bookingData.square_meters) {
            calculatePrice();
        }
        updateSidebarDisplay();
    }
    
    // Utility functions
    function showError(errorId, message) {
        $('#' + errorId).text(message).addClass('show');
        
        // Also show notification for important errors
        if (message.includes('required') || message.includes('invalid')) {
            showNotification(message, 'error');
        }
    }
    
    function hideError(errorId) {
        $('#' + errorId).removeClass('show').text('');
    }
    
    function showNotification(message, type = 'info') {
        // Remove any existing notifications
        $('.cb-notification').remove();
        
        // Create notification element
        const $notification = $(`
            <div class="cb-notification cb-notification-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#F44336' : '#2196F3'}; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 400px; animation: slideInRight 0.3s ease;">
                ${message}
            </div>
        `);
        
        $('body').append($notification);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $notification.remove();
            });
        }, 5000);
    }
    
    function showSuccessMessage(message) {
        showNotification(message, 'success');
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    }
    
    function formatDuration(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        
        if (hours > 0 && mins > 0) {
            return `${hours}h ${mins}m`;
        } else if (hours > 0) {
            return `${hours}h`;
        } else {
            return `${mins}m`;
        }
    }
    
    // Initialize payment method selection
    $(document).on('click', '.cb-payment-option', function() {
        $('.cb-payment-option').removeClass('selected');
        $(this).addClass('selected');
        
        const paymentMethod = $(this).data('payment');
        bookingData.payment_method = paymentMethod;
        
        console.log('Payment method selected:', paymentMethod);
        
        // Update button states
        updateButtonStates();
    });
    
    // Initialize default payment method selection when step 5 is displayed
    function initializePaymentMethod() {
        if (currentStep === 5) {
            // Set default selection to cash
            $('.cb-payment-option[data-payment="cash"]').addClass('selected');
            bookingData.payment_method = 'cash';
            
            // Update button states
            updateButtonStates();
        }
    }
});