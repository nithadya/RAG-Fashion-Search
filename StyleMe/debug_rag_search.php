<?php
// Debug version of rag_search.php to isolate the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug RAG Search API Test\n";

// Test the exact same flow as the API
require_once './includes/config.php';
require_once './includes/db.php';

class DebugRAGSearch
{
    private $db;
    private $ragServiceURL = 'http://localhost:5000';

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function testSearch()
    {
        $query = 'red t-shirt';
        $userId = 0;
        $preferences = [];
        $searchType = 'auto';

        // Preprocess query
        $processedQuery = $this->preprocessQuery($query, $preferences);
        echo "✅ Query preprocessed: " . json_encode($processedQuery) . "\n";

        // Perform RAG search
        $searchResult = $this->performRAGSearch($processedQuery, $userId, $preferences, $searchType);
        echo "🔍 RAG Search Result: " . json_encode($searchResult) . "\n";

        if (!$searchResult['success']) {
            echo "❌ RAG search failed, reason: " . ($searchResult['error'] ?? 'unknown') . "\n";
            return;
        }

        // Get products
        if (isset($searchResult['product_ids'])) {
            echo "🎯 Getting products for IDs: " . implode(',', $searchResult['product_ids']) . "\n";
            $products = $this->getProductsByIds($searchResult['product_ids']);
            echo "📦 Found " . count($products) . " products\n";

            if (!empty($products)) {
                foreach ($products as $product) {
                    echo "  - " . $product['name'] . " (ID: " . $product['product_id'] . ")\n";
                }
            }
        }
    }

    private function preprocessQuery($query, $preferences = [])
    {
        return [
            'original_query' => $query,
            'enhanced_query' => $query,
            'detected_filters' => []
        ];
    }

    private function performRAGSearch($processedQuery, $userId, $preferences, $searchType)
    {
        try {
            $endpoint = '/search_with_preferences';
            $payload = [
                'query' => $processedQuery['enhanced_query'],
                'user_id' => $userId
            ];

            echo "🚀 Calling: " . $this->ragServiceURL . $endpoint . "\n";
            echo "📦 Payload: " . json_encode($payload) . "\n";

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

            echo "📡 HTTP Code: " . $httpCode . "\n";

            if ($curlError) {
                throw new Exception('RAG service connection failed: ' . $curlError);
            }

            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("RAG service returned HTTP $httpCode");
            }

            $result = json_decode($response, true);
            echo "📝 Raw Response: " . $response . "\n";

            if (!$result || !isset($result['success'])) {
                throw new Exception('Invalid RAG service response');
            }

            return $result;
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
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

            echo "🔍 SQL Query: " . $sql . "\n";
            echo "🔢 Parameters: " . implode(',', $productIds) . "\n";

            return $this->db->fetchAll($sql, $productIds);
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
            return [];
        }
    }
}

$debug = new DebugRAGSearch();
$debug->testSearch();
