<?php
session_start(); // Ensure session is started
include 'connection.php'; // Include your database connection

// Get the form data from the AJAX request
$quarterID = $_POST['quarterID'];
$startDate = $_POST['startDate'];
$endDate = $_POST['endDate'];

// Query to update the quarter's start and end dates
$query = "UPDATE quarter SET Start_Date = ?, End_Date = ? WHERE Quarter_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $startDate, $endDate, $quarterID);

if ($stmt->execute()) {
    echo json_encode(['success' => true]); // Successfully updated
} else {
    echo json_encode(['error' => 'Failed to update quarter.']);
}

$stmt->close();
$conn->close();
?>
