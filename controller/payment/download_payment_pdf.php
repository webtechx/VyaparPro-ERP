<?php
// download_payment_pdf.php - Wrapper to download PDF using shared helper
require_once '../../config/auth_guard.php';
require_once __DIR__ . '/pdf_helper.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Payment ID");
}

$payment_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// Get Payment Number for filename (helper doesn't return it directly, but we can quick fetch or just use ID)
// Actually, let's just fetch the number for a nice filename.
$sql = "SELECT payment_number FROM payment_received WHERE payment_id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $payment_id, $organization_id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0) die("Payment not found");
$pay = $res->fetch_assoc();
$payment_no = $pay['payment_number'];

// Generate PDF
$pdfContent = generatePaymentPDF($payment_id, $conn, $organization_id);

if (!$pdfContent) {
    die("Error generating PDF.");
}

// Stream to browser
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=Payment_Receipt_$payment_no.pdf");
header("Content-Length: " . strlen($pdfContent));
echo $pdfContent;
exit;
?>
