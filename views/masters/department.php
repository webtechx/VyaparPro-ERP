<?php
$title = 'Department';
?>

<!-- ============================================================== -->
<!-- Start Main Content -->
<!-- ============================================================== -->

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <div class="card d-none" id="add_department_form">
                
                <div class="card-header">
                    <h5 class="card-title">Add Department</h5>
                </div>

                <div class="card-body">
                    <form id="department_form" action="<?= $basePath ?>/controller/masters/departments/add_department.php" method="post">
                        <input type="hidden" name="department_id" id="edit_department_id">
                        <div class="row g-3">

                            <!-- Department Name -->
                            <div class="col-lg-3">
                                <label class="form-label">Department Name</label>
                                <input type="text" name="department_name" class="form-control" placeholder="Enter department name" required>
                            </div>

                            <div class="col-lg-4 ">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" id="cancel_btn" class="btn btn-secondary ms-2"> Cancel </button>
                                    <button type="submit" name="add_department" id="submit_btn" class="btn btn-primary"> Add Department </button>
                                    <button type="submit" name="update_department" id="update_btn" class="btn btn-success d-none"> Update Department </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================== -->

            <div class="card" id="add_department_list">
 
                <div class="card-header justify-content-between">
                     <h5 class="card-title">Department List </h5>
                    <button type="button" id="add_department_btn" class="btn btn-primary"> Add Department </button>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th>Department Name</th>
                                <th>Department Slug</th>
                                <th style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $org_id = $_SESSION['organization_id'];
                            // Assume department_listing table exists or will be created
                            $sql = "SELECT d.*, 
                                    (SELECT COUNT(e.employee_id) FROM employees e WHERE e.department_id = d.department_id) as usage_count 
                                    FROM department_listing d 
                                    WHERE d.organization_id = $org_id 
                                    ORDER BY d.department_id DESC ";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                  
                                    $is_used = $row['usage_count'] > 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(ucwords(strtolower($row['department_name']))) ?></td>
                                        <td><?= htmlspecialchars($row['department_slug']) ?></td>
                                        <td class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info me-2" 
                                                    data-id="<?= $row['department_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['department_name']) ?>" 
                                                    onclick="editDepartment(this)">
                                                <i class="ti ti-edit me-1"></i> Edit
                                            </button>

                                            <?php if ($is_used): ?>
                                                <button class="btn btn-sm btn-danger disabled" title="Cannot delete: Assigned to <?= $row['usage_count'] ?> employees">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <a href="<?= $basePath ?>/controller/masters/departments/delete_department.php?id=<?= $row['department_id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this department?');">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr>
                                        <td colspan="3" class="text-center">No departments found.</td>
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
        document.getElementById('add_department_form').classList.add('d-none');
        document.getElementById('add_department_list').classList.remove('d-none');
    });

    // Toggle Add Department Form
    document.getElementById('add_department_btn').addEventListener('click', function() {
        const formCard = document.getElementById('add_department_form');
        const add_department_list = document.getElementById('add_department_list');
         

        if (formCard.classList.contains('d-none')) {
            resetForm();
            formCard.classList.remove('d-none');
            add_department_list.classList.add('d-none');
        } else {
            formCard.classList.add('d-none');
            add_department_list.classList.remove('d-none');
        }
    });

    function editDepartment(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');

        // Show form
        const formCard = document.getElementById('add_department_form');
        const add_department_list = document.getElementById('add_department_list');

        formCard.classList.remove('d-none');
        add_department_list.classList.add('d-none');

        // Populate fields
        document.getElementById('edit_department_id').value = id;
        document.querySelector('input[name="department_name"]').value = name;

        // Change Form Action & Button
        const form = document.getElementById('department_form');
        form.action = '<?= $basePath ?>/controller/masters/departments/update_department.php';

        document.querySelector('.card-title').innerText = 'Edit Department';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('department_form').reset();
        document.getElementById('department_form').action = '<?= $basePath ?>/controller/masters/departments/add_department.php';
        document.getElementById('edit_department_id').value = '';
        document.querySelector('.card-title').innerText = 'Add Department';
        document.getElementById('submit_btn').classList.remove('d-none');
        document.getElementById('update_btn').classList.add('d-none');
    }
</script>
