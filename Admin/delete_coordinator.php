<?php
session_start();
// Include the database connection
include 'connection.php';

if (isset($_POST['coordinatorID']) && !empty($_POST['coordinatorID'])) {
    // Check if any chairperson IDs are selected
    $selectedChairpersons = $_POST['coordinatorID'];

    // Prepare the SQL DELETE query
    $ids = implode(',', array_map('intval', $selectedChairpersons)); // Ensure the IDs are integers
    $sql = "DELETE FROM guidance_coordinator WHERE Coordinator_ID IN ($ids)";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Guidance Coordinator(s) deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No coordinators selected for deletion.']);
}

$conn->close();
?>
