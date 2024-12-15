<?php
include 'connection.php'; // Include your database connection

if (isset($_GET['userID']) && isset($_GET['mpsID'])) {
    $userID = (int)$_GET['userID'];
    $mpsID = (int)$_GET['mpsID'];

    // Query to fetch concatenated name, mobile, and MPS
    $query = "
        SELECT CONCAT(u.fname, ' ', u.mname, ' ', u.lname) AS fullName, u.mobile, m.MPS 
        FROM useracc u
        JOIN mps m ON u.UserID = m.UserID
        WHERE u.UserID = ? AND mpsID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $userID, $mpsID);
    $stmt->execute();
    $stmt->bind_result($fullName, $mobile, $MPS);
    $stmt->fetch();

    // Format MPS to show 2 decimal places
    $formattedMPS = number_format($MPS, 2); 

    // Output the result as JSON
    echo json_encode(['fullName' => $fullName, 'mobile' => $mobile, 'MPS' => $formattedMPS]);
} else {
    echo json_encode(['fullName' => null, 'mobile' => null, 'MPS' => null]);
}

$conn->close();
?>
