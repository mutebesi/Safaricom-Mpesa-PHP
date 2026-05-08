<?php

/**
 * Example: Initiating an STK Push
 */

require_once __DIR__ . '/../src/Mpesa.php';

use MpesaSdk\Mpesa;

// Load config
$config = require __DIR__ . '/../config/config.php';
$mpesaConfig = $config['mpesa'];

// Initialize SDK
$mpesa = new Mpesa(
    $mpesaConfig['consumer_key'],
    $mpesaConfig['consumer_secret'],
    $mpesaConfig['env']
);

// Transaction Details
$amount = '1'; // Amount in KES
$phoneNumber = '2547XXXXXXXX'; // Customer Phone (format: 2547...)
$accountRef = 'SITE_A_INV123'; // Prefix with SITE_A for routing!
$desc = 'Payment for services';
$callbackUrl = 'https://your-domain.com/callback.php';

try {
    echo "Initiating STK Push for $phoneNumber..." . PHP_EOL;
    
    $result = $mpesa->stkPush(
        $mpesaConfig['shortcode'],
        $mpesaConfig['passkey'],
        $amount,
        $phoneNumber,
        $accountRef,
        $desc,
        $callbackUrl
    );

    echo "Response Status: " . $result['status'] . PHP_EOL;
    echo "Response Body: " . json_encode($result['response'], JSON_PRETTY_PRINT) . PHP_EOL;

    if ($result['status'] === 200 && $result['response']['ResponseCode'] === '0') {
        echo "STK Push sent successfully! CheckoutRequestID: " . $result['response']['CheckoutRequestID'] . PHP_EOL;
    } else {
        echo "Failed to initiate STK Push." . PHP_EOL;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
