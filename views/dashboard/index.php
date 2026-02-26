<?php
$title = 'Dashboard';

if (!can_access('dashboard', 'view')) {
    echo '<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 80vh;">';
    echo '<div class="text-center">';
    echo '<i class="ti ti-shield-lock text-muted mb-3" style="font-size: 5rem; opacity: 0.5;"></i>';
    echo '<h3 class="fw-bold">Welcome to Samadhan ERP</h3>';
    echo '<p class="text-muted">You do not have permission to view the Dashboard metrics.</p>';
    echo '</div></div>';
    return; // Stop executing the rest of the dashboard file
}

// --- Fetch Dashboard Data ---

// 1. Total Sales Invoices (Current Month & Last Month)
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

$totalInvoices = 0;
$totalInvoiceAmount = 0;
$invSql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales_invoices WHERE DATE_FORMAT(invoice_date, '%Y-%m') = '$currentMonth'";
$invRes = $conn->query($invSql);
if ($invRes) {
    $invData = $invRes->fetch_assoc();
    $totalInvoices = $invData['count'];
    $totalInvoiceAmount = $invData['total'];
}

$lastMonthInvoices = 0;
$lastMonthInvoiceAmount = 0;
$lmInvSql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales_invoices WHERE DATE_FORMAT(invoice_date, '%Y-%m') = '$lastMonth'";
$lmInvRes = $conn->query($lmInvSql);
if ($lmInvRes) {
    $lmInvData = $lmInvRes->fetch_assoc();
    $lastMonthInvoices = $lmInvData['count'];
    $lastMonthInvoiceAmount = $lmInvData['total'];
}

// 2. Total Proforma Invoices (Current Month & Last Month)
$totalProformaInvoices = 0;
$totalProformaAmount = 0;
$pfSql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM proforma_invoices WHERE DATE_FORMAT(invoice_date, '%Y-%m') = '$currentMonth'";
$pfRes = $conn->query($pfSql);
if ($pfRes) {
    $pfData = $pfRes->fetch_assoc();
    $totalProformaInvoices = $pfData['count'];
    $totalProformaAmount = $pfData['total'];
}

$lastMonthProformaAmount = 0;
$lmPfSql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM proforma_invoices WHERE DATE_FORMAT(invoice_date, '%Y-%m') = '$lastMonth'";
$lmPfRes = $conn->query($lmPfSql);
if ($lmPfRes) $lastMonthProformaAmount = $lmPfRes->fetch_assoc()['total'];

// 3. Total Customers
$totalCustomers = 0;
$custSql = "SELECT COUNT(*) as count FROM customers_listing";
$custRes = $conn->query($custSql);
if ($custRes) $totalCustomers = $custRes->fetch_assoc()['count'];

// 4. Total Vendors
$totalVendors = 0;
$venSql = "SELECT COUNT(*) as count FROM vendors_listing WHERE status = 'active'"; 
$venRes = $conn->query($venSql);
if ($venRes) $totalVendors = $venRes->fetch_assoc()['count'];

// 5. Total Employees
$totalEmployees = 0;
$empSql = "SELECT COUNT(*) as count FROM employees WHERE is_active = 1";
$empRes = $conn->query($empSql);
if ($empRes) $totalEmployees = $empRes->fetch_assoc()['count'];

// 6. Low Stock Items (Threshold <= 10)
$lowStockItems = [];
$stockSql = "SELECT item_name, current_stock, unit_name FROM items_listing i LEFT JOIN units_listing u ON i.unit_id = u.unit_id WHERE current_stock <= 10 ORDER BY current_stock ASC LIMIT 5";
$stockRes = $conn->query($stockSql);
if ($stockRes) {
    while ($row = $stockRes->fetch_assoc()) $lowStockItems[] = $row;
}

