<?php
$title = 'HSN/SAC Master';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <!-- Add/Edit Form -->
            <div class="card d-none" id="add_hsn_form">
                <div class="card-header">
                    <h5 class="card-title">Add HSN/SAC</h5>
                </div>

                <div class="card-body">
                    <form id="hsn_form" action="<?= $basePath ?>/controller/masters/hsn_listing/add_hsn.php" method="post">
                        <input type="hidden" name="hsn_id" id="edit_hsn_id">
                        <div class="row g-3">

                            <!-- HSN Code -->
                            <div class="col-lg-3">
                                <label class="form-label">HSN/SAC Code <span class="text-danger">*</span></label>
                                <input type="text" name="hsn_code" class="form-control" placeholder="Enter HSN/SAC Code" required>
                            </div>

                            <!-- GST Rate -->
                            <div class="col-lg-3">
                                <label class="form-label">GST Rate (%) <span class="text-danger">*</span></label>
                                <select name="gst_rate" class="form-select" required>
                                    <option value="">Select Rate</option>
                                    <option value="0">0%</option>
                                    <option value="3">3%</option>
                                    <option value="5">5%</option>
                                    <option value="12">12%</option>
                                    <option value="18">18%</option>
                                    <option value="28">28%</option>
                                </select>
                            </div>

                            <!-- Description -->
                            <div class="col-lg-6">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="1" placeholder="Enter description"></textarea>
                            </div>

                            <div class="col-lg-12 text-end">
                                <button type="button" id="cancel_btn" class="btn btn-secondary ms-2"> Cancel </button>
                                <button type="submit" name="add_hsn" id="submit_btn" class="btn btn-primary"> Add HSN </button>
                                <button type="submit" name="update_hsn" id="update_btn" class="btn btn-success d-none"> Update HSN </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List View -->
            <div class="card" id="hsn_list_card">
                <div class="card-header justify-content-end">
                    <?php if(can_access('hsn_list', 'add')): ?>
                    <a href="<?= $basePath ?>/controller/masters/hsn_listing/export_hsn_excel.php" class="btn btn-success me-2">
                        <i class="ti ti-download me-1"></i> Export to Excel
                    </a>
                    <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                        <i class="ti ti-upload me-1"></i> Upload Bulk HSN
                    </button>
                    <button type="button" id="add_hsn_btn" class="btn btn-primary"> Add HSN/SAC </button>
                    <?php endif; ?>
                </div>

                <!-- Bulk Upload Modal -->
                <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="<?= $basePath ?>/controller/masters/hsn_listing/bulk_upload_hsn.php" method="post" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title">Bulk Upload HSN/SAC</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <p class="small text-muted">Upload an Excel file (.xlsx) with your HSN data. <br>
                                        <a href="<?= $basePath ?>/controller/masters/hsn_listing/download_sample_excel.php" class="text-decoration-none fw-bold">Download Sample Template</a></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Select Excel File</label>
                                        <input type="file" name="excel_file" class="form-control" accept=".xlsx, .xls" required>
                                    </div>
                                    <div class="alert alert-info py-2 small">
                                        <i class="ti ti-info-circle me-1"></i> Ensure HSN Codes are unique. Duplicate HSN Codes will be skipped.
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

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th>HSN/SAC Code</th>
                                <th>Description</th>
                                <th>GST Rate (%)</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "
                                SELECT h.*,
                                    (SELECT COUNT(il.item_id)
                                        FROM items_listing il
                                        WHERE il.hsn_id = h.hsn_id) AS usage_count
                                FROM hsn_listing h
                                WHERE h.organization_id = ?
                                ORDER BY h.hsn_id DESC
                            ";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['organization_id']); // i = integer
                            $stmt->execute();

                            $result = $stmt->get_result();


                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $isUsed = $row['usage_count'] > 0;
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($row['hsn_code']) ?></td>
                                        <td><?= htmlspecialchars($row['description']) ?></td>
                                        <td><span class="badge bg-info"><?= $row['gst_rate'] ?>%</span></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-info" 
                                                        data-id="<?= $row['hsn_id'] ?>" 
                                                        data-code="<?= htmlspecialchars($row['hsn_code']) ?>" 
                                                        data-desc="<?= htmlspecialchars(ucwords(strtolower($row['description']))) ?>" 
                                                        data-rate="<?= $row['gst_rate'] ?>" 
                                                        <?= can_access('hsn_list', 'edit') ? 'onclick="editHsn(this)"' : 'disabled title="Access Denied"' ?>>
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                
                                                <?php if ($isUsed): ?>
                                                    <button class="btn btn-sm btn-danger disabled" title="Cannot delete: HSN is assigned to <?= $row['usage_count'] ?> item(s)" disabled>
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <a href="<?= can_access('hsn_list', 'delete') ? $basePath . '/controller/masters/hsn_listing/delete_hsn.php?id=' . $row['hsn_id'] : 'javascript:void(0);' ?>" 
                                                       class="btn btn-sm btn-danger <?= can_access('hsn_list', 'delete') ? '' : 'disabled' ?>" 
                                                       <?= can_access('hsn_list', 'delete') ? 'onclick="return confirm(\'Are you sure you want to delete this HSN?\');"' : 'title="Access Denied"' ?>>
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Cancel Button
    document.getElementById('cancel_btn').addEventListener('click', function() {
        resetForm();
        toggleView(false);
    });

    // Add Button
    document.getElementById('add_hsn_btn').addEventListener('click', function() {
        resetForm();
        toggleView(true);
    });

    function toggleView(showForm) {
        const formCard = document.getElementById('add_hsn_form');
        const listCard = document.getElementById('hsn_list_card');
        if (showForm) {
            formCard.classList.remove('d-none');
            listCard.classList.add('d-none');
        } else {
            formCard.classList.add('d-none');
            listCard.classList.remove('d-none');
        }
    }

    function editHsn(btn) {
        const id = btn.getAttribute('data-id');
        const code = btn.getAttribute('data-code');
        const desc = btn.getAttribute('data-desc');
        const rate = btn.getAttribute('data-rate');

        // Show form
        toggleView(true);

        // Populate fields
        document.getElementById('edit_hsn_id').value = id;
        document.querySelector('input[name="hsn_code"]').value = code;
        document.querySelector('textarea[name="description"]').value = desc;
        document.querySelector('select[name="gst_rate"]').value = Math.floor(rate); // Handle decimals if any

        // Change Form Action & Button
        const form = document.getElementById('hsn_form');
        form.action = '<?= $basePath ?>/controller/masters/hsn_listing/update_hsn.php';

        document.querySelector('.card-title').innerText = 'Edit HSN/SAC';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('hsn_form').reset();
        document.getElementById('hsn_form').action = '<?= $basePath ?>/controller/masters/hsn_listing/add_hsn.php';
        document.getElementById('edit_hsn_id').value = '';
        document.querySelector('.card-title').innerText = 'Add HSN/SAC';
        document.getElementById('submit_btn').classList.remove('d-none');
        document.getElementById('update_btn').classList.add('d-none');
    }
</script>
