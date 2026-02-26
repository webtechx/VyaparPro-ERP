<?php
require_once dirname(__DIR__, 2) . '/config/conn.php';
session_start(); // Ensure session is active

// Auto-print script
$autoPrintScript = "<script>window.onload = function() { window.print(); }</script>";

if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$dn_id = intval($_GET['id']);

// Fetch Debit Note Header
$sql = "SELECT dn.*, po.po_number, po.order_date as po_date, v.display_name as vendor_name, 
        v.work_phone, v.email, vad.address_line1, vad.city, vad.state, vad.pin_code,
        org.organization_name, org.organization_logo, org.address as org_address, org.city as org_city, org.state as org_state, org.pincode as org_pin, org.phone as org_phone, org.email as org_email
        FROM debit_notes dn
        LEFT JOIN purchase_orders po ON dn.po_id = po.purchase_orders_id
        LEFT JOIN vendors_listing v ON dn.vendor_id = v.vendor_id
        LEFT JOIN vendors_addresses vad ON v.vendor_id = vad.vendor_id AND vad.address_type = 'billing'
        CROSS JOIN organizations org ON org.organization_id = dn.organization_id
        WHERE dn.debit_note_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dn_id);
$stmt->execute();
$headRes = $stmt->get_result();

if ($headRes->num_rows === 0) {
    die("Debit Note Not Found");
}

$dn = $headRes->fetch_assoc();

// Fetch Items
$iSql = "SELECT dni.*, il.item_name, u.unit_name, poi.rate as po_rate, h.hsn_code 
         FROM debit_note_items dni
         LEFT JOIN items_listing il ON dni.item_id = il.item_id
         LEFT JOIN units_listing u ON il.unit_id = u.unit_id
         LEFT JOIN hsn_listing h ON il.hsn_id = h.hsn_id
         LEFT JOIN purchase_order_items poi ON dni.po_item_id = poi.id
         WHERE dni.debit_note_id = ?";
