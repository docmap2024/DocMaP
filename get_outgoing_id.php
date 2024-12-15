<?php
require 'connection.php';

if (isset($_GET['task_id'])) {
    $task_id = intval($_GET['task_id']);

    // Query to get the UserID based on the TaskID
    $sql = "SELECT UserID FROM tasks WHERE TaskID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->bind_result($outgoing_id);
        if ($stmt->fetch()) {
            echo json_encode(['outgoing_id' => $outgoing_id]);
        } else {
            echo json_encode(['error' => 'No user found for the given task ID']);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Query preparation failed']);
    }
} else {
    echo json_encode(['error' => 'Task ID not provided']);
}

$conn->close();
?>
