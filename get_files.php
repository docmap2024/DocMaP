<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include 'connection.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['task_id'])) {
        throw new Exception('Task ID is required');
    }

    $task_id = intval($_GET['task_id']);
    $content_id = isset($_GET['content_id']) && $_GET['content_id'] !== '' ? intval($_GET['content_id']) : null;
    $user_id = intval($_SESSION['user_id']);

    // Debug: Log the received parameters
    error_log("Fetching files for task_id: $task_id, content_id: " . ($content_id ?? 'NULL') . ", user_id: $user_id");

    $files = [];

    // Query for documents table (content-related files)
    $sql_documents = "SELECT DocuID, name FROM documents WHERE TaskID = ? AND UserID = ?";
    $params_documents = [$task_id, $user_id];
    $types_documents = "ii";

    if ($content_id !== null) {
        $sql_documents .= " AND ContentID = ?";
        $params_documents[] = $content_id;
        $types_documents .= "i";
    } else {
        $sql_documents .= " AND ContentID IS NULL";
    }

    $stmt_documents = $conn->prepare($sql_documents);
    if (!$stmt_documents) {
        throw new Exception('Database error (documents): ' . $conn->error);
    }

    $stmt_documents->bind_param($types_documents, ...$params_documents);
    
    if (!$stmt_documents->execute()) {
        throw new Exception('Query execution failed (documents): ' . $stmt_documents->error);
    }

    $result_documents = $stmt_documents->get_result();
    while ($row = $result_documents->fetch_assoc()) {
        $files[] = [
            'id' => $row['DocuID'],
            'name' => $row['name'],
            'type' => 'content' // Add type to distinguish between document types
        ];
    }
    $stmt_documents->close();

    // Only query administrative documents when content_id is null or not provided
    if ($content_id === null) {
        // First, get the TaskDept_ID from task_department table
        $sql_get_taskdept = "SELECT TaskDept_ID FROM task_department WHERE TaskID = ?";
        $stmt_get_taskdept = $conn->prepare($sql_get_taskdept);
        if (!$stmt_get_taskdept) {
            throw new Exception('Database error (task_department): ' . $conn->error);
        }

        $stmt_get_taskdept->bind_param("i", $task_id);
        
        if (!$stmt_get_taskdept->execute()) {
            throw new Exception('Query execution failed (task_department): ' . $stmt_get_taskdept->error);
        }

        $result_taskdept = $stmt_get_taskdept->get_result();
        $taskdept_ids = [];
        
        while ($row = $result_taskdept->fetch_assoc()) {
            $taskdept_ids[] = $row['TaskDept_ID'];
        }
        $stmt_get_taskdept->close();

        // If we found TaskDept_ID(s), query the administrative documents
        if (!empty($taskdept_ids)) {
            // Create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($taskdept_ids), '?'));
            $types = str_repeat('i', count($taskdept_ids));
            
            $sql_admin = "SELECT Admin_Docu_ID, name FROM administrative_document 
                        WHERE TaskDept_ID IN ($placeholders) AND UserID = ?";
            $stmt_admin = $conn->prepare($sql_admin);
            if (!$stmt_admin) {
                throw new Exception('Database error (administrative_document): ' . $conn->error);
            }

            // Bind parameters - taskdept_ids first, then user_id
            $stmt_admin->bind_param($types . "i", ...array_merge($taskdept_ids, [$user_id]));
            
            if (!$stmt_admin->execute()) {
                throw new Exception('Query execution failed (administrative_document): ' . $stmt_admin->error);
            }

            $result_admin = $stmt_admin->get_result();
            while ($row = $result_admin->fetch_assoc()) {
                $files[] = [
                    'id' => $row['Admin_Docu_ID'],
                    'name' => $row['name'],
                    'type' => 'administrative'
                ];
            }
            $stmt_admin->close();
        }
    }

    // Debug: Log the results
    error_log("Found " . count($files) . " files");

    echo json_encode($files);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => [
            'task_id' => $task_id ?? null,
            'content_id' => $content_id ?? null,
            'user_id' => $user_id ?? null
        ]
    ]);
} finally {
    $conn->close();
}