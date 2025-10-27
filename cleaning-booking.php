<?php
/**
 * Plugin Name: Cleaning Booking System
 * Plugin URI: https://example.com/cleaning-booking
 * Description: Professional booking system for cleaning services with WooCommerce integration, multi-language support, and advanced scheduling features.
 * Version: 1.0.0
 * Author: Cleaning Booking System
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cleaning-booking
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Network: false
 * 
 * @package CleaningBooking
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Define plugin constants
if (!defined('CB_PLUGIN_FILE')) {
    define('CB_PLUGIN_FILE', __FILE__);
}
if (!defined('CB_PLUGIN_DIR')) {
    define('CB_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('CB_PLUGIN_URL')) {
    define('CB_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('CB_VERSION')) {
    define('CB_VERSION', '1.0.0');
}
if (!defined('CB_DB_VERSION')) {
    define('CB_DB_VERSION', '1.0.0');
}

// WooCommerce integration is optional
// The plugin can work standalone or with WooCommerce for payment processing

/**
 * Main plugin class
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */
class CleaningBooking {
    
    /**
     * Plugin instance
     * 
     * @var CleaningBooking
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     * 
     * @return CleaningBooking
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check minimum requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Load text domain
        $this->load_textdomain();
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_hooks();
    }
    
    /**
     * Check minimum requirements
     * 
     * @return bool
     */
    private function check_requirements() {
        $php_version = phpversion();
        $wp_version = get_bloginfo('version');
        
        if (version_compare($php_version, '7.4', '<')) {
            add_action('admin_notices', function() use ($php_version) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __('Cleaning Booking System requires PHP 7.4 or higher. You are running PHP %s.', 'cleaning-booking'),
                    esc_html($php_version)
                );
                echo '</p></div>';
            });
            return false;
        }
        
        if (version_compare($wp_version, '5.0', '<')) {
            add_action('admin_notices', function() use ($wp_version) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __('Cleaning Booking System requires WordPress 5.0 or higher. You are running WordPress %s.', 'cleaning-booking'),
                    esc_html($wp_version)
                );
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Load plugin text domain
     * Note: Translations are now loaded from database only, not from .po/.mo files
     */
    private function load_textdomain() {
        // Translations are handled through database and gettext filters
        // No need to load .po/.mo files anymore
        
        // Override WordPress translation functions to use database
        add_filter('gettext', array($this, 'override_translations'), 10, 3);
        add_filter('gettext_with_context', array($this, 'override_translations_with_context'), 10, 4);
    }
    
    /**
     * Include required files
     */
    private function includes() {
        $required_files = array(
            'class-cb-security.php',
            'class-cb-logger.php',
            'class-cb-database.php',
            'class-cb-pricing.php',
            'class-cb-admin.php',
            'class-cb-rest-api.php',
            'class-cb-frontend.php',
            'class-cb-woocommerce.php',
            'class-cb-slot-manager.php',
            'class-cb-translations.php',
            'class-cb-translation-seeder.php',
            'class-cb-form-fields.php',
            'class-cb-style-manager.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = CB_PLUGIN_DIR . 'includes/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Cleaning Booking System: Required file not found: {$file_path}");
            }
        }
    }
    
    /**
     * Initialize hooks and components
     */
    private function init_hooks() {
        try {
            // Initialize logger
            if (class_exists('CB_Logger')) {
                CB_Logger::init();
            }
            
            // Initialize database
            if (class_exists('CB_Database')) {
                new CB_Database();
            }
            
            // Initialize admin interface
            if (is_admin() && class_exists('CB_Admin')) {
                new CB_Admin();
            }
            
            // Initialize REST API
            if (class_exists('CB_REST_API')) {
                new CB_REST_API();
            }
            
            // Initialize frontend
            if (class_exists('CB_Frontend')) {
                new CB_Frontend();
            }
            
            // Initialize WooCommerce integration
            if (class_exists('CB_WooCommerce')) {
                new CB_WooCommerce();
            }
            
            // Initialize slot manager
            if (class_exists('CB_Slot_Manager')) {
                new CB_Slot_Manager();
            }
            
            // Add Gravatar fallback to prevent connection errors
            add_filter('get_avatar_url', array($this, 'gravatar_fallback'), 10, 3);
            
        } catch (Exception $e) {
            error_log("Cleaning Booking System initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Check if database tables exist
        if (get_option('cb_db_version') !== CB_DB_VERSION) {
            echo '<div class="notice notice-warning"><p>';
            _e('Cleaning Booking System database needs to be updated. Please deactivate and reactivate the plugin.', 'cleaning-booking');
            echo '</p></div>';
        }
    }
    
    public function gravatar_fallback($url, $id_or_email, $args) {
        // If Gravatar URL fails, use a data URI for a simple default avatar
        if (strpos($url, 'gravatar.com') !== false) {
            // Return a simple data URI for a default avatar (gray circle)
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="#ccc"/><circle cx="32" cy="24" r="8" fill="#999"/><path d="M16 48c0-8.8 7.2-16 16-16s16 7.2 16 16" fill="#999"/></svg>');
        }
        return $url;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check requirements before activation
        if (!$this->check_requirements()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Cleaning Booking System could not be activated due to insufficient requirements.', 'cleaning-booking'),
                __('Plugin Activation Error', 'cleaning-booking'),
                array('back_link' => true)
            );
        }
        
        try {
            // Include required files for activation
            $this->includes();
            
            // Create database tables
            if (class_exists('CB_Database')) {
                CB_Database::create_tables();
            }
            
            // Seed translation data
            if (class_exists('CB_Translation_Seeder')) {
                CB_Translation_Seeder::seed_all();
            }
            
            // Set default options
            $this->set_default_options();
            
            // Update database version
            update_option('cb_db_version', CB_DB_VERSION);
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            error_log("Cleaning Booking System activation error: " . $e->getMessage());
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Cleaning Booking System could not be activated due to an error.', 'cleaning-booking'),
                __('Plugin Activation Error', 'cleaning-booking'),
                array('back_link' => true)
            );
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        try {
            // Clear scheduled events
            wp_clear_scheduled_hook('cb_cleanup_expired_bookings');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            error_log("Cleaning Booking System deactivation error: " . $e->getMessage());
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'cb_slot_duration' => 30, // minutes
            'cb_buffer_time' => 15, // minutes
            'cb_booking_hold_time' => 15, // minutes
            'cb_max_advance_booking' => 30, // days
            'cb_min_advance_booking' => 1, // days
            'cb_enable_notifications' => true,
            'cb_enable_email_notifications' => true,
            'cb_enable_sms_notifications' => false,
            'cb_business_hours' => array(
                'monday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'tuesday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'wednesday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'thursday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'friday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'saturday' => array('start' => '10:00', 'end' => '16:00', 'enabled' => true),
                'sunday' => array('start' => '10:00', 'end' => '16:00', 'enabled' => false),
            ),
            'cb_default_language' => 'en',
            'cb_enable_translations' => true,
            'cb_cache_duration' => 3600, // 1 hour
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Override WordPress gettext to use database translations
     */
    public function override_translations($translated, $text, $domain) {
        return $this->get_database_translation($text, $domain);
    }
    
    /**
     * Override WordPress gettext_with_context to use database translations
     */
    public function override_translations_with_context($translated, $text, $context, $domain) {
        return $this->get_database_translation($text, $domain, $context);
    }
    
    /**
     * Get translation from database
     */
    private function get_database_translation($text, $domain, $context = '') {
        if ($domain !== 'cleaning-booking') {
            return $text;
        }
        
        $language = $this->get_current_language();
        
        if ($context) {
            return CB_Translations::get_translation_by_text($text, $language, $context) ?: $text;
        }
        
        return CB_Translations::get_translation_by_text($text, $language) ?: $text;
    }
    
    /**
     * Get current language
     */
    private function get_current_language() {
        if (!get_option('cb_enable_translations', true)) {
            return 'en';
        }
        
        $language_sources = array(
            'url' => $this->get_language_from_url(),
            'session' => $this->get_language_from_session(),
            'option' => get_option('cb_default_language', 'en')
        );
        
        foreach ($language_sources as $source => $lang) {
            if ($this->is_valid_language($lang)) {
                if ($source === 'url') {
                    $this->set_session_language($lang);
                }
                return $lang;
            }
        }
        
        return 'en';
    }
    
    /**
     * Get language from URL parameter
     */
    private function get_language_from_url() {
        return isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : null;
    }
    
    /**
     * Get language from session
     */
    private function get_language_from_session() {
        return isset($_SESSION['cb_language']) ? $_SESSION['cb_language'] : null;
    }
    
    /**
     * Set language in session
     */
    private function set_session_language($language) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['cb_language'] = $language;
    }
    
    /**
     * Validate language code
     */
    private function is_valid_language($language) {
        return in_array($language, array('en', 'el'), true);
    }
}

// Initialize the plugin
CleaningBooking::get_instance();
