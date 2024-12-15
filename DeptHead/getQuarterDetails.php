<?php
include 'connection.php'; // Include your database connection

// Get the quarter ID from the AJAX request
$quarterID = $_GET['id'];

// Query to fetch the specific quarter details
$query = "SELECT Quarter_ID, Start_Date, End_Date FROM quarter WHERE Quarter_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $quarterID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the quarter details
    $data = $result->fetch_assoc();
    echo json_encode($data); // Return data as JSON
} else {
    echo json_encode(['error' => 'Quarter not found.']);
}

$stmt->close();
$conn->close();
?>
