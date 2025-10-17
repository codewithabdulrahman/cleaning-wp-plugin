<?php
/**
 * Booking widget template - Modern Design
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cb-widget-container">
    <div id="cb-booking-widget" class="cb-widget">
        <div class="cb-container">
        <div class="cb-header">
            <div class="cb-header-top">
                <h1 class="cb-title"><?php echo esc_html($atts['title'] ?: CB_Frontend::get_dynamic_text('cb_widget_title', 'Book Your Cleaning Service')); ?></h1>
        <div class="cb-header-controls">
            <div class="cb-language-widget">
                <select class="cb-language-selector">
                    <option value="en">English</option>
                    <option value="el" selected>Ελληνικά</option>
                </select>
            </div>
        </div>
            </div>
            <?php if ($atts['show_steps']): ?>
                <div class="cb-steps">
                    <div class="cb-step cb-step-active" data-step="1">
                        <span class="cb-step-number">1</span>
                        <span class="cb-step-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step1_label', 'Location')); ?></span>
                    </div>
                    <div class="cb-step" data-step="2">
                        <span class="cb-step-number">2</span>
                        <span class="cb-step-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step2_label', 'Service')); ?></span>
                    </div>
                    <div class="cb-step" data-step="3">
                        <span class="cb-step-number">3</span>
                        <span class="cb-step-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step3_label', 'Details')); ?></span>
                    </div>
                    <div class="cb-step" data-step="4">
                        <span class="cb-step-number">4</span>
                        <span class="cb-step-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step4_label', 'Date & Time')); ?></span>
                    </div>
                    <div class="cb-step" data-step="5">
                        <span class="cb-step-number">5</span>
                        <span class="cb-step-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step5_label', 'Checkout')); ?></span>
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
                            <h2><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step1_title', 'Enter Your ZIP Code')); ?></h2>
                            <p><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step1_description', 'Let us know your location to check availability')); ?></p>
                        </div>
                        
                        <div class="cb-form-group">
                            <label for="cb-zip-code"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_zip_code_label', 'ZIP Code')); ?></label>
                            <input type="text" id="cb-zip-code" name="zip_code" class="cb-input" placeholder="12345" maxlength="10" required>
                            <div class="cb-error-message" id="cb-zip-error"></div>
                        </div>
                        
                        <div class="cb-form-actions">
                            <button type="button" class="cb-btn cb-btn-primary cb-next-step" data-next="2">
                                <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_continue_button_text', 'Check Availability')); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Service Selection -->
                    <div class="cb-step-content cb-step-2">
                        <div class="cb-step-header">
                            <h2><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step2_title', 'Select Your Service')); ?></h2>
                            <p><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step2_description', 'Choose the type of cleaning service you need')); ?></p>
                        </div>
                        
                        <div class="cb-services-grid" id="cb-services-container">
                            <div class="cb-loading"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_loading_services_text', 'Loading services...')); ?></div>
                        </div>
                        
                        <div class="cb-form-actions">
                            <button type="button" class="cb-btn cb-btn-secondary cb-prev-step" data-prev="1">
                                <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_back_button_text', 'Back')); ?>
                            </button>
                            <button type="button" class="cb-btn cb-btn-primary cb-next-step" data-next="3" disabled>
                                <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_continue_button_text', 'Continue')); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Square Meters & Extras -->
                    <div class="cb-step-content cb-step-3">
                        <div class="cb-step-header">
                            <h2><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step3_title', 'Service Details')); ?></h2>
                            <p><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step3_description', 'Tell us about your space and any additional services')); ?></p>
                        </div>
                        
                        <div class="cb-form-group">
                            <label for="cb-square-meters"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_space_label', 'Space (m²)')); ?></label>
                            <input type="number" id="cb-square-meters" name="square_meters" class="cb-input" min="0" max="1000" placeholder="0" value="0">
                            <div class="cb-field-hint" id="cb-sqm-hint" style="display: none;">
                                <small><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_space_hint_text', 'Enter additional space beyond the default area, or leave as 0 to skip')); ?></small>
                            </div>
                            <div class="cb-base-area-info" id="cb-base-area-info" style="display: none;">
                                <div class="cb-base-info-card">
                                    <h4><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_base_service_included', 'Base Service Included')); ?></h4>
                                    <p id="cb-base-area-message"></p>
                                    <div class="cb-base-pricing">
                                        <span id="cb-base-price-display"></span>
                                        <span id="cb-base-duration-display"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="cb-error-message" id="cb-sqm-error"></div>
                        </div>
                        
                        <div class="cb-form-group">
                            <label><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_additional_services_label', 'Additional Services')); ?></label>
                            <div class="cb-extras-grid" id="cb-extras-container">
                                <div class="cb-loading"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_loading_extras_text', 'Loading extras...')); ?></div>
                            </div>
                        </div>
                        
                        <!-- Price & Duration Display for Step 3 -->
                        <div class="cb-price-summary cb-step-3-price">
                            <div class="cb-price-breakdown">
                                <div class="cb-price-item">
                                    <span><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_service_price_text', 'Service Price:')); ?></span>
                                    <span id="cb-step3-service-price">€0.00</span>
                                </div>
                                <div class="cb-price-item">
                                    <span><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_extras_text', 'Extras:')); ?></span>
                                    <span id="cb-step3-extras-price">€0.00</span>
                                </div>
                                <div class="cb-price-item cb-price-total">
                                    <span><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_total_text', 'Total:')); ?></span>
                                    <span id="cb-step3-total-price">€0.00</span>
                                </div>
                            </div>
                            <div class="cb-duration-breakdown">
                                <div class="cb-duration-item">
                                    <span><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_service_duration_text', 'Service Duration:')); ?></span>
                                    <span id="cb-step3-service-duration">0h 0m</span>
                                </div>
                                <div class="cb-duration-item">
                                    <span><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_extras_duration_text', 'Extras Duration:')); ?></span>
                                    <span id="cb-step3-extras-duration">0h 0m</span>
                                </div>
                                <div class="cb-duration-item cb-duration-total">
                                    <span><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_total_duration_text', 'Total Duration:')); ?></span>
                                    <span id="cb-step3-total-duration">0h 0m</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="cb-form-actions">
                            <button type="button" class="cb-btn cb-btn-secondary cb-prev-step" data-prev="2">
                                <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_back_button_text', 'Back')); ?>
                            </button>
                            <button type="button" class="cb-btn cb-btn-primary cb-next-step" data-next="4" disabled>
                                <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_continue_button_text', 'Continue')); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 4: Date & Time Selection -->
                    <div class="cb-step-content cb-step-4">
                        <div class="cb-step-header">
                            <h2><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step4_title', 'Select Date & Time')); ?></h2>
                            <p><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_step4_description', 'Choose your preferred date and time slot')); ?></p>
                        </div>
                        
                        <div class="cb-form-group">
                            <label for="cb-booking-date"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_date_label', 'Date')); ?></label>
                            <input type="date" id="cb-booking-date" name="booking_date" class="cb-input" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                            <div class="cb-error-message" id="cb-date-error"></div>
                        </div>
                        
                        <div class="cb-form-group">
                            <label><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_time_slots_label', 'Available Time Slots')); ?></label>
                            <div class="cb-time-slots" id="cb-time-slots">
                                <div class="cb-loading"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_select_date_hint_text', 'Select a date to see available times')); ?></div>
                            </div>
                            <div class="cb-error-message" id="cb-time-error"></div>
                        </div>
                        
                        <div class="cb-form-actions">
                            <button type="button" class="cb-btn cb-btn-secondary cb-prev-step" data-prev="3">
                                <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_back_button_text', 'Back')); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Checkout Sidebar -->
            <div class="cb-checkout-sidebar">
                <div class="cb-order-summary">
                    <h3 id="cb-sidebar-service-title"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_sidebar_service_title', 'Select a service to see pricing')); ?></h3>
                    
                    <div class="cb-info-banner">
                        <div class="cb-info-icon">🧹</div>
                        <div class="cb-info-text">
                            <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_sidebar_info_text', 'Our contractors have all the necessary cleaning products and equipment')); ?>
                        </div>
                    </div>
                    
                    <div class="cb-duration-section">
                        <span class="cb-duration-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_working_time_label', 'Approximate working time')); ?></span>
                        <span class="cb-duration-value" id="cb-sidebar-duration">-<?php echo esc_html(CB_Frontend::get_dynamic_text('cb_minutes_greek_text', 'λεπτά')); ?></span>
                    </div>
                    
                    <div class="cb-selected-extras-section" id="cb-selected-extras" style="display: none;">
                        <span class="cb-selected-extras-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_selected_services_label', 'Selected Services:')); ?></span>
                        <div class="cb-selected-extras-list" id="cb-selected-extras-list"></div>
                    </div>
                    
                    <div class="cb-price-section">
                        <div class="cb-price-label"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_pricing_label', 'Pricing:')); ?></div>
                        <div class="cb-price-display">
                            <span class="cb-price-current" id="cb-sidebar-total"><?php echo esc_html(CB_Frontend::get_dynamic_text('cb_price_calculated_text', 'Price will be calculated')); ?></span>
                        </div>
                    </div>
                    
                    <button type="button" class="cb-btn cb-btn-checkout" disabled id="cb-sidebar-checkout">
                        <span id="cb-checkout-price">€0.00</span> - <?php echo esc_html(CB_Frontend::get_dynamic_text('cb_checkout_button_text', 'Proceed to Checkout')); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>