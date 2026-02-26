<?php
$title = 'Employees';
?>

<!-- ============================================================== -->
<!-- Start Main Content -->
<!-- ============================================================== -->

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <!-- Add/Edit Employee Form -->
            <div class="card d-none" id="add_emp_form">
                <div class="card-header">
                    <h5 class="card-title">Add Employee </h5>
                </div>

                <div class="card-body">
                    <form id="emp_form" action="<?= $basePath ?>/controller/masters/employees/add_employee.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="employee_id" id="edit_employee_id">

                        <div class="row g-3">
                            <div class="col-md-1 text-center ">
                                <div class="position-relative d-inline-block ">
                                    <img id="edit_avatar_preview" src="<?= $basePath ?>/public/assets/images/users/default_user_image.png" 
                                         class="rounded-circle object-fit-cover shadow-sm border border-2 border-white bg-secondary-subtle" 
                                         style="width: 60px; height: 60px;"
                                         alt="Avatar Preview">
                                    <label for="edit_employee_image" class="position-absolute bottom-0 end-0 bg-primary text-white p-1 rounded-circle cursor-pointer shadow-sm" 
                                           style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transform: translate(10%, 10%);"
                                           title="Upload Avatar">
                                        <i class="ti ti-camera"></i>
                                    </label>
                                    <input type="file" name="employee_image" id="edit_employee_image" class="d-none" accept="image/*" onchange="previewAvatar(this)">
                                </div>
                            </div>

                            <!-- Basic Information -->
                             <div class="col-md-1">
                                <label class="form-label">Salutation</label>
                                <select name="salutation" class="form-select">
                                    <option value="Mr.">Mr.</option>
                                    <option value="Ms.">Ms.</option>
                                    <option value="Mrs.">Mrs.</option>
                                    <option value="Dr.">Dr.</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Primary Email <span class="text-danger">*</span></label>
                                <input type="email" name="primary_email" class="form-control" placeholder="Primary Email" required>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select" required>
                                    <option value="">Select Department</option>
                                    <?php
                                    $sql = "SELECT department_id, department_name FROM department_listing";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['department_id'] . '">' . htmlspecialchars($row['department_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <?php
                                    $sql = "SELECT role_id, role_name FROM roles_listing";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['role_id'] . '">' . htmlspecialchars($row['role_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Designation <span class="text-danger">*</span></label>
                                <select name="designation_id" class="form-select" required>
                                    <option value="">Select Designation</option>
                                    <?php
                                    $sql = "SELECT designation_id, designation_name FROM designation_listing";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['designation_id'] . '">' . htmlspecialchars($row['designation_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                             <div class="col-md-3">
                                <label class="form-label">Enrollment Type</label>
                                <select name="enrollment_type" class="form-select">
                                    <option value="Permanent">Permanent</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Probation">Probation</option>
                                    <option value="Intern">Intern</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Employment Status</label>
                                <select name="employment_status" class="form-select">
                                    <option value="Hired">Hired</option>
                                    <option value="Joined">Joined</option>
                                    <option value="Left">Left</option>
                                </select>
                            </div>
                           

                            
                            <!-- Login Active (Moved) -->
                            <div class="col-lg-2">
                                <label class="form-label">Login Active</label>
                                <div class="form-check form-switch form-check-success fs-xxl">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input mt-1" id="switchSuccess" checked>
                                    <label class="form-check-label fs-base" for="switchSuccess">Active</label>
                                </div>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div class="row mt-4">
                            <div class="col-xl-12">
                                <ul class="nav nav-tabs nav-justified nav-bordered nav-bordered-primary mb-3" role="tablist">
                                    <li class="nav-item">
                                        <a href="#personal-details" data-bs-toggle="tab" class="nav-link active">
                                            <i class="ti ti-user fs-lg me-1"></i> Personal Details
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#addresses" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-map-pin fs-lg me-1"></i> Addresses
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bank-details" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-building-bank fs-lg me-1"></i> Bank Details
                                        </a>
                                    </li>
                                     <li class="nav-item">
                                        <a href="#remarks-tab" data-bs-toggle="tab" class="nav-link">
                                            <i class="ti ti-notes fs-lg me-1"></i> Remarks
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Personal Details Tab -->
                                    <div class="tab-pane show active" id="personal-details">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Primary Phone</label>
                                                <input type="text" name="primary_phone" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Alternate Phone</label>
                                                <input type="text" name="alternate_phone" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Alternate Email</label>
                                                <input type="email" name="alternate_email" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Reference Phone No</label>
                                                <input type="text" name="ref_phone_no" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                                <input type="date" name="date_of_birth" class="form-control" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Joined On</label>
                                                <input type="date" name="joined_on" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Gender</label>
                                                <select name="gender" class="form-select">
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Blood Group</label>
                                                <select name="blood_group" class="form-select">
                                                    <option value="">Select</option>
                                                    <option value="A+">A+</option>
                                                    <option value="A-">A-</option>
                                                    <option value="B+">B+</option>
                                                    <option value="B-">B-</option>
                                                    <option value="AB+">AB+</option>
                                                    <option value="AB-">AB-</option>
                                                    <option value="O+">O+</option>
                                                    <option value="O-">O-</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Father's Name</label>
                                                <input type="text" name="father_name" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Mother's Name</label>
                                                <input type="text" name="mother_name" class="form-control">
                                            </div>
                                            
                                         
                                            <div class="col-md-3">
                                                <label class="form-label">PAN Card</label>
                                                <input type="text" name="pan" class="form-control text-uppercase">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Aadhar Card</label>
                                                <input type="text" name="aadhar" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Voter ID</label>
                                                <input type="text" name="voter_id" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Document Attachment</label>
                                                <input type="file" name="document_attachment" class="form-control">
                                                <div id="existing_doc_link" class="form-text d-none">
                                                    <a href="#" target="_blank" class="text-primary"><i class="ti ti-paperclip"></i> View Document</a>
                                                </div>
                                            </div>

                                            <div class="col-12"><hr></div>
                                            <div class="col-12"><h6>Emergency Contact</h6></div>

                                            <div class="col-md-6">
                                                <label class="form-label">Contact Name</label>
                                                <input type="text" name="emergency_contact_name" class="form-control">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Contact Phone</label>
                                                <input type="text" name="emergency_contact_phone" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Addresses Tab -->
                                    <div class="tab-pane" id="addresses">
                                        <div class="row g-3">
                                            <!-- Current Address -->
                                            <div class="col-lg-6 border-end">
                                                <h6 class="mb-3">Current Address</h6>
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <label class="form-label">Street/Building</label>
                                                        <input type="text" name="current_street" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">City</label>
                                                        <input type="text" name="current_city" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">District</label>
                                                        <input type="text" name="current_district" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">State</label>
                                                        <input type="text" name="current_state" class="form-control">
                                                    </div>
                                                     <div class="col-md-6">
                                                        <label class="form-label">Pincode</label>
                                                        <input type="text" name="current_pincode" class="form-control">
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="form-label">Country</label>
                                                        <input type="text" name="current_country" class="form-control" value="India">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Permanent Address -->
                                             <div class="col-lg-6">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="mb-0">Permanent Address</h6>
                                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="copyCurrentToPermanent()">
                                                        <i class="ti ti-copy"></i> Same as Current
                                                    </button>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <label class="form-label">Street/Building</label>
                                                        <input type="text" name="permanent_street" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">City</label>
                                                        <input type="text" name="permanent_city" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">District</label>
                                                        <input type="text" name="permanent_district" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">State</label>
                                                        <input type="text" name="permanent_state" class="form-control">
                                                    </div>
                                                     <div class="col-md-6">
                                                        <label class="form-label">Pincode</label>
                                                        <input type="text" name="permanent_pincode" class="form-control">
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="form-label">Country</label>
                                                        <input type="text" name="permanent_country" class="form-control" value="India">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Details Tab -->
                                    <div class="tab-pane" id="bank-details">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Bank Name</label>
                                                <input type="text" name="bank_name" class="form-control">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Branch Name</label>
                                                <input type="text" name="branch_name" class="form-control">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">IFSC Code</label>
                                                <input type="text" name="ifsc_code" class="form-control">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Account Number</label>
                                                <input type="text" name="account_number" class="form-control">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Account Type</label>
                                                <select name="account_type" class="form-select">
                                                    <option value="Savings">Savings</option>
                                                    <option value="Current">Current</option>
                                                    <option value="Salary">Salary</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remarks Tab -->
                                    <div class="tab-pane" id="remarks-tab">
                                        <div class="row">
                                            <div class="col-12">
                                                <label class="form-label">Notes / Remarks</label>
                                                <textarea name="notes" class="form-control" rows="5"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" id="cancel_btn" class="btn btn-secondary me-2">Cancel</button>
                            <button type="submit" name="add_employee" id="submit_btn" class="btn btn-primary">Save Employee</button>
                            <button type="submit" name="update_employee" id="update_btn" class="btn btn-success d-none">Update Employee</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List View -->
            <div class="card" id="add_emp_list">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Employees List </h5>
                    <div class="d-flex gap-2">
                        <a href="<?= $basePath ?>/controller/masters/employees/export_employees_excel.php" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-info" onclick="printEmployeesTable()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                        <button type="button" id="add_emp_btn" class="btn btn-primary" <?= can_access('employees', 'add') ? '' : 'disabled title="Access Denied"' ?>> Add Employee </button>
                    </div>
                </div>

                <div class="card-body">
                    <table data-tables="basic" class="table table-striped dt-responsive align-middle mb-0" style="width: 100%;">
                        <thead class="bg-light text-uppercase fs-xs">
                            <tr>
                                <th class="py-2">Name</th>
                                <th class="py-2">Department </th>
                        
                                <th class="py-2">Contact</th>
                               
                                <th class="py-2">Emp Status</th>
                                <th class="py-2">Account</th>
                                <th style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all employees that have constraints (referenced in other transactional tables)
                            $restricted_employees = [];
                            $chk_query = "
                                SELECT make_employee_id AS emp_id FROM sales_invoices WHERE make_employee_id IS NOT NULL
                                UNION
                                SELECT sales_employee_id AS emp_id FROM sales_invoices WHERE sales_employee_id IS NOT NULL
                                UNION
                                SELECT make_employee_id AS emp_id FROM proforma_invoices WHERE make_employee_id IS NOT NULL
                                UNION
                                SELECT sales_employee_id AS emp_id FROM proforma_invoices WHERE sales_employee_id IS NOT NULL
                                UNION
                                SELECT employee_id AS emp_id FROM incentive_ledger WHERE employee_id IS NOT NULL
                                UNION
                                SELECT created_by AS emp_id FROM payment_received WHERE created_by IS NOT NULL
                                UNION
                                SELECT created_by AS emp_id FROM payment_made WHERE created_by IS NOT NULL
                                UNION
                                SELECT created_by AS emp_id FROM credit_notes WHERE created_by IS NOT NULL
                            ";
                            $chk_res = $conn->query($chk_query);
                            if ($chk_res) {
                                while ($c_row = $chk_res->fetch_assoc()) {
                                    $restricted_employees[] = $c_row['emp_id'];
                                }
                            }
                            $restricted_employees = array_unique($restricted_employees);

                            $query = "SELECT e.*, r.role_name, d.designation_name, o.organizations_code, dep.department_name 
                                      FROM employees e 
                                      LEFT JOIN roles_listing r ON e.role_id = r.role_id 
                                      LEFT JOIN designation_listing d ON e.designation_id = d.designation_id 
                                      LEFT JOIN department_listing dep ON e.department_id = dep.department_id 
                                      LEFT JOIN organizations o ON e.organization_id = o.organization_id 
                                      ORDER BY e.created_at DESC";
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0) {
                                $has_del_access = can_access('employees', 'delete');
                                while ($row = $result->fetch_assoc()) {
                                    $is_referenced = in_array($row['employee_id'], $restricted_employees);
                                    $can_del = $has_del_access && !$is_referenced;
                                    
                                    $statusBadge = $row['is_active'] 
                                        ? '<span class="badge badge-label badge-soft-success">Active</span>' 
                                        : '<span class="badge badge-label badge-soft-danger">Inactive</span>';
                                    
                                    $avatarHtml = '';
                                    if (!empty($row['employee_image'])) {
                                        $orgCode = $row['organizations_code'];
                                        $avatarHtml = '<img src="'.$basePath.'/uploads/'.$orgCode.'/employees/avatars/'.$row['employee_image'].'" class="rounded-circle avatar-sm me-2">';
                                    } else {
                                        $avatarHtml = '<div class="avatar-sm me-2 d-flex align-items-center justify-content-center bg-light text-primary fw-bold rounded-circle">'.strtoupper(substr($row['first_name'], 0, 1)).'</div>';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?= $avatarHtml ?>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"><?= htmlspecialchars(ucwords(strtolower($row['salutation'] . ' ' . $row['first_name'] . ' ' . $row['last_name']))) ?></span>
                                                    <small class="text-dark"><?= htmlspecialchars($row['designation_name'] ?? '-') ?> (<?= htmlspecialchars($row['employee_code'] ?? '') ?>)</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold"><i class="ti ti-building me-1"></i><?= htmlspecialchars(ucwords(strtolower($row['department_name'] ?? '-'))) ?></span>
                                                <small class="text-dark"><i class="ti ti-id-badge-2 me-1"></i>Role: <?= htmlspecialchars($row['role_name'] ?? '-') ?></small>
                                            </div>
                                        </td>
                                   
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold"><i class="ti ti-mail me-1"></i><?= htmlspecialchars($row['primary_email']) ?></span>
                                                <small class="text-dark"><i class="ti ti-phone me-1"></i><?= htmlspecialchars($row['primary_phone']) ?></small>
                                            </div>
                                        </td>
                                       
                                        <td>
                                            <span class="badge badge-label badge-soft-info"><?= htmlspecialchars($row['employment_status'] ?? 'Joined') ?></span>
                                        </td>
                                        <td><?= $statusBadge ?></td>
                                        <td class="d-flex gap-1">
                                            <button class="btn btn-sm btn-light" onclick="viewEmployee(<?= $row['employee_id'] ?>)" title="View Details">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-info" 
                                                <?= can_access('employees', 'edit') ? 'onclick="editEmployee(' . $row['employee_id'] . ')"' : 'disabled title="Access Denied"' ?>>
                                                Edit
                                            </button>

                                            <a href="<?= $can_del ? $basePath . '/controller/masters/employees/delete_employee.php?id=' . $row['employee_id'] : 'javascript:void(0);' ?>" 
                                               class="btn btn-sm btn-danger <?= $can_del ? '' : 'disabled' ?>" 
                                               <?= $can_del ? 'onclick="return confirm(\'Delete?\')"' : ($is_referenced ? 'title="Cannot delete: employee is referenced in other records"' : 'title="Access Denied"') ?>>
                                                Delete
                                            </a>
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

<script>
    // Copy Address Logic
    function copyCurrentToPermanent(){
        $('input[name="permanent_street"]').val($('input[name="current_street"]').val());
        $('input[name="permanent_city"]').val($('input[name="current_city"]').val());
        $('input[name="permanent_district"]').val($('input[name="current_district"]').val());
        $('input[name="permanent_state"]').val($('input[name="current_state"]').val());
        $('input[name="permanent_pincode"]').val($('input[name="current_pincode"]').val());
        $('input[name="permanent_country"]').val($('input[name="current_country"]').val());
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Employees Listing JS Loaded');
        
        // Ensure BasePath is correct
        const form = document.getElementById('emp_form');
        if(form && form.action.includes('<?= $basePath ?>') === false){
             console.warn('Base path might be missing in action');
        }

        // Toggle view listeners
        const addBtn = document.getElementById('add_emp_btn');
        const cancelBtn = document.getElementById('cancel_btn');
        
        if(addBtn){
            addBtn.addEventListener('click', () => {
                console.log('Add Employee Clicked');
                resetForm();
                toggleView(true);
            });
        } else {
            console.error('Add Button not found');
        }

        if(cancelBtn){
            cancelBtn.addEventListener('click', () => {
                console.log('Cancel Clicked');
                toggleView(false);
            });
        }
    });

    // View Switching
    function toggleView(showForm){
        const formCard = document.getElementById('add_emp_form');
        const listCard = document.getElementById('add_emp_list');
        
        if(showForm){
            if(formCard) formCard.classList.remove('d-none');
            if(listCard) listCard.classList.add('d-none');
        } else {
            if(formCard) formCard.classList.add('d-none');
            if(listCard) listCard.classList.remove('d-none');
        }
    }

    function resetForm(){
        const form = document.getElementById('emp_form');
        if(form) {
            form.reset();
            // Reset Action to Add
            form.action = '<?= $basePath ?>/controller/masters/employees/add_employee.php';
        }
        
        // Reset Avatar
        const avatarPreview = document.getElementById('edit_avatar_preview');
        if(avatarPreview) avatarPreview.src = '<?= $basePath ?>/public/assets/images/users/default_user_image.png';
        
        const idInput = document.getElementById('edit_employee_id');
        if(idInput) idInput.value = '';
        
        const title = document.querySelector('.card-title');
        if(title) title.innerText = 'Add Employee';
        
        const subBtn = document.getElementById('submit_btn');
        const updBtn = document.getElementById('update_btn');
        if(subBtn) subBtn.classList.remove('d-none');
        if(updBtn) updBtn.classList.add('d-none');
        
        const sw = document.getElementById('switchSuccess');
        if(sw) sw.checked = true;

        const codeInput = document.querySelector('input[name="employee_code"]');
        if(codeInput) codeInput.value = '';

        // Reset new fields
        document.getElementById('existing_doc_link').classList.add('d-none');
        $('input[name="ref_phone_no"]').val('');
        $('select[name="blood_group"]').val('');
    }

    function editEmployee(id){
        toggleView(true);
        document.querySelector('.card-title').innerText = 'Edit Employee';
        document.getElementById('edit_employee_id').value = id;
        document.getElementById('emp_form').action = '<?= $basePath ?>/controller/masters/employees/update_employee.php';
        document.getElementById('submit_btn').classList.add('d-none');
        document.getElementById('update_btn').classList.remove('d-none');

        // Fetch Details
        fetch(`<?= $basePath ?>/controller/masters/employees/get_employee_details.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.error){ alert(data.error); return; }
                const e = data.employee; // Expecting employee object
                
                // --- Basic Info ---
                $('select[name="salutation"]').val(e.salutation);
                $('input[name="first_name"]').val(e.first_name);
                $('input[name="last_name"]').val(e.last_name);
                $('input[name="primary_email"]').val(e.primary_email);
                $('select[name="department_id"]').val(e.department_id);
                $('select[name="role_id"]').val(e.role_id);
                $('select[name="designation_id"]').val(e.designation_id);
                $('input[name="employee_code"]').val(e.employee_code);
                $('select[name="enrollment_type"]').val(e.enrollment_type);
                $('select[name="employment_status"]').val(e.employment_status);
                
                $('#switchSuccess').prop('checked', e.is_active == 1);

                // --- Personal Details ---
                $('input[name="primary_phone"]').val(e.primary_phone);
                $('input[name="alternate_phone"]').val(e.alternate_phone);
                $('input[name="alternate_email"]').val(e.alternate_email);
                $('input[name="ref_phone_no"]').val(e.ref_phone_no);
                
                $('input[name="date_of_birth"]').val(e.date_of_birth);
                $('input[name="joined_on"]').val(e.joined_on);
                $('select[name="gender"]').val(e.gender);
                $('select[name="blood_group"]').val(e.blood_group);

                $('input[name="father_name"]').val(e.father_name);
                $('input[name="mother_name"]').val(e.mother_name);
                $('input[name="pan"]').val(e.pan);
                $('input[name="aadhar"]').val(e.aadhar);
                $('input[name="voter_id"]').val(e.voter_id);
                
                if(e.document_attachment){
                     const docLink = document.getElementById('existing_doc_link');
                     docLink.classList.remove('d-none');
                     // Ensure organizations_code is part of the path
                     const orgCode = data.employee.organizations_code; 
                     docLink.querySelector('a').href = `<?= $basePath ?>/uploads/${orgCode}/employees/docs/${e.document_attachment}`;
                } else {
                     document.getElementById('existing_doc_link').classList.add('d-none');
                }

                $('input[name="emergency_contact_name"]').val(e.emergency_contact_name);
                $('input[name="emergency_contact_phone"]').val(e.emergency_contact_phone);
                $('textarea[name="notes"]').val(e.notes);
                
                // Avatar
                const avatarPreview = document.getElementById('edit_avatar_preview');
                if(avatarPreview) {
                    const orgCode = data.employee.organizations_code;
                    avatarPreview.src = e.employee_image 
                        ? `<?= $basePath ?>/uploads/${orgCode}/employees/avatars/${e.employee_image}` 
                        : '<?= $basePath ?>/public/assets/images/users/default_user_image.png'; 
                }

                // --- Addresses ---
                if(data.address){
                    const a = data.address;
                    $('input[name="current_street"]').val(a.current_street);
                    $('input[name="current_city"]').val(a.current_city);
                    $('input[name="current_district"]').val(a.current_district);
                    $('input[name="current_state"]').val(a.current_state);
                    $('input[name="current_pincode"]').val(a.current_pincode);
                    $('input[name="current_country"]').val(a.current_country);

                    $('input[name="permanent_street"]').val(a.permanent_street);
                    $('input[name="permanent_city"]').val(a.permanent_city);
                    $('input[name="permanent_district"]').val(a.permanent_district);
                    $('input[name="permanent_state"]').val(a.permanent_state);
                    $('input[name="permanent_pincode"]').val(a.permanent_pincode);
                    $('input[name="permanent_country"]').val(a.permanent_country);
                } else {
                    // Clear address inputs if no address data
                     $('#addresses input').val('');
                     $('input[name="current_country"]').val('India'); 
                     $('input[name="permanent_country"]').val('India');
                }

                // --- Bank ---
                if(data.bank){
                    const b = data.bank;
                    $('input[name="bank_name"]').val(b.bank_name);
                    $('input[name="branch_name"]').val(b.branch_name);
                    $('input[name="account_number"]').val(b.account_number);
                    $('input[name="ifsc_code"]').val(b.ifsc_code);
                    $('select[name="account_type"]').val(b.account_type);
                } else {
                    $('#bank-details input').val('');
                }

            })
            .catch(err => console.error(err));
    }

    function viewEmployee(id) {
        // Fetch Details
        fetch(`<?= $basePath ?>/controller/masters/employees/get_employee_details.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.error){ alert(data.error); return; }
                const e = data.employee;
                
                const orgCode = e.organizations_code;
                
                let html = `
                    <div class="p-2">
                        <!-- Professional Header -->
                        <div class="d-flex align-items-center mb-4 pb-4 border-bottom">
                            ${e.employee_image ? `<img src="<?= $basePath ?>/uploads/${orgCode}/employees/avatars/${e.employee_image}" class="rounded-circle me-4 shadow-sm" style="width:90px; height:90px; object-fit:cover;">` : '<div class="bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center me-4 shadow-sm" style="width:90px; height:90px; font-size:2.5rem; font-weight:bold;">' + e.first_name.charAt(0) + '</div>'}
                            
                            <div class="flex-grow-1">
                                <h3 class="mb-1 text-dark fw-bold">${e.salutation} ${e.first_name} ${e.last_name}</h3>
                                <div class="mb-2">
                                     <span class="badge bg-primary px-3 py-2 fs-xs me-2">${e.role_name || '-'}</span>
                                     <span class="badge bg-warning px-3 py-2 fs-xs me-2 text-dark">${e.designation_name || '-'}</span>
                                     <span class="badge bg-light text-dark border px-3 py-2 fs-xs">${e.employee_code}</span>
                                </div>
                                <div class="text-muted d-flex align-items-center flex-wrap gap-3">
                                    <span><i class="ti ti-mail me-1"></i> ${e.primary_email}</span>
                                    <span><i class="ti ti-phone me-1"></i> ${e.primary_phone}</span>
                                    ${e.ref_phone_no ? `<span><i class="ti ti-phone-call me-1"></i> Ref: ${e.ref_phone_no}</span>` : ''}
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <div class="text-muted fs-xs text-uppercase fw-bold mb-1">Status</div>
                                <span class="badge badge-lg ${e.employment_status == 'Joined' ? 'bg-success' : 'bg-secondary'} fs-6 mb-2">${e.employment_status}</span>
                                <div class="text-muted fs-xs text-uppercase fw-bold mb-1">Account</div>
                                ${e.is_active == 1 ? '<span class="badge bg-soft-success text-success fw-bold">Active</span>' : '<span class="badge bg-soft-danger text-danger fw-bold">Inactive</span>'}
                            </div>
                        </div>

                        <!-- Personal Details Section -->
                        <div class="mb-4">
                            <h5 class="text-uppercase text-muted fs-xs fw-bolder ls-1 mb-3 border-bottom pb-2">
                                <i class="ti ti-user me-2"></i>Personal Information
                            </h5>
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Father's Name</label>
                                    <div class="text-dark fw-medium fs-6">${e.father_name || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Mother's Name</label>
                                    <div class="text-dark fw-medium fs-6">${e.mother_name || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Date of Birth</label>
                                    <div class="text-dark fw-medium fs-6">${e.date_of_birth || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Blood Group</label>
                                    <div class="text-dark fw-medium fs-6">${e.blood_group || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Gender</label>
                                    <div class="text-dark fw-medium fs-6">${e.gender || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Joined On</label>
                                    <div class="text-dark fw-medium fs-6">${e.joined_on || '-'}</div>
                                </div>
                                <div class="col-md-5">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Emergency Contact</label>
                                    <div class="text-dark fw-medium fs-6">${e.emergency_contact_name || '-'} <span class="text-muted ms-1">(${e.emergency_contact_phone || '-'})</span></div>
                                </div>
                            </div>
                            
                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1">PAN CARD</label>
                                        <span class="text-dark fw-bold font-monospace">${e.pan || '-'}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1">AADHAR NO</label>
                                        <span class="text-dark fw-bold font-monospace">${e.aadhar || '-'}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1">VOTER ID</label>
                                        <span class="text-dark fw-bold font-monospace">${e.voter_id || '-'}</span>
                                    </div>
                                     <div class="col-md-3">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1">DOCUMENT</label>
                                        ${e.document_attachment ? `<a href="<?= $basePath ?>/uploads/${orgCode}/employees/docs/${e.document_attachment}" target="_blank" class="fw-bold text-primary"><i class="ti ti-file-description me-1"></i>View File</a>` : '<span class="text-muted">-</span>'}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Addresses Section -->
                         <div class="mb-4">
                            <h5 class="text-uppercase text-muted fs-xs fw-bolder ls-1 mb-3 border-bottom pb-2">
                                <i class="ti ti-map-pin me-2"></i>Address Details
                            </h5>
                            ${data.address ? `
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-white h-100">
                                        <div class="badge bg-light text-dark mb-2">Current Address</div>
                                        <p class="mb-0 fs-6 text-dark leading-relaxed">
                                            <strong>${data.address.current_street}</strong><br>
                                            ${data.address.current_city}, ${data.address.current_district}<br>
                                            ${data.address.current_state} - <span class="fw-bold">${data.address.current_pincode}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                   <div class="p-3 border rounded bg-white h-100">
                                        <div class="badge bg-light text-dark mb-2">Permanent Address</div>
                                        <p class="mb-0 fs-6 text-dark leading-relaxed">
                                            <strong>${data.address.permanent_street}</strong><br>
                                            ${data.address.permanent_city}, ${data.address.permanent_district}<br>
                                            ${data.address.permanent_state} - <span class="fw-bold">${data.address.permanent_pincode}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>` : '<p class="text-muted fst-italic">No address details available.</p>'}
                        </div>

                        <!-- Bank Details Section -->
                        <div class="mb-4">
                            <h5 class="text-uppercase text-muted fs-xs fw-bolder ls-1 mb-3 border-bottom pb-2">
                                <i class="ti ti-building-bank me-2"></i>Bank Account
                            </h5>
                            ${data.bank ? `
                            <div class="card card-body bg-soft-info border-0">
                                <div class="row g-3">
                                     <div class="col-md-4">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Bank Name</label>
                                        <div class="text-dark fw-bold fs-6">${data.bank.bank_name || '-'}</div>
                                     </div>
                                     <div class="col-md-4">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Account No</label>
                                        <div class="text-dark fw-bold fs-5 font-monospace">${data.bank.account_number || '-'}</div>
                                     </div>
                                     <div class="col-md-4">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">IFSC Code</label>
                                        <div class="text-dark fw-bold fs-6 font-monospace">${data.bank.ifsc_code || '-'}</div>
                                     </div>
                                      <div class="col-md-4">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Branch</label>
                                        <div class="text-dark fw-medium fs-6">${data.bank.branch_name || '-'}</div>
                                     </div>
                                     <div class="col-md-4">
                                        <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Type</label>
                                        <div class="text-dark fw-medium fs-6">${data.bank.account_type || '-'}</div>
                                     </div>
                                </div>
                            </div>` : '<p class="text-muted fst-italic">No bank details available.</p>'}
                        </div>
                        
                        <!-- Notes -->
                        ${e.notes ? `
                        <div class="mb-0">
                             <h5 class="text-uppercase text-muted fs-xs fw-bolder ls-1 mb-2 border-bottom pb-2">
                                <i class="ti ti-notes me-2"></i>Remarks
                            </h5>
                            <div class="p-3 bg-light border rounded text-dark fs-6">
                                ${e.notes}
                            </div>
                        </div>` : ''}

                    </div>
                `;
                
                document.getElementById('view_employee_content').innerHTML = html;
                var myModal = new bootstrap.Modal(document.getElementById('viewEmployeeModal'));
                myModal.show();
            });
    }

    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('edit_avatar_preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<!-- View Employee Modal -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Employee Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="view_employee_content">
        <!-- Content loaded via AJAX -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #print-area, #print-area * { visibility: visible !important; }
    #print-area { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>

<div id="print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:16px;">
        <h3 style="margin:0;">Employees List</h3>
        <p style="margin:4px 0; font-size:12px; color:#666;">Printed on: <span id="print-date-emp"></span></p>
    </div>
    <table id="print-emp-table" border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:12px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>Emp Code</th>
                <th>Name</th>
                <th>Department</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Emp Status</th>
                <th>Account</th>
            </tr>
        </thead>
        <tbody id="print-emp-tbody"></tbody>
    </table>
</div>

<script>
function printEmployeesTable() {
    // Gather data from the visible DataTable rows
    var rows = document.querySelectorAll('#add_emp_list tbody tr');
    var tbody = document.getElementById('print-emp-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        if(tr.style.display === 'none') return;
        var tds = tr.querySelectorAll('td');
        if(tds.length < 5) return;
        // Extract clean text from each column
        var empCode  = (tds[0].querySelector('small') ? tds[0].querySelector('small').textContent : '').replace(/[()]/g,'').trim();
        var empName  = (tds[0].querySelector('.fw-bold') ? tds[0].querySelector('.fw-bold').textContent : '').trim();
        var dept     = (tds[1].querySelector('.fw-bold') ? tds[1].querySelector('.fw-bold').textContent : '').trim();
        var role     = (tds[1].querySelector('small') ? tds[1].querySelector('small').textContent.replace('Role:','').trim() : '');
        var email    = (tds[2].querySelector('.fw-bold') ? tds[2].querySelector('.fw-bold').textContent : '').trim();
        var phone    = (tds[2].querySelector('small') ? tds[2].querySelector('small').textContent : '').trim();
        var empStatus= (tds[3].querySelector('.badge') ? tds[3].querySelector('.badge').textContent : '').trim();
        var account  = (tds[4].querySelector('.badge') ? tds[4].querySelector('.badge').textContent : '').trim();
        
        var newRow = '<tr>' +
            '<td>' + empCode + '</td>' +
            '<td>' + empName + '</td>' +
            '<td>' + dept + '</td>' +
            '<td>' + role + '</td>' +
            '<td>' + email + '</td>' +
            '<td>' + phone + '</td>' +
            '<td>' + empStatus + '</td>' +
            '<td>' + account + '</td>' +
            '</tr>';
        tbody.innerHTML += newRow;
    });
    
    document.getElementById('print-date-emp').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('print-area').style.display = 'block';
    window.print();
    document.getElementById('print-area').style.display = 'none';
}
</script>
