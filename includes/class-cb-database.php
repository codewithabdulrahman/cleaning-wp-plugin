<?php
/**
 * Database management class for Cleaning Booking System
 * 
 * Handles all database operations including table creation, migrations,
 * and data management with proper error handling and security.
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * CB_Database class
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */
class CB_Database {
    
    /**
     * Database version for migrations
     * 
     * @var string
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor for future use
    }
    
    /**
     * Create all plugin database tables
     * 
     * @return bool True on success, false on failure
     */
    public static function create_tables() {
        global $wpdb;
        
        try {
            $charset_collate = $wpdb->get_charset_collate();
            
            // Run migrations first for existing installations
            self::run_migrations();
            
            // Update existing data to Greek if needed
            self::update_existing_data_to_greek();
            
            // Create tables
            $tables_created = self::create_services_table($charset_collate);
            $tables_created &= self::create_service_extras_table($charset_collate);
            $tables_created &= self::create_bookings_table($charset_collate);
            $tables_created &= self::create_booking_services_table($charset_collate);
            $tables_created &= self::create_booking_extras_table($charset_collate);
            $tables_created &= self::create_trucks_table($charset_collate);
            $tables_created &= self::create_zip_codes_table($charset_collate);
            $tables_created &= self::create_translations_table($charset_collate);
            
            if ($tables_created) {
                update_option('cb_db_version', self::DB_VERSION);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("CB_Database::create_tables error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create services table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_services_table($charset_collate) {
        global $wpdb;
        
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
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        return self::execute_create_table($services_table, $services_sql);
    }
    
    /**
     * Execute CREATE TABLE statement with error handling
     * 
     * @param string $table_name Table name
     * @param string $sql SQL statement
     * @return bool True on success, false on failure
     */
    private static function execute_create_table($table_name, $sql) {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $result = dbDelta($sql);
        
        if (empty($result)) {
            error_log("CB_Database: Failed to create table {$table_name}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Create service extras table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_service_extras_table($charset_collate) {
        global $wpdb;
        
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
        
        return self::execute_create_table($service_extras_table, $service_extras_sql);
    }
    
    /**
     * Create bookings table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_bookings_table($charset_collate) {
        global $wpdb;
        
        // Bookings table
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        $bookings_sql = "CREATE TABLE $bookings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            zipcode varchar(20) NOT NULL,
            address text NOT NULL,
            notes text,
            total_price decimal(10,2) NOT NULL DEFAULT 0.00,
            total_duration int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY customer_email (customer_email)
        ) $charset_collate;";
        
        return self::execute_create_table($bookings_table, $bookings_sql);
    }
    
    /**
     * Create booking services table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_booking_services_table($charset_collate) {
        global $wpdb;
        
        // Booking services junction table
        $booking_services_table = $wpdb->prefix . 'cb_booking_services';
        $booking_services_sql = "CREATE TABLE $booking_services_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_id mediumint(9) NOT NULL,
            service_id mediumint(9) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            duration int(11) NOT NULL DEFAULT 0,
            area int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY service_id (service_id)
        ) $charset_collate;";
        
        return self::execute_create_table($booking_services_table, $booking_services_sql);
    }
    
    /**
     * Create booking extras table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_booking_extras_table($charset_collate) {
        global $wpdb;
        
        // Booking extras junction table
        $booking_extras_table = $wpdb->prefix . 'cb_booking_extras';
        $booking_extras_sql = "CREATE TABLE $booking_extras_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_id mediumint(9) NOT NULL,
            extra_id mediumint(9) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            duration int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY extra_id (extra_id)
        ) $charset_collate;";
        
        return self::execute_create_table($booking_extras_table, $booking_extras_sql);
    }
    
    /**
     * Create trucks table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_trucks_table($charset_collate) {
        global $wpdb;
        
        // Trucks table
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        $trucks_sql = "CREATE TABLE $trucks_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            capacity int(11) NOT NULL DEFAULT 1,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        return self::execute_create_table($trucks_table, $trucks_sql);
    }
    
    /**
     * Create zip codes table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_zip_codes_table($charset_collate) {
        global $wpdb;
        
        // ZIP codes table
        $zip_codes_table = $wpdb->prefix . 'cb_zip_codes';
        $zip_codes_sql = "CREATE TABLE $zip_codes_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            zipcode varchar(20) NOT NULL,
            city varchar(255) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY zipcode (zipcode),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        return self::execute_create_table($zip_codes_table, $zip_codes_sql);
    }
    
    /**
     * Create translations table
     * 
     * @param string $charset_collate Database charset collate
     * @return bool True on success, false on failure
     */
    private static function create_translations_table($charset_collate) {
        global $wpdb;
        
        // Translations table
        $translations_table = $wpdb->prefix . 'cb_translations';
        $translations_sql = "CREATE TABLE $translations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            string_key varchar(255) NOT NULL,
            text_en text NOT NULL,
            text_el text,
            category varchar(50) NOT NULL DEFAULT 'general',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY string_key (string_key),
            KEY category (category),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        return self::execute_create_table($translations_table, $translations_sql);
    }
    
    /**
     * Migrate database to specific version
     * 
     * @param string $version Target version
     */
    private static function migrate_to_version($version) {
        // Add specific migration logic here
        update_option('cb_db_version', $version);
    }
    
    public static function run_migrations() {
        // Run all migration checks
        self::check_service_extras_table();
        self::check_payment_method_column();
        self::check_default_area_column();
        self::check_slot_holds_truck_column();
        self::check_form_fields_table();
        self::check_translations_table();
        self::check_style_settings_table();
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
        }
    }
    
    private static function check_slot_holds_truck_column() {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $slot_holds_table LIKE 'truck_id'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $slot_holds_table ADD COLUMN truck_id mediumint(9) DEFAULT NULL AFTER duration");
        }
    }
    
    private static function check_form_fields_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cb_form_fields';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $form_fields_sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                field_key varchar(100) NOT NULL,
                field_type varchar(50) NOT NULL DEFAULT 'text',
                label_en varchar(255) NOT NULL,
                label_el varchar(255) NOT NULL,
                placeholder_en varchar(255),
                placeholder_el varchar(255),
                is_required tinyint(1) NOT NULL DEFAULT 0,
                is_visible tinyint(1) NOT NULL DEFAULT 1,
                sort_order int(11) NOT NULL DEFAULT 0,
                validation_rules text,
                field_options text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY field_key (field_key),
                KEY field_type (field_type),
                KEY is_visible (is_visible),
                KEY sort_order (sort_order)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($form_fields_sql);
            
            // Insert default form fields
            self::insert_default_form_fields();
        }
    }
    
    private static function check_translations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cb_translations';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $translations_sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                string_key varchar(255) NOT NULL,
                category varchar(100) NOT NULL DEFAULT 'general',
                text_en text NOT NULL,
                text_el text NOT NULL,
                context varchar(255),
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY string_key_category (string_key, category),
                KEY category (category),
                KEY is_active (is_active)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($translations_sql);
            
            // JSON seed removed; translations will be added via admin UI
        }
    }
    
    private static function check_style_settings_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cb_style_settings';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $style_settings_sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                setting_key varchar(100) NOT NULL,
                setting_value text NOT NULL,
                setting_type varchar(50) NOT NULL DEFAULT 'text',
                category varchar(100) NOT NULL DEFAULT 'general',
                description text,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY setting_key (setting_key),
                KEY category (category),
                KEY is_active (is_active)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($style_settings_sql);
            
            // Insert default style settings
            self::insert_default_style_settings();
        }
    }
    
    private static function insert_default_data() {
        global $wpdb;
        
        // Insert default services
        $services_table = $wpdb->prefix . 'cb_services';
        $default_services = array(
            array(
                'name' => 'Καθαρισμός Σπιτιού',
                'description' => 'Πλήρης υπηρεσία καθαρισμού σπιτιού',
                'base_price' => 80.00,
                'base_duration' => 120,
                'sqm_multiplier' => 0.50,
                'sqm_duration_multiplier' => 0.25,
                'sort_order' => 1
            ),
            array(
                'name' => 'Καθαρισμός Διαμερίσματος',
                'description' => 'Υπηρεσία καθαρισμού διαμερίσματος',
                'base_price' => 60.00,
                'base_duration' => 90,
                'sqm_multiplier' => 0.40,
                'sqm_duration_multiplier' => 0.20,
                'sort_order' => 2
            ),
            array(
                'name' => 'Καθαρισμός Γραφείου',
                'description' => 'Καθαρισμός γραφείου και χώρου εργασίας',
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
            array('name' => 'Πλύσιμο Πιάτων', 'description' => 'Πλύσιμο και τακτοποίηση πιάτων', 'price' => 15.00, 'duration' => 30, 'sort_order' => 1),
            array('name' => 'Σιδέρωμα', 'description' => 'Σιδέρωμα ρούχων και λινών', 'price' => 20.00, 'duration' => 45, 'sort_order' => 2),
            array('name' => 'Μαγείρεμα', 'description' => 'Προετοιμασία απλών γευμάτων', 'price' => 25.00, 'duration' => 60, 'sort_order' => 3),
            array('name' => 'Καθαρισμός Παραθύρων', 'description' => 'Καθαρισμός εσωτερικών και εξωτερικών παραθύρων', 'price' => 30.00, 'duration' => 45, 'sort_order' => 4)
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
            array('zip_code' => '10431', 'city' => 'Αθήνα', 'surcharge' => 0.00),
            array('zip_code' => '10432', 'city' => 'Αθήνα', 'surcharge' => 0.00),
            array('zip_code' => '10433', 'city' => 'Αθήνα', 'surcharge' => 0.00),
            array('zip_code' => '54621', 'city' => 'Θεσσαλονίκη', 'surcharge' => 0.00),
            array('zip_code' => '54622', 'city' => 'Θεσσαλονίκη', 'surcharge' => 0.00)
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
    
    private static function insert_default_form_fields() {
        global $wpdb;
        
        $form_fields_table = $wpdb->prefix . 'cb_form_fields';
        $default_fields = array(
            array(
                'field_key' => 'zip_code',
                'field_type' => 'text',
                'label_en' => 'ZIP Code',
                'label_el' => 'Ταχυδρομικός Κώδικας',
                'placeholder_en' => '12345',
                'placeholder_el' => '12345',
                'is_required' => 1,
                'is_visible' => 1,
                'sort_order' => 1,
                'validation_rules' => json_encode(array('required' => true, 'pattern' => '^[0-9]{5}$'))
            ),
            array(
                'field_key' => 'service_selection',
                'field_type' => 'select',
                'label_en' => 'Select Your Service',
                'label_el' => 'Επιλέξτε την Υπηρεσία σας',
                'placeholder_en' => 'Choose a service',
                'placeholder_el' => 'Επιλέξτε υπηρεσία',
                'is_required' => 1,
                'is_visible' => 1,
                'sort_order' => 2,
                'validation_rules' => json_encode(array('required' => true))
            ),
            array(
                'field_key' => 'square_meters',
                'field_type' => 'number',
                'label_en' => 'Space (m²)',
                'label_el' => 'Χώρος (m²)',
                'placeholder_en' => '0',
                'placeholder_el' => '0',
                'is_required' => 0,
                'is_visible' => 1,
                'sort_order' => 3,
                'validation_rules' => json_encode(array('min' => 0, 'max' => 1000))
            ),
            array(
                'field_key' => 'extras',
                'field_type' => 'checkbox',
                'label_en' => 'Additional Services',
                'label_el' => 'Επιπλέον Υπηρεσίες',
                'placeholder_en' => '',
                'placeholder_el' => '',
                'is_required' => 0,
                'is_visible' => 1,
                'sort_order' => 4,
                'validation_rules' => json_encode(array())
            ),
            array(
                'field_key' => 'booking_date',
                'field_type' => 'date',
                'label_en' => 'Date',
                'label_el' => 'Ημερομηνία',
                'placeholder_en' => '',
                'placeholder_el' => '',
                'is_required' => 1,
                'is_visible' => 1,
                'sort_order' => 5,
                'validation_rules' => json_encode(array('required' => true))
            ),
            array(
                'field_key' => 'booking_time',
                'field_type' => 'select',
                'label_en' => 'Available Time Slots',
                'label_el' => 'Διαθέσιμες Χρονικές Περιόδους',
                'placeholder_en' => 'Select time',
                'placeholder_el' => 'Επιλέξτε ώρα',
                'is_required' => 1,
                'is_visible' => 1,
                'sort_order' => 6,
                'validation_rules' => json_encode(array('required' => true))
            ),
            array(
                'field_key' => 'customer_name',
                'field_type' => 'text',
                'label_en' => 'Full Name',
                'label_el' => 'Ονοματεπώνυμο',
                'placeholder_en' => 'Enter your full name',
                'placeholder_el' => 'Εισάγετε το ονοματεπώνυμό σας',
                'is_required' => 1,
                'is_visible' => 1,
                'sort_order' => 7,
                'validation_rules' => json_encode(array('required' => true, 'minlength' => 2))
            ),
            array(
                'field_key' => 'customer_email',
                'field_type' => 'email',
                'label_en' => 'Email Address',
                'label_el' => 'Διεύθυνση Email',
                'placeholder_en' => 'your@email.com',
                'placeholder_el' => 'το@email.com',
                'is_required' => 1,
                'is_visible' => 1,
                'sort_order' => 8,
                'validation_rules' => json_encode(array('required' => true, 'type' => 'email'))
            ),
            array(
                'field_key' => 'customer_phone',
                'field_type' => 'tel',
                'label_en' => 'Phone Number',
                'label_el' => 'Τηλεφωνικός Αριθμός',
                'placeholder_en' => '+30 123 456 7890',
                'placeholder_el' => '+30 123 456 7890',
                'is_required' => 1,
                'is_visible' => 1,
                'sort_order' => 9,
                'validation_rules' => json_encode(array('required' => true))
            ),
            array(
                'field_key' => 'address',
                'field_type' => 'textarea',
                'label_en' => 'Address',
                'label_el' => 'Διεύθυνση',
                'placeholder_en' => 'Street address, apartment, etc.',
                'placeholder_el' => 'Οδική διεύθυνση, διαμέρισμα, κλπ.',
                'is_required' => 0,
                'is_visible' => 1,
                'sort_order' => 10,
                'validation_rules' => json_encode(array())
            ),
            array(
                'field_key' => 'notes',
                'field_type' => 'textarea',
                'label_en' => 'Special Instructions',
                'label_el' => 'Ειδικές Οδηγίες',
                'placeholder_en' => 'Any special requests or instructions...',
                'placeholder_el' => 'Οποιεσδήποτε ειδικές αιτήσεις ή οδηγίες...',
                'is_required' => 0,
                'is_visible' => 1,
                'sort_order' => 11,
                'validation_rules' => json_encode(array())
            )
        );
        
        foreach ($default_fields as $field) {
            // Check if field already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $form_fields_table WHERE field_key = %s",
                $field['field_key']
            ));
            
            if (!$existing) {
                $wpdb->insert($form_fields_table, $field);
            }
        }
    }
    
    // import_json_translations removed
    
    private static function insert_default_style_settings() {
        global $wpdb;
        
        $style_settings_table = $wpdb->prefix . 'cb_style_settings';
        $default_settings = array(
            // Typography settings
            array(
                'setting_key' => 'heading_font_family',
                'setting_value' => 'Inter, sans-serif',
                'setting_type' => 'select',
                'category' => 'typography',
                'description' => 'Font family for headings'
            ),
            array(
                'setting_key' => 'body_font_family',
                'setting_value' => 'Inter, sans-serif',
                'setting_type' => 'select',
                'category' => 'typography',
                'description' => 'Font family for body text'
            ),
            array(
                'setting_key' => 'heading_font_size',
                'setting_value' => '28px',
                'setting_type' => 'text',
                'category' => 'typography',
                'description' => 'Font size for main headings'
            ),
            array(
                'setting_key' => 'body_font_size',
                'setting_value' => '16px',
                'setting_type' => 'text',
                'category' => 'typography',
                'description' => 'Font size for body text'
            ),
            array(
                'setting_key' => 'label_font_size',
                'setting_value' => '14px',
                'setting_type' => 'text',
                'category' => 'typography',
                'description' => 'Font size for form labels'
            ),
            array(
                'setting_key' => 'button_font_size',
                'setting_value' => '16px',
                'setting_type' => 'text',
                'category' => 'typography',
                'description' => 'Font size for buttons'
            ),
            array(
                'setting_key' => 'font_weight_heading',
                'setting_value' => '700',
                'setting_type' => 'select',
                'category' => 'typography',
                'description' => 'Font weight for headings'
            ),
            array(
                'setting_key' => 'font_weight_body',
                'setting_value' => '400',
                'setting_type' => 'select',
                'category' => 'typography',
                'description' => 'Font weight for body text'
            ),
            
            // Spacing settings
            array(
                'setting_key' => 'form_padding',
                'setting_value' => '24px',
                'setting_type' => 'text',
                'category' => 'spacing',
                'description' => 'Padding for form containers'
            ),
            array(
                'setting_key' => 'field_spacing',
                'setting_value' => '20px',
                'setting_type' => 'text',
                'category' => 'spacing',
                'description' => 'Spacing between form fields'
            ),
            array(
                'setting_key' => 'button_padding',
                'setting_value' => '16px 32px',
                'setting_type' => 'text',
                'category' => 'spacing',
                'description' => 'Padding for buttons'
            ),
            array(
                'setting_key' => 'container_max_width',
                'setting_value' => '1200px',
                'setting_type' => 'text',
                'category' => 'spacing',
                'description' => 'Maximum width of the booking widget'
            ),
            
            // Layout settings
            array(
                'setting_key' => 'sidebar_position',
                'setting_value' => 'right',
                'setting_type' => 'select',
                'category' => 'layout',
                'description' => 'Position of the checkout sidebar'
            ),
            array(
                'setting_key' => 'step_indicator_style',
                'setting_value' => 'numbered',
                'setting_type' => 'select',
                'category' => 'layout',
                'description' => 'Style of step indicators'
            ),
            array(
                'setting_key' => 'mobile_breakpoint',
                'setting_value' => '768px',
                'setting_type' => 'text',
                'category' => 'layout',
                'description' => 'Mobile breakpoint for responsive design'
            ),
            
            // Language settings
            array(
                'setting_key' => 'default_language',
                'setting_value' => 'el',
                'setting_type' => 'select',
                'category' => 'language',
                'description' => 'Default language for the booking form'
            ),
            array(
                'setting_key' => 'auto_detect_language',
                'setting_value' => '1',
                'setting_type' => 'checkbox',
                'category' => 'language',
                'description' => 'Automatically detect user language'
            ),
            array(
                'setting_key' => 'geolocation_detection',
                'setting_value' => '0',
                'setting_type' => 'checkbox',
                'category' => 'language',
                'description' => 'Use geolocation for language detection'
            )
        );
        
        foreach ($default_settings as $setting) {
            // Check if setting already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $style_settings_table WHERE setting_key = %s",
                $setting['setting_key']
            ));
            
            if (!$existing) {
                $wpdb->insert($style_settings_table, $setting);
            }
        }
    }
    
    private static function update_existing_data_to_greek() {
        global $wpdb;
        
        // Update existing services to Greek names
        $services_table = $wpdb->prefix . 'cb_services';
        $service_updates = array(
            'House Cleaning' => 'Καθαρισμός Σπιτιού',
            'Apartment Cleaning' => 'Καθαρισμός Διαμερίσματος',
            'Office Cleaning' => 'Καθαρισμός Γραφείου'
        );
        
        foreach ($service_updates as $english_name => $greek_name) {
            $wpdb->update(
                $services_table,
                array('name' => $greek_name),
                array('name' => $english_name),
                array('%s'),
                array('%s')
            );
        }
        
        // Update service descriptions
        $description_updates = array(
            'Complete house cleaning service' => 'Πλήρης υπηρεσία καθαρισμού σπιτιού',
            'Apartment cleaning service' => 'Υπηρεσία καθαρισμού διαμερίσματος',
            'Office and workspace cleaning' => 'Καθαρισμός γραφείου και χώρου εργασίας'
        );
        
        foreach ($description_updates as $english_desc => $greek_desc) {
            $wpdb->update(
                $services_table,
                array('description' => $greek_desc),
                array('description' => $english_desc),
                array('%s'),
                array('%s')
            );
        }
        
        // Update existing extras to Greek names
        $service_extras_table = $wpdb->prefix . 'cb_service_extras';
        $extra_updates = array(
            'Dishwashing' => 'Πλύσιμο Πιάτων',
            'Ironing' => 'Σιδέρωμα',
            'Cooking' => 'Μαγείρεμα',
            'Window Cleaning' => 'Καθαρισμός Παραθύρων'
        );
        
        foreach ($extra_updates as $english_name => $greek_name) {
            $wpdb->update(
                $service_extras_table,
                array('name' => $greek_name),
                array('name' => $english_name),
                array('%s'),
                array('%s')
            );
        }
        
        // Update extra descriptions
        $extra_description_updates = array(
            'Wash and put away dishes' => 'Πλύσιμο και τακτοποίηση πιάτων',
            'Iron clothes and linens' => 'Σιδέρωμα ρούχων και λινών',
            'Prepare simple meals' => 'Προετοιμασία απλών γευμάτων',
            'Clean interior and exterior windows' => 'Καθαρισμός εσωτερικών και εξωτερικών παραθύρων'
        );
        
        foreach ($extra_description_updates as $english_desc => $greek_desc) {
            $wpdb->update(
                $service_extras_table,
                array('description' => $greek_desc),
                array('description' => $english_desc),
                array('%s'),
                array('%s')
            );
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
                
                // Fetch the actual price from service_extras table
                $extra_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT price FROM {$wpdb->prefix}cb_service_extras WHERE id = %d",
                    $extra_id
                ));
                $price = $extra_data ? floatval($extra_data->price) : 0;
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
        
        $result = $wpdb->update($table, $data, array('id' => $booking_id));
        
        // $wpdb->update returns false on error, 0 if no rows affected, or number of affected rows
        // We consider it successful if it's not false (even if 0 rows affected)
        return $result !== false;
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
    
    public static function get_all_extras($active_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_service_extras';
        $where = $active_only ? "WHERE is_active = 1" : "";
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY service_id, id DESC");
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
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        
        // Get all active trucks
        $trucks = $wpdb->get_results(
            "SELECT * FROM $trucks_table 
             WHERE is_active = 1 
             ORDER BY sort_order ASC, id ASC"
        );
        
        foreach ($trucks as $truck) {
            // Check if this truck is available for the requested time slot
            // Include buffer time in conflict detection
            $buffer_time = get_option('cb_buffer_time', 15);
            
            // Check for conflicting bookings
            $conflicting_bookings = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table 
                 WHERE truck_id = %d 
                 AND booking_date = %s 
                 AND status IN ('pending', 'confirmed', 'paid', 'on-hold')
                 AND (
                     (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME((total_duration + %d) * 60)) > %s) OR
                     (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME((total_duration + %d) * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
                 )",
                $truck->id,
                $booking_date,
                $booking_time,
                $buffer_time,
                $booking_time,
                $booking_time,
                $duration,
                $buffer_time,
                $booking_time,
                $duration
            ));
            
            // Check for conflicting slot holds
            $conflicting_holds = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $slot_holds_table 
                 WHERE truck_id = %d 
                 AND booking_date = %s 
                 AND expires_at > NOW()
                 AND (
                     (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME((duration + %d) * 60)) > %s) OR
                     (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME((duration + %d) * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
                 )",
                $truck->id,
                $booking_date,
                $booking_time,
                $buffer_time,
                $booking_time,
                $booking_time,
                $duration,
                $buffer_time,
                $booking_time,
                $duration
            ));
            
            if ($conflicting_bookings == 0 && $conflicting_holds == 0) {
                return $truck;
            }
        }
        
        return null; // No available truck
    }
    
    // Form Fields Management Methods
    public static function get_form_fields($visible_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        $where = $visible_only ? 'WHERE is_visible = 1' : '';
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY sort_order, id");
    }
    
    public static function get_form_field($field_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE field_key = %s", $field_key));
    }
    
    public static function save_form_field($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        
        $field_data = array(
            'field_key' => sanitize_text_field($data['field_key']),
            'field_type' => sanitize_text_field($data['field_type']),
            'label_en' => sanitize_text_field($data['label_en']),
            'label_el' => sanitize_text_field($data['label_el']),
            'placeholder_en' => sanitize_text_field($data['placeholder_en']),
            'placeholder_el' => sanitize_text_field($data['placeholder_el']),
            'is_required' => isset($data['is_required']) ? intval($data['is_required']) : 0,
            'is_visible' => isset($data['is_visible']) ? intval($data['is_visible']) : 1,
            'sort_order' => intval($data['sort_order']),
            'validation_rules' => isset($data['validation_rules']) ? json_encode($data['validation_rules']) : '',
            'field_options' => isset($data['field_options']) ? json_encode($data['field_options']) : ''
        );
        
        if (isset($data['id']) && !empty($data['id'])) {
            return $wpdb->update($table, $field_data, array('id' => intval($data['id'])));
        } else {
            return $wpdb->insert($table, $field_data);
        }
    }
    
    public static function delete_form_field($field_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        return $wpdb->delete($table, array('id' => intval($field_id)));
    }
    
    public static function update_field_order($field_orders) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        
        foreach ($field_orders as $field_id => $order) {
            $wpdb->update($table, array('sort_order' => intval($order)), array('id' => intval($field_id)));
        }
        
        return true;
    }
    
    // Translations Management Methods
    public static function get_translations($category = null, $language = 'en') {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $where = "WHERE is_active = 1";
        if ($category) {
            $where .= $wpdb->prepare(" AND category = %s", $category);
        }
        
        $results = $wpdb->get_results("SELECT * FROM $table $where ORDER BY category, string_key");
        
        $translations = array();
        foreach ($results as $row) {
            // Key translations by English text instead of string_key for frontend compatibility
            $translations[$row->text_en] = $language === 'el' ? $row->text_el : $row->text_en;
        }
        
        return $translations;
    }
    
    public static function get_translation($string_key, $language = 'en', $category = 'general') {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE string_key = %s AND category = %s AND is_active = 1",
            $string_key, $category
        ));
        
        if ($result) {
            return $language === 'el' ? $result->text_el : $result->text_en;
        }
        
        return $string_key; // Fallback to key if translation not found
    }
    
    public static function save_translation($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $translation_data = array(
            'string_key' => sanitize_text_field($data['string_key']),
            'category' => sanitize_text_field($data['category']),
            'text_en' => sanitize_textarea_field($data['text_en']),
            'text_el' => sanitize_textarea_field($data['text_el']),
            'context' => sanitize_text_field($data['context']),
            'is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1
        );
        
        if (isset($data['id']) && !empty($data['id'])) {
            return $wpdb->update($table, $translation_data, array('id' => intval($data['id'])));
        } else {
            return $wpdb->insert($table, $translation_data);
        }
    }
    
    public static function delete_translation($translation_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        return $wpdb->delete($table, array('id' => intval($translation_id)));
    }
    
    public static function get_translation_categories() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $results = $wpdb->get_results("SELECT DISTINCT category FROM $table WHERE is_active = 1 ORDER BY category");
        
        $categories = array();
        foreach ($results as $row) {
            $categories[] = $row->category;
        }
        
        return $categories;
    }
    
    // Style Settings Management Methods
    public static function get_style_settings($category = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_style_settings';
        
        $where = "WHERE is_active = 1";
        if ($category) {
            $where .= $wpdb->prepare(" AND category = %s", $category);
        }
        
        $results = $wpdb->get_results("SELECT * FROM $table $where ORDER BY category, setting_key");
        
        $settings = array();
        foreach ($results as $row) {
            $settings[$row->setting_key] = array(
                'value' => $row->setting_value,
                'type' => $row->setting_type,
                'category' => $row->category,
                'description' => $row->description
            );
        }
        
        return $settings;
    }
    
    public static function get_style_setting($setting_key, $default = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_style_settings';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_key = %s AND is_active = 1",
            $setting_key
        ));
        
        return $result ?: $default;
    }
    
    public static function save_style_setting($setting_key, $setting_value, $setting_type = 'text', $category = 'general', $description = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_style_settings';
        
        $setting_data = array(
            'setting_key' => sanitize_text_field($setting_key),
            'setting_value' => sanitize_text_field($setting_value),
            'setting_type' => sanitize_text_field($setting_type),
            'category' => sanitize_text_field($category),
            'description' => sanitize_text_field($description),
            'is_active' => 1
        );
        
        // Check if setting exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE setting_key = %s",
            $setting_key
        ));
        
        if ($existing) {
            return $wpdb->update($table, $setting_data, array('setting_key' => $setting_key));
        } else {
            return $wpdb->insert($table, $setting_data);
        }
    }
    
    public static function delete_style_setting($setting_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_style_settings';
        return $wpdb->delete($table, array('setting_key' => $setting_key));
    }
    
    public static function get_style_categories() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_style_settings';
        
        $results = $wpdb->get_results("SELECT DISTINCT category FROM $table WHERE is_active = 1 ORDER BY category");
        
        $categories = array();
        foreach ($results as $row) {
            $categories[] = $row->category;
        }
        
        return $categories;
    }
}
