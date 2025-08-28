<?php
// api/feedback.php - Handle feedback submissions (both contact and feedback)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
        $response = handleFeedbackSubmission();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'stats':
                $response = getFeedbackStats();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } else {
        $response['message'] = 'Method not allowed';
    }
} catch (Exception $e) {
    error_log("Feedback API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred while processing your request'];
}

echo json_encode($response);

function handleFeedbackSubmission() {
    global $db;
    
    try {
        // Get and validate input data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'feedback');
        
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
        
        // Validate type
        $allowedTypes = ['contact', 'feedback', 'suggestion', 'complaint'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'feedback';
        }
        
        // Get user ID if logged in
        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        
        // Check for spam (simple rate limiting)
        if (isSpamSubmission($email)) {
            return ['success' => false, 'message' => 'Too many submissions. Please wait before sending another message.'];
        }
        
        // Insert into feedback table
        $sql = "INSERT INTO feedback (user_id, name, email, subject, message, type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())";
        
        $insertId = $db->query($sql, [
            $userId,
            htmlspecialchars($name),
            htmlspecialchars($email),
            htmlspecialchars($subject),
            htmlspecialchars($message),
            $type
        ]);
        
        // Log the activity
        error_log("New $type submission (#$insertId): $subject from $email");
        
        // Send notification email to admin (optional)
        sendAdminNotification($insertId, $type, $name, $email, $subject, $message);
        
        // Personalized response based on type
        $responseMessages = [
            'contact' => 'Thank you for contacting us! We will get back to you within 24 hours.',
            'feedback' => 'Thank you for your valuable feedback! We appreciate your input.',
            'suggestion' => 'Thank you for your suggestion! We will review it and consider implementing it.',
            'complaint' => 'Thank you for bringing this to our attention. We will investigate and resolve this issue promptly.'
        ];
        
        return [
            'success' => true,
            'message' => $responseMessages[$type] ?? 'Thank you for your message!',
            'feedback_id' => $insertId
        ];
        
    } catch (Exception $e) {
        error_log("Feedback submission error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send your message. Please try again.'];
    }
}

function getFeedbackStats() {
    global $db;
    
    try {
        $stats = [];
        
        // Total feedback count
        $total = $db->fetchOne("SELECT COUNT(*) as count FROM feedback")['count'];
        $stats['total'] = $total;
        
        // Count by type
        $typeStats = $db->fetchAll("
            SELECT type, COUNT(*) as count 
            FROM feedback 
            GROUP BY type 
            ORDER BY count DESC
        ");
        
        $stats['by_type'] = [];
        foreach ($typeStats as $row) {
            $stats['by_type'][$row['type']] = $row['count'];
        }
        
        // Recent feedback (last 30 days)
        $recent = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM feedback 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")['count'];
        
        $stats['recent_30_days'] = $recent;
        
        // Status counts
        $statusStats = $db->fetchAll("
            SELECT status, COUNT(*) as count 
            FROM feedback 
            GROUP BY status
        ");
        
        $stats['by_status'] = [];
        foreach ($statusStats as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        return [
            'success' => true,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("Get feedback stats error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load feedback statistics'];
    }
}

function isSpamSubmission($email) {
    global $db;
    
    try {
        // Check if same email submitted more than 3 times in last hour
        $count = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM feedback 
            WHERE email = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ", [$email])['count'];
        
        return $count >= 3;
        
    } catch (Exception $e) {
        return false; // If error checking, allow submission
    }
}

function sendAdminNotification($feedbackId, $type, $name, $email, $subject, $message) {
    // This is a placeholder for email notification
    // You can implement actual email sending using PHPMailer or similar
    
    $notificationData = [
        'id' => $feedbackId,
        'type' => $type,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => substr($message, 0, 100) . '...'
    ];
    
    // Log notification (replace with actual email sending)
    error_log("Admin notification for feedback #$feedbackId: " . json_encode($notificationData));
    
    // TODO: Implement actual email notification
    // $mail = new PHPMailer();
    // $mail->setFrom('noreply@styleme.com', 'StyleMe Feedback');
    // $mail->addAddress('admin@styleme.com');
    // $mail->Subject = "New $type: $subject";
    // $mail->Body = "New $type received from $name ($email):\n\n$message";
    // $mail->send();
}
?>
