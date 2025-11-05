<?php
/**
 * Admin template for special days management
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Special Days Management', 'cleaning-booking'); ?></h1>
    <p class="description"><?php _e('Manage holidays, maintenance days, and custom off days. These days will be unavailable for bookings.', 'cleaning-booking'); ?></p>
    
    <!-- Add New Special Day Form -->
    <div class="cb-admin-section" style="margin-top: 20px;">
        <h2><?php _e('Add New Special Day', 'cleaning-booking'); ?></h2>
        <form id="cb-special-day-form" method="post">
            <input type="hidden" name="special_day_id" id="special_day_id" value="">
            <input type="hidden" name="action" value="cb_save_special_day">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cb_admin_nonce'); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="special_day_date"><?php _e('Date', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="date" id="special_day_date" name="date" class="regular-text" required>
                        <p class="description"><?php _e('Select the date that should be unavailable for bookings', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="special_day_type"><?php _e('Type', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <select id="special_day_type" name="type" class="regular-text">
                            <option value="holiday"><?php _e('Holiday', 'cleaning-booking'); ?></option>
                            <option value="maintenance"><?php _e('Maintenance', 'cleaning-booking'); ?></option>
                            <option value="custom" selected><?php _e('Custom', 'cleaning-booking'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select the type of special day', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="special_day_reason"><?php _e('Reason', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="special_day_reason" name="reason" rows="3" class="large-text" required></textarea>
                        <p class="description"><?php _e('Enter the reason for this day being unavailable (e.g., "Christmas Day", "Van Broken", "Greek Holiday")', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary" id="cb-save-special-day-btn"><?php _e('Save Special Day', 'cleaning-booking'); ?></button>
                <button type="button" class="button" id="cb-cancel-special-day-btn" style="display:none;"><?php _e('Cancel', 'cleaning-booking'); ?></button>
            </p>
        </form>
    </div>
    
    <!-- Special Days List -->
    <div class="cb-admin-section" style="margin-top: 30px;">
        <h2><?php _e('Existing Special Days', 'cleaning-booking'); ?></h2>
        
        <?php if (empty($special_days)) : ?>
            <p><?php _e('No special days have been added yet.', 'cleaning-booking'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('Date', 'cleaning-booking'); ?></th>
                        <th scope="col"><?php _e('Type', 'cleaning-booking'); ?></th>
                        <th scope="col"><?php _e('Reason', 'cleaning-booking'); ?></th>
                        <th scope="col"><?php _e('Actions', 'cleaning-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($special_days as $special_day) : ?>
                        <tr data-special-day-id="<?php echo esc_attr($special_day->id); ?>">
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($special_day->date))); ?></td>
                            <td>
                                <span class="cb-badge cb-badge-<?php echo esc_attr($special_day->type); ?>">
                                    <?php echo esc_html(ucfirst($special_day->type)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($special_day->reason); ?></td>
                            <td>
                                <button type="button" class="button button-small cb-edit-special-day" data-id="<?php echo esc_attr($special_day->id); ?>" data-date="<?php echo esc_attr($special_day->date); ?>" data-type="<?php echo esc_attr($special_day->type); ?>" data-reason="<?php echo esc_attr($special_day->reason); ?>">
                                    <?php _e('Edit', 'cleaning-booking'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete cb-delete-special-day" data-id="<?php echo esc_attr($special_day->id); ?>">
                                    <?php _e('Delete', 'cleaning-booking'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.cb-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}
.cb-badge-holiday {
    background-color: #ffeb3b;
    color: #856404;
}
.cb-badge-maintenance {
    background-color: #ff9800;
    color: #fff;
}
.cb-badge-custom {
    background-color: #9e9e9e;
    color: #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle form submission
    $('#cb-special-day-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'cb_save_special_day',
            nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>',
            date: $('#special_day_date').val(),
            type: $('#special_day_type').val(),
            reason: $('#special_day_reason').val(),
            special_day_id: $('#special_day_id').val()
        };
        
        if (!formData.date || !formData.reason) {
            alert('<?php _e('Please fill in all required fields', 'cleaning-booking'); ?>');
            return;
        }
        
        $('#cb-save-special-day-btn').prop('disabled', true).text('<?php _e('Saving...', 'cleaning-booking'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('Error saving special day', 'cleaning-booking'); ?>');
                    $('#cb-save-special-day-btn').prop('disabled', false).text('<?php _e('Save Special Day', 'cleaning-booking'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Error occurred', 'cleaning-booking'); ?>');
                $('#cb-save-special-day-btn').prop('disabled', false).text('<?php _e('Save Special Day', 'cleaning-booking'); ?>');
            }
        });
    });
    
    // Handle edit button
    $('.cb-edit-special-day').on('click', function() {
        const id = $(this).data('id');
        const date = $(this).data('date');
        const type = $(this).data('type');
        const reason = $(this).data('reason');
        
        $('#special_day_id').val(id);
        $('#special_day_date').val(date);
        $('#special_day_type').val(type);
        $('#special_day_reason').val(reason);
        $('#cb-save-special-day-btn').text('<?php _e('Update Special Day', 'cleaning-booking'); ?>');
        $('#cb-cancel-special-day-btn').show();
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#cb-special-day-form').offset().top - 50
        }, 500);
    });
    
    // Handle cancel button
    $('#cb-cancel-special-day-btn').on('click', function() {
        $('#special_day_id').val('');
        $('#special_day_date').val('');
        $('#special_day_type').val('custom');
        $('#special_day_reason').val('');
        $('#cb-save-special-day-btn').text('<?php _e('Save Special Day', 'cleaning-booking'); ?>');
        $(this).hide();
    });
    
    // Handle delete button
    $('.cb-delete-special-day').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to delete this special day?', 'cleaning-booking'); ?>')) {
            return;
        }
        
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cb_delete_special_day',
                nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>',
                special_day_id: id
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        if ($('tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || '<?php _e('Error deleting special day', 'cleaning-booking'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Error occurred', 'cleaning-booking'); ?>');
            }
        });
    });
});
</script>

