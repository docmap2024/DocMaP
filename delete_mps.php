<?php
session_start();
include 'connection.php'; // Include your database connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the MPS ID and password from the POST request
    $mpsID = $_POST['mpsID'];
    $password = md5($_POST['password']); // MD5 hash the provided password
    $userID = $_SESSION['user_id']; // Use the session variable for User ID

    // Prepare and execute the query to get the user's stored MD5 hashed password
    $query = "SELECT Password FROM useracc WHERE UserID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userID); // Bind the parameters
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stored_hash = $row['Password'];

        // Compare the provided MD5 hash with the stored hash
        if ($stored_hash === $password) {
            // Password is correct, proceed to delete the MPS record
            $deleteQuery = "DELETE FROM mps WHERE mpsID = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param('i', $mpsID);
            $deleteStmt->execute();

            if ($deleteStmt->affected_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete record.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
