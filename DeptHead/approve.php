<?php
session_start();
require 'connection.php';

function logMessage($message) {
    $log_file = __DIR__ . '/logfile.log';
    error_log(date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, 3, $log_file);
}

header('Content-Type: application/json'); // Set header for JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskUserId = $_POST['userID'];
    $contentID = $_POST['contentID'];
    $taskID = $_POST['taskID']; 
    $comment = $_POST['comment'] ?? null;

    if (empty($taskUserId) || empty($contentID) || empty($taskID)) {
        logMessage("Error: Missing required data in approve.php.");
        echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
        exit;
    }

    $status = 'Approved';
    $approveDate = date('Y-m-d H:i:s'); // Get the current date and time

    // Update the task_user table with the approval status, comment, and approve date
    $sql = "UPDATE task_user SET Status = ?, Comment = ?, ApproveDate = ? WHERE Task_User_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $status, $comment, $approveDate, $taskUserId);

    if ($stmt->execute()) {
        logMessage("Task user status updated successfully in approve.php.");
        echo json_encode(['status' => 'success', 'message' => 'The submission has been approved successfully.']);
    } else {
        logMessage("Error updating task user status in approve.php.");
        echo json_encode(['status' => 'error', 'message' => 'Error updating task user status.']);
    }

    $stmt->close();
    $conn->close();
}
?>