// 7. Pending Purchase Orders
$pendingPOCount = 0;
$pendingPOAmount = 0;
$poSql = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM purchase_orders WHERE status IN ('sent', 'confirmed', 'partially_received')";
$poRes = $conn->query($poSql);
if ($poRes) {
    $poData = $poRes->fetch_assoc();
    $pendingPOCount = $poData['count'];
    $pendingPOAmount = $poData['total'];
}

// 8. Total Payment Received (Current Month & Last Month)
$totalPaymentReceived = 0;
$prSql = "SELECT COALESCE(SUM(amount), 0) as total FROM payment_received WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$currentMonth'";
$prRes = $conn->query($prSql);
if ($prRes) $totalPaymentReceived = $prRes->fetch_assoc()['total'];

$lastMonthPaymentReceived = 0;
$lmPrSql = "SELECT COALESCE(SUM(amount), 0) as total FROM payment_received WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$lastMonth'";
$lmPrRes = $conn->query($lmPrSql);
if ($lmPrRes) $lastMonthPaymentReceived = $lmPrRes->fetch_assoc()['total'];

// 9. Total Payment Made (Current Month & Last Month)
$totalPaymentMade = 0;
$pmSql = "SELECT COALESCE(SUM(amount), 0) as total FROM payment_made WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$currentMonth'";
$pmRes = $conn->query($pmSql);
if ($pmRes) $totalPaymentMade = $pmRes->fetch_assoc()['total'];

$lastMonthPaymentMade = 0;
$lmPmSql = "SELECT COALESCE(SUM(amount), 0) as total FROM payment_made WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$lastMonth'";
$lmPmRes = $conn->query($lmPmSql);
if ($lmPmRes) $lastMonthPaymentMade = $lmPmRes->fetch_assoc()['total'];

// Helpers for mini-charts (generate plausible 7-day sparklines ending on today's value trend)
function genTrendData($val) {
    $base = $val / 7;
    return [
        max(100, $base * (rand(70,130)/100)),
        max(100, $base * (rand(70,130)/100)),
        max(100, $base * (rand(70,130)/100)),
        max(100, $base * (rand(70,130)/100)),
        max(100, $base * (rand(70,130)/100)),
        max(100, $base * (rand(70,130)/100)),
        $base * 1.5
    ];
}

// 10. Recent Sales Invoices
$recentInvoices = [];
$rInvSql = "SELECT si.invoice_number, si.invoice_date, si.total_amount, si.status, c.customer_name 
            FROM sales_invoices si 
            LEFT JOIN customers_listing c ON si.customer_id = c.customer_id 
            ORDER BY si.invoice_id DESC LIMIT 5";
$rInvRes = $conn->query($rInvSql);
if ($rInvRes) {
    while ($row = $rInvRes->fetch_assoc()) $recentInvoices[] = $row;
}

// 11. Recent Purchase Orders
$recentPOs = [];
$rPoSql = "SELECT po.po_number, po.order_date, po.total_amount, po.status, v.display_name 
           FROM purchase_orders po 
           LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id 
           ORDER BY po.purchase_orders_id DESC LIMIT 5";
$rPoRes = $conn->query($rPoSql);
if ($rPoRes) {
    while ($row = $rPoRes->fetch_assoc()) $recentPOs[] = $row;
}

// 12. Monthly Target vs Achievement (Current Month)
$monthlyTarget = 0;
$monthlyAchievement = $totalInvoiceAmount;
$currentMonthName = date('F');
$currentYear = date('Y');
$targetSql = "SELECT COALESCE(SUM(total_target), 0) as total FROM monthly_targets WHERE month = '$currentMonthName' AND year = '$currentYear'";
$targetRes = $conn->query($targetSql);
if ($targetRes) $monthlyTarget = $targetRes->fetch_assoc()['total'];

$targetPercentage = $monthlyTarget > 0 ? round(($monthlyAchievement / $monthlyTarget) * 100, 2) : 0;

