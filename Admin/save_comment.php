<?php
// Database connection
include 'connection.php'; // Make sure this file contains your database connection logic

// Start session to access session variables
session_start();

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve posted data
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
    $outgoing_id = isset($_POST['outgoing_id']) ? intval($_POST['outgoing_id']) : 0; // User ID fetched from button click
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Retrieve incoming ID from session
    $incoming_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0; // Assuming 'user_id' is stored in session

    // Validate input data
    if ($task_id > 0 && $content_id > 0 && $outgoing_id > 0 && $incoming_id > 0 && !empty($comment)) {
        // Prepare the SQL statement
        $sql = "INSERT INTO comments (ContentID, TaskID, IncomingID, OutgoingID, Comment) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters
            $stmt->bind_param("iiiss", $content_id, $task_id, $incoming_id, $outgoing_id, $comment);
            
            // Execute the statement
            if ($stmt->execute()) {
                // Comment inserted successfully
                echo json_encode(['success' => true, 'message' => 'Comment saved successfully!']);
            } else {
                // Error executing the statement
                echo json_encode(['success' => false, 'message' => 'Error saving comment.']);
            }

            // Close the statement
            $stmt->close();
        } else {
            // Error preparing the statement
            echo json_encode(['success' => false, 'message' => 'Error preparing statement.']);
        }
    } else {
        // Invalid input data
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    }
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Close the database connection
$conn->close();
?>
