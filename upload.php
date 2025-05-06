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
    // Fetch the UserID of the Department Head for the department
    $headQuery = "SELECT UserID FROM useracc WHERE dept_ID = ? AND role = 'Department Head'";
    $headStmt = $conn->prepare($headQuery);
    $headStmt->bind_param("i", $dept_ID);
    $headStmt->execute();
    $headResult = $headStmt->get_result();

    if ($headResult && $headResult->num_rows > 0) {
        $headRow = $headResult->fetch_assoc();
        return $headRow['UserID']; // Return the Department Head's UserID
    }

    return false; // Return false if no department head is found
}

function getAdminUserID($conn) {
    // Fetch the UserID of the Admin (assuming role = 'Admin')
    $adminQuery = "SELECT UserID FROM useracc WHERE role = 'Admin' LIMIT 1";
    $adminResult = mysqli_query($conn, $adminQuery);

    if ($adminResult && mysqli_num_rows($adminResult) > 0) {
        $adminRow = mysqli_fetch_assoc($adminResult);
        return $adminRow['UserID']; // Return the Admin's UserID
    }

    return false; // Return false if no admin is found
}

function write_log($message) {
    // Implement your logging function here
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
        $uploadDirectory = 'Documents/';

        foreach ($_FILES['files']['name'] as $key => $name) {
            $fileTmpName = $_FILES['files']['tmp_name'][$key];
            $originalFileName = $name;

            // Generate unique file name
            $rd2 = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $fileName = $rd2 . '_' . $originalFileName;
            $target_file = $uploadDirectory . $fileName;
            $fileSize = $_FILES['files']['size'][$key];
            $fileType = $_FILES['files']['type'][$key];

            // Check if the file is already in database with submitted status
            $checkQuery = "SELECT DocuID, Status FROM documents WHERE UserID = '$user_id' AND TaskID = '$task_id'";
            if ($content_id) {
                $checkQuery .= " AND ContentID = '$content_id'";
            }
            $checkQuery .= " AND name = '$originalFileName'";
            
            $checkResult = mysqli_query($conn, $checkQuery);

            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                // If file exists and is already submitted, skip further action for this file
                $row = mysqli_fetch_assoc($checkResult);
                if ($row['Status'] == 1) {
                    $response['error'] = "File '$originalFileName' is already submitted and cannot be resubmitted.";
                    continue;
                }
            }

            // Move uploaded file to temporary local destination
            if (move_uploaded_file($fileTmpName, $target_file)) {
                // GitHub Repository Details
                $githubRepo = "docmap2024/DocMaP"; // GitHub username/repo
                $branch = "main";
                $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/Admin/Attachments/$fileName";
            
                // Fetch GitHub Token from Environment Variables
                $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
                if (!$githubToken) {
                    $response['error'] = "GitHub token not found in environment variables";
                    write_log("GitHub token not found in environment variables");
                    continue;
                }
            
                // Prepare File Data for GitHub
                $content = base64_encode(file_get_contents($target_file));
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
            
                if ($githubResponse === false) {
                    write_log("GitHub upload failed for file: $fileName - " . curl_error($ch));
                    $response['error'] = "GitHub upload failed for file: $fileName";
                    curl_close($ch);
                    continue;
                } else {
                    $responseData = json_decode($githubResponse, true);
                    if ($httpCode == 201) { // Successful upload
                        $githubDownloadUrl = $responseData['content']['download_url'];
                        write_log("File uploaded to GitHub: $fileName, Download URL: $githubDownloadUrl");
            
                        // Delete the local file after successful GitHub upload
                        unlink($target_file);
                        
                        // Use GitHub URL as the file destination
                        $fileDestination = $githubDownloadUrl;
                    } else {
                        write_log("GitHub upload failed for file: $fileName - HTTP Code: $httpCode");
                        $response['error'] = "GitHub upload failed for file: $fileName";
                        curl_close($ch);
                        continue;
                    }
                }
                curl_close($ch);

                $gradeLevelFolderID = null;
                $userFolderID = null;
                
                // Only retrieve these IDs if content_id is provided
                if ($content_id) {
                    // Retrieve GradeLevelFolderID using ContentID
                    $gradeLevelQuery = "SELECT GradeLevelFolderID FROM gradelevelfolders WHERE ContentID = '$content_id' LIMIT 1";
                    $gradeLevelResult = mysqli_query($conn, $gradeLevelQuery);

                    if ($gradeLevelResult && mysqli_num_rows($gradeLevelResult) > 0) {
                        $gradeLevelRow = mysqli_fetch_assoc($gradeLevelResult);
                        $gradeLevelFolderID = $gradeLevelRow['GradeLevelFolderID'];
                    } else {
                        $response['error'] = "Error retrieving GradeLevelFolderID.";
                        echo json_encode($response);
                        exit();
                    }

                    // Fetch UserFolderID by first getting UserContentID from usercontent table using ContentID
                    $getUserContentQuery = "SELECT UserContentID FROM usercontent WHERE UserID = '$user_id' AND ContentID = '$content_id'";
                    $getUserContentResult = mysqli_query($conn, $getUserContentQuery);

                    if ($getUserContentResult && mysqli_num_rows($getUserContentResult) > 0) {
                        $userContentRow = mysqli_fetch_assoc($getUserContentResult);
                        $userContentID = $userContentRow['UserContentID'];

                        // Retrieve UserFolderID using UserContentID from userfolders table
                        $getUserFolderQuery = "SELECT UserFolderID FROM userfolders WHERE UserContentID = '$userContentID'";
                        $getUserFolderResult = mysqli_query($conn, $getUserFolderQuery);

                        if ($getUserFolderResult && mysqli_num_rows($getUserFolderResult) > 0) {
                            $userFolderRow = mysqli_fetch_assoc($getUserFolderResult);
                            $userFolderID = $userFolderRow['UserFolderID'];
                        } else {
                            $response['error'] = "Error retrieving UserFolderID.";
                            echo json_encode($response);
                            exit();
                        }
                    } else {
                        $response['error'] = "Error retrieving UserContentID.";
                        echo json_encode($response);
                        exit();
                    }
                }

                // Insert new file or update the existing one
                if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                    // Update file info if it exists
                    $fileID = $row['DocuID'];
                    $updateQuery = "UPDATE documents SET Status = 1, uri = '$fileDestination', size = '$fileSize', mimeType = '$fileType' WHERE DocuID = '$fileID'";
                    if (mysqli_query($conn, $updateQuery)) {
                        $response['success'] = true; // Resubmission successful
                    } else {
                        $response['error'] = "Error resubmitting file: " . mysqli_error($conn);
                    }
                } else {
                    // Insert new file info
                    $sql = "INSERT INTO documents (UserID, TaskID, name, uri, mimeType, size, Status, TimeStamp";
                    $values = "'$user_id', '$task_id', '$fileName', '$fileDestination', '$fileType', '$fileSize', 1, NOW()";
                    
                    if ($content_id) {
                        $sql .= ", ContentID";
                        $values .= ", '$content_id'";
                    }
                    if ($gradeLevelFolderID) {
                        $sql .= ", GradeLevelFolderID";
                        $values .= ", '$gradeLevelFolderID'";
                    }
                    if ($userFolderID) {
                        $sql .= ", UserFolderID";
                        $values .= ", '$userFolderID'";
                    }
                    
                    $sql .= ") VALUES ($values)";
                    
                    if (mysqli_query($conn, $sql)) {
                        $response['success'] = true; // New file inserted successfully
                    } else {
                        $response['error'] = "Error inserting new file info into database: " . mysqli_error($conn);
                    }
                }
            } else {
                $response['error'] = "Failed to upload file: $name";
            }
        }
    }

    // Update task_user status to 'Submitted' only if it hasn't been updated
    $updateTaskUserQuery = "UPDATE task_user SET Status = 'Submitted', SubmitDate = NOW() WHERE TaskID = ? AND UserID = ? AND Status != 'Submitted'";
    $updateTaskUserStmt = $conn->prepare($updateTaskUserQuery);
    $updateTaskUserStmt->bind_param("ii", $task_id, $user_id);

    if (!$updateTaskUserStmt->execute()) {
        $response['error'] = "Error updating task_user status: " . mysqli_error($conn);
    } else {
        $response['success'] = true;

        // Only create notifications if content_id is provided
        if ($content_id) {
            // Fetch Task Title
            $taskTitleQuery = "SELECT Title FROM tasks WHERE TaskID = ?";
            $taskTitleStmt = $conn->prepare($taskTitleQuery);
            $taskTitleStmt->bind_param("i", $task_id);
            $taskTitleStmt->execute();
            $taskTitleResult = $taskTitleStmt->get_result();

            if ($taskTitleResult && $taskTitleResult->num_rows > 0) {
                $taskTitleRow = $taskTitleResult->fetch_assoc();
                $taskTitle = $taskTitleRow['Title'];
            } else {
                $response['error'] = "Error fetching Task Title.";
                echo json_encode($response);
                exit();
            }

            // Fetch Full Name of the user
            $userQuery = "SELECT fname, lname FROM useracc WHERE UserID = ?";
            $userStmt = $conn->prepare($userQuery);
            $userStmt->bind_param("i", $user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();

            if ($userResult && $userResult->num_rows > 0) {
                $userRow = $userResult->fetch_assoc();
                $fullName = $userRow['fname'] . ' ' . $userRow['lname'];
            } else {
                $response['error'] = "Error fetching user's full name.";
                echo json_encode($response);
                exit();
            }

            // Create notification
            $title = "$fullName has submitted a task!\n\n";
            $content = "A task has been submitted for \"$taskTitle\".";
            
            $notifID = createNotification($conn, $user_id, $content_id, $task_id, $title, $content);

            if ($notifID) {
                // Fetch the Department ID (dept_ID) based on the content_id or task_id
                // Assuming dept_ID is associated with the content_id in the `content` table
                $deptQuery = "SELECT dept_ID FROM feedcontent WHERE ContentID = ?";
                $deptStmt = $conn->prepare($deptQuery);
                $deptStmt->bind_param("i", $content_id);
                $deptStmt->execute();
                $deptResult = $deptStmt->get_result();

                if ($deptResult && $deptResult->num_rows > 0) {
                    $deptRow = $deptResult->fetch_assoc();
                    $dept_ID = $deptRow['dept_ID'];

                    // Debug: Log the dept_ID of the content
                    error_log("Content's dept_ID: " . $dept_ID);

                    // Fetch the Department Head's UserID
                    $department_head_user_id = getDepartmentHeadUserID($conn, $dept_ID);

                    // Debug: Log the Department Head's UserID
                    if ($department_head_user_id) {
                        error_log("Department Head UserID found: " . $department_head_user_id);
                    } else {
                        error_log("No Department Head found for dept_ID: " . $dept_ID);
                    }

                    // Fetch the Admin's UserID
                    $admin_user_id = getAdminUserID($conn);

                    // Debug: Log the Admin's UserID
                    if ($admin_user_id) {
                        error_log("Admin UserID found: " . $admin_user_id);
                    } else {
                        error_log("No Admin found.");
                    }

                    // Insert into notif_user table for Department Head
                    if ($department_head_user_id) {
                        $status = 1; // Assuming 0 means unread
                        $timestamp = date("Y-m-d H:i:s");

                        $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                        $notifUserStmt->bind_param("iiss", $notifID, $department_head_user_id, $status, $timestamp);

                        if (!$notifUserStmt->execute()) {
                            $response['error'] = "Error inserting into notif_user for Department Head: " . $notifUserStmt->error;
                            error_log("Error inserting into notif_user for Department Head: " . $notifUserStmt->error); // Debug: Log the error
                        } else {
                            error_log("Notification created for Department Head: UserID " . $department_head_user_id); // Debug: Log success
                        }
                        $notifUserStmt->close();
                    } else {
                        $response['error'] = "No Department Head found for the content's department.";
                        error_log("No Department Head found for the content's department."); // Debug: Log the error
                    }

                    // Insert into notif_user table for Admin
                    if ($admin_user_id) {
                        $status = 1; // Assuming 0 means unread
                        $timestamp = date("Y-m-d H:i:s");

                        $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                        $notifUserStmt->bind_param("iiss", $notifID, $admin_user_id, $status, $timestamp);

                        if (!$notifUserStmt->execute()) {
                            $response['error'] = "Error inserting into notif_user for Admin: " . $notifUserStmt->error;
                            error_log("Error inserting into notif_user for Admin: " . $notifUserStmt->error); // Debug: Log the error
                        } else {
                            error_log("Notification created for Admin: UserID " . $admin_user_id); // Debug: Log success
                        }
                        $notifUserStmt->close();
                    }

                    if (!$department_head_user_id && !$admin_user_id) {
                        $response['error'] = "No Department Head or Admin found.";
                        error_log("No Department Head or Admin found."); // Debug: Log the error
                    }
                } else {
                    $response['error'] = "Error fetching content's department.";
                    error_log("Error fetching content's department."); // Debug: Log the error
                }
            } else {
                $response['error'] = "Error creating notification.";
                error_log("Error creating notification."); // Debug: Log the error
            }
        }
    }
} else {
    $response['error'] = "Task ID is required.";
}

echo json_encode($response);
exit();
?>