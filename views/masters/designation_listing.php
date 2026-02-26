<?php
$title = 'Designations';
?>

<!-- ============================================================== -->
<!-- Start Main Content -->
<!-- ============================================================== -->

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <div class="card d-none" id="add_designation_form">
                
                <div class="card-header">
                    <h5 class="card-title">Add Designation</h5>
                </div>

                <div class="card-body">
                    <form id="designation_form" action="<?= $basePath ?>/controller/masters/designations/add_designation.php" method="post">
                        <input type="hidden" name="designation_id" id="edit_designation_id">
                        <div class="row g-3">

                            <!-- Designation Name -->
                            <div class="col-lg-3">
                                <label class="form-label">Designation Name</label>
                                <input type="text" name="designation_name" class="form-control" placeholder="Enter designation name" required>
                            </div>

                            <div class="col-lg-4 ">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" id="cancel_btn" class="btn btn-secondary ms-2"> Cancel </button>
                                    <button type="submit" name="add_designation" id="submit_btn" class="btn btn-primary"> Add Designation </button>
                                    <button type="submit" name="update_designation" id="update_btn" class="btn btn-success d-none"> Update Designation </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================== -->

            <div class="card" id="add_designation_list">
 
                <div class="card-header justify-content-between">
                     <h5 class="card-title">Designations List </h5>
                    <button type="button" id="add_designation_btn" class="btn btn-primary"> Add Designation </button>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th>Designation Name</th>
                                <th>Designation Slug</th>
                                <th style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $org_id = $_SESSION['organization_id'];
                            $sql = "SELECT d.*, 
                                    (SELECT COUNT(e.employee_id) FROM employees e WHERE e.designation_id = d.designation_id) as usage_count 
                                    FROM designation_listing d 
                                    WHERE d.organization_id = $org_id 
                                    ORDER BY d.designation_id DESC ";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $is_used = $row['usage_count'] > 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(ucwords(strtolower($row['designation_name']))) ?></td>
                                        <td><?= htmlspecialchars($row['designation_slug']) ?></td>
                                        <td class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info me-2" 
                                                    data-id="<?= $row['designation_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['designation_name']) ?>" 
                                                    onclick="editDesignation(this)">
                                                <i class="ti ti-edit me-1"></i> Edit
                                            </button>

                                            <?php if ($is_used): ?>
                                                <button class="btn btn-sm btn-danger disabled" title="Cannot delete: Assigned to <?= $row['usage_count'] ?> employees">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <a href="<?= $basePath ?>/controller/masters/designations/delete_designation.php?id=<?= $row['designation_id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this designation?');">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr>
                                        <td colspan="3" class="text-center">No designations found.</td>
                                    </tr>';
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
        document.getElementById('add_designation_form').classList.add('d-none');
        document.getElementById('add_designation_list').classList.remove('d-none');
    });

    // Toggle Add Designation Form
    document.getElementById('add_designation_btn').addEventListener('click', function() {
        const formCard = document.getElementById('add_designation_form');
        const add_designation_list = document.getElementById('add_designation_list');
         

        if (formCard.classList.contains('d-none')) {
            resetForm();
            formCard.classList.remove('d-none');
            add_designation_list.classList.add('d-none');
        } else {
            formCard.classList.add('d-none');
            add_designation_list.classList.remove('d-none');
        }
    });

    function editDesignation(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');

        // Show form
        const formCard = document.getElementById('add_designation_form');
        const add_designation_list = document.getElementById('add_designation_list');

        formCard.classList.remove('d-none');
        add_designation_list.classList.add('d-none');

        // Populate fields
        document.getElementById('edit_designation_id').value = id;
        document.querySelector('input[name="designation_name"]').value = name;

        // Change Form Action & Button
        const form = document.getElementById('designation_form');
        form.action = '<?= $basePath ?>/controller/masters/designations/update_designation.php';

        document.querySelector('.card-title').innerText = 'Edit Designation';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('designation_form').reset();
        document.getElementById('designation_form').action = '<?= $basePath ?>/controller/masters/designations/add_designation.php';
        document.getElementById('edit_designation_id').value = '';
        document.querySelector('.card-title').innerText = 'Add Designation';
        document.getElementById('submit_btn').classList.remove('d-none');
        document.getElementById('update_btn').classList.add('d-none');
    }
</script>
