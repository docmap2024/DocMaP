<?php
include 'connection.php';

// Fetch all year ranges from the database
$sql = "SELECT Year_Range FROM schoolyear ORDER BY Year_Range DESC"; // Modify this if necessary
$result = $conn->query($sql);

// Initialize an array to hold the year ranges
$yearRanges = [];
while ($row = $result->fetch_assoc()) {
    $yearRanges[] = $row['Year_Range'];
}

// Return the year ranges as JSON
echo json_encode([
    'yearRanges' => $yearRanges
]);

$conn->close();
?>
