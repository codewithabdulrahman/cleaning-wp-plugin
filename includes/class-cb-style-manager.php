<?php
/**
 * Style Settings management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Style_Manager {
    
    private static $cache = array();
    private static $cache_expiry = 3600; // 1 hour
    
    public function __construct() {
        // Constructor for future use
    }
    
    /**
     * Get all style settings
     */
    public static function get_all_settings($category = null) {
        $cache_key = "cb_style_settings" . ($category ? "_{$category}" : '');
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $settings = CB_Database::get_style_settings($category);
        
        // Cache the result
        set_transient($cache_key, $settings, self::$cache_expiry);
        
        return $settings;
    }
    
    /**
     * Get a specific style setting
     */
    public static function get_setting($setting_key, $default = '') {
        $cache_key = "cb_style_setting_{$setting_key}";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $setting = CB_Database::get_style_setting($setting_key, $default);
        
        // Cache the result
        set_transient($cache_key, $setting, self::$cache_expiry);
        
        return $setting;
    }
    
    /**
     * Save a style setting
     */
    public static function save_setting($setting_key, $setting_value, $setting_type = 'text', $category = 'general', $description = '') {
        $result = CB_Database::save_style_setting($setting_key, $setting_value, $setting_type, $category, $description);
        
        if ($result !== false) {
            // Clear related caches
            self::clear_setting_cache($setting_key, $category);
        }
        
        return $result;
    }
    
    /**
     * Delete a style setting
     */
    public static function delete_setting($setting_key) {
        $result = CB_Database::delete_style_setting($setting_key);
        
        if ($result) {
            // Clear related caches
            self::clear_setting_cache($setting_key);
        }
        
        return $result;
    }
    
    /**
     * Get typography settings
     */
    public static function get_typography_settings() {
        return self::get_all_settings('typography');
    }
    
    /**
     * Get spacing settings
     */
    public static function get_spacing_settings() {
        return self::get_all_settings('spacing');
    }
    
    /**
     * Get layout settings
     */
    public static function get_layout_settings() {
        return self::get_all_settings('layout');
    }
    
    /**
     * Get language settings
     */
    public static function get_language_settings() {
        return self::get_all_settings('language');
    }
    
    /**
     * Generate CSS variables from settings
     */
    public static function generate_css_variables() {
        $settings = self::get_all_settings();
        $css_vars = array();
        
        foreach ($settings as $key => $setting) {
            $css_key = '--cb-' . str_replace('_', '-', $key);
            $css_vars[$css_key] = $setting['value'];
        }
        
        return $css_vars;
    }
    
    /**
     * Generate dynamic CSS
     */
    public static function generate_dynamic_css() {
        $settings = self::get_all_settings();
        $css = '';
        
        // Typography CSS
        $typography_settings = self::get_typography_settings();
        if (!empty($typography_settings)) {
            $css .= "/* Typography Settings */\n";
            
            if (isset($typography_settings['heading_font_family'])) {
                $css .= ".cb-widget h1, .cb-widget h2, .cb-widget h3, .cb-widget h4, .cb-widget h5, .cb-widget h6 {\n";
                $css .= "  font-family: " . $typography_settings['heading_font_family']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($typography_settings['body_font_family'])) {
                $css .= ".cb-widget, .cb-widget p, .cb-widget span, .cb-widget div {\n";
                $css .= "  font-family: " . $typography_settings['body_font_family']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($typography_settings['heading_font_size'])) {
                $css .= ".cb-widget h1, .cb-widget h2 {\n";
                $css .= "  font-size: " . $typography_settings['heading_font_size']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($typography_settings['body_font_size'])) {
                $css .= ".cb-widget {\n";
                $css .= "  font-size: " . $typography_settings['body_font_size']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($typography_settings['label_font_size'])) {
                $css .= ".cb-widget label {\n";
                $css .= "  font-size: " . $typography_settings['label_font_size']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($typography_settings['button_font_size'])) {
                $css .= ".cb-widget .cb-btn {\n";
                $css .= "  font-size: " . $typography_settings['button_font_size']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($typography_settings['font_weight_heading'])) {
                $css .= ".cb-widget h1, .cb-widget h2, .cb-widget h3, .cb-widget h4, .cb-widget h5, .cb-widget h6 {\n";
                $css .= "  font-weight: " . $typography_settings['font_weight_heading']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($typography_settings['font_weight_body'])) {
                $css .= ".cb-widget {\n";
                $css .= "  font-weight: " . $typography_settings['font_weight_body']['value'] . ";\n";
                $css .= "}\n";
            }
        }
        
        // Spacing CSS
        $spacing_settings = self::get_spacing_settings();
        if (!empty($spacing_settings)) {
            $css .= "\n/* Spacing Settings */\n";
            
            if (isset($spacing_settings['form_padding'])) {
                $css .= ".cb-widget .cb-form-group {\n";
                $css .= "  padding: " . $spacing_settings['form_padding']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($spacing_settings['field_spacing'])) {
                $css .= ".cb-widget .cb-form-group {\n";
                $css .= "  margin-bottom: " . $spacing_settings['field_spacing']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($spacing_settings['button_padding'])) {
                $css .= ".cb-widget .cb-btn {\n";
                $css .= "  padding: " . $spacing_settings['button_padding']['value'] . ";\n";
                $css .= "}\n";
            }
            
            if (isset($spacing_settings['container_max_width'])) {
                $css .= ".cb-widget-container {\n";
                $css .= "  max-width: " . $spacing_settings['container_max_width']['value'] . ";\n";
                $css .= "}\n";
            }
        }
        
        // Layout CSS
        $layout_settings = self::get_layout_settings();
        if (!empty($layout_settings)) {
            $css .= "\n/* Layout Settings */\n";
            
            if (isset($layout_settings['sidebar_position']) && $layout_settings['sidebar_position']['value'] === 'left') {
                $css .= ".cb-content {\n";
                $css .= "  grid-template-columns: 1fr 2fr;\n";
                $css .= "  grid-template-areas: \"sidebar main\";\n";
                $css .= "}\n";
                $css .= ".cb-main-content {\n";
                $css .= "  border-left: 1px solid var(--cb-border-color);\n";
                $css .= "  border-right: none;\n";
                $css .= "}\n";
            }
            
            if (isset($layout_settings['mobile_breakpoint'])) {
                $breakpoint = $layout_settings['mobile_breakpoint']['value'];
                $css .= "\n@media (max-width: {$breakpoint}) {\n";
                $css .= "  .cb-content {\n";
                $css .= "    grid-template-columns: 1fr;\n";
                $css .= "    grid-template-areas: \"main\" \"sidebar\";\n";
                $css .= "  }\n";
                $css .= "}\n";
            }
        }
        
        return $css;
    }
    
    /**
     * Get Google Fonts URL
     */
    public static function get_google_fonts_url() {
        $typography_settings = self::get_typography_settings();
        $fonts = array();
        
        if (isset($typography_settings['heading_font_family'])) {
            $font = $typography_settings['heading_font_family']['value'];
            if (strpos($font, 'Google:') === 0) {
                $fonts[] = str_replace('Google:', '', $font);
            }
        }
        
        if (isset($typography_settings['body_font_family'])) {
            $font = $typography_settings['body_font_family']['value'];
            if (strpos($font, 'Google:') === 0) {
                $fonts[] = str_replace('Google:', '', $font);
            }
        }
        
        if (!empty($fonts)) {
            $fonts = array_unique($fonts);
            $font_families = implode('|', array_map('urlencode', $fonts));
            return "https://fonts.googleapis.com/css2?family={$font_families}&display=swap";
        }
        
        return '';
    }
    
    /**
     * Get available Google Fonts
     */
    public static function get_google_fonts() {
        return array(
            'Inter' => 'Inter',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato' => 'Lato',
            'Montserrat' => 'Montserrat',
            'Poppins' => 'Poppins',
            'Source Sans Pro' => 'Source Sans Pro',
            'Nunito' => 'Nunito',
            'Raleway' => 'Raleway',
            'Ubuntu' => 'Ubuntu',
            'Playfair Display' => 'Playfair Display',
            'Merriweather' => 'Merriweather',
            'PT Sans' => 'PT Sans',
            'PT Serif' => 'PT Serif',
            'Crimson Text' => 'Crimson Text',
            'Libre Baskerville' => 'Libre Baskerville',
            'Fira Sans' => 'Fira Sans',
            'Oswald' => 'Oswald',
            'Droid Sans' => 'Droid Sans',
            'Droid Serif' => 'Droid Serif'
        );
    }
    
    /**
     * Get font weight options
     */
    public static function get_font_weights() {
        return array(
            '100' => '100 (Thin)',
            '200' => '200 (Extra Light)',
            '300' => '300 (Light)',
            '400' => '400 (Normal)',
            '500' => '500 (Medium)',
            '600' => '600 (Semi Bold)',
            '700' => '700 (Bold)',
            '800' => '800 (Extra Bold)',
            '900' => '900 (Black)'
        );
    }
    
    /**
     * Get step indicator styles
     */
    public static function get_step_indicator_styles() {
        return array(
            'numbered' => 'Numbered Steps',
            'icons' => 'Icon Steps',
            'progress' => 'Progress Bar',
            'dots' => 'Dot Indicators'
        );
    }
    
    /**
     * Get sidebar positions
     */
    public static function get_sidebar_positions() {
        return array(
            'right' => 'Right Side',
            'left' => 'Left Side',
            'bottom' => 'Bottom'
        );
    }
    
    /**
     * Export settings to JSON
     */
    public static function export_settings() {
        $settings = self::get_all_settings();
        
        $export_data = array();
        foreach ($settings as $key => $setting) {
            $export_data[$key] = array(
                'value' => $setting['value'],
                'type' => $setting['type'],
                'category' => $setting['category'],
                'description' => $setting['description']
            );
        }
        
        return json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Import settings from JSON
     */
    public static function import_settings($json_data) {
        $settings_data = json_decode($json_data, true);
        if (!$settings_data) {
            return false;
        }
        
        $imported = 0;
        foreach ($settings_data as $key => $setting_data) {
            if (self::save_setting(
                $key,
                $setting_data['value'],
                $setting_data['type'],
                $setting_data['category'],
                $setting_data['description']
            )) {
                $imported++;
            }
        }
        
        return $imported;
    }
    
    /**
     * Reset settings to default
     */
    public static function reset_to_default() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_style_settings';
        
        // Delete all existing settings
        $wpdb->query("DELETE FROM $table");
        
        // Clear cache
        self::clear_all_settings_cache();
        
        // Re-insert default settings
        CB_Database::insert_default_style_settings();
        
        return true;
    }
    
    /**
     * Get settings statistics
     */
    public static function get_settings_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_style_settings';
        
        $stats = array();
        
        // Total settings
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1");
        
        // By category
        $category_stats = $wpdb->get_results(
            "SELECT category, COUNT(*) as count FROM $table WHERE is_active = 1 GROUP BY category ORDER BY category"
        );
        
        $stats['by_category'] = array();
        foreach ($category_stats as $stat) {
            $stats['by_category'][$stat->category] = $stat->count;
        }
        
        return $stats;
    }
    
    /**
     * Clear setting cache for specific key and category
     */
    private static function clear_setting_cache($setting_key, $category = null) {
        $cache_keys = array(
            "cb_style_settings",
            "cb_style_setting_{$setting_key}"
        );
        
        if ($category) {
            $cache_keys[] = "cb_style_settings_{$category}";
        }
        
        foreach ($cache_keys as $cache_key) {
            delete_transient($cache_key);
        }
    }
    
    /**
     * Clear all settings cache
     */
    public static function clear_all_settings_cache() {
        global $wpdb;
        
        // Get all cache keys that start with cb_style_settings
        $cache_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_cb_style_settings%'"
        );
        
        foreach ($cache_keys as $cache_key) {
            $transient_name = str_replace('_transient_', '', $cache_key);
            delete_transient($transient_name);
        }
    }
    
    /**
     * Validate setting value based on type
     */
    public static function validate_setting_value($value, $type) {
        switch ($type) {
            case 'number':
                return is_numeric($value);
            case 'color':
                return preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'checkbox':
                return in_array($value, array('0', '1', 'true', 'false'));
            default:
                return true;
        }
    }
    
    /**
     * Get setting categories
     */
    public static function get_categories() {
        $cache_key = "cb_style_categories";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $categories = CB_Database::get_style_categories();
        
        // Cache the result
        set_transient($cache_key, $categories, self::$cache_expiry);
        
        return $categories;
    }
}
