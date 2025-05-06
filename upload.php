<?php
session_start();
include 'connection.php';

$response = array('success' => false, 'error' => null);

// Log helper
function write_log($message) {
    $logfile = '/tmp/logfile.log';
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

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

    // Handle file upload
    if (!empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['name'] as $key => $name) {
            $tmpPath = $_FILES['files']['tmp_name'][$key];
            $originalFileName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $name);
            $uniqueFileName = sprintf('%06d_%s', random_int(100000, 999999), $originalFileName);

            if ($_FILES['files']['size'][$key] > 5 * 1024 * 1024) {
                $response['error'] = "File too large: $name (max 5MB)";
                echo json_encode($response);
                exit();
            }

            $fileContent = file_get_contents($tmpPath);
            if ($fileContent === false) {
                write_log("Failed to read file: $originalFileName");
                $response['error'] = "Failed to read uploaded file.";
                echo json_encode($response);
                exit();
            }

            // === GitHub Upload ===
            $repo = "docmap2024/DocMaP";
            $branch = "main";
            $uploadPath = "Documents/" . $uniqueFileName;
            $uploadUrl = "https://api.github.com/repos/$repo/contents/$uploadPath";

            write_log("Uploading to: $uploadUrl");

            $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
            if (!$githubToken) {
                write_log("Missing GitHub token");
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
                write_log("GitHub error: $curlError - $responseGitHub");
                $response['error'] = "GitHub upload failed.";
                echo json_encode($response);
                exit();
            }

            $ghData = json_decode($responseGitHub, true);
            $downloadUrl = $ghData['content']['download_url'] ?? '';

            // Retrieve GradeLevelFolderID
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

            // Get UserContentID
            $getUserContentQuery = "SELECT UserContentID FROM usercontent WHERE UserID = '$user_id' AND ContentID = '$content_id'";
            $getUserContentResult = mysqli_query($conn, $getUserContentQuery);

            if ($getUserContentResult && mysqli_num_rows($getUserContentResult) > 0) {
                $userContentRow = mysqli_fetch_assoc($getUserContentResult);
                $userContentID = $userContentRow['UserContentID'];

                // Get UserFolderID
                $getUserFolderQuery = "SELECT UserFolderID FROM userfolders WHERE UserContentID = '$userContentID'";
                $getUserFolderResult = mysqli_query($conn, $getUserFolderQuery);

                if ($getUserFolderResult && mysqli_num_rows($getUserFolderResult) > 0) {
                    $userFolderRow = mysqli_fetch_assoc($getUserFolderResult);
                    $userFolderID = $userFolderRow['UserFolderID'];

                    // Insert into documents
                    $insertQuery = "INSERT INTO documents 
                        (GradeLevelFolderID, UserFolderID, UserID, ContentID, TaskID, name, uri, mimeType, size, Status, TimeStamp) 
                        VALUES ('$gradeLevelFolderID', '$userFolderID', '$user_id', '$content_id', '$task_id', '$uniqueFileName', '$downloadUrl', 'application/octet-stream', 0, 1, NOW())";
                    $insertResult = mysqli_query($conn, $insertQuery);

                    if (!$insertResult) {
                        $response['error'] = "Database insert failed: " . mysqli_error($conn);
                        echo json_encode($response);
                        exit();
                    }

                    $response['success'] = true;

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
    }

    // Update task_user status
    $updateTaskUserQuery = "UPDATE task_user SET Status = 'Submitted', SubmitDate = NOW() WHERE TaskID = '$task_id' AND UserID = '$user_id' AND Status != 'Submitted'";
    $updateTaskUserResult = mysqli_query($conn, $updateTaskUserQuery);
}

echo json_encode($response);
exit();
?>
