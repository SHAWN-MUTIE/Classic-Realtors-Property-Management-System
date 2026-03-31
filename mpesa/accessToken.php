<?php
function get_access_token() {
    $consumerKey = 'YOUR_CONSUMER_KEY'; 
    $consumerSecret = 'YOUR_CONSUMER_SECRET';
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    $credentials = base64_encode($consumerKey.':'.$consumerSecret);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
    $curl_response = curl_exec($curl);
    return json_decode($curl_response)->access_token;
}
?>