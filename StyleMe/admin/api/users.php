<?php
// admin/api/users.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
                $response = getUsers();
                break;
            case 'get':
                $id = $_GET['id'] ?? 0;
                $response = getUser($id);
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'delete':
                $response = deleteUser();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Users API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);

function getUsers() {
    global $db;
    
    try {
        $sql = "
            SELECT u.*, COUNT(o.id) as order_count
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ";
        
        $users = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'data' => $users
        ];
        
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load users'];
    }
}

function getUser($id) {
    global $db;
    
    try {
        $sql = "
            SELECT u.*, COUNT(o.id) as order_count, 
                   COUNT(w.id) as wishlist_count,
                   COUNT(c.id) as cart_count
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            LEFT JOIN wishlist w ON u.id = w.user_id
            LEFT JOIN cart c ON u.id = c.user_id
            WHERE u.id = ?
            GROUP BY u.id
        ";
        
        $user = $db->fetchOne($sql, [$id]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Get user's recent orders
        $recentOrders = $db->fetchAll("
            SELECT order_number, total_amount, status, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ", [$id]);
        
        $user['recent_orders'] = $recentOrders;
        
        return [
            'success' => true,
            'data' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load user'];
    }
}

function deleteUser() {
    global $db;
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            return ['success' => false, 'message' => 'User ID is required'];
        }
        
        // Check if user exists
        $user = $db->fetchOne("SELECT name, email FROM users WHERE id = ?", [$id]);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Check if user has orders
        $orderCount = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$id])['count'];
        if ($orderCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete user with existing orders'];
        }
        
        $db->beginTransaction();
        
        // Delete related data
        $db->query("DELETE FROM cart WHERE user_id = ?", [$id]);
        $db->query("DELETE FROM wishlist WHERE user_id = ?", [$id]);
        $db->query("DELETE FROM feedback WHERE user_id = ?", [$id]);
        $db->query("DELETE FROM search_logs WHERE user_id = ?", [$id]);
        
        // Delete user
        $db->query("DELETE FROM users WHERE id = ?", [$id]);
        
        $db->commit();
        
        logAdminActivity('DELETE_USER', "Deleted user: {$user['name']} ({$user['email']})");
        
        return [
            'success' => true,
            'message' => 'User deleted successfully'
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete user'];
    }
}
?>
