<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'connection.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Update all unread notifications (status = 1) to read (status = 0)
    $sql = "UPDATE notif_user SET Status = 0 WHERE UserID = ? AND Status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false]);
}
?>
