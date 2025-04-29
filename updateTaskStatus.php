<?php
session_start();
include 'connection.php';

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Decode the JSON data
    $data = json_decode(file_get_contents("php://input"));

    // Validate required fields
    if (!isset($data->taskID) || !isset($data->status)) {
        echo json_encode([
            'success' => false,
            'message' => 'Task ID and status are required'
        ]);
        exit();
    }

    $taskID = mysqli_real_escape_string($conn, $data->taskID);
    $contentID = isset($data->contentID) && !empty($data->contentID) ? mysqli_real_escape_string($conn, $data->contentID) : null;
    $status = mysqli_real_escape_string($conn, $data->status);
    $user_id = $_SESSION['user_id'];

    // Get DueDate and DueTime
    $sqlDueDate = "SELECT DueDate, DueTime FROM Tasks WHERE TaskID = ?";
    $stmtDueDate = $conn->prepare($sqlDueDate);
    $stmtDueDate->bind_param("i", $taskID);
    $stmtDueDate->execute();
    $resultDueDate = $stmtDueDate->get_result();

    if ($resultDueDate->num_rows > 0) {
        $row = $resultDueDate->fetch_assoc();
        $dueDateTime = new DateTime($row['DueDate'] . ' ' . $row['DueTime']);
        $currentDateTime = new DateTime();

        $newStatus = ($currentDateTime < $dueDateTime) ? 'Assigned' : 'Missing';

        // Prepare Documents update query (with or without contentID)
        $sqlUpdateDocuments = "UPDATE Documents SET status = ? WHERE TaskID = ? " . 
                            ($contentID ? "AND ContentID = ?" : "AND ContentID IS NULL") . 
                            " AND UserID = ?";
        
        $stmtDocuments = $conn->prepare($sqlUpdateDocuments);
        
        if ($contentID) {
            $stmtDocuments->bind_param("siii", $status, $taskID, $contentID, $user_id);
        } else {
            $stmtDocuments->bind_param("sii", $status, $taskID, $user_id);
        }

        // Prepare TaskUser update query
        $sqlUpdateTaskUser = "UPDATE task_user SET Status = ? WHERE TaskID = ? AND UserID = ? " . 
                           ($contentID ? "AND ContentID = ?" : "AND ContentID IS NULL");
        
        $stmtTaskUser = $conn->prepare($sqlUpdateTaskUser);
        
        if ($contentID) {
            $stmtTaskUser->bind_param("siii", $newStatus, $taskID, $user_id, $contentID);
        } else {
            $stmtTaskUser->bind_param("sii", $newStatus, $taskID, $user_id);
        }

        // Execute updates
        $documentsUpdated = $stmtDocuments->execute();
        $taskUserUpdated = $stmtTaskUser->execute();

        if ($documentsUpdated && $taskUserUpdated) {
            $response = [
                'success' => true,
                'message' => 'Status updated successfully!'
            ];
        } else {
            $errors = [];
            if (!$documentsUpdated) $errors[] = 'Documents: ' . $stmtDocuments->error;
            if (!$taskUserUpdated) $errors[] = 'Task User: ' . $stmtTaskUser->error;
            
            $response = [
                'success' => false,
                'message' => 'Error updating: ' . implode(', ', $errors)
            ];
        }

        $stmtDocuments->close();
        $stmtTaskUser->close();
    } else {
        $response = [
            'success' => false,
            'message' => 'Task not found'
        ];
    }

    $stmtDueDate->close();
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method'
    ];
}

$conn->close();
echo json_encode($response);
?>