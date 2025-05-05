<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Get data from form
$UserID = $_SESSION['user_id'];
$ContentIDs = isset($_POST['grade']) ? $_POST['grade'] : [];
$Type = 'Announcement';
$Title = $_POST['title'];
$taskContent = $_POST['instructions'];
$timeStamp = date('Y-m-d H:i:s');
$ApprovalStatus = "Approved";
$dept_ID = isset($_POST['dept_ID']) ? $_POST['dept_ID'] : null;

$deptIDs = $dept_ID ? explode(',', $dept_ID) : [];

// Optional due date and time
$DueDate = NULL;
$DueTime = NULL;

// Get schedule date and time from POST if the action is schedule
if ($_POST['taskAction'] === 'Schedule') {
    $ScheduleDate = $_POST['schedule-date'];
    $ScheduleTime = $_POST['schedule-time'];
    $Status = 'Schedule';
} else {
    $ScheduleDate = null;
    $ScheduleTime = null;
    $Status = $_POST['taskAction'] === 'Draft' ? 'Draft' : 'Assign';
}

// File upload handling
$allFilesUploaded = true;
$uploadedFiles = [];

if (isset($_FILES['file']) && count($_FILES['file']['name']) > 0 && !empty($_FILES['file']['name'][0])) {
    $fileCount = count($_FILES['file']['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        $fileTmpName = $_FILES['file']['tmp_name'][$i];
        $fileOriginalName = basename($_FILES['file']['name'][$i]);
        $fileType = strtolower(pathinfo($fileOriginalName, PATHINFO_EXTENSION));
        $fileSize = $_FILES['file']['size'][$i];
        $fileMimeType = mime_content_type($fileTmpName);

        $randomNumber = rand(100000, 999999);
        $fileName = $randomNumber . "_" . $fileOriginalName;

        // Check file size
        if ($fileSize > 5000000) {
            $allFilesUploaded = false;
            continue;
        }

        // Allow certain file formats
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'pptx');
        if (!in_array($fileType, $allowedTypes)) {
            $allFilesUploaded = false;
            continue;
        }

        // GitHub Repository Details
        $githubRepo = "docmap2024/DocMaP";
        $branch = "main";
        $uploadUrl = "https://api.github.com/repos/$githubRepo/contents//Admin/Attachments/$fileName";
    
        // Fetch GitHub Token from Environment Variables
        $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
        if (!$githubToken) {
            $allFilesUploaded = false;
            continue;
        }
    
        // Prepare File Data for GitHub
        $fileContent = file_get_contents($fileTmpName);
        $content = base64_encode($fileContent);
        $data = json_encode([
            "message" => "Adding a new file to upload folder",
            "content" => $content,
            "branch" => $branch
        ]);
    
        $headers = [
            "Authorization: token $githubToken",
            "Content-Type: application/json",
            "User-Agent: DocMaP",
            "Accept: application/vnd.github.v3+json"
        ];
    
        // GitHub API Call
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if ($response === false) {
            $allFilesUploaded = false;
        } else {
            $responseData = json_decode($response, true);
            if ($httpCode == 201) {
                $githubDownloadUrl = $responseData['content']['download_url'];
    
                $uploadedFiles[] = [
                    'fileName' => $fileName,
                    'fileMimeType' => $fileMimeType,
                    'fileSize' => $fileSize,
                    'target_file' => $githubDownloadUrl
                ];
            } else {
                $allFilesUploaded = false;
            }
        }
    
        curl_close($ch);
    }
}

