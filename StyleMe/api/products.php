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
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    // Handle POST requests (for RAG integration)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'get_by_ids':
                $productIds = $input['product_ids'] ?? [];
                $response = getProductsByIds($productIds);
                break;
            default:
                $response = ['success' => false, 'message' => 'Invalid POST action'];
        }
    } else {
        // Handle GET requests (existing functionality)
        $action = $_GET['action'] ?? '';

        switch ($action) {
        case 'get_categories':
            $response = getCategories();
            break;

        case 'featured_categories':
            $response = getFeaturedCategories();
            break;

        case 'trending':
            $response = getTrendingProducts();
            break;

        case 'discounted':
            $response = getDiscountedProducts();
            break;

        case 'filter_options':
            $response = getFilterOptions();
            break;

        case 'detail':
            $productId = intval($_GET['id'] ?? 0);
            $response = getProductDetail($productId);
            break;

        case 'related':
            $productId = intval($_GET['id'] ?? 0);
            $response = getRelatedProducts($productId);
            break;

        case 'ids':
            $response = getProductsByIds();
            break;

            case 'search':
            default:
                // Check if 'ids' parameter is provided without action
                if (!empty($_GET['ids'])) {
                    $response = getProductsByIds();
                } else {
                    $response = getProducts();
                }
                break;
        }
    }
} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'An error occurred while fetching products.'
    ];
}

echo json_encode($response);

function getCategories()
{
    global $db;

    try {
        $sql = "SELECT id, name, slug, description FROM categories ORDER BY name";
        $categories = $db->fetchAll($sql);

        return $categories;
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        return [];
    }
}

function getFeaturedCategories()
{
    global $db;

    try {
        $sql = "SELECT c.id, c.name, c.image, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id 
                GROUP BY c.id, c.name, c.image
                ORDER BY product_count DESC 
                LIMIT 6";
        $categories = $db->fetchAll($sql);

        return $categories;
    } catch (Exception $e) {
        error_log("Get featured categories error: " . $e->getMessage());
        return [];
    }
}

function getTrendingProducts()
{
    global $db;

    try {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.stock > 0 
                ORDER BY p.created_at DESC 
                LIMIT 8";
        $products = $db->fetchAll($sql);

        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            $product['discount_price'] = floatval($product['discount_price']);
        }

        return $products;
    } catch (Exception $e) {
        error_log("Get trending products error: " . $e->getMessage());
        return [];
    }
}

function getDiscountedProducts()
{
    global $db;

    try {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.discount_price > 0 AND p.discount_price < p.price AND p.stock > 0 
                ORDER BY ((p.price - p.discount_price) / p.price) DESC 
                LIMIT 8";
        $products = $db->fetchAll($sql);

        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            $product['discount_price'] = floatval($product['discount_price']);
        }

        return $products;
    } catch (Exception $e) {
        error_log("Get discounted products error: " . $e->getMessage());
        return [];
    }
}

function getFilterOptions()
{
    global $db;

    try {
        $options = [];

        // Categories with product count
        $sql = "SELECT c.id, c.name, COUNT(p.id) as count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id 
                GROUP BY c.id, c.name 
                ORDER BY c.name";
        $options['categories'] = $db->fetchAll($sql);

        // Brands with product count
        $sql = "SELECT brand, COUNT(*) as count 
                FROM products 
                WHERE brand IS NOT NULL AND brand != '' 
                GROUP BY brand 
                ORDER BY brand";
        $options['brands'] = $db->fetchAll($sql);

        // Sizes with product count
        $sql = "SELECT size, COUNT(*) as count 
                FROM products 
                WHERE size IS NOT NULL AND size != '' 
                GROUP BY size 
                ORDER BY size";
        $options['sizes'] = $db->fetchAll($sql);

        // Colors with product count
        $sql = "SELECT color, COUNT(*) as count 
                FROM products 
                WHERE color IS NOT NULL AND color != '' 
                GROUP BY color 
                ORDER BY color";
        $options['colors'] = $db->fetchAll($sql);

        // Occasions with product count
        $sql = "SELECT occasion, COUNT(*) as count 
                FROM products 
                WHERE occasion IS NOT NULL AND occasion != '' 
                GROUP BY occasion 
                ORDER BY occasion";
        $options['occasions'] = $db->fetchAll($sql);

        return $options;
    } catch (Exception $e) {
        error_log("Get filter options error: " . $e->getMessage());
        return [
            'categories' => [],
            'brands' => [],
            'sizes' => [],
            'colors' => [],
            'occasions' => []
        ];
    }
}

