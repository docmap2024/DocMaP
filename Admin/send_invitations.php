<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

// Validate and sanitize inputs
$deptId = filter_input(INPUT_POST, 'deptId', FILTER_VALIDATE_INT);
$deptName = filter_input(INPUT_POST, 'deptName', FILTER_SANITIZE_STRING);
$pin = filter_input(INPUT_POST, 'pin', FILTER_SANITIZE_STRING);
$userIDs = $_POST['userIDs']; // Array of selected user IDs

// Validate userIDs
if (empty($userIDs)) {
    echo json_encode(['status' => 'error', 'message' => 'No users selected']);
    exit;
}

// Ensure userIDs are integers
$userIDs = array_map('intval', $userIDs);

// Fetch department details
$stmt = $conn->prepare("SELECT dept_name, dept_info FROM department WHERE dept_ID = ?");
$stmt->bind_param("i", $deptId);
$stmt->execute();
$result = $stmt->get_result();
$department = $result->fetch_assoc();
$stmt->close();

if (!$department) {
    echo json_encode(['status' => 'error', 'message' => 'Department not found']);
    exit;
}

// Fetch selected users' details
$userIdsStr = implode(',', $userIDs);
$stmt = $conn->prepare("SELECT UserID, fname, lname, email FROM useracc WHERE UserID IN ($userIdsStr)");
$stmt->execute();
$result = $stmt->get_result();
$selectedUsers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Send emails to selected users
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$failedEmails = []; // Track failed emails

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'proftal2024@gmail.com'; // SMTP username
    $mail->Password   = 'ytkj saab gnkb cxwa'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587; // TCP port

    // Recipients
    $mail->setFrom('proftal2024@gmail.com', 'ProfTal');

    // Loop through selected users and send emails
    foreach ($selectedUsers as $user) {
        $mail->clearAddresses(); // Clear previous recipients
        $mail->addAddress($user['email'], $user['fname'] . ' ' . $user['lname']); // Add recipient

        // Generate a secure random token
        $token = bin2hex(random_bytes(16)); // 32-character random token
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token expires in 1 hour

        // Store the token in the database
        $stmt = $conn->prepare("INSERT INTO token (UserID, dept_ID, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user['UserID'], $deptId, $token, $expiresAt);
        $stmt->execute();
        $stmt->close();

        // Generate the join link with the token
        $joinLink = "http://localhost/docmap-ojt/Admin/join_department.php?user_id={$user['UserID']}&dept_ID={$deptId}&token={$token}";

        // Email content with a modern design
        $mail->isHTML(true);
        $mail->Subject = "Invitation to Join Department: {$department['dept_name']}";
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
                    .header { font-size: 24px; color: #333333; margin-bottom: 20px; }
                    .content { font-size: 16px; color: #555555; line-height: 1.6; }
                    .button { display: inline-block; background-color: #4CAF50; color: #ffffff; padding: 12px 24px; text-align: center; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 20px 0; }
                    .footer { font-size: 14px; color: #888888; margin-top: 20px; }  
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='header'>Invitation to Join Department: {$department['dept_name']}</div>
                    <div class='content'>
                        Dear {$user['fname']} {$user['lname']},<br><br>
                        You have been invited to join the department: <strong>{$department['dept_name']}</strong>.<br><br>
                        <strong>Department Information:</strong><br>
                        {$department['dept_info']}<br><br>
                        Click the button below to join the department:<br><br>
                        <a href='{$joinLink}' class='button'>Join Department</a><br><br>
                        If the button doesn't work, copy and paste this link into your browser:<br>
                        <a href='{$joinLink}'>{$joinLink}</a><br><br>
                        Best regards,<br>
                        <strong>Admin</strong>
                    </div>
                    <div class='footer'>
                        This is an automated message. Please do not reply to this email.
                    </div>
                </div>
            </body>
            </html>
        ";

        try {
            $mail->send();
        } catch (Exception $e) {
            $failedEmails[] = $user['email']; // Track failed emails
            error_log("Failed to send email to {$user['email']}: " . $mail->ErrorInfo);
        }
    }

    if (empty($failedEmails)) {
        echo json_encode(['status' => 'success', 'message' => 'Invitations sent successfully!']);
    } else {
        echo json_encode(['status' => 'partial_success', 'message' => 'Invitations sent, but some emails failed.', 'failed_emails' => $failedEmails]);
    }
} catch (Exception $e) {
    error_log("PHPMailer Error: " . $mail->ErrorInfo);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send invitations: ' . $mail->ErrorInfo]);
}

// Close database connection
$conn->close();
?>