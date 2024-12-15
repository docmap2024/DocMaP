<?php
// Database connection
include 'connection.php';

$logFile = 'logfile.log'; // Specify the log file path

if (isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    error_log("Received request to delete task with ID: " . $task_id, 3, $logFile); // Log the received task_id

    // SQL query to delete a task based on TaskID
    $sql = "DELETE FROM tasks WHERE TaskID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $task_id);

    if ($stmt->execute()) {
        error_log("Task with ID " . $task_id . " deleted successfully.", 3, $logFile); // Log successful deletion
        $response = array('success' => true, 'message' => 'Announcement deleted successfully.');
    } else {
        error_log("Failed to delete task with ID " . $task_id . ": " . $stmt->error, 3, $logFile); // Log error message
        $response = array('success' => false, 'message' => 'Failed to delete task.');
    }

    $stmt->close();
    $conn->close();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Handle missing task_id error
    $response = array('success' => false, 'message' => 'Task ID not provided.');
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
