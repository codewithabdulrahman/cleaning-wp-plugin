<?php
/**
 * Logging utilities for Cleaning Booking System
 * 
 * Provides comprehensive logging functionality for debugging,
 * error tracking, and audit trails in production environments.
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * CB_Logger class
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */
class CB_Logger {
    
    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    /**
     * Log file path
     * 
     * @var string
     */
    private static $log_file = null;
    
    /**
     * Minimum log level
     * 
     * @var string
     */
    private static $min_level = self::INFO;
    
    /**
     * Initialize logger
     */
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/debug.log';
        
        // Set minimum log level based on WordPress debug settings
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::$min_level = self::DEBUG;
        }
    }
    
    /**
     * Log emergency messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function emergency($message, $context = array()) {
        self::log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log alert messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function alert($message, $context = array()) {
        self::log(self::ALERT, $message, $context);
    }
    
    /**
     * Log critical messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function critical($message, $context = array()) {
        self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log error messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function error($message, $context = array()) {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log warning messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function warning($message, $context = array()) {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log notice messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function notice($message, $context = array()) {
        self::log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log info messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function info($message, $context = array()) {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log debug messages
     * 
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function debug($message, $context = array()) {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Main logging method
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    private static function log($level, $message, $context = array()) {
        // Check if we should log this level
        if (!self::should_log($level)) {
            return;
        }
        
        // Initialize if needed
        if (self::$log_file === null) {
            self::init();
        }
        
        // Format log entry
        $log_entry = self::format_log_entry($level, $message, $context);
        
        // Write to log file
        self::write_to_file($log_entry);
        
        // Also log to WordPress error log if WP_DEBUG_LOG is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log($log_entry);
        }
    }
    
    /**
     * Check if we should log this level
     * 
     * @param string $level Log level
     * @return bool True if should log, false otherwise
     */
    private static function should_log($level) {
        $levels = array(
            self::EMERGENCY => 0,
            self::ALERT => 1,
            self::CRITICAL => 2,
            self::ERROR => 3,
            self::WARNING => 4,
            self::NOTICE => 5,
            self::INFO => 6,
            self::DEBUG => 7
        );
        
        return $levels[$level] <= $levels[self::$min_level];
    }
    
    /**
     * Format log entry
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return string Formatted log entry
     */
    private static function format_log_entry($level, $message, $context = array()) {
        $timestamp = current_time('Y-m-d H:i:s');
        $context_str = empty($context) ? '' : ' ' . wp_json_encode($context);
        
        return sprintf(
            '[%s] CB.%s: %s%s',
            $timestamp,
            strtoupper($level),
            $message,
            $context_str
        );
    }
    
    /**
     * Write log entry to file
     * 
     * @param string $log_entry Formatted log entry
     */
    private static function write_to_file($log_entry) {
        if (self::$log_file && is_writable(dirname(self::$log_file))) {
            file_put_contents(self::$log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Log database operations
     * 
     * @param string $operation Database operation
     * @param string $table Table name
     * @param array $data Data involved
     * @param string $level Log level
     */
    public static function log_db_operation($operation, $table, $data = array(), $level = self::INFO) {
        $message = sprintf('Database %s on table %s', $operation, $table);
        $context = array(
            'table' => $table,
            'operation' => $operation,
            'data' => $data
        );
        
        self::log($level, $message, $context);
    }
    
    /**
     * Log AJAX requests
     * 
     * @param string $action AJAX action
     * @param array $data Request data
     * @param string $level Log level
     */
    public static function log_ajax_request($action, $data = array(), $level = self::INFO) {
        $message = sprintf('AJAX request: %s', $action);
        $context = array(
            'action' => $action,
            'data' => $data,
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip()
        );
        
        self::log($level, $message, $context);
    }
    
    /**
     * Log booking operations
     * 
     * @param string $operation Booking operation
     * @param int $booking_id Booking ID
     * @param array $data Additional data
     * @param string $level Log level
     */
    public static function log_booking_operation($operation, $booking_id, $data = array(), $level = self::INFO) {
        $message = sprintf('Booking %s: ID %d', $operation, $booking_id);
        $context = array(
            'booking_id' => $booking_id,
            'operation' => $operation,
            'data' => $data,
            'user_id' => get_current_user_id()
        );
        
        self::log($level, $message, $context);
    }
    
    /**
     * Log security events
     * 
     * @param string $event Security event
     * @param array $context Additional context
     * @param string $level Log level
     */
    public static function log_security_event($event, $context = array(), $level = self::WARNING) {
        $message = sprintf('Security event: %s', $event);
        $context = array_merge($context, array(
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
        ));
        
        self::log($level, $message, $context);
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    }
    
    /**
     * Clear log file
     * 
     * @return bool True on success, false on failure
     */
    public static function clear_log() {
        if (self::$log_file && file_exists(self::$log_file)) {
            return unlink(self::$log_file);
        }
        return false;
    }
    
    /**
     * Get log file size
     * 
     * @return int Log file size in bytes
     */
    public static function get_log_size() {
        if (self::$log_file && file_exists(self::$log_file)) {
            return filesize(self::$log_file);
        }
        return 0;
    }
    
    /**
     * Set minimum log level
     * 
     * @param string $level Minimum log level
     */
    public static function set_min_level($level) {
        self::$min_level = $level;
    }
}
