<?php
include('connection.php'); // Include your database connection here
session_start();

// Ensure that the user is logged in and has a dept_id in the session
if (!isset($_SESSION['user_dept_id'])) {
    echo json_encode(['error' => 'User not logged in or department not assigned.']);
    exit;
}

$user_dept_id = $_SESSION['user_dept_id']; // Get the dept_ID of the logged-in user

// Query to get department details for the logged-in user's department ID
$departmentsQuery = "SELECT dept_ID, dept_name 
                     FROM department 
                     WHERE dept_ID = ?"; // Use the logged-in user's dept_ID to filter departments

$stmt_dept = $conn->prepare($departmentsQuery);
if (!$stmt_dept) {
    die(json_encode(['error' => 'Error in SQL preparation: ' . $conn->error]));
}

$stmt_dept->bind_param('i', $user_dept_id); // Bind the dept_ID from session to the query
$stmt_dept->execute();
$departmentsResult = $stmt_dept->get_result();

$departments = [];

if ($departmentsResult && $departmentsResult->num_rows > 0) {
    // Fetch department rows
    while ($dept = $departmentsResult->fetch_assoc()) {
        $deptID = $dept['dept_ID'];
        $deptName = $dept['dept_name'];

        // Fetch submitted task count for the department
        $submittedQuery = "SELECT COUNT(tu.UserID) AS totalSubmit 
                           FROM task_user tu
                           INNER JOIN tasks t ON tu.TaskID = t.TaskID  -- Join tasks table
                           INNER JOIN feedcontent fc ON tu.ContentID = fc.ContentID 
                           WHERE (tu.Status = 'Submitted' OR tu.Status = 'Approved' OR tu.Status = 'Rejected') 
                           AND fc.dept_ID = ? 
                           AND t.Type = 'Task'";  // Add condition for task type

        $submittedStmt = $conn->prepare($submittedQuery);
        if (!$submittedStmt) {
            die(json_encode(['error' => 'Error in SQL preparation: ' . $conn->error]));
        }

        $submittedStmt->bind_param('i', $deptID);
        $submittedStmt->execute();
        $submittedResult = $submittedStmt->get_result();

        // Fetch assigned task count for the department
        $assignedQuery = "SELECT COUNT(tu.UserID) AS totalAssigned 
                          FROM tasks t
                          INNER JOIN task_user tu ON t.TaskID = tu.TaskID
                          INNER JOIN feedcontent fc ON tu.ContentID = fc.ContentID 
                          WHERE fc.dept_ID = ? 
                          AND t.Type = 'Task'";  // Add condition for task type

        $assignedStmt = $conn->prepare($assignedQuery);
        if (!$assignedStmt) {
            die(json_encode(['error' => 'Error in SQL preparation: ' . $conn->error]));
        }

        $assignedStmt->bind_param('i', $deptID);
        $assignedStmt->execute();
        $assignedResult = $assignedStmt->get_result();

        // Fetch task counts
        $submittedRow = $submittedResult->fetch_assoc();
        $assignedRow = $assignedResult->fetch_assoc();

        $totalSubmit = $submittedRow['totalSubmit'] ?? 0;
        $totalAssigned = $assignedRow['totalAssigned'] ?? 0;

        // Add data to departments array
        $departments[] = [
            'dept_ID' => $deptID,
            'dept_name' => $deptName,
            'totalSubmit' => $totalSubmit,
            'totalAssigned' => $totalAssigned
        ];
    }

    // Output the data in JSON format
    echo json_encode($departments);
} else {
    // Handle case where no department is found for the logged-in user
    echo json_encode(['error' => 'No department found for the logged-in user.']);
}

// Fetch tasks for display, filtered by user's dept_ID, ordered by timestamp (newest first)
// Fetch tasks for display, filtered by user's dept_ID, ordered by timestamp (newest first)
$sql = "SELECT t.TaskID AS TaskID, t.Title AS TaskTitle, t.taskContent, t.DueDate, t.DueTime, 
               d.dept_name, fc.Title AS ContentTitle, fc.Captions, t.Status, t.TimeStamp
        FROM tasks t
        LEFT JOIN feedcontent fc ON t.ContentID = fc.ContentID
        LEFT JOIN department d ON fc.dept_ID = d.dept_ID
        LEFT JOIN task_user tu ON t.TaskID = tu.TaskID  -- Join task_user table
        WHERE t.Type = 'Task' 
          AND d.dept_ID = ? 
          AND t.ApprovalStatus = 'Approved' 
          AND tu.Status = 'Submitted'  -- Condition for submitted tasks
        ORDER BY t.TimeStamp DESC  
        LIMIT ? OFFSET ?";

$stmt_tasks = $conn->prepare($sql);
if (!$stmt_tasks) {
    die(json_encode(['error' => 'Error in SQL preparation: ' . $conn->error]));
}

// Define pagination parameters (example values)
$rows_per_page = 10;
$offset = 0;

$stmt_tasks->bind_param('iii', $user_dept_id, $rows_per_page, $offset);
$stmt_tasks->execute();
$result_tasks = $stmt_tasks->get_result();

$tasks = array();
if ($result_tasks->num_rows > 0) {
    while ($row = $result_tasks->fetch_assoc()) {
        $tasks[] = $row;
    }
}
// Close the database connection
$conn->close();
?>