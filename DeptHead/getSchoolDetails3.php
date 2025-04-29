<?php
// Include the database connection
include 'connection.php';

// Start the session to access the user_id
session_start();

// Set the header to return JSON
header('Content-Type: application/json');

// Get the user_id from the session
$user_id = $_SESSION['user_id']; // Ensure the session variable name matches your implementation

// Fetch the school details along with teacher and principal names, sex, and e-signatures
$sql = "
    SELECT 
        sd.`School_ID`, 
        sd.`Name`, 
        sd.`Email`,
        sd.`landline`,
        sd.`City_Muni`, 
        sd.`Address`, 
        sd.`Region`, 
        sd.`Country`, 
        sd.`Organization`, 
        sd.`Logo`, 
        sm.`Mobile_No`,
        t.`sex` AS Teacher_Sex,
        p.`sex` AS DHead_Sex,
        t.`esig` AS Teacher_Signature,
        p.`esig` AS DHead_Signature,
        CONCAT(t.fname, ' ', t.mname, '.', ' ', t.lname) AS Teacher_FullName,
        CONCAT(p.fname, ' ', p.mname, '.', ' ', p.lname) AS DHead_FullName,
        t.`URank` AS Teacher_Role,
        p.`URank` AS DHead_Role
    FROM 
        `school_details` sd
    INNER JOIN 
        `school_mobile` sm ON sm.Mobile_ID = 1
    LEFT JOIN 
        `useracc` t ON t.UserID = '$user_id' AND t.role = 'Teacher' 
    LEFT JOIN 
        `useracc` p ON p.role = 'Department Head'
    WHERE 
        1
";

// Execute the query
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetch the first row of the result
    $row = $result->fetch_assoc();

    // Prefixes removed: No modification to names
    echo json_encode($row); // Return the data as JSON
} else {
    echo json_encode([]); // Return an empty array if no results
}

// Close the database connection
$conn->close();
?>
