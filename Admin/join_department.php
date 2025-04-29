<?php
session_start();
include 'connection.php';

if (isset($_GET['user_id']) && isset($_GET['dept_ID']) && isset($_GET['token'])) {
    $userID = $_GET['user_id'];
    $deptID = $_GET['dept_ID'];
    $token = $_GET['token'];

    // Debugging: Log the received values
    error_log("Token from URL: $token");
    error_log("UserID: $userID, DeptID: $deptID");

    // Validate the token by checking the database
    $stmt = $conn->prepare("SELECT * FROM token WHERE UserID = ? AND dept_ID = ? AND token = ? AND expires_at > NOW()");
    $stmt->bind_param("iis", $userID, $deptID, $token);
    if ($stmt->execute()) {
        error_log("Token validation query executed successfully.");
    } else {
        error_log("Token validation query failed. Error: " . $stmt->error);
    }
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Token is valid
        $row = $result->fetch_assoc();
        error_log("Token found in database: " . $row['token']);

        // Check if the user is already in the department
        $stmt = $conn->prepare("SELECT * FROM user_department WHERE UserID = ? AND dept_ID = ?");
        $stmt->bind_param("ii", $userID, $deptID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User is already in the department
            $_SESSION['error_message'] = "You are already a member of this department.";
        } else {
            // Insert the user into the department
            $stmt = $conn->prepare("INSERT INTO user_department (UserID, dept_ID) VALUES (?, ?)");
            $stmt->bind_param("ii", $userID, $deptID);

            if ($stmt->execute()) {
                // Successfully joined the department
                $_SESSION['success_message'] = "You have successfully joined the department!";

                // Delete the used token
                $stmt = $conn->prepare("DELETE FROM token WHERE token_ID = ?");
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
            } else {
                // Error joining the department
                $_SESSION['error_message'] = "Error joining the department. Please try again.";
            }
        }
        $stmt->close();
    } else {
        // Token is invalid or expired
        $_SESSION['error_message'] = "Invalid or expired token. Access denied.";
        error_log("Token validation failed: Token not found or expired.");
        error_log("UserID: $userID, DeptID: $deptID, Token: $token");
    }
} else {
    // Required parameters are missing
    $_SESSION['error_message'] = "Invalid request. Missing parameters.";
}

// Redirect the user to the dashboard
header("Location: ../dash.php"); // Navigate one level up to access dash.php
exit();
?>