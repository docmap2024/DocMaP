<?php
session_start();
header('Content-Type: application/json');
include 'connection.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deptID = $_POST['deptID'];

    // Delete the department from the school_department table
    $sql = "DELETE FROM department WHERE dept_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deptID);

    if ($stmt->execute()) {
        // Delete the associated PIN from the department_pin table
        $deletePinSql = "DELETE FROM department_pin WHERE dept_ID = ?";
        $deletePinStmt = $conn->prepare($deletePinSql);
        $deletePinStmt->bind_param("i", $deptID);
        $deletePinStmt->execute();
        $deletePinStmt->close();

        // Return success response
        echo json_encode(['status' => 'success']);
    } else {
        // Return error response
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    // Return error response for invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>