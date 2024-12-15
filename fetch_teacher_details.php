<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

include 'connection.php'; // Include your database connection

// Check if ContentID is provided
if (isset($_GET['ContentID'])) {
    $content_id = mysqli_real_escape_string($conn, $_GET['ContentID']);

    // Prepare SQL query to fetch teacher details
    $sql = "SELECT uc.UserID, uc.Status, ua.Profile, 
                   CONCAT(ua.fname, ' ', ua.mname, '. ', ua.lname) AS FULLNAME
            FROM usercontent uc
            JOIN useracc ua ON uc.UserID = ua.UserID
            WHERE uc.ContentID = ? AND uc.Status = 1";

    // Prepare and execute the query
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $content_id); // Bind the ContentID to the query

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $teachers = [];

            // Fetch teacher details
            while ($row = mysqli_fetch_assoc($result)) {
                $teachers[] = $row;
            }

            // Return the data as JSON
            echo json_encode([
                "count" => count($teachers),
                "teachers" => $teachers
            ]);
        } else {
            echo json_encode(["error" => "Query execution failed"]);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["error" => "Failed to prepare the query"]);
    }
} else {
    echo json_encode(["error" => "ContentID not provided"]);
}
?>
