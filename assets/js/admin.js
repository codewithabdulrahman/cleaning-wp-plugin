/**
 * Admin JavaScript for Cleaning Booking plugin (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Services form handling
    const serviceForm = document.getElementById('cb-service-form');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
            const form = e.target;
            const submitBtn = form.querySelector('input[type="submit"]');
            const originalText = submitBtn.value;
            
            submitBtn.value = cb_admin.strings.saving;
            submitBtn.disabled = true;
            
            const formData = new FormData(form);
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    form.reset();
                    const serviceIdInput = document.getElementById('service-id');
                    if (serviceIdInput) serviceIdInput.value = '';
                    const cancelEditBtn = document.getElementById('cb-cancel-edit');
                    if (cancelEditBtn) cancelEditBtn.style.display = 'none';
                    loadServices();
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('Service form error:', error);
                showNotice(cb_admin.strings.error, 'error');
            })
            .finally(() => {
                submitBtn.value = originalText;
                submitBtn.disabled = false;
        });
    });
    }
    
    // Edit service
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-edit-service')) {
            const editBtn = e.target.closest('.cb-edit-service');
            const service = JSON.parse(editBtn.dataset.service);
            
            const serviceIdInput = document.getElementById('service-id');
            const serviceNameInput = document.getElementById('service-name');
            const serviceDescriptionInput = document.getElementById('service-description');
            const serviceBasePriceInput = document.getElementById('service-base-price');
            const serviceBaseDurationInput = document.getElementById('service-base-duration');
            const serviceSqmMultiplierInput = document.getElementById('service-sqm-multiplier');
            const serviceSqmDurationMultiplierInput = document.getElementById('service-sqm-duration-multiplier');
            const serviceDefaultAreaInput = document.getElementById('service-default-area');
            const serviceSortOrderInput = document.getElementById('service-sort-order');
            const serviceIsActiveInput = document.getElementById('service-is-active');
            
            if (serviceIdInput) serviceIdInput.value = service.id;
            if (serviceNameInput) serviceNameInput.value = service.name;
            if (serviceDescriptionInput) serviceDescriptionInput.value = service.description;
            if (serviceBasePriceInput) serviceBasePriceInput.value = service.base_price;
            if (serviceBaseDurationInput) serviceBaseDurationInput.value = service.base_duration;
            if (serviceSqmMultiplierInput) serviceSqmMultiplierInput.value = service.sqm_multiplier;
            if (serviceSqmDurationMultiplierInput) serviceSqmDurationMultiplierInput.value = service.sqm_duration_multiplier;
            if (serviceDefaultAreaInput) serviceDefaultAreaInput.value = service.default_area || 0;
            if (serviceSortOrderInput) serviceSortOrderInput.value = service.sort_order;
            if (serviceIsActiveInput) serviceIsActiveInput.checked = service.is_active == 1;
            
            const cancelEditBtn = document.getElementById('cb-cancel-edit');
            if (cancelEditBtn) cancelEditBtn.style.display = 'block';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
    
    // Cancel edit service
    const cancelEditBtn = document.getElementById('cb-cancel-edit');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            const serviceForm = document.getElementById('cb-service-form');
            if (serviceForm) serviceForm.reset();
            const serviceIdInput = document.getElementById('service-id');
            if (serviceIdInput) serviceIdInput.value = '';
            this.style.display = 'none';
        });
    }
    
    // Delete service
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-delete-service')) {
            const deleteBtn = e.target.closest('.cb-delete-service');
            
        if (!confirm(cb_admin.strings.confirm_delete)) {
            return;
        }
        
            const serviceId = deleteBtn.dataset.id;
            const row = deleteBtn.closest('tr');
            
            const formData = new FormData();
            formData.append('action', 'cb_delete_service');
            formData.append('nonce', cb_admin.nonce);
            formData.append('id', serviceId);
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => {
                            if (row.parentNode) {
                                row.remove();
                            }
                        }, 300);
                    }
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('Delete service error:', error);
                showNotice(cb_admin.strings.error, 'error');
            });
            }
    });
    
    
    // ZIP Codes form handling
    const zipCodeForm = document.getElementById('cb-zip-code-form');
    if (zipCodeForm) {
        zipCodeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
            const form = e.target;
            const submitBtn = form.querySelector('input[type="submit"]');
            const originalText = submitBtn.value;
            
            submitBtn.value = cb_admin.strings.saving;
            submitBtn.disabled = true;
            
            const formData = new FormData(form);
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    form.reset();
                    const zipCodeIdInput = document.getElementById('zip-code-id');
                    if (zipCodeIdInput) zipCodeIdInput.value = '';
                    const cancelEditZipBtn = document.getElementById('cb-cancel-edit-zip');
                    if (cancelEditZipBtn) cancelEditZipBtn.style.display = 'none';
                    loadZipCodes();
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('ZIP code form error:', error);
                showNotice(cb_admin.strings.error, 'error');
            })
            .finally(() => {
                submitBtn.value = originalText;
                submitBtn.disabled = false;
        });
    });
    }
    
    // Edit ZIP code
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-edit-zip-code')) {
            const editBtn = e.target.closest('.cb-edit-zip-code');
            const zip = JSON.parse(editBtn.dataset.zip);
            
            const zipCodeIdInput = document.getElementById('zip-code-id');
            const zipCodeInput = document.getElementById('zip-code');
            const zipCityInput = document.getElementById('zip-city');
            const zipSurchargeInput = document.getElementById('zip-surcharge');
            const zipIsActiveInput = document.getElementById('zip-is-active');
            
            if (zipCodeIdInput) zipCodeIdInput.value = zip.id;
            if (zipCodeInput) zipCodeInput.value = zip.zip_code;
            if (zipCityInput) zipCityInput.value = zip.city;
            if (zipSurchargeInput) zipSurchargeInput.value = zip.surcharge;
            if (zipIsActiveInput) zipIsActiveInput.checked = zip.is_active == 1;
            
            const cancelEditZipBtn = document.getElementById('cb-cancel-edit-zip');
            if (cancelEditZipBtn) cancelEditZipBtn.style.display = 'block';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
    
    // Cancel edit ZIP code
    const cancelEditZipBtn = document.getElementById('cb-cancel-edit-zip');
    if (cancelEditZipBtn) {
        cancelEditZipBtn.addEventListener('click', function() {
            const zipCodeForm = document.getElementById('cb-zip-code-form');
            if (zipCodeForm) zipCodeForm.reset();
            const zipCodeIdInput = document.getElementById('zip-code-id');
            if (zipCodeIdInput) zipCodeIdInput.value = '';
            this.style.display = 'none';
        });
    }
    
    // Delete ZIP code
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-delete-zip-code')) {
            const deleteBtn = e.target.closest('.cb-delete-zip-code');
            
        if (!confirm(cb_admin.strings.confirm_delete)) {
            return;
        }
        
            const zipId = deleteBtn.dataset.id;
            const row = deleteBtn.closest('tr');
            
            const formData = new FormData();
            formData.append('action', 'cb_delete_zip_code');
            formData.append('nonce', cb_admin.nonce);
            formData.append('id', zipId);
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => {
                            if (row.parentNode) {
                                row.remove();
                            }
                        }, 300);
                    }
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('Delete ZIP code error:', error);
                showNotice(cb_admin.strings.error, 'error');
            });
            }
    });
    
    // Helper functions
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = document.createElement('div');
        notice.className = `notice ${noticeClass} is-dismissible`;
        notice.innerHTML = `<p>${message}</p>`;
        
        const wrapH1 = document.querySelector('.wrap h1');
        if (wrapH1 && wrapH1.parentNode) {
            wrapH1.parentNode.insertBefore(notice, wrapH1.nextSibling);
        }
        
        setTimeout(function() {
            notice.style.opacity = '0';
            notice.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                if (notice.parentNode) {
                    notice.remove();
                }
            }, 300);
        }, 3000);
    }
    
    function loadServices() {
        // Reload the page to refresh the services list
        location.reload();
    }
    
    function loadZipCodes() {
        // Reload the page to refresh the ZIP codes list
        location.reload();
    }
    
    // Service extras management
    let currentServiceId = null;
    let currentServiceName = null;
    
    // Manage extras button from services page
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-manage-extras')) {
            const manageBtn = e.target.closest('.cb-manage-extras');
            currentServiceId = manageBtn.dataset.serviceId;
            currentServiceName = manageBtn.dataset.serviceName;
        
        // Hide services section
            const adminSections = document.querySelectorAll('.cb-admin-section');
            adminSections.forEach(section => {
                if (section.id !== 'cb-service-extras-section') {
                    section.style.display = 'none';
                }
            });
        
        // Show extras section
            const extrasSection = document.getElementById('cb-service-extras-section');
            if (extrasSection) extrasSection.style.display = 'block';
            
            const selectedServiceName = document.getElementById('selected-service-name');
            if (selectedServiceName) selectedServiceName.textContent = currentServiceName;
        
        // Load service extras
        loadServiceExtras(currentServiceId);
        }
    });
    
    // Back to services button
    const backToServicesBtn = document.getElementById('cb-back-to-services');
    if (backToServicesBtn) {
        backToServicesBtn.addEventListener('click', function() {
            const extrasSection = document.getElementById('cb-service-extras-section');
            if (extrasSection) extrasSection.style.display = 'none';
            
            const adminSections = document.querySelectorAll('.cb-admin-section');
            adminSections.forEach(section => {
                if (section.id !== 'cb-service-extras-section') {
                    section.style.display = 'block';
                }
            });
            
        currentServiceId = null;
        currentServiceName = null;
    });
    }
    
    
    // Remove service extra
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-remove-service-extra')) {
            const removeBtn = e.target.closest('.cb-remove-service-extra');
            
        if (!confirm('Are you sure you want to remove this extra from the service?')) {
            return;
        }
        
            const extraId = removeBtn.dataset.extraId;
            const row = removeBtn.closest('tr');
            
            const formData = new FormData();
            formData.append('action', 'cb_remove_service_extra');
            formData.append('nonce', cb_admin.nonce);
            formData.append('service_id', currentServiceId);
            formData.append('extra_id', extraId);
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => {
                            if (row.parentNode) {
                                row.remove();
                            }
                        }, 300);
                    }
                    loadAvailableExtras(currentServiceId);
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('Remove service extra error:', error);
                showNotice(cb_admin.strings.error, 'error');
            });
            }
        });
    
    // Show create extra form
    const showCreateFormBtn = document.getElementById('cb-show-create-form');
    if (showCreateFormBtn) {
        showCreateFormBtn.addEventListener('click', function() {
            this.style.display = 'none';
            const createForm = document.getElementById('cb-create-extra-form');
            if (createForm) createForm.style.display = 'block';
            const cancelCreateBtn = document.getElementById('cb-cancel-create');
            if (cancelCreateBtn) cancelCreateBtn.style.display = 'block';
        });
    }
    
    // Hide create extra form
    const cancelCreateBtn = document.getElementById('cb-cancel-create');
    if (cancelCreateBtn) {
        cancelCreateBtn.addEventListener('click', function() {
            const createForm = document.getElementById('cb-create-extra-form');
            if (createForm) createForm.style.display = 'none';
            const showCreateFormBtn = document.getElementById('cb-show-create-form');
            if (showCreateFormBtn) showCreateFormBtn.style.display = 'block';
            this.style.display = 'none';
            const createFormReset = document.getElementById('cb-create-extra-form');
            if (createFormReset) createFormReset.reset();
            const createExtraIdInput = document.getElementById('create-extra-id');
            if (createExtraIdInput) createExtraIdInput.value = '';
        });
    }
    
    // Create extra form submission
    const createExtraForm = document.getElementById('cb-create-extra-form');
    if (createExtraForm) {
        createExtraForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
            const form = e.target;
            const submitBtn = form.querySelector('input[type="submit"]');
            const originalText = submitBtn.value;
            
            submitBtn.value = cb_admin.strings.saving;
            submitBtn.disabled = true;
            
            const formData = new FormData(form);
            formData.append('service_id', currentServiceId);
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    form.reset();
                    const createExtraIdInput = document.getElementById('create-extra-id');
                    if (createExtraIdInput) createExtraIdInput.value = '';
                    
                    const cancelCreateBtn = document.getElementById('cb-cancel-create');
                    if (cancelCreateBtn) cancelCreateBtn.click(); // Hide form and show button
                    
                    // Reload service extras to show the new extra
                    setTimeout(function() {
                        loadServiceExtras(currentServiceId);
                    }, 500);
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('Create extra form error:', error);
                showNotice(cb_admin.strings.error, 'error');
            })
            .finally(() => {
                submitBtn.value = originalText;
                submitBtn.disabled = false;
        });
    });
    }
    
    // Edit extra inline
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-edit-extra-inline')) {
            const editBtn = e.target.closest('.cb-edit-extra-inline');
            const extra = JSON.parse(editBtn.dataset.extra);
            
            const createExtraIdInput = document.getElementById('create-extra-id');
            const createExtraNameInput = document.getElementById('create-extra-name');
            const createExtraDescriptionInput = document.getElementById('create-extra-description');
            const createExtraPriceInput = document.getElementById('create-extra-price');
            const createExtraDurationInput = document.getElementById('create-extra-duration');
            const createExtraIsActiveInput = document.getElementById('create-extra-is-active');
            
            if (createExtraIdInput) createExtraIdInput.value = extra.id;
            if (createExtraNameInput) createExtraNameInput.value = extra.name;
            if (createExtraDescriptionInput) createExtraDescriptionInput.value = extra.description;
            if (createExtraPriceInput) createExtraPriceInput.value = extra.price;
            if (createExtraDurationInput) createExtraDurationInput.value = extra.duration;
            if (createExtraIsActiveInput) createExtraIsActiveInput.checked = parseInt(extra.is_active) === 1;
            
            const showCreateFormBtn = document.getElementById('cb-show-create-form');
            if (showCreateFormBtn) showCreateFormBtn.style.display = 'none';
            const createForm = document.getElementById('cb-create-extra-form');
            if (createForm) createForm.style.display = 'block';
            const cancelCreateBtn = document.getElementById('cb-cancel-create');
            if (cancelCreateBtn) cancelCreateBtn.style.display = 'block';
            
            const createExtraSection = document.getElementById('cb-create-extra-section');
            if (createExtraSection) {
                const offsetTop = createExtraSection.offsetTop - 100;
                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
            }
        }
    });
    
    // Toggle extra status (active/inactive)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('cb-toggle-extra-status')) {
            const checkbox = e.target;
            const extraId = checkbox.dataset.extraId;
            const isActive = checkbox.checked ? 1 : 0;
            const statusText = checkbox.parentNode.querySelector('.cb-status-text');
            
            const formData = new FormData();
            formData.append('action', 'cb_update_extra_status');
            formData.append('nonce', cb_admin.nonce);
            formData.append('extra_id', extraId);
            formData.append('is_active', isActive);
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    if (isActive) {
                        statusText.classList.remove('cb-status-inactive');
                        statusText.classList.add('cb-status-active');
                        statusText.textContent = 'Active';
                    } else {
                        statusText.classList.remove('cb-status-active');
                        statusText.classList.add('cb-status-inactive');
                        statusText.textContent = 'Inactive';
                    }
                } else {
                    // Revert checkbox if error
                    checkbox.checked = !isActive;
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('Toggle extra status error:', error);
                // Revert checkbox if error
                checkbox.checked = !isActive;
                showNotice(cb_admin.strings.error, 'error');
        });
        }
    });
    
    function loadServiceExtras(serviceId) {
        const formData = new FormData();
        formData.append('action', 'cb_get_service_extras');
        formData.append('nonce', cb_admin.nonce);
        formData.append('service_id', serviceId);
        
        fetch(cb_admin.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(response => {
                if (response.success) {
                    displayServiceExtras(response.data.extras);
                }
        })
        .catch(error => {
            console.error('Load service extras error:', error);
        });
    }
    
    function displayServiceExtras(extras) {
        const tbody = document.getElementById('service-extras-tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (extras.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No extras assigned to this service</td></tr>';
            return;
        }
        
        extras.forEach(function(extra) {
            const row = document.createElement('tr');
            row.dataset.extraId = extra.id;
            row.innerHTML = 
                '<td><strong>' + extra.name + '</strong>' +
                (extra.description ? '<br><small>' + extra.description + '</small>' : '') + '</td>' +
                '<td>€' + parseFloat(extra.price).toFixed(2) + '</td>' +
                '<td>' + extra.duration + ' min</td>' +
                '<td>' +
                    '<label style="display: inline-flex; align-items: center;">' +
                        '<input type="checkbox" class="cb-toggle-extra-status" data-extra-id="' + extra.id + '" ' + (parseInt(extra.is_active) === 1 ? 'checked' : '') + ' style="margin-right: 5px;">' +
                        '<span class="cb-status-text cb-status-' + (parseInt(extra.is_active) === 1 ? 'active' : 'inactive') + '">' +
                            (parseInt(extra.is_active) === 1 ? 'Active' : 'Inactive') + 
                        '</span>' +
                    '</label>' +
                '</td>' +
                '<td>' +
                    '<button type="button" class="button button-small cb-edit-extra-inline" data-extra=\'' + JSON.stringify(extra) + '\'>Edit</button>' +
                    '<button type="button" class="button button-small cb-remove-service-extra" data-extra-id="' + extra.id + '" style="margin-left: 5px;">Remove</button>' +
                '</td>';
            tbody.appendChild(row);
        });
    }
    
    // Booking management functionality
    
    // Create/Edit Booking Form
    const createBookingForm = document.getElementById('cb-create-booking-form');
    if (createBookingForm) {
        createBookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('input[type="submit"]');
            const originalText = submitBtn.value;
            const bookingId = document.getElementById('create-booking-id').value;
            const isEdit = bookingId && bookingId !== '';
            
            submitBtn.value = isEdit ? 'Updating...' : 'Converting...';
            submitBtn.disabled = true;
            
            fetch(cb_admin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    if (isEdit) {
                        // Hide cancel button and reset form for new booking
                        const cancelBookingEditBtn = document.getElementById('cb-cancel-booking-edit');
                        if (cancelBookingEditBtn) cancelBookingEditBtn.style.display = 'none';
                        form.reset();
                        const createBookingDateInput = document.getElementById('create-booking-date');
                        if (createBookingDateInput) createBookingDateInput.valueAsDate = new Date();
                        
                        // Reload to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        // Reset form for new booking
                        form.reset();
                        const createBookingDateInput = document.getElementById('create-booking-date');
                        if (createBookingDateInput) createBookingDateInput.valueAsDate = new Date();
                        
                        // Reload to show new booking
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showNotice(response.data.message || 'Error saving booking', 'error');
                }
            })
            .catch(error => {
                console.error('Create booking form error:', error);
                showNotice('Error saving booking', 'error');
            })
            .finally(() => {
                submitBtn.value = isEdit ? 'Update Booking' : 'Save Booking';
                submitBtn.disabled = false;
        });
    });
    }
    
    // Set default datetime to current date/time
    const createBookingDatetimeInput = document.getElementById('create-booking-datetime');
    if (createBookingDatetimeInput) {
        const now = new Date();
        // Format for datetime-local input (YYYY-MM-DDTHH:MM)
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const year = now.getFullYear();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        const datetimeValue = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        createBookingDatetimeInput.value = datetimeValue;
    }
    
    // Edit booking inline (fill upper form like services)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-edit-booking-inline')) {
            const editBtn = e.target.closest('.cb-edit-booking-inline');
            const booking = JSON.parse(editBtn.dataset.booking);
        
        // Fill the form with booking data
            const createBookingIdInput = document.getElementById('create-booking-id');
            const createBookingCustomerNameInput = document.getElementById('create-booking-customer-name');
            const createBookingCustomerEmailInput = document.getElementById('create-booking-customer-email');
            const createBookingCustomerPhoneInput = document.getElementById('create-booking-customer-phone');
            const createBookingAddressInput = document.getElementById('create-booking-address');
            const createBookingServiceInput = document.getElementById('create-booking-service');
            const createBookingSquareMetersInput = document.getElementById('create-booking-square-meters');
            const createBookingDatetimeInput = document.getElementById('create-booking-datetime');
            const createBookingNotesInput = document.getElementById('create-booking-notes');
            
            if (createBookingIdInput) createBookingIdInput.value = booking.id;
            if (createBookingCustomerNameInput) createBookingCustomerNameInput.value = booking.customer_name;
            if (createBookingCustomerEmailInput) createBookingCustomerEmailInput.value = booking.customer_email;
            if (createBookingCustomerPhoneInput) createBookingCustomerPhoneInput.value = booking.customer_phone || '';
            if (createBookingAddressInput) createBookingAddressInput.value = booking.address || '';
            if (createBookingServiceInput) createBookingServiceInput.value = booking.service_id;
            if (createBookingSquareMetersInput) createBookingSquareMetersInput.value = booking.square_meters;
        
        // Combine date and time for datetime-local input
            const dateStr = booking.booking_date; // Format: YYYY-MM-DD
            const timeStr = booking.booking_time; // Format: HH:MM
            if (createBookingDatetimeInput) createBookingDatetimeInput.value = dateStr + 'T' + timeStr;
            
            if (createBookingNotesInput) createBookingNotesInput.value = booking.notes || '';
            
            const statusSelect = createBookingIdInput.parentNode.querySelector('select[name="status"]');
            if (statusSelect) statusSelect.value = booking.status;
        
        // Show cancel button and scroll to form
            const cancelBookingEditBtn = document.getElementById('cb-cancel-booking-edit');
            if (cancelBookingEditBtn) cancelBookingEditBtn.style.display = 'block';
            
            const createBookingSection = document.querySelector('.cb-create-booking-section');
            if (createBookingSection) {
                const offsetTop = createBookingSection.offsetTop - 100;
                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
            }
        
        // Change submit button text
            const submitBtn = document.querySelector('#cb-create-booking-form input[type="submit"]');
            if (submitBtn) submitBtn.value = 'Update Booking';
        }
    });
    
    // Cancel booking edit
    const cancelBookingEditBtn = document.getElementById('cb-cancel-booking-edit');
    if (cancelBookingEditBtn) {
        cancelBookingEditBtn.addEventListener('click', function() {
            const createBookingForm = document.getElementById('cb-create-booking-form');
            if (createBookingForm) createBookingForm.reset();
            const createBookingIdInput = document.getElementById('create-booking-id');
            if (createBookingIdInput) createBookingIdInput.value = '';
        
        // Set default datetime to current date/time
            const now = new Date();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const datetimeValue = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            const createBookingDatetimeInput = document.getElementById('create-booking-datetime');
            if (createBookingDatetimeInput) createBookingDatetimeInput.value = datetimeValue;
            
            this.style.display = 'none';
            const submitBtn = document.querySelector('#cb-create-booking-form input[type="submit"]');
            if (submitBtn) submitBtn.value = 'Save Booking';
        });
    }
    
    // Delete booking
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-delete-booking')) {
            const deleteBtn = e.target.closest('.cb-delete-booking');
            const bookingId = deleteBtn.dataset.bookingId;
            const bookingRef = deleteBtn.closest('tr').querySelector('td:first-child strong').textContent;
        
        if (confirm('Are you sure you want to delete booking ' + bookingRef + '? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'cb_delete_booking');
                formData.append('nonce', cb_admin.nonce);
                formData.append('booking_id', bookingId);
                
                fetch(cb_admin.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        showNotice('Booking deleted successfully!', 'success');
                        // Remove the row from table
                        const bookingRow = document.querySelector('tr[data-booking-id="' + bookingId + '"]');
                        if (bookingRow) {
                            bookingRow.style.opacity = '0';
                            bookingRow.style.transition = 'opacity 0.3s ease';
                            setTimeout(() => {
                                if (bookingRow.parentNode) {
                                    bookingRow.remove();
                                }
                            }, 300);
                        }
                    } else {
                        showNotice(response.data.message || 'Error deleting booking', 'error');
                    }
                })
                .catch(error => {
                    console.error('Delete booking error:', error);
                    showNotice('Error deleting booking', 'error');
            });
            }
        }
    });
    
    // Search functionality for all admin pages
    initializeSearchFunctionality();
    
    // Color customization functionality
    initializeColorCustomization();
    
    // Close the document ready function
});

// Search functionality functions
function initializeSearchFunctionality() {
    // Bookings search
    const bookingsSearch = document.getElementById('cb-bookings-search');
    const searchBookingsBtn = document.getElementById('cb-search-bookings');
    const clearBookingsSearch = document.getElementById('cb-clear-bookings-search');
    
    if (bookingsSearch && clearBookingsSearch) {
        // Real-time search as you type
        bookingsSearch.addEventListener('input', function() {
            searchBookings(this.value);
        });
        
        // Search button
        if (searchBookingsBtn) {
            searchBookingsBtn.addEventListener('click', function() {
                searchBookings(bookingsSearch.value);
            });
        }
        
        // Clear button
        clearBookingsSearch.addEventListener('click', function() {
            bookingsSearch.value = '';
            searchBookings('');
        });
        
        // Enter key to search
        bookingsSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBookings(this.value);
            }
        });
    }
    
    // Services search
    const servicesSearch = document.getElementById('cb-services-search');
    const searchServicesBtn = document.getElementById('cb-search-services');
    const clearServicesSearch = document.getElementById('cb-clear-services-search');
    
    if (servicesSearch && clearServicesSearch) {
        // Real-time search as you type
        servicesSearch.addEventListener('input', function() {
            searchServices(this.value);
        });
        
        // Search button
        if (searchServicesBtn) {
            searchServicesBtn.addEventListener('click', function() {
                searchServices(servicesSearch.value);
            });
        }
        
        // Clear button
        clearServicesSearch.addEventListener('click', function() {
            servicesSearch.value = '';
            searchServices('');
        });
        
        // Enter key to search
        servicesSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchServices(this.value);
            }
        });
    }
    
    // ZIP codes search
    const zipCodesSearch = document.getElementById('cb-zip-codes-search');
    const searchZipCodesBtn = document.getElementById('cb-search-zip-codes');
    const clearZipCodesSearch = document.getElementById('cb-clear-zip-codes-search');
    
    if (zipCodesSearch && clearZipCodesSearch) {
        // Real-time search as you type
        zipCodesSearch.addEventListener('input', function() {
            searchZipCodes(this.value);
        });
        
        // Search button
        if (searchZipCodesBtn) {
            searchZipCodesBtn.addEventListener('click', function() {
                searchZipCodes(zipCodesSearch.value);
            });
        }
        
        // Clear button
        clearZipCodesSearch.addEventListener('click', function() {
            zipCodesSearch.value = '';
            searchZipCodes('');
        });
        
        // Enter key to search
        zipCodesSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchZipCodes(this.value);
            }
        });
    }
    
    // Trucks search
    const trucksSearch = document.getElementById('cb-trucks-search');
    const searchTrucksBtn = document.getElementById('cb-search-trucks');
    const clearTrucksSearch = document.getElementById('cb-clear-trucks-search');
    
    if (trucksSearch && clearTrucksSearch) {
        // Real-time search as you type
        trucksSearch.addEventListener('input', function() {
            searchTrucks(this.value);
        });
        
        // Search button
        if (searchTrucksBtn) {
            searchTrucksBtn.addEventListener('click', function() {
                searchTrucks(trucksSearch.value);
            });
        }
        
        // Clear button
        clearTrucksSearch.addEventListener('click', function() {
            trucksSearch.value = '';
            searchTrucks('');
        });
        
        // Enter key to search
        trucksSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTrucks(this.value);
            }
        });
    }
}

function searchBookings(searchTerm) {
    const table = document.querySelector('#cb-bookings-list table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let found = false;
        
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(searchLower)) {
                found = true;
            }
        });
        
        row.style.display = found ? '' : 'none';
    });
}

// Color customization functionality
function initializeColorCustomization() {
    const colorInputs = document.querySelectorAll('.cb-color-picker');
    
    colorInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateColorPreview();
        });
    });
    
    // Initial preview update
    updateColorPreview();
}

function updateColorPreview() {
    const primaryColor = document.getElementById('primary-color')?.value || '#0073aa';
    const secondaryColor = document.getElementById('secondary-color')?.value || '#00a0d2';
    const backgroundColor = document.getElementById('background-color')?.value || '#ffffff';
    const textColor = document.getElementById('text-color')?.value || '#333333';
    const borderColor = document.getElementById('border-color')?.value || '#dddddd';
    const successColor = document.getElementById('success-color')?.value || '#46b450';
    const errorColor = document.getElementById('error-color')?.value || '#dc3232';
    
    // Update CSS variables for preview
    const root = document.documentElement;
    root.style.setProperty('--cb-primary-color', primaryColor);
    root.style.setProperty('--cb-secondary-color', secondaryColor);
    root.style.setProperty('--cb-background-color', backgroundColor);
    root.style.setProperty('--cb-text-color', textColor);
    root.style.setProperty('--cb-border-color', borderColor);
    root.style.setProperty('--cb-success-color', successColor);
    root.style.setProperty('--cb-error-color', errorColor);
}

function searchServices(searchTerm) {
    const table = document.querySelector('#cb-services-list table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let found = false;
        
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(searchLower)) {
                found = true;
            }
        });
        
        row.style.display = found ? '' : 'none';
    });
}

// Color customization functionality
function initializeColorCustomization() {
    const colorInputs = document.querySelectorAll('.cb-color-picker');
    
    colorInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateColorPreview();
        });
    });
    
    // Initial preview update
    updateColorPreview();
}

function updateColorPreview() {
    const primaryColor = document.getElementById('primary-color')?.value || '#0073aa';
    const secondaryColor = document.getElementById('secondary-color')?.value || '#00a0d2';
    const backgroundColor = document.getElementById('background-color')?.value || '#ffffff';
    const textColor = document.getElementById('text-color')?.value || '#333333';
    const borderColor = document.getElementById('border-color')?.value || '#dddddd';
    const successColor = document.getElementById('success-color')?.value || '#46b450';
    const errorColor = document.getElementById('error-color')?.value || '#dc3232';
    
    // Update CSS variables for preview
    const root = document.documentElement;
    root.style.setProperty('--cb-primary-color', primaryColor);
    root.style.setProperty('--cb-secondary-color', secondaryColor);
    root.style.setProperty('--cb-background-color', backgroundColor);
    root.style.setProperty('--cb-text-color', textColor);
    root.style.setProperty('--cb-border-color', borderColor);
    root.style.setProperty('--cb-success-color', successColor);
    root.style.setProperty('--cb-error-color', errorColor);
}

function searchZipCodes(searchTerm) {
    const table = document.querySelector('#cb-zip-codes-list table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let found = false;
        
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(searchLower)) {
                found = true;
            }
        });
        
        row.style.display = found ? '' : 'none';
    });
}

// Color customization functionality
function initializeColorCustomization() {
    const colorInputs = document.querySelectorAll('.cb-color-picker');
    
    colorInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateColorPreview();
        });
    });
    
    // Initial preview update
    updateColorPreview();
}

function updateColorPreview() {
    const primaryColor = document.getElementById('primary-color')?.value || '#0073aa';
    const secondaryColor = document.getElementById('secondary-color')?.value || '#00a0d2';
    const backgroundColor = document.getElementById('background-color')?.value || '#ffffff';
    const textColor = document.getElementById('text-color')?.value || '#333333';
    const borderColor = document.getElementById('border-color')?.value || '#dddddd';
    const successColor = document.getElementById('success-color')?.value || '#46b450';
    const errorColor = document.getElementById('error-color')?.value || '#dc3232';
    
    // Update CSS variables for preview
    const root = document.documentElement;
    root.style.setProperty('--cb-primary-color', primaryColor);
    root.style.setProperty('--cb-secondary-color', secondaryColor);
    root.style.setProperty('--cb-background-color', backgroundColor);
    root.style.setProperty('--cb-text-color', textColor);
    root.style.setProperty('--cb-border-color', borderColor);
    root.style.setProperty('--cb-success-color', successColor);
    root.style.setProperty('--cb-error-color', errorColor);
}

function searchTrucks(searchTerm) {
    const table = document.querySelector('#cb-trucks-search').closest('.cb-admin-section').querySelector('table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let found = false;
        
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(searchLower)) {
                found = true;
            }
        });
        
        row.style.display = found ? '' : 'none';
    });
}

// Color customization functionality
function initializeColorCustomization() {
    const colorInputs = document.querySelectorAll('.cb-color-picker');
    
    colorInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateColorPreview();
        });
    });
    
    // Initial preview update
    updateColorPreview();
}

function updateColorPreview() {
    const primaryColor = document.getElementById('primary-color')?.value || '#0073aa';
    const secondaryColor = document.getElementById('secondary-color')?.value || '#00a0d2';
    const backgroundColor = document.getElementById('background-color')?.value || '#ffffff';
    const textColor = document.getElementById('text-color')?.value || '#333333';
    const borderColor = document.getElementById('border-color')?.value || '#dddddd';
    const successColor = document.getElementById('success-color')?.value || '#46b450';
    const errorColor = document.getElementById('error-color')?.value || '#dc3232';
    
    // Update CSS variables for preview
    const root = document.documentElement;
    root.style.setProperty('--cb-primary-color', primaryColor);
    root.style.setProperty('--cb-secondary-color', secondaryColor);
    root.style.setProperty('--cb-background-color', backgroundColor);
    root.style.setProperty('--cb-text-color', textColor);
    root.style.setProperty('--cb-border-color', borderColor);
    root.style.setProperty('--cb-success-color', successColor);
    root.style.setProperty('--cb-error-color', errorColor);
}
