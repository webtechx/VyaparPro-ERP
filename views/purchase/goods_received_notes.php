<?php
$title = 'Goods Received Notes (GRN)';
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-xl-12">
            
            <!-- 1. Search Section -->
            <div class="card mb-4" id="search_section">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create New GRN</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Scan / Enter PO Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-scan"></i></span>
                                <input type="text" id="search_po_number" class="form-control" placeholder="e.g. PO-ABC-0001" autofocus>
                                <button class="btn btn-primary" type="button" id="fetch_btn">Fetch Details</button>
                            </div>
                            <div class="form-text">Enter Approved PO Number to generate GRN.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Create GRN Form (Hidden Initially) -->
            <form action="<?= $basePath ?>/controller/purchase/create_grn.php" method="POST" id="grn_form" class="d-none">
                <input type="hidden" name="save_grn" value="1">
                <input type="hidden" name="po_id" id="po_id">
                <input type="hidden" name="vendor_id" id="vendor_id">
                
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 text-white">New Goods Received Note</h5>
                        <button type="button" class="btn btn-sm btn-light text-primary" onclick="resetPage()">Cancel / Change PO</button>
                    </div>
                    <div class="card-body">
                        
                        <!-- Primary Info -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="text-muted small text-uppercase">Vendor</label>
                                <div class="fw-bold fs-5" id="disp_vendor"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small text-uppercase">PO Number</label>
                                <div class="fw-bold fs-5" id="disp_po_number"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small text-uppercase">PO Date</label>
                                <div class="fw-bold" id="disp_po_date"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small text-uppercase">Ref No</label>
                                <div class="fw-bold" id="disp_ref_no"></div>
                            </div>
                        </div>
                        
                        <hr>

                        <!-- GRN Inputs -->
                        <div class="row g-3 mb-4 bg-light p-3 rounded">
                            <div class="col-md-3">
                                <label class="form-label required">GRN Number</label>
                                <input type="text" name="grn_number" id="grn_number" class="form-control" required readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">GRN Date</label>
                                <?php 
                                    // Check permission for backdating
                                    $can_backdate = false;
                                    // Strictly rely on Access Control Permission
                                    if(isset($_SESSION['permissions']['grn_backdate']['view']) && $_SESSION['permissions']['grn_backdate']['view'] == 1) {
                                        $can_backdate = true;
                                    }
                                    
                                    // Super Admin Bypass (Check by Role Name)
                                    if (isset($currentUser['role_name']) && strcasecmp($currentUser['role_name'], 'SUPER ADMIN') === 0) {
                                        $can_backdate = true;
                                    }
                                    
                                    // If cannot backdate, restrict min date to Today. Future dates allowed.
                                    $dateAttr = $can_backdate ? '' : 'min="'.date('Y-m-d').'"';
                                ?>
                                <input type="date" name="grn_date" class="form-control" value="<?= date('Y-m-d') ?>" required <?= $dateAttr ?>>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Challan / Delivery Note No</label>
                                <input type="text" name="challan_no" class="form-control" placeholder="Vendor's Doc No">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">General Remarks</label>
                                <textarea name="grn_remarks" class="form-control" rows="1"></textarea>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20%">Item Details</th>
                                        <th style="width: 10%" class="text-center">HSN</th>
                                        <th style="width: 10%" class="text-end">Rate</th>
                                        <th style="width: 10%" class="text-center">Ord. Qty</th>
                                        <th style="width: 10%" class="text-end">Ord. Value</th>
                                        <th style="width: 10%" class="text-center">Rec. Qty</th>
                                        <th style="width: 10%" class="text-end">Rec. Value</th>
                                        <th style="width: 15%">Condition</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="items_body">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-success btn-lg"><i class="ti ti-device-floppy me-2"></i>Create GRN</button>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>

    
    <!-- 3. Existing GRNs List -->
    <div class="row" id="grn_list_section">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Goods Received Notes</h5>
                    <form method="GET" class="d-flex align-items-center">
                        <a href="<?= $basePath ?>/controller/purchase/export_grn_list_excel.php" class="btn btn-success btn-sm ms-2">
                            <i class="ti ti-file-spreadsheet me-1"></i> Excel
                        </a>
                        <button type="button" class="btn btn-info btn-sm ms-2" onclick="printGRNList()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <table data-tables="basic" class="table table-hover table-striped dt-responsive align-middle mb-0" id="grn_table" style="width: 100%;">
                        <thead class="table-light">
                                <tr>
                                    <th>GRN Number</th>
                                    <th>Date</th>
                                    <th>PO Number</th>
                                    <th>Vendor</th>
                                    <th>Challan No</th>
                                    <th class="no-export">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $gSql = "SELECT grn.*, po.po_number, v.display_name 
                                         FROM goods_received_notes grn
                                         LEFT JOIN purchase_orders po ON grn.po_id = po.purchase_orders_id
                                         LEFT JOIN vendors_listing v ON grn.vendor_id = v.vendor_id
                                         WHERE 1=1 ORDER BY grn.grn_id DESC";
                                         
                                $gRes = $conn->query($gSql);
                                if($gRes && $gRes->num_rows > 0){
                                    while($grow = $gRes->fetch_assoc()){
                                        ?>
                                        <tr>
                                            <td><span class="fw-bold text-primary"><?= htmlspecialchars($grow['grn_number']) ?></span></td>
                                            <td><?= date('d M Y', strtotime($grow['grn_date'])) ?></td>
                                            <td><?= htmlspecialchars($grow['po_number']) ?></td>
                                            <td><?= htmlspecialchars(ucwords(strtolower($grow['display_name']))) ?></td>
                                            <td><?= htmlspecialchars($grow['challan_no'] ?: '-') ?></td>
                                            <td class="no-export">
                                                <button class="btn btn-sm btn-light me-1" onclick="viewGRN(<?= $grow['grn_id'] ?>)" title="View Details">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                                <a href="<?= $basePath ?>/print_grn?id=<?= $grow['grn_id'] ?>" target="_blank" class="btn btn-sm btn-light text-dark" title="Print GRN">
                                                    <i class="ti ti-printer"></i>
                                                </a>
                                                <a href="<?= $basePath ?>/controller/purchase/export_grn_excel.php?id=<?= $grow['grn_id'] ?>" class="btn btn-sm btn-light text-success" title="Export Excel">
                                                    <i class="ti ti-file-spreadsheet"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center text-muted py-4">No GRNs created yet.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- SheetJS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- VIEW GRN MODAL -->
<div class="modal fade" id="viewGrnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">GRN Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modal_loader" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="modal_content" class="d-none">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase small">GRN Number</h6>
                            <p class="fw-bold fs-5" id="v_grn_number"></p>
                        </div>
                        <div class="col-sm-6 text-end">
                            <h6 class="text-muted text-uppercase small">Date</h6>
                            <p class="fw-bold" id="v_grn_date"></p>
                        </div>
                    </div>
                    <div class="row mb-4 p-3 bg-light rounded mx-0">
                        <div class="col-sm-4">
                            <small class="text-muted">PO Number</small>
                            <div class="fw-bold" id="v_po_number"></div>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted">Vendor</small>
                            <div class="fw-bold" id="v_vendor"></div>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted">Challan No</small>
                            <div class="fw-bold" id="v_challan"></div>
                        </div>
                        <div class="col-12 mt-2">
                             <small class="text-muted">Remarks</small>
                             <div class="fst-italic" id="v_remarks"></div>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Received Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">HSN</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-center">Ord. Qty</th>
                                    <th class="text-end">Ord. Value</th>
                                    <th class="text-center">Rec. Qty</th>
                                    <th class="text-end">Rec. Value</th>
                                    <th class="text-center">Condition</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="v_items_body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function exportTableToExcel(tableId, filename = 'excel_data') {
    var table = document.getElementById(tableId);
    var cloneTable = table.cloneNode(true);
    
    // Remove no-export cols
    cloneTable.querySelectorAll('.no-export').forEach(e => e.remove());
    
    var wb = XLSX.utils.table_to_book(cloneTable, {sheet: "Sheet1"});
    return XLSX.writeFile(wb, filename + ".xlsx");
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto focus search
    if(document.getElementById('search_po_number')) {
        document.getElementById('search_po_number').focus();
        document.getElementById('search_po_number').addEventListener('keypress', function(e){
            if(e.key === 'Enter') fetchPO();
        });
    }

    // Generate GRN Number
    const grnInput = document.getElementById('grn_number');
    if(grnInput){
        fetch('<?= $basePath ?>/controller/purchase/get_next_grn_number.php')
        .then(response => response.json())
        .then(data => {
            if(data.next_grn_number){
                grnInput.value = data.next_grn_number;
            }
        })
        .catch(err => console.error('Error fetching GRN number', err));
    }

    // Search events
    const fetchBtn = document.getElementById('fetch_btn');
    if(fetchBtn) fetchBtn.addEventListener('click', fetchPO);
});

