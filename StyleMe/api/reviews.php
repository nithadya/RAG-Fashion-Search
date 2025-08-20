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
            case 'add':
                $response = addReview();
                break;
                
            case 'update':
                $response = updateReview();
                break;
                
            case 'delete':
                $response = deleteReview();
                break;
                
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get':
                $response = getProductReviews();
                break;
                
            case 'user_reviews':
                $response = getUserReviews();
                break;
                
            case 'stats':
                $response = getReviewStats();
                break;
                
            default:
                $response = getProductReviews();
                break;
        }
    }
} catch (Exception $e) {
    error_log("Reviews API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);

function addReview() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false,
            'message' => 'Please login to write a review',
            'redirect' => true
        ];
    }
    
    $userId = $_SESSION['user_id'];
    $productId = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $title = trim($_POST['title'] ?? '');
    
    // Validation
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product'];
    }
    
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
    }
    
    if (empty($comment)) {
        return ['success' => false, 'message' => 'Review comment is required'];
    }
    
    if (strlen($comment) < 10) {
        return ['success' => false, 'message' => 'Review must be at least 10 characters long'];
    }
    
    try {
        // Check if product exists
        $sql = "SELECT id, name FROM products WHERE id = ?";
        $product = $db->fetchOne($sql, [$productId]);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Check if user already reviewed this product
        $sql = "SELECT id FROM reviews WHERE user_id = ? AND product_id = ?";
        $existing = $db->fetchOne($sql, [$userId, $productId]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'You have already reviewed this product'];
        }
        
        // Optional: Check if user actually purchased this product
        $sql = "SELECT COUNT(*) as count FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'";
        $purchaseCheck = $db->fetchOne($sql, [$userId, $productId]);
        
        if ($purchaseCheck['count'] == 0) {
            return ['success' => false, 'message' => 'You can only review products you have purchased'];
        }
        
        // Add review
        $sql = "INSERT INTO reviews (user_id, product_id, rating, title, comment, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $db->query($sql, [$userId, $productId, $rating, $title, $comment]);
        
        // Update product average rating
        updateProductRating($productId);
        
        return [
            'success' => true,
            'message' => 'Review added successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Add review error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add review'];
    }
}

function updateReview() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    $reviewId = intval($_POST['review_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $title = trim($_POST['title'] ?? '');
    
    // Validation
    if ($reviewId <= 0) {
        return ['success' => false, 'message' => 'Invalid review'];
    }
    
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
    }
    
    if (empty($comment)) {
        return ['success' => false, 'message' => 'Review comment is required'];
    }
    
    try {
        // Verify review belongs to user
        $sql = "SELECT product_id FROM reviews WHERE id = ? AND user_id = ?";
        $review = $db->fetchOne($sql, [$reviewId, $userId]);
        
        if (!$review) {
            return ['success' => false, 'message' => 'Review not found or access denied'];
        }
        
        // Update review
        $sql = "UPDATE reviews SET rating = ?, title = ?, comment = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?";
        $db->query($sql, [$rating, $title, $comment, $reviewId, $userId]);
        
        // Update product average rating
        updateProductRating($review['product_id']);
        
        return [
            'success' => true,
            'message' => 'Review updated successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Update review error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update review'];
    }
}

function deleteReview() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    $reviewId = intval($_POST['review_id'] ?? 0);
    
    if ($reviewId <= 0) {
        return ['success' => false, 'message' => 'Invalid review'];
    }
    
    try {
        // Verify review belongs to user
        $sql = "SELECT product_id FROM reviews WHERE id = ? AND user_id = ?";
        $review = $db->fetchOne($sql, [$reviewId, $userId]);
        
        if (!$review) {
            return ['success' => false, 'message' => 'Review not found or access denied'];
        }
        
        // Delete review
        $sql = "DELETE FROM reviews WHERE id = ? AND user_id = ?";
        $db->query($sql, [$reviewId, $userId]);
        
        // Update product average rating
        updateProductRating($review['product_id']);
        
        return [
            'success' => true,
            'message' => 'Review deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Delete review error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete review'];
    }
}

function getProductReviews() {
    global $db;
    
    $productId = intval($_GET['product_id'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product'];
    }
    
    try {
        // Get reviews with user info
        $sql = "SELECT r.id, r.rating, r.title, r.comment, r.created_at, r.updated_at,
                       u.name as user_name
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";
        
        $reviews = $db->fetchAll($sql, [$productId, $limit, $offset]);
        
        // Get total count
        $sql = "SELECT COUNT(*) as total FROM reviews WHERE product_id = ?";
        $totalResult = $db->fetchOne($sql, [$productId]);
        $total = intval($totalResult['total']);
        
        // Get rating breakdown
        $sql = "SELECT rating, COUNT(*) as count 
                FROM reviews 
                WHERE product_id = ? 
                GROUP BY rating 
                ORDER BY rating DESC";
        $ratingBreakdown = $db->fetchAll($sql, [$productId]);
        
        // Calculate average rating
        $sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?";
        $avgResult = $db->fetchOne($sql, [$productId]);
        $avgRating = round(floatval($avgResult['avg_rating']), 1);
        
        return [
            'success' => true,
            'reviews' => $reviews,
            'total_reviews' => $total,
            'average_rating' => $avgRating,
            'rating_breakdown' => $ratingBreakdown,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'per_page' => $limit
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Get product reviews error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load reviews'];
    }
}

function getUserReviews() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $sql = "SELECT r.id, r.rating, r.title, r.comment, r.created_at,
                       p.id as product_id, p.name as product_name, p.image1
                FROM reviews r
                JOIN products p ON r.product_id = p.id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC";
        
        $reviews = $db->fetchAll($sql, [$userId]);
        
        return [
            'success' => true,
            'reviews' => $reviews
        ];
        
    } catch (Exception $e) {
        error_log("Get user reviews error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load your reviews'];
    }
}

function getReviewStats() {
    global $db;
    
    $productId = intval($_GET['product_id'] ?? 0);
    
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product'];
    }
    
    try {
        // Get review statistics
        $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM reviews 
                WHERE product_id = ?";
        
        $stats = $db->fetchOne($sql, [$productId]);
        
        $stats['average_rating'] = round(floatval($stats['average_rating']), 1);
        $stats['total_reviews'] = intval($stats['total_reviews']);
        
        return [
            'success' => true,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("Get review stats error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load review statistics'];
    }
}

function updateProductRating($productId) {
    global $db;
    
    try {
        // Calculate new average rating
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                FROM reviews WHERE product_id = ?";
        $result = $db->fetchOne($sql, [$productId]);
        
        $avgRating = round(floatval($result['avg_rating']), 1);
        $reviewCount = intval($result['review_count']);
        
        // Update product table
        $sql = "UPDATE products SET average_rating = ?, review_count = ? WHERE id = ?";
        $db->query($sql, [$avgRating, $reviewCount, $productId]);
        
    } catch (Exception $e) {
        error_log("Update product rating error: " . $e->getMessage());
    }
}
?>
