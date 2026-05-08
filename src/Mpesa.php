<?php

namespace MpesaSdk;

use Exception;

/**
 * M-Pesa SDK Core Class
 * 
 * Handles authentication and main API requests to Safaricom's Daraja API.
 */
class Mpesa
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $env; // 'sandbox' or 'production'
    private string $baseUrl;

    /**
     * Initialize the M-Pesa SDK
     * 
     * @param string $consumerKey Your Daraja App Consumer Key
     * @param string $consumerSecret Your Daraja App Consumer Secret
     * @param string $env The environment to use ('sandbox' or 'production')
     */
    public function __construct(string $consumerKey, string $consumerSecret, string $env = 'sandbox')
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->env = $env;
        
        // Safaricom API base URLs depend on the environment
        $this->baseUrl = ($env === 'production') 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Generate an Access Token using OAuth2 client_credentials
     * 
     * Note: In production, you should cache this token for the duration of its 
     * validity (usually 1 hour) to avoid hitting the rate limit.
     * 
     * @return string The valid access token
     * @throws Exception If authentication fails
     */
    public function generateToken(): string
    {
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->consumerKey . ':' . $this->consumerSecret)
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        // Disable peer verification in sandbox to avoid certificate issues
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, ($this->env === 'production'));
        
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status !== 200) {
            throw new Exception("Authentication failed. M-Pesa said: $response (Status: $status)");
        }

        $data = json_decode($response);
        return $data->access_token;
    }

    /**
     * Lipa Na M-Pesa Online (STK Push)
     * 
     * This method triggers a pop-up on the customer's phone to enter their PIN.
     * 
     * @param string $businessShortCode The Paybill or Till number
     * @param string $passKey The LNM Passkey from Daraja
     * @param string $amount The amount to charge (in KES)
     * @param string $phoneNumber The phone number (format: 2547...)
     * @param string $accountReference A unique identifier for the transaction
     * @param string $transactionDesc A short description of the payment
     * @param string $callbackUrl The URL where M-Pesa will send the payment notification
     * @return array The API response decoded from JSON
     */
    public function stkPush(
        string $businessShortCode,
        string $passKey,
        string $amount,
        string $phoneNumber,
        string $accountReference,
        string $transactionDesc,
        string $callbackUrl
    ): array {
        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
        $timestamp = date('YmdHis');
        
        // Password is a base64 encoded string of Shortcode + Passkey + Timestamp
        $password = base64_encode($businessShortCode . $passKey . $timestamp);

        $body = [
            'BusinessShortCode' => $businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline', // Or 'CustomerBuyGoodsOnline'
            'Amount' => (int)$amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $businessShortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        ];

        return $this->makeRequest($url, $body);
    }

    /**
     * STK Push Query
     */
    public function stkQuery(
        string $businessShortCode,
        string $passKey,
        string $checkoutRequestId
    ): array {
        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
        $timestamp = date('YmdHis');
        $password = base64_encode($businessShortCode . $passKey . $timestamp);

        $body = [
            'BusinessShortCode' => $businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];

        return $this->makeRequest($url, $body);
    }

    /**
     * Register C2B URLs (Confirmation and Validation)
     * 
     * This is required once for C2B transactions to tell M-Pesa where to send payment notifications.
     */
    public function registerC2BUrls(string $shortCode, string $confirmationUrl, string $validationUrl): array
    {
        $url = $this->baseUrl . '/mpesa/c2b/v1/registerurl';
        
        $body = [
            'ShortCode' => $shortCode,
            'ResponseType' => 'Completed', // Or 'Cancelled'
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl
        ];

        return $this->makeRequest($url, $body);
    }

    /**
     * Internal HTTP Request Helper using cURL
     * 
     * @param string $url Target endpoint
     * @param array $body JSON body to send
     * @return array Result with 'status' (HTTP code) and 'response' (decoded JSON)
     */
    private function makeRequest(string $url, array $body): array
    {
        $token = $this->generateToken();
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, ($this->env === 'production'));
        
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'status' => $status,
            'response' => json_decode($response, true)
        ];
    }
}
