<?php
// api/contact.php - Handle contact form submissions
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = handleContactSubmission();
    } else {
        $response['message'] = 'Only POST method allowed';
    }
} catch (Exception $e) {
    error_log("Contact API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred while processing your request'];
}

echo json_encode($response);

function handleContactSubmission() {
    global $db;
    
    try {
        // Get and validate input data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address'];
        }
        
        // Validate message length
        if (strlen($message) < 10) {
            return ['success' => false, 'message' => 'Message must be at least 10 characters long'];
        }
        
        if (strlen($message) > 2000) {
            return ['success' => false, 'message' => 'Message is too long (maximum 2000 characters)'];
        }
        
        // Get user ID if logged in
        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        
        // Insert into feedback table
        $sql = "INSERT INTO feedback (user_id, name, email, subject, message, type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $db->query($sql, [
            $userId,
            htmlspecialchars($name),
            htmlspecialchars($email),
            htmlspecialchars($subject),
            htmlspecialchars($message),
            'contact'
        ]);
        
        // Log the activity
        error_log("New contact form submission: $subject from $email");
        
        // Send notification email (optional - you can implement this later)
        // sendNotificationEmail($name, $email, $subject, $message);
        
        return [
            'success' => true,
            'message' => 'Your message has been sent successfully! We will get back to you soon.'
        ];
        
    } catch (Exception $e) {
        error_log("Contact submission error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send your message. Please try again.'];
    }
}

function sendNotificationEmail($name, $email, $subject, $message) {
    // Optional: Implement email notification to admin
    // This would use PHPMailer or similar to send emails
    // For now, we'll just log it
    error_log("Email notification would be sent for: $subject");
}
?>
