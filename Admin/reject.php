<?php
session_start();
require 'connection.php';

function logMessage($message) {
    $log_file = __DIR__ . '/logfile.log';
    error_log(date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, 3, $log_file);
}

header('Content-Type: application/json');

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Semaphore API details
$api_url = "https://api.semaphore.co/api/v4/messages";
$api_key = "d796c0e11273934ac9d789536133684a";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskUserId = $_POST['userID'];
    $comment = $_POST['comment'] ?? null;

    if (empty($taskUserId)) {
        logMessage("Error: Missing required data in reject.php.");
        echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
        exit;
    }

    // Fetch details from task_user table
    $sql = "SELECT TaskID, ContentID, UserID FROM task_user WHERE Task_User_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $taskUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        logMessage("Error: No data found for Task_User_ID $taskUserId in reject.php.");
        echo json_encode(['status' => 'error', 'message' => 'No data found for the provided Task_User_ID.']);
        exit;
    }

    $row = $result->fetch_assoc();
    $taskID = $row['TaskID'];
    $contentID = $row['ContentID'];
    $userID = $row['UserID'];

    $stmt->close();

    // Fetch Title from tasks table
    $sql = "SELECT Title FROM tasks WHERE TaskID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $taskID);
    $stmt->execute();
    $stmt->bind_result($taskTitle);
    $stmt->fetch();
    $stmt->close();

    // Fetch Title and Caption from feedcontent table
    $sql = "SELECT CONCAT(Title, ' - ', Captions) AS ContentDetails FROM feedcontent WHERE ContentID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $contentID);
    $stmt->execute();
    $stmt->bind_result($contentDetails);
    $stmt->fetch();
    $stmt->close();

    // Fetch User details from useracc table
    $sql = "SELECT UPPER(CONCAT(fname, ' ', lname)) AS FullName, mobile FROM useracc WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($fullName, $mobile);
    $stmt->fetch();
    $stmt->close();

    // Update the status in the task_user table
    $status = 'Rejected';
    $rejectDate = date('Y-m-d H:i:s');
    $sql = "UPDATE task_user SET Status = ?, Comment = ?, RejectDate = ? WHERE Task_User_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $status, $comment, $rejectDate, $taskUserId);

    $UserID = $_SESSION['user_id'];
    $userQuery = $conn->prepare("SELECT CONCAT(fname, ' ', lname) AS fullName FROM useracc WHERE UserID = ?");
    $userQuery->bind_param("i", $UserID);
    $userQuery->execute();
    $userName = $userQuery->get_result()->fetch_assoc()['fullName'];
    logMessage("Fetched user name: $userName for UserID: $UserID");

    if ($stmt->execute()) {
        logMessage("Task user status updated to 'Rejected' in reject.php.");

        // Prepare SMS message
        $message = "OH NO! Rejected!\n\nHi, $fullName! Your task from $contentDetails (\"$taskTitle\"), has been REJECTED by $userName. Please check the comments and revise accordingly. Thank you.";
        
        // Send SMS via Semaphore
        $sms_data = [
            'apikey' => $api_key,
            'number' => $mobile,
            'message' => $message,
            'sendername' => 'DocMaP'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($sms_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            logMessage("SMS sent successfully to $mobile in reject.php.");
            echo json_encode([
                'status' => 'success',
                'message' => 'The submission has been rejected successfully and SMS notification sent.',
                'data' => [
                    'taskTitle' => $taskTitle,
                    'contentDetails' => $contentDetails,
                    'fullName' => $fullName,
                    'mobile' => $mobile
                ]
            ]);
        } else {
            logMessage("Error sending SMS to $mobile in reject.php. Response: $response");
            echo json_encode([
                'status' => 'success',
                'message' => 'The submission has been rejected successfully, but SMS notification failed.',
                'data' => [
                    'taskTitle' => $taskTitle,
                    'contentDetails' => $contentDetails,
                    'fullName' => $fullName,
                    'mobile' => $mobile
                ]
            ]);
        }
    } else {
        logMessage("Error updating task user status to 'Rejected' in reject.php.");
        echo json_encode(['status' => 'error', 'message' => 'Error updating task user status.']);
    }

    $stmt->close();
    $conn->close();
}
?>
