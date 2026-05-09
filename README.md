# M-Pesa PHP SDK Plus 🚀

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892bf.svg)](https://php.net)

A robust, modern, and high-performance PHP SDK for Safaricom's M-Pesa Daraja API. This SDK is designed for developers who need more than just simple API wrappers.

### 🌟 Why "Plus"?
Unlike standard M-Pesa libraries, this SDK features an **Advanced Callback Router**. It allows you to use a **single Callback URL** to manage transactions for **multiple websites or services** by intelligently forwarding the transaction data based on custom rules.

## 📋 System Requirements
- **PHP:** 7.4 or higher (8.1+ recommended)
- **Extensions:** `php-curl`, `php-json`, `php-pdo` (if using Database storage)
- **Safaricom Credentials:** Consumer Key, Consumer Secret, and LNM Passkey.
- **Server:** A publicly accessible URL (for Callbacks) with SSL (recommended).

## ✨ Key Features
- **Intelligent Callback Forwarding:** Proxy M-Pesa callbacks to different backend systems.
- **Lipa Na M-Pesa Online (STK Push):** Quick and easy integration for customer-initiated payments.
- **B2C & C2B Support:** Full support for business-to-customer and customer-to-business transactions.
- **Robust Logging:** Detailed logs for every transaction and callback attempt.
- **PSR-4 Compliant:** Easy integration with modern PHP frameworks (Laravel, Symfony, etc.).

## 🚀 Quick Start

### 1. Installation
Clone this repository or include it in your project:
```bash
git clone https://github.com/mutebesi/Safaricom-Mpesa-PHP.git
```

If using Composer:
```bash
composer require mutebesi/Safaricom-Mpesa-PHP
```

### 2. Configuration
Edit `config/config.php` with your Daraja credentials:
```php
'mpesa' => [
    'consumer_key'    => 'YOUR_CONSUMER_KEY',
    'consumer_secret' => 'YOUR_CONSUMER_SECRET',
    'shortcode'       => '174379',
    'passkey'         => 'bfb279...',
    'env'             => 'sandbox',
],
```

### 3. Setup Callback Routing
Define where you want your callbacks to go based on the `AccountReference` or `BillRefNumber`:
```php
'routing' => [
    'SITE_A' => 'https://website-a.com/api/mpesa-callback',
    'SITE_B' => 'https://website-b.com/payments/webhook',
],
```

### 4. Initiate a Payment (STK Push)
```php
use MpesaSdk\Mpesa;

$mpesa = new Mpesa($key, $secret, 'sandbox');
$result = $mpesa->stkPush(
    $shortcode, $passkey, '100', '2547XXXXXXXX', 'SITE_A_REF123', 'Payment Desc', $callbackUrl
);
```

## 💾 Database Integration (Optional)
If you want to save every transaction automatically, use the built-in `DatabaseStore` class:

1. **Configure DB in `config/config.php`**
2. **Initialize and Setup Table:**
```php
use MpesaSdk\DatabaseStore;

$db = new DatabaseStore($host, $dbname, $user, $pass);
$db->setupTable(); // Creates the 'mpesa_transactions' table automatically
```
3. **Save in your callback listener:**
```php
$db->saveStkCallback($mpesaData);
```

## 📄 Understanding the Data
M-Pesa sends transaction notifications as JSON. For a detailed breakdown of the fields (like `MpesaReceiptNumber` and `ResultCode`), see the **[Callback Data Structure Guide](file:///c:/Users/muteb/Desktop/repository/CALLBACK_SAMPLE.md)**.

## 🔄 How the Callback Proxy Works
When Safaricom sends a payment notification to your `callback.php`:
1. The **Callback Router** intercepts the JSON.
2. It looks at the `AccountReference` (e.g., `SITE_A_REF123`).
3. It finds the matching rule (e.g., `SITE_A`).
4. It **re-sends (proxies)** the exact JSON to the mapped target URL.
5. It logs the result of the forwarding attempt.

This solves the "Single Callback URL" limitation on the Daraja portal.

## 🛠 Advanced Usage
Check the `examples/` directory for detailed implementations of:
- STK Push Query
- Transaction Status
- Balance Inquiry

## 🔒 Security
- **SSL Verification:** Ensure you enable SSL verification in production.
- **IP Whitelisting:** It is recommended to whitelist Safaricom's IP addresses on your server.
- **Validation:** Always validate the `ResultCode` in your final target endpoints.

## 🤝 Contributing
Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
