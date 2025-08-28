<?php
// Simple test for user preferences API
$url = "http://localhost/StyleMe/api/get_user_preferences.php?user_id=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

curl_close($ch);

echo "Headers:\n";
echo $headers;
echo "\nBody:\n";
echo $body;
echo "\nBody length: " . strlen($body);
