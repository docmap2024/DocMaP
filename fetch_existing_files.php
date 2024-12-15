<?php
session_start();

// Redirect to index.php if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'connection.php';

$response = array('success' => false);

// Check if task_id and content_id are set
if (isset($_POST['task_id']) && isset($_POST['content_id'])) {
    $task_id = $_POST['task_id'];
    $content_id = $_POST['content_id'];
    $user_id = $_SESSION['user_id']; // Get user ID from session

    // Fetch existing file IDs from database
    $fetchQuery = "SELECT DocuID FROM documents WHERE UserID = '$user_id' AND TaskID = '$task_id' AND ContentID = '$content_id'";
    $fetchResult = mysqli_query($conn, $fetchQuery);

    if ($fetchResult) {
        $existing_files = array();
        while ($row = mysqli_fetch_assoc($fetchResult)) {
            $existing_files[] = $row['DocuID'];
        }
        $response['success'] = true;
        $response['existing_files'] = $existing_files;
    } else {
        $response['error'] = "Error fetching existing files: " . mysqli_error($conn);
    }
} else {
    $response['error'] = 'Task ID or Content ID missing.';
}

mysqli_close($conn);

// Return JSON response
echo json_encode($response);
?>
