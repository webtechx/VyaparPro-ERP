<?php
$title = "Account Settings";
require_once __DIR__ . '/../../config/auth_guard.php';

// Safe empty checks
$emp = $currentUser ?? [];
?>

<div class="row">
    <div class="col-xl-3 col-lg-4">
        <div class="card p-3">
            <h5 class="mb-3">Settings Menu</h5>
            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <a class="nav-link active mb-2" id="v-pills-password-tab" data-bs-toggle="pill" href="#v-pills-password" role="tab" aria-controls="v-pills-password" aria-selected="true" style="text-align: left;">
                    <i class="ti ti-lock me-2"></i> Security / Password
                </a>
                <a class="nav-link" id="v-pills-notifications-tab" data-bs-toggle="pill" href="#v-pills-notifications" role="tab" aria-controls="v-pills-notifications" aria-selected="false" style="text-align: left;">
                    <i class="ti ti-bell-ringing me-2"></i> Application Settings
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-9 col-lg-8">
        <div class="tab-content" id="v-pills-tabContent">
            
            <!-- Security / Password Tab -->
            <div class="tab-pane fade show active" id="v-pills-password" role="tabpanel" aria-labelledby="v-pills-password-tab">
                <div class="card">
                    <div class="card-header border-bottom border-dashed">
                        <h4 class="card-title mb-0">Change Password</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?= $basePath ?>/controller/profile/update_password.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label" for="current_password">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required placeholder="Enter current password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="new_password">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="Enter new password">
                                <small class="text-muted">Password must be at least 6 characters long.</small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="confirm_password">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Re-enter new password">
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-2"></i> Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border border-warning-subtle shadow-none">
                    <div class="card-body">
                        <h5 class="card-title text-warning mb-2"><i class="ti ti-alert-triangle me-2"></i> Session Security</h5>
                        <p class="card-text text-muted fs-14">Logging out will end your active session on this device. If you wish to switch accounts or secure your current session, click below.</p>
                        <a href="<?= $basePath ?>/logout" class="btn btn-warning d-inline-flex align-items-center"><i class="ti ti-logout me-1"></i> Log Out Now</a>
                    </div>
                </div>
            </div>

            <!-- Application Settings Tab -->
            <div class="tab-pane fade" id="v-pills-notifications" role="tabpanel" aria-labelledby="v-pills-notifications-tab">
                <div class="card">
                    <div class="card-header border-bottom border-dashed">
                        <h4 class="card-title mb-0">Application Settings</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?= $basePath ?>/controller/profile/update_settings.php" method="POST">
                            <h6 class="fs-15 mb-3">Theme Preferences</h6>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch" onchange="document.getElementById('light-dark-mode').click();">
                                <label class="form-check-label ms-2" for="darkModeSwitch">Enable Dark Mode Dashboard</label>
                            </div>
                            
                            <hr class="border-dashed my-4">

                            <!-- You can add more user-level settings here mapped to db fields if they exist -->

                            <div>
                                <button type="button" class="btn btn-primary"><i class="ti ti-device-floppy me-2"></i> Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Just map the manual toggle to the actual system switch
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const toggle = document.getElementById('darkModeSwitch');
        if (toggle) {
            toggle.checked = isDark;
        }
    });
</script>
