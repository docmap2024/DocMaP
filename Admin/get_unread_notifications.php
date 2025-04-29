<?php
session_start();
header('Content-Type: application/json');

include 'connection.php'; // make sure this connects to your DB

$response = [
    'success' => false,
    'unreadCount' => 0
];

if (!isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT COUNT(*) FROM notif_user nu INNER JOIN notifications ts ON nu.NotifID = ts.NotifID WHERE nu.UserID = ? AND nu.Status = 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($unreadCount);
$stmt->fetch();
$stmt->close();

$response['success'] = true;
$response['unreadCount'] = $unreadCount;

echo json_encode($response);
exit;
