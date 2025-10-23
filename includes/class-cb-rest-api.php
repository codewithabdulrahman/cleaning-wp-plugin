<?php
/**
 * REST API class for frontend wizard
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_REST_API {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('cleaning-booking/v1', '/check-zip', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_zip_code'),
            'permission_callback' => '__return_true',
            'args' => array(
                'zip_code' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route('cleaning-booking/v1', '/services', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_services'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('cleaning-booking/v1', '/extras', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_extras'),
            'permission_callback' => '__return_true',
            'args' => array(
                'service_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route('cleaning-booking/v1', '/calculate-price', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_price'),
            'permission_callback' => '__return_true',
            'args' => array(
                'service_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'square_meters' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'extras' => array(
                    'required' => false,
                    'type' => 'array',
                    'default' => array()
                ),
                'zip_code' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route('cleaning-booking/v1', '/available-slots', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_available_slots'),
            'permission_callback' => '__return_true',
            'args' => array(
                'date' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'duration' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        register_rest_route('cleaning-booking/v1', '/hold-slot', array(
            'methods' => 'POST',
            'callback' => array($this, 'hold_slot'),
            'permission_callback' => '__return_true',
            'args' => array(
                'date' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'time' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'duration' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        register_rest_route('cleaning-booking/v1', '/release-slot', array(
            'methods' => 'POST',
            'callback' => array($this, 'release_slot'),
            'permission_callback' => '__return_true',
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route('cleaning-booking/v1', '/booking/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_booking'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        register_rest_route('cleaning-booking/v1', '/create-booking', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_booking'),
            'permission_callback' => '__return_true',
            'args' => array(
                'customer_name' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'customer_email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email'
                ),
                'customer_phone' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'address' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'zip_code' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'service_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'square_meters' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'booking_date' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'booking_time' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'extras' => array(
                    'required' => false,
                    'type' => 'array',
                    'default' => array()
                ),
                'notes' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
    }
    
    public function check_zip_code($request) {
        $zip_code = $request->get_param('zip_code');
        
        if (CB_Database::is_zip_code_allowed($zip_code)) {
            $surcharge = CB_Database::get_zip_surcharge($zip_code);
            return new WP_REST_Response(array(
                'success' => true,
                'allowed' => true,
                'surcharge' => floatval($surcharge)
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'allowed' => false,
            'message' => __('Sorry, we don\'t provide services in this area yet.', 'cleaning-booking')
        ), 200);
    }
    
   public function get_services($request) {
    $services = CB_Database::get_services();
    
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
            'icon_url' => isset($service->icon_url) ? $service->icon_url : '', // Add this line
            'is_active' => $service->is_active,
            'sort_order' => $service->sort_order
        );
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'services' => $formatted_services
    ), 200);
}
   public function get_extras($request) {
    $service_id_param = $request->get_param('service_id');
    
    $formatted_extras = array();
    
    if (!empty($service_id_param)) {
        // Handle single ID or comma-separated IDs
        $service_ids = array_map('intval', explode(',', $service_id_param));
        
        // Get extras for specific services
        foreach ($service_ids as $service_id) {
            // Get only ACTIVE extras for frontend use
            $extras = CB_Database::get_service_extras($service_id, true);
            
            foreach ($extras as $extra) {
                $formatted_extras[] = array(
                    'id' => $extra->id,
                    'service_id' => $extra->service_id,
                    'name' => $extra->name,
                    'description' => $extra->description,
                    'price' => floatval($extra->price),
                    'duration' => intval($extra->duration),
                    'is_active' => isset($extra->is_active) ? intval($extra->is_active) : 1,
                    'sort_order' => isset($extra->sort_order) ? intval($extra->sort_order) : 0
                );
            }
        }
    } else {
        // Return empty array if no service_id provided
        // Frontend should call this endpoint with service_id when service is selected
        return new WP_REST_Response(array(
            'success' => true,
            'extras' => array(),
            'message' => __('Please provide service_id parameter (e.g., ?service_id=1 or ?service_id=1,2,3)', 'cleaning-booking')
        ), 200);
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'extras' => $formatted_extras
    ), 200);
}
    public function calculate_price($request) {
        $service_id = $request->get_param('service_id');
        $square_meters = $request->get_param('square_meters');
        $extras = $request->get_param('extras');
        $zip_code = $request->get_param('zip_code');
        
        // Get service details
        global $wpdb;
        $services_table = $wpdb->prefix . 'cb_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d AND is_active = 1",
            $service_id
        ));
        
        if (!$service) {
            return new WP_Error('invalid_service', __('Invalid service selected', 'cleaning-booking'), array('status' => 400));
        }
        
        // Calculate base price and duration
        $base_price = floatval($service->base_price);
        $base_duration = intval($service->base_duration);
        
        // Add square meter calculations
        $sqm_price = floatval($service->sqm_multiplier) * $square_meters;
        $sqm_duration = floatval($service->sqm_duration_multiplier) * $square_meters;
        
        $total_price = $base_price + $sqm_price;
        $total_duration = $base_duration + $sqm_duration;
        
        // Add extras
        $extras_price = 0;
        $extras_duration = 0;
        $extras_details = array();
        
        if (!empty($extras)) {
            $service_extras_table = $wpdb->prefix . 'cb_service_extras';
            $extra_ids = array_map('intval', $extras);
            $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
            
            // Get extras with service validation to ensure security
            $extras_data = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $service_extras_table WHERE id IN ($placeholders) AND service_id = %d AND is_active = 1",
                array_merge($extra_ids, array($service_id))
            ));
            
            foreach ($extras_data as $extra) {
                $extras_price += floatval($extra->price);
                $extras_duration += intval($extra->duration);
                $extras_details[] = array(
                    'id' => $extra->id,
                    'name' => $extra->name,
                    'price' => floatval($extra->price),
                    'duration' => intval($extra->duration)
                );
            }
        }
        
        $total_price += $extras_price;
        $total_duration += $extras_duration;
        
        // Add ZIP code surcharge
        $zip_surcharge = CB_Database::get_zip_surcharge($zip_code);
        if ($zip_surcharge) {
            $total_price += floatval($zip_surcharge);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'pricing' => array(
                'base_price' => $base_price,
                'sqm_price' => $sqm_price,
                'extras_price' => $extras_price,
                'zip_surcharge' => floatval($zip_surcharge),
                'total_price' => $total_price
            ),
            'duration' => array(
                'base_duration' => $base_duration,
                'sqm_duration' => $sqm_duration,
                'extras_duration' => $extras_duration,
                'total_duration' => $total_duration
            ),
            'extras' => $extras_details
        ), 200);
    }
    
    public function get_available_slots($request) {
        $date = $request->get_param('date');
        $duration = $request->get_param('duration');
        
        $slot_manager = new CB_Slot_Manager();
        $slots = $slot_manager->get_available_slots($date, $duration);
        
        return new WP_REST_Response(array(
            'success' => true,
            'slots' => $slots
        ), 200);
    }
    
    public function hold_slot($request) {
        $date = $request->get_param('date');
        $time = $request->get_param('time');
        $duration = $request->get_param('duration');
        
        $slot_manager = new CB_Slot_Manager();
        $session_id = $slot_manager->hold_slot($date, $time, $duration);
        
        if ($session_id) {
            return new WP_REST_Response(array(
                'success' => true,
                'session_id' => $session_id
            ), 200);
        }
        
        return new WP_Error('slot_unavailable', __('Slot is no longer available', 'cleaning-booking'), array('status' => 400));
    }
    
    public function release_slot($request) {
        $session_id = $request->get_param('session_id');
        
        $slot_manager = new CB_Slot_Manager();
        $result = $slot_manager->release_slot($session_id);
        
        return new WP_REST_Response(array(
            'success' => $result
        ), 200);
    }
    
    public function create_booking($request) {
        $data = $request->get_params();
        
        // Calculate final pricing
        $pricing_response = $this->calculate_price(new WP_REST_Request('POST', '/cleaning-booking/v1/calculate-price', array(
            'service_id' => $data['service_id'],
            'square_meters' => $data['square_meters'],
            'extras' => $data['extras'],
            'zip_code' => $data['zip_code']
        )));
        
        if (is_wp_error($pricing_response)) {
            return $pricing_response;
        }
        
        $pricing_data = $pricing_response->get_data();
        
        // Add calculated values to booking data
        $data['total_price'] = $pricing_data['pricing']['total_price'];
        $data['total_duration'] = $pricing_data['duration']['total_duration'];
        
        // Assign available truck automatically
        $available_truck = CB_Database::get_available_truck($data['booking_date'], $data['booking_time'], $data['total_duration']);
        if ($available_truck) {
            $data['truck_id'] = $available_truck->id;
        } else {
            return new WP_Error('no_truck_available', __('No trucks available for the selected time slot. Please choose another time.', 'cleaning-booking'), array('status' => 400));
        }
        
        // Create booking
        $booking_id = CB_Database::create_booking($data);
        
        if ($booking_id) {
            $booking = CB_Database::get_booking($booking_id);
            
            return new WP_REST_Response(array(
                'success' => true,
                'booking_id' => $booking_id,
                'booking_reference' => $booking->booking_reference,
                'redirect_url' => $this->create_woocommerce_checkout($booking)
            ), 200);
        }
        
        return new WP_Error('booking_failed', __('Failed to create booking', 'cleaning-booking'), array('status' => 500));
    }
    
    private function create_woocommerce_checkout($booking) {
        if (class_exists('CB_WooCommerce')) {
            $woocommerce = new CB_WooCommerce();
            return $woocommerce->create_booking_order($booking);
        }
        
        // Fallback if WooCommerce is not available
        return home_url('/booking-success/?booking_id=' . $booking->id);
    }
    
    public function get_booking($request) {
        $booking_id = $request['id'];
        
        $booking = CB_Database::get_booking($booking_id);
        
        if (!$booking) {
            return new WP_Error('booking_not_found', __('Booking not found', 'cleaning-booking'), array('status' => 404));
        }
        
        // Get service name
        global $wpdb;
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}cb_services WHERE id = %d",
            $booking->service_id
        ));
        
        $booking_data = array(
            'id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'service_name' => $service ? $service->name : 'Unknown Service',
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'total_duration' => $booking->total_duration,
            'total_price' => $booking->total_price,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'address' => $booking->address,
            'status' => $booking->status
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $booking_data
        ), 200);
    }
}
