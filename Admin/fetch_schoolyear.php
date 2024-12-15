<?php
include 'connection.php';

// Query to fetch school years
$sql = "SELECT School_Year_ID, Year_Range FROM schoolyear";
$result = $conn->query($sql);

$schoolyears = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schoolyears[] = $row;
    }
}

// Return school years as JSON
echo json_encode($schoolyears);

$conn->close();
?>
