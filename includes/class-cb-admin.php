<?php
/**
 * Admin interface class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_cb_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_cb_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_cb_save_extra', array($this, 'ajax_save_extra'));
        add_action('wp_ajax_cb_delete_extra', array($this, 'ajax_delete_extra'));
        add_action('wp_ajax_cb_get_service_extras', array($this, 'ajax_get_service_extras'));
        add_action('wp_ajax_cb_remove_service_extra', array($this, 'ajax_remove_service_extra'));
        add_action('wp_ajax_cb_update_extra_status', array($this, 'ajax_update_extra_status'));
        add_action('wp_ajax_cb_save_zip_code', array($this, 'ajax_save_zip_code'));
        add_action('wp_ajax_cb_delete_zip_code', array($this, 'ajax_delete_zip_code'));
        add_action('wp_ajax_cb_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_cb_get_booking_details', array($this, 'ajax_get_booking_details'));
        add_action('wp_ajax_cb_update_booking', array($this, 'ajax_update_booking'));
        add_action('wp_ajax_cb_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_cb_save_booking', array($this, 'ajax_save_booking'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Cleaning Booking', 'cleaning-booking'),
            __('Cleaning Booking', 'cleaning-booking'),
            'manage_options',
            'cleaning-booking',
            array($this, 'bookings_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Hide the default submenu item by overriding it with null
        add_submenu_page(
            'cleaning-booking',
            null,
            null,
            'manage_options',
            'cleaning-booking',
            array($this, 'bookings_page')
        );
        
        add_submenu_page(
            'cleaning-booking',
            __('All Bookings', 'cleaning-booking'),
            __('All Bookings', 'cleaning-booking'),
            'manage_options',
            'cb-all-bookings',
            array($this, 'bookings_page')
        );
        
        add_submenu_page(
            'cleaning-booking',
            __('Services', 'cleaning-booking'),
            __('Services', 'cleaning-booking'),
            'manage_options',
            'cb-services',
            array($this, 'services_page')
        );
        
        
        add_submenu_page(
            'cleaning-booking',
            __('ZIP Codes', 'cleaning-booking'),
            __('ZIP Codes', 'cleaning-booking'),
            'manage_options',
            'cb-zip-codes',
            array($this, 'zip_codes_page')
        );
        
        add_submenu_page(
            'cleaning-booking',
            __('Settings', 'cleaning-booking'),
            __('Settings', 'cleaning-booking'),
            'manage_options',
            'cb-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'cleaning-booking') === false && strpos($hook, 'cb-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('cb-admin', CB_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CB_VERSION, true);
        wp_enqueue_style('cb-admin', CB_PLUGIN_URL . 'assets/css/admin.css', array(), CB_VERSION);
        
        wp_localize_script('cb-admin', 'cb_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cb_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'cleaning-booking'),
                'saving' => __('Saving...', 'cleaning-booking'),
                'saved' => __('Saved!', 'cleaning-booking'),
                'error' => __('Error occurred', 'cleaning-booking')
            )
        ));
    }
    
    public function admin_page() {
        $this->bookings_page();
    }
    
    public function bookings_page() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        $services_table = $wpdb->prefix . 'cb_services';
        
        // Handle booking creation (AJAX will handle most cases, this is fallback)
        if (isset($_POST['create_booking']) && wp_verify_nonce($_POST['_wpnonce'], 'cb_create_booking')) {
            // This would be handled by AJAX, this is just a fallback
        }
        
        // Handle status updates (fallback for non-AJAX)
        if (isset($_POST['update_status']) && wp_verify_nonce($_POST['_wpnonce'], 'cb_update_booking_status')) {
            $booking_id = intval($_POST['booking_id']);
            $new_status = sanitize_text_field($_POST['status']);
            CB_Database::update_booking_status($booking_id, $new_status);
            echo '<div class="notice notice-success"><p>' . __('Booking status updated!', 'cleaning-booking') . '</p></div>';
        }
        
        // Get bookings
        $bookings = $wpdb->get_results("
            SELECT b.*, s.name as service_name 
            FROM $bookings_table b 
            LEFT JOIN $services_table s ON b.service_id = s.id 
            ORDER BY b.created_at DESC 
            LIMIT 50
        ");
        
        include CB_PLUGIN_DIR . 'templates/admin/bookings.php';
    }
    
    public function services_page() {
        $services = CB_Database::get_services(false);
        include CB_PLUGIN_DIR . 'templates/admin/services.php';
    }
    
    
    public function zip_codes_page() {
        $zip_codes = CB_Database::get_zip_codes(false);
        include CB_PLUGIN_DIR . 'templates/admin/zip-codes.php';
    }
    
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'cb_settings')) {
            $settings = array(
                'cb_slot_duration' => intval($_POST['slot_duration']),
                'cb_buffer_time' => intval($_POST['buffer_time']),
                'cb_booking_hold_time' => intval($_POST['booking_hold_time'])
            );
            
            foreach ($settings as $key => $value) {
                update_option($key, $value);
            }
            
            // Handle business hours
            $business_hours = array();
            $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            
            foreach ($days as $day) {
                $business_hours[$day] = array(
                    'enabled' => isset($_POST['business_hours'][$day]['enabled']),
                    'start' => sanitize_text_field($_POST['business_hours'][$day]['start']),
                    'end' => sanitize_text_field($_POST['business_hours'][$day]['end'])
                );
            }
            
            update_option('cb_business_hours', $business_hours);
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'cleaning-booking') . '</p></div>';
        }
        
        $slot_duration = get_option('cb_slot_duration', 30);
        $buffer_time = get_option('cb_buffer_time', 15);
        $booking_hold_time = get_option('cb_booking_hold_time', 15);
        $business_hours = get_option('cb_business_hours', array());
        
        include CB_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    // AJAX handlers
    public function ajax_save_service() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cb_services';
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'base_price' => floatval($_POST['base_price']),
            'base_duration' => intval($_POST['base_duration']),
            'sqm_multiplier' => floatval($_POST['sqm_multiplier']),
            'sqm_duration_multiplier' => floatval($_POST['sqm_duration_multiplier']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'])
        );
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing
            $result = $wpdb->update($table, $data, array('id' => intval($_POST['id'])));
            $message = $result ? __('Service updated successfully!', 'cleaning-booking') : __('Error updating service', 'cleaning-booking');
        } else {
            // Insert new
            $result = $wpdb->insert($table, $data);
            $message = $result ? __('Service created successfully!', 'cleaning-booking') : __('Error creating service', 'cleaning-booking');
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    public function ajax_delete_service() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $service_id = intval($_POST['id']);
        
        $result = CB_Database::delete_service_and_extras($service_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Service and all its extras deleted successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting service and extras', 'cleaning-booking')));
        }
    }
    
    public function ajax_save_extra() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $service_id = intval($_POST['service_id']); // This should be passed from frontend
        
        $extra_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'duration' => intval($_POST['duration']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'])
        );
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $result = CB_Database::update_service_extra($service_id, intval($_POST['id']), $extra_data);
            $message = $result ? __('Extra updated successfully!', 'cleaning-booking') : __('Error updating extra', 'cleaning-booking');
        } else {
            $result = CB_Database::add_service_extra($service_id, $extra_data);
            $message = $result ? __('Extra created successfully!', 'cleaning-booking') : __('Error creating extra', 'cleaning-booking');
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    public function ajax_delete_extra() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cb_extras';
        
        $result = $wpdb->delete($table, array('id' => intval($_POST['id'])));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Extra deleted successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting extra', 'cleaning-booking')));
        }
    }
    
    public function ajax_save_zip_code() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cb_zip_codes';
        
        $data = array(
            'zip_code' => sanitize_text_field($_POST['zip_code']),
            'city' => sanitize_text_field($_POST['city']),
            'surcharge' => floatval($_POST['surcharge']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $result = $wpdb->update($table, $data, array('id' => intval($_POST['id'])));
            $message = $result ? __('ZIP code updated successfully!', 'cleaning-booking') : __('Error updating ZIP code', 'cleaning-booking');
        } else {
            $result = $wpdb->insert($table, $data);
            $message = $result ? __('ZIP code created successfully!', 'cleaning-booking') : __('Error creating ZIP code', 'cleaning-booking');
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    public function ajax_delete_zip_code() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cb_zip_codes';
        
        $result = $wpdb->delete($table, array('id' => intval($_POST['id'])));
        
        if ($result) {
            wp_send_json_success(array('message' => __('ZIP code deleted successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting ZIP code', 'cleaning-booking')));
        }
    }
    
    public function ajax_update_booking_status() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = CB_Database::update_booking_status($booking_id, $status);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Booking status updated!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error updating booking status', 'cleaning-booking')));
        }
    }
    
    public function ajax_get_service_extras() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $service_id = intval($_POST['service_id']);
        // Get all extras (both active and inactive) for admin display
        $extras = CB_Database::get_service_extras($service_id, false);
        $available_extras = CB_Database::get_available_extras_for_service($service_id);
        
        wp_send_json_success(array(
            'extras' => $extras,
            'available_extras' => $available_extras
        ));
    }
    
    public function ajax_update_extra_status() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cb_service_extras';
        
        $extra_id = intval($_POST['extra_id']);
        $is_active = intval($_POST['is_active']);
        
        $result = $wpdb->update(
            $table,
            array('is_active' => $is_active),
            array('id' => $extra_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Extra status updated successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error updating extra status', 'cleaning-booking')));
        }
    }
    
    public function ajax_remove_service_extra() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $service_id = intval($_POST['service_id']);
        $extra_id = intval($_POST['extra_id']);
        
        $result = CB_Database::remove_service_extra($service_id, $extra_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Extra removed from service successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error removing extra from service', 'cleaning-booking')));
        }
    }
    
    public function ajax_get_booking_details() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $booking_id = intval($_POST['booking_id']);
        
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        $services_table = $wpdb->prefix . 'cb_services';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, s.name as service_name 
             FROM $bookings_table b 
             LEFT JOIN $services_table s ON b.service_id = s.id 
             WHERE b.id = %d",
            $booking_id
        ));
        
        if ($booking) {
            // Format the booking data for frontend
            $booking_data = array(
                'id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'address' => $booking->address,
                'service_name' => $booking->service_name,
                'square_meters' => $booking->square_meters,
                'booking_date' => date('M j, Y', strtotime($booking->booking_date)),
                'booking_time' => date('g:i A', strtotime($booking->booking_time)),
                'total_duration' => $booking->total_duration,
                'total_price' => number_format($booking->total_price, 2),
                'status' => $booking->status,
                'notes' => $booking->notes,
                'created_at' => $booking->created_at
            );
            
            wp_send_json_success($booking_data);
        } else {
            wp_send_json_error(array('message' => __('Booking not found', 'cleaning-booking')));
        }
    }
    
    public function ajax_update_booking() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $booking_id = intval($_POST['booking_id']);
        
        $data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'service_id' => intval($_POST['service_id']),
            'square_meters' => intval($_POST['square_meters']),
            'booking_date' => sanitize_text_field($_POST['booking_date']),
            'booking_time' => sanitize_text_field($_POST['booking_time']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );
        
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        
        $result = $wpdb->update($table, $data, array('id' => $booking_id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Booking updated successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error updating booking', 'cleaning-booking')));
        }
    }
    
    public function ajax_delete_booking() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $booking_id = intval($_POST['booking_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        
        $result = $wpdb->delete($table, array('id' => $booking_id));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Booking deleted successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting booking', 'cleaning-booking')));
        }
    }
    
    public function ajax_get_services() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        $services = CB_Database::get_services(false);
        
        $formatted_services = array();
        foreach ($services as $service) {
            $formatted_services[] = array(
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'base_price' => floatval($service->base_price)
            );
        }
        
        wp_send_json_success($formatted_services);
    }
    
    public function ajax_save_booking() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cleaning-booking'));
        }
        
        // Split datetime-local back into date and time
        $booking_datetime = sanitize_text_field($_POST['booking_datetime']);
        $date_time_parts = explode('T', $booking_datetime);
        $booking_date = $date_time_parts[0]; // YYYY-MM-DD
        $booking_time = isset($date_time_parts[1]) ? $date_time_parts[1] : '09:00'; // HH:MM
        
        $data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'service_id' => intval($_POST['service_id']),
            'square_meters' => intval($_POST['square_meters']),
            'booking_date' => $booking_date,
            'booking_time' => $booking_time,
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $booking_id = intval($_POST['id']);
        
        if ($booking_id && $booking_id > 0) {
            // Update existing booking
            $result = CB_Database::update_booking($booking_id, $data);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Booking updated successfully!', 'cleaning-booking'),
                    'booking_id' => $booking_id
                ));
            } else {
                wp_send_json_error(array('message' => __('Error updating booking', 'cleaning-booking')));
            }
        } else {
            // Create new booking
            $data['booking_reference'] = 'CB-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $result = CB_Database::create_booking($data);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Booking created successfully!', 'cleaning-booking'),
                    'booking_id' => $result
                ));
            } else {
                wp_send_json_error(array('message' => __('Error creating booking', 'cleaning-booking')));
            }
        }
    }
    
}
