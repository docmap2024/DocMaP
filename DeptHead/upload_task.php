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

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logfile.log');

write_log("Database connected successfully.");

// Function to send SMS via Semaphore API
function send_bulk_sms($conn, $ContentID, $notificationTitle, $Title, $DueDate, $DueTime) {
    $mobileQuery = $conn->prepare("
        SELECT ua.mobile, UPPER(CONCAT(ua.fname, ' ', ua.lname)) AS FullName, sex 
        FROM feedcontent fc  
        JOIN usercontent uc ON fc.ContentID = uc.ContentID
        JOIN useracc ua ON uc.UserID = ua.UserID 
        WHERE uc.ContentID = ?
    ");
    $mobileQuery->bind_param("i", $ContentID);
    $mobileQuery->execute();
    $mobileResult = $mobileQuery->get_result();

    if ($mobileResult->num_rows > 0) {
        $mobileNumbers = [];
        $messages = [];

        while ($row = $mobileResult->fetch_assoc()) {
            $mobileNumbers[] = $row['mobile']; // Add mobile number to the array
            $messages[] = "NEW TASK ALERT!\n\nHi " . $row['FullName'] . "! " . $notificationTitle . " \"" . $Title . "\" Due on " . $DueDate . " at " . $DueTime . ". Don't miss it! Have a nice day!";
        }

        // Create comma-separated list of mobile numbers
        $mobileNumbersList = implode(",", $mobileNumbers);

        // Log the message and mobile numbers
        write_log("Mobile numbers for ContentID $ContentID: $mobileNumbersList");
        write_log("Messages to be sent: " . implode(" | ", $messages));

        // Send SMS using Semaphore API (example)
        $api_url = "https://api.semaphore.co/api/v4/messages"; // Semaphore API URL
        $api_key = "d796c0e11273934ac9d789536133684a"; // Your Semaphore API key

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
        write_log("No mobile numbers found for ContentID $ContentID");
    }
}

// Get data from form
$UserID = $_SESSION['user_id'];
$ContentIDs = isset($_POST['grade']) ? $_POST['grade'] : []; // Get all selected ContentIDs as an array
$Type = 'Task';
$Title = $_POST['title'];
$DueDate = $_POST['due-date'];
$taskContent = $_POST['instructions'];
$DueTime = $_POST['due-time'];
$timeStamp = date('Y-m-d H:i:s'); // Current timestamp
$ApprovalStatus = "Approved"; // Set ApprovalStatus to Approved (no approval required)

// Get schedule date and time from POST if the action is schedule
if ($_POST['taskAction'] === 'Schedule') {
    $ScheduleDate = $_POST['schedule-date'];
    $ScheduleTime = $_POST['schedule-time'];
    $Status = 'Schedule';
} else {
    $ScheduleDate = null;
    $ScheduleTime = null;
    $Status = $_POST['taskAction'] === 'Draft' ? 'Draft' : 'Assign'; // Set to Draft if action is draft
}

write_log("Received form data: UserID = $UserID, ContentIDs = " . implode(", ", $ContentIDs) . ", Type = $Type, Title = $Title, DueDate = $DueDate, taskContent = $taskContent, DueTime = $DueTime, Status = $Status, Schedule Date = $ScheduleDate, Schedule Time = $ScheduleTime");

// File upload handling
$uploadOk = 1;
$target_dir = __DIR__ . '/Attachments/'; // Absolute path to the directory
$allFilesUploaded = true;

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // Create directory if not exists
}

$uploadedFiles = [];

if (isset($_FILES['file']) && count($_FILES['file']['name']) > 0 && !empty($_FILES['file']['name'][0])) {
    $fileCount = count($_FILES['file']['name']);
    write_log("Number of files to upload: $fileCount");

    for ($i = 0; $i < $fileCount; $i++) {
        $fileTmpName = $_FILES['file']['tmp_name'][$i];
        $fileOriginalName = basename($_FILES['file']['name'][$i]);
        $fileType = strtolower(pathinfo($fileOriginalName, PATHINFO_EXTENSION));
        $fileSize = $_FILES['file']['size'][$i];
        $fileMimeType = mime_content_type($fileTmpName);

        // Generate a 6-digit random number
        $randomNumber = rand(100000, 999999);
        $fileName = $randomNumber . "_" . $fileOriginalName; // Format: random_number_original_filename
        $target_file = $target_dir . $fileName; // Full path to the target file

        write_log("Processing file $fileOriginalName: New Name = $fileName, Type = $fileType, Size = $fileSize, MimeType = $fileMimeType");

        // Check file size
        if ($fileSize > 5000000) { // Limit to 5MB
            write_log("File too large: $fileOriginalName");
            $allFilesUploaded = false;
            continue; // Skip to the next file
        }

        // Allow certain file formats
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'pptx');
        if (!in_array($fileType, $allowedTypes)) {
            write_log("Invalid file type: $fileOriginalName");
            $allFilesUploaded = false;
            continue; // Skip to the next file
        }

        // Try to upload file
        if (move_uploaded_file($fileTmpName, $target_file)) {
            write_log("File uploaded: $fileName, Stored at: $target_file");

            // Store file details in an array to insert after task creation
            $uploadedFiles[] = [
                'fileName' => $fileName,
                'fileMimeType' => $fileMimeType,
                'fileSize' => $fileSize,
                'target_file' => $target_file
            ];
        } else {
            write_log("Error uploading file: $fileOriginalName");
            $allFilesUploaded = false;
        }
    }
} else {
    write_log("No files uploaded or file input is empty.");
}

