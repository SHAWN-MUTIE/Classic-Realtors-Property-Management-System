<?php
require_once("../config/db.php");
header("Content-Type: application/json");

// Acknowledge receipt immediately so Safaricom doesn't spam retries (Replay Defense Part 1)
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);

$stkCallbackResponse = file_get_contents('php://input');
$data = json_decode($stkCallbackResponse);

// Prevent errors if the file is hit directly with no payload
if (!$data || !isset($data->Body->stkCallback)) {
    exit();
}

$resultCode = $data->Body->stkCallback->ResultCode;
$checkoutRequestID = $data->Body->stkCallback->CheckoutRequestID;

if ($resultCode == 0) {
    // SUCCESS! Get the metadata
    $metadata = $data->Body->stkCallback->CallbackMetadata->Item;
    $amount = $metadata[0]->Value;
    $receipt = $metadata[1]->Value;
    $phone = $metadata[4]->Value;

    // --- REPLAY ATTACK DEFENSE ---
    // Check if this M-Pesa receipt already exists in our database
    $check = $conn->query("SELECT payment_id FROM payments WHERE mpesa_receipt = '$receipt'");
    if ($check->num_rows > 0) {
        exit(); // Stop execution if we already processed this exact receipt
    }

    // 1. Find the tenant by phone
    $tenant = $conn->query("SELECT tenant_id, balance FROM tenants WHERE phone LIKE '%$phone%' LIMIT 1")->fetch_assoc();
    
    if ($tenant) {
        $t_id = $tenant['tenant_id'];
        $new_balance = $tenant['balance'] - $amount;

        // 2. Update Tenant Balance
        $conn->query("UPDATE tenants SET balance = $new_balance WHERE tenant_id = $t_id");

        // 3. Record the Payment
        $conn->query("INSERT INTO payments (tenant_id, amount, mpesa_receipt, status) VALUES ($t_id, $amount, '$receipt', 'success')");
        
        // 4. Audit Log
        $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('System', $t_id, 'M-Pesa Payment Received: $receipt')");
    }
} else {
    // --- CANCELLED / FAILED TRANSACTION HANDLING ---
    $failReason = $conn->real_escape_string($data->Body->stkCallback->ResultDesc);
    
    // Safaricom doesn't return the phone number on failed transactions, 
    // so we log the failure to the audit_logs using the CheckoutRequestID for tracing.
    $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('System', 0, 'M-Pesa Failed/Cancelled [$checkoutRequestID]: $failReason')");
}
?>