<?php
error_log('GET taskID: ' . $_GET['taskID']);
error_log('GET contentID: ' . $_GET['contentID']);

// Database connection
include 'connection.php';

// Get taskID and contentID from the request
$taskID = isset($_GET['taskID']) ? $_GET['taskID'] : null;
$contentID = isset($_GET['contentID']) ? $_GET['contentID'] : null;

if ($taskID && $contentID) {
    try {
        // Query to get the Title from the tasks table
        $taskQuery = "SELECT Title AS taskTitle FROM tasks WHERE TaskID = ?";
        $taskStmt = $conn->prepare($taskQuery);
        $taskStmt->bind_param("i", $taskID);
        $taskStmt->execute();
        $taskResult = $taskStmt->get_result();
        $taskTitle = $taskResult->num_rows > 0 ? $taskResult->fetch_assoc()['taskTitle'] : "No Task Title";

        // Query to get Title + Caption from the feedcontent table
        $contentQuery = "SELECT CONCAT(Title, ' ', Captions) AS feedContentTitleCaption, dept_ID FROM feedcontent WHERE ContentID = ?";
        $contentStmt = $conn->prepare($contentQuery);
        $contentStmt->bind_param("i", $contentID);
        $contentStmt->execute();
        $contentResult = $contentStmt->get_result();
        $contentData = $contentResult->num_rows > 0 ? $contentResult->fetch_assoc() : null;
        $feedContentTitleCaption = $contentData ? $contentData['feedContentTitleCaption'] : "No Title + Caption";
        $deptID = $contentData ? $contentData['dept_ID'] : null;

        // Query to get dept_name from the department table
        $deptName = "No Department Name"; // Default value
        if ($deptID) {
            $deptQuery = "SELECT dept_name FROM department WHERE dept_ID = ?";
            $deptStmt = $conn->prepare($deptQuery);
            $deptStmt->bind_param("i", $deptID);
            $deptStmt->execute();
            $deptResult = $deptStmt->get_result();
            $deptName = $deptResult->num_rows > 0 ? $deptResult->fetch_assoc()['dept_name'] : "No Department Name";
        }

        // Query to get all userIDs from the task_user table
        $userQuery = "SELECT UserID FROM task_user WHERE TaskID = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $taskID);
        $userStmt->execute();
        $userResult = $userStmt->get_result();

        // Initialize an array to hold user date data
        $userDates = [];

        // For each userID, fetch the SubmitDate, ApproveDate, RejectDate
        while ($userRow = $userResult->fetch_assoc()) {
            $userID = $userRow['UserID'];
            
            // Query to get dates for the userID
            $dateQuery = "SELECT SubmitDate, ApproveDate, RejectDate FROM task_user WHERE UserID = ? AND TaskID = ?";
            $dateStmt = $conn->prepare($dateQuery);
            $dateStmt->bind_param("ii", $userID, $taskID);
            $dateStmt->execute();
            $dateResult = $dateStmt->get_result();
            
            if ($dateResult->num_rows > 0) {
                $dates = $dateResult->fetch_assoc();
                $userDates[] = [
                    'UserID' => $userID,
                    'SubmitDate' => $dates['SubmitDate'],
                    'ApproveDate' => $dates['ApproveDate'],
                    'RejectDate' => $dates['RejectDate']
                ];
            }
        }

        // Prepare response
        $response = [
            'taskTitle' => $taskTitle,
            'feedContentTitleCaption' => $feedContentTitleCaption,
            'deptName' => $deptName,
            'userDates' => $userDates // Include the userDates data
        ];

        // Return the response as JSON
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred while fetching data']);
    }
}
?>
