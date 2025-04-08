<?php
// File: test-twilio-direct.php
// Standalone script to test Twilio SMS sending directly

// Twilio credentials from your provided code
$account_sid = 'AC25385deb28765a6d185f8c56fa2f6464';
$auth_token = '482214c15db7cefd00b11e3edb17570b'; // Replace [AuthToken] with your actual auth token
$from_phone = '+19514042316';
$to_phone = '+16479795947';

echo "Testing Twilio SMS sending directly...\n\n";

// Prepare API request
$url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
$data = [
    'From' => $from_phone,
    'To' => $to_phone,
    'Body' => 'Hi this is a test SMS from the Payment Recovery System'
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_USERPWD, "{$account_sid}:{$auth_token}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute request
echo "Sending SMS to: {$to_phone}\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    echo "ERROR: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

curl_close($ch);

// Output response
echo "HTTP Response Code: {$httpCode}\n";
echo "Response Body:\n";
$responseData = json_decode($response, true);
print_r($responseData);

// Check if successful
if ($httpCode >= 200 && $httpCode < 300) {
    echo "\nSMS sent successfully! Message SID: " . $responseData['sid'] . "\n";
} else {
    echo "\nFailed to send SMS. Please check the error details above.\n";
}
?>