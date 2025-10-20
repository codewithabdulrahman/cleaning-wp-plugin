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
    
    <!-- Typography Settings Section -->
    <div class="cb-admin-section">
        <h2><?php _e('Typography Settings', 'cleaning-booking'); ?></h2>
        <p class="description"><?php _e('Customize fonts and text styling for your booking widget.', 'cleaning-booking'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="heading-font-family"><?php _e('Heading Font Family', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <select id="heading-font-family" name="heading_font_family" class="cb-font-selector">
                        <?php 
                        $google_fonts = CB_Style_Manager::get_google_fonts();
                        $current_heading_font = CB_Style_Manager::get_setting('heading_font_family', 'Inter, sans-serif');
                        ?>
                        <option value="Inter, sans-serif" <?php selected($current_heading_font, 'Inter, sans-serif'); ?>>Inter (Default)</option>
                        <option value="Arial, sans-serif" <?php selected($current_heading_font, 'Arial, sans-serif'); ?>>Arial</option>
                        <option value="Helvetica, sans-serif" <?php selected($current_heading_font, 'Helvetica, sans-serif'); ?>>Helvetica</option>
                        <option value="Georgia, serif" <?php selected($current_heading_font, 'Georgia, serif'); ?>>Georgia</option>
                        <option value="Times New Roman, serif" <?php selected($current_heading_font, 'Times New Roman, serif'); ?>>Times New Roman</option>
                        <?php foreach ($google_fonts as $font_name => $font_value): ?>
                            <option value="Google:<?php echo esc_attr($font_value); ?>" <?php selected($current_heading_font, 'Google:' . $font_value); ?>><?php echo esc_html($font_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Font family for headings and titles', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="body-font-family"><?php _e('Body Font Family', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <select id="body-font-family" name="body_font_family" class="cb-font-selector">
                        <?php 
                        $current_body_font = CB_Style_Manager::get_setting('body_font_family', 'Inter, sans-serif');
                        ?>
                        <option value="Inter, sans-serif" <?php selected($current_body_font, 'Inter, sans-serif'); ?>>Inter (Default)</option>
                        <option value="Arial, sans-serif" <?php selected($current_body_font, 'Arial, sans-serif'); ?>>Arial</option>
                        <option value="Helvetica, sans-serif" <?php selected($current_body_font, 'Helvetica, sans-serif'); ?>>Helvetica</option>
                        <option value="Georgia, serif" <?php selected($current_body_font, 'Georgia, serif'); ?>>Georgia</option>
                        <option value="Times New Roman, serif" <?php selected($current_body_font, 'Times New Roman, serif'); ?>>Times New Roman</option>
                        <?php foreach ($google_fonts as $font_name => $font_value): ?>
                            <option value="Google:<?php echo esc_attr($font_value); ?>" <?php selected($current_body_font, 'Google:' . $font_value); ?>><?php echo esc_html($font_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Font family for body text and content', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="heading-font-size"><?php _e('Heading Font Size', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="heading-font-size" name="heading_font_size" value="<?php echo esc_attr(CB_Style_Manager::get_setting('heading_font_size', '28px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Font size for main headings (e.g., 28px, 2rem)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="body-font-size"><?php _e('Body Font Size', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="body-font-size" name="body_font_size" value="<?php echo esc_attr(CB_Style_Manager::get_setting('body_font_size', '16px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Font size for body text (e.g., 16px, 1rem)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="label-font-size"><?php _e('Label Font Size', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="label-font-size" name="label_font_size" value="<?php echo esc_attr(CB_Style_Manager::get_setting('label_font_size', '14px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Font size for form labels (e.g., 14px, 0.875rem)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="button-font-size"><?php _e('Button Font Size', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="button-font-size" name="button_font_size" value="<?php echo esc_attr(CB_Style_Manager::get_setting('button_font_size', '16px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Font size for buttons (e.g., 16px, 1rem)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="font-weight-heading"><?php _e('Heading Font Weight', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <select id="font-weight-heading" name="font_weight_heading">
                        <?php 
                        $font_weights = CB_Style_Manager::get_font_weights();
                        $current_weight = CB_Style_Manager::get_setting('font_weight_heading', '700');
                        ?>
                        <?php foreach ($font_weights as $weight => $label): ?>
                            <option value="<?php echo esc_attr($weight); ?>" <?php selected($current_weight, $weight); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Font weight for headings', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="font-weight-body"><?php _e('Body Font Weight', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <select id="font-weight-body" name="font_weight_body">
                        <?php 
                        $current_body_weight = CB_Style_Manager::get_setting('font_weight_body', '400');
                        ?>
                        <?php foreach ($font_weights as $weight => $label): ?>
                            <option value="<?php echo esc_attr($weight); ?>" <?php selected($current_body_weight, $weight); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Font weight for body text', 'cleaning-booking'); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Spacing & Layout Settings Section -->
    <div class="cb-admin-section">
        <h2><?php _e('Spacing & Layout Settings', 'cleaning-booking'); ?></h2>
        <p class="description"><?php _e('Customize spacing, padding, and layout options for your booking widget.', 'cleaning-booking'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="form-padding"><?php _e('Form Padding', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="form-padding" name="form_padding" value="<?php echo esc_attr(CB_Style_Manager::get_setting('form_padding', '24px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Padding for form containers (e.g., 24px, 1.5rem)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="field-spacing"><?php _e('Field Spacing', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="field-spacing" name="field_spacing" value="<?php echo esc_attr(CB_Style_Manager::get_setting('field_spacing', '20px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Spacing between form fields (e.g., 20px, 1.25rem)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="button-padding"><?php _e('Button Padding', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="button-padding" name="button_padding" value="<?php echo esc_attr(CB_Style_Manager::get_setting('button_padding', '16px 32px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Padding for buttons (e.g., 16px 32px)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="container-max-width"><?php _e('Container Max Width', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="container-max-width" name="container_max_width" value="<?php echo esc_attr(CB_Style_Manager::get_setting('container_max_width', '1200px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Maximum width of the booking widget (e.g., 1200px, 75rem)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="sidebar-position"><?php _e('Sidebar Position', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <select id="sidebar-position" name="sidebar_position">
                        <?php 
                        $sidebar_positions = CB_Style_Manager::get_sidebar_positions();
                        $current_position = CB_Style_Manager::get_setting('sidebar_position', 'right');
                        ?>
                        <?php foreach ($sidebar_positions as $position => $label): ?>
                            <option value="<?php echo esc_attr($position); ?>" <?php selected($current_position, $position); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Position of the checkout sidebar', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="step-indicator-style"><?php _e('Step Indicator Style', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <select id="step-indicator-style" name="step_indicator_style">
                        <?php 
                        $step_styles = CB_Style_Manager::get_step_indicator_styles();
                        $current_style = CB_Style_Manager::get_setting('step_indicator_style', 'numbered');
                        ?>
                        <?php foreach ($step_styles as $style => $label): ?>
                            <option value="<?php echo esc_attr($style); ?>" <?php selected($current_style, $style); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Style of step indicators', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="mobile-breakpoint"><?php _e('Mobile Breakpoint', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="mobile-breakpoint" name="mobile_breakpoint" value="<?php echo esc_attr(CB_Style_Manager::get_setting('mobile_breakpoint', '768px')); ?>" class="regular-text">
                    <p class="description"><?php _e('Mobile breakpoint for responsive design (e.g., 768px)', 'cleaning-booking'); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Language Settings Section -->
    <div class="cb-admin-section">
        <h2><?php _e('Language Settings', 'cleaning-booking'); ?></h2>
        <p class="description"><?php _e('Configure language detection and default language settings.', 'cleaning-booking'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default-language"><?php _e('Default Language', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <select id="default-language" name="default_language">
                        <?php 
                        $current_language = CB_Style_Manager::get_setting('default_language', 'el');
                        ?>
                        <option value="en" <?php selected($current_language, 'en'); ?>><?php _e('English', 'cleaning-booking'); ?></option>
                        <option value="el" <?php selected($current_language, 'el'); ?>><?php _e('Greek', 'cleaning-booking'); ?></option>
                    </select>
                    <p class="description"><?php _e('Default language for the booking form', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Language Detection', 'cleaning-booking'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="auto-detect-language" name="auto_detect_language" value="1" <?php checked(CB_Style_Manager::get_setting('auto_detect_language', '1'), '1'); ?>>
                        <?php _e('Automatically detect user language from browser', 'cleaning-booking'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, the system will try to detect the user\'s preferred language from their browser settings.', 'cleaning-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Geolocation Detection', 'cleaning-booking'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="geolocation-detection" name="geolocation_detection" value="1" <?php checked(CB_Style_Manager::get_setting('geolocation_detection', '0'), '1'); ?>>
                        <?php _e('Use geolocation for language detection', 'cleaning-booking'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, the system will use the user\'s location to determine the appropriate language. This requires user permission.', 'cleaning-booking'); ?></p>
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

