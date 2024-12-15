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
        logMessage("Error: Missing required data in reject.php.");
        echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
        exit;
    }

    $status = 'Rejected'; // Set status as 'Rejected'
    $rejectDate = date('Y-m-d H:i:s'); // Get the current date and time

    // Prepare SQL statement to update the task status to 'Rejected' and set the reject date
    $sql = "UPDATE task_user SET Status = ?, Comment = ?, RejectDate = ? WHERE Task_User_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $status, $comment, $rejectDate, $taskUserId);

    // Execute the statement
    if ($stmt->execute()) {
        logMessage("Task user status updated to 'Rejected' in reject.php.");
        echo json_encode(['status' => 'success', 'message' => 'The submission has been rejected successfully.']);
    } else {
        logMessage("Error updating task user status in reject.php.");
        echo json_encode(['status' => 'error', 'message' => 'Error updating task user status.']);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
