<?php
/**
 * Translation Seeder Class
 * 
 * Handles seeding all translation strings from POT file into the database
 * and ensures no duplicates exist.
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class CB_Translation_Seeder {
    
    /**
     * Seed all translations from POT file
     */
    public static function seed_all() {
        error_log('CB_Translation_Seeder: seed_all() called');
        
        // Check and fix existing data first
        self::check_and_fix_existing_data();
        
        // Always run seeder - it will check for existing English strings internally
        self::seed_from_pot_file();
        self::seed_default_translations();
        
        error_log('CB_Translation_Seeder: Translation seeding completed (existing strings skipped)');
    }
    
    /**
     * Check and fix existing data issues
     */
    private static function check_and_fix_existing_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        // Check if there are any records with NULL is_active
        $null_active = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active IS NULL");
        if ($null_active > 0) {
            error_log("CB_Translation_Seeder: Found $null_active records with NULL is_active, fixing...");
            $wpdb->query("UPDATE $table SET is_active = 1 WHERE is_active IS NULL");
        }
        
        // Check total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $active = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1");
        error_log("CB_Translation_Seeder: Existing data - Total: $total, Active: $active");
    }
    
    /**
     * Force re-seed all translations (for admin use)
     */
    public static function force_seed_all() {
        self::seed_from_pot_file();
        self::seed_default_translations();
        error_log('CB_Translation_Seeder: Force seeded all translations');
    }
    
    /**
     * Seed translations from POT file (deprecated - now using comprehensive seeder)
     */
    private static function seed_from_pot_file() {
        // POT file seeding is deprecated - all translations are now handled by seed_default_translations()
        error_log('CB_Translation_Seeder: POT file seeding is deprecated - using comprehensive seeder instead');
    }
    
    /**
     * Parse POT file content and extract translations
     */
    private static function parse_pot_file($content) {
        $translations = array();
        $lines = explode("\n", $content);
        
        $current_translation = null;
        $in_msgid = false;
        $in_msgstr = false;
        $current_text = '';
        $current_category = 'general';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                // Check for category comments
                if (strpos($line, '# ') === 0) {
                    $category_line = trim(substr($line, 2));
                    if (strpos($category_line, 'Category:') === 0) {
                        $current_category = trim(substr($category_line, 9));
                    }
                }
                continue;
            }
            
            // Start of msgid
            if (strpos($line, 'msgid ') === 0) {
                if ($current_translation) {
                    $translations[] = $current_translation;
                }
                
                $current_translation = array(
                    'string_key' => '',
                    'text_en' => '',
                    'text_el' => '',
                    'category' => $current_category,
                    'context' => '',
                    'is_active' => 1
                );
                
                $in_msgid = true;
                $in_msgstr = false;
                $current_text = trim($line, 'msgid "');
                $current_text = rtrim($current_text, '"');
            }
            // Start of msgstr
            elseif (strpos($line, 'msgstr ') === 0) {
                $in_msgid = false;
                $in_msgstr = true;
                $current_text = trim($line, 'msgstr "');
                $current_text = rtrim($current_text, '"');
            }
            // Continuation line
            elseif (strpos($line, '"') === 0) {
                $current_text .= trim($line, '"');
            }
            // End of translation block
            elseif (empty($line)) {
                if ($current_translation && $in_msgid) {
                    $current_translation['string_key'] = $current_text;
                    $current_translation['text_en'] = $current_text;
                }
                $in_msgid = false;
                $in_msgstr = false;
            }
        }
        
        // Add the last translation
        if ($current_translation) {
            $translations[] = $current_translation;
        }
        
        return $translations;
    }
    
    /**
     * Insert a single translation into the database
     */
    private static function insert_translation($translation) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cb_translations';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            error_log('CB_Translation_Seeder: Translations table does not exist: ' . $table);
            return;
        }
        
        // Check table structure
        $columns = $wpdb->get_results("DESCRIBE $table");
        error_log('CB_Translation_Seeder: Table structure: ' . print_r($columns, true));
        
        // Check if English text already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE text_en = %s",
            $translation['text_en']
        ));
        
        if ($existing > 0) {
            error_log('CB_Translation_Seeder: Skipping existing translation: ' . $translation['text_en']);
            return; // Skip if English text already exists
        }
        
        // Insert new translation
        $result = $wpdb->insert($table, array(
            'string_key' => sanitize_text_field($translation['string_key']),
            'text_en' => sanitize_textarea_field($translation['text_en']),
            'text_el' => sanitize_textarea_field($translation['text_el']),
            'category' => sanitize_text_field($translation['category']),
            'is_active' => isset($translation['is_active']) ? intval($translation['is_active']) : 1
        ));
        
        if ($result === false) {
            error_log('CB_Translation_Seeder: Failed to insert translation: ' . $translation['text_en'] . ' - ' . $wpdb->last_error);
        } else {
            error_log('CB_Translation_Seeder: Successfully inserted translation: ' . $translation['text_en'] . ' (ID: ' . $wpdb->insert_id . ')');
        }
    }
    
    /**
     * Seed default translations for all frontend-facing words
     */
    private static function seed_default_translations() {
        error_log('CB_Translation_Seeder: Starting to seed default translations...');
        
        $default_translations = array(
            // Main Widget Title
            array(
                'string_key' => 'Book Your Cleaning Service',
                'text_en' => 'Book Your Cleaning Service',
                'text_el' => 'Κλείστε το Καθαρισμό σας',
                'category' => 'booking_widget',
                'context' => 'Main widget title'
            ),
            
            // Step Labels
            array(
                'string_key' => 'Location',
                'text_en' => 'Location',
                'text_el' => 'Τοποθεσία',
                'category' => 'steps',
                'context' => 'Step label'
            ),
            array(
                'string_key' => 'Service',
                'text_en' => 'Service',
                'text_el' => 'Υπηρεσία',
                'category' => 'steps',
                'context' => 'Step label'
            ),
            array(
                'string_key' => 'Details',
                'text_en' => 'Details',
                'text_el' => 'Λεπτομέρειες',
                'category' => 'steps',
                'context' => 'Step label'
            ),
            array(
                'string_key' => 'Date & Time',
                'text_en' => 'Date & Time',
                'text_el' => 'Ημερομηνία & Ώρα',
                'category' => 'steps',
                'context' => 'Step label'
            ),
            array(
                'string_key' => 'Checkout',
                'text_en' => 'Checkout',
                'text_el' => 'Αγορά',
                'category' => 'steps',
                'context' => 'Step label'
            ),
            
            // Step Headers
            array(
                'string_key' => 'Enter Your ZIP Code',
                'text_en' => 'Enter Your ZIP Code',
                'text_el' => 'Εισάγετε τον Ταχυδρομικό Κώδικά σας',
                'category' => 'step_headers',
                'context' => 'Step header'
            ),
            array(
                'string_key' => 'Let us know your location to check availability',
                'text_en' => 'Let us know your location to check availability',
                'text_el' => 'Ενημερώστε μας για την τοποθεσία σας για να ελέγξουμε τη διαθεσιμότητα',
                'category' => 'step_headers',
                'context' => 'Step description'
            ),
            array(
                'string_key' => 'Select Your Service',
                'text_en' => 'Select Your Service',
                'text_el' => 'Επιλέξτε την Υπηρεσία σας',
                'category' => 'step_headers',
                'context' => 'Step header'
            ),
            array(
                'string_key' => 'Choose the type of cleaning service you need',
                'text_en' => 'Choose the type of cleaning service you need',
                'text_el' => 'Επιλέξτε τον τύπο καθαριστικής υπηρεσίας που χρειάζεστε',
                'category' => 'step_headers',
                'context' => 'Step description'
            ),
            array(
                'string_key' => 'Service Details',
                'text_en' => 'Service Details',
                'text_el' => 'Λεπτομέρειες Υπηρεσίας',
                'category' => 'step_headers',
                'context' => 'Step header'
            ),
            array(
                'string_key' => 'Tell us about your space and any additional services',
                'text_en' => 'Tell us about your space and any additional services',
                'text_el' => 'Πείτε μας για τον χώρο σας και οποιεσδήποτε επιπλέον υπηρεσίες',
                'category' => 'step_headers',
                'context' => 'Step description'
            ),
            array(
                'string_key' => 'Select Date & Time',
                'text_en' => 'Select Date & Time',
                'text_el' => 'Επιλέξτε Ημερομηνία & Ώρα',
                'category' => 'step_headers',
                'context' => 'Step header'
            ),
            array(
                'string_key' => 'Choose your preferred date and time slot',
                'text_en' => 'Choose your preferred date and time slot',
                'text_el' => 'Επιλέξτε την προτιμώμενη ημερομηνία και χρονική περίοδο',
                'category' => 'step_headers',
                'context' => 'Step description'
            ),
            
            // Form Fields
            array(
                'string_key' => 'ZIP Code',
                'text_en' => 'ZIP Code',
                'text_el' => 'Ταχυδρομικός Κώδικας',
                'category' => 'form_fields',
                'context' => 'Form field label'
            ),
            array(
                'string_key' => 'Space (m²)',
                'text_en' => 'Space (m²)',
                'text_el' => 'Χώρος (m²)',
                'category' => 'form_fields',
                'context' => 'Form field label'
            ),
            array(
                'string_key' => 'Additional Services',
                'text_en' => 'Additional Services',
                'text_el' => 'Επιπλέον Υπηρεσίες',
                'category' => 'form_fields',
                'context' => 'Form field label'
            ),
            array(
                'string_key' => 'Date',
                'text_en' => 'Date',
                'text_el' => 'Ημερομηνία',
                'category' => 'form_fields',
                'context' => 'Form field label'
            ),
            array(
                'string_key' => 'Available Time Slots',
                'text_en' => 'Available Time Slots',
                'text_el' => 'Διαθέσιμες Χρονικές Περιόδους',
                'category' => 'form_fields',
                'context' => 'Form field label'
            ),
            
            // Buttons
            array(
                'string_key' => 'Check Availability',
                'text_en' => 'Check Availability',
                'text_el' => 'Ελέγξτε Διαθεσιμότητα',
                'category' => 'buttons',
                'context' => 'Button text'
            ),
            array(
                'string_key' => 'Back',
                'text_en' => 'Back',
                'text_el' => 'Πίσω',
                'category' => 'buttons',
                'context' => 'Button text'
            ),
            array(
                'string_key' => 'Continue',
                'text_en' => 'Continue',
                'text_el' => 'Συνέχεια',
                'category' => 'buttons',
                'context' => 'Button text'
            ),
            array(
                'string_key' => 'Proceed to Checkout',
                'text_en' => 'Proceed to Checkout',
                'text_el' => 'Προχωρήστε στην Αγορά',
                'category' => 'buttons',
                'context' => 'Button text'
            ),
            
            // Sidebar Content
            array(
                'string_key' => 'Select a service to see pricing',
                'text_en' => 'Select a service to see pricing',
                'text_el' => 'Επιλέξτε υπηρεσία για να δείτε τις τιμές',
                'category' => 'sidebar',
                'context' => 'Sidebar placeholder'
            ),
            array(
                'string_key' => 'Our contractors have all the necessary cleaning products and equipment',
                'text_en' => 'Our contractors have all the necessary cleaning products and equipment',
                'text_el' => 'Οι εργολάβοι μας έχουν όλα τα απαραίτητα καθαριστικά προϊόντα και εξοπλισμό',
                'category' => 'sidebar',
                'context' => 'Info banner text'
            ),
            array(
                'string_key' => 'Approximate working time',
                'text_en' => 'Approximate working time',
                'text_el' => 'Προσεγγιστικός χρόνος εργασίας',
                'category' => 'sidebar',
                'context' => 'Duration label'
            ),
            array(
                'string_key' => 'Selected Services:',
                'text_en' => 'Selected Services:',
                'text_el' => 'Επιλεγμένες Υπηρεσίες:',
                'category' => 'sidebar',
                'context' => 'Selected services label'
            ),
            array(
                'string_key' => 'Pricing:',
                'text_en' => 'Pricing:',
                'text_el' => 'Τιμολόγηση:',
                'category' => 'sidebar',
                'context' => 'Pricing label'
            ),
            array(
                'string_key' => 'Price will be calculated',
                'text_en' => 'Price will be calculated',
                'text_el' => 'Η τιμή θα υπολογιστεί',
                'category' => 'sidebar',
                'context' => 'Price placeholder'
            ),
            
            // Loading Messages
            array(
                'string_key' => 'Loading...',
                'text_en' => 'Loading...',
                'text_el' => 'Φόρτωση...',
                'category' => 'messages',
                'context' => 'Loading message'
            ),
            array(
                'string_key' => 'Loading services...',
                'text_en' => 'Loading services...',
                'text_el' => 'Φόρτωση υπηρεσιών...',
                'category' => 'messages',
                'context' => 'Loading services'
            ),
            array(
                'string_key' => 'Loading extras...',
                'text_en' => 'Loading extras...',
                'text_el' => 'Φόρτωση επιπλέον υπηρεσιών...',
                'category' => 'messages',
                'context' => 'Loading extras'
            ),
            array(
                'string_key' => 'Loading available times...',
                'text_en' => 'Loading available times...',
                'text_el' => 'Φόρτωση διαθέσιμων ωρών...',
                'category' => 'messages',
                'context' => 'Loading time slots'
            ),
            array(
                'string_key' => 'Select a date to see available times',
                'text_en' => 'Select a date to see available times',
                'text_el' => 'Επιλέξτε ημερομηνία για να δείτε διαθέσιμες ώρες',
                'category' => 'messages',
                'context' => 'Date selection prompt'
            ),
            
            // Error Messages
            array(
                'string_key' => 'An error occurred. Please try again.',
                'text_en' => 'An error occurred. Please try again.',
                'text_el' => 'Παρουσιάστηκε σφάλμα. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'error_messages',
                'context' => 'Generic error'
            ),
            array(
                'string_key' => 'Server error. Please try again.',
                'text_en' => 'Server error. Please try again.',
                'text_el' => 'Σφάλμα διακομιστή. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'error_messages',
                'context' => 'Server error'
            ),
            array(
                'string_key' => 'Please enter a ZIP code.',
                'text_en' => 'Please enter a ZIP code.',
                'text_el' => 'Παρακαλούμε εισάγετε ταχυδρομικό κώδικα.',
                'category' => 'error_messages',
                'context' => 'ZIP code required'
            ),
            array(
                'string_key' => 'Please enter a valid 5-digit ZIP code.',
                'text_en' => 'Please enter a valid 5-digit ZIP code.',
                'text_el' => 'Παρακαλούμε εισάγετε έγκυρο 5-ψήφιο ταχυδρομικό κώδικα.',
                'category' => 'error_messages',
                'context' => 'Invalid ZIP code'
            ),
            array(
                'string_key' => 'Sorry, we don\'t provide services in this area yet.',
                'text_en' => 'Sorry, we don\'t provide services in this area yet.',
                'text_el' => 'Λυπούμαστε, δεν παρέχουμε υπηρεσίες σε αυτή την περιοχή ακόμα.',
                'category' => 'error_messages',
                'context' => 'Service unavailable'
            ),
            array(
                'string_key' => 'Please select a service.',
                'text_en' => 'Please select a service.',
                'text_el' => 'Παρακαλούμε επιλέξτε υπηρεσία.',
                'category' => 'error_messages',
                'context' => 'Service selection required'
            ),
            array(
                'string_key' => 'Please select a date.',
                'text_en' => 'Please select a date.',
                'text_el' => 'Παρακαλούμε επιλέξτε ημερομηνία.',
                'category' => 'error_messages',
                'context' => 'Date selection required'
            ),
            array(
                'string_key' => 'Please select a time slot.',
                'text_en' => 'Please select a time slot.',
                'text_el' => 'Παρακαλούμε επιλέξτε χρονική περίοδο.',
                'category' => 'error_messages',
                'context' => 'Time selection required'
            ),
            
            // Success Messages
            array(
                'string_key' => 'Service available in your area!',
                'text_en' => 'Service available in your area!',
                'text_el' => 'Υπηρεσία διαθέσιμη στην περιοχή σας!',
                'category' => 'messages',
                'context' => 'Service available'
            ),
            array(
                'string_key' => 'Great! We provide services in your area.',
                'text_en' => 'Great! We provide services in your area.',
                'text_el' => 'Τέλεια! Παρέχουμε υπηρεσίες στην περιοχή σας.',
                'category' => 'messages',
                'context' => 'Service available confirmation'
            ),
            
            // No Data Messages
            array(
                'string_key' => 'No services available.',
                'text_en' => 'No services available.',
                'text_el' => 'Δεν υπάρχουν διαθέσιμες υπηρεσίες.',
                'category' => 'messages',
                'context' => 'No services'
            ),
            array(
                'string_key' => 'No additional services available.',
                'text_en' => 'No additional services available.',
                'text_el' => 'Δεν υπάρχουν διαθέσιμες επιπλέον υπηρεσίες.',
                'category' => 'messages',
                'context' => 'No extras'
            ),
            array(
                'string_key' => 'No time slots available.',
                'text_en' => 'No time slots available.',
                'text_el' => 'Δεν υπάρχουν διαθέσιμες χρονικές περιόδους.',
                'category' => 'messages',
                'context' => 'No time slots'
            ),
            array(
                'string_key' => 'No additional services available for this service. You can proceed to the next step.',
                'text_en' => 'No additional services available for this service. You can proceed to the next step.',
                'text_el' => 'Δεν υπάρχουν διαθέσιμες επιπλέον υπηρεσίες για αυτή την υπηρεσία. Μπορείτε να προχωρήσετε στο επόμενο βήμα.',
                'category' => 'messages',
                'context' => 'No extras for service'
            ),
            
            // Processing Messages
            array(
                'string_key' => 'Processing...',
                'text_en' => 'Processing...',
                'text_el' => 'Επεξεργασία...',
                'category' => 'messages',
                'context' => 'Processing'
            ),
            array(
                'string_key' => 'Redirecting to checkout...',
                'text_en' => 'Redirecting to checkout...',
                'text_el' => 'Ανακατεύθυνση στην αγορά...',
                'category' => 'messages',
                'context' => 'Redirecting'
            ),
            
            // Language Options
            array(
                'string_key' => 'English',
                'text_en' => 'English',
                'text_el' => 'English',
                'category' => 'language',
                'context' => 'Language option'
            ),
            array(
                'string_key' => 'Ελληνικά',
                'text_en' => 'Ελληνικά',
                'text_el' => 'Ελληνικά',
                'category' => 'language',
                'context' => 'Language option'
            ),
            
            // Time Units
            array(
                'string_key' => 'minutes',
                'text_en' => 'minutes',
                'text_el' => 'λεπτά',
                'category' => 'time_units',
                'context' => 'Time unit'
            ),
            array(
                'string_key' => 'hours',
                'text_en' => 'hours',
                'text_el' => 'ώρες',
                'category' => 'time_units',
                'context' => 'Time unit'
            ),
            array(
                'string_key' => 'hour',
                'text_en' => 'hour',
                'text_el' => 'ώρα',
                'category' => 'time_units',
                'context' => 'Time unit'
            ),
            
            // Service Details
            array(
                'string_key' => 'Enter total space beyond the default area, or leave as 0 to skip',
                'text_en' => 'Enter total space beyond the default area, or leave as 0 to skip',
                'text_el' => 'Εισάγετε Σύνολο χώρο πέρα από την προεπιλεγμένη περιοχή, ή αφήστε 0 για παράλειψη',
                'category' => 'service_details',
                'context' => 'Space input hint'
            ),
            array(
                'string_key' => 'Base Service Included',
                'text_en' => 'Base Service Included',
                'text_el' => 'Βασική Υπηρεσία Περιλαμβάνεται',
                'category' => 'service_details',
                'context' => 'Base service info'
            ),
            
            // Pricing Labels
            array(
                'string_key' => 'Service Price:',
                'text_en' => 'Service Price:',
                'text_el' => 'Τιμή Υπηρεσίας:',
                'category' => 'pricing',
                'context' => 'Service price label'
            ),
            array(
                'string_key' => 'Extras:',
                'text_en' => 'Extras:',
                'text_el' => 'Επιπλέον:',
                'category' => 'pricing',
                'context' => 'Extras price label'
            ),
            array(
                'string_key' => 'Total:',
                'text_en' => 'Total:',
                'text_el' => 'Σύνολο:',
                'category' => 'pricing',
                'context' => 'Total price label'
            ),
            array(
                'string_key' => 'Service Duration:',
                'text_en' => 'Service Duration:',
                'text_el' => 'Διάρκεια Υπηρεσίας:',
                'category' => 'pricing',
                'context' => 'Service duration label'
            ),
            array(
                'string_key' => 'Extras Duration:',
                'text_en' => 'Extras Duration:',
                'text_el' => 'Διάρκεια Επιπλέον:',
                'category' => 'pricing',
                'context' => 'Extras duration label'
            ),
            array(
                'string_key' => 'Total Duration:',
                'text_en' => 'Total Duration:',
                'text_el' => 'Συνολική Διάρκεια:',
                'category' => 'pricing',
                'context' => 'Total duration label'
            ),
            
            // Service Display Labels
            array(
                'string_key' => 'Duration:',
                'text_en' => 'Duration:',
                'text_el' => 'Διάρκεια:',
                'category' => 'service_display',
                'context' => 'Duration label'
            ),
            array(
                'string_key' => 'Includes:',
                'text_en' => 'Includes:',
                'text_el' => 'Περιλαμβάνει:',
                'category' => 'service_display',
                'context' => 'Includes label'
            ),
            
            // Validation Messages
            array(
                'string_key' => 'Square meters cannot be negative',
                'text_en' => 'Square meters cannot be negative',
                'text_el' => 'Τα τετραγωνικά μέτρα δεν μπορούν να είναι αρνητικά',
                'category' => 'validation',
                'context' => 'Negative square meters error'
            ),
            array(
                'string_key' => 'Maximum 1000 square meters allowed',
                'text_en' => 'Maximum 1000 square meters allowed',
                'text_el' => 'Επιτρέπονται μέχρι 1000 τετραγωνικά μέτρα',
                'category' => 'validation',
                'context' => 'Maximum square meters error'
            ),
            array(
                'string_key' => 'This time slot is already booked. Please select another time.',
                'text_en' => 'This time slot is already booked. Please select another time.',
                'text_el' => 'Αυτή η χρονική περίοδος είναι ήδη κρατημένη. Παρακαλούμε επιλέξτε άλλη ώρα.',
                'category' => 'validation',
                'context' => 'Time slot booked error'
            ),
            array(
                'string_key' => 'No trucks available for the selected time slot. Please choose another time.',
                'text_en' => 'No trucks available for the selected time slot. Please choose another time.',
                'text_el' => 'Δεν υπάρχουν διαθέσιμα φορτηγά για την επιλεγμένη χρονική περίοδο. Παρακαλούμε επιλέξτε άλλη ώρα.',
                'category' => 'validation',
                'context' => 'No trucks available error'
            ),
            
            // Network and Technical Messages
            array(
                'string_key' => 'Network error. Please check your connection and try again.',
                'text_en' => 'Network error. Please check your connection and try again.',
                'text_el' => 'Σφάλμα δικτύου. Παρακαλούμε ελέγξτε τη σύνδεσή σας και προσπαθήστε ξανά.',
                'category' => 'technical',
                'context' => 'Network error'
            ),
            array(
                'string_key' => 'Unable to calculate price. Please try again.',
                'text_en' => 'Unable to calculate price. Please try again.',
                'text_el' => 'Δεν είναι δυνατός ο υπολογισμός της τιμής. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'technical',
                'context' => 'Price calculation error'
            ),
            array(
                'string_key' => 'Error loading data.',
                'text_en' => 'Error loading data.',
                'text_el' => 'Σφάλμα φόρτωσης δεδομένων.',
                'category' => 'technical',
                'context' => 'Data loading error'
            ),
            
            // Language Switch Messages
            array(
                'string_key' => 'Language switched successfully',
                'text_en' => 'Language switched successfully',
                'text_el' => 'Η γλώσσα άλλαξε επιτυχώς',
                'category' => 'language',
                'context' => 'Language switch success'
            ),
            
            // Booking Process Messages
            array(
                'string_key' => 'Booking created successfully!',
                'text_en' => 'Booking created successfully!',
                'text_el' => 'Η κράτηση δημιουργήθηκε επιτυχώς!',
                'category' => 'booking',
                'context' => 'Booking success'
            ),
            array(
                'string_key' => 'Booking failed. Please try again.',
                'text_en' => 'Booking failed. Please try again.',
                'text_el' => 'Η κράτηση απέτυχε. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'booking',
                'context' => 'Booking failure'
            ),
            array(
                'string_key' => 'Failed to create booking. Please try again.',
                'text_en' => 'Failed to create booking. Please try again.',
                'text_el' => 'Αποτυχία δημιουργίας κράτησης. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'booking',
                'context' => 'Booking creation failure'
            ),
            array(
                'string_key' => 'An error occurred while creating the booking. Please try again.',
                'text_en' => 'An error occurred while creating the booking. Please try again.',
                'text_el' => 'Παρουσιάστηκε σφάλμα κατά τη δημιουργία της κράτησης. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'booking',
                'context' => 'Booking creation error'
            ),
            
            // Slot Management Messages
            array(
                'string_key' => 'Time slot held successfully.',
                'text_en' => 'Time slot held successfully.',
                'text_el' => 'Η χρονική περίοδος κρατήθηκε επιτυχώς.',
                'category' => 'slot_management',
                'context' => 'Slot held success'
            ),
            array(
                'string_key' => 'Slot is no longer available.',
                'text_en' => 'Slot is no longer available.',
                'text_el' => 'Η χρονική περίοδος δεν είναι πλέον διαθέσιμη.',
                'category' => 'slot_management',
                'context' => 'Slot no longer available'
            ),
            array(
                'string_key' => 'Failed to hold slot. Please try again.',
                'text_en' => 'Failed to hold slot. Please try again.',
                'text_el' => 'Αποτυχία κράτησης χρονικής περιόδου. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'slot_management',
                'context' => 'Slot hold failure'
            ),
            array(
                'string_key' => 'Slot released successfully.',
                'text_en' => 'Slot released successfully.',
                'text_el' => 'Η χρονική περίοδος απελευθερώθηκε επιτυχώς.',
                'category' => 'slot_management',
                'context' => 'Slot released success'
            ),
            array(
                'string_key' => 'Failed to release slot.',
                'text_en' => 'Failed to release slot.',
                'text_el' => 'Αποτυχία απελευθέρωσης χρονικής περιόδου.',
                'category' => 'slot_management',
                'context' => 'Slot release failure'
            ),
            array(
                'string_key' => 'Failed to release slot. Please try again.',
                'text_en' => 'Failed to release slot. Please try again.',
                'text_el' => 'Αποτυχία απελευθέρωσης χρονικής περιόδου. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'slot_management',
                'context' => 'Slot release failure retry'
            ),
            
            // Validation Error Messages
            array(
                'string_key' => 'Date and time are required.',
                'text_en' => 'Date and time are required.',
                'text_el' => 'Απαιτούνται ημερομηνία και ώρα.',
                'category' => 'validation',
                'context' => 'Date time required'
            ),
            array(
                'string_key' => 'Session ID is required.',
                'text_en' => 'Session ID is required.',
                'text_el' => 'Απαιτείται αναγνωριστικό συνεδρίας.',
                'category' => 'validation',
                'context' => 'Session ID required'
            ),
            array(
                'string_key' => 'Please select a service first.',
                'text_en' => 'Please select a service first.',
                'text_el' => 'Παρακαλούμε επιλέξτε πρώτα υπηρεσία.',
                'category' => 'validation',
                'context' => 'Service selection required first'
            ),
            
            // Security Messages
            array(
                'string_key' => 'Security ticket expired. Please refresh the page.',
                'text_en' => 'Security ticket expired. Please refresh the page.',
                'text_el' => 'Το δελτίο ασφαλείας έληξε. Παρακαλούμε ανανεώστε τη σελίδα.',
                'category' => 'security',
                'context' => 'Security ticket expired'
            ),
            
            // Service and Pricing Messages
            array(
                'string_key' => 'Service is required for price calculation.',
                'text_en' => 'Service is required for price calculation.',
                'text_el' => 'Απαιτείται υπηρεσία για τον υπολογισμό της τιμής.',
                'category' => 'pricing',
                'context' => 'Service required for pricing'
            ),
            array(
                'string_key' => 'Pricing system not available.',
                'text_en' => 'Pricing system not available.',
                'text_el' => 'Το σύστημα τιμολόγησης δεν είναι διαθέσιμο.',
                'category' => 'pricing',
                'context' => 'Pricing system unavailable'
            ),
            array(
                'string_key' => 'Service not found.',
                'text_en' => 'Service not found.',
                'text_el' => 'Η υπηρεσία δεν βρέθηκε.',
                'category' => 'pricing',
                'context' => 'Service not found'
            ),
            
            // Checkout Messages
            array(
                'string_key' => 'Checkout URL not available. Please try again.',
                'text_en' => 'Checkout URL not available. Please try again.',
                'text_el' => 'Η διεύθυνση αγοράς δεν είναι διαθέσιμη. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'checkout',
                'context' => 'Checkout URL unavailable'
            ),
            array(
                'string_key' => 'Failed to proceed to checkout. Please try again.',
                'text_en' => 'Failed to proceed to checkout. Please try again.',
                'text_el' => 'Αποτυχία προχώρησης στην αγορά. Παρακαλούμε προσπαθήστε ξανά.',
                'category' => 'checkout',
                'context' => 'Checkout failure'
            ),
            
            // Promocode Messages (if used)
            array(
                'string_key' => 'Please enter a promocode.',
                'text_en' => 'Please enter a promocode.',
                'text_el' => 'Παρακαλούμε εισάγετε κωδικό προσφοράς.',
                'category' => 'promocode',
                'context' => 'Promocode required'
            ),
            array(
                'string_key' => 'Promocode applied successfully!',
                'text_en' => 'Promocode applied successfully!',
                'text_el' => 'Ο κωδικός προσφοράς εφαρμόστηκε επιτυχώς!',
                'category' => 'promocode',
                'context' => 'Promocode success'
            ),
            
            // Additional Service Messages
            array(
                'string_key' => 'I am ordering',
                'text_en' => 'I am ordering',
                'text_el' => 'Κάνω παραγγελία',
                'category' => 'booking',
                'context' => 'Order button text'
            )
        );
        
        foreach ($default_translations as $translation) {
            self::insert_translation($translation);
        }
        
        // Check final count
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1");
        error_log('CB_Translation_Seeder: Final count of active translations: ' . $total_count);
        
        error_log('CB_Translation_Seeder: Seeded ' . count($default_translations) . ' default translations');
    }
    
    /**
     * Bulk delete translations by IDs
     */
    public static function bulk_delete_translations($translation_ids) {
        global $wpdb;
        
        if (empty($translation_ids) || !is_array($translation_ids)) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'cb_translations';
        $ids = array_map('intval', $translation_ids);
        $ids_string = implode(',', $ids);
        
        $deleted = $wpdb->query("DELETE FROM $table WHERE id IN ($ids_string)");
        
        error_log('CB_Translation_Seeder: Bulk deleted ' . $deleted . ' translations');
        return $deleted;
    }
    
    /**
     * Get translation statistics
     */
    public static function get_stats() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cb_translations';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1");
        $missing_el = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1 AND (text_el IS NULL OR text_el = '')");
        
        return array(
            'total' => intval($total),
            'missing_el' => intval($missing_el),
            'translated_el' => intval($total - $missing_el)
        );
    }
    
    /**
     * Public method to seed form field translations
     */
    public static function seed_form_field_translations() {
        return self::seed_default_translations();
    }
    
    /**
     * Import translations from PO file
     */
    public static function import_from_po_file() {
        try {
            $po_file = self::get_po_file_path();
            
            if (!self::validate_po_file($po_file)) {
                return self::create_error_response('PO file not found or invalid');
            }
            
            $po_content = file_get_contents($po_file);
            $translations = self::parse_po_file($po_content);
            
            $results = self::process_translations($translations);
            
            self::log_import_results($results);
            
            return self::create_success_response($results);
            
        } catch (Exception $e) {
            error_log('CB_Translation_Seeder: Import error - ' . $e->getMessage());
            return self::create_error_response('Import failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get PO file path (deprecated - now using comprehensive seeder)
     */
    private static function get_po_file_path() {
        // PO file import is deprecated - all translations are now handled by seed_default_translations()
        error_log('CB_Translation_Seeder: PO file import is deprecated - using comprehensive seeder instead');
        return '';
    }
    
    /**
     * Validate PO file
     */
    private static function validate_po_file($file_path) {
        return file_exists($file_path) && is_readable($file_path);
    }
    
    /**
     * Process translations
     */
    private static function process_translations($translations) {
        $results = array(
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        );
        
        foreach ($translations as $translation) {
            try {
                $result = self::import_translation($translation);
                $results[$result]++;
            } catch (Exception $e) {
                $results['errors']++;
                error_log('CB_Translation_Seeder: Error importing translation - ' . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Create success response
     */
    private static function create_success_response($results) {
        return array(
            'success' => true,
            'message' => sprintf(
                'Import completed: %d imported, %d updated, %d skipped',
                $results['imported'],
                $results['updated'],
                $results['skipped']
            ),
            'imported' => $results['imported'],
            'updated' => $results['updated'],
            'skipped' => $results['skipped']
        );
    }
    
    /**
     * Create error response
     */
    private static function create_error_response($message) {
        return array(
            'success' => false,
            'message' => $message,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0
        );
    }
    
    /**
     * Log import results
     */
    private static function log_import_results($results) {
        error_log(sprintf(
            'CB_Translation_Seeder: Imported %d, Updated %d, Skipped %d, Errors %d translations from PO file',
            $results['imported'],
            $results['updated'],
            $results['skipped'],
            $results['errors']
        ));
    }
    
    /**
     * Parse PO file content and extract translations
     */
    private static function parse_po_file($content) {
        $parser = new CB_PO_Parser();
        return $parser->parse($content);
    }
    
    /**
     * PO File Parser Class
     */
    private static function get_category_mapping() {
        return array(
            'Form Fields' => 'form_fields',
            'Buttons' => 'buttons',
            'Messages' => 'messages',
            'Booking Widget' => 'booking_widget',
            'Steps' => 'steps',
            'Step Headers' => 'step_headers',
            'Sidebar' => 'sidebar',
            'Booking Summary' => 'booking_summary',
            'Error Messages' => 'error_messages',
            'Admin Panel' => 'admin_panel'
        );
    }
    
    /**
     * Map comment to category
     */
    private static function map_comment_to_category($comment) {
        $mapping = self::get_category_mapping();
        
        foreach ($mapping as $keyword => $category) {
            if (strpos($comment, $keyword) !== false) {
                return $category;
            }
        }
        
        return 'general';
    }
    
    /**
     * Import a single translation with duplicate prevention
     */
    private static function import_translation($translation) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cb_translations';
        
        // Validate translation data
        if (!self::validate_translation_data($translation)) {
            throw new Exception('Invalid translation data');
        }
        
        // Check if translation already exists by text_en
        $existing = self::get_existing_translation($table, $translation['text_en']);
        
        if ($existing) {
            return self::handle_existing_translation($table, $existing, $translation);
        } else {
            return self::insert_new_translation($table, $translation);
        }
    }
    
    /**
     * Validate translation data
     */
    private static function validate_translation_data($translation) {
        return isset($translation['text_en']) && 
               isset($translation['text_el']) && 
               isset($translation['category']) &&
               !empty($translation['text_en']);
    }
    
    /**
     * Get existing translation
     */
    private static function get_existing_translation($table, $text_en) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE text_en = %s",
            $text_en
        ));
    }
    
    /**
     * Handle existing translation
     */
    private static function handle_existing_translation($table, $existing, $translation) {
        global $wpdb;
        
        // If exists and text_el is empty, update with Greek translation
        if (empty($existing->text_el)) {
            $wpdb->update(
                $table,
                array(
                    'text_el' => sanitize_textarea_field($translation['text_el']),
                    'category' => sanitize_text_field($translation['category'])
                ),
                array('id' => $existing->id)
            );
            return 'updated';
        } else {
            // If exists and text_el is filled, skip (preserve database version)
            return 'skipped';
        }
    }
    
    /**
     * Insert new translation
     */
    private static function insert_new_translation($table, $translation) {
        global $wpdb;
        
        $result = $wpdb->insert($table, array(
            'string_key' => sanitize_text_field($translation['string_key']),
            'text_en' => sanitize_textarea_field($translation['text_en']),
            'text_el' => sanitize_textarea_field($translation['text_el']),
            'category' => sanitize_text_field($translation['category']),
            'context' => '',
            'is_active' => 1
        ));
        
        if ($result === false) {
            throw new Exception('Failed to insert translation: ' . $wpdb->last_error);
        }
        
        return 'imported';
    }
}
