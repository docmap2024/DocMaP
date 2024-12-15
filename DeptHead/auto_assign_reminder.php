<?php
// auto_assign_tasks.php

include 'connection.php'; // Your database connection file

// Set the timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Create or open a log file to write logs
function write_log($message) {
    $logfile = 'logfile.log'; // Log file path
    $timestamp = date("Y-m-d H:i:s"); // Get current timestamp
    $logMessage = "[{$timestamp}] {$message}\n"; // Format log message
    file_put_contents($logfile, $logMessage, FILE_APPEND); // Append log message to the file
}

function autoAssignTasks() {
    global $conn; // Use your existing connection

    // Fetch tasks that are scheduled to be assigned
    $sql = "SELECT TaskID, ContentID, UserID, Type, Title, taskContent, Schedule_Date, Schedule_Time FROM tasks WHERE Type = 'Reminder' AND Status = 'Schedule'";
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
    $userQuery = $conn->prepare("SELECT fname FROM useracc WHERE UserID = ?");
    $userQuery->bind_param("s", $userID);
    $userQuery->execute();
    $userName = $userQuery->get_result()->fetch_assoc()['fname'];
    write_log("Fetched user name: $userName for UserID: $userID");
    $userQuery->close();
    return $userName;
}

function fetchContentTitle($contentID) {
    global $conn;
    $contentQuery = $conn->prepare("SELECT Title FROM feedcontent WHERE ContentID = ?");
    $contentQuery->bind_param("s", $contentID);
    $contentQuery->execute();
    $contentResult = $contentQuery->get_result();

    if ($contentResult->num_rows > 0) {
        $contentTitle = $contentResult->fetch_assoc()['Title'];
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
