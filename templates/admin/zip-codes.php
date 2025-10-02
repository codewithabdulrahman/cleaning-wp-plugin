<?php
/**
 * ZIP Codes admin page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('ZIP Codes', 'cleaning-booking'); ?></h1>
    
    <div class="cb-admin-section">
        <h2><?php _e('Add New ZIP Code', 'cleaning-booking'); ?></h2>
        <form id="cb-zip-code-form" class="cb-form">
            <input type="hidden" name="id" id="zip-code-id">
            <input type="hidden" name="action" value="cb_save_zip_code">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cb_admin_nonce'); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="zip-code"><?php _e('ZIP Code', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="zip-code" name="zip_code" class="regular-text" required>
                        <span class="description"><?php _e('Postal/ZIP code', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zip-city"><?php _e('City', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="zip-city" name="city" class="regular-text">
                        <span class="description"><?php _e('City name (optional)', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zip-surcharge"><?php _e('Surcharge', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="zip-surcharge" name="surcharge" step="0.01" min="0" class="small-text" value="0">
                        <span class="description"><?php _e('Additional charge for this ZIP code', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Status', 'cleaning-booking'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="zip-is-active" name="is_active" checked>
                            <?php _e('Active', 'cleaning-booking'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save ZIP Code', 'cleaning-booking'); ?>">
                <button type="button" id="cb-cancel-edit-zip" class="button" style="display: none;"><?php _e('Cancel', 'cleaning-booking'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="cb-admin-section">
        <h2><?php _e('Existing ZIP Codes', 'cleaning-booking'); ?></h2>
        <div id="cb-zip-codes-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ZIP Code', 'cleaning-booking'); ?></th>
                        <th><?php _e('City', 'cleaning-booking'); ?></th>
                        <th><?php _e('Surcharge', 'cleaning-booking'); ?></th>
                        <th><?php _e('Status', 'cleaning-booking'); ?></th>
                        <th><?php _e('Actions', 'cleaning-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($zip_codes)): ?>
                        <tr>
                            <td colspan="5" class="text-center"><?php _e('No ZIP codes found', 'cleaning-booking'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($zip_codes as $zip_code): ?>
                            <tr data-zip-id="<?php echo esc_attr($zip_code->id); ?>">
                                <td>
                                    <strong><?php echo esc_html($zip_code->zip_code); ?></strong>
                                </td>
                                <td><?php echo esc_html($zip_code->city); ?></td>
                                <td>
                                    <?php if ($zip_code->surcharge > 0): ?>
                                        $<?php echo esc_html(number_format($zip_code->surcharge, 2)); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><?php _e('No surcharge', 'cleaning-booking'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="cb-status cb-status-<?php echo $zip_code->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $zip_code->is_active ? __('Active', 'cleaning-booking') : __('Inactive', 'cleaning-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small cb-edit-zip-code" data-zip='<?php echo json_encode($zip_code); ?>'>
                                        <?php _e('Edit', 'cleaning-booking'); ?>
                                    </button>
                                    <button type="button" class="button button-small cb-delete-zip-code" data-id="<?php echo esc_attr($zip_code->id); ?>">
                                        <?php _e('Delete', 'cleaning-booking'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

