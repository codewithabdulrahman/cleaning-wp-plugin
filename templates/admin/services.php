<?php
/**
 * Services admin page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Services', 'cleaning-booking'); ?></h1>
    
    <div class="cb-admin-section">
        <h2><?php _e('Add New Service', 'cleaning-booking'); ?></h2>
        <form id="cb-service-form" class="cb-form">
            <input type="hidden" name="id" id="service-id">
            <input type="hidden" name="action" value="cb_save_service">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cb_admin_nonce'); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="service-name"><?php _e('Service Name', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="service-name" name="name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="service-description"><?php _e('Description', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="service-description" name="description" rows="3" class="large-text"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="service-base-price"><?php _e('Base Price', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="service-base-price" name="base_price" step="0.01" min="0" class="small-text" required>
                        <span class="description"><?php _e('Base price in USD', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="service-base-duration"><?php _e('Base Duration', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="service-base-duration" name="base_duration" min="1" class="small-text" required>
                        <span class="description"><?php _e('Base duration in minutes', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="service-sqm-multiplier"><?php _e('Price per m²', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="service-sqm-multiplier" name="sqm_multiplier" step="0.0001" min="0" class="small-text" required>
                        <span class="description"><?php _e('Additional price per square meter', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="service-sqm-duration-multiplier"><?php _e('Duration per m²', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="service-sqm-duration-multiplier" name="sqm_duration_multiplier" step="0.0001" min="0" class="small-text" required>
                        <span class="description"><?php _e('Additional duration per square meter (minutes)', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="service-default-area"><?php _e('Default Area (m²)', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="service-default-area" name="default_area" min="0" class="small-text" value="0">
                        <span class="description"><?php _e('Default area in square meters (0 = no default)', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="service-sort-order"><?php _e('Sort Order', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="service-sort-order" name="sort_order" min="0" class="small-text" value="0">
                        <span class="description"><?php _e('Lower numbers appear first', 'cleaning-booking'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Status', 'cleaning-booking'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="service-is-active" name="is_active" checked>
                            <?php _e('Active', 'cleaning-booking'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Service', 'cleaning-booking'); ?>">
                <button type="button" id="cb-cancel-edit" class="button" style="display: none;"><?php _e('Cancel', 'cleaning-booking'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="cb-admin-section">
        <h2><?php _e('Existing Services', 'cleaning-booking'); ?></h2>
        
        <!-- Search Bar -->
        <div class="cb-search-section">
            <input type="text" id="cb-services-search" placeholder="<?php _e('Search services by name or description...', 'cleaning-booking'); ?>" class="cb-search-input">
            <button type="button" id="cb-search-services" class="button button-primary"><?php _e('Search', 'cleaning-booking'); ?></button>
            <button type="button" id="cb-clear-services-search" class="button"><?php _e('Clear', 'cleaning-booking'); ?></button>
        </div>
        
        <div id="cb-services-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'cleaning-booking'); ?></th>
                        <th><?php _e('Base Price', 'cleaning-booking'); ?></th>
                        <th><?php _e('Base Duration', 'cleaning-booking'); ?></th>
                        <th><?php _e('Price/m²', 'cleaning-booking'); ?></th>
                        <th><?php _e('Duration/m²', 'cleaning-booking'); ?></th>
                        <th><?php _e('Default Area', 'cleaning-booking'); ?></th>
                        <th><?php _e('Status', 'cleaning-booking'); ?></th>
                        <th><?php _e('Actions', 'cleaning-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="8" class="text-center"><?php _e('No services found', 'cleaning-booking'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <tr data-service-id="<?php echo esc_attr($service->id); ?>">
                                <td>
                                    <strong><?php echo esc_html($service->name); ?></strong>
                                    <?php if ($service->description): ?>
                                        <br><small><?php echo esc_html($service->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo esc_html(number_format($service->base_price, 2)); ?></td>
                                <td><?php echo esc_html($service->base_duration); ?> min</td>
                                <td>$<?php echo esc_html(number_format($service->sqm_multiplier, 4)); ?></td>
                                <td><?php echo esc_html(number_format($service->sqm_duration_multiplier, 4)); ?> min</td>
                                <td><?php echo esc_html($service->default_area ?? 0); ?> m²</td>
                                <td>
                                    <span class="cb-status cb-status-<?php echo $service->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $service->is_active ? __('Active', 'cleaning-booking') : __('Inactive', 'cleaning-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small cb-edit-service" data-service='<?php echo json_encode($service); ?>'>
                                        <?php _e('Edit', 'cleaning-booking'); ?>
                                    </button>
                                    <button type="button" class="button button-small cb-manage-extras" data-service-id="<?php echo esc_attr($service->id); ?>" data-service-name="<?php echo esc_attr($service->name); ?>">
                                        <?php _e('Extras', 'cleaning-booking'); ?>
                                    </button>
                                    <button type="button" class="button button-small cb-delete-service" data-id="<?php echo esc_attr($service->id); ?>">
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
    
    <div class="cb-admin-section" id="cb-service-extras-section" style="display: none;">
        <h2><?php _e('Service Extras', 'cleaning-booking'); ?></h2>
        <p><?php _e('Manage extras that are available for the selected service:', 'cleaning-booking'); ?> <strong id="selected-service-name"></strong></p>
        
        <!-- Create New Extra -->
        <div class="cb-create-extra-section" style="margin-bottom: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
            <h3><?php _e('Create New Extra', 'cleaning-booking'); ?></h3>
            <form id="cb-create-extra-form" class="cb-form" style="display: none;">
                <input type="hidden" name="id" id="create-extra-id">
                <input type="hidden" name="action" value="cb_save_extra">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cb_admin_nonce'); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="create-extra-name"><?php _e('Extra Name', 'cleaning-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="create-extra-name" name="name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="create-extra-description"><?php _e('Description', 'cleaning-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="create-extra-description" name="description" rows="2" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="create-extra-price"><?php _e('Price', 'cleaning-booking'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="create-extra-price" name="price" step="0.01" min="0" class="small-text" required>
                            <span class="description"><?php _e('Price in USD', 'cleaning-booking'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="create-extra-duration"><?php _e('Duration', 'cleaning-booking'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="create-extra-duration" name="duration" min="0" class="small-text" required>
                            <span class="description"><?php _e('Additional duration in minutes', 'cleaning-booking'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Status', 'cleaning-booking'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="create-extra-is-active" name="is_active" checked>
                                <?php _e('Active', 'cleaning-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Extra', 'cleaning-booking'); ?>">
                    <button type="button" id="cb-cancel-create" class="button" style="display: none;"><?php _e('Cancel', 'cleaning-booking'); ?></button>
                </p>
            </form>
            <button type="button" id="cb-show-create-form" class="button">
                <?php _e('+ Create New Extra for This Service', 'cleaning-booking'); ?>
            </button>
        </div>
        
        
        <div id="cb-service-extras-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Extra Name', 'cleaning-booking'); ?></th>
                        <th><?php _e('Price', 'cleaning-booking'); ?></th>
                        <th><?php _e('Duration', 'cleaning-booking'); ?></th>
                        <th><?php _e('Status', 'cleaning-booking'); ?></th>
                        <th><?php _e('Actions', 'cleaning-booking'); ?></th>
                    </tr>
                </thead>
                <tbody id="service-extras-tbody">
                    <!-- Service extras will be loaded here via JavaScript -->
                </tbody>
            </table>
        </div>
        
        <p>
            <button type="button" id="cb-back-to-services" class="button">
                <?php _e('← Back to Services', 'cleaning-booking'); ?>
            </button>
        </p>
    </div>
</div>

