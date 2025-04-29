<?php
header('Content-Type: application/json');
require 'connection.php'; // Your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deptID = $_POST['deptID'] ?? null;
    $enteredPin = $_POST['pin'] ?? null;

    if (!$deptID || !$enteredPin) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    // Fetch stored PIN from database
    $stmt = $conn->prepare("SELECT dp.pin 
                            FROM department_pin dp
                            INNER JOIN department d 
                            ON dp.dept_ID = d.dept_ID
                            WHERE d.dept_ID = ? AND dept_type = 'Administrative'");
    $stmt->bind_param('i', $deptID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $storedPin = $row['pin'];
        if ($enteredPin === $storedPin) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid PIN']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Department not found']);
    }

    $stmt->close();
    $conn->close();
}
