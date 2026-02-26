<?php $title = 'Help & Documentation'; 

if (!can_access('documentation', 'view')) {
    echo '<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 80vh;">';
    echo '<div class="text-center">';
    echo '<i class="ti ti-shield-lock text-muted mb-3" style="font-size: 5rem; opacity: 0.5;"></i>';
    echo '<h3 class="fw-bold">Welcome to Samadhan ERP</h3>';
    echo '<p class="text-muted">You do not have permission to view the Documentation.</p>';
    echo '</div></div>';
    return; // Stop rendering
} 

// Comprehensive Documentation Array
// Key: Sidebar Group Name -> Array of Modules
$documentation = [
    'Administration' => [
        'dashboard' => [
            'title' => 'Dashboard', 'icon' => 'ti-home', 'color' => '#6366f1', 'bg' => '#e0e7ff',
            'desc'  => 'The central hub of Samadhan ERP, providing a high-level overview of daily operations.',
            'badges' => ['Live Analytics', 'Quick Actions'],
            'features' => [
                'Real-time overview of sales, inventory, and pending orders',
                'Visual charts for revenue trends and stock levels',
                'Quick links to fast-action features like creating a new invoice'
            ],
            'steps' => [
                'Navigate to the Dashboard from the main menu',
                'Hover over charts to see specific data points for a given day',
                'Click on any summary card to jump straight to the detailed report'
            ]
        ],
        'roles_permissions' => [
            'title' => 'Roles & Permissions', 'icon' => 'ti-shield-check', 'color' => '#14b8a6', 'bg' => '#ccfbf1',
            'desc'  => 'Granular access control defining exactly what each user type can view, add, edit, or delete.',
            'badges' => ['Access Control', 'Security'],
            'features' => [
                'Create unlimited custom roles (Admin, Sales, Manager, etc.)',
                'Toggle specific page access on or off via Role Permissions',
                'Restrict users from deleting or modifying older records'
            ],
            'steps' => [
                'Go to Administration -> Access Control -> Roles to create a title',
                'Go to Administration -> Access Control -> Role Permissions to assign exact module rights',
                'Assign the Role to an Employee in the Employee master'
            ]
        ],
        'designation_dept' => [
            'title' => 'Designation & Department', 'icon' => 'ti-building', 'color' => '#f59e0b', 'bg' => '#fef3c7',
            'desc'  => 'Company structure configuration for organizing human resources.',
            'badges' => ['HR Setup', 'Organization'],
            'features' => [
                'Create distinct departments (e.g., Sales, Operations, IT)',
                'Map designations (e.g., Area Manager, Executive) to departments',
                'Used for internal hierarchy and advanced filtering on reports'
            ],
            'steps' => [
                'Add Departments first under Administration -> Department',
                'Add Designations and link them to Departments',
                'Use these when creating new employees'
            ]
        ],
        'employees' => [
            'title' => 'Employees', 'icon' => 'ti-users', 'color' => '#3b82f6', 'bg' => '#dbeafe',
            'desc'  => 'Employee master data, platform logins, and tracking.',
            'badges' => ['Logins', 'User Profiles'],
            'features' => [
                'Store personal, contact, and structural details for staff',
                'Auto-generation of Employee Code (e.g., EMP-XYZ-0001)',
                'Creates the underlying user login credentials for the ERP',
                'Allows associating a specific Role mapped in Role Permissions'
            ],
            'steps' => [
                'Ensure roles, designations, and departments are created first',
                'Click "Add Employee" in Administration -> Employees',
                'Provide valid username and login details'
            ]
        ],
        'employee_wallet' => [
            'title' => 'Employee Wallet', 'icon' => 'ti-wallet', 'color' => '#10b981', 'bg' => '#d1fae5',
            'desc'  => 'Incentive tracking system where staff earn cash percentages based on sales distribution operations.',
            'badges' => ['Incentives', 'Sales Targets'],
            'features' => [
                'Tracks individual commission/incentive payouts automatically',
                'Links directly to the "Distribute Percentage" feature dynamically',
                'View a ledger of earned incentives vs withdrawn amounts'
            ],
            'steps' => [
                'Check an employee\'s balance by going to Administration -> Employee Wallet',
                'Wallet balances grow automatically as sales targets are achieved and percentages distributed'
            ]
        ],
    ],
    'Inventory & Products' => [
        'hsn_units' => [
            'title' => 'HSN & Units', 'icon' => 'ti-list-numbers', 'color' => '#8b5cf6', 'bg' => '#ede9fe',
            'desc'  => 'Fundamental settings for product classification, taxes, and measurable quantities.',
            'badges' => ['GST Pre-setup', 'Metrics'],
            'features' => [
                'Define HSN codes and their corresponding GST Tax percentages (CGST/SGST/IGST)',
                'Create unit metrics (Kgs, Pcs, Boxes, Meters)',
                'Essential prerequisites before adding any physical inventory items'
            ],
            'steps' => [
                'Under Product Setup, add all required HSN codes and link the correct GST rates.',
                'Add measurement units.',
                'These will become dropdown options when creating Items.'
            ]
        ],
        'items_master' => [
            'title' => 'Items Master', 'icon' => 'ti-package', 'color' => '#f43f5e', 'bg' => '#ffe4e6',
            'desc'  => 'Central catalog of all products and services available to buy or sell.',
            'badges' => ['Bulk Upload', 'Commissions'],
            'features' => [
                'Detailed tracking of SKU, Name, Brand, and Category',
                'Link HSN for automated tax calculation on invoices',
                'Configure specific commission structures per customer type',
                'Excel bulk import support available'
            ],
            'steps' => [
                'Go to Product Setup -> Items',
                'Add an item, define the opening stock if applicable',
                'Map it to an HSN. This is mandatory for tax compliance.'
            ]
        ],
        'current_stock' => [
            'title' => 'Current Stock & Ageing', 'icon' => 'ti-box', 'color' => '#06b6d4', 'bg' => '#cffafe',
            'desc'  => 'Live tracking of physical warehouse counts and stock metrics.',
            'badges' => ['Live Data', 'Valuation'],
            'features' => [
                'Current Stock module shows live numeric inventory values modified instantly by GRNs and Invoices',
                'Valuation Summary computes the total monetary value of sitting warehouse goods',
                'Ageing Summary tracks how long lots of stock have been sitting unsold'
            ],
            'steps' => [
                'Navigate to Inventory -> Current Stock to search any item.',
                'Check Valuation to get your current assets standing.',
                'Check Ageing to prioritize liquidation of old items.'
            ]
        ],
    ],
    'Supplier & Purchases' => [
        'vendors' => [
            'title' => 'Vendors', 'icon' => 'ti-truck', 'color' => '#f59e0b', 'bg' => '#fef3c7',
            'desc'  => 'Profiles and accounts for your inward suppliers.',
            'badges' => ['Ledgers', 'Accounts Payable'],
            'features' => [
                'Track GSTIN, multiple addresses, and contact persons',
                'Creates an automated Vendor Ledger',
                'Calculates overall Outward debt you owe'
            ],
            'steps' => [
                'Supplier -> Vendor -> Add Vendor',
                'Enter GST details for B2B procurement'
            ]
        ],
        'purchase_orders' => [
            'title' => 'Purchase Orders (PO)', 'icon' => 'ti-shopping-cart', 'color' => '#3b82f6', 'bg' => '#dbeafe',
            'desc'  => 'Document intents to buy from vendors, subject to internal approval.',
            'badges' => ['Approvals', 'Internal Intent'],
            'features' => [
                'Draft Purchase Orders (these stay in "Pending" status)',
                'Pending POs DO NOT alter physical inventory',
                'Admins review and move them to "Approved" status',
                'Approved POs can be downloaded as PDF and sent to suppliers'
            ],
            'steps' => [
                'Purchase -> Purchase Orders',
                'Draft a PO. Admin will check Purchase Order Pending',
                'Once approved, it moves to Purchase Order Approved'
            ]
        ],
        'grn' => [
            'title' => 'Goods Received Notes (GRN)', 'icon' => 'ti-truck-delivery', 'color' => '#10b981', 'bg' => '#d1fae5',
            'desc'  => 'The physical receiving module. Actioning this ADDS stock strictly.',
            'badges' => ['Stock +ADD', 'Logistics'],
            'features' => [
                'Pull an Approved PO directly into a GRN',
                'Receive partial deliveries if the supplier short-shipped',
                'Locks in physical inventory additions to the Items master',
                'Records vehicle/transport details'
            ],
            'steps' => [
                'Purchase -> Goods Received Notes -> Add New',
                'Select the Vendor and PO Number',
                'Confirm quantities received and Save.'
            ]
        ],
        'debit_note' => [
            'title' => 'Debit Note (Returns)', 'icon' => 'ti-receipt', 'color' => '#e879f9', 'bg' => '#fae8ff',
            'desc'  => 'Return items back to the supplier.',
            'badges' => ['Stock -DEDUCT', 'Account Adj'],
            'features' => [
                'Link to a past inward invoice or GRN',
                'Returning items instantly deducts from your physical stock',
                'Lowers your accounts payable to the vendor'
            ],
            'steps' => [
                'Purchase -> Debit Note -> Create',
                'Select items returning to vendor',
                'System will process the financial and stock deduction.'
            ]
        ],
    ],
    'Targets & Billing' => [
        'sales_targets' => [
            'title' => 'Sales Targets & Distribution', 'icon' => 'ti-target', 'color' => '#f43f5e', 'bg' => '#ffe4e6',
            'desc'  => 'Set goals and distribute percentages to staff wallets upon completion.',
            'badges' => ['Goal Tracking', 'Incentives'],
            'features' => [
                'Add Targets sets monetary or quantity goals for staff or teams via "Add Targets"',
                '"Distribute Percentage" allows calculating metrics and routing cash commissions to specific employee wallets automatically'
            ],
            'steps' => [
                'Go to Targets -> Add Targets and define the goal month/amount',
                'Use Distribute Percentage periodically to evaluate invoice results and route profit percentages to Employee Wallets'
            ]
        ],
        'proforma_invoice' => [
            'title' => 'Proforma Invoice', 'icon' => 'ti-file-text', 'color' => '#0ea5e9', 'bg' => '#e0f2fe',
            'desc'  => 'Non-deducting quotations for prospective sales.',
            'badges' => ['Quotation', 'No Stock Change'],
            'features' => [
                'Bypasses stock validation checks completely (quote items out of stock)',
                'Looks visually identical to a tax invoice on PDF',
                'Does not alter inventory or customer ledgers at all'
            ],
            'steps' => [
                'Billing -> Proforma Invoice',
                'Select Customer, Add Items, Generate PDF'
            ]
        ],
        'tax_invoice' => [
            'title' => 'Tax Invoice', 'icon' => 'ti-file-invoice', 'color' => '#22c55e', 'bg' => '#dcfce7',
            'desc'  => 'Final GST-compliant billing engine. Decreases item inventory immediately.',
            'badges' => ['Stock -DEDUCT', 'GST Calculation'],
            'features' => [
                'Automatically grabs HSN GST percentages from item master',
                'Validates stock so you cannot sell what you do not have',
                'Automatically creates financial liability (Accounts Receivable) in Customer Ledger',
                'Supports application of Loyalty points'
            ],
            'steps' => [
                'Billing -> Tax Invoice',
                'Enter Customer and Items, configure discounts',
                'Save to decrement inventory and finalize sale'
            ]
        ],
    ],
    'Customer Operations' => [
        'customers_setup' => [
            'title' => 'Customer Setup & Wallet', 'icon' => 'ti-user-plus', 'color' => '#6366f1', 'bg' => '#e0e7ff',
            'desc'  => 'Manage types, addresses, and customer profiles.',
            'badges' => ['Pricing Brackets', 'Ledgers'],
            'features' => [
                'Customer Types (e.g. Retail, Wholesale) dictate which commission/pricing structures apply to the customer',
                'Customers master stores GSTIN, specific contact days (birthday/anniversary), and ledger openings',
                'Customers Wallet explicitly tracks their monetary credits or deposits within your system'
            ],
            'steps' => [
                'Manage Types in Customer Setup -> Customer Types',
                'Add Customers and assign a type',
                'View ledger and balances inside Customers master'
            ]
        ],
        'payment_received' => [
            'title' => 'Payment Received', 'icon' => 'ti-cash', 'color' => '#14b8a6', 'bg' => '#ccfbf1',
            'desc'  => 'Inward payments module clearing accounts receivable.',
            'badges' => ['Cashflow', 'Invoice Linking'],
            'features' => [
                'Log payments via Bank, Cash, or UPI',
                'Map a single inward payment to clear out one or multiple due invoices dynamically',
                'Updates the Customer Ledger balance'
            ],
            'steps' => [
                'Customer Payments -> Payment Received',
                'Select Customer and define Amount',
                'System will assign funds to clear oldest pending invoices'
            ]
        ],
        'payment_made' => [
            'title' => 'Payment Made', 'icon' => 'ti-businessplan', 'color' => '#f59e0b', 'bg' => '#fef3c7',
            'desc'  => 'Outward payments clearing your accounts payable to suppliers.',
            'badges' => ['Outward Flow', 'Debt Clearance'],
            'features' => [
                'Mirrors inward payments but mapped against purchasing Vendors',
                'Lowers outstanding money owed globally'
            ],
            'steps' => [
                'Vendor Payment Management -> Payment Made',
                'Log check numbers or transaction hashes for auditing'
            ]
        ],
    ],
    'Loyalty & Rewards' => [
        'loyalty_system' => [
            'title' => 'Loyalty Program', 'icon' => 'ti-award', 'color' => '#eab308', 'bg' => '#fef08a',
            'desc'  => 'Customer retention engine rewarding points based on purchase histories.',
            'badges' => ['Retention', 'Automated Points'],
            'features' => [
                'Loyalty Point Slabs define the rules (e.g., Spend $1000 = Earn 50 Points)',
                'Points are automatically granted to the Customer Wallet ONLY when an invoice is fully marked Paid',
                'Points can be redeemed mathematically on future Tax Invoices',
                'Loyalty Point Report tracks point creation and burn mechanics'
            ],
            'steps' => [
                'Define configuration in Loyalty Program -> Loyalty Point Slabs',
                'Check total accrued liabilities in Loyalty Point Report'
            ]
        ],
    ],
    'Analytics & Reporting' => [
        'sales_reports' => [
            'title' => 'Sales Analytics', 'icon' => 'ti-trending-up', 'color' => '#3b82f6', 'bg' => '#dbeafe',
            'desc'  => 'Granular viewing of all revenue data under Sales Operations.',
            'badges' => ['Revenue Data', 'Visuals'],
            'features' => [
                'Sales Report: Standard chronological billing logs',
                'Sales by Customer/Item/Salesperson: Filtered top-performer matrices',
                'Outstanding Invoice: Vital list of money still to collect',
                'Discount/Commission Reports: Check macro profitability after cuts'
            ],
            'steps' => [
                'Open Sales Operations in the sidebar',
                'Apply deep filters (Date, User, Region) inside any specific report module'
            ]
        ],
        'other_reports' => [
            'title' => 'Customer & Ledger Reports', 'icon' => 'ti-chart-pie', 'color' => '#8b5cf6', 'bg' => '#ede9fe',
            'desc'  => 'Detailed specific tracking for customer relations and adjustments.',
            'badges' => ['Relationships', 'Adjustments'],
            'features' => [
                'Customer History: Find what any single client buys the most',
                'Customer Contact/Birthday: Automated CRM lists for outreach',
                'Vendor/Customer Ledgers: Standardized accounting statements T-formatting',
                'Credit/Debit Note Reports: Review systemic adjustment flows safely'
            ],
            'steps' => [
                'Located across Customer Reports and Notes & Adjustments in the sidebar',
                'Export any report to PDF/Excel for external analysis'
            ]
        ]
    ]
];

