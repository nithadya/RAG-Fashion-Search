<?php
// admin/api/feedback.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
                $response = getFeedback();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'delete':
                $response = deleteFeedback();
                break;
            case 'clearAll':
                $response = clearAllFeedback();
                break;
            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
} catch (Exception $e) {
    error_log("Feedback API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);

function getFeedback() {
    global $db;
    
    try {
        $sql = "
            SELECT f.*, u.name as user_name, u.email as user_email
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.id
            ORDER BY f.created_at DESC
        ";
        
        $feedback = $db->fetchAll($sql);
        
        return [
            'success' => true,
            'data' => $feedback
        ];
        
    } catch (Exception $e) {
        error_log("Get feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to load feedback'];
    }
}

function deleteFeedback() {
    global $db;
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            return ['success' => false, 'message' => 'Feedback ID is required'];
        }
        
        // Check if feedback exists
        $feedback = $db->fetchOne("SELECT id FROM feedback WHERE id = ?", [$id]);
        if (!$feedback) {
            return ['success' => false, 'message' => 'Feedback not found'];
        }
        
        $db->query("DELETE FROM feedback WHERE id = ?", [$id]);
        
        logAdminActivity('DELETE_FEEDBACK', "Deleted feedback ID: $id");
        
        return [
            'success' => true,
            'message' => 'Feedback deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Delete feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete feedback'];
    }
}

function clearAllFeedback() {
    global $db;
    
    try {
        $count = $db->fetchOne("SELECT COUNT(*) as count FROM feedback")['count'];
        
        if ($count == 0) {
            return ['success' => false, 'message' => 'No feedback to clear'];
        }
        
        $db->query("DELETE FROM feedback");
        
        logAdminActivity('CLEAR_ALL_FEEDBACK', "Cleared all feedback ($count items)");
        
        return [
            'success' => true,
            'message' => "Successfully cleared $count feedback items"
        ];
        
    } catch (Exception $e) {
        error_log("Clear all feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to clear feedback'];
    }
}
?>
