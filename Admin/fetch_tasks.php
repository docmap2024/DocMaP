<?php
    include('connection.php'); // Include your database connection here

    // Query to get departments
    $departmentsQuery = "SELECT dept_ID, dept_name FROM department";
    $departmentsResult = $conn->query($departmentsQuery);

    $departments = [];
    
    // Fetch department rows
    while($dept = $departmentsResult->fetch_assoc()) {
        $deptID = $dept['dept_ID'];
        $deptName = $dept['dept_name'];

        // Fetch submitted and assigned task counts for each department
        $submittedQuery = "SELECT COUNT(task_user.UserID) AS totalSubmit 
                   FROM task_user 
                   INNER JOIN tasks ON task_user.TaskID = tasks.TaskID
                   INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
                   WHERE task_user.Status = 'Submitted' 
                   AND tasks.Type = 'Task' 
                   AND feedcontent.dept_ID = $deptID";

        $assignedQuery = "SELECT COUNT(task_user.UserID) AS totalAssigned 
                    FROM task_user 
                    INNER JOIN tasks ON task_user.TaskID = tasks.TaskID
                    INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
                    WHERE tasks.Type = 'Task' 
                    AND feedcontent.dept_ID = $deptID";

        // Query to fetch user names and profile for the department
        $userQuery = "SELECT CONCAT(fname, ' ', mname, ' ', lname) AS fullname, profile 
                      FROM useracc 
                      WHERE dept_ID = $deptID";
        
        $submittedResult = $conn->query($submittedQuery);
        $assignedResult = $conn->query($assignedQuery);
        $userResult = $conn->query($userQuery);

        // Fetch task counts
        $submittedRow = $submittedResult->fetch_assoc();
        $assignedRow = $assignedResult->fetch_assoc();

        $totalSubmit = $submittedRow['totalSubmit'] ?? 0;
        $totalAssigned = $assignedRow['totalAssigned'] ?? 0;

        // Fetch users for this department
        $users = [];
        while($user = $userResult->fetch_assoc()) {
            $users[] = [
                'fullname' => $user['fullname'],
                'profile' => $user['profile']
            ];
        }

        // Add data to departments array
        $departments[] = [
            'dept_ID' => $deptID,
            'dept_name' => $deptName,
            'totalSubmit' => $totalSubmit,
            'totalAssigned' => $totalAssigned,
            'users' => $users
        ];
    }

    // Output the data in JSON format
    echo json_encode($departments);
?>
