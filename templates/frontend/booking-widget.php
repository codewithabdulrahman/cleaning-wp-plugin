<?php
/**
 * Booking widget template - Modern Design
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cb-widget-container">
    <div id="cb-booking-widget" class="cb-widget cb-theme-<?php echo esc_attr($atts['theme']); ?>">
        <div class="cb-container">
        <div class="cb-header">
            <div class="cb-header-top">
                <h1 class="cb-title"><?php echo esc_html($atts['title']); ?></h1>
                        <div class="cb-language-widget">
                            <select class="cb-language-selector">
                                <option value="en">English</option>
                                <option value="el">Ελληνικά</option>
                            </select>
                        </div>
            </div>
            <?php if ($atts['show_steps']): ?>
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
            <div class="cb-main-content">
                <form id="cb-booking-form" class="cb-form">
                    <!-- Step 1: ZIP Code Check -->
                    <div class="cb-step-content cb-step-1 cb-step-active">
                        <div class="cb-step-header">
                            <h2><?php _e('Enter Your ZIP Code', 'cleaning-booking'); ?></h2>
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
                            <h2><?php _e('Select Your Service', 'cleaning-booking'); ?></h2>
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
                            <h2><?php _e('Service Details', 'cleaning-booking'); ?></h2>
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
                        
                        <!-- Price & Duration Display for Step 3 -->
                        <div class="cb-price-summary cb-step-3-price">
                            <div class="cb-price-breakdown">
                                <div class="cb-price-item">
                                    <span><?php _e('Service Price:', 'cleaning-booking'); ?></span>
                                    <span id="cb-step3-service-price">$0.00</span>
                                </div>
                                <div class="cb-price-item">
                                    <span><?php _e('Extras:', 'cleaning-booking'); ?></span>
                                    <span id="cb-step3-extras-price">$0.00</span>
                                </div>
                                <div class="cb-price-item cb-price-total">
                                    <span><?php _e('Total:', 'cleaning-booking'); ?></span>
                                    <span id="cb-step3-total-price">$0.00</span>
                                </div>
                            </div>
                            <div class="cb-duration-breakdown">
                                <div class="cb-duration-item">
                                    <span><?php _e('Service Duration:', 'cleaning-booking'); ?></span>
                                    <span id="cb-step3-service-duration">0h 0m</span>
                                </div>
                                <div class="cb-duration-item">
                                    <span><?php _e('Extras Duration:', 'cleaning-booking'); ?></span>
                                    <span id="cb-step3-extras-duration">0h 0m</span>
                                </div>
                                <div class="cb-duration-item cb-duration-total">
                                    <span><?php _e('Total Duration:', 'cleaning-booking'); ?></span>
                                    <span id="cb-step3-total-duration">0h 0m</span>
                                </div>
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
                            <h2><?php _e('Select Date & Time', 'cleaning-booking'); ?></h2>
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
                            <h2><?php _e('Your Details', 'cleaning-booking'); ?></h2>
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
                        
                        <!-- Payment Method Selection -->
                        <div class="cb-form-group">
                            <label><?php _e('Select Payment Method', 'cleaning-booking'); ?></label>
                            <div class="cb-payment-methods">
                                <div class="cb-payment-option" data-payment="cash">
                                    <div class="cb-payment-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 8H22V16H2V8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M4 12H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M18 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div class="cb-payment-info">
                                        <h4><?php _e('Cash on Delivery', 'cleaning-booking'); ?></h4>
                                        <p><?php _e('Pay when service is completed', 'cleaning-booking'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="cb-payment-option" data-payment="online">
                                    <div class="cb-payment-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                            <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </div>
                                    <div class="cb-payment-info">
                                        <h4><?php _e('Online Payment', 'cleaning-booking'); ?></h4>
                                        <p><?php _e('Card / Apple Pay / Google Pay', 'cleaning-booking'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="cb-booking-summary">
                            <h4><?php _e('Booking Summary', 'cleaning-booking'); ?></h4>
                            <div class="cb-summary-item">
                                <span><?php _e('Service:', 'cleaning-booking'); ?></span>
                                <span id="cb-summary-service" class="cb-summary-value">-</span>
                            </div>
                            <div class="cb-summary-item">
                                <span><?php _e('Date:', 'cleaning-booking'); ?></span>
                                <span id="cb-summary-date" class="cb-summary-value">-</span>
                            </div>
                            <div class="cb-summary-item">
                                <span><?php _e('Time:', 'cleaning-booking'); ?></span>
                                <span id="cb-summary-time" class="cb-summary-value">-</span>
                            </div>
                            <div class="cb-summary-item">
                                <span><?php _e('Duration:', 'cleaning-booking'); ?></span>
                                <span id="cb-summary-duration" class="cb-summary-value">-</span>
                            </div>
                            <div class="cb-summary-item cb-summary-total">
                                <span><?php _e('Pricing:', 'cleaning-booking'); ?></span>
                                <span id="cb-summary-price" class="cb-summary-value">$0.00</span>
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
            
            <!-- Checkout Sidebar -->
            <div class="cb-checkout-sidebar">
                <div class="cb-order-summary">
                    <h3 id="cb-sidebar-service-title"><?php _e('Select a service to see pricing', 'cleaning-booking'); ?></h3>
                    
                    <div class="cb-info-banner">
                        <div class="cb-info-icon">🧹</div>
                        <div class="cb-info-text">
                            <?php _e('Our contractors have all the necessary cleaning products and equipment', 'cleaning-booking'); ?>
                        </div>
                    </div>
                    
                    <div class="cb-duration-section">
                        <span class="cb-duration-label"><?php _e('Approximate working time', 'cleaning-booking'); ?></span>
                        <span class="cb-duration-value" id="cb-sidebar-duration">-<?php _e('hours', 'cleaning-booking'); ?></span>
                    </div>
                    
                    <div class="cb-selected-extras-section" id="cb-selected-extras" style="display: none;">
                        <span class="cb-selected-extras-label"><?php _e('Selected Services:', 'cleaning-booking'); ?></span>
                        <div class="cb-selected-extras-list" id="cb-selected-extras-list"></div>
                    </div>
                    
                    <div class="cb-price-section">
                        <div class="cb-price-label"><?php _e('Pricing:', 'cleaning-booking'); ?></div>
                        <div class="cb-price-display">
                            <span class="cb-price-current" id="cb-sidebar-total">Price will be calculated</span>
                        </div>
                    </div>
                    
                    <button type="button" class="cb-btn cb-btn-checkout" disabled id="cb-sidebar-checkout">
                        <span id="cb-checkout-price">$0.00</span> - <?php _e('Proceed to Checkout', 'cleaning-booking'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>