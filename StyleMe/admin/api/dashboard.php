<?php
// admin/api/dashboard.php
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
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'stats':
            $response = getDashboardStats();
            break;
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);


// admin/api/dashboard.php (continued)
function getDashboardStats() {
    global $db;
    
    try {
        // Get total counts
        $totalProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'];
        $totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        $totalOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'];
        $totalCategories = $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'];
        
        // Get recent orders
        $recentOrders = $db->fetchAll("
            SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                   u.name as customer_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        
        // Get low stock products
        $lowStockProducts = $db->fetchAll("
            SELECT id, name, stock
            FROM products
            WHERE stock < 10
            ORDER BY stock ASC
            LIMIT 10
        ");
        
        return [
            'success' => true,
            'data' => [
                'totalProducts' => $totalProducts,
                'totalUsers' => $totalUsers,
                'totalOrders' => $totalOrders,
                'totalCategories' => $totalCategories,
                'recentOrders' => $recentOrders,
                'lowStockProducts' => $lowStockProducts
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load dashboard stats'];
    }
}
?>
