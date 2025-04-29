<?php
session_start();
header('Content-Type: application/json');
include 'connection.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deptID = $_POST['deptID'];
    $departmentName = $_POST['departmentName'];
    $departmentInfo = $_POST['departmentInfo'];
    $departmentPin = $_POST['departmentPin'];

    // Update the school_department table
    $sql = "UPDATE department SET dept_name = ?, dept_info = ? WHERE dept_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $departmentName, $departmentInfo, $deptID);

    if ($stmt->execute()) {
        // Check if a PIN is provided
        if (!empty($departmentPin)) {
            // Check if a PIN already exists for this department
            $checkPinSql = "SELECT pin_ID FROM department_pin WHERE dept_ID = ?";
            $checkPinStmt = $conn->prepare($checkPinSql);
            $checkPinStmt->bind_param("i", $deptID);
            $checkPinStmt->execute();
            $checkPinResult = $checkPinStmt->get_result();

            if ($checkPinResult->num_rows > 0) {
                // Update the existing PIN
                $updatePinSql = "UPDATE department_pin SET pin = ? WHERE dept_ID = ?";
                $updatePinStmt = $conn->prepare($updatePinSql);
                $updatePinStmt->bind_param("si", $departmentPin, $deptID);

                if ($updatePinStmt->execute()) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => $updatePinStmt->error]);
                }

                $updatePinStmt->close();
            } else {
                // Insert a new PIN
                $insertPinSql = "INSERT INTO department_pin (pin, dept_ID) VALUES (?, ?)";
                $insertPinStmt = $conn->prepare($insertPinSql);
                $insertPinStmt->bind_param("si", $departmentPin, $deptID);

                if ($insertPinStmt->execute()) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => $insertPinStmt->error]);
                }

                $insertPinStmt->close();
            }

            $checkPinStmt->close();
        } else {
            // No PIN provided, delete the existing PIN (if any)
            $deletePinSql = "DELETE FROM department_pin WHERE dept_ID = ?";
            $deletePinStmt = $conn->prepare($deletePinSql);
            $deletePinStmt->bind_param("i", $deptID);

            if ($deletePinStmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $deletePinStmt->error]);
            }

            $deletePinStmt->close();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>