<?php
/**
 * Security and validation utilities for Cleaning Booking System
 * 
 * Provides secure data sanitization, validation, and nonce handling
 * to ensure the plugin follows WordPress security best practices.
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * CB_Security class
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */
class CB_Security {
    
    /**
     * Verify nonce for AJAX requests
     * 
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool True if nonce is valid, false otherwise
     */
    public static function verify_nonce($nonce, $action = 'cb_admin_nonce') {
        if (empty($nonce)) {
            return false;
        }
        
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Create nonce for AJAX requests
     * 
     * @param string $action Nonce action
     * @return string Nonce value
     */
    public static function create_nonce($action = 'cb_admin_nonce') {
        return wp_create_nonce($action);
    }
    
    /**
     * Sanitize text input
     * 
     * @param mixed $input Input to sanitize
     * @return string Sanitized text
     */
    public static function sanitize_text($input) {
        if (is_array($input)) {
            return array_map(array(__CLASS__, 'sanitize_text'), $input);
        }
        
        return sanitize_text_field($input);
    }
    
    /**
     * Sanitize textarea input
     * 
     * @param mixed $input Input to sanitize
     * @return string Sanitized textarea content
     */
    public static function sanitize_textarea($input) {
        if (is_array($input)) {
            return array_map(array(__CLASS__, 'sanitize_textarea'), $input);
        }
        
        return sanitize_textarea_field($input);
    }
    
    /**
     * Sanitize email input
     * 
     * @param string $input Email to sanitize
     * @return string Sanitized email
     */
    public static function sanitize_email($input) {
        return sanitize_email($input);
    }
    
    /**
     * Sanitize URL input
     * 
     * @param string $input URL to sanitize
     * @return string Sanitized URL
     */
    public static function sanitize_url($input) {
        return esc_url_raw($input);
    }
    
    /**
     * Sanitize integer input
     * 
     * @param mixed $input Input to sanitize
     * @return int Sanitized integer
     */
    public static function sanitize_int($input) {
        return intval($input);
    }
    
    /**
     * Sanitize float input
     * 
     * @param mixed $input Input to sanitize
     * @return float Sanitized float
     */
    public static function sanitize_float($input) {
        return floatval($input);
    }
    
    /**
     * Sanitize boolean input
     * 
     * @param mixed $input Input to sanitize
     * @return bool Sanitized boolean
     */
    public static function sanitize_bool($input) {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_email($email) {
        return is_email($email);
    }
    
    /**
     * Validate phone number (basic validation)
     * 
     * @param string $phone Phone number to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_phone($phone) {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check if it's a reasonable length (7-15 digits)
        return strlen($cleaned) >= 7 && strlen($cleaned) <= 15;
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string to validate
     * @param string $format Date format (default: Y-m-d)
     * @return bool True if valid, false otherwise
     */
    public static function validate_date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate time format
     * 
     * @param string $time Time string to validate
     * @param string $format Time format (default: H:i)
     * @return bool True if valid, false otherwise
     */
    public static function validate_time($time, $format = 'H:i') {
        $d = DateTime::createFromFormat($format, $time);
        return $d && $d->format($format) === $time;
    }
    
    /**
     * Validate zip code (basic validation)
     * 
     * @param string $zipcode Zip code to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_zipcode($zipcode) {
        // Remove spaces and convert to uppercase
        $cleaned = strtoupper(str_replace(' ', '', $zipcode));
        
        // Check if it's alphanumeric and reasonable length (3-10 characters)
        return ctype_alnum($cleaned) && strlen($cleaned) >= 3 && strlen($cleaned) <= 10;
    }
    
    /**
     * Check if user has required capability
     * 
     * @param string $capability Required capability
     * @return bool True if user has capability, false otherwise
     */
    public static function check_capability($capability = 'manage_options') {
        return current_user_can($capability);
    }
    
    /**
     * Log security events
     * 
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     * @param array $context Additional context data
     */
    public static function log_security_event($message, $level = 'info', $context = array()) {
        $log_message = sprintf(
            '[CB Security %s] %s - Context: %s',
            strtoupper($level),
            $message,
            wp_json_encode($context)
        );
        
        error_log($log_message);
    }
    
    /**
     * Escape output for display
     * 
     * @param string $output Output to escape
     * @return string Escaped output
     */
    public static function escape_output($output) {
        return esc_html($output);
    }
    
    /**
     * Escape attribute for HTML attributes
     * 
     * @param string $attribute Attribute to escape
     * @return string Escaped attribute
     */
    public static function escape_attribute($attribute) {
        return esc_attr($attribute);
    }
    
    /**
     * Escape URL for href attributes
     * 
     * @param string $url URL to escape
     * @return string Escaped URL
     */
    public static function escape_url($url) {
        return esc_url($url);
    }
    
    /**
     * Validate and sanitize booking data
     * 
     * @param array $data Booking data to validate
     * @return array Validated and sanitized data
     */
    public static function validate_booking_data($data) {
        $validated = array();
        
        // Required fields
        $required_fields = array(
            'customer_name' => 'sanitize_text',
            'customer_email' => 'sanitize_email',
            'customer_phone' => 'sanitize_text',
            'booking_date' => 'sanitize_text',
            'booking_time' => 'sanitize_text',
            'zipcode' => 'sanitize_text',
            'address' => 'sanitize_textarea'
        );
        
        foreach ($required_fields as $field => $sanitizer) {
            if (isset($data[$field])) {
                $validated[$field] = self::$sanitizer($data[$field]);
            }
        }
        
        // Optional fields
        $optional_fields = array(
            'notes' => 'sanitize_textarea',
            'area' => 'sanitize_int',
            'services' => 'sanitize_text',
            'extras' => 'sanitize_text'
        );
        
        foreach ($optional_fields as $field => $sanitizer) {
            if (isset($data[$field])) {
                $validated[$field] = self::$sanitizer($data[$field]);
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate admin form data
     * 
     * @param array $data Form data to validate
     * @return array Validated and sanitized data
     */
    public static function validate_admin_data($data) {
        $validated = array();
        
        // Common admin fields
        $admin_fields = array(
            'name' => 'sanitize_text',
            'description' => 'sanitize_textarea',
            'price' => 'sanitize_float',
            'duration' => 'sanitize_int',
            'is_active' => 'sanitize_bool',
            'sort_order' => 'sanitize_int'
        );
        
        foreach ($admin_fields as $field => $sanitizer) {
            if (isset($data[$field])) {
                $validated[$field] = self::$sanitizer($data[$field]);
            }
        }
        
        return $validated;
    }
}
