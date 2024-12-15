<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// Check if task_id and content_id are provided
if (isset($_GET['task_id']) && isset($_GET['content_id'])) {
    $task_id = $_GET['task_id'];
    $content_id = $_GET['content_id'];
    $user_id = $_SESSION['user_id']; // Fetch user_id from session  

    // Query to fetch files associated with the task and content
    $sql_files = "SELECT * FROM documents WHERE TaskID = '$task_id' AND ContentID = '$content_id' AND UserID = '$user_id'";
    $result_files = mysqli_query($conn, $sql_files);

    // Initialize an array to store the file details
    $files = array();

    // Check if files are found
    if (mysqli_num_rows($result_files) > 0) {
        while ($row_files = mysqli_fetch_assoc($result_files)) {
            $files[] = array(
                'id' => $row_files['DocuID'],
                'name' => $row_files['name']
            );
        }
    }

    // Return the file details as JSON
    echo json_encode($files);
} else {
    echo json_encode(array());
}

// Close database connection
mysqli_close($conn);
?>
