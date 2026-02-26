<?php

$requestUri    = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 🔥 dynamic base path
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = rtrim($scriptDir, '/');
$basePath  = $scriptDir === '/' ? '' : $scriptDir;

// clean route path
$path = trim(str_replace($basePath, '', $requestUri), '/');
 

$routes = [
    'GET' => [
        //==>Auth Routes
        //'organizations_register' => 'views/organizations/organizations_register.php',
        'documentation' => 'views/documentation/documentation.php',
        ''              => 'views/auth/auth-sign-in.php',
        'register'      => 'views/auth/auth-sign-up.php',
        'lock-screen'   => 'views/auth/auth-lock-screen.php',
        'reset-password' => 'views/auth/auth-reset-pass.php',
        'new-password'   => 'views/auth/auth-new-pass.php',
        'two-factor'     => 'views/auth/auth-two-factor.php',
        'logout'         => 'controller/auth/LogoutController.php',
        
        //==>Add more GET routes as needed
        //==>Dashboard
        'dashboard'      => 'views/dashboard/index.php',

        //==>Profile & Settings
        'profile'          => 'views/profile/profile.php',
        'account_settings' => 'views/profile/account_settings.php',

        //==>Notifications
        'notifications' => 'views/notifications/notifications_listing.php',
        'read_notification' => 'controller/notifications/read_notification.php',
        'delete_notification' => 'controller/notifications/delete_notification.php',

        //==>Access Control (Masters)
        'access_control' => 'views/masters/access_control.php',
        // 'roles'          => 'views/masters/roles_listing.php',
        'designation_listing' => 'views/masters/designation_listing.php',
        'department'     => 'views/masters/department.php',
        'employees'      => 'views/masters/employees_listing.php',

        //==>Inventory Management (Product Management)
        'hsn_list'        => 'views/masters/hsn_listing.php',
        'units'           => 'views/masters/units_listing.php',
        'items'           => 'views/masters/items_listing.php',
        'current_stock'   => 'views/inventory/current_stock.php',
  
        //==>Sales Management: 
        
        //Purchase
        'purchase_orders'  => 'views/purchase/purchase_orders_listing.php',
        'purchase_orders_pending'  => 'views/purchase/purchase_orders_pending.php',
        'purchase_orders_approved' => 'views/purchase/purchase_orders_approved.php',
        'view_purchase_order' => 'views/purchase/view_purchase_order.php',
        'purchase_invoice' => 'views/purchase/purchase_invoice_listing.php', 

        //==>Receive Goods
        'goods_received_notes' => 'views/purchase/goods_received_notes.php', 
        'debit_note' => 'views/billing/debit_note.php',
        'print_grn' => 'views/purchase/print_grn.php',

        //==>Monthly Targets
        'add_targets' => 'views/purchase/add_targets.php', 
        'view_monthly_target' => 'views/purchase/view_monthly_target.php',
        'distribute_percentage' => 'views/purchase/distribute_percentage.php',
        'employee_incentive_wallet' => 'views/purchase/employee_incentive_wallet.php',

        //==>Billing Management
        'proforma_invoice' => 'views/billing/proforma_invoice.php',
        'proforma_invoice_view' => 'views/billing/proforma_invoice_view.php',
        'tax_invoice' => 'views/billing/tax_invoice.php',
        'sales_report' => 'views/reports/sales_report.php',
        'inventory_report' => 'views/reports/inventory_report.php',
        'view_invoice' => 'views/billing/tax_invoice_view.php', 
        'print_invoice_view' => 'views/billing/print_invoice_view.php', 
        'credit_note_report' => 'views/reports/credit_note_report.php',
        'debit_note_report' => 'views/reports/debit_note_report.php',
        'purchase_history_report' => 'views/reports/purchase_history_report.php',
        'customer_purchase_history_report' => 'views/reports/customer_purchase_history_report.php',
        'employee_performance_report' => 'views/reports/employee_performance_report.php',
        'discount_report' => 'views/reports/discount_report.php',
        'sales_by_customer_report' => 'views/reports/sales_by_customer_report.php',
        'sales_by_item_report' => 'views/reports/sales_by_item_report.php',
        'sales_by_salesperson_report' => 'views/reports/sales_by_salesperson_report.php',
        'department_wise_sales_report'=> 'views/reports/department_wise_sales_report.php',
        'user_wise_collection_report' => 'views/reports/user_wise_collection_report.php',
        'user_collection_details'     => 'views/reports/user_collection_details.php',
        'customer_ledger_report'      => 'views/reports/customer_ledger_report.php',
        'outstanding_invoice_report' => 'views/reports/outstanding_invoice_report.php',
        'product_wise_purchase_report' => 'views/reports/product_wise_purchase_report.php',
        'product_price_comparison_report' => 'views/reports/product_price_comparison_report.php',
        'loyalty_point_report' => 'views/reports/loyalty_point_report.php',
        'commission_report' => 'views/reports/commission_report.php',
        'customer_reports' => 'views/reports/customer_reports.php',
        'vendor_ledger_report' => 'views/reports/vendor_ledger_report.php',
        'inventory_ageing_report' => 'views/reports/inventory_ageing_report.php',
        'credit_note' => 'views/billing/credit_note.php',
        'credit_note_view' => 'views/billing/credit_note_view.php',

        //==>Customer Management
        'customers_type_listing' => 'views/customers/customers_type_listing.php',
        'customers_listing' => 'views/customers/customers_listing.php',
        'customers_wallet' => 'views/customers/customers_wallet.php',

        //==>Payment
        'payment_received' => 'views/payment/payment_received.php',
        'payment_made' => 'views/payment/payment_made.php',
        'payment_made_ledger' => 'views/payment/payment_made.php',
        'payment_received_ledger' => 'views/payment/payment_made.php', 

        //==>Supplier Management
        'vendors'        => 'views/masters/vendors_listing.php',

        //==>Others: Loyalty Program
        'loyalty_point_slabs' => 'views/loyalty/loyalty_point_slabs.php',
    ],
];


if (isset($routes[$requestMethod][$path])) {
    $file = __DIR__ . '/' . $routes[$requestMethod][$path];

    if (file_exists($file)) {
        
        // Determine if we should use the Master Layout
        // We use layout for Dashboard and Masters, but NOT for Auth views or Controllers.
        $isAuthView = strpos($routes[$requestMethod][$path], 'views/auth/') !== false;
        $isController = strpos($routes[$requestMethod][$path], 'controller/') !== false;

        if ($isAuthView || $isController || strpos($path, 'print_grn') !== false || strpos($path, 'print_invoice_view') !== false) {
            // Render directly without layout
            require $file;
        } else {
            // Render with Master Layout
            require __DIR__ . '/config/auth_guard.php'; // Ensure user is logged in
            
            // Set variables for the view if needed (can be set inside the view files too)
            $viewFile = $file; 
            
            // Load the Master Layout
            require __DIR__ . '/views/layouts/main_layout.php';
        }
        exit;
    }
}

http_response_code(404);
require __DIR__ . '/404.php';