// Insert task into tasks table
if (empty($ContentIDs)) {
    $sql = "INSERT INTO tasks (UserID, Type, Title, taskContent, DueDate, DueTime, Schedule_Date, Schedule_Time, Status, ApprovalStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssssssssss", $UserID, $Type, $Title, $taskContent, $DueDate, $DueTime, $ScheduleDate, $ScheduleTime, $Status, $ApprovalStatus);

        if ($stmt->execute()) {
            $TaskID = $stmt->insert_id;

            if (!empty($deptIDs)) {
                foreach ($deptIDs as $deptID) {
                    $taskDeptStmt = $conn->prepare("INSERT INTO task_department (TaskID, dept_ID) VALUES (?, ?)");
                    $taskDeptStmt->bind_param("ii", $TaskID, $deptID);
                    $taskDeptStmt->execute();
                    $taskDeptStmt->close();
                }
            }

            foreach ($uploadedFiles as $file) {
                $docuStmt = $conn->prepare("INSERT INTO attachment (UserID, TaskID, name, mimeType, size, uri, TimeStamp) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $timestamp = date("Y-m-d H:i:s");
                $docuStmt->bind_param("sssssss", $UserID, $TaskID, $file['fileName'], $file['fileMimeType'], $file['fileSize'], $file['target_file'], $timestamp);
                $docuStmt->execute();
                $docuStmt->close();
            }

            if ($_POST['taskAction'] === 'Assign') {
                $userDeptQuery = $conn->prepare("
                    SELECT ud.UserID
                    FROM user_department ud
                    WHERE ud.dept_ID IN (" . implode(",", $deptIDs) . ")
                ");
                $userDeptQuery->execute();
                $userResult = $userDeptQuery->get_result();

                if ($userResult) {
                    while ($row = $userResult->fetch_assoc()) {
                        $userInDeptId = $row['UserID'];
                        $taskUserStmt = $conn->prepare("INSERT INTO task_user (TaskID, UserID, Status) VALUES (?, ?, 'Assigned')");
                        if ($taskUserStmt) {
                            $taskUserStmt->bind_param("ii", $TaskID, $userInDeptId);
                            $taskUserStmt->execute();
                            $taskUserStmt->close();
                        }
                    }
                }

                $userQuery = $conn->prepare("SELECT CONCAT(fname, ' ', lname) AS fullName FROM useracc WHERE UserID = ?");
                $userQuery->bind_param("s", $UserID);
                $userQuery->execute();
                $userName = $userQuery->get_result()->fetch_assoc()['fullName'];

                $notificationTitle = "$userName posted a new $Type!";
                $notificationContent = "$Title: $taskContent";

                $notifStmt = $conn->prepare("INSERT INTO notifications (UserID, TaskID, Title, Content, Status) VALUES (?, ?, ?, ?, ?)");
                $status = 1;
                $notifStmt->bind_param("iissi", $UserID, $TaskID, $notificationTitle, $notificationContent, $status);

                if ($notifStmt->execute()) {
                    $notifID = $notifStmt->insert_id;

                    $userDeptQuery = $conn->prepare("
                        SELECT ud.UserID
                        FROM user_department ud
                        WHERE ud.dept_ID IN (" . implode(",", $deptIDs) . ")
                    ");
                    $userDeptQuery->execute();
                    $userDeptResult = $userDeptQuery->get_result();

                    if ($userDeptResult) {
                        while ($row = $userDeptResult->fetch_assoc()) {
                            $userInDeptId = $row['UserID'];
                            $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                            $timestamp = date("Y-m-d H:i:s");
                            $status = 1;
                            $notifUserStmt->bind_param("iiss", $notifID, $userInDeptId, $status, $timestamp);
                            $notifUserStmt->execute();
                            $notifUserStmt->close();
                        }
                    }

                    $mobileQuery = $conn->prepare("
                        SELECT ua.mobile, UPPER(CONCAT(ua.fname, ' ', ua.lname)) AS FullName 
                        FROM user_department ud
                        JOIN useracc ua ON ud.UserID = ua.UserID
                        WHERE ud.dept_ID = ?
                    ");
                    $mobileQuery->bind_param("i", $deptID);
                    $mobileQuery->execute();
                    $mobileResult = $mobileQuery->get_result();
                    
                    if ($mobileResult->num_rows > 0) {
                        $mobileNumbers = [];
                        $messages = [];
                    
                        while ($row = $mobileResult->fetch_assoc()) {
                            $mobileNumbers[] = $row['mobile'];
                            $messages[] = "NEW ANNOUNCEMENT ALERT!\n\nHi " . $row['FullName'] . "! " . $notificationTitle . " \"" . $Title . "\"" . " Don't miss it! Have a nice day!";
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

                    $userDeptQuery->close();
                }

                $notifStmt->close();
                $userQuery->close();
            }

            $stmt->close();
        }
    }
} else {
    foreach ($ContentIDs as $ContentID) {
        $sql = "INSERT INTO tasks (UserID, ContentID, Type, Title, taskContent, DueDate, DueTime, Schedule_Date, Schedule_Time, Status, ApprovalStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssssssssss", $UserID, $ContentID, $Type, $Title, $taskContent, $DueDate, $DueTime, $ScheduleDate, $ScheduleTime, $Status, $ApprovalStatus);

            if ($stmt->execute()) {
                $TaskID = $stmt->insert_id;

                foreach ($uploadedFiles as $file) {
                    $docuStmt = $conn->prepare("INSERT INTO attachment (UserID, ContentID, TaskID, name, mimeType, size, uri, TimeStamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $timestamp = date("Y-m-d H:i:s");
                    $docuStmt->bind_param("ssssssss", $UserID, $ContentID, $TaskID, $file['fileName'], $file['fileMimeType'], $file['fileSize'], $file['target_file'], $timestamp);
                    $docuStmt->execute();
                    $docuStmt->close();
                }

                if ($_POST['taskAction'] === 'Assign') {
                    $userContentQuery = $conn->prepare("
                    SELECT ua.UserID, uc.Status
                    FROM usercontent uc
                    JOIN useracc ua ON uc.UserID = ua.UserID
                    WHERE uc.ContentID = ?
                    AND uc.Status = 1
                    ");
                    $userContentQuery->bind_param("i", $ContentID);
                    $userContentQuery->execute();
                    $userResult = $userContentQuery->get_result();

                    if ($userResult) {
                        while ($row = $userResult->fetch_assoc()) {
                            $userInContentId = $row['UserID'];
                            $taskUserStmt = $conn->prepare("INSERT INTO task_user (ContentID, TaskID, UserID, Status) VALUES (?, ?, ?, 'Assigned')");
                            if ($taskUserStmt) {
                                $taskUserStmt->bind_param("sss", $ContentID, $TaskID, $userInContentId);
                                $taskUserStmt->execute();
                                $taskUserStmt->close();
                            }
                        }
                    }

                    $userQuery = $conn->prepare("SELECT CONCAT(fname, ' ', lname) AS fullName FROM useracc WHERE UserID = ?");
                    $userQuery->bind_param("s", $UserID);
                    $userQuery->execute();
                    $userName = $userQuery->get_result()->fetch_assoc()['fullName'];

                    $contentQuery = $conn->prepare("SELECT Title , Captions FROM feedcontent WHERE ContentID = ?");
                    $contentQuery->bind_param("s", $ContentID);
                    $contentQuery->execute();
                    $contentResult = $contentQuery->get_result();

                    if ($contentResult->num_rows > 0) {
                        $row = $contentResult->fetch_assoc();
                        $contentTitle = $row['Title'];
                        $contentCaptions = $row['Captions'];
                        $fullContent = $contentTitle . ' - ' . $contentCaptions;
                    } else {
                        $fullContent = "Unknown Content";
                    }

                    $notificationTitle = "$userName posted a new $Type! ($fullContent)";
                    $notificationContent = "$Title: $taskContent";

                    $notifStmt = $conn->prepare("INSERT INTO notifications (UserID, TaskID, ContentID, Title, Content, Status) VALUES (?, ?, ?, ?, ?, ?)");
                    $status = 1;
                    $notifStmt->bind_param("sssssi", $UserID, $TaskID, $ContentID, $notificationTitle, $notificationContent, $status);

                    if ($notifStmt->execute()) {
                        $notifID = $notifStmt->insert_id;

                        $userContentQuery = $conn->prepare("SELECT ua.UserID FROM usercontent uc JOIN useracc ua ON uc.UserID = ua.UserID WHERE uc.ContentID = ?");
                        $userContentQuery->bind_param("i", $ContentID);
                        $userContentQuery->execute();
                        $userContentResult = $userContentQuery->get_result();

                        if ($userContentResult) {
                            while ($row = $userContentResult->fetch_assoc()) {
                                $userInContentId = $row['UserID'];
                                $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                                $timestamp = date("Y-m-d H:i:s");
                                $status = 1;
                                $notifUserStmt->bind_param("iiss", $notifID, $userInContentId, $status, $timestamp);
                                $notifUserStmt->execute();
                                $notifUserStmt->close();
                            }
                        }

                        $mobileQuery = $conn->prepare("
                            SELECT ua.mobile, UPPER(CONCAT(ua.fname, ' ', ua.lname)) AS FullName 
                            FROM usercontent uc
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
                                $mobileNumbers[] = $row['mobile'];
                                $messages[] = "NEW ANNOUNCEMENT ALERT!\n\nHi " . $row['FullName'] . "! " . $notificationTitle . " \"" . $Title . "\" Due on " . $DueDate . " at " . $DueTime . ". Don't miss it! Have a nice day!";
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

                        $userContentQuery->close();
                    }

                    $notifStmt->close();
                    $userQuery->close();
                    $contentQuery->close();
                }

                $stmt->close();
            }
        }
    }
}

$response = array("success" => true, "message" => "Announcement created successfully.");
if (!$allFilesUploaded) {
    $response = array("success" => false, "message" => "Announcement created, but some files may not have been uploaded.");
}
echo json_encode($response);

$conn->close();
?>