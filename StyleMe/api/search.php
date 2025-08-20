<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$query = $_GET['query'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Search query is required']);
    exit;
}

// Enhance the search query with AI (in a real app, this would call OpenAI)
$enhancedQuery = enhanceSearchQuery($query);

// Log the search (for analytics and improving search)
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} else {
    $userId = null;
}

$db->query(
    "INSERT INTO search_logs (user_id, query) VALUES (?, ?)",
    [$userId, $query]
);

// Search products
$sql = "SELECT p.* 
        FROM products p
        WHERE p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?
        ORDER BY p.created_at DESC
        LIMIT 10";
$results = $db->fetchAll($sql, ["%$enhancedQuery%", "%$enhancedQuery%", "%$enhancedQuery%"]);

echo json_encode([
    'success' => true,
    'results' => $results,
    'enhanced_query' => $enhancedQuery
]);
?>