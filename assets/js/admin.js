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
    
    // Extras form handling
    $('#cb-extra-form').on('submit', function(e) {
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
                    $('#extra-id').val('');
                    $('#cb-cancel-edit-extra').hide();
                    loadExtras();
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
    
    // Edit extra
    $(document).on('click', '.cb-edit-extra', function() {
        var extra = $(this).data('extra');
        
        $('#extra-id').val(extra.id);
        $('#extra-name').val(extra.name);
        $('#extra-description').val(extra.description);
        $('#extra-price').val(extra.price);
        $('#extra-duration').val(extra.duration);
        $('#extra-sort-order').val(extra.sort_order);
        $('#extra-is-active').prop('checked', extra.is_active == 1);
        
        $('#cb-cancel-edit-extra').show();
        $('html, body').animate({ scrollTop: 0 }, 500);
    });
    
    // Cancel edit extra
    $('#cb-cancel-edit-extra').on('click', function() {
        $('#cb-extra-form')[0].reset();
        $('#extra-id').val('');
        $(this).hide();
    });
    
    // Delete extra
    $(document).on('click', '.cb-delete-extra', function() {
        if (!confirm(cb_admin.strings.confirm_delete)) {
            return;
        }
        
        var extraId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: cb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cb_delete_extra',
                nonce: cb_admin.nonce,
                id: extraId
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
    
    function loadExtras() {
        // Reload the page to refresh the extras list
        location.reload();
    }
    
    function loadZipCodes() {
        // Reload the page to refresh the ZIP codes list
        location.reload();
    }
});
