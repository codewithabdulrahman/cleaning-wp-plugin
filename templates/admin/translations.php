<?php
/**
 * Enhanced Translations management admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get translations data
$translations = CB_Translations::get_admin_translations();
$categories = CB_Translations::get_categories();
$stats = CB_Translations::get_translation_stats();

// Get missing translations for better filtering
$missing_translations = CB_Translations::get_missing_translations('el');
?>

<div class="wrap">
    <h1><?php _e('Translation Management', 'cleaning-booking'); ?></h1>
    <p class="description"><?php _e('Manage all site strings and their translations in one place. Edit translations directly in the table below.', 'cleaning-booking'); ?></p>
    
    <!-- Statistics -->
    <div class="cb-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px;"><?php _e('Total Strings', 'cleaning-booking'); ?></h3>
            <div style="font-size: 28px; font-weight: bold; color: #0073aa;"><?php echo $stats['total']; ?></div>
        </div>
        
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px;"><?php _e('Translated', 'cleaning-booking'); ?></h3>
            <div style="font-size: 28px; font-weight: bold; color: #00a32a;"><?php echo ($stats['total'] - $stats['missing_el']); ?></div>
        </div>
        
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px;"><?php _e('Missing Greek', 'cleaning-booking'); ?></h3>
            <div style="font-size: 28px; font-weight: bold; color: #d63638;"><?php echo $stats['missing_el']; ?></div>
        </div>
        
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px;"><?php _e('Completion', 'cleaning-booking'); ?></h3>
            <div style="font-size: 28px; font-weight: bold; color: #0073aa;">
                <?php echo $stats['total'] > 0 ? round((($stats['total'] - $stats['missing_el']) / $stats['total']) * 100) : 0; ?>%
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="cb-action-buttons" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
        <button type="button" class="button button-primary" id="cb-add-translation">
            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Add New String', 'cleaning-booking'); ?>
        </button>
        
        <button type="button" class="button" id="cb-bulk-save" style="display: none;">
            <span class="dashicons dashicons-saved" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Save All Changes', 'cleaning-booking'); ?>
        </button>
        
        <button type="button" class="button" id="cb-cancel-bulk" style="display: none;">
            <span class="dashicons dashicons-no" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Cancel Changes', 'cleaning-booking'); ?>
        </button>
        
        <button type="button" class="button button-secondary" id="cb-clear-cache">
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Clear Cache', 'cleaning-booking'); ?>
        </button>
        
        <button type="button" class="button button-secondary" id="cb-remove-duplicates">
            <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Remove Duplicates', 'cleaning-booking'); ?>
        </button>
        
        <div id="cb-duplicate-status" style="margin-left: 15px; font-weight: bold; display: none;"></div>
        
        <div style="margin-left: auto;">
            <span class="cb-changes-indicator" style="color: #d63638; font-weight: bold; display: none;">
                <span class="dashicons dashicons-warning" style="vertical-align: middle; margin-right: 5px;"></span>
                <span class="cb-changes-count">0</span> <?php _e('unsaved changes', 'cleaning-booking'); ?>
            </span>
        </div>
    </div>
    
    <!-- Enhanced Filters -->
    <div class="cb-filters" style="background: #fff; padding: 20px; border: 1px solid #ddd; margin: 20px 0; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end;">
            <div>
                <label for="cb-category-filter" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Category', 'cleaning-booking'); ?></label>
                <select id="cb-category-filter" style="width: 100%;">
                    <option value=""><?php _e('All Categories', 'cleaning-booking'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(ucfirst($category)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="cb-status-filter" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Translation Status', 'cleaning-booking'); ?></label>
                <select id="cb-status-filter" style="width: 100%;">
                    <option value=""><?php _e('All', 'cleaning-booking'); ?></option>
                    <option value="translated"><?php _e('Translated', 'cleaning-booking'); ?></option>
                    <option value="untranslated"><?php _e('Untranslated', 'cleaning-booking'); ?></option>
                </select>
            </div>
            
            <div>
                <label for="cb-search-translations" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Search', 'cleaning-booking'); ?></label>
                <input type="text" id="cb-search-translations" placeholder="<?php _e('Search in English or Greek...', 'cleaning-booking'); ?>" style="width: 100%;">
            </div>
            
            <div>
                <button type="button" class="button" id="cb-clear-filters" style="width: 100%;">
                    <span class="dashicons dashicons-filter" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Clear Filters', 'cleaning-booking'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Translations Table -->
    <div class="cb-translations-container" style="background: #fff; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <div class="cb-table-header" style="background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #ddd;">
            <h3 style="margin: 0; font-size: 16px; color: #333;"><?php _e('Translation Strings', 'cleaning-booking'); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">
                <?php _e('Click on any text to edit it directly. Changes are saved automatically.', 'cleaning-booking'); ?>
            </p>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped" id="cb-translations-table" style="margin: 0;">
            <thead>
                    <tr style="background: #f1f1f1;">
                        <th style="width: 30px; padding: 12px 8px;">
                        <input type="checkbox" id="cb-select-all-translations">
                    </th>
                        <th style="width: 100px; padding: 12px 8px;"><?php _e('Category', 'cleaning-booking'); ?></th>
                        <th style="width: 45%; padding: 12px 8px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-translation" style="color: #0073aa;"></span>
                                <?php _e('English (Original)', 'cleaning-booking'); ?>
                            </div>
                        </th>
                        <th style="width: 45%; padding: 12px 8px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-translation" style="color: #d63638;"></span>
                                <?php _e('Greek (Translation)', 'cleaning-booking'); ?>
                            </div>
                        </th>
                        <th style="width: 80px; padding: 12px 8px;"><?php _e('Status', 'cleaning-booking'); ?></th>
                        <th style="width: 80px; padding: 12px 8px;"><?php _e('Actions', 'cleaning-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                    <?php foreach ($translations as $translation): 
                        $is_translated = !empty($translation->text_el) && $translation->text_el !== $translation->text_en;
                        $status_class = $is_translated ? 'translated' : 'untranslated';
                        $status_text = $is_translated ? __('Translated', 'cleaning-booking') : __('Untranslated', 'cleaning-booking');
                    ?>
                        <tr data-translation-id="<?php echo $translation->id; ?>" 
                            data-category="<?php echo esc_attr($translation->category); ?>"
                            data-status="<?php echo $status_class; ?>"
                            class="cb-translation-row">
                            <td style="padding: 12px 8px; vertical-align: top;">
                            <input type="checkbox" class="cb-translation-checkbox" value="<?php echo $translation->id; ?>">
                        </td>
                            <td style="padding: 12px 8px; vertical-align: top;">
                                <span class="cb-category-badge" style="background: #0073aa; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500;">
                                <?php echo esc_html(ucfirst($translation->category)); ?>
                            </span>
                        </td>
                            <td style="padding: 12px 8px; vertical-align: top;">
                                <div class="cb-translation-text cb-original-text" 
                                     data-field="text_en" 
                                     data-translation-id="<?php echo $translation->id; ?>"
                                     style="min-height: 40px; padding: 8px; border: 1px solid #e1e1e1; border-radius: 3px; background: #f9f9f9; cursor: pointer; transition: all 0.2s ease;">
                                <?php echo esc_html($translation->text_en); ?>
                            </div>
                        </td>
                            <td style="padding: 12px 8px; vertical-align: top;">
                                <div class="cb-translation-text cb-translation-text" 
                                     data-field="text_el" 
                                     data-translation-id="<?php echo $translation->id; ?>"
                                     style="min-height: 40px; padding: 8px; border: 1px solid #e1e1e1; border-radius: 3px; background: <?php echo $is_translated ? '#f0f8f0' : '#fff5f5'; ?>; cursor: pointer; transition: all 0.2s ease;"
                                     data-original-text="<?php echo esc_attr($translation->text_el); ?>">
                                    <?php if (empty($translation->text_el) || $translation->text_el === $translation->text_en): ?>
                                        <span style="color: #999; font-style: italic;"><?php _e('Click to add translation', 'cleaning-booking'); ?></span>
                                    <?php else: ?>
                                <?php echo esc_html($translation->text_el); ?>
                                    <?php endif; ?>
                            </div>
                        </td>
                            <td style="padding: 12px 8px; vertical-align: top; text-align: center;">
                                <span class="cb-status-badge cb-status-<?php echo $status_class; ?>" 
                                      style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; text-transform: uppercase;">
                                    <?php echo $status_text; ?>
                                </span>
                        </td>
                        <td style="padding: 12px 8px; vertical-align: top; text-align: center;">
                            <button type="button" class="button button-small cb-delete-translation" 
                                    data-translation-id="<?php echo $translation->id; ?>"
                                    data-translation-text="<?php echo esc_attr($translation->text_en); ?>"
                                    style="color: #d63638; border-color: #d63638; background: transparent; padding: 4px 8px; font-size: 11px;">
                                <span class="dashicons dashicons-trash" style="font-size: 12px; vertical-align: middle;"></span>
                                <?php _e('Delete', 'cleaning-booking'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <?php if (empty($translations)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #666;">
                <span class="dashicons dashicons-translation" style="font-size: 48px; color: #ddd; margin-bottom: 15px; display: block;"></span>
                <h3 style="margin: 0 0 10px 0;"><?php _e('No translations found', 'cleaning-booking'); ?></h3>
                <p style="margin: 0;"><?php _e('Add your first translation string to get started.', 'cleaning-booking'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Translation Modal -->
<div id="cb-translation-modal" class="cb-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="cb-modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 4px; width: 90%; max-width: 600px; max-height: 80%; overflow-y: auto;">
        <h2 id="cb-modal-title"><?php _e('Add New Translation', 'cleaning-booking'); ?></h2>
        
        <form id="cb-translation-form">
            <input type="hidden" id="cb-translation-id" name="translation_id">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cb-string-key"><?php _e('String Key', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cb-string-key" name="string_key" class="regular-text" required>
                        <p class="description"><?php _e('Unique identifier for this translation string.', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-category"><?php _e('Category', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <select id="cb-category" name="category" required>
                            <option value=""><?php _e('Select Category', 'cleaning-booking'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(ucfirst($category)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-text-en"><?php _e('English Text', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="cb-text-en" name="text_en" rows="3" class="large-text" required></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-text-el"><?php _e('Greek Text', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="cb-text-el" name="text_el" rows="3" class="large-text" required></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-context"><?php _e('Context', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cb-context" name="context" class="regular-text">
                        <p class="description"><?php _e('Optional context information for translators.', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-is-active"><?php _e('Active', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="cb-is-active" name="is_active" value="1" checked>
                        <label for="cb-is-active"><?php _e('Enable this translation', 'cleaning-booking'); ?></label>
                    </td>
                </tr>
            </table>
            
            <div class="cb-modal-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="button" id="cb-cancel-translation"><?php _e('Cancel', 'cleaning-booking'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Save Translation', 'cleaning-booking'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="cb-import-modal" class="cb-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="cb-modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 4px; width: 90%; max-width: 500px;">
        <h2><?php _e('Import Translations', 'cleaning-booking'); ?></h2>
        
        <form id="cb-import-form" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cb-import-language"><?php _e('Language', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <select id="cb-import-language" name="language" required>
                            <option value="en"><?php _e('English', 'cleaning-booking'); ?></option>
                            <option value="el"><?php _e('Greek', 'cleaning-booking'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-import-file"><?php _e('JSON File', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="file" id="cb-import-file" name="translation_file" accept=".json" required>
                        <p class="description"><?php _e('Select a JSON file containing translations.', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="cb-modal-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="button" id="cb-cancel-import"><?php _e('Cancel', 'cleaning-booking'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Import', 'cleaning-booking'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
/* Enhanced Translation Interface Styles */
.cb-translation-text:hover {
    border-color: #0073aa !important;
    background: #fff !important;
    box-shadow: 0 0 0 1px #0073aa !important;
}

