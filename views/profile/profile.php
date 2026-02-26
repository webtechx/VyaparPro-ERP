<?php
$title = "My Profile";
require_once __DIR__ . '/../../config/auth_guard.php';

// Prepare data mapping to be safe empty checks
$emp = $currentUser ?? [];

// Fetch Department and Designation Names
$deptName = 'N/A';
$desigName = 'N/A';

if (!empty($emp['department_id'])) {
    $deptQ = $conn->query("SELECT department_name FROM departments WHERE department_id = " . intval($emp['department_id']));
    if ($deptQ && $deptQ->num_rows > 0) {
        $deptName = $deptQ->fetch_assoc()['department_name'];
    }
}

if (!empty($emp['designation_id'])) {
    $desigQ = $conn->query("SELECT designation_name FROM designations WHERE designation_id = " . intval($emp['designation_id']));
    if ($desigQ && $desigQ->num_rows > 0) {
        $desigName = $desigQ->fetch_assoc()['designation_name'];
    }
}

// Avatar logic
$avatarUrl = '';
if (!empty($emp['employee_image'])) {
    $imgSrc = $emp['employee_image'];
    if (strpos($imgSrc, 'http') !== 0) {
        $avatarUrl = $basePath . '/uploads/' . $_SESSION['organization_code'] . '/employees/avatars/' . $imgSrc;
    } else {
        $avatarUrl = $imgSrc;
    }
}
$name = trim(($emp['salutation'] ?? '') . ' ' . ($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
if (empty(trim($name))) $name = 'User';
$initial = strtoupper(substr($emp['first_name'] ?? 'U', 0, 1));
?>

<div class="row">
    <!-- Profile Card Sidebar -->
    <div class="col-xl-4 col-lg-5">
        <div class="card text-center text-lg-start">
            <div class="card-body">
                <div class="d-flex flex-column align-items-center mb-4">
                    <div class="position-relative mb-3">
                        <?php if($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="Avatar" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle img-thumbnail bg-light text-primary d-flex align-items-center justify-content-center fw-bold display-4" style="width: 120px; height: 120px;">
                                <?= $initial ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($name) ?></h4>
                    <p class="text-muted mb-2"><?= htmlspecialchars($emp['role_name'] ?? 'Employee') ?> | <?= htmlspecialchars($desigName) ?></p>
                    <span class="badge bg-<?= ($emp['is_active'] ?? 1) ? 'success' : 'danger' ?>-subtle text-<?= ($emp['is_active'] ?? 1) ? 'success' : 'danger' ?> mb-3">
                        <?= ($emp['is_active'] ?? 1) ? 'Active Account' : 'Inactive Account' ?>
                    </span>
                    <div class="w-100 mt-2 d-flex gap-2 justify-content-center">
                        <a href="<?= $basePath ?>/account_settings" class="btn btn-outline-primary btn-sm"><i class="ti ti-settings me-1"></i> Account Settings</a>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-3">Contact Information</h6>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                        <i class="ti ti-mail"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0 fs-13">Primary Email Address</h6>
                        <span class="text-muted fs-13"><?= htmlspecialchars($emp['primary_email'] ?? 'Not Provided') ?></span>
                    </div>
                </div>
                
                <?php if(!empty($emp['alternate_email'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-secondary-subtle text-secondary rounded d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                        <i class="ti ti-mail-forward"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0 fs-13">Alternate Email</h6>
                        <span class="text-muted fs-13"><?= htmlspecialchars($emp['alternate_email']) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                        <i class="ti ti-phone"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0 fs-13">Primary Phone Number</h6>
                        <span class="text-muted fs-13"><?= htmlspecialchars($emp['primary_phone'] ?? 'Not Provided') ?></span>
                    </div>
                </div>

                <?php if(!empty($emp['alternate_phone'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-secondary-subtle text-secondary rounded d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                        <i class="ti ti-phone-plus"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0 fs-13">Alternate Phone Number</h6>
                        <span class="text-muted fs-13"><?= htmlspecialchars($emp['alternate_phone']) ?></span>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        
        <?php if (!empty($emp['emergency_contact_name']) || !empty($emp['emergency_contact_phone']) || !empty($emp['ref_phone_no'])): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="text-uppercase text-danger fs-11 fw-bold mb-3"><i class="ti ti-alert-circle me-1"></i> Emergency Contacts</h6>
                
                <?php if(!empty($emp['emergency_contact_name'])): ?>
                <div class="mb-2">
                    <strong class="fs-13">Contact Name:</strong> <span class="fs-13 text-muted"><?= htmlspecialchars($emp['emergency_contact_name']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($emp['emergency_contact_phone'])): ?>
                <div class="mb-2">
                    <strong class="fs-13">Contact Phone:</strong> <span class="fs-13 text-muted"><?= htmlspecialchars($emp['emergency_contact_phone']) ?></span>
                </div>
                <?php endif; ?>

                <?php if(!empty($emp['ref_phone_no'])): ?>
                <div class="mb-0 mt-3 pt-2 border-top border-dashed">
                    <strong class="fs-13">Reference Phone:</strong> <span class="fs-13 text-muted"><?= htmlspecialchars($emp['ref_phone_no']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Profile Details List -->
    <div class="col-xl-8 col-lg-7">
        
        <!-- Personal Details -->
        <div class="card mb-3">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="card-title mb-0">Personal Details</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">First Name</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($emp['first_name'] ?? '-') ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Last Name</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($emp['last_name'] ?? '-') ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Gender</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars(ucfirst($emp['gender'] ?? '-')) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Date of Birth</div>
                    <div class="col-sm-8 text-dark fs-14"><?= !empty($emp['date_of_birth']) ? date('d M Y', strtotime($emp['date_of_birth'])) : '-' ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Blood Group</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($emp['blood_group'] ?? 'Not Specified') ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Father's Name</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($emp['father_name'] ?? '-') ?></div>
                </div>
                <div class="row mb-0">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Mother's Name</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($emp['mother_name'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <!-- Identifications -->
        <div class="card mb-3">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="card-title mb-0">Government Identifications</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">PAN Number</div>
                    <div class="col-sm-8 text-dark fs-14 text-uppercase"><?= htmlspecialchars($emp['pan'] ?? 'Not Uploaded') ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Aadhar Number</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($emp['aadhar'] ?? 'Not Uploaded') ?></div>
                </div>
                <div class="row mb-0">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Voter ID</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($emp['voter_id'] ?? 'Not Uploaded') ?></div>
                </div>
            </div>
        </div>

        <!-- Employment Information -->
        <div class="card mb-3">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="card-title mb-0">Employment Information</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Employee Code</div>
                    <div class="col-sm-8 text-dark fs-14 fw-medium"><?= htmlspecialchars($emp['employee_code'] ?? '-') ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Department</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($deptName) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Designation</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars($desigName) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Joined On</div>
                    <div class="col-sm-8 text-dark fs-14"><?= !empty($emp['joined_on']) ? date('d M Y', strtotime($emp['joined_on'])) : '-' ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Enrollment Type</div>
                    <div class="col-sm-8 text-dark fs-14"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $emp['enrollment_type'] ?? '-'))) ?></div>
                </div>
                <div class="row mb-0">
                    <div class="col-sm-4 text-muted fs-14 fw-medium mb-1 mb-sm-0">Employment Status</div>
                    <div class="col-sm-8 text-dark fs-14">
                        <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars(ucfirst($emp['employment_status'] ?? '-')) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Notes -->
        <?php if(!empty($emp['notes'])): ?>
        <div class="card mb-3">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="card-title mb-0">Notes / Remarks</h4>
            </div>
            <div class="card-body">
                <p class="text-muted fs-14 mb-0"><?= nl2br(htmlspecialchars($emp['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
