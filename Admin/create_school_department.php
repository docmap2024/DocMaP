<?php
session_start();
header('Content-Type: application/json');
include 'connection.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentName = $_POST['departmentName'];
    $departmentInfo = $_POST['departmentInfo'];
    $departmentPin = $_POST['departmentPin'];
    $type = 'Administrative';

    // Insert into department table
    $sql = "INSERT INTO department (dept_name, dept_info, dept_type) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $departmentName, $departmentInfo, $type);

    if ($stmt->execute()) {
        // Get the dept_ID of the newly inserted department
        $dept_ID = $stmt->insert_id;

        // Insert into the departmentfolders table
        $timestamp = date('Y-m-d H:i:s'); // Set the creation timestamp
        $sql_folder = "INSERT INTO departmentfolders (dept_ID, Name, CreationTimestamp) VALUES (?, ?, ?)";

        $stmt_folder = $conn->prepare($sql_folder);
        $stmt_folder->bind_param("iss", $dept_ID, $departmentName, $timestamp);

        if ($stmt_folder->execute()) {
            // Insert the PIN into the department_pin table (if provided)
            if (!empty($departmentPin)) {
                $sql2 = "INSERT INTO department_pin (pin, dept_ID) VALUES (?, ?)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("si", $departmentPin, $dept_ID);

                if ($stmt2->execute()) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => $stmt2->error]);
                }

                $stmt2->close();
            } else {
                echo json_encode(['status' => 'success']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt_folder->error]);
        }

        $stmt_folder->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>