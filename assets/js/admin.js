/**
 * Admin JavaScript for Cleaning Booking plugin
 */

jQuery(document).ready(function($) {
    
    // Services form handling
    $('#cb-service-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('input[type="submit"]');
        var originalText = $submitBtn.val();
        
        $submitBtn.val(cb_admin.strings.saving).prop('disabled', true);
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $form[0].reset();
                    $('#service-id').val('');
                    $('#cb-cancel-edit').hide();
                    loadServices();
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(cb_admin.strings.error, 'error');
            },
            complete: function() {
                $submitBtn.val(originalText).prop('disabled', false);
            }
        });
    });
    
    // Edit service
    $(document).on('click', '.cb-edit-service', function() {
        var service = $(this).data('service');
        
        $('#service-id').val(service.id);
        $('#service-name').val(service.name);
        $('#service-description').val(service.description);
        $('#service-base-price').val(service.base_price);
        $('#service-base-duration').val(service.base_duration);
        $('#service-sqm-multiplier').val(service.sqm_multiplier);
        $('#service-sqm-duration-multiplier').val(service.sqm_duration_multiplier);
        $('#service-sort-order').val(service.sort_order);
        $('#service-is-active').prop('checked', service.is_active == 1);
        
        $('#cb-cancel-edit').show();
        $('html, body').animate({ scrollTop: 0 }, 500);
    });
    
    // Cancel edit service
    $('#cb-cancel-edit').on('click', function() {
        $('#cb-service-form')[0].reset();
        $('#service-id').val('');
        $(this).hide();
    });
    
    // Delete service
    $(document).on('click', '.cb-delete-service', function() {
        if (!confirm(cb_admin.strings.confirm_delete)) {
            return;
        }
        
        var serviceId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_delete_service',
                nonce: cb_admin.nonce,
                id: serviceId
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(cb_admin.strings.error, 'error');
            }
        });
    });
    
    
    // ZIP Codes form handling
    $('#cb-zip-code-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('input[type="submit"]');
        var originalText = $submitBtn.val();
        
        $submitBtn.val(cb_admin.strings.saving).prop('disabled', true);
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $form[0].reset();
                    $('#zip-code-id').val('');
                    $('#cb-cancel-edit-zip').hide();
                    loadZipCodes();
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(cb_admin.strings.error, 'error');
            },
            complete: function() {
                $submitBtn.val(originalText).prop('disabled', false);
            }
        });
    });
    
    // Edit ZIP code
    $(document).on('click', '.cb-edit-zip-code', function() {
        var zip = $(this).data('zip');
        
        $('#zip-code-id').val(zip.id);
        $('#zip-code').val(zip.zip_code);
        $('#zip-city').val(zip.city);
        $('#zip-surcharge').val(zip.surcharge);
        $('#zip-is-active').prop('checked', zip.is_active == 1);
        
        $('#cb-cancel-edit-zip').show();
        $('html, body').animate({ scrollTop: 0 }, 500);
    });
    
    // Cancel edit ZIP code
    $('#cb-cancel-edit-zip').on('click', function() {
        $('#cb-zip-code-form')[0].reset();
        $('#zip-code-id').val('');
        $(this).hide();
    });
    
    // Delete ZIP code
    $(document).on('click', '.cb-delete-zip-code', function() {
        if (!confirm(cb_admin.strings.confirm_delete)) {
            return;
        }
        
        var zipId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_delete_zip_code',
                nonce: cb_admin.nonce,
                id: zipId
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(cb_admin.strings.error, 'error');
            }
        });
    });
    
    // Helper functions
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
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
    var currentServiceId = null;
    var currentServiceName = null;
    
    // Manage extras button from services page
    $(document).on('click', '.cb-manage-extras', function() {
        currentServiceId = $(this).data('service-id');
        currentServiceName = $(this).data('service-name');
        
        // Hide services section
        $('.cb-admin-section').not('#cb-service-extras-section').hide();
        
        // Show extras section
        $('#cb-service-extras-section').show();
        $('#selected-service-name').text(currentServiceName);
        
        // Load service extras
        loadServiceExtras(currentServiceId);
    });
    
    // Back to services button
    $('#cb-back-to-services').on('click', function() {
        $('#cb-service-extras-section').hide();
        $('.cb-admin-section').not('#cb-service-extras-section').show();
        currentServiceId = null;
        currentServiceName = null;
    });
    
    
    // Remove service extra
    $(document).on('click', '.cb-remove-service-extra', function() {
        if (!confirm('Are you sure you want to remove this extra from the service?')) {
            return;
        }
        
        var extraId = $(this).data('extra-id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_remove_service_extra',
                nonce: cb_admin.nonce,
                service_id: currentServiceId,
                extra_id: extraId
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    loadAvailableExtras(currentServiceId);
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(cb_admin.strings.error, 'error');
            }
        });
    });
    
    
    // Show create extra form
    $('#cb-show-create-form').on('click', function() {
        $(this).hide();
        $('#cb-create-extra-form').show();
        $('#cb-cancel-create').show();
    });
    
    // Hide create extra form
    $('#cb-cancel-create').on('click', function() {
        $('#cb-create-extra-form').hide();
        $('#cb-show-create-form').show();
        $(this).hide();
        $('#cb-create-extra-form')[0].reset();
        $('#create-extra-id').val('');
    });
    
    // Create extra form submission
    $('#cb-create-extra-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('input[type="submit"]');
        var originalText = $submitBtn.val();
        
        $submitBtn.val(cb_admin.strings.saving).prop('disabled', true);
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&service_id=' + currentServiceId,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    $form[0].reset();
                    $('#create-extra-id').val('');
                    $('#cb-cancel-create').click(); // Hide form and show button
                    
                    // Reload service extras to show the new extra
                    setTimeout(function() {
                        loadServiceExtras(currentServiceId);
                    }, 500);
                } else {
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(cb_admin.strings.error, 'error');
            },
            complete: function() {
                $submitBtn.val(originalText).prop('disabled', false);
            }
        });
    });
    
    // Edit extra inline
    $(document).on('click', '.cb-edit-extra-inline', function() {
        var extra = $(this).data('extra');
        
        $('#create-extra-id').val(extra.id);
        $('#create-extra-name').val(extra.name);
        $('#create-extra-description').val(extra.description);
        $('#create-extra-price').val(extra.price);
        $('#create-extra-duration').val(extra.duration);
        $('#create-extra-is-active').prop('checked', parseInt(extra.is_active) === 1);
        
        $('#cb-show-create-form').hide();
        $('#cb-create-extra-form').show();
        $('#cb-cancel-create').show();
        $('html, body').animate({ scrollTop: $('#cb-create-extra-section').offset().top - 100 }, 500);
    });
    
    // Toggle extra status (active/inactive)
    $(document).on('change', '.cb-toggle-extra-status', function() {
        var extraId = $(this).data('extra-id');
        var isActive = $(this).is(':checked') ? 1 : 0;
        var $statusText = $(this).siblings('.cb-status-text');
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_update_extra_status',
                nonce: cb_admin.nonce,
                extra_id: extraId,
                is_active: isActive
            },
            success: function(response) {
                if (response.success) {
                    if (isActive) {
                        $statusText.removeClass('cb-status-inactive').addClass('cb-status-active').text('Active');
                    } else {
                        $statusText.removeClass('cb-status-active').addClass('cb-status-inactive').text('Inactive');
                    }
                } else {
                    // Revert checkbox if error
                    $(this).prop('checked', !isActive);
                    showNotice(response.data.message || cb_admin.strings.error, 'error');
                }
            },
            error: function() {
                // Revert checkbox if error
                $(this).prop('checked', !isActive);
                showNotice(cb_admin.strings.error, 'error');
            }.bind(this)
        });
    });
    
    function loadServiceExtras(serviceId) {
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_get_service_extras',
                nonce: cb_admin.nonce,
                service_id: serviceId
            },
            success: function(response) {
                if (response.success) {
                    displayServiceExtras(response.data.extras);
                }
            }
        });
    }
    
    
    
    
    function displayServiceExtras(extras) {
        var tbody = $('#service-extras-tbody');
        tbody.empty();
        
        if (extras.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center">No extras assigned to this service</td></tr>');
            return;
        }
        
        extras.forEach(function(extra) {
            var row = '<tr data-extra-id="' + extra.id + '">' +
                '<td><strong>' + extra.name + '</strong>' +
                (extra.description ? '<br><small>' + extra.description + '</small>' : '') + '</td>' +
                '<td>$' + parseFloat(extra.price).toFixed(2) + '</td>' +
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
                '</td>' +
            '</tr>';
            tbody.append(row);
        });
    }
    
    // Booking management functionality
    
    // Create/Edit Booking Form
    $('#cb-create-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var $submitBtn = $(this).find('input[type="submit"]');
        var originalText = $submitBtn.val();
        var bookingId = $('#create-booking-id').val();
        var isEdit = bookingId && bookingId !== '';
        
        $submitBtn.val(isEdit ? 'Updating...' : 'Converting...').prop('disabled', true);
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    if (isEdit) {
                        // Hide cancel button and reset form for new booking
                        $('#cb-cancel-booking-edit').hide();
                        $('#cb-create-booking-form')[0].reset();
                        $('#create-booking-date').valueAsDate = new Date();
                        
                        // Reload to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        // Reset form for new booking
                        $('#cb-create-booking-form')[0].reset();
                        $('#create-booking-date').valueAsDate = new Date();
                        
                        // Reload to show new booking
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showNotice(response.data.message || 'Error saving booking', 'error');
                }
            },
            error: function() {
                showNotice('Error saving booking', 'error');
            },
            complete: function() {
                $submitBtn.val(isEdit ? 'Update Booking' : 'Save Booking').prop('disabled', false);
            }
        });
    });
    
    // Set default datetime to current date/time
    if (document.getElementById('create-booking-datetime')) {
        var now = new Date();
        // Format for datetime-local input (YYYY-MM-DDTHH:MM)
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var year = now.getFullYear();
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        
        var datetimeValue = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        document.getElementById('create-booking-datetime').value = datetimeValue;
    }
    
    // Edit booking inline (fill upper form like services)
    $(document).on('click', '.cb-edit-booking-inline', function() {
        var booking = $(this).data('booking');
        
        // Fill the form with booking data
        $('#create-booking-id').val(booking.id);
        $('#create-booking-customer-name').val(booking.customer_name);
        $('#create-booking-customer-email').val(booking.customer_email);
        $('#create-booking-customer-phone').val(booking.customer_phone || '');
        $('#create-booking-address').val(booking.address || '');
        $('#create-booking-service').val(booking.service_id);
        $('#create-booking-square-meters').val(booking.square_meters);
        
        // Combine date and time for datetime-local input
        var dateStr = booking.booking_date; // Format: YYYY-MM-DD
        var timeStr = booking.booking_time; // Format: HH:MM
        $('#create-booking-datetime').val(dateStr + 'T' + timeStr);
        
        $('#create-booking-notes').val(booking.notes || '');
        $('#create-booking-id').siblings('form').find('select[name="status"]').val(booking.status);
        
        // Show cancel button and scroll to form
        $('#cb-cancel-booking-edit').show();
        $('html, body').animate({ scrollTop: $('.cb-create-booking-section').offset().top - 100 }, 500);
        
        // Change submit button text
        $('#cb-create-booking-form input[type="submit"]').val('Update Booking');
    });
    
    // Cancel booking edit
    $(document).on('click', '#cb-cancel-booking-edit', function() {
        $('#cb-create-booking-form')[0].reset();
        $('#create-booking-id').val('');
        
        // Set default datetime to current date/time
        var now = new Date();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var year = now.getFullYear();
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        
        var datetimeValue = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        $('#create-booking-datetime').val(datetimeValue);
        
        $(this).hide();
        $('#cb-create-booking-form input[type="submit"]').val('Save Booking');
    });
    
    // Delete booking
    $(document).on('click', '.cb-delete-booking', function() {
        var bookingId = $(this).data('booking-id');
        var bookingRef = $(this).closest('tr').find('td:first strong').text();
        
        if (confirm('Are you sure you want to delete booking ' + bookingRef + '? This action cannot be undone.')) {
            $.ajax({
                url: cb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'cb_delete_booking',
                    nonce: cb_admin.nonce,
                    booking_id: bookingId
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Booking deleted successfully!', 'success');
                        // Remove the row from table
                        $('tr[data-booking-id="' + bookingId + '"]').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        showNotice(response.data.message || 'Error deleting booking', 'error');
                    }
                },
                error: function() {
                    showNotice('Error deleting booking', 'error');
                }
            });
        }
    });
    
});
