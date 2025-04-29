<?php
include 'connection.php'; // Adjust this to your actual DB connection file

if (isset($_GET['taskID'])) {
    $taskID = intval($_GET['taskID']); // Prevent SQL injection

    $query = "SELECT taskContent FROM tasks WHERE TaskID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $taskID);
    $stmt->execute();
    $stmt->bind_result($taskContent);
    
    if ($stmt->fetch()) {
        echo json_encode(['taskContent' => $taskContent]);
    } else {
        echo json_encode(['error' => 'No taskContent found for TaskID: ' . $taskID]);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'TaskID not provided']);
}
?>