?>

<style>
/* ===== DOCS LAYOUT ===== */
.docs-wrapper {
    display: flex;
    height: calc(100vh - 70px);
    overflow: hidden;
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 2px 20px rgba(0,0,0,.06);
}

/* Left Sidebar */
.docs-sidebar {
    width: 290px;
    min-width: 290px;
    background: #fff;
    border-right: 1px solid #e8ecf0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.docs-sidebar-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid #eee;
}
.docs-sidebar-header h6 {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #888;
    margin: 0 0 10px;
}
.docs-search {
    position: relative;
}
.docs-search input {
    width: 100%;
    padding: 7px 12px 7px 32px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    background: #f8f9fa;
    outline: none;
    transition: border .2s;
}
.docs-search input:focus { border-color: #4361ee; background: #fff; }
.docs-search i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: #aaa;
}
.docs-nav { overflow-y: auto; flex: 1; padding: 10px 0 20px; }
.docs-nav::-webkit-scrollbar { width: 4px; }
.docs-nav::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

.docs-group { margin-bottom: 4px; }
.docs-group-title {
    padding: 8px 20px 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #aaa;
}
.docs-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 20px;
    cursor: pointer;
    font-size: 13.5px;
    color: #444;
    border-left: 3px solid transparent;
    transition: all .15s;
    user-select: none;
    text-decoration: none;
}
.docs-nav-item:hover { background: #f0f4ff; color: #4361ee; }
.docs-nav-item.active { background: #eef1ff; color: #4361ee; border-left-color: #4361ee; font-weight: 600; }
.docs-nav-item i { font-size: 16px; width: 20px; flex-shrink: 0; }
.docs-nav-item.hidden { display: none; }

/* Right Content */
.docs-content {
    flex: 1;
    overflow-y: auto;
    padding: 32px 40px;
    position: relative;
}
.docs-content::-webkit-scrollbar { width: 6px; }
.docs-content::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

.doc-section { display: none; animation: fadeIn .25s ease; }
.doc-section.active { display: block; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Content Styling */
.doc-hero {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 28px;
    padding-bottom: 22px;
    border-bottom: 1px solid #eee;
}
.doc-hero-icon {
    width: 60px; height: 60px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}
.doc-hero h2 { font-size: 24px; font-weight: 700; margin: 0 0 5px; color: #222; }
.doc-hero p  { font-size: 14px; color: #666; margin: 0; }

.doc-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 25px; }
.doc-badge { background: #eef1ff; color: #4361ee; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

.doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 25px; }

.doc-card {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 12px;
    padding: 22px 24px;
}
.doc-card h4 {
    font-size: 15px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.doc-card h4 i { color: #4361ee; }

.doc-features-list { list-style: none; padding: 0; margin: 0; }
.doc-features-list li {
    padding: 10px 0;
    border-bottom: 1px dashed #eee;
    font-size: 13.5px;
    color: #444;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.doc-features-list li:last-child { border-bottom: none; padding-bottom: 0; }
.doc-features-list li i { color: #10b981; margin-top: 3px; }

.doc-steps { list-style: none; padding: 0; margin: 0; }
.doc-steps li {
    display: flex;
    gap: 14px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
    font-size: 13.5px;
    color: #444;
    line-height: 1.6;
}
.doc-steps li:last-child { border-bottom: none; }
.step-num {
    width: 24px; height: 24px;
    background: #4361ee;
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 1px;
}

</style>

<div class="container-fluid p-3">
    <?php
    // Map documentation keys to actual route slugs to check permissions dynamically
    $docPermissionMap = [
        'dashboard' => 'dashboard',
        'roles_permissions' => 'access_control',
        'designation_dept' => 'department',
        'employees' => 'employees',
        'employee_wallet' => 'employee_incentive_wallet',
        'hsn_units' => 'hsn_list',
        'items_master' => 'items',
        'current_stock' => 'current_stock',
        'vendors' => 'vendors',
        'purchase_orders' => 'purchase_orders',
        'grn' => 'goods_received_notes',
        'debit_note' => 'debit_note',
        'sales_targets' => 'add_targets',
        'proforma_invoice' => 'proforma_invoice',
        'tax_invoice' => 'tax_invoice',
        'customers_setup' => 'customers_listing',
        'payment_received' => 'payment_received',
        'payment_made' => 'payment_made',
        'loyalty_system' => 'loyalty_point_slabs'
    ];

    foreach ($documentation as $group => &$items) {
        foreach ($items as $id => $data) {
            $slugToCheck = $docPermissionMap[$id] ?? $id;
            // Add view access check. If no access, remove from array.
            if (!can_access($slugToCheck, 'view')) {
                unset($items[$id]);
            }
        }
        // If a group has NO accessible items, remove the whole group
        if (empty($items)) {
            unset($documentation[$group]);
        }
    }
    unset($items);

    if (empty($documentation)) {
        echo '<div class="text-center mt-5">';
        echo '<i class="ti ti-notebook text-muted mb-3" style="font-size: 5rem; opacity: 0.5;"></i>';
        echo '<h4 class="fw-bold">No Documentation Available</h4>';
        echo '<p class="text-muted">You do not have access to any modules that contain documentation.</p>';
        echo '</div></div><!-- closes container -->';
        return; // Stop rendering UI wrapper if empty
    }
    ?>
    <div class="docs-wrapper">
        <!-- Sidebar Navigation -->
        <div class="docs-sidebar">
            <div class="docs-sidebar-header">
                <h6><i class="ti ti-help-circle me-1"></i> System Docs</h6>
                <div class="docs-search">
                    <i class="ti ti-search"></i>
                    <input type="text" id="docsSearch" placeholder="Search modules, features...">
                </div>
            </div>
            <nav class="docs-nav" id="docsNav">
                <?php
                $first = true;
                foreach ($documentation as $group => $items) {
                    echo "<div class='docs-group'>";
                    echo "<div class='docs-group-title'>" . htmlspecialchars($group) . "</div>";
                    foreach ($items as $id => $data) {
                        $active = $first ? 'active' : '';
                        $first = false;
                        // VERY IMPORTANT: Using data-section instead of data-target to avoid app.js NaN bug!
                        echo "<div class='docs-nav-item {$active}' data-section='{$id}'>
                                <i class='ti {$data['icon']}' style='color: {$data['color']}'></i> {$data['title']}
                              </div>";
                    }
                    echo "</div>";
                }
                ?>
            </nav>
        </div>

        <!-- Right Content Area -->
        <div class="docs-content" id="docsContent">
            <?php
            $first = true;
            foreach ($documentation as $group => $items) {
                foreach ($items as $id => $data) {
                    $active = $first ? 'active' : '';
                    $first = false;
                    
                    echo "<div class='doc-section {$active}' id='doc-{$id}'>";
                    
                    // Hero
                    echo "<div class='doc-hero'>";
                    echo "<div class='doc-hero-icon' style='background: {$data['bg']}; color: {$data['color']};'><i class='ti {$data['icon']}'></i></div>";
                    echo "<div><h2>{$data['title']}</h2><p>{$data['desc']}</p></div>";
                    echo "</div>";

                    // Badges
                    if (!empty($data['badges'])) {
                        echo "<div class='doc-badges'>";
                        foreach ($data['badges'] as $badge) {
                            echo "<span class='doc-badge'>{$badge}</span>";
                        }
                        echo "</div>";
                    }

                    echo "<div class='doc-grid'>";
                    
                    // Features Card
                    if (!empty($data['features'])) {
                        echo "<div class='doc-card'>";
                        echo "<h4><i class='ti ti-star'></i> Key Features</h4>";
                        echo "<ul class='doc-features-list'>";
                        foreach ($data['features'] as $feature) {
                            echo "<li><i class='ti ti-check'></i> <div>{$feature}</div></li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }

                    // Steps Card
                    if (!empty($data['steps'])) {
                        echo "<div class='doc-card'>";
                        echo "<h4><i class='ti ti-list-check'></i> How it works</h4>";
                        echo "<ul class='doc-steps'>";
                        foreach ($data['steps'] as $index => $step) {
                            $num = $index + 1;
                            echo "<li><div class='step-num'>{$num}</div><div>{$step}</div></li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }

                    echo "</div>"; // End Grid
                    echo "</div>"; // End Section
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Sidebar Navigation Click
    document.querySelectorAll('.docs-nav-item').forEach(item => {
        item.addEventListener('click', function() {
            // Remove active from all nav items
            document.querySelectorAll('.docs-nav-item').forEach(n => n.classList.remove('active'));
            // Add active to clicked
            this.classList.add('active');
            
            // Hide all sections
            document.querySelectorAll('.doc-section').forEach(s => s.classList.remove('active'));
            
            // Get module ID explicitly through data-section (AVOIDING data-target for compatibility)
            const sectionId = this.getAttribute('data-section');
            if (sectionId) {
                const sectionEl = document.getElementById('doc-' + sectionId);
                if (sectionEl) {
                    sectionEl.classList.add('active');
                    // Scroll to top
                    document.getElementById('docsContent').scrollTop = 0;
                }
            }
        });
    });

    // Sidebar Live Search
    const searchInput = document.getElementById('docsSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            
            document.querySelectorAll('.docs-group').forEach(group => {
                let hasVisible = false;
                group.querySelectorAll('.docs-nav-item').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(term)) {
                        item.classList.remove('hidden');
                        hasVisible = true;
                    } else {
                        item.classList.add('hidden');
                    }
                });
                
                if (hasVisible) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });
        });
    }
});
</script>
