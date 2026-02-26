<?php
$title = 'Access Control';
require_once __DIR__ . '/../../controller/masters/access_control/get_permissions_data.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="card-title mb-0">Role Permissions & Redirects</h5>

                </div>
                <div class="card-body">
                    
                    <div class="row align-items-end pb-3 mb-4 rounded border-bottom border-light" style="background-color: #fafbfe;">
                        <!-- Employee Selector -->
                        <div class="col-md-6">
                            <form method="get" id="empSelectForm" class="m-0">
                                <label class="form-label fw-bold text-dark"><i class="ti ti-users me-1 text-primary"></i> Select Employee</label>
                                <select name="employee_id" class="form-select shadow-sm select2" onchange="document.getElementById('empSelectForm').submit()">
                                    <option value="">-- Select Employee --</option>
                                    <?php
                                    foreach($employees as $emp){
                                        $sel = ($selected_employee_id == $emp['employee_id']) ? 'selected' : '';
                                        echo "<option value='{$emp['employee_id']}' $sel>" . htmlspecialchars(ucwords(strtolower($emp['first_name'] . ' ' . $emp['last_name']))) . "  (" . htmlspecialchars($emp['role_name'] . ' - ' . $emp['employee_code']) . ")</option>";
                                    }
                                    ?>
                                </select>
                            </form>
                        </div>

                        <?php if($selected_employee_id > 0): ?>
                        <!-- Redirect Setting (Part of POST form via 'form' attribute mapping) -->
                        <div class="col-md-6 mt-3 mt-md-0">
                            <label class="form-label fw-bold text-dark"><i class="ti ti-link me-1 text-primary"></i> Login Redirect Page</label>
                            <select name="redirect_url" class="form-select shadow-sm" form="perm_form">
                                <?php foreach($modules as $slug => $name): ?>
                                    <option value="<?= $slug ?>" <?= $currentRedirect == $slug ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                    </div>
                    
                    <?php if($selected_employee_id > 0): ?>
                    <form id="perm_form" action="<?= $basePath ?>/controller/masters/access_control/save_permissions.php" method="post">
                        <input type="hidden" name="employee_id" value="<?= $selected_employee_id ?>">

                        <!-- Table Controls -->
                        <div class="row mb-4 align-items-center bg-light p-3 rounded shadow-sm border">
                            <div class="col-lg-3 col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 border-primary border-opacity-25" style="border-radius: 8px 0 0 8px;"><i class="ti ti-search text-primary"></i></span>
                                    <input type="text" id="moduleSearch" class="form-control border-start-0 border-primary border-opacity-25 shadow-none" placeholder="Search modules via live typing..." style="border-radius: 0 8px 8px 0; padding-left: 0;">
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-8 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
                                <button type="submit" form="perm_form" class="btn btn-sm btn-primary px-3 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1 me-1"><i class="ti ti-device-floppy fs-6"></i> Save Changes</button>
                                <button type="button" class="btn btn-sm btn-soft-primary px-3 rounded-pill fw-medium" onclick="selectAll('view')"><i class="ti ti-eye"></i> All View</button>
                                <button type="button" class="btn btn-sm btn-soft-success px-3 rounded-pill fw-medium" onclick="selectAll('add')"><i class="ti ti-plus"></i> All Add</button>
                                <button type="button" class="btn btn-sm btn-soft-warning px-3 rounded-pill fw-medium" onclick="selectAll('edit')"><i class="ti ti-pencil"></i> All Edit</button>
                                <button type="button" class="btn btn-sm btn-soft-danger px-3 rounded-pill fw-medium" onclick="selectAll('delete')"><i class="ti ti-trash"></i> All Delete</button>
                                <div class="vr mx-1 d-none d-md-block"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-medium" onclick="clearAll()"><i class="ti ti-eraser"></i> Clear All</button>
                            </div>
                        </div>

                        <!-- Permissions Table -->
                        <div class="table-responsive shadow-sm" style="max-height: 65vh; border-radius: 12px; border: 1px solid rgba(0,0,0,0.05);">
                            <table class="table table-hover align-middle mb-0 bg-white" id="permissionsTable">
                                <thead style="z-index: 10;">
                                    <tr>
                                        <th class="bg-primary text-white sticky-top py-3" style="min-width: 250px; font-weight: 600;"><i class="ti ti-box me-1"></i> Module Name</th>
                                        <th class="bg-primary text-white sticky-top py-3 text-center" style="width: 130px; font-weight: 600;"><i class="ti ti-eye me-1"></i> Can View</th>
                                        <th class="bg-primary text-white sticky-top py-3 text-center" style="width: 130px; font-weight: 600;"><i class="ti ti-plus me-1"></i> Can Add</th>
                                        <th class="bg-primary text-white sticky-top py-3 text-center" style="width: 130px; font-weight: 600;"><i class="ti ti-pencil me-1"></i> Can Edit</th>
                                        <th class="bg-primary text-white sticky-top py-3 text-center" style="width: 130px; font-weight: 600;"><i class="ti ti-trash me-1"></i> Can Delete</th>
                                        <th class="bg-primary text-white sticky-top py-3 text-center" style="width: 100px; font-weight: 600;">Row All</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($modules as $slug => $name): 
                                        $p = $permMap[$slug] ?? [];
                                        $view = isset($p['can_view']) && $p['can_view'] ? 'checked' : '';
                                        $add = isset($p['can_add']) && $p['can_add'] ? 'checked' : '';
                                        $edit = isset($p['can_edit']) && $p['can_edit'] ? 'checked' : '';
                                        $del = isset($p['can_delete']) && $p['can_delete'] ? 'checked' : '';
                                    ?>
                                    <tr class="module-row">
                                        <td class="fw-medium text-dark module-name p-3"><?= htmlspecialchars($name) ?> <small class="text-muted d-block" style="font-size: 11px;">(<?= htmlspecialchars($slug) ?>)</small></td>
                                        
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="perms[<?= $slug ?>][view]" value="1" <?= $view ?> style="transform: scale(1.2); cursor: pointer;">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input perm-add" type="checkbox" name="perms[<?= $slug ?>][add]" value="1" <?= $add ?> style="transform: scale(1.2); cursor: pointer;">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input perm-edit" type="checkbox" name="perms[<?= $slug ?>][edit]" value="1" <?= $edit ?> style="transform: scale(1.2); cursor: pointer;">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input perm-delete" type="checkbox" name="perms[<?= $slug ?>][delete]" value="1" <?= $del ?> style="transform: scale(1.2); cursor: pointer;">
                                            </div>
                                        </td>
                                        <td class="text-center bg-light">
                                            <button type="button" class="btn btn-sm btn-icon btn-soft-primary rounded-circle" onclick="toggleRow(this)" title="Toggle Entire Row">
                                                <i class="ti ti-check"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    <?php endif; ?>

                    <script>
                        // Global Select All Functions
                        function selectAll(type) {
                            document.querySelectorAll('.perm-' + type).forEach(cb => {
                                if (cb.closest('tr').style.display !== 'none') {
                                    cb.checked = true;
                                    triggerChange(cb);
                                }
                            });
                        }
                        
                        function clearAll() {
                            document.querySelectorAll('.form-check-input').forEach(cb => {
                                if (cb.closest('tr').style.display !== 'none') {
                                    cb.checked = false;
                                }
                            });
                        }
                        
                        function toggleRow(btn) {
                            const row = btn.closest('tr');
                            const checkboxes = row.querySelectorAll('.form-check-input');
                            // If any is unchecked, check all. Else, uncheck all.
                            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                            checkboxes.forEach(cb => { cb.checked = !allChecked; });
                        }
                        
                        function triggerChange(el) {
                            const event = new Event('change', { bubbles: true });
                            el.dispatchEvent(event);
                        }

                        document.addEventListener('DOMContentLoaded', function() {
                            
                            // Module Search Bar
                            const searchInput = document.getElementById('moduleSearch');
                            if (searchInput) {
                                searchInput.addEventListener('input', function(e) {
                                    const term = e.target.value.toLowerCase().trim();
                                    document.querySelectorAll('.module-row').forEach(row => {
                                        const text = row.querySelector('.module-name').textContent.toLowerCase();
                                        row.style.display = text.includes(term) ? '' : 'none';
                                    });
                                });
                            }

                            // Smart Checkbox Dependencies
                            const table = document.getElementById('permissionsTable');
                            if (table) {
                                table.addEventListener('change', function(e) {
                                    if (e.target.matches('input[type="checkbox"]')) {
                                        const name = e.target.name;
                                        const row = e.target.closest('tr');
                                        
                                        // Case 1: If Add/Edit/Delete is checked, View must dynamically be checked
                                        if (name.includes('[add]') || name.includes('[edit]') || name.includes('[delete]')) {
                                            if (e.target.checked) {
                                                const viewCheckbox = row.querySelector('.perm-view');
                                                if (viewCheckbox && !viewCheckbox.checked) {
                                                    viewCheckbox.checked = true;
                                                }
                                            }
                                        }

                                        // Case 2: If View is unchecked, uncheck Add/Edit/Delete dynamically
                                        if (name.includes('[view]')) {
                                            if (!e.target.checked) {
                                                const otherCheckboxes = row.querySelectorAll('.perm-add, .perm-edit, .perm-delete');
                                                otherCheckboxes.forEach(checkbox => {
                                                    checkbox.checked = false;
                                                });
                                            }
                                        }

                                    }
                                });
                            }
                        });
                    </script>

                </div>
            </div>
        </div>
    </div>
</div>
