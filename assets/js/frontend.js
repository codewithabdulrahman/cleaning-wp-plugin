/**
 * Frontend JavaScript for Cleaning Booking widget - Modern Design
 */

jQuery(document).ready(function($) {
    
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
        notes: ''
    };
    
    let services = [];
    let extras = [];
    let availableSlots = [];
    let pricingData = null;
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
        // Set active language in selector
        const currentLang = cb_frontend.current_language;
        $('.cb-language-selector').val(currentLang);
        
        // Update UI with current translations
        if (cb_frontend.translations && Object.keys(cb_frontend.translations).length > 0) {
            updateUITranslations();
        }
    }
    
    function initializeDatePicker() {
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        $('#cb-booking-date').val(today);
        bookingData.booking_date = today;
        
        // Auto-load slots for today's date if we have pricing data
        if (pricingData) {
            loadAvailableSlots();
        }
    }
    
    function updateSidebarDisplay() {
        updateServiceTitle();
        updateDuration();
        updatePricing();
        updateCheckoutButton();
    }
    
    function updateServiceTitle() {
        let title = cb_frontend.strings.select_service;
        if (bookingData.service_id) {
            const service = services.find(s => s.id == bookingData.service_id);
            if (service) {
                title = service.name + ' - $' + parseFloat(service.base_price).toFixed(2);
                if (bookingData.square_meters) {
                    title += ' (' + bookingData.square_meters + ' m²)';
                }
            }
        }
        $('#cb-sidebar-service-title').text(title);
    }
    
    function updateDuration() {
        let duration = '-';
        if (pricingData && pricingData.total_duration) {
            const hours = Math.round(pricingData.total_duration / 60 * 10) / 10;
            duration = hours + ' ' + (hours === 1 ? cb_frontend.strings.hour : cb_frontend.strings.hours);
        }
        $('#cb-sidebar-duration').text(duration);
    }
    
    function updatePricing() {
        let currentPrice = '$0.00';
        let originalPrice = null;
        
        if (pricingData) {
            currentPrice = '$' + pricingData.total_price.toFixed(2);
            if (pricingData.original_price && pricingData.original_price > pricingData.total_price) {
                originalPrice = '$' + pricingData.original_price.toFixed(2);
            }
        }
        
        $('#cb-sidebar-total').text(currentPrice);
        $('#cb-checkout-price').text(currentPrice);
        
        if (originalPrice) {
            $('#cb-sidebar-original').text(originalPrice).show();
        } else {
            $('#cb-sidebar-original').hide();
        }
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
        if (validateCurrentStep()) {
            currentStep++;
            updateStepDisplay();
        }
    }
    
    function handlePrevStep() {
        currentStep--;
        updateStepDisplay();
    }
    
    function handleZipCodeChange() {
        bookingData.zip_code = $(this).val().trim();
    }
    
    function handleSquareMetersChange() {
        bookingData.square_meters = parseInt($(this).val()) || null;
        if (bookingData.service_id && bookingData.square_meters) {
            calculatePrice();
        }
        updateSidebarDisplay();
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
        
        // Simulate promocode application (20% discount)
        if (pricingData) {
            pricingData.total_price = pricingData.total_price * 0.8;
            pricingData.original_price = pricingData.total_price / 0.8;
            updatePricing();
            showNotification(cb_frontend.strings.promocode_applied, 'success');
        }
    }
    
    function handleServiceSelect(e) {
        e.preventDefault();
        
        // Remove previous selection
        $('.cb-service-card').removeClass('selected');
        
        // Add selection to clicked card
        $(this).addClass('selected');
        
        bookingData.service_id = $(this).data('service-id');
        
        // Enable next button
        $('.cb-step-2 .cb-next-step').prop('disabled', false);
        
        // Load pricing
        calculatePrice();
        updateSidebarDisplay();
    }
    
    function handleExtraSelect(e) {
        e.preventDefault();
        
        const extraId = $(this).data('extra-id');
        const extraPrice = parseFloat($(this).data('extra-price'));
        
        if ($(this).hasClass('selected')) {
            // Remove extra
            $(this).removeClass('selected');
            bookingData.extras = bookingData.extras.filter(id => id !== extraId);
        } else {
            // Add extra
            $(this).addClass('selected');
            bookingData.extras.push(extraId);
        }
        
        calculatePrice();
        updateSidebarDisplay();
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
        
        // Show loading state
        $(this).prop('disabled', true);
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_switch_language',
                language: targetLanguage
            },
            success: function(response) {
                if (response.success) {
                    // Update translations
                    cb_frontend.translations = response.data.translations;
                    
                    // Update UI text
                    updateUITranslations();
                    
                    // Update current language
                    cb_frontend.current_language = targetLanguage;
                }
            },
            error: function() {
                showNotification('Error switching language', 'error');
            },
            complete: function() {
                $('.cb-language-selector').prop('disabled', false);
            }
        });
    }
    
    function updateUITranslations() {
        const translations = cb_frontend.translations;
        
        // Update step labels
        if (translations['Location']) $('.cb-step[data-step="1"] .cb-step-label').text(translations['Location']);
        if (translations['Service']) $('.cb-step[data-step="2"] .cb-step-label').text(translations['Service']);
        if (translations['Details']) $('.cb-step[data-step="3"] .cb-step-label').text(translations['Details']);
        if (translations['Date & Time']) $('.cb-step[data-step="4"] .cb-step-label').text(translations['Date & Time']);
        if (translations['Checkout']) $('.cb-step[data-step="5"] .cb-step-label').text(translations['Checkout']);
        
        // Update form labels
        if (translations['ZIP Code']) $('label[for="cb-zip-code"]').text(translations['ZIP Code']);
        if (translations['Square Meters']) $('label[for="cb-square-meters"]').text(translations['Square Meters']);
        if (translations['Date']) $('label[for="cb-booking-date"]').text(translations['Date']);
        if (translations['Full Name']) $('label[for="cb-customer-name"]').text(translations['Full Name']);
        if (translations['Email Address']) $('label[for="cb-customer-email"]').text(translations['Email Address']);
        if (translations['Phone Number']) $('label[for="cb-customer-phone"]').text(translations['Phone Number']);
        
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
        if (translations['Continue']) $('.cb-next-step').next().text(translations['Continue']);
        if (translations['I am ordering']) $('#cb-sidebar-checkout').text(translations['I am ordering']);
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
        
        // Submit booking
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Redirect to thank you page or WooCommerce checkout
                    showSuccessMessage(response.data.message);
                    
                    setTimeout(function() {
                        if (response.data.checkout_url) {
                            window.location.href = response.data.checkout_url;
                        } else {
                            location.reload(); // Refresh to show success
                        }
                    }, 2000);
                } else {
                    showNotification(response.data.message || cb_frontend.strings.booking_error, 'error');
                }
            },
            error: function() {
                showNotification(cb_frontend.strings.server_error, 'error');
            },
            complete: function() {
                $('#cb-sidebar-checkout').prop('disabled', false).text(cb_frontend.strings.proceed_checkout);
            }
        });
    }
    
    function validateCurrentStep() {
        let isValid = true;
        
        switch(currentStep) {
            case 1:
                isValid = validateZipCode();
                break;
            case 2:
                isValid = validateServiceSelection();
                break;
            case 3:
                isValid = validateServiceDetails();
                break;
            case 4:
                isValid = validateDateTimeSelection();
                break;
            case 5:
                isValid = validateCustomerDetails();
                break;
        }
        
        return isValid;
    }
    
    function validateZipCode() {
        const zipCode = $('#cb-zip-code').val().trim();
        
        if (!zipCode) {
            showError('cb-zip-error', cb_frontend.strings.zip_required);
            return false;
        }
        
        if (!/^\d{5}$/.test(zipCode)) {
            showError('cb-zip-error', cb_frontend.strings.zip_invalid);
            return false;
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
                } else {
                    showError('cb-zip-error', response.data.message || cb_frontend.strings.zip_unavailable);
                }
            }
        });
        
        return true;
    }
    
    function validateServiceSelection() {
        if (!bookingData.service_id) {
            showNotification(cb_frontend.strings.select_service, 'error');
            return false;
        }
        return true;
    }
    
    function validateServiceDetails() {
        const squareMeters = parseInt($('#cb-square-meters').val());
        
        if (!squareMeters || squareMeters < 1) {
            showError('cb-sqm-error', cb_frontend.strings.square_meters_required);
            return false;
        }
        
        hideError('cb-sqm-error');
        return true;
    }
    
    function validateDateTimeSelection() {
        if (!bookingData.booking_time) {
            showError('cb-time-error', cb_frontend.strings.select_time);
            return false;
        }
        
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
        
        return isValid;
    }
    
    function validateBookingData() {
        return bookingData.service_id && 
               bookingData.square_meters && 
               bookingData.booking_date && 
               bookingData.booking_time &&
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
            notes: $('#cb-notes').val().trim()
        };
    }
    
    function calculatePrice() {
        if (!bookingData.service_id || !bookingData.square_meters) {
            return;
        }
        
        // Get selected service and extras
        const service = services.find(s => s.id == bookingData.service_id);
        const selectedExtras = extras.filter(e => bookingData.extras.includes(e.id));
        
        if (!service) return;
        
        // Calculate pricing
        const basePrice = parseFloat(service.base_price);
        const squareMeterPrice = parseFloat(service.price_per_sqm) * bookingData.square_meters;
        const extrasPrice = selectedExtras.reduce((sum, extra) => sum + parseFloat(extra.price), 0);
        
        // Check ZIP surcharge
        let zipSurcharge = 0;
        if (bookingData.zip_code) {
        $.ajax({
                url: cb_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'cb_get_zip_surcharge',
                    zip_code: bookingData.zip_code
                },
                success: function(response) {
                    if (response.success) {
                        zipSurcharge = parseFloat(response.data.surcharge) || 0;
                    }
                    
                    // Update pricing with ZIP surcharge
                    const totalPrice = basePrice + squareMeterPrice + extrasPrice + zipSurcharge;
                    const totalDuration = (parseInt(service.base_duration) + selectedExtras.reduce((sum, extra) => sum + parseInt(extra.duration), 0));
                    
                    pricingData = {
                        base_price: basePrice,
                        square_meter_price: squareMeterPrice,
                        extras_price: extrasPrice,
                        zip_surcharge: zipSurcharge,
                        total_price: totalPrice,
                        total_duration: totalDuration
                    };
                    
                    updateSidebarDisplay();
                }
            });
        }
        
        // Set initial pricing (without ZIP surcharge for now)
        const totalPrice = basePrice + squareMeterPrice + extrasPrice;
        const totalDuration = (parseInt(service.base_duration) + selectedExtras.reduce((sum, extra) => sum + parseInt(extra.duration), 0));
        
        pricingData = {
            base_price: basePrice,
            square_meter_price: squareMeterPrice,
            extras_price: extrasPrice,
            zip_surcharge: 0,
            total_price: totalPrice,
            total_duration: totalDuration
        };
        
        updateSidebarDisplay();
    }
    
    function loadAvailableSlots() {
        if (!bookingData.booking_date || !pricingData) {
            return;
        }
        
        const $timeSlotsContainer = $('#cb-time-slots');
        $timeSlotsContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_slots + '</div>');
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_get_available_slots',
                booking_date: bookingData.booking_date,
                duration: pricingData.total_duration
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
        
        if (pricingData) {
            $('#cb-summary-duration').text(formatDuration(pricingData.total_duration));
            $('#cb-summary-price').text('$' + pricingData.total_price.toFixed(2));
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
                // Already handled by setup
                break;
            case 2:
                loadServices();
                break;
            case 3:
                loadExtras();
                break;
            case 4:
                loadAvailableSlots();
                break;
            case 5:
                updateBookingSummary();
                break;
        }
        
        // Auto-scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
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
                console.log('Debug request - URL:', cb_frontend.ajax_url);
                console.log('Debug request - Action:', 'cb_debug');
            },
            success: function(response) {
                console.log('Debug response:', response);
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
            const $card = $(`
                <div class="cb-service-card" data-service-id="${service.id}">
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
        if (!bookingData.service_id || extras.length > 0) {
            displayExtras();
            return;
        }
        
        const $extrasContainer = $('#cb-extras-container');
        $extrasContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_extras + '</div>');
        
        $.ajax({
            url: cb_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_get_extras',
                service_id: bookingData.service_id
            },
            success: function(response) {
                if (response.success && response.data.extras) {
                    extras = response.data.extras;
                    displayExtras();
                } else {
                    $extrasContainer.html('<div class="cb-loading">' + (response.data.message || cb_frontend.strings.no_extras_available) + '</div>');
                }
            },
            error: function() {
                $extrasContainer.html('<div class="cb-loading">' + cb_frontend.strings.loading_error + '</div>');
            }
        });
    }
    
    function displayExtras() {
        const $extrasContainer = $('#cb-extras-container');
        $extrasContainer.empty();
        
        if (extras.length === 0) {
            $extrasContainer.html('<div class="cb-loading">' + cb_frontend.strings.no_extras_available + '</div>');
            return;
        }
        
        extras.forEach(function(extra) {
            const $item = $(`
                <div class="cb-extra-item" data-extra-id="${extra.id}" data-extra-price="${extra.price}">
                    <div class="cb-extra-checkbox"></div>
                    <div class="cb-extra-info">
                        <div class="cb-extra-name">${extra.name}</div>
                        <div class="cb-extra-description">${extra.description}</div>
                        <div class="cb-extra-price">$${parseFloat(extra.price).toFixed(2)}</div>
                    </div>
                </div>
            `);
            
            $extrasContainer.append($item);
        });
    }
    
    // Utility functions
    function showError(errorId, message) {
        $('#' + errorId).text(message).addClass('show');
    }
    
    function hideError(errorId) {
        $('#' + errorId).removeClass('show');
    }
    
    function showNotification(message, type = 'info') {
        // Create notification element
        const $notification = $(`
            <div class="cb-notification cb-notification-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#F44336' : '#2196F3'}; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 400px;">
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
});