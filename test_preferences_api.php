<?php
// Quick test to check what save_preferences.php is returning
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test the save_preferences API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/api/save_preferences.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'user_id' => 999999,
    'preferences' => [
        'style_preferences' => ['casual'],
        'color_preferences' => ['blue'],
        'budget_min' => 1000,
        'budget_max' => 5000,
        'occasion' => 'office'
    ]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";
echo "Is valid JSON: " . (json_decode($response) ? "Yes" : "No") . "\n";
?>
