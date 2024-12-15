<?php
session_start(); // Start the session to access session variables

// Include the database connection from connection.php
require_once 'connection.php'; // Assuming connection.php is in the same directory

// Check if data was posted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the UserID from the session
    $userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Get the task title and due date from the POST request
    $title = isset($_POST['taskName']) ? $conn->real_escape_string($_POST['taskName']) : '';
    $due = isset($_POST['taskDate']) ? $conn->real_escape_string($_POST['taskDate']) : '';

    // Validate input
    if ($userID && !empty($title) && !empty($due)) {
        // Step 1: Insert into the todo table
        $sql = "INSERT INTO todo (Title, Due, Status) VALUES ('$title', '$due', 'Active')";
        
        if ($conn->query($sql) === TRUE) {
            // Get the last inserted TodoID
            $todoID = $conn->insert_id;

            // Step 2: Insert the relationship into the usertodo table
            $sql = "INSERT INTO usertodo (UserID, TodoID) VALUES ('$userID', '$todoID')";

            if ($conn->query($sql) === TRUE) {
                // Success
                echo json_encode(["status" => "success", "message" => "Task added and linked to user successfully."]);
            } else {
                // Error inserting into usertodo
                echo json_encode(["status" => "error", "message" => "Failed to link task to user: " . $conn->error]);
            }
        } else {
            // Error inserting into todo
            echo json_encode(["status" => "error", "message" => "Failed to add task: " . $conn->error]);
        }
    } else {
        // Validation failed
        echo json_encode(["status" => "error", "message" => "Please provide all required fields."]);
    }

    // Close the connection
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>
