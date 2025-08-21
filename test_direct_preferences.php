<?php
// Direct test of get_user_preferences.php
echo "Testing get_user_preferences.php directly...\n";

// Set GET parameters
$_GET['user_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();

// Include the API file
try {
    include 'StyleMe/api/get_user_preferences.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
} finally {
    ob_end_clean();
}

echo "Output:\n";
echo $output;
echo "\nOutput length: " . strlen($output);

// Try to decode as JSON
if (!empty($output)) {
    $json = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nJSON is valid:\n";
        print_r($json);
    } else {
        echo "\nJSON decode error: " . json_last_error_msg();
        echo "\nRaw output (hex): " . bin2hex($output);
    }
}
?>
