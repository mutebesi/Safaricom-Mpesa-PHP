<?php

/**
 * Main M-Pesa Callback Listener
 * 
 * This file receives the POST request from Safaricom and forwards it
 * to the appropriate internal system based on routing rules.
 */

require_once __DIR__ . '/src/CallbackRouter.php';

use MpesaSdk\CallbackRouter;

// Load config
$config = require __DIR__ . '/config/config.php';

// Initialize Router
$router = new CallbackRouter($config['log_path']);

// Register Routing Rules from config
foreach ($config['routing'] as $pattern => $url) {
    $router->addRule($pattern, $url);
}

// Process incoming request
try {
    $router->process();
    
    // Always respond to Safaricom with a success to prevent retries
    header('Content-Type: application/json');
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Callback received and processed successfully'
    ]);
} catch (Exception $e) {
    // Log exception
    file_put_contents($config['log_path'], "[ERROR] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
