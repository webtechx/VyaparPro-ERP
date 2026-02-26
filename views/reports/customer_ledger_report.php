<?php
$title = "Customer wise Ledger Report";
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/../../controller/reports/customer_ledger_report.php';
?>

<div class="container-fluid">
    <style>
        .customer-text-primary { color: #2a3547; }
        
        /* Highlight Theme */
        .select2-results__option--highlighted .customer-text-primary { color: white !important; }
        .select2-results__option--highlighted .customer-text-secondary { color: #e9ecef !important; }
        .select2-results__option--highlighted .status-badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; }
        .select2-results__option--highlighted .text-dark { color: white !important; }
        .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
        .select2-results__option--highlighted .badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; border-color: white !important; }
        .select2-results__option--highlighted .text-success { color: white !important; }
        .select2-results__option--highlighted .text-danger { color: white !important; }
        .select2-results__option--highlighted .customer-avatar { background-color: white !important; color: #5d87ff !important; }
        
        .customer-select2-dropdown .select2-results__options {
            max-height: 400px !important;
        }

        .select2-container--open .select2-dropdown {
            z-index: 9999999 !important;
        }
        .select2-dropdown {
            z-index: 9999999 !important;
        }
    </style>

    <div class="row">
        <div class="col-12">
            
            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" aria-hidden="true" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                                            <span class="input-group-text">to</span>
                                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Specific Customer (Optional)</label>
                                        <select name="customer_id" id="customer_id" class="form-select">
                                            <?php if($customer_id > 0 && !empty($customer_name_prefill)): ?>
                                                <option value="<?= $customer_id ?>" data-avatar="<?= htmlspecialchars($customer_avatar_prefill) ?>" selected><?= htmlspecialchars($customer_name_prefill) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Search Transaction</label>
                                        <input type="text" name="q" class="form-control" placeholder="Search Ref No..." value="<?= htmlspecialchars($search_query) ?>">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="<?= $basePath ?>/customer_ledger_report" class="btn btn-warning"><i class="ti ti-refresh me-1"></i> Reset</a>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ledger Detailed View -->
            <div class="card mb-3">
                <div class="card-body p-0">
                   <div class="row align-items-center p-4 border-bottom">
                        <div class="col-lg-8">
                            <div>
                                <?php if($customer_id > 0): ?>
                                    <span class="badge bg-primary-subtle text-primary mb-2">Customer Ledger</span>
                                    <h3 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($customer_name_prefill) ?></h3>
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-calendar me-1"></i> Period: 
                                        <span class="fw-medium text-dark"><?= date('d M Y', strtotime($start_date)) ?></span> to <span class="fw-medium text-dark"><?= date('d M Y', strtotime($end_date)) ?></span>
                                    </p>
                                <?php else: ?>
                                    <h3 class="mb-1 fw-bold text-dark">Customer wise Ledger Report</h3>
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-calendar me-1"></i> Period: 
                                        <span class="fw-medium text-dark"><?= date('d M Y', strtotime($start_date)) ?></span> to <span class="fw-medium text-dark"><?= date('d M Y', strtotime($end_date)) ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-lg-4 text-end d-print-none">
                            <div class="d-flex gap-2 justify-content-lg-end mt-3 mt-lg-0">
                                <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                                <a href="<?= $basePath ?>/controller/reports/export_customer_ledger_excel.php<?= $qs ?>" class="btn btn-success">
                                    <i class="ti ti-download me-1"></i> Excel
                                </a>
                                <button class="btn btn-info" onclick="window.print()" <?= $customer_id == 0 ? 'disabled' : '' ?>><i class="ti ti-printer me-1"></i> Print</button>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                    <i class="ti ti-filter me-1"></i> Filter
                                </button>
 

                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 w-100" id="customer_ledger_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Ref No</th>
                                    <th>Type</th>
                                    <th>Particulars</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $running_balance = 0; 
                                if($customer_id > 0) {
                                    $running_balance = $opening_balance;
                                }
                                
                                if(empty($transactions) && $customer_id > 0 && $running_balance == 0): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">No transactions found for the selected period.</td></tr>
                                <?php elseif(empty($transactions) && $customer_id == 0): ?>
                                     <tr><td colspan="7" class="text-center py-4 text-muted">Please select a customer to view ledger.</td></tr>
                                <?php else: ?>
                                    
                                    <?php if($customer_id > 0): ?>
                                    <!-- Opening Balance Row -->
                                    <tr class="bg-light fw-bold text-muted">
                                        <td colspan="4" class="text-end">Opening Balance</td>
                                        <td class="text-end fw-semibold text-danger"></td>
                                        <td class="text-end fw-semibold text-success"></td>
                                        <td class="text-end">
                                            ₹<?= number_format(abs($opening_balance), 2) ?> 
                                            <small><?= $opening_balance >= 0 ? 'Dr' : 'Cr' ?></small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <?php foreach($transactions as $row): 
                                        $debit = floatval($row['debit_amount']);
                                        $credit = floatval($row['credit_amount']);
                                        
                                        // Balance (Receivable) = Invoices (Debit) - Payments Received (Credit)
                                        $running_balance = $running_balance + $debit - $credit;
                                        
                                        $typeClass = match($row['type_code']) {
                                            'invoice' => 'info',
                                            'payment' => 'success',
                                            'credit_note' => 'warning',
                                            default => 'secondary'
                                        };
                                        $typeLabel = match($row['type_code']) {
                                            'invoice' => 'Invoice',
                                            'payment' => 'Payment',
                                            'credit_note' => 'Credit Note',
                                            default => ucfirst($row['type_code'])
                                        };
                                        
                                        $refLink = 'javascript:void(0);';
                                        if($row['type_code'] == 'invoice') $refLink = $basePath . '/views/sales/view_sales_invoice.php?id=' . $row['reference_id'];
                                        if($row['type_code'] == 'payment') $refLink = $basePath . '/views/payment/view_payment.php?id=' . $row['reference_id'];
                                        if($row['type_code'] == 'credit_note') $refLink = $basePath . '/view_credit_note?id=' . $row['reference_id'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="text-muted small"><?= date('d M Y', strtotime($row['trans_date'])) ?></div>
                                        </td>
                                        <td>
                                            <a href="<?= $refLink ?>" class="font-monospace fw-bold text-primary"><?= htmlspecialchars($row['ref_no'] ?? '') ?></a>
                                        </td>
                                        <td><span class="badge bg-<?= $typeClass ?>-subtle text-<?= $typeClass ?> border border-<?= $typeClass ?>-subtle"><?= $typeLabel ?></span></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($row['type_label'] ?? '') ?></div>
                                            <?php if(!empty($row['notes'])): ?><small class="text-muted d-block fst-italic"><?= htmlspecialchars(substr($row['notes'], 0, 50)) ?></small><?php endif; ?>
                                        </td>
                                        <td class="text-end fw-semibold text-danger">
                                            <?= $debit > 0 ? '₹'.number_format($debit, 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-semibold text-success">
                                            <?= $credit > 0 ? '₹'.number_format($credit, 2) : '-' ?>
                                        </td>
                                        <td class="text-end fw-bold">
                                            ₹<?= number_format(abs($running_balance), 2) ?> <small><?= $running_balance >= 0 ? 'Dr' : 'Cr' ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Closing Balance -->
                                    <tr class="table-active fw-bold border-top border-2">
                                        <td colspan="4" class="text-end">Closing Balance</td>
                                        <td class="text-end text-danger">₹<?= number_format($total_debit, 2) ?></td>
                                        <td class="text-end text-success">₹<?= number_format($total_credit, 2) ?></td>
                                        <td class="text-end">₹<?= number_format(abs($running_balance), 2) ?> <small><?= $running_balance >= 0 ? 'Dr' : 'Cr' ?></small></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


<script>
    const basePath = '<?= $basePath ?>';

    // Initialize Select2 when modal is shown
    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('shown.bs.modal', function () {
            if (!$('#customer_id').hasClass('select2-hidden-accessible')) {
                initCustomerSelect();
            }
        });
    }

    $(document).ready(function() {
        initCustomerSelect();
        // Prompt for customer if null
        <?php if($customer_id == 0): ?>
            var myModal = new bootstrap.Modal(document.getElementById('filterModal'));
            myModal.show();
        <?php endif; ?>
    });

    // Initialize Customer Select2
    function initCustomerSelect() {
        const $customer = $('#customer_id');
        if ($customer.hasClass('select2-hidden-accessible')) {
            $customer.select2('destroy');
        }

        $customer.select2({
            placeholder: 'Filter by Customer (Optional)...',
            width: '100%',
            dropdownCssClass: 'customer-select2-dropdown',
            allowClear: true,
            dropdownParent: $('#filterModal'),
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/billing/search_customers_listing.php',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    if (!Array.isArray(data)) data = [];
                    let results = data.map(c => ({
                        id: String(c.id),
                        text: c.customer_name,
                        display_name: c.display_name,
                        customer_name: c.customer_name || c.text,
                        company_name: c.company_name || '',
                        email: c.email || '',
                        phone: c.phone || '',
                        avatar: c.avatar || '',
                        customer_code: c.customer_code || '',
                        customers_type_name: c.customers_type_name || ''
                    }));
                    return { results };
                }
            },
            templateResult: formatRepoResult,
            templateSelection: formatRepoSelection,
            escapeMarkup: m => m
        });
    }

    function formatRepoResult(repo) {
        if (repo.loading) return repo.text;
        
        let name = repo.customer_name || repo.vendor_name || repo.text;
        let code = repo.customer_code || repo.vendor_code || '';
        let typeName = repo.customers_type_name || ''; 
        let letter = (name || '').charAt(0).toUpperCase();
        
        let avatarHtml = '';
        if(repo.avatar && repo.avatar.trim() !== ''){
            avatarHtml = `<img src="${basePath}/${repo.avatar}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">`;
        } else {
            avatarHtml = `<div class="customer-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;min-width:32px;">${letter}</div>`;
        }

        return `
            <div class="d-flex align-items-center gap-2 py-1">
                ${avatarHtml}
                <div class="flex-grow-1">
                    <div class="customer-text-primary fw-semibold lh-sm mb-1">
                        ${name} 
                        ${code ? `<span class="small text-muted fw-normal">(${code}${typeName ? ' - ' + typeName : ''})</span>` : ''}
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="d-flex flex-column small text-muted">
                            ${repo.phone ? `<span class="mb-1 text-nowrap"><i class="ti ti-phone me-1"></i>${repo.phone}</span>` : ''}
                            ${repo.email ? `<span class="text-break"><i class="ti ti-mail me-1"></i>${repo.email}</span>` : ''}
                        </div>
                        ${repo.company_name ? `<div class="small text-muted"><i class="ti ti-building me-1"></i>${repo.company_name}</div>` : ''}
                    </div>
                </div>
            </div>`;
    }

    function formatRepoSelection(repo) {
        if (!repo.id) return repo.text;
        let name = repo.customer_name || repo.vendor_name || repo.employee_name || repo.text;
        
        let avatar = repo.avatar || $(repo.element).data('avatar');
        let avatarHtml = '';
        if(avatar && avatar.trim() !== ''){
             let cleanAvatar = avatar.startsWith('/') ? avatar.substring(1) : avatar;
            avatarHtml = `<img src="${basePath}/${cleanAvatar}" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;vertical-align:middle;">`;
        } else {
             let letter = (name || '').charAt(0).toUpperCase();
             avatarHtml = `<span class="badge rounded-circle bg-primary text-white me-2 d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;vertical-align:middle;">${letter}</span>`;
        }
        
        return `<span>${avatarHtml}${name}</span>`;
    }

</script>
