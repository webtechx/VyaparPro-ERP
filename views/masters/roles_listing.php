<?php
$title = 'Roles';
?>

<!-- ============================================================== -->
<!-- Start Main Content -->
<!-- ============================================================== -->

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <div class="card d-none" id="add_role_form">
                <div class="card-header">
                    <h5 class="card-title">Add Role</h5>
                </div>

                <div class="card-body">
                    <form id="role_form" action="<?= $basePath ?>/controller/masters/roles/add_role.php" method="post">
                        <input type="hidden" name="role_id" id="edit_role_id">
                        <div class="row g-3">

                            <!-- Role Name -->
                            <div class="col-lg-3">
                                <label class="form-label">Role Name</label>
                                <input type="text" name="role_name" class="form-control" placeholder="Enter role name" required>
                            </div>

                            <div class="col-lg-4 ">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" id="cancel_btn" class="btn btn-secondary ms-2"> Cancel </button>
                                    <button type="submit" name="add_role" id="submit_btn" class="btn btn-primary"> Add Role </button>
                                    <button type="submit" name="update_role" id="update_btn" class="btn btn-success d-none"> Update Role </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================== -->

            <div class="card" id="add_role_list">
 
                <div class="card-header justify-content-end">
                    <button type="button" id="add_role_btn" class="btn btn-primary" <?= can_access('roles', 'add') ? '' : 'disabled title="Access Denied"' ?>> Add Role </button>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th>Role Name</th>
                                <th>Role Slug</th>
                                <th style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT r.*, (SELECT COUNT(employee_id) FROM employees e WHERE e.role_id = r.role_id) as assigned_count FROM roles_listing r ORDER BY r.role_id DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['role_name']) ?></td>
                                        <td><?= htmlspecialchars($row['role_slug']) ?></td>
                                        <td class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info me-2" 
                                                    data-id="<?= $row['role_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['role_name']) ?>" 
                                                    <?= can_access('roles', 'edit') ? 'onclick="editRole(this)"' : 'disabled title="Access Denied"' ?>>
                                                <i class="ti ti-edit me-1"></i> Edit
                                            </button>

                                            <?php 
                                            // Determine if delete is allowed by logic AND permission
                                            $canDelete = can_access('roles', 'delete');
                                            $isAssigned = $row['assigned_count'] > 0;
                                            
                                            // Link: Only if can delete AND not assigned
                                            $delLink = ($canDelete && !$isAssigned) ? $basePath . '/controller/masters/roles/delete_role.php?id=' . $row['role_id'] : 'javascript:void(0);';
                                            
                                            // Classes: Disabled if NO permission OR is assigned
                                            $delClass = (!$canDelete || $isAssigned) ? 'disabled' : '';
                                            
                                            // Title/Click
                                            if(!$canDelete) {
                                                $delCtx = 'title="Access Denied"';
                                            } else if($isAssigned) {
                                                $delCtx = 'title="Cannot delete assigned role"';
                                            } else {
                                                $delCtx = "onclick=\"return confirm('Are you sure you want to delete this role?');\"";
                                            }
                                            ?>
                                            <a href="<?= $delLink ?>" 
                                               class="btn btn-sm btn-danger <?= $delClass ?>" 
                                               <?= $delCtx ?>>
                                                <i class="ti ti-trash me-1"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                    '<tr>
                                        <td colspan="3" class="text-center">No roles found.</td>
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
        document.getElementById('add_role_form').classList.add('d-none');
        document.getElementById('add_role_list').classList.remove('d-none');
    });

    // Toggle Add Role Form
    document.getElementById('add_role_btn').addEventListener('click', function() {
        const formCard = document.getElementById('add_role_form');
        const add_role_list = document.getElementById('add_role_list');
         

        if (formCard.classList.contains('d-none')) {
            resetForm();
            formCard.classList.remove('d-none');
            add_role_list.classList.add('d-none');
        } else {
            formCard.classList.add('d-none');
            add_emp_list.classList.remove('d-none');

            
        }
    });

    function editRole(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');

        // Show form
        const formCard = document.getElementById('add_role_form');
        const add_role_list = document.getElementById('add_role_list');

        formCard.classList.remove('d-none');
        add_role_list.classList.add('d-none');

        // Populate fields
        document.getElementById('edit_role_id').value = id;
        document.querySelector('input[name="role_name"]').value = name;

        // Change Form Action & Button
        const form = document.getElementById('role_form');
        form.action = '<?= $basePath ?>/controller/masters/roles/update_role.php';

        document.querySelector('.card-title').innerText = 'Edit Role';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('role_form').reset();
        document.getElementById('role_form').action = '<?= $basePath ?>/controller/masters/roles/add_role.php';
        document.getElementById('edit_role_id').value = '';
        document.querySelector('.card-title').innerText = 'Add Role';
        document.getElementById('submit_btn').classList.remove('d-none');
        document.getElementById('update_btn').classList.add('d-none');
    }
</script>


