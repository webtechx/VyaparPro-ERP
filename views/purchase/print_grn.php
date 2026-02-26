<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/config/conn.php';
if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$grn_id = intval($_GET['id']);

// Fetch GRN Details
$sql = "SELECT grn.*, po.po_number, po.order_date, v.display_name as vendor_name, 
        v.work_phone, v.email, vad.address_line1, vad.city, vad.state, vad.pin_code,
        org.organization_name, org.organization_logo, org.address as org_address
        FROM goods_received_notes grn
        LEFT JOIN purchase_orders po ON grn.po_id = po.purchase_orders_id
        LEFT JOIN vendors_listing v ON grn.vendor_id = v.vendor_id
        LEFT JOIN vendors_addresses vad ON v.vendor_id = vad.vendor_id AND vad.address_type = 'billing'
        CROSS JOIN organizations org ON org.organization_id = 1
        WHERE grn.grn_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $grn_id);
$stmt->execute();
$headRes = $stmt->get_result();

if ($headRes->num_rows === 0) {
    die("GRN Not Found");
}

$grn = $headRes->fetch_assoc();

// Fetch Items
$iSql = "SELECT gi.*, il.item_name, u.unit_name, poi.rate as po_rate, h.hsn_code 
         FROM goods_received_note_items gi
         LEFT JOIN items_listing il ON gi.item_id = il.item_id
         LEFT JOIN units_listing u ON il.unit_id = u.unit_id
         LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
         LEFT JOIN purchase_order_items poi ON gi.po_item_id = poi.id
         WHERE gi.grn_id = ?";
$iStmt = $conn->prepare($iSql);
$iStmt->bind_param("i", $grn_id);
$iStmt->execute();
$items = $iStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN Print - <?= $grn['grn_number'] ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px;}
        .container { max-width: 900px; margin: auto; padding: 20px; border: 1px solid #ddd; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .logo img { max-height: 60px; }
        .company-info h2 { margin: 0 0 5px; color: #444; }
        .company-info p { margin: 2px 0; color: #777; font-size: 11px; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; color: #2c3e50; font-size: 24px; }
        .doc-title p { margin: 5px 0 0; color: #7f8c8d; font-weight: bold; }
        
        .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-box { width: 48%; }
        .info-box h3 { margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px; color: #555; text-transform: uppercase; }
        .info-row { display: flex; margin-bottom: 5px; }
        .info-label { width: 100px; font-weight: bold; color: #666; }
        .info-val { flex: 1; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #444; border-top: 2px solid #444; border-bottom: 2px solid #444; }
        td { vertical-align: top; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: space-between; font-size: 11px; color: #777; }
        
        .remarks-box { background: #f9f9f9; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .remarks-title { font-weight: bold; margin-bottom: 5px; font-size: 11px; text-transform: uppercase; color: #666;}
        
        @media print {
            body { padding: 0; }
            .container { border: none; max-width: 100%; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right; max-width: 800px; margin: 0 auto 10px;">
        <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 4px;">Print GRN</button>
    </div>

    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="logo">
                     <!-- Adjust path if logo is stored differently -->
                    <?php if(!empty($grn['organization_logo'])): ?>
                         <img src="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $grn['organization_logo'] ?>" alt="Logo" style="height: 50px;">
                    <?php else: ?>
                         <h2><?= htmlspecialchars($grn['organization_name']) ?></h2>
                    <?php endif; ?>
                  
                </div>
                <div style="margin-top: 10px;">
                    <p><strong><?= htmlspecialchars($grn['organization_name']) ?></strong></p>
                    <p><?= nl2br(htmlspecialchars($grn['org_address'])) ?></p>
                </div>
            </div>
            <div class="doc-title">
                <h1>GOODS RECEIVED NOTE</h1>
                <p><?= $grn['grn_number'] ?></p>
            </div>
        </div>

        <!-- Info -->
        <div class="info-section">
            <div class="info-box">
                <h3>Vendor Details</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-val"><?= htmlspecialchars($grn['vendor_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-val"><?= htmlspecialchars($grn['work_phone']) ?></span>
                </div>
                <?php if($grn['address_line1']): ?>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-val">
                        <?= htmlspecialchars($grn['address_line1']) ?><br>
                        <?= htmlspecialchars($grn['city']) ?>, <?= htmlspecialchars($grn['state']) ?> - <?= $grn['pin_code'] ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="info-box">
                <h3>Reference Details</h3>
                <div class="info-row">
                    <span class="info-label">GRN Date:</span>
                    <span class="info-val"><?= date('d M Y', strtotime($grn['grn_date'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PO Number:</span>
                    <span class="info-val fw-bold"><?= htmlspecialchars($grn['po_number']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PO Date:</span>
                    <span class="info-val"><?= date('d M Y', strtotime($grn['order_date'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Challan No:</span>
                    <span class="info-val"><?= htmlspecialchars($grn['challan_no'] ?: '-') ?></span>
                </div>
            </div>
        </div>

        <!-- Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 30%">Item Description</th>
                    <th style="width: 10%" class="text-center">HSN</th>
                    <th style="width: 10%" class="text-right">Rate</th>
                    <th style="width: 8%" class="text-center">Ord. Qty</th>
                    <th style="width: 10%" class="text-right">Ord. Val</th>
                    <th style="width: 8%" class="text-center">Rec. Qty</th>
                    <th style="width: 10%" class="text-right">Rec. Val</th>
                    <th style="width: 19%">Condition / Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                while($item = $items->fetch_assoc()): 
                    $rate = floatval($item['po_rate']);
                    $ordQty = floatval($item['ordered_qty']);
                    $recQty = floatval($item['received_qty']);
                    $ordVal = $ordQty * $rate;
                    $recVal = $recQty * $rate;
                ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td>
                        <span class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></span><br>
                        <span style="font-size: 10px; color: #666;"><?= $item['unit_name'] ?></span>
                    </td>
                    <td class="text-center"><?= $item['hsn_code'] ?? '-' ?></td>
                    <td class="text-right"><?= number_format($rate, 2) ?></td>
                    <td class="text-center"><?= $ordQty ?></td>
                    <td class="text-right"><?= number_format($ordVal, 2) ?></td>
                    <td class="text-center fw-bold"><?= $recQty ?></td>
                    <td class="text-right fw-bold"><?= number_format($recVal, 2) ?></td>
                    <td>
                        <div style="font-weight: bold;"><?= $item['condition_status'] ?></div>
                        <?php if(!empty($item['remarks'])): ?>
                            <div style="font-size: 10px; color: #555; font-style: italic;"><?= htmlspecialchars($item['remarks']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

         <?php if(!empty($grn['remarks'])): ?>
            <div class="remarks-box">
                <div class="remarks-title">General Remarks</div>
                <?= nl2br(htmlspecialchars($grn['remarks'])) ?>
            </div>
        <?php endif; ?>

        <!-- Footer Signatures -->
        <div class="footer">
            <div>
                Received By<br><br>
                __________________________<br>
                (Signature & Date)
            </div>
            <div style="text-align: right;">
                Authorized By<br><br>
                __________________________<br>
                (Signature & Date)
            </div>
        </div>
        
        <div class="text-center" style="margin-top: 30px; font-size: 10px; color: #999;">
            Generated from Samadhan ERP on <?= date('d M Y H:i') ?>
        </div>

    </div>

</body>
</html>
