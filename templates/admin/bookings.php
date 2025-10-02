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
    
    <div class="cb-bookings-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Reference', 'cleaning-booking'); ?></th>
                    <th><?php _e('Customer', 'cleaning-booking'); ?></th>
                    <th><?php _e('Service', 'cleaning-booking'); ?></th>
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
                        <td colspan="8" class="text-center"><?php _e('No bookings found', 'cleaning-booking'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
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
                                <?php echo esc_html($booking->service_name); ?><br>
                                <small><?php echo esc_html($booking->square_meters); ?> m²</small>
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
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('cb_update_booking_status'); ?>
                                    <input type="hidden" name="booking_id" value="<?php echo esc_attr($booking->id); ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="pending" <?php selected($booking->status, 'pending'); ?>><?php _e('Pending', 'cleaning-booking'); ?></option>
                                        <option value="confirmed" <?php selected($booking->status, 'confirmed'); ?>><?php _e('Confirmed', 'cleaning-booking'); ?></option>
                                        <option value="paid" <?php selected($booking->status, 'paid'); ?>><?php _e('Paid', 'cleaning-booking'); ?></option>
                                        <option value="cancelled" <?php selected($booking->status, 'cancelled'); ?>><?php _e('Cancelled', 'cleaning-booking'); ?></option>
                                        <option value="completed" <?php selected($booking->status, 'completed'); ?>><?php _e('Completed', 'cleaning-booking'); ?></option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

