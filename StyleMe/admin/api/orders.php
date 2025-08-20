<?php
// admin/api/orders.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                $response = getOrders();
                break;
            case 'get':
                $id = $_GET['id'] ?? 0;
                $response = getOrder($id);
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'updateStatus':
                $response = updateOrderStatus();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);

function getOrders() {
    global $db;
    
    try {
        $sql = "
            SELECT o.*, u.name as customer_name, u.email as customer_email,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";
        
        $orders = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'data' => $orders
        ];
        
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load orders'];
    }
}

function getOrder($id) {
    global $db;
    
    try {
        $sql = "
            SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ";
        
        $order = $db->fetchOne($sql, [$id]);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Get order items
        $orderItems = $db->fetchAll("
            SELECT oi.*, p.name as product_name, p.image1
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ", [$id]);
        
        $order['items'] = $orderItems;
        
        return [
            'success' => true,
            'data' => $order
        ];
        
    } catch (Exception $e) {
        error_log("Get order error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load order'];
    }
}

function updateOrderStatus() {
    global $db;
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        $status = sanitizeAdminInput($_POST['status'] ?? '');
        
        $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        
        if (!$id || !in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid order data'];
        }
        
        // Check if order exists
        $order = $db->fetchOne("SELECT order_number FROM orders WHERE id = ?", [$id]);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$status, $id]);
        
        logAdminActivity('UPDATE_ORDER_STATUS', "Updated order {$order['order_number']} status to $status");
        
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
