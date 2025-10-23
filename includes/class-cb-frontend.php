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
        add_action('wp_ajax_cb_hold_slot', array($this, 'ajax_hold_slot'));
        add_action('wp_ajax_cb_release_slot', array($this, 'ajax_release_slot'));
        
        // AJAX actions for non-authenticated users (public access)
        add_action('wp_ajax_nopriv_cb_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_nopriv_cb_check_zip_availability', array($this, 'ajax_check_zip_availability'));
        add_action('wp_ajax_nopriv_cb_get_zip_surcharge', array($this, 'ajax_get_zip_surcharge'));
        add_action('wp_ajax_nopriv_cb_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_cb_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_nopriv_cb_get_extras', array($this, 'ajax_get_extras'));
        add_action('wp_ajax_nopriv_cb_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_cb_hold_slot', array($this, 'ajax_hold_slot'));
        add_action('wp_ajax_nopriv_cb_release_slot', array($this, 'ajax_release_slot'));
        
        // Clear WooCommerce session data when booking widget is loaded
        add_action('wp_footer', array($this, 'clear_woocommerce_session_on_widget_load'));
        // Add this with your other AJAX handlers:
        add_action('wp_ajax_cb_store_booking_session', array($this, 'ajax_store_booking_session'));
        add_action('wp_ajax_nopriv_cb_store_booking_session', array($this, 'ajax_store_booking_session'));
    }

    public function ajax_store_booking_session() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cb_frontend_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }
    
        // Store booking data in WooCommerce session
        if (!function_exists('WC') || !WC()) {
            wp_send_json_error(array('message' => 'WooCommerce is not active'));
            return;
        }
        
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }
    
        $booking_data = json_decode(stripslashes($_POST['booking_data']), true);
    
        // Store in WooCommerce session
        WC()->session->set('cb_booking_data', $booking_data);
        WC()->session->set('cb_booking_timestamp', time());
    
        // Generate checkout URL (this will create the product and get checkout URL)
        $woocommerce = new CB_WooCommerce();
        $checkout_url = $woocommerce->generate_checkout_url_from_session();
    
        if ($checkout_url) {
            wp_send_json_success(array(
                'message' => 'Booking data stored successfully',
                'checkout_url' => $checkout_url
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to generate checkout URL'));
        }
    }
    
    /**
     * Clear WooCommerce session data when booking widget is loaded
     * This ensures fresh data for each new booking
     */
    public function clear_woocommerce_session_on_widget_load() {
        // Only run if WooCommerce is active and we're on a page with the booking widget
        if (!class_exists('WooCommerce') || !function_exists('WC') || !WC() || !WC()->session) {
            return;
        }
        
        // Check if the booking widget is present on the page
        if (strpos(get_the_content(), '[cb_widget]') !== false || 
            strpos(get_the_content(), 'cb-widget') !== false ||
            is_page() && has_shortcode(get_post()->post_content, 'cb_widget')) {
            
            // Clear WooCommerce session data to prevent old data from persisting
            WC()->session->set('billing_first_name', '');
            WC()->session->set('billing_last_name', '');
            WC()->session->set('billing_company', '');
            WC()->session->set('billing_email', '');
            WC()->session->set('billing_phone', '');
            WC()->session->set('billing_address_1', '');
            WC()->session->set('billing_city', '');
            WC()->session->set('billing_state', '');
            WC()->session->set('billing_postcode', '');
            WC()->session->set('billing_country', '');
            
            WC()->session->set('shipping_first_name', '');
            WC()->session->set('shipping_last_name', '');
            WC()->session->set('shipping_company', '');
            WC()->session->set('shipping_address_1', '');
            WC()->session->set('shipping_city', '');
            WC()->session->set('shipping_state', '');
            WC()->session->set('shipping_postcode', '');
            WC()->session->set('shipping_country', '');
            
            WC()->session->set('order_comments', '');
            WC()->session->set('chosen_payment_method', '');
            WC()->session->set('customer_data', array());
            
            // Also clear WooCommerce customer data directly
            if (WC()->customer) {
                WC()->customer->set_billing_first_name('');
                WC()->customer->set_billing_last_name('');
                WC()->customer->set_billing_company('');
                WC()->customer->set_billing_email('');
                WC()->customer->set_billing_phone('');
                WC()->customer->set_billing_address_1('');
                WC()->customer->set_billing_city('');
                WC()->customer->set_billing_state('');
                WC()->customer->set_billing_postcode('');
                WC()->customer->set_billing_country('');
                
                WC()->customer->set_shipping_first_name('');
                WC()->customer->set_shipping_last_name('');
                WC()->customer->set_shipping_company('');
                WC()->customer->set_shipping_address_1('');
                WC()->customer->set_shipping_city('');
                WC()->customer->set_shipping_state('');
                WC()->customer->set_shipping_postcode('');
                WC()->customer->set_shipping_country('');
                
                WC()->customer->save();
            }
        }
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
                current_language: 'el',
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
            
            // Add dynamic CSS for admin color customization
            $this->add_dynamic_css();
            
            // Set high priority and ensure it loads before other scripts
            wp_script_add_data('cb-frontend-init', 'priority', 1);
            wp_script_add_data('cb-frontend', 'priority', 2);
            
            wp_localize_script('cb-frontend', 'cb_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('cleaning-booking/v1/'),
                'nonce' => wp_create_nonce('cb_frontend_nonce'),
                'current_language' => $this->get_current_language(),
                'booking_hold_time' => get_option('cb_booking_hold_time', 15), // Add booking hold time setting
                'translations' => $this->get_translations(),
                'form_fields' => CB_Form_Fields::get_frontend_fields($this->get_current_language()),
                'style_settings' => CB_Style_Manager::get_all_settings(),
                'colors' => array(
                    'primary' => get_option('cb_primary_color', '#0073aa'),
                    'secondary' => get_option('cb_secondary_color', '#00a0d2'),
                    'background' => get_option('cb_background_color', '#ffffff'),
                    'text' => get_option('cb_text_color', '#333333'),
                    'border' => get_option('cb_border_color', '#dddddd'),
                    'success' => get_option('cb_success_color', '#46b450'),
                    'error' => get_option('cb_error_color', '#dc3232'),
                ),
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
            'show_steps' => 'true'
        ), $atts);
        
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
        
        if ($data['square_meters'] < 0) {
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
        
        // Assign available truck automatically
        $available_truck = CB_Database::get_available_truck($data['booking_date'], $data['booking_time'], $total_duration);
        if ($available_truck) {
            $data['truck_id'] = $available_truck->id;
        } else {
            wp_send_json_error(array('message' => __('No trucks available for the selected time slot. Please choose another time.', 'cleaning-booking')));
        }
        
        // Create booking
        $booking_id = CB_Database::create_booking($data);
        
        if ($booking_id) {
            $booking = CB_Database::get_booking($booking_id);
            
            // Create WooCommerce order
            $woocommerce = new CB_WooCommerce();
            $order_url = $woocommerce->create_booking_order($booking);
                
                // If WooCommerce is not available, provide a fallback
                if (!$order_url) {
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
        
        // Use the proper slot manager to get available slots
        $slot_manager = new CB_Slot_Manager();
        $slots = $slot_manager->get_available_slots($booking_date, $duration);
        
        wp_send_json_success(array('slots' => $slots));
    }
public function ajax_get_services() {
    // Public endpoint - services listing (no authentication needed)
    
    try {
        // Ensure migrations are run before getting services
        CB_Database::run_migrations();
        
        // Get current language
        $language = $this->get_current_language();
        
        // Get translated services WITH icons
        $services = $this->get_translated_services($language);
        
        if ($services === false || empty($services)) {
            wp_send_json_success(array('services' => array(), 'message' => 'No services found'));
        }
        
        // Ensure icon_url is properly included in each service
        $formatted_services = array();
        foreach ($services as $service) {
            $formatted_service = array(
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'base_price' => floatval($service->base_price),
                'base_duration' => intval($service->base_duration),
                'sqm_multiplier' => floatval($service->sqm_multiplier),
                'sqm_duration_multiplier' => floatval($service->sqm_duration_multiplier),
                'default_area' => isset($service->default_area) ? intval($service->default_area) : 0,
                'icon_url' => isset($service->icon_url) ? $service->icon_url : '', // Make sure this is included
                'is_active' => $service->is_active,
                'sort_order' => $service->sort_order
            );
            
            $formatted_services[] = $formatted_service;
        }
        
        wp_send_json_success(array('services' => $formatted_services));
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
        
        // Get current language
        $language = $this->get_current_language();
        
        // Get active extras for the selected service
        $extras = CB_Database::get_service_extras($service_id, true);
        
        // Translate extras
        $translated_extras = $this->get_translated_extras($language);
        
        wp_send_json_success(array('extras' => $translated_extras));
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
        // Priority 1: Check if user has manually selected a language (stored in cookie)
        if (isset($_COOKIE['cb_language'])) {
            $cookie_language = sanitize_text_field($_COOKIE['cb_language']);
            if (in_array($cookie_language, array('en', 'el'))) {
                return $cookie_language;
            }
        }
        
        // Priority 2: Location-based detection (Greece = Greek, others = English)
        $geo_language = $this->detect_geolocation_language();
        if ($geo_language) {
            return $geo_language;
        }
        
        // Priority 3: Check browser language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_language = $this->detect_browser_language($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if ($browser_language) {
                return $browser_language;
            }
        }
        
        // Priority 4: Check WordPress locale
        $locale = get_locale();
        $language = substr($locale, 0, 2);
        if (in_array($language, array('en', 'el'))) {
            return $language;
        }
        
        // Priority 5: Default to English (changed from Greek)
        return 'en';
    }
    
    private function detect_browser_language($accept_language) {
        // Parse Accept-Language header
        $languages = array();
        $accept_language = strtolower($accept_language);
        
        // Split by comma and parse each language
        $parts = explode(',', $accept_language);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, ';') !== false) {
                list($lang, $q) = explode(';', $part, 2);
                $q = floatval(str_replace('q=', '', $q));
            } else {
                $lang = $part;
                $q = 1.0;
            }
            
            $lang = trim($lang);
            if (strlen($lang) >= 2) {
                $languages[substr($lang, 0, 2)] = $q;
            }
        }
        
        // Sort by quality score
        arsort($languages);
        
        // Check for supported languages
        foreach ($languages as $lang => $q) {
            if ($lang === 'en') {
                return 'en';
            } elseif ($lang === 'el') {
                return 'el';
            }
        }
        
        return null;
    }
    
    private function detect_geolocation_language() {
        // Get user's IP address
        $ip = $this->get_user_ip();
        if (!$ip) {
            return 'en'; // Default to English if IP not available
        }
        
        // Use a free geolocation service
        $geo_data = $this->get_geolocation_data($ip);
        if ($geo_data && isset($geo_data['country_code'])) {
            $country_code = strtoupper($geo_data['country_code']);
            
            // Map country codes to languages
            $country_language_map = array(
                'GR' => 'el',  // Greece
                'CY' => 'el',  // Cyprus
                // All other countries default to English
            );
            
            if (isset($country_language_map[$country_code])) {
                return $country_language_map[$country_code];
            }
        }
        
        // Default to English for all other countries
        return 'en';
    }
    
    private function get_user_ip() {
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
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }
    
    private function get_geolocation_data($ip) {
        // Use a free geolocation service
        // Note: In production, you might want to use a more reliable service
        $url = "http://ip-api.com/json/{$ip}";
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'user-agent' => 'Cleaning Booking Plugin'
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data && $data['status'] === 'success') {
            return $data;
        }
        
        return null;
    }
    
    private function get_translations() {
        // Load translation file based on current language
        $language = $this->get_current_language();
        return $this->get_translations_for_language($language);
    }
    
    private function get_translations_for_language($language) {
        // Try to get translations from database first
        $db_translations = CB_Translations::get_all_translations($language);
        
        if (!empty($db_translations)) {
            return $db_translations;
        }
        
        // Fallback to JSON files if database is empty
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
    // Get original services from database WITH icons
    $services = CB_Database::get_services(true);
    
    if (!$services) {
        return array();
    }
    
    $translated_services = array();
    foreach ($services as $service) {
        $service_id = intval($service->id);
        
        // Create service object with ALL original properties including icon_url
        $translated_service = new stdClass();
        $translated_service->id = $service_id;
        $translated_service->name = CB_Translations::get_translation('service_name_' . $service_id, $language, 'services') ?: $service->name;
        $translated_service->description = CB_Translations::get_translation('service_desc_' . $service_id, $language, 'services') ?: $service->description;
        $translated_service->base_price = $service->base_price;
        $translated_service->base_duration = $service->base_duration;
        $translated_service->sqm_multiplier = $service->sqm_multiplier;
        $translated_service->sqm_duration_multiplier = $service->sqm_duration_multiplier;
        $translated_service->default_area = isset($service->default_area) ? $service->default_area : 0;
        $translated_service->icon_url = isset($service->icon_url) ? $service->icon_url : ''; // CRITICAL: Preserve icon_url
        $translated_service->is_active = $service->is_active;
        $translated_service->sort_order = $service->sort_order;
        
        $translated_services[] = $translated_service;
    }
    
    return $translated_services;
}
    private function get_translated_extras($language = 'en') {
        $extras = CB_Database::get_all_extras(true);
        $translated_extras = array();
        
        foreach ($extras as $extra) {
            $extra_id = intval($extra->id);
            $translated_extras[] = array(
                'id' => $extra_id,
                'service_id' => intval($extra->service_id),
                'name' => CB_Translations::get_translation('extra_name_' . $extra_id, $language, 'extras') ?: $extra->name,
                'description' => CB_Translations::get_translation('extra_desc_' . $extra_id, $language, 'extras') ?: $extra->description,
                'price' => $extra->price,
                'duration' => isset($extra->duration) ? intval($extra->duration) : 0,
                'is_active' => isset($extra->is_active) ? intval($extra->is_active) : 1,
                'sort_order' => isset($extra->sort_order) ? intval($extra->sort_order) : 0
            );
        }
        
        return $translated_extras;
    }
    
    private function get_available_slots_data() {
        // This method provides sample slot data - in real implementation, 
        // this would generate actual available slots
        $current_date = date('Y-m-d');
        $next_date = date('Y-m-d', strtotime('+1 day'));
        
        return array(
            'today' => array(
                'date' => $current_date,
                'slots' => array(
                    array('time' => '09:00', 'available' => true),
                    array('time' => '10:00', 'available' => true),
                    array('time' => '11:00', 'available' => true),
                    array('time' => '14:00', 'available' => true),
                    array('time' => '15:00', 'available' => true),
                    array('time' => '16:00', 'available' => true)
                )
            ),
            'tomorrow' => array(
                'date' => $next_date,
                'slots' => array(
                    array('time' => '09:00', 'available' => true),
                    array('time' => '10:00', 'available' => true),
                    array('time' => '11:00', 'available' => true),
                    array('time' => '14:00', 'available' => true),
                    array('time' => '15:00', 'available' => true),
                    array('time' => '16:00', 'available' => true)
                )
            )
        );
    }
    
    public function ajax_switch_language() {
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        
        // Validate language
        $allowed_languages = array('en', 'el');
        if (!in_array($language, $allowed_languages)) {
            $language = 'en';
        }
        
        // Set cookie to remember user's language preference
        setcookie('cb_language', $language, time() + (365 * 24 * 60 * 60), '/'); // 1 year
        
        // Load translations from database
        $translations = CB_Translations::get_all_translations($language);
        
        // Get translated services data
        $translated_services = $this->get_translated_services($language);
        
        // Get translated extras data
        $translated_extras = $this->get_translated_extras($language);
        
        // Get form fields in the selected language
        $form_fields = CB_Form_Fields::get_frontend_fields($language);
        
        // Get all available slots (this will use the new language for messages)
        $available_slots = $this->get_available_slots_data();
        
        // Return comprehensive updated data
        wp_send_json_success(array(
            'language' => $language,
            'translations' => $translations,
            'services' => $translated_services,
            'extras' => $translated_extras,
            'form_fields' => $form_fields,
            'available_slots' => $available_slots,
            'message' => isset($translations['Language switched successfully']) ? $translations['Language switched successfully'] : 'Language switched successfully'
        ));
    }
    
    
    public function ajax_hold_slot() {
        try {
            $booking_date = sanitize_text_field($_POST['booking_date']);
            $booking_time = sanitize_text_field($_POST['booking_time']);
            $duration = intval($_POST['duration']);
            
            if (empty($booking_date) || empty($booking_time)) {
                wp_send_json_error(array('message' => __('Date and time are required.', 'cleaning-booking')));
            }
            
            $slot_manager = new CB_Slot_Manager();
            $session_id = $slot_manager->hold_slot($booking_date, $booking_time, $duration);
            
            if ($session_id) {
                wp_send_json_success(array(
                    'session_id' => $session_id,
                    'message' => __('Time slot held successfully.', 'cleaning-booking')
                ));
            } else {
                wp_send_json_error(array('message' => __('Slot is no longer available.', 'cleaning-booking')));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to hold slot. Please try again.', 'cleaning-booking')));
        }
    }
    
    public function ajax_release_slot() {
        try {
            $session_id = sanitize_text_field($_POST['session_id']);
            
            if (empty($session_id)) {
                wp_send_json_error(array('message' => __('Session ID is required.', 'cleaning-booking')));
            }
            
            $slot_manager = new CB_Slot_Manager();
            $result = $slot_manager->release_slot($session_id);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Slot released successfully.', 'cleaning-booking')));
            } else {
                wp_send_json_error(array('message' => __('Failed to release slot.', 'cleaning-booking')));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to release slot. Please try again.', 'cleaning-booking')));
        }
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
        
        // Format services to include icon_url
        $formatted_services = array();
        foreach ($services as $service) {
            $formatted_services[] = array(
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'base_price' => floatval($service->base_price),
                'base_duration' => intval($service->base_duration),
                'sqm_multiplier' => floatval($service->sqm_multiplier),
                'sqm_duration_multiplier' => floatval($service->sqm_duration_multiplier),
                'default_area' => isset($service->default_area) ? intval($service->default_area) : 0,
                'icon_url' => isset($service->icon_url) ? $service->icon_url : '', // Make sure this is included
                'is_active' => $service->is_active,
                'sort_order' => $service->sort_order
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'services' => $formatted_services
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
            $additional_area = intval($_POST['square_meters'], 10); // Force base 10 to prevent octal interpretation
            $use_smart_area = isset($_POST['use_smart_area']) && $_POST['use_smart_area'] === '1';
            
            // Handle extras properly - it might be an empty string or array
            $extras = array();
            if (isset($_POST['extras']) && !empty($_POST['extras'])) {
                if (is_array($_POST['extras'])) {
                    $extras = array_map('intval', $_POST['extras']);
                } else {
                    // If it's a string, try to decode it as JSON or split by comma
                    $extras_string = trim($_POST['extras']);
                    if (!empty($extras_string)) {
                        // Try JSON decode first
                        $decoded = json_decode($extras_string, true);
                        if (is_array($decoded)) {
                            $extras = array_map('intval', $decoded);
                        } else {
                            // Fallback: split by comma
                            $extras = array_map('intval', explode(',', $extras_string));
                        }
                    }
                }
            }
            
            $zip_code = sanitize_text_field($_POST['zip_code']);

            // Validate required fields
            if (empty($service_id)) {
                wp_send_json_error(array('message' => __('Service is required for price calculation.', 'cleaning-booking')));
            }
            
            // Check if CB_Pricing class exists
            if (!class_exists('CB_Pricing')) {
                wp_send_json_error(array('message' => __('Pricing system not available.', 'cleaning-booking')));
            }
            
            // Get service data to check for default area
            $service = CB_Database::get_service($service_id);
            if (!$service) {
                wp_send_json_error(array('message' => __('Service not found.', 'cleaning-booking')));
            }
            
            $total_area = $additional_area;
            $base_area_included = 0;
            
            if ($use_smart_area && $service->default_area > 0) {
                // Smart area calculation: base area + additional area
                $base_area_included = $service->default_area;
                $total_area = $base_area_included + $additional_area;
            }
            
            // Calculate pricing breakdown using ONLY additional area (not total area)
            // The base service is handled separately in smart_area calculation
            $pricing = CB_Pricing::get_pricing_breakdown($service_id, $additional_area, $extras, $zip_code);
            
            // Add smart area info to response
            $pricing['smart_area'] = array(
                'use_smart_area' => $use_smart_area,
                'base_area_included' => $base_area_included,
                'additional_area' => $additional_area,
                'total_area' => $total_area,
                'base_price' => $service->base_price,
                'base_duration' => $service->base_duration,
                'additional_price' => $use_smart_area && $additional_area > 0 ? ($additional_area * $service->sqm_multiplier) : 0,
                'additional_duration' => $use_smart_area && $additional_area > 0 ? ($additional_area * $service->sqm_duration_multiplier) : 0
            );
            
            // Override pricing for smart area calculation
            if ($use_smart_area && $service->default_area > 0) {
                // For smart area, we want base service + additional area pricing
                // Only add additional area pricing if additional_area > 0
                if ($additional_area > 0) {
                    $pricing['service_price'] = floatval($service->base_price) + ($additional_area * floatval($service->sqm_multiplier));
                    $pricing['service_duration'] = intval($service->base_duration) + ($additional_area * floatval($service->sqm_duration_multiplier));
                } else {
                    // When additional_area is 0, use base service only
                    $pricing['service_price'] = floatval($service->base_price);
                    $pricing['service_duration'] = intval($service->base_duration);
                }
                $pricing['total_price'] = $pricing['service_price'] + $pricing['extras_price'] + $pricing['zip_surcharge'];
                $pricing['total_duration'] = max($pricing['service_duration'] + $pricing['extras_duration'], 30);
            }
            
            wp_send_json_success(array(
                'pricing' => $pricing,
                'formatted_price' => CB_Pricing::format_price($pricing['total_price']),
                'formatted_duration' => CB_Pricing::format_duration($pricing['total_duration'])
            ));
            
        } catch (Exception $e) {
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
    
    /**
     * Add dynamic CSS for admin color customization
     */
    private function add_dynamic_css() {
        // Get color settings
        $primary_color = get_option('cb_primary_color', '#0073aa');
        $secondary_color = get_option('cb_secondary_color', '#00a0d2');
        $background_color = get_option('cb_background_color', '#ffffff');
        $text_color = get_option('cb_text_color', '#333333');
        $border_color = get_option('cb_border_color', '#dddddd');
        $success_color = get_option('cb_success_color', '#46b450');
        $error_color = get_option('cb_error_color', '#dc3232');
        
        // Get style settings from database
        $style_settings = CB_Style_Manager::get_all_settings();
        
        // Generate CSS variables
        $css_vars = CB_Style_Manager::generate_css_variables();
        
        // Get Google Fonts URL if needed
        $google_fonts_url = CB_Style_Manager::get_google_fonts_url();
        
        $css = "
        <style id='cb-dynamic-styles'>
        :root {
            /* Color Variables */
            --cb-primary-color: {$primary_color};
            --cb-secondary-color: {$secondary_color};
            --cb-background-color: {$background_color};
            --cb-text-color: {$text_color};
            --cb-border-color: {$border_color};
            --cb-success-color: {$success_color};
            --cb-error-color: {$error_color};
            
            /* Typography Variables */
            --cb-heading-font-family: " . CB_Style_Manager::get_setting('heading_font_family', 'Inter, sans-serif') . ";
            --cb-body-font-family: " . CB_Style_Manager::get_setting('body_font_family', 'Inter, sans-serif') . ";
            --cb-heading-font-size: " . CB_Style_Manager::get_setting('heading_font_size', '28px') . ";
            --cb-body-font-size: " . CB_Style_Manager::get_setting('body_font_size', '16px') . ";
            --cb-label-font-size: " . CB_Style_Manager::get_setting('label_font_size', '14px') . ";
            --cb-button-font-size: " . CB_Style_Manager::get_setting('button_font_size', '16px') . ";
            --cb-font-weight-heading: " . CB_Style_Manager::get_setting('font_weight_heading', '700') . ";
            --cb-font-weight-body: " . CB_Style_Manager::get_setting('font_weight_body', '400') . ";
            
            /* Spacing Variables */
            --cb-form-padding: " . CB_Style_Manager::get_setting('form_padding', '24px') . ";
            --cb-field-spacing: " . CB_Style_Manager::get_setting('field_spacing', '20px') . ";
            --cb-button-padding: " . CB_Style_Manager::get_setting('button_padding', '16px 32px') . ";
            --cb-container-max-width: " . CB_Style_Manager::get_setting('container_max_width', '1200px') . ";
            
            /* Layout Variables */
            --cb-sidebar-position: " . CB_Style_Manager::get_setting('sidebar_position', 'right') . ";
            --cb-step-indicator-style: " . CB_Style_Manager::get_setting('step_indicator_style', 'numbered') . ";
            --cb-mobile-breakpoint: " . CB_Style_Manager::get_setting('mobile_breakpoint', '768px') . ";
        }
        
        /* Google Fonts */
        @import url('{$google_fonts_url}');
        
        /* Typography Styles */
        .cb-widget h1, .cb-widget h2, .cb-widget h3, .cb-widget h4, .cb-widget h5, .cb-widget h6 {
            font-family: var(--cb-heading-font-family);
            font-size: var(--cb-heading-font-size);
            font-weight: var(--cb-font-weight-heading);
        }
        
        .cb-widget, .cb-widget p, .cb-widget span, .cb-widget div {
            font-family: var(--cb-body-font-family);
            font-size: var(--cb-body-font-size);
            font-weight: var(--cb-font-weight-body);
        }
        
        .cb-widget label {
            font-size: var(--cb-label-font-size);
        }
        
        .cb-widget .cb-btn {
            font-size: var(--cb-button-font-size);
            padding: var(--cb-button-padding);
        }
        
        /* Spacing Styles */
        .cb-widget .cb-form-group {
            padding: var(--cb-form-padding);
            margin-bottom: var(--cb-field-spacing);
        }
        
        .cb-widget-container {
            max-width: var(--cb-container-max-width);
        }
        
        /* Layout Styles */
        .cb-content {
            display: grid;
            gap: var(--cb-field-spacing);
        }
        /* Default: sidebar on the right */
        .cb-content.cb-sidebar-right {
            grid-template-columns: 2fr 1fr;
            grid-template-areas: \"main sidebar\";
        }
        .cb-main-content.cb-sidebar-right {
            border-right: 1px solid var(--cb-border-color);
            border-left: none;
        }
        /* Sidebar on the left */
        .cb-content.cb-sidebar-left {
            grid-template-columns: 1fr 2fr;
            grid-template-areas: \"sidebar main\";
        }
        .cb-main-content.cb-sidebar-left {
            border-left: 1px solid var(--cb-border-color);
            border-right: none;
        }
        /* Add breathing room */
        .cb-main-content,
        .cb-checkout-sidebar {
            padding: var(--cb-form-padding);
        }
        
        /* Mobile Responsive */
        @media (max-width: var(--cb-mobile-breakpoint)) {
            .cb-content,
            .cb-content.cb-sidebar-right,
            .cb-content.cb-sidebar-left {
                grid-template-columns: 1fr;
                grid-template-areas: \"main\" \"sidebar\";
            }
        }
        
        /* Color Styles */
        .cb-input {
            border-color: var(--cb-border-color);
        }
        
        .cb-input:focus {
            border-color: var(--cb-primary-color);
        }
        
        .cb-service-card {
            border-color: var(--cb-border-color);
        }
        
        .cb-time-slot {
            border-color: var(--cb-border-color);
        }
        
        .cb-payment-option {
            border-color: var(--cb-border-color);
        }
        
        .cb-btn-primary {
            background-color: var(--cb-primary-color);
            color: white;
        }
        
        .cb-btn-primary:hover {
            background-color: var(--cb-secondary-color);
        }
        
        .cb-success-message {
            color: var(--cb-success-color);
        }
        
        .cb-error-message {
            color: var(--cb-error-color);
        }
        </style>";
        
        echo $css;
    }
}
