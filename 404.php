<?php
$title = '404 Error';
include __DIR__ . '/views/layouts/components/head.php';
?>

    <div class="auth-box overflow-hidden align-items-center d-flex">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-4 col-md-6 col-sm-8">
                    <div class="card">
                        <div class="card-body">
            
                            <div class="p-2 text-center">
                                <div class="text-error fw-bold fs-60">404</div>
                                <h3 class="fw-semibold">Page Not Found</h3>
                                <p class="text-muted">The page you’re looking for doesn’t exist or has been moved.</p>

                                <button class="btn btn-primary mt-3 rounded-pill" onclick="history.back()">Go Back</button>

                            </div>
                        </div>
                    </div>
    
                    <p class="text-center text-muted mt-4 mb-0">
                        © <script>document.write(new Date().getFullYear())</script> Samadhan ERP — by <span class="fw-semibold">SKC INFOTECH</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/views/layouts/components/scripts.php'; ?>
