<?php
// Prevent any output before headers - Critical Fix #1
ob_start();

// Set proper error handling - Critical Fix #2
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Set headers first
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

// Clear any unwanted output - Critical Fix #3
$unwanted_output = ob_get_clean();
if (!empty($unwanted_output)) {
    error_log("Unwanted output in admin.php: " . $unwanted_output);
}

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_product':
                $response = addProduct();
                break;
            case 'update_product':
                $response = updateProduct();
                break;
            case 'delete_product':
                $response = deleteProduct();
                break;
            case 'add_category':
                $response = addCategory();
                break;
            case 'update_category':
                $response = updateCategory();
                break;
            case 'delete_category':
                $response = deleteCategory();
                break;
            case 'update_order_status':
                $response = updateOrderStatus();
                break;
            case 'add_user':
                $response = addUser();
                break;
            case 'update_user':
                $response = updateUser();
                break;
            case 'delete_user':
                $response = deleteUser();
                break;
            case 'toggle_user_status':
                $response = toggleUserStatus();
                break;
            case 'reset_user_password':
                $response = resetUserPassword();
                break;    
            case 'delete_feedback':
                $response = deleteFeedback();
                break;
            default:
                $response['message'] = 'Invalid action: ' . $action;
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'dashboard_stats':
                $response = getDashboardStats();
                break;
            case 'recent_orders':
                $response = getRecentOrders();
                break;
            case 'get_products':
                $response = getProducts();
                break;
            case 'get_product':
                $response = getProduct();
                break;
            case 'get_categories':
                $response = getCategories();
                break;
            case 'get_category':
                $response = getCategory();
                break;
            case 'get_orders':
                $response = getOrders();
                break;
            case 'get_order_details':
                $response = getOrderDetails();
                break;
            case 'get_users':
                $response = getUsers();
                break;
            case 'get_user_details':
                $response = getUserDetails();
                break;
            case 'get_user':
                $response = getUser();
                break;    
            case 'get_feedback':
                $response = getFeedback();
                break;
            default:
                $response['message'] = 'Invalid action: ' . $action;
                break;
        }
    }
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Server error occurred'];
}

// Ensure clean JSON output - Critical Fix #4
ob_clean();
echo json_encode($response);
exit;

// ============================================================================
// AUTHENTICATION FUNCTIONS
// ============================================================================

function checkAdminAuth() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        return false;
    }
    return true;
}

// ============================================================================
// SIMPLIFIED IMAGE UPLOAD FUNCTION - Critical Fix #5
// ============================================================================

function handleImageUpload($files, $type = 'product') {
    $uploadDir = '../assets/uploads/';
    $uploadedFiles = [];
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }
    
    // Handle single file upload (for categories)
    if (isset($files['tmp_name']) && !is_array($files['tmp_name'])) {
        $files = [
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'name' => [$files['name']],
            'size' => [$files['size']]
        ];
    }
    
    foreach ($files['tmp_name'] as $key => $tmpName) {
        if ($tmpName && $files['error'][$key] === UPLOAD_ERR_OK) {
            // Validate file size (5MB max)
            if ($files['size'][$key] > 5 * 1024 * 1024) {
                throw new Exception("File too large. Maximum size is 5MB.");
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $extension = strtolower(pathinfo($files['name'][$key], PATHINFO_EXTENSION));
            
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }
            
            // Generate simple filename
            $filename = $type . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Simply move the file
            if (move_uploaded_file($tmpName, $filepath)) {
                $uploadedFiles[] = $filename;
            } else {
                throw new Exception("Failed to upload file");
            }
        } else {
            $uploadedFiles[] = null;
        }
    }
    
    return $uploadedFiles;
}

// ============================================================================
// DASHBOARD FUNCTIONS
// ============================================================================

