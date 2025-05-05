<?php
// Log errors but don't show them in output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');
include '../connection.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$task_id = $data['task_id'] ?? null;

if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Task ID is missing.']);
    exit;
}

// Prepare and execute delete query
$stmt = $conn->prepare("DELETE FROM tasks WHERE TaskID = ?");
$stmt->bind_param("i", $task_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

$stmt->close();
$conn->close();
?>
