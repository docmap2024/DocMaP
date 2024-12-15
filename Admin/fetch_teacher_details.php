<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php'; // Include your database connection

if (isset($_GET['ContentID'])) {
    $content_id = $_GET['ContentID'];

    // Query to fetch teacher details
    $sql = "SELECT uc.UserID,uc.Status, ua.Profile, 
                   CONCAT(ua.fname, ' ', ua.mname, '. ', ua.lname) AS FULLNAME
            FROM usercontent uc
            JOIN useracc ua ON uc.UserID = ua.UserID
            WHERE uc.ContentID = '$content_id'
            AND uc.Status = 1";
    
    $result = mysqli_query($conn, $sql);

    $teachers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $teachers[] = $row;
    }

    // Add count to the response
    $response = [
        "count" => count($teachers), // Count of teachers
        "teachers" => $teachers      // Teacher details
    ];

    // Return the data as JSON
    echo json_encode($response);
} else {
    echo json_encode(["error" => "ContentID not provided"]);
}
?>

