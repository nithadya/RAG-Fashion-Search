<?php
// Test the get_user_preferences.php file directly
$_GET['user_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
include 'StyleMe/api/get_user_preferences.php';
$output = ob_get_clean();

echo "Raw output:\n";
var_dump($output);
echo "\nOutput length: " . strlen($output);

if ($output) {
    echo "\nTrying to decode as JSON:\n";
    $decoded = json_decode($output, true);
    if ($decoded === null) {
        echo "JSON decode failed. Last error: " . json_last_error_msg();
        echo "\nHex dump of first 200 chars:\n";
        echo bin2hex(substr($output, 0, 200));
    } else {
        echo "JSON decoded successfully:\n";
        print_r($decoded);
    }
}
?>
