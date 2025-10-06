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
        
        // Run migrations first for existing installations
        self::run_migrations();
        
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
            default_area int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Service extras table (combining previous extras + junction table)
        $service_extras_table = $wpdb->prefix . 'cb_service_extras';
        $service_extras_sql = "CREATE TABLE $service_extras_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            duration int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
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
            status enum('pending','confirmed','paid','cancelled','completed','on-hold') NOT NULL DEFAULT 'pending',
            truck_id mediumint(9) DEFAULT NULL,
            woocommerce_order_id int(11) DEFAULT NULL,
            extras_data text,
            notes text,
            payment_method varchar(50) DEFAULT 'cash',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_reference (booking_reference),
            KEY service_id (service_id),
            KEY truck_id (truck_id),
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
        
        // Trucks table
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        $trucks_sql = "CREATE TABLE $trucks_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            truck_name varchar(255) NOT NULL,
            truck_number varchar(50),
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($services_sql);
        dbDelta($service_extras_sql);
        dbDelta($zip_codes_sql);
        dbDelta($bookings_sql);
        dbDelta($booking_extras_sql);
        dbDelta($slot_holds_sql);
        dbDelta($trucks_sql);
        
        // Insert default data
        self::insert_default_data();
    }
    
    public static function run_migrations() {
        // Run all migration checks
        self::check_service_extras_table();
        self::check_payment_method_column();
        self::check_default_area_column();
    }
    
    private static function check_service_extras_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cb_service_extras';
        $old_extras_table = $wpdb->prefix . 'cb_extras';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $old_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_extras_table'") == $old_extras_table;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $service_extras_sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                service_id mediumint(9) NOT NULL,
                name varchar(255) NOT NULL,
                description text,
                price decimal(10,2) NOT NULL DEFAULT 0.00,
                duration int(11) NOT NULL DEFAULT 0,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                sort_order int(11) NOT NULL DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY service_id (service_id),
                KEY is_active (is_active),
                KEY sort_order (sort_order)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($service_extras_sql);
        }
        
        // Migration: Migrate existing extras data
        if ($old_table_exists) {
            self::migrate_extras_data();
        }
    }
    
    private static function migrate_extras_data() {
        global $wpdb;
        
        $old_extras_table = $wpdb->prefix . 'cb_extras';
        $new_service_extras_table = $wpdb->prefix . 'cb_service_extras';
        
        // Get all existing extras
        $extras = $wpdb->get_results("SELECT * FROM $old_extras_table");
        
        if (!empty($extras)) {
            // Get the first service to assign existing extras to
            $services = CB_Database::get_services(false);
            $default_service_id = isset($services[0]) ? $services[0]->id : null;
            
            foreach ($extras as $extra) {
                $wpdb->insert($new_service_extras_table, array(
                    'service_id' => $default_service_id ?: 1,
                    'name' => $extra->name,
                    'description' => $extra->description,
                    'price' => $extra->price,
                    'duration' => $extra->duration,
                    'is_active' => $extra->is_active,
                    'sort_order' => $extra->sort_order,
                    'created_at' => $extra->created_at,
                    'updated_at' => $extra->updated_at
                ));
            }
        }
        
        // Drop the old extras table after migration
        $wpdb->query("DROP TABLE IF EXISTS $old_extras_table");
    }
    
    private static function check_payment_method_column() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        
        // Check if payment_method column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $bookings_table LIKE 'payment_method'");
        
        if (empty($column_exists)) {
            // Add payment_method column
            $wpdb->query("ALTER TABLE $bookings_table ADD COLUMN payment_method varchar(50) DEFAULT 'cash' AFTER notes");
        }
    }
    
    private static function check_default_area_column() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'cb_services';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $services_table LIKE 'default_area'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $services_table ADD COLUMN default_area int(11) NOT NULL DEFAULT 0 AFTER sqm_duration_multiplier");
            error_log("CB Debug - Added default_area column to services table");
        }
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
        
        // Insert default extras for each service
        $service_extras_table = $wpdb->prefix . 'cb_service_extras';
        $default_extras = array(
            array('name' => 'Dishwashing', 'description' => 'Wash and put away dishes', 'price' => 15.00, 'duration' => 30, 'sort_order' => 1),
            array('name' => 'Ironing', 'description' => 'Iron clothes and linens', 'price' => 20.00, 'duration' => 45, 'sort_order' => 2),
            array('name' => 'Cooking', 'description' => 'Prepare simple meals', 'price' => 25.00, 'duration' => 60, 'sort_order' => 3),
            array('name' => 'Window Cleaning', 'description' => 'Clean interior and exterior windows', 'price' => 30.00, 'duration' => 45, 'sort_order' => 4)
        );
        
        // Get default services to assign extras to
        $services = $wpdb->get_results("SELECT id FROM $services_table ORDER BY sort_order");
        
        if (!empty($services)) {
            foreach ($services as $service) {
                foreach ($default_extras as $extra) {
                    // Check if extra already exists for this service
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $service_extras_table WHERE service_id = %d AND name = %s",
                        $service->id,
                        $extra['name']
                    ));
                    
                    if (!$existing) {
                        $extra_data = array_merge($extra, array('service_id' => $service->id));
                        $wpdb->insert($service_extras_table, $extra_data);
                    }
                }
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
    
    public static function get_service($service_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_services';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $service_id));
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
    
    public static function check_zip_availability($zip_code) {
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
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT surcharge FROM $table WHERE zip_code = %s AND is_active = 1",
            $zip_code
        ));
        
        return $result ?: 0;
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
            'truck_id' => isset($data['truck_id']) ? intval($data['truck_id']) : null,
            'extras_data' => json_encode($data['extras']),
            'notes' => sanitize_textarea_field($data['notes']),
            'payment_method' => sanitize_text_field($data['payment_method'])
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
            // Handle both formats: array of IDs or array of objects
            if (is_numeric($extra)) {
                // Simple ID format: [2, 3, 4]
                $extra_id = intval($extra);
                $quantity = 1; // Default quantity
                $price = 0; // Will be calculated later
            } else {
                // Object format: [{'id': 2, 'quantity': 1, 'price': 25.00}]
                $extra_id = intval($extra['id']);
                $quantity = intval($extra['quantity']);
                $price = floatval($extra['price']);
            }
            
            // Only insert if extra_id is valid
            if ($extra_id > 0) {
                $wpdb->insert($table, array(
                    'booking_id' => $booking_id,
                    'extra_id' => $extra_id,
                    'quantity' => $quantity,
                    'price' => $price
                ));
            }
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
    
    public static function update_booking_status($booking_id, $status, $woocommerce_order_id = null, $payment_method = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        
        $data = array('status' => $status);
        if ($woocommerce_order_id) {
            $data['woocommerce_order_id'] = $woocommerce_order_id;
        }
        if ($payment_method) {
            $data['payment_method'] = sanitize_text_field($payment_method);
        }
        
        return $wpdb->update($table, $data, array('id' => $booking_id));
    }
    
    public static function update_booking($booking_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_bookings';
        
        $update_data = array(
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'address' => sanitize_textarea_field($data['address']),
            'service_id' => intval($data['service_id']),
            'square_meters' => intval($data['square_meters']),
            'booking_date' => sanitize_text_field($data['booking_date']),
            'booking_time' => sanitize_text_field($data['booking_time']),
            'notes' => sanitize_textarea_field($data['notes']),
            'status' => sanitize_text_field($data['status'])
        );
        
        return $wpdb->update($table, $update_data, array('id' => $booking_id));
    }
    
    public static function get_service_extras($service_id, $active_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_service_extras';
        $where = $wpdb->prepare("WHERE service_id = %d", $service_id);
        
        if ($active_only) {
            $where .= " AND is_active = 1";
        }
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY id DESC");
    }
    
    public static function get_available_extras_for_service($service_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_service_extras';
        
        // Get extras from other services that are not yet assigned to this service
        return $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT name, description, price, duration 
            FROM $table 
            WHERE service_id != %d AND is_active = 1
            AND name NOT IN (
                SELECT name FROM $table 
                WHERE service_id = %d AND is_active = 1
            )
            ORDER BY name
        ", $service_id, $service_id));
    }
    
    public static function add_service_extra($service_id, $extra_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_service_extras';
        
        $data = array(
            'service_id' => $service_id,
            'name' => sanitize_text_field($extra_data['name']),
            'description' => sanitize_textarea_field($extra_data['description']),
            'price' => floatval($extra_data['price']),
            'duration' => intval($extra_data['duration']),
            'is_active' => isset($extra_data['is_active']) ? intval($extra_data['is_active']) : 1,
            'sort_order' => isset($extra_data['sort_order']) ? intval($extra_data['sort_order']) : 0
        );
        
        return $wpdb->insert($table, $data);
    }
    
    public static function remove_service_extra($service_id, $extra_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_service_extras';
        
        return $wpdb->delete($table, array(
            'id' => $extra_id,
            'service_id' => $service_id
        ));
    }
    
    public static function update_service_extra($service_id, $extra_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_service_extras';
        
        $update_data = array();
        if (isset($data['name'])) $update_data['name'] = sanitize_text_field($data['name']);
        if (isset($data['description'])) $update_data['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['price'])) $update_data['price'] = floatval($data['price']);
        if (isset($data['duration'])) $update_data['duration'] = intval($data['duration']);
        if (isset($data['is_active'])) $update_data['is_active'] = intval($data['is_active']);
        if (isset($data['sort_order'])) $update_data['sort_order'] = intval($data['sort_order']);
        
        return $wpdb->update($table, $update_data, array(
            'id' => $extra_id,
            'service_id' => $service_id
        ));
    }
    
    public static function delete_service_and_extras($service_id) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'cb_services';
        $service_extras_table = $wpdb->prefix . 'cb_service_extras';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete all extras for this service
            $wpdb->delete($service_extras_table, array('service_id' => $service_id));
            
            // Delete the service
            $result = $wpdb->delete($services_table, array('id' => $service_id));
            
            if ($result) {
                $wpdb->query('COMMIT');
                return true;
            } else {
                $wpdb->query('ROLLBACK');
                return false;
            }
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    // Truck management methods
    public static function get_trucks() {
        global $wpdb;
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        
        return $wpdb->get_results(
            "SELECT * FROM $trucks_table 
             WHERE is_active = 1 
             ORDER BY sort_order ASC, id ASC"
        );
    }
    
    public static function get_all_trucks() {
        global $wpdb;
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        
        return $wpdb->get_results(
            "SELECT * FROM $trucks_table 
             ORDER BY sort_order ASC, id ASC"
        );
    }
    
    public static function get_truck($truck_id) {
        global $wpdb;
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $trucks_table WHERE id = %d",
            $truck_id
        ));
    }
    
    public static function create_truck($truck_name, $truck_number = '', $is_active = 1) {
        global $wpdb;
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        
        $result = $wpdb->insert(
            $trucks_table,
            array(
                'truck_name' => sanitize_text_field($truck_name),
                'truck_number' => sanitize_text_field($truck_number),
                'is_active' => intval($is_active),
                'sort_order' => 0
            ),
            array('%s', '%s', '%d', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public static function update_truck($truck_id, $truck_name, $truck_number = '', $is_active = 1) {
        global $wpdb;
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        
        return $wpdb->update(
            $trucks_table,
            array(
                'truck_name' => sanitize_text_field($truck_name),
                'truck_number' => sanitize_text_field($truck_number),
                'is_active' => intval($is_active)
            ),
            array('id' => intval($truck_id)),
            array('%s', '%s', '%d'),
            array('%d')
        );
    }
    
    public static function delete_truck($truck_id) {
        global $wpdb;
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        
        return $wpdb->delete(
            $trucks_table,
            array('id' => intval($truck_id)),
            array('%d')
        );
    }
    
    public static function get_available_truck($booking_date, $booking_time, $duration) {
        global $wpdb;
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        
        // Get all active trucks
        $trucks = $wpdb->get_results(
            "SELECT * FROM $trucks_table 
             WHERE is_active = 1 
             ORDER BY sort_order ASC, id ASC"
        );
        
        foreach ($trucks as $truck) {
            // Check if this truck is available for the requested time slot
            // One truck = one slot, so we just check if truck has any conflicting bookings
            $conflicting_bookings = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table 
                 WHERE truck_id = %d 
                 AND booking_date = %s 
                 AND status IN ('pending', 'confirmed', 'paid', 'on-hold')
                 AND (
                     (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME(total_duration * 60)) > %s) OR
                     (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME(total_duration * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
                 )",
                $truck->id,
                $booking_date,
                $booking_time,
                $booking_time,
                $booking_time,
                $duration,
                $booking_time,
                $duration
            ));
            
            if ($conflicting_bookings == 0) {
                return $truck;
            }
        }
        
        return null; // No available truck
    }
}
