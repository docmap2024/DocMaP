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

    if ($content_id !== null) {
        // Regular document processing (content_id provided)
        $query = "SELECT DocuID FROM documents WHERE UserID = ? AND TaskID = ? AND ContentID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $task_id, $content_id);
    } else {
        // Administrative document processing (content_id not provided)
        // First get TaskDept_ID and DepartmentFolderID
        $taskDeptQuery = "SELECT td.TaskDept_ID, df.DepartmentFolderID 
                         FROM task_department td
                         JOIN departmentfolders df ON td.dept_ID = df.dept_ID
                         WHERE td.TaskID = ?";
        
        $stmt = $conn->prepare($taskDeptQuery);
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $response['error'] = "No administrative task found with the provided Task ID";
            echo json_encode($response);
            exit();
        }
        
        $taskData = $result->fetch_assoc();
        $task_dept_id = $taskData['TaskDept_ID'];
        $department_folder_id = $taskData['DepartmentFolderID'];
        
        // Now query administrative documents
        $query = "SELECT Admin_Docu_ID FROM administrative_document 
                 WHERE UserID = ? AND TaskDept_ID = ? AND DepartmentFolderID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $task_dept_id, $department_folder_id);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $doc_ids = [];
        
        while ($row = $result->fetch_assoc()) {
            $doc_ids[] = $content_id !== null ? $row['DocuID'] : $row['Admin_Docu_ID'];
        }
        
        $response = [
            'success' => true,
            'doc_ids' => $doc_ids,
            'doc_type' => $content_id !== null ? 'regular' : 'administrative'
        ];
    } else {
        $response['error'] = "Database error: " . $stmt->error;
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