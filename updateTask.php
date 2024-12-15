<?php
// updateTask.php
include 'connection.php'; // Include your database connection

if (isset($_POST['TodoID']) && isset($_POST['taskName']) && isset($_POST['taskDate'])) {
    $todoId = intval($_POST['TodoID']);
    $taskName = $_POST['taskName'];
    $taskDate = $_POST['taskDate'];

    $query = "UPDATE todo SET Title = ?, Due = ? WHERE TodoID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $taskName, $taskDate, $todoId);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Task updated successfully']);
    } else {
        echo json_encode(['error' => 'Failed to update task']);
    }
} else {
    echo json_encode(['error' => 'Invalid input']);
}
?>
