<?php
require_once('accessToken.php');

// Define Variables
$BusinessShortCode = '174379'; // Default Sandbox Shortcode
$Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode.$Passkey.$Timestamp);

$Amount = '1'; // Test with 1 KES
$PartyA = '2547XXXXXXXX'; // The tenant's phone
$CallBackURL = 'https://your-public-url.com/mpesa/callback.php';

$access_token = get_access_token();

$stk_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/query'; // Sandbox URL

// ... CURL logic to send the JSON payload to Safaricom ...
?>