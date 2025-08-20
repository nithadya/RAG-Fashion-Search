<?php
// admin/api/categories.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
                $response = getCategories();
                break;
            case 'get':
                $id = $_GET['id'] ?? 0;
                $response = getCategory($id);
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $response = createCategory();
                break;
            case 'update':
                $response = updateCategory();
                break;
            case 'delete':
                $response = deleteCategory();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Categories API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);

function getCategories() {
    global $db;
    
    try {
        $sql = "
            SELECT c.*, COUNT(p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ";
        
        $categories = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'data' => $categories
        ];
        
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load categories'];
    }
}

function getCategory($id) {
    global $db;
    
    try {
        $sql = "SELECT * FROM categories WHERE id = ?";
        $category = $db->fetchOne($sql, [$id]);
        
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        return [
            'success' => true,
            'data' => $category
        ];
        
    } catch (Exception $e) {
        error_log("Get category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load category'];
    }
}

function createCategory() {
    global $db;
    
    try {
        $name = sanitizeAdminInput($_POST['name'] ?? '');
        $description = sanitizeAdminInput($_POST['description'] ?? '');
        $image = sanitizeAdminInput($_POST['image'] ?? '');
        
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required'];
        }
        
        $slug = generateSlug($name);
        
        // Check if slug exists
        $existingSlug = $db->fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
        if ($existingSlug) {
            $slug .= '-' . time();
        }
        
        $sql = "INSERT INTO categories (name, slug, description, image, created_at) VALUES (?, ?, ?, ?, NOW())";
        $db->query($sql, [$name, $slug, $description, $image]);
        
        logAdminActivity('CREATE_CATEGORY', "Created category: $name");
        
        return [
            'success' => true,
            'message' => 'Category created successfully',
            'id' => $db->lastInsertId()
        ];
        
    } catch (Exception $e) {
        error_log("Create category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create category'];
    }
}

function updateCategory() {
    global $db;
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitizeAdminInput($_POST['name'] ?? '');
        $description = sanitizeAdminInput($_POST['description'] ?? '');
        $image = sanitizeAdminInput($_POST['image'] ?? '');
        
        if (!$id || empty($name)) {
            return ['success' => false, 'message' => 'Invalid category data'];
        }
        
        // Check if category exists
        $existing = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [$id]);
        if (!$existing) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        $sql = "UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?";
        $db->query($sql, [$name, $description, $image, $id]);
        
        logAdminActivity('UPDATE_CATEGORY', "Updated category: $name (ID: $id)");
        
        return [
            'success' => true,
            'message' => 'Category updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update category'];
    }
}

function deleteCategory() {
    global $db;
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            return ['success' => false, 'message' => 'Category ID is required'];
        }
        
        // Check if category exists
        $category = $db->fetchOne("SELECT name FROM categories WHERE id = ?", [$id]);
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        // Check if category has products
        $productCount = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$id])['count'];
        if ($productCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete category that has products'];
        }
        
        $db->query("DELETE FROM categories WHERE id = ?", [$id]);
        
        logAdminActivity('DELETE_CATEGORY', "Deleted category: {$category['name']} (ID: $id)");
        
        return [
            'success' => true,
            'message' => 'Category deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Delete category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete category'];
    }
}
?>
