<?php
require 'connection.php';

if (isset($_GET['task_id'])) {
    $task_id = intval($_GET['task_id']);
    $response = [];

    // 1. Get the task uploader (original owner)
    $uploader_sql = "SELECT UserID FROM tasks WHERE TaskID = ?";
    if ($uploader_stmt = $conn->prepare($uploader_sql)) {
        $uploader_stmt->bind_param("i", $task_id);
        $uploader_stmt->execute();
        $uploader_stmt->bind_result($uploader_id);
        $uploader_stmt->fetch();
        $uploader_stmt->close();
        
        $response['uploader_id'] = $uploader_id;
    } else {
        echo json_encode(['error' => 'Failed to prepare uploader query']);
        $conn->close();
        exit();
    }

    // 2. Check for most recent commenter
    $comment_sql = "SELECT IncomingID FROM comments 
                   WHERE TaskID = ? 
                   ORDER BY Timestamp DESC 
                   LIMIT 1";
    if ($comment_stmt = $conn->prepare($comment_sql)) {
        $comment_stmt->bind_param("i", $task_id);
        $comment_stmt->execute();
        $comment_stmt->bind_result($latest_commenter_id);
        
        if ($comment_stmt->fetch()) {
            // If comments exist, use most recent commenter
            $response['outgoing_id'] = $latest_commenter_id;
            $response['is_reply'] = true;
        } else {
            // If no comments, use uploader
            $response['outgoing_id'] = $uploader_id;
            $response['is_reply'] = false;
        }
        $comment_stmt->close();
    } else {
        echo json_encode(['error' => 'Failed to prepare comments query']);
        $conn->close();
        exit();
    }

    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Task ID not provided']);
}

$conn->close();
?>