<?php
// Include the database connection
include 'connection.php';

// Start the session to access the user_id
session_start();

// Set the header to return JSON
header('Content-Type: application/json');

// Get the user_id from the session
$user_id = $_SESSION['user_id']; // Ensure the session variable name matches your implementation

// Fetch the school details along with teacher and principal names
$sql = "
    SELECT 
        sd.`School_ID`, 
        sd.`Name`, 
        sd.`Address`, 
        sd.`Region`, 
        sd.`Country`, 
        sd.`Organization`, 
        sd.`Logo`, 
        CONCAT(t.fname, ' ', t.mname,'.', ' ', t.lname) AS Teacher_FullName,
        CONCAT(p.fname, ' ', p.mname,'.', ' ', p.lname) AS Principal_FullName
    FROM 
        `school_details` sd
    LEFT JOIN 
        `useracc` t ON t.UserID = '$user_id' AND t.role = 'Teacher' 
    LEFT JOIN 
        `useracc` p ON p.role = 'Admin'
    WHERE 
        1
";

// Execute the query
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetch the first row of the result
    $row = $result->fetch_assoc();
    echo json_encode($row); // Return the school details along with names as JSON
} else {
    echo json_encode([]); // Return an empty array if no results
}

// Close the database connection
$conn->close();
?>
