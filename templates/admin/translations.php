<?php
/**
 * Translations management admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get translations data
$translations = CB_Translations::get_admin_translations();
$stats = CB_Translations::get_translation_stats();

// Get current filter status
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

// Filter translations based on status
if ($current_status === 'translated') {
    $translations = array_filter($translations, function($translation) {
        return !empty($translation->text_en) && !empty($translation->text_el);
    });
} elseif ($current_status === 'untranslated') {
    $translations = array_filter($translations, function($translation) {
        return empty($translation->text_en) || empty($translation->text_el);
    });
}

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
        
        <button type="button" class="button button-primary" id="cb-save-all-translations" style="display: none;">
            <?php _e('Save All Changes', 'cleaning-booking'); ?>
        </button>
    </div>
    
    <!-- Filters -->
    <div class="cb-filters" style="background: #fff; padding: 15px; border: 1px solid #ddd; margin: 20px 0;">
        <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
            <div>
                <label for="cb-search-translations"><?php _e('Search:', 'cleaning-booking'); ?></label>
                <input type="text" id="cb-search-translations" placeholder="<?php _e('Search translations...', 'cleaning-booking'); ?>" style="margin-left: 5px; width: 300px;">
            </div>
            
            <div>
                <label for="cb-status-filter"><?php _e('Status:', 'cleaning-booking'); ?></label>
                <select id="cb-status-filter" style="margin-left: 5px;">
                    <option value="all" <?php selected($current_status, 'all'); ?>><?php _e('All Translations', 'cleaning-booking'); ?></option>
                    <option value="translated" <?php selected($current_status, 'translated'); ?>><?php _e('Fully Translated', 'cleaning-booking'); ?></option>
                    <option value="untranslated" <?php selected($current_status, 'untranslated'); ?>><?php _e('Untranslated', 'cleaning-booking'); ?></option>
                </select>
            </div>
            
            <div>
                <a href="<?php echo admin_url('admin.php?page=cleaning-booking-translations'); ?>" class="button button-secondary"><?php _e('Reset Filters', 'cleaning-booking'); ?></a>
            </div>
        </div>
    </div>
    
    <!-- Translations Table -->
    <div class="cb-translations-container" style="background: #fff; border: 1px solid #ddd;">
        <table class="wp-list-table widefat fixed striped" id="cb-translations-table">
            <thead>
                <tr>
                    <th style="width: 200px;"><?php _e('String Key', 'cleaning-booking'); ?></th>
                    <th><?php _e('English Text', 'cleaning-booking'); ?></th>
                    <th><?php _e('Greek Text', 'cleaning-booking'); ?></th>
                    <th style="width: 120px;"><?php _e('Status', 'cleaning-booking'); ?></th>
                    <th style="width: 100px;"><?php _e('Actions', 'cleaning-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($translations as $translation): 
                    $is_translated = !empty($translation->text_en) && !empty($translation->text_el);
                    $status_class = $is_translated ? 'cb-status-translated' : 'cb-status-untranslated';
                    $status_text = $is_translated ? __('Translated', 'cleaning-booking') : __('Untranslated', 'cleaning-booking');
                ?>
                    <tr data-translation-id="<?php echo $translation->id; ?>">
                        <td>
                            <strong><?php echo esc_html($translation->string_key); ?></strong>
                            <?php if ($translation->context): ?>
                                <br><small style="color: #666;"><?php echo esc_html($translation->context); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="text" 
                                   class="cb-translation-input regular-text" 
                                   data-field="text_en" 
                                   data-original-value="<?php echo esc_attr($translation->text_en); ?>"
                                   value="<?php echo esc_attr($translation->text_en); ?>"
                                   data-translation-id="<?php echo $translation->id; ?>"
                                   placeholder="<?php _e('Enter English text...', 'cleaning-booking'); ?>">
                            <?php if (empty($translation->text_en)): ?>
                                <div class="cb-field-warning" style="color: #d63638; font-size: 12px; margin-top: 5px;">
                                    <?php _e('Missing English translation', 'cleaning-booking'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="text" 
                                   class="cb-translation-input regular-text" 
                                   data-field="text_el" 
                                   data-original-value="<?php echo esc_attr($translation->text_el); ?>"
                                   value="<?php echo esc_attr($translation->text_el); ?>"
                                   data-translation-id="<?php echo $translation->id; ?>"
                                   placeholder="<?php _e('Enter Greek text...', 'cleaning-booking'); ?>">
                            <?php if (empty($translation->text_el)): ?>
                                <div class="cb-field-warning" style="color: #d63638; font-size: 12px; margin-top: 5px;">
                                    <?php _e('Missing Greek translation', 'cleaning-booking'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cb-status-badge <?php echo $status_class; ?>" style="padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; display: inline-block;">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td>
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

<!-- Add Translation Modal -->
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
            </table>
            
            <div class="cb-modal-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="button" id="cb-cancel-translation"><?php _e('Cancel', 'cleaning-booking'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Save Translation', 'cleaning-booking'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.cb-status-translated {
    background-color: #d1f7c4;
    color: #0e5c0e;
}

.cb-status-untranslated {
    background-color: #ffd7d7;
    color: #8a1a1a;
}

.cb-field-warning {
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Track changes for bulk save
    var changes = {};
    var saveButton = $('#cb-save-all-translations');
    
    // Add new translation
    $('#cb-add-translation').on('click', function() {
        $('#cb-modal-title').text('<?php _e('Add New Translation', 'cleaning-booking'); ?>');
        $('#cb-translation-form')[0].reset();
        $('#cb-translation-id').val('');
        $('#cb-translation-modal').show();
    });
    
    // Save translation (for new entries)
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
    
    // Track input changes for bulk save - FIXED VERSION
    $('.cb-translation-input').on('input', function() {
        var $this = $(this);
        var translationId = $this.data('translation-id');
        var field = $this.data('field');
        var newValue = $this.val();
        
        // Get the current values for both fields
        var row = $this.closest('tr');
        var textEn = row.find('[data-field="text_en"]').val();
        var textEl = row.find('[data-field="text_el"]').val();
        
        // Get original values for comparison
        var originalEn = row.find('[data-field="text_en"]').data('original-value');
        var originalEl = row.find('[data-field="text_el"]').data('original-value');
        
        // Check if any value in this row has changed
        var hasChanges = (textEn !== originalEn) || (textEl !== originalEl);
        
        if (hasChanges) {
            // Add to changes object with BOTH current values
            changes[translationId] = {
                id: translationId,
                text_en: textEn,
                text_el: textEl
            };
            
            // Show save button
            saveButton.show();
        } else {
            // Remove from changes if no changes in this row
            delete changes[translationId];
            
            // Hide save button if no changes
            if (Object.keys(changes).length === 0) {
                saveButton.hide();
            }
        }
    });
    
    // Save all changes - FIXED VERSION
    saveButton.on('click', function() {
        if (Object.keys(changes).length === 0) {
            alert('<?php _e('No changes to save.', 'cleaning-booking'); ?>');
            return;
        }
        
        // Convert changes object to array
        var updates = Object.values(changes);
        
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
                    // Update original values for ALL fields in changed rows
                    Object.keys(changes).forEach(function(translationId) {
                        var row = $('tr[data-translation-id="' + translationId + '"]');
                        var change = changes[translationId];
                        
                        row.find('[data-field="text_en"]').data('original-value', change.text_en);
                        row.find('[data-field="text_el"]').data('original-value', change.text_el);
                    });
                    
                    changes = {};
                    saveButton.hide();
                    alert('<?php _e('All changes saved successfully!', 'cleaning-booking'); ?>');
                    
                    // Update status badges after save
                    updateStatusBadges();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Search functionality
    $('#cb-search-translations').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        
        $('#cb-translations-table tbody tr').each(function() {
            var row = $(this);
            var rowText = row.text().toLowerCase();
            
            if (!search || rowText.includes(search)) {
                row.show();
            } else {
                row.hide();
            }
        });
    });
    
    // Status filter functionality
    $('#cb-status-filter').on('change', function() {
        var status = $(this).val();
        var currentUrl = new URL(window.location.href);
        
        if (status === 'all') {
            currentUrl.searchParams.delete('status');
        } else {
            currentUrl.searchParams.set('status', status);
        }
        
        window.location.href = currentUrl.toString();
    });
    
    // Function to update status badges
    function updateStatusBadges() {
        $('#cb-translations-table tbody tr').each(function() {
            var row = $(this);
            var textEn = row.find('[data-field="text_en"]').val();
            var textEl = row.find('[data-field="text_el"]').val();
            var isTranslated = textEn && textEl;
            
            var statusBadge = row.find('.cb-status-badge');
            statusBadge.removeClass('cb-status-translated cb-status-untranslated');
            
            if (isTranslated) {
                statusBadge.addClass('cb-status-translated').text('<?php _e('Translated', 'cleaning-booking'); ?>');
            } else {
                statusBadge.addClass('cb-status-untranslated').text('<?php _e('Untranslated', 'cleaning-booking'); ?>');
            }
        });
    }
    
    // Update status badges on page load
    updateStatusBadges();
    
    // Modal handlers
    $('#cb-cancel-translation').on('click', function() {
        $('#cb-translation-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('cb-modal')) {
            $(e.target).hide();
        }
    });
});
</script>