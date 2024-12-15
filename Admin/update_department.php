<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $id = $conn->real_escape_string($data['id']);
    $name = $conn->real_escape_string($data['name']);
    $info = $conn->real_escape_string($data['info']);

    $sql = "UPDATE department SET dept_name = '$name', dept_info = '$info' WHERE dept_ID = '$id'";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'invalid request']);
}

$conn->close();
?>