/* ... existing fetchPO and populateForm ... */

function viewGRN(id) {
    const modalEl = document.getElementById('viewGrnModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    document.getElementById('modal_loader').classList.remove('d-none');
    document.getElementById('modal_content').classList.add('d-none');
    
    fetch(`<?= $basePath ?>/controller/purchase/get_grn_details.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const h = data.header;
            document.getElementById('v_grn_number').innerText = h.grn_number;
            document.getElementById('v_grn_date').innerText = h.grn_date;
            document.getElementById('v_po_number').innerText = h.po_number;
            document.getElementById('v_vendor').innerText = h.vendor_name;
            document.getElementById('v_challan').innerText = h.challan_no || '-';
            document.getElementById('v_remarks').innerText = h.remarks || 'No remarks';
            
            const tbody = document.getElementById('v_items_body');
            tbody.innerHTML = '';
            data.items.forEach(item => {
                const rate = parseFloat(item.po_rate || 0);
                const ordQty = parseFloat(item.ordered_qty || 0);
                const recQty = parseFloat(item.received_qty || 0);
                const ordVal = (ordQty * rate).toFixed(2);
                const recVal = (recQty * rate).toFixed(2);

                tbody.innerHTML += `
                    <tr>
                        <td>
                            <div class="fw-bold">${item.item_name}</div>
                            <small class="text-muted">${item.unit_name || ''}</small>
                        </td>
                        <td class="text-center">${item.hsn_code || '-'}</td>
                        <td class="text-end">${rate.toFixed(2)}</td>
                        <td class="text-center">${ordQty}</td>
                        <td class="text-end">${ordVal}</td>
                        <td class="text-center fw-bold">${recQty}</td>
                        <td class="text-end fw-bold text-success">${recVal}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${item.condition_status}</span></td>
                        <td><small>${item.remarks || ''}</small></td>
                    </tr>
                `;
            });
            
            document.getElementById('modal_loader').classList.add('d-none');
            document.getElementById('modal_content').classList.remove('d-none');
        } else {
            alert(data.message);
            modal.hide();
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to load details');
    });
}


function fetchPO() {
    const poNum = document.getElementById('search_po_number').value.trim();
    if(!poNum) {
        alert('Please enter a PO Number');
        return;
    }

    const btn = document.getElementById('fetch_btn');
    const originalText = btn.innerText;
    btn.innerText = 'Searching...';
    btn.disabled = true;

    // Remove old alerts
    document.querySelectorAll('.result-alert').forEach(e => e.remove());

    fetch(`<?= $basePath ?>/controller/purchase/get_po_for_grn.php?po_number=${encodeURIComponent(poNum)}`)
        .then(response => response.json())
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;

            if(data.status === 'success') {
                populateForm(data.po, data.items);
            } else {
                alert(data.message || 'Error fetching details');
                document.getElementById('search_po_number').select();
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerText = originalText;
            btn.disabled = false;
            alert('System Error occurred');
        });
}

function populateForm(po, items) {
    // Hide Search, Show Form
    document.getElementById('search_section').classList.add('d-none');
    document.getElementById('grn_form').classList.remove('d-none');

    // Header Data
    document.getElementById('po_id').value = po.purchase_orders_id;
    document.getElementById('vendor_id').value = po.vendor_id;
    
    document.getElementById('disp_vendor').innerText = po.vendor_name;
    document.getElementById('disp_po_number').innerText = po.po_number;
    document.getElementById('disp_po_date').innerText = po.order_date;
    document.getElementById('disp_ref_no').innerText = po.reference_no || '-';

    // Items
    const tbody = document.getElementById('items_body');
    tbody.innerHTML = '';

    items.forEach((item, index) => {
        const row = document.createElement('tr');
        
        const receivedQty = parseFloat(item.quantity) || 0; 
        const rate = parseFloat(item.rate) || 0;
        const orderedVal = (item.quantity * rate).toFixed(2);
        const receivedVal = (receivedQty * rate).toFixed(2);
        
        row.innerHTML = `
            <td>
                <div class="fw-bold">${item.item_name}</div>
                <small class="text-muted">${item.unit_name || ''}</small>
                <input type="hidden" name="items[${index}][po_item_id]" value="${item.purchase_order_items_id || item.id || ''}">
                <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                <input type="hidden" name="items[${index}][rate]" value="${rate}" class="item-rate">
            </td>
            <td class="text-center">
                ${item.hsn_code || '-'}
            </td>
            <td class="text-end">
                ${rate.toFixed(2)}
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark border fs-6" title="Total Ordered">${item.quantity}</span>
                ${parseFloat(item.already_received) > 0 ? `<br><small class="text-muted">Already Rec: ${item.already_received}</small>` : ''}
                <input type="hidden" name="items[${index}][ordered_qty]" value="${item.quantity}">
            </td>
            <td class="text-end">
                ${orderedVal}
            </td>
            <td>
                <input type="number" name="items[${index}][received_qty]" class="form-control text-center fw-bold" 
                       value="${item.remaining_qty}" min="0" max="${item.remaining_qty}" step="0.01" required 
                       oninput="calculateReceivedValue(this)">
            </td>
            <td class="text-end">
                <span class="rec-value-display fw-bold text-success">${receivedVal}</span>
            </td>
            <td>
                <select name="items[${index}][condition]" class="form-select border-0 bg-transparent">
                    <option value="Good">Good</option>
                    <option value="Damaged">Damaged</option>
                    <option value="Wrong Item">Wrong Item</option>
                </select>
            </td>
            <td>
                <input type="text" name="items[${index}][remarks]" class="form-control form-control-sm" placeholder="Optional">
            </td>
        `;

        tbody.appendChild(row);
    });
}

function calculateReceivedValue(input) {
    const row = input.closest('tr');
    const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
    const qty = parseFloat(input.value) || 0;
    const val = (qty * rate).toFixed(2);
    
    row.querySelector('.rec-value-display').innerText = val;
    
    // Optional: Visual cue if over-received
    // const max = parseFloat(input.getAttribute('max'));
    // if(qty > max) input.classList.add('text-danger');
    // else input.classList.remove('text-danger');
}

function validateQty(input, max) {
    // Deprecated / Merged into calculateReceivedValue visually, 
    // but specific validation typically managed by 'max' attr or form submit.
}

function resetPage() {
    if(confirm('Are you sure you want to cancel? Data will be lost.')) {
        window.location.reload();
    }
}
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #grn-print-area, #grn-print-area * { visibility: visible !important; }
    #grn-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="grn-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:16px;">
        <h3 style="margin:0;">Goods Received Notes</h3>
        <p style="margin:4px 0; font-size:12px; color:#666;">Printed on: <span id="grn-print-date"></span></p>
    </div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>GRN Number</th>
                <th>Date</th>
                <th>PO Number</th>
                <th>Vendor</th>
                <th>Challan No</th>
            </tr>
        </thead>
        <tbody id="grn-print-tbody"></tbody>
    </table>
</div>

<script>
function printGRNList() {
    var rows = document.querySelectorAll('#grn_table tbody tr');
    var tbody = document.getElementById('grn-print-tbody');
    tbody.innerHTML = '';
    rows.forEach(function(tr) {
        var tds = tr.querySelectorAll('td');
        if(tds.length < 5) return;
        tbody.innerHTML += '<tr>' +
            '<td>' + tds[0].textContent.trim() + '</td>' +
            '<td>' + tds[1].textContent.trim() + '</td>' +
            '<td>' + tds[2].textContent.trim() + '</td>' +
            '<td>' + tds[3].textContent.trim() + '</td>' +
            '<td>' + tds[4].textContent.trim() + '</td>' +
            '</tr>';
    });
    document.getElementById('grn-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('grn-print-area').style.display = 'block';
    window.print();
    document.getElementById('grn-print-area').style.display = 'none';
}
</script>
