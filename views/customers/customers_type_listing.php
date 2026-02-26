<?php
$title = 'Customers Type';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <!-- Type Form Card (Initially Hidden) -->
            <div class="card d-none" id="add_type_form">
                <div class="card-header">
                    <h5 class="card-title" id="formTitle">Add Customer Type</h5>
                </div>

                <div class="card-body">
                    <form id="type_form" method="post" onsubmit="event.preventDefault(); saveType();">
                        <input type="hidden" name="customers_type_id" id="customers_type_id">
                        <input type="hidden" name="action" id="form_action" value="add">
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <label class="form-label">Type Name <span class="text-danger">*</span></label>
                                <input type="text" name="customers_type_name" id="customers_type_name" class="form-control" placeholder="Enter type name" required>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" id="cancel_btn" class="btn btn-secondary ms-2"> Cancel </button>
                                    <button type="submit" id="save_type_btn" class="btn btn-primary"> Add Type </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Card -->
            <div class="card" id="add_type_list">
                <div class="card-header d-flex justify-content-between">
                     <h5 class="card-title" id="formTitle">Customer Types</h5>
                     <div class="d-flex gap-2">
                         <a href="<?= $basePath ?>/controller/customers/export_customer_types_excel.php" class="btn btn-success">
                             <i class="ti ti-download me-1"></i> Export Excel
                         </a>
                         <button type="button" id="add_type_btn" class="btn btn-primary"> Add Type </button>
                     </div>
                </div>
 
                <div class="card-body">
 

                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th>Type Name</th>
                                <th style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT ct.*, 
                                    (SELECT COUNT(*) FROM customers_listing cl WHERE cl.customers_type_id = ct.customers_type_id) as customer_count,
                                    (SELECT COUNT(*) FROM item_commissions ic WHERE ic.customers_type_id = ct.customers_type_id) as commission_count
                                    FROM customers_type_listing ct 
                                    WHERE ct.organization_id = " . $_SESSION['organization_id'] . " 
                                    ORDER BY ct.customers_type_id DESC";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $usageMsg = [];
                                    if ($row['customer_count'] > 0) $usageMsg[] = $row['customer_count'] . " Customer(s)";
                                    if ($row['commission_count'] > 0) $usageMsg[] = $row['commission_count'] . " Item Commission(s)";
                                    
                                    $isUsed = !empty($usageMsg);
                                    $deleteTitle = $isUsed ? "Cannot delete: Used in " . implode(', ', $usageMsg) : "Delete Type";
                                    ?>
                                    <tr>
                                        <td><?= ucwords(strtolower($row['customers_type_name'])) ?></td>
                                        <td class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info me-2" 
                                                    onclick='editType(<?= json_encode($row) ?>)'>
                                                <i class="ti ti-edit me-1"></i> Edit
                                            </button>
                                            
                                            <?php if ($isUsed): ?>
                                                <button class="btn btn-sm btn-danger disabled" disabled title="<?= htmlspecialchars($deleteTitle) ?>">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <a href="javascript:void(0)" onclick="deleteType(<?= $row['customers_type_id'] ?>)" 
                                                   class="btn btn-sm btn-danger" title="Delete Type">
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
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
    const basePath = '<?= $basePath ?>';

    // Toggle Add/List Views
    document.getElementById('add_type_btn').addEventListener('click', function() {
        document.getElementById('type_form').reset();
        document.getElementById('customers_type_id').value = '';
        document.getElementById('form_action').value = 'add';
        document.getElementById('formTitle').innerText = 'Add Customer Type';
        document.getElementById('save_type_btn').innerText = 'Add Type';
        
        document.getElementById('add_type_form').classList.remove('d-none');
        document.getElementById('add_type_list').classList.add('d-none');
    });

    document.getElementById('cancel_btn').addEventListener('click', function() {
        document.getElementById('add_type_form').classList.add('d-none');
        document.getElementById('add_type_list').classList.remove('d-none');
    });

    function editType(type) {
        document.getElementById('customers_type_name').value = type.customers_type_name;
        document.getElementById('customers_type_id').value = type.customers_type_id;
        document.getElementById('form_action').value = 'update';
        document.getElementById('formTitle').innerText = 'Edit Customer Type';
        document.getElementById('save_type_btn').innerText = 'Update Type';

        document.getElementById('add_type_form').classList.remove('d-none');
        document.getElementById('add_type_list').classList.add('d-none');
    }

    function saveType() {
        const form = document.getElementById('type_form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const btn = document.getElementById('save_type_btn');
        btn.disabled = true;
        const originalText = btn.innerText;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        fetch(basePath + '/controller/customers/save_customer_type.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = window.location.pathname + '?success=' + encodeURIComponent(data.message);
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerText = originalText;
            }
        })
        .catch(error => {
            alert('An error occurred: ' + error);
            btn.disabled = false;
            btn.innerText = originalText;
        });
    }

    function deleteType(id) {
        if(confirm('Are you sure you want to delete this type?')) {
            fetch(basePath + '/controller/customers/delete_customer_type.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'customers_type_id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = window.location.pathname + '?success=' + encodeURIComponent(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
</script>
<?php 
$extra_scripts = ob_get_clean();
?>
