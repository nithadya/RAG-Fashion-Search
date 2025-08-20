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

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    if (!isset($_SESSION['user_id'])) {
        $response = ['success' => false, 'message' => 'Please login first'];
        echo json_encode($response);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_personal':
                $response = updatePersonalInfo($userId);
                break;
                
            case 'update_address':
                $response = updateAddress($userId);
                break;
                
            case 'update_password':
                $response = updatePassword($userId);
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'stats':
                $response = getUserStats($userId);
                break;
                
            case 'profile':
                $response = getUserProfile($userId);
                break;
                
            case 'recent_orders':
                $response = getRecentOrders($userId);
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Profile API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Server error occurred'];
}

echo json_encode($response);

function updatePersonalInfo($userId) {
    global $db;
    
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($firstName)) {
        return ['success' => false, 'message' => 'First name is required'];
    }
    
    try {
        $fullName = trim($firstName . ' ' . $lastName);
        
        $sql = "UPDATE users SET 
                name = ?, 
                phone = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        
        $db->query($sql, [$fullName, $phone, $userId]);
        
        // Update session data
        $_SESSION['user_name'] = $fullName;
        
        return [
            'success' => true,
            'message' => 'Personal information updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update personal info error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update personal information'];
    }
}

function updateAddress($userId) {
    global $db;
    
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postalCode = trim($_POST['postalCode'] ?? '');
    
    try {
        $sql = "UPDATE users SET 
                address = ?, 
                city = ?, 
                postal_code = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        
        $db->query($sql, [$address, $city, $postalCode, $userId]);
        
        return [
            'success' => true,
            'message' => 'Address information updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update address error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update address information'];
    }
}

function updatePassword($userId) {
    global $db;
    
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        return ['success' => false, 'message' => 'All password fields are required'];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long'];
    }
    
    try {
        // Verify current password
        $sql = "SELECT password FROM users WHERE id = ?";
        $user = $db->fetchOne($sql, [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$hashedPassword, $userId]);
        
        return [
            'success' => true,
            'message' => 'Password updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update password'];
    }
}

function getUserStats($userId) {
    global $db;
    
    try {
        // Get order statistics
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent
                FROM orders 
                WHERE user_id = ?";
        $orderStats = $db->fetchOne($sql, [$userId]);
        
        // Get wishlist count
        $sql = "SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = ?";
        $wishlistStats = $db->fetchOne($sql, [$userId]);
        
        // Get cart count
        $sql = "SELECT COALESCE(SUM(quantity), 0) as cart_count FROM cart WHERE user_id = ?";
        $cartStats = $db->fetchOne($sql, [$userId]);
        
        $stats = [
            'total_orders' => intval($orderStats['total_orders']),
            'total_spent' => floatval($orderStats['total_spent']),
            'wishlist_count' => intval($wishlistStats['wishlist_count']),
            'cart_count' => intval($cartStats['cart_count'])
        ];
        
        return [
            'success' => true,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("Get user stats error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load user statistics'];
    }
}

function getUserProfile($userId) {
    global $db;
    
    try {
        $sql = "SELECT id, name, email, phone, address, city, postal_code, created_at FROM users WHERE id = ?";
        $user = $db->fetchOne($sql, [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        return [
            'success' => true,
            'user' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Get user profile error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load user profile'];
    }
}

function getRecentOrders($userId) {
    global $db;
    
    try {
        $sql = "SELECT 
                    id, 
                    order_number, 
                    total_amount, 
                    status, 
                    created_at 
                FROM orders 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5";
        
        $orders = $db->fetchAll($sql, [$userId]);
        
        return [
            'success' => true,
            'orders' => $orders
        ];
        
    } catch (Exception $e) {
        error_log("Get recent orders error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load recent orders'];
    }
}
?>
