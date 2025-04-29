<?php
session_start();
header('Content-Type: application/json');
include 'connection.php'; // Include your database connection file

// Fetch all departments
$sql = "SELECT d.dept_ID AS dept_ID, d.dept_name AS dept_name, d.dept_info AS dept_info, dp.pin 
        FROM department d 
        LEFT JOIN department_pin dp ON d.dept_ID = dp.dept_ID
        WHERE dept_type = 'Administrative'";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare SQL statement']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$departments = [];

while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

echo json_encode(['status' => 'success', 'departments' => $departments]);

$stmt->close();
$conn->close();
?>