function getDashboardStats() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT COUNT(*) as count FROM products";
        $totalProducts = $db->fetchOne($sql)['count'];
        
        $sql = "SELECT COUNT(*) as count FROM orders";
        $totalOrders = $db->fetchOne($sql)['count'];
        
        $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
        $totalUsers = $db->fetchOne($sql)['count'];
        
        $sql = "SELECT COUNT(*) as count FROM categories";
        $totalCategories = $db->fetchOne($sql)['count'];
        
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status != 'Cancelled'";
        $totalRevenue = $db->fetchOne($sql)['revenue'];
        
        $sql = "SELECT COUNT(*) as count FROM feedback";
        $totalFeedback = $db->fetchOne($sql)['count'];
        
        return [
            'success' => true,
            'stats' => [
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'total_users' => $totalUsers,
                'total_categories' => $totalCategories,
                'total_revenue' => $totalRevenue,
                'total_feedback' => $totalFeedback
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load dashboard stats'];
    }
}

function getRecentOrders() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC 
                LIMIT 10";
        $orders = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'orders' => $orders
        ];
        
    } catch (Exception $e) {
        error_log("Get recent orders error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load recent orders'];
    }
}

// ============================================================================
// PRODUCT MANAGEMENT FUNCTIONS
// ============================================================================

function getProducts() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created_at DESC";
        $products = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'products' => $products
        ];
        
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load products'];
    }
}

function getProduct() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID'];
    }
    
    try {
        $sql = "SELECT * FROM products WHERE id = ?";
        $product = $db->fetchOne($sql, [$id]);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        return [
            'success' => true,
            'product' => $product
        ];
        
    } catch (Exception $e) {
        error_log("Get product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load product'];
    }
}

