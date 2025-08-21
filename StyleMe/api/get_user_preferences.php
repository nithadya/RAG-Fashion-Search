<?php

/**
 * Get User Preferences API
 * Retrieves saved user preferences for the intelligent search
 */

// Disable error display to prevent corrupting JSON output
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Only GET method allowed');
    }

    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($user_id <= 0) {
        throw new Exception('Valid user_id required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get user preferences
    $stmt = $conn->prepare("
        SELECT 
            style_preferences,
            color_preferences,
            budget_min,
            budget_max,
            occasion,
            updated_at
        FROM user_preferences 
        WHERE user_id = ? 
        ORDER BY updated_at DESC 
        LIMIT 1
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        $preferences = [
            'style_preferences' => json_decode($row['style_preferences'], true) ?: [],
            'color_preferences' => json_decode($row['color_preferences'], true) ?: [],
            'budget_min' => (int)$row['budget_min'],
            'budget_max' => (int)$row['budget_max'],
            'occasion' => $row['occasion'] ?: 'casual',
            'updated_at' => $row['updated_at']
        ];

        echo json_encode([
            'success' => true,
            'preferences' => $preferences
        ]);
    } else {
        // Return default preferences
        echo json_encode([
            'success' => true,
            'preferences' => [
                'style_preferences' => [],
                'color_preferences' => [],
                'budget_min' => 1000,
                'budget_max' => 10000,
                'occasion' => 'casual',
                'updated_at' => null
            ]
        ]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
