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
        
        // Form Fields AJAX handlers
        add_action('wp_ajax_cb_save_form_field', array($this, 'ajax_save_form_field'));
        add_action('wp_ajax_cb_delete_form_field', array($this, 'ajax_delete_form_field'));
        add_action('wp_ajax_cb_update_field_order', array($this, 'ajax_update_field_order'));
        
        // Translations AJAX handlers
        add_action('wp_ajax_cb_save_translation', array($this, 'ajax_save_translation'));
        add_action('wp_ajax_cb_delete_translation', array($this, 'ajax_delete_translation'));
        add_action('wp_ajax_cb_bulk_update_translations', array($this, 'ajax_bulk_update_translations'));
        add_action('wp_ajax_cb_remove_duplicate_translations', array($this, 'ajax_remove_duplicate_translations'));
        add_action('wp_ajax_cb_clear_translation_cache', array($this, 'ajax_clear_translation_cache'));
        // Import/Export removed per product decision
        
        // Style Settings AJAX handlers
        add_action('wp_ajax_cb_save_style_setting', array($this, 'ajax_save_style_setting'));
        add_action('wp_ajax_cb_reset_style_settings', array($this, 'ajax_reset_style_settings'));
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
            __('Form Fields', 'cleaning-booking'),
            __('Form Fields', 'cleaning-booking'),
            'manage_options',
            'cb-form-fields',
            array($this, 'form_fields_page')
        );
        
        add_submenu_page(
            'cleaning-booking',
            __('Translations', 'cleaning-booking'),
            __('Translations', 'cleaning-booking'),
            'manage_options',
            'cb-translations',
            array($this, 'translations_page')
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
            __('Trucks', 'cleaning-booking'),
            __('Trucks', 'cleaning-booking'),
            'manage_options',
            'cb-trucks',
            array($this, 'trucks_page')
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
        
        // Ensure database migrations are run
        CB_Database::run_migrations();
        
        wp_enqueue_media();
        wp_enqueue_script('cb-admin', CB_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'media-upload'), CB_VERSION, true);
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
            
            // Handle color settings
            $color_settings = array(
                'cb_primary_color' => sanitize_hex_color($_POST['primary_color']),
                'cb_secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                'cb_background_color' => sanitize_hex_color($_POST['background_color']),
                'cb_text_color' => sanitize_hex_color($_POST['text_color']),
                'cb_border_color' => sanitize_hex_color($_POST['border_color']),
                'cb_success_color' => sanitize_hex_color($_POST['success_color']),
                'cb_error_color' => sanitize_hex_color($_POST['error_color'])
            );
            
            foreach ($color_settings as $key => $value) {
                update_option($key, $value);
            }
            
            // Handle style settings
            $style_settings = array(
                // Typography settings
                'heading_font_family' => sanitize_text_field($_POST['heading_font_family']),
                'body_font_family' => sanitize_text_field($_POST['body_font_family']),
                'heading_font_size' => sanitize_text_field($_POST['heading_font_size']),
                'body_font_size' => sanitize_text_field($_POST['body_font_size']),
                'label_font_size' => sanitize_text_field($_POST['label_font_size']),
                'button_font_size' => sanitize_text_field($_POST['button_font_size']),
                'font_weight_heading' => sanitize_text_field($_POST['font_weight_heading']),
                'font_weight_body' => sanitize_text_field($_POST['font_weight_body']),
                
                // Spacing settings
                'form_padding' => sanitize_text_field($_POST['form_padding']),
                'field_spacing' => sanitize_text_field($_POST['field_spacing']),
                'button_padding' => sanitize_text_field($_POST['button_padding']),
                'container_max_width' => sanitize_text_field($_POST['container_max_width']),
                
                // Layout settings
                'sidebar_position' => sanitize_text_field($_POST['sidebar_position']),
                'step_indicator_style' => sanitize_text_field($_POST['step_indicator_style']),
                'mobile_breakpoint' => sanitize_text_field($_POST['mobile_breakpoint']),
                
                // Language settings
                'default_language' => sanitize_text_field($_POST['default_language']),
                'auto_detect_language' => isset($_POST['auto_detect_language']) ? '1' : '0',
                'geolocation_detection' => isset($_POST['geolocation_detection']) ? '1' : '0'
            );
            
            foreach ($style_settings as $key => $value) {
                CB_Style_Manager::save_setting($key, $value, 'text', 'general', '');
            }
            
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
        
        // Debug: Log all POST data
        error_log('Admin save service POST data: ' . print_r($_POST, true));
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'base_price' => floatval($_POST['base_price']),
            'base_duration' => intval($_POST['base_duration']),
            'sqm_multiplier' => floatval($_POST['sqm_multiplier']),
            'sqm_duration_multiplier' => floatval($_POST['sqm_duration_multiplier']),
            'default_area' => intval($_POST['default_area']),
            'icon_url' => esc_url_raw($_POST['icon_url']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'])
        );
        
        // Debug: Log the data being saved
        error_log('Admin save service data array: ' . print_r($data, true));
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing
            $result = $wpdb->update($table, $data, array('id' => intval($_POST['id'])));
            error_log('Database update result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            if ($wpdb->last_error) {
                error_log('Database error: ' . $wpdb->last_error);
            }
            $message = $result ? __('Service updated successfully!', 'cleaning-booking') : __('Error updating service', 'cleaning-booking');
            // Persist translations for service name/description (default both languages to provided values)
            $service_id = intval($_POST['id']);
            if ($service_id) {
                CB_Translations::save_translation(array(
                    'string_key' => 'service_name_' . $service_id,
                    'category' => 'services',
                    'text_en' => $data['name'],
                    'text_el' => $data['name'],
                    'context' => 'Service name'
                ));
                CB_Translations::save_translation(array(
                    'string_key' => 'service_desc_' . $service_id,
                    'category' => 'services',
                    'text_en' => $data['description'],
                    'text_el' => $data['description'],
                    'context' => 'Service description'
                ));
            }
        } else {
            // Insert new
            $result = $wpdb->insert($table, $data);
            $message = $result ? __('Service created successfully!', 'cleaning-booking') : __('Error creating service', 'cleaning-booking');
            if ($result) {
                $service_id = intval($wpdb->insert_id);
                // Initialize translations so admin can edit them later
                CB_Translations::save_translation(array(
                    'string_key' => 'service_name_' . $service_id,
                    'category' => 'services',
                    'text_en' => $data['name'],
                    'text_el' => $data['name'],
                    'context' => 'Service name'
                ));
                CB_Translations::save_translation(array(
                    'string_key' => 'service_desc_' . $service_id,
                    'category' => 'services',
                    'text_en' => $data['description'],
                    'text_el' => $data['description'],
                    'context' => 'Service description'
                ));
            }
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
            $extra_id = intval($_POST['id']);
            if ($extra_id) {
                // Persist translations for extra name/description (defaults to provided value in both langs)
                CB_Translations::save_translation(array(
                    'string_key' => 'extra_name_' . $extra_id,
                    'category' => 'extras',
                    'text_en' => $extra_data['name'],
                    'text_el' => $extra_data['name'],
                    'context' => 'Extra name (service ' . $service_id . ')'
                ));
                CB_Translations::save_translation(array(
                    'string_key' => 'extra_desc_' . $extra_id,
                    'category' => 'extras',
                    'text_en' => $extra_data['description'],
                    'text_el' => $extra_data['description'],
                    'context' => 'Extra description (service ' . $service_id . ')'
                ));
            }
        } else {
            $result = CB_Database::add_service_extra($service_id, $extra_data);
            $message = $result ? __('Extra created successfully!', 'cleaning-booking') : __('Error creating extra', 'cleaning-booking');
            if ($result) {
                $extra_id = intval($wpdb->insert_id);
                CB_Translations::save_translation(array(
                    'string_key' => 'extra_name_' . $extra_id,
                    'category' => 'extras',
                    'text_en' => $extra_data['name'],
                    'text_el' => $extra_data['name'],
                    'context' => 'Extra name (service ' . $service_id . ')'
                ));
                CB_Translations::save_translation(array(
                    'string_key' => 'extra_desc_' . $extra_id,
                    'category' => 'extras',
                    'text_en' => $extra_data['description'],
                    'text_el' => $extra_data['description'],
                    'context' => 'Extra description (service ' . $service_id . ')'
                ));
            }
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
                'base_price' => floatval($service->base_price),
                'icon_url' => isset($service->icon_url) ? $service->icon_url : ''
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
    
    // Form Fields Page Methods
    public function form_fields_page() {
        include CB_PLUGIN_DIR . 'templates/admin/form-fields.php';
    }
    
    public function ajax_save_form_field() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $data = array(
            'id' => isset($_POST['field_id']) ? intval($_POST['field_id']) : '',
            'field_key' => sanitize_text_field($_POST['field_key']),
            'field_type' => sanitize_text_field($_POST['field_type']),
            'label_en' => sanitize_text_field($_POST['label_en']),
            'label_el' => sanitize_text_field($_POST['label_el']),
            'placeholder_en' => sanitize_text_field($_POST['placeholder_en']),
            'placeholder_el' => sanitize_text_field($_POST['placeholder_el']),
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order']),
            'validation_rules' => isset($_POST['validation_rules']) ? $_POST['validation_rules'] : array(),
            'field_options' => isset($_POST['field_options']) ? $_POST['field_options'] : array()
        );
        
        $errors = CB_Form_Fields::validate_field_data($data);
        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode(', ', $errors)));
        }
        
        $result = CB_Form_Fields::save_field($data);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Field saved successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error saving field.', 'cleaning-booking')));
        }
    }
    
    public function ajax_delete_form_field() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $field_id = intval($_POST['field_id']);
        $result = CB_Form_Fields::delete_field($field_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Field deleted successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting field.', 'cleaning-booking')));
        }
    }
    
    public function ajax_update_field_order() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $field_orders = $_POST['field_orders'];
        $result = CB_Form_Fields::update_field_order($field_orders);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Field order updated successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error updating field order.', 'cleaning-booking')));
        }
    }
    
    // Duplicate field method removed
    
    // Translations Page Methods
    public function translations_page() {
        include CB_PLUGIN_DIR . 'templates/admin/translations.php';
    }
    
    public function ajax_save_translation() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $data = array(
            'id' => isset($_POST['translation_id']) ? intval($_POST['translation_id']) : '',
            'string_key' => sanitize_text_field($_POST['string_key']),
            'category' => sanitize_text_field($_POST['category']),
            'text_en' => sanitize_textarea_field($_POST['text_en']),
            'text_el' => sanitize_textarea_field($_POST['text_el']),
            'context' => sanitize_text_field($_POST['context']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        
        $result = CB_Translations::save_translation($data);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Translation saved successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error saving translation.', 'cleaning-booking')));
        }
    }
    
    public function ajax_delete_translation() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $translation_id = intval($_POST['translation_id']);
        $result = CB_Translations::delete_translation($translation_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Translation deleted successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting translation.', 'cleaning-booking')));
        }
    }
    
    public function ajax_bulk_update_translations() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $updates = $_POST['updates'];
        $updated = CB_Translations::bulk_update_translations($updates);
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d translations updated successfully!', 'cleaning-booking'), $updated),
            'updated' => $updated
        ));
    }
    
    // Import/Export translations removed
    
    // Style Settings AJAX Methods
    public function ajax_save_style_setting() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $setting_key = sanitize_text_field($_POST['setting_key']);
        $setting_value = sanitize_text_field($_POST['setting_value']);
        $setting_type = sanitize_text_field($_POST['setting_type']);
        $category = sanitize_text_field($_POST['category']);
        $description = sanitize_text_field($_POST['description']);
        
        $result = CB_Style_Manager::save_setting($setting_key, $setting_value, $setting_type, $category, $description);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Setting saved successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error saving setting.', 'cleaning-booking')));
        }
    }
    
    public function ajax_reset_style_settings() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $result = CB_Style_Manager::reset_to_default();
        
        if ($result) {
            wp_send_json_success(array('message' => __('Style settings reset to default successfully!', 'cleaning-booking')));
        } else {
            wp_send_json_error(array('message' => __('Error resetting style settings.', 'cleaning-booking')));
        }
    }
    
    public function trucks_page() {
        // Handle truck actions
        if (isset($_POST['action'])) {
            $this->handle_truck_action();
        }
        
        $trucks = CB_Database::get_all_trucks();
        
        include CB_PLUGIN_DIR . 'templates/admin/trucks.php';
    }
    
    private function handle_truck_action() {
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'create_truck':
                $truck_name = sanitize_text_field($_POST['truck_name']);
                $truck_number = sanitize_text_field($_POST['truck_number']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($truck_name)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Truck name is required.', 'cleaning-booking') . '</p></div>';
                    });
                    return;
                }
                
                $truck_id = CB_Database::create_truck($truck_name, $truck_number, $is_active);
                
                if ($truck_id) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Truck created successfully!', 'cleaning-booking') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Error creating truck.', 'cleaning-booking') . '</p></div>';
                    });
                }
                break;
                
            case 'update_truck':
                $truck_id = intval($_POST['truck_id']);
                $truck_name = sanitize_text_field($_POST['truck_name']);
                $truck_number = sanitize_text_field($_POST['truck_number']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($truck_name)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Truck name is required.', 'cleaning-booking') . '</p></div>';
                    });
                    return;
                }
                
                $result = CB_Database::update_truck($truck_id, $truck_name, $truck_number, $is_active);
                
                if ($result !== false) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Truck updated successfully!', 'cleaning-booking') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Error updating truck.', 'cleaning-booking') . '</p></div>';
                    });
                }
                break;
                
            case 'delete_truck':
                $truck_id = intval($_POST['truck_id']);
                
                $result = CB_Database::delete_truck($truck_id);
                
                if ($result) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Truck deleted successfully!', 'cleaning-booking') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Error deleting truck.', 'cleaning-booking') . '</p></div>';
                    });
                }
                break;
                
            case 'toggle_truck_status':
                $truck_id = intval($_POST['truck_id']);
                $current_status = intval($_POST['current_status']);
                $new_status = $current_status ? 0 : 1; // Toggle status
                
                $truck = CB_Database::get_truck($truck_id);
                if (!$truck) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Truck not found.', 'cleaning-booking') . '</p></div>';
                    });
                    return;
                }
                
                $result = CB_Database::update_truck($truck_id, $truck->truck_name, $truck->truck_number, $new_status);
                
                if ($result !== false) {
                    $status_text = $new_status ? __('activated', 'cleaning-booking') : __('deactivated', 'cleaning-booking');
                    add_action('admin_notices', function() use ($status_text) {
                        echo '<div class="notice notice-success"><p>' . sprintf(__('Truck %s successfully!', 'cleaning-booking'), $status_text) . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Error updating truck status.', 'cleaning-booking') . '</p></div>';
                    });
                }
                break;
        }
    }
    
    /**
     * AJAX handler for removing duplicate translations
     */
    public function ajax_remove_duplicate_translations() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'cleaning-booking')));
        }
        
        try {
            global $wpdb;
            
            $translations_table = $wpdb->prefix . 'cb_translations';
            
            // Find duplicates based on English text
            $duplicates = $wpdb->get_results("
                SELECT text_en, COUNT(*) as count 
                FROM {$translations_table} 
                WHERE text_en IS NOT NULL AND text_en != '' 
                GROUP BY text_en 
                HAVING COUNT(*) > 1
            ");
            
            $removed_count = 0;
            
            foreach ($duplicates as $duplicate) {
                // Get all records with this English text
                $records = $wpdb->get_results($wpdb->prepare("
                    SELECT id FROM {$translations_table} 
                    WHERE text_en = %s 
                    ORDER BY id ASC
                ", $duplicate->text_en));
                
                // Keep the first record, remove the rest
                if (count($records) > 1) {
                    $ids_to_remove = array_slice(array_column($records, 'id'), 1);
                    
                    if (!empty($ids_to_remove)) {
                        $placeholders = implode(',', array_fill(0, count($ids_to_remove), '%d'));
                        $wpdb->query($wpdb->prepare("
                            DELETE FROM {$translations_table} 
                            WHERE id IN ({$placeholders})
                        ", $ids_to_remove));
                        
                        $removed_count += count($ids_to_remove);
                    }
                }
            }
            
            // Clear translation cache after removing duplicates
            if (class_exists('CB_Translations')) {
                CB_Translations::clear_all_translation_cache();
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully removed %d duplicate translations.', 'cleaning-booking'), $removed_count),
                'removed_count' => $removed_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for clearing translation cache
     */
    public function ajax_clear_translation_cache() {
        check_ajax_referer('cb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'cleaning-booking')));
        }
        
        try {
            // Clear all translation caches
            CB_Translations::clear_all_translation_cache();
            
            wp_send_json_success(array('message' => __('Translation cache cleared successfully!', 'cleaning-booking')));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}
