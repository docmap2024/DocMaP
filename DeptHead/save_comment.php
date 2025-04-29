<?php
// Database connection
include 'connection.php'; // Make sure this file contains your database connection logic

header('Content-Type: application/json');

// Start session to access session variables
session_start();

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve posted data
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : null;
    $outgoing_id = isset($_POST['outgoing_id']) ? intval($_POST['outgoing_id']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Retrieve incoming ID from session (the user who is commenting)
    $incoming_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    // Prepare timestamp and status
    $timestamp = date("Y-m-d H:i:s");
    $status = 1;  // Status is 1 for all users

    // Validate input data
    if ($task_id > 0 && $outgoing_id > 0 && $incoming_id > 0 && !empty($comment)) {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // 1. Insert the comment
            $sql = "INSERT INTO comments (ContentID, TaskID, IncomingID, OutgoingID, Comment) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiss", $content_id, $task_id, $incoming_id, $outgoing_id, $comment);
            $stmt->execute();
            $stmt->close();

            // 2. Get task details and the user who needs to be notified
            $task_query = "SELECT t.Title, ua.fname, ua.lname 
                          FROM task_user tu
                          JOIN tasks t ON tu.TaskID = t.TaskID
                          JOIN useracc ua ON tu.UserID = ua.UserID
                          WHERE tu.TaskID = ?";
            $stmt = $conn->prepare($task_query);
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            $task_result = $stmt->get_result();
            
            if ($task_result->num_rows > 0) {
                $task_data = $task_result->fetch_assoc();
                $task_title = $task_data['Title'];
                $uploader_name = $task_data['fname'] . ' ' . $task_data['lname'];
                
                // 3. Get the commenter's name
                $commenter_query = "SELECT fname, lname FROM useracc WHERE UserID = ?";
                $stmt = $conn->prepare($commenter_query);
                $stmt->bind_param("i", $incoming_id);
                $stmt->execute();
                $commenter_result = $stmt->get_result();
                $commenter_data = $commenter_result->fetch_assoc();
                $commenter_name = $commenter_data['fname'] . ' ' . $commenter_data['lname'];
                
                // 4. Create notification content
                $notification_title = "New Comment from " . $commenter_name;
                $notification_content = $commenter_name . " commented on your task's output on '" . $task_title . "': " . $comment;
                
                // 5. Insert into notifications table
                $notif_sql = "INSERT INTO notifications 
                              (UserID, ContentID, TaskID, Title, Content, Status, TimeStamp) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($notif_sql);
                $stmt->bind_param("iiissis", $outgoing_id, $content_id, $task_id, $notification_title, $notification_content, $status, $timestamp);
                $stmt->execute();
                $notification_id = $conn->insert_id;
                $stmt->close();
                
                // 6. Insert into notif_user table
                $notif_user_sql = "INSERT INTO notif_user 
                                  (NotifID, UserID, Status, TimeStamp) 
                                  VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($notif_user_sql);
                $stmt->bind_param("iiis", $notification_id, $outgoing_id, $status, $timestamp);
                $stmt->execute();
                $stmt->close();
                
                // 7. Send SMS notification if outgoing_id is valid and we have uploader name
                if ($outgoing_id > 0 && !empty($uploader_name)) {
                    // Get mobile number of the outgoing user
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
                        
                        // Ensure Philippine numbers start with 63 (if local numbers are stored as 09...)
                        if (strlen($mobile_number) == 10 && strpos($mobile_number, '09') === 0) {
                            $mobile_number = '63' . substr($mobile_number, 1);
                        }
                        
                        if (!empty($mobile_number) && strlen($mobile_number) >= 11) {
                            // Determine time-based greeting
                            $current_hour = date('H');
                            $greeting = ($current_hour < 12) ? "Good day" : "Good evening";
                            
                            // Prepare SMS message
                            $sms_message = "$greeting $uploader_name!\n\n" .
                                         "$commenter_name left a new comment on your task's output on\"$task_title\":\n" .
                                         substr($comment, 0, 120) . "...\n\n" .
                                         "Please check the full comment on the website.";
                            
                            // Send SMS using Semaphore API
                            $api_url = "https://api.semaphore.co/api/v4/messages";
                            $api_key = "d796c0e11273934ac9d789536133684a"; // Replace with your actual API key
                            
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
                                error_log("SMS content: " . $sms_message);
                            }
                            
                            curl_close($ch);
                        }
                    }
                    $mobile_stmt->close();
                }
                
                // Commit transaction
                $conn->commit();
                
                // Return success with additional data if needed
                echo json_encode([
                    'success' => true, 
                    'message' => 'Comment saved and notification sent successfully!',
                    'mobile' => $mobile_number,
                    'notified_user' => $uploader_name,
                    'sms_sent' => isset($response) ? true : false
                ]);
            } else {
                // No task/user found
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Task or user not found.']);
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        // Invalid input data
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    }
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Close the database connection
$conn->close();
?>