<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$title = $conn->real_escape_string($data['title']);
$caption = $conn->real_escape_string($data['caption']);
$id = $conn->real_escape_string($_GET['id']);

$sql = "UPDATE feedcontent SET Title = '$title', Captions = '$caption' WHERE ContentID = '$id'";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}

$conn->close();
?>
