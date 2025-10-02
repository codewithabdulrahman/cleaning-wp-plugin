<?php
/**
 * Frontend class for booking wizard
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('cb_widget', array($this, 'booking_widget_shortcode'));
        add_action('wp_ajax_cb_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_nopriv_cb_create_booking', array($this, 'ajax_create_booking'));
    }
    
    public function enqueue_scripts() {
        // Only load on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'cb_widget')) {
            wp_enqueue_script('cb-frontend', CB_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CB_VERSION, true);
            wp_enqueue_style('cb-frontend', CB_PLUGIN_URL . 'assets/css/frontend.css', array(), CB_VERSION);
            
            wp_localize_script('cb-frontend', 'cb_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('cleaning-booking/v1/'),
                'nonce' => wp_create_nonce('cb_frontend_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'cleaning-booking'),
                    'error' => __('An error occurred. Please try again.', 'cleaning-booking'),
                    'invalid_zip' => __('Please enter a valid ZIP code.', 'cleaning-booking'),
                    'zip_not_available' => __('Sorry, we don\'t provide services in this area yet.', 'cleaning-booking'),
                    'select_service' => __('Please select a service.', 'cleaning-booking'),
                    'enter_sqm' => __('Please enter the square meters.', 'cleaning-booking'),
                    'select_date' => __('Please select a date.', 'cleaning-booking'),
                    'select_time' => __('Please select a time slot.', 'cleaning-booking'),
                    'enter_name' => __('Please enter your name.', 'cleaning-booking'),
                    'enter_email' => __('Please enter a valid email address.', 'cleaning-booking'),
                    'enter_phone' => __('Please enter your phone number.', 'cleaning-booking'),
                    'booking_created' => __('Booking created successfully!', 'cleaning-booking'),
                    'redirecting' => __('Redirecting to checkout...', 'cleaning-booking')
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
        
        ob_start();
        include CB_PLUGIN_DIR . 'templates/frontend/booking-widget.php';
        return ob_get_clean();
    }
    
    public function ajax_create_booking() {
        check_ajax_referer('cb_frontend_nonce', 'nonce');
        
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
            'extras' => array_map('intval', $_POST['extras']),
            'notes' => sanitize_textarea_field($_POST['notes'])
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
        
        // Create booking
        $booking_id = CB_Database::create_booking($data);
        
        if ($booking_id) {
            $booking = CB_Database::get_booking($booking_id);
            
            // Create WooCommerce order
            $woocommerce = new CB_WooCommerce();
            $order_url = $woocommerce->create_booking_order($booking);
            
            wp_send_json_success(array(
                'booking_id' => $booking_id,
                'booking_reference' => $booking->booking_reference,
                'redirect_url' => $order_url
            ));
        }
        
        wp_send_json_error(array('message' => __('Failed to create booking. Please try again.', 'cleaning-booking')));
    }
}