function addProduct() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $name = trim($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $discountPrice = floatval($_POST['discount_price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $size = trim($_POST['size'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $occasion = trim($_POST['occasion'] ?? '');
        $gender = $_POST['gender'] ?? 'Unisex';
        
        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            return ['success' => false, 'message' => 'Please fill all required fields (name, category, price)'];
        }
        
        // Handle image uploads
        $images = ['', '', ''];
        if (isset($_FILES['image_files']) && !empty($_FILES['image_files']['tmp_name'][0])) {
            try {
                $uploadedImages = handleImageUpload($_FILES['image_files'], 'product');
                $images = array_pad($uploadedImages, 3, '');
            } catch (Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        if (empty($images[0])) {
            return ['success' => false, 'message' => 'At least one product image is required'];
        }
        
        $slug = generateSlug($name);
        
        // Check if slug already exists
        $sql = "SELECT id FROM products WHERE slug = ?";
        $existing = $db->fetchOne($sql, [$slug]);
        if ($existing) {
            $slug = $slug . '-' . time();
        }
        
        $sql = "INSERT INTO products (category_id, name, slug, description, price, discount_price, 
                size, color, brand, occasion, gender, stock, image1, image2, image3, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $db->query($sql, [
            $categoryId, $name, $slug, $description, $price, 
            $discountPrice > 0 ? $discountPrice : null,
            $size, $color, $brand, $occasion, $gender, $stock, 
            $images[0], $images[1], $images[2]
        ]);
        
        return ['success' => true, 'message' => 'Product added successfully'];
        
    } catch (Exception $e) {
        error_log("Add product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()];
    }
}

function updateProduct() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $id = intval($_POST['productId'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $discountPrice = floatval($_POST['discount_price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $size = trim($_POST['size'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $occasion = trim($_POST['occasion'] ?? '');
        $gender = $_POST['gender'] ?? 'Unisex';
        
        if ($id <= 0 || empty($name) || $categoryId <= 0 || $price <= 0) {
            return ['success' => false, 'message' => 'Please fill all required fields'];
        }
        
        // Get current product data
        $sql = "SELECT image1, image2, image3 FROM products WHERE id = ?";
        $currentProduct = $db->fetchOne($sql, [$id]);
        
        if (!$currentProduct) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        $images = [$currentProduct['image1'], $currentProduct['image2'], $currentProduct['image3']];
        
        // Handle new image uploads
        if (isset($_FILES['image_files'])) {
            try {
                $uploadedImages = handleImageUpload($_FILES['image_files'], 'product');
                
                // Replace images only if new ones are uploaded
                foreach ($uploadedImages as $key => $newImage) {
                    if ($newImage) {
                        // Delete old image if exists
                        if ($images[$key] && file_exists('../assets/uploads/' . $images[$key])) {
                            unlink('../assets/uploads/' . $images[$key]);
                        }
                        $images[$key] = $newImage;
                    }
                }
            } catch (Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        $slug = generateSlug($name);
        
        // Check if slug already exists for other products
        $sql = "SELECT id FROM products WHERE slug = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$slug, $id]);
        if ($existing) {
            $slug = $slug . '-' . time();
        }
        
        $sql = "UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, 
                price = ?, discount_price = ?, size = ?, color = ?, brand = ?, occasion = ?, 
                gender = ?, stock = ?, image1 = ?, image2 = ?, image3 = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $db->query($sql, [
            $categoryId, $name, $slug, $description, $price, 
            $discountPrice > 0 ? $discountPrice : null,
            $size, $color, $brand, $occasion, $gender, $stock, 
            $images[0], $images[1], $images[2], $id
        ]);
        
        return ['success' => true, 'message' => 'Product updated successfully'];
        
    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update product: ' . $e->getMessage()];
    }
}

function deleteProduct() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID'];
    }
    
    try {
        // Get product images before deletion
        $sql = "SELECT image1, image2, image3 FROM products WHERE id = ?";
        $product = $db->fetchOne($sql, [$id]);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Check if product is in any orders
        $sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
        $orderCount = $db->fetchOne($sql, [$id])['count'];
        
        if ($orderCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete product that has been ordered'];
        }
        
        // Delete product
        $sql = "DELETE FROM products WHERE id = ?";
        $db->query($sql, [$id]);
        
        // Delete associated images
        $images = [$product['image1'], $product['image2'], $product['image3']];
        foreach ($images as $image) {
            if ($image && file_exists('../assets/uploads/' . $image)) {
                unlink('../assets/uploads/' . $image);
            }
        }
        
        return ['success' => true, 'message' => 'Product deleted successfully'];
        
    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete product'];
    }
}

// ============================================================================
// CATEGORY MANAGEMENT FUNCTIONS
// ============================================================================

function getCategories() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id 
                GROUP BY c.id 
                ORDER BY c.name";
        $categories = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'categories' => $categories
        ];
        
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load categories'];
    }
}

function getCategory() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid category ID'];
    }
    
    try {
        $sql = "SELECT * FROM categories WHERE id = ?";
        $category = $db->fetchOne($sql, [$id]);
        
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        return [
            'success' => true,
            'category' => $category
        ];
        
    } catch (Exception $e) {
        error_log("Get category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load category'];
    }
}

function addCategory() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name) || empty($slug)) {
            return ['success' => false, 'message' => 'Name and slug are required'];
        }
        
        // Handle image upload
        $image = '';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadedImages = handleImageUpload($_FILES['image_file'], 'category');
                $image = $uploadedImages[0] ?? '';
            } catch (Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        // Check if slug already exists
        $sql = "SELECT id FROM categories WHERE slug = ?";
        $existing = $db->fetchOne($sql, [$slug]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'Slug already exists'];
        }
        
        $sql = "INSERT INTO categories (name, slug, description, image, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $db->query($sql, [$name, $slug, $description, $image]);
        
        return ['success' => true, 'message' => 'Category added successfully'];
        
    } catch (Exception $e) {
        error_log("Add category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add category: ' . $e->getMessage()];
    }
}

function updateCategory() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $id = intval($_POST['categoryId'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($id <= 0 || empty($name) || empty($slug)) {
            return ['success' => false, 'message' => 'ID, name and slug are required'];
        }
        
        // Get current category data
        $sql = "SELECT image FROM categories WHERE id = ?";
        $currentCategory = $db->fetchOne($sql, [$id]);
        
        if (!$currentCategory) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        $image = $currentCategory['image'];
        
        // Handle new image upload
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadedImages = handleImageUpload($_FILES['image_file'], 'category');
                if ($uploadedImages[0]) {
                    // Delete old image if exists
                    if ($image && file_exists('../assets/uploads/' . $image)) {
                        unlink('../assets/uploads/' . $image);
                    }
                    $image = $uploadedImages[0];
                }
            } catch (Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        // Check if slug already exists for other categories
        $sql = "SELECT id FROM categories WHERE slug = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$slug, $id]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'Slug already exists'];
        }
        
        $sql = "UPDATE categories SET name = ?, slug = ?, description = ?, image = ? WHERE id = ?";
        $db->query($sql, [$name, $slug, $description, $image, $id]);
        
        return ['success' => true, 'message' => 'Category updated successfully'];
        
    } catch (Exception $e) {
        error_log("Update category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update category: ' . $e->getMessage()];
    }
}

