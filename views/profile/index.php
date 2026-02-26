<?php
$title = "My Profile";

// Fetch Additional Details (Address & Bank)
$address = [];
$bank = [];

$empId = $currentUser['employee_id'];

// Address
$addrSql = "SELECT * FROM employee_addresses WHERE employee_id = ? LIMIT 1";
$stmt = $conn->prepare($addrSql);
$stmt->bind_param("i", $empId);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows > 0) $address = $res->fetch_assoc();

// Bank
$bankSql = "SELECT * FROM employee_bank_details WHERE employee_id = ? LIMIT 1";
$stmt = $conn->prepare($bankSql);
$stmt->bind_param("i", $empId);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows > 0) $bank = $res->fetch_assoc();

// Helper for initials
$initials = strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1));
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <!-- Professional Header -->
                    <div class="d-flex align-items-center mb-4 pb-4 border-bottom">
                        <?php if(!empty($currentUser['employee_image'])): 
                            $imgSrc = $currentUser['employee_image'];
                            if (strpos($imgSrc, 'http') !== 0) {
                                $imgSrc = $basePath . '/uploads/' . $_SESSION['organization_code'] . '/employees/avatars/' . $imgSrc;
                            }
                        ?>
                            <img src="<?= $imgSrc ?>" class="rounded-circle me-4 shadow-sm" style="width:90px; height:90px; object-fit:cover;">
                        <?php else: ?>
                            <div class="bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center me-4 shadow-sm" style="width:90px; height:90px; font-size:2.5rem; font-weight:bold;">
                                <?= $initials ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-grow-1">
                            <h3 class="mb-1 text-dark fw-bold"><?= htmlspecialchars($currentUser['salutation'] . ' ' . $currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></h3>
                            <div class="mb-2">
                                    <span class="badge bg-primary px-3 py-2 fs-xs me-2"><?= htmlspecialchars($currentUser['role_name'] ?? '-') ?></span>
                                    <span class="badge bg-light text-dark border px-3 py-2 fs-xs"><?= htmlspecialchars($currentUser['employee_code']) ?></span>
                            </div>
                            <div class="text-muted d-flex align-items-center flex-wrap gap-3">
                                <span><i class="ti ti-mail me-1"></i> <?= htmlspecialchars($currentUser['primary_email']) ?></span>
                                <span><i class="ti ti-phone me-1"></i> <?= htmlspecialchars($currentUser['primary_phone']) ?></span>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <div class="text-muted fs-xs text-uppercase fw-bold mb-1">Status</div>
                            <span class="badge badge-lg <?= ($currentUser['employment_status'] ?? 'Joined') == 'Joined' ? 'bg-success' : 'bg-secondary' ?> fs-6 mb-2"><?= htmlspecialchars($currentUser['employment_status'] ?? 'Joined') ?></span>
                            
                            <div class="mt-2">
                                <span class="badge bg-soft-success text-success fw-bold">Active Account</span>
                            </div>
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
                                <div class="text-dark fw-medium fs-6"><?= htmlspecialchars($currentUser['father_name'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-3">
                                <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Mother's Name</label>
                                <div class="text-dark fw-medium fs-6"><?= htmlspecialchars($currentUser['mother_name'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-3">
                                <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Date of Birth</label>
                                <div class="text-dark fw-medium fs-6"><?= htmlspecialchars($currentUser['date_of_birth'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-3">
                                <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Gender</label>
                                <div class="text-dark fw-medium fs-6"><?= htmlspecialchars($currentUser['gender'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-3">
                                <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Joined On</label>
                                <div class="text-dark fw-medium fs-6"><?= htmlspecialchars($currentUser['joined_on'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-5">
                                <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Emergency Contact</label>
                                <div class="text-dark fw-medium fs-6">
                                    <?= htmlspecialchars($currentUser['emergency_contact_name'] ?? '-') ?> 
                                    <span class="text-muted ms-1">(<?= htmlspecialchars($currentUser['emergency_contact_phone'] ?? '-') ?>)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1">PAN CARD</label>
                                    <span class="text-dark fw-bold font-monospace"><?= htmlspecialchars($currentUser['pan'] ?? '-') ?></span>
                                </div>
                                <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1">AADHAR NO</label>
                                    <span class="text-dark fw-bold font-monospace"><?= htmlspecialchars($currentUser['aadhar'] ?? '-') ?></span>
                                </div>
                                <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1">VOTER ID</label>
                                    <span class="text-dark fw-bold font-monospace"><?= htmlspecialchars($currentUser['voter_id'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Addresses Section -->
                     <div class="mb-4">
                        <h5 class="text-uppercase text-muted fs-xs fw-bolder ls-1 mb-3 border-bottom pb-2">
                            <i class="ti ti-map-pin me-2"></i>Address Details
                        </h5>
                        <?php if(!empty($address)): ?>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white h-100">
                                    <div class="badge bg-light text-dark mb-2">Current Address</div>
                                    <p class="mb-0 fs-6 text-dark leading-relaxed">
                                        <strong><?= htmlspecialchars($address['current_street'] ?? '') ?></strong><br>
                                        <?= htmlspecialchars($address['current_city'] ?? '') ?>, <?= htmlspecialchars($address['current_district'] ?? '') ?><br>
                                        <?= htmlspecialchars($address['current_state'] ?? '') ?> - <span class="fw-bold"><?= htmlspecialchars($address['current_pincode'] ?? '') ?></span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                               <div class="p-3 border rounded bg-white h-100">
                                    <div class="badge bg-light text-dark mb-2">Permanent Address</div>
                                    <p class="mb-0 fs-6 text-dark leading-relaxed">
                                        <strong><?= htmlspecialchars($address['permanent_street'] ?? '') ?></strong><br>
                                        <?= htmlspecialchars($address['permanent_city'] ?? '') ?>, <?= htmlspecialchars($address['permanent_district'] ?? '') ?><br>
                                        <?= htmlspecialchars($address['permanent_state'] ?? '') ?> - <span class="fw-bold"><?= htmlspecialchars($address['permanent_pincode'] ?? '') ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                            <p class="text-muted fst-italic">No address details available.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Bank Details Section -->
                    <div class="mb-4">
                        <h5 class="text-uppercase text-muted fs-xs fw-bolder ls-1 mb-3 border-bottom pb-2">
                            <i class="ti ti-building-bank me-2"></i>Bank Account
                        </h5>
                        <?php if(!empty($bank)): ?>
                        <div class="card card-body bg-soft-info border-0">
                            <div class="row g-3">
                                 <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Bank Name</label>
                                    <div class="text-dark fw-bold fs-6"><?= htmlspecialchars($bank['bank_name'] ?? '-') ?></div>
                                 </div>
                                 <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Account No</label>
                                    <div class="text-dark fw-bold fs-5 font-monospace"><?= htmlspecialchars($bank['account_number'] ?? '-') ?></div>
                                 </div>
                                 <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">IFSC Code</label>
                                    <div class="text-dark fw-bold fs-6 font-monospace"><?= htmlspecialchars($bank['ifsc_code'] ?? '-') ?></div>
                                 </div>
                                  <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Branch</label>
                                    <div class="text-dark fw-medium fs-6"><?= htmlspecialchars($bank['branch_name'] ?? '-') ?></div>
                                 </div>
                                 <div class="col-md-4">
                                    <label class="d-block text-muted fs-xs fw-bold mb-1 text-uppercase">Type</label>
                                    <div class="text-dark fw-medium fs-6"><?= htmlspecialchars($bank['account_type'] ?? '-') ?></div>
                                 </div>
                            </div>
                        </div>
                        <?php else: ?>
                            <p class="text-muted fst-italic">No bank details available.</p>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
