<?php
// auto_assign_tasks.php

include 'connection.php'; // Your database connection file

// Set the timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Function to append logs to GitHub
function appendToGitHubLog($message) {
    $githubRepo = "docmap2024/DocMaP"; // Your repo
    $branch = "main";
    $logFilePath = "Admin/logfile.log"; // Path in your repo
    
    $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
    if (!$githubToken) {
        error_log("GitHub token not configured");
        return false;
    }

    // 1. Get current log file content
    $ch = curl_init("https://api.github.com/repos/$githubRepo/contents/$logFilePath?ref=$branch");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $githubToken",
        "User-Agent: DocMaP-Logger",
        "Accept: application/vnd.github.v3+json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $existingContent = "";
    $sha = null;
    
    if ($response) {
        $fileData = json_decode($response, true);
        if (isset($fileData['content'])) {
            $existingContent = base64_decode($fileData['content']);
            $sha = $fileData['sha']; // Needed for updates
        }
    }

    // 2. Append new message
    $timestamp = date("Y-m-d H:i:s");
    $newContent = $existingContent . "[$timestamp] $message\n";

    // 3. Update file on GitHub
    $data = [
        "message" => "Log update " . date('Y-m-d H:i:s'),
        "content" => base64_encode($newContent),
        "branch" => $branch
    ];
    
    if ($sha) {
        $data["sha"] = $sha; // Required for updates
    }

    $ch = curl_init("https://api.github.com/repos/$githubRepo/contents/$logFilePath");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $githubToken",
        "Content-Type: application/json",
        "User-Agent: DocMaP-Logger"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

// Modified write_log function
function write_log($message) {
    $logfile = '/tmp/logfile.log';
    $timestamp = date("Y-m-d H:i:s");
    
    // 1. Write to local tmp file
    file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
    
    // 2. Also append to GitHub (with error handling)
    try {
        if (!appendToGitHubLog($message)) {
            // If GitHub fails, keep the message in local logs
            file_put_contents($logfile, "[$timestamp] [ERROR] Failed to upload to GitHub: $message\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        file_put_contents($logfile, "[$timestamp] [EXCEPTION] GitHub log error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}


function autoAssignTasks() {
    global $conn; // Use your existing connection

    // Fetch tasks that are scheduled to be assigned
    $sql = "SELECT TaskID, ContentID, UserID, Type, Title, taskContent, Schedule_Date, Schedule_Time, DueDate, DueTime FROM tasks WHERE Type = 'Task' AND Status = 'Schedule'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignedTasks = [];
    $currentDateTime = new DateTime(); // Get current date and time
    write_log("Current date and time: " . $currentDateTime->format('Y-m-d H:i:s')); // Log the current date and time

    while ($task = $result->fetch_assoc()) {
        // Log the TaskID and its corresponding data
        write_log("Fetched scheduled task: TaskID: {$task['TaskID']}, UserID: {$task['UserID']}, ContentID: {$task['ContentID']}, Scheduled for: {$task['Schedule_Date']} {$task['Schedule_Time']}");

        // Combine schedule date and time
        $scheduledDateTime = new DateTime($task['Schedule_Date'] . ' ' . $task['Schedule_Time']);
        write_log("Checking task assignment for TaskID: {$task['TaskID']}, Scheduled: {$scheduledDateTime->format('Y-m-d H:i:s')} vs Current: {$currentDateTime->format('Y-m-d H:i:s')}");

        // Check if the scheduled date and time have passed
        if ($scheduledDateTime <= $currentDateTime) {
            // Update the task status to 'Assign'
            $updateSql = "UPDATE tasks SET Status = 'Assign' WHERE TaskID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $task['TaskID']);
            
            if ($updateStmt->execute()) {
                // Log the successful status update
                write_log("TaskID {$task['TaskID']} status updated to 'Assign'");

                // Add the task to the assigned tasks array
                $assignedTasks[] = [
                    'task_id' => $task['TaskID']
                ];

                // Fetch user name for the notification
                $userName = fetchUserName($task['UserID']);
                
                // Fetch content title for the notification
                $contentTitle = fetchContentTitle($task['ContentID']);

                // Create a notification for the user
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

                // Assign task to users associated with the ContentID
                assignTaskToUsers($task['ContentID'], $task['TaskID']);

                // Send SMS notifications
                sendSMSNotifications($task['ContentID'], $task['TaskID'], $task['Title'], $task['DueDate'], $task['DueTime'], $userName, $contentTitle);

            } else {
                // Log if the status update fails
                write_log("Failed to update status for TaskID {$task['TaskID']}: " . $updateStmt->error);
            }
        } else {
            // Log that the task is not ready to be assigned yet
            write_log("TaskID {$task['TaskID']} is scheduled for a future date: {$task['Schedule_Date']} {$task['Schedule_Time']}");
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
    write_log("Fetched user name: $userName for UserID: $userID");
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
        write_log("Fetched content title: $contentTitle for ContentID: $contentID");
    } else {
        $contentTitle = "Unknown Content"; // Default value if no title is found
        write_log("No content title found for ContentID: $contentID");
    }
    $contentQuery->close();
    return $contentTitle;
}

function createNotification($userID, $taskID, $contentID, $type, $title, $taskContent, $userName, $contentTitle) {
    global $conn;

    // Notification title and content
    $notificationTitle = "$userName Posted a new $type! ($contentTitle)";
    $notificationContent = "$title: $taskContent";
    $status = 1; // Assuming 1 indicates the notification is unread or active

    // Insert notification into the notifications table
    $notifSql = "INSERT INTO notifications (UserID, TaskID, ContentID, Title, Content, Status) VALUES (?, ?, ?, ?, ?, ?)";
    $notifStmt = $conn->prepare($notifSql);
    $notifStmt->bind_param("iisssi", $userID, $taskID, $contentID, $notificationTitle, $notificationContent, $status);

    if ($notifStmt->execute()) {
        write_log("Notification added for TaskID $taskID, Title: $notificationTitle");
    } else {
        write_log("Error inserting into notifications: " . $notifStmt->error);
    }

    // Close the statement
    $notifStmt->close();
}


function assignTaskToUsers($contentID, $taskID) {
    global $conn;
    // Fetch users associated with the ContentID from usercontent
    $userContentQuery = $conn->prepare("
    SELECT ua.UserID, uc.Status
    FROM usercontent uc
    JOIN useracc ua ON uc.UserID = ua.UserID
    WHERE uc.ContentID = ?
    AND uc.Status = 1
    ");
    $userContentQuery->bind_param("i", $contentID); // Assuming ContentID is an integer
    $userContentQuery->execute();
    $userResult = $userContentQuery->get_result();

    if ($userResult) {
        while ($row = $userResult->fetch_assoc()) {
            $userInContentId = $row['UserID'];
            // Insert into task_user for each user associated with this ContentID
            $taskUserSql = "INSERT INTO task_user (ContentID, TaskID, UserID, Status) VALUES (?, ?, ?, 'Assigned')";
            $taskUserStmt = $conn->prepare($taskUserSql);
            if ($taskUserStmt) {
                $taskUserStmt->bind_param("sss", $contentID, $taskID, $userInContentId);
                if (!$taskUserStmt->execute()) {
                    write_log("Error inserting into task_user: " . $taskUserStmt->error);
                }
                $taskUserStmt->close();
            } else {
                write_log("Error preparing task_user statement: " . $conn->error);
            }
        }
    } else {
        write_log("Error fetching users for ContentID $contentID: " . $conn->error);
    }
    $userContentQuery->close();
}

function sendSMSNotifications($contentID, $taskID, $title, $dueDate, $dueTime, $userName, $contentTitle) {
    global $conn;
    // Fetch mobile numbers for bulk SMS
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
        $mobileNumbers[] = $row['mobile']; // Add mobile number to the array
        $messages[] = "NEW TASK ALERT!\n\nHi " . $row['FullName'] . "! " . $userName . " Posted a new Task! ($contentTitle) \"" . $title . "\" Due on " . $dueDate . " at " . $dueTime . ". Don't miss it! Have a nice day!";

    }

    // Create comma-separated list of mobile numbers
    $mobileNumbersList = implode(",", $mobileNumbers);

    // Log the message and mobile numbers
    write_log("Mobile numbers for ContentID $contentID: $mobileNumbersList");
    write_log("Messages to be sent: " . implode(" | ", $messages));

    // Send SMS using Semaphore API (example)
    $api_url = "https://api.semaphore.co/api/v4/messages"; // Semaphore API URL
    $api_key = $_ENV['SEMAPHORE_API_KEY'] ?? '';
    if (empty($api_key)) {
        write_log("Semaphore API key not configured");
        return false;
    }

    foreach ($messages as $index => $message) {
        $number = $mobileNumbers[$index]; // Get the corresponding mobile number

        // Prepare POST data
        $postData = [
            'apikey' => $api_key,
            'number' => $number, // Individual number
            'message' => $message
        ];

        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL request
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            write_log("Error sending SMS to number ($number): " . curl_error($ch));
        } else {
            write_log("SMS sent successfully to number: $number");
        }
        curl_close($ch);
    }
} else {
    write_log("No mobile numbers found for ContentID $contentID");
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