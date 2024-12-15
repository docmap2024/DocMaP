<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

session_start(); // Start session to access user data

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

// Assuming you have the user's ID stored in a session variable
$user_id = $_SESSION['user_id']; // Change this to your actual session variable

// Fetch the dept_ID for the logged-in user
$sql = "SELECT dept_ID FROM useracc WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$dept_id = null;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $dept_id = $row['dept_ID'];
} else {
    echo json_encode(['error' => 'Department ID not found for user.']);
    exit;
}

// Now, fetch the departments associated with the dept_ID
$sql = "SELECT * FROM department WHERE dept_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$result = $stmt->get_result();

$departments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = [
            'dept_ID' => $row['dept_ID'],
            'dept_name' => $row['dept_name'],
            'dept_info' => $row['dept_info']
        ];
    }
} else {
    echo json_encode(['department' => []]); // No departments found
    exit;
}

echo json_encode(['department' => $departments]);

$stmt->close();
$conn->close();
?>
