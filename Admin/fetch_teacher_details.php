<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php'; // Include your database connection

// Check if ContentID or dept_ID is provided
if (isset($_GET['ContentID'])) {
    $content_id = $_GET['ContentID'];

    // Query to fetch teacher details based on ContentID
    $sql = "SELECT uc.UserID, uc.Status, ua.profile, 
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
} elseif (isset($_GET['dept_ID'])) {
    $dept_ID = $_GET['dept_ID'];

    // Query to fetch teacher details based on dept_ID
    $sql = "SELECT ua.UserID, ua.Status, ua.profile, 
                   CONCAT(ua.fname, ' ', ua.mname, '. ', ua.lname) AS FULLNAME
            FROM useracc ua
            JOIN user_department ud ON ua.UserID = ud.UserID
            WHERE ud.dept_ID = '$dept_ID'"; // Only fetch teachers

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
    echo json_encode(["error" => "ContentID or dept_ID not provided"]);
}
?>