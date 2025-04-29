<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

// Get form data
$userID = $_POST['userID'] ?? null;
$departmentID = $_POST['departmentID'] ?? null;

// Validate inputs
if (!$userID || !$departmentID) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Check if the department already has a head
    $checkQuery = "SELECT UserID FROM useracc WHERE dept_ID = ? AND role = 'Department Head'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $departmentID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception("This department already has a department head");
    }

    // Update user to department head
    $updateQuery = "UPDATE useracc SET role = 'Department Head', dept_ID = ? WHERE UserID = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ii", $departmentID, $userID);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        throw new Exception("Failed to assign department head");
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Department head has been successfully assigned'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$checkStmt->close();
$updateStmt->close();
$conn->close();
?>