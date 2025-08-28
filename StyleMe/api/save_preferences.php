<?php
// Disable error display for production
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../includes/config.php';
require_once '../includes/db.php';

/**
 * Save User Style Preferences API - Enhanced for RAG Integration
 */

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        // Log the raw input for debugging
        error_log("Raw input: " . file_get_contents('php://input'));
        throw new Exception('Invalid JSON input');
    }

    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
    $preferences = $input['preferences'] ?? [];

    // Allow guest users with temporary IDs
    if ($userId <= 0) {
        $userId = 999999; // Default guest user ID
    }

    // Validate preferences structure
    $validatedPrefs = validatePreferences($preferences);

    $db = new Database();
    $conn = $db->getConnection();

    // Check if user preferences already exist
    $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Prepare JSON data for binding
    $stylePrefsJson = json_encode($validatedPrefs['style_preferences']);
    $colorPrefsJson = json_encode($validatedPrefs['color_preferences']);

    if ($existing) {
        // Update existing preferences
        $stmt = $conn->prepare("
            UPDATE user_preferences SET 
            style_preferences = ?, 
            color_preferences = ?, 
            budget_min = ?, 
            budget_max = ?, 
            occasion = ?,
            updated_at = NOW()
            WHERE user_id = ?
        ");

        $stmt->bind_param(
            "ssiisi",
            $stylePrefsJson,
            $colorPrefsJson,
            $validatedPrefs['budget_min'],
            $validatedPrefs['budget_max'],
            $validatedPrefs['occasion'],
            $userId
        );
    } else {
        // Insert new preferences
        $stmt = $conn->prepare("
            INSERT INTO user_preferences 
            (user_id, style_preferences, color_preferences, budget_min, budget_max, occasion, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "issiis",
            $userId,
            $stylePrefsJson,
            $colorPrefsJson,
            $validatedPrefs['budget_min'],
            $validatedPrefs['budget_max'],
            $validatedPrefs['occasion']
        );
    }

    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        // Log the preference update for analytics (optional)
        try {
            $logStmt = $conn->prepare("
                INSERT INTO preference_update_log (user_id, preferences_data, created_at) 
                VALUES (?, ?, NOW())
            ");
            $logStmt->bind_param("is", $userId, json_encode($validatedPrefs));
            $logStmt->execute();
            $logStmt->close();
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log preference update: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Preferences saved successfully',
            'preferences' => $validatedPrefs
        ]);
    } else {
        throw new Exception('Failed to save preferences to database');
    }
} catch (Exception $e) {
    error_log("Error saving user preferences: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save preferences: ' . $e->getMessage(),
        'error_code' => 'DATABASE_ERROR'
    ]);
}

/**
 * Validate and sanitize user preferences
 */
function validatePreferences($preferences)
{
    $validated = [
        'style_preferences' => [],
        'color_preferences' => [],
        'budget_min' => 1000,
        'budget_max' => 10000,
        'occasion' => 'casual'
    ];

    // Validate style preferences
    $validStyles = [
        'casual',
        'formal',
        'business',
        'party',
        'western',
        'ethnic',
        'sports',
        'trendy',
        'vintage',
        'bohemian',
        'minimalist',
        'chic'
    ];
    if (isset($preferences['style_preferences']) && is_array($preferences['style_preferences'])) {
        $validated['style_preferences'] = array_intersect($preferences['style_preferences'], $validStyles);
    }

    // Validate color preferences
    $validColors = [
        'black',
        'white',
        'blue',
        'red',
        'green',
        'grey',
        'navy',
        'brown',
        'pink',
        'yellow',
        'orange',
        'purple',
        'beige',
        'maroon',
        'teal'
    ];
    if (isset($preferences['color_preferences']) && is_array($preferences['color_preferences'])) {
        $validated['color_preferences'] = array_intersect($preferences['color_preferences'], $validColors);
    }

    // Validate budget
    if (isset($preferences['budget_min']) && is_numeric($preferences['budget_min'])) {
        $validated['budget_min'] = max(500, min(100000, (int)$preferences['budget_min']));
    }

    if (isset($preferences['budget_max']) && is_numeric($preferences['budget_max'])) {
        $validated['budget_max'] = max(500, min(500000, (int)$preferences['budget_max']));
    }

    // Ensure budget_min <= budget_max
    if ($validated['budget_min'] > $validated['budget_max']) {
        $validated['budget_min'] = $validated['budget_max'];
    }

    // Validate occasion
    $validOccasions = [
        'office',
        'casual',
        'party',
        'wedding',
        'sports',
        'travel',
        'date',
        'meeting',
        'festival',
        'formal'
    ];
    if (isset($preferences['occasion']) && in_array($preferences['occasion'], $validOccasions)) {
        $validated['occasion'] = $preferences['occasion'];
    }

    return $validated;
}
