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
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 1. Fetch Vendor list for filter
// (Typically handled by Select2 via AJAX, but for prefilling)
$vendor_name_prefill = '';
$vendor_avatar_prefill = '';
if ($vendor_id > 0) {
    $v_sql = "SELECT display_name as vendor_name, avatar FROM vendors_listing WHERE vendor_id = ? AND organization_id = ?";
    $stmt = $conn->prepare($v_sql);
    $stmt->bind_param("ii", $vendor_id, $organization_id);
    $stmt->execute();
    $pres = $stmt->get_result();
    if($pres->num_rows > 0){
        $vrow = $pres->fetch_assoc();
        $vendor_name_prefill = $vrow['vendor_name'];
        $vendor_avatar_prefill = $vrow['avatar'];
    }
}

// 2. Main Query for Ledger
// We need to fetch:
// - GRN / Goods Received Notes (Debit) - Increases balance due, shown under PO number
// - Payments Made (Credit) - Decreases balance due
// - Debit Notes (Credit) - Decreases balance due

$grn_date_part = "";
$pay_date_part = "";
$dn_date_part = "";

if($start_date && $end_date) {
    $grn_date_part = " AND grn.grn_date BETWEEN '$start_date' AND '$end_date'";
    $pay_date_part = " AND payment_date BETWEEN '$start_date' AND '$end_date'";
    $dn_date_part  = " AND dn.debit_note_date BETWEEN '$start_date' AND '$end_date'";
}

// Sub-Query Construction
$union_queries = [];

// A. GRN / Goods Received Notes (Debit - increases payable)
// Amount = SUM(received_qty * rate) from grn_items joined with po_items for rate
$sql_grn = "SELECT 
            grn.grn_id as id, 
            grn.grn_date as trans_date, 
            grn.grn_number as ref_no, 
            po.po_number as external_ref,
            'Receive GRN' as type_label,
            'GRN' as type_code,
            COALESCE(SUM(gi.received_qty * poi.rate), 0) as debit_amount,
            0 as credit_amount,
            'received' as status,
            grn.vendor_id,
            grn.remarks as notes,
            '' as payment_mode,
            v.display_name as vendor_name
           FROM goods_received_notes grn
           LEFT JOIN vendors_listing v ON grn.vendor_id = v.vendor_id
           LEFT JOIN purchase_orders po ON grn.po_id = po.purchase_orders_id
           LEFT JOIN goods_received_note_items gi ON grn.grn_id = gi.grn_id
           LEFT JOIN purchase_order_items poi ON gi.po_item_id = poi.id
           WHERE grn.organization_id = $organization_id
           $grn_date_part";

if($vendor_id > 0) {
    $sql_grn .= " AND grn.vendor_id = $vendor_id";
}
$sql_grn .= " GROUP BY grn.grn_id";
$union_queries[] = $sql_grn;


// B. PayOuts (Decrease Payable -> Credit)
// Table: payment_made
$sql_pay = "SELECT 
            pm.payment_id as id, 
            pm.payment_date as trans_date, 
            pm.payment_number as ref_no, 
            pm.reference_no as external_ref,
            'Payment Made' as type_label,
            'PAY' as type_code,
            0 as debit_amount,
            pm.amount as credit_amount,
            'paid' as status,
            pm.vendor_id,
            pm.notes,
            pm.payment_mode,
            v.display_name as vendor_name
           FROM payment_made pm
           LEFT JOIN vendors_listing v ON pm.vendor_id = v.vendor_id
           WHERE pm.organization_id = $organization_id 
           $pay_date_part";
           
if($vendor_id > 0) {
    $sql_pay .= " AND pm.vendor_id = $vendor_id";
}
$union_queries[] = $sql_pay;