function getProducts()
{
    global $db;

    try {
        // Build WHERE clause based on filters
        $where = ["p.stock >= 0"];
        $params = [];

        // Search filter
        if (!empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        // Category filter
        if (!empty($_GET['category'])) {
            $where[] = "p.category_id = ?";
            $params[] = intval($_GET['category']);
        }

        // Price range filter
        if (!empty($_GET['min_price'])) {
            $where[] = "COALESCE(p.discount_price, p.price) >= ?";
            $params[] = floatval($_GET['min_price']);
        }

        if (!empty($_GET['max_price'])) {
            $where[] = "COALESCE(p.discount_price, p.price) <= ?";
            $params[] = floatval($_GET['max_price']);
        }

        // Brand filter
        if (!empty($_GET['brand']) && is_array($_GET['brand'])) {
            $brandPlaceholders = str_repeat('?,', count($_GET['brand']) - 1) . '?';
            $where[] = "p.brand IN ($brandPlaceholders)";
            $params = array_merge($params, $_GET['brand']);
        }

        // Size filter
        if (!empty($_GET['size']) && is_array($_GET['size'])) {
            $sizeConditions = [];
            foreach ($_GET['size'] as $size) {
                $sizeConditions[] = "FIND_IN_SET(?, p.size)";
                $params[] = $size;
            }
            $where[] = "(" . implode(' OR ', $sizeConditions) . ")";
        }

        // Color filter
        if (!empty($_GET['color']) && is_array($_GET['color'])) {
            $colorConditions = [];
            foreach ($_GET['color'] as $color) {
                $colorConditions[] = "FIND_IN_SET(?, p.color)";
                $params[] = $color;
            }
            $where[] = "(" . implode(' OR ', $colorConditions) . ")";
        }

        // Occasion filter
        if (!empty($_GET['occasion']) && is_array($_GET['occasion'])) {
            $occasionConditions = [];
            foreach ($_GET['occasion'] as $occasion) {
                $occasionConditions[] = "FIND_IN_SET(?, p.occasion)";
                $params[] = $occasion;
            }
            $where[] = "(" . implode(' OR ', $occasionConditions) . ")";
        } elseif (!empty($_GET['occasion']) && is_string($_GET['occasion'])) {
            // Handle single occasion from preferences
            $where[] = "FIND_IN_SET(?, p.occasion)";
            $params[] = $_GET['occasion'];
        }

        // === User Preference Filters ===
        
        // Budget range from preferences
        if (!empty($_GET['budget_min'])) {
            $where[] = "COALESCE(p.discount_price, p.price) >= ?";
            $params[] = floatval($_GET['budget_min']);
        }
        if (!empty($_GET['budget_max'])) {
            $where[] = "COALESCE(p.discount_price, p.price) <= ?";
            $params[] = floatval($_GET['budget_max']);
        }

        // Style preferences (styles parameter)
        if (!empty($_GET['styles'])) {
            $styles = is_string($_GET['styles']) ? explode(',', $_GET['styles']) : $_GET['styles'];
            if (is_array($styles) && count($styles) > 0) {
                $styleConditions = [];
                foreach ($styles as $style) {
                    // Check in both category name and description
                    $styleConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
                    $styleParam = '%' . trim($style) . '%';
                    $params[] = $styleParam;
                    $params[] = $styleParam;
                    $params[] = $styleParam;
                }
                $where[] = "(" . implode(' OR ', $styleConditions) . ")";
            }
        }

        // Color preferences (colors parameter)
        if (!empty($_GET['colors'])) {
            $colors = is_string($_GET['colors']) ? explode(',', $_GET['colors']) : $_GET['colors'];
            if (is_array($colors) && count($colors) > 0) {
                $colorConditions = [];
                foreach ($colors as $color) {
                    $colorConditions[] = "FIND_IN_SET(?, p.color)";
                    $params[] = trim($color);
                }
                $where[] = "(" . implode(' OR ', $colorConditions) . ")";
            }
        }

        // Gender filter
        if (!empty($_GET['gender'])) {
            $where[] = "p.gender = ?";
            $params[] = $_GET['gender'];
        }

        // Discount filter
        if (!empty($_GET['min_discount'])) {
            $where[] = "p.discount_price > 0 AND p.discount_price < p.price";
        }

        // Build ORDER BY clause
        $orderBy = "p.created_at DESC";
        $sort = $_GET['sort'] ?? 'newest';

        switch ($sort) {
            case 'name_asc':
                $orderBy = "p.name ASC";
                break;
            case 'name_desc':
                $orderBy = "p.name DESC";
                break;
            case 'price_asc':
                $orderBy = "COALESCE(p.discount_price, p.price) ASC";
                break;
            case 'price_desc':
                $orderBy = "COALESCE(p.discount_price, p.price) DESC";
                break;
            case 'discount':
                $orderBy = "((p.price - COALESCE(p.discount_price, p.price)) / p.price) DESC";
                break;
            default:
                $orderBy = "p.created_at DESC";
                break;
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE $whereClause";
        $totalResult = $db->fetchOne($countSql, $params);
        $total = intval($totalResult['total']);

        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;
        $totalPages = ceil($total / $limit);

        // Get products
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE $whereClause 
                ORDER BY $orderBy 
                LIMIT $limit OFFSET $offset";

        $products = $db->fetchAll($sql, $params);

        // Convert price fields to float
        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            $product['discount_price'] = floatval($product['discount_price']);
        }

        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'per_page' => $limit
            ]
        ];
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        return [
            'products' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 0,
                'total_items' => 0,
                'per_page' => 12
            ]
        ];
    }
}

