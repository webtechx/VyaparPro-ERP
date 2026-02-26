<?php
$title = 'Loyalty Point Slabs';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Slab Form Card (Initially Hidden) -->
            <div class="card d-none" id="add_slab_form">
                <div class="card-header">
                    <h5 class="card-title" id="formTitle">Add New Slab</h5>
                </div>
                <div class="card-body">
                    <form id="slab_form" method="post" onsubmit="event.preventDefault(); saveSlab();">
                        <input type="hidden" name="slab_id" id="slab_id">
                        <input type="hidden" name="action" id="form_action" value="add">
                        
                        <div class="row g-3">
                            <!-- Hidden Slab No for Updates -->
                            <input type="hidden" name="slab_no" id="slab_no">

                            <div class="col-md-2">
                                <label class="form-label">From Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="from_sale_amount" id="from_sale_amount" class="form-control" required step="0.01">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="to_sale_amount" id="to_sale_amount" class="form-control" required step="0.01">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Points per ₹100 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="points_per_100_rupees" id="points_per_100_rupees" class="form-control" required step="0.01">
                                </div>
                                
                            </div>
                           
                            <div class="col-md-2">
                                <label class="form-label">Applicable From <span class="text-danger">*</span></label>
                                <input type="date" name="applicable_from_date" id="applicable_from_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Applicable To</label>
                                <input type="date" name="applicable_to_date" id="applicable_to_date" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Valid For Points (Days)</label>
                                <input type="number" name="valid_for_days" id="valid_for_days" class="form-control" placeholder="Calculated automatically" readonly>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" id="cancel_btn" class="btn btn-secondary me-2"> Cancel </button>
                            <button type="submit" id="save_slab_btn" class="btn btn-primary"> Save Slab </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Card -->
             <div class="card" id="slab_list">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Loyalty Slabs List</h5>
                    <button type="button" id="add_slab_btn" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Add New Slab
                    </button>
                </div>
                <div class="card-body">
                    
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="thead-sm text-uppercase fs-xxs">
                                <tr>
                                    <th>Slab</th>
                                    <th>Range (₹)</th>
                                    <th>Points / ₹100</th>
                                    <th>Validity</th>
                                    <th>Active Period</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM loyalty_point_slabs WHERE organization_id = " . $_SESSION['organization_id'] . " ORDER BY from_sale_amount ASC";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['slab_no']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    ₹<?= number_format($row['from_sale_amount'], 2) ?> - ₹<?= number_format($row['to_sale_amount'], 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-xs rounded-circle bg-warning-subtle  me-2 d-flex justify-content-center align-items-center" style="color: rgb(233 171 16) !important;"><i class="ti ti-coin"></i></span>
                                                    <span class="fw-bold">₹<?= htmlspecialchars($row['points_per_100_rupees']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $row['valid_for_days'] ? $row['valid_for_days'] . ' Days' : '<span class="text-success">Lifetime</span>' ?>
                                            </td>
                                            <td>
                                                <small class="d-block text-dark">From: <?= date('d M Y', strtotime($row['applicable_from_date'])) ?></small>
                                                <?php if($row['applicable_to_date']): ?>
                                                    <small class="d-block text-dark">To: <?= date('d M Y', strtotime($row['applicable_to_date'])) ?></small>
                                                <?php else: ?>
                                                    <small class="d-block text-success">Until Revoked</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-warning" onclick='editSlab(<?= json_encode($row) ?>)' title="Edit">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSlab(<?= $row['slab_id'] ?>)" title="Delete">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
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

<?php ob_start(); ?>
<script>
    const basePath = '<?= $basePath ?>';

    // Toggle logic
    document.getElementById('add_slab_btn').addEventListener('click', function() {
        openSlabForm('add');
    });

    document.getElementById('cancel_btn').addEventListener('click', function() {
        document.getElementById('add_slab_form').classList.add('d-none');
        document.getElementById('slab_list').classList.remove('d-none');
    });

    function openSlabForm(mode) {
        document.getElementById('slab_form').reset();
        document.getElementById('add_slab_form').classList.remove('d-none');
        document.getElementById('slab_list').classList.add('d-none');
        
        if (mode === 'add') {
             document.getElementById('slab_id').value = '';
             document.getElementById('slab_no').value = '';
             document.getElementById('form_action').value = 'add';
             document.getElementById('formTitle').innerText = 'Add New Slab';
             document.getElementById('save_slab_btn').innerText = 'Save Slab';
             // Default dates
             document.getElementById('applicable_from_date').value = new Date().toISOString().split('T')[0];
        }
    }

    function editSlab(data) {
        openSlabForm('update');
        document.getElementById('formTitle').innerText = 'Edit Slab';
        document.getElementById('form_action').value = 'update';
        document.getElementById('save_slab_btn').innerText = 'Update Slab';
        
        document.getElementById('slab_id').value = data.slab_id;
        document.getElementById('slab_no').value = data.slab_no;
        document.getElementById('from_sale_amount').value = data.from_sale_amount;
        document.getElementById('to_sale_amount').value = data.to_sale_amount;
        document.getElementById('points_per_100_rupees').value = data.points_per_100_rupees;
        document.getElementById('valid_for_days').value = data.valid_for_days;
        document.getElementById('applicable_from_date').value = data.applicable_from_date;
        document.getElementById('applicable_to_date').value = data.applicable_to_date;
    }
    
    function saveSlab() {
        const form = document.getElementById('slab_form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Basic Validation: To Amount > From Amount
        const fromAmt = parseFloat(document.getElementById('from_sale_amount').value);
        const toAmt = parseFloat(document.getElementById('to_sale_amount').value);
        if(toAmt <= fromAmt) {
             alert('Error: "To Amount" must be greater than "From Amount".');
             return;
        }

        const formData = new FormData(form);
        const btn = document.getElementById('save_slab_btn');
        btn.disabled = true;
        const originalText = btn.innerText;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        fetch(basePath + '/controller/loyalty/save_slab.php', {
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

    function deleteSlab(id) {
         if(confirm('Are you sure you want to delete this slab?')) {
            fetch(basePath + '/controller/loyalty/delete_slab.php', {
                method: 'POST',
                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'slab_id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }

    // Auto Calculation of Days
    function calculateDays() {
        const fromDateStr = document.getElementById('applicable_from_date').value;
        const toDateStr = document.getElementById('applicable_to_date').value;
        
        if (fromDateStr && toDateStr) {
            const fromDate = new Date(fromDateStr);
            const toDate = new Date(toDateStr);
            
            // Reset to midnight
            fromDate.setHours(0,0,0,0);
            toDate.setHours(0,0,0,0);

            if (toDate > fromDate) { // Just strictly greater for logic, or >=
                const diffTime = toDate - fromDate; 
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                document.getElementById('valid_for_days').value = diffDays;
            } else if (toDate.getTime() === fromDate.getTime()) {
                 document.getElementById('valid_for_days').value = 0; // Same day
            } else {
                 document.getElementById('valid_for_days').value = ''; // Invalid range
            }
        } else {
             // document.getElementById('valid_for_days').value = ''; // Keep existing if one date removed? Or clear? Safe to clear or leave.
        }
    }

    document.getElementById('applicable_from_date').addEventListener('change', calculateDays);
    document.getElementById('applicable_to_date').addEventListener('change', calculateDays);
</script>
<?php 
$extra_scripts = ob_get_clean();
?>
