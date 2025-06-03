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
    $sqlDueDate = "SELECT DueDate, DueTime FROM tasks WHERE TaskID = ?";
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
        $sqlUpdateDocuments = "UPDATE documents SET status = ? WHERE TaskID = ? " . 
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

        // NEW: Prepare Administrative Documents update query (only when contentID is null)
        $adminUpdated = true; // Default to true in case we don't need to update
        if ($contentID === null) {
            // First get TaskDept_ID from task_department table
            $sqlGetTaskDept = "SELECT TaskDept_ID FROM task_department WHERE TaskID = ?";
            $stmtGetTaskDept = $conn->prepare($sqlGetTaskDept);
            $stmtGetTaskDept->bind_param("i", $taskID);
            $stmtGetTaskDept->execute();
            $resultTaskDept = $stmtGetTaskDept->get_result();
            
            if ($resultTaskDept->num_rows > 0) {
                $taskDeptIDs = [];
                while ($row = $resultTaskDept->fetch_assoc()) {
                    $taskDeptIDs[] = $row['TaskDept_ID'];
                }
                
                // Create placeholders for IN clause
                $placeholders = implode(',', array_fill(0, count($taskDeptIDs), '?'));
                $types = str_repeat('i', count($taskDeptIDs));
                
                $sqlUpdateAdmin = "UPDATE administrative_document SET Status = ? 
                                  WHERE TaskDept_ID IN ($placeholders) AND UserID = ?";
                
                $stmtAdmin = $conn->prepare($sqlUpdateAdmin);

                // Create the type string: 's' for status, then types for taskDeptIDs, then 'i' for user_id
                $types = 's' . $types . 'i';        
                
                // Bind parameters - status first, then taskDeptIDs, then user_id
                $bindParams = array_merge([$status], $taskDeptIDs, [$user_id]);
                $stmtAdmin->bind_param($types, ...$bindParams);
                
                $adminUpdated = $stmtAdmin->execute();
                if (!$adminUpdated) {
                    $adminError = $stmtAdmin->error;
                }
                $stmtAdmin->close();
            }
            $stmtGetTaskDept->close();
        }

        // Execute updates for documents and task_user
        $documentsUpdated = $stmtDocuments->execute();
        $taskUserUpdated = $stmtTaskUser->execute();

        if ($documentsUpdated && $taskUserUpdated && $adminUpdated) {
            $response = [
                'success' => true,
                'message' => 'Status updated successfully!'
            ];
        } else {
            $errors = [];
            if (!$documentsUpdated) $errors[] = 'Documents: ' . $stmtDocuments->error;
            if (!$taskUserUpdated) $errors[] = 'Task User: ' . $stmtTaskUser->error;
            if (!$adminUpdated) $errors[] = 'Administrative Documents: ' . ($adminError ?? 'Unknown error');
            
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