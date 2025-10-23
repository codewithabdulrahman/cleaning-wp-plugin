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
        add_action('woocommerce_new_order', array($this, 'link_booking_to_order'));
        add_action('woocommerce_thankyou', array($this, 'link_booking_to_order_thankyou'));
        add_action('woocommerce_order_status_changed', array($this, 'update_booking_status'));
        
        // Specific hooks for payment completion
        add_action('woocommerce_order_status_processing', array($this, 'update_booking_status'));
        add_action('woocommerce_order_status_completed', array($this, 'update_booking_status'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        
        add_action('woocommerce_order_status_cancelled', array($this, 'handle_order_cancellation'));
        add_action('woocommerce_order_status_refunded', array($this, 'handle_order_cancellation'));
        add_action('woocommerce_order_status_failed', array($this, 'handle_order_cancellation'));
        
        // Add booking details to order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_booking_details'));
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_booking_details_frontend'));
        
        // Display booking details on checkout page
        add_action('woocommerce_checkout_before_customer_details', array($this, 'display_booking_details_on_checkout'));
        
        // Handle booking checkout redirect
        add_action('template_redirect', array($this, 'handle_booking_checkout_redirect'));
        
        // Pre-fill checkout fields
        add_action('woocommerce_checkout_init', array($this, 'prefill_checkout_fields_from_session'));
        
        // Ensure payment gateways are enabled
        add_action('init', array($this, 'ensure_payment_gateways_enabled'));
        
        // Add admin notice for payment gateway issues
        add_action('admin_notices', array($this, 'admin_payment_gateway_notice'));
    }
    
    public function create_booking_order($booking) {
        if (!$booking) {
            return false;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Check if WooCommerce functions are available
        if (!function_exists('wc_get_checkout_url')) {
            return false;
        }
        
        // Create a dynamic WooCommerce product
        $product_id = $this->create_booking_product($booking);
        
        if (!$product_id) {
            return false;
        }
        
        // Create checkout URL with booking data
        $checkout_url = add_query_arg(array(
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'add-to-cart' => $product_id,
            'quantity' => 1
        ), wc_get_checkout_url());
        
        // Pre-fill WooCommerce checkout fields with customer details
        $this->prefill_checkout_fields($booking);
        
        error_log('CB Debug - Generated checkout URL: ' . $checkout_url);
        
        return $checkout_url;
    }
    
    /**
     * Pre-fill WooCommerce checkout fields with customer details from booking
     */
    private function prefill_checkout_fields($booking) {
        if (!function_exists('WC') || !WC() || !WC()->session) {
            return;
        }
        
        // Clear existing session data first to prevent old data from persisting
        $this->clear_checkout_session_data();
        
        // Split customer name into first and last name
        $name_parts = explode(' ', $booking->customer_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        // Set customer details in WooCommerce session
        WC()->session->set('billing_first_name', $first_name);
        WC()->session->set('billing_last_name', $last_name);
        WC()->session->set('billing_company', $booking->customer_company ?? '');
        WC()->session->set('billing_email', $booking->customer_email);
        WC()->session->set('billing_phone', $booking->customer_phone);
        WC()->session->set('billing_address_1', $booking->address);
        WC()->session->set('billing_city', $booking->customer_city ?? '');
        WC()->session->set('billing_state', $booking->customer_state ?? '');
        WC()->session->set('billing_postcode', $booking->zip_code);
        WC()->session->set('billing_country', 'US'); // Default country
        
        // Set shipping same as billing
        WC()->session->set('shipping_first_name', $first_name);
        WC()->session->set('shipping_last_name', $last_name);
        WC()->session->set('shipping_company', $booking->customer_company ?? '');
        WC()->session->set('shipping_address_1', $booking->address);
        WC()->session->set('shipping_city', $booking->customer_city ?? '');
        WC()->session->set('shipping_state', $booking->customer_state ?? '');
        WC()->session->set('shipping_postcode', $booking->zip_code);
        WC()->session->set('shipping_country', 'US');
        
        // Set order notes
        if (!empty($booking->notes)) {
            WC()->session->set('order_comments', $booking->notes);
        }
        
        // Set preferred payment method
        if (!empty($booking->payment_method)) {
            $mapped_payment_method = $this->map_payment_method($booking->payment_method);
            WC()->session->set('chosen_payment_method', $mapped_payment_method);
            error_log("CB Debug - Payment method mapping: {$booking->payment_method} -> {$mapped_payment_method}");
        } else {
            error_log("CB Debug - No payment method found in booking data");
        }
        
        // Set customer data for WooCommerce
        WC()->session->set('customer_data', array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $booking->customer_company ?? '',
            'email' => $booking->customer_email,
            'phone' => $booking->customer_phone,
            'address' => $booking->address,
            'city' => $booking->customer_city ?? '',
            'state' => $booking->customer_state ?? '',
            'postcode' => $booking->zip_code,
            'country' => 'US',
            'payment_method' => $booking->payment_method ?? 'cash'
        ));
        
        // Also set WooCommerce customer data directly
        $this->set_woocommerce_customer_data($booking);
        
        error_log("CB Debug - Session data updated for booking: " . $booking->customer_name . " (" . $booking->customer_email . ")");
    }
    
    /**
     * Set WooCommerce customer data directly
     */
    private function set_woocommerce_customer_data($booking) {
        if (!WC()->customer) {
            return;
        }
        
        // Split customer name into first and last name
        $name_parts = explode(' ', $booking->customer_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        // Set customer data directly
        WC()->customer->set_billing_first_name($first_name);
        WC()->customer->set_billing_last_name($last_name);
        WC()->customer->set_billing_company($booking->customer_company ?? '');
        WC()->customer->set_billing_email($booking->customer_email);
        WC()->customer->set_billing_phone($booking->customer_phone);
        WC()->customer->set_billing_address_1($booking->address);
        WC()->customer->set_billing_city($booking->customer_city ?? '');
        WC()->customer->set_billing_state($booking->customer_state ?? '');
        WC()->customer->set_billing_postcode($booking->zip_code);
        WC()->customer->set_billing_country('US');
        
        // Set shipping same as billing
        WC()->customer->set_shipping_first_name($first_name);
        WC()->customer->set_shipping_last_name($last_name);
        WC()->customer->set_shipping_company($booking->customer_company ?? '');
        WC()->customer->set_shipping_address_1($booking->address);
        WC()->customer->set_shipping_city($booking->customer_city ?? '');
        WC()->customer->set_shipping_state($booking->customer_state ?? '');
        WC()->customer->set_shipping_postcode($booking->zip_code);
        WC()->customer->set_shipping_country('US');
        
        // Save customer data
        WC()->customer->save();
        
        error_log("CB Debug - WooCommerce customer data set directly for: " . $booking->customer_name);
    }
    
    /**
     * Clear existing WooCommerce checkout session data
     */
    private function clear_checkout_session_data() {
        if (!WC()->session) {
            return;
        }
        
        // Clear billing fields
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
        
        // Clear shipping fields
        WC()->session->set('shipping_first_name', '');
        WC()->session->set('shipping_last_name', '');
        WC()->session->set('shipping_company', '');
        WC()->session->set('shipping_address_1', '');
        WC()->session->set('shipping_city', '');
        WC()->session->set('shipping_state', '');
        WC()->session->set('shipping_postcode', '');
        WC()->session->set('shipping_country', '');
        
        // Clear other fields
        WC()->session->set('order_comments', '');
        WC()->session->set('chosen_payment_method', '');
        WC()->session->set('customer_data', array());
        
        error_log("CB Debug - Cleared existing checkout session data");
    }
    
    /**
     * Map booking payment method to WooCommerce payment gateway
     */
    private function map_payment_method($booking_payment_method) {
        $payment_mapping = array(
            'cash' => 'cod', // Cash on Delivery
            'bank_transfer' => 'bacs' // Bank Transfer
        );
        
        return isset($payment_mapping[$booking_payment_method]) ? $payment_mapping[$booking_payment_method] : 'cod';
    }
    
    /**
     * Pre-fill checkout fields from WooCommerce session data
     */
    public function prefill_checkout_fields_from_session() {
        if (!WC()->session) {
            return;
        }
        
        // Get customer details from session
        $first_name = WC()->session->get('billing_first_name');
        $email = WC()->session->get('billing_email');
        $phone = WC()->session->get('billing_phone');
        $address = WC()->session->get('billing_address_1');
        $postcode = WC()->session->get('billing_postcode');
        $notes = WC()->session->get('order_comments');
        
        if ($first_name || $email || $phone || $address) {
            // Pre-fill checkout fields using JavaScript
            add_action('wp_footer', array($this, 'add_checkout_prefill_script'));
        }
    }
    
    /**
     * Add JavaScript to pre-fill checkout fields
     */
    public function add_checkout_prefill_script() {
        if (!WC()->session) {
            return;
        }
        
        $first_name = WC()->session->get('billing_first_name');
        $last_name = WC()->session->get('billing_last_name');
        $company = WC()->session->get('billing_company');
        $email = WC()->session->get('billing_email');
        $phone = WC()->session->get('billing_phone');
        $address = WC()->session->get('billing_address_1');
        $city = WC()->session->get('billing_city');
        $state = WC()->session->get('billing_state');
        $postcode = WC()->session->get('billing_postcode');
        $country = WC()->session->get('billing_country');
        $notes = WC()->session->get('order_comments');
        $payment_method = WC()->session->get('chosen_payment_method');
        
        if ($first_name || $email || $phone || $address) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Function to force set field values
                function forceSetField(selector, value) {
                    if (value && value.trim() !== '') {
                        const element = document.querySelector(selector);
                        if (element) {
                            element.value = value;
                            // Trigger change and input events
                            element.dispatchEvent(new Event('change', { bubbles: true }));
                            element.dispatchEvent(new Event('input', { bubbles: true }));
                            // Also try to trigger WooCommerce specific events
                            element.dispatchEvent(new CustomEvent('woocommerce_update_checkout', { bubbles: true }));
                            element.dispatchEvent(new CustomEvent('update_checkout', { bubbles: true }));
                        }
                    }
                }
                
                // Clear existing values first
                const billingFields = ['#billing_first_name', '#billing_last_name', '#billing_email', '#billing_phone', '#billing_address_1', '#billing_city', '#billing_state', '#billing_postcode'];
                const shippingFields = ['#shipping_first_name', '#shipping_last_name', '#shipping_address_1', '#shipping_city', '#shipping_state', '#shipping_postcode'];
                
                billingFields.forEach(selector => {
                    const element = document.querySelector(selector);
                    if (element) element.value = '';
                });
                
                shippingFields.forEach(selector => {
                    const element = document.querySelector(selector);
                    if (element) element.value = '';
                });
                
                // Wait a moment for fields to be cleared
                setTimeout(function() {
                    // Pre-fill billing fields
                    <?php if ($first_name): ?>
                    forceSetField('#billing_first_name', '<?php echo esc_js($first_name); ?>');
                    forceSetField('#shipping_first_name', '<?php echo esc_js($first_name); ?>');
                    <?php endif; ?>
                    
                    <?php if ($last_name): ?>
                    forceSetField('#billing_last_name', '<?php echo esc_js($last_name); ?>');
                    forceSetField('#shipping_last_name', '<?php echo esc_js($last_name); ?>');
                    <?php endif; ?>
                    
                    <?php if ($company): ?>
                    forceSetField('#billing_company', '<?php echo esc_js($company); ?>');
                    forceSetField('#shipping_company', '<?php echo esc_js($company); ?>');
                    <?php endif; ?>
                    
                    <?php if ($email): ?>
                    forceSetField('#billing_email', '<?php echo esc_js($email); ?>');
                    <?php endif; ?>
                    
                    <?php if ($phone): ?>
                    forceSetField('#billing_phone', '<?php echo esc_js($phone); ?>');
                    <?php endif; ?>
                    
                    <?php if ($address): ?>
                    forceSetField('#billing_address_1', '<?php echo esc_js($address); ?>');
                    forceSetField('#shipping_address_1', '<?php echo esc_js($address); ?>');
                    <?php endif; ?>
                    
                    <?php if ($city): ?>
                    forceSetField('#billing_city', '<?php echo esc_js($city); ?>');
                    forceSetField('#shipping_city', '<?php echo esc_js($city); ?>');
                    <?php endif; ?>
                    
                    <?php if ($state): ?>
                    forceSetField('#billing_state', '<?php echo esc_js($state); ?>');
                    forceSetField('#shipping_state', '<?php echo esc_js($state); ?>');
                    <?php endif; ?>
                    
                    <?php if ($postcode): ?>
                    forceSetField('#billing_postcode', '<?php echo esc_js($postcode); ?>');
                    forceSetField('#shipping_postcode', '<?php echo esc_js($postcode); ?>');
                    <?php endif; ?>
                    
                    <?php if ($country): ?>
                    forceSetField('#billing_country', '<?php echo esc_js($country); ?>');
                    forceSetField('#shipping_country', '<?php echo esc_js($country); ?>');
                    <?php endif; ?>
                    
                    <?php if ($notes): ?>
                    forceSetField('#order_comments', '<?php echo esc_js($notes); ?>');
                    <?php endif; ?>
                    
                    // Pre-select payment method
                    <?php if ($payment_method): ?>
                    const paymentMethodInput = document.querySelector('input[name="payment_method"][value="<?php echo esc_js($payment_method); ?>"]');
                    if (paymentMethodInput) {
                        paymentMethodInput.checked = true;
                        paymentMethodInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    <?php endif; ?>
                    
                    // Force update checkout
                    const body = document.body;
                    body.dispatchEvent(new CustomEvent('update_checkout', { bubbles: true }));
                    body.dispatchEvent(new CustomEvent('woocommerce_update_checkout', { bubbles: true }));
                    
                    console.log('CB Debug - Checkout fields pre-filled with booking data');
                }, 500);
            });
            </script>
            <?php
        }
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
        
         // Create new product with proper service name
         global $wpdb;
         
         // Try to get service name from session first (for session-based bookings)
         $service_name = WC()->session ? WC()->session->get('cb_service_name') : null;
         
         // If not in session, get from database
         if (!$service_name) {
             $service = $wpdb->get_row($wpdb->prepare(
                 "SELECT name FROM {$wpdb->prefix}cb_services WHERE id = %d",
                 $booking->service_id
             ));
             $service_name = $service ? $service->name : 'Cleaning Service';
         }
         
         $product_data = array(
             'post_title' => sprintf('%s - %s', $service_name, $booking->booking_reference),
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
     
     private function update_product_title_with_booking_reference($order_id, $booking_id) {
         global $wpdb;
         
         // Get the booking details
         $booking = CB_Database::get_booking($booking_id);
         if (!$booking) {
             error_log("CB Debug - No booking found with ID $booking_id");
             return false;
         }
         
         // Get the order
         $order = wc_get_order($order_id);
         if (!$order) {
             error_log("CB Debug - No order found with ID $order_id");
             return false;
         }
         
         // Get the service name
         $service = $wpdb->get_row($wpdb->prepare(
             "SELECT name FROM {$wpdb->prefix}cb_services WHERE id = %d",
             $booking->service_id
         ));
         $service_name = $service ? $service->name : 'Cleaning Service';
         
         // Get all products in the order
         $items = $order->get_items();
         foreach ($items as $item) {
             $product_id = $item->get_product_id();
             
             // Check if this is a booking product
             $is_booking_product = get_post_meta($product_id, '_cb_booking_id', true);
             if ($is_booking_product !== false) {
                 // Update the product title with correct booking reference
                 $new_title = sprintf('%s - %s', $service_name, $booking->booking_reference);
                 
                 $result = wp_update_post(array(
                     'ID' => $product_id,
                     'post_title' => $new_title
                 ));
                 
                 if ($result) {
                     // Update the booking reference meta
                     update_post_meta($product_id, '_cb_booking_reference', $booking->booking_reference);
                     update_post_meta($product_id, '_cb_booking_id', $booking_id);
                     
                     error_log("CB Debug - Updated product $product_id title to: $new_title");
                 } else {
                     error_log("CB Debug - Failed to update product $product_id title");
                 }
             }
         }
         
         return true;
     }
     
     private function get_booking_description($booking) {
        global $wpdb;
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT name, default_area FROM {$wpdb->prefix}cb_services WHERE id = %d",
            $booking->service_id
        ));
        
        $service_name = $service ? $service->name : 'Cleaning Service';
        
        // Calculate total space (base + additional)
        $base_area = $service ? intval($service->default_area) : 0;
        $additional_area = intval($booking->square_meters);
        $total_space = $base_area + $additional_area;
        
        // If total_space is 0 but we have a base_area, show the base_area
        if ($total_space == 0 && $base_area > 0) {
            $total_space = $base_area;
        }
        
        $description = sprintf(
            "Booking Reference: %s\nService: %s\nSquare Meters: %d m²",
            $booking->booking_reference,
            $service_name,
            $total_space
        );
        
        // Add breakdown if both base and additional areas exist
        if ($base_area > 0 && $additional_area > 0) {
            $description .= sprintf(" (%d base + %d additional)", $base_area, $additional_area);
        } elseif ($base_area > 0) {
            $description .= sprintf(" (%d base)", $base_area);
        }
        
        $description .= sprintf(
            "\nDate: %s\nTime: %s\nDuration: %d minutes",
            $booking->booking_date,
            $booking->booking_time,
            $booking->total_duration
        );
                    
        if ($booking->extras_data) {
            $extras = json_decode($booking->extras_data, true);
            if (!empty($extras) && is_array($extras)) {
                $description .= "\n\nAdditional Services:";
                foreach ($extras as $extra) {
                    if (is_array($extra) && isset($extra['name']) && isset($extra['quantity'])) {
                        $description .= "\n- " . $extra['name'] . " (x" . $extra['quantity'] . ")";
                    }
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
                error_log("CB Debug - Session data set: booking_id=$booking_id, booking_reference=$booking_reference");
            } else {
                error_log("CB Debug - Booking not found or reference mismatch");
            }
        }
    }
    
    private function hold_booking_slot($booking) {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        $hold_duration = get_option('cb_booking_hold_time', 15); // minutes
        
        // Use the same session ID system as frontend (UUID4)
        $session_id = wp_generate_uuid4();
        
        $hold_data = array(
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'duration' => $booking->total_duration,
            'session_id' => $session_id,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$hold_duration} minutes"))
        );
        
        $wpdb->insert($slot_holds_table, $hold_data);
        
        // Store the session ID in WooCommerce session for later release
        WC()->session->set('cb_slot_session_id', $session_id);
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
        error_log("CB Debug - link_booking_to_order called for order $order_id");
    
        // Check if this is a session-based booking
        $booking_data = WC()->session->get('cb_booking_data');
    
        if ($booking_data) {
            error_log("CB Debug - Creating booking from session data for order $order_id");
    
            // Get customer details from order
            $order = wc_get_order($order_id);
            $payment_method = $order ? $order->get_payment_method() : null;
    
            // Create the actual booking from session data
            $booking_id = $this->create_booking_from_session($booking_data, $order_id, $order);
    
             if ($booking_id) {
                 error_log("CB Debug - Successfully created booking $booking_id from session for order $order_id");
     
                 // Update booking status based on payment method
                 $status = $payment_method === 'cod' ? 'confirmed' : 'pending';
                 CB_Database::update_booking_status($booking_id, $status, $order_id, $payment_method);
     
                 // Update product title with correct booking reference
                 $this->update_product_title_with_booking_reference($order_id, $booking_id);
     
                 // Release slot hold for the new booking
                 $this->release_slot_hold($booking_id);
             } else {
                 error_log("CB Debug - Failed to create booking from session for order $order_id");
             }
    
            // Clear session data
            WC()->session->set('cb_booking_data', null);
            WC()->session->set('cb_booking_timestamp', null);
            WC()->session->set('cb_generated_booking_reference', null);
            WC()->session->set('cb_service_name', null);
        } else {
            error_log("CB Debug - No booking session data found for order $order_id");
        }
    }
    
    /**
     * Handle linking booking to order from thankyou page
     */
    public function link_booking_to_order_thankyou($order_id) {
        error_log("CB Debug - link_booking_to_order_thankyou called for order $order_id");
        $this->link_booking_to_order($order_id);
    }
    
    public function update_booking_status($order_id, $old_status = null, $new_status = null) {
        global $wpdb;
        
        // Handle different hook signatures
        if (func_num_args() == 1) {
            // If only one argument is passed, get the order and determine status
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }
            $new_status = $order->get_status();
            $old_status = 'unknown'; // We don't have the old status
        }
        
        // Get the order to extract payment method
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("CB Debug - Order $order_id not found");
            return;
        }
        
        // Get payment method from the order
        $payment_method = $order->get_payment_method();
        error_log("CB Debug - Payment method for order $order_id: $payment_method");
        
        // Log the status update
        error_log("CB Debug - Updating booking status for order $order_id: $old_status -> $new_status");
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_bookings WHERE woocommerce_order_id = %d",
            $order_id
        ));
        
        if ($booking) {
            $booking_status = $this->map_order_status_to_booking_status($new_status);
            error_log("CB Debug - Mapping order status '$new_status' to booking status '$booking_status' for booking ID {$booking->id}");
            
            // Update booking status with payment method
            $result = CB_Database::update_booking_status($booking->id, $booking_status, $order_id, $payment_method);
            
            if ($result) {
                error_log("CB Debug - Successfully updated booking {$booking->id} status to '$booking_status' with payment method '$payment_method'");
            } else {
                error_log("CB Debug - Failed to update booking {$booking->id} status");
            }
        } else {
            error_log("CB Debug - No booking found for order $order_id");
        }
    }
    
    /**
     * Handle payment completion - update booking status to paid
     */
    public function handle_payment_complete($order_id) {
        global $wpdb;
        
        error_log("CB Debug - Payment completed for order $order_id");
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cb_bookings WHERE woocommerce_order_id = %d",
            $order_id
        ));
        
        if ($booking) {
            $result = CB_Database::update_booking_status($booking->id, 'paid', $order_id);
            
            if ($result) {
                error_log("CB Debug - Successfully updated booking {$booking->id} status to 'paid' after payment completion");
            } else {
                error_log("CB Debug - Failed to update booking {$booking->id} status after payment completion");
            }
        } else {
            error_log("CB Debug - No booking found for order $order_id during payment completion");
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
    
    /**
     * Utility method to sync all booking statuses with their WooCommerce orders
     * This can be called manually or via admin action
     */
    public function sync_all_booking_statuses() {
        global $wpdb;
        
        $bookings = $wpdb->get_results("
            SELECT b.id, b.woocommerce_order_id, b.status as booking_status
            FROM {$wpdb->prefix}cb_bookings b
            WHERE b.woocommerce_order_id IS NOT NULL 
            AND b.woocommerce_order_id > 0
        ");
        
        $updated_count = 0;
        
        foreach ($bookings as $booking) {
            $order = wc_get_order($booking->woocommerce_order_id);
            
            if ($order) {
                $order_status = $order->get_status();
                $expected_booking_status = $this->map_order_status_to_booking_status($order_status);
                
                if ($booking->booking_status !== $expected_booking_status) {
                    error_log("CB Debug - Syncing booking {$booking->id}: {$booking->booking_status} -> $expected_booking_status (order status: $order_status)");
                    
                    $result = CB_Database::update_booking_status($booking->id, $expected_booking_status, $booking->woocommerce_order_id);
                    
                    if ($result) {
                        $updated_count++;
                    }
                }
            }
        }
        
        error_log("CB Debug - Synced $updated_count booking statuses");
        return $updated_count;
    }
    
    private function map_order_status_to_booking_status($order_status) {
        $status_map = array(
            'pending' => 'pending',
            'processing' => 'paid',        // Payment received, booking confirmed
            'on-hold' => 'on-hold',       // Payment on hold - new status
            'completed' => 'paid',         // Order completed, booking paid
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
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        $buffer_time = get_option('cb_buffer_time', 15);
        
        // Count total active trucks (one truck = one slot)
        $total_trucks = $wpdb->get_var(
            "SELECT COUNT(*) FROM $trucks_table WHERE is_active = 1"
        );
        
        // If trucks table doesn't exist or no trucks, fall back to old logic
        if ($total_trucks === null || $total_trucks <= 0) {
            // Fallback: check for any existing bookings (old behavior)
            $existing_booking = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table 
                 WHERE booking_date = %s 
                 AND status IN ('confirmed', 'paid') 
                 AND id != %d
                 AND (
                     (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME((total_duration + %d) * 60)) > %s) OR
                     (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME((total_duration + %d) * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
                 )",
                $booking->booking_date,
                $booking->id,
                $booking->booking_time,
                $buffer_time,
                $booking->booking_time,
                $booking->booking_time,
                $booking->total_duration,
                $buffer_time,
                $booking->booking_time,
                $booking->total_duration
            ));
            
            if ($existing_booking > 0) {
                return false;
            }
            
            // Check for active holds (excluding current session)
            $current_session_id = WC()->session->get('cb_slot_session_id');
            $active_hold = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $slot_holds_table 
                 WHERE booking_date = %s 
                 AND booking_time = %s 
                 AND expires_at > NOW() 
                 AND session_id != %s",
                $booking->booking_date,
                $booking->booking_time,
                $current_session_id ?: ''
            ));
            
            return $active_hold == 0;
        }
        
        // Count existing bookings for this time slot (pending, confirmed, paid, on-hold)
        // Include buffer time in the conflict detection
        $existing_bookings_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE booking_date = %s 
             AND status IN ('pending', 'confirmed', 'paid', 'on-hold')
             AND id != %d
             AND (
                 (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME((total_duration + %d) * 60)) > %s) OR
                 (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME((total_duration + %d) * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
             )",
            $booking->booking_date,
            $booking->id,
            $booking->booking_time,
            $buffer_time,
            $booking->booking_time,
            $booking->booking_time,
            $booking->total_duration,
            $buffer_time,
            $booking->booking_time,
            $booking->total_duration
        ));
        
        // Count active slot holds for this time slot
        $active_holds_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $slot_holds_table 
             WHERE booking_date = %s 
             AND expires_at > NOW()
             AND (
                 (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME((duration + %d) * 60)) > %s) OR
                 (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME((duration + %d) * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
             )",
            $booking->booking_date,
            $booking->booking_time,
            $buffer_time,
            $booking->booking_time,
            $booking->booking_time,
            $booking->total_duration,
            $buffer_time,
            $booking->booking_time,
            $booking->total_duration
        ));
        
        // Total occupied slots = bookings + holds
        $total_occupied = $existing_bookings_count + $active_holds_count;
        
        // Slot available if occupied slots < total trucks
        return $total_occupied < $total_trucks;
    }
    
    private function release_slot_hold($booking_id) {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        
        // Get the session ID from WooCommerce session
        $session_id = WC()->session->get('cb_slot_session_id');
        
        if ($session_id) {
            $wpdb->delete($slot_holds_table, array(
                'session_id' => $session_id
            ));
            
            // Clear the session ID from WooCommerce session
            WC()->session->set('cb_slot_session_id', null);
        }
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
    
    public function display_booking_details_on_checkout() {
        if (!WC()->session) {
            return;
        }
        
        $booking_data = WC()->session->get('cb_booking_data');
        if (!$booking_data) {
            return;
        }
        
        // Create a temporary booking object for display
        $booking = new stdClass();
        $booking->booking_reference = WC()->session->get('cb_generated_booking_reference') ?: 'CB' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $booking->service_id = $booking_data['service_id'];
        $booking->square_meters = $booking_data['square_meters'];
        $booking->booking_date = $booking_data['booking_date'];
        $booking->booking_time = $booking_data['booking_time'];
        $booking->total_duration = $booking_data['pricing']['total_duration'] ?? 120;
        $booking->extras_data = json_encode($booking_data['extras']);
        
        echo '<div class="cb-checkout-booking-summary" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px;">';
        echo '<h3 style="margin-top: 0; color: #495057;">' . __('Your Booking Summary', 'cleaning-booking') . '</h3>';
        $this->render_booking_details($booking);
        echo '</div>';
    }
    
    private function render_booking_details($booking) {
        global $wpdb;
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT name, default_area FROM {$wpdb->prefix}cb_services WHERE id = %d",
            $booking->service_id
        ));
        
        $service_name = $service ? $service->name : 'Unknown Service';
        
        // Calculate total space (base + additional)
        $base_area = $service ? intval($service->default_area) : 0;
        $additional_area = intval($booking->square_meters);
        $total_space = $base_area + $additional_area;
        
        error_log("CB Debug - Square meters calculation: base_area={$base_area}, additional_area={$additional_area}, total_space={$total_space}");
        
        // If total_space is 0 but we have a base_area, show the base_area
        if ($total_space == 0 && $base_area > 0) {
            $total_space = $base_area;
        }
        
        echo '<div class="cb-booking-details">';
        echo '<h3>' . __('Booking Details', 'cleaning-booking') . '</h3>';
        echo '<table class="widefat">';
        echo '<tr><td><strong>' . __('Booking Reference', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->booking_reference) . '</td></tr>';
        echo '<tr><td><strong>' . __('Service', 'cleaning-booking') . ':</strong></td><td>' . esc_html($service_name) . '</td></tr>';
        echo '<tr><td><strong>' . __('Square Meters', 'cleaning-booking') . ':</strong></td><td>' . esc_html($total_space) . ' m²';
        if ($base_area > 0 && $additional_area > 0) {
            echo ' (' . esc_html($base_area) . ' base + ' . esc_html($additional_area) . ' additional)';
        } elseif ($base_area > 0) {
            echo ' (' . esc_html($base_area) . ' base)';
        }
        echo '</td></tr>';
        echo '<tr><td><strong>' . __('Date', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->booking_date) . '</td></tr>';
        echo '<tr><td><strong>' . __('Time', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->booking_time) . '</td></tr>';
        echo '<tr><td><strong>' . __('Duration', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->total_duration) . ' ' . __('minutes', 'cleaning-booking') . '</td></tr>';
        echo '<tr><td><strong>' . __('Customer', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->customer_name) . '</td></tr>';
        echo '<tr><td><strong>' . __('Email', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->customer_email) . '</td></tr>';
        echo '<tr><td><strong>' . __('Phone', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->customer_phone) . '</td></tr>';
        echo '<tr><td><strong>' . __('Address', 'cleaning-booking') . ':</strong></td><td>' . esc_html($booking->address) . '</td></tr>';
        
        if ($booking->extras_data) {
            $extras = json_decode($booking->extras_data, true);
            if (!empty($extras) && is_array($extras)) {
                echo '<tr><td><strong>' . __('Additional Services', 'cleaning-booking') . ':</strong></td><td>';
                foreach ($extras as $extra) {
                    if (is_array($extra) && isset($extra['name']) && isset($extra['quantity'])) {
                        echo esc_html($extra['name']) . ' (x' . esc_html($extra['quantity']) . ')<br>';
                    }
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
        
        // Always ensure our required payment gateways are enabled
        $gateways = WC()->payment_gateways()->payment_gateways();
        
        // Enable Cash on Delivery if available
        if (isset($gateways['cod'])) {
            $gateway_settings = get_option('woocommerce_cod_settings', array());
            $gateway_settings['enabled'] = 'yes';
            $gateway_settings['title'] = __('Cash on Delivery', 'cleaning-booking');
            $gateway_settings['description'] = __('Pay when service is completed', 'cleaning-booking');
            update_option('woocommerce_cod_settings', $gateway_settings);
            
            error_log('CB Debug - Enabled Cash on Delivery payment gateway');
        }
        
        // Enable Bank Transfer if available
        if (isset($gateways['bacs'])) {
            $gateway_settings = get_option('woocommerce_bacs_settings', array());
            $gateway_settings['enabled'] = 'yes';
            $gateway_settings['title'] = __('Bank Transfer', 'cleaning-booking');
            update_option('woocommerce_bacs_settings', $gateway_settings);
            
            error_log('CB Debug - Enabled Bank Transfer payment gateway');
        }
        
        // Disable Stripe if available (as per user request)
        if (isset($gateways['stripe'])) {
            $gateway_settings = get_option('woocommerce_stripe_settings', array());
            $gateway_settings['enabled'] = 'no'; // Disable
            update_option('woocommerce_stripe_settings', $gateway_settings);
            
            error_log('CB Debug - Disabled Stripe payment gateway');
        }
        
        // Disable PayPal if available (as per user request)
        if (isset($gateways['ppec_paypal'])) {
            $gateway_settings = get_option('woocommerce_ppec_paypal_settings', array());
            $gateway_settings['enabled'] = 'no'; // Disable
            update_option('woocommerce_ppec_paypal_settings', $gateway_settings);
            
            error_log('CB Debug - Disabled PayPal payment gateway');
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
        /**
         * Generate checkout URL from session data
         */
        /**
         * Generate checkout URL from session data
         */
        public function generate_checkout_url_from_session() {  // Changed from private to public
            if (!WC()->session) {
                error_log('CB Debug - No WooCommerce session available');
                return false;
            }

            $booking_data = WC()->session->get('cb_booking_data');

            if (!$booking_data) {
                error_log('CB Debug - No booking data in session');
                return false;
            }

            error_log('CB Debug - Generating checkout from session data: ' . print_r($booking_data, true));

             // Create a booking object from session data with proper reference
             $booking = new stdClass();
             $booking->id = 0; // Temporary ID, will be created after checkout
             $booking->booking_reference = 'CB' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
             $booking->service_id = $booking_data['service_id'];
             $booking->square_meters = $booking_data['square_meters'];
             $booking->booking_date = $booking_data['booking_date'];
             $booking->booking_time = $booking_data['booking_time'];
             $booking->total_price = $booking_data['pricing']['total_price'] ?? 0;
             $booking->total_duration = $booking_data['pricing']['total_duration'] ?? 120;
             $booking->extras_data = json_encode($booking_data['extras']);
             
             // Store the generated reference in session for later use
             WC()->session->set('cb_generated_booking_reference', $booking->booking_reference);
             
             // Also store the service name for product creation
             global $wpdb;
             $service = $wpdb->get_row($wpdb->prepare(
                 "SELECT name FROM {$wpdb->prefix}cb_services WHERE id = %d",
                 $booking->service_id
             ));
             $service_name = $service ? $service->name : 'Cleaning Service';
             WC()->session->set('cb_service_name', $service_name);

            // Create product and get checkout URL
            $product_id = $this->create_booking_product($booking);

            if (!$product_id) {
                error_log('CB Debug - Failed to create booking product from session');
                return false;
            }

            $checkout_url = add_query_arg(array(
                'booking_session' => '1',
                'add-to-cart' => $product_id,
                'quantity' => 1
            ), wc_get_checkout_url());

            error_log('CB Debug - Generated checkout URL from session: ' . $checkout_url);

            return $checkout_url;
        }

        /**
         * Create booking from session data after order is created
         */
        /**
         * Create booking from session data after order is created
         */
        public function create_booking_from_session($booking_data, $order_id, $order) {  // Changed from private to public
            global $wpdb;

            // Get customer details from order
            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $customer_email = $order->get_billing_email();
            $customer_phone = $order->get_billing_phone();
            $address = $order->get_billing_address_1();
            $zip_code = $order->get_billing_postcode();

            // Use the generated booking reference from session or create new one
            $booking_reference = WC()->session->get('cb_generated_booking_reference');
            if (!$booking_reference) {
                $booking_reference = 'CB' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            // Assign available truck automatically
            $total_duration = $booking_data['pricing']['total_duration'] ?? 120;
            $available_truck = CB_Database::get_available_truck($booking_data['booking_date'], $booking_data['booking_time'], $total_duration);
            if (!$available_truck) {
                error_log("CB Debug - No available truck found for WooCommerce booking on {$booking_data['booking_date']} at {$booking_data['booking_time']}");
                return false;
            }

            // Prepare booking data for database
            $booking_db_data = array(
                'booking_reference' => $booking_reference,
                'service_id' => $booking_data['service_id'],
                'square_meters' => $booking_data['square_meters'],
                'booking_date' => $booking_data['booking_date'],
                'booking_time' => $booking_data['booking_time'],
                'total_duration' => $total_duration,
                'total_price' => $booking_data['pricing']['total_price'] ?? 0,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'address' => $address,
                'zip_code' => $zip_code,
                'extras_data' => json_encode($booking_data['extras']),
                'status' => 'pending',
                'truck_id' => $available_truck->id,
                'woocommerce_order_id' => $order_id,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );

            error_log('CB Debug - Creating booking from session: ' . print_r($booking_db_data, true));

            $result = $wpdb->insert("{$wpdb->prefix}cb_bookings", $booking_db_data);

            if ($result) {
                $booking_id = $wpdb->insert_id;
                error_log("CB Debug - Booking created successfully with ID: $booking_id");
                return $booking_id;
            } else {
                error_log("CB Debug - Failed to create booking: " . $wpdb->last_error);
                return false;
            }
        }
}
