<?php
/**
 * Bookings admin page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Bookings', 'cleaning-booking'); ?></h1>
    
    <!-- Add New Booking Section -->
    <div class="cb-admin-section cb-create-booking-section">
        <h2><?php _e('Add New Booking', 'cleaning-booking'); ?></h2>
        
        <form id="cb-create-booking-form" class="cb-form">
            <input type="hidden" name="id" id="create-booking-id">
            <input type="hidden" name="action" value="cb_save_booking">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cb_admin_nonce'); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="create-booking-customer-name"><?php _e('Customer Name', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="create-booking-customer-name" name="customer_name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create-booking-customer-email"><?php _e('Customer Email', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="create-booking-customer-email" name="customer_email" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create-booking-customer-phone"><?php _e('Customer Phone', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="create-booking-customer-phone" name="customer_phone" class="regular-text">
                        <p class="description"><?php _e('Optional phone number', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create-booking-address"><?php _e('Address', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="create-booking-address" name="address" rows="3" class="large-text"></textarea>
                        <p class="description"><?php _e('Service address', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create-booking-service"><?php _e('Service', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <select id="create-booking-service" name="service_id" class="regular-text" required>
                            <option value=""><?php _e('Select Service', 'cleaning-booking'); ?></option>
                            <?php 
                            $services = CB_Database::get_services(false);
                            foreach($services as $service): ?>
                                <option value="<?php echo esc_attr($service->id); ?>">
                                    <?php echo esc_html($service->name); ?> - $<?php echo esc_html($service->base_price); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create-booking-square-meters"><?php _e('Space in Square Meters', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="create-booking-square-meters" name="square_meters" min="1" class="small-text" required>
                        <span class="description"><?php _e('Enter the total space area in square meters', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create-booking-datetime"><?php _e('Date & Time', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="create-booking-datetime" name="booking_datetime" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create-booking-notes"><?php _e('Notes', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="create-booking-notes" name="notes" rows="2" class="large-text"></textarea>
                        <p class="description"><?php _e('Additional notes for the booking', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Status', 'cleaning-booking'); ?></th>
                    <td>
                        <label>
                            <select name="status">
                                <option value="pending"><?php _e('Pending', 'cleaning-booking'); ?></option>
                                <option value="confirmed"><?php _e('Confirmed', 'cleaning-booking'); ?></option>
                                <option value="paid"><?php _e('Paid', 'cleaning-booking'); ?></option>
                                <option value="completed"><?php _e('Completed', 'cleaning-booking'); ?></option>
                            </select>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Booking', 'cleaning-booking'); ?>">
                <button type="button" id="cb-cancel-booking-edit" class="button" style="display: none;"><?php _e('Cancel', 'cleaning-booking'); ?></button>
            </p>
        </form>
    </div>
    
    <!-- Existing Bookings Section -->
    <div class="cb-admin-section">
        <h2><?php _e('Existing Bookings', 'cleaning-booking'); ?></h2>
        
        <!-- Search Bar -->
        <div class="cb-search-section">
            <input type="text" id="cb-bookings-search" placeholder="<?php _e('Search bookings by reference, customer name, email, or phone...', 'cleaning-booking'); ?>" class="cb-search-input">
            <button type="button" id="cb-search-bookings" class="button button-primary"><?php _e('Search', 'cleaning-booking'); ?></button>
            <button type="button" id="cb-clear-bookings-search" class="button"><?php _e('Clear', 'cleaning-booking'); ?></button>
        </div>
        
        <div id="cb-bookings-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Reference', 'cleaning-booking'); ?></th>
                        <th><?php _e('Customer', 'cleaning-booking'); ?></th>
                        <th><?php _e('Service', 'cleaning-booking'); ?></th>
                        <th><?php _e('Truck', 'cleaning-booking'); ?></th>
                        <th><?php _e('Date & Time', 'cleaning-booking'); ?></th>
                        <th><?php _e('Duration', 'cleaning-booking'); ?></th>
                        <th><?php _e('Price', 'cleaning-booking'); ?></th>
                        <th><?php _e('Status', 'cleaning-booking'); ?></th>
                        <th><?php _e('Actions', 'cleaning-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="9" class="text-center"><?php _e('No bookings found', 'cleaning-booking'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                <td>
                                    <strong><?php echo esc_html($booking->booking_reference); ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo esc_html($booking->customer_name); ?></strong><br>
                                        <small><?php echo esc_html($booking->customer_email); ?></small><br>
                                        <small><?php echo esc_html($booking->customer_phone); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php echo esc_html($booking->service_names); ?><br>
                                    <small><?php echo esc_html($booking->square_meters); ?> m²</small>
                                </td>
                                <td>
                                    <?php if ($booking->truck_id): ?>
                                        <?php 
                                        $truck = CB_Database::get_truck($booking->truck_id);
                                        echo $truck ? esc_html($truck->truck_name) : __('Unknown Truck', 'cleaning-booking');
                                        ?>
                                    <?php else: ?>
                                        <span class="cb-status cb-status-pending"><?php _e('Not Assigned', 'cleaning-booking'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html(date('M j, Y', strtotime($booking->booking_date))); ?><br>
                                    <small><?php echo esc_html(date('g:i A', strtotime($booking->booking_time))); ?></small>
                                </td>
                                <td><?php echo esc_html($booking->total_duration); ?> min</td>
                                <td>$<?php echo esc_html(number_format($booking->total_price, 2)); ?></td>
                                <td>
                                    <span class="cb-status cb-status-<?php echo esc_attr($booking->status); ?>">
                                        <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small cb-edit-booking-inline" data-booking='<?php echo esc_attr(json_encode($booking)); ?>'>Edit</button>
                                    <button type="button" class="button button-small button-secondary cb-delete-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

