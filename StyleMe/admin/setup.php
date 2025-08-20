<?php
// admin/setup.php
require_once '../includes/config.php';
require_once '../includes/db.php';

// This script should only be run once to create the first admin user
// Remove this file after creating the admin user for security

try {
    // Check if admin user already exists
    $existingAdmin = $db->fetchOne("SELECT id FROM admin_users LIMIT 1");
    
    if ($existingAdmin) {
        die("Admin user already exists. Please remove this file for security.");
    }
    
    // Create admin user
    $username = 'admin';
    $password = 'admin123'; // Change this to a secure password
    $fullName = 'System Administrator';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO admin_users (username, password, full_name, created_at) VALUES (?, ?, ?, NOW())";
    $db->query($sql, [$username, $hashedPassword, $fullName]);
    
    echo "Admin user created successfully!\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "Please change the password after first login and remove this file.\n";
    
} catch (Exception $e) {
    echo "Error creating admin user: " . $e->getMessage() . "\n";
}
?>
