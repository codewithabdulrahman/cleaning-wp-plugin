<?php
/**
 * Booking widget template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="cb-booking-widget" class="cb-widget cb-theme-<?php echo esc_attr($atts['theme']); ?>">
    <div class="cb-container">
        <div class="cb-header">
            <h2 class="cb-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php if ($atts['show_steps'] === 'true'): ?>
                <div class="cb-steps">
                    <div class="cb-step cb-step-active" data-step="1">
                        <span class="cb-step-number">1</span>
                        <span class="cb-step-label"><?php _e('Location', 'cleaning-booking'); ?></span>
                    </div>
                    <div class="cb-step" data-step="2">
                        <span class="cb-step-number">2</span>
                        <span class="cb-step-label"><?php _e('Service', 'cleaning-booking'); ?></span>
                    </div>
                    <div class="cb-step" data-step="3">
                        <span class="cb-step-number">3</span>
                        <span class="cb-step-label"><?php _e('Details', 'cleaning-booking'); ?></span>
                    </div>
                    <div class="cb-step" data-step="4">
                        <span class="cb-step-number">4</span>
                        <span class="cb-step-label"><?php _e('Date & Time', 'cleaning-booking'); ?></span>
                    </div>
                    <div class="cb-step" data-step="5">
                        <span class="cb-step-number">5</span>
                        <span class="cb-step-label"><?php _e('Checkout', 'cleaning-booking'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="cb-content">
            <form id="cb-booking-form" class="cb-form">
                <!-- Step 1: ZIP Code Check -->
                <div class="cb-step-content cb-step-1 cb-step-active">
                    <div class="cb-step-header">
                        <h3><?php _e('Enter Your ZIP Code', 'cleaning-booking'); ?></h3>
                        <p><?php _e('Let us know your location to check availability', 'cleaning-booking'); ?></p>
                    </div>
                    
                    <div class="cb-form-group">
                        <label for="cb-zip-code"><?php _e('ZIP Code', 'cleaning-booking'); ?></label>
                        <input type="text" id="cb-zip-code" name="zip_code" class="cb-input" placeholder="12345" maxlength="10" required>
                        <div class="cb-error-message" id="cb-zip-error"></div>
                    </div>
                    
                    <div class="cb-form-actions">
                        <button type="button" class="cb-btn cb-btn-primary cb-next-step" data-next="2">
                            <?php _e('Check Availability', 'cleaning-booking'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Service Selection -->
                <div class="cb-step-content cb-step-2">
                    <div class="cb-step-header">
                        <h3><?php _e('Select Your Service', 'cleaning-booking'); ?></h3>
                        <p><?php _e('Choose the type of cleaning service you need', 'cleaning-booking'); ?></p>
                    </div>
                    
                    <div class="cb-services-grid" id="cb-services-container">
                        <div class="cb-loading"><?php _e('Loading services...', 'cleaning-booking'); ?></div>
                    </div>
                    
                    <div class="cb-form-actions">
                        <button type="button" class="cb-btn cb-btn-secondary cb-prev-step" data-prev="1">
                            <?php _e('Back', 'cleaning-booking'); ?>
                        </button>
                        <button type="button" class="cb-btn cb-btn-primary cb-next-step" data-next="3" disabled>
                            <?php _e('Continue', 'cleaning-booking'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Square Meters & Extras -->
                <div class="cb-step-content cb-step-3">
                    <div class="cb-step-header">
                        <h3><?php _e('Service Details', 'cleaning-booking'); ?></h3>
                        <p><?php _e('Tell us about your space and any additional services', 'cleaning-booking'); ?></p>
                    </div>
                    
                    <div class="cb-form-group">
                        <label for="cb-square-meters"><?php _e('Square Meters', 'cleaning-booking'); ?></label>
                        <input type="number" id="cb-square-meters" name="square_meters" class="cb-input" min="1" max="1000" placeholder="50" required>
                        <div class="cb-error-message" id="cb-sqm-error"></div>
                    </div>
                    
                    <div class="cb-form-group">
                        <label><?php _e('Additional Services', 'cleaning-booking'); ?></label>
                        <div class="cb-extras-grid" id="cb-extras-container">
                            <div class="cb-loading"><?php _e('Loading extras...', 'cleaning-booking'); ?></div>
                        </div>
                    </div>
                    
                    <div class="cb-pricing-summary" id="cb-pricing-summary" style="display: none;">
                        <h4><?php _e('Price Summary', 'cleaning-booking'); ?></h4>
                        <div class="cb-price-breakdown">
                            <div class="cb-price-item">
                                <span class="cb-price-label"><?php _e('Base Price', 'cleaning-booking'); ?></span>
                                <span class="cb-price-value" id="cb-base-price">$0.00</span>
                            </div>
                            <div class="cb-price-item">
                                <span class="cb-price-label"><?php _e('Square Meters', 'cleaning-booking'); ?></span>
                                <span class="cb-price-value" id="cb-sqm-price">$0.00</span>
                            </div>
                            <div class="cb-price-item">
                                <span class="cb-price-label"><?php _e('Extras', 'cleaning-booking'); ?></span>
                                <span class="cb-price-value" id="cb-extras-price">$0.00</span>
                            </div>
                            <div class="cb-price-item">
                                <span class="cb-price-label"><?php _e('ZIP Surcharge', 'cleaning-booking'); ?></span>
                                <span class="cb-price-value" id="cb-zip-surcharge">$0.00</span>
                            </div>
                            <div class="cb-price-item cb-price-total">
                                <span class="cb-price-label"><?php _e('Total', 'cleaning-booking'); ?></span>
                                <span class="cb-price-value" id="cb-total-price">$0.00</span>
                            </div>
                        </div>
                        <div class="cb-duration-info">
                            <span><?php _e('Estimated Duration:', 'cleaning-booking'); ?></span>
                            <span id="cb-total-duration">0</span> <?php _e('minutes', 'cleaning-booking'); ?>
                        </div>
                    </div>
                    
                    <div class="cb-form-actions">
                        <button type="button" class="cb-btn cb-btn-secondary cb-prev-step" data-prev="2">
                            <?php _e('Back', 'cleaning-booking'); ?>
                        </button>
                        <button type="button" class="cb-btn cb-btn-primary cb-next-step" data-next="4" disabled>
                            <?php _e('Continue', 'cleaning-booking'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Step 4: Date & Time Selection -->
                <div class="cb-step-content cb-step-4">
                    <div class="cb-step-header">
                        <h3><?php _e('Select Date & Time', 'cleaning-booking'); ?></h3>
                        <p><?php _e('Choose your preferred date and time slot', 'cleaning-booking'); ?></p>
                    </div>
                    
                    <div class="cb-form-group">
                        <label for="cb-booking-date"><?php _e('Date', 'cleaning-booking'); ?></label>
                        <input type="date" id="cb-booking-date" name="booking_date" class="cb-input" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                        <div class="cb-error-message" id="cb-date-error"></div>
                    </div>
                    
                    <div class="cb-form-group">
                        <label><?php _e('Available Time Slots', 'cleaning-booking'); ?></label>
                        <div class="cb-time-slots" id="cb-time-slots">
                            <div class="cb-loading"><?php _e('Select a date to see available times', 'cleaning-booking'); ?></div>
                        </div>
                        <div class="cb-error-message" id="cb-time-error"></div>
                    </div>
                    
                    <div class="cb-form-actions">
                        <button type="button" class="cb-btn cb-btn-secondary cb-prev-step" data-prev="3">
                            <?php _e('Back', 'cleaning-booking'); ?>
                        </button>
                        <button type="button" class="cb-btn cb-btn-primary cb-next-step" data-next="5" disabled>
                            <?php _e('Continue', 'cleaning-booking'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Step 5: Customer Details & Checkout -->
                <div class="cb-step-content cb-step-5">
                    <div class="cb-step-header">
                        <h3><?php _e('Your Details', 'cleaning-booking'); ?></h3>
                        <p><?php _e('Please provide your contact information to complete the booking', 'cleaning-booking'); ?></p>
                    </div>
                    
                    <div class="cb-form-row">
                        <div class="cb-form-group">
                            <label for="cb-customer-name"><?php _e('Full Name', 'cleaning-booking'); ?> *</label>
                            <input type="text" id="cb-customer-name" name="customer_name" class="cb-input" required>
                            <div class="cb-error-message" id="cb-name-error"></div>
                        </div>
                        
                        <div class="cb-form-group">
                            <label for="cb-customer-email"><?php _e('Email Address', 'cleaning-booking'); ?> *</label>
                            <input type="email" id="cb-customer-email" name="customer_email" class="cb-input" required>
                            <div class="cb-error-message" id="cb-email-error"></div>
                        </div>
                    </div>
                    
                    <div class="cb-form-row">
                        <div class="cb-form-group">
                            <label for="cb-customer-phone"><?php _e('Phone Number', 'cleaning-booking'); ?> *</label>
                            <input type="tel" id="cb-customer-phone" name="customer_phone" class="cb-input" required>
                            <div class="cb-error-message" id="cb-phone-error"></div>
                        </div>
                        
                        <div class="cb-form-group">
                            <label for="cb-customer-address"><?php _e('Address', 'cleaning-booking'); ?></label>
                            <textarea id="cb-customer-address" name="address" class="cb-input" rows="3" placeholder="<?php _e('Street address, apartment, etc.', 'cleaning-booking'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="cb-form-group">
                        <label for="cb-notes"><?php _e('Special Instructions', 'cleaning-booking'); ?></label>
                        <textarea id="cb-notes" name="notes" class="cb-input" rows="3" placeholder="<?php _e('Any special requests or instructions...', 'cleaning-booking'); ?>"></textarea>
                    </div>
                    
                    <div class="cb-booking-summary">
                        <h4><?php _e('Booking Summary', 'cleaning-booking'); ?></h4>
                        <div class="cb-summary-item">
                            <span><?php _e('Service:', 'cleaning-booking'); ?></span>
                            <span id="cb-summary-service">-</span>
                        </div>
                        <div class="cb-summary-item">
                            <span><?php _e('Date:', 'cleaning-booking'); ?></span>
                            <span id="cb-summary-date">-</span>
                        </div>
                        <div class="cb-summary-item">
                            <span><?php _e('Time:', 'cleaning-booking'); ?></span>
                            <span id="cb-summary-time">-</span>
                        </div>
                        <div class="cb-summary-item">
                            <span><?php _e('Duration:', 'cleaning-booking'); ?></span>
                            <span id="cb-summary-duration">-</span>
                        </div>
                        <div class="cb-summary-item cb-summary-total">
                            <span><?php _e('Total Price:', 'cleaning-booking'); ?></span>
                            <span id="cb-summary-price">$0.00</span>
                        </div>
                    </div>
                    
                    <div class="cb-form-actions">
                        <button type="button" class="cb-btn cb-btn-secondary cb-prev-step" data-prev="4">
                            <?php _e('Back', 'cleaning-booking'); ?>
                        </button>
                        <button type="submit" class="cb-btn cb-btn-primary cb-btn-checkout">
                            <?php _e('Proceed to Checkout', 'cleaning-booking'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
