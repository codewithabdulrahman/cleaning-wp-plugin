/**
 * Frontend JavaScript for Cleaning Booking widget
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
    
    function setupEventListeners() {
        // Step navigation
        $(document).on('click', '.cb-next-step', function() {
            const nextStep = parseInt($(this).data('next'));
            if (validateCurrentStep()) {
                goToStep(nextStep);
            }
        });
        
        $(document).on('click', '.cb-prev-step', function() {
            const prevStep = parseInt($(this).data('prev'));
            goToStep(prevStep);
        });
        
        // ZIP code check
        $('#cb-zip-code').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                checkZipCode();
            }
        });
        
        // Square meters input
        $('#cb-square-meters').on('input', function() {
            if (bookingData.service_id) {
                calculatePricing();
            }
        });
        
        // Date selection
        $('#cb-booking-date').on('change', function() {
            bookingData.booking_date = $(this).val();
            if (bookingData.service_id && bookingData.square_meters) {
                loadAvailableSlots();
            }
        });
        
        // Form submission
        $('#cb-booking-form').on('submit', function(e) {
            e.preventDefault();
            submitBooking();
        });
    }
    
    function loadServices() {
        $.ajax({
            url: cb_frontend.rest_url + 'services',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    services = response.services;
                    renderServices();
                }
            },
            error: function() {
                showError('#cb-services-container', cb_frontend.strings.error);
            }
        });
    }
    
    function loadExtras() {
        $.ajax({
            url: cb_frontend.rest_url + 'extras',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    extras = response.extras;
                    renderExtras();
                }
            },
            error: function() {
                showError('#cb-extras-container', cb_frontend.strings.error);
            }
        });
    }
    
    function renderServices() {
        const container = $('#cb-services-container');
        container.empty();
        
        if (services.length === 0) {
            container.html('<div class="cb-loading">No services available</div>');
            return;
        }
        
        services.forEach(function(service) {
            const card = $(`
                <div class="cb-service-card" data-service-id="${service.id}">
                    <h4>${service.name}</h4>
                    <p>${service.description || ''}</p>
                    <div class="cb-service-price">From $${service.base_price.toFixed(2)}</div>
                </div>
            `);
            
            card.on('click', function() {
                $('.cb-service-card').removeClass('selected');
                $(this).addClass('selected');
                bookingData.service_id = service.id;
                $('.cb-step-2 .cb-next-step').prop('disabled', false);
                
                if (bookingData.square_meters) {
                    calculatePricing();
                }
            });
            
            container.append(card);
        });
    }
    
    function renderExtras() {
        const container = $('#cb-extras-container');
        container.empty();
        
        if (extras.length === 0) {
            container.html('<div class="cb-loading">No extras available</div>');
            return;
        }
        
        extras.forEach(function(extra) {
            const item = $(`
                <div class="cb-extra-item" data-extra-id="${extra.id}">
                    <input type="checkbox" class="cb-extra-checkbox" id="extra-${extra.id}">
                    <div class="cb-extra-info">
                        <div class="cb-extra-name">${extra.name}</div>
                        <div class="cb-extra-description">${extra.description || ''}</div>
                        <div class="cb-extra-price">+$${extra.price.toFixed(2)} (${extra.duration} min)</div>
                    </div>
                </div>
            `);
            
            item.on('click', function(e) {
                if (e.target.type !== 'checkbox') {
                    const checkbox = $(this).find('.cb-extra-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });
            
            item.find('.cb-extra-checkbox').on('change', function() {
                const extraId = parseInt($(this).closest('.cb-extra-item').data('extra-id'));
                
                if ($(this).prop('checked')) {
                    if (!bookingData.extras.includes(extraId)) {
                        bookingData.extras.push(extraId);
                    }
                } else {
                    bookingData.extras = bookingData.extras.filter(id => id !== extraId);
                }
                
                calculatePricing();
            });
            
            container.append(item);
        });
    }
    
    function checkZipCode() {
        const zipCode = $('#cb-zip-code').val().trim();
        
        if (!zipCode) {
            showError('#cb-zip-error', cb_frontend.strings.invalid_zip);
            return;
        }
        
        bookingData.zip_code = zipCode;
        
        $.ajax({
            url: cb_frontend.rest_url + 'check-zip',
            method: 'POST',
            data: {
                zip_code: zipCode
            },
            success: function(response) {
                if (response.success && response.allowed) {
                    hideError('#cb-zip-error');
                    goToStep(2);
                } else {
                    showError('#cb-zip-error', response.message || cb_frontend.strings.zip_not_available);
                }
            },
            error: function() {
                showError('#cb-zip-error', cb_frontend.strings.error);
            }
        });
    }
    
    function calculatePricing() {
        if (!bookingData.service_id || !bookingData.square_meters || !bookingData.zip_code) {
            return;
        }
        
        $.ajax({
            url: cb_frontend.rest_url + 'calculate-price',
            method: 'POST',
            data: {
                service_id: bookingData.service_id,
                square_meters: bookingData.square_meters,
                extras: bookingData.extras,
                zip_code: bookingData.zip_code
            },
            success: function(response) {
                if (response.success) {
                    pricingData = response;
                    updatePricingDisplay();
                    $('.cb-step-3 .cb-next-step').prop('disabled', false);
                    
                    // Auto-load slots if date is already selected
                    if (bookingData.booking_date) {
                        loadAvailableSlots();
                    }
                }
            },
            error: function() {
                showError('#cb-sqm-error', cb_frontend.strings.error);
            }
        });
    }
    
    function updatePricingDisplay() {
        if (!pricingData) return;
        
        $('#cb-base-price').text('$' + pricingData.pricing.base_price.toFixed(2));
        $('#cb-sqm-price').text('$' + pricingData.pricing.sqm_price.toFixed(2));
        $('#cb-extras-price').text('$' + pricingData.pricing.extras_price.toFixed(2));
        $('#cb-zip-surcharge').text('$' + pricingData.pricing.zip_surcharge.toFixed(2));
        $('#cb-total-price').text('$' + pricingData.pricing.total_price.toFixed(2));
        $('#cb-total-duration').text(pricingData.duration.total_duration);
        
        $('#cb-pricing-summary').show();
    }
    
    function loadAvailableSlots() {
        if (!bookingData.booking_date || !pricingData) {
            return;
        }
        
        const container = $('#cb-time-slots');
        container.html('<div class="cb-loading">' + cb_frontend.strings.loading + '</div>');
        
        $.ajax({
            url: cb_frontend.rest_url + 'available-slots',
            method: 'GET',
            data: {
                date: bookingData.booking_date,
                duration: pricingData.duration.total_duration
            },
            success: function(response) {
                if (response.success) {
                    availableSlots = response.slots;
                    renderTimeSlots();
                }
            },
            error: function() {
                showError('#cb-time-error', cb_frontend.strings.error);
            }
        });
    }
    
    function renderTimeSlots() {
        const container = $('#cb-time-slots');
        container.empty();
        
        if (availableSlots.length === 0) {
            container.html('<div class="cb-loading">No available time slots for this date</div>');
            return;
        }
        
        availableSlots.forEach(function(slot) {
            const slotElement = $(`
                <div class="cb-time-slot" data-time="${slot.time}">
                    ${slot.formatted_time}
                </div>
            `);
            
            slotElement.on('click', function() {
                if (!$(this).hasClass('unavailable')) {
                    $('.cb-time-slot').removeClass('selected');
                    $(this).addClass('selected');
                    bookingData.booking_time = slot.time;
                    $('.cb-step-4 .cb-next-step').prop('disabled', false);
                }
            });
            
            container.append(slotElement);
        });
    }
    
    function goToStep(step) {
        // Hide current step
        $('.cb-step-content').removeClass('cb-step-active');
        $('.cb-step').removeClass('cb-step-active');
        
        // Show new step
        $(`.cb-step-content.cb-step-${step}`).addClass('cb-step-active');
        $(`.cb-step[data-step="${step}"]`).addClass('cb-step-active');
        
        currentStep = step;
        
        // Update summary if on final step
        if (step === 5) {
            updateBookingSummary();
        }
    }
    
    function updateBookingSummary() {
        const selectedService = services.find(s => s.id === bookingData.service_id);
        
        $('#cb-summary-service').text(selectedService ? selectedService.name : '-');
        $('#cb-summary-date').text(bookingData.booking_date ? formatDate(bookingData.booking_date) : '-');
        $('#cb-summary-time').text(bookingData.booking_time ? formatTime(bookingData.booking_time) : '-');
        $('#cb-summary-duration').text(pricingData ? pricingData.duration.total_duration + ' minutes' : '-');
        $('#cb-summary-price').text(pricingData ? '$' + pricingData.pricing.total_price.toFixed(2) : '$0.00');
    }
    
    function validateCurrentStep() {
        let isValid = true;
        
        switch (currentStep) {
            case 1:
                if (!bookingData.zip_code) {
                    showError('#cb-zip-error', cb_frontend.strings.invalid_zip);
                    isValid = false;
                }
                break;
                
            case 2:
                if (!bookingData.service_id) {
                    showError('#cb-services-container', cb_frontend.strings.select_service);
                    isValid = false;
                }
                break;
                
            case 3:
                if (!bookingData.square_meters || bookingData.square_meters <= 0) {
                    showError('#cb-sqm-error', cb_frontend.strings.enter_sqm);
                    isValid = false;
                }
                break;
                
            case 4:
                if (!bookingData.booking_date) {
                    showError('#cb-date-error', cb_frontend.strings.select_date);
                    isValid = false;
                }
                if (!bookingData.booking_time) {
                    showError('#cb-time-error', cb_frontend.strings.select_time);
                    isValid = false;
                }
                break;
                
            case 5:
                if (!bookingData.customer_name) {
                    showError('#cb-name-error', cb_frontend.strings.enter_name);
                    isValid = false;
                }
                if (!bookingData.customer_email) {
                    showError('#cb-email-error', cb_frontend.strings.enter_email);
                    isValid = false;
                }
                if (!bookingData.customer_phone) {
                    showError('#cb-phone-error', cb_frontend.strings.enter_phone);
                    isValid = false;
                }
                break;
        }
        
        return isValid;
    }
    
    function submitBooking() {
        if (!validateCurrentStep()) {
            return;
        }
        
        // Collect form data
        bookingData.customer_name = $('#cb-customer-name').val();
        bookingData.customer_email = $('#cb-customer-email').val();
        bookingData.customer_phone = $('#cb-customer-phone').val();
        bookingData.address = $('#cb-customer-address').val();
        bookingData.notes = $('#cb-notes').val();
        
        const $submitBtn = $('.cb-btn-checkout');
        const originalText = $submitBtn.text();
        
        $submitBtn.text(cb_frontend.strings.loading).prop('disabled', true);
        
        $.ajax({
            url: cb_frontend.ajax_url,
            method: 'POST',
            data: {
                action: 'cb_create_booking',
                nonce: cb_frontend.nonce,
                ...bookingData
            },
            success: function(response) {
                if (response.success) {
                    $submitBtn.text(cb_frontend.strings.redirecting);
                    
                    // Redirect to checkout
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    showError('.cb-step-5', response.data.message || cb_frontend.strings.error);
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showError('.cb-step-5', cb_frontend.strings.error);
                $submitBtn.text(originalText).prop('disabled', false);
            }
        });
    }
    
    function showError(selector, message) {
        $(selector).find('.cb-error-message').text(message).addClass('show');
    }
    
    function hideError(selector) {
        $(selector).removeClass('show');
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(parseInt(hours), parseInt(minutes));
        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }
    
    // Auto-advance when ZIP code is entered
    $('#cb-zip-code').on('blur', function() {
        if ($(this).val().trim() && !bookingData.zip_code) {
            checkZipCode();
        }
    });
    
    // Auto-calculate when square meters changes
    $('#cb-square-meters').on('blur', function() {
        const value = parseInt($(this).val());
        if (value && value > 0) {
            bookingData.square_meters = value;
            if (bookingData.service_id) {
                calculatePricing();
            }
        }
    });
});
