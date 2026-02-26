<?php
$title = "Customer Report";

// Determine which report type from URL parameter or default
$report_type = isset($_GET['type']) ? $_GET['type'] : 'contact';
$page_title = 'Customer Contact Report';
if ($report_type === 'birthday') $page_title = 'Customer Birthday Report';
if ($report_type === 'anniversary') $page_title = 'Customer Anniversary Report';

// Include controller
require_once __DIR__ . '/../../controller/reports/customer_reports.php';
?>

<style>
    @media print {
        .d-print-none { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { display: none !important; }
        .container-fluid { padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        .table-responsive { overflow: visible !important; }
        .page-wrapper { margin-left: 0 !important; }
        body { background-color: white !important; }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="GET" id="filter_form">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label text-muted">Report Type</label>
                                        <select name="type" class="form-select" id="reportTypeSelect">
                                            <option value="contact" <?= $report_type === 'contact' ? 'selected' : '' ?>>Customer Contact Report</option>
                                            <option value="birthday" <?= $report_type === 'birthday' ? 'selected' : '' ?>>Customer Birthday Report</option>
                                            <option value="anniversary" <?= $report_type === 'anniversary' ? 'selected' : '' ?>>Customer Anniversary Report</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12" id="monthFilterContainer" style="<?= $report_type === 'contact' ? 'display:none;' : '' ?>">
                                        <label class="form-label text-muted">Filter by Month</label>
                                        <select name="month" class="form-select">
                                            <option value="0">All Months</option>
                                            <?php foreach($months as $num => $name): ?>
                                                <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label text-muted">Search Customer</label>
                                        <input type="text" name="q" class="form-control" placeholder="Name, Code, Phone..." value="<?= htmlspecialchars($search_query) ?>">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="<?= $basePath ?>/customer_reports?type=<?= $report_type ?>" class="btn btn-warning"><i class="ti ti-refresh me-1"></i> Reset</a>
                            <button type="submit" form="filter_form" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const typeSelect = document.getElementById('reportTypeSelect');
                    const monthContainer = document.getElementById('monthFilterContainer');
                    
                    if(typeSelect && monthContainer) {
                        typeSelect.addEventListener('change', function() {
                            if(this.value === 'contact') {
                                monthContainer.style.display = 'none';
                            } else {
                                monthContainer.style.display = 'block';
                            }
                        });
                    }
                });
            </script>

            <!-- Report Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center d-print-none">
                    <h5 class="card-title mb-0"><?= $page_title ?></h5>
                    <div class="d-print-none d-flex gap-2">
                        <?php $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '?type=' . $report_type; ?>
                        <a href="<?= $basePath ?>/controller/reports/export_customer_reports_excel.php<?= $qs ?>" class="btn btn-success">
                            <i class="ti ti-download me-1"></i> Export Excel
                        </a>
                        <button class="btn btn-info" onclick="window.print()"><i class="ti ti-printer me-1"></i> Print</button>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="ti ti-filter me-1"></i> Filter</button>
                    </div>
                </div>
                
                <div class="d-none d-print-block text-center mb-4">
                    <h3><?= $page_title ?></h3>
                    <p class="mb-0">Run Date: <?= date('d M Y') ?></p>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Contact Info</th>
                                    <th>Type</th>
                                    <?php if($report_type === 'contact'): ?>
                                        <th>Address / Location</th>
                                    <?php endif; ?>
                                    <?php if($report_type === 'birthday' || $report_type === 'contact'): ?>
                                        <th>Date of Birth</th>
                                    <?php endif; ?>
                                    <?php if($report_type === 'anniversary' || $report_type === 'contact'): ?>
                                        <th>Anniversary</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($customers)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach($customers as $row): 
                                        $dob = $row['date_of_birth'] ? date('d M Y', strtotime($row['date_of_birth'])) : '-';
                                        $anniversary = $row['anniversary_date'] ? date('d M Y', strtotime($row['anniversary_date'])) : '-';
                                        
                                        // Highlight current month dates
                                        $isBirthdayMonth = $row['date_of_birth'] && date('m', strtotime($row['date_of_birth'])) == date('m');
                                        $isAnniversaryMonth = $row['anniversary_date'] && date('m', strtotime($row['anniversary_date'])) == date('m');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark mb-0"><?= htmlspecialchars($row['customer_name']) ?></div>
                                            <?php if($row['company_name']): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars($row['company_name']) ?></small>
                                            <?php endif; ?>
                                            <small class="text-muted"><?= htmlspecialchars($row['customer_code']) ?></small>
                                        </td>
                                        <td>
                                            <?php if($row['phone']): ?>
                                                <div class="mb-1"><i class="ti ti-phone me-1 text-muted"></i><?= htmlspecialchars($row['phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if($row['email']): ?>
                                                <div><i class="ti ti-mail me-1 text-muted"></i><?= htmlspecialchars($row['email']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['customers_type_name']) ?></span></td>
                                        
                                        <?php if($report_type === 'contact'): ?>
                                            <td>
                                                <?php if(!empty($row['address_line1'])): ?>
                                                    <div class="mb-1 text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['address_line1']) ?></div>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($row['city'] ?? '-') ?>, <?= htmlspecialchars($row['state'] ?? '') ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if($report_type === 'birthday' || $report_type === 'contact'): ?>
                                            <td class="<?= $isBirthdayMonth ? 'bg-success-subtle text-success fw-bold' : '' ?>">
                                                <?= $dob ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if($report_type === 'anniversary' || $report_type === 'contact'): ?>
                                            <td class="<?= $isAnniversaryMonth ? 'bg-warning-subtle text-warning-emphasis fw-bold' : '' ?>">
                                                <?= $anniversary ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