.cb-translation-text.editing {
    border-color: #0073aa !important;
    background: #fff !important;
    box-shadow: 0 0 0 2px #0073aa !important;
}

.cb-status-translated {
    background: #00a32a !important;
    color: white !important;
}

.cb-status-untranslated {
    background: #d63638 !important;
    color: white !important;
}

.cb-changes-indicator {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.cb-translation-row.changed {
    background-color: #fff3cd !important;
}

.cb-translation-row.changed .cb-translation-text {
    border-color: #ffc107 !important;
}

.cb-save-indicator {
    position: fixed;
    top: 32px;
    right: 20px;
    background: #00a32a;
    color: white;
    padding: 10px 15px;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 10000;
    display: none;
}

.cb-error-indicator {
    position: fixed;
    top: 32px;
    right: 20px;
    background: #d63638;
    color: white;
    padding: 10px 15px;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 10000;
    display: none;
}
</style>

<div class="cb-save-indicator" id="cb-save-indicator">
    <span class="dashicons dashicons-yes" style="vertical-align: middle; margin-right: 5px;"></span>
    <span id="cb-save-message"><?php _e('Changes saved!', 'cleaning-booking'); ?></span>
</div>

<div class="cb-error-indicator" id="cb-error-indicator">
    <span class="dashicons dashicons-warning" style="vertical-align: middle; margin-right: 5px;"></span>
    <span id="cb-error-message"><?php _e('Error saving changes', 'cleaning-booking'); ?></span>
</div>

<script>
jQuery(document).ready(function($) {
    // Enhanced Translation Management
    var translationsData = <?php echo json_encode($translations); ?>;
    var pendingChanges = {};
    var changeCount = 0;
    
    // Initialize
    updateChangeIndicator();
    
    // Add new translation
    $('#cb-add-translation').on('click', function() {
        $('#cb-modal-title').text('<?php _e('Add New Translation String', 'cleaning-booking'); ?>');
        $('#cb-translation-form')[0].reset();
        $('#cb-translation-id').val('');
        $('#cb-translation-modal').show();
    });
    
    // Enhanced filter functionality
    function applyFilters() {
        var category = $('#cb-category-filter').val();
        var status = $('#cb-status-filter').val();
        var search = $('#cb-search-translations').val().toLowerCase();
        
        $('#cb-translations-table tbody tr').each(function() {
            var row = $(this);
            var rowCategory = row.data('category');
            var rowStatus = row.data('status');
            var rowText = row.text().toLowerCase();
            
            var categoryMatch = !category || rowCategory === category;
            var statusMatch = !status || rowStatus === status;
            var searchMatch = !search || rowText.includes(search);
            
            if (categoryMatch && statusMatch && searchMatch) {
                row.show();
            } else {
                row.hide();
            }
        });
        
        updateRowCount();
    }
    
    // Clear filters
    $('#cb-clear-filters').on('click', function() {
        $('#cb-category-filter').val('');
        $('#cb-status-filter').val('');
        $('#cb-search-translations').val('');
        applyFilters();
    });
    
    // Apply filters on change
    $('#cb-category-filter, #cb-status-filter').on('change', applyFilters);
    $('#cb-search-translations').on('keyup', function() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(applyFilters, 300);
    });
    
    // Enhanced inline editing
    $('.cb-translation-text').on('click', function() {
        if ($(this).hasClass('editing')) return;
        
        var $this = $(this);
        var currentText = $this.data('original-text') || $this.text().trim();
        var field = $this.data('field');
        var translationId = $this.data('translation-id');
        
        // Handle empty translation case
        if (field === 'text_el' && (currentText === '<?php _e('Click to add translation', 'cleaning-booking'); ?>' || !currentText)) {
            currentText = '';
        }
        
        $this.addClass('editing');
        $this.html('<textarea style="width: 100%; min-height: 60px; border: none; resize: vertical; font-family: inherit; font-size: inherit; padding: 0;">' + currentText + '</textarea>');
        
        var $textarea = $this.find('textarea');
        $textarea.focus().select();
        
        // Auto-resize textarea
        $textarea.on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(60, this.scrollHeight) + 'px';
        });
        
        $textarea.on('blur keydown', function(e) {
            if (e.type === 'keydown' && e.keyCode !== 13 && !(e.keyCode === 13 && e.ctrlKey)) return;
            
            var newText = $textarea.val().trim();
            var originalText = $this.data('original-text') || '';
            
            if (newText !== originalText) {
                // Mark as changed
                markAsChanged(translationId, field, newText);
                $this.closest('tr').addClass('changed');
                
                // Update display
                if (field === 'text_el') {
                    if (newText) {
                        $this.html(newText);
                        $this.css('background', '#f0f8f0');
                        $this.closest('tr').find('.cb-status-badge').removeClass('cb-status-untranslated').addClass('cb-status-translated').text('<?php _e('Translated', 'cleaning-booking'); ?>');
                        $this.closest('tr').attr('data-status', 'translated');
                    } else {
                        $this.html('<span style="color: #999; font-style: italic;"><?php _e('Click to add translation', 'cleaning-booking'); ?></span>');
                        $this.css('background', '#fff5f5');
                        $this.closest('tr').find('.cb-status-badge').removeClass('cb-status-translated').addClass('cb-status-untranslated').text('<?php _e('Untranslated', 'cleaning-booking'); ?>');
                        $this.closest('tr').attr('data-status', 'untranslated');
                    }
                } else {
                    $this.html(newText);
                }
                
                $this.data('original-text', newText);
            } else {
                // Restore original
                if (field === 'text_el' && !originalText) {
                    $this.html('<span style="color: #999; font-style: italic;"><?php _e('Click to add translation', 'cleaning-booking'); ?></span>');
                } else {
                    $this.html(originalText);
                }
            }
            
            $this.removeClass('editing');
        });
    });
    
    // Mark changes for bulk save
    function markAsChanged(translationId, field, value) {
        if (!pendingChanges[translationId]) {
            pendingChanges[translationId] = {};
        }
        pendingChanges[translationId][field] = value;
        changeCount++;
        updateChangeIndicator();
    }
    
    // Update change indicator
    function updateChangeIndicator() {
        if (changeCount > 0) {
            $('.cb-changes-indicator').show();
            $('.cb-changes-count').text(changeCount);
            $('#cb-bulk-save, #cb-cancel-bulk').show();
        } else {
            $('.cb-changes-indicator').hide();
            $('#cb-bulk-save, #cb-cancel-bulk').hide();
        }
    }
    
    // Bulk save
    $('#cb-bulk-save').on('click', function() {
        if (Object.keys(pendingChanges).length === 0) return;
        
        var $button = $(this);
        var originalText = $button.text();
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> <?php _e('Saving...', 'cleaning-booking'); ?>');
        
        var updates = [];
        $.each(pendingChanges, function(translationId, changes) {
            updates.push({
                id: translationId,
                text_en: changes.text_en || translationsData.find(t => t.id == translationId).text_en,
                text_el: changes.text_el || translationsData.find(t => t.id == translationId).text_el
            });
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cb_bulk_update_translations',
                updates: updates,
                nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Clear changes
                    pendingChanges = {};
                    changeCount = 0;
                    updateChangeIndicator();
                    
                    // Remove changed classes
                    $('.cb-translation-row.changed').removeClass('changed');
                    
                    // Show success message
                    showSaveIndicator(response.data.message);
                    
                    // Update statistics
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showErrorIndicator(response.data.message);
                }
            },
            error: function() {
                showErrorIndicator('<?php _e('Network error. Please try again.', 'cleaning-booking'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Cancel changes
    $('#cb-cancel-bulk').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to cancel all unsaved changes?', 'cleaning-booking'); ?>')) {
            location.reload();
        }
    });
    
    // Clear cache
    $('#cb-clear-cache').on('click', function() {
        if (confirm('<?php _e('This will clear all translation caches. Continue?', 'cleaning-booking'); ?>')) {
            var $button = $(this);
            var originalText = $button.text();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> <?php _e('Clearing...', 'cleaning-booking'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cb_clear_translation_cache',
                    nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showSaveIndicator('<?php _e('Translation cache cleared successfully!', 'cleaning-booking'); ?>');
                    } else {
                        showErrorIndicator('<?php _e('Error clearing cache: ', 'cleaning-booking'); ?>' + response.data.message);
                    }
                },
                error: function() {
                    showErrorIndicator('<?php _e('Network error. Please try again.', 'cleaning-booking'); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    });
    
    // Delete translation
    $(document).on('click', '.cb-delete-translation', function() {
        var $button = $(this);
        var translationId = $button.data('translation-id');
        var translationText = $button.data('translation-text');
        
        if (confirm('<?php _e('Are you sure you want to delete this translation?', 'cleaning-booking'); ?>\n\n"' + translationText + '"')) {
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> <?php _e('Deleting...', 'cleaning-booking'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cb_delete_translation',
                    translation_id: translationId,
                    nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row from table
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            updateRowCount();
                        });
                        showSaveIndicator('<?php _e('Translation deleted successfully!', 'cleaning-booking'); ?>');
                    } else {
                        showErrorIndicator('<?php _e('Error deleting translation: ', 'cleaning-booking'); ?>' + response.data.message);
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-trash" style="font-size: 12px; vertical-align: middle;"></span> <?php _e('Delete', 'cleaning-booking'); ?>');
                    }
                },
                error: function() {
                    showErrorIndicator('<?php _e('Network error. Please try again.', 'cleaning-booking'); ?>');
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-trash" style="font-size: 12px; vertical-align: middle;"></span> <?php _e('Delete', 'cleaning-booking'); ?>');
                }
            });
        }
    });
    
    // Show save indicator
    function showSaveIndicator(message) {
        $('#cb-save-message').text(message);
        $('#cb-save-indicator').fadeIn().delay(3000).fadeOut();
    }
    
    // Show error indicator
    function showErrorIndicator(message) {
        $('#cb-error-message').text(message);
        $('#cb-error-indicator').fadeIn().delay(5000).fadeOut();
    }
    
    // Update row count
    function updateRowCount() {
        var visibleRows = $('#cb-translations-table tbody tr:visible').length;
        var totalRows = $('#cb-translations-table tbody tr').length;
        
        if (visibleRows < totalRows) {
            $('.cb-table-header p').html('<?php _e('Click on any text to edit it directly. Changes are saved automatically.', 'cleaning-booking'); ?> <strong>(' + visibleRows + ' of ' + totalRows + ' <?php _e('strings shown', 'cleaning-booking'); ?>)</strong>');
        } else {
            $('.cb-table-header p').html('<?php _e('Click on any text to edit it directly. Changes are saved automatically.', 'cleaning-booking'); ?>');
        }
    }
    
    // Modal functionality (existing)
    $('#cb-translation-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'cb_save_translation');
        formData.append('nonce', '<?php echo wp_create_nonce('cb_admin_nonce'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showErrorIndicator(response.data.message);
                }
            }
        });
    });
    
    // Close modal
    $('#cb-cancel-translation, .cb-modal').on('click', function(e) {
        if (e.target === this) {
            $('#cb-translation-modal').hide();
        }
    });
    
    // Duplicate removal functionality
    $('#cb-remove-duplicates').on('click', function() {
        if (confirm('<?php _e('This will remove duplicate translations based on English text. This action cannot be undone. Continue?', 'cleaning-booking'); ?>')) {
            var $button = $(this);
            var originalText = $button.text();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> <?php _e('Removing Duplicates...', 'cleaning-booking'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cb_remove_duplicate_translations',
                    nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#cb-duplicate-status').text('<?php _e('Duplicates removed successfully! Removed ', 'cleaning-booking'); ?>' + response.data.removed_count + ' <?php _e('duplicate(s)', 'cleaning-booking'); ?>').css('color', '#00a32a').show();
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#cb-duplicate-status').text('<?php _e('Error removing duplicates: ', 'cleaning-booking'); ?>' + response.data.message).css('color', '#d63638').show();
                    }
                },
                error: function() {
                    $('#cb-duplicate-status').text('<?php _e('Network error. Please try again.', 'cleaning-booking'); ?>').css('color', '#d63638').show();
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    });
    
    // Initialize
    updateRowCount();
});

// CSS Animation for loading spinner
var style = document.createElement('style');
style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>

</script>
