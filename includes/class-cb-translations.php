<?php
/**
 * Translations management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Translations {
    
    private static $cache = array();
    private static $cache_expiry = 3600; // 1 hour
    
    public function __construct() {
        // Constructor for future use
    }
    
    /**
     * Get all translations for a specific language
     */
    public static function get_all_translations($language = 'en') {
        $cache_key = "cb_translations_all_{$language}";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $translations = CB_Database::get_translations(null, $language);
        
        // Cache the result
        set_transient($cache_key, $translations, self::$cache_expiry);
        
        return $translations;
    }
    
    /**
     * Get translations by category
     */
    public static function get_translations_by_category($category, $language = 'en') {
        $cache_key = "cb_translations_{$category}_{$language}";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $translations = CB_Database::get_translations($category, $language);
        
        // Cache the result
        set_transient($cache_key, $translations, self::$cache_expiry);
        
        return $translations;
    }
    
    /**
     * Get a single translation
     */
    public static function get_translation($string_key, $language = 'en', $category = 'general') {
        $cache_key = "cb_translation_{$string_key}_{$category}_{$language}";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $translation = CB_Database::get_translation($string_key, $language, $category);
        
        // Cache the result
        set_transient($cache_key, $translation, self::$cache_expiry);
        
        return $translation;
    }
    
    /**
     * Save or update a translation
     */
    public static function save_translation($data) {
        $result = CB_Database::save_translation($data);
        
        if ($result !== false) {
            // Clear related caches
            self::clear_translation_cache($data['string_key'], $data['category']);
        }
        
        return $result;
    }
    
    /**
     * Delete a translation
     */
    public static function delete_translation($translation_id) {
        // Get translation data before deletion for cache clearing
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        $translation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $translation_id));
        
        $result = CB_Database::delete_translation($translation_id);
        
        if ($result && $translation) {
            // Clear related caches
            self::clear_translation_cache($translation->string_key, $translation->category);
        }
        
        return $result;
    }
    
    /**
     * Get all translation categories
     */
    public static function get_categories() {
        $cache_key = "cb_translation_categories";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $categories = CB_Database::get_translation_categories();
        
        // Cache the result
        set_transient($cache_key, $categories, self::$cache_expiry);
        
        return $categories;
    }
    
    /**
     * Get translations for admin interface
     */
    public static function get_admin_translations($category = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        if ($category) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE is_active = 1 AND category = %s ORDER BY category, string_key",
                $category
            ));
        } else {
            $results = $wpdb->get_results("SELECT * FROM $table WHERE is_active = 1 ORDER BY category, string_key");
        }
        
        error_log('CB_Translations: get_admin_translations returned ' . count($results) . ' translations');
        
        return $results;
    }
    
    // Import/Export removed per product decision. All translations are managed in DB via admin UI.
    
    /**
     * Search translations
     */
    public static function search_translations($search_term, $language = 'en', $category = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $where = "WHERE is_active = 1";
        $language_field = $language === 'el' ? 'text_el' : 'text_en';
        
        if ($category) {
            $where .= $wpdb->prepare(" AND category = %s", $category);
        }
        
        $where .= $wpdb->prepare(" AND (string_key LIKE %s OR $language_field LIKE %s)", 
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        );
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY category, string_key");
    }
    
    /**
     * Get missing translations (where one language is empty)
     */
    public static function get_missing_translations($language = 'el') {
        global $wpdb;
        $table = $wpdb->prefix . 'cb_translations';
        
        $language_field = $language === 'el' ? 'text_el' : 'text_en';
        $other_field = $language === 'el' ? 'text_en' : 'text_el';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE is_active = 1 AND ($language_field = '' OR $language_field = $other_field) ORDER BY category, string_key",
            $language_field
        ));
    }
    
    /**
     * Bulk update translations
     */
  /**
 * Bulk update translations
 */
