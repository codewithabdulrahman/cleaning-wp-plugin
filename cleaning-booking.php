<?php
/**
 * Plugin Name: Cleaning Booking System
 * Plugin URI: https://example.com/cleaning-booking
 * Description: Custom booking system for cleaning services with WooCommerce integration
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: cleaning-booking
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CB_PLUGIN_FILE', __FILE__);
define('CB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CB_VERSION', '1.0.0');

// WooCommerce integration is optional
// The plugin can work standalone or with WooCommerce for payment processing

/**
 * Load plugin translations
 */
function cb_load_textdomain() {
    load_plugin_textdomain('cleaning-booking', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'cb_load_textdomain');

// Main plugin class
class CleaningBooking {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('cleaning-booking', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_hooks();
    }
    
    private function includes() {
        require_once CB_PLUGIN_DIR . 'includes/class-cb-database.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-pricing.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-admin.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-rest-api.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-frontend.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-woocommerce.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-slot-manager.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-translations.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-form-fields.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cb-style-manager.php';
    }
    
    private function init_hooks() {
        // Initialize database
        new CB_Database();
        
        // Initialize admin interface
        if (is_admin()) {
            new CB_Admin();
        }
        
        // Initialize REST API
        new CB_REST_API();
        
        // Initialize frontend
        new CB_Frontend();
        
        // Initialize WooCommerce integration
        new CB_WooCommerce();
        
        // Initialize slot manager
        new CB_Slot_Manager();
        
        // Add Gravatar fallback to prevent connection errors
        add_filter('get_avatar_url', array($this, 'gravatar_fallback'), 10, 3);
    }
    
    public function gravatar_fallback($url, $id_or_email, $args) {
        // If Gravatar URL fails, use a data URI for a simple default avatar
        if (strpos($url, 'gravatar.com') !== false) {
            // Return a simple data URI for a default avatar (gray circle)
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="#ccc"/><circle cx="32" cy="24" r="8" fill="#999"/><path d="M16 48c0-8.8 7.2-16 16-16s16 7.2 16 16" fill="#999"/></svg>');
        }
        return $url;
    }
    
    public function activate() {
        // Include required files for activation
        require_once CB_PLUGIN_DIR . 'includes/class-cb-database.php';
        
        // Create database tables
        CB_Database::create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function set_default_options() {
        $defaults = array(
            'cb_slot_duration' => 30, // minutes
            'cb_buffer_time' => 15, // minutes
            'cb_booking_hold_time' => 15, // minutes
            'cb_business_hours' => array(
                'monday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'tuesday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'wednesday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'thursday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'friday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'saturday' => array('start' => '10:00', 'end' => '16:00', 'enabled' => true),
                'sunday' => array('start' => '10:00', 'end' => '16:00', 'enabled' => false),
            )
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

// Initialize the plugin
new CleaningBooking();
