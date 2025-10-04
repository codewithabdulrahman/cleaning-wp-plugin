<?php
/**
 * WooCommerce integration class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_WooCommerce {
    
    public function __construct() {
        // Only initialize if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        add_action('woocommerce_checkout_process', array($this, 'process_booking_checkout'));
        add_action('woocommerce_checkout_order_processed', array($this, 'link_booking_to_order'));
        add_action('woocommerce_order_status_changed', array($this, 'update_booking_status'));
        add_action('woocommerce_order_status_cancelled', array($this, 'handle_order_cancellation'));
        add_action('woocommerce_order_status_refunded', array($this, 'handle_order_cancellation'));
        add_action('woocommerce_order_status_failed', array($this, 'handle_order_cancellation'));
        
        // Add booking details to order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_booking_details'));
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_booking_details_frontend'));
        
        // Handle booking checkout redirect
        add_action('template_redirect', array($this, 'handle_booking_checkout_redirect'));
        
        // Ensure payment gateways are enabled
        add_action('init', array($this, 'ensure_payment_gateways_enabled'));
        
        // Add admin notice for payment gateway issues
        add_action('admin_notices', array($this, 'admin_payment_gateway_notice'));
    }
    
    public function create_booking_order($booking) {
        if (!$booking) {
            error_log('CB Debug - No booking provided to create_booking_order');
            return false;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            error_log('CB Debug - WooCommerce is not active');
            return false;
        }
        
        // Check if WooCommerce functions are available
        if (!function_exists('wc_get_checkout_url')) {
            error_log('CB Debug - WooCommerce functions not available');
            return false;
        }
        
        // Create a dynamic WooCommerce product
        $product_id = $this->create_booking_product($booking);
        
        if (!$product_id) {
            error_log('CB Debug - Failed to create booking product');
            return false;
        }
        
        error_log('CB Debug - Created product ID: ' . $product_id);
        
        // Create checkout URL with booking data
        $checkout_url = add_query_arg(array(
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'add-to-cart' => $product_id,
            'quantity' => 1
        ), wc_get_checkout_url());
        
        error_log('CB Debug - Generated checkout URL: ' . $checkout_url);
        
        return $checkout_url;
    }
    
    private function create_booking_product($booking) {
        // Check if product already exists for this booking
        $existing_product = get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_cb_booking_id',
                    'value' => $booking->id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_product)) {
            return $existing_product[0]->ID;
        }
        
        // Create new product
        $product_data = array(
            'post_title' => sprintf('Cleaning Service - %s', $booking->booking_reference),
            'post_content' => $this->get_booking_description($booking),
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => array(
                '_cb_booking_id' => $booking->id,
                '_cb_booking_reference' => $booking->booking_reference,
                '_price' => $booking->total_price,
                '_regular_price' => $booking->total_price,
                '_sale_price' => '',
                '_manage_stock' => 'no',
                '_stock_status' => 'instock',
                '_virtual' => 'yes',
                '_downloadable' => 'no',
                '_sold_individually' => 'yes'
            )
        );
        
        $product_id = wp_insert_post($product_data);
        
        if ($product_id) {
            // Set product type
            wp_set_object_terms($product_id, 'simple', 'product_type');
            
            // Add booking metadata
            update_post_meta($product_id, '_cb_booking_data', array(
                'service_id' => $booking->service_id,
                'square_meters' => $booking->square_meters,
                'total_duration' => $booking->total_duration,
                'booking_date' => $booking->booking_date,
                'booking_time' => $booking->booking_time,
                'extras' => json_decode($booking->extras_data, true)
            ));
        }
        
        return $product_id;
    }
    
    private function get_booking_description($booking) {
        global $wpdb;
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}cb_services WHERE id = %d",
            $booking->service_id
        ));
        
        $service_name = $service ? $service->name : 'Cleaning Service';
        
        $description = sprintf(
            "Booking Reference: %s\nService: %s\nSquare Meters: %d\nDate: %s\nTime: %s\nDuration: %d minutes",
            $booking->booking_reference,
            $service_name,
            $booking->square_meters,
            $booking->booking_date,
            $booking->booking_time,
            $booking->total_duration
        );
                    
                    if ($booking->extras_data) {
                        $extras = json_decode($booking->extras_data, true);
                        if (!empty($extras)) {
                $description .= "\n\nAdditional Services:";
                            foreach ($extras as $extra) {
                    $description .= "\n- " . $extra['name'] . " (x" . $extra['quantity'] . ")";
                }
            }
        }
        
        return $description;
    }
    
    public function handle_booking_checkout_redirect() {
        if (is_checkout() && isset($_GET['booking_id']) && isset($_GET['booking_reference'])) {
            $booking_id = intval($_GET['booking_id']);
            $booking_reference = sanitize_text_field($_GET['booking_reference']);
            
            $booking = CB_Database::get_booking($booking_id);
            
            if ($booking && $booking->booking_reference === $booking_reference) {
                // Check if payment gateways are available
                $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
                
                if (empty($available_gateways)) {
                    // Try to enable payment gateways
                    $this->ensure_payment_gateways_enabled();
                    
                    // Check again after enabling
                    $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
                    
                    if (empty($available_gateways)) {
                        // Show error message if still no gateways
                        wc_add_notice(
                            __('No payment methods are currently available. Please contact us to complete your booking.', 'cleaning-booking'),
                            'error'
                        );
                    }
                }
                
                // Hold the slot during checkout
                $this->hold_booking_slot($booking);
                
                // Add booking data to session
                WC()->session->set('cb_booking_id', $booking_id);
                WC()->session->set('cb_booking_reference', $booking_reference);
            }
        }
    }
    
    private function hold_booking_slot($booking) {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        $hold_duration = get_option('cb_booking_hold_time', 15); // minutes
        
        $hold_data = array(
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'duration' => $booking->total_duration,
            'session_id' => WC()->session->get_customer_id(),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$hold_duration} minutes"))
        );
        
        $wpdb->insert($slot_holds_table, $hold_data);
    }
    
    public function process_booking_checkout() {
        $booking_id = WC()->session->get('cb_booking_id');
        
        if (!$booking_id) {
            return;
        }
        
        $booking = CB_Database::get_booking($booking_id);
        
        if (!$booking) {
            wc_add_notice(__('Invalid booking reference.', 'cleaning-booking'), 'error');
            return;
        }
        
        // Check if slot is still available
        if (!$this->is_slot_available($booking)) {
            wc_add_notice(__('Sorry, this time slot is no longer available.', 'cleaning-booking'), 'error');
            return;
        }
    }
    
    public function link_booking_to_order($order_id) {
        $booking_id = WC()->session->get('cb_booking_id');
        
        if ($booking_id) {
            // Update booking with order ID
            CB_Database::update_booking_status($booking_id, 'paid', $order_id);
            
            // Clear session data
            WC()->session->set('cb_booking_id', null);
            WC()->session->set('cb_booking_reference', null);
            
            // Remove slot hold
            $this->release_slot_hold($booking_id);
        }
    }
    
    public function update_booking_status($order_id, $old_status, $new_status) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_bookings WHERE woocommerce_order_id = %d",
            $order_id
        ));
        
        if ($booking) {
            $booking_status = $this->map_order_status_to_booking_status($new_status);
            CB_Database::update_booking_status($booking->id, $booking_status, $order_id);
        }
    }
    
    public function handle_order_cancellation($order_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_bookings WHERE woocommerce_order_id = %d",
            $order_id
        ));
        
        if ($booking) {
        // Update booking status
            CB_Database::update_booking_status($booking->id, 'cancelled', $order_id);
            
            // Release the slot
            $this->release_slot_hold($booking->id);
        }
    }
    
    private function map_order_status_to_booking_status($order_status) {
        $status_map = array(
            'pending' => 'pending',
            'processing' => 'confirmed',
            'on-hold' => 'pending',
            'completed' => 'confirmed',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'failed' => 'cancelled'
        );
        
        return isset($status_map[$order_status]) ? $status_map[$order_status] : 'pending';
    }
    
    private function is_slot_available($booking) {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        
        // Check for existing bookings
        $existing_booking = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE booking_date = %s 
             AND booking_time = %s 
             AND status IN ('confirmed', 'paid') 
             AND id != %d",
            $booking->booking_date,
            $booking->booking_time,
            $booking->id
        ));
        
        if ($existing_booking > 0) {
            return false;
        }
        
        // Check for active holds (excluding current session)
        $active_hold = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $slot_holds_table 
             WHERE booking_date = %s 
             AND booking_time = %s 
             AND expires_at > NOW() 
             AND session_id != %s",
            $booking->booking_date,
            $booking->booking_time,
            WC()->session->get_customer_id()
        ));
        
        return $active_hold == 0;
    }
    
    private function release_slot_hold($booking_id) {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        
        $wpdb->delete($slot_holds_table, array(
            'session_id' => WC()->session->get_customer_id()
        ));
    }
    
    public function display_booking_details($order) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_bookings WHERE woocommerce_order_id = %d",
            $order->get_id()
        ));
        
        if ($booking) {
            $this->render_booking_details($booking);
        }
    }
    
    public function display_booking_details_frontend($order) {
        $this->display_booking_details($order);
    }
    
    private function render_booking_details($booking) {
        global $wpdb;
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}cb_services WHERE id = %d",
            $booking->service_id
        ));
        
        $service_name = $service ? $service->name : 'Unknown Service';
        
        echo '<div class="cb-booking-details">';
        echo '<h3>' . __('Booking Details', 'cleaning-booking') . '</h3>';
        echo '<table class="widefat">';
        echo '<tr><td><strong>' . __('Booking Reference', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->booking_reference) . '</td></tr>';
        echo '<tr><td><strong>' . __('Service', 'cleaning-booking') . ':</strong></td><td>' . esc_html($service_name) . '</td></tr>';
        echo '<tr><td><strong>' . __('Square Meters', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->square_meters) . '</td></tr>';
        echo '<tr><td><strong>' . __('Date', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->booking_date) . '</td></tr>';
        echo '<tr><td><strong>' . __('Time', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->booking_time) . '</td></tr>';
        echo '<tr><td><strong>' . __('Duration', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->total_duration) . ' ' . __('minutes', 'cleaning-booking') . '</td></tr>';
        echo '<tr><td><strong>' . __('Customer', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->customer_name) . '</td></tr>';
        echo '<tr><td><strong>' . __('Email', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->customer_email) . '</td></tr>';
        echo '<tr><td><strong>' . __('Phone', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->customer_phone) . '</td></tr>';
        echo '<tr><td><strong>' . __('Address', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->address) . '</td></tr>';
        
        if ($booking->extras_data) {
            $extras = json_decode($booking->extras_data, true);
            if (!empty($extras)) {
                echo '<tr><td><strong>' . __('Additional Services', 'cleaning-booking') . ':</strong></td><td>';
                foreach ($extras as $extra) {
                    echo esc_html($extra['name']) . ' (x' . esc_html($extra['quantity']) . ')<br>';
                }
                echo '</td></tr>';
            }
        }
        
        if ($booking->notes) {
            echo '<tr><td><strong>' . __('Notes', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->notes) . '</td></tr>';
        }
        
        echo '</table>';
        echo '</div>';
    }
    
    public function ensure_payment_gateways_enabled() {
        // Only run if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Check if any payment gateways are enabled
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        
        if (empty($available_gateways)) {
            // Enable Cash on Delivery if available
            $gateways = WC()->payment_gateways()->payment_gateways();
            
            if (isset($gateways['cod'])) {
                // Enable Cash on Delivery
                $gateway_settings = get_option('woocommerce_cod_settings', array());
                $gateway_settings['enabled'] = 'yes';
                $gateway_settings['title'] = __('Cash on Delivery', 'cleaning-booking');
                $gateway_settings['description'] = __('Pay when service is completed', 'cleaning-booking');
                update_option('woocommerce_cod_settings', $gateway_settings);
                
                error_log('CB Debug - Enabled Cash on Delivery payment gateway');
            }
            
            // Also try to enable Bank Transfer if available
            if (isset($gateways['bacs'])) {
                $gateway_settings = get_option('woocommerce_bacs_settings', array());
                $gateway_settings['enabled'] = 'yes';
                $gateway_settings['title'] = __('Bank Transfer', 'cleaning-booking');
                update_option('woocommerce_bacs_settings', $gateway_settings);
                
                error_log('CB Debug - Enabled Bank Transfer payment gateway');
            }
        }
    }
    
    public function admin_payment_gateway_notice() {
        // Only show on admin pages
        if (!is_admin()) {
            return;
        }
        
        // Only show if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Check if any payment gateways are enabled
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        
        if (empty($available_gateways)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Cleaning Booking Plugin', 'cleaning-booking') . ':</strong> ';
            echo __('No WooCommerce payment gateways are enabled. Please enable at least one payment method in ', 'cleaning-booking');
            echo '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">';
            echo __('WooCommerce → Settings → Payments', 'cleaning-booking');
            echo '</a> to allow customers to complete bookings.</p>';
            echo '</div>';
        }
    }
}
