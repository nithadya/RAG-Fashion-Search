<?php
// Temporary debug version that shows logs in response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';
require_once '../includes/db.php';

$logs = [];
function debug_log($message)
{
    global $logs;
    $logs[] = $message;
}

class DebugRAGSearchAPI
{
    private $db;
    private $ragServiceURL = 'http://localhost:5000';

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function handleSearch()
    {
        global $logs;
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                $input = [
                    'query' => $_GET['query'] ?? '',
                    'user_id' => $_GET['user_id'] ?? 0,
                    'preferences' => $_GET['preferences'] ?? [],
                    'search_type' => $_GET['search_type'] ?? 'auto'
                ];
            }

            $query = $input['query'] ?? '';
            $userId = $input['user_id'] ?? 0;
            $preferences = $input['preferences'] ?? [];
            $searchType = $input['search_type'] ?? 'auto';

            debug_log("🔍 Processing query: $query");

            if (empty(trim($query))) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Search query is required',
                    'products' => [],
                    'results_count' => 0,
                    'logs' => $logs
                ]);
                return;
            }

            // Simple preprocessing
            $processedQuery = [
                'enhanced_query' => $query,
                'detected_filters' => []
            ];

            debug_log("🚀 Starting RAG search...");
            $searchResult = $this->performRAGSearch($processedQuery, $userId, $preferences, $searchType);

            debug_log("🔬 RAG result success: " . ($searchResult['success'] ? 'true' : 'false'));

            if (!$searchResult['success']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'RAG search failed: ' . ($searchResult['error'] ?? 'unknown'),
                    'products' => [],
                    'results_count' => 0,
                    'logs' => $logs
                ]);
                return;
            }

            // Get products
            $products = $this->getProductsByIds($searchResult['product_ids'] ?? []);
            debug_log("📦 Retrieved " . count($products) . " products from database");

            echo json_encode([
                'success' => true,
                'products' => $products,
                'search_type' => 'rag',
                'query' => $query,
                'results_count' => count($products),
                'logs' => $logs
            ]);
        } catch (Exception $e) {
            debug_log("❌ Exception: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'API Error: ' . $e->getMessage(),
                'products' => [],
                'results_count' => 0,
                'logs' => $logs
            ]);
        }
    }

    private function performRAGSearch($processedQuery, $userId, $preferences, $searchType)
    {
        try {
            $endpoint = '/search_with_preferences';
            $payload = [
                'query' => $processedQuery['enhanced_query'],
                'user_id' => $userId
            ];

            debug_log("🚀 Calling: " . $this->ragServiceURL . $endpoint);

            $ch = curl_init($this->ragServiceURL . $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            debug_log("📡 HTTP Code: " . $httpCode);

            if ($curlError) {
                throw new Exception('RAG service connection failed: ' . $curlError);
            }

            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("RAG service returned HTTP $httpCode");
            }

            $result = json_decode($response, true);

            if (!$result || !isset($result['success'])) {
                throw new Exception('Invalid RAG service response');
            }

            debug_log("✅ RAG success with " . count($result['product_ids'] ?? []) . " product IDs");

            return $result;
        } catch (Exception $e) {
            debug_log("❌ RAG Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getProductsByIds($productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        try {
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.id IN ($placeholders) AND p.stock > 0";

            debug_log("🔍 SQL: $sql with IDs: " . implode(',', $productIds));

            return $this->db->fetchAll($sql, $productIds);
        } catch (Exception $e) {
            debug_log("❌ Database error: " . $e->getMessage());
            return [];
        }
    }
}

$api = new DebugRAGSearchAPI();
$api->handleSearch();
