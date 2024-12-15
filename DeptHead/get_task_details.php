<?php
include 'connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$task_id = $data['task_id'] ?? null;

if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Task ID is missing.']);
    exit;
}

// Fetch task details
$sql = "SELECT t.TaskID, t.Title AS TaskTitle, t.taskContent, t.DueDate, t.DueTime, d.dept_name, fc.Title AS ContentTitle, fc.Captions, t.Status
        FROM tasks t
        LEFT JOIN feedcontent fc ON t.ContentID = fc.ContentID
        LEFT JOIN department d ON fc.dept_ID = d.dept_ID
        WHERE t.TaskID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($task = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'task' => $task]);
} else {
    echo json_encode(['success' => false, 'message' => 'Task not found.']);
}
