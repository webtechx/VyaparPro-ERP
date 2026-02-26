<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/conn.php';

// Organization Security
$organization_id = $_SESSION['organization_id'];

// Filter Variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch Customer list for filter prefilling
$customer_name_prefill = '';
$customer_avatar_prefill = '';
$customer_details = null;

if ($customer_id > 0) {
    $c_sql = "SELECT customer_name, company_name, avatar, current_balance_due FROM customers_listing WHERE customer_id = ? AND organization_id = ?";
    $stmt = $conn->prepare($c_sql);
    $stmt->bind_param("ii", $customer_id, $organization_id);
    $stmt->execute();
    $cres = $stmt->get_result();
    if($cres->num_rows > 0){
        $customer_details = $cres->fetch_assoc();
        $customer_name_prefill = $customer_details['company_name'] ? $customer_details['company_name'] . ' (' . $customer_details['customer_name'] . ')' : $customer_details['customer_name'];
        $customer_avatar_prefill = $customer_details['avatar'];
    }
}

// Ledger Query Setup
$transactions = [];
$total_debit = 0;
$total_credit = 0;
$opening_balance = 0;

if($customer_id > 0) {
    
    // 1. Calculate Opening Balance before start date
    // Balance for a customer ledger = SUM(debit) - SUM(credit)
    $sql_prev = "SELECT COALESCE(SUM(debit), 0) as tod, COALESCE(SUM(credit), 0) as toc 
                 FROM customers_ledger 
                 WHERE organization_id = ? AND customer_id = ? AND transaction_date < ?";
    $stmt_prev = $conn->prepare($sql_prev);
    $stmt_prev->bind_param("iis", $organization_id, $customer_id, $start_date);
    $stmt_prev->execute();
    $res_prev = $stmt_prev->get_result();
    
    if($res_prev && $res_prev->num_rows > 0) {
        $row_prev = $res_prev->fetch_assoc();
        $opening_balance = floatval($row_prev['tod']) - floatval($row_prev['toc']);
    }

    // 2. Fetch Transactions
    $sql_trans = "SELECT cl.ledger_id as id, 
                         cl.transaction_date as trans_date, 
                         cl.reference_id, 
                         cl.reference_type as type_code, 
                         cl.particulars as type_label, 
                         cl.debit as debit_amount, 
                         cl.credit as credit_amount,
                         CASE 
                             WHEN cl.reference_type = 'invoice' THEN si.invoice_number
                             WHEN cl.reference_type = 'payment' THEN pr.payment_number
                             WHEN cl.reference_type = 'credit_note' THEN cn.credit_note_number
                             ELSE ''
                         END as ref_no,
                         CASE 
                             WHEN cl.reference_type = 'invoice' THEN si.notes
                             WHEN cl.reference_type = 'payment' THEN pr.notes
                             WHEN cl.reference_type = 'credit_note' THEN cn.notes
                             ELSE ''
                         END as notes,
                         '' as payment_mode
                  FROM customers_ledger cl
                  LEFT JOIN sales_invoices si ON cl.reference_type = 'invoice' AND cl.reference_id = si.invoice_id
                  LEFT JOIN payment_received pr ON cl.reference_type = 'payment' AND cl.reference_id = pr.payment_id
                  LEFT JOIN credit_notes cn ON cl.reference_type = 'credit_note' AND cl.reference_id = cn.credit_note_id
                  WHERE cl.organization_id = ? 
                  AND cl.customer_id = ? 
                  AND cl.transaction_date BETWEEN ? AND ?";
    
    if (!empty($search_query)) {
        $sql_trans .= " AND (si.invoice_number LIKE ? OR pr.payment_number LIKE ? OR cn.credit_note_number LIKE ?)";
    }
                  
    $sql_trans .= " ORDER BY cl.transaction_date ASC, cl.ledger_id ASC";
    
    $stmt_trans = $conn->prepare($sql_trans);
    
    if (!empty($search_query)) {
        $like = "%$search_query%";
        $stmt_trans->bind_param("iisssss", $organization_id, $customer_id, $start_date, $end_date, $like, $like, $like);
    } else {
        $stmt_trans->bind_param("iiss", $organization_id, $customer_id, $start_date, $end_date);
    }
    
    $stmt_trans->execute();
    $res_trans = $stmt_trans->get_result();
    
    while ($row = $res_trans->fetch_assoc()) {
        $transactions[] = $row;
        $total_debit += $row['debit_amount'];
        $total_credit += $row['credit_amount'];
    }
} else {
    // If no customer is selected, we can fetch all ledgers or none. 
    // Usually ledger is specifically for 1 entity. 
    // We'll leave $transactions empty if $customer_id == 0
}
?>
