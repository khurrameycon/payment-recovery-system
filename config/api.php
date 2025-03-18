<?php
// NMI API Configuration
// define('NMI_API_URL', 'https://secure.networkmerchants.com/api/query.php');
// define('NMI_API_KEY', 'c3wDyHQ5qPQ3e9b2EMcbdFX2X6dQ3WXs'); // Replace with your actual test key

// // Twilio Configuration
// define('TWILIO_SID', 'your_twilio_sid');
// define('TWILIO_TOKEN', 'your_twilio_token');
// define('TWILIO_PHONE', 'your_twilio_phone');
// define('STRIPE_API_KEY', 'your_stripe_key_here');
// // Bitly Configuration
// define('BITLY_TOKEN', 'your_bitly_token');



// NMI API Configuration (keep your existing config)
define('NMI_API_URL', 'https://secure.networkmerchants.com/api/query.php');
define('NMI_API_KEY', 'c3wDyHQ5qPQ3e9b2EMcbdFX2X6dQ3WXs'); // Replace with your actual test key

// Payment API Configuration (add these lines)
define('PAYMENT_API_URL', 'https://api.example.com/payments'); // Replace with actual payment API URL
define('PAYMENT_API_KEY', 'payment_key_placeholder'); // Replace with actual payment API key

// Twilio Configuration (keep your existing config)
define('TWILIO_SID', 'your_twilio_sid');
define('TWILIO_TOKEN', 'your_twilio_token');
define('TWILIO_PHONE', 'your_twilio_phone');
define('STRIPE_API_KEY', 'your_stripe_key_here');

// Bitly Configuration
define('BITLY_TOKEN', 'your_bitly_token');

// WhatsApp Configuration (add these lines)
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/v1');
define('WHATSAPP_ACCESS_TOKEN', 'whatsapp_token_placeholder');
define('WHATSAPP_PHONE_NUMBER_ID', 'whatsapp_phone_id_placeholder');
define('WHATSAPP_BUSINESS_ACCOUNT_ID', 'whatsapp_business_id_placeholder');
?>
