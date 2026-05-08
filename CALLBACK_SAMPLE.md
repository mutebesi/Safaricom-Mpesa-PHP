# M-Pesa Callback Data Structure

When a transaction is completed (or cancelled) on the customer's phone, Safaricom sends a JSON payload to your callback URL. Below is the structure you can expect.

## Successful STK Push (ResultCode 0)

```json
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "29115-3462713-1",
      "CheckoutRequestID": "ws_CO_191220191020363925",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          { "Name": "Amount", "Value": 1.00 },
          { "Name": "MpesaReceiptNumber", "Value": "NLJ7RT61SV" },
          { "Name": "TransactionDate", "Value": 20260508224511 },
          { "Name": "PhoneNumber", "Value": 254712345678 }
        ]
      }
    }
  }
}
```

### Key Fields:
- **ResultCode**: `0` means success. Anything else means failure (e.g., `1032` for user cancelled).
- **CheckoutRequestID**: The unique ID returned when you initiated the push. Use this to match the response to your request.
- **MpesaReceiptNumber**: The official M-Pesa transaction code (e.g., `NLJ7RT61SV`).
- **Amount**: The actual amount paid by the customer.

---

## Cancelled/Failed Transaction

```json
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "29115-3462713-1",
      "CheckoutRequestID": "ws_CO_191220191020363925",
      "ResultCode": 1032,
      "ResultDesc": "Request cancelled by user."
    }
  }
}
```

### Common Result Codes:
- **0**: Success
- **1**: Insufficient Funds
- **1032**: Cancelled by User
- **2001**: Invalid Authorization Code
- **1037**: DS Timeout (User took too long to enter PIN)
