<?php
// auto_assign_tasks.php

include 'connection.php'; // Your database connection file

// Set the timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

function autoAssignTasks() {
    global $conn;

    // Fetch tasks that are scheduled to be assigned
    $sql = "SELECT TaskID, ContentID, UserID, Type, Title, taskContent, Schedule_Date, Schedule_Time, DueDate, DueTime FROM tasks WHERE Type = 'Task' AND Status = 'Schedule'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignedTasks = [];
    $currentDateTime = new DateTime();

    while ($task = $result->fetch_assoc()) {
        $scheduledDateTime = new DateTime($task['Schedule_Date'] . ' ' . $task['Schedule_Time']);

        if ($scheduledDateTime <= $currentDateTime) {
            $updateSql = "UPDATE tasks SET Status = 'Assign' WHERE TaskID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $task['TaskID']);
            
            if ($updateStmt->execute()) {
                $assignedTasks[] = [
                    'task_id' => $task['TaskID']
                ];

                $userName = fetchUserName($task['UserID']);
                $contentTitle = fetchContentTitle($task['ContentID']);

                createNotification(
                    $task['UserID'],
                    $task['TaskID'],
                    $task['ContentID'],
                    $task['Type'],
                    $task['Title'],
                    $task['taskContent'],
                    $userName,
                    $contentTitle
                );

                assignTaskToUsers($task['ContentID'], $task['TaskID']);
                sendSMSNotifications($task['ContentID'], $task['TaskID'], $task['Title'], $task['DueDate'], $task['DueTime'], $userName, $contentTitle);
            }
        }
    }

    return $assignedTasks;
}

function fetchUserName($userID) {
    global $conn;
    $userQuery = $conn->prepare("SELECT CONCAT(fname, ' ', lname) AS fullName FROM useracc WHERE UserID = ?");
    $userQuery->bind_param("s", $userID);
    $userQuery->execute();
    $userName = $userQuery->get_result()->fetch_assoc()['fullName'];
    $userQuery->close();
    return $userName;
}

function fetchContentTitle($contentID) {
    global $conn;
    $contentQuery = $conn->prepare("SELECT CONCAT(Title, '-' , Captions) AS FullContent FROM feedcontent WHERE ContentID = ?");
    $contentQuery->bind_param("s", $contentID);
    $contentQuery->execute();
    $contentResult = $contentQuery->get_result();

    if ($contentResult->num_rows > 0) {
        $contentTitle = $contentResult->fetch_assoc()['FullContent'];
    } else {
        $contentTitle = "Unknown Content";
    }
    $contentQuery->close();
    return $contentTitle;
}

function createNotification($userID, $taskID, $contentID, $type, $title, $taskContent, $userName, $contentTitle) {
    global $conn;

    $notificationTitle = "$userName Posted a new $type! ($contentTitle)";
    $notificationContent = "$title: $taskContent";
    $status = 1;

    $notifSql = "INSERT INTO notifications (UserID, TaskID, ContentID, Title, Content, Status) VALUES (?, ?, ?, ?, ?, ?)";
    $notifStmt = $conn->prepare($notifSql);
    $notifStmt->bind_param("iisssi", $userID, $taskID, $contentID, $notificationTitle, $notificationContent, $status);
    $notifStmt->execute();
    $notifStmt->close();
}

function assignTaskToUsers($contentID, $taskID) {
    global $conn;
    $userContentQuery = $conn->prepare("
        SELECT ua.UserID, uc.Status
        FROM usercontent uc
        JOIN useracc ua ON uc.UserID = ua.UserID
        WHERE uc.ContentID = ?
        AND uc.Status = 1
    ");
    $userContentQuery->bind_param("i", $contentID);
    $userContentQuery->execute();
    $userResult = $userContentQuery->get_result();

    if ($userResult) {
        while ($row = $userResult->fetch_assoc()) {
            $userInContentId = $row['UserID'];
            $taskUserSql = "INSERT INTO task_user (ContentID, TaskID, UserID, Status) VALUES (?, ?, ?, 'Assigned')";
            $taskUserStmt = $conn->prepare($taskUserSql);
            if ($taskUserStmt) {
                $taskUserStmt->bind_param("sss", $contentID, $taskID, $userInContentId);
                $taskUserStmt->execute();
                $taskUserStmt->close();
            }
        }
    }
    $userContentQuery->close();
}

function sendSMSNotifications($contentID, $taskID, $title, $dueDate, $dueTime, $userName, $contentTitle) {
    global $conn;
    
    $mobileQuery = $conn->prepare("
        SELECT ua.mobile, UPPER(CONCAT(ua.fname, ' ', ua.lname)) AS FullName 
        FROM usercontent uc
        JOIN useracc ua ON uc.UserID = ua.UserID
        WHERE uc.ContentID = ?
    ");
    $mobileQuery->bind_param("i", $contentID);
    $mobileQuery->execute();
    $mobileResult = $mobileQuery->get_result();

    if ($mobileResult->num_rows > 0) {
        $mobileNumbers = [];
        $messages = [];

        while ($row = $mobileResult->fetch_assoc()) {
            $mobileNumbers[] = $row['mobile'];
            $messages[] = "NEW TASK ALERT!\n\nHi " . $row['FullName'] . "! " . $userName . " Posted a new Task! ($contentTitle) \"" . $title . "\" Due on " . $dueDate . " at " . $dueTime . ". Don't miss it! Have a nice day!";
        }

        $api_url = "https://api.semaphore.co/api/v4/messages";
        $api_key = $_ENV['SEMAPHORE_API_KEY'] ?? '';
        
        if (!empty($api_key)) {
            foreach ($messages as $index => $message) {
                $number = $mobileNumbers[$index];
                $postData = [
                    'apikey' => $api_key,
                    'number' => $number,
                    'message' => $message
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
    $mobileQuery->close();
}

// Call the function to automatically assign tasks
$assignedTasks = autoAssignTasks();

if (!empty($assignedTasks)) {
    echo json_encode([
        'status' => 'success',
        'assigned_tasks' => $assignedTasks
    ]);
} else {
    echo json_encode([
        'status' => 'no_tasks',
        'message' => 'No tasks were assigned.'
    ]);
}
?>