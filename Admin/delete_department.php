<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, update all users in this department to set deptID to NULL
        $updateSql = "UPDATE useracc SET dept_ID = NULL, role = 'Teacher' WHERE dept_ID = '$id'";
        if (!$conn->query($updateSql)) {
            throw new Exception("Failed to update users: " . $conn->error);
        }
        
        // Then delete the department
        $deleteSql = "DELETE FROM department WHERE dept_ID = '$id'";
        if (!$conn->query($deleteSql)) {
            throw new Exception("Failed to delete department: " . $conn->error);
        }
        
        // Commit transaction if both queries succeeded
        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'invalid request']);
}

$conn->close();
?>
