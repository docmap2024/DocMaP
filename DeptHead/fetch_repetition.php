<?php
include 'connection.php'; // Ensure the connection file is correctly set up

// Query to fetch enrollment data with the school year range
$sql = "SELECT r.Repetition_ID, r.Rate, s.Year_Range 
        FROM repetition r 
        JOIN schoolyear s ON r.School_Year_ID = s.School_Year_ID";

$result = $conn->query($sql);

$repetition = [];
if ($result->num_rows > 0) {
    // Loop through the results and add them to the enrollment array
    while ($row = $result->fetch_assoc()) {
        $repetition[] = $row;
    }
}

// Return enrollment data as JSON
echo json_encode(['repetition' => $repetition]);

// Close the connection
$conn->close();
?>
