<?php
include 'connection.php'; // Ensure the connection file is correctly set up

// Query to fetch enrollment data with the school year range
$sql = "SELECT p.Promotion_ID, p.Rate, s.Year_Range 
        FROM promotion p 
        JOIN schoolyear s ON p.School_Year_ID = s.School_Year_ID";

$result = $conn->query($sql);

$promotion = [];
if ($result->num_rows > 0) {
    // Loop through the results and add them to the enrollment array
    while ($row = $result->fetch_assoc()) {
        $promotion[] = $row;
    }
}

// Return enrollment data as JSON
echo json_encode(['promotion' => $promotion]);

// Close the connection
$conn->close();
?>
