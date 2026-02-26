<?php
// ============================================================
// CRON JOB: Purchase Order Payment Reminder
// Should be scheduled to run once daily (e.g., at 00:01 AM)
//   Windows Task Scheduler:
//     Program : C:\xampp\php\php.exe
//     Arguments: "D:\xampp\htdocs\software_websites_2026\COMPLETE SOFTWARE\Samadhan_ERP_2026\cron\po_payment_reminder.php"
//
//   Linux/Mac (crontab -e):
//     0 7 * * * php /path/to/cron/po_payment_reminder.php
//
// Logic:
//   - Find all purchase_orders where payment_date is within 20, 10, 5, 3, 1, or 0 days
//   - Status must NOT be 'cancelled' or 'paid'
//   - Insert a notification row into `notifications` for every active
//     SUPER ADMIN AND ADMIN of that organization
//   - Skip if a notification with same po_id + reminder label already exists
//     today (prevents duplicate runs on the same day)
// ============================================================

// --- Load DB Connection ---
$configPath = __DIR__ . '/../config/conn.php';
if (!file_exists($configPath)) {
    die("Configuration file not found.\n");
}
require_once $configPath;

// --- Log File ---
$logFile = __DIR__ . '/po_payment_reminder.log';
if (!file_exists($logFile)) {
    file_put_contents($logFile, '');
}

function writeLog(string $message): void {
    global $logFile;
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
    echo $entry; // visible when run from CLI
}

writeLog('======================================================');
writeLog('Starting PO Payment Reminder Cron Job');

$today          = date('Y-m-d');
// The specific dates are handled dynamically in the SQL query using DATEDIFF
$notificationsInserted = 0;
$ordersProcessed = 0;

try {

    // -------------------------------------------------------
    // 1. Fetch POs whose payment_date falls 20, 10, 5, 3, 1, or 0 days from today
    //    and are still in actionable status
    // -------------------------------------------------------
    $sql = "
        SELECT 
            po.purchase_orders_id,
            po.organization_id,
            po.po_number,
            po.payment_date,
            po.payment_terms,
            po.total_amount,
            po.status,
            v.display_name AS vendor_name,
            DATEDIFF(po.payment_date, CURDATE()) AS days_left
        FROM purchase_orders po
        LEFT JOIN vendors_listing v ON po.vendor_id = v.vendor_id
        WHERE po.payment_date IS NOT NULL
          AND po.status NOT IN ('cancelled', 'paid', 'draft')
          AND DATEDIFF(po.payment_date, CURDATE()) IN (20, 10, 5, 3, 1, 0)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        writeLog("No POs matching the reminder intervals (20, 10, 5, 3, 1, 0 days). Nothing to do.");
        $stmt->close();
        $conn->close();
        exit(0);
    }

    writeLog("Found {$result->num_rows} PO(s) requiring a reminder today.");

    // -------------------------------------------------------
    // 2. For each PO, notify all active employees of that org
    // -------------------------------------------------------
    while ($po = $result->fetch_assoc()) {
        $poId       = (int) $po['purchase_orders_id'];
        $orgId      = (int) $po['organization_id'];
        $poNumber   = $po['po_number'];
        $vendorName = $po['vendor_name'] ?? 'Vendor';
        $payDate    = date('d M Y', strtotime($po['payment_date']));
        $amount     = number_format((float)$po['total_amount'], 2);
        $poStatus   = ucfirst($po['status']);
        $daysLeft   = (int) $po['days_left'];

        writeLog("Processing PO #$poNumber (ID: $poId) | Org: $orgId | Vendor: $vendorName | Due: $payDate ($daysLeft days left) | Amount: ₹$amount");

        // — Dedup check: was a reminder notification for this PO already sent today?
        $dupCheck = $conn->prepare("
            SELECT id FROM notifications
            WHERE url LIKE ?
              AND title LIKE ?
              AND DATE(created_at) = ?
            LIMIT 1
        ");
        $urlPattern = "%purchase_orders%$poId%";
        $titlePattern = "%$poNumber%";
        $dupCheck->bind_param('sss', $urlPattern, $titlePattern, $today);
        $dupCheck->execute();
        $dupResult = $dupCheck->get_result();
        if ($dupResult->num_rows > 0) {
            writeLog("  → Already notified today for PO #$poNumber. Skipping.");
            $dupCheck->close();
            continue;
        }
        $dupCheck->close();

        // — Get active SUPER ADMIN AND ADMIN employees of this organization
        $empSql  = "
            SELECT e.employee_id 
            FROM employees e
            JOIN roles_listing r ON e.role_id = r.role_id
            WHERE e.organization_id = ? 
              AND e.is_active = 1 
              AND r.role_name IN ('SUPER ADMIN', 'ADMIN')
        ";
        $empStmt = $conn->prepare($empSql);
        $empStmt->bind_param('i', $orgId);
        $empStmt->execute();
        $empResult = $empStmt->get_result();

        if ($empResult->num_rows === 0) {
            writeLog("  → No active employees found for org $orgId. Skipping.");
            $empStmt->close();
            continue;
        }

        // — Prepare notification insert
        if ($daysLeft === 0) {
            $notifTitle = "⏰ Payment Due TODAY –  #$poNumber";
        } elseif ($daysLeft === 1) {
            $notifTitle = "⏰ Payment Due Tomorrow – #$poNumber";
        } else {
            $notifTitle = "⏰ Payment Due in $daysLeft Days – #$poNumber";
        }
        $notifMessage = "Purchase Order <strong>#$poNumber</strong> from vendor <strong>$vendorName</strong> "
                      . "has a payment due on <strong>$payDate</strong>. "
                      . "Total Amount: <strong>₹$amount</strong>. "
                      . "Current Status: <strong>$poStatus</strong>. "
                      . "Please ensure timely payment.";
        $notifType    = 'reminder';
        $notifIcon    = 'ti ti-calendar-dollar';
        $notifUrl     = "/view_purchase_order?id=$poId";
        $notifPriority = 'high';

        $insertSql = "
            INSERT INTO notifications 
                (user_id, title, message, type, icon, url, is_read, is_deleted, priority, created_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, 0, 0, ?, NOW())
        ";
        $insertStmt = $conn->prepare($insertSql);

        $poNotifCount = 0;
        while ($emp = $empResult->fetch_assoc()) {
            $userId = (int) $emp['employee_id'];
            $insertStmt->bind_param(
                'issssss',
                $userId,
                $notifTitle,
                $notifMessage,
                $notifType,
                $notifIcon,
                $notifUrl,
                $notifPriority
            );
            if ($insertStmt->execute()) {
                $poNotifCount++;
                $notificationsInserted++;
            } else {
                writeLog("  → Failed to insert notification for employee $userId: " . $insertStmt->error);
            }
        }

        $insertStmt->close();
        $empStmt->close();

        writeLog("  → Inserted $poNotifCount notification(s) for PO #$poNumber");
        $ordersProcessed++;
    }

    $stmt->close();

    writeLog("------------------------------------------------------");
    writeLog("Job Completed Successfully.");
    writeLog("Orders Processed   : $ordersProcessed");
    writeLog("Notifications Sent : $notificationsInserted");
    writeLog("======================================================");

} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
}

$conn->close();
?>
