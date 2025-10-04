<?php
/**
 * Frontend class for booking wizard
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Frontend {
    
    public function __construct() {
        // Add inline script to head IMMEDIATELY to prevent Elementor conflicts
        add_action('wp_head', array($this, 'add_early_cb_frontend_init'), 1);
        
        // Also add to multiple hooks with very high priority as backup
        add_action('init', array($this, 'add_early_cb_frontend_init'), 1);
        add_action('wp_print_scripts', array($this, 'add_early_cb_frontend_init'), 1);
        add_action('wp_print_styles', array($this, 'add_early_cb_frontend_init'), 1);
        
        // Disable Elementor frontend script on pages with our booking widget
        add_action('wp_enqueue_scripts', array($this, 'disable_elementor_conflicts'), 1);
        
        // Load our scripts early to prevent Elementor conflicts (priority 5)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 5);
        add_shortcode('cb_widget', array($this, 'booking_widget_shortcode'));
        add_action('wp_ajax_cb_switch_language', array($this, 'ajax_switch_language'));
        add_action('wp_ajax_nopriv_cb_switch_language', array($this, 'ajax_switch_language'));
        
        // Add REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Add a debug endpoint
        add_action('wp_ajax_cb_debug', array($this, 'ajax_debug'));
        add_action('wp_ajax_nopriv_cb_debug', array($this, 'ajax_debug'));
        
        // Run database migrations for existing installations
        $this->run_database_migrations();
        
        // AJAX actions for authenticated users
        add_action('wp_ajax_cb_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_cb_check_zip_availability', array($this, 'ajax_check_zip_availability'));
        add_action('wp_ajax_cb_get_zip_surcharge', array($this, 'ajax_get_zip_surcharge'));
        add_action('wp_ajax_cb_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_cb_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_cb_get_extras', array($this, 'ajax_get_extras'));
        add_action('wp_ajax_cb_calculate_price', array($this, 'ajax_calculate_price'));
        
        // AJAX actions for non-authenticated users (public access)
        add_action('wp_ajax_nopriv_cb_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_nopriv_cb_check_zip_availability', array($this, 'ajax_check_zip_availability'));
        add_action('wp_ajax_nopriv_cb_get_zip_surcharge', array($this, 'ajax_get_zip_surcharge'));
        add_action('wp_ajax_nopriv_cb_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_cb_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_nopriv_cb_get_extras', array($this, 'ajax_get_extras'));
        add_action('wp_ajax_nopriv_cb_calculate_price', array($this, 'ajax_calculate_price'));
    }
    
    /**
     * Add early cb_frontend initialization directly to HTML head
     * This runs with priority 1 to ensure it loads before any other scripts
     */
    public function add_early_cb_frontend_init() {
        // Prevent duplicate output
        static $already_output = false;
        if ($already_output) {
            return;
        }
        $already_output = true;
        
        // Only add on pages with our shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'cb_widget')) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        // IMMEDIATE initialization to prevent Elementor conflicts
        (function() {
            'use strict';
            
            // Create multiple global objects that Elementor might be expecting
            window.cb_frontend = {
                tools: {
                    initOnReadyComponents: function() {
                        console.log('Elementor called cb_frontend.tools.initOnReadyComponents - preventing error');
                        return true;
                    }
                },
                ajax_url: '',
                nonce: '',
                current_language: 'en',
                translations: {},
                strings: {},
                init: function() {},
                ready: function() {},
                onReady: function() {},
                components: {},
                modules: {},
                utils: {},
                helpers: {},
                Frontend: {
                    initOnReadyComponents: function() {
                        console.log('Elementor called cb_frontend.Frontend.initOnReadyComponents - preventing error');
                        return true;
                    },
                    init: function() {}
                }
            };
            
            // Also create a global Frontend object that Elementor might be looking for
            window.Frontend = {
                tools: {
                    initOnReadyComponents: function() {
                        console.log('Elementor called Frontend.tools.initOnReadyComponents - preventing error');
                        return true;
                    }
                },
                initOnReadyComponents: function() {
                    console.log('Elementor called Frontend.initOnReadyComponents - preventing error');
                    return true;
                },
                init: function() {
                    console.log('Elementor called Frontend.init - preventing error');
                    return true;
                }
            };
            
            // Create Elementor-specific global objects
            window.elementorFrontend = {
                tools: {
                    initOnReadyComponents: function() {
                        console.log('Elementor called elementorFrontend.tools.initOnReadyComponents - preventing error');
                        return true;
                    }
                },
                initOnReadyComponents: function() {
                    console.log('Elementor called elementorFrontend.initOnReadyComponents - preventing error');
                    return true;
                },
                init: function() {
                    console.log('Elementor called elementorFrontend.init - preventing error');
                    return true;
                }
            };
            
            // Create a generic tools object
            window.tools = {
                initOnReadyComponents: function() {
                    console.log('Elementor called tools.initOnReadyComponents - preventing error');
                    return true;
                }
            };
            
            // Also ensure it's available when DOM is ready
            if (typeof document !== 'undefined') {
                document.addEventListener('DOMContentLoaded', function() {
                    if (!window.cb_frontend.tools) {
                        window.cb_frontend.tools = {};
                    }
                });
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Disable Elementor frontend script on pages with our booking widget
     */
    public function disable_elementor_conflicts() {
        // Only disable on pages with our shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'cb_widget')) {
            return;
        }
        
        // Dequeue Elementor frontend script to prevent conflicts
        wp_dequeue_script('elementor-frontend');
        wp_deregister_script('elementor-frontend');
        
        // Also try to dequeue any Elementor-related scripts
        wp_dequeue_script('elementor-frontend-min');
        wp_deregister_script('elementor-frontend-min');
        
        wp_dequeue_script('elementor-frontend-min-js');
        wp_deregister_script('elementor-frontend-min-js');
        
        wp_dequeue_script('elementor-frontend-js');
        wp_deregister_script('elementor-frontend-js');  
    }
    
    public function enqueue_scripts() {
        // Only load on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'cb_widget')) {
            // Load early initialization script FIRST (no dependencies, loads immediately)
            wp_enqueue_script('cb-frontend-init', CB_PLUGIN_URL . 'assets/js/cb-frontend-init.js', array(), CB_VERSION, false);
            
            // Load our main script early with high priority to prevent Elementor conflicts
            wp_enqueue_script('cb-frontend', CB_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CB_VERSION, false);
            wp_enqueue_style('cb-frontend', CB_PLUGIN_URL . 'assets/css/frontend.css', array(), CB_VERSION);
            
            // Set high priority and ensure it loads before other scripts
            wp_script_add_data('cb-frontend-init', 'priority', 1);
            wp_script_add_data('cb-frontend', 'priority', 2);
            
            wp_localize_script('cb-frontend', 'cb_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('cleaning-booking/v1/'),
                'nonce' => wp_create_nonce('cb_frontend_nonce'),
                'current_language' => $this->get_current_language(),
                'translations' => $this->get_translations(),
                'strings' => array(
                    'loading' => __('Loading...', 'cleaning-booking'),
                    'error' => __('An error occurred. Please try again.', 'cleaning-booking'),
                    'server_error' => __('Server error. Please try again.', 'cleaning-booking'),
                    'zip_required' => __('Please enter a ZIP code.', 'cleaning-booking'),
                    'zip_invalid' => __('Please enter a valid 5-digit ZIP code.', 'cleaning-booking'),
                    'zip_unavailable' => __('Sorry, we don\'t provide services in this area yet.', 'cleaning-booking'),
                    'select_service' => __('Please select a service.', 'cleaning-booking'),
                    'square_meters_required' => __('Please enter the square meters.', 'cleaning-booking'),
                    'select_date' => __('Please select a date.', 'cleaning-booking'),
                    'select_time' => __('Please select a time slot.', 'cleaning-booking'),
                    'name_required' => __('Please enter your name.', 'cleaning-booking'),
                    'email_required' => __('Please enter your email address.', 'cleaning-booking'),
                    'email_invalid' => __('Please enter a valid email address.', 'cleaning-booking'),
                    'phone_required' => __('Please enter your phone number.', 'cleaning-booking'),
                    'booking_error' => __('Booking failed. Please try again.', 'cleaning-booking'),
                    'booking_created' => __('Booking created successfully!', 'cleaning-booking'),
                    'booking_saved' => __('Booking saved successfully!', 'cleaning-booking'),
                    'redirecting' => __('Redirecting to checkout...', 'cleaning-booking'),
                    'processing' => __('Processing...', 'cleaning-booking'),
                    'proceed_checkout' => __('I am ordering', 'cleaning-booking'),
                    'enter_promocode' => __('Please enter a promocode.', 'cleaning-booking'),
                    'promocode_applied' => __('Promocode applied successfully!', 'cleaning-booking'),
                    'loading_services' => __('Loading services...', 'cleaning-booking'),
                    'loading_extras' => __('Loading extras...', 'cleaning-booking'),
                    'loading_slots' => __('Loading available times...', 'cleaning-booking'),
                    'no_services_available' => __('No services available.', 'cleaning-booking'),
                    'no_extras_available' => __('No additional services available.', 'cleaning-booking'),
                    'no_slots_available' => __('No time slots available.', 'cleaning-booking'),
                    'loading_error' => __('Error loading data.', 'cleaning-booking'),
                    'minutes' => __('minutes', 'cleaning-booking'),
                    'hours' => __('hours', 'cleaning-booking'),
                    'hour' => __('hour', 'cleaning-booking')
                )
            ));
        }
    }
    
    public function booking_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Book Your Cleaning Service', 'cleaning-booking'),
            'show_steps' => 'true',
            'theme' => 'light'
        ), $atts);
        
        // Validate theme parameter
        $themes = array('light', 'dark');
        if (!in_array($atts['theme'], $themes)) {
            $atts['theme'] = 'light';
        }
        
        // Convert show_steps to boolean
        $atts['show_steps'] = filter_var($atts['show_steps'], FILTER_VALIDATE_BOOLEAN);
        
        ob_start();
        include CB_PLUGIN_DIR . 'templates/frontend/booking-widget.php';
        return ob_get_clean();
    }
    
    public function ajax_create_booking() {
        try {
        // Require nonce for booking creation (has side effects)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cb_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security ticket expired. Please refresh the page.', 'cleaning-booking')));
        }
            
            // Debug logging
            error_log('CB Debug - Creating booking with data: ' . print_r($_POST, true));
            error_log('CB Debug - Extras received: ' . print_r($_POST['extras'], true));
        
        $data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'zip_code' => sanitize_text_field($_POST['zip_code']),
            'service_id' => intval($_POST['service_id']),
            'square_meters' => intval($_POST['square_meters']),
            'booking_date' => sanitize_text_field($_POST['booking_date']),
            'booking_time' => sanitize_text_field($_POST['booking_time']),
            'extras' => isset($_POST['extras']) ? array_map('intval', $_POST['extras']) : array(),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'payment_method' => sanitize_text_field($_POST['payment_method'])
        );
        
        // Validate required fields
        if (empty($data['customer_name'])) {
            wp_send_json_error(array('message' => __('Please enter your name.', 'cleaning-booking')));
        }
        
        if (empty($data['customer_email']) || !is_email($data['customer_email'])) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'cleaning-booking')));
        }
        
        if (empty($data['customer_phone'])) {
            wp_send_json_error(array('message' => __('Please enter your phone number.', 'cleaning-booking')));
        }
        
        if (empty($data['service_id'])) {
            wp_send_json_error(array('message' => __('Please select a service.', 'cleaning-booking')));
        }
        
        if (empty($data['square_meters']) || $data['square_meters'] <= 0) {
            wp_send_json_error(array('message' => __('Please enter a valid square meter value.', 'cleaning-booking')));
        }
        
        if (empty($data['booking_date'])) {
            wp_send_json_error(array('message' => __('Please select a date.', 'cleaning-booking')));
        }
        
        if (empty($data['booking_time'])) {
            wp_send_json_error(array('message' => __('Please select a time slot.', 'cleaning-booking')));
        }
        
        // Calculate final price and duration
        $total_price = CB_Pricing::calculate_booking_price($data['service_id'], $data['square_meters'], $data['extras'], $data['zip_code']);
        $total_duration = CB_Pricing::calculate_booking_duration($data['service_id'], $data['square_meters'], $data['extras']);
        
        $data['total_price'] = $total_price;
        $data['total_duration'] = $total_duration;
        
        // Create booking
        $booking_id = CB_Database::create_booking($data);
        
        if ($booking_id) {
            $booking = CB_Database::get_booking($booking_id);
            
            // Create WooCommerce order
            $woocommerce = new CB_WooCommerce();
            $order_url = $woocommerce->create_booking_order($booking);
                
                // Debug logging
                error_log('CB Debug - Booking created with ID: ' . $booking_id);
                error_log('CB Debug - Checkout URL: ' . $order_url);
                
                // If WooCommerce is not available, provide a fallback
                if (!$order_url) {
                    error_log('CB Debug - WooCommerce not available, using fallback');
                    $order_url = add_query_arg(array(
                        'booking_id' => $booking_id,
                        'booking_reference' => $booking->booking_reference
                    ), home_url('/booking-confirmation/'));
                }
            
            wp_send_json_success(array(
                'booking_id' => $booking_id,
                'booking_reference' => $booking->booking_reference,
                'checkout_url' => $order_url,
                'message' => __('Booking created successfully!', 'cleaning-booking')
            ));
        }
        
        wp_send_json_error(array('message' => __('Failed to create booking. Please try again.', 'cleaning-booking')));
            
        } catch (Exception $e) {
            error_log('CB Debug - Booking creation error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while creating the booking. Please try again.', 'cleaning-booking')));
        }
    }
    
    public function ajax_check_zip_availability() {
        // Public endpoint - ZIP code availability check
        // Basic spam protection - require valid zip code format
        $zip_code = sanitize_text_field($_POST['zip_code'] ?? '');
        
        if (empty($zip_code)) {
            wp_send_json_error(array('message' => __('Please enter a ZIP code.', 'cleaning-booking')));
        }
        
        // Check if ZIP code exists in our database
        $zip_available = CB_Database::check_zip_availability($zip_code);
        
        if ($zip_available) {
            wp_send_json_success(array('available' => true, 'message' => __('Service available in your area!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('available' => false, 'message' => __('Sorry, we don\'t provide services in this area yet.', 'cleaning-booking')));
        }
    }
    
    public function ajax_get_zip_surcharge() {
        // Public endpoint - ZIP surcharge lookup
        $zip_code = sanitize_text_field($_POST['zip_code'] ?? '');
        
        if (empty($zip_code)) {
            wp_send_json_success(array('surcharge' => 0));
        }
        
        // Get ZIP surcharge from database
        $surcharge = CB_Database::get_zip_surcharge($zip_code);
        
        wp_send_json_success(array('surcharge' => $surcharge));
    }
    
    public function ajax_get_available_slots() {
        // Public endpoint - time slot generation
        
        $booking_date = sanitize_text_field($_POST['booking_date']);
        $duration = intval($_POST['duration']);
        
        if (empty($booking_date)) {
            wp_send_json_error(array('message' => __('Please select a date.', 'cleaning-booking')));
        }
        
        // Generate available time slots for the given date
        $slots = $this->generate_time_slots($booking_date, $duration);
        
        wp_send_json_success(array('slots' => $slots));
    }
    
    public function ajax_get_services() {
        // Public endpoint - services listing (no authentication needed)
        
        // Check if WordPress is blocking this request
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_die('Not an AJAX request');
        }
        
        // Allow cross-origin requests
        header('Access-Control-Allow-Origin: *');
        
        try {
            // Get active services from database
            $services = CB_Database::get_services(true);
            
            if ($services === false || empty($services)) {
                wp_send_json_success(array('services' => array(), 'message' => 'No services found'));
            }
            
            wp_send_json_success(array('services' => $services));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Database error: ' . $e->getMessage()));
        }
    }
    
    public function ajax_get_extras() {
        // Public endpoint - extras listing for service
        
        $service_id = intval($_POST['service_id']);
        
        if (empty($service_id)) {
            wp_send_json_error(array('message' => __('Please select a service first.', 'cleaning-booking')));
        }
        
        // Get active extras for the selected service
        $extras = CB_Database::get_service_extras($service_id, true);
        
        // Debug logging
        error_log('CB Debug - ajax_get_extras called for service_id: ' . $service_id);
        error_log('CB Debug - extras found: ' . print_r($extras, true));
        
        wp_send_json_success(array('extras' => $extras));
    }
    
    private function generate_time_slots($date, $duration = 120) {
        $slots = array();
        $interval = 30; // 30-minute intervals
        
        // Generate slots from 9 AM to 6 PM
        $start_hour = 9;
        $end_hour = 18;
        
        $current_time = strtotime($date . ' ' . sprintf('%02d:00', $start_hour));
        $end_time = strtotime($date . ' ' . sprintf('%02d:00', $end_hour));
        
        while ($current_time < $end_time) {
            $slot_time = date('H:i', $current_time);
            $slot_end_time = date('H:i', $current_time + ($duration * 60));
            
            $slots[] = array(
                'time' => $slot_time,
                'formatted_time' => date('g:i A', $current_time),
                'available' => true,
                'duration' => $duration
            );
            
            $current_time += $interval * 60; // Move to next slot
        }
        
        return $slots;
    }
    
    private function get_current_language() {
        $locale = get_locale();
        return substr($locale, 0, 2); // Get language code (en, el)
    }
    
    private function get_translations() {
        // Load translation file based on current language
        $language = $this->get_current_language();
        return $this->get_translations_for_language($language);
    }
    
    private function get_translations_for_language($language) {
        // Map language codes to actual filenames
        $file_mapping = array(
            'en' => 'cleaning-booking-en.json',
            'el' => 'cleaning-booking-el_GR.json'
        );
        
        $filename = isset($file_mapping[$language]) ? $file_mapping[$language] : "cleaning-booking-{$language}.json";
        $translation_file = CB_PLUGIN_DIR . "languages/{$filename}";
        
        if (file_exists($translation_file)) {
            $translations = json_decode(file_get_contents($translation_file), true);
            return $translations ?: array();
        }
        
        // Return empty array if no translations found
        return array();
    }
    
    private function get_translated_services($language) {
        // Get original services from database
        $services = CB_Database::get_services(true);
        
        if (!$services) {
            return array();
        }
        
        // Load service translations
        $translations = $this->get_translations_for_language($language);
        $service_translations = isset($translations['services']) ? $translations['services'] : array();
        
        // Translate service names and descriptions
        $translated_services = array();
        foreach ($services as $service) {
            $translated_service = (array) $service; // Convert object to array
            
            // Translate service name
            if (isset($service_translations[$service->name])) {
                $translated_service['name'] = $service_translations[$service->name];
            }
            
            // Translate service description
            if (isset($service_translations[$service->description])) {
                $translated_service['description'] = $service_translations[$service->description];
            }
            
            $translated_services[] = (object) $translated_service;
        }
        
        return $translated_services;
    }
    
    public function ajax_switch_language() {
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        
        // Validate language
        $allowed_languages = array('en', 'el');
        if (!in_array($language, $allowed_languages)) {
            $language = 'en';
        }
        
        // Load translations directly from JSON files
        $translations = $this->get_translations_for_language($language);
        
        // Get translated services data
        $translated_services = $this->get_translated_services($language);
        
        // Return updated translations
        wp_send_json_success(array(
            'language' => $language,
            'translations' => $translations,
            'services' => $translated_services,
            'message' => $translations['Language switched successfully'] ?? 'Language switched successfully'
        ));
    }
    
    public function ajax_debug() {
        wp_send_json_success(array(
            'message' => 'AJAX Debug Endpoint Working',
            'timestamp' => current_time('mysql'),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'action' => $_POST['action'] ?? 'none'
        ));
    }
    
    public function register_rest_routes() {
        register_rest_route('cleaning-booking/v1', '/services', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_services'),
            'permission_callback' => '__return_true', // Public endpoint
        ));
        
        register_rest_route('cleaning-booking/v1', '/extras', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_extras'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'service_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }
    
    public function rest_get_services($request) {
        try {
            $services = CB_Database::get_services(true);
            
            return new WP_REST_Response(array(
                'success' => true,
                'services' => $services ?: array()
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('error', 'Unable to retrieve services', array('status' => 500));
        }
    }
    
    public function ajax_calculate_price() {
        try {
            // Require nonce for price calculation
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cb_frontend_nonce')) {
                wp_send_json_error(array('message' => __('Security ticket expired. Please refresh the page.', 'cleaning-booking')));
            }
            
            $service_id = intval($_POST['service_id']);
            $square_meters = intval($_POST['square_meters']);
            $extras = isset($_POST['extras']) ? array_map('intval', $_POST['extras']) : array();
            $zip_code = sanitize_text_field($_POST['zip_code']);
            
            // Debug logging
            error_log('CB Debug - Price calculation request: service_id=' . $service_id . ', sqm=' . $square_meters . ', extras=' . print_r($extras, true) . ', zip=' . $zip_code);
            
            // Validate required fields
            if (empty($service_id) || empty($square_meters)) {
                wp_send_json_error(array('message' => __('Service and square meters are required for price calculation.', 'cleaning-booking')));
            }
            
            // Check if CB_Pricing class exists
            if (!class_exists('CB_Pricing')) {
                error_log('CB Debug - CB_Pricing class not found!');
                wp_send_json_error(array('message' => __('Pricing system not available.', 'cleaning-booking')));
            }
            
            // Calculate pricing breakdown
            $pricing = CB_Pricing::get_pricing_breakdown($service_id, $square_meters, $extras, $zip_code);
            
            error_log('CB Debug - Price calculation result: ' . print_r($pricing, true));
            
            wp_send_json_success(array(
                'pricing' => $pricing,
                'formatted_price' => CB_Pricing::format_price($pricing['total_price']),
                'formatted_duration' => CB_Pricing::format_duration($pricing['total_duration'])
            ));
            
        } catch (Exception $e) {
            error_log('CB Debug - Price calculation error: ' . $e->getMessage());
            error_log('CB Debug - Price calculation stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('Unable to calculate price. Please try again.', 'cleaning-booking')));
        }
    }
    
    public function rest_get_extras($request) {
        try {
            $service_id = $request->get_param('service_id');
            $extras = CB_Database::get_service_extras($service_id, true);
            
            return new WP_REST_Response(array(
                'success' => true,
                'extras' => $extras ?: array()
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('error', 'Unable to retrieve extras', array('status' => 500));
        }
    }
    
    private function run_database_migrations() {
        // Include database class
        require_once CB_PLUGIN_DIR . 'includes/class-cb-database.php';
        
        // Run migrations
        CB_Database::run_migrations();
    }
}
