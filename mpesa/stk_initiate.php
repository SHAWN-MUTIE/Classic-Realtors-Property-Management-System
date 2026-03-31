<?php
session_start();
header('Content-Type: application/json'); // Tell JS we are sending JSON back

// 1. Credentials
// 2. Daraja Credentials
$consumerKey = 'YOUR_DARAJA_CONSUMER_KEY'; 
$consumerSecret = 'YOUR_DARAJA_CONSUMER_SECRET';
$BusinessShortCode = 'YOUR_PAYBILL_OR_TILL_NUMBER'; 
$Passkey = 'YOUR_DARAJA_PASSKEY';
$callback_url = 'https://your-production-domain.com/crpms/mpesa/callback.php';

// YOUR NGROK URL
$callback_url = ' use ngrok url provided';

// 2. Safely get variables from the Dashboard
if (!isset($_SESSION['phone']) || !isset($_POST['amount'])) {
    echo json_encode(["ResponseCode" => "1", "CustomerMessage" => "Missing phone or amount."]);
    exit();
}

$phone = $_SESSION['phone']; 
$amount = (int)$_POST['amount'];

// 3. GET TOKEN
$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic '.base64_encode($consumerKey.':'.$consumerSecret)]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$response = json_decode(curl_exec($curl));

if (!$response || !isset($response->access_token)) {
    echo json_encode(["ResponseCode" => "1", "CustomerMessage" => "Failed to authenticate with Safaricom."]);
    exit();
}
$access_token = $response->access_token;

// 4. STK PUSH
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
    'TransactionDesc' => 'Tenant Rent Payment'
];

$curl = curl_init($stk_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json','Authorization:Bearer '.$access_token]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$stk_response = curl_exec($curl);

// Send the exact response back to the Dashboard JS
echo $stk_response;
?>