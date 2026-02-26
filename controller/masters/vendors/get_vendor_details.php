<?php
require_once '../../../config/conn.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $vendor_id = intval($_GET['id']);
    
    $response = [];

    // 1. Fetch Main Vendor Data
    $sql = "SELECT * FROM vendors_listing WHERE vendor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vendor = $result->fetch_assoc();
    
    if (!$vendor) {
        echo json_encode(['error' => 'Vendor not found']);
        exit;
    }
    $response['vendor'] = $vendor;

    // 2. Fetch Addresses
    $addrSql = "SELECT * FROM vendors_addresses WHERE vendor_id = ?";
    $stmt = $conn->prepare($addrSql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $addresses = [];
    while($row = $result->fetch_assoc()) {
        $addresses[$row['address_type']] = $row;
    }
    $response['addresses'] = $addresses;

    // 3. Fetch Bank Accounts (Taking the first/default one for now as per form design)
    $bankSql = "SELECT * FROM vendors_bank_accounts WHERE vendor_id = ? LIMIT 1";
    $stmt = $conn->prepare($bankSql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['bank_account'] = $result->fetch_assoc();

    // 4. Fetch Contacts
    $contactSql = "SELECT * FROM vendors_contacts WHERE vendor_id = ?";
    $stmt = $conn->prepare($contactSql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contacts = [];
    while($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
    $response['contacts'] = $contacts;



    // 7. Fetch Remarks
    $remSql = "SELECT * FROM vendors_remarks WHERE vendor_id = ? LIMIT 1";
    $stmt = $conn->prepare($remSql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $remark = $result->fetch_assoc();
    $response['remarks'] = $remark ? $remark['remarks'] : '';

    echo json_encode($response);

} else {
    echo json_encode(['error' => 'No ID provided']);
}
?>
