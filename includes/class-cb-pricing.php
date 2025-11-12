<?php
/**
 * Pricing calculation class for Cleaning Booking
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Pricing {
    
    /**
     * Calculate total price for a booking
     */
    public static function calculate_booking_price($service_id, $square_meters, $extras = array(), $zip_code = null) {
        global $wpdb;
        
        // Get service details
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_services WHERE id = %d AND is_active = 1",
            $service_id
        ));
        
        if (!$service) {
            return 0;
        }
        
        // Calculate base service price
        $base_price = floatval($service->base_price);
        $sqm_multiplier = floatval($service->sqm_multiplier);
        
        // Calculate price based on square meters
        $service_price = $base_price + ($square_meters * $sqm_multiplier);
        
        // Calculate extras price
        $extras_price = 0;
        if (!empty($extras)) {
            foreach ($extras as $extra_id) {
                $extra = $wpdb->get_row($wpdb->prepare(
                    "SELECT price FROM {$wpdb->prefix}cb_service_extras WHERE id = %d AND is_active = 1",
                    $extra_id
                ));
                
                if ($extra) {
                    $extras_price += floatval($extra->price);
                }
            }
        }
        
        // Calculate zip code surcharge
        $zip_surcharge = self::calculate_zip_surcharge($zip_code, $service_price + $extras_price);
        
        // Calculate total price
        $total_price = $service_price + $extras_price + $zip_surcharge;
        
        return round($total_price, 2);
    }
    
    /**
     * Calculate total duration for a booking
     */
    public static function calculate_booking_duration($service_id, $square_meters, $extras = array()) {
        global $wpdb;
        
        // Get service details
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_services WHERE id = %d AND is_active = 1",
            $service_id
        ));
        
        if (!$service) {
            return 60; // Default duration
        }
        
        // Calculate base duration
        $base_duration = intval($service->base_duration);
        $sqm_duration_multiplier = floatval($service->sqm_duration_multiplier);
        
        // Calculate duration based on square meters
        $service_duration = $base_duration + ($square_meters * $sqm_duration_multiplier);
        
        // Calculate extras duration
        $extras_duration = 0;
        if (!empty($extras)) {
            foreach ($extras as $extra_id) {
                $extra = $wpdb->get_row($wpdb->prepare(
                    "SELECT duration FROM {$wpdb->prefix}cb_service_extras WHERE id = %d AND is_active = 1",
                    $extra_id
                ));
                
                if ($extra) {
                    $extras_duration += intval($extra->duration);
                }
            }
        }
        
        // Calculate total duration
        $total_duration = $service_duration + $extras_duration;
        
        return max($total_duration, 30); // Minimum 30 minutes
    }
    
    /**
     * Calculate zip code surcharge
     */
    public static function calculate_zip_surcharge($zip_code, $base_price) {
        if (empty($zip_code)) {
            return 0;
        }
        
        global $wpdb;
        
        // Check if the zip surcharges table exists
        $table_name = $wpdb->prefix . 'cb_zip_codes';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, return 0 surcharge
            return 0;
        }
        
        $zip_surcharge = $wpdb->get_var($wpdb->prepare(
            "SELECT surcharge FROM {$wpdb->prefix}cb_zip_codes WHERE zip_code = %s AND is_active = 1",
            $zip_code
        ));
        
        if ($zip_surcharge) {
            return floatval($zip_surcharge);
        }
        
        return 0;
    }
    
    /**
     * Get pricing breakdown for display
     */
    public static function get_pricing_breakdown($service_id, $square_meters, $extras = array(), $zip_code = null) {
        global $wpdb;
        
        $breakdown = array(
            'service_price' => 0,
            'extras_price' => 0,
            'zip_surcharge' => 0,
            'total_price' => 0,
            'service_duration' => 0,
            'extras_duration' => 0,
            'total_duration' => 0
        );
        
        // Get service details
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_services WHERE id = %d AND is_active = 1",
            $service_id
        ));
        
        if (!$service) {
            return $breakdown;
        }
        
        // Calculate service price
        $base_price = floatval($service->base_price);
        $sqm_multiplier = floatval($service->sqm_multiplier);
        $service_price_before_discount = $base_price + ($square_meters * $sqm_multiplier);
        
        // Apply service discount if available
        if (!empty($service->discount) && floatval($service->discount) > 0) {
            $discount_percent = floatval($service->discount);
            $breakdown['service_price'] = $service_price_before_discount * (1 - $discount_percent / 100);
            $breakdown['service_discount'] = $discount_percent;
            $breakdown['service_original_price'] = $service_price_before_discount;
        } else {
            $breakdown['service_price'] = $service_price_before_discount;
        }
        
        // Calculate service duration
        $base_duration = intval($service->base_duration);
        $sqm_duration_multiplier = floatval($service->sqm_duration_multiplier);
        $breakdown['service_duration'] = $base_duration + ($square_meters * $sqm_duration_multiplier);
        
        // Calculate extras
        $extras_data = isset($extras['extras']) && is_array($extras['extras']) ? $extras['extras'] : array();
        $extra_spaces = isset($extras['extra_spaces']) && is_array($extras['extra_spaces']) ? $extras['extra_spaces'] : array();
        
        if (!empty($extras_data)) {
            foreach ($extras_data as $extra_id) {
                $extra = $wpdb->get_row($wpdb->prepare(
                    "SELECT price, duration, pricing_type, price_per_sqm, duration_per_sqm, discount FROM {$wpdb->prefix}cb_extras WHERE id = %d AND is_active = 1",
                    $extra_id
                ));
                
                if ($extra) {
                    $extra_price_before_discount = 0;
                    
                    // Check if this extra is charged per m²
                    if ($extra->pricing_type === 'per_sqm' && !empty($extra->price_per_sqm)) {
                        // Get the space for this extra (if provided)
                        $extra_space = isset($extra_spaces[$extra_id]) ? floatval($extra_spaces[$extra_id]) : 0;
                        $extra_price_before_discount = floatval($extra->price_per_sqm) * $extra_space;
                        
                        // Calculate duration based on space if duration_per_sqm is set
                        if (!empty($extra->duration_per_sqm)) {
                            $extra_duration = intval($extra->duration_per_sqm) * $extra_space;
                            $breakdown['extras_duration'] += $extra_duration;
                        }
                    } else {
                        // Fixed price and duration
                        $extra_price_before_discount = floatval($extra->price);
                        $breakdown['extras_duration'] += intval($extra->duration);
                    }
                    
                    // Apply extra discount if available
                    if (!empty($extra->discount) && floatval($extra->discount) > 0) {
                        $discount_percent = floatval($extra->discount);
                        $extra_price = $extra_price_before_discount * (1 - $discount_percent / 100);
                        $breakdown['extras_price'] += $extra_price;
                    } else {
                        $breakdown['extras_price'] += $extra_price_before_discount;
                    }
                }
            }
        }
        
        // Calculate zip surcharge
        $breakdown['zip_surcharge'] = self::calculate_zip_surcharge($zip_code, $breakdown['service_price'] + $breakdown['extras_price']);
        
        // Calculate totals
        $breakdown['total_price'] = $breakdown['service_price'] + $breakdown['extras_price'] + $breakdown['zip_surcharge'];
        $breakdown['total_duration'] = max($breakdown['service_duration'] + $breakdown['extras_duration'], 30);
        
        return $breakdown;
    }
    
    /**
     * Format price for display
     */
    public static function format_price($price) {
        return '$' . number_format($price, 2);
    }
    
    /**
     * Format duration for display
     */
    public static function format_duration($minutes) {
        // Convert to integer to avoid precision issues
        $minutes = (int) round($minutes);
        
        if ($minutes < 60) {
            return $minutes . ' min';
        } else {
            $hours = floor($minutes / 60);
            $remaining_minutes = $minutes % 60;
            
            if ($remaining_minutes == 0) {
                return $hours . ' hour' . ($hours > 1 ? 's' : '');
            } else {
                return $hours . 'h ' . $remaining_minutes . 'm';
            }
        }
    }
}
