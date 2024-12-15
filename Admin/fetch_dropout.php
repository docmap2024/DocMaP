<?php
include 'connection.php'; // Ensure the connection file is correctly set up

// Query to fetch enrollment data with the school year range
$sql = "SELECT d.Dropout_ID, d.Rate, s.Year_Range 
        FROM dropout d 
        JOIN schoolyear s ON d.School_Year_ID = s.School_Year_ID";

$result = $conn->query($sql);

$dropout = [];
if ($result->num_rows > 0) {
    // Loop through the results and add them to the enrollment array
    while ($row = $result->fetch_assoc()) {
        $dropout[] = $row;
    }
}

// Return enrollment data as JSON
echo json_encode(['dropout' => $dropout]);

// Close the connection
$conn->close();
?>