public static function bulk_update_translations($updates) {
    global $wpdb;
    $table = $wpdb->prefix . 'cb_translations';
    
    $updated = 0;
    foreach ($updates as $update) {
        // Only update if we have valid data
        if (empty($update['id']) || (!isset($update['text_en']) && !isset($update['text_el']))) {
            continue;
        }
        
        // Build update data dynamically
        $update_data = array();
        $format = array();
        
        if (isset($update['text_en'])) {
            $update_data['text_en'] = !empty($update['text_en']) ? sanitize_textarea_field($update['text_en']) : '';
            $format[] = '%s';
        }
        
        if (isset($update['text_el'])) {
            $update_data['text_el'] = !empty($update['text_el']) ? sanitize_textarea_field($update['text_el']) : '';
            $format[] = '%s';
        }
        
        // Only proceed if we have data to update
        if (!empty($update_data)) {
            $update_data['updated_at'] = current_time('mysql');
            $format[] = '%s';
            
            $result = $wpdb->update(
                $table,
                $update_data,
                array('id' => intval($update['id'])),
                $format,
                array('%d')
            );
            
            if ($result !== false) {
                $updated++;
            }
        }
    }
    
    // Clear all translation caches
    self::clear_all_translation_cache();
    
    return $updated;
}
    /**
     * Clear translation cache for specific key and category
     */
    private static function clear_translation_cache($string_key, $category) {
        $cache_keys = array(
            "cb_translations_all_en",
            "cb_translations_all_el",
            "cb_translations_{$category}_en",
            "cb_translations_{$category}_el",
            "cb_translation_{$string_key}_{$category}_en",
            "cb_translation_{$string_key}_{$category}_el"
        );
        
        foreach ($cache_keys as $cache_key) {
            delete_transient($cache_key);
        }
    }
    
    /**
     * Clear all translation caches
     */
    public static function clear_all_translation_cache() {
        global $wpdb;
        
        // Get all cache keys that start with cb_translations
        $cache_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_cb_translations%'"
        );
        
        foreach ($cache_keys as $cache_key) {
            $transient_name = str_replace('_transient_', '', $cache_key);
            delete_transient($transient_name);
        }
        
        // Also clear translation categories cache
        delete_transient('cb_translation_categories');
    }
    
    /**
     * Get translation statistics
     */
   /**
 * Get translation statistics
 */
public static function get_translation_stats() {
    global $wpdb;
    $table = $wpdb->prefix . 'cb_translations';
    
    $stats = array();
    
    // Total translations
    $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1");
    
    // By category
    $category_stats = $wpdb->get_results(
        "SELECT category, COUNT(*) as count FROM $table WHERE is_active = 1 GROUP BY category ORDER BY category"
    );
    
    $stats['by_category'] = array();
    foreach ($category_stats as $stat) {
        $stats['by_category'][$stat->category] = $stat->count;
    }
    
    // Missing translations - where text is empty OR same as the other language
    $stats['missing_en'] = $wpdb->get_var("
        SELECT COUNT(*) FROM $table 
        WHERE is_active = 1 
        AND (text_en = '' OR text_en IS NULL OR text_en = text_el)
    ");
    
    $stats['missing_el'] = $wpdb->get_var("
        SELECT COUNT(*) FROM $table 
        WHERE is_active = 1 
        AND (text_el = '' OR text_el IS NULL OR text_el = text_en)
    ");
    
    // Also count truly empty fields (not just same as other language)
    $stats['empty_en'] = $wpdb->get_var("
        SELECT COUNT(*) FROM $table 
        WHERE is_active = 1 
        AND (text_en = '' OR text_en IS NULL)
    ");
    
    $stats['empty_el'] = $wpdb->get_var("
        SELECT COUNT(*) FROM $table 
        WHERE is_active = 1 
        AND (text_el = '' OR text_el IS NULL)
    ");
    
    // Count where both languages are the same (potential duplicates)
    $stats['duplicate_content'] = $wpdb->get_var("
        SELECT COUNT(*) FROM $table 
        WHERE is_active = 1 
        AND text_en = text_el 
        AND text_en != '' 
        AND text_en IS NOT NULL
    ");
    
    return $stats;
}
}
