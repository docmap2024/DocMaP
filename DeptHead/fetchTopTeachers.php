<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['dept_ID'])) {
    echo "Department ID not found in session.";
    exit;
}

$dept_ID = $_SESSION['dept_ID'];

// Get the year from the GET request (default to current year if not provided)
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

// Query the department name based on dept_ID
$deptNameQuery = "SELECT dept_name FROM department WHERE dept_ID = ?";
$stmtDeptName = $conn->prepare($deptNameQuery);

if (!$stmtDeptName) {
    die("Error in SQL preparation: " . $conn->error);
}

$stmtDeptName->bind_param("i", $dept_ID);
$stmtDeptName->execute();
$resultDeptName = $stmtDeptName->get_result();

// Check if department name was found
if ($resultDeptName->num_rows > 0) {
    $deptRow = $resultDeptName->fetch_assoc();
    $departmentName = $deptRow['dept_name'];
} else {
    echo "Department not found.";
    exit;
}


$query = "
SELECT 
    CONCAT(u.fname, ' ', COALESCE(CONCAT(u.mname, '. '), ''), u.lname) AS full_name,
    u.profile,
    u.UserID,
    COUNT(tu.TaskID) AS total_tasks, 
    SUM(CASE WHEN tu.Status = 'Approved' THEN 1 ELSE 0 END) AS approved_tasks,
    SUM(CASE WHEN tu.Status = 'Missing' THEN 1 ELSE 0 END) AS missing_tasks,
    SUM(CASE WHEN tu.Status = 'Assigned' THEN 1 ELSE 0 END) AS assigned_tasks,
    SUM(CASE WHEN tu.Status IN ('Submitted', 'Approved', 'Rejected') THEN 1 ELSE 0 END) AS submitted_tasks
FROM 
    useracc u
JOIN 
    task_user tu ON u.UserID = tu.UserID
JOIN 
    tasks t ON tu.TaskID = t.TaskID
JOIN 
    feedcontent fc ON t.ContentID = fc.ContentID
WHERE 
    t.Type = 'Task' 
    AND tu.Status IN ('Missing', 'Assigned', 'Submitted', 'Approved')
    AND fc.dept_ID = ?
    AND (
        YEAR(t.TimeStamp) = ? OR  
        YEAR(tu.SubmitDate) = ? OR  
        YEAR(tu.RejectDate) = ? OR 
        YEAR(tu.ApproveDate) = ?  
    )
GROUP BY 
    u.UserID
ORDER BY 
    submitted_tasks DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error in SQL preparation: " . $conn->error);
}

// Bind the department ID and year parameter (multiple times for different date fields)
$stmt->bind_param("iiiii", $dept_ID, $year, $year, $year, $year);

$stmt->execute();
$result = $stmt->get_result();


$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $precision = ($row['total_tasks'] > 0) ? round(($row['submitted_tasks'] / $row['total_tasks']) * 100, 2) : 0;

        $data[] = [
            'full_name' => $row['full_name'],
            'profile' => $row['profile'],
            'precision' => $precision,
            'missing_tasks' => $row['missing_tasks'],
            'assigned_tasks' => $row['assigned_tasks'],
            'submitted_tasks' => $row['submitted_tasks'],
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);

$stmt->close();
$conn->close();
?>