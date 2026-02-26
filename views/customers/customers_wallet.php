<?php
$title = 'Customers Wallet';
require_once __DIR__ . '/../../config/auth_guard.php';

// Get selected customer ID
$selected_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

// Fetch Customers for Sidebar
$customers = [];
$cSql = "SELECT customer_id, customer_name, company_name, avatar, customer_code FROM customers_listing WHERE organization_id = ? ORDER BY customer_name ASC";
$stmt = $conn->prepare($cSql);
$stmt->bind_param("i", $_SESSION['organization_id']);
$stmt->execute();
$cRes = $stmt->get_result();
while($row = $cRes->fetch_assoc()){
    $customers[] = $row;
}
$stmt->close();

// Fetch Selected Customer Details
$customer = null;
$transactions = [];
$earned_history = [];

if($selected_customer_id > 0){
    // 1. Customer Profile
    $custSql = "SELECT * FROM customers_listing WHERE customer_id = ? AND organization_id = ?";
    $cStmt = $conn->prepare($custSql);
    $cStmt->bind_param("ii", $selected_customer_id, $_SESSION['organization_id']);
    $cStmt->execute();
    $customer = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();

    if($customer){
        // 2. Loyalty Transactions
        $transSql = "SELECT * FROM loyalty_point_transactions WHERE customer_id = ? ORDER BY created_at DESC";
        $tStmt = $conn->prepare($transSql);
        $tStmt->bind_param("i", $selected_customer_id);
        $tStmt->execute();
        $tRes = $tStmt->get_result();
        while($row = $tRes->fetch_assoc()){
            $transactions[] = $row;
        }
        $tStmt->close();

        // 3. Points Earned Details
        $earnSql = "SELECT lpe.*, si.invoice_number FROM loyalty_points_earned lpe LEFT JOIN sales_invoices si ON lpe.invoice_id = si.invoice_id WHERE lpe.customer_id = ? ORDER BY lpe.created_at DESC";
        $eStmt = $conn->prepare($earnSql);
        $eStmt->bind_param("i", $selected_customer_id);
        $eStmt->execute();
        $eRes = $eStmt->get_result();
        while($row = $eRes->fetch_assoc()){
            $earned_history[] = $row;
        }
        $eStmt->close();

        // 4. Ledger Entries
        $ledger_entries = [];
        $ledSql = "SELECT * FROM customers_ledger WHERE customer_id = ? ORDER BY transaction_date DESC, created_at DESC";
        $lStmt = $conn->prepare($ledSql);
        $lStmt->bind_param("i", $selected_customer_id);
        $lStmt->execute();
        $lRes = $lStmt->get_result();
        while($row = $lRes->fetch_assoc()){
            $ledger_entries[] = $row;
        }
        $lStmt->close();

        // 5. Commission Ledger Entries
        $commission_ledger = [];
        // Determine table name. Assuming it's created via the previous migration.
        // It's safer to check query success but assuming standard flow.
        $commSql = "SELECT cl.*, si.invoice_number FROM customers_commissions_ledger cl 
                    LEFT JOIN sales_invoices si ON cl.invoice_id = si.invoice_id 
                    WHERE cl.customer_id = ? ORDER BY cl.created_at DESC";
        $clStmt = $conn->prepare($commSql);
        if ($clStmt) {
            $clStmt->bind_param("i", $selected_customer_id);
            $clStmt->execute();
            $clRes = $clStmt->get_result();
            if ($clRes) {
                while($row = $clRes->fetch_assoc()){
                    $commission_ledger[] = $row;
                }
            }
            $clStmt->close();

        }

        // 6. Credit Notes
        $credit_notes = [];
        $cnSql = "SELECT cn.*, si.invoice_number 
                  FROM credit_notes cn 
                  LEFT JOIN sales_invoices si ON cn.invoice_id = si.invoice_id 
                  WHERE cn.customer_id = ? 
                  ORDER BY cn.created_at DESC";
        $cnStmt = $conn->prepare($cnSql);
        if($cnStmt){
            $cnStmt->bind_param("i", $selected_customer_id);
            $cnStmt->execute();
            $cnRes = $cnStmt->get_result();
            while($row = $cnRes->fetch_assoc()){
                $credit_notes[] = $row;
            }
            $cnStmt->close();
        }
    }
}
?>

