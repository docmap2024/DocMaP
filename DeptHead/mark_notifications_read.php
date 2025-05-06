<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Include database connection
require_once 'connection.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Prepare SQL to mark all notifications as read
    $sql = "UPDATE notif_user 
            SET Status = 0 
            WHERE UserID = ? AND Status = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    
    // Check if any rows were affected
    $affected_rows = $stmt->affected_rows;
    
    if ($affected_rows >= 0) {
        // Success - even if 0 rows were changed (no unread notifications)
        $response = [
            'success' => true,
            'message' => 'All notifications marked as read',
            'affected_rows' => $affected_rows
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Failed to update notifications'
        ];
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

// Close database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>