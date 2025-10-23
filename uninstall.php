<?php
/**
 * Uninstall script for Cleaning Booking System
 * 
 * This file is executed when the plugin is deleted through WordPress admin.
 * It removes all plugin data, options, and database tables.
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit('Direct access denied.');
}

// Include WordPress database functions
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

/**
 * Clean up plugin data on uninstall
 */
function cb_uninstall_cleanup() {
    global $wpdb;
    
    // Remove all plugin options
    $options_to_remove = array(
        'cb_slot_duration',
        'cb_buffer_time',
        'cb_booking_hold_time',
        'cb_max_advance_booking',
        'cb_min_advance_booking',
        'cb_enable_notifications',
        'cb_enable_email_notifications',
        'cb_enable_sms_notifications',
        'cb_business_hours',
        'cb_default_language',
        'cb_enable_translations',
        'cb_cache_duration',
        'cb_db_version'
    );
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
    }
    
    // Remove all plugin database tables
    $tables_to_remove = array(
        $wpdb->prefix . 'cb_services',
        $wpdb->prefix . 'cb_service_extras',
        $wpdb->prefix . 'cb_bookings',
        $wpdb->prefix . 'cb_booking_services',
        $wpdb->prefix . 'cb_booking_extras',
        $wpdb->prefix . 'cb_trucks',
        $wpdb->prefix . 'cb_zip_codes',
        $wpdb->prefix . 'cb_translations'
    );
    
    foreach ($tables_to_remove as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Clear scheduled events
    wp_clear_scheduled_hook('cb_cleanup_expired_bookings');
    
    // Remove transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cb_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cb_%'");
    
    // Remove user meta related to plugin
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'cb_%'");
    
    // Remove post meta related to plugin
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'cb_%'");
    
    // Log uninstall event
    error_log('Cleaning Booking System: Plugin uninstalled and data cleaned up');
}

// Execute cleanup
cb_uninstall_cleanup();
