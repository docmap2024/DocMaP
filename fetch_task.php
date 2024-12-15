<?php
session_start();
include 'connection.php';

if (isset($_GET['id'])) {
    $todoId = intval($_GET['id']); // Get the TodoID

    // Log the TodoID to the terminal or PHP error log
    error_log("Received TodoID: " . $todoId); // Logs the TodoID to the PHP error log

    // SQL query to fetch the task details for the specified TodoID
    $query = "SELECT Title, Due 
              FROM todo 
              WHERE TodoID = ?";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        die(json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]));
    }

    $stmt->bind_param("i", $todoId); // Bind TodoID
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $task = $result->fetch_assoc();
            echo json_encode($task);
        } else {
            echo json_encode(['error' => 'Task not found']);
        }
    } else {
        echo json_encode(['error' => 'Failed to execute query: ' . $stmt->error]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
