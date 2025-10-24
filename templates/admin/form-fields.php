<?php
/**
 * Form Fields management admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get form fields data
$fields = CB_Form_Fields::get_admin_fields();
$field_types = CB_Form_Fields::get_field_types();
$validation_rules = CB_Form_Fields::get_validation_rules();
$stats = CB_Form_Fields::get_field_stats();

// Import/Export/Reset functionality removed
?>

<div class="wrap">
    <h1><?php _e('Form Fields Management', 'cleaning-booking'); ?></h1>
    
    <!-- Statistics -->
    <div class="cb-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Total Fields', 'cleaning-booking'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo $stats['total']; ?></div>
        </div>
        
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Visible Fields', 'cleaning-booking'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo $stats['visible']; ?></div>
        </div>
        
        <div class="cb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Required Fields', 'cleaning-booking'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo $stats['required']; ?></div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="cb-action-buttons" style="margin: 20px 0;">
        <button type="button" class="button button-primary" id="cb-add-field">
            <?php _e('Add New Field', 'cleaning-booking'); ?>
        </button>
    </div>
    
    <!-- Fields Table -->
    <div class="cb-fields-container" style="background: #fff; border: 1px solid #ddd;">
        <table class="wp-list-table widefat fixed striped" id="cb-fields-table">
            <thead>
                <tr>
                    <th style="width: 20px;">
                        <input type="checkbox" id="cb-select-all-fields">
                    </th>
                    <th style="width: 30px;"><?php _e('Order', 'cleaning-booking'); ?></th>
                    <th style="width: 150px;"><?php _e('Field Key', 'cleaning-booking'); ?></th>
                    <th style="width: 100px;"><?php _e('Type', 'cleaning-booking'); ?></th>
                    <th><?php _e('English Label', 'cleaning-booking'); ?></th>
                    <th style="width: 80px;"><?php _e('Required', 'cleaning-booking'); ?></th>
                    <th style="width: 80px;"><?php _e('Visible', 'cleaning-booking'); ?></th>
                    <th style="width: 120px;"><?php _e('Actions', 'cleaning-booking'); ?></th>
                </tr>
            </thead>
            <tbody id="cb-sortable-fields">
                <?php foreach ($fields as $field): ?>
                    <tr data-field-id="<?php echo $field->id; ?>" data-sort-order="<?php echo $field->sort_order; ?>">
                        <td>
                            <input type="checkbox" class="cb-field-checkbox" value="<?php echo $field->id; ?>">
                        </td>
                        <td>
                            <span class="cb-drag-handle" style="cursor: move; color: #666;">⋮⋮</span>
                        </td>
                        <td>
                            <strong><?php echo esc_html($field->field_key); ?></strong>
                        </td>
                        <td>
                            <span class="cb-field-type-badge" style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                                <?php echo esc_html(ucfirst($field->field_type)); ?>
                            </span>
                        </td>
                        <td>
                            <div class="cb-field-label" data-field="label_en" data-field-id="<?php echo $field->id; ?>">
                                <?php echo esc_html($field->label_en); ?>
                            </div>
                        </td>
                        <td>
                            <span class="cb-status-badge <?php echo $field->is_required ? 'required' : 'optional'; ?>" style="padding: 2px 8px; border-radius: 3px; font-size: 11px; <?php echo $field->is_required ? 'background: #d63638; color: white;' : 'background: #00a32a; color: white;'; ?>">
                                <?php echo $field->is_required ? __('Required', 'cleaning-booking') : __('Optional', 'cleaning-booking'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="cb-status-badge <?php echo $field->is_visible ? 'visible' : 'hidden'; ?>" style="padding: 2px 8px; border-radius: 3px; font-size: 11px; <?php echo $field->is_visible ? 'background: #00a32a; color: white;' : 'background: #666; color: white;'; ?>">
                                <?php echo $field->is_visible ? __('Visible', 'cleaning-booking') : __('Hidden', 'cleaning-booking'); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button button-small cb-edit-field" data-field-id="<?php echo $field->id; ?>">
                                <?php _e('Edit', 'cleaning-booking'); ?>
                            </button>
                            <button type="button" class="button button-small button-link-delete cb-delete-field" data-field-id="<?php echo $field->id; ?>">
                                <?php _e('Delete', 'cleaning-booking'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Field Modal -->
<div id="cb-field-modal" class="cb-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="cb-modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 4px; width: 90%; max-width: 700px; max-height: 80%; overflow-y: auto;">
        <h2 id="cb-field-modal-title"><?php _e('Add New Field', 'cleaning-booking'); ?></h2>
        
        <form id="cb-field-form">
            <input type="hidden" id="cb-field-id" name="field_id">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cb-field-key"><?php _e('Field Key', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cb-field-key" name="field_key" class="regular-text" required>
                        <p class="description"><?php _e('Unique identifier for this field (lowercase, underscores only).', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-field-type"><?php _e('Field Type', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <select id="cb-field-type" name="field_type" required>
                            <option value=""><?php _e('Select Field Type', 'cleaning-booking'); ?></option>
                            <?php foreach ($field_types as $type => $info): ?>
                                <option value="<?php echo esc_attr($type); ?>" data-supports-placeholder="<?php echo $info['supports_placeholder'] ? 'true' : 'false'; ?>" data-supports-validation="<?php echo $info['supports_validation'] ? 'true' : 'false'; ?>">
                                    <?php echo esc_html($info['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" id="cb-field-type-description"></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-label-en"><?php _e('English Label', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cb-label-en" name="label_en" class="regular-text" required>
                    </td>
                </tr>
                
                
                <tr id="cb-placeholder-row">
                    <th scope="row">
                        <label for="cb-placeholder-en"><?php _e('English Placeholder', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cb-placeholder-en" name="placeholder_en" class="regular-text">
                    </td>
                </tr>
                
                <tr id="cb-placeholder-el-row">
                    <th scope="row">
                        <label for="cb-placeholder-el"><?php _e('Greek Placeholder', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cb-placeholder-el" name="placeholder_el" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cb-sort-order"><?php _e('Sort Order', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="cb-sort-order" name="sort_order" class="small-text" min="0" value="0">
                        <p class="description"><?php _e('Lower numbers appear first.', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Field Options', 'cleaning-booking'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cb-is-required" name="is_required" value="1">
                            <?php _e('Required field', 'cleaning-booking'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" id="cb-is-visible" name="is_visible" value="1" checked>
                            <?php _e('Visible field', 'cleaning-booking'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr id="cb-validation-row">
                    <th scope="row"><?php _e('Validation Rules', 'cleaning-booking'); ?></th>
                    <td>
                        <div id="cb-validation-rules">
                            <!-- Validation rules will be populated by JavaScript -->
                        </div>
                    </td>
                </tr>
            </table>
            
            <div class="cb-modal-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="button" id="cb-cancel-field"><?php _e('Cancel', 'cleaning-booking'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Save Field', 'cleaning-booking'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Import/Export/Reset functionality removed -->

<script>
jQuery(document).ready(function($) {
    var fieldsData = <?php echo json_encode($fields); ?>;
    var fieldTypes = <?php echo json_encode($field_types); ?>;
    var validationRules = <?php echo json_encode($validation_rules); ?>;
    
    // Make fields sortable
    $('#cb-sortable-fields').sortable({
        handle: '.cb-drag-handle',
        update: function(event, ui) {
            var fieldOrders = {};
            $('#cb-sortable-fields tr').each(function(index) {
                var fieldId = $(this).data('field-id');
                fieldOrders[fieldId] = index;
            });
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cb_update_field_order',
                    field_orders: fieldOrders,
                    nonce: '<?php echo wp_create_nonce('cb_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Update sort order values
                        $('#cb-sortable-fields tr').each(function(index) {
                            $(this).data('sort-order', index);
                        });
                    }
                }
            });
        }
    });
    
    // Add new field
    $('#cb-add-field').on('click', function() {
        $('#cb-field-modal-title').text('<?php _e('Add New Field', 'cleaning-booking'); ?>');
        $('#cb-field-form')[0].reset();
        $('#cb-field-id').val('');
        $('#cb-is-visible').prop('checked', true);
        updateFieldTypeDescription();
        $('#cb-field-modal').show();
    });
    
    // Edit field
    $('.cb-edit-field').on('click', function() {
        var fieldId = $(this).data('field-id');
        var field = fieldsData.find(f => f.id == fieldId);
        
        if (field) {
            $('#cb-field-modal-title').text('<?php _e('Edit Field', 'cleaning-booking'); ?>');
            $('#cb-field-id').val(field.id);
            $('#cb-field-key').val(field.field_key);
            $('#cb-field-type').val(field.field_type);
            $('#cb-label-en').val(field.label_en);
            $('#cb-placeholder-en').val(field.placeholder_en);
            $('#cb-placeholder-el').val(field.placeholder_el);
            $('#cb-sort-order').val(field.sort_order);
            $('#cb-is-required').prop('checked', field.is_required == 1);
            $('#cb-is-visible').prop('checked', field.is_visible == 1);
            
            updateFieldTypeDescription();
            updateValidationRules();
            $('#cb-field-modal').show();
        }
    });
    
    // Field type change
    $('#cb-field-type').on('change', function() {
        updateFieldTypeDescription();
        updatePlaceholderVisibility();
        updateValidationRules();
    });
    
    function updateFieldTypeDescription() {
        var selectedType = $('#cb-field-type').val();
        if (selectedType && fieldTypes[selectedType]) {
            $('#cb-field-type-description').text(fieldTypes[selectedType].description);
        } else {
            $('#cb-field-type-description').text('');
        }
    }
    
    function updatePlaceholderVisibility() {
        var selectedType = $('#cb-field-type').val();
        var supportsPlaceholder = selectedType && fieldTypes[selectedType] && fieldTypes[selectedType].supports_placeholder;
        
        if (supportsPlaceholder) {
            $('#cb-placeholder-row, #cb-placeholder-el-row').show();
        } else {
            $('#cb-placeholder-row, #cb-placeholder-el-row').hide();
        }
    }
    
    function updateValidationRules() {
        var selectedType = $('#cb-field-type').val();
        var supportsValidation = selectedType && fieldTypes[selectedType] && fieldTypes[selectedType].supports_validation;
        
        if (supportsValidation) {
            $('#cb-validation-row').show();
            var rulesHtml = '';
            
            for (var rule in validationRules) {
                var ruleInfo = validationRules[rule];
                rulesHtml += '<label style="display: block; margin-bottom: 5px;">';
                
                if (ruleInfo.type === 'checkbox') {
                    rulesHtml += '<input type="checkbox" name="validation_rules[' + rule + ']" value="1"> ';
                } else {
                    rulesHtml += '<input type="' + ruleInfo.type + '" name="validation_rules[' + rule + ']" style="margin-right: 5px;"> ';
                }
                
                rulesHtml += ruleInfo.label + '<br><small style="color: #666;">' + ruleInfo.description + '</small>';
                rulesHtml += '</label>';
            }
            
            $('#cb-validation-rules').html(rulesHtml);
        } else {
            $('#cb-validation-row').hide();
        }
    }
    
    // Save field
    $('#cb-field-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'cb_save_form_field');
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
    
    // Delete and duplicate handlers moved to admin.js (vanilla JS)
    
    // Import/Export/Reset handlers removed
    
    // Modal handlers moved to admin.js (vanilla JS)
    
    // Inline editing
    $('.cb-field-label').on('click', function() {
        var $this = $(this);
        var currentText = $this.text();
        var field = $this.data('field');
        var fieldId = $this.data('field-id');
        
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
                        action: 'cb_save_form_field',
                        field_id: fieldId,
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
    
    // Initialize field type description
    updateFieldTypeDescription();
    updatePlaceholderVisibility();
    updateValidationRules();
});
</script>
