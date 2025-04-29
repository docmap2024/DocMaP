<?php
// Database connection
include 'connection.php';

$logFile = 'logfile.log'; // Log file

// Get JSON input
$requestData = json_decode(file_get_contents("php://input"), true);

// Check if task_id is received
if (isset($requestData['task_id'])) {
    $task_id = $requestData['task_id'];
    error_log("Received request to delete task with ID: " . $task_id . "\n", 3, $logFile);

    // SQL query to delete the task
    $sql = "DELETE FROM tasks WHERE TaskID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $task_id);

    if ($stmt->execute()) {
        error_log("Task with ID " . $task_id . " deleted successfully.\n", 3, $logFile);
        $response = array('success' => true, 'message' => 'Task deleted successfully.');
    } else {
        error_log("Failed to delete task with ID " . $task_id . ": " . $stmt->error . "\n", 3, $logFile);
        $response = array('success' => false, 'message' => 'Failed to delete task.');
    }

    $stmt->close();
    $conn->close();
} else {
    // Log missing task_id error
    error_log("Error: Task ID not provided.\n", 3, $logFile);
    $response = array('success' => false, 'message' => 'Task ID not provided.');
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
