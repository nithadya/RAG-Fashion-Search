<?php
session_start();
require_once 'functions.php';

function handleRegistration($name, $email, $password, $phone = null, $address = null, $city = null, $postal_code = null) {
    global $db;
    
    try {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $existing = $db->fetchOne($sql, [$email]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $sql = "INSERT INTO users (name, email, password, phone, address, city, postal_code) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->query($sql, [$name, $email, $hashedPassword, $phone, $address, $city, $postal_code]);
        
        if ($stmt) {
            $userId = $db->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;
            return ['success' => true, 'message' => 'Registration successful'];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during registration'];
    }
}

function handleLogin($email, $password) {
    global $db;
    
    try {
        $email = sanitizeInput($email);
        $sql = "SELECT * FROM users WHERE email = ?";
        $user = $db->fetchOne($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkAuth() {
    if (!isLoggedIn()) {
        redirect('login.html');
    }
}
?>
