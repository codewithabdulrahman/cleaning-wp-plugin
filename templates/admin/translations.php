<?php
/**
 * Translations management admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get translations data
$translations = CB_Translations::get_admin_translations();
$categories = CB_Translations::get_categories();
$stats = CB_Translations::get_translation_stats();

// Import/Export removed. No form actions handled here.
?>

<div class="wrap">
    <h1><?php _e('Translations Management', 'cleaning-booking'); ?></h1>
    
    <!-- Statistics -->
    <div class="cb-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Total Translations', 'cleaning-booking'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo $stats['total']; ?></div>
        </div>
        
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Missing English', 'cleaning-booking'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo $stats['missing_en']; ?></div>
        </div>
        
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Missing Greek', 'cleaning-booking'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo $stats['missing_el']; ?></div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="cb-action-buttons" style="margin: 20px 0;">
        <button type="button" class="button button-primary" id="cb-add-translation">
            <?php _e('Add New Translation', 'cleaning-booking'); ?>
        </button>
        
        <!-- Import/Export removed -->
        
        <button type="button" class="button" id="cb-bulk-edit">
            <?php _e('Bulk Edit', 'cleaning-booking'); ?>
        </button>
    </div>
    
    <!-- Filters -->
    <div class="cb-filters" style="background: #fff; padding: 15px; border: 1px solid #ddd; margin: 20px 0;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div>
                <label for="cb-category-filter"><?php _e('Category:', 'cleaning-booking'); ?></label>
                <select id="cb-category-filter" style="margin-left: 5px;">
                    <option value=""><?php _e('All Categories', 'cleaning-booking'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(ucfirst($category)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="cb-search-translations"><?php _e('Search:', 'cleaning-booking'); ?></label>
                <input type="text" id="cb-search-translations" placeholder="<?php _e('Search translations...', 'cleaning-booking'); ?>" style="margin-left: 5px; width: 200px;">
            </div>
            
            <div>
                <label for="cb-language-filter"><?php _e('Language:', 'cleaning-booking'); ?></label>
                <select id="cb-language-filter" style="margin-left: 5px;">
                    <option value="en"><?php _e('English', 'cleaning-booking'); ?></option>
                    <option value="el"><?php _e('Greek', 'cleaning-booking'); ?></option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Translations Table -->
    <div class="cb-translations-container" style="background: #fff; border: 1px solid #ddd;">
        <table class="wp-list-table widefat fixed striped" id="cb-translations-table">
            <thead>
                <tr>
                    <th style="width: 20px;">
                        <input type="checkbox" id="cb-select-all-translations">
                    </th>
                    <th style="width: 200px;"><?php _e('String Key', 'cleaning-booking'); ?></th>
                    <th style="width: 100px;"><?php _e('Category', 'cleaning-booking'); ?></th>
                    <th><?php _e('English Text', 'cleaning-booking'); ?></th>
                    <th><?php _e('Greek Text', 'cleaning-booking'); ?></th>
                    <th style="width: 100px;"><?php _e('Actions', 'cleaning-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($translations as $translation): ?>
                    <tr data-translation-id="<?php echo $translation->id; ?>" data-category="<?php echo esc_attr($translation->category); ?>">
                        <td>
                            <input type="checkbox" class="cb-translation-checkbox" value="<?php echo $translation->id; ?>">
                        </td>
                        <td>
                            <strong><?php echo esc_html($translation->string_key); ?></strong>
                            <?php if ($translation->context): ?>
                                <br><small style="color: #666;"><?php echo esc_html($translation->context); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cb-category-badge" style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                                <?php echo esc_html(ucfirst($translation->category)); ?>
                            </span>
                        </td>
                        <td>
                            <div class="cb-translation-text" data-field="text_en" data-translation-id="<?php echo $translation->id; ?>">
                                <?php echo esc_html($translation->text_en); ?>
                            </div>
                        </td>
                        <td>
                            <div class="cb-translation-text" data-field="text_el" data-translation-id="<?php echo $translation->id; ?>">
                                <?php echo esc_html($translation->text_el); ?>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="button button-small cb-edit-translation" data-translation-id="<?php echo $translation->id; ?>">
                                <?php _e('Edit', 'cleaning-booking'); ?>
                            </button>
                            <button type="button" class="button button-small button-link-delete cb-delete-translation" data-translation-id="<?php echo $translation->id; ?>">
                                <?php _e('Delete', 'cleaning-booking'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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

<script>
jQuery(document).ready(function($) {
    // Translation management functionality
    var translationsData = <?php echo json_encode($translations); ?>;
    
    // Add new translation
    $('#cb-add-translation').on('click', function() {
        $('#cb-modal-title').text('<?php _e('Add New Translation', 'cleaning-booking'); ?>');
        $('#cb-translation-form')[0].reset();
        $('#cb-translation-id').val('');
        $('#cb-translation-modal').show();
    });
    
    // Edit translation
    $('.cb-edit-translation').on('click', function() {
        var translationId = $(this).data('translation-id');
        var translation = translationsData.find(t => t.id == translationId);
        
        if (translation) {
            $('#cb-modal-title').text('<?php _e('Edit Translation', 'cleaning-booking'); ?>');
            $('#cb-translation-id').val(translation.id);
            $('#cb-string-key').val(translation.string_key);
            $('#cb-category').val(translation.category);
            $('#cb-text-en').val(translation.text_en);
            $('#cb-text-el').val(translation.text_el);
            $('#cb-context').val(translation.context);
            $('#cb-is-active').prop('checked', translation.is_active == 1);
            $('#cb-translation-modal').show();
        }
    });
    
    // Save translation
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
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Delete translation
    $('.cb-delete-translation').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to delete this translation?', 'cleaning-booking'); ?>')) {
            var translationId = $(this).data('translation-id');
            
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
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
    
    // Import/Export removed
    
    // Close modals
    $('.cb-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    $('#cb-cancel-translation, #cb-cancel-import').on('click', function() {
        $(this).closest('.cb-modal').hide();
    });
    
    // Filter functionality
    $('#cb-category-filter, #cb-search-translations').on('change keyup', function() {
        var category = $('#cb-category-filter').val();
        var search = $('#cb-search-translations').val().toLowerCase();
        
        $('#cb-translations-table tbody tr').each(function() {
            var row = $(this);
            var rowCategory = row.data('category');
            var rowText = row.text().toLowerCase();
            
            var categoryMatch = !category || rowCategory === category;
            var searchMatch = !search || rowText.includes(search);
            
            if (categoryMatch && searchMatch) {
                row.show();
            } else {
                row.hide();
            }
        });
    });
    
    // Inline editing
    $('.cb-translation-text').on('click', function() {
        var $this = $(this);
        var currentText = $this.text();
        var field = $this.data('field');
        var translationId = $this.data('translation-id');
        
        $this.html('<input type="text" value="' + currentText + '" style="width: 100%;">');
        var $input = $this.find('input');
        $input.focus().select();
        
        $input.on('blur keypress', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            
            var newText = $input.val();
            if (newText !== currentText) {
                // Save the change
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cb_save_translation',
                        translation_id: translationId,
                        [field]: newText,
                        nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $this.text(newText);
                        } else {
                            $this.text(currentText);
                            alert(response.data.message);
                        }
                    }
                });
            } else {
                $this.text(currentText);
            }
        });
    });
});
</script>
