<?php
// Simple test to debug RAG connection
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

echo "Testing RAG connection...\n";

$ragServiceURL = 'http://localhost:5000';
$endpoint = '/search_with_preferences';
$payload = [
    'query' => 'red t-shirt',
    'user_id' => 0
];

echo "🚀 Calling: " . $ragServiceURL . $endpoint . "\n";
echo "📦 Payload: " . json_encode($payload) . "\n";

$ch = curl_init($ragServiceURL . $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlInfo = curl_getinfo($ch);

echo "📡 HTTP Code: " . $httpCode . "\n";
echo "🔍 cURL Info: " . print_r($curlInfo, true) . "\n";

if ($curlError) {
    echo "❌ cURL Error: " . $curlError . "\n";
} else {
    echo "📝 Response: " . $response . "\n";

    $result = json_decode($response, true);
    if ($result) {
        echo "✅ JSON Valid\n";
        echo "📊 Success: " . ($result['success'] ? 'true' : 'false') . "\n";
        if (isset($result['product_ids'])) {
            echo "🎯 Product IDs: " . implode(',', $result['product_ids']) . "\n";
        }
    } else {
        echo "❌ Invalid JSON response\n";
    }
}

curl_close($ch);
