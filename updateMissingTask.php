<?php
session_start();
include 'connection.php';

date_default_timezone_set('Asia/Manila');

$user_id = $_SESSION['user_id'] ?? null; // Ensure user_id is set

// Ensure user is logged in
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit;
}

// Query tasks assigned to the user
$sql = "SELECT tu.TaskID, tu.Status, t.DueDate, t.DueTime 
        FROM task_user tu
        JOIN tasks t ON tu.TaskID = t.TaskID
        WHERE tu.UserID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error preparing query.']);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Collect tasks that need updating
$tasksToUpdate = [];

while ($row = $result->fetch_assoc()) {
    $taskID = $row['TaskID'];
    $status = $row['Status'];
    $dueDate = $row['DueDate'];
    $dueTime = $row['DueTime'];

    $currentDateTime = new DateTime();
    $taskDueDateTime = new DateTime("$dueDate $dueTime");

    // Only update tasks that are overdue and have a status of 'Assigned'
    if ($currentDateTime > $taskDueDateTime && $status === 'Assigned' && $status !== 'Rejected' && $status !== 'Approved' && $status !== 'Submitted' && $status !== 'Missing') {
        $tasksToUpdate[] = $taskID;
    }
}

if (!empty($tasksToUpdate)) {
    $taskIDPlaceholders = implode(',', array_fill(0, count($tasksToUpdate), '?'));
    $sqlUpdate = "UPDATE task_user SET Status = 'Missing' WHERE TaskID IN ($taskIDPlaceholders) AND UserID = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        echo json_encode(['success' => false, 'error' => 'Error preparing update query.']);
        exit;
    }

    $types = str_repeat('i', count($tasksToUpdate)) . 'i';
    $params = array_merge($tasksToUpdate, [$user_id]);
    $stmtUpdate->bind_param($types, ...$params);
    $stmtUpdate->execute();

    if ($stmtUpdate->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No tasks were updated.']);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'No overdue tasks or updates needed.']);
}

$stmt->close();
$conn->close();
?>
