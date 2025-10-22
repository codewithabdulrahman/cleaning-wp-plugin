<?php
/**
 * Form Fields management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Form_Fields {
    
    private static $cache = array();
    private static $cache_expiry = 3600; // 1 hour
    
    public function __construct() {
        // Constructor for future use
    }
    
    /**
     * Get all form fields
     */
    public static function get_all_fields($visible_only = true) {
        $cache_key = "cb_form_fields_" . ($visible_only ? 'visible' : 'all');
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $fields = CB_Database::get_form_fields($visible_only);
        
        // Cache the result
        set_transient($cache_key, $fields, self::$cache_expiry);
        
        return $fields;
    }
    
    /**
     * Get a specific form field by key
     */
    public static function get_field($field_key) {
        $cache_key = "cb_form_field_{$field_key}";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $field = CB_Database::get_form_field($field_key);
        
        // Cache the result
        set_transient($cache_key, $field, self::$cache_expiry);
        
        return $field;
    }
    
    /**
     * Get form fields for frontend rendering
     */
    public static function get_frontend_fields($language = 'en') {
        $fields = self::get_all_fields(true);
        $frontend_fields = array();
        
        foreach ($fields as $field) {
            $frontend_fields[] = array(
                'key' => $field->field_key,
                'type' => $field->field_type,
                'label' => $language === 'el' ? $field->label_el : $field->label_en,
                'placeholder' => $language === 'el' ? $field->placeholder_el : $field->placeholder_en,
                'required' => (bool) $field->is_required,
                'visible' => (bool) $field->is_visible,
                'order' => $field->sort_order,
                'validation' => $field->validation_rules ? json_decode($field->validation_rules, true) ?: array() : array(),
                'options' => $field->field_options ? json_decode($field->field_options, true) ?: array() : array()
            );
        }
        
        // Sort by order
        usort($frontend_fields, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        return $frontend_fields;
    }
    
    /**
     * Save or update a form field
     */
    public static function save_field($data) {
        $result = CB_Database::save_form_field($data);
        
        if ($result !== false) {
            // Clear related caches
            self::clear_field_cache($data['field_key']);
        }
        
        return $result;
    }
    
    /**
     * Delete a form field
     */
    public static function delete_field($field_id) {
        // Get field data before deletion for cache clearing
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        $field = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $field_id));
        
        $result = CB_Database::delete_form_field($field_id);
        
        if ($result && $field) {
            // Clear related caches
            self::clear_field_cache($field->field_key);
        }
        
        return $result;
    }
    
    /**
     * Update field order
     */
    public static function update_field_order($field_orders) {
        $result = CB_Database::update_field_order($field_orders);
        
        if ($result) {
            // Clear all field caches
            self::clear_all_field_cache();
        }
        
        return $result;
    }
    
    /**
     * Get field types
     */
    public static function get_field_types() {
        return array(
            'text' => array(
                'label' => 'Text Input',
                'description' => 'Single line text input',
                'supports_placeholder' => true,
                'supports_validation' => true
            ),
            'email' => array(
                'label' => 'Email Input',
                'description' => 'Email address input with validation',
                'supports_placeholder' => true,
                'supports_validation' => true
            ),
            'tel' => array(
                'label' => 'Phone Input',
                'description' => 'Telephone number input',
                'supports_placeholder' => true,
                'supports_validation' => true
            ),
            'number' => array(
                'label' => 'Number Input',
                'description' => 'Numeric input with min/max validation',
                'supports_placeholder' => true,
                'supports_validation' => true
            ),
            'textarea' => array(
                'label' => 'Textarea',
                'description' => 'Multi-line text input',
                'supports_placeholder' => true,
                'supports_validation' => true
            ),
            'select' => array(
                'label' => 'Select Dropdown',
                'description' => 'Dropdown selection',
                'supports_placeholder' => true,
                'supports_validation' => true,
                'supports_options' => true
            ),
            'checkbox' => array(
                'label' => 'Checkbox',
                'description' => 'Single checkbox',
                'supports_placeholder' => false,
                'supports_validation' => true
            ),
            'radio' => array(
                'label' => 'Radio Buttons',
                'description' => 'Radio button group',
                'supports_placeholder' => false,
                'supports_validation' => true,
                'supports_options' => true
            ),
            'date' => array(
                'label' => 'Date Input',
                'description' => 'Date picker input',
                'supports_placeholder' => false,
                'supports_validation' => true
            ),
            'time' => array(
                'label' => 'Time Input',
                'description' => 'Time picker input',
                'supports_placeholder' => false,
                'supports_validation' => true
            )
        );
    }
    
    /**
     * Get validation rules
     */
    public static function get_validation_rules() {
        return array(
            'required' => array(
                'label' => 'Required',
                'type' => 'checkbox',
                'description' => 'Field is required'
            ),
            'minlength' => array(
                'label' => 'Minimum Length',
                'type' => 'number',
                'description' => 'Minimum number of characters'
            ),
            'maxlength' => array(
                'label' => 'Maximum Length',
                'type' => 'number',
                'description' => 'Maximum number of characters'
            ),
            'min' => array(
                'label' => 'Minimum Value',
                'type' => 'number',
                'description' => 'Minimum numeric value'
            ),
            'max' => array(
                'label' => 'Maximum Value',
                'type' => 'number',
                'description' => 'Maximum numeric value'
            ),
            'pattern' => array(
                'label' => 'Pattern (Regex)',
                'type' => 'text',
                'description' => 'Regular expression pattern'
            ),
            'email' => array(
                'label' => 'Email Format',
                'type' => 'checkbox',
                'description' => 'Must be valid email format'
            ),
            'url' => array(
                'label' => 'URL Format',
                'type' => 'checkbox',
                'description' => 'Must be valid URL format'
            ),
            'phone' => array(
                'label' => 'Phone Format',
                'type' => 'checkbox',
                'description' => 'Must be valid phone format'
            )
        );
    }
    
    /**
     * Validate field data
     */
    public static function validate_field_data($data) {
        $errors = array();
        
        // Required fields
        if (empty($data['field_key'])) {
            $errors[] = 'Field key is required';
        } elseif (!preg_match('/^[a-z_][a-z0-9_]*$/', $data['field_key'])) {
            $errors[] = 'Field key must contain only lowercase letters, numbers, and underscores, and start with a letter or underscore';
        }
        
        if (empty($data['field_type'])) {
            $errors[] = 'Field type is required';
        } elseif (!array_key_exists($data['field_type'], self::get_field_types())) {
            $errors[] = 'Invalid field type';
        }
        
        if (empty($data['label_en'])) {
            $errors[] = 'English label is required';
        }
        
        if (empty($data['label_el'])) {
            $errors[] = 'Greek label is required';
        }
        
        // Validate field key uniqueness (if not updating)
        if (empty($data['id'])) {
            $existing_field = self::get_field($data['field_key']);
            if ($existing_field) {
                $errors[] = 'Field key already exists';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get field configuration for admin interface
     */
    public static function get_admin_fields() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order, id");
    }
    
    // Duplicate field method removed
    
    /**
     * Get field statistics
     */
    public static function get_field_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_form_fields';
        
        $stats = array();
        
        // Total fields
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        // Visible fields
        $stats['visible'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_visible = 1");
        
        // Required fields
        $stats['required'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_required = 1");
        
        // By field type
        $type_stats = $wpdb->get_results(
            "SELECT field_type, COUNT(*) as count FROM $table GROUP BY field_type ORDER BY field_type"
        );
        
        $stats['by_type'] = array();
        foreach ($type_stats as $stat) {
            $stats['by_type'][$stat->field_type] = $stat->count;
        }
        
        return $stats;
    }
    
    // Export fields method removed
    
    // Import fields method removed
    
    /**
     * Clear field cache for specific key
     */
    private static function clear_field_cache($field_key) {
        $cache_keys = array(
            "cb_form_fields_visible",
            "cb_form_fields_all",
            "cb_form_field_{$field_key}"
        );
        
        foreach ($cache_keys as $cache_key) {
            delete_transient($cache_key);
        }
    }
    
    /**
     * Clear all field caches
     */
    public static function clear_all_field_cache() {
        global $wpdb;
        
        // Get all cache keys that start with cb_form_fields
        $cache_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_cb_form_fields%'"
        );
        
        foreach ($cache_keys as $cache_key) {
            $transient_name = str_replace('_transient_', '', $cache_key);
            delete_transient($transient_name);
        }
    }
    
    // Reset to default method removed
}
