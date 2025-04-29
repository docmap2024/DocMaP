<?php
// Include the database connection
include 'connection.php';

// Start the session to access the user_id
session_start();

// Set the header to return JSON
header('Content-Type: application/json');

// Get the user_id from the session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Ensure user_id exists
if (!$user_id) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

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
        p.`sex` AS Principal_Sex,
        ua.`sex` AS Teacher_Sex,
        t.`esig` AS DHead_Signature,
        p.`esig` AS Principal_Signature,
        ua.`esig` AS Teacher_Signature,
        CONCAT(t.fname, 
               IF(t.mname IS NOT NULL AND t.mname != '', CONCAT(' ', LEFT(t.mname, 1), '.'), ''), 
               ' ', t.lname) AS DHead_FullName,
        CONCAT(p.fname, 
               IF(p.mname IS NOT NULL AND p.mname != '', CONCAT(' ', LEFT(p.mname, 1), '.'), ''), 
               ' ', p.lname) AS Principal_FullName,
       CONCAT(ua.fname, 
               IF(ua.mname IS NOT NULL AND ua.mname != '', CONCAT(' ', LEFT(ua.mname, 1), '.'), ''), 
               ' ', ua.lname) AS Teacher_FullName,

    t.`rank` AS DHead_Role,
        p.`rank` AS Principal_Role,
        ua.`rank` AS Teacher_Role
    FROM 
        `school_details` sd
    INNER JOIN 
        `school_mobile` sm ON sm.Mobile_ID = 1
    LEFT JOIN 
        `useracc` t ON t.UserID = '$user_id' AND t.role = 'Department Head' 
    LEFT JOIN 
        `useracc` p ON p.role = 'Admin'
        LEFT JOIN 
        `useracc` ua ON ua.role = 'Teacher'
    WHERE 
        1
";

// Execute the query
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetch the first row of the result
    $row = $result->fetch_assoc();



    // Add prefixes to names only if the name exists
    if (!empty($row['DHead_FullName'])) {
        $row['DHead_FullName'] = $row['DHead_FullName'];
    }
    if (!empty($row['Principal_FullName'])) {
        $row['Principal_FullName'] =  $row['Principal_FullName'];
    }

    echo json_encode($row); // Return the updated data as JSON
} else {
    echo json_encode(["error" => "No data found"]);
}

// Close the database connection
$conn->close();
?>
