<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';
require_once '../includes/db.php';

/**
 * Enhanced RAG Search API
 * Handles both simple and complex queries with intelligent preprocessing
 */
class RAGSearchAPI {
    private $db;
    private $ragServiceURL = 'http://localhost:5000';
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Main search handler
     */
    public function handleSearch() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                // Handle GET requests for compatibility
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
            
            if (empty(trim($query))) {
                $result = $this->errorResponse('Search query is required');
                echo json_encode($result);
                return;
            }
            
            // Simple preprocessing that works
            $processedQuery = [
                'original_query' => $query,
                'enhanced_query' => $query,
                'detected_filters' => []
            ];
            
            // Perform RAG search
            $searchResult = $this->performRAGSearch($processedQuery, $userId, $preferences, $searchType);
            
            if (!$searchResult['success']) {
                // Fallback to database search
                $fallbackResult = $this->fallbackSearch($query, $preferences);
                echo json_encode($fallbackResult);
                return;
            }
            
            // Get product details for RAG results
            $products = $this->getProductsByIds($searchResult['product_ids'] ?? []);
            
            // Enhance results with similarity scoring
            $enhancedProducts = $this->enhanceWithScoring($products, $query, $preferences);
            
            $result = [
                'success' => true,
                'products' => $enhancedProducts,
                'search_type' => 'rag',
                'query' => $query,
                'processed_query' => $processedQuery['enhanced_query'],
                'results_count' => count($enhancedProducts),
                'processing_time' => $searchResult['processing_time'] ?? 0,
                'suggestions' => $this->generateQuerySuggestions($query),
                'filters_detected' => $processedQuery['detected_filters'],
                'matching_info' => [
                    'max_score' => count($enhancedProducts) > 0 ? max(array_column($enhancedProducts, 'similarity_score')) : 0,
                    'avg_score' => count($enhancedProducts) > 0 ? array_sum(array_column($enhancedProducts, 'similarity_score')) / count($enhancedProducts) : 0,
                    'score_distribution' => $this->getScoreDistribution($enhancedProducts)
                ]
            ];
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("RAG Search API Error: " . $e->getMessage());
            $errorResult = $this->errorResponse('Search failed: ' . $e->getMessage());
            echo json_encode($errorResult);
        }
    }
    
    /**
     * Intelligent query preprocessing
     */
    private function preprocessQuery($query, $preferences = []) {
        $originalQuery = $query;
        $detectedFilters = [];
        $enhancedQuery = $query;
        
        // Extract colors
        $colors = ['red', 'blue', 'green', 'black', 'white', 'pink', 'yellow', 'purple', 'brown', 'gray', 'navy', 'maroon'];
        foreach ($colors as $color) {
            if (stripos($query, $color) !== false) {
                $detectedFilters['colors'][] = $color;
            }
        }
        
        // Extract sizes
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38', '40'];
        foreach ($sizes as $size) {
            if (stripos($query, $size) !== false) {
                $detectedFilters['sizes'][] = $size;
            }
        }
        
        // Extract occasions
        $occasions = ['casual', 'formal', 'party', 'wedding', 'office', 'sport', 'beach', 'winter', 'summer'];
        foreach ($occasions as $occasion) {
            if (stripos($query, $occasion) !== false) {
                $detectedFilters['occasions'][] = $occasion;
            }
        }
        
        // Extract categories
        $categories = ['shirt', 'dress', 'pants', 'shoes', 'jacket', 'skirt', 'jeans', 'top', 'bottom'];
        foreach ($categories as $category) {
            if (stripos($query, $category) !== false) {
                $detectedFilters['categories'][] = $category;
            }
        }
        
        // Extract price ranges
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $query, $matches)) {
            $detectedFilters['price_range'] = [
                'min' => (int)$matches[1],
                'max' => (int)$matches[2]
            ];
        }
        
        // Enhance query with preferences
        if (!empty($preferences)) {
            $prefParts = [];
            if (!empty($preferences['style_preferences'])) {
                $prefParts[] = 'style: ' . implode(', ', $preferences['style_preferences']);
            }
            if (!empty($preferences['color_preferences'])) {
                $prefParts[] = 'colors: ' . implode(', ', $preferences['color_preferences']);
            }
            if (!empty($preferences['occasion'])) {
                $prefParts[] = 'occasion: ' . $preferences['occasion'];
            }
            if (!empty($preferences['budget_min']) && !empty($preferences['budget_max'])) {
                $prefParts[] = 'budget: Rs.' . $preferences['budget_min'] . '-' . $preferences['budget_max'];
            }
            
            if (!empty($prefParts)) {
                $enhancedQuery = $originalQuery . ' | ' . implode(' | ', $prefParts);
            }
        }
        
        return [
            'original_query' => $originalQuery,
            'enhanced_query' => $enhancedQuery,
            'detected_filters' => $detectedFilters
        ];
    }
    
    /**
     * Perform RAG search with error handling
     */
    private function performRAGSearch($processedQuery, $userId, $preferences, $searchType) {
        try {
            $endpoint = '/search_with_preferences';
            $payload = [
                'query' => $processedQuery['enhanced_query'],
                'user_id' => $userId
            ];
            
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
            
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Fallback to database search when RAG fails
     */
    private function fallbackSearch($query, $preferences = []) {
        try {
            $sql = "SELECT p.*, c.name as category_name FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.stock > 0 AND (
                        p.name LIKE ? OR 
                        p.description LIKE ? OR 
                        p.brand LIKE ? OR 
                        p.color LIKE ? OR 
                        c.name LIKE ?
                    )";
            
            $params = [];
            $searchTerm = '%' . $query . '%';
            for ($i = 0; $i < 5; $i++) {
                $params[] = $searchTerm;
            }
            
            // Add preference filters
            if (!empty($preferences['budget_min']) && !empty($preferences['budget_max'])) {
                $sql .= " AND p.price BETWEEN ? AND ?";
                $params[] = $preferences['budget_min'];
                $params[] = $preferences['budget_max'];
            }
            
            if (!empty($preferences['color_preferences'])) {
                $colorPlaceholders = str_repeat('?,', count($preferences['color_preferences']) - 1) . '?';
                $sql .= " AND p.color IN ($colorPlaceholders)";
                $params = array_merge($params, $preferences['color_preferences']);
            }
            
            $sql .= " ORDER BY 
                CASE WHEN p.name LIKE ? THEN 1
                     WHEN p.brand LIKE ? THEN 2
                     WHEN p.color LIKE ? THEN 3
                     ELSE 4 END,
                p.created_at DESC LIMIT 20";
            
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            
            $products = $this->db->fetchAll($sql, $params);
            
            return [
                'success' => true,
                'products' => $products,
                'search_type' => 'fallback',
                'query' => $query,
                'results_count' => count($products),
                'message' => 'AI search unavailable, using database search'
            ];
            
        } catch (Exception $e) {
            return $this->errorResponse('Search failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get products by IDs with details
     */
    private function getProductsByIds($productIds) {
        if (empty($productIds)) {
            return [];
        }
        
        try {
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.id IN ($placeholders) AND p.stock > 0";
            
            error_log("🔍 Getting products by IDs: " . implode(',', $productIds));
            
            return $this->db->fetchAll($sql, $productIds);
            
        } catch (Exception $e) {
            error_log("Get products by IDs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Enhance products with similarity scoring
     */
    private function enhanceWithScoring($products, $query, $preferences) {
        $queryWords = explode(' ', strtolower(trim($query)));
        $queryWords = array_filter($queryWords); // Remove empty elements
        
        foreach ($products as &$product) {
            $score = 0;
            $matchDetails = [];
            
            // Name matching (highest weight) - exact and partial matches
            $nameWords = explode(' ', strtolower($product['name']));
            $nameWords = array_filter($nameWords);
            
            // Exact word matches (10 points each)
            $exactMatches = array_intersect($queryWords, $nameWords);
            $exactMatchCount = count($exactMatches);
            $score += $exactMatchCount * 10;
            if ($exactMatchCount > 0) {
                $matchDetails[] = "Name matches: " . implode(', ', $exactMatches);
            }
            
            // Partial name matches (5 points each)
            foreach ($queryWords as $qWord) {
                foreach ($nameWords as $nWord) {
                    if (strlen($qWord) > 3 && strpos($nWord, $qWord) !== false && $nWord !== $qWord) {
                        $score += 5;
                        $matchDetails[] = "Partial name match: $qWord → $nWord";
                    }
                }
            }
            
            // Color matching (8 points)
            if (!empty($product['color'])) {
                $productColor = strtolower($product['color']);
                foreach ($queryWords as $word) {
                    if (stripos($productColor, $word) !== false || stripos($word, $productColor) !== false) {
                        $score += 8;
                        $matchDetails[] = "Color match: $word ↔ {$product['color']}";
                        break;
                    }
                }
            }
            
            // Category matching (6 points)
            if (!empty($product['category_name'])) {
                $categoryWords = explode(' ', strtolower($product['category_name']));
                foreach ($queryWords as $word) {
                    foreach ($categoryWords as $catWord) {
                        if (strlen($word) > 2 && (stripos($catWord, $word) !== false || stripos($word, $catWord) !== false)) {
                            $score += 6;
                            $matchDetails[] = "Category match: $word ↔ {$product['category_name']}";
                            break 2; // Break both loops
                        }
                    }
                }
            }
            
            // Brand matching (5 points)
            if (!empty($product['brand'])) {
                foreach ($queryWords as $word) {
                    if (stripos($product['brand'], $word) !== false) {
                        $score += 5;
                        $matchDetails[] = "Brand match: $word ↔ {$product['brand']}";
                        break;
                    }
                }
            }
            
            // Description matching (3 points) - if available
            if (!empty($product['description'])) {
                $descWords = explode(' ', strtolower($product['description']));
                $descMatches = array_intersect($queryWords, $descWords);
                if (count($descMatches) > 0) {
                    $score += count($descMatches) * 3;
                    $matchDetails[] = "Description matches: " . implode(', ', array_slice($descMatches, 0, 3));
                }
            }
            
            // User preference matching bonus (7 points)
            if (!empty($preferences['color_preferences']) && !empty($product['color'])) {
                $prefColors = array_map('strtolower', $preferences['color_preferences']);
                if (in_array(strtolower($product['color']), $prefColors)) {
                    $score += 7;
                    $matchDetails[] = "Matches your color preference: {$product['color']}";
                }
            }
            
            // Style preference matching (4 points)
            if (!empty($preferences['style_preferences']) && !empty($product['category_name'])) {
                foreach ($preferences['style_preferences'] as $style) {
                    if (stripos($product['category_name'], $style) !== false) {
                        $score += 4;
                        $matchDetails[] = "Matches your style: $style";
                        break;
                    }
                }
            }
            
            // Budget matching bonus (2 points) - if product is within budget
            $productPrice = floatval($product['discount_price'] > 0 ? $product['discount_price'] : $product['price']);
            if (!empty($preferences['budget_min']) && !empty($preferences['budget_max'])) {
                if ($productPrice >= $preferences['budget_min'] && $productPrice <= $preferences['budget_max']) {
                    $score += 2;
                    $matchDetails[] = "Within your budget range";
                }
            }
            
            // Store the match details for debugging/explanation
            $product['similarity_score'] = $score;
            $product['match_details'] = $matchDetails;
            $product['match_explanation'] = implode(' • ', array_slice($matchDetails, 0, 3)); // Top 3 reasons
        }
        
        // Sort by similarity score (highest first)
        usort($products, function($a, $b) {
            return $b['similarity_score'] - $a['similarity_score'];
        });
        
        return $products;
    }
    
    /**
     * Get score distribution for analytics
     */
    private function getScoreDistribution($products) {
        if (empty($products)) return [];
        
        $distribution = [
            'excellent' => 0,  // 80-100%
            'good' => 0,       // 60-79%
            'fair' => 0,       // 40-59%
            'poor' => 0        // 0-39%
        ];
        
        // Calculate max possible score for normalization (estimated)
        $maxPossibleScore = 50;
        
        foreach ($products as $product) {
            $score = $product['similarity_score'];
            $percentage = ($score / $maxPossibleScore) * 100;
            
            if ($percentage >= 80) $distribution['excellent']++;
            elseif ($percentage >= 60) $distribution['good']++;
            elseif ($percentage >= 40) $distribution['fair']++;
            else $distribution['poor']++;
        }
        
        return $distribution;
    }
    
    /**
     * Generate query suggestions for better results
     */
    private function generateQuerySuggestions($query) {
        $suggestions = [];
        
        // Basic suggestions
        if (strlen($query) < 3) {
            $suggestions[] = "Try a more specific search like 'casual blue shirt' or 'formal dress'";
        }
        
        // Category suggestions
        if (!preg_match('/shirt|dress|pants|shoes|jacket/', strtolower($query))) {
            $suggestions[] = "Add item type: 'shirt', 'dress', 'pants', 'shoes'";
        }
        
        // Color suggestions
        if (!preg_match('/red|blue|green|black|white|pink/', strtolower($query))) {
            $suggestions[] = "Specify color: 'blue shirt', 'red dress', 'black shoes'";
        }
        
        // Occasion suggestions
        if (!preg_match('/casual|formal|party|wedding|office/', strtolower($query))) {
            $suggestions[] = "Add occasion: 'casual wear', 'formal attire', 'party dress'";
        }
        
        return $suggestions;
    }
    
    /**
     * Error response helper
     */
    private function errorResponse($message) {
        return [
            'success' => false,
            'message' => $message,
            'products' => [],
            'results_count' => 0
        ];
    }
}

// Handle the request
try {
    $api = new RAGSearchAPI();
    $api->handleSearch();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API Error: ' . $e->getMessage(),
        'products' => [],
        'results_count' => 0
    ]);
}
?>
