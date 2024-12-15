<?php
include 'connection.php'; // Ensure the connection file is correctly set up

// Query to fetch enrollment data with the school year range
$sql = "SELECT e.Enroll_ID, e.Rate, s.Year_Range 
        FROM enroll e 
        JOIN schoolyear s ON e.School_Year_ID = s.School_Year_ID";

$result = $conn->query($sql);

$enrollment = [];
if ($result->num_rows > 0) {
    // Loop through the results and add them to the enrollment array
    while ($row = $result->fetch_assoc()) {
        $enrollment[] = $row;
    }
}

// Return enrollment data as JSON
echo json_encode(['enrollment' => $enrollment]);

// Close the connection
$conn->close();
?>
