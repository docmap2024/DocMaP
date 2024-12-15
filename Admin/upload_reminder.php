<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

// Log file path
$log_file = 'logfile.log';

// Function to write to log file
function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}


write_log("Database connected successfully.");

// Get data from form
$UserID = $_SESSION['user_id'];
$ContentIDs = isset($_POST['grade']) ? $_POST['grade'] : [];
$Type = 'Reminder';
$Title = $_POST['title'];
$DueDate = $_POST['due-date'];
$taskContent = $_POST['instructions'];
$DueTime = $_POST['due-time'];
$timeStamp = date('Y-m-d H:i:s');

if ($_POST['taskAction'] === 'Schedule') {
    $ScheduleDate = $_POST['schedule-date'];
    $ScheduleTime = $_POST['schedule-time'];
    $Status = 'Schedule';
} else {
    $ScheduleDate = null;
    $ScheduleTime = null;
    $Status = $_POST['taskAction'] === 'Draft' ? 'Draft' : 'Assign';
}

write_log("Received form data: UserID = $UserID, ContentIDs = " . implode(", ", $ContentIDs) . ", Type = $Type, Title = $Title, DueDate = $DueDate, taskContent = $taskContent, DueTime = $DueTime, Status = $Status, Schedule Date = $ScheduleDate, Schedule Time = $ScheduleTime");

// Insert reminder into tasks table for each ContentID
foreach ($ContentIDs as $ContentID) {
    if ($Status == 'Schedule') {
        $sql = "INSERT INTO tasks (UserID, ContentID, Type, Title, taskContent, DueDate, DueTime, Status, Schedule_Date, Schedule_Time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssssss", $UserID, $ContentID, $Type, $Title, $taskContent, $DueDate, $DueTime, $Status, $ScheduleDate, $ScheduleTime);
        } else {
            write_log("Error preparing statement: " . $conn->error);
        }
    } else {
        $sql = "INSERT INTO tasks (UserID, ContentID, Type, Title, taskContent, DueDate, DueTime, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssss", $UserID, $ContentID, $Type, $Title, $taskContent, $DueDate, $DueTime, $Status);
        } else {
            write_log("Error preparing statement: " . $conn->error);
        }
    }

    // Execute the statement
    if ($stmt && $stmt->execute()) {
        $TaskID = $stmt->insert_id; // Get the auto-incremented TaskID
        write_log("Task added with ID: $TaskID, UserID: $UserID, ContentID: $ContentID");

        // Insert into task_user table with NULL status
        $taskUserSql = "INSERT INTO task_user (ContentID, TaskID, UserID, Status) VALUES (?, ?, ?, NULL)";
        $taskUserStmt = $conn->prepare($taskUserSql);
        if ($taskUserStmt) {
            // Assuming you have a way to get users associated with ContentID
            $userContentQuery = $conn->prepare("SELECT UserID FROM usercontent WHERE ContentID = ?");
            $userContentQuery->bind_param("s", $ContentID);
            $userContentQuery->execute();
            $userResult = $userContentQuery->get_result();

            while ($row = $userResult->fetch_assoc()) {
                $userInContentId = $row['UserID'];
                $taskUserStmt->bind_param("ssi", $ContentID, $TaskID, $userInContentId);
                $taskUserStmt->execute();
                write_log("Inserted into task_user for TaskID: $TaskID, UserID: $userInContentId");
            }
            $taskUserStmt->close();
        } else {
            write_log("Error preparing task_user statement: " . $conn->error);
        }

        // Fetch user name for notifications
        $userQuery = $conn->prepare("SELECT fname FROM useracc WHERE UserID = ?");
        $userQuery->bind_param("s", $UserID);
        $userQuery->execute();
        $userName = $userQuery->get_result()->fetch_assoc()['fname'];
        write_log("Fetched user name: $userName for UserID: $UserID");

        // Fetch content title for notifications
        $contentQuery = $conn->prepare("SELECT Title FROM feedcontent WHERE ContentID = ?");
        $contentQuery->bind_param("s", $ContentID);
        $contentQuery->execute();
        $contentResult = $contentQuery->get_result();

        if ($contentResult->num_rows > 0) {
            $contentTitle = $contentResult->fetch_assoc()['Title'];
            write_log("Fetched content title: $contentTitle for ContentID: $ContentID");
        } else {
            $contentTitle = "Unknown Content";
            write_log("No content title found for ContentID: $ContentID");
        }

        // Create notification
        $notificationTitle = "$userName Posted a new $Type! ($contentTitle)";
        $notificationContent = "$Title: $taskContent";

        $notifStmt = $conn->prepare("INSERT INTO notifications (UserID, TaskID, ContentID, Title, Content, Status) VALUES (?, ?, ?, ?, ?, ?)");
        $status = 1;
        $notifStmt->bind_param("sssssi", $UserID, $TaskID, $ContentID, $notificationTitle, $notificationContent, $status);

        if ($notifStmt->execute()) {
            write_log("Notification added for TaskID $TaskID, Title: $notificationTitle");
        } else {
            write_log("Error inserting into notifications: " . $notifStmt->error);
        }

        // Close statements
        $notifStmt->close();
        $userQuery->close();
        $contentQuery->close();
    } else {
        write_log("Error inserting into tasks: " . $stmt->error);
    }

    $stmt->close(); // Close statement after each iteration
}

header('Content-Type: application/json');
$response = array("success" => true, "message" => "Reminders created successfully.");
echo json_encode($response);

// Close connection
$conn->close();
write_log("Database connection closed.");
?>
