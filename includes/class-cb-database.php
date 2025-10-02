<?php
/**
 * Database management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Database {
    
    public function __construct() {
        // Constructor for future use
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Services table
        $services_table = $wpdb->prefix . 'cb_services';
        $services_sql = "CREATE TABLE $services_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            base_price decimal(10,2) NOT NULL DEFAULT 0.00,
            base_duration int(11) NOT NULL DEFAULT 60,
            sqm_multiplier decimal(10,4) NOT NULL DEFAULT 0.0000,
            sqm_duration_multiplier decimal(10,4) NOT NULL DEFAULT 0.0000,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Extras table
        $extras_table = $wpdb->prefix . 'cb_extras';
        $extras_sql = "CREATE TABLE $extras_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            duration int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // ZIP codes table
        $zip_codes_table = $wpdb->prefix . 'cb_zip_codes';
        $zip_codes_sql = "CREATE TABLE $zip_codes_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            zip_code varchar(20) NOT NULL,
            city varchar(255),
            surcharge decimal(10,2) NOT NULL DEFAULT 0.00,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY zip_code (zip_code)
        ) $charset_collate;";
        
        // Bookings table
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        $bookings_sql = "CREATE TABLE $bookings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_reference varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50),
            zip_code varchar(20) NOT NULL,
            address text,
            service_id mediumint(9) NOT NULL,
            square_meters int(11) NOT NULL,
            total_price decimal(10,2) NOT NULL,
            total_duration int(11) NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            status enum('pending','confirmed','paid','cancelled','completed') NOT NULL DEFAULT 'pending',
            woocommerce_order_id int(11) DEFAULT NULL,
            extras_data text,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_reference (booking_reference),
            KEY service_id (service_id),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY woocommerce_order_id (woocommerce_order_id)
        ) $charset_collate;";
        
        // Booking extras junction table
        $booking_extras_table = $wpdb->prefix . 'cb_booking_extras';
        $booking_extras_sql = "CREATE TABLE $booking_extras_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_id mediumint(9) NOT NULL,
            extra_id mediumint(9) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY extra_id (extra_id)
        ) $charset_collate;";
        
        // Slot holds table (for temporary holds during checkout)
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        $slot_holds_sql = "CREATE TABLE $slot_holds_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            duration int(11) NOT NULL,
            session_id varchar(255) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_date (booking_date),
            KEY booking_time (booking_time),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($services_sql);
        dbDelta($extras_sql);
        dbDelta($zip_codes_sql);
        dbDelta($bookings_sql);
        dbDelta($booking_extras_sql);
        dbDelta($slot_holds_sql);
        
        // Insert default data
        self::insert_default_data();
    }
    
    private static function insert_default_data() {
        global $wpdb;
        
        // Insert default services
        $services_table = $wpdb->prefix . 'cb_services';
        $default_services = array(
            array(
                'name' => 'House Cleaning',
                'description' => 'Complete house cleaning service',
                'base_price' => 80.00,
                'base_duration' => 120,
                'sqm_multiplier' => 0.50,
                'sqm_duration_multiplier' => 0.25,
                'sort_order' => 1
            ),
            array(
                'name' => 'Apartment Cleaning',
                'description' => 'Apartment cleaning service',
                'base_price' => 60.00,
                'base_duration' => 90,
                'sqm_multiplier' => 0.40,
                'sqm_duration_multiplier' => 0.20,
                'sort_order' => 2
            ),
            array(
                'name' => 'Office Cleaning',
                'description' => 'Office and workspace cleaning',
                'base_price' => 100.00,
                'base_duration' => 150,
                'sqm_multiplier' => 0.60,
                'sqm_duration_multiplier' => 0.30,
                'sort_order' => 3
            )
        );
        
        foreach ($default_services as $service) {
            // Check if service already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $services_table WHERE name = %s",
                $service['name']
            ));
            
            if (!$existing) {
            $wpdb->insert($services_table, $service);
            }
        }
        
        // Insert default extras
        $extras_table = $wpdb->prefix . 'cb_extras';
        $default_extras = array(
            array(
                'name' => 'Dishwashing',
                'description' => 'Wash and put away dishes',
                'price' => 15.00,
                'duration' => 30,
                'sort_order' => 1
            ),
            array(
                'name' => 'Ironing',
                'description' => 'Iron clothes and linens',
                'price' => 20.00,
                'duration' => 45,
                'sort_order' => 2
            ),
            array(
                'name' => 'Cooking',
                'description' => 'Prepare simple meals',
                'price' => 25.00,
                'duration' => 60,
                'sort_order' => 3
            ),
            array(
                'name' => 'Window Cleaning',
                'description' => 'Clean interior and exterior windows',
                'price' => 30.00,
                'duration' => 45,
                'sort_order' => 4
            )
        );
        
        foreach ($default_extras as $extra) {
            // Check if extra already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $extras_table WHERE name = %s",
                $extra['name']
            ));
            
            if (!$existing) {
            $wpdb->insert($extras_table, $extra);
            }
        }
        
        // Insert some default ZIP codes
        $zip_codes_table = $wpdb->prefix . 'cb_zip_codes';
        $default_zips = array(
            array('zip_code' => '10001', 'city' => 'New York', 'surcharge' => 0.00),
            array('zip_code' => '10002', 'city' => 'New York', 'surcharge' => 0.00),
            array('zip_code' => '10003', 'city' => 'New York', 'surcharge' => 0.00),
            array('zip_code' => '90210', 'city' => 'Beverly Hills', 'surcharge' => 20.00),
            array('zip_code' => '90211', 'city' => 'Beverly Hills', 'surcharge' => 20.00)
        );
        
        foreach ($default_zips as $zip) {
            // Check if ZIP code already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $zip_codes_table WHERE zip_code = %s",
                $zip['zip_code']
            ));
            
            if (!$existing) {
            $wpdb->insert($zip_codes_table, $zip);
            }
        }
    }
    
    public static function get_services($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_services';
        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY sort_order, name");
    }
    
    public static function get_extras($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_extras';
        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY sort_order, name");
    }
    
    public static function get_zip_codes($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_zip_codes';
        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY zip_code");
    }
    
    public static function is_zip_code_allowed($zip_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_zip_codes';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE zip_code = %s AND is_active = 1",
            $zip_code
        ));
        return $result > 0;
    }
    
    public static function get_zip_surcharge($zip_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_zip_codes';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT surcharge FROM $table WHERE zip_code = %s AND is_active = 1",
            $zip_code
        ));
    }
    
    public static function create_booking($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        
        // Generate unique booking reference
        $booking_reference = 'CB' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $booking_data = array(
            'booking_reference' => $booking_reference,
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'zip_code' => sanitize_text_field($data['zip_code']),
            'address' => sanitize_textarea_field($data['address']),
            'service_id' => intval($data['service_id']),
            'square_meters' => intval($data['square_meters']),
            'total_price' => floatval($data['total_price']),
            'total_duration' => intval($data['total_duration']),
            'booking_date' => sanitize_text_field($data['booking_date']),
            'booking_time' => sanitize_text_field($data['booking_time']),
            'status' => 'pending',
            'extras_data' => json_encode($data['extras']),
            'notes' => sanitize_textarea_field($data['notes'])
        );
        
        $result = $wpdb->insert($table, $booking_data);
        
        if ($result) {
            $booking_id = $wpdb->insert_id;
            
            // Insert booking extras
            if (!empty($data['extras'])) {
                self::add_booking_extras($booking_id, $data['extras']);
            }
            
            return $booking_id;
        }
        
        return false;
    }
    
    public static function add_booking_extras($booking_id, $extras) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_booking_extras';
        
        foreach ($extras as $extra) {
            $wpdb->insert($table, array(
                'booking_id' => $booking_id,
                'extra_id' => $extra['id'],
                'quantity' => $extra['quantity'],
                'price' => $extra['price']
            ));
        }
    }
    
    public static function get_booking($booking_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $booking_id));
    }
    
    public static function get_booking_by_reference($reference) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE booking_reference = %s", $reference));
    }
    
    public static function update_booking_status($booking_id, $status, $woocommerce_order_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        
        $data = array('status' => $status);
        if ($woocommerce_order_id) {
            $data['woocommerce_order_id'] = $woocommerce_order_id;
        }
        
        return $wpdb->update($table, $data, array('id' => $booking_id));
    }
}
