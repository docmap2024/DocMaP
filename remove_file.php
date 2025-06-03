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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['file_id'])) {
        throw new Exception('File ID is required');
    }

    $file_id = intval($_POST['file_id']);
    $user_id = intval($_SESSION['user_id']);

    // First try to delete from documents table
    $sql_documents = "DELETE FROM documents WHERE DocuID = ? AND UserID = ?";
    $stmt_documents = $conn->prepare($sql_documents);
    $stmt_documents->bind_param("ii", $file_id, $user_id);
    $stmt_documents->execute();

    $affected_rows = $stmt_documents->affected_rows;
    $stmt_documents->close();

    // If no rows were affected in documents table, try administrative_document table
    if ($affected_rows === 0) {
        $sql_admin = "DELETE FROM administrative_document WHERE Admin_Docu_ID = ? AND UserID = ?";
        $stmt_admin = $conn->prepare($sql_admin);
        $stmt_admin->bind_param("ii", $file_id, $user_id);
        $stmt_admin->execute();

        $affected_rows = $stmt_admin->affected_rows;
        $stmt_admin->close();
    }

    if ($affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('File not found or you don\'t have permission to delete it');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => [
            'file_id' => $file_id ?? null,
            'user_id' => $user_id ?? null
        ]
    ]);
} finally {
    $conn->close();
}
