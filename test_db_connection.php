<?php
// Simple database connection test
echo "Testing database connection...\n";

try {
    $conn = new mysqli('localhost', 'root', '1488@@Mihisara', 'ecommerce_sl');
    echo "Database connection successful!\n";

    // Test if user_preferences table exists
    $result = $conn->query("SHOW TABLES LIKE 'user_preferences'");
    if ($result->num_rows > 0) {
        echo "user_preferences table exists.\n";

        // Check table structure
        $result = $conn->query("DESCRIBE user_preferences");
        echo "Table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }

        // Check if there's any data
        $result = $conn->query("SELECT COUNT(*) as count FROM user_preferences");
        $count = $result->fetch_assoc()['count'];
        echo "Records in table: $count\n";
    } else {
        echo "user_preferences table does not exist!\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
