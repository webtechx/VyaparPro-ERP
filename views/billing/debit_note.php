<?php
$title = 'Debit Notes (Purchase Returns)';
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-xl-12">
            
            <!-- 1. Search Section -->
            <div class="card mb-4" id="search_section">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create New Debit Note (Return)</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Scan / Enter PO Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-scan"></i></span>
                                <input type="text" id="search_po_number" class="form-control" placeholder="e.g. PO-ABC-0001" autofocus>
                                <button class="btn btn-primary" type="button" id="fetch_btn" onclick="fetchPO()">Fetch Details</button>
                            </div>
                            <div class="form-text">Enter PO Number to create a Debit Note (Return).</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Create Debit Note Form (Hidden Initially) -->
            <form action="<?= $basePath ?>/controller/purchase/create_debit_note.php" method="POST" id="dn_form" class="d-none">
                <input type="hidden" name="save_dn" value="1">
                <input type="hidden" name="po_id" id="po_id">
                <input type="hidden" name="vendor_id" id="vendor_id">
                <input type="hidden" name="dn_id" id="dn_id">
                <input type="hidden" name="mode" id="form_mode" value="create">
                
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 text-white">New Debit Note (Purchase Return)</h5>
                        <button type="button" class="btn btn-sm btn-light text-danger" onclick="resetPage()">Cancel / Change PO</button>
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

                        <!-- Debit Note Inputs -->
                        <div class="row g-3 mb-4 bg-light p-3 rounded">
                            <div class="col-md-3">
                                <label class="form-label required">Debit Note Number</label>
                                <input type="text" name="dn_number" id="dn_number" class="form-control" required readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Date</label>
                                <input type="date" name="dn_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">General Remarks / Reason</label>
                                <input type="text" name="dn_remarks" class="form-control" placeholder="Overall reason for return...">
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%">Item Details</th>
                                        <th style="width: 10%" class="text-end">Rate</th>
                                        <th style="width: 10%" class="text-center">Received (Total)</th>
                                        <th style="width: 10%" class="text-center">Already Returned</th>
                                        <th style="width: 10%" class="text-center">Available Return</th>
                                        <th style="width: 15%" class="text-center">Return Qty</th>
                                        <th style="width: 15%">Reason</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="items_body">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-danger btn-lg" id="submit_btn"><i class="ti ti-device-floppy me-2"></i>Create Debit Note</button>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>

    
    <!-- 3. Existing Debit Notes List -->
    <div class="row" id="dn_list_section">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Debit Notes</h5>
                    <form method="GET" class="d-flex align-items-center">
                        <a href="<?= $basePath ?>/controller/purchase/export_debit_notes_list_excel.php" class="btn btn-success btn-sm ms-2">
                            <i class="ti ti-file-spreadsheet me-1"></i> Excel
                        </a>
                        <button type="button" class="btn btn-info btn-sm ms-2" onclick="printDNList()">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <table data-tables="basic" class="table table-hover table-striped dt-responsive align-middle mb-0" id="dn_table" style="width: 100%;">
                        <thead class="table-light">
                                <tr>
                                    <th>DN Number</th>
                                    <th>Date</th>
                                    <th>PO Number</th>
                                    <th>Vendor</th>
                                    <th>Remarks</th>
                                    <th class="no-export">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $gSql = "SELECT dn.*, po.po_number, v.display_name 
                                         FROM debit_notes dn
                                         LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
                                         LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
                                         WHERE 1=1 ORDER BY dn.debit_note_id DESC";

                                $gRes = $conn->query($gSql);
                                if($gRes && $gRes->num_rows > 0){
                                    while($row = $gRes->fetch_assoc()){
                                        ?>
                                        <tr>
                                            <td><span class="fw-bold text-danger"><?= htmlspecialchars($row['debit_note_number']) ?></span></td>
                                            <td><?= date('d M Y', strtotime($row['debit_note_date'])) ?></td>
                                            <td><?= htmlspecialchars($row['po_number']) ?></td>
                                            <td><?= htmlspecialchars($row['display_name']) ?></td>
                                            <td><?= htmlspecialchars(substr($row['remarks'], 0, 50)) ?></td>
                                            <td class="no-export">
                                                <button class="btn btn-sm btn-light me-1" onclick="viewDN(<?= $row['debit_note_id'] ?>)" title="View Details">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                                <!-- Edit (Only allowed if no subsequent transaction exists? Assuming allowed for now) -->
                                                <button class="btn btn-sm btn-light me-1 text-primary" onclick="editDN(<?= $row['debit_note_id'] ?>)" title="Edit">
                                                    <i class="ti ti-pencil"></i>
                                                </button>
                                                <a href="<?= $basePath ?>/views/billing/print_debit_note.php?id=<?= $row['debit_note_id'] ?>" target="_blank" class="btn btn-sm btn-light me-1 text-dark" title="Print">
                                                    <i class="ti ti-printer"></i>
                                                </a>
                                                <a href="<?= $basePath ?>/controller/purchase/export_debit_note_pdf.php?id=<?= $row['debit_note_id'] ?>" class="btn btn-sm btn-light text-danger" title="Download PDF">
                                                    <i class="ti ti-file-type-pdf"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center text-muted py-4">No Debit Notes found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-5"></div>
</div>

<!-- SheetJS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- VIEW DN MODAL -->
<div class="modal fade" id="viewDnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white">Debit Note Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modal_loader" class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
                <div id="modal_content" class="d-none">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase small">Debit Note #</h6>
                            <p class="fw-bold fs-5" id="v_dn_number"></p>
                        </div>
                        <div class="col-sm-6 text-end">
                            <h6 class="text-muted text-uppercase small">Date</h6>
                            <p class="fw-bold" id="v_dn_date"></p>
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
                             <small class="text-muted">Remarks</small>
                             <div class="fst-italic" id="v_remarks"></div>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Returned Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Rate (PO)</th>
                                    <th class="text-center">Return Qty</th>
                                    <th class="text-end">Return Value</th>
                                    <th>Reason</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="v_items_body"></tbody>
                        </table>
                    </div>
                </div>
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

    // Generate DN Number
    const input = document.getElementById('dn_number');
    if(input){
        const orgShortCode = '<?= $_SESSION['organization_short_code'] ?? 'DN' ?>';
        // We can either do a random generation OR fetch next sequential number via AJAX (better).
        // Since original code used random, we will update format but keep random for now unless sequential requested.
        // Requested: DN-$_SESSION['organization_short_code']-0001
        
        // Let's fetch the next sequential number properly via AJAX to be consistent with POs
        // Or if we must stick to client side for now:
        // But user asked for 0001 format. Let's try to fetch it or default to a safe random if not.
        
        // Actually, let's just make an AJAX call to get the next number, it's safer.
        fetch('<?= $basePath ?>/controller/purchase/get_next_dn_number.php')
            .then(r => r.json())
            .then(d => {
                input.value = d.next_dn_number;
            })
            .catch(e => {
                console.error("Could not fetch next DN number", e);
                // Fallback
                const dateStr = new Date().toISOString().slice(0,10).replace(/-/g,'');
                input.value = `DN-${orgShortCode}-${dateStr}`;
            });
    }


});

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

    fetch(`<?= $basePath ?>/controller/purchase/get_po_for_debit_note.php?po_number=${encodeURIComponent(poNum)}`)
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
    document.getElementById('dn_form').classList.remove('d-none');

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
        // Edit Mode specific fields
        const currentQty = parseFloat(item.current_return_qty) || 0;
        const currentReason = item.current_reason || 'Damaged';
        const currentRemarks = item.current_remarks || '';
        const alreadyReturned = parseFloat(item.returned_qty_others) || parseFloat(item.returned_qty) || 0;

        const rate = parseFloat(item.rate) || parseFloat(item.po_rate) || 0;
        const available = parseFloat(item.available_qty) || 0;
        
        const row = document.createElement('tr');
        if(available <= 0 && currentQty <= 0) row.classList.add('bg-light', 'text-muted');

        row.innerHTML = `
            <td>
                <div class="fw-bold">${item.item_name}</div>
                <small class="text-muted">${item.unit_name || ''}</small>
                <input type="hidden" name="items[${index}][po_item_id]" value="${item.purchase_order_items_id || item.id || ''}">
                <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
            </td>
            <td class="text-end">
                ${rate.toFixed(2)}
            </td>
            <td class="text-center">
                ${item.received_qty}
            </td>
            <td class="text-center">
                ${alreadyReturned}
            </td>
            <td class="text-center fw-bold">
                ${available}
            </td>
            <td>
                <input type="number" name="items[${index}][return_qty]" class="form-control text-center fw-bold text-danger" 
                       value="${currentQty > 0 ? currentQty : 0}" min="0" max="${available}" step="0.01" 
                       ${(available <= 0 && currentQty <= 0) ? 'disabled' : ''}>
            </td>
            <td>
                <select name="items[${index}][reason]" class="form-select border-0 bg-transparent" ${(available <= 0 && currentQty <= 0) ? 'disabled' : ''}>
                    <option value="Damaged" ${currentReason === 'Damaged' ? 'selected' : ''}>Damaged</option>
                    <option value="Wrong Item" ${currentReason === 'Wrong Item' ? 'selected' : ''}>Wrong Item</option>
                    <option value="Excess" ${currentReason === 'Excess' ? 'selected' : ''}>Excess</option>
                    <option value="Other" ${currentReason === 'Other' ? 'selected' : ''}>Other</option>
                </select>
            </td>
            <td>
                <input type="text" name="items[${index}][remarks]" value="${currentRemarks}" class="form-control form-control-sm" placeholder="Optional" ${(available <= 0 && currentQty <= 0) ? 'disabled' : ''}>
            </td>
        `;

        tbody.appendChild(row);
    });
}

