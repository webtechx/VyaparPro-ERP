<?php
// CRON JOB: Daily Loyalty Points Expiry
// Should be scheduled to run once daily (e.g., at 00:01 AM)

// Determine path to config
$configPath = __DIR__ . '/../config/conn.php';
if (!file_exists($configPath)) {
    // Fallback if script is in controller/cron or similar
    $configPath = __DIR__ . '/../../config/conn.php';
}

if (!file_exists($configPath)) {
    die("Configuration file not found.");
}

require_once $configPath;

$logFile = __DIR__ . '/daily_points_expiry.log';

if (!file_exists($logFile)) {
    file_put_contents($logFile, "");
}

function writeLog($message) {
    global $logFile;
    $entry = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}

writeLog("Starting Loyalty Points Expiry Job");

try {
    $conn->begin_transaction();

    // 1. Identify Expired Points
    // Group by customer to create single transaction entry per customer for all expiring batches
    $today = date('Y-m-d');
    
    $sql = "SELECT organization_id, customer_id, SUM(points_remaining) as total_expired, GROUP_CONCAT(loyalty_point_id) as batch_ids
            FROM loyalty_points_earned
            WHERE valid_till < '$today' 
            AND valid_till IS NOT NULL 
            AND points_remaining > 0
            GROUP BY organization_id, customer_id";
    
    $result = $conn->query($sql);
    
    $processed_customers = 0;
    $total_points_expired = 0;
    $daily_expiry_data = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $org_id = $row['organization_id'];
            $cust_id = $row['customer_id'];
            $expired_points = floatval($row['total_expired']);
            $batch_ids = $row['batch_ids']; // Comma separated IDs

            if ($expired_points <= 0) continue;

            writeLog("Processing Customer ID: $cust_id -> Expiring: $expired_points points (Batches: $batch_ids)");

            // A. Update Customer Balance
            // Fetch current balance first to be safe
            $balSql = "SELECT loyalty_point_balance FROM customers_listing WHERE customer_id = $cust_id FOR UPDATE";
            $balRes = $conn->query($balSql);
            $current_balance = 0;
            if ($balRes && $balRes->num_rows > 0) {
                $current_balance = floatval($balRes->fetch_assoc()['loyalty_point_balance']);
            }

            // Calculate new balance
            // Ensure we don't go below zero (though theoretically shouldn't if logic is sound)
            $new_balance = max(0, $current_balance - $expired_points);

            $updCust = $conn->prepare("UPDATE customers_listing SET loyalty_point_balance = ? WHERE customer_id = ?");
            $updCust->bind_param("di", $new_balance, $cust_id);
            $updCust->execute();
            $updCust->close();

            // B. Zero out the expired batches
            // Use query directly with IN clause
            $updBatches = $conn->query("UPDATE loyalty_points_earned SET points_remaining = 0 WHERE loyalty_point_id IN ($batch_ids)");
            
            // C. Log Transaction
            // Note: invoice_id is NULL for expiry aggregation
            // Added expiry_date to match user request and schema
            $transSql = "INSERT INTO loyalty_point_transactions 
                        (organization_id, customer_id, invoice_id, transaction_type, points, balance_after_transaction, expiry_date, note, created_at) 
                        VALUES (?, ?, NULL, 'EXPIRED', ?, ?, ?, ?, NOW())";
            
            $note = "Points Expired";
            $ts = $conn->prepare($transSql);
            // Types: i (org), i (cust), d (points), d (bal), s (expiry), s (note)
            $ts->bind_param("iiddss", $org_id, $cust_id, $expired_points, $new_balance, $today, $note);
            $ts->execute();
            $ts->close();

            $processed_customers++;
            $total_points_expired += $expired_points;

            $daily_expiry_data[] = [
                'customer_id' => $cust_id,
                'expired_points' => $expired_points
            ];
        }
    }

    if (!empty($daily_expiry_data)) {
        writeLog("Daily Expiry Details: " . print_r($daily_expiry_data, true));
    }

    $conn->commit();
    writeLog("Job Completed Successfully.");
    writeLog("Customers Processed: $processed_customers");
    writeLog("Total Points Expired: $total_points_expired");

} catch (Exception $e) {
    $conn->rollback();
    writeLog("Job Failed: " . $e->getMessage());
}

$conn->close();
?>
