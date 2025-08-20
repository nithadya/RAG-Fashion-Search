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
                $response = addToCart();
                break;
                
            case 'update':
                $response = updateCartItem();
                break;
                
            case 'remove':
                $response = removeCartItem();
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get':
                $response = getCartItems();
                break;
                
            case 'count':
                $response = getCartCount();
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Cart API Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ];
}

echo json_encode($response);

function addToCart() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false,
            'message' => 'Please login to add items to cart',
            'redirect' => true
        ];
    }
    
    $userId = $_SESSION['user_id'];
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $size = trim($_POST['size'] ?? '');
    $color = trim($_POST['color'] ?? '');
    
    if ($productId <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Invalid product or quantity'];
    }
    
    try {
        // Check if product exists and is in stock
        $sql = "SELECT id, name, stock, price, discount_price FROM products WHERE id = ?";
        $product = $db->fetchOne($sql, [$productId]);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        if ($product['stock'] < $quantity) {
            return ['success' => false, 'message' => 'Insufficient stock available'];
        }
        
        // Check if item already exists in cart
        $sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $existingItem = $db->fetchOne($sql, [$userId, $productId]);
        
        if ($existingItem) {
            // Update existing item
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($newQuantity > $product['stock']) {
                return ['success' => false, 'message' => 'Cannot add more items than available stock'];
            }
            
            $sql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$newQuantity, $existingItem['id']]);
        } else {
            // Add new item
            $sql = "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
            $db->query($sql, [$userId, $productId, $quantity]);
        }
        
        return [
            'success' => true,
            'message' => 'Product added to cart successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Add to cart error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add product to cart'];
    }
}

function updateCartItem() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    $cartItemId = intval($_POST['cart_item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($cartItemId <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Invalid item or quantity'];
    }
    
    try {
        // Verify item belongs to user and get product info
        $sql = "SELECT c.id, c.product_id, p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.id = ? AND c.user_id = ?";
        $item = $db->fetchOne($sql, [$cartItemId, $userId]);
        
        if (!$item) {
            return ['success' => false, 'message' => 'Cart item not found'];
        }
        
        if ($quantity > $item['stock']) {
            return ['success' => false, 'message' => 'Quantity exceeds available stock'];
        }
        
        // Update quantity
        $sql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$quantity, $cartItemId]);
        
        return [
            'success' => true,
            'message' => 'Cart updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update cart error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update cart'];
    }
}

function removeCartItem() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    $cartItemId = intval($_POST['cart_item_id'] ?? 0);
    
    if ($cartItemId <= 0) {
        return ['success' => false, 'message' => 'Invalid item ID'];
    }
    
    try {
        // Verify item belongs to user
        $sql = "SELECT id FROM cart WHERE id = ? AND user_id = ?";
        $item = $db->fetchOne($sql, [$cartItemId, $userId]);
        
        if (!$item) {
            return ['success' => false, 'message' => 'Cart item not found'];
        }
        
        // Remove item
        $sql = "DELETE FROM cart WHERE id = ?";
        $db->query($sql, [$cartItemId]);
        
        return [
            'success' => true,
            'message' => 'Item removed from cart'
        ];
        
    } catch (Exception $e) {
        error_log("Remove cart item error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to remove item'];
    }
}

function getCartItems() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // Get cart items with product details
        $sql = "SELECT c.id, c.quantity, c.created_at,
                       p.id as product_id, p.name, p.price, p.discount_price, 
                       p.image1, p.size, p.color, p.stock
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC";
        
        $items = $db->fetchAll($sql, [$userId]);
        
        // Calculate summary
        $subtotal = 0;
        $totalItems = 0;
        $discount = 0;
        
        foreach ($items as &$item) {
            $item['price'] = floatval($item['price']);
            $item['discount_price'] = floatval($item['discount_price']);
            $item['quantity'] = intval($item['quantity']);
            
            $itemPrice = $item['discount_price'] > 0 && $item['discount_price'] < $item['price'] 
                ? $item['discount_price'] 
                : $item['price'];
            
            $itemTotal = $itemPrice * $item['quantity'];
            $subtotal += $itemTotal;
            $totalItems += $item['quantity'];
            
            // Calculate discount if applicable
            if ($item['discount_price'] > 0 && $item['discount_price'] < $item['price']) {
                $discount += ($item['price'] - $item['discount_price']) * $item['quantity'];
            }
        }
        
        // Calculate shipping (free for orders over Rs. 5000)
        $shipping = $subtotal >= 5000 ? 0 : 500;
        $total = $subtotal + $shipping;
        
        $summary = [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'total_items' => $totalItems
        ];
        
        return [
            'success' => true,
            'items' => $items,
            'summary' => $summary
        ];
        
    } catch (Exception $e) {
        error_log("Get cart items error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load cart items'];
    }
}

function getCartCount() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => true, 'count' => 0];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $sql = "SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?";
        $result = $db->fetchOne($sql, [$userId]);
        
        return [
            'success' => true,
            'count' => intval($result['count'])
        ];
        
    } catch (Exception $e) {
        error_log("Get cart count error: " . $e->getMessage());
        return ['success' => true, 'count' => 0];
    }
}
?>
