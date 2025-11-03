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
            current_language: 'el',
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
    if (typeof document !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.cb_frontend === 'undefined') {
                window.cb_frontend = {
                    tools: {},
                    ajax_url: '',
                    nonce: '',
                    current_language: 'el',
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

document.addEventListener('DOMContentLoaded', function() {
    
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
        cb_frontend.current_language = 'el';
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
    
    // Global variables for slot management
    let currentSlotSessionId = null;
    let slotHoldTimeout = null;
    
    let bookingData = {
        zip_code: '',
        service_id: null,
        square_meters: 0,
        extras: [],
        extra_spaces: {}, // Store space for each per m² extra
        express_cleaning: false, // Express cleaning option
        booking_date: '',
        booking_time: '',
        pricing: {
            service_price: 0,
            extras_price: 0,
            zip_surcharge: 0,
            total_price: 0,
            service_duration: 0,
            extras_duration: 0,
            total_duration: 0
        }
    };
    
    // Ensure extras array is always properly initialized
    if (!Array.isArray(bookingData.extras)) {
        bookingData.extras = [];
    }
    
    let services = [];
    let extras = [];
    let availableSlots = [];
    let heldSessionId = null;
    
    // Debounce utility for price calculation
    let priceCalculationTimeout = null;
    function debouncePriceCalculation(callback, delay = 300) {
        if (priceCalculationTimeout) {
            clearTimeout(priceCalculationTimeout);
        }
        priceCalculationTimeout = setTimeout(callback, delay);
    }
    
    // Initialize
    init();
    
    function init() {
        loadServices();
        loadExtras();
        setupEventListeners();
        initializeDatePicker();
        updateSidebarDisplay();
        initializeLanguage();
        
        // Update UI translations on initialization
        setTimeout(() => {
            updateUITranslations();
        }, 100);
        
        // Debug: Check if custom colors are loaded
        if (cb_frontend.colors) {
            
            // Apply custom border colors to input fields
            applyCustomBorderColors();
        }
    }
    
    function applyCustomBorderColors() {
        const colors = cb_frontend.colors || {};
        const borderColor = colors.border || '#dddddd';
        
        // Apply border color to all input fields
        const inputs = document.querySelectorAll('.cb-input, input[type="text"], input[type="email"], input[type="tel"], textarea, select');
        inputs.forEach(input => {
            input.style.borderColor = borderColor;
        });
        
        // Also apply to any other elements with borders
        const borderedElements = document.querySelectorAll('.cb-service-card, .cb-time-slot, .cb-payment-option');
        borderedElements.forEach(element => {
            element.style.borderColor = borderColor;
        });
    }
    
    // Slot management functions
    function holdSlot(date, time, duration) {
        const formData = new FormData();
        formData.append('action', 'cb_hold_slot');
        formData.append('booking_date', date);
        formData.append('booking_time', time);
        formData.append('duration', duration);
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                currentSlotSessionId = response.data.session_id;
                
                // Set up automatic release after configured minutes
                const holdTimeMinutes = cb_frontend.booking_hold_time || 15;
                slotHoldTimeout = setTimeout(() => {
                    releaseSlot();
                }, holdTimeMinutes * 60 * 1000); // Use admin setting
            } else {
                console.error('Failed to hold slot:', response.data.message);
                showNotification(response.data.message || 'Failed to hold slot', 'error');
            }
        })
        .catch(error => {
            console.error('Slot hold error:', error);
        });
    }
    
    function releaseSlot() {
        if (!currentSlotSessionId) return;
        
        const formData = new FormData();
        formData.append('action', 'cb_release_slot');
        formData.append('session_id', currentSlotSessionId);
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                // Slot released successfully
            } else {
                console.error('Failed to release slot:', response.data.message);
            }
        })
        .catch(error => {
            console.error('Slot release error:', error);
        })
        .finally(() => {
            currentSlotSessionId = null;
            if (slotHoldTimeout) {
                clearTimeout(slotHoldTimeout);
                slotHoldTimeout = null;
            }
        });
    }
    
    function initializeLanguage() {
        // Check for saved language preference, default to Greek
        const savedLanguage = localStorage.getItem('cb_language') || 'el';
        
        // Set active language in selector
        const languageSelector = document.querySelector('.cb-language-selector');
        if (languageSelector) {
            languageSelector.value = savedLanguage;
        }
        
        // Update UI with current translations
        if (cb_frontend.translations && Object.keys(cb_frontend.translations).length > 0) {
            updateUITranslations();
        }
        
        // If saved language is different from current, switch to it
        if (savedLanguage !== cb_frontend.current_language) {
            
            switchLanguage(savedLanguage);
        }
    }
    
    function initializeDatePicker() {
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        const bookingDateInput = document.getElementById('cb-booking-date');
        if (bookingDateInput) {
            bookingDateInput.value = today;
        }
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
        const selectedExtrasSection = document.getElementById('cb-selected-extras');
        const selectedExtrasList = document.getElementById('cb-selected-extras-list');
        
        if (bookingData.extras && bookingData.extras.length > 0) {
            const selectedExtrasNames = bookingData.extras.map(extraId => {
                const extra = extras.find(e => e.id == extraId);
                return extra ? extra.name : 'Unknown';
            });
            
            // Show the section
            if (selectedExtrasSection) {
                selectedExtrasSection.style.display = 'block';
            }
            
            // Update the list
            if (selectedExtrasList) {
                selectedExtrasList.innerHTML = selectedExtrasNames.map(name => 
                `<div class="cb-selected-extra-item">✓ ${name}</div>`
                ).join('');
            }
            
            
        } else {
            // Hide the section if no extras selected
            if (selectedExtrasSection) {
                selectedExtrasSection.style.display = 'none';
            }
        }
    }
    
    function updateServiceTitle() {
        let title = cb_frontend.strings.select_service;
        let iconHtml = '';
        
        if (bookingData.service_id) {
            const service = services.find(s => s.id == bookingData.service_id);
            if (service) {
                title = service.name;
                if (bookingData.square_meters) {
                    title += ' (' + bookingData.square_meters + ' m²)';
                }
                
                // Add service icon if available
                if (service.icon_url) {
                    iconHtml = `<span class="cb-sidebar-service-icon"><img src="${service.icon_url}" alt="${service.name}"></span>`;
                } else {
                    iconHtml = `<span class="cb-sidebar-service-icon">🧹</span>`;
                }
            }
        }
        
        const sidebarServiceTitle = document.getElementById('cb-sidebar-service-title');
        if (sidebarServiceTitle) {
            sidebarServiceTitle.innerHTML = iconHtml + title;
        }
    }
    
    function updateDuration() {
        // Use default duration since pricing is disabled
        const sidebarDuration = document.getElementById('cb-sidebar-duration');
        if (sidebarDuration) {
            const hoursText = cb_frontend.strings.hours || 'ώρες';
            sidebarDuration.textContent = `2 ${hoursText}`;
        }
    }
    
    function updatePricing() {
        const sidebarTotal = document.getElementById('cb-sidebar-total');
        const checkoutPrice = document.getElementById('cb-checkout-price');
        const sidebarOriginal = document.getElementById('cb-sidebar-original');
        
        if (bookingData.pricing) {
            // Show dynamic pricing
            if (sidebarTotal) sidebarTotal.textContent = formatPrice(bookingData.pricing.total_price);
            if (checkoutPrice) checkoutPrice.textContent = formatPrice(bookingData.pricing.total_price);
            
            // Show duration in sidebar if available
            const sidebarDuration = document.getElementById('cb-sidebar-duration');
            if (sidebarDuration) {
                sidebarDuration.textContent = formatDuration(bookingData.pricing.total_duration);
            }
        } else {
            // Show placeholder when no pricing available
            if (sidebarTotal) sidebarTotal.textContent = 'Price TBD';
            if (checkoutPrice) checkoutPrice.textContent = 'Price TBD';
            if (sidebarOriginal) sidebarOriginal.style.display = 'none';
        }
    }
    
    function updateCheckoutButton() {
        const isValid = bookingData.service_id && bookingData.booking_date && bookingData.booking_time;
        const sidebarCheckout = document.getElementById('cb-sidebar-checkout');
        if (sidebarCheckout) {
            sidebarCheckout.disabled = !isValid;
        }
    }

    function setupEventListeners() {
    // Language switching
    const languageSelector = document.querySelector('.cb-language-selector');
    if (languageSelector) {
        languageSelector.addEventListener('change', handleLanguageSwitch);
    }

    // Checkout button
    const proceedCheckoutBtn = document.getElementById('cb-proceed-checkout');
    if (proceedCheckoutBtn) {
        proceedCheckoutBtn.addEventListener('click', proceedToCheckout);
    }
    
    // Step navigation
    const nextStepButtons = document.querySelectorAll('.cb-next-step');
    nextStepButtons.forEach(button => {
        button.addEventListener('click', handleNextStep);
    });
    
    const prevStepButtons = document.querySelectorAll('.cb-prev-step');
    prevStepButtons.forEach(button => {
        button.addEventListener('click', handlePrevStep);
    });
    
    // Form inputs
    const zipCodeInput = document.getElementById('cb-zip-code');
    if (zipCodeInput) {
        zipCodeInput.addEventListener('input', handleZipCodeChange);
    }
    
    const squareMetersInput = document.getElementById('cb-square-meters');
    if (squareMetersInput) {
        squareMetersInput.addEventListener('input', handleSquareMetersChange);
    }
    
    const bookingDateInput = document.getElementById('cb-booking-date');
    if (bookingDateInput) {
        bookingDateInput.addEventListener('change', handleDateChange);
    }
    
    // Square meters slider
    const squareMetersSlider = document.getElementById('cb-square-meters-slider');
    const sliderValueDisplay = document.getElementById('cb-slider-value-display');
    
    if (squareMetersSlider && squareMetersInput && sliderValueDisplay) {
        // Update slider when input changes
        squareMetersInput.addEventListener('input', function(e) {
            const value = parseInt(e.target.value) || 0;
            const clampedValue = Math.min(1000, Math.max(0, value));
            
            squareMetersSlider.value = clampedValue;
            sliderValueDisplay.textContent = clampedValue + ' m²';
            squareMetersInput.value = clampedValue;
        });
        
        // Update input when slider changes
        squareMetersSlider.addEventListener('input', function(e) {
            const value = parseInt(e.target.value);
            squareMetersInput.value = value;
            sliderValueDisplay.textContent = value + ' m²';
            
            // Create and trigger input event on the number input
            const inputEvent = new Event('input', { bubbles: true });
            squareMetersInput.dispatchEvent(inputEvent);
        });
        
        // Initialize slider display
        sliderValueDisplay.textContent = squareMetersInput.value + ' m²';
    }
    
    // Promocode
    const applyPromocodeBtn = document.getElementById('cb-apply-promocode');
    if (applyPromocodeBtn) {
        applyPromocodeBtn.addEventListener('click', handlePromocodeApply);
    }
    
    const promocodeInput = document.getElementById('cb-promocode');
    if (promocodeInput) {
        promocodeInput.addEventListener('keypress', function(e) {
        if (e.which === 13) {
            handlePromocodeApply();
        }
    });
    }
    
    // Sidebar checkout button
    const sidebarCheckout = document.getElementById('cb-sidebar-checkout');
    if (sidebarCheckout) {
        sidebarCheckout.addEventListener('click', proceedToCheckout);
    }
    
    // Form submission
    const bookingForm = document.getElementById('cb-booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Service selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-service-card')) {
            handleServiceSelect(e);
        }
    });
    
    // Extra selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-extra-item')) {
            handleExtraSelect(e);
        }
    });
    
    // Extra space input changes (for per m² extras)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('cb-extra-space-input')) {
            handleExtraSpaceChange(e);
        }
    });
    
    // Time slot selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-time-slot')) {
            handleTimeSlotSelect(e);
        }
    });
    
    // Express cleaning checkbox
    const expressCleaningCheckbox = document.getElementById('cb-express-cleaning');
    if (expressCleaningCheckbox) {
        expressCleaningCheckbox.addEventListener('change', handleExpressCleaningChange);
    }
}
    
    function handleNextStep(e) {
        const button = e.target;
        const originalText = button.textContent;
        
        // Show loading state with proper spinner
        button.disabled = true;
        button.classList.add('loading');
        button.textContent = cb_frontend.strings.processing;
        
        // Add timeout to prevent infinite loading
        const timeoutId = setTimeout(function() {
            button.disabled = false;
            button.classList.remove('loading');
            button.textContent = originalText;
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
            showNotification(cb_frontend.translations['Validation failed. Please try again.'] || 'Η επικύρωση απέτυχε. Παρακαλούμε προσπαθήστε ξανά.', 'error');
        }).finally(function() {
            // Restore button state
            button.disabled = false;
            button.classList.remove('loading');
            button.textContent = originalText;
        });
    }
    
    function handlePrevStep() {
        // Release slot if going back from step 4 (time selection)
        if (currentStep === 4 && currentSlotSessionId) {
            releaseSlot();
        }
        
        currentStep--;
        updateStepDisplay();
        
        // Scroll to header when going back to previous step (with small delay for smooth transition)
        setTimeout(() => {
            scrollToHeader();
        }, 100);
    }
    
    function checkAndCalculatePrice(immediate = false) {
        if (bookingData.service_id && bookingData.square_meters >= 0) {
            if (immediate) {
                calculatePrice();
            } else {
                // Debounce price calculation for input events
                debouncePriceCalculation(function() {
                    calculatePrice();
                }, 300);
            }
        }
        updateSidebarDisplay();
    }
    
    function handleZipCodeChange(e) {
        bookingData.zip_code = e.target.value.trim();
        // Recalculate price when ZIP code changes (immediate for ZIP changes)
        checkAndCalculatePrice(true);
    }
    
   function handleSquareMetersChange(e) {
    const squareMeters = parseInt(e.target.value, 10) || 0; // Use base 10 explicitly
    bookingData.square_meters = squareMeters;
    
    // Calculate price if service is selected (debounced for typing)
    checkAndCalculatePrice(false);
    
    // Update button states
    updateButtonStates();
}
    
    function handleExtraSpaceChange(e) {
        const extraId = parseInt(e.target.dataset.extraId, 10);
        const space = parseFloat(e.target.value) || 0;
        
        // Initialize extra_spaces if it doesn't exist
        if (!bookingData.extra_spaces) {
            bookingData.extra_spaces = {};
        }
        
        // Store the space for this extra
        if (space > 0) {
            bookingData.extra_spaces[extraId] = space;
        } else {
            delete bookingData.extra_spaces[extraId];
        }
        
        // Update only the price display for this extra without re-rendering everything
        updateExtraPriceDisplay(extraId);
        
        // Recalculate price (debounced for input events)
        checkAndCalculatePrice(false);
    }
    
    function updateExtraPriceDisplay(extraId) {
        // Find the extra item and update only its price display
        const extraItem = document.querySelector(`[data-extra-id="${extraId}"]`);
        if (!extraItem) return;
        
        const extra = extras.find(e => e.id === extraId);
        if (!extra || !extra.pricing_type) return;
        
        // Only update if it's a per_sqm extra
        if (extra.pricing_type === 'per_sqm') {
            const space = bookingData.extra_spaces[extraId] || 0;
            if (space > 0) {
                const calculatedPrice = extra.price_per_sqm * space;
                const priceDisplay = extraItem.querySelector('.cb-extra-price');
                if (priceDisplay) {
                    priceDisplay.textContent = `€${calculatedPrice.toFixed(2)}`;
                }
            }
        }
    }
    
    function handleDateChange(e) {
        bookingData.booking_date = e.target.value;
        bookingData.booking_time = '';
        loadAvailableSlots();
        
        // Update checkout button state
        updateCheckoutButton();
    }
    
    function handlePromocodeApply() {
        const promocodeInput = document.getElementById('cb-promocode');
        const promocode = promocodeInput ? promocodeInput.value.trim() : '';
        
        if (!promocode) {
            showNotification(cb_frontend.strings.enter_promocode, 'error');
            return;
        }
        
        // Promocode functionality removed - just show success message
        showNotification('Promocode functionality temporarily disabled', 'info');
    }
    
    function handleExpressCleaningChange(e) {
        bookingData.express_cleaning = e.target.checked;
        
        // Recalculate price with express cleaning surcharge (immediate for checkbox)
        if (bookingData.service_id && bookingData.square_meters >= 0) {
            calculatePrice();
        }
        updateSidebarDisplay();
    }
    
  function handleServiceSelect(e) {
    e.preventDefault();
    
    const clickedCard = e.target.closest('.cb-service-card');
    
    // Remove previous selection
    const serviceCards = document.querySelectorAll('.cb-service-card');
    serviceCards.forEach(card => card.classList.remove('selected'));
    
    // Add selection to clicked card
    clickedCard.classList.add('selected');
    
    const newServiceId = clickedCard.dataset.serviceId;
    
    // If service changed, reset extras
    if (bookingData.service_id != newServiceId) {
        extras = []; // Clear cached extras
        bookingData.extras = []; // Clear selected extras
        bookingData.extra_spaces = {}; // Clear extra spaces
    }
    
    bookingData.service_id = newServiceId;
    
    // Set square meters to 0 and update the input field
    bookingData.square_meters = 0;
    const squareMetersInput = document.getElementById('cb-square-meters');
    if (squareMetersInput) {
        squareMetersInput.value = '0';
    }
    
    // Enable next button
    const nextStepBtn = document.querySelector('.cb-step-2 .cb-next-step');
    if (nextStepBtn) {
        nextStepBtn.disabled = false;
    }
    
    // Calculate price and load extras (immediate for service selection)
    checkAndCalculatePrice(true);
    loadExtras();
    
    // Update sidebar display
    updateSidebarDisplay();
    
    // AUTO-ADVANCE TO NEXT STEP AFTER A BRIEF DELAY
    setTimeout(() => {
        // Validate service selection first
        if (validateServiceSelection()) {
            currentStep++;
            updateStepDisplay();
            
            // Show success notification
            showNotification(cb_frontend.translations['Service selected successfully'] || 'Η υπηρεσία επιλέχθηκε με επιτυχία', 'success');
        }
    }, 800); // 800ms delay to give user visual feedback
}
    
    function handleExtraSelect(e) {
        // Don't handle if clicking on space input or its container (including label)
        const spaceInputContainer = e.target.closest('.cb-extra-space-input-container');
        const spaceInput = e.target.closest('.cb-extra-space-input');
        
        if (e.target.classList.contains('cb-extra-space-input') || 
            spaceInputContainer ||
            spaceInput ||
            (e.target.tagName === 'INPUT' && e.target.type === 'number') ||
            (e.target.tagName === 'LABEL')) {
            // Don't process extra selection for these elements
            return;
        }
        
        e.preventDefault();
        
        const clickedItem = e.target.closest('.cb-extra-item');
        
        // Prevent double-clicking
        if (clickedItem.classList.contains('cb-processing')) {
            
            return;
        }
        
        clickedItem.classList.add('cb-processing');
        
        const extraId = parseInt(clickedItem.dataset.extraId, 10); // Convert to number immediately
        const extraPrice = parseFloat(clickedItem.dataset.extraPrice);
        
        
        
        // Ensure extras array exists
        if (!Array.isArray(bookingData.extras)) {
            bookingData.extras = [];
        }
        
        
        
        if (clickedItem.classList.contains('selected')) {
            // Remove extra
            clickedItem.classList.remove('selected');
            
            bookingData.extras = bookingData.extras.filter(id => parseInt(id, 10) !== extraId);
            
            // Remove space data if this was a per m² extra
            if (bookingData.extra_spaces && bookingData.extra_spaces[extraId]) {
                delete bookingData.extra_spaces[extraId];
            }
        } else {
            // Add extra
            clickedItem.classList.add('selected');
            
            bookingData.extras.push(extraId);
            
        }
        
        // Re-render extras to show/hide space inputs
        displayExtras();
        
        // Recalculate price with new extras (immediate for click events)
        checkAndCalculatePrice(true);
        
        // Remove processing class after a short delay
        setTimeout(() => {
            clickedItem.classList.remove('cb-processing');
        }, 300);
    }
    
    function handleTimeSlotSelect(e) {
        e.preventDefault();
        
        const clickedSlot = e.target.closest('.cb-time-slot');
        
        if (clickedSlot.classList.contains('unavailable')) {
            // Show a tooltip or message that this slot is booked
            showNotification(cb_frontend.translations['This time slot is already booked. Please select another time.'] || 'Αυτή η χρονική περίοδος είναι ήδη κρατημένη. Παρακαλούμε επιλέξτε άλλη ώρα.', 'warning');
            return;
        }
        
        // Remove previous selection
        const timeSlots = document.querySelectorAll('.cb-time-slot');
        timeSlots.forEach(slot => slot.classList.remove('selected'));
        
        // Add selection to clicked slot
        clickedSlot.classList.add('selected');
        
        bookingData.booking_time = clickedSlot.dataset.time;
        
        // Hold the slot to prevent double-booking
        if (bookingData.booking_date && bookingData.booking_time) {
            const duration = bookingData.pricing ? bookingData.pricing.total_duration : 120; // Default 2 hours
            holdSlot(bookingData.booking_date, bookingData.booking_time, duration);
        }
        
        // Enable both checkout buttons when time slot is selected
        const proceedCheckoutBtn = document.getElementById('cb-proceed-checkout');
        const sidebarCheckout = document.getElementById('cb-sidebar-checkout');
        
        if (proceedCheckoutBtn) {
            proceedCheckoutBtn.disabled = false;
        }
        if (sidebarCheckout) {
            sidebarCheckout.disabled = false;
        }
        
        // Update booking summary
        updateBookingSummary();
    }
    
    function handleFormSubmit(e) {
        e.preventDefault();
        proceedToCheckout();
    }
    
    function handleLanguageSwitch(e) {
        e.preventDefault();
        
        const targetLanguage = e.target.value;
        
        if (targetLanguage === cb_frontend.current_language) {
            return; // Already in this language
        }
        
        switchLanguage(targetLanguage);
    }
    
    function switchLanguage(targetLanguage) {
        // Show loading state
        const languageSelector = document.querySelector('.cb-language-selector');
        if (languageSelector) {
            languageSelector.disabled = true;
        }
        
        
        
        const formData = new FormData();
        formData.append('action', 'cb_switch_language');
        formData.append('language', targetLanguage);
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
                
                if (response.success) {
                    // Update translations
                    cb_frontend.translations = response.data.translations;
                    
                    // Update services with translated data
                    if (response.data.services) {
                        services = response.data.services;
                        displayServices(); // Re-render service cards with translations
                        
                        // Update sidebar service title if a service is selected
                        updateServiceTitle();
                    }
                    
                    // Update extras with translated data
                    if (response.data.extras) {
                        // Update extras for currently selected service
                        const selectedService = getSelectedService();
                        if (selectedService) {
                            const serviceExtras = response.data.extras.filter(extra => extra.service_id === selectedService.id);
                            extras = serviceExtras; // Update global extras array
                            displayExtras();
                        }
                    }
                    
                    // Update form fields with translated data
                    if (response.data.form_fields) {
                        cb_frontend.form_fields = response.data.form_fields;
                        // Re-render form fields if we're on a form step
                        if (currentStep >= 2) {
                            updateFormFields();
                        }
                    }
                    
                    // Update available slots with translated data
                    if (response.data.available_slots) {
                        // Re-render time slots if we're on the time selection step
                        if (currentStep >= 3) {
                            displayTimeSlots(response.data.available_slots);
                        }
                    }
                    
                    // Update UI text
                    updateUITranslations();
                    
                    // Update current language
                    cb_frontend.current_language = targetLanguage;
                    
                    // Update language selector value
                    if (languageSelector) {
                        languageSelector.value = targetLanguage;
                    }
                    
                    // Save language preference
                    localStorage.setItem('cb_language', targetLanguage);
                    
                    
                    
                    
                    // Show success message
                    const message = response.data.translations['Language switched successfully'] || response.data.message || 'Language switched successfully';
                    showNotification(message, 'success');
                } else {
                    console.error('Language switch failed:', response);
                    showNotification('Failed to switch language', 'error');
                }
        })
        .catch(error => {
            console.error('Language switch fetch error:', error);
            showNotification('Error switching language: ' + error.message, 'error');
        })
        .finally(() => {
            if (languageSelector) {
                languageSelector.disabled = false;
            }
        });
    }
    
    function updateUITranslations() {
        const translations = cb_frontend.translations;
        
        
        if (!translations || Object.keys(translations).length === 0) {
            return; // No translations available
        }
        
        // Update main title
        if (translations['Book Your Cleaning Service']) {
            const mainTitle = document.querySelector('.cb-title');
            if (mainTitle) mainTitle.textContent = translations['Book Your Cleaning Service'];
        }
        
        // Update step labels
        if (translations['Location']) {
            const step1Label = document.querySelector('.cb-step[data-step="1"] .cb-step-label');
            if (step1Label) step1Label.textContent = translations['Location'];
        }
        if (translations['Service']) {
            const step2Label = document.querySelector('.cb-step[data-step="2"] .cb-step-label');
            if (step2Label) step2Label.textContent = translations['Service'];
        }
        if (translations['Details']) {
            const step3Label = document.querySelector('.cb-step[data-step="3"] .cb-step-label');
            if (step3Label) step3Label.textContent = translations['Details'];
        }
        if (translations['Date & Time']) {
            const step4Label = document.querySelector('.cb-step[data-step="4"] .cb-step-label');
            if (step4Label) step4Label.textContent = translations['Date & Time'];
        }
        if (translations['Checkout']) {
            const step5Label = document.querySelector('.cb-step[data-step="5"] .cb-step-label');
            if (step5Label) step5Label.textContent = translations['Checkout'];
        }
        
        // Update step headers
        if (translations['Enter Your ZIP Code']) {
            const step1Header = document.querySelector('.cb-step-1 .cb-step-header h2');
            if (step1Header) step1Header.textContent = translations['Enter Your ZIP Code'];
        }
        if (translations['Let us know your location to check availability']) {
            const step1Desc = document.querySelector('.cb-step-1 .cb-step-header p');
            if (step1Desc) step1Desc.textContent = translations['Let us know your location to check availability'];
        }
        if (translations['Select Your Service']) {
            const step2Header = document.querySelector('.cb-step-2 .cb-step-header h2');
            if (step2Header) step2Header.textContent = translations['Select Your Service'];
        }
        if (translations['Choose the type of cleaning service you need']) {
            const step2Desc = document.querySelector('.cb-step-2 .cb-step-header p');
            if (step2Desc) step2Desc.textContent = translations['Choose the type of cleaning service you need'];
        }
        
        // Update step 3 headers
        if (translations['Service Details']) {
            const step3Header = document.querySelector('.cb-step-3 .cb-step-header h2');
            if (step3Header) step3Header.textContent = translations['Service Details'];
        }
        if (translations['Tell us about your space and any additional services']) {
            const step3Desc = document.querySelector('.cb-step-3 .cb-step-header p');
            if (step3Desc) step3Desc.textContent = translations['Tell us about your space and any additional services'];
        }
        
        // Update step 4 headers
        if (translations['Select Date & Time']) {
            const step4Header = document.querySelector('.cb-step-4 .cb-step-header h2');
            if (step4Header) step4Header.textContent = translations['Select Date & Time'];
        }
        if (translations['Choose your preferred date and time slot']) {
            const step4Desc = document.querySelector('.cb-step-4 .cb-step-header p');
            if (step4Desc) step4Desc.textContent = translations['Choose your preferred date and time slot'];
        }
        
        // Update form labels
        if (translations['ZIP Code']) {
            const zipLabel = document.querySelector('label[for="cb-zip-code"]');
            if (zipLabel) zipLabel.textContent = translations['ZIP Code'];
        }
        if (translations['Space (m²)']) {
            const spaceLabel = document.querySelector('label[for="cb-square-meters"]');
            if (spaceLabel) spaceLabel.textContent = translations['Space (m²)'];
        }
        if (translations['Additional Services']) {
            const extrasLabel = document.querySelector('.cb-step-3 label');
            if (extrasLabel && extrasLabel.textContent.includes('Additional Services')) {
                extrasLabel.textContent = translations['Additional Services'];
            }
        }
        if (translations['Date']) {
            const dateLabel = document.querySelector('label[for="cb-booking-date"]');
            if (dateLabel) dateLabel.textContent = translations['Date'];
        }
        if (translations['Available Time Slots']) {
            const timeLabel = document.querySelector('.cb-step-4 .cb-form-group:last-child label');
            if (timeLabel) timeLabel.textContent = translations['Available Time Slots'];
        }
        
        // Update express cleaning label
        if (translations['Express Cleaning']) {
            const expressLabel = document.getElementById('cb-express-cleaning-label');
            if (expressLabel) {
                expressLabel.textContent = translations['Express Cleaning'] + ' (+20%)';
            }
        }
        
        // Update info banner
        if (translations['Our contractors have all the necessary cleaning products and equipment']) {
            const infoBanner = document.querySelector('.cb-info-text');
            if (infoBanner) infoBanner.textContent = translations['Our contractors have all the necessary cleaning products and equipment'];
        }
        
        // Update buttons
        if (translations['Check Availability']) {
            const checkBtn = document.querySelector('.cb-next-step[data-next="2"]');
            if (checkBtn) checkBtn.textContent = translations['Check Availability'];
        }
        if (translations['Back']) {
            const backBtns = document.querySelectorAll('.cb-prev-step');
            backBtns.forEach(btn => {
                if (btn.textContent.trim() === 'Back' || btn.textContent.trim() === 'Πίσω') {
                    btn.textContent = translations['Back'];
                }
            });
        }
        if (translations['Proceed to Checkout']) {
            const checkoutBtn = document.querySelector('#cb-sidebar-checkout');
            if (checkoutBtn) {
                const priceSpan = checkoutBtn.querySelector('#cb-checkout-price');
                const price = priceSpan ? priceSpan.textContent : '€0.00';
                checkoutBtn.innerHTML = `<span id="cb-checkout-price">${price}</span> - ${translations['Proceed to Checkout']}`;
            }
        }
        if (translations['Continue']) {
            const continueBtns = document.querySelectorAll('.cb-next-step');
            continueBtns.forEach(btn => {
                if (btn.textContent.trim() === 'Continue' || btn.textContent.trim() === 'Συνέχεια') {
                    btn.textContent = translations['Continue'];
                }
            });
        }
        if (translations['Proceed to Checkout']) {
            const checkoutBtn = document.querySelector('.cb-checkout-btn');
            if (checkoutBtn) checkoutBtn.textContent = translations['Proceed to Checkout'];
        }
        
        // Update sidebar content
        if (translations['Select a service to see pricing']) {
            const sidebarTitle = document.querySelector('#cb-sidebar-service-title');
            if (sidebarTitle) sidebarTitle.textContent = translations['Select a service to see pricing'];
        }
        if (translations['Base Service Included']) {
            const baseServiceLabel = document.querySelector('.cb-sidebar-base-service .cb-sidebar-label');
            if (baseServiceLabel) baseServiceLabel.textContent = translations['Base Service Included'];
        }
        if (translations['Additional Services']) {
            const additionalServicesLabel = document.querySelector('.cb-sidebar-extras .cb-sidebar-label');
            if (additionalServicesLabel) additionalServicesLabel.textContent = translations['Additional Services'];
        }
        if (translations['Our contractors have all the necessary cleaning products and equipment']) {
            const infoText = document.querySelector('.cb-info-box p');
            if (infoText) infoText.textContent = translations['Our contractors have all the necessary cleaning products and equipment'];
        }
        if (translations['Approximate working time']) {
            const durationLabel = document.querySelector('.cb-duration-label');
            if (durationLabel) durationLabel.textContent = translations['Approximate working time'];
        }
        if (translations['Selected Services:']) {
            const selectedLabel = document.querySelector('.cb-selected-extras-label');
            if (selectedLabel) selectedLabel.textContent = translations['Selected Services:'];
        }
        if (translations['Pricing:']) {
            const pricingLabel = document.querySelector('.cb-price-label');
            if (pricingLabel) pricingLabel.textContent = translations['Pricing:'];
        }
        
        // Update placeholders
        if (translations['Enter total area in square meters']) {
            const spacePlaceholder = document.querySelector('#cb-square-meters + small');
            if (spacePlaceholder) spacePlaceholder.textContent = translations['Enter total area in square meters'];
        }
        
        // Update Step 3 pricing labels
        if (translations['Service Price:']) {
            const servicePriceLabel = document.querySelector('.cb-step-3 .cb-price-item:first-child span:first-child');
            if (servicePriceLabel) servicePriceLabel.textContent = translations['Service Price:'];
        }
        if (translations['Extras:']) {
            const extrasLabel = document.querySelector('.cb-step-3 .cb-price-item:nth-child(2) span:first-child');
            if (extrasLabel) extrasLabel.textContent = translations['Extras:'];
        }
        if (translations['Total:']) {
            const totalLabel = document.querySelector('.cb-step-3 .cb-price-total span:first-child');
            if (totalLabel) totalLabel.textContent = translations['Total:'];
        }
        if (translations['Service Duration:']) {
            const serviceDurationLabel = document.querySelector('.cb-step-3 .cb-duration-item:first-child span:first-child');
            if (serviceDurationLabel) serviceDurationLabel.textContent = translations['Service Duration:'];
        }
        if (translations['Extras Duration:']) {
            const extrasDurationLabel = document.querySelector('.cb-step-3 .cb-duration-item:nth-child(2) span:first-child');
            if (extrasDurationLabel) extrasDurationLabel.textContent = translations['Extras Duration:'];
        }
        if (translations['Total Duration:']) {
            const totalDurationLabel = document.querySelector('.cb-step-3 .cb-duration-total span:first-child');
            if (totalDurationLabel) totalDurationLabel.textContent = translations['Total Duration:'];
        }
    }
    
    function proceedToCheckout() {
        if (!validateBookingData()) {
            return;
        }
    
        // Show loading state
        const proceedCheckoutBtn = document.getElementById('cb-proceed-checkout');
        const sidebarCheckout = document.getElementById('cb-sidebar-checkout');
    
        if (proceedCheckoutBtn) {
            proceedCheckoutBtn.disabled = true;
            proceedCheckoutBtn.textContent = cb_frontend.strings.processing;
        }
        if (sidebarCheckout) {
            sidebarCheckout.disabled = true;
            sidebarCheckout.textContent = cb_frontend.strings.processing;
        }
    
        // Store booking data in session instead of creating booking immediately
        const formData = new FormData();
        formData.append('action', 'cb_store_booking_session');
        formData.append('nonce', cb_frontend.nonce);
    
        // Calculate actual additional area to send (not total space)
        // If smart_area is available, use the calculated additional_area
        // Otherwise, use the entered square_meters value
        let actual_square_meters = bookingData.square_meters;
        if (bookingData.pricing && bookingData.pricing.smart_area && bookingData.pricing.smart_area.use_smart_area) {
            actual_square_meters = bookingData.pricing.smart_area.additional_area || 0;
        }
    
        // Add booking data to store in session
        const sessionData = {
            zip_code: bookingData.zip_code,
            service_id: bookingData.service_id,
            square_meters: actual_square_meters, // Use calculated additional area, not total
            extras: bookingData.extras || [],
            extra_spaces: bookingData.extra_spaces || {}, // Include extra spaces for per m² extras
            express_cleaning: bookingData.express_cleaning || false, // Express cleaning option
            booking_date: bookingData.booking_date,
            booking_time: bookingData.booking_time,
            pricing: bookingData.pricing || {}
        };
    
        formData.append('booking_data', JSON.stringify(sessionData));
    
        
    
        // Store booking data in session
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            
    
            if (response.success) {
                // Release the held slot since we're moving to checkout
                if (currentSlotSessionId) {
                    releaseSlot();
                }
    
                // Show success message
                showSuccessMessage('Redirecting to checkout...');
    
                // Redirect to WooCommerce checkout with session reference
                if (response.data.checkout_url) {
                    
                    window.location.replace(response.data.checkout_url);
                } else {
                    console.error('No checkout URL provided');
                    showNotification('Checkout URL not available. Please try again.', 'error');
                    restoreButtonState();
                }
            } else {
                console.error('Session storage failed:', response);
                showNotification(response.data.message || 'Failed to proceed to checkout. Please try again.', 'error');
                restoreButtonState();
            }
        })
        .catch(error => {
        console.error('Fetch error:', error);
    
        // Check if it's a JSON parse error (HTML response)
        if (error instanceof SyntaxError && error.message.includes('JSON')) {
            showNotification(cb_frontend.strings.server_error || 'Σφάλμα διακομιστή. Παρακαλούμε προσπαθήστε ξανά ή επικοινωνήστε με την υποστήριξη.', 'error');
            console.error('Server returned HTML instead of JSON - likely a PHP error');
    
            // Try to get the response text to see the actual error
            fetch(cb_frontend.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                console.error('Server returned HTML:', html.substring(0, 500)); // Log first 500 chars
            });
        } else {
            showNotification(cb_frontend.translations['Network error. Please check your connection and try again.'] || 'Σφάλμα δικτύου. Παρακαλούμε ελέγξτε τη σύνδεσή σας και προσπαθήστε ξανά.', 'error');
        }
        restoreButtonState();
    });
    
        function restoreButtonState() {
            if (proceedCheckoutBtn) {
                proceedCheckoutBtn.disabled = false;
                proceedCheckoutBtn.textContent = cb_frontend.translations['Proceed to Checkout'] || 'Προχωρήστε στην Αγορά';
            }
            if (sidebarCheckout) {
                sidebarCheckout.disabled = false;
                sidebarCheckout.textContent = cb_frontend.translations['Proceed to Checkout'] || 'Προχωρήστε στην Αγορά';
            }
        }
    }
    
    function validateBookingData() {
        // Validate required fields
        if (!bookingData.zip_code) {
            showNotification(cb_frontend.translations['ZIP code is required'] || 'Απαιτείται ταχυδρομικός κώδικας', 'error');
            return false;
        }
        
        if (!bookingData.service_id) {
            showNotification(cb_frontend.translations['Please select a service'] || 'Παρακαλούμε επιλέξτε υπηρεσία', 'error');
            return false;
        }
        
        if (!bookingData.booking_date) {
            showNotification(cb_frontend.translations['Please select a date'] || 'Παρακαλούμε επιλέξτε ημερομηνία', 'error');
            return false;
        }
        
        if (!bookingData.booking_time) {
            showNotification(cb_frontend.translations['Please select a time slot'] || 'Παρακαλούμε επιλέξτε χρονική περίοδο', 'error');
            return false;
        }
        
        // Validate per-square-meter extras
        if (bookingData.extras && bookingData.extras.length > 0) {
            for (const extraId of bookingData.extras) {
                const extra = extras.find(e => e.id === extraId);
                if (extra && extra.pricing_type === 'per_sqm') {
                    const space = bookingData.extra_spaces && bookingData.extra_spaces[extraId];
                    if (!space || space <= 0) {
                        showNotification(cb_frontend.translations['Please enter space for selected extra'] || 'Παρακαλούμε εισάγετε χώρο για το επιλεγμένο επιπλέον', 'error');
                        // Scroll to the extra
                        const extraElement = document.querySelector(`[data-extra-id="${extraId}"]`);
                        if (extraElement) {
                            extraElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            // Add a visual highlight
                            extraElement.style.border = '2px solid red';
                            setTimeout(() => {
                                extraElement.style.border = '';
                            }, 3000);
                        }
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    function showSuccessMessage(message) {
        showNotification(message, 'success');
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
                default:
                    validationPromise = Promise.resolve(true);
            }
            
            validationPromise.then(resolve).catch(reject);
        });
    }
    
    function validateZipCode() {
        return new Promise(function(resolve, reject) {
            const zipCodeInput = document.getElementById('cb-zip-code');
            const zipCode = zipCodeInput ? zipCodeInput.value.trim() : '';
            
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
            const formData = new FormData();
            formData.append('action', 'cb_check_zip_availability');
            formData.append('zip_code', zipCode);
            
            fetch(cb_frontend.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                    if (response.success && response.data.available) {
                        hideError('cb-zip-error');
                        bookingData.zip_code = zipCode; // Save valid ZIP code
                        showNotification(cb_frontend.translations['Great! We provide services in your area.'] || 'Τέλεια! Παρέχουμε υπηρεσίες στην περιοχή σας.', 'success');
                        resolve(true);
                    } else {
                        showError('cb-zip-error', response.data.message || cb_frontend.strings.zip_unavailable);
                        showNotification(response.data.message || cb_frontend.strings.zip_unavailable, 'error');
                        resolve(false);
                    }
            })
            .catch(error => {
                console.error('ZIP code validation error:', error);
                    showError('cb-zip-error', cb_frontend.strings.server_error);
                    showNotification(cb_frontend.strings.server_error, 'error');
                    reject(new Error('ZIP code validation failed'));
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
            const squareMetersInput = document.getElementById('cb-square-meters');
            const squareMeters = squareMetersInput ? parseInt(squareMetersInput.value, 10) : 0;
            
            // Allow 0 for skipping, only validate if user enters a positive number
            if (squareMeters < 0) {
                showError('cb-sqm-error', cb_frontend.translations['Square meters cannot be negative'] || 'Τα τετραγωνικά μέτρα δεν μπορούν να είναι αρνητικά');
                resolve(false);
                return;
            }
            
            if (squareMeters > 1000) {
                showError('cb-sqm-error', cb_frontend.translations['Maximum 1000 square meters allowed'] || 'Επιτρέπονται μέχρι 1000 τετραγωνικά μέτρα');
                resolve(false);
                return;
            }
            
            hideError('cb-sqm-error');
            bookingData.square_meters = squareMeters;
            
            // Validate per-square-meter extras if selected
            if (bookingData.extras && bookingData.extras.length > 0) {
                for (const extraId of bookingData.extras) {
                    const extra = extras.find(e => e.id === extraId);
                    if (extra && extra.pricing_type === 'per_sqm') {
                        const space = bookingData.extra_spaces && bookingData.extra_spaces[extraId];
                        if (!space || space <= 0) {
                            showNotification(cb_frontend.translations['Please enter space for selected extra'] || 'Παρακαλούμε εισάγετε χώρο για το επιλεγμένο επιπλέον', 'error');
                            // Scroll to the extra
                            const extraElement = document.querySelector(`[data-extra-id="${extraId}"]`);
                            if (extraElement) {
                                extraElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                // Add a visual highlight
                                extraElement.style.border = '2px solid red';
                                setTimeout(() => {
                                    extraElement.style.border = '';
                                }, 3000);
                            }
                            resolve(false);
                            return;
                        }
                    }
                }
            }
            
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
    
    // Price calculation function removed - pricing disabled for now
    
    function loadAvailableSlots() {
        if (!bookingData.booking_date) {
            return;
        }
        
        // Prevent multiple simultaneous requests
        if (window.loadingSlots) {
            return;
        }
        window.loadingSlots = true;
        
        const timeSlotsContainer = document.getElementById('cb-time-slots');
        if (timeSlotsContainer) {
            timeSlotsContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.loading_slots + '</div>';
        }
        
        // Use default duration since pricing is disabled
        const defaultDuration = 120; // 2 hours default
        
        const formData = new FormData();
        formData.append('action', 'cb_get_available_slots');
        formData.append('booking_date', bookingData.booking_date);
        formData.append('duration', defaultDuration);
        
        // Create AbortController for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData,
            signal: controller.signal
        })
        .then(response => response.json())
        .then(response => {
            clearTimeout(timeoutId); // Clear timeout
            window.loadingSlots = false; // Reset loading flag
            
            if (response.success && response.data && response.data.slots) {
                availableSlots = response.data.slots;
                try {
                    displayTimeSlots(response.data);
                } catch (error) {
                    console.error('Error in displayTimeSlots:', error); // Temporary debug
                }
            } else {
                // Set empty array to prevent infinite loop
                availableSlots = [];
                if (timeSlotsContainer) {
                    timeSlotsContainer.innerHTML = '<div class="cb-loading">' + (response.data?.message || cb_frontend.strings.no_slots_available) + '</div>';
                }
            }
        })
        .catch(error => {
            clearTimeout(timeoutId); // Clear timeout
            window.loadingSlots = false; // Reset loading flag
            
            if (error.name === 'AbortError') {
                console.error('Load available slots timeout');
                if (timeSlotsContainer) {
                    timeSlotsContainer.innerHTML = '<div class="cb-loading">Request timeout. Please try again.</div>';
                }
            } else {
                console.error('Load available slots error:', error);
                if (timeSlotsContainer) {
                    timeSlotsContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.loading_error + '</div>';
                }
            }
            
            // Set empty array to prevent infinite loop
            availableSlots = [];
        });
    }
    
    
    function updateBookingSummary() {
        const service = services.find(s => s.id == bookingData.service_id);
        
        if (service) {
            const summaryService = document.getElementById('cb-summary-service');
            if (summaryService) {
                let iconHtml = '';
                if (service.icon_url) {
                    iconHtml = `<img src="${service.icon_url}" alt="${service.name}" class="cb-summary-service-icon" style="width: 16px; height: 16px; margin-right: 6px; vertical-align: middle; object-fit: contain;">`;
                } else {
                    iconHtml = `<span class="cb-summary-service-icon" style="margin-right: 6px; font-size: 14px;">🧹</span>`;
                }
                summaryService.innerHTML = iconHtml + service.name;
            }
        }
        
        if (bookingData.booking_date) {
            const summaryDate = document.getElementById('cb-summary-date');
            if (summaryDate) {
                summaryDate.textContent = formatDate(bookingData.booking_date);
            }
        }
        
        if (bookingData.booking_time) {
            const summaryTime = document.getElementById('cb-summary-time');
            if (summaryTime) {
                summaryTime.textContent = formatTime(bookingData.booking_time);
            }
        }
        
        // Use calculated pricing data if available
        const summaryDuration = document.getElementById('cb-summary-duration');
        const summaryPrice = document.getElementById('cb-summary-price');
        
        if (bookingData.pricing) {
            if (summaryDuration) summaryDuration.textContent = formatDuration(bookingData.pricing.total_duration);
            if (summaryPrice) summaryPrice.textContent = formatPrice(bookingData.pricing.total_price);
        } else {
            if (summaryDuration) summaryDuration.textContent = '2 hours';
            if (summaryPrice) summaryPrice.textContent = 'Price will be calculated';
        }
    }
    
    function updateStepDisplay() {
        // Hide all steps
        const stepContents = document.querySelectorAll('.cb-step-content');
        stepContents.forEach(step => step.classList.remove('cb-step-active'));
        
        // Show current step
        const currentStepElement = document.querySelector(`.cb-step-${currentStep}`);
        if (currentStepElement) {
            currentStepElement.classList.add('cb-step-active');
        }
        
        // Update step indicators
        const stepIndicators = document.querySelectorAll('.cb-step');
        stepIndicators.forEach(step => step.classList.remove('cb-step-active'));
        
        const currentStepIndicator = document.querySelector(`.cb-step[data-step="${currentStep}"]`);
        if (currentStepIndicator) {
            currentStepIndicator.classList.add('cb-step-active');
        }
        
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
                
                
                // Auto-trigger calculation when entering step 3
                if (bookingData.service_id) {
                    
                    
                    // Ensure extras is initialized as empty array for base service calculation
                    // Only initialize if it doesn't exist, don't reset existing selections
                    if (!bookingData.extras || !Array.isArray(bookingData.extras)) {
                        bookingData.extras = [];
                    }
                    
                    
                    calculatePrice();
                }
                
                if (bookingData.service_id && extras.length === 0) {
                    
                    loadExtras();
                } else {
                    
                    displayExtras();
                    
                    // Force update visual state after displaying extras
                    setTimeout(() => {
                        forceUpdateExtrasVisualState();
                    }, 100);
                }
                
                // Set default area for the selected service
                setDefaultArea(bookingData.service_id);
                
                // Update sidebar display
                updateSidebarDisplay();
                break;
            case 4:
                // Date & time step
                if (bookingData.booking_date && availableSlots.length === 0 && !window.loadingSlots) {
                    loadAvailableSlots();
                } else {
                    displayTimeSlots();
                }
                break;
        }
        
        // Update sidebar display
        updateSidebarDisplay();
        
        // Update button states
        updateButtonStates();
        
        // Scroll to header when moving to next step (with small delay for smooth transition)
        setTimeout(() => {
            scrollToHeader();
        }, 100);
    }
    
    function scrollToHeader() {
        // Find the booking widget header
        const bookingHeader = document.querySelector('.cb-header');
        if (bookingHeader) {
            // Scroll to the header of the booking widget, not the very top of the page
            bookingHeader.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start',
                inline: 'nearest'
            });
        } else {
            // Fallback to the entire widget if header not found
            const bookingWidget = document.querySelector('.cb-widget');
            if (bookingWidget) {
                bookingWidget.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start',
                    inline: 'nearest'
                });
            }
        }
    }
    
    function updateButtonStates() {
        // Enable/disable buttons based on current step and validation
        switch(currentStep) {
            case 1:
                // ZIP code step - enable if ZIP code is valid
                const nextStepBtns1 = document.querySelectorAll('.cb-next-step');
                nextStepBtns1.forEach(btn => {
                    btn.disabled = !bookingData.zip_code;
                });
                break;
            case 2:
                // Service selection step - enable if service is selected
                const nextStepBtns2 = document.querySelectorAll('.cb-next-step');
                nextStepBtns2.forEach(btn => {
                    btn.disabled = !bookingData.service_id;
                });
                break;
            case 3:
                // Service details step - enable if square meters is valid (0 is allowed for skip)
                const squareMetersInput = document.getElementById('cb-square-meters');
                const squareMeters = squareMetersInput ? parseInt(squareMetersInput.value, 10) : 0;
                const nextStepBtns3 = document.querySelectorAll('.cb-next-step');
                nextStepBtns3.forEach(btn => {
                    btn.disabled = squareMeters < 0; // Allow 0 for skip option
                });
                break;
            case 4:
                break;
        }
    }
    
    function loadServices() {
        if (services.length > 0) {
            displayServices();
            return;
        }
        
        const servicesContainer = document.getElementById('cb-services-container');
        if (servicesContainer) {
            servicesContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.loading_services + '</div>';
        }
        
        // Load services directly
        loadServicesReal();
    }
    
    function loadServicesReal() {
        const servicesContainer = document.getElementById('cb-services-container');
        
        const formData = new FormData();
        formData.append('action', 'cb_get_services');
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            
                if (response.success && response.data.services) {
                    services = response.data.services;
                    displayServices();
                } else {
                if (servicesContainer) {
                    servicesContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.no_services_available + '</div>';
                }
            }
        })
        .catch(error => {
            console.error('Services fetch error:', error);
                
                
                // Fallback to REST API
            fetch(cb_frontend.rest_url + 'services', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(response => {
                        
                        if (response.success && response.services) {
                            services = response.services;
                            displayServices();
                        } else {
                    if (servicesContainer) {
                        servicesContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.no_services_available + '</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Services REST error:', error);
                if (servicesContainer) {
                    servicesContainer.innerHTML = '<div class="cb-loading">Connection Error: ' + error.message + '</div>';
                }
            });
        });
    }
    
 function displayServices() {
    const servicesContainer = document.getElementById('cb-services-container');
    if (!servicesContainer) return;
    
    servicesContainer.innerHTML = '';
    
    services.forEach(function(service) {
        const isSelected = bookingData.service_id == service.id;
        const selectedClass = isSelected ? ' selected' : '';
        
        const cardElement = document.createElement('div');
        cardElement.className = `cb-service-card${selectedClass}`;
        cardElement.dataset.serviceId = service.id;
        
        // Use icon_url if available, otherwise fallback to emoji
        let iconHtml = '';
        if (service.icon_url && service.icon_url.trim() !== '') {
            iconHtml = `<img src="${service.icon_url}" alt="${service.name}" class="cb-service-icon-img" style="max-width: 50px; max-height: 50px; object-fit: contain;">`;
        } else {
            // Fallback to emoji icon
            iconHtml = `<div class="cb-service-icon">🧹</div>`;
        }
        
        cardElement.innerHTML = `
            <div class="cb-service-icon-container">
                ${iconHtml}
            </div>
            <h4>${service.name}</h4>
            <p>${service.description}</p>
            <div class="cb-service-details">
                <div class="cb-service-price">
                    <span class="cb-price-current">€${parseFloat(service.base_price).toFixed(2)}</span>
                </div>
                <div class="cb-service-duration">
                    <span class="cb-duration-label">${cb_frontend.translations['Duration:'] || 'Διάρκεια:'}</span>
                    <span class="cb-duration-value">${service.base_duration} ${cb_frontend.translations['minutes'] || 'λεπτά'}</span>
                </div>
                ${service.default_area > 0 ? `
                <div class="cb-service-area">
                    <span class="cb-area-label">${cb_frontend.translations['Includes:'] || 'Περιλαμβάνει:'}</span>
                    <span class="cb-area-value">${service.default_area} m²</span>
                </div>
                ` : ''}
            </div>
        `;
        
        servicesContainer.appendChild(cardElement);
    });
    
    // Apply custom border colors to newly created service cards
    if (cb_frontend.colors) {
        applyCustomBorderColors();
    }
}   /**
     * Set default area for the selected service
     */
    function setDefaultArea(serviceId) {
        if (!serviceId) return;
        
        // Get service data from the services array
        const service = services.find(s => s.id == serviceId);
        const hintElement = document.getElementById('cb-sqm-hint');
        const baseAreaInfo = document.getElementById('cb-base-area-info');
        const baseAreaMessage = document.getElementById('cb-base-area-message');
        const basePriceDisplay = document.getElementById('cb-base-price-display');
        const baseDurationDisplay = document.getElementById('cb-base-duration-display');
        
        if (!service || !service.default_area || service.default_area <= 0) {
            // No default area - hide all hints and info
            if (hintElement) hintElement.style.display = 'none';
            if (baseAreaInfo) baseAreaInfo.style.display = 'none';
            return;
        }
        
        // Show hint about total space
        if (hintElement) {
            hintElement.style.display = 'block';
            const hintText = cb_frontend.translations['Enter total area in square meters'] || 'Εισάγετε Σύνολικό χώρο σε τετραγωνικά μέτρα';
            hintElement.innerHTML = `<small>${hintText.replace('default area', `default ${service.default_area} m²`)}</small>`;
        }
        
        // Show base area info card
        if (baseAreaInfo && baseAreaMessage && basePriceDisplay && baseDurationDisplay) {
            baseAreaInfo.style.display = 'block';
            
            
            
            // Calculate and display base pricing
            const basePrice = parseFloat(service.base_price) || 0;
            const baseDuration = parseInt(service.base_duration, 10) || 0;
            
            const basePriceText = cb_frontend.translations['Base Price: $80.00'] || 'Βασική Τιμή: €80.00';
            const baseDurationText = cb_frontend.translations['Base Duration: 20 min'] || 'Βασική Διάρκεια: 20 λεπτά';
            
            basePriceDisplay.textContent = basePriceText.replace('€80.00', `€${basePrice.toFixed(2)}`);
            baseDurationDisplay.textContent = baseDurationText.replace('20 λεπτά', `${baseDuration} λεπτά`);
        }
        
        // Clear the input field and set to 0 (base space only)
        const squareMetersInput = document.getElementById('cb-square-meters');
        if (squareMetersInput) {
            squareMetersInput.value = '0';
            bookingData.square_meters = 0; // Start with 0 (base space only, no extra)
            
            // Trigger change event to calculate price (base space only)
            squareMetersInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
    
    function loadExtras() {
        
        // Only skip if service_id matches and we already have extras loaded
        const alreadyLoadedForService = bookingData.service_id && extras.length > 0 && extras[0] && extras[0].service_id == bookingData.service_id;
        
        if (!bookingData.service_id || alreadyLoadedForService) {
            displayExtras();
            return;
        }
        
        const extrasContainer = document.getElementById('cb-extras-container');
        if (extrasContainer) {
            extrasContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.loading_extras + '</div>';
        }
        
        
        
        const formData = new FormData();
        formData.append('action', 'cb_get_extras');
        formData.append('service_id', bookingData.service_id);
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            
                if (response.success && response.data.extras) {
                    extras = response.data.extras;
                    
                    displayExtras();
                } else {
                    
                if (extrasContainer) {
                    extrasContainer.innerHTML = '<div class="cb-loading">' + (response.data.message || cb_frontend.strings.no_extras_available) + '</div>';
                }
            }
        })
        .catch(error => {
            console.error('Extras fetch error:', error);
            if (extrasContainer) {
                extrasContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.loading_error + '</div>';
            }
        });
    }
    
    function displayExtras() {
        const extrasContainer = document.getElementById('cb-extras-container');
        if (!extrasContainer) return;
        
        // Preserve current state before clearing
        const preservedExtras = preserveExtrasState();
        
        extrasContainer.innerHTML = '';
        
        if (extras.length === 0) {
            const noExtrasText = cb_frontend.translations['No additional services available for this service. You can proceed to the next step.'] || 'Δεν υπάρχουν διαθέσιμες επιπλέον υπηρεσίες για αυτή την υπηρεσία. Μπορείτε να προχωρήσετε στο επόμενο βήμα.';
            extrasContainer.innerHTML = '<div class="cb-extras-optional">' + 
                '<p style="color: ' + (cb_frontend.colors?.text || '#666') + '; font-style: italic; text-align: center; padding: 20px;">' +
                noExtrasText +
                '</p></div>';
            return;
        }
        
        // Restore state after clearing
        restoreExtrasState(preservedExtras);
        
        extras.forEach(function(extra) {
            // Check if this extra is already selected - use loose comparison for type safety
            const isSelected = bookingData.extras && bookingData.extras.some(id => id == extra.id);
            const selectedClass = isSelected ? ' selected' : '';
            const isPerSqm = extra.pricing_type === 'per_sqm';
            
            const itemElement = document.createElement('div');
            itemElement.className = `cb-extra-item${selectedClass}`;
            itemElement.dataset.extraId = extra.id;
            itemElement.dataset.extraPrice = extra.price;
            itemElement.dataset.pricingType = extra.pricing_type || 'fixed';
            itemElement.dataset.pricePerSqm = extra.price_per_sqm || '0';
            itemElement.style.cursor = 'pointer';
            
            // Get current space for this extra if it's per sqm
            const currentSpace = bookingData.extra_spaces && bookingData.extra_spaces[extra.id] ? bookingData.extra_spaces[extra.id] : '';
            
            let priceDisplay = '€0.00';
            if (isPerSqm && isSelected && currentSpace) {
                const calculatedPrice = parseFloat(extra.price_per_sqm) * parseFloat(currentSpace);
                priceDisplay = '€' + calculatedPrice.toFixed(2);
            } else if (isPerSqm) {
                // Show per m² rate for unselected per sqm extras
                priceDisplay = '€' + parseFloat(extra.price_per_sqm || 0).toFixed(2) + ' per m²';
            } else {
                priceDisplay = '€' + parseFloat(extra.price).toFixed(2);
            }
            
            let spaceInput = '';
            if (isPerSqm && isSelected) {
                spaceInput = `
                    <div class="cb-extra-space-input-container" style="margin-top: 10px;">
                        <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Enter space (m²):</label>
                        <input type="number" class="cb-extra-space-input" data-extra-id="${extra.id}" 
                               value="${currentSpace}" min="0" step="0.1" 
                               style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px;" />
                    </div>
                `;
            }
            
            itemElement.innerHTML = `
                    <div class="cb-extra-checkbox"></div>
                    <div class="cb-extra-info">
                        <div class="cb-extra-name">${extra.name}</div>
                        <div class="cb-extra-description">${extra.description}</div>
                        ${spaceInput}
                        <div class="cb-extra-price">${priceDisplay}</div>
                    </div>
            `;
            
            // Click handler is already attached via document delegation
            
            extrasContainer.appendChild(itemElement);
        });
    }
    
    function preserveExtrasState() {
        // Store current selected extras before any DOM manipulation
        const currentSelectedExtras = [...(bookingData.extras || [])];
        return currentSelectedExtras;
    }
    
    function restoreExtrasState(preservedExtras) {
        // Restore the selected extras after DOM manipulation
        if (preservedExtras && Array.isArray(preservedExtras)) {
            bookingData.extras = [...preservedExtras];
        }
    }
    
    function forceUpdateExtrasVisualState() {
        
        // Update all extra items to reflect current state
        const extraItems = document.querySelectorAll('.cb-extra-item');
        extraItems.forEach(function(item) {
            const extraId = parseInt(item.dataset.extraId, 10);
            const isSelected = bookingData.extras && bookingData.extras.some(id => id == extraId);
            
            if (isSelected) {
                // Only add class if not already present
                if (!item.classList.contains('selected')) {
                    item.classList.add('selected');
                }
            } else {
                // Only remove class if present
                if (item.classList.contains('selected')) {
                    item.classList.remove('selected');
                }
            }
        });
        
        // Update sidebar
        updateSelectedExtras();
    }
    
    // Price calculation functions
    function calculatePrice() {
        if (!bookingData.service_id) {
            return;
        }
        
        // Get service data to check for default area
        const service = services.find(s => s.id == bookingData.service_id);
        const additionalArea = bookingData.square_meters || 0;
        
        
        
        const formData = new FormData();
        formData.append('action', 'cb_calculate_price');
        formData.append('nonce', cb_frontend.nonce);
        formData.append('service_id', bookingData.service_id);
        formData.append('square_meters', additionalArea); // Send additional area only
        formData.append('use_smart_area', '1'); // Flag to indicate smart area calculation
        
        // Handle extras properly - ensure it's always an array
        if (bookingData.extras && Array.isArray(bookingData.extras) && bookingData.extras.length > 0) {
            formData.append('extras', JSON.stringify(bookingData.extras));
        } else {
            formData.append('extras', '[]'); // Send empty array as JSON string
        }
            
        // Send extra spaces for per m² extras
        if (bookingData.extra_spaces && Object.keys(bookingData.extra_spaces).length > 0) {
            formData.append('extra_spaces', JSON.stringify(bookingData.extra_spaces));
        }
        
        formData.append('zip_code', bookingData.zip_code);
        
        // Send express cleaning flag
        formData.append('express_cleaning', bookingData.express_cleaning ? '1' : '0');
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid JSON');
                }
            });
        })
        .then(response => {
                
                if (response.success) {
                    bookingData.pricing = response.data.pricing;
                    
                    updatePricingDisplay();
                    updateSidebarDisplay();
                } else {
                    console.error('Price calculation failed:', response.data.message);
                }
        })
        .catch(error => {
            console.error('Price calculation error:', error);
            
            // Show user-friendly error message
            showNotification(cb_frontend.translations['Unable to calculate price. Please try again.'] || 'Δεν είναι δυνατός ο υπολογισμός της τιμής. Παρακαλούμε προσπαθήστε ξανά.', 'error');
                
                // Try to extract JSON from mixed response
                try {
                if (error.message && error.message.includes('Invalid JSON')) {
                    console.error('Server returned HTML instead of JSON - likely a PHP error');
                    showNotification(cb_frontend.strings.server_error || 'Σφάλμα διακομιστή. Παρακαλούμε ελέγξτε τη διαμόρφωσή σας.', 'error');
                    }
                } catch (e) {
                console.error('Could not extract error details:', e);
            }
        });
    }
    
    function updatePricingDisplay() {
        const pricing = bookingData.pricing;
        
        // Debug logging
        
        
        
        
        // Update main pricing display (Step 5)
        const summaryPrice = document.getElementById('cb-summary-price');
        const summaryDuration = document.getElementById('cb-summary-duration');
        
        if (summaryPrice) summaryPrice.textContent = formatPrice(pricing.total_price);
        if (summaryDuration) summaryDuration.textContent = formatDuration(pricing.total_duration);
        
        // Update sidebar pricing
        const sidebarTotal = document.getElementById('cb-sidebar-total');
        if (sidebarTotal) sidebarTotal.textContent = formatPrice(pricing.total_price);
        
        // Update checkout button price
        const checkoutPrice = document.getElementById('cb-checkout-price');
        if (checkoutPrice) checkoutPrice.textContent = formatPrice(pricing.total_price);
        
        // Update Step 3 pricing display with smart area breakdown
        const step3ServicePrice = document.getElementById('cb-step3-service-price');
        const step3ExtraSqmPrice = document.getElementById('cb-step3-extra-sqm-price');
        const step3ExtraSqmItem = document.getElementById('cb-step3-extra-sqm-item');
        const step3ExtrasPrice = document.getElementById('cb-step3-extras-price');
        const step3TotalPrice = document.getElementById('cb-step3-total-price');
        
        if (step3ServicePrice) {
            if (pricing.smart_area && pricing.smart_area.use_smart_area) {
                // Show base service price with inline breakdown
                const basePrice = parseFloat(pricing.smart_area.base_price) || 0;
                const additionalPrice = parseFloat(pricing.smart_area.additional_price) || 0;
                const zipSurcharge = parseFloat(pricing.zip_surcharge) || 0;
                
                // Show base service price with inline breakdown including extra m²
                step3ServicePrice.innerHTML = `
                    <div class="cb-price-breakdown">
                        <div>Base: ${formatPrice(basePrice)}</div>
                        ${additionalPrice > 0 ? `<div>Extra m²: ${formatPrice(additionalPrice)}</div>` : ''}
                        ${zipSurcharge > 0 ? `<div>Zip Fee: ${formatPrice(zipSurcharge)}</div>` : ''}
                    </div>
                `;
                
                // Hide separate extra m² row since it's shown inline
                if (step3ExtraSqmItem) {
                    step3ExtraSqmItem.style.display = 'none';
                }
            } else {
                // Non-smart area: split service price into base and extra m² if applicable
                const basePrice = parseFloat(pricing.smart_area?.base_price) || parseFloat(pricing.service_price) || 0;
                const additionalPrice = parseFloat(pricing.smart_area?.additional_price) || 0;
                
                if (additionalPrice > 0 && pricing.smart_area) {
                    // Show with inline breakdown
                    step3ServicePrice.innerHTML = `
                        <div class="cb-price-breakdown">
                            <div>Base: ${formatPrice(basePrice)}</div>
                            <div>Extra m²: ${formatPrice(additionalPrice)}</div>
                        </div>
                    `;
                    if (step3ExtraSqmItem) {
                        step3ExtraSqmItem.style.display = 'none';
                    }
                } else {
                    step3ServicePrice.textContent = formatPrice(pricing.service_price);
                    if (step3ExtraSqmItem) {
                        step3ExtraSqmItem.style.display = 'none';
                    }
                }
            }
        }
        
        // Update service extras price separately (this is for additional services, not extra m²)
        if (step3ExtrasPrice) {
            if (pricing.extras_price > 0) {
                step3ExtrasPrice.textContent = formatPrice(pricing.extras_price);
            } else {
                step3ExtrasPrice.textContent = '€0.00';
            }
        }
        
        // Show express cleaning surcharge if applicable
        if (pricing.express_cleaning && pricing.express_surcharge > 0) {
            const step3ExpressPrice = document.getElementById('cb-step3-express-price');
            if (step3ExpressPrice) {
                step3ExpressPrice.textContent = formatPrice(pricing.express_surcharge);
            }
        }
        
        if (step3TotalPrice) step3TotalPrice.textContent = formatPrice(pricing.total_price);
        
        // Update Step 3 duration display with smart area breakdown
        const step3ServiceDuration = document.getElementById('cb-step3-service-duration');
        const step3ExtraSqmDuration = document.getElementById('cb-step3-extra-sqm-duration');
        const step3ExtraSqmDurationItem = document.getElementById('cb-step3-extra-sqm-duration-item');
        const step3ExtrasDuration = document.getElementById('cb-step3-extras-duration');
        const step3TotalDuration = document.getElementById('cb-step3-total-duration');
        
        if (step3ServiceDuration) {
            if (pricing.smart_area && pricing.smart_area.use_smart_area) {
                // Show base service duration with inline breakdown
                const baseDuration = parseInt(pricing.smart_area.base_duration, 10) || 0;
                const additionalDuration = parseInt(pricing.smart_area.additional_duration, 10) || 0;
                
                if (additionalDuration > 0) {
                    step3ServiceDuration.innerHTML = `
                        <div class="cb-duration-breakdown">
                            <div>Base: ${formatDuration(baseDuration)}</div>
                            <div>Extra m²: ${formatDuration(additionalDuration)}</div>
                        </div>
                    `;
                } else {
                    step3ServiceDuration.textContent = formatDuration(baseDuration);
                }
                
                // Hide separate extra m² duration row since it's shown inline
                if (step3ExtraSqmDurationItem) {
                    step3ExtraSqmDurationItem.style.display = 'none';
                }
            } else {
                // Non-smart area: split duration into base and extra m² if applicable
                const baseDuration = parseInt(pricing.smart_area?.base_duration, 10) || parseInt(pricing.service_duration, 10) || 0;
                const additionalDuration = parseInt(pricing.smart_area?.additional_duration, 10) || 0;
                
                if (additionalDuration > 0 && pricing.smart_area) {
                    step3ServiceDuration.innerHTML = `
                        <div class="cb-duration-breakdown">
                            <div>Base: ${formatDuration(baseDuration)}</div>
                            <div>Extra m²: ${formatDuration(additionalDuration)}</div>
                        </div>
                    `;
                    if (step3ExtraSqmDurationItem) {
                        step3ExtraSqmDurationItem.style.display = 'none';
                    }
                } else {
                    step3ServiceDuration.textContent = formatDuration(pricing.service_duration);
                    if (step3ExtraSqmDurationItem) {
                        step3ExtraSqmDurationItem.style.display = 'none';
                    }
                }
            }
        }
        
        // Update service extras duration (for additional services, not extra m²)
        if (step3ExtrasDuration) step3ExtrasDuration.textContent = formatDuration(pricing.extras_duration);
        if (step3TotalDuration) step3TotalDuration.textContent = formatDuration(pricing.total_duration);
        
        // Update booking summary to ensure all fields are current
        updateBookingSummary();
    }
    
    function formatPrice(price) {
        return '€' + parseFloat(price).toFixed(2);
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
    
    
    // Utility functions
    function showError(errorId, message) {
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
        
        // Also show notification for important errors
        if (message.includes('required') || message.includes('invalid')) {
            showNotification(message, 'error');
        }
    }
    
    function hideError(errorId) {
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
            errorElement.classList.remove('show');
            errorElement.textContent = '';
        }
    }
    
    function showNotification(message, type = 'info') {
        // Remove any existing notifications
        const existingNotifications = document.querySelectorAll('.cb-notification');
        existingNotifications.forEach(notification => notification.remove());
        
        // Get custom colors from admin settings
        const colors = cb_frontend.colors || {};
        let backgroundColor;
        
        switch(type) {
            case 'success':
                backgroundColor = colors.success || '#46b450';
                break;
            case 'error':
                backgroundColor = colors.error || '#dc3232';
                break;
            case 'warning':
                backgroundColor = colors.secondary || '#00a0d2';
                break;
            default:
                backgroundColor = colors.primary || '#0073aa';
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `cb-notification cb-notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: ${backgroundColor};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 400px;
            animation: slideInRight 0.3s ease;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
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
    
    // Initialize default payment method selection when step 5 is displayed
    function initializePaymentMethod() {
        if (currentStep === 5) {
            // Set default selection to cash
            const cashOption = document.querySelector('.cb-payment-option[data-payment="cash"]');
            if (cashOption) {
                cashOption.classList.add('selected');
            }
            bookingData.payment_method = 'cash';
            
            // Update button states
            updateButtonStates();
        }
    }
    
    
    // Release slot when page is unloaded
    window.addEventListener('beforeunload', function() {
        if (currentSlotSessionId) {
            // Use synchronous request for beforeunload
            const formData = new FormData();
            formData.append('action', 'cb_release_slot');
            formData.append('session_id', currentSlotSessionId);
            
            // Use sendBeacon if available, otherwise synchronous XMLHttpRequest
            if (navigator.sendBeacon) {
                navigator.sendBeacon(cb_frontend.ajax_url, formData);
            } else {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', cb_frontend.ajax_url, false);
                xhr.send(formData);
            }
        }
    });

    // Clear any cached form data when the page loads
    clearCachedFormData();
    
    function clearCachedFormData() {
        // Clear any localStorage data that might be caching old form data
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && (key.startsWith('cb_') || key.startsWith('woocommerce_'))) {
                keysToRemove.push(key);
            }
        }
        
        keysToRemove.forEach(key => {
            localStorage.removeItem(key);
        });
    }
    
    // Helper function to get currently selected service
    function getSelectedService() {
        const selectedServiceCard = document.querySelector('.cb-service-card.selected');
        if (selectedServiceCard) {
            const serviceId = parseInt(selectedServiceCard.dataset.serviceId);
            return services.find(service => service.id === serviceId);
        }
        return null;
    }
    
    // Helper function to update form fields with new translations
    function updateFormFields() {
        // Update field labels
        const fieldLabels = document.querySelectorAll('.cb-form-group label');
        fieldLabels.forEach(label => {
            const fieldKey = label.dataset.fieldKey;
            if (fieldKey && cb_frontend.form_fields && cb_frontend.form_fields[fieldKey]) {
                label.textContent = cb_frontend.form_fields[fieldKey].label;
            }
        });
        
        // Update field placeholders
        const fieldInputs = document.querySelectorAll('.cb-form-group input, .cb-form-group textarea, .cb-form-group select');
        fieldInputs.forEach(input => {
            const fieldKey = input.dataset.fieldKey;
            if (fieldKey && cb_frontend.form_fields && cb_frontend.form_fields[fieldKey]) {
                input.placeholder = cb_frontend.form_fields[fieldKey].placeholder;
            }
        });
    }
    
    // Helper function to display time slots with new data
    function displayTimeSlots(slotsData) {
        if (!slotsData) {
            return;
        }
        
        const timeSlotsContainer = document.querySelector('.cb-time-slots');
        if (!timeSlotsContainer) {
            return;
        }
        
        // Clear existing slots
        timeSlotsContainer.innerHTML = '';
        
        // Handle different data structures
        if (slotsData.slots && Array.isArray(slotsData.slots)) {
            // Structure: {slots: [...]}
            const slots = slotsData.slots;
            if (slots.length === 0) {
                timeSlotsContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.no_slots_available + '</div>';
                return;
            }
            
            // Group slots by date if they have date information
            const slotsByDate = {};
            slots.forEach(slot => {
                const date = slot.date || 'Today'; // Use slot date or default to 'Today'
                if (!slotsByDate[date]) {
                    slotsByDate[date] = [];
                }
                slotsByDate[date].push(slot);
            });
            
            // Display slots for each date
            Object.keys(slotsByDate).forEach(dateKey => {
                const dateData = slotsByDate[dateKey];
                const dateElement = document.createElement('div');
                dateElement.className = 'cb-date-group';
                dateElement.innerHTML = `
                    <h4>${dateKey}</h4>
                    <div class="cb-time-grid">
                        ${dateData.map(slot => `
                            <button class="cb-time-slot ${slot.available ? 'available' : 'unavailable'}" 
                                    data-time="${slot.time}" 
                                    data-date="${dateKey}"
                                    ${!slot.available ? 'disabled' : ''}>
                                ${slot.time}
                            </button>
                        `).join('')}
                    </div>
                `;
                timeSlotsContainer.appendChild(dateElement);
            });
        } else {
            // Structure: {date1: {slots: [...]}, date2: {slots: [...]}}
            Object.keys(slotsData).forEach(dateKey => {
                const dateData = slotsData[dateKey];
                const dateElement = document.createElement('div');
                dateElement.className = 'cb-date-group';
                dateElement.innerHTML = `
                    <h4>${dateData.date}</h4>
                    <div class="cb-time-grid">
                        ${dateData.slots.map(slot => `
                            <button class="cb-time-slot ${slot.available ? 'available' : 'unavailable'}" 
                                    data-time="${slot.time}" 
                                    data-date="${dateData.date}"
                                    ${!slot.available ? 'disabled' : ''}>
                                ${slot.time}
                            </button>
                        `).join('')}
                    </div>
                `;
                timeSlotsContainer.appendChild(dateElement);
            });
        }
    }
});