// Insert task into tasks table for each ContentID
foreach ($ContentIDs as $ContentID) {
    $sql = "INSERT INTO tasks (UserID, ContentID, Type, Title, taskContent, DueDate, DueTime, Schedule_Date, Schedule_Time, Status, ApprovalStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssssssssss", $UserID, $ContentID, $Type, $Title, $taskContent, $DueDate, $DueTime, $ScheduleDate, $ScheduleTime, $Status, $ApprovalStatus);

        if ($stmt->execute()) {
            $TaskID = $stmt->insert_id;
            write_log("Task added with ID: $TaskID, UserID: $UserID, ContentID: $ContentID");

            // Insert files into attachment table using the fetched TaskID
            foreach ($uploadedFiles as $file) {
                $docuStmt = $conn->prepare("INSERT INTO attachment (UserID, ContentID, TaskID, name, mimeType, size, uri, TimeStamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $timestamp = date("Y-m-d H:i:s");
                $docuStmt->bind_param("ssssssss", $UserID, $ContentID, $TaskID, $file['fileName'], $file['fileMimeType'], $file['fileSize'], $file['target_file'], $timestamp);

                if (!$docuStmt->execute()) {
                    write_log("Error inserting into attachment: " . $docuStmt->error);
                }
                $docuStmt->close();
            }

            // Fetch associated users for the ContentID
            $userContentQuery = $conn->prepare("SELECT ua.UserID FROM usercontent uc 
                                                JOIN useracc ua ON uc.UserID = ua.UserID 
                                                WHERE uc.ContentID = ?");
            $userContentQuery->bind_param("i", $ContentID);
            $userContentQuery->execute();
            $userResult = $userContentQuery->get_result();

            if ($userResult) {
                while ($row = $userResult->fetch_assoc()) {
                    $userInContentId = $row['UserID'];

                    // Insert into task_user table
                    $taskUserSql = "INSERT INTO task_user (ContentID, TaskID, UserID, Status, SubmitDate) VALUES (?, ?, ?, 'Assigned', ?)";
                    $taskUserStmt = $conn->prepare($taskUserSql);
                    $submitDate = date("Y-m-d H:i:s"); // Current timestamp for SubmitDate
                    $taskUserStmt->bind_param("ssss", $ContentID, $TaskID, $userInContentId, $submitDate);

                    if (!$taskUserStmt->execute()) {
                        write_log("Error inserting into task_user: " . $taskUserStmt->error);
                    }
                    $taskUserStmt->close();
                }
            }

            // Notification logic
            $creatorName = $_SESSION['username']; // Assuming the creator's name is stored in the session
            $notificationTitle = "$creatorName Posted a new Task! ($Title)";
            $notificationContent = "$Title: $taskContent";
            $status = 1;

            $notifStmt = $conn->prepare("INSERT INTO notifications (UserID, TaskID, ContentID, Title, Content, Status) VALUES (?, ?, ?, ?, ?, ?)");
            $notifStmt->bind_param("sssssi", $UserID, $TaskID, $ContentID, $notificationTitle, $notificationContent, $status);

            if ($notifStmt->execute()) {
                $notifID = $notifStmt->insert_id;

                // Insert into notif_user for each associated user
                $userContentQuery->execute(); // Re-execute the query to fetch users again
                $userResult = $userContentQuery->get_result();

                while ($row = $userResult->fetch_assoc()) {
                    $userInContentId = $row['UserID'];
                    $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                    $timestamp = date("Y-m-d H:i:s");
                    $notifUserStmt->bind_param("iiss", $notifID, $userInContentId, $status, $timestamp);

                    if (!$notifUserStmt->execute()) {
                        write_log("Error inserting into notif_user: " . $notifUserStmt->error);
                        mysqli_rollback($conn);
                        return false;
                    }
                    $notifUserStmt->close();
                }

                // Call the SMS sending function
                send_bulk_sms($conn, $ContentID, $notificationTitle, $Title, $DueDate, $DueTime);
            } else {
                write_log("Error inserting into notifications: " . $notifStmt->error);
                mysqli_rollback($conn);
                return false;
            }

            $notifStmt->close();
        } else {
            write_log("Error inserting into tasks: " . $stmt->error);
        }

        $stmt->close();
    } else {
        write_log("Error preparing tasks statement: " . $conn->error);
    }
}

// Set response
header('Content-Type: application/json');
$response = array("success" => true, "message" => "Tasks created successfully.");
if (!$allFilesUploaded) {
    $response = array("success" => false, "message" => "Tasks created, but some files may not have been uploaded.");
}
echo json_encode($response);

$conn->close();
write_log("Database connection closed.");
?>