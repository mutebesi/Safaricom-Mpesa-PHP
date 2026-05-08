<?php

namespace MpesaSdk;

use PDO;
use Exception;

/**
 * Helper class for saving M-Pesa transaction data to a MySQL database.
 */
class DatabaseStore
{
    private PDO $pdo;
    private string $tableName;

    /**
     * @param string $host Database host
     * @param string $db Database name
     * @param string $user Username
     * @param string $pass Password
     * @param string $tableName Table to store transactions in
     */
    public function __construct(string $host, string $db, string $user, string $pass, string $tableName = 'mpesa_transactions')
    {
        $this->tableName = $tableName;
        
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERR_MODE => PDO::ERR_MODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::ATTR_OBJ
            ]);
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Create the transactions table if it doesn't exist
     */
    public function setupTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_request_id` VARCHAR(50),
            `checkout_request_id` VARCHAR(50),
            `result_code` INT,
            `result_desc` TEXT,
            `amount` DECIMAL(10,2),
            `mpesa_receipt_number` VARCHAR(50),
            `transaction_date` DATETIME,
            `phone_number` VARCHAR(20),
            `raw_json` JSON,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->pdo->exec($sql);
    }

    /**
     * Save an STK Push callback to the database
     * 
     * @param array $data The decoded M-Pesa callback JSON
     */
    public function saveStkCallback(array $data): bool
    {
        $stk = $data['Body']['stkCallback'];
        
        $merchantId = $stk['MerchantRequestID'];
        $checkoutId = $stk['CheckoutRequestID'];
        $resCode = $stk['ResultCode'];
        $resDesc = $stk['ResultDesc'];
        
        $amount = null;
        $receipt = null;
        $date = null;
        $phone = null;

        // If the transaction was successful (ResultCode 0), extract metadata
        if ($resCode === 0 && isset($stk['CallbackMetadata']['Item'])) {
            foreach ($stk['CallbackMetadata']['Item'] as $item) {
                switch ($item['Name']) {
                    case 'Amount': $amount = $item['Value']; break;
                    case 'MpesaReceiptNumber': $receipt = $item['Value']; break;
                    case 'TransactionDate': 
                        // M-Pesa date format: 20260508224056 -> Y-m-d H:i:s
                        $rawDate = (string)$item['Value'];
                        $date = date("Y-m-d H:i:s", strtotime($rawDate));
                        break;
                    case 'PhoneNumber': $phone = $item['Value']; break;
                }
            }
        }

        $sql = "INSERT INTO `{$this->tableName}` 
                (merchant_request_id, checkout_request_id, result_code, result_desc, amount, mpesa_receipt_number, transaction_date, phone_number, raw_json) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $merchantId, 
            $checkoutId, 
            $resCode, 
            $resDesc, 
            $amount, 
            $receipt, 
            $date, 
            $phone, 
            json_encode($data)
        ]);
    }
}