function resetPage() {
    if(confirm('Are you sure you want to cancel?')) {
        window.location.reload();
    }
}

function viewDN(id) {
    const modalEl = document.getElementById('viewDnModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    document.getElementById('modal_loader').classList.remove('d-none');
    document.getElementById('modal_content').classList.add('d-none');
    
    fetch(`<?= $basePath ?>/controller/purchase/get_debit_note_details.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const h = data.header;
            document.getElementById('v_dn_number').innerText = h.debit_note_number;
            document.getElementById('v_dn_date').innerText = h.debit_note_date;
            document.getElementById('v_po_number').innerText = h.po_number;
            document.getElementById('v_vendor').innerText = h.vendor_name;
            document.getElementById('v_remarks').innerText = h.remarks || '-';
            
            const tbody = document.getElementById('v_items_body');
            tbody.innerHTML = '';
            data.items.forEach(item => {
                const rate = parseFloat(item.po_rate || 0);
                const retQty = parseFloat(item.return_qty || 0);
                const retVal = (retQty * rate).toFixed(2);

                tbody.innerHTML += `
                    <tr>
                        <td>
                            <div class="fw-bold">${item.item_name}</div>
                            <small class="text-muted">${item.unit_name || ''}</small>
                        </td>
                        <td class="text-end">${rate.toFixed(2)}</td>
                        <td class="text-center fw-bold text-danger">${retQty}</td>
                        <td class="text-end fw-bold">${retVal}</td>
                        <td>${item.return_reason || '-'}</td>
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
        alert('Failed to load detail');
    });
}


