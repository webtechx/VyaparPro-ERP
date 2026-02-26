        <!-- Sidenav Menu Start -->
        <div class="sidenav-menu" style="display: flex; flex-direction: column; height: 100%;">
            
            <div class="sticky-sidebar-header" style="flex-shrink: 0;">
                <!-- User -->
                <div class="sidenav-user text-nowrap border border-dashed rounded-3" style="margin: 15px 15px 0;">
                    <a href="#!" class="sidenav-user-name d-flex align-items-center">
                        <?php 
                        if (!empty($currentUser['employee_image'])) {
                            $imgSrc = $currentUser['employee_image'];
                            if (strpos($imgSrc, 'http') !== 0) {
                                $imgSrc = $basePath . '/uploads/'.$_SESSION['organization_code'].'/employees/avatars/' . $imgSrc;
                            }
                            echo '<img src="' . $imgSrc . '" width="36" class="rounded-circle me-2 d-flex" style="width: 40px; height: 40px; object-fit: cover;" alt="user-image">';
                        } else {
                            $initial = strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1));
                            echo '<div class="avatar-sm me-2 d-flex align-items-center justify-content-center bg-light text-primary fw-bold rounded-circle" style="width: 40px; height: 40px; min-width: 40px;">' . $initial . '</div>';
                        }
                        ?>
                        <span>
                            <h6 class="my-0 fw-semibold"><?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?></h5>
                            <h6 class="my-0 text-muted"><?= htmlspecialchars($currentUser['role_name'] ?? '') ?></h6>
                        </span>
                    </a>
                </div>

                <!-- Sidebar Search Bar -->
                <div class="sidebar-search-wrap px-3 py-2">
                    <div class="sidebar-search-box position-relative border border-dashed rounded-3">
                        <span class="sidebar-search-icon position-absolute top-50 translate-middle-y ps-2" style="left:0;pointer-events:none;">
                            <i data-lucide="search" style="width:15px;height:15px;color:#8a9ab5;"></i>
                        </span>
                        <input type="text" id="sidebarSearchInput" autocomplete="off"
                            placeholder="Search menu..."
                            style="
                                width:100%;
                                padding: 7px 30px 7px 30px;
                                border: 1px solid rgba(255,255,255,0.12);
                                border-radius: 8px;
                                background: rgba(255,255,255,0.07);
                                color: inherit;
                                font-size: 12.5px;
                                outline: none;
                                transition: background 0.2s, border-color 0.2s;
                            "
                            onfocus="this.style.background='rgba(255,255,255,0.13)';this.style.borderColor='rgba(255,255,255,0.28)';"
                            onblur="this.style.background='rgba(255,255,255,0.07)';this.style.borderColor='rgba(255,255,255,0.12)';"
                        >
                        <button id="sidebarSearchClear" onclick="clearSidebarSearch()" title="Clear"
                            style="
                                display:none;position:absolute;right:6px;top:50%;transform:translateY(-50%);
                                background:none;border:none;cursor:pointer;padding:2px;color:#8a9ab5;line-height:1;
                            ">
                            <i data-lucide="x" style="width:13px;height:13px;"></i>
                        </button>
                    </div>
                    <div id="sidebarNoResults" style="display:none;text-align:center;padding:10px 0 4px;font-size:12px;color:#8a9ab5;">
                        No menu found
                    </div>
                </div>
            </div>

            <div class="sidebar-scroll-wrapper" style="flex: 1; min-height: 0; position: relative;">
                <div class="scrollbar" data-simplebar style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;">

                    <!--- Sidenav Menu -->
                    <ul class="side-nav" id="sideNavMenuList">

                    <?php if (can_access('dashboard', 'view')) : ?>
                    <li class="side-nav-item">
                        <a href="<?= $basePath ?>/dashboard" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="circle-gauge"></i></span>
                            <span class="menu-text" data-lang="dashboard">Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (can_access('designation_listing', 'view') || can_access('department', 'view') || can_access('access_control', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="administration">Administration</li>
                    <?php endif; ?>
                    
                    <?php if (can_access('designation_listing', 'view') || can_access('department', 'view') || can_access('access_control', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarAccessControl" aria-expanded="false" aria-controls="sidebarAccessControl" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="shield-check"></i></span>
                            <span class="menu-text" data-lang="access_control">Access Control</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarAccessControl">
                            <ul class="sub-menu">
                                <?php if (can_access('designation_listing', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/designation_listing" class="side-nav-link">
                                        <span class="menu-text" data-lang="designation">Designation</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('department', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/department" class="side-nav-link">
                                        <span class="menu-text" data-lang="department">Department</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('access_control', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/access_control" class="side-nav-link">
                                        <span class="menu-text" data-lang="permissions">Role Permissions</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('employees', 'view')) : ?>
                    <li class="side-nav-item">
                        <a href="<?= $basePath ?>/employees" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="users"></i></span>
                            <span class="menu-text" data-lang="employees">Employees</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('distribute_percentage', 'view')) : ?>
                    <li class="side-nav-item">
                        <a href="<?= $basePath ?>/employee_incentive_wallet" class="side-nav-link" title="Employee Wallet">
                            <span class="menu-icon"><i data-lucide="wallet"></i></span>
                            <span class="menu-text" data-lang="employee_incentive_wallet">Employee Wallet</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (can_access('hsn_list', 'view') || can_access('units', 'view') || can_access('items', 'view') || can_access('current_stock', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="inventory_management">Inventory Management</li>
                    <?php endif; ?>
                    <?php if (can_access('hsn_list', 'view') || can_access('units', 'view') || can_access('items', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarProductSetup" aria-expanded="false" aria-controls="sidebarProductSetup" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="box"></i></span>
                            <span class="menu-text" data-lang="product_setup">Product Setup</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarProductSetup">
                            <ul class="sub-menu">
                                <?php if (can_access('hsn_list', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/hsn_list" class="side-nav-link">
                                        <span class="menu-text" data-lang="hsn_list">HSN List</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('units', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/units" class="side-nav-link">
                                        <span class="menu-text" data-lang="units">Units</span>
                                    </a>
                                </li>
                                 <?php endif; ?>
                                <?php if (can_access('items', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/items" class="side-nav-link">
                                        <span class="menu-text" data-lang="items">Items</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('current_stock', 'view') || can_access('items', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarInventory" aria-expanded="false" aria-controls="sidebarInventory" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="package-search"></i></span>
                            <span class="menu-text" data-lang="inventory">Inventory</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarInventory">
                            <ul class="sub-menu">
                                <?php if (can_access('current_stock', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/current_stock" class="side-nav-link">
                                        <span class="menu-text" data-lang="current_stock">Current Stock</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('items', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/inventory_report" class="side-nav-link" title="Inventory Valuation Summary">
                                        <span class="menu-text" data-lang="inventory_valuation_summary">Inventory Valuation Summary</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/inventory_ageing_report" class="side-nav-link" title="Inventory Ageing Summary">
                                        <span class="menu-text" data-lang="inventory_ageing_summary">Inventory Ageing Summary</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (can_access('vendors', 'view') || can_access('purchase_orders', 'view') || can_access('purchase_orders_pending', 'view') || can_access('purchase_orders_approved', 'view') || can_access('goods_received_notes', 'view') || can_access('debit_note', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="supplier_management">Supplier & Purchase Management</li>
                    <?php endif; ?>
                    <?php if (can_access('vendors', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarSupplier" aria-expanded="false" aria-controls="sidebarSupplier" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="truck"></i></span>
                            <span class="menu-text" data-lang="supplier">Supplier</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSupplier">
                            <ul class="sub-menu">
                                <?php if (can_access('vendors', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/vendors" class="side-nav-link" title="Vendor">
                                        <span class="menu-text" data-lang="vendor">Vendor</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('purchase_orders', 'view') || can_access('purchase_orders_pending', 'view') || can_access('purchase_orders_approved', 'view') || can_access('goods_received_notes', 'view') || can_access('debit_note', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarPurchases" aria-expanded="false" aria-controls="sidebarPurchases" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="shopping-cart"></i></span>
                            <span class="menu-text" data-lang="purchases">Purchases</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarPurchases">
                            <ul class="sub-menu">
                                <?php if (can_access('purchase_orders', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/purchase_orders" class="side-nav-link" title="Purchase Order">
                                        <span class="menu-text" data-lang="purchase_orders">Purchase Order</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('purchase_orders_pending', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/purchase_orders_pending" class="side-nav-link" title="Purchase Order Pending">
                                        <span class="menu-text" data-lang="purchase_orders_pending">Purchase Order Pending</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('purchase_orders_approved', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/purchase_orders_approved" class="side-nav-link" title="Purchase Order Approved">
                                        <span class="menu-text" data-lang="purchase_orders_approved">Purchase Order Approved</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('goods_received_notes', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/goods_received_notes" class="side-nav-link" title="Goods Received Notes">
                                        <span class="menu-text" data-lang="goods_received_notes">Goods Received Notes</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('debit_note', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/debit_note" class="side-nav-link" title="Debit Note">
                                        <span class="menu-text" data-lang="debit_note">Debit Note</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('purchase_orders', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarPurchaseReports" aria-expanded="false" aria-controls="sidebarPurchaseReports" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="bar-chart"></i></span>
                            <span class="menu-text" data-lang="purchase_reports">Purchase Reports</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarPurchaseReports">
                            <ul class="sub-menu">
                                <?php if (can_access('purchase_orders', 'view')) : ?>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/purchase_history_report" class="side-nav-link" title="Purchase History Report">
                                        <span class="menu-text" data-lang="purchase_history_report">Purchase History Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/product_wise_purchase_report" class="side-nav-link" title="Product wise Purchase Report">
                                        <span class="menu-text" data-lang="product_wise_purchase_report">Product Wise Purchase Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/vendor_ledger_report" class="side-nav-link" title="Vendor Ledger Report">
                                        <span class="menu-text" data-lang="vendor_ledger_report">Vendor Ledger Report</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (can_access('add_targets', 'view') || can_access('distribute_percentage', 'view') || can_access('proforma_invoice', 'view') || can_access('tax_invoice', 'view') || can_access('sales_report', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="sales_billing_management">Sales & Billing Management</li>
                    <?php endif; ?>
                    <?php if (can_access('add_targets', 'view') || can_access('distribute_percentage', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarTargets" aria-expanded="false" aria-controls="sidebarTargets" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="target"></i></span>
                            <span class="menu-text" data-lang="targets">Targets</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarTargets">
                            <ul class="sub-menu">
                                <?php if (can_access('add_targets', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/add_targets" class="side-nav-link" title="Add Targets">
                                        <span class="menu-text" data-lang="add_targets">Add Targets</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('distribute_percentage', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/distribute_percentage" class="side-nav-link" title="Distribute Percentage">
                                        <span class="menu-text" data-lang="distribute_percentage">Distribute Percentage</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('proforma_invoice', 'view') || can_access('tax_invoice', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarBilling" aria-expanded="false" aria-controls="sidebarBilling" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="receipt"></i></span>
                            <span class="menu-text" data-lang="billing">Billing</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarBilling">
                            <ul class="sub-menu">
                                <?php if (can_access('proforma_invoice', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/proforma_invoice" class="side-nav-link" title="Proforma Invoice">
                                        <span class="menu-text" data-lang="proforma_invoice">Proforma Invoice</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('tax_invoice', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/tax_invoice" class="side-nav-link" title="Tax Invoice">
                                        <span class="menu-text" data-lang="tax_invoice">Tax Invoice</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('sales_report', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarSalesOperations" aria-expanded="false" aria-controls="sidebarSalesOperations" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="trending-up"></i></span>
                            <span class="menu-text" data-lang="sales_operations">Sales Operations</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSalesOperations">
                            <ul class="sub-menu">
                                <?php if (can_access('sales_report', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/sales_report" class="side-nav-link" title="Sales Report">
                                        <span class="menu-text" data-lang="sales_report">Sales Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/sales_by_customer_report" class="side-nav-link" title="Sales by Customer">
                                        <span class="menu-text" data-lang="sales_by_customer">Sales by Customer</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/sales_by_item_report" class="side-nav-link" title="Sales by Item">
                                        <span class="menu-text" data-lang="sales_by_item">Sales by Item</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/sales_by_salesperson_report" class="side-nav-link" title="Sales by Salesperson">
                                        <span class="menu-text" data-lang="sales_by_salesperson">Sales by Salesperson</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/department_wise_sales_report" class="side-nav-link" title="Department wise Sales Report">
                                        <span class="menu-text" data-lang="department_wise_sales_report">Department Wise Sales Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/outstanding_invoice_report" class="side-nav-link" title="Outstanding Invoice Report">
                                        <span class="menu-text" data-lang="outstanding_invoice_report">Outstanding Invoice Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/discount_report" class="side-nav-link" title="Discount Report">
                                        <span class="menu-text" data-lang="discount_report">Discount Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/commission_report" class="side-nav-link" title="Commission Report">
                                        <span class="menu-text" data-lang="commission_report">Commission Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/user_wise_collection_report" class="side-nav-link" title="User Collection Report">
                                        <span class="menu-text" data-lang="user_wise_collection_report">User Collection Report</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (can_access('customers_type_listing', 'view') || can_access('customers_listing', 'view') || can_access('customers_wallet', 'view') || can_access('payment_received', 'view') || can_access('sales_report', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="customer_management_section">Customer Management</li>
                    <?php endif; ?>
                    <?php if (can_access('customers_type_listing', 'view') || can_access('customers_listing', 'view') || can_access('customers_wallet', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarCustomerSetup" aria-expanded="false" aria-controls="sidebarCustomerSetup" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="users"></i></span>
                            <span class="menu-text" data-lang="customer_setup">Customer Setup</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarCustomerSetup">
                            <ul class="sub-menu">
                                <?php if (can_access('customers_type_listing', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/customers_type_listing" class="side-nav-link" title="Customer Types" >
                                        <span class="menu-text" data-lang="customers_type_listing">Customer Types</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('customers_listing', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/customers_listing" class="side-nav-link" title="Customers"  >
                                        <span class="menu-text" data-lang="customers">Customers</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('customers_wallet', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/customers_wallet" class="side-nav-link" title="Customers Wallet"  >
                                        <span class="menu-text" data-lang="customers_wallet">Customers Wallet</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('payment_received', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarCustomerPayments" aria-expanded="false" aria-controls="sidebarCustomerPayments" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="credit-card"></i></span>
                            <span class="menu-text" data-lang="customer_payments">Customer Payments</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarCustomerPayments">
                            <ul class="sub-menu">
                                <?php if (can_access('payment_received', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/payment_received" class="side-nav-link" title="Payment Received (Customer)">
                                        <span class="menu-text" data-lang="payment_received">Payment Received (Customer)</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('sales_report', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarCustomerReports" aria-expanded="false" aria-controls="sidebarCustomerReports" class="side-nav-link">
                            <span class="menu-icon"><i data-lucide="pie-chart"></i></span>
                            <span class="menu-text" data-lang="customer_reports">Customer Reports</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarCustomerReports">
                            <ul class="sub-menu">
                                <?php if (can_access('sales_report', 'view')) : ?>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/customer_purchase_history_report" class="side-nav-link" title="Customer History Report">
                                        <span class="menu-text" data-lang="customer_purchase_history_report">Customer Purchase History Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/customer_reports?type=contact" class="side-nav-link" title="Customer Contact Report">
                                        <span class="menu-text" data-lang="customer_contact_report">Customer Contact Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/customer_reports?type=birthday" class="side-nav-link" title="Customer Birthday Report">
                                        <span class="menu-text" data-lang="customer_birthday_report">Customer Birthday Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/customer_reports?type=anniversary" class="side-nav-link" title="Customer Anniversary Report">
                                        <span class="menu-text" data-lang="customer_anniversary_report">Customer Anniversary Report</span>
                                    </a>
                                </li>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/customer_ledger_report" class="side-nav-link" title="Customer wise Ledger Report">
                                        <span class="menu-text" data-lang="customer_ledger_report">Customer Ledger Report</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (can_access('payment_made', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="vendor_payment_management">Vendor Payment Management</li>
                    <li class="side-nav-item">
                        <a href="<?= $basePath ?>/payment_made" class="side-nav-link" title="Payment Made (Vendor)">
                            <span class="menu-icon"><i data-lucide="banknote"></i></span>
                            <span class="menu-text" data-lang="payment_made">Payment Made (Vendor)</span>
                        </a>
                    </li>

                    <?php endif; ?>

                    <?php if (can_access('loyalty_point_slabs', 'view') || can_access('sales_report', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="loyalty_program">Loyalty Program</li>
                    <?php endif; ?>
                    <?php if (can_access('loyalty_point_slabs', 'view') || can_access('sales_report', 'view')) : ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="javascript: void(0);" data-bs-target="#sidebarLoyaltyManage" aria-expanded="false" aria-controls="sidebarLoyaltyManage" class="side-nav-link" title="Loyalty Program">
                            <span class="menu-icon"><i data-lucide="award"></i></span>
                            <span class="menu-text" data-lang="loyalty_program_manage">Loyalty Program</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarLoyaltyManage">
                            <ul class="sub-menu">
                                <?php if (can_access('loyalty_point_slabs', 'view')) : ?>
                                <li class="side-nav-item">
                                    <a href="<?= $basePath ?>/loyalty_point_slabs" class="side-nav-link" title="Loyalty Point Slabs">
                                        <span class="menu-text" data-lang="loyalty_point_slabs">Loyalty Point Slabs</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (can_access('sales_report', 'view')) : ?>
                                <li class="side-nav-item">
                                     <a href="<?= $basePath ?>/loyalty_point_report" class="side-nav-link" title="Loyalty Point Report">
                                        <span class="menu-text" data-lang="loyalty_point_report">Loyalty Point Report</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (can_access('credit_note', 'view') || can_access('debit_note', 'view')) : ?>
                    <li class="side-nav-title mt-2" data-lang="notes_adjustments">Notes & Adjustments</li>
                    <?php endif; ?>
                    <?php if (can_access('credit_note', 'view')) : ?>
                    <li class="side-nav-item">
                        <a href="<?= $basePath ?>/credit_note_report" class="side-nav-link" title="Credit Note Report">
                            <span class="menu-icon"><i data-lucide="file-minus"></i></span>
                            <span class="menu-text" data-lang="credit_note_report">Credit Note Report</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (can_access('debit_note', 'view')) : ?>
                    <li class="side-nav-item">
                        <a href="<?= $basePath ?>/debit_note_report" class="side-nav-link" title="Debit Note Report">
                            <span class="menu-icon"><i data-lucide="file-plus"></i></span>
                            <span class="menu-text" data-lang="debit_note_report">Debit Note Report</span>
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
                </div>
            </div>

            <div class="menu-collapse-box d-none d-xl-block">
                <button class="button-collapse-toggle">
                    <i data-lucide="square-chevron-left" class="align-middle flex-shrink-0"></i> <span>Collapse Menu</span>
                </button>
            </div>
        </div>
        <!-- Sidenav Menu End -->

        <script>
            // Note: If you do not want any of this logic here, you can remove it. It's already in app.js. This is for removing delays.

            // Sidenav Icons
            lucide.createIcons();

            // Sidenav Link Activation
            const currentUrlT = window.location.href.split(/[?#]/)[0];
            const currentPageT = window.location.pathname.split("/").pop();
            const activeMenuOverride = "<?= $activeMenu ?? '' ?>";
            const sideNavT = document.querySelector('.side-nav');

            document.querySelectorAll('.side-nav-link[href]').forEach(link => {
                const linkHref = link.getAttribute('href');
                if (!linkHref) return;

                const match = (currentPageT && linkHref.includes(currentPageT)) || link.href === currentUrlT || (activeMenuOverride && link.href.includes(activeMenuOverride));

                if (match) {
                    // Mark link and its li active
                    link.classList.add('active');
                    const li = link.closest('li.side-nav-item');
                    if (li) li.classList.add('active');

                    // Expand all parent .collapse and set toggles
                    let parentCollapse = link.closest('.collapse');
                    while (parentCollapse) {
                        parentCollapse.classList.add('show');

                        const parentToggle = document.querySelector(`a[href="#${parentCollapse.id}"]`);
                        if (parentToggle) {
                            parentToggle.setAttribute('aria-expanded', 'true');
                            const parentLi = parentToggle.closest('li.side-nav-item');
                            if (parentLi) parentLi.classList.add('active');
                        }

                        parentCollapse = parentCollapse.parentElement.closest('.collapse');
                    }
                }
            });
            // ================================================
            // Sidebar Search Filter
            // ================================================
            const sidebarSearchInput = document.getElementById('sidebarSearchInput');
            const sidebarSearchClear = document.getElementById('sidebarSearchClear');
            const sidebarNoResults   = document.getElementById('sidebarNoResults');
            const sideNavMenu        = document.getElementById('sideNavMenuList');

            function runSidebarSearch(query) {
                query = query.trim().toLowerCase();

                // Show/hide clear button
                sidebarSearchClear.style.display = query.length > 0 ? 'block' : 'none';

                if (query === '') {
                    // Reset: show everything, collapse groups
                    sideNavMenu.querySelectorAll('li.side-nav-item, li.side-nav-title').forEach(el => {
                        el.style.display = '';
                    });
                    // Re-collapse all expand groups (but keep active one open)
                    sideNavMenu.querySelectorAll('.collapse.show').forEach(col => {
                        // Only close if it doesn't have an active link
                        if (!col.querySelector('.side-nav-link.active')) {
                            col.classList.remove('show');
                            const toggle = document.querySelector(`[data-bs-target="#${col.id}"]`);
                            if (toggle) toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                    sidebarNoResults.style.display = 'none';
                    return;
                }

                // Track if any result exists
                let anyMatch = false;

                // Get all navigable leaf items (actual links, not collapse toggles)
                const allNavLinks = sideNavMenu.querySelectorAll('li.side-nav-item a.side-nav-link[href]:not([href="javascript: void(0);"])');

                // First pass: mark matching links
                const matchedLis = new Set();
                allNavLinks.forEach(link => {
                    const li = link.closest('li.side-nav-item');
                    if (!li) return;
                    const text = (link.textContent || '').trim().toLowerCase();
                    if (text.includes(query)) {
                        li.style.display = '';
                        matchedLis.add(li);
                        anyMatch = true;

                        // Expand all ancestor collapse groups
                        let parent = li.closest('.collapse');
                        while (parent) {
                            parent.classList.add('show');
                            const toggle = document.querySelector(`[data-bs-target="#${parent.id}"]`);
                            if (toggle) {
                                toggle.setAttribute('aria-expanded', 'true');
                                const toggleLi = toggle.closest('li.side-nav-item');
                                if (toggleLi) toggleLi.style.display = '';
                            }
                            parent = parent.parentElement?.closest('.collapse');
                        }
                    } else {
                        li.style.display = 'none';
                    }
                });

                // Handle collapse-parent li's (group headers like "Billing", "Purchase")
                // If they were shown because a child matched, don't hide them
                sideNavMenu.querySelectorAll('li.side-nav-item').forEach(li => {
                    if (matchedLis.has(li)) return; // Already shown
                    const toggle = li.querySelector('a[data-bs-toggle="collapse"]');
                    if (toggle) {
                        // It's a group header - keep it if any child inside is visible
                        const collapseId = toggle.getAttribute('data-bs-target')?.replace('#', '');
                        const collapseEl = collapseId ? document.getElementById(collapseId) : null;
                        if (collapseEl) {
                            const hasVisible = collapseEl.querySelector('li.side-nav-item[style=""]') || 
                                              [...collapseEl.querySelectorAll('li.side-nav-item')].some(c => c.style.display !== 'none');
                            li.style.display = hasVisible ? '' : 'none';
                            if (!hasVisible && collapseEl.classList.contains('show')) {
                                collapseEl.classList.remove('show');
                            }
                        }
                    }
                });

                // Handle section title separators (side-nav-title)
                sideNavMenu.querySelectorAll('li.side-nav-title').forEach(title => {
                    // Find all side-nav-items between this title and the next title
                    let sibling = title.nextElementSibling;
                    let hasVisibleSibling = false;
                    while (sibling && !sibling.classList.contains('side-nav-title')) {
                        if (sibling.classList.contains('side-nav-item') && sibling.style.display !== 'none') {
                            hasVisibleSibling = true;
                            break;
                        }
                        sibling = sibling.nextElementSibling;
                    }
                    title.style.display = hasVisibleSibling ? '' : 'none';
                });

                sidebarNoResults.style.display = anyMatch ? 'none' : 'block';
            }

            if (sidebarSearchInput) {
                sidebarSearchInput.addEventListener('input', function () {
                    runSidebarSearch(this.value);
                });
            }

            function clearSidebarSearch() {
                if (sidebarSearchInput) {
                    sidebarSearchInput.value = '';
                    runSidebarSearch('');
                    sidebarSearchInput.focus();
                }
            }

            // Keyboard shortcut: Ctrl+/ or Cmd+/ to focus sidebar search
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                    e.preventDefault();
                    if (sidebarSearchInput) sidebarSearchInput.focus();
                }
            });

        </script>        
        
        
        <div class="content-page">
            <div class="container-fluid">
                <div class="row">   
                    <div class="col-xl-12">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> <?= htmlspecialchars($_GET['success']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> <?= htmlspecialchars($_GET['error']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                    </div>        
                </div>
            </div>

