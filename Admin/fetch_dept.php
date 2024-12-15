<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

header('Content-Type: application/json');

// SQL query to fetch department information and the department head's information
$sql = "
    SELECT 
        d.dept_ID, 
        d.dept_name, 
        d.dept_info,
        CONCAT(u.fname, ' ', IFNULL(u.mname, ''), '.',' ', u.lname) AS full_name,
        u.Profile
    FROM department d
    LEFT JOIN useracc u ON d.dept_ID = u.dept_ID AND u.role = 'Department Head'";

$result = $conn->query($sql);

$departments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = [
            'dept_ID' => $row['dept_ID'],
            'dept_name' => $row['dept_name'],
            'dept_info' => $row['dept_info'],
            'dept_head' => [
                'full_name' => $row['full_name'],
                'profile_image' => $row['Profile']
            ]
        ];
    }
}
echo json_encode(['department' => $departments]);

$conn->close();
?>
