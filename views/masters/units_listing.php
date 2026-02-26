<?php
$title = 'Units';
?>

<!-- ============================================================== -->
<!-- Start Main Content -->
<!-- ============================================================== -->

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <div class="card d-none" id="add_unit_form">
                <div class="card-header">
                    <h5 class="card-title">Add Unit</h5>
                </div>

                <div class="card-body">
                    <form id="unit_form" action="<?= $basePath ?>/controller/masters/units/add_unit.php" method="post">
                        <input type="hidden" name="unit_id" id="edit_unit_id">
                        <div class="row g-3">

                            <!-- Unit Name -->
                            <div class="col-lg-4">
                                <label class="form-label">Unit Name</label>
                                <input type="text" name="unit_name" class="form-control" placeholder="Enter unit name" required>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" id="cancel_btn" class="btn btn-secondary ms-2"> Cancel </button>
                                    <button type="submit" name="add_unit" id="submit_btn" class="btn btn-primary"> Add Unit </button>
                                    <button type="submit" name="update_unit" id="update_btn" class="btn btn-success d-none"> Update Unit </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- ============================================================== -->

            <div class="card"  id="add_unit_list">
               
                <div class="card-header justify-content-end">
                    <?php if(can_access('units', 'add')): ?>
                    <button type="submit" id="add_unit_btn" class="btn btn-primary"> Add Unit </button>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th>Unit Name</th>
                                <th>Unit Slug</th>
                                <th style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT u.*, (SELECT COUNT(*) FROM items_listing WHERE unit_id = u.unit_id) as usage_count 
                                    FROM units_listing u WHERE u.organization_id = ? ORDER BY u.unit_id DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['organization_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $isUsed = $row['usage_count'] > 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(ucwords(strtolower($row['unit_name']))) ?></td>
                                        <td><?= htmlspecialchars($row['unit_slug']) ?></td>
                                        <td class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info me-2" 
                                                    data-id="<?= $row['unit_id'] ?>" 
                                                    data-name="<?= htmlspecialchars(ucwords(strtolower($row['unit_name']))) ?>" 
                                                    <?= can_access('units', 'edit') ? 'onclick="editUnit(this)"' : 'disabled title="Access Denied"' ?>>
                                                <i class="ti ti-edit me-1"></i> Edit
                                            </button>
                                            
                                            <?php 
                                            $canDelete = can_access('units', 'delete');
                                            // Note: For units, I'll combine the "Used" check with permission check visually
                                            // The original code had an IF/ELSE for used.
                                            // I will prioritize Access Denied, then Used check.
                                            
                                            if(!$canDelete): ?>
                                                 <button class="btn btn-sm btn-danger" disabled title="Access Denied">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            <?php elseif($isUsed): ?>
                                                <button class="btn btn-sm btn-secondary" disabled title="Cannot delete: Unit is in use by <?= $row['usage_count'] ?> items">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <a href="<?= $basePath ?>/controller/masters/units/delete_unit.php?id=<?= $row['unit_id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this unit?');">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </a>
                                            <?php endif; ?>
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
    // Cancel Button
    document.getElementById('cancel_btn').addEventListener('click', function() {
        resetForm();
        document.getElementById('add_unit_form').classList.add('d-none');
        document.getElementById('add_unit_list').classList.remove('d-none');
    });

    // Toggle Add Unit Form
    document.getElementById('add_unit_btn').addEventListener('click', function() {
        const formCard = document.getElementById('add_unit_form');
        const add_unit_list = document.getElementById('add_unit_list');
        if (formCard.classList.contains('d-none')) {
            resetForm();
            formCard.classList.remove('d-none');
            add_unit_list.classList.add('d-none');
        } else {
            formCard.classList.add('d-none');
            add_unit_list.classList.remove('d-none');
        }
    });

    function editUnit(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');

        // Show form
        const formCard = document.getElementById('add_unit_form');
        const add_unit_list = document.getElementById('add_unit_list');

        formCard.classList.remove('d-none');
        add_unit_list.classList.add('d-none');

        // Populate fields
        document.getElementById('edit_unit_id').value = id;
        document.querySelector('input[name="unit_name"]').value = name;

        // Change Form Action & Button
        const form = document.getElementById('unit_form');
        form.action = '<?= $basePath ?>/controller/masters/units/update_unit.php';

        document.querySelector('.card-title').innerText = 'Edit Unit';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('unit_form').reset();
        document.getElementById('unit_form').action = '<?= $basePath ?>/controller/masters/units/add_unit.php';
        document.getElementById('edit_unit_id').value = '';
        document.querySelector('.card-title').innerText = 'Add Unit';
        document.getElementById('submit_btn').classList.remove('d-none');
        document.getElementById('update_btn').classList.add('d-none');
    }
</script>


