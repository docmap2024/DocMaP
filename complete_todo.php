<?php
session_start();
require_once 'connection.php'; // Ensure 'connection.php' contains the $conn variable for the database connection

if (isset($_SESSION['user_id']) && isset($_POST['TodoID']) && isset($_POST['Status'])) {
    $todoId = $_POST['TodoID'];
    $status = $_POST['Status'];

    // Prepare the update statement
    $sql = "UPDATE todo SET Status = ? WHERE TodoID = ?";
    
    // Use a prepared statement
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $status, $todoId);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Task status updated to completed."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating task status."]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Error preparing statement."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}

$conn->close();
?>
