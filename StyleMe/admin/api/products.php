<?php
// admin/api/products.php
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
                $response = getProducts();
                break;
            case 'get':
                $id = $_GET['id'] ?? 0;
                $response = getProduct($id);
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $response = createProduct();
                break;
            case 'update':
                $response = updateProduct();
                break;
            case 'delete':
                $response = deleteProduct();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);

function getProducts() {
    global $db;
    
    try {
        $sql = "
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.created_at DESC
        ";
        
        $products = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'data' => $products
        ];
        
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load products'];
    }
}

function getProduct($id) {
    global $db;
    
    try {
        $sql = "SELECT * FROM products WHERE id = ?";
        $product = $db->fetchOne($sql, [$id]);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        return [
            'success' => true,
            'data' => $product
        ];
        
    } catch (Exception $e) {
        error_log("Get product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load product'];
    }
}

function createProduct() {
    global $db;
    
    try {
        $name = sanitizeAdminInput($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = sanitizeAdminInput($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $size = sanitizeAdminInput($_POST['size'] ?? '');
        $color = sanitizeAdminInput($_POST['color'] ?? '');
        $brand = sanitizeAdminInput($_POST['brand'] ?? '');
        $occasion = sanitizeAdminInput($_POST['occasion'] ?? '');
        $gender = sanitizeAdminInput($_POST['gender'] ?? 'Unisex');
        $stock = (int)($_POST['stock'] ?? 0);
        $image1 = sanitizeAdminInput($_POST['image1'] ?? '');
        $image2 = sanitizeAdminInput($_POST['image2'] ?? '');
        $image3 = sanitizeAdminInput($_POST['image3'] ?? '');
        
        if (empty($name) || empty($image1) || $price <= 0) {
            return ['success' => false, 'message' => 'Name, image, and price are required'];
        }
        
        $slug = generateSlug($name);
        
        // Check if slug exists
        $existingSlug = $db->fetchOne("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existingSlug) {
            $slug .= '-' . time();
        }
        
        $sql = "
            INSERT INTO products (
                category_id, name, slug, description, price, discount_price,
                size, color, brand, occasion, gender, stock,
                image1, image2, image3, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        
        $db->query($sql, [
            $categoryId, $name, $slug, $description, $price, $discountPrice,
            $size, $color, $brand, $occasion, $gender, $stock,
            $image1, $image2, $image3
        ]);
        
        logAdminActivity('CREATE_PRODUCT', "Created product: $name");
        
        return [
            'success' => true,
            'message' => 'Product created successfully',
            'id' => $db->lastInsertId()
        ];
        
    } catch (Exception $e) {
        error_log("Create product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create product'];
    }
}

function updateProduct() {
    global $db;
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitizeAdminInput($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = sanitizeAdminInput($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $size = sanitizeAdminInput($_POST['size'] ?? '');
        $color = sanitizeAdminInput($_POST['color'] ?? '');
        $brand = sanitizeAdminInput($_POST['brand'] ?? '');
        $occasion = sanitizeAdminInput($_POST['occasion'] ?? '');
        $gender = sanitizeAdminInput($_POST['gender'] ?? 'Unisex');
        $stock = (int)($_POST['stock'] ?? 0);
        $image1 = sanitizeAdminInput($_POST['image1'] ?? '');
        $image2 = sanitizeAdminInput($_POST['image2'] ?? '');
        $image3 = sanitizeAdminInput($_POST['image3'] ?? '');
        
        if (!$id || empty($name) || empty($image1) || $price <= 0) {
            return ['success' => false, 'message' => 'Invalid product data'];
        }
        
        // Check if product exists
        $existing = $db->fetchOne("SELECT id FROM products WHERE id = ?", [$id]);
        if (!$existing) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        $sql = "
            UPDATE products SET
                category_id = ?, name = ?, description = ?, price = ?, discount_price = ?,
                size = ?, color = ?, brand = ?, occasion = ?, gender = ?, stock = ?,
                image1 = ?, image2 = ?, image3 = ?, updated_at = NOW()
            WHERE id = ?
        ";
        
        $db->query($sql, [
            $categoryId, $name, $description, $price, $discountPrice,
            $size, $color, $brand, $occasion, $gender, $stock,
            $image1, $image2, $image3, $id
        ]);
        
        logAdminActivity('UPDATE_PRODUCT', "Updated product: $name (ID: $id)");
        
        return [
            'success' => true,
            'message' => 'Product updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update product'];
    }
}

function deleteProduct() {
    global $db;
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            return ['success' => false, 'message' => 'Product ID is required'];
        }
        
        // Check if product exists
        $product = $db->fetchOne("SELECT name FROM products WHERE id = ?", [$id]);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Check if product is in any orders
        $orderCount = $db->fetchOne("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?", [$id])['count'];
        if ($orderCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete product that has been ordered'];
        }
        
        $db->query("DELETE FROM products WHERE id = ?", [$id]);
        
        logAdminActivity('DELETE_PRODUCT', "Deleted product: {$product['name']} (ID: $id)");
        
        return [
            'success' => true,
            'message' => 'Product deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete product'];
    }
}
?>
