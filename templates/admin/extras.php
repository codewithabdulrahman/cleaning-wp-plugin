<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Extras', 'cleaning-booking'); ?></h1>

    <div class="cb-admin-section">
        <h2><?php _e('Create New Extra', 'cleaning-booking'); ?></h2>
        <form id="cb-global-extra-form" class="cb-form">
            <input type="hidden" name="action" value="cb_save_global_extra">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cb_admin_nonce'); ?>">
            <input type="hidden" name="id" id="global-extra-id" value="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="extra-name-en"><?php _e('Extra Name (English)', 'cleaning-booking'); ?></label></th>
                    <td><input type="text" id="extra-name-en" name="name_en" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="extra-name-el"><?php _e('Extra Name (Greek)', 'cleaning-booking'); ?></label></th>
                    <td><input type="text" id="extra-name-el" name="name_el" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="extra-description-en"><?php _e('Description (English)', 'cleaning-booking'); ?></label></th>
                    <td><textarea id="extra-description-en" name="description_en" rows="2" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="extra-description-el"><?php _e('Description (Greek)', 'cleaning-booking'); ?></label></th>
                    <td><textarea id="extra-description-el" name="description_el" rows="2" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="extra-pricing-type"><?php _e('Pricing Type', 'cleaning-booking'); ?></label></th>
                    <td>
                        <select id="extra-pricing-type" name="pricing_type" class="regular-text">
                            <option value="fixed"><?php _e('Fixed Price', 'cleaning-booking'); ?></option>
                            <option value="per_sqm"><?php _e('Per Square Meter', 'cleaning-booking'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr class="extra-price-row">
                    <th scope="row"><label for="extra-price"><?php _e('Price', 'cleaning-booking'); ?></label></th>
                    <td><input type="number" id="extra-price" name="price" step="0.01" min="0" class="small-text" value="0"></td>
                </tr>
                <tr class="extra-price-per-sqm-row" style="display:none;">
                    <th scope="row"><label for="extra-price-per-sqm"><?php _e('Price per m²', 'cleaning-booking'); ?></label></th>
                    <td><input type="number" id="extra-price-per-sqm" name="price_per_sqm" step="0.01" min="0" class="small-text"></td>
                </tr>
                <tr class="extra-duration-per-sqm-row" style="display:none;">
                    <th scope="row"><label for="extra-duration-per-sqm"><?php _e('Duration per m²', 'cleaning-booking'); ?></label></th>
                    <td><input type="number" id="extra-duration-per-sqm" name="duration_per_sqm" min="0" class="small-text"></td>
                </tr>
                <tr class="extra-duration-row">
                    <th scope="row"><label for="extra-duration"><?php _e('Duration', 'cleaning-booking'); ?></label></th>
                    <td><input type="number" id="extra-duration" name="duration" min="0" class="small-text" value="0"></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Assign to Services', 'cleaning-booking'); ?></th>
                    <td>
                        <select id="extra-service-ids" name="service_ids[]" multiple style="display:none;">
                            <?php foreach ($services as $s): ?>
                                <option value="<?php echo esc_attr($s->id); ?>"><?php echo esc_html($s->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="extra-service-ids-ui" class="cb-multi"></div>
                        <p class="description"><?php _e('Click to open and select multiple services.', 'cleaning-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Status', 'cleaning-booking'); ?></th>
                    <td>
                        <label><input type="checkbox" name="is_active" checked> <?php _e('Active', 'cleaning-booking'); ?></label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Extra', 'cleaning-booking'); ?>">
            </p>
        </form>
    </div>

    <div class="cb-admin-section">
        <h2><?php _e('All Extras', 'cleaning-booking'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Extra Name', 'cleaning-booking'); ?></th>
                    <th><?php _e('Pricing Type', 'cleaning-booking'); ?></th>
                    <th><?php _e('Price', 'cleaning-booking'); ?></th>
                    <th><?php _e('Duration', 'cleaning-booking'); ?></th>
                    <th><?php _e('Status', 'cleaning-booking'); ?></th>
                    <th><?php _e('Assigned Services', 'cleaning-booking'); ?></th>
                    <th><?php _e('Actions', 'cleaning-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($extras)): ?>
                    <tr><td colspan="7"><?php _e('No extras found.', 'cleaning-booking'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($extras as $extra): $assigned = CB_Database::get_extra_services($extra->id); $isPerSqm = ($extra->pricing_type === 'per_sqm'); ?>
                        <tr data-extra-id="<?php echo esc_attr($extra->id); ?>">
                            <td><strong><?php echo esc_html($extra->name); ?></strong><?php if ($extra->description): ?><br><small><?php echo esc_html($extra->description); ?></small><?php endif; ?></td>
                            <td><?php echo $isPerSqm ? '<span style="background:#2271b1;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">Per SQM</span>' : '<span style="background:#00a32a;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">Fixed</span>'; ?></td>
                            <td><?php echo $isPerSqm ? '€'.number_format((float)($extra->price_per_sqm ?: 0),2).' /m²' : '€'.number_format((float)$extra->price,2); ?></td>
                            <td><?php echo $isPerSqm ? intval($extra->duration_per_sqm).' min/m²' : intval($extra->duration).' min'; ?></td>
                            <td>
                                <label style="display:inline-flex;align-items:center;">
                                    <input type="checkbox" class="cb-toggle-extra-status" data-extra-id="<?php echo esc_attr($extra->id); ?>" <?php echo intval($extra->is_active) === 1 ? 'checked' : ''; ?> style="margin-right:5px;">
                                    <span class="cb-status-text cb-status-<?php echo intval($extra->is_active) === 1 ? 'active' : 'inactive'; ?>"><?php echo intval($extra->is_active) === 1 ? 'Active' : 'Inactive'; ?></span>
                                </label>
                            </td>
                            <td>
                                <?php if (empty($assigned)): ?>
                                    <em><?php _e('Not assigned', 'cleaning-booking'); ?></em>
                                <?php else: ?>
                                    <?php foreach ($assigned as $as): ?>
                                        <span class="cb-chip"><?php echo esc_html($as->name); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small cb-edit-global-extra" data-extra='<?php echo json_encode($extra); ?>' data-services='<?php echo json_encode(array_map(function($s){ return $s->id; }, $assigned)); ?>'><?php _e('Edit', 'cleaning-booking'); ?></button>
                                <button type="button" class="button button-small cb-delete-global-extra" data-extra-id="<?php echo esc_attr($extra->id); ?>" style="margin-left:5px;"><?php _e('Remove', 'cleaning-booking'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    // Lightweight multiselect UI (chips + dropdown)
    const buildMulti = function(select, mount) {
        const state = new Set(Array.from(select.options).filter(o=>o.selected).map(o=>o.value));
        mount.classList.add('cb-multi-wrap');
        mount.innerHTML = '';
        const head = document.createElement('div');
        head.className = 'cb-multi-head';
        const chips = document.createElement('div');
        chips.className = 'cb-chips';
        const caret = document.createElement('div');
        caret.className = 'cb-caret';
        head.appendChild(chips);
        head.appendChild(caret);
        const list = document.createElement('div');
        list.className = 'cb-multi-list';
        const renderChips = () => {
            chips.innerHTML = '';
            if (state.size === 0) {
                const ph = document.createElement('span');
                ph.className = 'cb-placeholder';
                ph.textContent = '<?php echo esc_js(__('Select services...', 'cleaning-booking')); ?>';
                chips.appendChild(ph);
            } else {
                state.forEach(val => {
                    const opt = Array.from(select.options).find(o=>o.value === val);
                    if (!opt) return;
                    const chip = document.createElement('span');
                    chip.className = 'cb-chip';
                    chip.textContent = opt.text;
                    const x = document.createElement('button');
                    x.type = 'button';
                    x.className = 'cb-chip-x';
                    x.textContent = '×';
                    x.addEventListener('click', (e)=>{
                        e.stopPropagation();
                        state.delete(val);
                        opt.selected = false;
                        renderChips();
                        renderList();
                    });
                    chip.appendChild(x);
                    chips.appendChild(chip);
                });
            }
        };
        const renderList = () => {
            list.innerHTML = '';
            Array.from(select.options).forEach(opt => {
                const row = document.createElement('div');
                row.className = 'cb-multi-item' + (state.has(opt.value) ? ' selected' : '');
                const check = document.createElement('span');
                check.className = 'cb-check';
                row.appendChild(check);
                const label = document.createElement('span');
                label.textContent = opt.text;
                row.appendChild(label);
                row.addEventListener('click', () => {
                    const isSel = state.has(opt.value);
                    if (isSel) {
                        state.delete(opt.value);
                        opt.selected = false;
                    } else {
                        state.add(opt.value);
                        opt.selected = true;
                    }
                    renderChips();
                    renderList();
                });
                list.appendChild(row);
            });
        };
        head.addEventListener('click', ()=>{
            mount.classList.toggle('open');
        });
        document.addEventListener('click', (e)=>{
            if (!mount.contains(e.target)) mount.classList.remove('open');
        });
        mount.appendChild(head);
        mount.appendChild(list);
        renderChips();
        renderList();
    };

    const form = document.getElementById('cb-global-extra-form');
    const pricingType = document.getElementById('extra-pricing-type');
    const toggle = function(){
        const isPerSqm = pricingType.value === 'per_sqm';
        document.querySelector('.extra-price-row').style.display = isPerSqm ? 'none' : '';
        document.querySelector('.extra-duration-row').style.display = isPerSqm ? 'none' : '';
        document.querySelector('.extra-price-per-sqm-row').style.display = isPerSqm ? '' : 'none';
        document.querySelector('.extra-duration-per-sqm-row').style.display = isPerSqm ? '' : 'none';
    };
    if (pricingType) {
        pricingType.addEventListener('change', toggle);
        toggle();
    }
    if (form) {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const fd = new FormData(form);
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(r=>r.json()).then(r=>{
                if (r && r.success) { location.reload(); }
                else { alert((r && r.data && r.data.message) || 'Error'); }
            }).catch(()=>alert('Error'));
        });
    }
    // Initialize custom multiselect
    const selectEl = document.getElementById('extra-service-ids');
    const mountEl = document.getElementById('extra-service-ids-ui');
    if (selectEl && mountEl) { buildMulti(selectEl, mountEl); }

    // Edit existing extra -> fill the form
    document.addEventListener('click', function(e){
        if (e.target.closest('.cb-edit-global-extra')) {
            const btn = e.target.closest('.cb-edit-global-extra');
            const extra = JSON.parse(btn.dataset.extra);
            const services = JSON.parse(btn.dataset.services || '[]');
            document.getElementById('global-extra-id').value = extra.id;
            document.getElementById('extra-name-en').value = extra.name || '';
            document.getElementById('extra-name-el').value = extra.name_el || '';
            document.getElementById('extra-description-en').value = extra.description || '';
            document.getElementById('extra-description-el').value = extra.description_el || '';
            document.getElementById('extra-pricing-type').value = extra.pricing_type || 'fixed';
            toggle();
            const price = document.getElementById('extra-price');
            const dur = document.getElementById('extra-duration');
            const pps = document.getElementById('extra-price-per-sqm');
            const dps = document.getElementById('extra-duration-per-sqm');
            if (price) price.value = extra.price || 0;
            if (dur) dur.value = extra.duration || 0;
            if (pps) pps.value = extra.price_per_sqm || '';
            if (dps) dps.value = extra.duration_per_sqm || '';
            const svc = document.getElementById('extra-service-ids');
            if (svc) {
                // Normalize services array to strings for robust comparison
                const selSet = new Set((Array.isArray(services) ? services : []).map(v => String(parseInt(v, 10))));
                Array.from(svc.options).forEach(o => { o.selected = selSet.has(String(o.value)); });
                if (mountEl) { mountEl.innerHTML = ''; buildMulti(svc, mountEl); }
            }
            window.scrollTo({ top: form.offsetTop - 60, behavior: 'smooth' });
        }
    });

    // Delete extra
    document.addEventListener('click', function(e){
        if (e.target.closest('.cb-delete-global-extra')) {
            const id = e.target.closest('.cb-delete-global-extra').dataset.extraId;
            if (!confirm('Are you sure you want to delete this extra?')) return;
            const fd = new FormData();
            fd.append('action', 'cb_delete_extra');
            fd.append('nonce', '<?php echo wp_create_nonce('cb_admin_nonce'); ?>');
            fd.append('id', id);
            fetch(ajaxurl, { method: 'POST', body: fd })
              .then(r=>r.json()).then(r=>{ if (r && r.success) location.reload(); else alert('Error'); })
              .catch(()=>alert('Error'));
        }
    });
})();
</script>

<style>
.cb-chip { display:inline-flex; align-items:center; background:#f0f0f1; border-radius:16px; padding:2px 8px; margin:2px; font-size:12px; }
.cb-chip .cb-chip-x { margin-left:6px; border:0; background:transparent; cursor:pointer; font-size:14px; line-height:1; color:#666; }
.cb-multi-wrap { position:relative; width:320px; font-size:13px; }
.cb-multi-head { display:flex; align-items:center; min-height:36px; padding:4px 32px 4px 8px; border:1px solid #c3c4c7; background:#fff; border-radius:4px; cursor:pointer; flex-wrap:wrap; }
.cb-chips { display:flex; flex-wrap:wrap; gap:4px; }
.cb-placeholder { color:#888; }
.cb-caret { position:absolute; right:8px; top:8px; width:0; height:0; border-left:6px solid transparent; border-right:6px solid transparent; border-top:6px solid #555; }
.cb-multi-list { position:absolute; z-index:10; display:none; background:#fff; border:1px solid #c3c4c7; border-radius:4px; width:100%; max-height:240px; overflow:auto; margin-top:4px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.cb-multi-wrap.open .cb-multi-list { display:block; }
.cb-multi-item { padding:8px 10px; display:flex; gap:8px; align-items:center; cursor:pointer; }
.cb-multi-item:hover { background:#f6f7f7; }
.cb-check { width:16px; height:16px; border:2px solid #c3c4c7; border-radius:3px; display:inline-block; }
.cb-multi-item.selected .cb-check { background:#2271b1; border-color:#2271b1; box-shadow:inset 0 0 0 2px #fff; }
</style>