$iStmt = $conn->prepare($iSql);
$iStmt->bind_param("i", $dn_id);
$iStmt->execute();
$items = $iStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Note Print - <?= htmlspecialchars($dn['debit_note_number']) ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px;}
        .container { max-width: 900px; margin: auto; padding: 20px; border: 1px solid #ddd; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .logo img { max-height: 60px; }
        .company-info h2 { margin: 0 0 5px; color: #444; }
        .company-info p { margin: 2px 0; color: #777; font-size: 11px; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; color: #dc3545; font-size: 24px; text-transform: uppercase; }
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
        .text-danger { color: #dc3545; }

        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 11px; color: #777; width: 100%; overflow: hidden; }
        
        .remarks-box { background: #f9f9f9; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .remarks-title { font-weight: bold; margin-bottom: 5px; font-size: 11px; text-transform: uppercase; color: #666;}
        
        @media print {
            body { padding: 0; margin: 0; background: white; }
            body * { visibility: hidden; } /* Hide everything by default */
            .container, .container * { visibility: visible; } /* Show only container and children */
            .container { 
                position: absolute; 
                left: 0; 
                top: 0; 
                max-width: 100% !important; 
                width: 100% !important;
                border: none; 
                padding: 0; 
                margin: 0;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right; max-width: 900px; margin: 0 auto 10px;">
        <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer; background: #dc3545; color: white; border: none; border-radius: 4px;">Print Debit Note</button>
    </div>
    <?= $autoPrintScript ?>

    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <!-- We assume base path logic or just use relative if simpler, but for views usually we need proper path handling or CDN -->
                <!-- Since this is a standalone view file inside views/billing, we need to handle image path carefully -->
                <?php 
                    $logoPath = '';
                    if(!empty($dn['organization_logo'])){
                         $logoPath = '../../uploads/' . ($_SESSION['organization_code'] ?? 'ORG') . '/organization_logo/' . $dn['organization_logo'];
                    }
                ?>
                <?php if(!empty($logoPath) && file_exists($logoPath)): ?>
                     <img src="<?= $logoPath ?>" alt="Logo" style="max-height: 60px;">
                <?php else: ?>
                     <h2><?= htmlspecialchars($dn['organization_name']) ?></h2>
                <?php endif; ?>
                
                <div style="margin-top: 10px;">
                    <p><strong><?= htmlspecialchars($dn['organization_name']) ?></strong></p>
                    <p>
                        <?= htmlspecialchars($dn['org_address']) ?><br>
                        <?= htmlspecialchars($dn['org_city']) ?>, <?= htmlspecialchars($dn['org_state']) ?> - <?= $dn['org_pin'] ?>
                    </p>
                    <p>Phone: <?= htmlspecialchars($dn['org_phone']) ?> | Email: <?= htmlspecialchars($dn['org_email']) ?></p>
                </div>
            </div>
            <div class="doc-title">
                <h1>DEBIT NOTE</h1>
                <p><?= htmlspecialchars($dn['debit_note_number']) ?></p>
            </div>
        </div>

        <!-- Info -->
        <div class="info-section">
            <div class="info-box">
                <h3>Vendor Details</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-val"><?= htmlspecialchars($dn['vendor_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-val"><?= htmlspecialchars($dn['work_phone']) ?></span>
                </div>
                <?php if($dn['address_line1']): ?>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-val">
                        <?= htmlspecialchars($dn['address_line1']) ?><br>
                        <?= htmlspecialchars($dn['city']) ?>, <?= htmlspecialchars($dn['state']) ?> - <?= $dn['pin_code'] ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="info-box">
                <h3>Reference Details</h3>
                <div class="info-row">
                    <span class="info-label">DN Date:</span>
                    <span class="info-val"><?= date('d M Y', strtotime($dn['debit_note_date'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PO Number:</span>
                    <span class="info-val fw-bold"><?= htmlspecialchars($dn['po_number']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PO Date:</span>
                    <span class="info-val"><?= date('d M Y', strtotime($dn['po_date'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 35%">Item Description</th>
                    <th style="width: 10%" class="text-center">HSN</th>
                    <th style="width: 10%" class="text-right">Rate</th>
                    <th style="width: 10%" class="text-center">Return Qty</th>
                    <th style="width: 10%" class="text-right">Total Value</th>
                    <th style="width: 20%">Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                $total_val = 0;
                while($item = $items->fetch_assoc()): 
                    $rate = floatval($item['po_rate']);
                    $qty = floatval($item['return_qty']);
                    $val = $qty * $rate;
                    $total_val += $val;
                ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td>
                        <span class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></span><br>
                        <span style="font-size: 10px; color: #666;"><?= $item['unit_name'] ?></span>
                    </td>
                    <td class="text-center"><?= $item['hsn_code'] ?? '-' ?></td>
                    <td class="text-right"><?= number_format($rate, 2) ?></td>
                    <td class="text-center fw-bold text-danger"><?= $qty ?></td>
                    <td class="text-right fw-bold"><?= number_format($val, 2) ?></td>
                    <td>
                        <?= $item['return_reason'] ?>
                        <?php if(!empty($item['remarks'])): ?>
                            <br><small class="text-muted"><i><?= htmlspecialchars($item['remarks']) ?></i></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <tr style="background-color: #f8f9fa;">
                    <td colspan="5" class="text-right fw-bold" style="border-top: 2px solid #ddd;">Grand Total</td>
                    <td class="text-right fw-bold text-danger" style="border-top: 2px solid #ddd;"><?= number_format($total_val, 2) ?></td>
                    <td style="border-top: 2px solid #ddd;"></td>
                </tr>
            </tbody>
        </table>

         <?php if(!empty($dn['remarks'])): ?>
            <div class="remarks-box">
                <div class="remarks-title">Internal Remarks/Notes</div>
                <?= nl2br(htmlspecialchars($dn['remarks'])) ?>
            </div>
        <?php endif; ?>

        <!-- Footer Signatures -->
        <div class="footer">
            <div style="float: left; width: 50%;">
                <p><strong>Terms & Conditions:</strong></p>
                <p>1. Goods once returned cannot be taken back unless approved.</p>
                <p>2. Values are calculated based on Purchase Order rates.</p>
            </div>
            <div style="float: right; width: 40%; text-align: right;">
                For <?= htmlspecialchars($dn['organization_name']) ?><br><br><br>
                <span style="display: inline-block; border-top: 1px solid #333; padding-top: 5px; width: 80%;">Authorized Signatory</span>
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <div class="text-center" style="margin-top: 30px; font-size: 10px; color: #999;">
            System Generated Debit Note | <?= date('d M Y H:i') ?>
        </div>

    </div>

</body>
</html>
