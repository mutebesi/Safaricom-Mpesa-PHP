<?php

/**
 * M-Pesa SDK Configuration
 */

return [
    // API Credentials (Get these from Daraja Portal)
    'mpesa' => [
        'consumer_key'    => 'YOUR_CONSUMER_KEY',
        'consumer_secret' => 'YOUR_CONSUMER_SECRET',
        'shortcode'       => '174379', // For Sandbox use 174379
        'passkey'         => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
        'env'             => 'sandbox', // 'sandbox' or 'production'
    ],
    
    // Database Settings (Optional)
    'database' => [
        'host' => 'localhost',
        'name' => 'your_database',
        'user' => 'your_username',
        'pass' => 'your_password',
        'table' => 'mpesa_transactions'
    ],

    // Callback Forwarding Rules
    // Key: Pattern to match in BillRefNumber (C2B) or CheckoutRequestID (STK)
    // Value: Target URL to forward the JSON to
    'routing' => [
        'SITE_A' => 'https://website-a.com/mpesa-listener.php',
        'SITE_B' => 'https://website-b.com/payments/callback.php',
    ],

    // Logging
    'log_path' => __DIR__ . '/../logs/mpesa.log'
];