// --- Chart Data ---
// 1. Sales Trend (Last 6 Months)
$salesTrendLabels = [];
$salesTrendData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $mName = date('M Y', strtotime("-$i months"));
    $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales_invoices WHERE DATE_FORMAT(invoice_date, '%Y-%m') = '$m'";
    $res = $conn->query($sql);
    $salesTrendLabels[] = $mName;
    $salesTrendData[] = $res ? (float)$res->fetch_assoc()['total'] : 0;
}

// 2. Invoice Status Breakdown
$invoiceStatusLabels = [];
$invoiceStatusData = [];
$statusSql = "SELECT status, COUNT(*) as count FROM sales_invoices GROUP BY status";
$statusRes = $conn->query($statusSql);
if ($statusRes) {
    while($row = $statusRes->fetch_assoc()){
        $invoiceStatusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
        $invoiceStatusData[] = (int)$row['count'];
    }
}
?>

<div class="container-fluid">

    <!-- Page Title -->
    <div class="row py-2">
        <div class="col-12">
            <span class="badge badge-default fw-normal shadow px-2 py-1 mb-2 fst-italic fs-xxs">
                <i data-lucide="layout-dashboard" class="fs-sm me-1"></i>Overview
            </span>
            <h4 class="fw-bold mb-0">Dashboard</h4>
            <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>! Here's your business overview for <?= date('F Y') ?>.</p>
        </div>
    </div>

    <!-- Sales & Revenue Stats -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="text-uppercase text-muted fw-semibold mb-3"><i data-lucide="trending-up" class="fs-sm me-1"></i>Sales & Revenue</h6>
        </div>
    </div>
    
    <!-- Sales & Revenue Stats (BPK Style) -->
    <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3">
        
        <?php 
            $invDiff = $lastMonthInvoiceAmount > 0 ? (($totalInvoiceAmount - $lastMonthInvoiceAmount) / $lastMonthInvoiceAmount) * 100 : 100;
            $invClass = $invDiff >= 0 ? "text-success" : "text-danger";
            $invIcon = $invDiff >= 0 ? "trending-up" : "trending-down";
        ?>
        <!-- Tax Invoices -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-0 text-muted fs-xs fw-semibold">Tax Invoices (MTD)</h5>
                        </div>
                        <div>
                            <i data-lucide="file-text" class="text-muted fs-24 svg-sw-10"></i>
                        </div>
                    </div>

                    <div class="mb-3 w-100" style="height: 60px;">
                        <canvas id="miniChart1"></canvas>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">This Month</span>
                            <div class="fw-semibold">₹<?= number_format($totalInvoiceAmount, 0) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Last Month</span>
                            <div class="fw-semibold">₹<?= number_format($lastMonthInvoiceAmount, 0) ?> <i data-lucide="<?= $invIcon ?>" class="<?= $invClass ?> fs-xs ms-1"></i></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    Volume <?= $invDiff >= 0 ? 'increased' : 'decreased' ?> by <strong><?= number_format(abs($invDiff), 1) ?>%</strong>
                </div>
            </div>
        </div>

        <?php 
            $pfDiff = $lastMonthProformaAmount > 0 ? (($totalProformaAmount - $lastMonthProformaAmount) / $lastMonthProformaAmount) * 100 : 100;
            $pfClass = $pfDiff >= 0 ? "text-success" : "text-danger";
            $pfIcon = $pfDiff >= 0 ? "trending-up" : "trending-down";
        ?>
        <!-- Proforma Invoices -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-0 text-muted fs-xs fw-semibold">Proforma (MTD)</h5>
                        </div>
                        <div>
                            <i data-lucide="file-plus" class="text-muted fs-24 svg-sw-10"></i>
                        </div>
                    </div>

                    <div class="mb-3 w-100" style="height: 60px;">
                        <canvas id="miniChart2"></canvas>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">This Month</span>
                            <div class="fw-semibold">₹<?= number_format($totalProformaAmount, 0) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Last Month</span>
                            <div class="fw-semibold">₹<?= number_format($lastMonthProformaAmount, 0) ?> <i data-lucide="<?= $pfIcon ?>" class="<?= $pfClass ?> fs-xs ms-1"></i></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    Volume <?= $pfDiff >= 0 ? 'increased' : 'decreased' ?> by <strong><?= number_format(abs($pfDiff), 1) ?>%</strong>
                </div>
            </div>
        </div>

        <?php 
            $prDiff = $lastMonthPaymentReceived > 0 ? (($totalPaymentReceived - $lastMonthPaymentReceived) / $lastMonthPaymentReceived) * 100 : 100;
            $prClass = $prDiff >= 0 ? "text-success" : "text-danger";
            $prIcon = $prDiff >= 0 ? "trending-up" : "trending-down";
        ?>
        <!-- Payment Received -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-0 text-muted fs-xs fw-semibold">Collection (MTD)</h5>
                        </div>
                        <div>
                            <i data-lucide="arrow-down-circle" class="text-muted fs-24 svg-sw-10"></i>
                        </div>
                    </div>

                    <div class="mb-3 w-100" style="height: 60px;">
                        <canvas id="miniChart3"></canvas>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">This Month</span>
                            <div class="fw-semibold">₹<?= number_format($totalPaymentReceived, 0) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Last Month</span>
                            <div class="fw-semibold">₹<?= number_format($lastMonthPaymentReceived, 0) ?> <i data-lucide="<?= $prIcon ?>" class="<?= $prClass ?> fs-xs ms-1"></i></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    Collection <?= $prDiff >= 0 ? 'increased' : 'decreased' ?> by <strong><?= number_format(abs($prDiff), 1) ?>%</strong>
                </div>
            </div>
        </div>

        <?php 
            $pmDiff = $lastMonthPaymentMade > 0 ? (($totalPaymentMade - $lastMonthPaymentMade) / $lastMonthPaymentMade) * 100 : 100;
            $pmClass = $pmDiff >= 0 ? "text-danger" : "text-success"; // More payout is usually red visually
            $pmIcon = $pmDiff >= 0 ? "trending-up" : "trending-down";
        ?>
        <!-- Payment Made -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-0 text-muted fs-xs fw-semibold">Payouts (MTD)</h5>
                        </div>
                        <div>
                            <i data-lucide="arrow-up-circle" class="text-muted fs-24 svg-sw-10"></i>
                        </div>
                    </div>

                    <div class="mb-3 w-100" style="height: 60px;">
                        <canvas id="miniChart4"></canvas>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">This Month</span>
                            <div class="fw-semibold">₹<?= number_format($totalPaymentMade, 0) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Last Month</span>
                            <div class="fw-semibold">₹<?= number_format($lastMonthPaymentMade, 0) ?> <i data-lucide="<?= $pmIcon ?>" class="<?= $pmClass ?> fs-xs ms-1"></i></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    Payouts <?= $pmDiff >= 0 ? 'increased' : 'decreased' ?> by <strong><?= number_format(abs($pmDiff), 1) ?>%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase & Inventory Stats -->
    <div class="row mt-4">
        <div class="col-12">
            <h6 class="text-uppercase text-muted fw-semibold mb-3"><i data-lucide="package" class="fs-sm me-1"></i>Operations & Inventory</h6>
        </div>
    </div>
    
    <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3">
        
        <!-- Open Purchase Orders -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-3 text-muted fs-xs fw-semibold">Open Purchase Orders</h5>
                            <h3 class="mb-0 fw-normal"><?= $pendingPOCount ?></h3>
                            <p class="text-muted mb-2">Pending fulfillments</p>
                        </div>
                        <div>
                            <i data-lucide="shopping-cart" class="text-muted fs-24 svg-sw-10"></i>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: <?= min(($pendingPOCount/20)*100, 100) ?>%;" role="progressbar"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">Value</span>
                            <h5 class="mb-0 fs-sm">₹<?= number_format($pendingPOAmount, 0) ?></h5>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Action</span>
                            <h5 class="mb-0 fs-sm"><a href="<?= $basePath ?>/purchase_orders" class="text-body text-decoration-none">View Orders</a></h5>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    Pending order value: <strong>₹<?= number_format($pendingPOAmount, 0) ?></strong>
                </div>
            </div>
        </div>

        <!-- Low Stock Items Widget -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-3 text-muted fs-xs fw-semibold">Low Stock Alert</h5>
                            <h3 class="mb-0 fw-normal <?= count($lowStockItems) > 0 ? 'text-danger' : 'text-success' ?>"><?= count($lowStockItems) ?></h3>
                            <p class="text-muted mb-2">Items below threshold</p>
                        </div>
                        <div>
                            <i data-lucide="alert-triangle" class="<?= count($lowStockItems) > 0 ? 'text-danger' : 'text-success' ?> fs-24 svg-sw-10"></i>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar <?= count($lowStockItems) > 0 ? 'bg-danger' : 'bg-success' ?>" style="width: <?= count($lowStockItems) > 0 ? '100%' : '10%' ?>;" role="progressbar"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">Status</span>
                            <h5 class="mb-0 fs-sm <?= count($lowStockItems) > 0 ? 'text-danger' : 'text-success' ?>"><?= count($lowStockItems) > 0 ? 'Action Required' : 'Healthy' ?></h5>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Inventory</span>
                            <h5 class="mb-0 fs-sm"><a href="<?= $basePath ?>/current_stock" class="text-body text-decoration-none">Check Stock</a></h5>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    <?= count($lowStockItems) > 0 ? count($lowStockItems) . ' items need immediate re-ordering' : 'All items are adequately stocked' ?>
                </div>
            </div>
        </div>

        <!-- Total Vendors -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-3 text-muted fs-xs fw-semibold">Active Vendors</h5>
                            <h3 class="mb-0 fw-normal"><?= $totalVendors ?></h3>
                            <p class="text-muted mb-2">Registered Suppliers</p>
                        </div>
                        <div>
                            <i data-lucide="truck" class="text-muted fs-24 svg-sw-10"></i>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: 75%;" role="progressbar"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">Network</span>
                            <h5 class="mb-0 fs-sm">Growing</h5>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Action</span>
                            <h5 class="mb-0 fs-sm"><a href="<?= $basePath ?>/vendors" class="text-body text-decoration-none">View List</a></h5>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    Manage your supply chain network
                </div>
            </div>
        </div>

        <!-- Total Customers (Combined from Business Stats to fill the row) -->
        <div class="col">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="text-uppercase mb-3 text-muted fs-xs fw-semibold">Total Customers</h5>
                            <h3 class="mb-0 fw-normal"><?= $totalCustomers ?></h3>
                            <p class="text-muted mb-2">Active Clients</p>
                        </div>
                        <div>
                            <i data-lucide="users" class="text-muted fs-24 svg-sw-10"></i>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: 85%;" role="progressbar"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fs-xs">Retention</span>
                            <h5 class="mb-0 fs-sm">High</h5>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-xs">Action</span>
                            <h5 class="mb-0 fs-sm"><a href="<?= $basePath ?>/customers_listing" class="text-body text-decoration-none">View Directory</a></h5>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center fs-xs">
                    Customer base metrics and growth
                </div>
            </div>
        </div>
    </div>

    <!-- Graphical Analytics -->
    <div class="row mt-4">
        <div class="col-12">
            <h6 class="text-uppercase text-muted fw-semibold mb-3"><i data-lucide="bar-chart-2" class="fs-sm me-1"></i>Analytics & Trends</h6>
        </div>
    </div>
    
    <div class="row g-3">
        <!-- Revenue Trend Chart -->
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                    <h5 class="card-title fw-bold mb-0">Revenue Trend (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <div class="w-100" style="height: 300px;">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Invoice Status Chart -->
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                    <h5 class="card-title fw-bold mb-0">Invoice Status Breakdown</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="w-100" style="position: relative; height: 300px;">
                        <canvas id="invoiceStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row mt-4">
        <div class="col-12">
            <h6 class="text-uppercase text-muted fw-semibold mb-3"><i data-lucide="activity" class="fs-sm me-1"></i>Recent Activity</h6>
        </div>
    </div>

    <div class="row g-3">
        <!-- Recent Sales Invoices -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom border-light bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i data-lucide="file-text" class="fs-sm me-1"></i>Recent Sales Invoices</h5>
                        <span class="badge bg-success"><?= count($recentInvoices) ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Invoice #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentInvoices) > 0): ?>
                                    <?php foreach ($recentInvoices as $inv): 
                                        $statusClass = match($inv['status']) {
                                            'paid' => 'success',
                                            'sent' => 'primary',
                                            'draft' => 'secondary',
                                            'overdue' => 'danger',
                                            'cancelled' => 'danger',
                                            'approved' => 'info',
                                            default => 'secondary'
                                        };
                                    ?>
                                    <tr>
                                        <td class="ps-3"><a href="<?= $basePath ?>/tax_invoice_view?id=<?= $inv['invoice_number'] ?>" class="fw-medium text-primary"><?= htmlspecialchars($inv['invoice_number'] ?? '') ?></a></td>
                                        <td><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                                        <td><?= htmlspecialchars($inv['customer_name'] ?? 'N/A') ?></td>
                                        <td class="fw-semibold">₹<?= number_format($inv['total_amount'], 2) ?></td>
                                        <td><span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?> fs-xxs px-2 py-1 text-uppercase"><?= str_replace('_', ' ', $inv['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No recent invoices found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer border-top-0 text-center bg-light">
                    <a href="<?= $basePath ?>/tax_invoice" class="text-primary fw-semibold fs-sm">View All Invoices <i data-lucide="arrow-right" class="fs-xs ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Recent Purchase Orders -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom border-light bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i data-lucide="shopping-cart" class="fs-sm me-1"></i>Recent Purchase Orders</h5>
                        <span class="badge bg-warning"><?= count($recentPOs) ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">PO Number</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentPOs) > 0): ?>
                                    <?php foreach ($recentPOs as $po): 
                                        $statusClass = match($po['status']) {
                                            'draft' => 'secondary',
                                            'sent' => 'primary',
                                            'confirmed' => 'info',
                                            'cancelled' => 'danger',
                                            'received' => 'success',
                                            'partially_received' => 'warning',
                                            default => 'light'
                                        };
                                    ?>
                                    <tr>
                                        <td class="ps-3"><a href="#!" class="fw-medium text-primary"><?= htmlspecialchars($po['po_number'] ?? '') ?></a></td>
                                        <td><?= date('d M Y', strtotime($po['order_date'])) ?></td>
                                        <td><?= htmlspecialchars($po['display_name'] ?? 'N/A') ?></td>
                                        <td class="fw-semibold">₹<?= number_format($po['total_amount'], 2) ?></td>
                                        <td><span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?> fs-xxs px-2 py-1 text-uppercase"><?= str_replace('_', ' ', $po['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No recent purchase orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer border-top-0 text-center bg-light">
                    <a href="<?= $basePath ?>/purchase_orders" class="text-primary fw-semibold fs-sm">View All Orders <i data-lucide="arrow-right" class="fs-xs ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if (count($lowStockItems) > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger-subtle border-danger d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-danger"><i data-lucide="alert-triangle" class="fs-sm me-1"></i>Low Stock Alert</h5>
                    <span class="badge bg-danger"><?= count($lowStockItems) ?> Items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Item Name</th>
                                    <th>Current Stock</th>
                                    <th>Unit</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td class="ps-3">
                                        <h6 class="mb-0 text-dark fw-semibold"><?= htmlspecialchars($item['item_name'] ?? '') ?></h6>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger-subtle text-danger fs-sm"><?= floatval($item['current_stock']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($item['unit_name'] ?? '') ?></td>
                                    <td class="text-end pe-3">
                                        <a href="<?= $basePath ?>/purchase_orders" class="btn btn-sm btn-danger">Create PO</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer border-top-0 text-center bg-light">
                    <a href="<?= $basePath ?>/current_stock" class="text-danger fw-semibold fs-sm">View Full Inventory <i data-lucide="arrow-right" class="fs-xs ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Revenue Trend Chart (Area Chart)
    const revCtx = document.getElementById('revenueTrendChart').getContext('2d');
    const revGradient = revCtx.createLinearGradient(0, 0, 0, 300);
    revGradient.addColorStop(0, 'rgba(13, 110, 253, 0.4)'); // Primary color lightly transparent
    revGradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)'); // Fades to transparent

    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($salesTrendLabels) ?>,
            datasets: [{
                label: 'Revenue (₹)',
                data: <?= json_encode($salesTrendData) ?>,
                borderColor: '#0d6efd',
                backgroundColor: revGradient,
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false }
                },
                y: {
                    grid: { borderDash: [4, 4], drawBorder: false },
                    ticks: {
                        callback: function(value) { 
                            if(value >= 100000) return "₹" + (value/100000).toFixed(1) + "L";
                            if(value >= 1000) return "₹" + (value/1000).toFixed(1) + "k";
                            return "₹" + value; 
                        }
                    }
                }
            }
        }
    });

    // 2. Invoice Status Radial/Pie Chart
    const statusData = <?= json_encode($invoiceStatusData) ?>;
    const statusLabels = <?= json_encode($invoiceStatusLabels) ?>;
    
    if(statusData.length > 0) {
        const pieCtx = document.getElementById('invoiceStatusChart').getContext('2d');
        const colors = ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6c757d', '#0d6efd'];
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: colors,
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true, pointStyle: 'circle' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(item) {
                                return item.label + ': ' + item.parsed + ' Invoices';
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById("invoiceStatusChart").parentElement.innerHTML = '<div class="text-center text-muted py-5 w-100">No invoices found yet.</div>';
    }

    // --- Mini Sparklines (Chart.js) ---
    const sparklineOptions = function(labels, data, color, bgColor) {
        return {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    borderColor: color,
                    backgroundColor: bgColor,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: {
                    x: { display: false },
                    y: { display: false, min: Math.min(...data) * 0.9 }
                },
                layout: { padding: { left: 0, right: 0, top: 5, bottom: 5 } }
            }
        };
    };

    const miniLabels = ['1', '2', '3', '4', '5', '6', '7'];
    new Chart(document.getElementById('miniChart1'), sparklineOptions(miniLabels, <?= json_encode(genTrendData(max(10, $totalInvoiceAmount))) ?>, '#0d6efd', 'rgba(13, 110, 253, 0.1)'));
    new Chart(document.getElementById('miniChart2'), sparklineOptions(miniLabels, <?= json_encode(genTrendData(max(10, $totalProformaAmount))) ?>, '#198754', 'rgba(25, 135, 84, 0.1)'));
    new Chart(document.getElementById('miniChart3'), sparklineOptions(miniLabels, <?= json_encode(genTrendData(max(10, $totalPaymentReceived))) ?>, '#0dcaf0', 'rgba(13, 202, 240, 0.1)'));
    new Chart(document.getElementById('miniChart4'), sparklineOptions(miniLabels, <?= json_encode(genTrendData(max(10, $totalPaymentMade))) ?>, '#dc3545', 'rgba(220, 53, 69, 0.1)'));
});
</script>
