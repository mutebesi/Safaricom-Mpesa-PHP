<?php

namespace MpesaSdk;

use Exception;

/**
 * M-Pesa Callback Router
 * 
 * Handles incoming M-Pesa callbacks and forwards them to specific endpoints
 * based on the account reference or other transaction metadata.
 */
class CallbackRouter
{
    private array $rules = [];
    private string $logFile;

    public function __construct(string $logFile = 'logs/callback.log')
    {
        $this->logFile = $logFile;
    }

    /**
     * Add a routing rule
     * 
     * @param string $pattern String to match in AccountReference
     * @param string $targetUrl URL to forward the JSON to
     */
    public function addRule(string $pattern, string $targetUrl): void
    {
        $this->rules[$pattern] = $targetUrl;
    }

    /**
     * Process the incoming callback
     * 
     * This method reads the JSON from M-Pesa, logs it, identifies the 
     * target website based on the transaction data, and forwards the JSON.
     */
    public function process(): void
    {
        // 1. Get the raw JSON POST data from the request body
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Basic validation: ensure we actually got some JSON
        if (!$data) {
            $this->log("ERROR: Received empty or invalid JSON payload.");
            return;
        }

        $this->log("INFO: Received M-Pesa callback payload.");

        // 2. Identify the target based on the Account Reference
        // We look for patterns in the reference to know which site this belongs to.
        $accountReference = $this->extractAccountReference($data);
        $targetUrl = $this->determineTarget($accountReference);

        if ($targetUrl) {
            $this->log("INFO: Forwarding transaction [$accountReference] to: $targetUrl");
            $this->forward($targetUrl, $json);
        } else {
            // If no rule matches, we just log it and do nothing.
            // You can add a default fallback URL here if needed.
            $this->log("WARNING: No routing rule matches for reference: $accountReference");
        }
    }

    /**
     * Extract AccountReference from different M-Pesa callback structures
     * 
     * M-Pesa sends different formats for STK Push vs C2B. This method
     * standardizes how we find the "Key" to route by.
     * 
     * @param array $data The decoded JSON data
     * @return string The extracted reference or 'UNKNOWN'
     */
    private function extractAccountReference(array $data): string
    {
        // Case A: STK Push Callback Structure
        if (isset($data['Body']['stkCallback']['CheckoutRequestID'])) {
            // Note: STK Callbacks don't always contain the 'AccountReference' 
            // field that you passed during initiation. Instead, we use the 
            // CheckoutRequestID, or you can check for specific metadata items.
            return $data['Body']['stkCallback']['CheckoutRequestID'];
        }

        // Case B: C2B (Customer to Business) Callback Structure
        if (isset($data['BillRefNumber'])) {
            // BillRefNumber is what the customer enters as the Account Number
            return $data['BillRefNumber'];
        }

        return 'UNKNOWN';
    }

    /**
     * Match the extracted reference against our registered routing rules
     * 
     * @param string $ref The reference string to search for
     * @return string|null The target URL if found, otherwise null
     */
    private function determineTarget(string $ref): ?string
    {
        foreach ($this->rules as $pattern => $url) {
            // We use strpos for flexible matching (e.g., 'SITE_A' matches 'SITE_A_INV123')
            if (strpos($ref, $pattern) !== false) {
                return $url;
            }
        }
        return null;
    }

    /**
     * Re-send (Proxy) the exact JSON payload to another endpoint
     * 
     * @param string $url The target URL to forward to
     * @param string $json The raw JSON string received from M-Pesa
     */
    private function forward(string $url, string $json): void
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15); // Set a reasonable timeout
        
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            $this->log("ERROR: Forwarding failed. cURL Error: $error");
        } else {
            $this->log("DEBUG: Forwarding complete. Target responded with Status: $status");
        }
    }

    /**
     * Simple file logger
     */
    private function log(string $message): void
    {
        $time = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$time] $message" . PHP_EOL, FILE_APPEND);
    }
}