function deleteCategory() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid category ID'];
    }
    
    try {
        // Check if category has products
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $productCount = $db->fetchOne($sql, [$id])['count'];
        
        if ($productCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete category with existing products'];
        }
        
        // Get category image before deletion
        $sql = "SELECT image FROM categories WHERE id = ?";
        $category = $db->fetchOne($sql, [$id]);
        
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        // Delete category
        $sql = "DELETE FROM categories WHERE id = ?";
        $db->query($sql, [$id]);
        
        // Delete associated image
        if ($category['image'] && file_exists('../assets/uploads/' . $category['image'])) {
            unlink('../assets/uploads/' . $category['image']);
        }
        
        return ['success' => true, 'message' => 'Category deleted successfully'];
        
    } catch (Exception $e) {
        error_log("Delete category error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete category'];
    }
}

// ============================================================================
// ORDER MANAGEMENT FUNCTIONS
// ============================================================================

function getOrders() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email, COUNT(oi.id) as item_count
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN order_items oi ON o.id = oi.order_id
                GROUP BY o.id
                ORDER BY o.created_at DESC";
        $orders = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'orders' => $orders
        ];
        
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load orders'];
    }
}

function getOrderDetails() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid order ID'];
    }
    
    try {
        // Get order details
        $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
        $order = $db->fetchOne($sql, [$id]);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Get order items
        $sql = "SELECT oi.*, p.name as product_name, p.image1 as image
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
        $items = $db->fetchAll($sql, [$id]);
        
        $order['items'] = $items;
        
        return [
            'success' => true,
            'order' => $order
        ];
        
    } catch (Exception $e) {
        error_log("Get order details error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load order details'];
    }
}

function updateOrderStatus() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $orderId = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    
    if ($orderId <= 0 || !in_array($status, $validStatuses)) {
        return ['success' => false, 'message' => 'Invalid order ID or status'];
    }
    
    try {
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$status, $orderId]);
        
        return ['success' => true, 'message' => 'Order status updated successfully'];
        
    } catch (Exception $e) {
        error_log("Update order status error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update order status'];
    }
}

// ============================================================================
// ENHANCED USER MANAGEMENT FUNCTIONS
// ============================================================================

function getUsers() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT u.*, 
                       COUNT(DISTINCT o.id) as order_count, 
                       COUNT(DISTINCT w.id) as wishlist_count,
                       COUNT(DISTINCT c.id) as cart_count,
                       COALESCE(SUM(o.total_amount), 0) as total_spent,
                       MAX(o.created_at) as last_order_date
                FROM users u 
                LEFT JOIN orders o ON u.id = o.user_id
                LEFT JOIN wishlist w ON u.id = w.user_id
                LEFT JOIN cart c ON u.id = c.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        $users = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'users' => $users
        ];
        
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load users'];
    }
}

