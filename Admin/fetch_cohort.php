<?php
include 'connection.php'; // Ensure the connection file is correctly set up

// Query to fetch enrollment data with the school year range
$sql = "SELECT cs.Cohort_ID, cs.Rate, s.Year_Range 
        FROM cohort_survival cs 
        JOIN schoolyear s ON cs.School_Year_ID = s.School_Year_ID";

$result = $conn->query($sql);

$cohort = [];
if ($result->num_rows > 0) {
    // Loop through the results and add them to the enrollment array
    while ($row = $result->fetch_assoc()) {
        $cohort[] = $row;
    }
}

// Return enrollment data as JSON
echo json_encode(['cohort' => $cohort]);

// Close the connection
$conn->close();
?>
