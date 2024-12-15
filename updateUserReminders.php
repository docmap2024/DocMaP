<?php
session_start();
require 'connection.php'; // Ensure connection to your DB

$user_id = $_SESSION['user_id']; // Assuming UserID is stored in session
$currentDate = date('Y-m-d H:i:s'); // Current date/time in PH time

$sql = "
    SELECT ut.TodoID, t.Title, t.Due, t.Status
    FROM usertodo ut
    INNER JOIN todo t ON ut.TodoID = t.TodoID
    WHERE ut.UserID = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $todoID = $row['TodoID'];
        $dueDate = $row['Due'];
        $status = $row['Status'];

        // Check if the Due date has passed and status is "Active"
        if (strtotime($dueDate) < strtotime($currentDate) && $status === 'Active') {
            // Update the status to "Missing"
            $update_sql = "UPDATE todo SET Status = 'Missing' WHERE TodoID = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("i", $todoID);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    }
    $stmt->close();
    
    // Include user_id in the response
    echo json_encode(['success' => true, 'user_id' => $user_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error preparing query.']);
}

$conn->close();
?>
