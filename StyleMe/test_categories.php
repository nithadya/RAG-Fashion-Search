<?php
// Test to check if categories exist in the database
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h2>Testing Categories Database</h2>\n";

try {
    global $db;
    
    // Test database connection
    echo "<h3>Database Connection:</h3>\n";
    if ($db) {
        echo "✅ Database connected successfully<br>\n";
    } else {
        echo "❌ Database connection failed<br>\n";
        exit;
    }
    
    // Check if categories table exists
    echo "<h3>Categories Table Check:</h3>\n";
    $sql = "SHOW TABLES LIKE 'categories'";
    $result = $db->fetchAll($sql);
    
    if (empty($result)) {
        echo "❌ Categories table does not exist<br>\n";
    } else {
        echo "✅ Categories table exists<br>\n";
        
        // Check table structure
        echo "<h4>Table Structure:</h4>\n";
        $sql = "DESCRIBE categories";
        $structure = $db->fetchAll($sql);
        echo "<pre>";
        print_r($structure);
        echo "</pre>";
        
        // Count categories
        echo "<h4>Category Count:</h4>\n";
        $sql = "SELECT COUNT(*) as count FROM categories";
        $countResult = $db->fetchAll($sql);
        $count = $countResult[0]['count'] ?? 0;
        echo "Total categories: " . $count . "<br>\n";
        
        // List all categories
        if ($count > 0) {
            echo "<h4>Categories List:</h4>\n";
            $sql = "SELECT id, name, slug, description, image FROM categories ORDER BY name";
            $categories = $db->fetchAll($sql);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Description</th><th>Image</th></tr>";
            foreach ($categories as $category) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($category['id']) . "</td>";
                echo "<td>" . htmlspecialchars($category['name']) . "</td>";
                echo "<td>" . htmlspecialchars($category['slug'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($category['description'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($category['image'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ No categories found in the database<br>\n";
        }
    }
    
    // Test the API endpoint directly
    echo "<h3>Testing API Endpoint:</h3>\n";
    
    // Include the products API functions
    require_once 'api/products.php';
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "\n<h3>Test Complete</h3>\n";
?>
