<?php
$title = 'Items';
?>

<!-- ============================================================== -->
<!-- Start Main Content -->
<!-- ============================================================== -->

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <div class="card d-none" id="add_item_form">
                <div class="card-header">
                    <h5 class="card-title">Add Item</h5>
                </div>

                <div class="card-body">
                    <form id="item_form" action="<?= $basePath ?>/controller/masters/items/add_item.php" method="post">
                        <input type="hidden" name="item_id" id="edit_item_id">
                        <div class="row g-3">

                            <!-- Item Name -->
                            <div class="col-lg-3">
                                <label class="form-label">Item Name</label>
                                <input type="text" name="item_name" class="form-control" placeholder="Enter item name" required>
                            </div>

                            <!-- Brand -->
                            <div class="col-lg-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" placeholder="Enter brand">
                            </div>

                            <!-- Opening Stock -->
                            <div class="col-lg-3">
                                <label class="form-label">Opening Stock</label>
                                <input type="number" step="0.01" name="opening_stock" class="form-control" placeholder="0.00">
                            </div>

                            <!-- SKU -->
                            <div class="col-lg-3">
                                <label class="form-label">SKU Code</label>
                                <input type="text" name="stock_keeping_unit" class="form-control" placeholder="Enter SKU Code">
                            </div>

                            <!-- Unit -->
                            <div class="col-lg-3">
                                <label class="form-label">Unit</label>
                                <select name="unit_id" class="form-select" required>
                                    <option value="">Select Unit</option>
                                    <?php
                                    $unitSql = "SELECT unit_id, unit_name FROM units_listing ORDER BY unit_name ASC";
                                    $unitResult = $conn->query($unitSql);
                                    if ($unitResult->num_rows > 0) {
                                        while ($unit = $unitResult->fetch_assoc()) {
                                            echo '<option value="' . $unit['unit_id'] . '">' . htmlspecialchars($unit['unit_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- HSN Code -->
                            <div class="col-lg-3">
                                <label class="form-label">HSN Code</label>
                                <select name="hsn_id" class="form-select select2">
                                    <option value="">Select HSN</option>
                                    <?php
                                    // Fetch HSNs
                                    $hsnSql = "SELECT hsn_id, hsn_code, gst_rate FROM hsn_listing ORDER BY hsn_code ASC";
                                    $hsnResult = $conn->query($hsnSql);
                                    if ($hsnResult && $hsnResult->num_rows > 0) {
                                        while ($hsn = $hsnResult->fetch_assoc()) {
                                            $label = $hsn['hsn_code'] . ' - ' . ($hsn['gst_rate'] + 0) . '%';
                                            echo '<option value="' . $hsn['hsn_id'] . '">' . htmlspecialchars($label) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- MRP -->
                            <div class="col-lg-3">
                                <label class="form-label">MRP</label>
                                <input type="number" step="0.01" name="mrp" class="form-control" placeholder="0.00">
                            </div>

                            <!-- Selling Price -->
                            <div class="col-lg-3">
                                <label class="form-label">Selling Price</label>
                                <input type="number" step="0.01" name="selling_price" class="form-control" placeholder="0.00" required>
                            </div>

                            <!-- Description -->
                            <div class="col-lg-6">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="1" placeholder="Enter item description"></textarea>
                            </div>

                            <div class="col-12"><hr class="my-2"></div>
                            <div class="col-12">
                                <h6 class="text-muted text-uppercase fs-xs">Customer Type Commissions (%)</h6>
                            </div>

                            <!-- Dynamic Customer Types -->
                            <?php
                            $ctSql = "SELECT customers_type_id, customers_type_name FROM customers_type_listing WHERE organization_id = " . $_SESSION['organization_id'];
                            $ctRes = $conn->query($ctSql);
                            if ($ctRes && $ctRes->num_rows > 0) {
                                while ($ct = $ctRes->fetch_assoc()) {
                                    $ctId = $ct['customers_type_id'];
                                    ?>
                                    <div class="col-lg-2">
                                        <label class="form-label"><?= htmlspecialchars($ct['customers_type_name']) ?> %</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" max="100" 
                                                   name="commission[<?= $ctId ?>]" 
                                                   class="form-control commission-input" 
                                                   data-type-id="<?= $ctId ?>"
                                                   placeholder="0.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="col-12"><div class="alert alert-light text-center small">No Customer Types found. Add types in "Masters -> Customers Type" to set commissions.</div></div>';
                            }
                            ?>

                            <div class="col-lg-12 text-end">
                                <button type="button" id="cancel_btn" class="btn btn-secondary ms-2"> Cancel </button>
                                <button type="submit" name="add_item" id="submit_btn" class="btn btn-primary"> Add Item </button>
                                <button type="submit" name="update_item" id="update_btn" class="btn btn-success d-none"> Update Item </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================== -->

            <div class="card" id="add_item_list">
                
                <div class="card-header justify-content-end">
                    <?php if(can_access('items', 'add')): ?>
                    <a href="<?= $basePath ?>/controller/masters/items/export_items_excel.php" class="btn btn-success me-2">
                        <i class="ti ti-download me-1"></i> Export to Excel
                    </a>
                    <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                        <i class="ti ti-upload me-1"></i> Upload Bulk Items
                    </button>
                    <button type="submit" id="add_item_btn" class="btn btn-primary"> Add Item </button>
                    <?php endif; ?>
                </div>

                <!-- Bulk Upload Modal -->
                <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="<?= $basePath ?>/controller/masters/items/bulk_upload_items.php" method="post" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title">Bulk Upload Items</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <p class="small text-muted">Upload an Excel file (.xlsx) with your item data. <br>
                                        <a href="<?= $basePath ?>/controller/masters/items/download_sample_excel.php" class="text-decoration-none fw-bold">Download Sample Template</a></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Select Excel File</label>
                                        <input type="file" name="excel_file" class="form-control" accept=".xlsx, .xls" required>
                                    </div>
                                    <div class="alert alert-info py-2 small">
                                        <i class="ti ti-info-circle me-1"></i> Ensure Unit Names and HSN Codes match existing records exactly. Items with duplicate Names or SKUs will be skipped.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="upload_bulk" class="btn btn-primary">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Commission View Modal -->
                <div class="modal fade" id="viewCommissionsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header py-2 bg-light">
                                <h6 class="modal-title fs-bold mb-0" id="commModalTitle">Commissions</h6>
                                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <tbody id="commModalBody">
                                        <!-- Content -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th style="min-width:180px;" data-priority="1">Item Name</th>
                                <th style="white-space:nowrap;">Brand</th>
                                <th style="white-space:nowrap;">HSN / GST</th>
                                <th style="white-space:nowrap;">Op. Stock</th>
                                <th style="white-space:nowrap;">SKU Code</th>
                                <th style="white-space:nowrap;">Unit</th>
                                <th style="white-space:nowrap;">Price (MRP / SP)</th>
                                <th style="width:1%; white-space:nowrap;" data-priority="1" data-orderable="false">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT i.*, u.unit_name, h.hsn_code, h.gst_rate,
                                    (SELECT COUNT(poi.id) FROM purchase_order_items poi WHERE poi.item_id = i.item_id) as usage_count,
                                    (SELECT GROUP_CONCAT(CONCAT(ctl.customers_type_name, '||', ROUND(ic.commission_percentage, 2)) SEPARATOR '##') 
                                     FROM item_commissions ic 
                                     JOIN customers_type_listing ctl ON ic.customers_type_id = ctl.customers_type_id 
                                     WHERE ic.item_id = i.item_id) as commissions
                                    FROM items_listing i 
                                    LEFT JOIN units_listing u ON i.unit_id = u.unit_id 
                                    LEFT JOIN hsn_listing h ON i.hsn_id = h.hsn_id
                                    WHERE i.organization_id = ?
                                    ORDER BY i.item_id DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['organization_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $isUsed = $row['usage_count'] > 0;
                                    ?>
                                    <tr>
                                        <td style="min-width:180px;">
                                            <span class="fw-semibold"><?= htmlspecialchars(ucwords(strtolower($row['item_name']))) ?></span>
                                            <?php if(!empty($row['description'])): ?>
                                                <div class="text-muted small" title="<?= htmlspecialchars(ucwords(strtolower($row['description']))) ?>">
                                                    <?= htmlspecialchars(mb_strimwidth(ucwords(strtolower($row['description'])), 0, 50, '...')) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="white-space:nowrap;"><?= htmlspecialchars(ucwords(strtolower($row['brand']))) ?></td>
                                        <td style="white-space:nowrap;"><?= htmlspecialchars($row['hsn_code'] ?? '-') ?> <br><span class="badge bg-light text-dark"><?= ($row['gst_rate'] ?? 0) + 0 ?>%</span></td>
                                        <td style="white-space:nowrap;"><?= $row['opening_stock'] + 0 ?></td>
                                        <td style="white-space:nowrap;"><?= htmlspecialchars(strtoupper($row['stock_keeping_unit'])) ?></td>
                                        <td style="white-space:nowrap;"><?= htmlspecialchars(strtoupper($row['unit_name'])) ?></td>
                                        <td style="white-space:nowrap;">
                                            <span class="text-muted text-decoration-line-through me-1">₹<?= $row['mrp'] ?></span>
                                            <span class="fw-bold">₹<?= $row['selling_price'] ?></span>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <!-- 3-dot Dropdown Actions -->
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border px-2" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false"
                                                        title="Actions">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <!-- Commissions -->
                                                    <?php if(!empty($row['commissions'])): ?>
                                                    <li>
                                                        <button class="dropdown-item"
                                                                onclick="viewCommissions('<?= htmlspecialchars($row['commissions']) ?>', '<?= htmlspecialchars(addslashes($row['item_name'])) ?>')">
                                                            <i class="ti ti-percentage me-2 text-success"></i> Commissions
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php endif; ?>
                                                    <!-- Edit -->
                                                    <li>
                                                        <button class="dropdown-item"
                                                                data-id="<?= $row['item_id'] ?>"
                                                                data-name="<?= htmlspecialchars(ucwords(strtolower($row['item_name']))) ?>"
                                                                data-brand="<?= htmlspecialchars(ucwords(strtolower($row['brand']))) ?>"
                                                                data-opening="<?= $row['opening_stock'] ?>"
                                                                data-sku="<?= htmlspecialchars(strtoupper($row['stock_keeping_unit'])) ?>"
                                                                data-unit="<?= $row['unit_id'] ?>"
                                                                data-mrp="<?= $row['mrp'] ?>"
                                                                data-sp="<?= $row['selling_price'] ?>"
                                                                data-hsn="<?= $row['hsn_id'] ?? '' ?>"
                                                                data-desc="<?= htmlspecialchars(ucwords(strtolower($row['description']))) ?>"
                                                                <?= can_access('items', 'edit') ? 'onclick="editItem(this)"' : 'disabled title="Access Denied"' ?>>
                                                            <i class="ti ti-edit me-2 text-info"></i> Edit
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <!-- Delete -->
                                                    <?php if ($isUsed): ?>
                                                    <li>
                                                        <button class="dropdown-item text-muted" disabled
                                                                title="Cannot delete: Item is used in Purchase Orders">
                                                            <i class="ti ti-trash me-2"></i> Delete
                                                        </button>
                                                    </li>
                                                    <?php else: ?>
                                                    <li>
                                                        <a href="<?= can_access('items', 'delete') ? $basePath . '/controller/masters/items/delete_item.php?id=' . $row['item_id'] : 'javascript:void(0);' ?>"
                                                           class="dropdown-item text-danger <?= can_access('items', 'delete') ? '' : 'disabled' ?>"
                                                           <?= can_access('items', 'delete') ? 'onclick="return confirm(\'Are you sure you want to delete this item?\');"' : 'title="Access Denied"' ?>>
                                                            <i class="ti ti-trash me-2"></i> Delete
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                        <tfoot></tfoot>
                    </table>
                </div> <!-- end card-body-->
            </div>

        </div>
    </div>
</div>

<script>
    // Initialize Select2
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $('.select2').select2({
                width: '100%',
                placeholder: "Select Option",
                allowClear: true
            });
        }
    });

    // Cancel Button
    document.getElementById('cancel_btn').addEventListener('click', function() {
        resetForm();
        document.getElementById('add_item_form').classList.add('d-none');
        document.getElementById('add_item_list').classList.remove('d-none');
    });

    // Toggle Add Item Form
    document.getElementById('add_item_btn').addEventListener('click', function() {
        const formCard = document.getElementById('add_item_form');
        const add_item_list = document.getElementById('add_item_list');
        if (formCard.classList.contains('d-none')) {
            resetForm();
            formCard.classList.remove('d-none');
            add_item_list.classList.add('d-none');
        } else {
            formCard.classList.add('d-none');
            add_item_list.classList.remove('d-none');
        }
    }); 

    function editItem(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        const brand = btn.getAttribute('data-brand');
        const opening = btn.getAttribute('data-opening');
        const sku = btn.getAttribute('data-sku');
        const unit = btn.getAttribute('data-unit');
        const mrp = btn.getAttribute('data-mrp');
        const sp = btn.getAttribute('data-sp');
        const desc = btn.getAttribute('data-desc');
        const hsn = btn.getAttribute('data-hsn');

        // Show form
        const formCard = document.getElementById('add_item_form');
        const add_item_list = document.getElementById('add_item_list');

        formCard.classList.remove('d-none');
        add_item_list.classList.add('d-none');

        // Populate fields
        document.getElementById('edit_item_id').value = id;
        document.querySelector('input[name="item_name"]').value = name;
        document.querySelector('input[name="brand"]').value = brand;
        document.querySelector('input[name="opening_stock"]').value = opening;
        document.querySelector('input[name="stock_keeping_unit"]').value = sku;
        document.querySelector('select[name="unit_id"]').value = unit;
        document.querySelector('input[name="mrp"]').value = mrp;
        document.querySelector('input[name="selling_price"]').value = sp;
        document.querySelector('textarea[name="description"]').value = desc;

        // Set HSN Select2
        if(hsn) {
            $('select[name="hsn_id"]').val(hsn).trigger('change');
        } else {
             $('select[name="hsn_id"]').val('').trigger('change');
        }

        // Fetch Commissions
        document.querySelectorAll('.commission-input').forEach(input => input.value = ''); // Reset first
        fetch('<?= $basePath ?>/controller/masters/items/get_item_commissions.php?item_id=' + id)
            .then(res => res.json())
            .then(data => {
                // data is object: { type_id: percentage }
                for (const [typeId, percent] of Object.entries(data)) {
                     const input = document.querySelector(`input[name="commission[${typeId}]"]`);
                     if(input) input.value = percent;
                }
            })
            .catch(err => console.error('Error fetching commissions:', err));

        // Change Form Action & Button
        const form = document.getElementById('item_form');
        form.action = '<?= $basePath ?>/controller/masters/items/update_item.php';

        document.querySelector('.card-title').innerText = 'Edit Item';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('item_form').reset();
         $('select[name="hsn_id"]').val('').trigger('change'); // Reset Select2
        document.querySelectorAll('.commission-input').forEach(input => input.value = '');
        document.getElementById('item_form').action = '<?= $basePath ?>/controller/masters/items/add_item.php';
        document.getElementById('edit_item_id').value = '';
        document.querySelector('.card-title').innerText = 'Add Item';
        document.getElementById('submit_btn').classList.remove('d-none');
        document.getElementById('update_btn').classList.add('d-none');
    }

    function viewCommissions(dataString, itemName) {
        // dataString format: TypeName||Percent##TypeName||Percent
        const tbody = document.getElementById('commModalBody');
        document.getElementById('commModalTitle').innerText = itemName;
        tbody.innerHTML = '';

        if(!dataString) {
             tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No commissions set</td></tr>';
        } else {
            const items = dataString.split('##');
            items.forEach(item => {
                const parts = item.split('||');
                if(parts.length === 2) {
                    const row = `
                        <tr>
                            <td class="ps-3">${parts[0]}</td>
                            <td class="text-end pe-3 fw-bold text-dark">${parts[1]}%</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                }
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('viewCommissionsModal'));
        modal.show();
    }
</script>


