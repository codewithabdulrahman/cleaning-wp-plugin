<?php
/**
 * Extras admin page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Extras', 'cleaning-booking'); ?></h1>
    
    <div class="cb-admin-section">
        <h2><?php _e('Add New Extra', 'cleaning-booking'); ?></h2>
        <form id="cb-extra-form" class="cb-form">
            <input type="hidden" name="id" id="extra-id">
            <input type="hidden" name="action" value="cb_save_extra">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cb_admin_nonce'); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="extra-name"><?php _e('Extra Name', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="extra-name" name="name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="extra-description"><?php _e('Description', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="extra-description" name="description" rows="3" class="large-text"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="extra-price"><?php _e('Price', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="extra-price" name="price" step="0.01" min="0" class="small-text" required>
                        <span class="description"><?php _e('Price in USD', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="extra-duration"><?php _e('Duration', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="extra-duration" name="duration" min="0" class="small-text" required>
                        <span class="description"><?php _e('Additional duration in minutes', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="extra-sort-order"><?php _e('Sort Order', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="extra-sort-order" name="sort_order" min="0" class="small-text" value="0">
                        <span class="description"><?php _e('Lower numbers appear first', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Status', 'cleaning-booking'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="extra-is-active" name="is_active" checked>
                            <?php _e('Active', 'cleaning-booking'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Extra', 'cleaning-booking'); ?>">
                <button type="button" id="cb-cancel-edit-extra" class="button" style="display: none;"><?php _e('Cancel', 'cleaning-booking'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="cb-admin-section">
        <h2><?php _e('Existing Extras', 'cleaning-booking'); ?></h2>
        <div id="cb-extras-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'cleaning-booking'); ?></th>
                        <th><?php _e('Price', 'cleaning-booking'); ?></th>
                        <th><?php _e('Duration', 'cleaning-booking'); ?></th>
                        <th><?php _e('Status', 'cleaning-booking'); ?></th>
                        <th><?php _e('Actions', 'cleaning-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($extras)): ?>
                        <tr>
                            <td colspan="5" class="text-center"><?php _e('No extras found', 'cleaning-booking'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($extras as $extra): ?>
                            <tr data-extra-id="<?php echo esc_attr($extra->id); ?>">
                                <td>
                                    <strong><?php echo esc_html($extra->name); ?></strong>
                                    <?php if ($extra->description): ?>
                                        <br><small><?php echo esc_html($extra->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo esc_html(number_format($extra->price, 2)); ?></td>
                                <td><?php echo esc_html($extra->duration); ?> min</td>
                                <td>
                                    <span class="cb-status cb-status-<?php echo $extra->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $extra->is_active ? __('Active', 'cleaning-booking') : __('Inactive', 'cleaning-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small cb-edit-extra" data-extra='<?php echo json_encode($extra); ?>'>
                                        <?php _e('Edit', 'cleaning-booking'); ?>
                                    </button>
                                    <button type="button" class="button button-small cb-delete-extra" data-id="<?php echo esc_attr($extra->id); ?>">
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

