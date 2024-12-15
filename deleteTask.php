<?php
session_start(); // Start the session to access session variables

// Include the database connection from connection.php
require_once 'connection.php'; // Ensure 'connection.php' contains the $conn variable for the database connection

// Check if the user is logged in and if the task ID is provided
if (isset($_SESSION['user_id']) && isset($_POST['id'])) {
    $todoId = $_POST['id'];
    $userId = $_SESSION['user_id'];

    // Prepare a delete statement for the usertodo table
    $sql1 = "DELETE FROM usertodo WHERE TodoID = ? AND UserID = ?";
    
    // Prepare a delete statement for the todo table
    $sql2 = "DELETE FROM todo WHERE TodoID = ?";
    
    // Use a transaction to ensure both deletions succeed or fail together
    $conn->begin_transaction();

    try {
        // Delete from usertodo table
        if ($stmt1 = $conn->prepare($sql1)) {
            $stmt1->bind_param("ii", $todoId, $userId);
            $stmt1->execute();
            $stmt1->close();
        }

        // Delete from todo table
        if ($stmt2 = $conn->prepare($sql2)) {
            $stmt2->bind_param("i", $todoId);
            $stmt2->execute();
            $stmt2->close();
        }

        // Commit the transaction
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Task deleted successfully from both tables."]);
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error deleting task: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}

// Close the connection
$conn->close();
?>
