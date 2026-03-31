<?php
session_start();
require_once('../config/db.php');

// Ensure user is logged in
if (!isset($_SESSION['tenant_id']) || !isset($_POST['amount'])) {
    die("Error: Missing session or amount.");
}

$amount = (int)$_POST['amount'];
$phone = $_SESSION['phone']; // Your 254720210903 number
$tenant_id = $_SESSION['tenant_id'];

// 1. Log to Database as 'Pending'
$stmt = $conn->prepare("INSERT INTO payments (tenant_id, amount, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("id", $tenant_id, $amount);
$stmt->execute();

// 2. Daraja Credentials
$consumerKey = 'ZATU048GMjIx1RKvsiym4UsCZiAk4SN2uDAANn2A1mywmLGD'; 
$consumerSecret = 'DmMryIxlDjvNeg8BnYpQXhGKfG3EfDTQK3YRr0dwqSKYTYrs1lu7hBA8VVYqIxbx';
$BusinessShortCode = '174379'; 
$Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$callback_url = 'https://joycelyn-nondiathermanous-gauge.ngrok-free.dev/crpms/mpesa/callback.php';

// 3. Authenticate
$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic '.base64_encode($consumerKey.':'.$consumerSecret)]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$response = json_decode(curl_exec($curl));

if (!isset($response->access_token)) {
    die("<h3>Safaricom Authentication Failed. Check your internet or keys.</h3>");
}
$access_token = $response->access_token;

// 4. Send STK Push
$stk_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$timestamp = date('YmdHis');
$password = base64_encode($BusinessShortCode.$Passkey.$timestamp);

$curl_post_data = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callback_url,
    'AccountReference' => 'CRPMS_RENT',
    'TransactionDesc' => 'Rent Payment'
];

$curl = curl_init($stk_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json','Authorization:Bearer '.$access_token]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$stk_response = curl_exec($curl);
$data = json_decode($stk_response);

// 5. Handle the result
if (isset($data->ResponseCode) && $data->ResponseCode == "0") {
    // Success: Alert the user and send them back to the dashboard
    echo "<script>
            alert('✅ M-Pesa Prompt Sent! Please check your phone and enter your PIN.');
            window.location.href='../tenant/dashboard.php';
          </script>";
} else {
    // Failure: Show exact error
    echo "<h3>Safaricom Error:</h3>";
    echo "<p>" . $stk_response . "</p>";
    echo "<a href='../tenant/dashboard.php'>Go Back</a>";
}
?>