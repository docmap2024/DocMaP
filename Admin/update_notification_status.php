<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start(); // Start the session to access the user ID
    include 'connection.php'; // Include the database connection

    // Ensure that the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "User not logged in"]);
        exit();
    }

    // Get the data from the request (assuming the notification ID is passed)
    $data = json_decode(file_get_contents("php://input"), true);
    $notifId = $data['notifId'];

    // Get the user_id from the session
    $user_id = $_SESSION['user_id'];

    // Update the notification status in the database
    $sql = "UPDATE notif_user SET Status = ? WHERE NotifID = ? AND UserID = ?";
    $stmt = $conn->prepare($sql);
    $newStatus = 0; // Assuming you want to mark the notification as read (0)
    $stmt->bind_param("iis", $newStatus, $notifId, $user_id); // Bind the parameters

    // Execute the query and check the result
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update notification"]);
    }

    // Close the prepared statement and the database connection
    $stmt->close();
    $conn->close();
}
?>
