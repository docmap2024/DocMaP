<?php
session_start();
include 'connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'You need to log in to post a comment.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$content_id = isset($_POST['content_id']) && $_POST['content_id'] !== '' ? intval($_POST['content_id']) : null;
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate input
if (empty($task_id) || empty($message)) {
    echo json_encode(['error' => 'Task ID and message are required.']);
    exit();
}

// Check if content_id exists in feedcontent if provided
if ($content_id !== null) {
    $check_sql = "SELECT ContentID FROM feedcontent WHERE ContentID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $content_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows === 0) {
        echo json_encode(['error' => 'Invalid Content ID provided.']);
        $check_stmt->close();
        $conn->close();
        exit();
    }
    $check_stmt->close();
}

// Get task details and determine who to notify
$uploader_id = 0;
$uploader_name = '';
$commenter_name = '';
$task_title = '';
$outgoing_id = 0;

// Get task uploader details and task title
$uploader_sql = "SELECT u.UserID, u.fname, u.lname, t.Title 
                FROM tasks t
                JOIN useracc u ON t.UserID = u.UserID
                WHERE t.TaskID = ?";
$uploader_stmt = $conn->prepare($uploader_sql);
$uploader_stmt->bind_param("i", $task_id);
$uploader_stmt->execute();
$uploader_result = $uploader_stmt->get_result();

if ($uploader_result->num_rows > 0) {
    $uploader_data = $uploader_result->fetch_assoc();
    $uploader_id = $uploader_data['UserID'];
    $uploader_name = $uploader_data['fname'] . ' ' . $uploader_data['lname'];
    $task_title = $uploader_data['Title'];
}
$uploader_stmt->close();

// Get most recent commenter for this task (if any)
$comment_sql = "SELECT IncomingID FROM comments 
               WHERE TaskID = ? 
               ORDER BY Timestamp DESC 
               LIMIT 1";
$comment_stmt = $conn->prepare($comment_sql);
$comment_stmt->bind_param("i", $task_id);
$comment_stmt->execute();
$comment_stmt->bind_result($latest_commenter_id);
$comment_stmt->fetch();
$comment_stmt->close();

// Determine outgoing_id - prioritize recent commenter over uploader
$outgoing_id = ($latest_commenter_id !== null) ? $latest_commenter_id : $uploader_id;

// Get name of the current commenter
$commenter_sql = "SELECT fname, lname FROM useracc WHERE UserID = ?";
$commenter_stmt = $conn->prepare($commenter_sql);
$commenter_stmt->bind_param("i", $user_id);
$commenter_stmt->execute();
$commenter_result = $commenter_stmt->get_result();

if ($commenter_result->num_rows > 0) {
    $commenter_data = $commenter_result->fetch_assoc();
    $commenter_name = $commenter_data['fname'] . ' ' . $commenter_data['lname'];
}
$commenter_stmt->close();

// Insert comment into the database
if ($content_id !== null) {
    $sql = "INSERT INTO comments (ContentID, TaskID, IncomingID, OutgoingID, Comment) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $incoming_id = $user_id;
        $stmt->bind_param("iiiss", $content_id, $task_id, $incoming_id, $outgoing_id, $message);
    }
} else {
    $sql = "INSERT INTO comments (TaskID, IncomingID, OutgoingID, Comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $incoming_id = $user_id;
        $stmt->bind_param("iiss", $task_id, $incoming_id, $outgoing_id, $message);
    }
}

if ($stmt && $stmt->execute()) {
    $comment_id = $stmt->insert_id;
    $timestamp = date("Y-m-d H:i:s");
    $status = 1;
    
    // Only notify if outgoing_id is different from current user (don't notify yourself)
    if ($outgoing_id != $user_id) {
        // Create notification content
        $notification_title = "New Comment from " . $commenter_name;
        $notification_content = $commenter_name . " commented on \"" . $task_title . "\": " . 
                             substr($message, 0, 100) . "... Please check the full comment on the website.";
        
        // Insert notification
        $notification_sql = "INSERT INTO notifications 
                            (UserID, ContentID, TaskID, Title, Content, Status, TimeStamp) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $notification_stmt = $conn->prepare($notification_sql);
        
        if ($notification_stmt) {
            $notification_stmt->bind_param("iiissss", $outgoing_id, $content_id, $task_id, 
                                        $notification_title, $notification_content, $status, $timestamp);
            $notification_stmt->execute();
            $notif_id = $notification_stmt->insert_id;
            $notification_stmt->close();
            
            // Insert into notif_user table
            if ($notif_id) {
                $notif_user_sql = "INSERT INTO notif_user 
                                (NotifID, UserID, Status, TimeStamp) 
                                VALUES (?, ?, ?, ?)";
                $notif_user_stmt = $conn->prepare($notif_user_sql);
                
                if ($notif_user_stmt) {
                    $notif_user_stmt->bind_param("iiis", $notif_id, $outgoing_id, $status, $timestamp);
                    $notif_user_stmt->execute();
                    $notif_user_stmt->close();
                }
            }
        }
        
        // Send SMS notification
        if ($outgoing_id > 0) {
            $mobile_sql = "SELECT mobile FROM useracc WHERE UserID = ?";
            $mobile_stmt = $conn->prepare($mobile_sql);
            $mobile_stmt->bind_param("i", $outgoing_id);
            $mobile_stmt->execute();
            $mobile_result = $mobile_stmt->get_result();
            
            if ($mobile_result->num_rows > 0) {
                $user_data = $mobile_result->fetch_assoc();
                $mobile_number = $user_data['mobile'];
                
                // Clean and validate mobile number
                $mobile_number = preg_replace('/[^0-9]/', '', $mobile_number);
                
                if (strlen($mobile_number) == 10 && strpos($mobile_number, '09') === 0) {
                    $mobile_number = '63' . substr($mobile_number, 1);
                }
                
                if (!empty($mobile_number) && strlen($mobile_number) >= 11) {
                    $current_hour = date('H');
                    $greeting = ($current_hour < 12) ? "Good day" : "Good evening";
                    
                    $recipient_name = ($outgoing_id == $uploader_id) ? $uploader_name : 
                                     "Task participant"; // Or fetch actual name if needed
                    
                    $sms_message = "$greeting $recipient_name!\n\n" .
                                 "$commenter_name left a new comment on \"$task_title\":\n" .
                                 substr($message, 0, 120) . "...\n\n" .
                                 "Please check the full comment on the website.";
                    
                    // Send SMS using Semaphore API
                    $api_url = "https://api.semaphore.co/api/v4/messages";
                    $api_key = "d796c0e11273934ac9d789536133684a";
                    
                    $postData = [
                        'apikey' => $api_key,
                        'number' => $mobile_number,
                        'message' => $sms_message,
                    ];
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    $response = curl_exec($ch);
                    
                    if (curl_errno($ch)) {
                        error_log("Error sending SMS to {$mobile_number}: " . curl_error($ch));
                    } else {
                        error_log("SMS sent successfully to {$mobile_number}");
                    }
                    
                    curl_close($ch);
                }
            }
            $mobile_stmt->close();
        }
    }
    
    http_response_code(200);
    exit();
} else {
    $error = $stmt ? $stmt->error : $conn->error;
    echo json_encode(['error' => 'Error inserting comment: ' . $error]);
}

if ($stmt) {
    $stmt->close();
}
$conn->close();
?>
