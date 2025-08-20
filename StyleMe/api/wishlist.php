<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $response = addToWishlist();
                break;
                
            case 'remove':
                $response = removeFromWishlist();
                break;
                
            case 'toggle':
                $response = toggleWishlist();
                break;
                
            default:
                $response['message'] = 'Invalid action specified';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get':
                $response = getWishlistItems();
                break;
                
            case 'get_status':
                $response = getWishlistStatus();
                break;
                
            case 'count':
                $response = getWishlistCount();
                break;
                
            default:
                $response = getWishlistItems();
                break;
        }
    }
} catch (Exception $e) {
    error_log("Wishlist API Error: " . $e->getMessage());
    $response = [
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again.'
    ];
}

echo json_encode($response);

function addToWishlist() {
    global $db;
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false,
            'message' => 'Please login to add items to your wishlist',
            'redirect' => true
        ];
    }
    
    $userId = $_SESSION['user_id'];
    $productId = intval($_POST['product_id'] ?? 0);
    
    // Validate product ID
    if ($productId <= 0) {
        return [
            'success' => false, 
            'message' => 'Invalid product selected'
        ];
    }
    
    try {
        // Check if product exists
        $sql = "SELECT id, name, stock FROM products WHERE id = ?";
        $product = $db->fetchOne($sql, [$productId]);
        
        if (!$product) {
            return [
                'success' => false, 
                'message' => 'Product not found or has been removed'
            ];
        }
        
        // Check if already in wishlist
        $sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
        $existing = $db->fetchOne($sql, [$userId, $productId]);
        
        if ($existing) {
            return [
                'success' => false, 
                'message' => 'Product is already in your wishlist'
            ];
        }
        
        // Add to wishlist
        $sql = "INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())";
        $result = $db->query($sql, [$userId, $productId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Product added to your wishlist successfully'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to add product to wishlist. Please try again.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Add to wishlist error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Database error occurred. Please try again later.'
        ];
    }
}

function removeFromWishlist() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false, 
            'message' => 'Please login to manage your wishlist'
        ];
    }
    
    $userId = $_SESSION['user_id'];
    $productId = intval($_POST['product_id'] ?? 0);
    
    if ($productId <= 0) {
        return [
            'success' => false, 
            'message' => 'Invalid product selected'
        ];
    }
    
    try {
        // Check if item exists in wishlist
        $sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
        $existing = $db->fetchOne($sql, [$userId, $productId]);
        
        if (!$existing) {
            return [
                'success' => false, 
                'message' => 'Product not found in your wishlist'
            ];
        }
        
        // Remove from wishlist
        $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
        $result = $db->query($sql, [$userId, $productId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Product removed from your wishlist'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to remove product from wishlist'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Remove from wishlist error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Database error occurred. Please try again later.'
        ];
    }
}

function toggleWishlist() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false,
            'message' => 'Please login to manage your wishlist',
            'redirect' => true
        ];
    }
    
    $userId = $_SESSION['user_id'];
    $productId = intval($_POST['product_id'] ?? 0);
    
    if ($productId <= 0) {
        return [
            'success' => false, 
            'message' => 'Invalid product selected'
        ];
    }
    
    try {
        // Check if already in wishlist
        $sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
        $existing = $db->fetchOne($sql, [$userId, $productId]);
        
        if ($existing) {
            // Remove from wishlist
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            $db->query($sql, [$userId, $productId]);
            
            return [
                'success' => true,
                'action' => 'removed',
                'message' => 'Product removed from your wishlist'
            ];
        } else {
            // Check if product exists first
            $sql = "SELECT id FROM products WHERE id = ?";
            $product = $db->fetchOne($sql, [$productId]);
            
            if (!$product) {
                return [
                    'success' => false, 
                    'message' => 'Product not found'
                ];
            }
            
            // Add to wishlist
            $sql = "INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())";
            $db->query($sql, [$userId, $productId]);
            
            return [
                'success' => true,
                'action' => 'added',
                'message' => 'Product added to your wishlist'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Toggle wishlist error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Failed to update wishlist. Please try again.'
        ];
    }
}

function getWishlistItems() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false, 
            'message' => 'Please login to view your wishlist'
        ];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $sql = "SELECT w.id as wishlist_id, w.created_at,
                       p.id, p.name, p.price, p.discount_price, p.image1, p.stock,
                       c.name as category_name
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC";
        
        $items = $db->fetchAll($sql, [$userId]);
        
        // Convert price fields to float
        foreach ($items as &$item) {
            $item['price'] = floatval($item['price']);
            $item['discount_price'] = floatval($item['discount_price']);
        }
        
        return [
            'success' => true,
            'items' => $items,
            'count' => count($items)
        ];
        
    } catch (Exception $e) {
        error_log("Get wishlist items error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Failed to load wishlist items'
        ];
    }
}

function getWishlistStatus() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => true, 'wishlist' => []];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $sql = "SELECT product_id FROM wishlist WHERE user_id = ?";
        $items = $db->fetchAll($sql, [$userId]);
        
        $productIds = array_column($items, 'product_id');
        
        return [
            'success' => true,
            'wishlist' => $productIds
        ];
        
    } catch (Exception $e) {
        error_log("Get wishlist status error: " . $e->getMessage());
        return ['success' => true, 'wishlist' => []];
    }
}

function getWishlistCount() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => true, 'count' => 0];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
        $result = $db->fetchOne($sql, [$userId]);
        
        return [
            'success' => true,
            'count' => intval($result['count'])
        ];
        
    } catch (Exception $e) {
        error_log("Get wishlist count error: " . $e->getMessage());
        return ['success' => true, 'count' => 0];
    }
}
?>
