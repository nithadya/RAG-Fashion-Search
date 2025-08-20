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
            case 'login':
                $response = loginUser();
                break;
            case 'register':
                $response = registerUser();
                break;
            case 'logout':
                $response = logoutUser();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'check':
                $response = checkAuthStatus();
                break;
            case 'get_user_role':
                $response = getUserRole();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Authentication error occurred'];
}

echo json_encode($response);

function checkAuthStatus() {
    if (isset($_SESSION['user_id'])) {
        global $db;
        
        try {
            $sql = "SELECT id, name, email, role, phone, address, city, postal_code FROM users WHERE id = ?";
            $user = $db->fetchOne($sql, [$_SESSION['user_id']]);
            
            if ($user) {
                return [
                    'success' => true,
                    'loggedIn' => true,
                    'user' => $user
                ];
            }
        } catch (Exception $e) {
            error_log("Check auth status error: " . $e->getMessage());
        }
    }
    
    return [
        'success' => true,
        'loggedIn' => false
    ];
}

function getUserRole() {
    $userId = intval($_GET['user_id'] ?? 0);
    
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT role FROM users WHERE id = ?";
        $user = $db->fetchOne($sql, [$userId]);
        
        if ($user) {
            return [
                'success' => true,
                'role' => $user['role']
            ];
        }
        
        return ['success' => false, 'message' => 'User not found'];
        
    } catch (Exception $e) {
        error_log("Get user role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to get user role'];
    }
}

function loginUser() {
    global $db;
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }
    
    try {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $user = $db->fetchOne($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Set admin session if user is admin
            if ($user['role'] === 'admin') {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['email'];
                $_SESSION['admin_name'] = $user['name'];
            }
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed'];
    }
}

function registerUser() {
    global $db;
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Name, email and password are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    try {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $existing = $db->fetchOne($sql, [$email]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user (default role is 'user')
        $sql = "INSERT INTO users (name, email, password, phone, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())";
        $db->query($sql, [$name, $email, $hashedPassword, $phone]);
        
        $userId = $db->lastInsertId();
        
        // Auto login after registration
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'user';
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => 'user'
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

function logoutUser() {
    try {
        // Clear all session data
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
        
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Logout failed'];
    }
}

?>
