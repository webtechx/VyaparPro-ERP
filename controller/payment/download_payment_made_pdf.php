<?php
// controller/payment/download_payment_made_pdf.php
require_once '../../config/auth_guard.php';
require_once __DIR__ . '/pdf_helper_made.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Payment ID");
}

$payment_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// Get Payment Number
$sql = "SELECT payment_number FROM payment_made WHERE payment_id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $payment_id, $organization_id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0) die("Payment not found");
$pay = $res->fetch_assoc();
$payment_no = $pay['payment_number'];

// Generate PDF
$pdfContent = generatePaymentMadePDF($payment_id, $conn, $organization_id);

if (!$pdfContent) {
    die("Error generating PDF.");
}

// Stream to browser
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=Payment_Voucher_$payment_no.pdf");
header("Content-Length: " . strlen($pdfContent));
echo $pdfContent;
exit;
?>