function getProductDetail($productId)
{
    global $db;

    if ($productId <= 0) {
        return null;
    }

    try {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";

        $product = $db->fetchOne($sql, [$productId]);

        if ($product) {
            $product['price'] = floatval($product['price']);
            $product['discount_price'] = floatval($product['discount_price']);
        }

        return $product;
    } catch (Exception $e) {
        error_log("Get product detail error: " . $e->getMessage());
        return null;
    }
}

function getRelatedProducts($productId)
{
    global $db;

    if ($productId <= 0) {
        return [];
    }

    try {
        // Get the category of the current product
        $sql = "SELECT category_id FROM products WHERE id = ?";
        $currentProduct = $db->fetchOne($sql, [$productId]);

        if (!$currentProduct) {
            return [];
        }

        // Get related products from the same category
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? AND p.id != ? AND p.stock > 0 
                ORDER BY p.created_at DESC 
                LIMIT 4";

        $products = $db->fetchAll($sql, [$currentProduct['category_id'], $productId]);

        // Convert price fields to float
        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            $product['discount_price'] = floatval($product['discount_price']);
        }

        return $products;
    } catch (Exception $e) {
        error_log("Get related products error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get products by specific IDs (for RAG search results)
 */
function getProductsByIds($productIds = null)
{
    global $db;

    try {
        // Get IDs from parameter or query parameter
        if ($productIds !== null && is_array($productIds)) {
            $ids = array_filter(array_map('intval', $productIds));
        } else {
            $idsParam = $_GET['ids'] ?? '';
            if (empty($idsParam)) {
                return [
                    'success' => true,
                    'products' => [],
                    'total' => 0
                ];
            }
            // Parse comma-separated IDs
            $ids = array_filter(array_map('intval', explode(',', $idsParam)));
        }

        if (empty($ids)) {
            return [
                'success' => true,
                'products' => [],
                'total' => 0
            ];
        }

        // Create placeholders for prepared statement
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';

        $sql = "SELECT 
                    p.product_id,
                    p.name,
                    p.description,
                    p.price,
                    p.discount_price,
                    p.stock,
                    p.brand,
                    p.image_url,
                    p.created_at,
                    p.updated_at,
                    c.name as category_name,
                    c.id as category_id,
                    0 as average_rating,
                    0 as review_count,
                    CASE 
                        WHEN p.discount_price > 0 AND p.discount_price < p.price 
                        THEN ROUND(((p.price - p.discount_price) / p.price) * 100)
                        ELSE 0 
                    END as discount_percentage
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.product_id IN ($placeholders) AND p.status = 'active' AND p.stock >= 0
                GROUP BY p.product_id, p.name, p.description, p.price, p.discount_price, 
                         p.stock, p.brand, p.image_url, p.created_at, p.updated_at, 
                         c.name, c.id
                ORDER BY FIELD(p.product_id, " . implode(',', $ids) . ")";

        $products = $db->fetchAll($sql, $ids);

        // Convert price fields to float and format data
        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            $product['discount_price'] = floatval($product['discount_price']);
            $product['average_rating'] = floatval($product['average_rating']);
            $product['review_count'] = intval($product['review_count']);
            $product['discount_percentage'] = intval($product['discount_percentage']);
            $product['product_id'] = intval($product['product_id']);
            $product['category_id'] = intval($product['category_id']);
            $product['stock'] = intval($product['stock']);

            // Ensure image URL is properly formatted
            if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                $product['image_url'] = 'assets/uploads/' . $product['image_url'];
            }
        }

        return [
            'success' => true,
            'products' => $products,
            'total' => count($products),
            'requested_ids' => $ids,
            'found_ids' => array_column($products, 'product_id')
        ];
    } catch (Exception $e) {
        error_log("Get products by IDs error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to fetch products by IDs',
            'products' => [],
            'total' => 0
        ];
    }
}
