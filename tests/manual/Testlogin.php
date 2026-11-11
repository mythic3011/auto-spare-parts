<?php
require_once __DIR__ . "/../../resources/php/class/DemoAccounts.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . "/../../resources/php/class/User.php");
require_once(__DIR__ . "/../../resources/php/class/Database.php");
require_once(__DIR__ . "/../../resources/php/class/Session.php");

// Set username and password
$username = "alex@auto-racing.com";
$password = DemoAccounts::PASSWORD;

// Prepare the API request
$url = "http://127.0.0.1:8880/resources/php/api.php";
$data = [
    'action' => 'login',
    'username' => $username,
    'password' => $password
];

// Initialize cURL session
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Enable verbose output for debugging
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Execute the request and get the response
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit;
}

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "Verbose information:\n", htmlspecialchars($verboseLog), "\n\n";

// Get the HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status Code: " . $httpCode . "\n\n";

// Close cURL session
curl_close($ch);

// Output raw response
echo "Raw response:\n" . $response . "\n\n";

// Decode the JSON response
$result = json_decode($response, true);

// Check the response and handle accordingly
if ($result === null) {
    echo "Error: Unable to parse JSON response\n";
} elseif (isset($result['error'])) {
    echo "Error: " . $result['error'] . "\n";
} elseif (isset($result['result']['token'])) {
    echo "Login successful. Token: " . $result['result']['token'] . "\n";
} else {
    echo "Unexpected response from the server\n";
}

// Print the full result for debugging
echo "Full result:\n";
print_r($result);
?>