function editDN(id) {
    fetch(`<?= $basePath ?>/controller/purchase/get_debit_note_for_edit.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const h = data.header;
            
            // Set Mode
            document.getElementById('form_mode').value = 'update';
            document.getElementById('dn_id').value = h.debit_note_id;
            
            // Populate Header
            document.getElementById('po_id').value = h.po_id;
            document.getElementById('vendor_id').value = h.vendor_id;
            
            document.getElementById('disp_vendor').innerText = h.vendor_name;
            document.getElementById('disp_po_number').innerText = h.po_number;
            document.getElementById('disp_po_date').innerText = h.order_date;
            document.getElementById('disp_ref_no').innerText = h.reference_no || '-';

            document.getElementById('dn_number').value = h.debit_note_number;
            document.getElementsByName('dn_date')[0].value = h.debit_note_date;
            document.getElementsByName('dn_remarks')[0].value = h.remarks;
            
            // Populate Items
            populateForm({po_number: h.po_number}, data.items); // pass dummy PO obj as populateForm uses it? No, it uses inputs.
            
            // Show Form
            document.getElementById('search_section').classList.add('d-none');
            document.getElementById('dn_form').classList.remove('d-none');
            document.getElementById('submit_btn').innerHTML = '<i class="ti ti-device-floppy me-2"></i>Update Debit Note';
            // Change Action
            document.getElementById('dn_form').action = '<?= $basePath ?>/controller/purchase/update_debit_note.php';

        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to load for editing');
    });
}
</script>

<style>
@media print {
    body * { visibility: hidden !important; }
    #dn-print-area, #dn-print-area * { visibility: visible !important; }
    #dn-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<div id="dn-print-area" style="display:none;">
    <div style="text-align:center; margin-bottom:16px;">
        <h3 style="margin:0;">Debit Notes (Purchase Returns)</h3>
        <p style="margin:4px 0; font-size:12px; color:#666;">Printed on: <span id="dn-print-date"></span></p>
    </div>
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead style="background:#5d282a; color:#fff;">
            <tr>
                <th>DN Number</th>
                <th>Date</th>
                <th>PO Number</th>
                <th>Vendor</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody id="dn-print-tbody"></tbody>
    </table>
</div>

<script>
function printDNList() {
    var rows = document.querySelectorAll('#dn_table tbody tr');
    var tbody = document.getElementById('dn-print-tbody');
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
    document.getElementById('dn-print-date').textContent = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('dn-print-area').style.display = 'block';
    window.print();
    document.getElementById('dn-print-area').style.display = 'none';
}
</script>
