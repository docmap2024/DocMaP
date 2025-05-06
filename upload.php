<?php
session_start();

// Redirect to index.php if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$response = array('success' => false, 'error' => null);

function createNotification($conn, $user_id, $content_id, $task_id, $title, $content) {
    $sql = "INSERT INTO notifications (UserID, ContentID, TaskID, Title, Content, Status, TimeStamp) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $user_id, $content_id, $task_id, $title, $content);

    if ($stmt->execute()) {
        return $stmt->insert_id; // Return the NotifID
    } else {
        $response['error'] = "Error creating notification: " . $stmt->error;
        return false;
    }
}

function getDepartmentHeadUserID($conn, $dept_ID) {
    $headQuery = "SELECT UserID FROM useracc WHERE dept_ID = ? AND role = 'Department Head'";
    $headStmt = $conn->prepare($headQuery);
    $headStmt->bind_param("i", $dept_ID);
    $headStmt->execute();
    $headResult = $headStmt->get_result();

    if ($headResult && $headResult->num_rows > 0) {
        $headRow = $headResult->fetch_assoc();
        return $headRow['UserID'];
    }
    return false;
}

function getAdminUserID($conn) {
    $adminQuery = "SELECT UserID FROM useracc WHERE role = 'Admin' LIMIT 1";
    $adminResult = mysqli_query($conn, $adminQuery);

    if ($adminResult && mysqli_num_rows($adminResult) > 0) {
        $adminRow = mysqli_fetch_assoc($adminResult);
        return $adminRow['UserID'];
    }
    return false;
}

function write_log($message) {
    error_log($message);
}

