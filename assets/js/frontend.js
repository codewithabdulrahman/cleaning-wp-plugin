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
                console.log('Slot held successfully:', currentSlotSessionId);
                
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
        
        // Calculate price and load extras
        checkAndCalculatePrice();
        loadExtras();
        
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
                    
                    console.log('Language switched to:', targetLanguage);
                    console.log('Current language is now:', cb_frontend.current_language);
                    
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
        console.log('Current language:', cb_frontend.current_language);
        
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
        
        // Update step headers - dynamic text with translation fallback
        const step1Header = document.querySelector('.cb-step-1 .cb-step-header h2');
        if (step1Header) {
            step1Header.textContent = cb_frontend.dynamic_texts?.cb_step1_title || translations['Enter Your ZIP Code'] || step1Header.textContent;
        }
        const step1Desc = document.querySelector('.cb-step-1 .cb-step-header p');
        if (step1Desc) {
            step1Desc.textContent = cb_frontend.dynamic_texts?.cb_step1_description || translations['Let us know your location to check availability'] || step1Desc.textContent;
        }
        const step2Header = document.querySelector('.cb-step-2 .cb-step-header h2');
        if (step2Header) {
            step2Header.textContent = cb_frontend.dynamic_texts?.cb_step2_title || translations['Select Your Service'] || step2Header.textContent;
        }
        const step2Desc = document.querySelector('.cb-step-2 .cb-step-header p');
        if (step2Desc) {
            step2Desc.textContent = cb_frontend.dynamic_texts?.cb_step2_description || translations['Choose the type of cleaning service you need'] || step2Desc.textContent;
        }
        
        // Update step 3 and 4 headers - dynamic text with translation fallback
        const step3Header = document.querySelector('.cb-step-3 .cb-step-header h2');
        if (step3Header) {
            step3Header.textContent = cb_frontend.dynamic_texts?.cb_step3_title || translations['Service Details'] || step3Header.textContent;
        }
        const step3Desc = document.querySelector('.cb-step-3 .cb-step-header p');
        if (step3Desc) {
            step3Desc.textContent = cb_frontend.dynamic_texts?.cb_step3_description || translations['Tell us about your space and any additional services'] || step3Desc.textContent;
        }
        const step4Header = document.querySelector('.cb-step-4 .cb-step-header h2');
        if (step4Header) {
            step4Header.textContent = cb_frontend.dynamic_texts?.cb_step4_title || translations['Select Date & Time'] || step4Header.textContent;
        }
        const step4Desc = document.querySelector('.cb-step-4 .cb-step-header p');
        if (step4Desc) {
            step4Desc.textContent = cb_frontend.dynamic_texts?.cb_step4_description || translations['Choose your preferred date and time slot'] || step4Desc.textContent;
        }
        
        // Update form labels - dynamic text with translation fallback
        const zipLabel = document.querySelector('label[for="cb-zip-code"]');
        if (zipLabel) {
            zipLabel.textContent = cb_frontend.dynamic_texts?.cb_zip_code_label || translations['ZIP Code'] || zipLabel.textContent;
        }
        const spaceLabel = document.querySelector('label[for="cb-square-meters"]');
        if (spaceLabel) {
            spaceLabel.textContent = cb_frontend.dynamic_texts?.cb_space_label || translations['Space (m²)'] || spaceLabel.textContent;
        }
        const extrasLabel = document.querySelector('.cb-step-3 label');
        if (extrasLabel) {
            extrasLabel.textContent = cb_frontend.dynamic_texts?.cb_additional_services_label || translations['Additional Services'] || extrasLabel.textContent;
        }
        const dateLabel = document.querySelector('label[for="cb-booking-date"]');
        if (dateLabel) {
            dateLabel.textContent = cb_frontend.dynamic_texts?.cb_date_label || translations['Date'] || dateLabel.textContent;
        }
        const timeLabel = document.querySelector('.cb-step-4 .cb-form-group:last-child label');
        if (timeLabel) {
            timeLabel.textContent = cb_frontend.dynamic_texts?.cb_time_slots_label || translations['Available Time Slots'] || timeLabel.textContent;
        }
        
        // Update info banner
        if (translations['Our contractors have all the necessary cleaning products and equipment']) {
            const infoBanner = document.querySelector('.cb-info-text');
            if (infoBanner) infoBanner.textContent = translations['Our contractors have all the necessary cleaning products and equipment'];
        }
        
        // Update buttons - dynamic text with translation fallback
        const checkBtn = document.querySelector('.cb-next-step[data-next="2"]');
        if (checkBtn) {
            checkBtn.textContent = cb_frontend.dynamic_texts?.cb_continue_button_text || translations['Check Availability'] || checkBtn.textContent;
        }
        const backBtns = document.querySelectorAll('.cb-prev-step');
        backBtns.forEach(btn => {
            btn.textContent = cb_frontend.dynamic_texts?.cb_back_button_text || translations['Back'] || btn.textContent;
        });
        const continueBtns = document.querySelectorAll('.cb-next-step');
        continueBtns.forEach(btn => {
            btn.textContent = cb_frontend.dynamic_texts?.cb_continue_button_text || translations['Continue'] || btn.textContent;
        });
        const checkoutBtn = document.querySelector('#cb-sidebar-checkout');
        if (checkoutBtn) {
            const priceSpan = checkoutBtn.querySelector('#cb-checkout-price');
            const price = priceSpan ? priceSpan.textContent : '€0.00';
            const checkoutText = cb_frontend.dynamic_texts?.cb_checkout_button_text || translations['Proceed to Checkout'] || 'Proceed to Checkout';
            checkoutBtn.innerHTML = `<span id="cb-checkout-price">${price}</span> - ${checkoutText}`;
        }
        
        // Update sidebar content - dynamic text with translation fallback
        const sidebarTitle = document.querySelector('#cb-sidebar-service-title');
        if (sidebarTitle) {
            sidebarTitle.textContent = cb_frontend.dynamic_texts?.cb_sidebar_service_title || translations['Select a service to see pricing'] || sidebarTitle.textContent;
        }
        const infoBanner = document.querySelector('.cb-info-text');
        if (infoBanner) {
            infoBanner.textContent = cb_frontend.dynamic_texts?.cb_sidebar_info_text || translations['Our contractors have all the necessary cleaning products and equipment'] || infoBanner.textContent;
        }
        const durationLabel = document.querySelector('.cb-duration-label');
        if (durationLabel) {
            durationLabel.textContent = cb_frontend.dynamic_texts?.cb_working_time_label || translations['Approximate working time'] || durationLabel.textContent;
        }
        const selectedLabel = document.querySelector('.cb-selected-extras-label');
        if (selectedLabel) {
            selectedLabel.textContent = cb_frontend.dynamic_texts?.cb_selected_services_label || translations['Selected Services:'] || selectedLabel.textContent;
        }
        const pricingLabel = document.querySelector('.cb-price-label');
        if (pricingLabel) {
            pricingLabel.textContent = cb_frontend.dynamic_texts?.cb_pricing_label || translations['Pricing:'] || pricingLabel.textContent;
        }
        
        // Update placeholders and pricing labels - dynamic text with translation fallback
        const spacePlaceholder = document.querySelector('#cb-square-meters + small');
        if (spacePlaceholder) {
            spacePlaceholder.textContent = cb_frontend.dynamic_texts?.cb_space_hint_text || translations['Enter additional space beyond the default area, or leave as 0 to skip'] || spacePlaceholder.textContent;
        }
        const servicePriceLabel = document.querySelector('.cb-step-3 .cb-price-item:first-child span:first-child');
        if (servicePriceLabel) {
            servicePriceLabel.textContent = cb_frontend.dynamic_texts?.cb_service_price_text || translations['Service Price:'] || servicePriceLabel.textContent;
        }
        const extrasPriceLabel = document.querySelector('.cb-step-3 .cb-price-item:nth-child(2) span:first-child');
        if (extrasPriceLabel) {
            extrasPriceLabel.textContent = cb_frontend.dynamic_texts?.cb_extras_text || translations['Extras:'] || extrasPriceLabel.textContent;
        }
        const totalPriceLabel = document.querySelector('.cb-step-3 .cb-price-total span:first-child');
        if (totalPriceLabel) {
            totalPriceLabel.textContent = cb_frontend.dynamic_texts?.cb_total_text || translations['Total:'] || totalPriceLabel.textContent;
        }
        const serviceDurationLabel = document.querySelector('.cb-step-3 .cb-duration-item:first-child span:first-child');
        if (serviceDurationLabel) {
            serviceDurationLabel.textContent = cb_frontend.dynamic_texts?.cb_service_duration_text || translations['Service Duration:'] || serviceDurationLabel.textContent;
        }
        const extrasDurationLabel = document.querySelector('.cb-step-3 .cb-duration-item:nth-child(2) span:first-child');
        if (extrasDurationLabel) {
            extrasDurationLabel.textContent = cb_frontend.dynamic_texts?.cb_extras_duration_text || translations['Extras Duration:'] || extrasDurationLabel.textContent;
        }
        const totalDurationLabel = document.querySelector('.cb-step-3 .cb-duration-total span:first-child');
        if (totalDurationLabel) {
            totalDurationLabel.textContent = cb_frontend.dynamic_texts?.cb_total_duration_text || translations['Total Duration:'] || totalDurationLabel.textContent;
        }
        
        // Apply typography settings
        applyTypographySettings();
    }
    
    function applyTypographySettings() {
        if (!cb_frontend.typography) return;
        
        // Apply widget title typography
        const widgetTitle = document.querySelector('.cb-title');
        if (widgetTitle && cb_frontend.typography.cb_title_font_size) {
            widgetTitle.style.fontSize = cb_frontend.typography.cb_title_font_size;
        }
        if (widgetTitle && cb_frontend.typography.cb_title_font_color) {
            widgetTitle.style.color = cb_frontend.typography.cb_title_font_color;
        }
        
        // Apply step titles typography
        const stepTitles = document.querySelectorAll('.cb-step-header h2');
        stepTitles.forEach(title => {
            if (cb_frontend.typography.cb_step_title_font_size) {
                title.style.fontSize = cb_frontend.typography.cb_step_title_font_size;
            }
            if (cb_frontend.typography.cb_step_title_font_color) {
                title.style.color = cb_frontend.typography.cb_step_title_font_color;
            }
        });
        
        // Apply button typography
        const buttons = document.querySelectorAll('.cb-next-step, .cb-prev-step, #cb-sidebar-checkout');
        buttons.forEach(button => {
            if (cb_frontend.typography.cb_button_font_size) {
                button.style.fontSize = cb_frontend.typography.cb_button_font_size;
            }
            if (cb_frontend.typography.cb_button_font_color) {
                button.style.color = cb_frontend.typography.cb_button_font_color;
            }
        });
        
        // Apply label typography
        const labels = document.querySelectorAll('label');
        labels.forEach(label => {
            if (cb_frontend.typography.cb_label_font_size) {
                label.style.fontSize = cb_frontend.typography.cb_label_font_size;
            }
            if (cb_frontend.typography.cb_label_font_color) {
                label.style.color = cb_frontend.typography.cb_label_font_color;
            }
        });
        
        // Apply description typography
        const descriptions = document.querySelectorAll('.cb-step-header p, .description, small');
        descriptions.forEach(desc => {
            if (cb_frontend.typography.cb_description_font_size) {
                desc.style.fontSize = cb_frontend.typography.cb_description_font_size;
            }
            if (cb_frontend.typography.cb_description_font_color) {
                desc.style.color = cb_frontend.typography.cb_description_font_color;
            }
        });
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
    
        // Add booking data to store in session
        const sessionData = {
            zip_code: bookingData.zip_code,
            service_id: bookingData.service_id,
            square_meters: bookingData.square_meters,
            extras: bookingData.extras || [],
            booking_date: bookingData.booking_date,
            booking_time: bookingData.booking_time,
            pricing: bookingData.pricing || {}
        };
    
        formData.append('booking_data', JSON.stringify(sessionData));
    
        console.log('Storing booking data in session:', sessionData);
    
        // Store booking data in session
        fetch(cb_frontend.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            console.log('Session storage response:', response);
    
            if (response.success) {
                // Release the held slot since we're moving to checkout
                if (currentSlotSessionId) {
                    releaseSlot();
                }
    
                // Show success message
                showSuccessMessage('Redirecting to checkout...');
    
                // Redirect to WooCommerce checkout with session reference
                if (response.data.checkout_url) {
                    console.log('Redirecting to checkout URL:', response.data.checkout_url);
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
        
        // Apply custom border colors to newly created time slots
        if (cb_frontend.colors) {
            applyCustomBorderColors();
        }
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
                console.log('Step 3 - service_id:', bookingData.service_id);
                
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
                    console.log('Step 3 - calling displayExtras');
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
            const hintText = cb_frontend.translations['Enter additional space beyond the default area, or leave as 0 to skip'] || 'Εισάγετε επιπλέον χώρο πέρα από την προεπιλεγμένη περιοχή, ή αφήστε 0 για παράλειψη';
            hintElement.innerHTML = `<small>${hintText.replace('default area', `default ${service.default_area} m²`)}</small>`;
        }
        
        // Show base area info card
        if (baseAreaInfo && baseAreaMessage && basePriceDisplay && baseDurationDisplay) {
            baseAreaInfo.style.display = 'block';
            
            // Set base area message with skip option
            const baseMessage = cb_frontend.translations['This service includes 50 m² at base price. Enter additional space above if needed, or skip to proceed with base service.'] || 'Αυτή η υπηρεσία περιλαμβάνει 50 m² σε βασική τιμή. Εισάγετε επιπλέον χώρο παραπάνω αν χρειάζεται, ή παραλείψτε για να προχωρήσετε με τη βασική υπηρεσία.';
            baseAreaMessage.textContent = baseMessage.replace('50 m²', `${service.default_area} m²`);
            
            // Calculate and display base pricing
            const basePrice = parseFloat(service.base_price) || 0;
            const baseDuration = parseInt(service.base_duration, 10) || 0;
            
            const basePriceText = cb_frontend.translations['Base Price: $80.00'] || 'Βασική Τιμή: €80.00';
            const baseDurationText = cb_frontend.translations['Base Duration: 20 min'] || 'Βασική Διάρκεια: 20 λεπτά';
            
            basePriceDisplay.textContent = basePriceText.replace('€80.00', `€${basePrice.toFixed(2)}`);
            baseDurationDisplay.textContent = baseDurationText.replace('20 λεπτά', `${baseDuration} λεπτά`);
        }
        
        // Clear the input field and set to 0 (no additional area)
        const squareMetersInput = document.getElementById('cb-square-meters');
        if (squareMetersInput) {
            squareMetersInput.value = '0';
            bookingData.square_meters = 0; // Start with 0 additional area
            
            // Trigger change event to calculate price (base + 0 additional)
            squareMetersInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
    
    function loadExtras() {
        
        if (!bookingData.service_id || extras.length > 0) {
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
                        <div class="cb-extra-price">€${parseFloat(extra.price).toFixed(2)}</div>
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
                step3ExtrasPrice.textContent = '€0.00';
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
    
    function checkAndCalculatePrice() {
        if (bookingData.service_id && bookingData.square_meters >= 0) {
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
    }
});