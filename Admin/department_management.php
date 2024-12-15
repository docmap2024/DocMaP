<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $name = $conn->real_escape_string($data['name']);
    $info = $conn->real_escape_string($data['info']);

    // Step 1: Insert into the department table
    $sql = "INSERT INTO department (dept_name, dept_info) VALUES ('$name', '$info')";
    if ($conn->query($sql) === TRUE) {
        // Step 2: Retrieve the ID of the newly inserted department
        $dept_ID = $conn->insert_id;

        // Step 3: Insert into the departmentfolders table
        $timestamp = date('Y-m-d H:i:s'); // Set the creation timestamp
        $sql_folder = "INSERT INTO departmentfolders (dept_ID, Name, CreationTimestamp) VALUES ('$dept_ID', '$name', '$timestamp')";
        
        if ($conn->query($sql_folder) === TRUE) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'invalid request']);
}

$conn->close();
?>
