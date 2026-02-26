<?php
$title = 'Sign In';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($basePath ?? '') . '/dashboard');
    exit();
}
include __DIR__ . '/../layouts/components/head.php';
?>
 

    <div class="auth-box overflow-hidden align-items-center d-flex">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-4 col-md-6 col-sm-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="auth-brand mb-4 ">
                                <a href="<?= $basePath ?>/" class="logo-dark">
                                    <span class="text-center gap-1">
                                         <div>
                                             <img src="<?= $basePath ?>/public/assets/images/logo/logo-sm.png" alt="">
                                         </div>
                                        <div>
                                            <span class="logo-text text-body fw-bold fs-xl">SAMADHAN</span>
                                        </div>
                                    </span>
                                </a>
                                <a href="<?= $basePath ?>/" class="logo-light">
                                    <span class="text-center gap-1">
                                        <div>
                                             <img src="<?= $basePath ?>/public/assets/images/logo/logo-sm.png" alt="">
                                         </div>
                                        <div>
                                            <span class="logo-text text-body fw-bold fs-xl">SAMADHAN</span>
                                        </div>
                                    </span>
                                </a>
                                <p class="text-center text-muted mt-3">Let’s get you signed in. <br> Enter your Employee Code and password to continue.</p>
                            </div>
            
                            <div class="">
                                <?php if(isset($_GET['error'])) {?> <div class="alert alert-danger"><?= $_GET['error'] ?></div> <?php } ?>
                                

                                <form action="<?= $basePath ?>/controller/auth/SigninController.php" method="post">
                                    <div class="mb-3">
                                        <label for="employeeCode" class="form-label">Employee Code <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="employeeCode" name="employee_code" placeholder="Employee Code" required>
                                        </div>
                                    </div>
            
                                    <div class="mb-3">
                                        <label for="userPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="userPassword" name="password" placeholder="••••••••" required>
                                        </div>
                                    </div>
            
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input form-check-input-light fs-14" type="checkbox" id="rememberMe">
                                            <label class="form-check-label" for="rememberMe">Keep me signed in</label>
                                        </div>
                                        <a href="<?= $basePath ?>/reset-password" class="text-decoration-underline link-offset-3 text-muted">Forgot Password?</a>
                                    </div>
            
                                    <div class="d-grid">
                                        <button type="submit" name="sign_in" class="btn btn-primary fw-semibold py-2">Sign In</button>
                                    </div>
                                </form>
            
                                <!-- <p class="text-muted text-center mt-4 mb-0">
                                    New here? <a href="<?= $basePath ?>/register" class="text-decoration-underline link-offset-3 fw-semibold">Create an account</a>
                                </p> -->
                            </div>
                        </div>
                    </div>
                    <p class="text-center text-muted mt-4 mb-0">
                        © <script>document.write(new Date().getFullYear())</script> Samadhan — by <span class="fw-semibold">SKC INFOTECH</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    

<?php include __DIR__ . '/../layouts/components/scripts.php'; ?>