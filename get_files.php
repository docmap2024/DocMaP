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

    // Prepare the query
    $sql = "SELECT DocuID, name FROM documents WHERE TaskID = ? AND UserID = ?";
    $params = [$task_id, $user_id];
    $types = "ii";

    if ($content_id !== null) {
        $sql .= " AND ContentID = ?";
        $params[] = $content_id;
        $types .= "i";
    } else {
        $sql .= " AND ContentID IS NULL";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $files = [];

    while ($row = $result->fetch_assoc()) {
        $files[] = [
            'id' => $row['DocuID'],
            'name' => $row['name']
        ];
    }

    // Debug: Log the query and results
    error_log("Query executed: $sql");
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
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>