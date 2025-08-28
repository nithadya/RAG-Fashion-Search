<?php
// Simple API test for categories
header('Content-Type: application/json');

require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    global $db;
    
    // Test getting categories
    $sql = "SELECT id, name, slug, description FROM categories ORDER BY name";
    $categories = $db->fetchAll($sql);
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($categories)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
