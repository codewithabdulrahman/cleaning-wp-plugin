<?php
/**
 * Settings admin page template
 */

if (!defined('ABSPATH')) {
    exit;
}

$days = array(
    'monday' => __('Monday', 'cleaning-booking'),
    'tuesday' => __('Tuesday', 'cleaning-booking'),
    'wednesday' => __('Wednesday', 'cleaning-booking'),
    'thursday' => __('Thursday', 'cleaning-booking'),
    'friday' => __('Friday', 'cleaning-booking'),
    'saturday' => __('Saturday', 'cleaning-booking'),
    'sunday' => __('Sunday', 'cleaning-booking')
);
?>

<div class="wrap">
    <h1><?php _e('Cleaning Booking Settings', 'cleaning-booking'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('cb_settings'); ?>
        
        <div class="cb-admin-section">
            <h2><?php _e('Booking Settings', 'cleaning-booking'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="slot-duration"><?php _e('Slot Duration', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="slot-duration" name="slot_duration" value="<?php echo esc_attr($slot_duration); ?>" min="15" max="120" class="small-text">
                        <span class="description"><?php _e('Default slot duration in minutes', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="buffer-time"><?php _e('Buffer Time', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="buffer-time" name="buffer_time" value="<?php echo esc_attr($buffer_time); ?>" min="0" max="60" class="small-text">
                        <span class="description"><?php _e('Buffer time between bookings in minutes', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="booking-hold-time"><?php _e('Booking Hold Time', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="booking-hold-time" name="booking_hold_time" value="<?php echo esc_attr($booking_hold_time); ?>" min="5" max="60" class="small-text">
                        <span class="description"><?php _e('How long to hold a slot during checkout in minutes', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="cb-admin-section">
            <h2><?php _e('Business Hours', 'cleaning-booking'); ?></h2>
            
            <table class="form-table">
                <?php foreach ($days as $day_key => $day_name): ?>
                    <?php 
                    $day_settings = isset($business_hours[$day_key]) ? $business_hours[$day_key] : array(
                        'enabled' => false,
                        'start' => '09:00',
                        'end' => '17:00'
                    );
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($day_name); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="business_hours[<?php echo esc_attr($day_key); ?>][enabled]" value="1" <?php checked($day_settings['enabled']); ?>>
                                <?php _e('Enabled', 'cleaning-booking'); ?>
                            </label>
                            <br><br>
                            <label class="cb-time-label">
                                <?php _e('From:', 'cleaning-booking'); ?>
                                <input type="time" name="business_hours[<?php echo esc_attr($day_key); ?>][start]" value="<?php echo esc_attr($day_settings['start']); ?>" class="cb-time-input">
                            </label>
                            <label class="cb-time-label">
                                <?php _e('To:', 'cleaning-booking'); ?>
                                <input type="time" name="business_hours[<?php echo esc_attr($day_key); ?>][end]" value="<?php echo esc_attr($day_settings['end']); ?>" class="cb-time-input">
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings', 'cleaning-booking'); ?>">
        </p>
    </form>
    
    <div class="cb-admin-section">
        <h2><?php _e('Shortcode Usage', 'cleaning-booking'); ?></h2>
        <p><?php _e('Use the following shortcode to display the booking widget on any page:', 'cleaning-booking'); ?></p>
        <code>[cb_widget]</code>
        
        <h3><?php _e('Available Parameters', 'cleaning-booking'); ?></h3>
        <ul>
            <li><code>title</code> - <?php _e('Custom title for the widget', 'cleaning-booking'); ?></li>
            <li><code>show_steps</code> - <?php _e('Show step indicators (true/false)', 'cleaning-booking'); ?></li>
            <li><code>theme</code> - <?php _e('Theme color (light/dark)', 'cleaning-booking'); ?></li>
        </ul>
        
        <h4><?php _e('Example:', 'cleaning-booking'); ?></h4>
        <code>[cb_widget title="Book Your Cleaning Service" show_steps="true" theme="light"]</code>
    </div>
</div>

