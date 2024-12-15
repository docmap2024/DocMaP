<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set the default timezone to Philippine Time
include 'connection.php'; // Include your database connection file

// Redirect to index.php if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Function to update task status
function updateTaskStatus($task_id) {
    global $conn;
    $user_id = $_SESSION['user_id'];

    // Fetch due date and time from the tasks table
    $sql_fetch_due = "SELECT DueDate, DueTime FROM tasks WHERE TaskID = ?";
    $stmt_fetch_due = mysqli_prepare($conn, $sql_fetch_due);
    mysqli_stmt_bind_param($stmt_fetch_due, "i", $task_id);
    mysqli_stmt_execute($stmt_fetch_due);
    mysqli_stmt_bind_result($stmt_fetch_due, $due_date, $due_time);
    mysqli_stmt_fetch($stmt_fetch_due);
    mysqli_stmt_close($stmt_fetch_due);

    // Combine DueDate and DueTime into a DateTime object
    if ($due_date && $due_time) {
        $due_date_time = DateTime::createFromFormat('Y-m-d H:i:s', $due_date . ' ' . $due_time);
    } else {
        return; // Exit if no due date or time is found
    }

    // Get current date and time
    $current_date_time = new DateTime();

    // Check the current status in task_user
    $sql_check_status = "SELECT Status FROM task_user WHERE TaskID = ? AND UserID = ?";
    $stmt_check_status = mysqli_prepare($conn, $sql_check_status);
    mysqli_stmt_bind_param($stmt_check_status, "ii", $task_id, $user_id);
    mysqli_stmt_execute($stmt_check_status);
    mysqli_stmt_bind_result($stmt_check_status, $current_status);
    mysqli_stmt_fetch($stmt_check_status);
    mysqli_stmt_close($stmt_check_status);

    // Update status if conditions are met and the current status is not "Handed In"
    if ($current_status === 'Assigned' && $current_date_time >= $due_date_time) {
        // Check if current status is "Handed In"
        if ($current_status !== 'Submitted') {
            $new_status = 'Missing';
            $sql_update_status = "UPDATE task_user SET Status = ? WHERE TaskID = ? AND UserID = ?";
            $stmt_update_status = mysqli_prepare($conn, $sql_update_status);
            mysqli_stmt_bind_param($stmt_update_status, "sii", $new_status, $task_id, $user_id);
            mysqli_stmt_execute($stmt_update_status);
            mysqli_stmt_close($stmt_update_status);
        }
    }
}

// Check if task_id is provided
if (isset($_GET['task_id'])) {
    updateTaskStatus($_GET['task_id']);
}

// Redirect to task details page
if (isset($_GET['content_id'])) {
    header("Location: taskdetails.php?task_id=" . htmlspecialchars($_GET['task_id']) . "&content_id=" . htmlspecialchars($_GET['content_id']));
    exit();
}

// Close the database connection
mysqli_close($conn);
?>
