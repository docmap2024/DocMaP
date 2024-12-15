<?php
// productivity_data.php
header('Content-Type: application/json');

// Database connection
include ('connection.php');

// SQL query to aggregate productivity by month
$sql = "SELECT 
            DATE_FORMAT(SubmitDate, '%Y-%m') AS Month, 
            COUNT(*) AS SubmittedTasks, 
            SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) AS ApprovedTasks
        FROM task_user
        WHERE SubmitDate IS NOT NULL
        GROUP BY DATE_FORMAT(SubmitDate, '%Y-%m')
        ORDER BY Month";

$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Output JSON
echo json_encode($data);

$conn->close();
?>
