<?php
session_start();

// Redirect to index.php if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$response = array('success' => false, 'error' => null);

if (isset($_POST['task_id']) && isset($_POST['content_id'])) {
    $task_id = $_POST['task_id'];
    $content_id = $_POST['content_id'];
    $user_id = $_SESSION['user_id'];

    // Update document status to 'Submitted' if not already submitted
    $updateStatusQuery = "UPDATE documents SET Status = 1 WHERE UserID = '$user_id' AND TaskID = '$task_id' AND ContentID = '$content_id' AND Status != 1";
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
            $fileDestination = $uploadDirectory . $fileName;
            $fileSize = $_FILES['files']['size'][$key];
            $fileType = $_FILES['files']['type'][$key];

            // Check if the file is already in database with submitted status
            $checkQuery = "SELECT DocuID, Status FROM documents WHERE UserID = '$user_id' AND TaskID = '$task_id' AND ContentID = '$content_id' AND name = '$originalFileName'";
            $checkResult = mysqli_query($conn, $checkQuery);

            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                // If file exists and is already submitted, skip further action for this file
                $row = mysqli_fetch_assoc($checkResult);
                if ($row['Status'] == 1) {
                    $response['error'] = "File '$originalFileName' is already submitted and cannot be resubmitted.";
                    continue;
                }
            }

            // Move uploaded file to destination
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
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
                    $sql = "INSERT INTO documents (GradeLevelFolderID, UserFolderID, UserID, ContentID, TaskID, name, uri, mimeType, size, Status, TimeStamp) 
                            VALUES ('$gradeLevelFolderID', '$userFolderID', '$user_id', '$content_id', '$task_id', '$fileName', '$fileDestination', '$fileType', '$fileSize', 1, NOW())";
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
    }
} else {
    $response['error'] = "Task ID and Content ID are required.";
}

echo json_encode($response);
exit();
?>