function getUser() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }
    
    try {
        $sql = "SELECT u.*, 
                       COUNT(DISTINCT o.id) as order_count, 
                       COUNT(DISTINCT w.id) as wishlist_count,
                       COUNT(DISTINCT c.id) as cart_count,
                       COALESCE(SUM(o.total_amount), 0) as total_spent,
                       MAX(o.created_at) as last_order_date
                FROM users u 
                LEFT JOIN orders o ON u.id = o.user_id
                LEFT JOIN wishlist w ON u.id = w.user_id
                LEFT JOIN cart c ON u.id = c.user_id
                WHERE u.id = ?
                GROUP BY u.id";
        $user = $db->fetchOne($sql, [$id]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        return [
            'success' => true,
            'user' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load user'];
    }
}

function addUser() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        
        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Name, email and password are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        if (!in_array($role, ['user', 'admin'])) {
            return ['success' => false, 'message' => 'Invalid role'];
        }
        
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $existing = $db->fetchOne($sql, [$email]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, role, phone, address, city, postal_code, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $db->query($sql, [$name, $email, $hashedPassword, $role, $phone, $address, $city, $postalCode]);
        
        return ['success' => true, 'message' => 'User added successfully'];
        
    } catch (Exception $e) {
        error_log("Add user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add user: ' . $e->getMessage()];
    }
}

function updateUser() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $id = intval($_POST['userId'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        
        if ($id <= 0 || empty($name) || empty($email)) {
            return ['success' => false, 'message' => 'ID, name and email are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (!in_array($role, ['user', 'admin'])) {
            return ['success' => false, 'message' => 'Invalid role'];
        }
        
        // Check if user exists
        $sql = "SELECT id FROM users WHERE id = ?";
        $user = $db->fetchOne($sql, [$id]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Check if email already exists for other users
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$email, $id]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Update user
        if (!empty($password)) {
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, email = ?, password = ?, role = ?, phone = ?, 
                    address = ?, city = ?, postal_code = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$name, $email, $hashedPassword, $role, $phone, $address, $city, $postalCode, $id]);
        } else {
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, phone = ?, 
                    address = ?, city = ?, postal_code = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$name, $email, $role, $phone, $address, $city, $postalCode, $id]);
        }
        
        return ['success' => true, 'message' => 'User updated successfully'];
        
    } catch (Exception $e) {
        error_log("Update user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()];
    }
}

function deleteUser() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }
    
    try {
        // Check if user exists
        $sql = "SELECT id, role FROM users WHERE id = ?";
        $user = $db->fetchOne($sql, [$id]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Prevent deleting the current admin
        if ($user['id'] == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Cannot delete your own account'];
        }
        
        // Check if user has orders
        $sql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
        $orderCount = $db->fetchOne($sql, [$id])['count'];
        
        if ($orderCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete user with existing orders. Consider deactivating instead.'];
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Delete related records
            $sql = "DELETE FROM cart WHERE user_id = ?";
            $db->query($sql, [$id]);
            
            $sql = "DELETE FROM wishlist WHERE user_id = ?";
            $db->query($sql, [$id]);
            
            $sql = "DELETE FROM feedback WHERE user_id = ?";
            $db->query($sql, [$id]);
            
            // Delete user
            $sql = "DELETE FROM users WHERE id = ?";
            $db->query($sql, [$id]);
            
            $db->commit();
            
            return ['success' => true, 'message' => 'User deleted successfully'];
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete user'];
    }
}

function toggleUserStatus() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    if ($id <= 0 || !in_array($status, ['active', 'inactive'])) {
        return ['success' => false, 'message' => 'Invalid parameters'];
    }
    
    try {
        // Add status column if it doesn't exist
        $sql = "SHOW COLUMNS FROM users LIKE 'status'";
        $statusColumn = $db->fetchOne($sql);
        
        if (!$statusColumn) {
            $sql = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
            $db->query($sql);
        }
        
        $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$status, $id]);
        
        return ['success' => true, 'message' => 'User status updated successfully'];
        
    } catch (Exception $e) {
        error_log("Toggle user status error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update user status'];
    }
}

function resetUserPassword() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    
    if ($id <= 0 || empty($newPassword)) {
        return ['success' => false, 'message' => 'User ID and new password are required'];
    }
    
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$hashedPassword, $id]);
        
        return ['success' => true, 'message' => 'Password reset successfully'];
        
    } catch (Exception $e) {
        error_log("Reset user password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset password'];
    }
}

// ============================================================================
// FEEDBACK MANAGEMENT FUNCTIONS
// ============================================================================

function getFeedback() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    try {
        $sql = "SELECT f.*, u.name as user_name, u.email as user_email
                FROM feedback f 
                LEFT JOIN users u ON f.user_id = u.id
                ORDER BY f.created_at DESC";
        $feedback = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'feedback' => $feedback
        ];
        
    } catch (Exception $e) {
        error_log("Get feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load feedback'];
    }
}

function deleteFeedback() {
    if (!checkAdminAuth()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Invalid feedback ID'];
    }
    
    try {
        $sql = "DELETE FROM feedback WHERE id = ?";
        $db->query($sql, [$id]);
        
        return ['success' => true, 'message' => 'Feedback deleted successfully'];
        
    } catch (Exception $e) {
        error_log("Delete feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete feedback'];
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
