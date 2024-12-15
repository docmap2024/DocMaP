<?php
// Include your database connection
include 'connection.php';

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

// Function to send SMS using Semaphore
function sendSms($mobile, $message) {
    $apiKey = 'd796c0e11273934ac9d789536133684a'; // Replace with your Semaphore API key
    $senderName = 'DocMaP'; // Replace with your sender name or ID

    $postData = [
        'apikey' => $apiKey,
        'number' => $mobile,
        'message' => $message,
        'sendername' => $senderName
    ];

    $ch = curl_init('https://semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

try {
    $currentDateTime = new DateTime(); // Current datetime
    $upcomingDateTime = clone $currentDateTime; // Clone to calculate 24 hours ahead
    $upcomingDateTime->add(new DateInterval('PT24H')); // Add 24 hours

    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('H:i:s');
    $upcomingDate = $upcomingDateTime->format('Y-m-d');
    $upcomingTime = $upcomingDateTime->format('H:i:s');

    // Fetch all tasks from the tasks table
    $taskQuery = "SELECT TaskID, DueDate, DueTime, Title FROM tasks";
    $taskResult = $conn->query($taskQuery);

    if ($taskResult->num_rows > 0) {
        while ($task = $taskResult->fetch_assoc()) {
            $taskID = $task['TaskID'];
            $dueDate = $task['DueDate'];
            $dueTime = $task['DueTime'];
            $taskTitle = $task['Title'];

            // Check if the task is overdue
            if ($currentDate > $dueDate || ($currentDate === $dueDate && $currentTime > $dueTime)) {
                $taskUserQuery = "
                    SELECT Task_User_ID, Status 
                    FROM task_user 
                    WHERE TaskID = '$taskID' 
                    AND Status NOT IN ('Missing', 'Approved', 'Rejected', 'Submitted')
                ";
                $taskUserResult = $conn->query($taskUserQuery);

                if ($taskUserResult->num_rows > 0) {
                    while ($taskUser = $taskUserResult->fetch_assoc()) {
                        $taskUserID = $taskUser['Task_User_ID'];
                        $status = $taskUser['Status'];

                        // Update status if assigned and overdue
                        if ($status === 'Assigned') {
                            $updateQuery = "UPDATE task_user SET Status = 'Missing' WHERE Task_User_ID = '$taskUserID'";
                            $conn->query($updateQuery);
                        }
                    }
                }
            }

            // Check if the task is due within the next 24 hours
            if (
                ($dueDate > $currentDate || ($dueDate === $currentDate && $dueTime > $currentTime)) &&
                ($dueDate < $upcomingDate || ($dueDate === $upcomingDate && $dueTime <= $upcomingTime))
            ) {
                $taskUserQuery = "
                    SELECT Task_User_ID, ContentID, UserID, Status, StatusSMS 
                    FROM task_user 
                    WHERE TaskID = '$taskID' 
                    AND Status = 'Assigned'
                ";
                $taskUserResult = $conn->query($taskUserQuery);

                if ($taskUserResult->num_rows > 0) {
                    while ($taskUser = $taskUserResult->fetch_assoc()) {
                        $taskUserID = $taskUser['Task_User_ID'];
                        $contentID = $taskUser['ContentID'];
                        $userID = $taskUser['UserID'];
                        $statusSMS = $taskUser['StatusSMS'];

                        // Only send SMS if StatusSMS is NULL
                        if (is_null($statusSMS) || $statusSMS !== 'Notified') {
                            $feedContentQuery = "
                                SELECT CONCAT(Title, '-', Captions) AS FullContent, dept_ID 
                                FROM feedcontent 
                                WHERE ContentID = '$contentID'
                            ";
                            $feedContentResult = $conn->query($feedContentQuery);
                            $feedContentData = $feedContentResult->fetch_assoc();
                            $fullContent = $feedContentData['FullContent'];
                            $deptID = $feedContentData['dept_ID'];

                            $userAccQuery = "
                                SELECT UPPER(CONCAT(fname, ' ', lname)) AS FullName, mobile 
                                FROM useracc 
                                WHERE UserID = '$userID'
                            ";
                            $userAccResult = $conn->query($userAccQuery);
                            $userAccData = $userAccResult->fetch_assoc();
                            $fullName = $userAccData['FullName'];
                            $mobile = $userAccData['mobile'];

                            // Prepare and send SMS
                            $message = "DUE TOMORROW!\n\nHi, $fullName! Task \"$taskTitle\" from $fullContent is due within 24 hours! Don't miss it! Have a nice day!";
                            sendSms($mobile, $message);

                            // Update status in task_user table
                            $updateQuery = "UPDATE task_user SET StatusSMS = 'Notified' WHERE Task_User_ID = '$taskUserID'";
                            $conn->query($updateQuery);

                            echo "SMS sent to $fullName ($mobile). Task_User_ID: $taskUserID updated to 'Notified'.<br>";
                        }
                    }
                }
            }
        }
    }

    echo "Task statuses checked, updated, and notifications sent successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
