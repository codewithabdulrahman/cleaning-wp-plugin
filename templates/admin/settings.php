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

<style>
.cb-admin-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin: 30px 0;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.cb-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f1;
}

.cb-section-title h2 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 24px;
    font-weight: 600;
}

.cb-section-title .description {
    margin: 0;
    color: #646970;
    font-size: 14px;
    line-height: 1.5;
}

.cb-section-preview {
    text-align: center;
    min-width: 300px;
}

.cb-preview-placeholder {
    border: 2px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}

.cb-preview-caption {
    margin-top: 10px;
    font-size: 12px;
    color: #646970;
    font-style: italic;
}

.color-picker {
    width: 60px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.form-table th {
    width: 200px;
    font-weight: 600;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"],
.form-table input[type="color"] {
    margin-right: 10px;
}

.description {
    display: block;
    margin-top: 5px;
    font-size: 13px;
    color: #646970;
}

/* Responsive Design */
@media (max-width: 768px) {
    .cb-section-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .cb-section-preview {
        margin-top: 20px;
        min-width: auto;
    }
    
    .cb-section-preview .cb-preview-placeholder {
        width: 100%;
        max-width: 300px;
    }
    
    .form-table th {
        width: auto;
    }
}
</style>

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
    
    <!-- Widget Title Section -->
    <div class="cb-admin-section">
        <div class="cb-section-header">
            <div class="cb-section-title">
                <h2><?php _e('Widget Title', 'cleaning-booking'); ?></h2>
                <p class="description"><?php _e('Customize the main title text, font size, and color of the booking widget.', 'cleaning-booking'); ?></p>
            </div>
            <div class="cb-section-preview">
                <img src="<?php echo plugins_url('assets/images/title-image.png', dirname(dirname(__FILE__))); ?>" alt="Booking Widget Preview" style="max-width: 300px; border: 2px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                <p class="cb-preview-caption"><?php _e('Preview: Booking widget with title customization', 'cleaning-booking'); ?></p>
            </div>
        </div>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="widget-title"><?php _e('Title Text', 'cleaning-booking'); ?></label>
                </th>
                <td>
                     <input type="text" id="widget-title" name="widget_title" value="<?php echo esc_attr(get_option('cb_widget_title', 'Book Your Cleaning Service')); ?>" class="regular-text">
                    <span class="description"><?php _e('Main title displayed at the top of the booking widget', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="title-font-size"><?php _e('Font Size', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="title-font-size" name="title_font_size" value="<?php echo esc_attr(get_option('cb_title_font_size', '24px')); ?>" class="regular-text">
                    <span class="description"><?php _e('Font size for the widget title (e.g., 24px, 1.5rem)', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="title-font-color"><?php _e('Font Color', 'cleaning-booking'); ?></label>
                </th>
                <td>
                    <input type="color" id="title-font-color" name="title_font_color" value="<?php echo esc_attr(get_option('cb_title_font_color', '#ffffff')); ?>" class="color-picker">
                    <span class="description"><?php _e('Color for the widget title', 'cleaning-booking'); ?></span>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Dynamic Text Content Section -->
    <div class="cb-admin-section">
        <h2><?php _e('Dynamic Text Content', 'cleaning-booking'); ?></h2>
        <p class="description"><?php _e('Customize the text content displayed in the booking widget. You can change titles, descriptions, and messages.', 'cleaning-booking'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="step1-title"><?php _e('Step 1 Title', 'cleaning-booking'); ?></label>
                </th>
                <td>
                     <input type="text" id="step1-title" name="step1_title" value="<?php echo esc_attr(get_option('cb_step1_title', 'Enter Your ZIP Code')); ?>" class="regular-text">
                    <span class="description"><?php _e('Title for the first step (ZIP code entry)', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="step1-description"><?php _e('Step 1 Description', 'cleaning-booking'); ?></label>
                </th>
                <td>
                     <textarea id="step1-description" name="step1_description" rows="3" class="large-text"><?php echo esc_textarea(get_option('cb_step1_description', 'Let us know your location to check availability')); ?></textarea>
                    <span class="description"><?php _e('Description text for step 1', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="step2-title"><?php _e('Step 2 Title', 'cleaning-booking'); ?></label>
                </th>
                <td>
                     <input type="text" id="step2-title" name="step2_title" value="<?php echo esc_attr(get_option('cb_step2_title', 'Select Your Service')); ?>" class="regular-text">
                    <span class="description"><?php _e('Title for the second step (service selection)', 'cleaning-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="step2-description"><?php _e('Step 2 Description', 'cleaning-booking'); ?></label>
                </th>
                <td>
                     <textarea id="step2-description" name="step2_description" rows="3" class="large-text"><?php echo esc_textarea(get_option('cb_step2_description', 'Choose the type of cleaning service you need')); ?></textarea>
                     <span class="description"><?php _e('Description text for step 2', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step3-title"><?php _e('Step 3 Title', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="step3-title" name="step3_title" value="<?php echo esc_attr(get_option('cb_step3_title', 'Service Details')); ?>" class="regular-text">
                     <span class="description"><?php _e('Title for the third step (service details)', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step3-description"><?php _e('Step 3 Description', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <textarea id="step3-description" name="step3_description" rows="3" class="large-text"><?php echo esc_textarea(get_option('cb_step3_description', 'Tell us about your space and any additional services')); ?></textarea>
                     <span class="description"><?php _e('Description text for step 3', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step4-title"><?php _e('Step 4 Title', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="step4-title" name="step4_title" value="<?php echo esc_attr(get_option('cb_step4_title', 'Select Date & Time')); ?>" class="regular-text">
                     <span class="description"><?php _e('Title for the fourth step (date and time selection)', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step4-description"><?php _e('Step 4 Description', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <textarea id="step4-description" name="step4_description" rows="3" class="large-text"><?php echo esc_textarea(get_option('cb_step4_description', 'Choose your preferred date and time slot')); ?></textarea>
                     <span class="description"><?php _e('Description text for step 4', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="sidebar-info-text"><?php _e('Sidebar Info Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <textarea id="sidebar-info-text" name="sidebar_info_text" rows="3" class="large-text"><?php echo esc_textarea(get_option('cb_sidebar_info_text', 'Our contractors have all the necessary cleaning products and equipment')); ?></textarea>
                     <span class="description"><?php _e('Information text displayed in the sidebar', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="checkout-button-text"><?php _e('Checkout Button Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="checkout-button-text" name="checkout_button_text" value="<?php echo esc_attr(get_option('cb_checkout_button_text', 'Proceed to Checkout')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text for the checkout button', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="continue-button-text"><?php _e('Continue Button Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="continue-button-text" name="continue_button_text" value="<?php echo esc_attr(get_option('cb_continue_button_text', 'Continue')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text for the continue button', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="back-button-text"><?php _e('Back Button Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="back-button-text" name="back_button_text" value="<?php echo esc_attr(get_option('cb_back_button_text', 'Back')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text for the back button', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step1-label"><?php _e('Step 1 Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="step1-label" name="step1_label" value="<?php echo esc_attr(get_option('cb_step1_label', 'Location')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for step 1 in the progress indicator', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step2-label"><?php _e('Step 2 Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="step2-label" name="step2_label" value="<?php echo esc_attr(get_option('cb_step2_label', 'Service')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for step 2 in the progress indicator', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step3-label"><?php _e('Step 3 Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="step3-label" name="step3_label" value="<?php echo esc_attr(get_option('cb_step3_label', 'Details')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for step 3 in the progress indicator', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step4-label"><?php _e('Step 4 Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="step4-label" name="step4_label" value="<?php echo esc_attr(get_option('cb_step4_label', 'Date & Time')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for step 4 in the progress indicator', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="step5-label"><?php _e('Step 5 Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="step5-label" name="step5_label" value="<?php echo esc_attr(get_option('cb_step5_label', 'Checkout')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for step 5 in the progress indicator', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="zip-code-label"><?php _e('ZIP Code Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="zip-code-label" name="zip_code_label" value="<?php echo esc_attr(get_option('cb_zip_code_label', 'ZIP Code')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for ZIP code field', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="space-label"><?php _e('Space Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="space-label" name="space_label" value="<?php echo esc_attr(get_option('cb_space_label', 'Space (m²)')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for space/square meters field', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="additional-services-label"><?php _e('Additional Services Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="additional-services-label" name="additional_services_label" value="<?php echo esc_attr(get_option('cb_additional_services_label', 'Additional Services')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for additional services section', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="date-label"><?php _e('Date Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="date-label" name="date_label" value="<?php echo esc_attr(get_option('cb_date_label', 'Date')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for date field', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="time-slots-label"><?php _e('Time Slots Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="time-slots-label" name="time_slots_label" value="<?php echo esc_attr(get_option('cb_time_slots_label', 'Available Time Slots')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for time slots section', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="sidebar-service-title"><?php _e('Sidebar Service Title', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="sidebar-service-title" name="sidebar_service_title" value="<?php echo esc_attr(get_option('cb_sidebar_service_title', 'Select a service to see pricing')); ?>" class="regular-text">
                     <span class="description"><?php _e('Title shown in sidebar when no service is selected', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="working-time-label"><?php _e('Working Time Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="working-time-label" name="working_time_label" value="<?php echo esc_attr(get_option('cb_working_time_label', 'Approximate working time')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for working time in sidebar', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="selected-services-label"><?php _e('Selected Services Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="selected-services-label" name="selected_services_label" value="<?php echo esc_attr(get_option('cb_selected_services_label', 'Selected Services:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for selected services in sidebar', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="pricing-label"><?php _e('Pricing Label', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="pricing-label" name="pricing_label" value="<?php echo esc_attr(get_option('cb_pricing_label', 'Pricing:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for pricing section in sidebar', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="price-calculated-text"><?php _e('Price Calculated Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="price-calculated-text" name="price_calculated_text" value="<?php echo esc_attr(get_option('cb_price_calculated_text', 'Price will be calculated')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text shown when price is not yet calculated', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="space-hint-text"><?php _e('Space Hint Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="space-hint-text" name="space_hint_text" value="<?php echo esc_attr(get_option('cb_space_hint_text', 'Enter additional space beyond the default area, or leave as 0 to skip')); ?>" class="regular-text">
                     <span class="description"><?php _e('Hint text for space field', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="base-service-included"><?php _e('Base Service Included Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="base-service-included" name="base_service_included" value="<?php echo esc_attr(get_option('cb_base_service_included', 'Base Service Included')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text for base service included section', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
         </table>
         
         <h3><?php _e('JavaScript Messages', 'cleaning-booking'); ?></h3>
         <p class="description"><?php _e('Customize error messages and notifications shown to users', 'cleaning-booking'); ?></p>
         
         <table class="form-table">
             <tr>
                 <th scope="row">
                     <label for="loading-text"><?php _e('Loading Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="loading-text" name="loading_text" value="<?php echo esc_attr(get_option('cb_loading_text', 'Loading...')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text shown while loading', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="loading-services-text"><?php _e('Loading Services Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="loading-services-text" name="loading_services_text" value="<?php echo esc_attr(get_option('cb_loading_services_text', 'Loading services...')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text shown while loading services', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="loading-extras-text"><?php _e('Loading Extras Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="loading-extras-text" name="loading_extras_text" value="<?php echo esc_attr(get_option('cb_loading_extras_text', 'Loading extras...')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text shown while loading extras', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="loading-slots-text"><?php _e('Loading Slots Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="loading-slots-text" name="loading_slots_text" value="<?php echo esc_attr(get_option('cb_loading_slots_text', 'Loading available times...')); ?>" class="regular-text">
                     <span class="description"><?php _e('Text shown while loading time slots', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="error-text"><?php _e('Error Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="error-text" name="error_text" value="<?php echo esc_attr(get_option('cb_error_text', 'An error occurred. Please try again.')); ?>" class="regular-text">
                     <span class="description"><?php _e('General error message', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="zip-required-text"><?php _e('ZIP Required Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="zip-required-text" name="zip_required_text" value="<?php echo esc_attr(get_option('cb_zip_required_text', 'Please enter a ZIP code.')); ?>" class="regular-text">
                     <span class="description"><?php _e('Message when ZIP code is required', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="zip-invalid-text"><?php _e('ZIP Invalid Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="zip-invalid-text" name="zip_invalid_text" value="<?php echo esc_attr(get_option('cb_zip_invalid_text', 'Please enter a valid 5-digit ZIP code.')); ?>" class="regular-text">
                     <span class="description"><?php _e('Message when ZIP code is invalid', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="zip-unavailable-text"><?php _e('ZIP Unavailable Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="zip-unavailable-text" name="zip_unavailable_text" value="<?php echo esc_attr(get_option('cb_zip_unavailable_text', 'Sorry, we don\'t provide services in this area yet.')); ?>" class="regular-text">
                     <span class="description"><?php _e('Message when service is not available in ZIP code', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="select-service-text"><?php _e('Select Service Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="select-service-text" name="select_service_text" value="<?php echo esc_attr(get_option('cb_select_service_text', 'Please select a service.')); ?>" class="regular-text">
                     <span class="description"><?php _e('Message when service selection is required', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="select-date-text"><?php _e('Select Date Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="select-date-text" name="select_date_text" value="<?php echo esc_attr(get_option('cb_select_date_text', 'Please select a date.')); ?>" class="regular-text">
                     <span class="description"><?php _e('Message when date selection is required', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="select-time-text"><?php _e('Select Time Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="select-time-text" name="select_time_text" value="<?php echo esc_attr(get_option('cb_select_time_text', 'Please select a time slot.')); ?>" class="regular-text">
                     <span class="description"><?php _e('Message when time selection is required', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="booking-success-text"><?php _e('Booking Success Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="booking-success-text" name="booking_success_text" value="<?php echo esc_attr(get_option('cb_booking_created_text', 'Booking created successfully!')); ?>" class="regular-text">
                     <span class="description"><?php _e('Message when booking is created successfully', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
         </table>
         
         <h3><?php _e('Price & Duration Labels', 'cleaning-booking'); ?></h3>
         <p class="description"><?php _e('Customize labels for pricing and duration sections', 'cleaning-booking'); ?></p>
         
         <table class="form-table">
             <tr>
                 <th scope="row">
                     <label for="service-price-text"><?php _e('Service Price Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="service-price-text" name="service_price_text" value="<?php echo esc_attr(get_option('cb_service_price_text', 'Service Price:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for service price', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="extras-text"><?php _e('Extras Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="extras-text" name="extras_text" value="<?php echo esc_attr(get_option('cb_extras_text', 'Extras:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for extras', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="total-text"><?php _e('Total Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="total-text" name="total_text" value="<?php echo esc_attr(get_option('cb_total_text', 'Total:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for total', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="service-duration-text"><?php _e('Service Duration Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="service-duration-text" name="service_duration_text" value="<?php echo esc_attr(get_option('cb_service_duration_text', 'Service Duration:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for service duration', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="extras-duration-text"><?php _e('Extras Duration Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="extras-duration-text" name="extras_duration_text" value="<?php echo esc_attr(get_option('cb_extras_duration_text', 'Extras Duration:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for extras duration', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="total-duration-text"><?php _e('Total Duration Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="total-duration-text" name="total_duration_text" value="<?php echo esc_attr(get_option('cb_total_duration_text', 'Total Duration:')); ?>" class="regular-text">
                     <span class="description"><?php _e('Label for total duration', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="select-date-hint-text"><?php _e('Select Date Hint Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="select-date-hint-text" name="select_date_hint_text" value="<?php echo esc_attr(get_option('cb_select_date_hint_text', 'Select a date to see available times')); ?>" class="regular-text">
                     <span class="description"><?php _e('Hint text for date selection', 'cleaning-booking'); ?></span>
                 </td>
             </tr>
             <tr>
                 <th scope="row">
                     <label for="minutes-greek-text"><?php _e('Minutes (Greek) Text', 'cleaning-booking'); ?></label>
                 </th>
                 <td>
                     <input type="text" id="minutes-greek-text" name="minutes_greek_text" value="<?php echo esc_attr(get_option('cb_minutes_greek_text', 'λεπτά')); ?>" class="regular-text">
                     <span class="description"><?php _e('Greek word for minutes', 'cleaning-booking'); ?></span>
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