<style>
    @media print {
        /* Hide layout structural elements */
        #main-wrapper > header, 
        .left-sidebar,
        .page-titles,
        .col-lg-3, 
        .card-header-tabs, 
        .btn, 
        .modal,
        .d-print-none { 
            display: none !important; 
        }

        /* Expand the main content width */
        .page-wrapper { margin-left: 0 !important; padding-top: 0 !important; }
        .col-lg-9 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        
        /* Clean up containers */
        .card { border: none !important; box-shadow: none !important; margin-bottom: 10px !important; }
        .card-body { padding: 0 !important; }

        /* Ensure active tab shows fully, inactive hidden */
        .tab-content > .tab-pane { display: none !important; }
        .tab-content > .active { display: block !important; opacity: 1 !important; visibility: visible !important; }

        /* Page breaking rules */
        table { page-break-inside: auto; }
        tr    { page-break-inside: avoid; page-break-after: auto; }
        th, td { font-size: 11px !important; padding: 4px !important; color: #000 !important; }
        
        body { background-color: white !important; }
        h3, h6 { color: #000 !important; }
    }
</style>

<div class="container-fluid">
    <div class="row g-3">
        <!-- LEFT SIDEBAR: Customer List -->
        <div class="col-lg-3 col-md-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Customers</h5>
                    <div class="mt-2">
                        <input type="text" id="customerSearch" class="form-control form-control-sm" placeholder="Search customer...">
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                    <div class="list-group list-group-flush" id="customerList">
                        <?php foreach($customers as $c): ?>
                            <?php 
                                $isActive = ($c['customer_id'] == $selected_customer_id) ? 'active' : ''; 
                                $initial = strtoupper(substr($c['customer_name'], 0, 1));
                            ?>
                            <a href="?customer_id=<?= $c['customer_id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?= $isActive ?> customer-item">
                                <?php if(!empty($c['avatar'])): ?>
                                    <img src="<?= $basePath ?>/<?= $c['avatar'] ?>" class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:32px; height:32px; min-width:32px;">
                                        <?= $initial ?>
                                    </div>
                                <?php endif; ?>
                                <div class="w-100 overflow-hidden">
                                    <div class="fw-semibold text-truncate"><?= $c['customer_name'] ?></div>
                                    <div class="small text-muted text-truncate"><?= $c['company_name'] ?: $c['customer_code'] ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <?php if(empty($customers)): ?>
                            <div class="p-3 text-center text-muted">No customers found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT CONTENT: Wallet Details -->
        <div class="col-lg-9 col-md-8">
            <?php if($selected_customer_id > 0 && $customer): ?>
                
                <!-- PROFILE HEADER -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <?php if(!empty($customer['avatar'])): ?>
                                <img src="<?= $basePath ?>/<?= $customer['avatar'] ?>" class="rounded-circle border border-3 border-light shadow-sm" width="80" height="80" style="object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-1 shadow-sm" style="width:80px; height:80px;">
                                    <?= strtoupper(substr($customer['customer_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h3 class="fw-bold mb-1"><?= $customer['customer_name'] ?></h3>
                                <div class="text-muted mb-2">
                                    <i class="ti ti-building me-1"></i><?= $customer['company_name'] ?? 'N/A' ?> &nbsp;|&nbsp; 
                                    <i class="ti ti-id me-1"></i><?= $customer['customer_code'] ?>
                                </div>
                                <div class="d-flex gap-3 fs-sm">
                                    <?php if($customer['email']): ?><span><i class="ti ti-mail me-1"></i><?= $customer['email'] ?></span><?php endif; ?>
                                    <?php if($customer['phone']): ?><span><i class="ti ti-phone me-1"></i><?= $customer['phone'] ?></span><?php endif; ?>
                                    <?php if($customer['city']): ?><span><i class="ti ti-map-pin me-1"></i><?= $customer['city'] ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STAT CARDS -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary-subtle border-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-primary text-uppercase fw-bold mb-1">Loyalty Points</h6>
                                        <h2 class="mb-0 fw-bold text-primary"><?= number_format($customer['loyalty_point_balance'], 2) ?></h2>
                                    </div>
                                    <div class="p-3 bg-primary text-white rounded-circle">
                                        <i class="ti ti-gift fs-2"></i>
                                    </div>
                                </div>
                                <div class="mt-3 small text-primary-emphasis">
                                    Total available points for redemption
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        // Commission Wallet Logic
                        $commBalance = floatval($customer['commissions_amount'] ?? 0);
                    ?>
                    <div class="col-md-4">
                         <div class="card bg-warning-subtle border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-warning-emphasis text-uppercase fw-bold mb-1">Commission Wallet</h6>
                                        <h2 class="mb-0 fw-bold text-warning-emphasis">₹ <?= number_format($commBalance, 2) ?></h2>
                                    </div>
                                    <div class="p-3 bg-warning text-white rounded-circle">
                                        <i class="ti ti-cash fs-2"></i>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="small text-warning-emphasis">
                                        Total commission earned
                                    </div>
                                    <button class="btn btn-sm btn-warning text-white py-1 px-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#payoutModal">
                                        <i class="ti ti-minus me-1"></i> Pay Out
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        $balance = floatval($customer['current_balance_due']);
                        $isAdvance = $balance < 0;
                        $balClass = $isAdvance ? 'text-success' : 'text-danger';
                        $bgClass = $isAdvance ? 'bg-success-subtle border-success' : 'bg-danger-subtle border-danger';
                        $iconBg = $isAdvance ? 'bg-success' : 'bg-danger';
                        $label = $isAdvance ? 'Advance Balance' : 'Outstanding Balance';
                        $subText = $isAdvance ? 'Advance available' : 'Current due amount';
                    ?>
                    <div class="col-md-4">
                        <div class="card <?= $bgClass ?> h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="<?= $balClass ?> text-uppercase fw-bold mb-1"><?= $label ?></h6>
                                        <h2 class="mb-0 fw-bold <?= $balClass ?>">₹ <?= number_format(abs($balance), 2) ?></h2>
                                    </div>
                                    <div class="p-3 <?= $iconBg ?> text-white rounded-circle">
                                        <i class="ti ti-wallet fs-2"></i>
                                    </div>
                                </div>
                                <div class="mt-3 small <?= $isAdvance ? 'text-success-emphasis' : 'text-danger-emphasis' ?>">
                                    <?= $subText ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABS & HISTORY -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                             <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-ledger">
                                    <i class="ti ti-book me-1"></i> Ledger
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-transactions">
                                    <i class="ti ti-history me-1"></i> Point Transactions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-earned">
                                    <i class="ti ti-trophy me-1"></i> Points Earned History
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-commission">
                                    <i class="ti ti-cash me-1"></i> Commission Ledger
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-credit-notes">
                                    <i class="ti ti-file-invoice me-1"></i> Credit Notes
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 ms-2">
                            <a href="<?= $basePath ?>/controller/customers/export_customer_wallet_excel.php?customer_id=<?= $selected_customer_id ?>" class="btn btn-success btn-sm">
                                <i class="ti ti-download me-1"></i> Export Excel
                            </a>
                            <button type="button" class="btn btn-info btn-sm" onclick="window.print()">
                                <i class="ti ti-printer me-1"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                             <!-- Ledger Tab -->
                            <div class="tab-pane active" id="tab-ledger">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Particulars</th>
                                                <th>Ref Type</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                                <th class="text-end">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($ledger_entries) > 0): ?>
                                                <?php foreach($ledger_entries as $l): ?>
                                                    <tr>
                                                        <td><?= date('d M Y', strtotime($l['transaction_date'])) ?></td>
                                                        <td><?= $l['particulars'] ?></td>
                                                        <td><span class="badge bg-light text-dark border"><?= ucwords($l['reference_type']) ?></span></td>
                                                        <td class="text-end fw-medium"><?= $l['debit'] > 0 ? '₹'.number_format($l['debit'], 2) : '-' ?></td>
                                                        <td class="text-end fw-medium"><?= $l['credit'] > 0 ? '₹'.number_format($l['credit'], 2) : '-' ?></td>
                                                        <td class="text-end fw-bold">₹<?= number_format($l['balance'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center py-4 text-muted">No ledger entries found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Transactions Tab -->
                            <div class="tab-pane" id="tab-transactions">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Ref / Note</th>
                                                <th class="text-end">Points</th>
                                                <th class="text-end">Balance</th>
                                                <th>Expiry</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($transactions) > 0): ?>
                                                <?php foreach($transactions as $t): ?>
                                                    <?php 
                                                        $transType = strtoupper($t['transaction_type']);
                                                        $badgeClass = match($transType) {
                                                            'EARNED', 'EARN' => 'bg-success-subtle text-success',
                                                            'REDEEMED', 'REDEEM' => 'bg-warning-subtle text-warning',
                                                            'EXPIRED' => 'bg-danger-subtle text-danger',
                                                            'ADJUSTMENT', 'ADJUST' => 'bg-info-subtle text-info',
                                                            default => 'bg-secondary-subtle text-secondary'
                                                        };
                                                        
                                                        // Determine Sign and Color
                                                        $isDeduction = in_array($transType, ['REDEEM', 'REDEEMED', 'EXPIRED']);
                                                        
                                                        if ($isDeduction) {
                                                            $sign = '-';
                                                            $ptClass = 'text-danger';
                                                            $displayPoints = number_format(abs($t['points']), 2);
                                                        } elseif ($t['points'] < 0) {
                                                            $sign = ''; // Native minus will show
                                                            $ptClass = 'text-danger';
                                                            $displayPoints = number_format($t['points'], 2);
                                                        } else {
                                                            $sign = '+';
                                                            $ptClass = 'text-success';
                                                            $displayPoints = number_format($t['points'], 2);
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></td>
                                                        <td><span class="badge <?= $badgeClass ?> text-uppercase"><?= $t['transaction_type'] ?></span></td>
                                                        <td><?= $t['note'] ?></td>
                                                        <td class="text-end fw-bold <?= $ptClass ?>"><?= $sign . $displayPoints ?></td>
                                                        <td class="text-end"><?= number_format($t['balance_after_transaction'], 2) ?></td>
                                                        <td><?= $t['expiry_date'] ? date('d M Y', strtotime($t['expiry_date'])) : '-' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center py-4 text-muted">No transactions found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Earned History Tab -->
                            <div class="tab-pane" id="tab-earned">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Invoice #</th>
                                                <th class="text-end">Bill Amount</th>
                                                <th class="text-end">Points Earned</th>
                                                <th>Valid Till</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($earned_history) > 0): ?>
                                                <?php foreach($earned_history as $e): ?>
                                                    <tr>
                                                        <td><?= date('d M Y', strtotime($e['created_at'])) ?></td>
                                                        <td>
                                                            <a href="#" class="text-dark fw-medium"><?= $e['invoice_number'] ?: '#'.$e['invoice_id'] ?></a>  
                                                        </td>
                                                        <td class="text-end">₹ <?= number_format($e['bill_amount'], 2) ?></td>
                                                        <td class="text-end fw-bold text-success">+<?= number_format($e['points_earned'], 2) ?></td>
                                                        <td><?= $e['valid_till'] ? date('d M Y', strtotime($e['valid_till'])) : '-' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted">No earning history found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Commission Ledger Tab -->
                            <div class="tab-pane" id="tab-commission">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Particulars / Ref</th>
                                                <th class="text-end">Amount</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($commission_ledger) > 0): ?>
                                                <?php foreach($commission_ledger as $c): ?>
                                                    <?php 
                                                        $amt = floatval($c['commission_amount']);
                                                        $isPayout = $amt < 0; 
                                                        $amtClass = $isPayout ? 'text-danger' : 'text-success';
                                                        $invRef = $c['invoice_number'] ? '<span class="badge bg-light text-dark border">'.$c['invoice_number'].'</span>' : '';
                                                        $note = $c['notes'] ?? '';
                                                        
                                                        // Fallback for older Payouts without notes or just explicit payout tag
                                                        if($isPayout && !$invRef) {
                                                            $invRef = '<span class="badge bg-danger-subtle text-danger border border-danger">PAYOUT</span>';
                                                        } else if (!$isPayout && !$invRef) {
                                                            $invRef = '<span class="badge bg-success-subtle text-success border border-success">ADJUST</span>';
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></td>
                                                        <td>
                                                            <?= $invRef ?>
                                                        </td>
                                                        <td class="text-end fw-bold <?= $amtClass ?>">
                                                            <?= number_format($amt, 2) ?>
                                                        </td>
                                                        <td class="small text-muted">
                                                            <?= $note ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center py-4 text-muted">No commission history found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Credit Notes Tab -->
                            <div class="tab-pane" id="tab-credit-notes">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Credit Note #</th>
                                                <th>Invoice #</th>
                                                <th class="text-end">Amount</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($credit_notes) > 0): ?>
                                                <?php foreach($credit_notes as $cn): ?>
                                                    <tr>
                                                        <td><?= date('d M Y', strtotime($cn['created_at'])) ?></td>
                                                        <td><span class="fw-bold text-dark"><?= $cn['credit_note_number'] ?? $cn['id'] ?></span></td>
                                                        <td>
                                                            <?php if(isset($cn['invoice_number']) && $cn['invoice_number']): ?>
                                                                <span class="badge bg-light text-dark border"><?= $cn['invoice_number'] ?></span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end fw-bold text-danger">
                                                            ₹<?= number_format($cn['total_amount'], 2) ?>
                                                        </td>
                                                        <td class="small text-muted"><?= $cn['reason'] ?? '' ?></td>
                                                        <td><span class="badge bg-success-subtle text-success border border-success"><?= ucfirst($cn['status'] ?? 'Active') ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center py-4 text-muted">No credit notes found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- EMPTY STATE -->
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted p-5 bg-white border rounded">
                    <?php if(empty($customers)): ?>
                         <div class="mb-3"><i class="ti ti-users fs-1"></i></div>
                         <h4>No Customers Found</h4>
                         <p>Add customers to view their wallet details.</p>
                    <?php else: ?>
                        <div class="mb-3"><i class="ti ti-fingerprint fs-1"></i></div>
                        <h4>Select a Customer</h4>
                        <p>Click on a customer from the list to view their wallet & transactions.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<!-- Payout Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="payoutForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Commission Payout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="customer_id" value="<?= $selected_customer_id ?>">
                
                <div class="mb-3">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mode</label>
                        <select name="payment_mode" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Adjustment">Adjustment</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Transaction Ref, Remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="savePayoutBtn">Confirm Payout</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search Functionality
    const searchInput = document.getElementById('customerSearch');
    const customerList = document.getElementById('customerList');
    if(searchInput && customerList) {
        const items = customerList.getElementsByClassName('customer-item');
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            Array.from(items).forEach(function(item) {
                const text = item.textContent.toLowerCase();
                if (text.includes(filter)) {
                    item.style.display = ''; // Reset
                    item.classList.add('d-flex'); // Restore flex
                } else {
                    item.style.display = 'none';
                    item.classList.remove('d-flex');
                }
            });
        });
    }

    // Payout Form Handling
    const payoutForm = document.getElementById('payoutForm');
    if(payoutForm) {
        payoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('savePayoutBtn');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Processing...';

            const formData = new FormData(payoutForm);

            fetch('<?= $basePath ?>/controller/customers/save_commission_payout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Payout recorded successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error occurred.');
                btn.disabled = false;
                btn.innerText = originalText;
            });
        });
    }
});
</script>
