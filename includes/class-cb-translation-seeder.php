<?php
/**
 * Translation Seeder Class
 * Automatically populates translation database on plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Translation_Seeder {
    
    /**
     * Seed all translation data
     */
    public static function seed_all() {
        $results = array();
        
        $results['frontend'] = self::seed_frontend_translations();
        $results['admin'] = self::seed_admin_translations();
        $results['general'] = self::seed_general_translations();
        $results['messages'] = self::seed_messages_translations();
        $results['ui'] = self::seed_ui_translations();
        $results['services'] = self::seed_services_translations();
        
        // Clear translation cache
        if (class_exists('CB_Translations')) {
            CB_Translations::clear_all_translation_cache();
        }
        
        return $results;
    }
    
    /**
     * Seed frontend translations
     */
    private static function seed_frontend_translations() {
        $frontend_strings = [
            // Main widget strings
            'Book Your Cleaning Service',
            'Location',
            'Service', 
            'Details',
            'Date & Time',
            'Checkout',
            
            // Step headers
            'Enter Your ZIP Code',
            'Let us know your location to check availability',
            'Select Your Service',
            'Choose the type of cleaning service you need',
            'Service Details',
            'Tell us about your space and any additional services',
            'Select Date & Time',
            'Choose your preferred date and time slot',
            'Your Details',
            'Please provide your contact information to complete the booking',
            
            // Form labels
            'ZIP Code',
            'Square Meters',
            'Space (m²)',
            'Additional Services',
            'Date',
            'Available Time Slots',
            'Full Name',
            'Email Address',
            'Phone Number',
            'Address',
            'Special Instructions',
            
            // Buttons
            'Check Availability',
            'Back',
            'Continue',
            'I am ordering',
            'Apply',
            'Proceed to Checkout',
            
            // Sidebar
            'Select a service to see pricing',
            'Our contractors have all the necessary cleaning products and equipment',
            'Approximate working time',
            'hours',
            'hour',
            'minutes',
            'Promocode',
            'To be paid:',
            
            // Booking Summary
            'Booking Summary',
            'Service:',
            'Date:',
            'Time:',
            'Duration:',
            'Total Price:',
            
            // Messages
            'Loading services...',
            'Loading extras...',
            'Loading available times...',
            'No services available.',
            'No additional services available.',
            'No time slots available.',
            'Select a date to see available times',
            'Street address, apartment, etc.',
            'Any special requests or instructions...',
            
            // Error Messages
            'Please enter a ZIP code.',
            'Please enter a valid 5-digit ZIP code.',
            'Sorry, we don\'t provide services in this area yet.',
            'Please select a service.',
            'Please enter the square meters.',
            'Please enter your name.',
            'Please enter a valid email address.',
            'Please enter your phone number.',
            'Please select a date.',
            'Please select a time slot.',
            'Booking saved successfully!',
            'Processing...',
            'Security ticket expired. Please refresh the page.',
            'Failed to create booking. Please try again.',
            
            // Additional strings from template
            'Enter additional space beyond the default area, or leave as 0 to skip',
            'Base Service Included',
            'Service Price:',
            'Extras:',
            'Total:',
            'Service Duration:',
            'Extras Duration:',
            'Total Duration:',
            'Selected Services:',
            'Pricing:',
            'Price will be calculated',
            'English',
            'Ελληνικά',
            
            // Additional frontend strings
            'Loading...',
            'An error occurred. Please try again.',
            'Server error. Please try again.',
            'zip_required',
            'zip_invalid',
            'zip_unavailable',
            'select_service',
            'square_meters_required',
            'select_date',
            'select_time',
            'name_required',
            'email_required',
            'email_invalid',
            'phone_required',
            'booking_error',
            'booking_created',
            'booking_saved',
            'redirecting',
            'processing',
            'proceed_checkout',
            'enter_promocode',
            'promocode_applied',
            'loading_services',
            'loading_extras',
            'loading_slots',
            'no_services_available',
            'no_extras_available',
            'no_slots_available',
            'loading_error',
            'minutes',
            'hours',
            'hour'
        ];
        
        return self::insert_strings($frontend_strings, 'frontend');
    }
    
    /**
     * Seed admin translations
     */
    private static function seed_admin_translations() {
        $admin_strings = [
            // Services page
            'Services',
            'Add New Service',
            'Service Name',
            'Description',
            'Base Price',
            'Base price in USD',
            'Base Duration',
            'Base duration in minutes',
            'Price per m²',
            'Additional price per square meter',
            'Duration per m²',
            'Additional duration per square meter (minutes)',
            'Default Area (m²)',
            'Default area in square meters (0 = no default)',
            'Sort Order',
            'Lower numbers appear first',
            'Status',
            'Active',
            'Save Service',
            'Update Service',
            'Create Service',
            'Service Details',
            'Existing Services',
            'No services found',
            'Edit',
            'Delete',
            'Are you sure you want to delete this service?',
            'Service created successfully!',
            'Service updated successfully!',
            'Service deleted successfully!',
            'Error creating service',
            'Error updating service',
            'Error deleting service',
            
            // Bookings page
            'Bookings',
            'Add New Booking',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Optional phone number',
            'Address',
            'Service address',
            'Service',
            'Select Service',
            'Space in Square Meters',
            'Enter the total space area in square meters',
            'Date & Time',
            'Notes',
            'Additional notes for the booking',
            'Save Booking',
            'Update Booking',
            'Existing Bookings',
            'No bookings found',
            'Reference',
            'Customer',
            'Price',
            'Booking Date',
            'Booking Time',
            'Booking Status',
            'Actions',
            'Are you sure you want to delete this booking?',
            'Booking created successfully!',
            'Booking updated successfully!',
            'Booking deleted successfully!',
            'Error creating booking',
            'Error updating booking',
            'Error deleting booking',
            
            // Settings page
            'Cleaning Booking Settings',
            'Booking Settings',
            'Slot Duration',
            'Default slot duration in minutes',
            'Buffer Time',
            'Buffer time between bookings in minutes',
            'Booking Hold Time',
            'How long to hold a slot during checkout in minutes',
            'Frontend Color Customization',
            'Customize the colors of your booking widget frontend. Changes will be applied immediately.',
            'Primary Color',
            'Main brand color for buttons and highlights',
            'Secondary Color',
            'Secondary color for accents and hover states',
            'Background Color',
            'Main background color of the booking widget',
            'Text Color',
            'Main text color for content',
            'Border Color',
            'Border color for form elements and cards',
            'Error Color',
            'Color for error messages and warnings',
            'Success Color',
            'Color for success messages and confirmations',
            'Save Settings',
            'Settings saved!',
            
            // Form Fields page
            'Form Fields',
            'Add New Field',
            'Field Name',
            'Field Type',
            'Select Field Type',
            'English Label',
            'Greek Label',
            'English Placeholder',
            'Greek Placeholder',
            'Required',
            'Optional',
            'Visible',
            'Hidden',
            'Field Options',
            'Validation Rules',
            'Save Field',
            'Update Field',
            'Edit Field',
            'Existing Fields',
            'No fields found',
            'Field Key',
            'Unique identifier for this field (lowercase, underscores only).',
            'Field Order',
            'Field saved successfully!',
            'Field updated successfully!',
            'Field deleted successfully!',
            'Error saving field',
            'Error updating field',
            'Error deleting field',
            
            // ZIP Codes page
            'ZIP Codes',
            'Add New ZIP Code',
            'ZIP Code',
            'City',
            'City name (optional)',
            'State/Province',
            'Surcharge',
            'Additional charge for this ZIP code',
            'Save ZIP Code',
            'Update ZIP Code',
            'Existing ZIP Codes',
            'No ZIP codes found',
            'ZIP code created successfully!',
            'ZIP code updated successfully!',
            'ZIP code deleted successfully!',
            'Error creating ZIP code',
            'Error updating ZIP code',
            'Error deleting ZIP code',
            
            // Trucks page
            'Trucks',
            'Add New Truck',
            'Truck Name',
            'Truck Number',
            'Optional: License plate or identification number',
            'Save Truck',
            'Update Truck',
            'Existing Trucks',
            'No trucks found. Add your first truck above.',
            'Truck created successfully!',
            'Truck updated successfully!',
            'Truck deleted successfully!',
            'Error creating truck',
            'Error updating truck',
            'Error deleting truck',
            'Truck name is required.',
            'Truck not found.',
            'Truck Management',
            
            // Translations page
            'Translation Management',
            'Manage all site strings and their translations in one place. Edit translations directly in the table below.',
            'Total Strings',
            'Translated',
            'Missing Greek',
            'Completion',
            'Add New String',
            'Save All Changes',
            'Cancel Changes',
            'unsaved changes',
            'Category',
            'All Categories',
            'Translation Status',
            'All',
            'Translated',
            'Untranslated',
            'Search',
            'Search in English or Greek...',
            'Clear Filters',
            'Translation Strings',
            'Click on any text to edit it directly. Changes are saved automatically.',
            'String Key',
            'English (Original)',
            'Greek (Translation)',
            'Status',
            'No translations found',
            'Add your first translation string to get started.',
            'Add New Translation',
            'Unique identifier for this translation string.',
            'Select Category',
            'English Text',
            'Greek Text',
            'Context',
            'Optional context information for translators.',
            'Enable this translation',
            'Save Translation',
            'Cancel',
            'Import Translations',
            'Language',
            'English',
            'Greek',
            'JSON File',
            'Select a JSON file containing translations.',
            'Import',
            'Changes saved!',
            'Error saving changes',
            'Network error. Please try again.',
            'Are you sure you want to cancel all unsaved changes?',
            'Saving...',
            'strings shown',
            'Translation saved successfully!',
            'Translation deleted successfully!',
            'Error saving translation.',
            'Error deleting translation.',
            'Error occurred',
            'Insufficient permissions',
            'Click to add translation',
            'Clear Cache',
            'This will clear all translation caches. Continue?',
            'Clearing...',
            'Translation cache cleared successfully!',
            'Error clearing cache: '
        ];
        
        return self::insert_strings($admin_strings, 'admin');
    }
    
    /**
     * Seed general translations
     */
    private static function seed_general_translations() {
        $general_strings = [
            'Additional Services',
            'Address',
            'Any special requests or instructions...',
            'Apply',
            'Approximate working time',
            'Auto-filled from address',
            'Available Time Slots',
            'Back',
            'Bank Transfer',
            'Base Service Included',
            'Book Your Cleaning Service',
            'Booking saved successfully!',
            'Booking Summary',
            'Booking Details',
            'Booking not found',
            'Booking Reference',
            'Cash on Delivery',
            'Check Availability',
            'Checkout',
            'Choose the type of cleaning service you need',
            'Choose your preferred date and time slot',
            'City',
            'Cleaning Booking Plugin',
            'Company Name',
            'Continue',
            'Customer',
            'Date',
            'Date & Time',
            'Details',
            'Direct bank transfer',
            'Duration',
            'Email',
            'Enter additional space beyond the default area, or leave as 0 to skip',
            'Enter Your ZIP Code',
            'Extras Duration:',
            'Extras:',
            'Failed to create booking',
            'Full Address',
            'Full Name',
            'hour',
            'hours',
            'I am ordering',
            'Invalid booking reference.',
            'Invalid service selected',
            'Language switched successfully',
            'Let us know your location to check availability',
            'Location',
            'minutes',
            'No payment methods are currently available. Please contact us to complete your booking.',
            'No trucks available for the selected time slot. Please choose another time.',
            'No WooCommerce payment gateways are enabled. Please enable at least one payment method in',
            'Notes',
            'Optional',
            'Our contractors have all the necessary cleaning products and equipment',
            'Pay when service is completed',
            'Phone',
            'Please provide service_id parameter (e.g., ?service_id=1 or ?service_id=1,2,3)',
            'Pricing:',
            'Proceed to Checkout',
            'Processing...',
            'Promocode',
            'Security ticket expired. Please refresh the page.',
            'Select a date to see available times',
            'Select Date & Time',
            'Select Payment Method',
            'Select Your Service',
            'Selected Services:',
            'Service',
            'Service Details',
            'Service Duration:',
            'Service Price:',
            'Slot is no longer available',
            'Sorry, this time slot is no longer available.',
            'Space (m²)',
            'Space in Square Meters',
            'Special Instructions',
            'State/Province',
            'Street address, apartment, city, state, etc.',
            'Street address, apartment, etc.',
            'Tell us about your space and any additional services',
            'Time',
            'To be paid:',
            'Total Duration:',
            'Total Price:',
            'Total:',
            'WooCommerce → Settings → Payments',
            'Your Details',
            'Your Booking Summary',
            'ZIP Code'
        ];
        
        return self::insert_strings($general_strings, 'general');
    }
    
    /**
     * Seed messages translations
     */
    private static function seed_messages_translations() {
        $messages_strings = [
            'Please enter a valid 5-digit ZIP code.',
            'Please enter a valid email address.',
            'Please enter a ZIP code.',
            'Please enter the space in square meters.',
            'Please enter your name.',
            'Please enter your phone number.',
            'Please provide your contact information to complete the booking',
            'Please select a date.',
            'Please select a service to see pricing',
            'Please select a service.',
            'Please select a time slot.',
            'Sorry, we don\'t provide services in this area yet.'
        ];
        
        return self::insert_strings($messages_strings, 'messages');
    }
    
    /**
     * Seed UI translations
     */
    private static function seed_ui_translations() {
        $ui_strings = [
            'Loading available times...',
            'Loading extras...',
            'Loading services...',
            'No additional services available.',
            'No services available.',
            'No time slots available.'
        ];
        
        return self::insert_strings($ui_strings, 'ui');
    }
    
    /**
     * Seed services translations
     */
    private static function seed_services_translations() {
        $services_strings = [
            'Apartment Cleaning',
            'Apartment cleaning service',
            'Background Color',
            'Booking Widget Preview',
            'Border Color',
            'Border color for form elements and cards',
            'Checkout Button',
            'Color for error messages and warnings',
            'Color for success messages and confirmations',
            'Color Preview',
            'Complete house cleaning service',
            'Customize the colors of your booking widget frontend. Changes will be applied immediately.',
            'Error Color',
            'Frontend Color Customization',
            'House Cleaning',
            'Main background color of the booking widget',
            'Main brand color for buttons and highlights',
            'Main Content Area',
            'Main text color for content',
            'Office and workspace cleaning',
            'Office Cleaning',
            'Primary Color',
            'Sample Button',
            'Search bookings by reference, customer name, email, or phone...',
            'Search services by name or description...',
            'Search trucks by name or description...',
            'Search ZIP codes by code, city, or state...',
            'Secondary Color',
            'Secondary color for accents and hover states',
            'Service information and pricing',
            'Sidebar',
            'Success Color',
            'Success message',
            'Text Color'
        ];
        
        return self::insert_strings($services_strings, 'services');
    }
    
    /**
     * Insert strings into database (avoiding duplicates)
     */
    private static function insert_strings($strings, $category) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $inserted_count = 0;
        $skipped_count = 0;
        
        foreach ($strings as $string) {
            // Check if string already exists in this category
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE text_en = %s AND category = %s AND is_active = 1",
                $string,
                $category
            ));
            
            if ($existing == 0) {
                // Generate a unique string key
                $string_key = $category . '_' . substr(md5($string), 0, 8);
                
                // Ensure string key is unique
                $key_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE string_key = %s",
                    $string_key
                ));
                
                $counter = 1;
                $original_key = $string_key;
                while ($key_exists > 0) {
                    $string_key = $original_key . '_' . $counter;
                    $key_exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE string_key = %s",
                        $string_key
                    ));
                    $counter++;
                }
                
                // Insert the string
                $result = $wpdb->insert(
                    $table,
                    array(
                        'string_key' => $string_key,
                        'text_en' => $string,
                        'text_el' => '', // Empty Greek translation
                        'category' => $category,
                        'context' => '',
                        'is_active' => 1,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
                );
                
                if ($result !== false) {
                    $inserted_count++;
                }
            } else {
                $skipped_count++;
            }
        }
        
        return array('inserted' => $inserted_count, 'skipped' => $skipped_count);
    }
    
    /**
     * Get seeder statistics
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $stats = array();
        $categories = array('frontend', 'admin', 'general', 'messages', 'ui', 'services');
        
        foreach ($categories as $category) {
            $stats[$category] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE category = %s AND is_active = 1",
                $category
            ));
        }
        
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1");
        
        return $stats;
    }
}
