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
    if (typeof document !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function() {
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
    
    // Global variables for slot management
    let currentSlotSessionId = null;
    let slotHoldTimeout = null;
    
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
        customer_company: '',
        address: '',
        customer_city: '',
        customer_state: '',
        notes: '',
        payment_method: 'cash',
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
                console.log('Slot held successfully:', currentSlotSessionId);
                
                // Set up automatic release after 15 minutes
                slotHoldTimeout = setTimeout(() => {
                    releaseSlot();
                }, 15 * 60 * 1000); // 15 minutes
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
                console.log('Slot released successfully');
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
        // Check for saved language preference
        const savedLanguage = localStorage.getItem('cb_language') || cb_frontend.current_language;
        
        console.log('Initializing language. Saved:', savedLanguage, 'Current:', cb_frontend.current_language);
        
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
            console.log('Language mismatch, switching to saved language:', savedLanguage);
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
            
            console.log('Selected extras:', selectedExtrasNames);
        } else {
            // Hide the section if no extras selected
            if (selectedExtrasSection) {
                selectedExtrasSection.style.display = 'none';
            }
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
        const sidebarServiceTitle = document.getElementById('cb-sidebar-service-title');
        if (sidebarServiceTitle) {
            sidebarServiceTitle.textContent = title;
        }
    }
    
    function updateDuration() {
        // Use default duration since pricing is disabled
        const sidebarDuration = document.getElementById('cb-sidebar-duration');
        if (sidebarDuration) {
            sidebarDuration.textContent = '2 hours';
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
        
        // Customer details
        const customerInputs = document.querySelectorAll('#cb-customer-name, #cb-customer-email, #cb-customer-phone');
        customerInputs.forEach(input => {
            input.addEventListener('input', updateCheckoutButton);
        });
        
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
        
        // Time slot selection
        document.addEventListener('click', function(e) {
            if (e.target.closest('.cb-time-slot')) {
                handleTimeSlotSelect(e);
            }
        });
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
            showNotification('Validation failed. Please try again.', 'error');
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
    }
    
    function checkAndCalculatePrice() {
        if (bookingData.service_id) {
            calculatePrice();
        }
    }
    
    function handleZipCodeChange(e) {
        bookingData.zip_code = e.target.value.trim();
    }
    
    function handleSquareMetersChange(e) {
        const squareMeters = parseInt(e.target.value, 10) || 0; // Use base 10 explicitly
        bookingData.square_meters = squareMeters;
        
        // Calculate price if service is selected
        checkAndCalculatePrice();
        
        // Update button states
        updateButtonStates();
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
    
    function handleServiceSelect(e) {
        e.preventDefault();
        
        const clickedCard = e.target.closest('.cb-service-card');
        
        // Remove previous selection
        const serviceCards = document.querySelectorAll('.cb-service-card');
        serviceCards.forEach(card => card.classList.remove('selected'));
        
        // Add selection to clicked card
        clickedCard.classList.add('selected');
        
        bookingData.service_id = clickedCard.dataset.serviceId;
        console.log('Service selected - service_id:', bookingData.service_id);
        console.log('Current bookingData:', bookingData);
        
        // Enable next button
        const nextStepBtn = document.querySelector('.cb-step-2 .cb-next-step');
        if (nextStepBtn) {
            nextStepBtn.disabled = false;
        }
        
        // Calculate price if square meters are available
        checkAndCalculatePrice();
        
        // Update sidebar display
        updateSidebarDisplay();
    }
    
    function handleExtraSelect(e) {
        e.preventDefault();
        
        const clickedItem = e.target.closest('.cb-extra-item');
        
        // Prevent double-clicking
        if (clickedItem.classList.contains('cb-processing')) {
            console.log('Extra item is being processed, ignoring click');
            return;
        }
        
        clickedItem.classList.add('cb-processing');
        
        const extraId = parseInt(clickedItem.dataset.extraId, 10); // Convert to number immediately
        const extraPrice = parseFloat(clickedItem.dataset.extraPrice);
        
        console.log('Extra item clicked:', clickedItem, 'extraId:', extraId, 'type:', typeof extraId);
        
        // Ensure extras array exists
        if (!Array.isArray(bookingData.extras)) {
            bookingData.extras = [];
        }
        
        console.log('Current extras array:', bookingData.extras, 'Looking for ID:', extraId);
        
        if (clickedItem.classList.contains('selected')) {
            // Remove extra
            console.log('Removing extra:', extraId);
            console.log('Element classes before removal:', clickedItem.className);
            clickedItem.classList.remove('selected');
            console.log('Element classes after removal:', clickedItem.className);
            bookingData.extras = bookingData.extras.filter(id => parseInt(id, 10) !== extraId);
        } else {
            // Add extra
            console.log('Adding extra:', extraId);
            console.log('Element classes before adding:', clickedItem.className);
            clickedItem.classList.add('selected');
            console.log('Element classes after adding:', clickedItem.className);
            bookingData.extras.push(extraId);
            
            // Debug: Check if class was actually added
            setTimeout(() => {
                console.log('Element has selected class after adding:', clickedItem.classList.contains('selected'));
                console.log('Element classes:', clickedItem.className);
            }, 100);
        }
        
        console.log('Updated extras array:', bookingData.extras);
        
        // Recalculate price with new extras
        checkAndCalculatePrice();
        
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
            showNotification('This time slot is already booked. Please select another time.', 'warning');
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
        
        // Enable next button
        const nextStepBtn = document.querySelector('.cb-step-4 .cb-next-step');
        if (nextStepBtn) {
            nextStepBtn.disabled = false;
        }
        
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
        
        console.log('Switching to language:', targetLanguage);
        
        const formData = new FormData();
        formData.append('action', 'cb_switch_language');
        formData.append('language', targetLanguage);
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
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
        
        console.log('Updating UI translations with:', translations);
        
        if (!translations || Object.keys(translations).length === 0) {
            console.log('No translations available, skipping update');
            return; // No translations available
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
        if (translations['Service Details']) {
            const step3Header = document.querySelector('.cb-step-3 .cb-step-header h2');
            if (step3Header) step3Header.textContent = translations['Service Details'];
        }
        if (translations['Tell us about your space and any additional services']) {
            const step3Desc = document.querySelector('.cb-step-3 .cb-step-header p');
            if (step3Desc) step3Desc.textContent = translations['Tell us about your space and any additional services'];
        }
        if (translations['Select Date & Time']) {
            const step4Header = document.querySelector('.cb-step-4 .cb-step-header h2');
            if (step4Header) step4Header.textContent = translations['Select Date & Time'];
        }
        if (translations['Choose your preferred date and time slot']) {
            const step4Desc = document.querySelector('.cb-step-4 .cb-step-header p');
            if (step4Desc) step4Desc.textContent = translations['Choose your preferred date and time slot'];
        }
        if (translations['Your Details']) {
            const step5Header = document.querySelector('.cb-step-5 .cb-step-header h2');
            if (step5Header) step5Header.textContent = translations['Your Details'];
        }
        if (translations['Please provide your contact information to complete the booking']) {
            const step5Desc = document.querySelector('.cb-step-5 .cb-step-header p');
            if (step5Desc) step5Desc.textContent = translations['Please provide your contact information to complete the booking'];
        }
        
        // Update main title
        if (translations['Book Your Cleaning Service']) {
            const mainTitle = document.querySelector('.cb-title');
            if (mainTitle) mainTitle.textContent = translations['Book Your Cleaning Service'];
        }
        
        // Update form labels
        if (translations['ZIP Code']) {
            const zipLabel = document.querySelector('label[for="cb-zip-code"]');
            if (zipLabel) zipLabel.textContent = translations['ZIP Code'];
        }
        if (translations['Square Meters']) {
            const sqmLabel = document.querySelector('label[for="cb-square-meters"]');
            if (sqmLabel) sqmLabel.textContent = translations['Square Meters'];
        }
        if (translations['Additional Services']) {
            const extrasLabel = document.querySelector('.cb-step-3 .cb-form-group label');
            if (extrasLabel) extrasLabel.textContent = translations['Additional Services'];
        }
        if (translations['Date']) {
            const dateLabel = document.querySelector('label[for="cb-booking-date"]');
            if (dateLabel) dateLabel.textContent = translations['Date'];
        }
        if (translations['Available Time Slots']) {
            const timeLabel = document.querySelector('.cb-step-4 .cb-form-group label');
            if (timeLabel) timeLabel.textContent = translations['Available Time Slots'];
        }
        if (translations['Full Name']) {
            const nameLabel = document.querySelector('label[for="cb-customer-name"]');
            if (nameLabel) nameLabel.textContent = translations['Full Name'];
        }
        if (translations['Email Address']) {
            const emailLabel = document.querySelector('label[for="cb-customer-email"]');
            if (emailLabel) emailLabel.textContent = translations['Email Address'];
        }
        if (translations['Phone Number']) {
            const phoneLabel = document.querySelector('label[for="cb-customer-phone"]');
            if (phoneLabel) phoneLabel.textContent = translations['Phone Number'];
        }
        if (translations['Address']) {
            const addressLabel = document.querySelector('label[for="cb-customer-address"]');
            if (addressLabel) addressLabel.textContent = translations['Address'];
        }
        if (translations['Special Instructions']) {
            const notesLabel = document.querySelector('label[for="cb-notes"]');
            if (notesLabel) notesLabel.textContent = translations['Special Instructions'];
        }
        
        // Update placeholder text
        if (translations['Street address, apartment, etc.']) {
            const addressInput = document.getElementById('cb-customer-address');
            if (addressInput) addressInput.setAttribute('placeholder', translations['Street address, apartment, etc.']);
        }
        if (translations['Any special requests or instructions...']) {
            const notesInput = document.getElementById('cb-notes');
            if (notesInput) notesInput.setAttribute('placeholder', translations['Any special requests or instructions...']);
        }
        
        // Update button text
        if (translations['Check Availability']) {
            const checkAvailabilityBtn = document.querySelector('.cb-next-step[data-next="2"]');
            if (checkAvailabilityBtn) checkAvailabilityBtn.textContent = translations['Check Availability'];
        }
        if (translations['Back']) {
            const backBtns = document.querySelectorAll('.cb-prev-step');
            backBtns.forEach(btn => btn.textContent = translations['Back']);
        }
        if (translations['Continue']) {
            const continueBtns = document.querySelectorAll('.cb-next-step:not([data-next="2"])');
            continueBtns.forEach(btn => btn.textContent = translations['Continue']);
        }
        if (translations['I am ordering']) {
            const checkoutBtn = document.getElementById('cb-sidebar-checkout');
            if (checkoutBtn) checkoutBtn.textContent = translations['I am ordering'];
        }
        if (translations['Proceed to Checkout']) {
            const proceedBtns = document.querySelectorAll('.cb-btn-checkout');
            proceedBtns.forEach(btn => btn.textContent = translations['Proceed to Checkout']);
        }
        
        // Update sidebar text
        if (translations['Please select a service to see pricing']) {
            const sidebarTitle = document.getElementById('cb-sidebar-service-title');
            if (sidebarTitle) sidebarTitle.textContent = translations['Please select a service to see pricing'];
        }
        if (translations['Our contractors have all the necessary cleaning products and equipment']) {
            const infoText = document.querySelector('.cb-info-text');
            if (infoText) infoText.textContent = translations['Our contractors have all the necessary cleaning products and equipment'];
        }
        if (translations['Approximate working time']) {
            const durationLabel = document.querySelector('.cb-duration-label');
            if (durationLabel) durationLabel.textContent = translations['Approximate working time'];
        }
        if (translations['Promocode']) {
            const promocodeInput = document.getElementById('cb-promocode');
            if (promocodeInput) promocodeInput.setAttribute('placeholder', translations['Promocode']);
        }
        if (translations['Apply']) {
            const applyBtn = document.getElementById('cb-apply-promocode');
            if (applyBtn) applyBtn.textContent = translations['Apply'];
        }
        if (translations['To be paid:']) {
            const priceLabel = document.querySelector('.cb-price-label');
            if (priceLabel) priceLabel.textContent = translations['To be paid:'];
        }
        
        // Update all buttons more comprehensively
        if (translations['Check Availability']) {
            const checkBtns = document.querySelectorAll('.cb-next-step[data-next="2"]');
            checkBtns.forEach(btn => btn.textContent = translations['Check Availability']);
            
            // Also update any buttons containing the text
            const allButtons = document.querySelectorAll('button');
            allButtons.forEach(btn => {
                if (btn.textContent.includes('Check Availability')) {
                    btn.textContent = translations['Check Availability'];
                }
            });
        }
        if (translations['Back']) {
            const backBtns = document.querySelectorAll('.cb-prev-step');
            backBtns.forEach(btn => btn.textContent = translations['Back']);
            
            // Also update any buttons containing the text
            const allButtons = document.querySelectorAll('button');
            allButtons.forEach(btn => {
                if (btn.textContent.includes('Back')) {
                    btn.textContent = translations['Back'];
                }
            });
        }
        if (translations['Continue']) {
            const continueBtns = document.querySelectorAll('.cb-next-step:not([data-next="2"])');
            continueBtns.forEach(btn => btn.textContent = translations['Continue']);
            
            // Also update any buttons containing the text
            const allButtons = document.querySelectorAll('button');
            allButtons.forEach(btn => {
                if (btn.textContent.includes('Continue')) {
                    btn.textContent = translations['Continue'];
                }
            });
        }
        if (translations['I am ordering']) {
            const checkoutBtn = document.getElementById('cb-sidebar-checkout');
            if (checkoutBtn) checkoutBtn.textContent = translations['I am ordering'];
            
            // Also update any buttons containing the text
            const allButtons = document.querySelectorAll('button');
            allButtons.forEach(btn => {
                if (btn.textContent.includes('I am ordering')) {
                    btn.textContent = translations['I am ordering'];
                }
            });
        }
        if (translations['Proceed to Checkout']) {
            const proceedBtns = document.querySelectorAll('.cb-btn-checkout');
            proceedBtns.forEach(btn => btn.textContent = translations['Proceed to Checkout']);
            
            // Also update any buttons containing the text
            const allButtons = document.querySelectorAll('button');
            allButtons.forEach(btn => {
                if (btn.textContent.includes('Proceed to Checkout')) {
                    btn.textContent = translations['Proceed to Checkout'];
                }
            });
        }
        
        // Update booking summary
        if (translations['Booking Summary']) {
            const summaryTitle = document.querySelector('.cb-booking-summary h4');
            if (summaryTitle) summaryTitle.textContent = translations['Booking Summary'];
        }
        if (translations['Service:']) {
            const serviceItems = document.querySelectorAll('.cb-summary-item');
            serviceItems.forEach(item => {
                if (item.textContent.includes('Service:')) {
                    const firstSpan = item.querySelector('span:first-child');
                    if (firstSpan) firstSpan.textContent = translations['Service:'];
                }
            });
        }
        if (translations['Date:']) {
            const dateItems = document.querySelectorAll('.cb-summary-item');
            dateItems.forEach(item => {
                if (item.textContent.includes('Date:')) {
                    const firstSpan = item.querySelector('span:first-child');
                    if (firstSpan) firstSpan.textContent = translations['Date:'];
                }
            });
        }
        if (translations['Time:']) {
            const timeItems = document.querySelectorAll('.cb-summary-item');
            timeItems.forEach(item => {
                if (item.textContent.includes('Time:')) {
                    const firstSpan = item.querySelector('span:first-child');
                    if (firstSpan) firstSpan.textContent = translations['Time:'];
                }
            });
        }
        if (translations['Duration:']) {
            const durationItems = document.querySelectorAll('.cb-summary-item');
            durationItems.forEach(item => {
                if (item.textContent.includes('Duration:')) {
                    const firstSpan = item.querySelector('span:first-child');
                    if (firstSpan) firstSpan.textContent = translations['Duration:'];
                }
            });
        }
        if (translations['Total Price:']) {
            const priceItems = document.querySelectorAll('.cb-summary-item');
            priceItems.forEach(item => {
                if (item.textContent.includes('Total Price:')) {
                    const firstSpan = item.querySelector('span:first-child');
                    if (firstSpan) firstSpan.textContent = translations['Total Price:'];
                }
            });
        }
        
        // Update loading messages
        if (translations['Loading services...']) {
            const loadingElements = document.querySelectorAll('.cb-loading');
            loadingElements.forEach(el => el.textContent = translations['Loading services...']);
        }
        if (translations['Loading extras...']) {
            const loadingElements = document.querySelectorAll('.cb-loading');
            loadingElements.forEach(el => el.textContent = translations['Loading extras...']);
        }
        if (translations['Loading available times...']) {
            const loadingElements = document.querySelectorAll('.cb-loading');
            loadingElements.forEach(el => el.textContent = translations['Loading available times...']);
        }
        if (translations['Select a date to see available times']) {
            const loadingElements = document.querySelectorAll('.cb-loading');
            loadingElements.forEach(el => el.textContent = translations['Select a date to see available times']);
        }
        
        // Update time units
        if (translations['hours']) {
            const durationElements = document.querySelectorAll('.cb-duration-value');
            durationElements.forEach(el => {
                el.textContent = el.textContent.replace(/hours?/g, translations['hours']);
            });
        }
        if (translations['hour']) {
            const durationElements = document.querySelectorAll('.cb-duration-value');
            durationElements.forEach(el => {
                el.textContent = el.textContent.replace(/hour/g, translations['hour']);
            });
        }
        
        // Force update any remaining English text with more aggressive selectors
        console.log('Translation update completed. Checking for remaining English text...');
        
        // Update any text that might have been missed
        setTimeout(function() {
            // Force update main title if it's still in English
            if (translations['Book Your Cleaning Service']) {
                const titleElement = document.querySelector('.cb-title');
                if (titleElement && titleElement.textContent === 'Book Your Cleaning Service') {
                    titleElement.textContent = translations['Book Your Cleaning Service'];
                console.log('Updated main title to:', translations['Book Your Cleaning Service']);
                }
            }
            
            // Force update step headers
            if (translations['Select Your Service']) {
                const step2Header = document.querySelector('.cb-step-2 .cb-step-header h2');
                if (step2Header && step2Header.textContent === 'Select Your Service') {
                    step2Header.textContent = translations['Select Your Service'];
                console.log('Updated step 2 header to:', translations['Select Your Service']);
                }
            }
            
            // Force update sidebar title
            if (translations['Please select a service to see pricing']) {
                const sidebarTitle = document.getElementById('cb-sidebar-service-title');
                if (sidebarTitle && sidebarTitle.textContent === 'Please select a service to see pricing') {
                    sidebarTitle.textContent = translations['Please select a service to see pricing'];
                console.log('Updated sidebar title to:', translations['Please select a service to see pricing']);
                }
            }
        }, 100);
    }
    
    function proceedToCheckout() {
        if (!validateBookingData()) {
            return;
        }
        
        // Show loading state
        const sidebarCheckout = document.getElementById('cb-sidebar-checkout');
        if (sidebarCheckout) {
            sidebarCheckout.disabled = true;
            sidebarCheckout.textContent = cb_frontend.strings.processing;
        }
        
        // Collect all form data
        const formData = new FormData();
        formData.append('action', 'cb_create_booking');
        formData.append('nonce', cb_frontend.nonce);
        
        // Add booking data
        Object.keys(bookingData).forEach(key => {
            if (key === 'extras' && Array.isArray(bookingData[key])) {
                bookingData[key].forEach(extra => {
                    formData.append('extras[]', extra);
                });
            } else {
                formData.append(key, bookingData[key]);
            }
        });
        
        // Add payment data
        const paymentData = collectPaymentData();
        Object.keys(paymentData).forEach(key => {
            formData.append(key, paymentData[key]);
        });
        
        console.log('Submitting booking data:', Object.fromEntries(formData));
        console.log('Extras being sent:', bookingData.extras, 'Type:', typeof bookingData.extras, 'Length:', bookingData.extras.length);
        
        // Submit booking
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
                console.log('Booking response:', response);
                
                if (response.success) {
                // Release the held slot since booking is created
                if (currentSlotSessionId) {
                    releaseSlot();
                }
                
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
                    if (sidebarCheckout) {
                        sidebarCheckout.disabled = false;
                        sidebarCheckout.textContent = 'Proceed to Checkout';
                    }
                        }
                } else {
                    console.error('Booking failed:', response);
                    showNotification(response.data.message || cb_frontend.strings.booking_error, 'error');
                    // Restore button state
                if (sidebarCheckout) {
                    sidebarCheckout.disabled = false;
                    sidebarCheckout.textContent = 'Proceed to Checkout';
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
                
                // Try to extract JSON from mixed HTML/JSON response
                let errorMessage = cb_frontend.strings.server_error;
                try {
                if (error.responseText) {
                    const responseText = error.responseText;
                    console.log('Response text:', responseText);
                    
                    // Look for JSON in the response
                    const jsonMatch = responseText.match(/\{.*\}/s);
                    if (jsonMatch) {
                        const jsonResponse = JSON.parse(jsonMatch[0]);
                        if (jsonResponse.data && jsonResponse.data.message) {
                            errorMessage = jsonResponse.data.message;
                        }
                        }
                    }
                } catch (e) {
                    console.log('Could not parse JSON from response');
                }
                
                showNotification(errorMessage, 'error');
                // Restore button state
            if (sidebarCheckout) {
                sidebarCheckout.disabled = false;
                sidebarCheckout.textContent = 'Proceed to Checkout';
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
                        showNotification('Great! We provide services in your area.', 'success');
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
                showError('cb-sqm-error', 'Square meters cannot be negative');
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
        
        const customerNameInput = document.getElementById('cb-customer-name');
        const customerEmailInput = document.getElementById('cb-customer-email');
        const customerPhoneInput = document.getElementById('cb-customer-phone');
        const customerAddressInput = document.getElementById('cb-customer-address');
        const customerCompanyInput = document.getElementById('cb-customer-company');
        const customerCityInput = document.getElementById('cb-customer-city');
        const customerStateInput = document.getElementById('cb-customer-state');
        const notesInput = document.getElementById('cb-notes');
        
        const customerName = customerNameInput ? customerNameInput.value.trim() : '';
        const customerEmail = customerEmailInput ? customerEmailInput.value.trim() : '';
        const customerPhone = customerPhoneInput ? customerPhoneInput.value.trim() : '';
        const customerAddress = customerAddressInput ? customerAddressInput.value.trim() : '';
        
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
        
        if (!customerAddress) {
            showError('cb-address-error', 'Address is required');
            isValid = false;
        } else {
            hideError('cb-address-error');
        }
        
        // Validate payment method selection
        const selectedPayment = document.querySelector('.cb-payment-option.selected');
        if (!selectedPayment) {
            showError('cb-payment-error', 'Please select a payment method');
            isValid = false;
        } else {
            hideError('cb-payment-error');
        }
        
        // Save valid customer data
        if (isValid) {
            bookingData.customer_name = customerName;
            bookingData.customer_email = customerEmail;
            bookingData.customer_phone = customerPhone;
            bookingData.customer_company = customerCompanyInput ? customerCompanyInput.value.trim() : '';
            bookingData.address = customerAddress;
            bookingData.customer_city = customerCityInput ? customerCityInput.value.trim() : '';
            bookingData.customer_state = customerStateInput ? customerStateInput.value.trim() : '';
            bookingData.notes = notesInput ? notesInput.value.trim() : '';
            bookingData.payment_method = selectedPayment ? selectedPayment.dataset.payment : 'cash';
        }
        
        return isValid;
    }
    
    function validateBookingData() {
        const customerNameInput = document.getElementById('cb-customer-name');
        const customerEmailInput = document.getElementById('cb-customer-email');
        const customerPhoneInput = document.getElementById('cb-customer-phone');
        
        return bookingData.service_id && 
               bookingData.square_meters && 
               bookingData.booking_date && 
               bookingData.booking_time &&
               bookingData.payment_method &&
               (customerNameInput ? customerNameInput.value.trim() : '') &&
               (customerEmailInput ? customerEmailInput.value.trim() : '') &&
               (customerPhoneInput ? customerPhoneInput.value.trim() : '');
    }
    
    function collectPaymentData() {
        const customerNameInput = document.getElementById('cb-customer-name');
        const customerEmailInput = document.getElementById('cb-customer-email');
        const customerPhoneInput = document.getElementById('cb-customer-phone');
        const customerAddressInput = document.getElementById('cb-customer-address');
        const notesInput = document.getElementById('cb-notes');
        
        return {
            customer_name: customerNameInput ? customerNameInput.value.trim() : '',
            customer_email: customerEmailInput ? customerEmailInput.value.trim() : '',
            customer_phone: customerPhoneInput ? customerPhoneInput.value.trim() : '',
            address: customerAddressInput ? customerAddressInput.value.trim() : '',
            notes: notesInput ? notesInput.value.trim() : '',
            payment_method: bookingData.payment_method || 'cash'
        };
    }
    
    // Price calculation function removed - pricing disabled for now
    
    function loadAvailableSlots() {
        if (!bookingData.booking_date) {
            return;
        }
        
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
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
                if (response.success && response.data.slots) {
                    availableSlots = response.data.slots;
                    displayTimeSlots();
                } else {
                if (timeSlotsContainer) {
                    timeSlotsContainer.innerHTML = '<div class="cb-loading">' + (response.data.message || cb_frontend.strings.no_slots_available) + '</div>';
                }
            }
        })
        .catch(error => {
            console.error('Load available slots error:', error);
            if (timeSlotsContainer) {
                timeSlotsContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.loading_error + '</div>';
            }
        });
    }
    
    function displayTimeSlots() {
        const timeSlotsContainer = document.getElementById('cb-time-slots');
        if (!timeSlotsContainer) return;
        
        timeSlotsContainer.innerHTML = '';
        
        if (availableSlots.length === 0) {
            timeSlotsContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.no_slots_available + '</div>';
            return;
        }
        
        availableSlots.forEach(function(slot) {
            const slotElement = document.createElement('div');
            slotElement.className = 'cb-time-slot';
            slotElement.dataset.time = slot.time;
            slotElement.textContent = slot.time;
            
            if (!slot.available) {
                slotElement.classList.add('unavailable');
            }
            
            timeSlotsContainer.appendChild(slotElement);
        });
    }
    
    function updateBookingSummary() {
        const service = services.find(s => s.id == bookingData.service_id);
        
        if (service) {
            const summaryService = document.getElementById('cb-summary-service');
            if (summaryService) {
                summaryService.textContent = service.name;
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
                console.log('Step 3 - service_id:', bookingData.service_id, 'extras.length:', extras.length, 'bookingData.extras:', bookingData.extras);
                
                // Auto-trigger calculation when entering step 3
                if (bookingData.service_id) {
                    console.log('Step 3 - Auto-triggering price calculation');
                    
                    // Ensure extras is initialized as empty array for base service calculation
                    // Only initialize if it doesn't exist, don't reset existing selections
                    if (!bookingData.extras || !Array.isArray(bookingData.extras)) {
                        bookingData.extras = [];
                    }
                    
                    console.log('Step 3 - Current extras before calculation:', bookingData.extras);
                    calculatePrice();
                }
                
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
                
                // Set default area for the selected service
                setDefaultArea(bookingData.service_id);
                
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
        window.scrollTo({ top: 0, behavior: 'smooth' });
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
                // Date & time step - enable if date and time are selected
                const nextStepBtns4 = document.querySelectorAll('.cb-next-step');
                nextStepBtns4.forEach(btn => {
                    btn.disabled = !bookingData.booking_date || !bookingData.booking_time;
                });
                break;
            case 5:
                // Customer details step - enable if all required fields are filled AND payment method is selected
                const hasRequiredFields = bookingData.customer_name && 
                                        bookingData.customer_email && 
                                        bookingData.customer_phone;
                const hasPaymentMethod = bookingData.payment_method;
                const checkoutBtns = document.querySelectorAll('.cb-btn-checkout');
                checkoutBtns.forEach(btn => {
                    btn.disabled = !hasRequiredFields || !hasPaymentMethod;
                });
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
        
        // First test debug endpoint
        const debugFormData = new FormData();
        debugFormData.append('action', 'cb_debug');
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: debugFormData
        })
        .then(response => response.json())
        .then(response => {
                // Now try to get services
                loadServicesReal();
        })
        .catch(error => {
            console.error('Debug error:', error);
            if (servicesContainer) {
                servicesContainer.innerHTML = '<div class="cb-loading">Debug Error: ' + error.message + '</div>';
            }
        });
    }
    
    function loadServicesReal() {
        const servicesContainer = document.getElementById('cb-services-container');
        
        const formData = new FormData();
        formData.append('action', 'cb_get_services');
        
        console.log('Services fetch request - URL:', cb_frontend.ajax_url);
        console.log('Services fetch request - Action:', 'cb_get_services');
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            console.log('Services fetch response:', response);
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
                console.log('Trying REST API fallback...');
                
                // Fallback to REST API
            fetch(cb_frontend.rest_url + 'services', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(response => {
                        console.log('Services REST response:', response);
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
            
            cardElement.innerHTML = `
                    <div class="cb-service-icon">🧹</div>
                    <h4>${service.name}</h4>
                    <p>${service.description}</p>
                    <div class="cb-service-details">
                        <div class="cb-service-price">
                            <span class="cb-price-current">$${parseFloat(service.base_price).toFixed(2)}</span>
                        </div>
                        <div class="cb-service-duration">
                            <span class="cb-duration-label">Duration:</span>
                            <span class="cb-duration-value">${service.base_duration} min</span>
                        </div>
                        ${service.default_area > 0 ? `
                        <div class="cb-service-area">
                            <span class="cb-area-label">Includes:</span>
                            <span class="cb-area-value">${service.default_area} m²</span>
                        </div>
                        ` : ''}
                    </div>
            `;
            
            servicesContainer.appendChild(cardElement);
        });
    }
    
    /**
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
        
        // Show hint about additional area
        if (hintElement) {
            hintElement.style.display = 'block';
            hintElement.innerHTML = `<small>Enter additional space beyond the default ${service.default_area} m², or leave as 0 to skip</small>`;
        }
        
        // Show base area info card
        if (baseAreaInfo && baseAreaMessage && basePriceDisplay && baseDurationDisplay) {
            baseAreaInfo.style.display = 'block';
            
            // Set base area message with skip option
            baseAreaMessage.textContent = `This service includes ${service.default_area} m² at base price. Enter additional space above if needed, or skip to proceed with base service.`;
            
            // Calculate and display base pricing
            const basePrice = parseFloat(service.base_price) || 0;
            const baseDuration = parseInt(service.base_duration, 10) || 0;
            
            basePriceDisplay.textContent = `Base Price: $${basePrice.toFixed(2)}`;
            baseDurationDisplay.textContent = `Base Duration: ${baseDuration} min`;
        }
        
        // Clear the input field and set to 0 (no additional area)
        const squareMetersInput = document.getElementById('cb-square-meters');
        if (squareMetersInput) {
            squareMetersInput.value = '0';
            bookingData.square_meters = 0; // Start with 0 additional area
            
            // Trigger change event to calculate price (base + 0 additional)
            squareMetersInput.dispatchEvent(new Event('input', { bubbles: true }));
            
            console.log('CB Debug - Set smart area logic for service:', service.name, 'Default:', service.default_area, 'Additional: 0');
        }
    }
    
    function loadExtras() {
        console.log('loadExtras called - service_id:', bookingData.service_id, 'extras.length:', extras.length);
        
        if (!bookingData.service_id || extras.length > 0) {
            console.log('Skipping loadExtras - calling displayExtras directly');
            displayExtras();
            return;
        }
        
        const extrasContainer = document.getElementById('cb-extras-container');
        if (extrasContainer) {
            extrasContainer.innerHTML = '<div class="cb-loading">' + cb_frontend.strings.loading_extras + '</div>';
        }
        
        console.log('Making fetch call to load extras for service_id:', bookingData.service_id);
        
        const formData = new FormData();
        formData.append('action', 'cb_get_extras');
        formData.append('service_id', bookingData.service_id);
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            console.log('Extras fetch response:', response);
                if (response.success && response.data.extras) {
                    extras = response.data.extras;
                    console.log('Loaded extras:', extras);
                    displayExtras();
                } else {
                    console.log('No extras available or error:', response.data.message);
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
            extrasContainer.innerHTML = '<div class="cb-extras-optional">' + 
                '<p style="color: #666; font-style: italic; text-align: center; padding: 20px;">' +
                'No additional services available for this service. You can proceed to the next step.' +
                '</p></div>';
            return;
        }
        
        // Restore state after clearing
        restoreExtrasState(preservedExtras);
        
        extras.forEach(function(extra) {
            // Check if this extra is already selected - use loose comparison for type safety
            const isSelected = bookingData.extras && bookingData.extras.some(id => id == extra.id);
            const selectedClass = isSelected ? ' selected' : '';
            
            const itemElement = document.createElement('div');
            itemElement.className = `cb-extra-item${selectedClass}`;
            itemElement.dataset.extraId = extra.id;
            itemElement.dataset.extraPrice = extra.price;
            itemElement.style.cursor = 'pointer';
            
            itemElement.innerHTML = `
                    <div class="cb-extra-checkbox"></div>
                    <div class="cb-extra-info">
                        <div class="cb-extra-name">${extra.name}</div>
                        <div class="cb-extra-description">${extra.description}</div>
                        <div class="cb-extra-price">$${parseFloat(extra.price).toFixed(2)}</div>
                    </div>
            `;
            
            // Click handler is already attached via document delegation
            
            extrasContainer.appendChild(itemElement);
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
        if (preservedExtras && Array.isArray(preservedExtras)) {
            bookingData.extras = [...preservedExtras];
            console.log('Restored extras state:', bookingData.extras);
        }
    }
    
    function forceUpdateExtrasVisualState() {
        console.log('Force updating extras visual state - bookingData.extras:', bookingData.extras);
        
        // Update all extra items to reflect current state
        const extraItems = document.querySelectorAll('.cb-extra-item');
        extraItems.forEach(function(item) {
            const extraId = parseInt(item.dataset.extraId, 10);
            const isSelected = bookingData.extras && bookingData.extras.some(id => id == extraId);
            
            console.log('Updating visual state for extra ID:', extraId, 'Selected:', isSelected);
            
            if (isSelected) {
                // Only add class if not already present
                if (!item.classList.contains('selected')) {
                    item.classList.add('selected');
                }
                console.log('Applied selected class to extra ID:', extraId);
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
        
        console.log('Calculating price for service:', bookingData.service_id, 'additional sqm:', additionalArea, 'extras:', bookingData.extras);
        
        const formData = new FormData();
        formData.append('action', 'cb_calculate_price');
        formData.append('nonce', cb_frontend.nonce);
        formData.append('service_id', bookingData.service_id);
        formData.append('square_meters', additionalArea); // Send additional area only
        formData.append('use_smart_area', '1'); // Flag to indicate smart area calculation
        
        // Handle extras properly - ensure it's always an array
        if (bookingData.extras && Array.isArray(bookingData.extras) && bookingData.extras.length > 0) {
            formData.append('extras', JSON.stringify(bookingData.extras));
            console.log('Sending extras:', bookingData.extras);
        } else {
            formData.append('extras', '[]'); // Send empty array as JSON string
            console.log('No extras to send, sending empty array');
        }
        
        formData.append('zip_code', bookingData.zip_code);
        
        console.log('Sending smart price calculation request:', {
                action: 'cb_calculate_price',
                nonce: cb_frontend.nonce,
                service_id: bookingData.service_id,
                square_meters: additionalArea,
                use_smart_area: '1',
                extras: bookingData.extras,
                zip_code: bookingData.zip_code
        });
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                console.log('Raw server response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid JSON');
                }
            });
        })
        .then(response => {
                console.log('Price calculation response:', response);
                if (response.success) {
                    bookingData.pricing = response.data.pricing;
                    console.log('Price calculated:', bookingData.pricing);
                    updatePricingDisplay();
                    updateSidebarDisplay();
                } else {
                    console.error('Price calculation failed:', response.data.message);
                }
        })
        .catch(error => {
            console.error('Price calculation error:', error);
            
            // Show user-friendly error message
            showNotification('Unable to calculate price. Please try again.', 'error');
                
                // Try to extract JSON from mixed response
                try {
                if (error.message && error.message.includes('Invalid JSON')) {
                    console.error('Server returned HTML instead of JSON - likely a PHP error');
                    showNotification('Server error occurred. Please check your configuration.', 'error');
                    }
                } catch (e) {
                console.error('Could not extract error details:', e);
            }
        });
    }
    
    function updatePricingDisplay() {
        const pricing = bookingData.pricing;
        
        // Debug logging
        console.log('updatePricingDisplay called with pricing:', pricing);
        console.log('Extras price:', pricing.extras_price);
        console.log('Total price:', pricing.total_price);
        
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
        const step3ExtrasPrice = document.getElementById('cb-step3-extras-price');
        const step3TotalPrice = document.getElementById('cb-step3-total-price');
        
        if (step3ServicePrice) {
            if (pricing.smart_area && pricing.smart_area.use_smart_area) {
                // Show base + additional breakdown using backend data
                const basePrice = parseFloat(pricing.smart_area.base_price) || 0;
                const additionalPrice = parseFloat(pricing.smart_area.additional_price) || 0;
                
                step3ServicePrice.innerHTML = `
                    <div class="cb-price-breakdown">
                        <div>Base: ${formatPrice(basePrice)}</div>
                        ${additionalPrice > 0 ? `<div>Extra: ${formatPrice(additionalPrice)}</div>` : ''}
                        ${pricing.zip_surcharge > 0 ? `<div>Zip Fee: ${formatPrice(pricing.zip_surcharge)}</div>` : ''}
                    </div>
                `;
            } else {
                step3ServicePrice.textContent = formatPrice(pricing.service_price);
            }
        }
        
        // Update extras price separately
        if (step3ExtrasPrice) {
            if (pricing.extras_price > 0) {
                step3ExtrasPrice.textContent = formatPrice(pricing.extras_price);
            } else {
                step3ExtrasPrice.textContent = '$0.00';
            }
        }
        if (step3TotalPrice) step3TotalPrice.textContent = formatPrice(pricing.total_price);
        
        // Update Step 3 duration display with smart area breakdown
        const step3ServiceDuration = document.getElementById('cb-step3-service-duration');
        const step3ExtrasDuration = document.getElementById('cb-step3-extras-duration');
        const step3TotalDuration = document.getElementById('cb-step3-total-duration');
        
        if (step3ServiceDuration) {
            if (pricing.smart_area && pricing.smart_area.use_smart_area) {
                // Show base + additional breakdown using backend data
                const baseDuration = parseInt(pricing.smart_area.base_duration, 10) || 0;
                const additionalDuration = parseInt(pricing.smart_area.additional_duration, 10) || 0;
                
                step3ServiceDuration.innerHTML = `
                    <div class="cb-duration-breakdown">
                        <div>Base: ${formatDuration(baseDuration)}</div>
                        ${additionalDuration > 0 ? `<div>Extra: ${formatDuration(additionalDuration)}</div>` : ''}
                    </div>
                `;
            } else {
                step3ServiceDuration.textContent = formatDuration(pricing.service_duration);
            }
        }
        
        if (step3ExtrasDuration) step3ExtrasDuration.textContent = formatDuration(pricing.extras_duration);
        if (step3TotalDuration) step3TotalDuration.textContent = formatDuration(pricing.total_duration);
        
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
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `cb-notification cb-notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#F44336' : '#2196F3'};
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
    
    // Initialize payment method selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-payment-option')) {
            const clickedOption = e.target.closest('.cb-payment-option');
            
            // Remove previous selection
            const paymentOptions = document.querySelectorAll('.cb-payment-option');
            paymentOptions.forEach(option => option.classList.remove('selected'));
            
            // Add selection to clicked option
            clickedOption.classList.add('selected');
            
            const paymentMethod = clickedOption.dataset.payment;
        bookingData.payment_method = paymentMethod;
        
        console.log('Payment method selected:', paymentMethod);
        
        // Update button states
        updateButtonStates();
        }
    });
    
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
    
    // Add database check function for debugging
    window.checkDatabaseStatus = function() {
        const formData = new FormData();
        formData.append('action', 'cb_check_database');
        
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            console.log('Database Status:', response);
            if (response.success) {
                const data = response.data;
                console.log('Services table exists:', data.services_table_exists);
                console.log('Extras table exists:', data.extras_table_exists);
                console.log('Services count:', data.services_count);
                console.log('Extras count:', data.extras_count);
                console.log('Pricing class exists:', data.pricing_class_exists);
            }
        })
        .catch(error => {
            console.error('Database check failed:', error);
        });
    };
    
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
        
        if (keysToRemove.length > 0) {
            console.log('CB Debug - Cleared cached form data:', keysToRemove);
        }
    }
});