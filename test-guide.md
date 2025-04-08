# Testing Guide for Payment Recovery System

This guide will help you test your Payment Recovery System on XAMPP with both the NMI API and communication services.

## 1. Prerequisites

Make sure you have the following:

- XAMPP installed and running (Apache + MySQL)
- Your database is set up
- Twilio account with SID, Token, and phone number (for SMS)

## 2. Configuration 

### Update the API Configuration

Edit `config/api.php` and add your actual credentials:

```php
// NMI API Configuration
define('NMI_API_URL', 'https://secure.networkmerchants.com/api/query.php');
define('NMI_API_KEY', 'YOUR_NMI_API_KEY_HERE');

// Twilio Configuration
define('TWILIO_SID', 'YOUR_TWILIO_SID_HERE');
define('TWILIO_TOKEN', 'YOUR_TWILIO_TOKEN_HERE');
define('TWILIO_PHONE', 'YOUR_TWILIO_PHONE_HERE');
```

### Update Your Test Contact Information

Edit `app/services/CommunicationService.php` to add your email and phone:

```php
// Test contact information - replace with your own for testing
$this->testEmail = "your-email@example.com"; // REPLACE WITH YOUR EMAIL
$this->testPhone = "+1234567890"; // REPLACE WITH YOUR PHONE NUMBER
```

## 3. Running the Tests

### Testing NMI API Integration

Run the NMI API test script:

```
php test-nmi.php
```

This will:
- Fetch failed transactions from the past 7 days
- Fetch a specific transaction (if transaction ID is valid)
- Display the results

If successful, you should see transaction data from your NMI account.

### Testing Communication Services

Run the communication test script:

```
php test-communication.php
```

This will:
- Send a test email to your configured test email address
- Send a test SMS to your configured test phone number

If your XAMPP isn't configured for email, the system will save the email content to the `/logs` directory as an HTML file.

## 4. Testing the Full System

### Import Transactions from NMI

Visit the following URL in your browser:
```
http://localhost/payment-recovery/public/index.php?route=fetch-from-nmi
```

This will import failed transactions from NMI into your database.

### View Transactions

Visit:
```
http://localhost/payment-recovery/public/index.php?route=failed-transactions
```

You should see the transactions you imported.

### Send a Manual Reminder

To send a reminder for a specific transaction:
```
http://localhost/payment-recovery/public/index.php?route=send-reminder&id=X&channel=email
```
(Replace X with an actual transaction ID from your database)

### Process Scheduled Reminders

To process all scheduled reminders:
```
http://localhost/payment-recovery/public/index.php?route=process-reminders
```

## Troubleshooting

### Email Issues

If emails aren't being sent:

1. Check the `/logs` directory for saved email files
2. Configure XAMPP's php.ini mail settings:
   - Open `xampp/php/php.ini`
   - Find the [mail function] section
   - Update SMTP settings to use a valid mail server

### SMS Issues

If SMS aren't being sent:

1. Verify your Twilio credentials
2. Make sure your Twilio account is active and funded
3. Check that your Twilio phone number can send SMS
4. Check your logs for detailed error messages

### NMI API Issues

If NMI API calls fail:

1. Verify your API key
2. Check your internet connection
3. Look at error logs for detailed error messages

## Next Steps

Once testing is complete, you can:

1. Set `$testMode = false` in the CommunicationService to send to actual customers
2. Update your email configuration to use a production mail server
3. Deploy to a production environment with proper mail server configuration
