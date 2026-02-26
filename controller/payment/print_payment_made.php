<?php
// controller/payment/print_payment_made.php - Print exact View
require_once '../../config/auth_guard.php';
require_once __DIR__ . '/pdf_helper_made.php'; // Reuse helper for HTML content

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Payment ID");
}

$payment_id = intval($_GET['id']);
$organization_id = $_SESSION['organization_id'];

// Initial Fetch to check existence
$sql = "SELECT payment_id FROM payment_made WHERE payment_id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $payment_id, $organization_id);
$stmt->execute();
if($stmt->get_result()->num_rows === 0) die("Payment not found");

// Get HTML
$html = getPaymentMadeHTML($payment_id, $conn, $organization_id, false);
if(!$html) die("Error generating view");

// Inject Print Scripts
$backBtn = '<div class="no-print" style="position: fixed; top: 10px; left: 10px; z-index:1000;"><a href="../../payment_made" style="text-decoration:none; display:inline-block; padding: 10px 20px; background: #333; color: white; border-radius: 4px; font-family: sans-serif;">&larr; Back</a></div>';
$html = str_replace('<body>', '<body>' . $backBtn, $html);
$html = str_replace('</body>', '<script>window.onload = function() { window.print(); }</script></body>', $html);
$html = str_replace('</head>', '<style>@media print { .no-print { display: none !important; } @page { size: A5 landscape; margin: 0; } body { margin: 0; padding: 10mm; } }</style>', $html); // Add basic print styles if needed, though helper has them

echo $html;
?>
