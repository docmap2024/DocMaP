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
    // Allow NULL content_id in the notification
    $sql = "INSERT INTO notifications (UserID, ContentID, TaskID, Title, Content, Status, TimeStamp) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())";
    $stmt = $conn->prepare($sql);
    
    // Use different bind_param based on whether content_id is null
    if ($content_id !== null) {
        $stmt->bind_param("iiiss", $user_id, $content_id, $task_id, $title, $content);
    } else {
        $stmt->bind_param("iisss", $user_id, $task_id, $title, $content);
        // Note: The parameter count is reduced by 1 when content_id is null
    }

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

if (isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $content_id = isset($_POST['content_id']) ? $_POST['content_id'] : null;
    $user_id = $_SESSION['user_id'];

    // Always update document status, handling both cases (with and without content_id)
    if ($content_id !== null) {
        $updateStatusQuery = "UPDATE documents SET Status = 1 WHERE UserID = ? AND TaskID = ? AND ContentID = ?";
        $stmt = $conn->prepare($updateStatusQuery);
        $stmt->bind_param("iii", $user_id, $task_id, $content_id);
    } else {
        $updateStatusQuery = "UPDATE documents SET Status = 1 WHERE UserID = ? AND TaskID = ? AND ContentID IS NULL";
        $stmt = $conn->prepare($updateStatusQuery);
        $stmt->bind_param("ii", $user_id, $task_id);
    }

    if (!$stmt->execute()) {
        $response['error'] = "Error updating document status: " . $stmt->error;
        echo json_encode($response);
        exit();
    }

    // Process file uploads only if new files are provided
    if (!empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['name'] as $key => $name) {
            $tmpPath = $_FILES['files']['tmp_name'][$key];
            $originalFileName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $name);
            
            // Check if this exact file already exists in the database
            $checkQuery = "SELECT DocuID, uri FROM documents 
                          WHERE UserID = ? AND TaskID = ? " . 
                          ($content_id !== null ? "AND ContentID = ? " : "AND ContentID IS NULL ") . 
                          "AND name LIKE ?";
            $stmt = $conn->prepare($checkQuery);
            $searchPattern = '%' . $originalFileName;
            if ($content_id !== null) {
                $stmt->bind_param("iiis", $user_id, $task_id, $content_id, $searchPattern);
            } else {
                $stmt->bind_param("iis", $user_id, $task_id, $searchPattern);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $existingFile = $result->fetch_assoc();
            $stmt->close();

            if ($existingFile) {
                // File exists, just update the status without re-uploading
                $updateQuery = "UPDATE documents SET Status = 1 WHERE DocuID = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("i", $existingFile['DocuID']);
                if (!$stmt->execute()) {
                    $response['error'] = "Error updating existing file status: " . $stmt->error;
                    echo json_encode($response);
                    exit();
                }
                continue; // Skip to next file
            }

            // Proceed with new file upload
            $uniqueFileName = sprintf('%06d_%s', random_int(100000, 999999), $originalFileName);

            // Size validation (max 5MB)
            if ($_FILES['files']['size'][$key] > 5 * 1024 * 1024) {
                $response['error'] = "File too large: $name (max 5MB)";
                echo json_encode($response);
                exit();
            }

            $fileContent = file_get_contents($tmpPath);
            if ($fileContent === false) {
                $response['error'] = "Failed to read uploaded file.";
                echo json_encode($response);
                exit();
            }

            // === GitHub Upload ===
            $repo = "docmap2024/DocMaP";
            $branch = "main";
            $uploadPath = "Documents/" . $uniqueFileName;
            $uploadUrl = "https://api.github.com/repos/$repo/contents/$uploadPath";

            $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
            if (!$githubToken) {
                $response['error'] = "Server configuration error.";
                echo json_encode($response);
                exit();
            }

            $payload = json_encode([
                "message" => "Upload document: $uniqueFileName",
                "content" => base64_encode($fileContent),
                "branch" => $branch
            ]);

            $headers = [
                "Authorization: token $githubToken",
                "Content-Type: application/json",
                "User-Agent: DocMaP"
            ];

            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $responseGitHub = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($responseGitHub === false || $httpCode != 201) {
                $response['error'] = "GitHub upload failed.";
                echo json_encode($response);
                exit();
            }

            $ghData = json_decode($responseGitHub, true);
            $downloadUrl = $ghData['content']['download_url'] ?? '';

            // === Insert new document ===
            if ($content_id !== null) {
                // For tasks with content_id, get the folder IDs
                $gradeLevelID = null;
                $gradeQuery = "SELECT GradeLevelFolderID FROM gradelevelfolders WHERE ContentID = ? LIMIT 1";
                $stmt = $conn->prepare($gradeQuery);
                $stmt->bind_param("i", $content_id);
                $stmt->execute();
                $stmt->bind_result($gradeLevelID);
                $stmt->fetch();
                $stmt->close();

                if (!$gradeLevelID) {
                    $response['error'] = "GradeLevelFolderID not found.";
                    echo json_encode($response);
                    exit();
                }

                $userContentID = null;
                $stmt = $conn->prepare("SELECT UserContentID FROM usercontent WHERE UserID = ? AND ContentID = ?");
                $stmt->bind_param("ii", $user_id, $content_id);
                $stmt->execute();
                $stmt->bind_result($userContentID);
                $stmt->fetch();
                $stmt->close();

                if (!$userContentID) {
                    $response['error'] = "UserContentID not found.";
                    echo json_encode($response);
                    exit();
                }

                $userFolderID = null;
                $stmt = $conn->prepare("SELECT UserFolderID FROM userfolders WHERE UserContentID = ?");
                $stmt->bind_param("i", $userContentID);
                $stmt->execute();
                $stmt->bind_result($userFolderID);
                $stmt->fetch();
                $stmt->close();

                if (!$userFolderID) {
                    $response['error'] = "UserFolderID not found.";
                    echo json_encode($response);
                    exit();
                }

                // Insert with all folder IDs
                $stmt = $conn->prepare("INSERT INTO documents 
                    (GradeLevelFolderID, UserFolderID, UserID, ContentID, TaskID, name, uri, mimeType, size, Status, TimeStamp) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'application/octet-stream', 0, 1, NOW())");
                $stmt->bind_param("iiiiiss", $gradeLevelID, $userFolderID, $user_id, $content_id, $task_id, $uniqueFileName, $downloadUrl);
            } else {
                // For administrative tasks (no content_id), insert without folder IDs
                $stmt = $conn->prepare("INSERT INTO documents 
                    (UserID, TaskID, name, uri, mimeType, size, Status, TimeStamp) 
                    VALUES (?, ?, ?, ?, 'application/octet-stream', 0, 1, NOW())");
                $stmt->bind_param("iiss", $user_id, $task_id, $uniqueFileName, $downloadUrl);
            }
            
            if (!$stmt->execute()) {
                $response['error'] = "Database insert failed: " . $stmt->error;
                echo json_encode($response);
                exit();
            }
        }
    }

    // Update task_user status
    $updateTaskUser = $conn->prepare("UPDATE task_user SET Status = 'Submitted', SubmitDate = NOW() WHERE TaskID = ? AND UserID = ?");
    $updateTaskUser->bind_param("ii", $task_id, $user_id);
    $updateTaskUser->execute();

    // Always create notifications regardless of content_id
    // Fetch Task Title for notification
    $taskTitleQuery = "SELECT Title FROM tasks WHERE TaskID = ?";
    $taskTitleStmt = $conn->prepare($taskTitleQuery);
    $taskTitleStmt->bind_param("i", $task_id);
    $taskTitleStmt->execute();
    $taskTitleResult = $taskTitleStmt->get_result();

    if ($taskTitleResult && $taskTitleResult->num_rows > 0) {
        $taskTitleRow = $taskTitleResult->fetch_assoc();
        $taskTitle = $taskTitleRow['Title'];

        // Fetch user's full name for notification
        $userQuery = "SELECT fname, lname FROM useracc WHERE UserID = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $user_id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();

        if ($userResult && $userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            $fullName = $userRow['fname'] . ' ' . $userRow['lname'];

            // Create notification
            $title = "$fullName has submitted a task!";
            $content = "A task has been submitted for \"$taskTitle\".";
            
            $notifID = createNotification($conn, $user_id, $content_id, $task_id, $title, $content);

            if ($notifID) {
                // Only try to notify department head if content_id is provided
                if ($content_id !== null) {
                    // Fetch department and notify department head and admin
                    $deptQuery = "SELECT dept_ID FROM feedcontent WHERE ContentID = ?";
                    $deptStmt = $conn->prepare($deptQuery);
                    $deptStmt->bind_param("i", $content_id);
                    $deptStmt->execute();
                    $deptResult = $deptStmt->get_result();

                    if ($deptResult && $deptResult->num_rows > 0) {
                        $deptRow = $deptResult->fetch_assoc();
                        $dept_ID = $deptRow['dept_ID'];

                        // Notify department head
                        $department_head_user_id = getDepartmentHeadUserID($conn, $dept_ID);
                        if ($department_head_user_id) {
                            $status = 1;
                            $timestamp = date("Y-m-d H:i:s");
                            $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                            $notifUserStmt->bind_param("iiss", $notifID, $department_head_user_id, $status, $timestamp);
                            $notifUserStmt->execute();
                            $notifUserStmt->close();
                        }
                    }
                }

                // Always notify admin regardless of content_id
                $admin_user_id = getAdminUserID($conn);
                if ($admin_user_id) {
                    $status = 1;
                    $timestamp = date("Y-m-d H:i:s");
                    $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                    $notifUserStmt->bind_param("iiss", $notifID, $admin_user_id, $status, $timestamp);
                    $notifUserStmt->execute();
                    $notifUserStmt->close();
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