// C. Debit Notes (Decrease Payable -> Credit)
// Table: debit_notes
$sql_dn = "SELECT 
            dn.debit_note_id as id, 
            dn.debit_note_date as trans_date, 
            dn.debit_note_number as ref_no, 
            '' as external_ref,
            'Debit Note' as type_label,
            'DN' as type_code,
            0 as debit_amount,
            COALESCE(SUM(dni.return_qty * poi.rate), 0) as credit_amount,
            'approved' as status,
            dn.vendor_id,
            dn.remarks as notes,
            '' as payment_mode,
            v.display_name as vendor_name
           FROM debit_notes dn
           LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
           LEFT JOIN debit_note_items dni ON dn.debit_note_id = dni.debit_note_id
           LEFT JOIN purchase_order_items poi ON dni.po_item_id = poi.id
           WHERE dn.organization_id = $organization_id 
           $dn_date_part";

if($vendor_id > 0) {
    $sql_dn .= " AND dn.vendor_id = $vendor_id";
}
$sql_dn .= " GROUP BY dn.debit_note_id";
$union_queries[] = $sql_dn;

// Combine
$full_sql = implode(" UNION ALL ", $union_queries) . " ORDER BY trans_date ASC";

// echo $full_sql; // Debug

$result = $conn->query($full_sql);

$transactions = [];
$total_debit = 0;
$total_credit = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
        $total_debit += $row['debit_amount'];
        $total_credit += $row['credit_amount'];
    }
}

// Vendor Details & Opening Balance Calculation
$vendor_details = null;
$opening_balance = 0;

if($vendor_id > 0) {
    // 1. Fetch Vendor Initial Opening Balance
    $v_res = $conn->query("SELECT *, display_name as vendor_name FROM vendors_listing WHERE vendor_id = $vendor_id");
    if($v_res && $v_res->num_rows > 0) {
        $vendor_details = $v_res->fetch_assoc();
        
        $initial_op = floatval($vendor_details['opening_balance']);
        $obt = strtolower($vendor_details['opening_balance_type']);
        if($obt == 'credit' || $obt == 'cr') {
             $initial_op = -1 * $initial_op;
        }
        $opening_balance = $initial_op;
    }

    // 2. Add Transactions BEFORE $start_date

    // GRNs before start_date (Debit)
    $sql_prev_grn = "SELECT COALESCE(SUM(gi.received_qty * poi.rate), 0) as total 
                     FROM goods_received_notes grn
                     LEFT JOIN goods_received_note_items gi ON grn.grn_id = gi.grn_id
                     LEFT JOIN purchase_order_items poi ON gi.po_item_id = poi.id
                     WHERE grn.organization_id = $organization_id 
                     AND grn.vendor_id = $vendor_id 
                     AND grn.grn_date < '$start_date'";
    $res_prev_grn = $conn->query($sql_prev_grn);
    $prev_grn = ($res_prev_grn) ? floatval($res_prev_grn->fetch_assoc()['total']) : 0;

    // Payments before start_date (Credit)
    $sql_prev_pay = "SELECT SUM(amount) as total FROM payment_made 
                     WHERE organization_id = $organization_id 
                     AND vendor_id = $vendor_id 
                     AND payment_date < '$start_date'";
    $res_prev_pay = $conn->query($sql_prev_pay);
    $prev_pay = ($res_prev_pay) ? floatval($res_prev_pay->fetch_assoc()['total']) : 0;

    // Debit Notes before start_date (Credit - reduces payable)
    $sql_prev_dn = "SELECT COALESCE(SUM(dni.return_qty * poi.rate), 0) as total 
                    FROM debit_notes dn
                    LEFT JOIN debit_note_items dni ON dn.debit_note_id = dni.debit_note_id
                    LEFT JOIN purchase_order_items poi ON dni.po_item_id = poi.id
                    WHERE dn.organization_id = $organization_id 
                    AND dn.vendor_id = $vendor_id 
                    AND dn.debit_note_date < '$start_date'";
    $res_prev_dn = $conn->query($sql_prev_dn);
    $prev_dn = ($res_prev_dn) ? floatval($res_prev_dn->fetch_assoc()['total']) : 0;

    // Calculate Net Opening Balance
    // Balance = Initial + Debit(GRN) - Credit(Pay) - Credit(DN)
    $opening_balance = $opening_balance + $prev_grn - $prev_pay - $prev_dn;
}

?>
