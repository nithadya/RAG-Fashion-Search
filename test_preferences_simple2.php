<?php
// Simplified user preferences test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting user preferences test...\n";

try {
    // Test database connection
    $conn = new mysqli('localhost', 'root', '1488@@Mihisara', 'ecommerce_sl');
    echo "Database connected.\n";

    // Test query
    $user_id = 1;
    $stmt = $conn->prepare("SELECT style_preferences, color_preferences, budget_min, budget_max, occasion FROM user_preferences WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");

    if (!$stmt) {
        echo "Prepare failed: " . $conn->error . "\n";
        exit;
    }

    $stmt->bind_param("i", $user_id);
    echo "Statement prepared and bound.\n";

    $stmt->execute();
    echo "Statement executed.\n";

    $result = $stmt->get_result();
    echo "Result obtained.\n";

    if ($row = $result->fetch_assoc()) {
        echo "Row found:\n";
        print_r($row);

        // Test JSON decoding
        $style_prefs = json_decode($row['style_preferences'], true);
        $color_prefs = json_decode($row['color_preferences'], true);

        echo "Style preferences decoded: ";
        var_dump($style_prefs);
        echo "Color preferences decoded: ";
        var_dump($color_prefs);

        // Build response
        $preferences = [
            'style_preferences' => $style_prefs ?: [],
            'color_preferences' => $color_prefs ?: [],
            'budget_min' => (int)$row['budget_min'],
            'budget_max' => (int)$row['budget_max'],
            'occasion' => $row['occasion'] ?: 'casual'
        ];

        $response = [
            'success' => true,
            'preferences' => $preferences
        ];

        echo "Final response:\n";
        $json = json_encode($response);
        echo $json . "\n";
        echo "JSON length: " . strlen($json) . "\n";
    } else {
        echo "No row found for user_id $user_id\n";

        // Return default preferences
        $response = [
            'success' => true,
            'preferences' => [
                'style_preferences' => [],
                'color_preferences' => [],
                'budget_min' => 1000,
                'budget_max' => 10000,
                'occasion' => 'casual'
            ]
        ];

        echo "Default response:\n";
        echo json_encode($response) . "\n";
    }

    $stmt->close();
    $conn->close();
    echo "Connections closed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
