<?php
require_once 'db.php';
require_once 'config.php';

function sanitizeInput($data) {
    global $db;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $db->getConnection()->real_escape_string($data);
}

function redirect($url) {
    header("Location: " . BASE_URL . '/' . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function generateOrderNumber() {
    return 'ORD-' . strtoupper(uniqid());
}

function getProductImageUrl($image) {
    return BASE_URL . '/assets/uploads/' . $image;
}

function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}

// AI Search Enhancement Function
function enhanceSearchQuery($query) {
    // In a real implementation, this would call OpenAI API
    // For demo purposes, we'll just return some enhanced keywords
    $enhanced = strtolower($query);
    $enhanced = preg_replace('/\s+/', ' ', $enhanced); // Remove extra spaces
    
    // Add some common enhancements for Sri Lankan context
    if (strpos($enhanced, 'dress') !== false) {
        $enhanced .= ' women';
    } elseif (strpos($enhanced, 'shirt') !== false) {
        $enhanced .= ' men';
    }
    
    return $enhanced;
}


function getChatbotResponse($message) {
    $message = strtolower(trim($message));
    
    $responses = [
        'hi|hello|hey' => 'Hello! How can I help you today?',
        'how are you' => 'I\'m just a bot, but thanks for asking! How can I assist you?',
        'products|items' => 'We have a wide range of products. You can browse by category or use the search bar to find specific items.',
        'price|cost' => 'Prices vary by product. Please check the product details page for accurate pricing.',
        'delivery|shipping' => 'We offer islandwide delivery in Sri Lanka. Delivery times are typically 3-5 working days.',
        'return|exchange' => 'We have a 7-day return policy for unused items with original tags.',
        'contact|help' => 'You can reach our customer service at 0112 345 678 or email support@ecommerce.lk',
        'default' => 'I\'m sorry, I didn\'t understand that. Could you please rephrase your question?'
    ];
    
    foreach ($responses as $keywords => $response) {
        if ($keywords === 'default') continue;
        $keywords = explode('|', $keywords);
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return $response;
            }
        }
    }
    
    return $responses['default'];
}



function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function sanitizeAdminInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function logAdminActivity($action, $details = '') {
    if (isset($_SESSION['admin_id'])) {
        error_log("Admin Activity - User: {$_SESSION['admin_username']}, Action: {$action}, Details: {$details}");
    }
}

?>