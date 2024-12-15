<?php
session_start(); // Start the session to access session variables

// Include the database connection from connection.php
require_once 'connection.php'; // Ensure 'connection.php' contains the $conn variable for the database connection

// Get the UserID from the session
$userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($userID) {
    // Prepare and execute the select statement with a join between todo and usertodo tables
    $sql = "
    SELECT t.TodoID, t.Title, t.Due, t.Status 
    FROM todo t
    INNER JOIN usertodo ut ON t.TodoID = ut.TodoID
    WHERE ut.UserID = '$userID' AND t.Status IN ('Active', 'Missing')
";

    
    $result = $conn->query($sql);   

    $tasks = [];
    if ($result->num_rows > 0) {
        // Fetch tasks and store in an array
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    } else {
        echo json_encode(["message" => "No tasks found for this user."]);
        exit();
    }

    // Output tasks as JSON
    echo json_encode($tasks);
} else {
    echo json_encode(["error" => "User not logged in"]);
}

// Close the connection
$conn->close();
?>
