<?php
$title = 'Vendor Payments';
?>

<style>
    .vendor-text-primary { color: #2a3547; }
    
    /* Standard Highlight Theme */
    .select2-results__option--highlighted .vendor-text-primary { color: white !important; }
    .select2-results__option--highlighted .text-muted { color: #e9ecef !important; }
    .select2-results__option--highlighted .badge { background-color: rgba(255,255,255,0.2) !important; color: white !important; border-color: white !important; }
    .select2-results__option--highlighted .text-success { color: white !important; }
    .select2-results__option--highlighted .text-danger { color: white !important; }
    .select2-results__option--highlighted .vendor-avatar { background-color: white !important; color: #5d87ff !important; }
    
    /* Custom Dropdown Styles */
    .vendor-select2-dropdown .select2-results__options {
        max-height: 400px !important;
    }
    
    /* Fix Select2 Z-Index */
    .select2-container--open .select2-dropdown {
        z-index: 9999999 !important;
    }
    .select2-dropdown {
        z-index: 9999999 !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            
            <!-- Payment Form -->
            <div class="card mb-4 d-none" id="payment_form_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Record Payment Made</h5>
                </div>
                <div class="card-body">
                    <form action="<?= $basePath ?>/controller/payment/save_payment_made.php" method="POST" id="payment_form">
                         <input type="hidden" name="payment_id" id="form_payment_id" value="">
                        
                        <div class="row g-3">
                            <!-- Vendor Selection -->
                            <div class="col-md-4">
                                <label class="form-label required">Select Vendor</label>
                                <select name="vendor_id" id="vendor_select" class="form-select" required></select>
                            </div>
                            
                            <!-- Current Balance Display -->
                            <div class="col-md-2">
                                <label class="form-label">Current Balance Due</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">₹</span>
                                    <input type="text" id="current_balance_due" class="form-control bg-light fw-bold" readonly value="0.00">
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label required">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                       

                     
                             <div class="col-md-2">
                                <label class="form-label required">Payment Mode</label>
                                <select name="payment_mode" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="Cheque No / Trans ID">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required">Amount Paid</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="amount" class="form-control fw-bold" step="0.01" min="0.01" required placeholder="0.00">
                                </div>
                            </div>
                        
                            <div class="col-md-9">
                                <label class="form-label">Notes / Remarks</label>
                                <textarea name="notes" class="form-control" rows="1" placeholder="Optional remarks about this payment"></textarea>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" id="cancel_edit_btn" onclick="cancelEdit()">Cancel</button>
                            <button type="submit" name="save_payment" class="btn btn-success"><i class="ti ti-check me-1"></i> <span id="save_btn_text">Save Payment</span></button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Recent Payments List -->
            <div class="card" id="payment_list_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                     <h5 class="card-title mb-0">Recent Payments</h5>
                     <button type="button" class="btn btn-primary" id="add_payment_btn"><i class="ti ti-plus me-1"></i> Record Payment</button>
                </div>
                <div class="card-body">
                         <table data-tables="basic" id="payment_made_table" class="table table-hover align-middle dt-responsive w-100" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment No</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Mode</th>
                                    <th>Reference</th>
                                    <th class="text-end">Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                               $pSql = "SELECT pm.*, v.display_name
                                        FROM payment_made pm
                                        LEFT JOIN vendors_listing v ON pm.vendor_id = v.vendor_id
                                        WHERE pm.organization_id = '" . $_SESSION['organization_id'] . "' ORDER BY pm.payment_id DESC LIMIT 50 ";
                                $pRes = $conn->query($pSql);
                                if($pRes && $pRes->num_rows > 0){
                                    while($pRow = $pRes->fetch_assoc()){
                                        ?>
                                        <tr>
                                            <td><span class="text-primary fw-bold"><?= htmlspecialchars($pRow['payment_number']) ?></span></td>
                                            <td><?= date('d M Y', strtotime($pRow['payment_date'])) ?></td>
                                            <td><?= htmlspecialchars($pRow['display_name']) ?></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($pRow['payment_mode']) ?></span></td>
                                            <td><?= htmlspecialchars($pRow['reference_no'] ?: '-') ?></td>
                                            <td class="text-end fw-bold">₹<?= number_format($pRow['amount'], 2) ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">Action</button>
                                                    <ul class="dropdown-menu">
                                                        <?php
                                                            $onClickJs = "editPayment(" . 
                                                                $pRow['payment_id'] . ", " .
                                                                json_encode((string)$pRow['vendor_id']) . ", " .
                                                                json_encode((string)($pRow['display_name'] ?: 'Unknown Vendor')) . ", " .
                                                                json_encode((string)$pRow['payment_date']) . ", " .
                                                                json_encode((string)$pRow['payment_mode']) . ", " .
                                                                json_encode((string)($pRow['reference_no'] ?: '')) . ", " .
                                                                json_encode((string)$pRow['amount']) . ", " .
                                                                json_encode((string)($pRow['notes'] ?: '')) . 
                                                            "); return false;";
                                                            $onClickHtml = htmlspecialchars($onClickJs, ENT_QUOTES, 'UTF-8');
                                                        ?>
                                                        <li><a class="dropdown-item" href="#" onclick="<?= $onClickHtml ?>"><i class="ti ti-pencil me-2"></i> Edit</a></li>
                                                        <li><a class="dropdown-item" href="<?= $basePath ?>/controller/payment/print_payment_made.php?id=<?= $pRow['payment_id'] ?>" target="_blank"><i class="ti ti-printer me-2"></i> Print</a></li>
                                                        <li><a class="dropdown-item" href="<?= $basePath ?>/controller/payment/download_payment_made_pdf.php?id=<?= $pRow['payment_id'] ?>"><i class="ti ti-download me-2"></i> Download PDF</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deletePayment(<?= $pRow['payment_id'] ?>, '<?= $pRow['payment_number'] ?>'); return false;"><i class="ti ti-trash me-2"></i> Delete</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                         </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php $extra_scripts = "
<script>
    const basePath = '$basePath'; 
    const formCard = document.getElementById('payment_form_card');
    const listCard = document.getElementById('payment_list_card');
    const addBtn = document.getElementById('add_payment_btn');
    const vendorSelect = $('#vendor_select');

    $(document).ready(function() {
        if(addBtn) {
            addBtn.addEventListener('click', () => {
                formCard.classList.remove('d-none');
                listCard.classList.add('d-none');
                document.getElementById('save_btn_text').innerText = 'Save Payment';
                document.getElementById('payment_form').reset();
                document.getElementById('form_payment_id').value = '';
                document.getElementById('current_balance_due').value = '0.00';
                document.getElementById('current_balance_due').classList.remove('text-danger', 'text-success');
                
                vendorSelect.val(null).trigger('change');
                initVendorSelect();
            });
        }
    
        // Auto-init if form is visible
        if(!formCard.classList.contains('d-none')){
            initVendorSelect();
        }
    });

    function initVendorSelect() {
        if (vendorSelect.hasClass('select2-hidden-accessible')) {
            return;
        }
    
        vendorSelect.select2({
            placeholder: 'Search Vendor...',
            width: '100%',
            dropdownCssClass: 'vendor-select2-dropdown',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: basePath + '/controller/payment/search_vendors_listing.php',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    if (!Array.isArray(data)) data = [];
                    let results = data.map(v => ({
                        id: String(v.id),
                        text: v.display_name,
                        display_name: v.display_name,
                        company_name: v.company_name || '',
                        vendor_code: v.vendor_code || '',
                        email: v.email || '',
                        avatar: v.avatar || '',
                        mobile: v.mobile || '',
                        current_balance_due: v.current_balance_due || 0
                    }));
                    return { results };
                }
            },
            templateResult: function (vendor) {
                if (vendor.loading) return vendor.text;
                
                let letter = (vendor.display_name || vendor.text || '').charAt(0).toUpperCase();
    
                let avatarHtml = '';
                if(vendor.avatar && vendor.avatar.trim() !== ''){
                    avatarHtml = `<img src='` + basePath + `/` + vendor.avatar + `' class='rounded-circle' style='width:32px;height:32px;object-fit:cover;'>`;
                } else {
                    avatarHtml = `<div class='vendor-avatar rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold'
                                 style='width:32px;height:32px;min-width:32px;'>
                                ` + letter + `
                            </div>`;
                }
                
                return $(`
                    <div class='d-flex align-items-center gap-2 py-1'>
                        ` + avatarHtml + `
                        <div class='flex-grow-1'>
                            <div class='d-flex justify-content-between align-items-center'>
                                <div class='vendor-text-primary fw-semibold lh-sm'>
                                    ` + vendor.display_name + ` 
                                    ` + (vendor.vendor_code ? `<span class='small text-muted fw-normal'>(` + vendor.vendor_code + `)</span>` : '') + `
                                </div>
                            </div>
                            <div class='vendor-text-primary small text-muted d-flex align-items-center gap-3 mt-1'>
                                ` + (vendor.email ? `<span><i class='ti ti-mail me-1'></i>` + vendor.email + `</span>` : '') + `
                                ` + (vendor.company_name ? `<span><i class='ti ti-building me-1'></i>` + vendor.company_name + `</span>` : '') + `
                            </div>
                        </div>
                    </div>`);
            },
            templateSelection: function (vendor) {
                if (!vendor.id) return vendor.text;
                let name = vendor.display_name || vendor.text;
                if(!name) return '';
                return $('<span>' + name + (vendor.vendor_code ? ' (' + vendor.vendor_code + ')' : '') + '</span>');
            },
            escapeMarkup: m => m
        }).on('select2:select', function (e) {
            var data = e.params.data;
            updateBalanceDisplay(data.current_balance_due);
        }).on('select2:clear', function (e) {
            updateBalanceDisplay(0);
        });
    }

    function updateBalanceDisplay(balance) {
        const balInput = document.getElementById('current_balance_due');
        let bal = parseFloat(balance || 0);
        balInput.value = bal.toFixed(2);
        balInput.classList.remove('text-danger', 'text-success');
        
        if(bal > 0) {
            balInput.classList.add('text-danger');
        } else if (bal < 0) {
            balInput.classList.add('text-success');
        }
    }
    
    function fetchVendorBalance(vendorId) {
        if(!vendorId) {
            updateBalanceDisplay(0);
            return;
        }
        
        fetch(basePath + '/controller/purchase/get_vendor_balance.php?vendor_id=' + vendorId)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                updateBalanceDisplay(data.balance);
            }
        })
        .catch(console.error);
    }

    function deletePayment(id, num) {
        if(confirm(`Are you sure you want to delete Payment ` + num + `? This will revert the vendor balance.`)) {
            window.location.href = basePath + '/controller/payment/delete_payment_made.php?id=' + id;
        }
    }
    
    function editPayment(id, vendorId, vendorName, date, mode, ref, amount, notes) {
        formCard.classList.remove('d-none');
        listCard.classList.add('d-none');
    
        document.getElementById('form_payment_id').value = id;
        document.getElementsByName('payment_date')[0].value = date;
        document.getElementsByName('payment_mode')[0].value = mode;
        document.getElementsByName('reference_no')[0].value = ref;
        document.getElementsByName('amount')[0].value = amount;
        document.getElementsByName('notes')[0].value = notes;
    
        initVendorSelect(); 

        var option = new Option(vendorName, vendorId, true, true);
        
        vendorSelect.empty();
        vendorSelect.append(option);
        vendorSelect.trigger('change');
            
        fetchVendorBalance(vendorId);
    
        document.getElementById('save_btn_text').innerText = 'Update Payment';
        
        document.getElementById('payment_form_card').scrollIntoView({behavior: 'smooth'});
    }
    
    function cancelEdit() {
        formCard.classList.add('d-none');
        listCard.classList.remove('d-none');
        document.getElementById('payment_form').reset();
        document.getElementById('form_payment_id').value = '';
        document.getElementById('save_btn_text').innerText = 'Save Payment';
        document.getElementById('current_balance_due').value = '0.00';
        document.getElementById('current_balance_due').classList.remove('text-danger', 'text-success');
        
        vendorSelect.val(null).trigger('change');
    }
</script>
"; ?>
