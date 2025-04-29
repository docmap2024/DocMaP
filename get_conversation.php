<?php
session_start();
require 'Connection.php';

// Set charset for proper encoding handling
$conn->set_charset("utf8mb4");

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'You need to log in to view the conversation.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$content_id = isset($_GET['content_id']) && $_GET['content_id'] !== '' ? intval($_GET['content_id']) : null;
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$output_type = isset($_GET['output']) && $_GET['output'] === 'html' ? 'html' : 'json';

if (empty($task_id)) {
    echo json_encode(['error' => 'Task ID is required.']);
    exit();
}

$messages = [];

function sanitizeForOutput($data, $output_type) {
    if ($output_type === 'html') {
        return is_array($data) 
            ? array_map(fn($item) => htmlspecialchars($item, ENT_QUOTES, 'UTF-8'), $data)
            : htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data; // json_encode will handle escaping
}

// Prepare the query based on whether content_id is provided
if ($content_id !== null) {
    // Get comments with content_id
    $sql_comments = "SELECT c.CommentID, c.Comment, c.IncomingID, c.OutgoingID, c.Timestamp, 
                    u.fname, u.lname, u.profile
                    FROM comments c
                    JOIN useracc u ON c.IncomingID = u.UserID 
                    WHERE c.ContentID = ? AND c.TaskID = ? 
                    ORDER BY c.CommentID";
    
    $stmt = $conn->prepare($sql_comments);
    if ($stmt) {
        $stmt->bind_param("ii", $content_id, $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $message = [
                'CommentID' => $row['CommentID'],
                'Comment' => $output_type === 'html' 
                    ? htmlspecialchars($row['Comment'], ENT_QUOTES, 'UTF-8')
                    : $row['Comment'],
                'IncomingID' => $row['IncomingID'],
                'OutgoingID' => $row['OutgoingID'],
                'Timestamp' => $row['Timestamp'],
                'FullName' => htmlspecialchars($row['fname'] . ' ' . $row['lname'], ENT_QUOTES, 'UTF-8'),
                'profile' => htmlspecialchars($row['profile'], ENT_QUOTES, 'UTF-8'),
                'source' => 'comments'
            ];
            $messages[] = $message;
        }
        $stmt->close();
    }

    // Get task_user comments with content_id
    $sql_task_user = "SELECT Comment, Status, ApproveDate, RejectDate 
                      FROM task_user 
                      WHERE TaskID = ? AND ContentID = ? AND UserID = ?";
    if ($stmt = $conn->prepare($sql_task_user)) {
        $stmt->bind_param("iii", $task_id, $content_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $task_user_comment = [
                'Comment' => $output_type === 'html' 
                    ? htmlspecialchars($row['Comment'], ENT_QUOTES, 'UTF-8')
                    : $row['Comment'],
                'Status' => $row['Status'],
                'ApproveDate' => $row['ApproveDate'],
                'RejectDate' => $row['RejectDate'],
                'FullName' => 'Task Remarks',
                'source' => 'task_user'
            ];
            $messages[] = $task_user_comment;
        }
        $stmt->close();
    }
} else {
    // Get comments without content_id
    $sql_comments = "SELECT c.CommentID, c.Comment, c.IncomingID, c.OutgoingID, c.Timestamp,
                    u.fname, u.lname, u.profile
                    FROM comments c
                    JOIN useracc u ON c.IncomingID = u.UserID 
                    WHERE c.TaskID = ? AND c.ContentID IS NULL
                    ORDER BY c.CommentID";
    
    $stmt = $conn->prepare($sql_comments);
    if ($stmt) {
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $message = [
                'CommentID' => $row['CommentID'],
                'Comment' => $output_type === 'html' 
                    ? htmlspecialchars($row['Comment'], ENT_QUOTES, 'UTF-8')
                    : $row['Comment'],
                'IncomingID' => $row['IncomingID'],
                'OutgoingID' => $row['OutgoingID'],
                'Timestamp' => $row['Timestamp'],
                'FullName' => htmlspecialchars($row['fname'] . ' ' . $row['lname'], ENT_QUOTES, 'UTF-8'),
                'profile' => htmlspecialchars($row['profile'], ENT_QUOTES, 'UTF-8'),
                'source' => 'comments'
            ];
            $messages[] = $message;
        }
        $stmt->close();
    }

    // Get task_user comments without content_id
    $sql_task_user = "SELECT Comment, Status, ApproveDate, RejectDate 
                      FROM task_user 
                      WHERE TaskID = ? AND ContentID IS NULL AND UserID = ?";
    if ($stmt = $conn->prepare($sql_task_user)) {
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $task_user_comment = [
                'Comment' => $output_type === 'html' 
                    ? htmlspecialchars($row['Comment'], ENT_QUOTES, 'UTF-8')
                    : $row['Comment'],
                'Status' => $row['Status'],
                'ApproveDate' => $row['ApproveDate'],
                'RejectDate' => $row['RejectDate'],
                'FullName' => 'Task Remarks',
                'source' => 'task_user'
            ];
            $messages[] = $task_user_comment;
        }
        $stmt->close();
    }
}

// Handle errors
if ($conn->error) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    $conn->close();
    exit();
}

// Output based on requested format
if ($output_type === 'html') {
    // For HTML output, messages are already sanitized
    header('Content-Type: text/html');
    // You would typically loop through $messages here to build HTML
    // For API consistency, we'll still return JSON
    echo json_encode(['messages' => $messages]);
} else {
    // For JSON output, let json_encode handle the encoding
    header('Content-Type: application/json');
    echo json_encode(['messages' => $messages], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>