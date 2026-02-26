<?php
$title = "Register Organization";
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 text-white">Register New Organization</h5>
                </div>
                <div class="card-body">
                    <form action="<?= $basePath ?>/controller/organizations/create_organization.php" method="POST" enctype="multipart/form-data">
                        
                        <!-- Basic Information -->
                        <h6 class="fw-bold text-primary mb-3">Basic Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label required">Organization Name <span class="text-danger">*</span></label>
                                <input type="text" name="organization_name" class="form-control" required placeholder="e.g. Acme Corp Pvt Ltd">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Industry</label>
                                <select name="industry" class="form-select">
                                    <option value="">Select Industry</option>
                                    <option value="IT">IT / Software</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Education">Education</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Logo</label>
                                <input type="file" name="organization_logo" class="form-control" accept="image/*" onchange="validateLogo(this)">
                            </div>
                        </div>

                        <!-- Location & Contact -->
                        <h6 class="fw-bold text-primary mb-3">Location & Address</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" placeholder="Street Address, Building, etc.">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="India">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" class="form-control">
                            </div>
                        </div>

                        <!-- Regional Settings & GST -->
                        <h6 class="fw-bold text-primary mb-3">Regional & Tax Settings</h6>
                        <div class="row g-3 mb-4">
                             <div class="col-md-3">
                                <label class="form-label">Currency Code</label>
                                <select name="currency_code" class="form-select">
                                    <option value="INR" selected>INR (₹)</option>
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                </select>
                            </div>
                             <div class="col-md-3">
                                <label class="form-label">Language</label>
                                <select name="language" class="form-select">
                                    <option value="en" selected>English</option>
                                    <option value="hi">Hindi</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Timezone</label>
                                <select name="timezone" class="form-select">
                                    <option value="Asia/Kolkata">Asia/Kolkata</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4 align-items-center bg-light p-3 rounded">
                            <div class="col-md-3">
                                <label class="form-label d-block fw-bold">GST Registered?</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gst_registered" id="gst_yes" value="1" onchange="toggleGST(true)">
                                    <label class="form-check-label" for="gst_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gst_registered" id="gst_no" value="0" checked onchange="toggleGST(false)">
                                    <label class="form-check-label" for="gst_no">No</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GST Number</label>
                                <input type="text" name="gst_number" id="gst_number" class="form-control" disabled placeholder="Enter GSTIN">
                            </div>
                        </div>

                        <!-- Bank Details section added -->
                        <h6 class="fw-bold text-primary mb-3">Bank Details</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="HDFC, ICICI, etc.">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="account_number" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" name="ifsc_code" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Account Holder Name</label>
                                <input type="text" name="account_holder_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch Name</label>
                                <input type="text" name="branch_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">UPI ID</label>
                                <input type="text" name="upi_id" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">QR Code Image</label>
                                <input type="file" name="qr_code" class="form-control" accept="image/*" onchange="validateLogo(this)">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">Register Organization</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</script>
<script>
function toggleGST(isYes) {
    const input = document.getElementById('gst_number');
    if(isYes) {
        input.disabled = false;
        input.required = true;
    } else {
        input.disabled = true;
        input.required = false;
        input.value = '';
    }
}

function validateLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 500 * 1024; // 500KB in bytes

        if (file.size > maxSize) {
            alert("Error! Logo size must be less than 500KB");
            input.value = ''; // Clear the input
        }
    }
}
</script>
