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
    
    <!-- Color Customization Section -->
    <div class="cb-admin-section">
        <h2><?php _e('Frontend Color Customization', 'cleaning-booking'); ?></h2>
        <p class="description"><?php _e('Customize the colors of your booking widget frontend. Changes will be applied immediately.', 'cleaning-booking'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="primary-color"><?php _e('Primary Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="primary-color" name="primary_color" value="<?php echo esc_attr(get_option('cb_primary_color', '#0073aa')); ?>" class="cb-color-picker">
                    <span class="description"><?php _e('Main brand color for buttons and highlights', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="secondary-color"><?php _e('Secondary Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="secondary-color" name="secondary_color" value="<?php echo esc_attr(get_option('cb_secondary_color', '#00a0d2')); ?>" class="cb-color-picker">
                    <span class="description"><?php _e('Secondary color for accents and hover states', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="background-color"><?php _e('Background Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="background-color" name="background_color" value="<?php echo esc_attr(get_option('cb_background_color', '#ffffff')); ?>" class="cb-color-picker">
                    <span class="description"><?php _e('Main background color of the booking widget', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="text-color"><?php _e('Text Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="text-color" name="text_color" value="<?php echo esc_attr(get_option('cb_text_color', '#333333')); ?>" class="cb-color-picker">
                    <span class="description"><?php _e('Main text color for content', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="border-color"><?php _e('Border Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="border-color" name="border_color" value="<?php echo esc_attr(get_option('cb_border_color', '#dddddd')); ?>" class="cb-color-picker">
                    <span class="description"><?php _e('Border color for form elements and cards', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="success-color"><?php _e('Success Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="success-color" name="success_color" value="<?php echo esc_attr(get_option('cb_success_color', '#46b450')); ?>" class="cb-color-picker">
                    <span class="description"><?php _e('Color for success messages and confirmations', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="error-color"><?php _e('Error Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="error-color" name="error_color" value="<?php echo esc_attr(get_option('cb_error_color', '#dc3232')); ?>" class="cb-color-picker">
                    <span class="description"><?php _e('Color for error messages and warnings', 'cleaning-booking'); ?></span>
                </td>
            </tr>
        </table>
        
        <!-- Color Preview -->
        <div class="cb-color-preview">
            <h3><?php _e('Color Preview', 'cleaning-booking'); ?></h3>
            <div class="cb-preview-widget">
                <div class="cb-preview-header" style="background: linear-gradient(135deg, var(--cb-primary-color, #0073aa) 0%, var(--cb-secondary-color, #00a0d2) 100%);">
                    <span style="color: white;"><?php _e('Booking Widget Preview', 'cleaning-booking'); ?></span>
                    <div style="margin-top: 15px; display: flex; gap: 0; background: rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 4px;">
                        <div style="flex: 1; display: flex; align-items: center; padding: 8px 12px; background: white; color: var(--cb-primary-color, #0073aa); border-radius: 4px; font-weight: 600;">
                            <div style="width: 20px; height: 20px; border-radius: 50%; background: var(--cb-primary-color, #0073aa); color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; margin-right: 8px;">1</div>
                            <span style="font-size: 12px;">Location</span>
                        </div>
                        <div style="flex: 1; display: flex; align-items: center; padding: 8px 12px; opacity: 0.6;">
                            <div style="width: 20px; height: 20px; border-radius: 50%; background: rgba(255, 255, 255, 0.3); color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; margin-right: 8px;">2</div>
                            <span style="font-size: 12px; color: white;">Service</span>
                        </div>
                        <div style="flex: 1; display: flex; align-items: center; padding: 8px 12px; opacity: 0.6;">
                            <div style="width: 20px; height: 20px; border-radius: 50%; background: rgba(255, 255, 255, 0.3); color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; margin-right: 8px;">3</div>
                            <span style="font-size: 12px; color: white;">Details</span>
                        </div>
                    </div>
                </div>
                <div class="cb-preview-layout" style="display: grid; grid-template-columns: 2fr 1fr; min-height: 200px; background: var(--cb-background-color, #ffffff);">
                    <div class="cb-preview-main" style="background-color: var(--cb-background-color, #ffffff); color: var(--cb-text-color, #333333); border-right: 1px solid var(--cb-border-color, #dddddd); padding: 20px;">
                        <h4 style="color: var(--cb-text-color, #333333); margin-top: 0;"><?php _e('Main Content Area', 'cleaning-booking'); ?></h4>
                        <div class="cb-preview-button" style="background-color: var(--cb-primary-color, #0073aa); color: white; padding: 10px 20px; border-radius: 4px; display: inline-block; margin: 10px 0;">
                            <?php _e('Sample Button', 'cleaning-booking'); ?>
                        </div>
                        <div class="cb-preview-success" style="color: var(--cb-success-color, #46b450); margin: 10px 0;">
                            <?php _e('Success message', 'cleaning-booking'); ?>
                        </div>
                        <div class="cb-preview-error" style="color: var(--cb-error-color, #dc3232); margin: 10px 0;">
                            <?php _e('Error message', 'cleaning-booking'); ?>
                        </div>
                    </div>
                    <div class="cb-preview-sidebar" style="background-color: var(--cb-background-color, #ffffff); color: var(--cb-text-color, #333333); padding: 20px;">
                        <h4 style="color: var(--cb-text-color, #333333); margin-top: 0;"><?php _e('Sidebar', 'cleaning-booking'); ?></h4>
                        <p style="color: var(--cb-text-color, #333333); opacity: 0.8;"><?php _e('Service information and pricing', 'cleaning-booking'); ?></p>
                        <div style="background-color: var(--cb-primary-color, #0073aa); color: white; padding: 15px; border-radius: 4px; margin: 10px 0;">
                            <?php _e('Checkout Button', 'cleaning-booking'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

