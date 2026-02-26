<?php
if (isset($_SESSION['user_id']) && !isset($orgLogo)) {
    $orgLogo = '';
    // Database connection if not available
    if (!isset($conn)) {
        $connFile = __DIR__ . '/../../../config/conn.php';
        if (file_exists($connFile)) {
            include $connFile;
        }
    }
    
    if (isset($conn)) {
        $orgSql = "SELECT organization_logo FROM organizations LIMIT 1";
        $orgRes = $conn->query($orgSql);
        if ($orgRes && $orgRes->num_rows > 0) {
            $orgRow = $orgRes->fetch_assoc();
            $orgLogo = $orgRow['organization_logo'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $title ?? 'Auth' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php if (isset($_SESSION['user_id']) && !empty($orgLogo)): ?>
    <link rel="shortcut icon" href="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $orgLogo ?>">
    <?php endif; ?>

    <script src="<?= $basePath ?>/public/assets/js/config.js"></script>

    <link href="<?= $basePath ?>/public/assets/css/vendors.min.css" rel="stylesheet">
    <link href="<?= $basePath ?>/public/assets/css/app.min.css" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Cube Loader Component CSS -->
    <link href="<?= $basePath ?>/public/assets/css/cube-loader.css" rel="stylesheet" />
    
    <style>
        /* Select2 Bootstrap 5 Theme Fixes (Global) */
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding-top: 5px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container {
            z-index: 9999; /* Ensure dropdowns appear above other elements */
        }
    </style>


    <script src="<?= $basePath ?>/public/assets/plugins/lucide/lucide.min.js"></script>
</head>
<body>

<!-- Begin page -->
<div class="wrapper">
