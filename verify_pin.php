<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$deptId = $data['deptId'];
$pin = $data['pin'];

// Query to verify the PIN
$sql = "SELECT pin FROM department_pin WHERE dept_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $deptId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['pin'] === $pin) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
mysqli_close($conn);
?>