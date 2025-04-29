<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$response = ['success' => false];

if (isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $content_id = isset($_POST['content_id']) && !empty($_POST['content_id']) ? $_POST['content_id'] : null;
    $user_id = $_SESSION['user_id'];

    // Prepare query with optional content_id
    $query = "SELECT DocuID FROM documents WHERE UserID = ? AND TaskID = ? " . 
             ($content_id ? "AND ContentID = ?" : "AND ContentID IS NULL");
    
    $stmt = $conn->prepare($query);
    
    if ($content_id) {
        $stmt->bind_param("iii", $user_id, $task_id, $content_id);
    } else {
        $stmt->bind_param("ii", $user_id, $task_id);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $doc_ids = [];
        
        while ($row = $result->fetch_assoc()) {
            $doc_ids[] = $row['DocuID'];
        }
        
        $response = [
            'success' => true,
            'doc_ids' => $doc_ids
        ];
    } else {
        $response['error'] = "Database error";
    }
    
    $stmt->close();
} else {
    $response['error'] = 'Task ID required';
}

mysqli_close($conn);

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>