if (isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $content_id = isset($_POST['content_id']) ? $_POST['content_id'] : null;
    $user_id = $_SESSION['user_id'];

    // Update document status to 'Submitted' if not already submitted
    $updateStatusQuery = "UPDATE documents SET Status = 1 WHERE UserID = '$user_id' AND TaskID = '$task_id'";
    if ($content_id) {
        $updateStatusQuery .= " AND ContentID = '$content_id'";
    }
    $updateStatusQuery .= " AND Status != 1";
    
    $updateStatusResult = mysqli_query($conn, $updateStatusQuery);

    if (!$updateStatusResult) {
        $response['error'] = "Error updating document status: " . mysqli_error($conn);
        echo json_encode($response);
        exit();
    }

    // If there is a file uploaded
    if (!empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['name'] as $key => $name) {
            $fileTmpName = $_FILES['files']['tmp_name'][$key];
            $originalFileName = $name;
            $fileSize = $_FILES['files']['size'][$key];
            $fileType = $_FILES['files']['type'][$key];

            // Generate unique file name
            $rd2 = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $fileName = $rd2 . '_' . $originalFileName;

            // Check if the file is already in database with submitted status
            $checkQuery = "SELECT DocuID, Status FROM documents WHERE UserID = '$user_id' AND TaskID = '$task_id'";
            if ($content_id) {
                $checkQuery .= " AND ContentID = '$content_id'";
            }
            $checkQuery .= " AND name = '$originalFileName'";
            
            $checkResult = mysqli_query($conn, $checkQuery);

            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                $row = mysqli_fetch_assoc($checkResult);
                if ($row['Status'] == 1) {
                    $response['error'] = "File '$originalFileName' is already submitted and cannot be resubmitted.";
                    continue;
                }
            }

            // GitHub Repository Details
            $githubRepo = "docmap2024/DocMaP";
            $branch = "main";
            $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/Documents/$fileName";
        
            // Fetch GitHub Token from Environment Variables
            $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
            if (!$githubToken) {
                $response['error'] = "GitHub token not found in environment variables";
                write_log("GitHub token not found in environment variables");
                continue;
            }
        
            // Read file content directly from tmp location
            $fileContent = file_get_contents($fileTmpName);
            if ($fileContent === false) {
                $response['error'] = "Failed to read file content: $fileName";
                continue;
            }
            
            // Prepare File Data for GitHub
            $content = base64_encode($fileContent);
            $data = json_encode([
                "message" => "Adding a new file to upload folder",
                "content" => $content,
                "branch" => $branch
            ]);
        
            $headers = [
                "Authorization: token $githubToken",
                "Content-Type: application/json",
                "User-Agent: DocMaP"
            ];
        
            // GitHub API Call
            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
            $githubResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        
            if ($githubResponse === false) {
                write_log("GitHub upload failed for file: $fileName");
                $response['error'] = "GitHub upload failed for file: $fileName";
                continue;
            }
            
            $responseData = json_decode($githubResponse, true);
            if ($httpCode != 201) {
                write_log("GitHub upload failed for file: $fileName - HTTP Code: $httpCode");
                $response['error'] = "GitHub upload failed for file: $fileName";
                continue;
            }
            
            // Successful upload
            $githubDownloadUrl = $responseData['content']['download_url'];
            write_log("File uploaded to GitHub: $fileName, Download URL: $githubDownloadUrl");
            
            $gradeLevelFolderID = null;
            $userFolderID = null;
            
            if ($content_id) {
                // Retrieve GradeLevelFolderID
                $gradeLevelQuery = "SELECT GradeLevelFolderID FROM gradelevelfolders WHERE ContentID = '$content_id' LIMIT 1";
                $gradeLevelResult = mysqli_query($conn, $gradeLevelQuery);
                if ($gradeLevelResult && mysqli_num_rows($gradeLevelResult) > 0) {
                    $gradeLevelRow = mysqli_fetch_assoc($gradeLevelResult);
                    $gradeLevelFolderID = $gradeLevelRow['GradeLevelFolderID'];
                }

                // Fetch UserFolderID
                $getUserContentQuery = "SELECT UserContentID FROM usercontent WHERE UserID = '$user_id' AND ContentID = '$content_id'";
                $getUserContentResult = mysqli_query($conn, $getUserContentQuery);
                if ($getUserContentResult && mysqli_num_rows($getUserContentResult) > 0) {
                    $userContentRow = mysqli_fetch_assoc($getUserContentResult);
                    $userContentID = $userContentRow['UserContentID'];

                    $getUserFolderQuery = "SELECT UserFolderID FROM userfolders WHERE UserContentID = '$userContentID'";
                    $getUserFolderResult = mysqli_query($conn, $getUserFolderQuery);
                    if ($getUserFolderResult && mysqli_num_rows($getUserFolderResult) > 0) {
                        $userFolderRow = mysqli_fetch_assoc($getUserFolderResult);
                        $userFolderID = $userFolderRow['UserFolderID'];
                    }
                }
            }

            // Insert or update file info
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                $fileID = $row['DocuID'];
                $updateQuery = "UPDATE documents SET Status = 1, uri = '$githubDownloadUrl', size = '$fileSize', mimeType = '$fileType' WHERE DocuID = '$fileID'";
                if (!mysqli_query($conn, $updateQuery)) {
                    $response['error'] = "Error resubmitting file: " . mysqli_error($conn);
                }
            } else {
                $sql = "INSERT INTO documents (UserID, TaskID, name, uri, mimeType, size, Status, TimeStamp";
                $values = "'$user_id', '$task_id', '$fileName', '$githubDownloadUrl', '$fileType', '$fileSize', 1, NOW()";
                
                if ($content_id) $sql .= ", ContentID"; $values .= ", '$content_id'";
                if ($gradeLevelFolderID) { $sql .= ", GradeLevelFolderID"; $values .= ", '$gradeLevelFolderID'"; }
                if ($userFolderID) { $sql .= ", UserFolderID"; $values .= ", '$userFolderID'"; }
                
                $sql .= ") VALUES ($values)";
                
                if (!mysqli_query($conn, $sql)) {
                    $response['error'] = "Error inserting new file info: " . mysqli_error($conn);
                }
            }
        }
    }

    // Update task_user status
    $updateTaskUserQuery = "UPDATE task_user SET Status = 'Submitted', SubmitDate = NOW() WHERE TaskID = ? AND UserID = ? AND Status != 'Submitted'";
    $updateTaskUserStmt = $conn->prepare($updateTaskUserQuery);
    $updateTaskUserStmt->bind_param("ii", $task_id, $user_id);
    $updateTaskUserStmt->execute();

    if ($updateTaskUserStmt->affected_rows > 0 && $content_id) {
        // Fetch task and user info for notifications
        $taskTitleQuery = "SELECT Title FROM tasks WHERE TaskID = ?";
        $taskTitleStmt = $conn->prepare($taskTitleQuery);
        $taskTitleStmt->bind_param("i", $task_id);
        $taskTitleStmt->execute();
        $taskTitleResult = $taskTitleStmt->get_result();
        
        $userQuery = "SELECT fname, lname FROM useracc WHERE UserID = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $user_id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($taskTitleResult->num_rows > 0 && $userResult->num_rows > 0) {
            $taskTitle = $taskTitleResult->fetch_assoc()['Title'];
            $userRow = $userResult->fetch_assoc();
            $fullName = $userRow['fname'] . ' ' . $userRow['lname'];
            
            $title = "$fullName has submitted a task!";
            $content = "A task has been submitted for \"$taskTitle\".";
            
            $notifID = createNotification($conn, $user_id, $content_id, $task_id, $title, $content);
            
            if ($notifID) {
                $deptQuery = "SELECT dept_ID FROM feedcontent WHERE ContentID = ?";
                $deptStmt = $conn->prepare($deptQuery);
                $deptStmt->bind_param("i", $content_id);
                $deptStmt->execute();
                $deptResult = $deptStmt->get_result();
                
                if ($deptResult->num_rows > 0) {
                    $dept_ID = $deptResult->fetch_assoc()['dept_ID'];
                    
                    // Notify Department Head
                    $department_head_user_id = getDepartmentHeadUserID($conn, $dept_ID);
                    if ($department_head_user_id) {
                        $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, 1, NOW())");
                        $notifUserStmt->bind_param("ii", $notifID, $department_head_user_id);
                        $notifUserStmt->execute();
                    }
                    
                    // Notify Admin
                    $admin_user_id = getAdminUserID($conn);
                    if ($admin_user_id) {
                        $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, 1, NOW())");
                        $notifUserStmt->bind_param("ii", $notifID, $admin_user_id);
                        $notifUserStmt->execute();
                    }
                }
            }
        }
    }
    
    $response['success'] = true;
} else {
    $response['error'] = "Task ID is required.";
}

echo json_encode($response);
exit();
?>