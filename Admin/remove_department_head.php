<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');


// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$deptHeadIDs = $data['deptHeadIDs'] ?? [];

if (empty($deptHeadIDs)) {
    echo json_encode(['success' => false, 'message' => 'No department heads selected']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE useracc SET role = 'Teacher', dept_ID = NULL WHERE UserID = ? AND role = 'Department Head'");
    
    foreach ($deptHeadIDs as $id) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            // If no rows were affected, the user might not exist or isn't a department head
            throw new Exception("Failed to remove department head with ID: $id");
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => count($deptHeadIDs) > 1 
            ? 'Department heads have been successfully removed' 
            : 'Department head has been successfully removed'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?>