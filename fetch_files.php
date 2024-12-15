<?php
// Include your database connection file here
include 'connection.php';

function fetchFiles($task_id, $content_id) {
    global $conn; // Assuming $conn is your database connection variable

    $files = [];

    // Query to fetch files based on task_id and content_id
    $sql_fetch_files = "SELECT * FROM documents WHERE TaskID = '$task_id' AND ContentID = '$content_id' ";
    $result_fetch_files = mysqli_query($conn, $sql_fetch_files);

    if (mysqli_num_rows($result_fetch_files) > 0) {
        while ($row = mysqli_fetch_assoc($result_fetch_files)) {
            $files[] = [
                'id' => $row['DocuID'],
                'name' => $row['name']
                // Add other fields as needed
            ];
        }
    }

    // Close database connection
    mysqli_close($conn);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($files);
}
?>
