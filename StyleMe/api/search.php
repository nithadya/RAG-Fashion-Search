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

// Get user ID for personalized search
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    // For non-logged users, use a default user ID or handle differently
    $userId = 0; // You can adjust this based on your needs
}

// Call the LangChain RAG service
$ragServiceUrl = 'http://localhost:5000/search';
$ragPayload = [
    'user_id' => $userId,
    'query' => $query
];

$startTime = microtime(true);

try {
    // Initialize cURL for RAG service call
    $ch = curl_init($ragServiceUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ragPayload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout
    
    $ragResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("cURL Error: " . $curlError);
    }
    
    if ($httpCode !== 200) {
        throw new Exception("RAG Service returned HTTP " . $httpCode);
    }
    
    $ragData = json_decode($ragResponse, true);
    
    if (!$ragData || !isset($ragData['success']) || !$ragData['success']) {
        throw new Exception("Invalid response from RAG service");
    }
    
    $productIds = $ragData['product_ids'] ?? [];
    $processingTime = microtime(true) - $startTime;
    
    // If RAG service returned product IDs, fetch full product details
    if (!empty($productIds)) {
        // Create placeholders for IN clause
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        // Fetch products maintaining the order returned by RAG
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id IN ($placeholders) AND p.stock > 0
                ORDER BY FIELD(p.id, $placeholders)";
        
        // Duplicate product IDs for both IN clause and ORDER BY FIELD
        $params = array_merge($productIds, $productIds);
        $results = $db->fetchAll($sql, $params);
    } else {
        $results = [];
    }
    
    // Enhanced response with RAG metadata
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'rag_metadata' => [
            'product_ids' => $productIds,
            'results_count' => count($results),
            'processing_time' => round($processingTime, 3),
            'rag_processing_time' => $ragData['processing_time'] ?? null,
            'history_considered' => $ragData['history_considered'] ?? false,
            'service_version' => '2.0-langchain'
        ]
    ]);
    
} catch (Exception $e) {
    // Fallback to traditional database search if RAG service is unavailable
    error_log("RAG Service Error: " . $e->getMessage());
    
    // Traditional search as fallback
    $enhancedQuery = enhanceSearchQuery($query);
    
    // Log the search for analytics
    if ($userId > 0) {
        $db->query(
            "INSERT INTO search_logs (user_id, query, enhanced_query, processing_time) VALUES (?, ?, ?, ?)",
            [$userId, $query, $enhancedQuery, round(microtime(true) - $startTime, 3)]
        );
    }
    
    // Fallback search
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?) 
              AND p.stock > 0
            ORDER BY 
              CASE WHEN p.discount_price > 0 THEN 1 ELSE 0 END DESC,
              p.created_at DESC
            LIMIT 15";
            
    $searchTerm = "%$enhancedQuery%";
    $results = $db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'enhanced_query' => $enhancedQuery,
        'fallback_mode' => true,
        'error_message' => 'Using fallback search - RAG service unavailable',
        'rag_metadata' => [
            'service_version' => '1.0-fallback',
            'processing_time' => round(microtime(true) - $startTime, 3)
        ]
    ]);
}
?>