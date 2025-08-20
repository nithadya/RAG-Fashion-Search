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
            case 'create':
                $response = createOrder();
                break;
                
            case 'update_status':
                $response = updateOrderStatus();
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'history':
                $response = getOrderHistory();
                break;
                
            case 'detail':
                $response = getOrderDetail();
                break;
                
            case 'track':
                $response = trackOrder();
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred while processing your order'];
}

echo json_encode($response);

function createOrder() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false,
            'message' => 'Please login to place an order',
            'redirect' => true
        ];
    }
    
    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    $requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'postalCode', 'paymentMethod'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            return ['success' => false, 'message' => "Please fill in all required fields"];
        }
    }
    
    try {
        // Get cart items
        $sql = "SELECT c.product_id, c.quantity, 
                       p.name, p.price, p.discount_price, p.stock
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?";
        $cartItems = $db->fetchAll($sql, [$userId]);
        
        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'Your cart is empty'];
        }
        
        // Check stock availability
        foreach ($cartItems as $item) {
            if ($item['stock'] < $item['quantity']) {
                return [
                    'success' => false, 
                    'message' => "Insufficient stock for {$item['name']}. Available: {$item['stock']}"
                ];
            }
        }
        
        // Calculate order totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $price = $item['discount_price'] > 0 && $item['discount_price'] < $item['price'] 
                ? $item['discount_price'] 
                : $item['price'];
            $subtotal += $price * $item['quantity'];
        }
        
        $shipping = $subtotal >= 5000 ? 0 : 500; // Free shipping over Rs. 5000
        $discount = 0; // Can implement discount logic here
        $total = $subtotal + $shipping - $discount;
        
        // Generate order number
        $orderNumber = generateOrderNumber();
        
        // Prepare order data
        $firstName = trim($_POST['firstName']);
        $lastName = trim($_POST['lastName']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $postalCode = trim($_POST['postalCode']);
        $notes = trim($_POST['notes'] ?? '');
        $paymentMethod = $_POST['paymentMethod'];
        
        $shippingAddress = "$address, $city, $postalCode";
        
        // Start transaction
        $db->beginTransaction();
        
        // Create order
        $sql = "INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, shipping_address, contact_number, notes, created_at)
                VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, NOW())";
        $db->query($sql, [$userId, $orderNumber, $total, $paymentMethod, $shippingAddress, $phone, $notes]);
        
        $orderId = $db->lastInsertId();
        
        // Add order items and update stock
        foreach ($cartItems as $item) {
            $price = $item['discount_price'] > 0 && $item['discount_price'] < $item['price'] 
                ? $item['discount_price'] 
                : $item['price'];
            
            // Insert order item
            $sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)";
            $db->query($sql, [$orderId, $item['product_id'], $item['quantity'], $price]);
            
            // Update product stock
            $sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $db->query($sql, [$item['quantity'], $item['product_id']]);
        }
        
        // Clear cart
        $sql = "DELETE FROM cart WHERE user_id = ?";
        $db->query($sql, [$userId]);
        
        // Commit transaction
        $db->commit();
        
        // Send order confirmation email (implement as needed)
        // sendOrderConfirmationEmail($orderId, $email, $firstName);
        
        return [
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Create order error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to process order. Please try again.'];
    }
}

function getOrderHistory() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $sql = "SELECT id, order_number, total_amount, status, payment_method, created_at
                FROM orders 
                WHERE user_id = ?
                ORDER BY created_at DESC";
        $orders = $db->fetchAll($sql, [$userId]);
        
        // Format dates and amounts
        foreach ($orders as &$order) {
            $order['total_amount'] = floatval($order['total_amount']);
            $order['created_at'] = date('M d, Y', strtotime($order['created_at']));
        }
        
        return [
            'success' => true,
            'orders' => $orders
        ];
        
    } catch (Exception $e) {
        error_log("Get order history error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load order history'];
    }
}

function getOrderDetail() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    $orderId = intval($_GET['order_id'] ?? 0);
    
    if ($orderId <= 0) {
        return ['success' => false, 'message' => 'Invalid order ID'];
    }
    
    try {
        // Get order details
        $sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
        $order = $db->fetchOne($sql, [$orderId, $userId]);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Get order items
        $sql = "SELECT oi.quantity, oi.price,
                       p.id as product_id, p.name, p.image1
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        $items = $db->fetchAll($sql, [$orderId]);
        
        // Format data
        $order['total_amount'] = floatval($order['total_amount']);
        foreach ($items as &$item) {
            $item['price'] = floatval($item['price']);
            $item['subtotal'] = $item['price'] * $item['quantity'];
        }
        
        return [
            'success' => true,
            'order' => $order,
            'items' => $items
        ];
        
    } catch (Exception $e) {
        error_log("Get order detail error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load order details'];
    }
}

function trackOrder() {
    global $db;
    
    $orderNumber = trim($_GET['order_number'] ?? '');
    
    if (empty($orderNumber)) {
        return ['success' => false, 'message' => 'Order number is required'];
    }
    
    try {
        $sql = "SELECT order_number, status, created_at, updated_at, shipping_address
                FROM orders 
                WHERE order_number = ?";
        $order = $db->fetchOne($sql, [$orderNumber]);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Create tracking timeline
        $timeline = [];
        $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
        $currentStatus = $order['status'];
        
        foreach ($statuses as $status) {
            $isCompleted = array_search($status, $statuses) <= array_search($currentStatus, $statuses);
            $timeline[] = [
                'status' => $status,
                'completed' => $isCompleted,
                'date' => $isCompleted ? $order['updated_at'] : null
            ];
        }
        
        return [
            'success' => true,
            'order' => $order,
            'timeline' => $timeline
        ];
        
    } catch (Exception $e) {
        error_log("Track order error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to track order'];
    }
}

function updateOrderStatus() {
    global $db;
    
    // This would typically be called by admin panel
    $orderId = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    
    if ($orderId <= 0 || !in_array($status, $validStatuses)) {
        return ['success' => false, 'message' => 'Invalid order ID or status'];
    }
    
    try {
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$status, $orderId]);
        
        return [
            'success' => true,
            'message' => 'Order status updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update order status error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update order status'];
    }
}


?>
