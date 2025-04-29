<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

try {
    // Verify required parameters
    if (!isset($_GET['task_id'])) {
        throw new Exception('Missing required parameter: task_id is required');
    }

    // Get and validate parameters
    $task_id = filter_var($_GET['task_id'], FILTER_VALIDATE_INT);
    $content_id = isset($_GET['content_id']) ? filter_var($_GET['content_id'], FILTER_VALIDATE_INT) : null;
    $session_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (!$task_id) {
        throw new Exception('Invalid parameter: task_id must be an integer');
    }

    // Prepare the query
    $sql = "SELECT c.Comment, c.IncomingID, c.OutgoingID, c.timestamp, 
                   u.fname, u.lname, u.profile
            FROM comments c
            JOIN useracc u ON c.IncomingID = u.UserID 
            WHERE c.TaskID = ?";
    
    $params = [$task_id];
    $param_types = "i";

    // Add content ID condition
    if ($content_id !== null) {
        $sql .= " AND c.ContentID = ?";
        $params[] = $content_id;
        $param_types .= "i";
    } else {
        $sql .= " AND c.ContentID IS NULL";
    }

    $sql .= " ORDER BY c.timestamp ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($param_types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $comments = [];

    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'Comment' => htmlspecialchars($row['Comment']),
            'fname' => htmlspecialchars($row['fname']),
            'lname' => htmlspecialchars($row['lname']),
            'profile' => '../img/UserProfile/' . htmlspecialchars($row['profile']),
            'timestamp' => (new DateTime($row['timestamp']))->format('Y/m/d H:i'),
            'isCurrentUser' => ($session_user_id && $row['IncomingID'] == $session_user_id)
        ];
    }

    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>