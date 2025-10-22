<?php
/**
 * Admin template for truck management
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Truck Management', 'cleaning-booking'); ?></h1>
    
    <!-- Add New Truck Form -->
    <div class="cb-admin-section">
        <h2><?php _e('Add New Truck', 'cleaning-booking'); ?></h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="create_truck">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="truck_name"><?php _e('Truck Name', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="truck_name" name="truck_name" class="regular-text" required>
                        <p class="description"><?php _e('Enter a descriptive name for the truck (e.g., "Truck 1", "Main Cleaning Vehicle")', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="truck_number"><?php _e('Truck Number', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="truck_number" name="truck_number" class="regular-text">
                        <p class="description"><?php _e('Optional: License plate or identification number', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="truck_status"><?php _e('Status', 'cleaning-booking'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="truck_status" name="is_active" value="1" checked>
                            <?php _e('Active', 'cleaning-booking'); ?>
                        </label>
                        <p class="description"><?php _e('Inactive trucks will not be assigned to new bookings', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Add Truck', 'cleaning-booking')); ?>
        </form>
    </div>
    
    <!-- Existing Trucks -->
    <div class="cb-admin-section">
        <h2><?php _e('Existing Trucks', 'cleaning-booking'); ?></h2>
        
        <!-- Search Bar -->
        <div class="cb-search-section">
            <input type="text" id="cb-trucks-search" placeholder="<?php _e('Search trucks by name or description...', 'cleaning-booking'); ?>" class="cb-search-input">
            <button type="button" id="cb-search-trucks" class="button button-primary"><?php _e('Search', 'cleaning-booking'); ?></button>
            <button type="button" id="cb-clear-trucks-search" class="button"><?php _e('Clear', 'cleaning-booking'); ?></button>
        </div>
        
        <?php if (empty($trucks)): ?>
            <p><?php _e('No trucks found. Add your first truck above.', 'cleaning-booking'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'cleaning-booking'); ?></th>
                        <th><?php _e('Truck Name', 'cleaning-booking'); ?></th>
                        <th><?php _e('Truck Number', 'cleaning-booking'); ?></th>
                        <th><?php _e('Status', 'cleaning-booking'); ?></th>
                        <th><?php _e('Actions', 'cleaning-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trucks as $truck): ?>
                        <tr>
                            <td><?php echo esc_html($truck->id); ?></td>
                            <td><?php echo esc_html($truck->truck_name); ?></td>
                            <td><?php echo esc_html($truck->truck_number ?: '-'); ?></td>
                            <td>
                                <span class="cb-status cb-status-<?php echo esc_attr($truck->is_active ? 'active' : 'inactive'); ?>">
                                    <?php echo $truck->is_active ? __('Active', 'cleaning-booking') : __('Inactive', 'cleaning-booking'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="cb-truck-actions">
                                    <button type="button" class="button button-small cb-edit-truck" data-truck-id="<?php echo esc_attr($truck->id); ?>">
                                        <?php _e('Edit', 'cleaning-booking'); ?>
                                    </button>
                                    <form method="post" style="display: inline-block;">
                                        <input type="hidden" name="action" value="toggle_truck_status">
                                        <input type="hidden" name="truck_id" value="<?php echo esc_attr($truck->id); ?>">
                                        <input type="hidden" name="current_status" value="<?php echo esc_attr($truck->is_active); ?>">
                                        <?php 
                                        $toggle_text = $truck->is_active ? __('Deactivate', 'cleaning-booking') : __('Activate', 'cleaning-booking');
                                        $toggle_class = $truck->is_active ? 'button-secondary' : 'button-primary';
                                        ?>
                                        <?php submit_button($toggle_text, "button-small $toggle_class", '', false); ?>
                                    </form>
                                    <form method="post" style="display: inline-block;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this truck?', 'cleaning-booking'); ?>');">
                                        <input type="hidden" name="action" value="delete_truck">
                                        <input type="hidden" name="truck_id" value="<?php echo esc_attr($truck->id); ?>">
                                        <?php submit_button(__('Delete', 'cleaning-booking'), 'button-small button-link-delete', '', false); ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Truck Modal -->
<div id="cb-edit-truck-modal" class="cb-modal" style="display: none;">
    <div class="cb-modal-content">
        <div class="cb-modal-header">
            <h2><?php _e('Edit Truck', 'cleaning-booking'); ?></h2>
            <span class="cb-modal-close">&times;</span>
        </div>
        <div class="cb-modal-body">
            <form id="cb-edit-truck-form" method="post">
                <input type="hidden" name="action" value="update_truck">
                <input type="hidden" name="truck_id" id="edit_truck_id">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit_truck_name"><?php _e('Truck Name', 'cleaning-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_truck_name" name="truck_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit_truck_number"><?php _e('Truck Number', 'cleaning-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_truck_number" name="truck_number" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit_is_active"><?php _e('Status', 'cleaning-booking'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="edit_is_active" name="is_active" value="1" checked>
                                <?php _e('Active', 'cleaning-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <div class="cb-modal-footer">
                    <button type="button" class="button cb-modal-cancel"><?php _e('Cancel', 'cleaning-booking'); ?></button>
                    <?php submit_button(__('Update Truck', 'cleaning-booking'), 'primary', '', false); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit truck functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-edit-truck')) {
            const editBtn = e.target.closest('.cb-edit-truck');
            const truckId = editBtn.dataset.truckId;
            
            // Get truck data (you might want to fetch this via AJAX)
            // For now, we'll populate from the table row
            const row = editBtn.closest('tr');
            const truckName = row.querySelector('td:nth-child(2)').textContent;
            const truckNumber = row.querySelector('td:nth-child(3)').textContent;
            const statusElement = row.querySelector('.cb-status');
            const isActive = statusElement && statusElement.classList.contains('cb-status-active');
            
            // Populate form
            const editTruckId = document.getElementById('edit_truck_id');
            const editTruckName = document.getElementById('edit_truck_name');
            const editTruckNumber = document.getElementById('edit_truck_number');
            const editIsActive = document.getElementById('edit_is_active');
            
            if (editTruckId) editTruckId.value = truckId;
            if (editTruckName) editTruckName.value = truckName;
            if (editTruckNumber) editTruckNumber.value = truckNumber === '-' ? '' : truckNumber;
            if (editIsActive) editIsActive.checked = isActive;
            
            // Show modal
            const modal = document.getElementById('cb-edit-truck-modal');
            if (modal) modal.style.display = 'block';
        }
    });
    
    // Close modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cb-modal-close') || e.target.closest('.cb-modal-cancel')) {
            const modal = document.getElementById('cb-edit-truck-modal');
            if (modal) modal.style.display = 'none';
        }
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.id === 'cb-edit-truck-modal') {
            const modal = document.getElementById('cb-edit-truck-modal');
            if (modal) modal.style.display = 'none';
        }
    });
});
</script>

<style>
.cb-admin-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.cb-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.cb-status-active {
    background: #d4edda;
    color: #155724;
}

.cb-status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.cb-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.cb-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #ccd0d4;
    width: 80%;
    max-width: 600px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.cb-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ccd0d4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cb-modal-header h2 {
    margin: 0;
}

.cb-modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.cb-modal-close:hover {
    color: #000;
}

.cb-modal-body {
    padding: 20px;
}

.cb-modal-footer {
    padding: 20px;
    border-top: 1px solid #ccd0d4;
    text-align: right;
}

.cb-modal-footer .button {
    margin-left: 10px;
}

.cb-truck-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.cb-truck-actions .button {
    margin: 0;
}

.cb-status-active {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.cb-status-inactive {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>
