<?php
// admin/api/admin_auth.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $response = adminLogin();
                break;
                
            case 'logout':
                $response = adminLogout();
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'check':
                $response = checkAdminAuth();
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Admin Auth API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Authentication error occurred'];
}

echo json_encode($response);

function adminLogin() {
    global $db;
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required'];
    }
    
    try {
        $sql = "SELECT id, username, password, full_name FROM admin_users WHERE username = ?";
        $admin = $db->fetchOne($sql, [$username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'name' => $admin['full_name']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed'];
    }
}

function adminLogout() {
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

function checkAdminAuth() {
    if (isset($_SESSION['admin_id'])) {
        return [
            'success' => true,
            'loggedIn' => true,
            'admin' => [
                'id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username'],
                'name' => $_SESSION['admin_name']
            ]
        ];
    }
    
    return [
        'success' => true,
        'loggedIn' => false
    ];
}
?>
