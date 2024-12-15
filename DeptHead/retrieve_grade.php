<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

header('Content-Type: application/json');

$deptID = $conn->real_escape_string($_GET['deptID']);

$sql = "SELECT Title, Captions FROM feedcontent WHERE dept_ID = '$deptID'";
$result = $conn->query($sql);

$feedcontent = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $feedcontent[] = [
            'ContentID' => $row['ContentID'],
            'Title' => $row['Title'],
            'Captions' => $row['Captions'],
            'dept_ID' => $row['dept_ID']
        ];
    }
}
echo json_encode(['feedcontent' => $feedcontent]);

$conn->close();
?>
