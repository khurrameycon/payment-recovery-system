<?php
// NMI API Configuration

// NMI API Configuration (keep your existing config)
define('NMI_API_URL', 'https://secure.networkmerchants.com/api/query.php');
define('NMI_API_KEY', 'c3wDyHQ5qPQ3e9b2EMcbdFX2X6dQ3WXs'); // Replace with your actual test key

// Payment API Configuration (add these lines)
define('PAYMENT_API_URL', 'https://api.example.com/payments'); // Replace with actual payment API URL
define('PAYMENT_API_KEY', 'payment_key_placeholder'); // Replace with actual payment API key

// Define base URL for tracking links
define('BASE_URL', 'http://localhost/payment-recovery/public');

// Twilio Configuration (Using the credentials you provided)
define('TWILIO_SID', 'AC25385deb28765a6d185f8c56fa2f6464'); // Your Twilio SID
define('TWILIO_TOKEN', '482214c15db7cefd00b11e3edb17570b'); // Replace [AuthToken] with your actual Twilio auth token
define('TWILIO_PHONE', '+19514042316'); // Your Twilio phone number

// Bitly Configuration
define('BITLY_TOKEN', 'your_bitly_token');

// WhatsApp Configuration (add these lines)
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/v1');
define('WHATSAPP_ACCESS_TOKEN', 'whatsapp_token_placeholder');
define('WHATSAPP_PHONE_NUMBER_ID', 'whatsapp_phone_id_placeholder');
define('WHATSAPP_BUSINESS_ACCOUNT_ID', 'whatsapp_business_id_placeholder');


?>
