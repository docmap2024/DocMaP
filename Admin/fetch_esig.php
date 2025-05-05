<?php
session_start();
include 'connection.php'; // Include your database connection file

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT esig FROM useracc WHERE UserID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['esig' => $row['esig']]);
    } else {
        echo json_encode(['esig' => null]);
    }
} else {
    echo json_encode(['esig' => null]);
}
?>
